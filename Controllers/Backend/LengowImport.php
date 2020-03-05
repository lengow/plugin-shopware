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

use Shopware\Models\Order\Order as OrderModel;
use Shopware\Models\Shop\Shop as ShopModel;
use Shopware\CustomModels\Lengow\Order as LengowOrderModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowImport as LengowImport;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;

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

        $em = LengowBootstrap::getEntityManager();
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
            's_order.number as orderShopwareSku',
            's_core_countries.name as countryName',
            's_core_countries.iso as countryIso',
            'orderError.message as errorMessage',
            's_lengow_action.actionType as lastActionType',
        );

        if (LengowMain::compareVersion('5.5.0')) {
            $select[] = 's_core_states.name as orderStatusDescription';
        } else {
            $select[] = 's_core_states.description as orderStatusDescription';
        }

        if (LengowMain::compareVersion('5.1')) {
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

        // search criteria
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
                'total' => $totalOrders,
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
            'orderError.isFinished',
        );
        $em = LengowBootstrap::getEntityManager();
        $builder = $em->createQueryBuilder();
        $builder->select($select)
            ->from('Shopware\CustomModels\Lengow\OrderError', 'orderError')
            ->where('orderError.isFinished = 0');
        $results = $builder->getQuery()->getArrayResult();
        if ($results) {
            $locale = LengowMain::getLocale();
            foreach ($results as $errorOrder) {
                if ($errorOrder['message'] !== '') {
                    $errorMessage = LengowMain::cleanData(
                        LengowMain::decodeLogMessage($errorOrder['message'], $locale)
                    );
                } else {
                    $errorMessage = LengowMain::decodeLogMessage('order/grid/errors/no_error_message', $locale);
                }
                $errorMessages[$errorOrder['lengowOrderId']] .= '<br />' . $errorMessage;
            }
        }

        return $errorMessages;
    }

    /**
     * Get datas for import header page
     */
    public function getPanelContentsAction()
    {
        $locale = LengowMain::getLocale();
        $lastImport = LengowMain::getLastImport();
        $data['nb_order_in_error'] = LengowOrder::countOrderWithError();
        $data['nb_order_to_be_sent'] = LengowOrder::countOrderToBeSent();
        $data['last_import'] = $lastImport['timestamp'] !== 'none'
            ? LengowMain::getDateInCorrectFormat($lastImport['timestamp'])
            : '';
        if (LengowConfiguration::getConfig('lengowImportReportMailEnabled')) {
            $data['mail_report'] = LengowMain::decodeLogMessage(
                'order/panel/mail_report',
                $locale,
                array('email' => implode(', ', LengowConfiguration::getReportEmailAddress()))
            );
        } else {
            $data['mail_report'] =LengowMain::decodeLogMessage('order/panel/no_mail_report', $locale);
        }
        $data['mail_report'] .= ' (<a href="#">Change this?</a>)';
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $data,
            )
        );
    }

    /**
     * Execute import process and get result/errors
     */
    public function launchImportProcessAction()
    {
        $import = new LengowImport();
        $result = $import->exec();
        $messages = $this->loadMessage($result);
        $data = array();
        $data['messages'] = join('<br/>', $messages);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $data,
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
        $locale = LengowMain::getLocale();
        // if global error return this
        if (isset($return['error'][0])) {
            $messages['error'] = LengowMain::decodeLogMessage(
                $return['error'][0],
                $locale
            );
            return $messages;
        }
        if (isset($return['order_new']) && $return['order_new'] > 0) {
            $messages['order_new'] = LengowMain::decodeLogMessage(
                'lengow_log/error/nb_order_imported',
                $locale,
                array('nb_order' => (int)$return['order_new'])
            );
        }
        if (isset($return['order_update']) && $return['order_update'] > 0) {
            $messages['order_update'] = LengowMain::decodeLogMessage(
                'lengow_log/error/nb_order_updated',
                $locale,
                array('nb_order' => (int)$return['order_update'])
            );
        }
        if (isset($return['order_error']) && $return['order_error'] > 0) {
            $messages['order_error'] = LengowMain::decodeLogMessage(
                'lengow_log/error/nb_order_with_error',
                $locale,
                array('nb_order' => (int)$return['order_error'])
            );
        }
        if (empty($messages)) {
            $messages['no_notification'] = LengowMain::decodeLogMessage('lengow_log/error/no_notification', $locale);
        }
        if (isset($return['error'])) {
            foreach ($return['error'] as $shopId => $values) {
                if ((int)$shopId > 0) {
                    $em = LengowBootstrap::getEntityManager();
                    /** @var ShopModel $shop */
                    $shop = $em->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('id' => (int)$shopId));
                    $shopName = $shop !== null ? $shop->getName() . ' : ' :  '';
                    $error = LengowMain::decodeLogMessage($values, $locale);
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
        /** @var OrderModel $order */
        $order = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(array('id' => $orderId));
        $success = LengowOrder::callAction($order, $action);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success,
            )
        );
    }

    /**
     * Synchronize Order action
     */
    public function synchronizeAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        /** @var OrderModel $order */
        $order = Shopware()->Models()
            ->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(array('id' => $orderId));
        $success = LengowOrder::synchronizeOrder($order);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success,
            )
        );
    }

    /**
     * Cancel and re-import order action
     */
    public function cancelAndReImportAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        /** @var OrderModel $order */
        $order = Shopware()->Models()
            ->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(array('id' => $orderId));
        $success = LengowOrder::cancelAndReImportOrder($order);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success,
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
        /** @var LengowOrderModel $lengowOrder */
        $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
            ->findOneBy(array('id' => $lengowOrderId));
        $success = LengowOrder::reImportOrder($lengowOrder);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success,
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
        /** @var LengowOrderModel $lengowOrder */
        $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
            ->findOneBy(array('id' => $lengowOrderId));
        $success = LengowOrder::reSendOrder($lengowOrder);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success,
            )
        );
    }

    /**
     * Mass action re-import order
     */
    public function reImportMassAction()
    {
        $totalReImport = 0;
        $lengowOrderIds = json_decode($this->Request()->getParam('lengowOrderIds'));
        $nbSelected = count($lengowOrderIds);
        $locale = LengowMain::getLocale();
        foreach ($lengowOrderIds as $lengowOrderId) {
            $result = $this->reImportAction((int)$lengowOrderId);
            if ($result && isset($result['order_new']) && $result['order_new']) {
                $totalReImport++;
            }
        }
        LengowMain::log(
            LengowLog::CODE_ACTION,
            LengowMain::setLogMessage(
                'lengow_log/error/mass_action_reimport_success',
                array(
                    'nb_imported' => $totalReImport,
                    'nb_selected' => $nbSelected,
                )
            ),
            false
        );
        $message = LengowMain::decodeLogMessage(
            'lengow_log/error/mass_action_reimport_success',
            $locale,
            array(
                'nb_imported' => $totalReImport,
                'nb_selected' => $nbSelected,
            )
        );
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $message,
            )
        );
    }

    /**
     * Mass action re-send action
     */
    public function reSendMassAction()
    {
        $totalReSent = 0;
        $lengowOrderIds = json_decode($this->Request()->getParam('lengowOrderIds'));
        $nbSelected = count($lengowOrderIds);
        $locale = LengowMain::getLocale();
        foreach ($lengowOrderIds as $lengowOrderId) {
            $result = $this->reSendAction((int)$lengowOrderId);
            if ($result && isset($result['order_new']) && $result['order_new']) {
                $totalReSent++;
            }
        }
        LengowMain::log(
            LengowLog::CODE_ACTION,
            LengowMain::setLogMessage(
                'lengow_log/error/mass_action_resend_success',
                array(
                    'nb_sent' => $totalReSent,
                    'nb_selected' => $nbSelected,
                )
            ),
            false
        );
        $message = LengowMain::decodeLogMessage(
            'lengow_log/error/mass_action_resend_success',
            $locale,
            array(
                'nb_sent' => $totalReSent,
                'nb_selected' => $nbSelected,
            )
        );
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $message,
            )
        );
    }
}
