<?php
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
 * Backend Lengow Import Controller
 */
class Shopware_Controllers_Backend_LengowImport extends Shopware_Controllers_Backend_ExtJs
{

    /**
     * Event listener function of orders store to list Lengow orders
     *
     * @throws Exception
     */
    public function getListAction()
    {
        $filterParams = $this->Request()->getParam('filter', array());
        $filterBy = $this->Request()->getParam('filterBy');
        $order = $this->Request()->getParam('sort', null);
        $start = $this->Request()->getParam('start', 0);
        $limit = $this->Request()->getParam('limit', 20);

        $filters = array();
        foreach ($filterParams as $singleFilter) {
            $filters[$singleFilter['property']] = $singleFilter['value'];
        }

        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $select = array(
            'orderLengow.id',
            'orderLengow.orderId',
            'orderLengow.orderSku',
            'orderLengow.totalPaid',
            'orderLengow.currency',
            'orderLengow.inError',
            'orderLengow.marketplaceSku',
            'orderLengow.marketplaceName',
            'orderLengow.orderLengowState',
            'orderLengow.orderProcessState',
            'orderLengow.orderDate',
            'orderLengow.customerName',
            'orderLengow.orderItem',
            'orderLengow.deliveryCountryIso',
            'shops.name as storeName',
            's_core_states.name as orderStatus'
        );
        $builder = $em->createQueryBuilder();
        $builder->select($select)
            ->from('Shopware\CustomModels\Lengow\Order', 'orderLengow')
            ->join('orderLengow.shopId', 'shops')
            ->leftJoin('orderLengow.order', 's_order')
            ->leftJoin('s_order.orderStatus', 's_core_states');

        //        // Search criteria
//        if (isset($filters['search'])) {
//            $searchFilter = '%' . $filters['search'] . '%';
//            $condition = 'details.number LIKE :searchFilter OR ' .
//                'articles.name LIKE :searchFilter OR ' .
//                'suppliers.name LIKE :searchFilter';
//            $builder->andWhere($condition)
//                ->setParameter('searchFilter', $searchFilter);
//        }
//        // Make sure that whe don't get a cold here
//        $columns = array(
//            'number',
//            'name',
//            'supplier',
//            'status',
//            'price',
//            'tax',
//            'inStock',
//            'lengowActive'
//        );
//        $directions = array('ASC', 'DESC');
//        if (null === $order
//            || !in_array($order[0]['property'], $columns)
//            || !in_array($order[0]['direction'], $directions)
//        ) {
//            $builder->orderBy('articles.id');
//        } else {
//            $order = array_shift($order);
//            switch ($order['property']) {
//                case 'active':
//                    $orderColumn = 'details.active';
//                    break;
//                case 'inStock':
//                    $orderColumn = 'details.inStock';
//                    break;
//                case 'status':
//                    $orderColumn = 'articles.active';
//                    break;
//                default:
//                    $orderColumn = $order['property'];
//                    break;
//            }
//            $builder->orderBy($orderColumn, $order['direction']);
//        }
//        $builder->distinct()->addOrderBy('details.number');

        $totalOrders = count($builder->getQuery()->getArrayResult());
        $builder->setFirstResult($start)->setMaxResults($limit);
        $result = $builder->getQuery()->getArrayResult();
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $result,
                'total' => $totalOrders
            )
        );

//        $treeId = $this->Request()->getParam('categoryId');
//        $filterParams = $this->Request()->getParam('filter', array());
//        $filterBy = $this->Request()->getParam('filterBy');
//        $order = $this->Request()->getParam('sort', null);
//        $start = $this->Request()->getParam('start', 0);
//        $limit = $this->Request()->getParam('limit', 20);
//        $categoryId = $treeId;
//        $shopId = $treeId;
//        $ids = explode('_', $treeId);
//        $isShopSelected = true;
//        if (count($ids) > 1) {
//            $isShopSelected = false;
//            $shopId = $ids[0];
//            $categoryId = $ids[1];
//        }
//        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
//        /** @var Shopware\Models\Shop\Shop $shop */
//        $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);
//        $filters = array();
//        foreach ($filterParams as $singleFilter) {
//            $filters[$singleFilter['property']] = $singleFilter['value'];
//        }
//        $select = array(
//            'attributes.id AS attributeId',
//            'articles.id AS articleId',
//            'articles.name AS name',
//            'suppliers.name AS supplier',
//            'articles.active AS status',
//            'details.number AS number',
//            'details.inStock',
//            'CONCAT(tax.tax, \' %\') AS vat',
//            'prices.price*(100+tax.tax)/100 AS price',
//            'attributes.lengowShop' . $shopId . 'Active AS lengowActive'
//        );
//        $builder = $em->createQueryBuilder();
//        $builder->select($select)
//            ->from('Shopware\Models\Article\Detail', 'details')
//            ->join('details.article', 'articles')
//            ->join('articles.attribute', 'attributes')
//            ->leftJoin('articles.supplier', 'suppliers')
//            ->leftJoin('details.prices', 'prices')
//            ->leftJoin('articles.tax', 'tax')
//            ->where('prices.to = \'beliebig\'')
//            ->andWhere('prices.customerGroupKey = \'EK\'')
//            ->andWhere('details.kind = 1')
//            ->andWhere('attributes.articleDetailId = details.id');
//        // Filter category
//        if ($filterBy == 'inStock') {
//            $builder->andWhere('details.inStock > 0');
//        } elseif ($filterBy == 'lengowProduct') {
//            $builder->andWhere('attributes.lengowShop' . $shopId . 'Active = 1');
//        } elseif ($filterBy == 'noCategory') {
//            $builder->leftJoin('articles.allCategories', 'allCategories')
//                ->andWhere('allCategories.id IS NULL');
//        } elseif ($categoryId !== 'NaN' && $categoryId != null) {
//            $mainCategory = null;
//            if ($isShopSelected) {
//                $mainCategory = $shop->getCategory();
//            } else {
//                $mainCategory = $em->getReference('Shopware\Models\Category\Category', $categoryId);
//            }
//            // Construct where clause with selected category children
//            $where = $this->getAllCategoriesClause($mainCategory);
//            $builder->leftJoin('articles.categories', 'categories')
//                ->innerJoin('articles.allCategories', 'allCategories')
//                ->andWhere($where);
//        }
//        // Search criteria
//        if (isset($filters['search'])) {
//            $searchFilter = '%' . $filters['search'] . '%';
//            $condition = 'details.number LIKE :searchFilter OR ' .
//                'articles.name LIKE :searchFilter OR ' .
//                'suppliers.name LIKE :searchFilter';
//            $builder->andWhere($condition)
//                ->setParameter('searchFilter', $searchFilter);
//        }
//        // Make sure that whe don't get a cold here
//        $columns = array(
//            'number',
//            'name',
//            'supplier',
//            'status',
//            'price',
//            'tax',
//            'inStock',
//            'lengowActive'
//        );
//        $directions = array('ASC', 'DESC');
//        if (null === $order
//            || !in_array($order[0]['property'], $columns)
//            || !in_array($order[0]['direction'], $directions)
//        ) {
//            $builder->orderBy('articles.id');
//        } else {
//            $order = array_shift($order);
//            switch ($order['property']) {
//                case 'active':
//                    $orderColumn = 'details.active';
//                    break;
//                case 'inStock':
//                    $orderColumn = 'details.inStock';
//                    break;
//                case 'status':
//                    $orderColumn = 'articles.active';
//                    break;
//                default:
//                    $orderColumn = $order['property'];
//                    break;
//            }
//            $builder->orderBy($orderColumn, $order['direction']);
//        }
//        $builder->distinct()->addOrderBy('details.number');
//        // Used to get number of products available/exported
//        $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, null);
//        $totalProducts = count($builder->getQuery()->getArrayResult());
//        $builder->setFirstResult($start)->setMaxResults($limit);
//        $result = $builder->getQuery()->getArrayResult();
//        $this->View()->assign(
//            array(
//                'success' => true,
//                'data' => $result,
//                'total' => $totalProducts,
//                'nbProductsAvailable' => $export->getTotalProducts(),
//                'nbExportedProducts' => $export->getExportedProducts()
//            )
//        );
    }

    /**
     * Check if Lengow import setting is enabled
     */
    public function getImportSettingStatusAction()
    {
        $status = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowEnableImport');
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $status
            )
        );
    }

    /**
     * Get translations and create labels displayed in import panel
     * Used despite Shopware translation tool because of parameters which are not settable
     */
    public function getPanelContentsAction()
    {
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();
        $nbDays = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowImportDays');
        $data['importDescription'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
            'order/panel/description',
            $locale,
            array('nb_days' => $nbDays)
        );
        // Get last import date
        $lastImport = Shopware_Plugins_Backend_Lengow_Components_LengowImport::getLastImport();
        $data['lastImport'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
            'order/panel/last_import',
            $locale,
            array('import_date' => $lastImport)
        );
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $data
            )
        );
    }

    /**
     * Execute import process and get result/errors
     */
    public function launchImportProcessAction()
    {
        $import = new Shopware_Plugins_Backend_Lengow_Components_LengowImport();
        $result = $import->exec();
        $messages = $this->loadMessage($result);
        $data = array();
        $data['messages'] = join( '<br/>', $messages);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $data
            )
        );
    }

    /**
     * Generate message array (new, update and errors)
     *
     * @param array $return
     *
     * @return array
     */
    public function loadMessage($return)
    {
        $messages = array();
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();
        // if global error return this
        if (isset($return['error'][0])) {
            $messages['error'] =  Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                $return['error'][0],
                $locale
            );
            return $messages;
        }
        if (isset($return['order_new']) && $return['order_new'] > 0) {
            $messages['order_new'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'lengow_log/error/nb_order_imported',
                $locale,
                array('nb_order' => (int)$return['order_new'])
            );
        }
        if (isset($return['order_update']) && $return['order_update'] > 0) {
            $messages['order_update'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'lengow_log/error/nb_order_updated',
                $locale,
                array('nb_order' => (int)$return['order_update'])
            );
        }
        if (isset($return['order_error']) && $return['order_error'] > 0) {
            $logUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getPathPlugin() .
                'Logs/logs-' . date('Y-m-d') . '.txt';
            $messages['order_error'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'order/panel/order_error_link',
                $locale,
                array(
                    'nb_order' => (int)$return['order_error'],
                    'log_url' => $logUrl
                )
            );
        }
        if (count($messages) == 0) {
            $messages['no_notification'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'lengow_log/error/no_notification',
                $locale
            );
        }
        if (isset($return['error'])) {
            foreach ($return['error'] as $shopId => $values) {
                if ((int)$shopId > 0) {
                    $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
                    $shop = $em->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('id' => (int)$shopId));
                    $shopName = !is_null($shop) ? $shop->getName() . ' : ' :  '';
                    $error = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($values, $locale);
                    $messages[] = $shopName . $error;
                }
            }
        }
        return $messages;
    }
}
