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

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\NonUniqueResultException;
use Shopware\Models\Order\History as OrderHistoryModel;
use Shopware\Models\Order\Order as OrderModel;
use Shopware\Models\Order\Status as OrderStatusModel;
use Shopware\CustomModels\Lengow\Order as LengowOrderModel;
use Shopware_Plugins_Backend_Lengow_Components_LengowAction as LengowAction;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowConnector as LengowConnector;
use Shopware_Plugins_Backend_Lengow_Components_LengowException as LengowException;
use Shopware_Plugins_Backend_Lengow_Components_LengowImport as LengowImport;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrderError as LengowOrderError;

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
     * @var string order state accepted
     */
    const STATE_ACCEPTED = 'accepted';

    /**
     * @var string order state waiting_shipment
     */
    const STATE_WAITING_SHIPMENT = 'waiting_shipment';

    /**
     * @var string order state shipped
     */
    const STATE_SHIPPED = 'shipped';

    /**
     * @var string order state closed
     */
    const STATE_CLOSED = 'closed';

    /**
     * @var string order state refused
     */
    const STATE_REFUSED = 'refused';

    /**
     * @var string order state canceled
     */
    const STATE_CANCELED = 'canceled';

    /**
     * @var string order state refunded
     */
    const STATE_REFUNDED = 'refunded';

    /**
     * @var string order type prime
     */
    const TYPE_PRIME = 'is_prime';

    /**
     * @var string order type express
     */
    const TYPE_EXPRESS = 'is_express';

    /**
     * @var string order type business
     */
    const TYPE_BUSINESS = 'is_business';

    /**
     * @var string order type delivered by marketplace
     */
    const TYPE_DELIVERED_BY_MARKETPLACE = 'is_delivered_by_marketplace';

    /**
     * @var string label fulfillment for old orders without order type
     */
    const LABEL_FULFILLMENT = 'Fulfillment';

    /**
     * Get Shopware order id from lengow order table
     *
     * @param string $marketplaceSku Lengow order id
     * @param string $marketplaceName marketplace name
     * @param integer $deliveryAddressId Lengow delivery address id
     *
     * @throws Exception
     *
     * @return OrderModel|false
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
                    'orderProcessState' => 0,
                )
            );
        $result['orderId'] = $builder->getQuery()->getOneOrNullResult();
        if ($result['orderId'] !== null) {
            /** @var OrderModel $order */
            $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')
                ->findOneBy(array('id' => $result['orderId']));
            if ($order !== null) {
                return $order;
            }
        }
        return false;
    }

    /**
     * Check if an order has an error
     *
     * @param string $marketplaceSku Marketplace sku
     * @param integer $deliveryAddressId Lengow delivery address id
     * @param string $type order error type (import or send)
     *
     * @return array|false
     */
    public static function orderIsInError($marketplaceSku, $deliveryAddressId, $type = 'import')
    {
        $type = LengowOrderError::getOrderErrorType($type);
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('loe.id', 'loe.message', 'loe.createdAt'))
            ->from('Shopware\CustomModels\Lengow\Order', 'lo')
            ->leftJoin(
                'Shopware\CustomModels\Lengow\OrderError',
                'loe',
                Join::WITH,
                'lo.id = loe.lengowOrderId'
            )
            ->where('lo.marketplaceSku = :marketplaceSku')
            ->andWhere('lo.deliveryAddressId = :deliveryAddressId')
            ->andWhere('loe.type = :type')
            ->andWhere('loe.isFinished != :isFinished')
            ->setParameters(
                array(
                    'marketplaceSku' => $marketplaceSku,
                    'deliveryAddressId' => $deliveryAddressId,
                    'type' => $type,
                    'isFinished' => true,
                )
            );
        $results = $builder->getQuery()->getResult();
        if (empty($results)) {
            return false;
        }
        return $results[0];
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
                    'deliveryAddressId' => $deliveryAddressId,
                )
            );
        try {
            $result = $builder->getQuery()->getOneOrNullResult();
            if ($result['id'] !== null) {
                return (int)$result['id'];
            }
        } catch (NonUniqueResultException $e) {
            return false;
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
                    'marketplaceName' => $marketplaceName,
                )
            );
        $results = $builder->getQuery()->getResult();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Get all Lengow order line ids from marketplace order
     *
     * @param OrderModel $order Shopware order instance
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
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Get Shopware order id by number
     *
     * @param string $number Shopware order number
     *
     * @return integer|false
     */
    public static function getOrderIdByNumber($number)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('o.id')
            ->from('Shopware\Models\Order\Order', 'o')
            ->where('o.number = :number')
            ->setParameters(array('number' => $number));
        try {
            $result = $builder->getQuery()->getOneOrNullResult();
            if ($result['id'] !== null) {
                return (int)$result['id'];
            }
        } catch (NonUniqueResultException $e) {
            return false;
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
        $isFromLengow = false;
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('lo.id')
            ->from('Shopware\CustomModels\Lengow\Order', 'lo')
            ->where('lo.orderId = :orderId')
            ->setParameters(array('orderId' => $orderId));
        try {
            $result = $builder->getQuery()->getOneOrNullResult();
            if ($result !== null) {
                $isFromLengow = true;
            }
        } catch (NonUniqueResultException $e) {
            $isFromLengow = false;
        }
        return $isFromLengow;
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
            case self::STATE_ACCEPTED:
            case self::STATE_WAITING_SHIPMENT:
                return self::PROCESS_STATE_IMPORT;
            case self::STATE_SHIPPED:
            case self::STATE_CLOSED:
            case self::STATE_REFUSED:
            case self::STATE_CANCELED:
            case self::STATE_REFUNDED:
                return self::PROCESS_STATE_FINISH;
            default:
                return false;
        }
    }

    /**
     * Count order lengow with error
     *
     * @return integer
     */
    public static function countOrderWithError()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('lo.orderId'))
            ->from('Shopware\CustomModels\Lengow\Order', 'lo')
            ->where('lo.inError = true');
        $results = $builder->getQuery()->getResult();
        return count($results);
    }

    /**
     * Count order to be sent
     *
     * @return integer
     */
    public static function countOrderToBeSent()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('lo.orderId'))
            ->from('Shopware\CustomModels\Lengow\Order', 'lo')
            ->where('lo.orderProcessState = 1');
        $results = $builder->getQuery()->getResult();
        return count($results);
    }

    /**
     * Get all unset orders
     *
     * @return array|false
     */
    public static function getUnsentOrders()
    {
        try {
            $changeDate = new DateTime(date('Y-m-d h:m:i', strtotime('-5 days', time())));
        } catch (Exception $e) {
            return false;
        }
        $unsentOrders = array();
        $orderStatusShipped = LengowMain::getOrderStatus(LengowOrder::STATE_SHIPPED);
        $orderStatusCanceled = LengowMain::getOrderStatus(LengowOrder::STATE_CANCELED);
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('lo.orderId', 'oh.orderStatusId'))
            ->from('Shopware\CustomModels\Lengow\Order', 'lo')
            ->leftJoin('lo.order', 'o')
            ->leftJoin('o.history', 'oh')
            ->where('lo.orderProcessState = :orderProcessState')
            ->andWhere('lo.inError = :inError')
            ->andWhere('o.orderStatus IN (:orderStatusShipped, :orderStatusCanceled)')
            ->andWhere('oh.changeDate >= :changeDate')
            ->groupBy('lo.orderId')
            ->setParameters(
                array(
                    'orderProcessState' => self::PROCESS_STATE_IMPORT,
                    'inError' => false,
                    'orderStatusShipped' => $orderStatusShipped,
                    'orderStatusCanceled' => $orderStatusCanceled,
                    'changeDate' => $changeDate,
                )
            );
        $results = $builder->getQuery()->getResult();
        if (!empty($results)) {
            foreach ($results as $result) {
                $orderId = (int)$result['orderId'];
                if (!LengowAction::getActiveActionByOrderId($orderId)) {
                    $action = (int)$result['orderStatusId'] === $orderStatusCanceled->getId()
                        ? LengowAction::TYPE_CANCEL
                        : LengowAction::TYPE_SHIP;
                    $unsentOrders[$orderId] = $action;
                }
            }
        }
        return !empty($unsentOrders) ? $unsentOrders : false;
    }

    /**
     * Update order status
     *
     * @param OrderModel $order Shopware order instance
     * @param LengowOrderModel $lengowOrder Lengow order instance
     * @param string $orderStateLengow marketplace state
     * @param mixed $packageData package data
     * @param boolean $logOutput output on screen
     *
     * @throws Exception
     *
     * @return string|false
     */
    public static function updateState($order, $lengowOrder, $orderStateLengow, $packageData, $logOutput)
    {
        $flushLengowOrder = false;
        $orderProcessState = self::getOrderProcessState($orderStateLengow);
        $trackingNumber = !empty($packageData->delivery->trackings)
            ? (string)$packageData->delivery->trackings[0]->number
            : null;
        // update Lengow order if necessary
        if ($lengowOrder->getOrderLengowState() !== $orderStateLengow) {
            $lengowOrder->setOrderLengowState($orderStateLengow)
                ->setCarrierTracking($trackingNumber);
            $flushLengowOrder = true;
        }
        if ($orderProcessState === self::PROCESS_STATE_FINISH) {
            // finish actions and order errors if lengow order is shipped, closed, cancel or refunded
            LengowAction::finishAllActions($order->getId());
            LengowOrderError::finishOrderErrors($lengowOrder->getId(), 'send');
            if ($lengowOrder->getOrderProcessState() !== $orderProcessState) {
                $lengowOrder->setOrderProcessState($orderProcessState);
                $flushLengowOrder = true;
            }
        }
        if ($flushLengowOrder) {
            $lengowOrder->setUpdatedAt(new DateTime());
            Shopware()->Models()->flush($lengowOrder);
        }
        // get Shopware equivalent order status to Lengow API state
        $orderStatus = LengowMain::getOrderStatus($orderStateLengow);
        $waitingShipmentOrderStatus = LengowMain::getOrderStatus(LengowOrder::STATE_ACCEPTED);
        $shippedOrderStatus = LengowMain::getOrderStatus(LengowOrder::STATE_SHIPPED);
        // if state is different between API and Shopware
        if (($orderStatus && $waitingShipmentOrderStatus && $shippedOrderStatus)
            && ($order->getOrderStatus()->getId() !== $orderStatus->getId())
        ) {
            // change state process to shipped
            if ($order->getOrderStatus()->getId() === $waitingShipmentOrderStatus->getId()
                && ($orderStateLengow === LengowOrder::STATE_SHIPPED
                    || $orderStateLengow === LengowOrder::STATE_CLOSED
                )
            ) {
                self::createOrderHistory($order, $shippedOrderStatus, $logOutput, $lengowOrder->getMarketplaceSku());
                self::updateOrderStatus($order->getId(), $shippedOrderStatus->getId());
                if ($trackingNumber) {
                    $order->setTrackingCode($trackingNumber);
                    Shopware()->Models()->flush($order);
                }
                return 'Shipped';
            } elseif (($order->getOrderStatus()->getId() === $waitingShipmentOrderStatus->getId()
                    || $order->getOrderStatus()->getId() === $shippedOrderStatus->getId()
                ) && ($orderStateLengow === LengowOrder::STATE_CANCELED
                    || $orderStateLengow === LengowOrder::STATE_REFUSED
                )
            ) {
                $canceledOrderStatus = LengowMain::getOrderStatus(LengowOrder::STATE_CANCELED);
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
     * @param OrderModel $order Shopware order instance
     * @param OrderStatusModel $newOrderStatus Shopware order status instance
     * @param boolean $logOutput output on screen
     * @param string|null $marketplaceSku Lengow marketplace sku
     *
     * @return boolean
     */
    public static function createOrderHistory($order, $newOrderStatus, $logOutput = false, $marketplaceSku = null)
    {
        try {
            $orderHistory = new OrderHistoryModel();
            $orderHistory->setOrder($order)
                ->setPreviousOrderStatus($order->getOrderStatus())
                ->setOrderStatus($newOrderStatus)
                ->setPreviousPaymentStatus($order->getPaymentStatus())
                ->setPaymentStatus($order->getPaymentStatus())
                ->setChangeDate(new \datetime());
            // get all admin user
            $users = LengowMain::getAllAdminUsers();
            if (!empty($users)) {
                $orderHistory->setUser($users[0]);
            }
            Shopware()->Models()->persist($orderHistory);
            Shopware()->Models()->flush();
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
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
     * @param OrderModel $order Shopware order instance
     * @param LengowConnector|null $connector Lengow connector instance
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public static function synchronizeOrder($order, $connector = null, $logOutput = false)
    {
        $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
            ->findOneBy(array('order' => $order));
        if ($lengowOrder === null) {
            return false;
        }
        $accessIds = LengowConfiguration::getAccessIds();
        list($accountId, $accessToken, $secretToken) = $accessIds;
        if ($connector === null) {
            if (LengowConnector::isValidAuth($logOutput)) {
                $connector = new LengowConnector($accessToken, $secretToken);
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
            try {
                $result = $connector->patch(
                    LengowConnector::API_ORDER_MOI,
                    array(
                        'account_id' => $accountId,
                        'marketplace_order_id' => $lengowOrder->getMarketplaceSku(),
                        'marketplace' => $lengowOrder->getMarketplaceName(),
                        'merchant_order_id' => $shopwareIds,
                    ),
                    LengowConnector::FORMAT_JSON,
                    '',
                    $logOutput
                );
            } catch (Exception $e) {
                $message = LengowMain::decodeLogMessage($e->getMessage());
                $error = LengowMain::setLogMessage(
                    'log/connector/error_api',
                    array(
                        'error_code' => $e->getCode(),
                        'error_message' => $message,
                    )
                );
                LengowMain::log(LengowLog::CODE_CONNECTOR, $error, $logOutput);
                return false;
            }
            if ($result === null
                || (isset($result['detail']) && $result['detail'] === 'Pas trouvé.')
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
     * Re-import order
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @return array|false
     */
    public static function reImportOrder($lengowOrder)
    {
        if ($lengowOrder->getOrderProcessState() === 0 && $lengowOrder->isInError()) {
            $params = array(
                'type' => LengowImport::TYPE_MANUAL,
                'lengow_order_id' => $lengowOrder->getId(),
                'marketplace_sku' => $lengowOrder->getMarketplaceSku(),
                'marketplace_name' => $lengowOrder->getMarketplaceName(),
                'delivery_address_id' => $lengowOrder->getDeliveryAddressId(),
                'shop_id' => $lengowOrder->getShopId(),
            );
            $import = new LengowImport($params);
            return $import->exec();
        }
        return false;
    }

    /**
     * Re-send order
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @return boolean
     */
    public static function reSendOrder($lengowOrder)
    {
        if ($lengowOrder->getOrderProcessState() === 1 && $lengowOrder->isInError()) {
            $order = $lengowOrder->getOrder();
            if ($order) {
                $action = LengowAction::getLastActionOrderType($order->getId());
                if (!$action) {
                    $orderStatusCanceled = LengowMain::getOrderStatus(LengowOrder::STATE_CANCELED);
                    $action = $orderStatusCanceled->getId() === $order->getOrderStatus()->getId()
                        ? LengowAction::TYPE_CANCEL
                        : LengowAction::TYPE_SHIP;
                }
                return LengowOrder::callAction($order, $action);
            }
        }
        return false;
    }

    /**
     * Cancel and re-import order
     *
     * @param OrderModel $order Shopware order instance
     *
     * @return array|false
     */
    public static function cancelAndReImportOrder($order)
    {
        /** @var LengowOrderModel $lengowOrder */
        $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
            ->findOneBy(array('order' => $order));
        if ($lengowOrder === null) {
            return false;
        }
        if (!self::isReimported($lengowOrder)) {
            return false;
        }
        $params = array(
            'marketplace_sku' => $lengowOrder->getMarketplaceSku(),
            'marketplace_name' => $lengowOrder->getMarketplaceName(),
            'delivery_address_id' => $lengowOrder->getDeliveryAddressId(),
            'shop_id' => $lengowOrder->getShopId(),
        );
        // import orders
        $import = new LengowImport($params);
        $result = $import->exec();
        if ((isset($result['order_id']) && $result['order_id'] != $order->getId())
            && (isset($result['order_new']) && $result['order_new'])
        ) {
            $newOrder = Shopware()->Models()
                ->getRepository('\Shopware\Models\Order\Order')
                ->findOneBy(array('id' => $result['order_id']));
            if ($newOrder) {
                $newStatus = LengowMain::getLengowTechnicalErrorStatus();
                if ($newStatus) {
                    self::createOrderHistory($order, $newStatus);
                    self::updateOrderStatus($order->getId(), $newStatus->getId());
                }
                return array(
                    'marketplace_sku' => $lengowOrder->getMarketplaceSku(),
                    'order_sku' => $newOrder->getNumber(),
                    'order_id' => $newOrder->getId(),
                );
            }
        }
        return false;
    }

    /**
     * Mark Lengow order as is_reimported in lengow_order table
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @return boolean
     */
    public static function isReimported($lengowOrder)
    {
        try {
            $lengowOrder->setReimported(true);
            Shopware()->Models()->flush($lengowOrder);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Check if order is express
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @return boolean
     */
    public static function isExpress($lengowOrder)
    {
        $orderTypes = (string)$lengowOrder->getOrderTypes();
        $orderTypes = $orderTypes !== '' ? json_decode($orderTypes, true) : array();
        if (isset($orderTypes[self::TYPE_EXPRESS]) || isset($orderTypes[self::TYPE_PRIME])) {
            return true;
        }
        return false;
    }

    /**
     * Check if order is B2B
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @return boolean
     */
    public static function isBusiness($lengowOrder)
    {
        $orderTypes = (string)$lengowOrder->getOrderTypes();
        $orderTypes = $orderTypes !== '' ? json_decode($orderTypes, true) : array();
        if (isset($orderTypes[self::TYPE_BUSINESS])) {
            return true;
        }
        return false;
    }

    /**
     * Check if order is delivered by marketplace
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @return boolean
     */
    public static function isDeliveredByMarketplace($lengowOrder)
    {
        $orderTypes = (string)$lengowOrder->getOrderTypes();
        $orderTypes = $orderTypes !== '' ? json_decode($orderTypes, true) : array();
        if (isset($orderTypes[self::TYPE_DELIVERED_BY_MARKETPLACE]) || $lengowOrder->isSentByMarketplace()) {
            return true;
        }
        return false;
    }

    /**
     * Send Order action
     *
     * @param OrderModel $order Shopware order instance
     * @param string $action Lengow Actions type (ship or cancel)
     *
     * @return boolean
     */
    public static function callAction($order, $action)
    {
        $success = true;
        /** @var LengowOrderModel $lengowOrder */
        $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
            ->findOneBy(array('order' => $order));
        if ($lengowOrder === null) {
            return false;
        }
        LengowMain::log(
            LengowLog::CODE_ACTION,
            LengowMain::setLogMessage(
                'log/order_action/try_to_send_action',
                array(
                    'action' => $action,
                    'order_id' => $order->getNumber(),
                )
            ),
            false,
            $lengowOrder->getMarketplaceSku()
        );
        try {
            // finish all order errors before API call
            LengowOrderError::finishOrderErrors($lengowOrder->getId(), 'send');
            if ($lengowOrder->isInError()) {
                $lengowOrder->setInError(false);
                Shopware()->Models()->flush($lengowOrder);
            }
            $marketplace = LengowMain::getMarketplaceSingleton($lengowOrder->getMarketplaceName());
            if ($marketplace->containOrderLine($action)) {
                $orderLines = self::getAllOrderLineIds($order);
                // get order lines by security
                if (!$orderLines) {
                    $orderLines = self::getOrderLineByApi($lengowOrder);
                }
                if (!$orderLines) {
                    throw new LengowException(LengowMain::setLogMessage('lengow_log/exception/order_line_required'));
                }
                $results = array();
                foreach ($orderLines as $orderLine) {
                    $results[] = $marketplace->callAction($action, $order, $lengowOrder, $orderLine['orderLineId']);
                }
                $success = !in_array(false, $results);
            } else {
                $success = $marketplace->callAction($action, $order, $lengowOrder);
            }
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Shopware error] "' . $e->getMessage()
                . '" ' . $e->getFile() . ' | ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ($lengowOrder->getOrderProcessState() !== self::PROCESS_STATE_FINISH) {
                LengowOrderError::createOrderError($lengowOrder, $errorMessage, 'send');
                try {
                    $lengowOrder->setInError(true);
                    Shopware()->Models()->flush($lengowOrder);
                } catch (Exception $e) {
                    $doctrineError = '[Doctrine error] "' . $e->getMessage() . '" '
                        . $e->getFile() . ' | ' . $e->getLine();
                    LengowMain::log(
                        LengowLog::CODE_ORM,
                        LengowMain::setLogMessage(
                            'log/exception/order_insert_failed',
                            array('decoded_message' => $doctrineError)
                        ),
                        false,
                        $lengowOrder->getMarketplaceSku()
                    );
                }
            }
            LengowMain::log(
                LengowLog::CODE_ACTION,
                LengowMain::setLogMessage(
                    'log/order_action/call_action_failed',
                    array('decoded_message' => LengowMain::decodeLogMessage($errorMessage))
                ),
                false,
                $lengowOrder->getMarketplaceSku()
            );
            $success = false;
        }
        if ($success) {
            $message = LengowMain::setLogMessage(
                'log/order_action/action_send',
                array(
                    'action' => $action,
                    'order_id' => $order->getNumber(),
                )
            );
        } else {
            $message = LengowMain::setLogMessage(
                'log/order_action/action_not_send',
                array(
                    'action' => $action,
                    'order_id' => $order->getNumber(),
                )
            );
        }
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            LengowLog::CODE_ACTION,
            $message,
            false,
            $lengowOrder->getMarketplaceSku()
        );
        return $success;
    }

    /**
     * Get order line by API
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @return array|false
     */
    public static function getOrderLineByApi($lengowOrder)
    {
        $orderLines = array();
        $results = LengowConnector::queryApi(
            LengowConnector::GET,
            LengowConnector::API_ORDER,
            array(
                'marketplace_order_id' => $lengowOrder->getMarketplaceSku(),
                'marketplace' => $lengowOrder->getMarketplaceName(),
            )
        );
        if (isset($results->count) && (int)$results->count === 0) {
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
        return !empty($return) ? $return : false;
    }
}
