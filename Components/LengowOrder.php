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
 * @subpackage  Components
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Lengow Order Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowOrder
{
    /**
     * @var integer order process state for order imported
     */
    const PROCESS_STATE_IMPORT = 1;

    /**
     * @var integer order process state for order finished
     */
    const PROCESS_STATE_FINISH = 2;

    /**
     * Get Shopware order id from lengow order table
     *
     * @param string $marketplaceSku Lengow order id
     * @param string $marketplaceName marketplace name
     * @param integer $deliveryAddressId Lengow delivery address id
     *
     * @return \Shopware\Models\Order\Order|false
     */
    public static function getOrderFromLengowOrder($marketplaceSku, $marketplaceName, $deliveryAddressId)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('lo.orderId')
            ->from('Shopware\CustomModels\Lengow\Order', 'lo')
            ->where('lo.marketplaceSku = :marketplaceSku')
            ->andWhere('lo.marketplaceName = :marketplaceName')
            ->andWhere('lo.deliveryAddressId = :deliveryAddressId')
            ->andWhere('lo.orderProcessState != :orderProcessState')
            ->setParameters(
                array(
                    'marketplaceSku' => $marketplaceSku,
                    'marketplaceName' => $marketplaceName,
                    'deliveryAddressId' => $deliveryAddressId,
                    'orderProcessState' => 0
                )
            );
        $result['orderId'] = $builder->getQuery()->getOneOrNullResult();
        if (!is_null($result['orderId'])) {
            $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')
                ->findOneBy(array('id' => $result['orderId']));
            if (!is_null($order)) {
                return $order;
            }
        }
        return false;
    }

    /**
     * Get lengow order id from lengow order table
     *
     * @param integer $orderId Shopware order id
     * @param integer $deliveryAddressId Lengow delivery address id
     *
     * @return integer|false
     */
    public static function getIdFromLengowDeliveryAddress($orderId, $deliveryAddressId)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('lo.id')
            ->from('Shopware\CustomModels\Lengow\Order', 'lo')
            ->where('lo.orderId = :orderId')
            ->andWhere('lo.deliveryAddressId = :deliveryAddressId')
            ->setParameters(
                array(
                    'orderId' => $orderId,
                    'deliveryAddressId' => $deliveryAddressId
                )
            );
        $result = $builder->getQuery()->getOneOrNullResult();
        if (!is_null($result['id'])) {
            return (int)$result['id'];
        }
        return false;
    }

    /**
     * Get all Shopware order ids from marketplace order
     *
     * @param string $marketplaceSku Lengow order id
     * @param string $marketplaceName marketplace name
     *
     * @return array|false
     */
    public static function getAllOrderIds($marketplaceSku, $marketplaceName)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('lo.orderId')
            ->from('Shopware\CustomModels\Lengow\Order', 'lo')
            ->where('lo.marketplaceSku = :marketplaceSku')
            ->andWhere('lo.marketplaceName = :marketplaceName')
            ->setParameters(
                array(
                    'marketplaceSku' => $marketplaceSku,
                    'marketplaceName' => $marketplaceName
                )
            );
        $results = $builder->getQuery()->getResult();
        if (count($results) > 0){
            return $results;
        }
        return false;
    }

    /**
     * Get all Lengow order line ids from marketplace order
     *
     * @param \Shopware\Models\Order\Order $order Shopware order instance
     *
     * @return array|false
     */
    public static function getAllOrderLineIds($order)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('lol.orderLineId')
            ->from('Shopware\CustomModels\Lengow\OrderLine', 'lol')
            ->where('lol.order = :order')
            ->setParameters(array('order' => $order));
        $results = $builder->getQuery()->getResult();
        if (count($results) > 0){
            return $results;
        }
        return false;
    }

    /**
     * Check if a lengow order or not
     *
     * @param integer $orderId Shopware order id
     *
     * @return boolean
     */
    public static function isFromLengow($orderId)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('lo.id')
            ->from('Shopware\CustomModels\Lengow\Order', 'lo')
            ->where('lo.orderId = :orderId')
            ->setParameters(array('orderId' => $orderId));
        $result = $builder->getQuery()->getOneOrNullResult();
        if (!is_null($result)) {
            return true;
        }
        return false;
    }

    /**
     * Check if a lengow order or not
     *
     * @param integer $orderId Shopware order id
     *
     * @return boolean
     */
    public static function orderIsFromLengow($orderId)
    {
        $result = Shopware()->Db()->fetchRow(
            "SELECT * FROM s_order_attributes WHERE orderID = ?",
            array(
                $orderId
            )
        );
        if ($result['lengow_is_from_lengow'] == 1) {
            return true;
        }
        return false;
    }


    /**
     * Get order process state
     *
     * @param string $state state to be matched
     *
     * @return integer
     */
    public static function getOrderProcessState($state)
    {
        switch ($state) {
            case 'accepted':
            case 'waiting_shipment':
                return self::PROCESS_STATE_IMPORT;
            case 'shipped':
            case 'closed':
            case 'refused':
            case 'canceled':
                return self::PROCESS_STATE_FINISH;
            default:
                return false;
        }
    }

    /**
     * Update order status
     *
     * @param \Shopware\Models\Order\Order $order Shopware order instance
     * @param \Shopware\CustomModels\Lengow\Order $lengowOrder Lengow order instance
     * @param string $orderStateLengow marketplace state
     * @param mixed $orderData order data
     * @param mixed $packageData package data
     * @param boolean $logOutput output on screen
     *
     * @return string|false
     */
    public static function updateState($order, $lengowOrder, $orderStateLengow, $orderData, $packageData, $logOutput)
    {
        $flushLengowOrder = false;
        $orderProcessState = self::getOrderProcessState($orderStateLengow);
        $trackingNumber = count($packageData->delivery->trackings) > 0
            ? (string)$packageData->delivery->trackings[0]->number
            : null;
        // Update Lengow order if necessary
        if ($lengowOrder->getOrderLengowState() != $orderStateLengow) {
            $lengowOrder->setOrderLengowState($orderStateLengow)
                ->setCarrierTracking($trackingNumber)
                ->setExtra(json_encode($orderData));
            $flushLengowOrder = true;
        }
        if ($orderProcessState == self::PROCESS_STATE_FINISH) {

            // TODO Finish actions if lengow order is shipped, closed or cancel

            if ($lengowOrder->getOrderProcessState() != $orderProcessState) {
                $lengowOrder->setOrderProcessState($orderProcessState);
                $flushLengowOrder = true;
            }
        }
        if ($flushLengowOrder) {
            $lengowOrder->setUpdatedAt(new DateTime());
            Shopware()->Models()->flush($lengowOrder);
        }
        // get Shopware equivalent order status to Lengow API state
        $orderStatus = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getOrderStatus($orderStateLengow);
        $waitingShipmentOrderStatus = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getOrderStatus('accepted');
        $shippedOrderStatus = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getOrderStatus('shipped');
        // if state is different between API and Shopware
        if ($order->getOrderStatus() != $orderStatus) {
            // Change state process to shipped
            if ($order->getOrderStatus() == $waitingShipmentOrderStatus
                && ($orderStateLengow == 'shipped' || $orderStateLengow == 'closed')
            ) {
                self::createOrderHistory($order, $shippedOrderStatus, $logOutput, $lengowOrder->getMarketplaceSku());
                self::updateOrderStatus($order->getId(), $shippedOrderStatus->getId());
                if ($trackingNumber) {
                    $order->setTrackingCode($trackingNumber);
                    Shopware()->Models()->flush($order);
                }
                return 'Shipped';
            } elseif (($order->getOrderStatus() == $waitingShipmentOrderStatus
                    || $order->getOrderStatus() == $shippedOrderStatus
                ) && ($orderStateLengow == 'canceled' || $orderStateLengow == 'refused')
            ) {
                $canceledOrderStatus = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getOrderStatus(
                    'canceled'
                );
                self::createOrderHistory($order, $canceledOrderStatus, $logOutput, $lengowOrder->getMarketplaceSku());
                self::updateOrderStatus($order->getId(), $canceledOrderStatus->getId());
                return 'Canceled';
            }
        }
        return false;
    }

    /**
     * Update order Status for compatibility with Shopware 4.3
     *
     * @param integer $orderId Shopware order id
     * @param integer $orderStatusId Shopware order status id
     */
    public static function updateOrderStatus($orderId, $orderStatusId)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->update('Shopware\Models\Order\Order', 'o')
            ->set('o.status', $orderStatusId)
            ->where('o.id = :orderId')
            ->setParameters(array('orderId' => $orderId))
            ->getQuery()
            ->execute();
    }

    /**
     * Create an order history with a new order status
     *
     * @param \Shopware\Models\Order\Order $order Shopware order instance
     * @param \Shopware\Models\Order\Status $newOrderStatus Shopware order status instance
     * @param boolean $logOutput output on screen
     * @param string $marketplaceSku Lengow marketplace sku
     *
     * @return boolean
     */
    public static function createOrderHistory($order, $newOrderStatus, $logOutput = false, $marketplaceSku = null)
    {
        try {
            $orderHistory = new Shopware\Models\Order\History();
            $orderHistory->setOrder($order)
                ->setPreviousOrderStatus($order->getOrderStatus())
                ->setOrderStatus($newOrderStatus)
                ->setPreviousPaymentStatus($order->getPaymentStatus())
                ->setPaymentStatus($order->getPaymentStatus())
                ->setChangeDate(new \datetime());
            // get all admin user
            $users = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getAllAdminUsers();
            if(count($users) > 0) {
                $orderHistory->setUser($users[0]);
            }
            Shopware()->Models()->persist($orderHistory);
            Shopware()->Models()->flush();
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Orm',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $logOutput,
                $marketplaceSku
            );
            return false;
        }
        return true;
    }

    /**
     * Synchronize order with Lengow API
     *
     * @param \Shopware\Models\Order\Order $order Shopware order instance
     * @param Shopware_Plugins_Backend_Lengow_Components_LengowConnector $connector Lengow connector instance
     *
     * @return boolean
     */
    public static function synchronizeOrder($order, $connector = null)
    {
        $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
            ->findOneBy(array('order' => $order));
        if (is_null($lengowOrder)) {
            return false;
        }
        $accessIds = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getAccessIds();
        list($accountId, $accessToken, $secretToken) = $accessIds;
        if (is_null($connector)) {
            if (Shopware_Plugins_Backend_Lengow_Components_LengowConnector::isValidAuth()) {
                $connector = new Shopware_Plugins_Backend_Lengow_Components_LengowConnector($accessToken, $secretToken);
            } else {
                return false;
            }
        }
        $orderIds = self::getAllOrderIds($lengowOrder->getMarketplaceSku(), $lengowOrder->getMarketplaceName());
        if ($orderIds) {
            $shopwareIds = array();
            foreach ($orderIds as $orderId) {
                $shopwareIds[] = $orderId['orderId'];
            }
            $result = $connector->patch(
                '/v3.0/orders/moi/',
                array(
                    'account_id' => $accountId,
                    'marketplace_order_id' => $lengowOrder->getMarketplaceSku(),
                    'marketplace' => $lengowOrder->getMarketplaceName(),
                    'merchant_order_id' => $shopwareIds
                )
            );
            if (is_null($result)
                || (isset($result['detail']) && $result['detail'] == 'Pas trouvé.')
                || isset($result['error'])
            ) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Send Order action
     *
     * @param \Shopware\Models\Order\Order $order Shopware order instance
     * @param string $action Lengow Actions type (ship or cancel)
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException order line is required
     *
     * @return boolean
     */
    public static function callAction($order, $action)
    {
        $success = true;
        $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
            ->findOneBy(array('order' => $order));
        if (is_null($lengowOrder)) {
            return false;
        }
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'API-OrderAction',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/order_action/try_to_send_action',
                array(
                    'action' => $action,
                    'order_id' => $order->getNumber()
                )
            ),
            false,
            $lengowOrder->getMarketplaceSku()
        );

        // TODO Finish all order error before new action

        try {
            $marketplace = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getMarketplaceSingleton(
                $lengowOrder->getMarketplaceName()
            );
            if ($marketplace->containOrderLine($action)) {
                $orderLines = self::getAllOrderLineIds($order);
                // get order lines by security
                if (!$orderLines) {
                    $orderLines = self::getOrderLineByApi($lengowOrder);
                }
                if (!$orderLines) {
                    throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'lengow_log/exception/order_line_required'
                        )
                    );
                }
                $results = array();
                foreach ($orderLines as $orderLine) {
                    $results[] = $marketplace->callAction($action, $order, $lengowOrder, $orderLine['orderLineId']);
                }
                $success = !in_array(false, $results);
            } else {
                $success = $marketplace->callAction($action, $order, $lengowOrder);
            }
        } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Shopware error] "' . $e->getMessage()
                . '" ' . $e->getFile() . ' | ' . $e->getLine();
        }
        if (isset($errorMessage)) {

            // TODO create order error

            $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($errorMessage);
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'API-OrderAction',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/order_action/call_action_failed',
                    array('decoded_message' => $decodedMessage)
                ),
                false,
                $lengowOrder->getMarketplaceSku()
            );
            $success = false;
        }
        if ($success) {
            $message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/order_action/action_send',
                array(
                    'action' => $action,
                    'order_id' => $order->getNumber()
                )
            );
        } else {
            $message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/order_action/action_not_send',
                array(
                    'action' => $action,
                    'order_id' => $order->getNumber()
                )
            );
        }
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'API-OrderAction',
            $message,
            false,
            $lengowOrder->getMarketplaceSku()
        );
        return $success;
    }

    /**
     * Get order line by API
     *
     * @param \Shopware\CustomModels\Lengow\Order $lengowOrder Lengow order instance
     *
     * @return array|false
     */
    public function getOrderLineByApi($lengowOrder)
    {
        $orderLines = array();
        $results = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
            'get',
            '/v3.0/orders',
            array(
                'marketplace_order_id' => $lengowOrder->getMarketplaceSku(),
                'marketplace' => $lengowOrder->getMarketplaceName()
            )
        );
        if (isset($results->count) && $results->count == 0) {
            return false;
        }
        $orderData = $results->results[0];
        foreach ($orderData->packages as $package) {
            $productLines = array();
            foreach ($package->cart as $product) {
                $productLines[] = array('orderLineId' => (string)$product->marketplace_order_line_id);
            }
            $orderLines[(int)$package->delivery->id] = $productLines;
        }
        $return = $orderLines[$lengowOrder->getDeliveryAddressId()];
        return count($return) > 0 ? $return : false;
    }

    /**
     * Get lengow order detail in order detail page
     *
     * @param $orderId
     * @return array|string
     */
    public static function getOrderDetailAction($orderId) {

        $keys = array(
            'order/details/' => array(
                'not_tracked_by_lengow',
                'not_lengow_order',
            )
        );
        $translations = Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::getTranslationsFromArray($keys);
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $repository = $em->getRepository('Shopware\CustomModels\Lengow\Order');
        $lengowOrder = $repository->findOneBy(array(
            'orderId' => $orderId
        ));
        if (Shopware_Plugins_Backend_Lengow_Components_LengowOrder::orderIsFromLengow($orderId) == 1) {
            if ($lengowOrder) {
                $data = Shopware()->Models()->toArray($lengowOrder);
                if (!Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowImportPreprodEnabled')) {
                    $data['canResendAction'] = true;
                }
            } else {
                $data = json_encode($translations['not_tracked_by_lengow']);
            }
        } else {
            $data = json_encode($translations['not_lengow_order']);
        }
        return $data;
    }
}
