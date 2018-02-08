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
            'orderLengow.marketplaceLabel as marketplaceLabel',
            'orderLengow.orderLengowState as orderLengowState',
            'orderLengow.orderProcessState as orderProcessState',
            'orderLengow.orderDate as orderDate',
            'orderLengow.customerName as customerName',
            'orderLengow.orderItem as orderItem',
            'orderLengow.deliveryCountryIso as deliveryCountryIso',
            'orderLengow.sentByMarketplace as sentByMarketplace',
            'orderLengow.commission as commission',
            'orderLengow.deliveryAddressId as deliveryAddressId',
            'orderLengow.customerEmail as customerEmail',
            'orderLengow.carrier as carrier',
            'orderLengow.carrierMethod as carrierMethod',
            'orderLengow.carrierTracking as carrierTracking',
            'orderLengow.carrierIdRelay as carrierIdRelay',
            'orderLengow.createdAt as createdAt',
            'orderLengow.message as message',
            'orderLengow.extra as extra',
            'shops.name as storeName',
            's_core_states.description as orderStatusDescription',
            's_order.number as orderShopwareSku',
            's_core_countries.name as countryName',
            's_core_countries.iso as countryIso',
            'orderError.message as errorMessage',
            's_lengow_action.actionType as lastActionType'
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
            ->leftJoin(
                'Shopware\Models\Country\Country',
                's_core_countries',
                'WITH',
                'orderLengow.deliveryCountryIso = s_core_countries.iso'
            )
            ->leftJoin(
                'Shopware\CustomModels\Lengow\OrderError',
                'orderError',
                'WITH',
                'orderLengow.id = orderError.lengowOrderId'
            )
            ->leftJoin(
                'Shopware\CustomModels\Lengow\Action',
                's_lengow_action',
                'WITH',
                's_order.id = s_lengow_action.orderId'
            );

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

        $result = $builder->getQuery()->getArrayResult();
        $totalOrders = count($result);
        $builder->setFirstResult($start)->setMaxResults($limit);
        $orderErrors = $this->getOrderErrors();

        if ($result) {
            foreach ($result as $key => $error) {
                $result[$key]['errorMessage'] = $orderErrors[$error['id']];
            }
        }

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $result,
                'total' => $totalOrders
            )
        );
    }

    /**
     * Get Order Errors translated
     *
     * @return array
     */
    public function getOrderErrors()
    {
        $errorMessages = array();
        $select = array(
            'orderError.lengowOrderId',
            'orderError.message',
            'orderError.type',
            'orderError.isFinished'
        );

        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $builder = $em->createQueryBuilder();
        $builder->select($select)
            ->from('Shopware\CustomModels\Lengow\OrderError', 'orderError')
            ->where('orderError.isFinished = 0');

        $results = $builder->getQuery()->getArrayResult();

        if ($results) {
            foreach ($results as $errorOrder) {
                $errorMessages[$errorOrder['lengowOrderId']] .=
                    '<br />' . Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                        $errorOrder['message']
                    );
            }
        }

        return $errorMessages;
    }

    /**
     * Get datas for import header page
     */
    public function getPanelContentsAction()
    {
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();
        $data['nb_order_in_error'] = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::countOrderWithError();
        $data['nb_order_to_be_sent'] = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::countOrderToBeSent();
        $data['last_import'] = Shopware_Plugins_Backend_Lengow_Components_LengowImport::getLastImport();
        $reportMailActive = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowImportReportMailEnabled'
        );

        if ($reportMailActive) {
            $data['mail_report'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'order/panel/mail_report',
                $locale,
                array('email' => implode(", ",
                    Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getReportEmailAddress())
                )
            );
        } else {
            $data['mail_report'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'order/panel/no_mail_report',
                $locale
            );
        }
        $data['mail_report'] .= ' (<a href="#">Change this?</a>)';
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
        $data['messages'] = join('<br/>', $messages);
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
            $messages['order_error'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'lengow_log/error/nb_order_with_error',
                $locale,
                array('nb_order' => (int)$return['order_error'])
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

    /**
     * Send Order action
     */
    public function sendAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $action = $this->Request()->getParam('actionName');
        $order = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(array('id' => $orderId));
        $success = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::callAction($order, $action);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success
            )
        );
    }

    /**
     * Synchronize Order action
     */
    public function synchronizeAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $order = Shopware()->Models()
            ->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(array('id' => $orderId));
        $success = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::synchronizeOrder($order);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success
            )
        );
    }

    /**
     * Cancel and re-import order action
     */
    public function cancelAndReImportAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $order = Shopware()->Models()
            ->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(array('id' => $orderId));
        $success = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::cancelAndReImportOrder($order);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success
            )
        );
    }

    /**
     * Re-import action
     *
     * @param int|null $lengowOrderId Lengow Order Id
     */
    public function reImportAction($lengowOrderId = null)
    {
        if (!$lengowOrderId) {
            $lengowOrderId = $this->Request()->getParam('lengowOrderId');
        }
        $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
            ->findOneBy(array('id' => $lengowOrderId));
        $success = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::reImportOrder($lengowOrder);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success
            )
        );
    }

    /**
     * Re-send Order action
     *
     * @param int|null $lengowOrderId Lengow Order Id
     */
    public function reSendAction($lengowOrderId = null)
    {
        if (!$lengowOrderId) {
            $lengowOrderId = $this->Request()->getParam('lengowOrderId');
        }
        $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
            ->findOneBy(array('id' => $lengowOrderId));
        $success = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::reSendOrder($lengowOrder);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success
            )
        );
    }

    /**
     * Mass action re-import order
     */
    public function reImportMassAction()
    {
        $lengowOrderIds = json_decode($this->Request()->getParam('lengowOrderIds'));
        $nbSelected = count($lengowOrderIds);
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();

        $totalReImport = 0;
        foreach ($lengowOrderIds as $lengowOrderId) {
            $result = $this->reImportAction((int)$lengowOrderId);
            if ($result && isset($result['order_new']) && $result['order_new']) {
                $totalReImport++;
            }
        }

        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'API-OrderAction',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/mass_action_reimport_success',
                array('nb_imported' => $totalReImport, 'nb_selected' => $nbSelected)
            ),
            false
        );

        $message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
            'lengow_log/error/mass_action_reimport_success',
            $locale,
            array('nb_imported' => $totalReImport, 'nb_selected' => $nbSelected)
        );

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $message
            )
        );
    }

    /**
     * Mass action re-send action
     */
    public function reSendMassAction()
    {
        $lengowOrderIds = json_decode($this->Request()->getParam('lengowOrderIds'));
        $nbSelected = count($lengowOrderIds);
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();

        $totalReSent = 0;
        foreach ($lengowOrderIds as $lengowOrderId) {
            $result = $this->reSendAction((int)$lengowOrderId);
            if ($result && isset($result['order_new']) && $result['order_new']) {
                $totalReSent++;
            }
        }

        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'API-OrderAction',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/mass_action_resend_success',
                array('nb_sent' => $totalReSent, 'nb_selected' => $nbSelected)
            ),
            false
        );

        $message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
            'lengow_log/error/mass_action_resend_success',
            $locale,
            array('nb_sent' => $totalReSent, 'nb_selected' => $nbSelected)
        );

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $message
            )
        );
    }
}
