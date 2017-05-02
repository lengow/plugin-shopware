<?php

use Doctrine\ORM\Query\Expr;

/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * It is available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/agpl-3.0
 *
 * @category    Lengow
 * @package     Lengow
 * @subpackage  Controllers
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Backend Lengow Export Controller
 */
class Shopware_Controllers_Backend_LengowExport extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Event listener function of articles store to list Lengow products
     */
    public function getListAction()
    {
        $treeId = $this->Request()->getParam('categoryId');
        $filterParams = $this->Request()->getParam('filter', array());
        $filterBy = $this->Request()->getParam('filterBy');
        $order = $this->Request()->getParam('sort', null);
        $start = $this->Request()->getParam('start', 0);
        $limit = $this->Request()->getParam('limit', 20);
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
        /** @var Shopware\Models\Shop\Shop $shop */
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
        // Filter category
        if ($filterBy == 'inStock') {
            $builder->andWhere('details.inStock > 0');
        } elseif ($filterBy == 'lengowProduct') {
            $builder->andWhere('attributes.lengowShop' . $shopId . 'Active = 1');
        } elseif ($filterBy == 'noCategory') {
            $builder->leftJoin('articles.allCategories', 'allCategories')
                ->andWhere('allCategories.id IS NULL');
        } elseif ($categoryId !== 'NaN' && $categoryId != null) {
            $mainCategory = null;
            if ($isShopSelected) {
                $mainCategory = $shop->getCategory();
            } else {
                $mainCategory = $em->getReference('Shopware\Models\Category\Category', $categoryId);
            }
            // Construct where clause with selected category children
            $where = $this->getAllCategoriesClause($mainCategory);
            $builder->leftJoin('articles.categories', 'categories')
                ->innerJoin('articles.allCategories', 'allCategories')
                ->andWhere($where);
        }
        // Search criteria
        if (isset($filters['search'])) {
            $searchFilter = '%' . $filters['search'] . '%';
            $condition = 'details.number LIKE :searchFilter OR ' .
                'articles.name LIKE :searchFilter OR ' .
                'suppliers.name LIKE :searchFilter';
            $builder->andWhere($condition)
                ->setParameter('searchFilter', $searchFilter);
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
        if (null === $order
            || !in_array($order[0]['property'], $columns)
            || !in_array($order[0]['direction'], $directions)
        ) {
            $builder->orderBy('articles.id');
        } else {
            $order = array_shift($order);
            switch ($order['property']) {
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
        $builder->distinct()->addOrderBy('details.number');
        // Used to get number of products available/exported
        $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, null);
        $totalProducts = count($builder->getQuery()->getArrayResult());
        $builder->setFirstResult($start)->setMaxResults($limit);
        $result = $builder->getQuery()->getArrayResult();
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $result,
                'total' => $totalProducts,
                'nbProductsAvailable' => $export->getTotalProducts(),
                'nbExportedProducts' => $export->getExportedProducts()
            )
        );
    }

    /**
     * Generate where clause used to list articles from a selected category
     *
     * @param Shopware\Models\Category\Category $selectedCategory Shopware category instance
     *
     * @return string
     */
    private function getAllCategoriesClause($selectedCategory)
    {
        $children = $selectedCategory->getChildren();
        $where = 'categories.id = ' . $selectedCategory->getId();
        foreach ($children as $child) {
            if ($child->isLeaf()) {
                $where .= ' OR categories.id = ' . $child->getId();
            } else {
                $where .= ' OR ' . $this->getAllCategoriesClause($child);
            }
        }
        return $where;
    }

    /**
     * Event listener function of articles store to export a list of products
     */
    public function exportAction()
    {
        $host = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getBaseUrl();
        $exportUrl = $host . '/LengowController/export';
        $this->View()->assign(
            array(
                'success' => true,
                'url' => $exportUrl
            )
        );
    }

    /**
     * Set Lengow status for an article
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
     *
     * @param Shopware\Models\Category\Category $category Shopware category instance
     * @param integer $shopId Shopware shop id
     * @param boolean $status Lengow status to set for articles which belong to the category
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

    /*
     * Get tree structure
     */
    public function getShopsTreeAction()
    {
        $parentId = $this->Request()->getParam('id');
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $result = array();
        // If the root is selected, return list of enabled shops
        if ($parentId == 'root') {
            // @var Shopware\Models\Shop\Shop[] $shops
            $shops = $em->getRepository('Shopware\Models\Shop\Shop')->findBy(array('active' => 1));
            foreach ($shops as $shop) {
                $mainCategory = $shop->getCategory();
                $result[] = array(
                    'leaf' => $mainCategory->isLeaf(),
                    'text' => $shop->getName(),
                    'id' => $shop->getId(),
                    'lengowStatus' => Shopware_Plugins_Backend_Lengow_Components_LengowSync::checkSyncShop($shop)
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
            // @var \Shopware\Models\Category\Category $category
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
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $result
            )
        );
    }

    /**
     * Change Lengow shop settings (checkboxes above export grid)
     * integer id     Shop id
     * string  name   Setting name
     * boolean status New setting value
     */
    public function setConfigValueAction()
    {
        $shopId = $this->Request()->getParam('id');
        $name = $this->Request()->getParam('name');
        $status = (int)($this->Request()->getParam('status') === 'true');
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig($name, $status, $shop);
    }

    /**
     * Get config value for a shop from the database
     * integer id         Shop id
     * array   configList List of settings to get
     */
    public function getConfigValueAction()
    {
        $shopId = $this->Request()->getParam('id');
        $configList = $this->Request()->getParam('configList');
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);
        $names = json_decode($configList);
        $result = array();
        foreach ($names as $name) {
            $result[$name] = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig($name, $shop);
        }
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $result
            )
        );
    }

    /**
     * Get the default shop in Shopware
     * Used to display default shop data in grid when starting Lengow
     */
    public function getDefaultShopAction()
    {
        $defaultShop = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getDefaultShop();
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $defaultShop->getId()
            )
        );
    }
}