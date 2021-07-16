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

use Shopware\Models\Article\Detail as ArticleDetailModel;
use Shopware\Models\Category\Category as CategoryModel;
use Shopware\Models\Shop\Shop as ShopModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowExport as LengowExport;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;

/**
 * Backend Lengow Export Controller
 */
class Shopware_Controllers_Backend_LengowExport extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Event listener function of articles store to list Lengow products
     *
     * @throws Exception
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
        $em = LengowBootstrap::getEntityManager();
        /** @var ShopModel $shop */
        $shop = $em->getReference(ShopModel::class, $shopId);
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
            'attributes.lengowShop' . $shopId . 'Active AS lengowActive',
        );
        $builder = $em->createQueryBuilder();
        $builder->select($select)
            ->from(ArticleDetailModel::class, 'details')
            ->join('details.article', 'articles')
            ->join('details.attribute', 'attributes')
            ->leftJoin('articles.supplier', 'suppliers')
            ->leftJoin('details.prices', 'prices')
            ->leftJoin('articles.tax', 'tax')
            ->where('prices.to = \'beliebig\'')
            ->andWhere('prices.customerGroupKey = \'EK\'')
            ->andWhere('details.kind = 1')
            ->andWhere('attributes.articleDetailId = details.id');
        // filter category
        if ($filterBy === 'inStock') {
            $builder->andWhere('details.inStock > 0');
        } elseif ($filterBy === 'lengowProduct') {
            $builder->andWhere('attributes.lengowShop' . $shopId . 'Active = 1');
        } elseif ($filterBy === 'activeProduct') {
            $builder->andWhere('articles.active = 1');
        } elseif ($filterBy === 'noCategory') {
            $builder->leftJoin('articles.allCategories', 'allCategories')
                ->andWhere('allCategories.id IS NULL');
        } elseif ($categoryId !== 'NaN' && $categoryId !== null) {
            $mainCategory = null;
            if ($isShopSelected) {
                $mainCategory = $shop->getCategory();
            } else {
                $mainCategory = $em->getReference(CategoryModel::class, $categoryId);
            }
            // construct where clause with selected category children
            $where = $this->getAllCategoriesClause($mainCategory);
            $builder->leftJoin('articles.categories', 'categories')
                ->innerJoin('articles.allCategories', 'allCategories')
                ->andWhere($where);
        }
        // search criteria
        if (isset($filters['search'])) {
            $searchFilter = '%' . $filters['search'] . '%';
            $condition = 'details.number LIKE :searchFilter OR ' .
                'articles.name LIKE :searchFilter OR ' .
                'suppliers.name LIKE :searchFilter';
            $builder->andWhere($condition)
                ->setParameter('searchFilter', $searchFilter);
        }
        // make sure that whe don't get a cold here
        $columns = array(
            'number',
            'name',
            'supplier',
            'status',
            'price',
            'tax',
            'inStock',
            'lengowActive',
        );
        $directions = array('ASC', 'DESC');
        if ($order === null
            || !in_array($order[0]['property'], $columns, true)
            || !in_array($order[0]['direction'], $directions, true)
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
        // used to get number of products available/exported
        $export = new LengowExport($shop);
        $totalProducts = count($builder->getQuery()->getArrayResult());
        $builder->setFirstResult($start)->setMaxResults($limit);
        $result = $builder->getQuery()->getArrayResult();
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $result,
                'total' => $totalProducts,
                'nbProductsAvailable' => $export->getTotalProduct(),
                'nbExportedProducts' => $export->getTotalExportProduct(),
            )
        );
    }

    /**
     * Generate where clause used to list articles from a selected category
     *
     * @param CategoryModel $selectedCategory Shopware category instance
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
        $exportUrl = LengowMain::getBaseUrl() . '/LengowController/export';
        $this->View()->assign(
            array(
                'success' => true,
                'url' => $exportUrl,
            )
        );
    }

    /**
     * Set Lengow status for an article
     *
     * @throws Exception
     */
    public function setStatusInLengowAction()
    {
        $articleIds = $this->Request()->getParam('ids');
        $status = $this->Request()->getParam('status', false);
        $categoriesIds = $this->Request()->getParam('categoryId');
        $active = $status === 'true';
        $shopId = $categoriesIds;
        // tree is based on shopId_categoryId (except for shops)
        $articleCategory = explode('_', $categoriesIds);
        if (count($articleCategory) > 1) {
            $shopId = $articleCategory[0];
        }
        $em = LengowBootstrap::getEntityManager();
        // if export all product for this shop (checkbox)
        if ($articleIds === '') {
            /** @var ShopModel $shop */
            $shop = $em->getReference(ShopModel::class, $shopId);
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
     * @param CategoryModel $category Shopware category instance
     * @param integer $shopId Shopware shop id
     * @param boolean $status Lengow status to set for articles which belong to the category
     *
     * @throws Exception
     */
    private function setLengowStatusFromCategory($category, $shopId, $status)
    {
        $em = LengowBootstrap::getEntityManager();
        // if not a parent category
        if ($category->isLeaf()) {
            $articles = $category->getArticles();
            foreach ($articles as $article) {
                $mainDetail = $article->getMainDetail();
                if ($mainDetail) {
                    $attribute = $article->getMainDetail()->getAttribute();
                } else {
                    $attribute = $article->getAttribute();
                }
                if ($attribute !== null) {
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
     * Get tree structure
     *
     * @throws Exception
     */
    public function getShopsTreeAction()
    {
        $parentId = $this->Request()->getParam('id');
        $em = LengowBootstrap::getEntityManager();
        $result = array();
        // if the root is selected, return list of enabled shops
        if ($parentId === 'root') {
            /** @var ShopModel[] $shops */
            $shops = $em->getRepository(ShopModel::class)->findBy(array('active' => 1));
            foreach ($shops as $shop) {
                $mainCategory = $shop->getCategory();
                $result[] = array(
                    'leaf' => $mainCategory->isLeaf(),
                    'text' => $shop->getName(),
                    'id' => $shop->getId(),
                );
            }
        } else {
            // as tree requires a unique id, explode id to get the shop and the category id
            $ids = explode('_', $parentId);
            if (count($ids) > 1) {
                $shopId = $ids[0];
                $categoryId = $ids[1];
            } else {
                /** @var ShopModel $shop */
                $shop = $em->getReference(ShopModel::class, $parentId);
                $shopId = $shop->getId();
                $categoryId = $shop->getCategory()->getId();
            }
            /** @var CategoryModel $category */
            $category = $em->getReference(CategoryModel::class, $categoryId);
            $categories = $category->getChildren();
            foreach ($categories as $category) {
                $result[] = array(
                    'leaf' => $category->isLeaf(),
                    'text' => $category->getName(),
                    'id' => $shopId . '_' . $category->getId(),
                );
            }
        }
        sort($result);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $result,
            )
        );
    }

    /**
     * Change Lengow shop settings (checkboxes above export grid)
     * integer id     Shop id
     * string  name   Setting name
     * boolean status New setting value
     *
     * @throws Exception
     */
    public function setConfigValueAction()
    {
        $shopId = $this->Request()->getParam('id');
        $name = $this->Request()->getParam('name');
        $status = (int)($this->Request()->getParam('status') === 'true');
        $em = LengowBootstrap::getEntityManager();
        /** @var ShopModel $shop */
        $shop = $em->getReference(ShopModel::class, $shopId);
        LengowConfiguration::checkAndLog($name, $status, $shop);
        LengowConfiguration::setConfig($name, $status, $shop);
    }

    /**
     * Get config value for a shop from the database
     * integer id         Shop id
     * array   configList List of settings to get
     *
     * @throws Exception
     */
    public function getConfigValueAction()
    {
        $shopId = $this->Request()->getParam('id');
        $configList = $this->Request()->getParam('configList');
        $em = LengowBootstrap::getEntityManager();
        /** @var ShopModel $shop */
        $shop = $em->getReference(ShopModel::class, $shopId);
        $names = json_decode($configList);
        $result = array();
        foreach ($names as $name) {
            $result[$name] = LengowConfiguration::getConfig($name, $shop);
        }
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $result,
            )
        );
    }

    /**
     * Get the default shop in Shopware
     * Used to display default shop data in grid when starting Lengow
     */
    public function getDefaultShopAction()
    {
        $defaultShop = LengowConfiguration::getDefaultShop();
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $defaultShop->getId(),
            )
        );
    }

    /**
     * Get shop token for a specific shop
     */
    public function getShopTokenAction()
    {
        $shopId = $this->Request()->getParam('shopId');
        $em = LengowBootstrap::getEntityManager();
        /** @var ShopModel $shop */
        $shop = $em->getRepository(ShopModel::class)->find($shopId);
        $shopToken = LengowMain::getToken($shop);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $shopToken,
            )
        );
    }
}
