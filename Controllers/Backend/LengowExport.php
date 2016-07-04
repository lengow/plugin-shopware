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
        $isRootSelected = $categoryId == null ? true : false;

        if (count($ids) > 1) {
            $isShopSelected = false;
            $shopId = $ids[0];
            $categoryId = $ids[1];
        }

        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();

        $filters = array();
        foreach ($filterParams as $singleFilter) {
            $filters[$singleFilter['property']] = $singleFilter['value'];
        }

        $select = array(
            'attributes.id AS attributeId',
            'articles.name AS name',
            'suppliers.name AS supplier',
            'articles.active AS status',
            'details.number AS number',
            'details.inStock',
            'tax.tax AS vat',
            'prices.price*(100+tax.tax)/100 AS price'
        );

        // If a shop in the tree is selected, get articles status for this one
        if (!$isRootSelected) {
            $select[] = 'attributes.lengowShop' . $shopId . 'Active AS lengowActive';
        }

        $builder = $em->createQueryBuilder();
        $builder->select($select)
            ->from('Shopware\Models\Article\Article', 'articles')
            ->leftJoin('articles.mainDetail', 'details')
            ->leftJoin('articles.supplier', 'suppliers')
            ->leftJoin('articles.attribute', 'attributes')
            ->leftJoin('details.prices', 'prices')
            ->leftJoin('articles.tax', 'tax')
            ->where('prices.to = \'beliebig\'')
            ->andWhere('prices.customerGroupKey = \'EK\'')
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

        // In stock products only
        if ($export_out_stock) {
            $builder->andWhere('details.inStock > 0');
        }

        // Active product only
        if ($export_disabled_products) {
            $builder->andWhere('articles.active = 1');
        }

        if ($categoryId !== 'NaN' && $categoryId != null) {
            $mainCategory = null;
            if ($isShopSelected) {
                $shop = Shopware()->Models()->getReference(
                        'Shopware\Models\Shop\Shop',
                        $shopId
                );

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

        $builder->distinct();

        // Get number of products before setting offset and limit parameters
        $articles = $builder->getQuery()->getArrayResult();
        $numberOfProducts = count($articles);

        // Count articles that are exported
        $nbLengowProducts = 0;
        foreach ($articles as $article) {
            if ($article['lengowActive']) {
                $nbLengowProducts++;
            }
        }

        $builder->addOrderBy('details.number')
            ->setFirstResult($start)
            ->setMaxResults($limit);

        $result = $builder->getQuery()->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data'    => $result,
            'total'   => $numberOfProducts,
            'nbLengowProducts'   => $nbLengowProducts
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

        // If root category is selected, active/desactive the product for all shops
        if (count($articleCategory) > 1) {
            $shopId = $articleCategory[0];
        }

        $attributeIds = json_decode($articleIds);

        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();

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

    public function getShopsTreeAction()
    {
        $parentId = $this->Request()->getParam('id');

        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();

        $result = array();

        // If the root is selected, return list of enabled shops
        if ($parentId == 'root') {
            $shops = $em->getRepository('Shopware\Models\Shop\Shop')->findBy(array('active' => 1));

            foreach ($shops as $shop) {
                if ($shop->getActive()) {
                    $mainCategory = $shop->getCategory();

                    $result[] = array(
                        'leaf' => $mainCategory->isLeaf(),
                        'text' => $shop->getName(),
                        'id' => $shop->getId()
                    );
                }
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
}