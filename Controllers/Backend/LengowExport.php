<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryExpressionVisitor;

class Shopware_Controllers_Backend_LengowExport extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Event listener function of articles store to list Lengow products
     *
     * @return mixed
     */
    public function getlistAction()
    {
        $treeId         = $this->Request()->getParam('categoryId');
        $filterParams   = $this->Request()->getParam('filter', array());
        $filterBy       = $this->Request()->getParam('filterBy');
        $order          = $this->Request()->getParam('sort', null);
        $start          = $this->Request()->getParam('start', 0);
        $limit          = $this->Request()->getParam('limit', 20);

        $categoryId = $treeId;
        $shopId = $treeId;
        $ids = explode('_', $treeId);
        $isShopSelected = true;

        if (count($ids) > 1) {
            $isShopSelected = false;
            $shopId = $ids[0];
            $categoryId = $ids[1];
        }

        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);

        $filters = array();
        foreach ($filterParams as $singleFilter) {
            $filters[$singleFilter['property']] = $singleFilter['value'];
        }

        $select = array(
            'attributes.id AS attributeId',
            'articles.id AS articleId',
            'articles.name AS name',
            'suppliers.name AS supplier',
            'articles.active AS status',
            'details.number AS number',
            'details.inStock',
            'CONCAT(tax.tax, \' %\') AS vat',
            'prices.price*(100+tax.tax)/100 AS price',
            'attributes.lengowShop' . $shopId . 'Active AS lengowActive'
        );

        $builder = $em->createQueryBuilder();
        $builder->select($select)
            ->from('Shopware\Models\Article\Detail', 'details')
            ->join('details.article', 'articles')
            ->join('articles.attribute', 'attributes')
            ->leftJoin('articles.supplier', 'suppliers')
            ->leftJoin('details.prices', 'prices')
            ->leftJoin('articles.tax', 'tax')
            ->where('prices.to = \'beliebig\'')
            ->andWhere('prices.customerGroupKey = \'EK\'')
            ->andWhere('details.kind = 1')
            ->andWhere('attributes.articleDetailId = details.id');

        // Search criteria
        if (isset($filters['search'])) {
            $searchFilter = '%' . $filters['search'] . '%';
            $condition = 'details.number LIKE :searchFilter OR ' .
                'articles.name LIKE :searchFilter OR ' .
                'suppliers.name LIKE :searchFilter';
            $builder->andWhere($condition)
                ->setParameter('searchFilter', $searchFilter);
        }

        if ($categoryId !== 'NaN' && $categoryId != null) {
            $mainCategory = null;
            if ($isShopSelected) {
                $mainCategory = $shop->getCategory();
            } else {
                $mainCategory = $em->getReference(
                        'Shopware\Models\Category\Category',
                        $categoryId
                );
            }

            // Construct where clause with selected category children 
            $where = $this->getAllCategoriesClause($mainCategory);

            $builder->leftJoin('articles.categories', 'categories')
                ->innerJoin('articles.allCategories', 'allCategories')
                ->andWhere($where);
        }

        // Make sure that whe don't get a cold here
        $columns = array(
            'number',
            'name',
            'supplier',
            'status',
            'price',
            'tax',
            'inStock',
            'lengowActive'
        );

        $directions = array('ASC', 'DESC');
        if (null === $order ||
            !in_array($order[0]['property'] , $columns) ||
            !in_array($order[0]['direction'], $directions)
        ) {
            $builder->orderBy('articles.id');
        } else {
            $order = array_shift($order);

            switch($order['property']) {
                case 'active':
                    $orderColumn = 'details.active';
                    break;
                case 'inStock':
                    $orderColumn = 'details.inStock';
                    break;
                case 'status':
                    $orderColumn = 'articles.active';
                    break;
                default:
                    $orderColumn = $order['property'];
                    break;
            }

            $builder->orderBy($orderColumn, $order['direction']);
        }

        $builder->distinct()
            ->addOrderBy('details.number');

        // Used to get number of products available/exported
        $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop);

        $totalProducts = count($builder->getQuery()->getArrayResult());
        $builder->setFirstResult($start)
            ->setMaxResults($limit);

        $result = $builder->getQuery()->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data'    => $result,
            'total'   => $totalProducts,
            'nbProductsAvailable' => $export->getTotalProducts(),
            'nbExportedProducts' => $export->getExportedProducts()
        ));
    }

    /**
     * Generate where clause used to list articles from a selected category
     * @param $selectedCategory Shopware\Models\Category\Category List of children of the selected category
     * @return string Exclusive clause which contains all sub-categories ids
     */
    private function getAllCategoriesClause($selectedCategory)
    {
        $children = $selectedCategory->getChildren();
        $where = 'categories.id = ' . $selectedCategory->getId();

        foreach ($children as $child) {
            if ($child->isLeaf()) {
                $where.= ' OR categories.id = ' . $child->getId();
            } else {
                $where.= ' OR ' . $this->getAllCategoriesClause($child);
            }
        }

        return $where;
    }

    /**
     * Event listener function of articles store to export a list of products
     * 
     * @return mixed
     */
    public function exportAction()
    {   
        $host = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getBaseUrl(); 
        $pathPlugin = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getPathPlugin();
        $exportUrl = $host . $pathPlugin . 'Webservice/export.php';

        $this->View()->assign(array(
            'success' => true,
            'url' => $exportUrl
        ));
    }

    /**
     * Set Lengow status for an article
     *
     */
    public function setStatusInLengowAction()
    {
        $articleIds = $this->Request()->getParam('ids');
        $status = $this->Request()->getParam('status', false);
        $categoriesIds = $this->Request()->getParam('categoryId');
        $active = $status == 'true' ? true : false;

        $shopId = $categoriesIds;
        // Tree is based on shopId_categoryId (except for shops)
        $articleCategory = explode('_', $categoriesIds);

        if (count($articleCategory) > 1) {
            $shopId = $articleCategory[0];
        }

        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();

        // If export all product for this shop (checkbox)
        if ($articleIds == '') {
            $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);
            $mainCategory = $shop->getCategory();
            $this->setLengowStatusFromCategory($mainCategory, $shopId, $active);
        } else {
            $attributeIds = json_decode($articleIds);

            foreach ($attributeIds as $id) {
                $attribute = $em->getReference('Shopware\Models\Attribute\Article', $id);

                if ($attribute) {
                    $column = 'setLengowShop' . $shopId . 'Active';
                    $attribute->$column($active);
                    $em->persist($attribute);
                    $em->flush($attribute);
                }
            }
        }
    }

    /**
     * Edit Lengow status for articles from a specific category
     * @param category int Category id the articles belong to
     * @param shopId int Shop id
     * @param status boolean Lengow status to set for articles
     */
    private function setLengowStatusFromCategory($category, $shopId, $status)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();

        // If not a parent category
        if ($category->isLeaf()) {
            $articles = $category->getArticles();
            foreach ($articles as $article) {
                $mainDetail = $article->getMainDetail();

                if ($mainDetail) {
                    $attribute = $article->getMainDetail()->getAttribute();
                } else {
                    $attribute = $article->getAttribute();
                }

                if ($attribute != null) {
                    $column = 'setLengowShop' . $shopId . 'Active';
                    $attribute->$column($status);
                    $em->persist($attribute);
                    $em->flush($attribute);
                }
            }
        } else {
            $children = $category->getChildren();
            foreach ($children as $child) {
                $this->setLengowStatusFromCategory($child, $shopId, $status);
            }
        }
    }

    /**
     * Get tree structure.
     * @param id Category id (shop id if a shop has been selected, else shopId_categoryId)
     */
    public function getShopsTreeAction()
    {
        $parentId = $this->Request()->getParam('id');

        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();

        $result = array();

        // If the root is selected, return list of enabled shops
        if ($parentId == 'root') {
            $shops = $em->getRepository('Shopware\Models\Shop\Shop')->findBy(array('active' => 1));

            foreach ($shops as $shop) {
                $mainCategory = $shop->getCategory();

                $result[] = array(
                    'leaf' => $mainCategory->isLeaf(),
                    'text' => $shop->getName(),
                    'id' => $shop->getId(),
                    'lengowStatus' => Shopware_Plugins_Backend_Lengow_Components_LengowCore::getConfigValue(
                        'lengowEnableShop',
                        $shop->getId()
                    )
                );
            }
        } else {
            // As tree requires a unique id, explode id to get the shop and the category id
            $ids = explode('_', $parentId);

            if (count($ids) > 1) {
                $shopId = $ids[0];
                $categoryId = $ids[1];
            } else {
                $shop = $em->getReference('Shopware\Models\Shop\Shop', $parentId);
                $shopId = $shop->getId();

                $categoryId = $shop->getCategory()->getId();
            }

            $category = $em->getReference('Shopware\Models\Category\Category', $categoryId);

            $categories = $category->getChildren();

            foreach ($categories as $category) {
                $result[] = array(
                    'leaf' => $category->isLeaf(),
                    'text' => $category->getName(),
                    'id' => $shopId . '_' . $category->getId() // Required to have a unique id in the tree
                );
            }
        }

        sort($result);

        $this->View()->assign(array(
            'success' => true,
            'data'    => $result
        ));
    }

    /**
     * Change Lengow shop settings (checkboxes above export grid)
     * @param id int Shop id
     * @param name String Setting name
     * @param status boolean New setting value
     */
    public function changeSettingsValueAction() 
    {
        $shopId = $this->Request()->getParam('id');
        $name = $this->Request()->getParam('name');
        $status = (int)($this->Request()->getParam('status') === 'true');

        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();

        if ($shopId != null) {
            $builder = $em->createQueryBuilder();
            $builder->select('values.id')
                ->from('Shopware\Models\Config\Value', 'values')
                ->leftJoin('values.element', 'elements')
                ->where('values.shopId = :shopId')
                ->andWhere('elements.name = :name')
                ->setParameter('shopId', $shopId)
                ->setParameter('name', $name);
            $result = $builder->getQuery()->getArrayResult();

            // If the config already exists, update the value
            if (!empty($result)) {
                $configId = $result[0]['id'];

                $config = $em->getReference('Shopware\Models\Config\Value', $configId);
                $config->setValue($status);
                $em->persist($config);
                $em->flush($config);
            } else {
                // If the config doesn't exist for the current shop, create it
                $config = $em->getRepository('Shopware\Models\Config\Element')->findOneBy(array('name' => $name));
                $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);
                $configValue = new Shopware\Models\Config\Value();
                $configValue->setElement($config);
                $configValue->setShop($shop);
                $config->setValue($status);
                $em->persist($configValue);
                $em->flush($configValue);
            }
        }
    }

    /**
     * Get config value for a shop from the database
     * @param id int Shop id
     * @param configList array List of settings to get
     */
    public function getConfigValueAction() 
    {
        $shopId = $this->Request()->getParam('id');
        $configList = $this->Request()->getParam('configList');

        $names = json_decode($configList);
        $result = array();

        foreach ($names as $name) {
            $result[$name] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getConfigValue($name, $shopId);
        }

        $this->View()->assign(array(
            'success' => true,
            'data'    => $result
        ));
    }

    /**
     * Get the default shop in Shopware
     * Used to display datas when launching Lengow
     */
    public function getDefaultShopAction()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $defaultShop = $em->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('default', 1));

        $this->View()->assign(array(
            'success' => true,
            'data'    => $defaultShop->getId()
        ));
    }
}