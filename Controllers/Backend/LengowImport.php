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
            'orderLengow.orderId as orderId',
            'orderLengow.orderSku as orderSku',
            'orderLengow.totalPaid as totalPaid',
            'orderLengow.currency as currency',
            'orderLengow.inError as inError',
            'orderLengow.marketplaceSku as marketplaceSku',
            'orderLengow.marketplaceName as marketplaceName',
            'orderLengow.orderLengowState as orderLengowState',
            'orderLengow.orderProcessState as orderProcessState',
            'orderLengow.orderDate as orderDate',
            'orderLengow.customerName as customerName',
            'orderLengow.orderItem as orderItem',
            'orderLengow.deliveryCountryIso as deliveryCountryIso',
            'shops.name as storeName',
            's_core_states.description as orderStatusDescription',
            's_order.number as orderShopwareSku',
            's_core_countries.name as countryName',
            's_core_countries.iso as countryIso'
        );

        $crudCompatibility = Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.1');

        if ($crudCompatibility) {
            $select[] = 's_core_states.name as orderStatus';
        } else {
            $select[] = 's_order.status as orderStatus';
        }

        $builder = $em->createQueryBuilder();
        $builder->select($select)
            ->from('Shopware\CustomModels\Lengow\Order', 'orderLengow')
            ->leftJoin('Shopware\Models\Shop\Shop', 'shops', 'WITH', 'orderLengow.shopId = shops.id')
            ->leftJoin('orderLengow.order', 's_order')
            ->leftJoin('Shopware\Models\Order\Status', 's_core_states', 'WITH', 's_order.status = s_core_states')
            ->leftJoin('Shopware\Models\Country\Country', 's_core_countries', 'WITH', 'orderLengow.deliveryCountryIso = s_core_countries.iso');

        // Search criteria
        if (isset($filters['search'])) {
            $searchFilter = '%' . $filters['search'] . '%';
            $condition = 'orderLengow.marketplaceSku LIKE :searchFilter OR ' .
                'orderLengow.marketplaceName LIKE :searchFilter OR ' .
                'orderLengow.customerName LIKE :searchFilter';
            $builder->andWhere($condition)
                ->setParameter('searchFilter', $searchFilter);
        }

        $order = array_shift($order);
        if ($order['property'] && $order['direction']) {
            $builder->orderBy($order['property'], $order['direction']);
        }
        $builder->distinct()->addOrderBy('orderLengow.orderDate', 'DESC');

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
     * Get datas for import header page
     */
    public function getPanelContentsAction()
    {
        $data['nb_order_in_error'] = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::countOrderWithError();
        $data['nb_order_to_be_sent'] = count(Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getUnsentOrders());
        $data['last_import'] = Shopware_Plugins_Backend_Lengow_Components_LengowImport::getLastImport();;
        $data['mail_report'] = implode(", ",
            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getReportEmailAddress());
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
