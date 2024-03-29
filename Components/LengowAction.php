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

use Shopware\Models\Order\Order as OrderModel;
use Shopware\CustomModels\Lengow\Action as LengowActionModel;
use Shopware\CustomModels\Lengow\Order as LengowOrderModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap_Database as LengowBootstrapDatabase;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowConnector as LengowConnector;
use Shopware_Plugins_Backend_Lengow_Components_LengowException as LengowException;
use Shopware_Plugins_Backend_Lengow_Components_LengowImport as LengowImport;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrderError as LengowOrderError;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

/**
 * Lengow Action Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowAction
{
    /* Action states */
    const STATE_NEW = 0;
    const STATE_FINISH = 1;

    /* Action types */
    const TYPE_SHIP = 'ship';
    const TYPE_CANCEL = 'cancel';

    /* Action API arguments */
    const ARG_ACTION_TYPE = 'action_type';
    const ARG_LINE = 'line';
    const ARG_CARRIER = 'carrier';
    const ARG_CARRIER_NAME = 'carrier_name';
    const ARG_CUSTOM_CARRIER = 'custom_carrier';
    const ARG_SHIPPING_METHOD = 'shipping_method';
    const ARG_TRACKING_NUMBER = 'tracking_number';
    const ARG_TRACKING_URL = 'tracking_url';
    const ARG_SHIPPING_PRICE = 'shipping_price';
    const ARG_SHIPPING_DATE = 'shipping_date';
    const ARG_DELIVERY_DATE = 'delivery_date';

    /**
     * @var integer max interval time for action synchronisation (3 days)
     */
    const MAX_INTERVAL_TIME = 259200;

    /**
     * @var integer security interval time for action synchronisation (2 hours)
     */
    const SECURITY_INTERVAL_TIME = 7200;

    /**
     * @var array Parameters to delete for Get call
     */
    public static $getParamsToDelete = array(
        self::ARG_SHIPPING_DATE,
        self::ARG_DELIVERY_DATE,
    );

    /**
     * Create an order action
     *
     * @param OrderModel $order Shopware order instance
     * @param string $actionType action type (ship or cancel)
     * @param integer $actionId Lengow action id
     * @param string|null $orderLineId Lengow order line id
     * @param array $params order action parameters
     *
     * @return boolean
     */
    public static function createOrderAction($order, $actionType, $actionId, $orderLineId = null, $params = array())
    {
        try {
            $orderAction = new LengowActionModel();
            $orderAction->setOrder($order)
                ->setActionType($actionType)
                ->setActionId($actionId)
                ->setOrderLineSku($orderLineId)
                ->setParameters(json_encode($params))
                ->setState(self::STATE_NEW)
                ->setCreatedAt(new DateTime());
            Shopware()->Models()->persist($orderAction);
            Shopware()->Models()->flush($orderAction);
            LengowMain::log(
                LengowLog::CODE_ACTION,
                LengowMain::setLogMessage('log/order_action/action_saved'),
                false,
                $params[LengowImport::ARG_MARKETPLACE_ORDER_ID]
            );
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Indicates whether an action can be created if it does not already exist
     *
     * @param array $params all available values
     * @param OrderModel $order Shopware order instance
     *
     * @throws Exception|LengowException
     *
     * @return boolean
     */
    public static function canSendAction($params, $order)
    {
        $sendAction = true;
        // check if action is already created
        $getParams = array_merge($params, array('queued' => 'True'));
        // array key deletion for GET verification
        foreach (self::$getParamsToDelete as $param) {
            if (isset($getParams[$param])) {
                unset($getParams[$param]);
            }
        }
        $result = LengowConnector::queryApi(LengowConnector::GET, LengowConnector::API_ORDER_ACTION, $getParams);
        if (isset($result->error, $result->error->message)) {
            throw new LengowException($result->error->message);
        }
        if (isset($result->count) && $result->count > 0) {
            foreach ($result->results as $row) {
                $orderAction = Shopware()->Models()
                    ->getRepository('Shopware\CustomModels\Lengow\Action')
                    ->findOneBy(array('actionId' => $row->id));
                if ($orderAction) {
                    if ($orderAction->getState() === self::STATE_NEW) {
                        $orderAction->setRetry($orderAction->getRetry() + 1)
                            ->setUpdatedAt(new DateTime());
                        Shopware()->Models()->flush($orderAction);
                        $sendAction = false;
                    }
                } else {
                    // if update doesn't work, create new action
                    self::createOrderAction(
                        $order,
                        $params[self::ARG_ACTION_TYPE],
                        $row->id,
                        isset($params[self::ARG_LINE]) ? $params[self::ARG_LINE] : null,
                        $params
                    );
                    $sendAction = false;
                }
            }
        }
        return $sendAction;
    }

    /**
     * Send a new action on the order via the Lengow API
     *
     * @param array $params all available values
     * @param OrderModel $order Shopware order instance
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @throws LengowException
     */
    public static function sendAction($params, $order, $lengowOrder)
    {
        if (!LengowConfiguration::debugModeIsActive()) {
            $result = LengowConnector::queryApi(LengowConnector::POST, LengowConnector::API_ORDER_ACTION, $params);
            if (isset($result->id)) {
                self::createOrderAction(
                    $order,
                    $params[self::ARG_ACTION_TYPE],
                    $result->id,
                    isset($params[self::ARG_LINE]) ? $params[self::ARG_LINE] : null,
                    $params
                );
            } else {
                if ($result) {
                    $message = LengowMain::setLogMessage(
                        'lengow_log/exception/action_not_created',
                        array('error_message' => json_encode($result))
                    );
                } else {
                    // generating a generic error message when the Lengow API is unavailable
                    $message = LengowMain::setLogMessage('lengow_log/exception/action_not_created_api');
                }
                throw new LengowException($message);
            }
        }
        // create log for call action
        $paramList = false;
        foreach ($params as $param => $value) {
            $paramList .= !$paramList ? '"' . $param . '": ' . $value : ' -- "' . $param . '": ' . $value;
        }
        LengowMain::log(
            LengowLog::CODE_ACTION,
            LengowMain::setLogMessage('log/order_action/call_tracking', array('parameters' => $paramList)),
            false,
            $lengowOrder->getMarketplaceSku()
        );
    }

    /**
     * Get all active actions
     *
     * @return array|false
     */
    public static function getActiveActions()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('la.id', 'la.actionId', 'la.actionType', 'la.orderId')
            ->from('Shopware\CustomModels\Lengow\Action', 'la')
            ->where('la.state = :state')
            ->setParameters(array('state' => self::STATE_NEW));
        $results = $builder->getQuery()->getResult();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Find active actions by order id
     *
     * @param integer $orderId Shopware order id
     * @param boolean $onlyActive get only active actions
     * @param string|null $actionType action type (ship or cancel)
     *
     * @return LengowActionModel[]|null
     */
    public static function getActionByOrderId($orderId, $onlyActive = false, $actionType = null)
    {
        $params = array('orderId' => $orderId);
        if ($onlyActive) {
            $params['state'] = self::STATE_NEW;
        }
        if ($actionType) {
            $params['actionType'] = $actionType;
        }
        $actions = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Action')->findBy($params);
        if (!empty($actions)) {
            return $actions;
        }
        return null;
    }

    /**
     * Get last action of an order
     *
     * @param integer $orderId Shopware order id
     *
     * @return string|false
     */
    public static function getLastActionOrderType($orderId)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('s_lengow_action.actionType')
            ->from('Shopware\CustomModels\Lengow\Action', LengowBootstrapDatabase::TABLE_ACTION)
            ->where('s_lengow_action.orderId = :orderId')
            ->orderBy('s_lengow_action.id', 'DESC')
            ->setParameter('orderId', $orderId);
        $result = $builder->getQuery()->getArrayResult();
        return $result[0]['actionType'] ?: false;
    }

    /**
     * Finish action
     *
     * @param integer $id Lengow action id
     *
     * @return boolean
     */
    public static function finishAction($id)
    {
        $action = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Action')->find($id);
        if ($action) {
            try {
                $action->setState(self::STATE_FINISH);
                $action->setUpdatedAt(new DateTime());
                Shopware()->Models()->flush($action);
            } catch (Exception $e) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Removes all actions for one order Shopware
     *
     * @param integer $orderId Shopware order id
     * @param string|null $actionType action type (ship or cancel)
     *
     * @return boolean
     */
    public static function finishAllActions($orderId, $actionType = null)
    {
        // get all order action
        $params = array('orderId' => $orderId);
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('la.id')
            ->from('Shopware\CustomModels\Lengow\Action', 'la')
            ->where('la.orderId = :orderId');
        if ($actionType) {
            $builder->andWhere('la.actionType = :actionType');
            $params['actionType'] = $actionType;
        }
        $builder->setParameters($params);
        $results = $builder->getQuery()->getResult();
        if (!empty($results)) {
            foreach ($results as $result) {
                self::finishAction($result['id']);
            }
            return true;
        }
        return false;
    }

    /**
     * Get interval time for action synchronisation
     *
     * @return integer
     */
    public static function getIntervalTime()
    {
        $intervalTime = self::MAX_INTERVAL_TIME;
        $lastActionSynchronisation = LengowConfiguration::getConfig(
            LengowConfiguration::LAST_UPDATE_ACTION_SYNCHRONIZATION
        );
        if ($lastActionSynchronisation) {
            $lastIntervalTime = time() - (int) $lastActionSynchronisation;
            $lastIntervalTime += self::SECURITY_INTERVAL_TIME;
            $intervalTime = $lastIntervalTime > $intervalTime ? $intervalTime : $lastIntervalTime;
        }
        return $intervalTime;
    }

    /**
     * Check if active actions are finished
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public static function checkFinishAction($logOutput = false)
    {
        if (LengowConfiguration::debugModeIsActive()) {
            return false;
        }
        LengowMain::log(
            LengowLog::CODE_ACTION,
            LengowMain::setLogMessage('log/order_action/check_completed_action'),
            $logOutput
        );
        $processStateFinish = LengowOrder::getOrderProcessState(LengowOrder::STATE_CLOSED);
        // get all active actions by shop
        $activeActions = self::getActiveActions();
        if (!$activeActions) {
            return true;
        }
        // get all actions with API (max 3 days)
        $page = 1;
        $apiActions = array();
        $intervalTime = self::getIntervalTime();
        $dateFrom = time() - $intervalTime;
        $dateTo = time();
        LengowMain::log(
            LengowLog::CODE_ACTION,
            LengowMain::setLogMessage(
                'log/order_action/connector_get_all_action',
                array(
                    'date_from' => date(LengowMain::DATE_FULL, $dateFrom),
                    'date_to' => date(LengowMain::DATE_FULL, $dateTo),
                )
            ),
            $logOutput
        );
        do {
            $results = LengowConnector::queryApi(
                LengowConnector::GET,
                LengowConnector::API_ORDER_ACTION,
                array(
                    LengowImport::ARG_UPDATED_FROM => date(LengowMain::DATE_ISO_8601, $dateFrom),
                    LengowImport::ARG_UPDATED_TO => date(LengowMain::DATE_ISO_8601, $dateTo),
                    LengowImport::ARG_PAGE => $page,
                ),
                '',
                $logOutput
            );
            if (!is_object($results) || isset($results->error)) {
                break;
            }
            // construct array actions
            foreach ($results->results as $action) {
                if (isset($action->id)) {
                    $apiActions[$action->id] = $action;
                }
            }
            $page++;
        } while ($results->next !== null);
        if (empty($apiActions)) {
            return false;
        }
        // check foreach action if is complete
        foreach ($activeActions as $action) {
            if (!isset($apiActions[$action['actionId']])) {
                continue;
            }
            $apiAction = $apiActions[$action['actionId']];
            if (isset($apiAction->queued, $apiAction->processed, $apiAction->errors)) {
                if ($apiAction->queued == false) {
                    // order action is waiting to return from the marketplace
                    if ($apiAction->processed == false && empty($apiAction->errors)) {
                        continue;
                    }
                    // finish action in lengow_action table
                    self::finishAction($action['id']);
                    /** @var LengowOrderModel $lengowOrder */
                    $lengowOrder = Shopware()->Models()
                        ->getRepository('Shopware\CustomModels\Lengow\Order')
                        ->findOneBy(array('orderId' => $action['orderId']));
                    if ($lengowOrder) {
                        // finish all order logs send
                        LengowOrderError::finishOrderErrors($lengowOrder->getId(), LengowOrderError::TYPE_ERROR_SEND);
                        if ($lengowOrder->getOrderProcessState() !== $processStateFinish) {
                            try {
                                // if action is accepted -> close order and finish all order actions
                                if ($apiAction->processed == true && empty($apiAction->errors)) {
                                    $lengowOrder->setOrderProcessState($processStateFinish);
                                    self::finishAllActions($lengowOrder->getOrder()->getId());
                                } else {
                                    // if action is denied -> create order logs and finish all order actions
                                    LengowOrderError::createOrderError(
                                        $lengowOrder,
                                        $apiAction->errors,
                                        LengowOrderError::TYPE_ERROR_SEND
                                    );
                                    $lengowOrder->setInError(true);
                                    LengowMain::log(
                                        LengowLog::CODE_ACTION,
                                        LengowMain::setLogMessage(
                                            'log/order_action/call_action_failed',
                                            array('decoded_message' => $apiAction->errors)
                                        ),
                                        $logOutput,
                                        $lengowOrder->getMarketplaceSku()
                                    );
                                }
                                $lengowOrder->setUpdatedAt(new DateTime());
                                Shopware()->Models()->flush($lengowOrder);
                            } catch (Exception $e) {
                                $doctrineError = '[Doctrine error]: "' . $e->getMessage()
                                    . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
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
                    }
                    unset($lengowOrder);
                }
            }
        }
        LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_ACTION_SYNCHRONIZATION, time());
        return true;
    }

    /**
     * Remove old actions > 3 days
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public static function checkOldAction($logOutput = false)
    {
        if (LengowConfiguration::debugModeIsActive()) {
            return false;
        }
        LengowMain::log(
            LengowLog::CODE_ACTION,
            LengowMain::setLogMessage('log/order_action/check_old_action'),
            $logOutput
        );
        // get all old order action (+ 3 days)
        $processStateFinish = LengowOrder::getOrderProcessState(LengowOrder::STATE_CLOSED);
        $actions = self::getOldActions();
        if ($actions) {
            foreach ($actions as $action) {
                self::finishAction($action['id']);
                /** @var LengowOrderModel $lengowOrder */
                $lengowOrder = Shopware()->Models()
                    ->getRepository('Shopware\CustomModels\Lengow\Order')
                    ->findOneBy(array('orderId' => $action['orderId']));
                if ($lengowOrder
                    && $lengowOrder->getOrderProcessState() !== $processStateFinish
                    && !$lengowOrder->isInError()
                ) {
                    // if action is denied -> create order error
                    $errorMessage = LengowMain::setLogMessage('lengow_log/exception/action_is_too_old');
                    LengowOrderError::createOrderError(
                        $lengowOrder,
                        $errorMessage,
                        LengowOrderError::TYPE_ERROR_SEND
                    );
                    try {
                        $lengowOrder->setInError(true)->setUpdatedAt(new DateTime());
                        Shopware()->Models()->flush($lengowOrder);
                    } catch (Exception $e) {
                        $doctrineError = '[Doctrine error]: "' . $e->getMessage()
                            . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                        LengowMain::log(
                            LengowLog::CODE_ORM,
                            LengowMain::setLogMessage(
                                'log/exception/order_insert_failed',
                                array('decoded_message' => $doctrineError)
                            ),
                            $logOutput,
                            $lengowOrder->getMarketplaceSku()
                        );
                    }
                    $decodedMessage = LengowMain::decodeLogMessage($errorMessage, LengowTranslation::DEFAULT_ISO_CODE);
                    LengowMain::log(
                        LengowLog::CODE_ACTION,
                        LengowMain::setLogMessage(
                            'log/order_action/call_action_failed',
                            array('decoded_message' => $decodedMessage)
                        ),
                        false,
                        $lengowOrder->getMarketplaceSku()
                    );
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Get old untreated actions of more than 3 days
     *
     * @return array|false
     */
    public static function getOldActions()
    {
        $params = array(
            'state' => self::STATE_NEW,
            'createdAt' => date('Y-m-d h:m:i', (time() - self::MAX_INTERVAL_TIME)),
        );
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('la.id', 'la.orderId')
            ->from('Shopware\CustomModels\Lengow\Action', 'la')
            ->where('la.state = :state')
            ->andWhere('la.createdAt <= :createdAt');
        $builder->setParameters($params);
        $results = $builder->getQuery()->getResult();
        return !empty($results) ? $results : false;
    }

    /**
     * Check if actions are not sent
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public static function checkActionNotSent($logOutput = false)
    {
        if (LengowConfiguration::debugModeIsActive()) {
            return false;
        }
        LengowMain::log(
            LengowLog::CODE_ACTION,
            LengowMain::setLogMessage('log/order_action/check_action_not_sent'),
            $logOutput
        );
        // get unsent orders
        $unsentOrders = LengowOrder::getUnsentOrders();
        if ($unsentOrders) {
            foreach ($unsentOrders as $idOrder => $actionType) {
                /** @var OrderModel $order */
                $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($idOrder);
                LengowOrder::callAction($order, $actionType);
            }
            return true;
        }
        return false;
    }
}
