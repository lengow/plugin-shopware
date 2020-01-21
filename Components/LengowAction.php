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

use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;

/**
 * Lengow Action Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowAction
{
    /**
     * @var integer action state for new action
     */
    const STATE_NEW = 0;

    /**
     * @var integer action state for action finished
     */
    const STATE_FINISH = 1;

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
        'shipping_date',
        'delivery_date',
    );

    /**
     * Create an order action
     *
     * @param \Shopware\Models\Order\Order $order Shopware order instance
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
            $orderAction = new Shopware\CustomModels\Lengow\Action();
            $orderAction->setOrder($order)
                ->setActionType($actionType)
                ->setActionId($actionId)
                ->setOrderLineSku($orderLineId)
                ->setParameters(json_encode($params))
                ->setState(self::STATE_NEW)
                ->setCreatedAt(new DateTime());
            Shopware()->Models()->persist($orderAction);
            Shopware()->Models()->flush($orderAction);
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'API-OrderAction',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/order_action/action_saved'),
                false,
                $params['marketplace_order_id']
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
     * @param \Shopware\Models\Order\Order $order Shopware order instance
     *
     * @throws Exception|Shopware_Plugins_Backend_Lengow_Components_LengowException
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
        $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
            Shopware_Plugins_Backend_Lengow_Components_LengowConnector::GET,
            Shopware_Plugins_Backend_Lengow_Components_LengowConnector::API_ORDER_ACTION,
            $getParams
        );
        if (isset($result->error) && isset($result->error->message)) {
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException($result->error->message);
        }
        if (isset($result->count) && $result->count > 0) {
            foreach ($result->results as $row) {
                $orderAction = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Action')
                    ->findOneBy(array('actionId' => $row->id));
                if ($orderAction) {
                    $stateNew = Shopware_Plugins_Backend_Lengow_Components_LengowAction::STATE_NEW;
                    if ($orderAction->getState() === $stateNew) {
                        $orderAction->setRetry($orderAction->getRetry() + 1)
                            ->setUpdatedAt(new DateTime());
                        Shopware()->Models()->flush($orderAction);
                        $sendAction = false;
                    }
                } else {
                    // if update doesn't work, create new action
                    self::createOrderAction(
                        $order,
                        $params['action_type'],
                        $row->id,
                        isset($params['line']) ? $params['line'] : null,
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
     * @param \Shopware\Models\Order\Order $order Shopware order instance
     * @param \Shopware\CustomModels\Lengow\Order $lengowOrder Lengow order instance
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException
     */
    public static function sendAction($params, $order, $lengowOrder)
    {
        $preprodMode = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowImportPreprodEnabled'
        );
        if (!$preprodMode) {
            $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
                Shopware_Plugins_Backend_Lengow_Components_LengowConnector::POST,
                Shopware_Plugins_Backend_Lengow_Components_LengowConnector::API_ORDER_ACTION,
                $params
            );
            if (isset($result->id)) {
                Shopware_Plugins_Backend_Lengow_Components_LengowAction::createOrderAction(
                    $order,
                    $params['action_type'],
                    $result->id,
                    isset($params['line']) ? $params['line'] : null,
                    $params
                );
            } else {
                if ($result !== null) {
                    $message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/action_not_created',
                        array('error_message' => json_encode($result))
                    );
                } else {
                    // generating a generic error message when the Lengow API is unavailable
                    $message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/action_not_created_api'
                    );
                }
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException($message);
            }
        }
        // create log for call action
        $paramList = false;
        foreach ($params as $param => $value) {
            $paramList .= !$paramList ? '"' . $param . '": ' . $value : ' -- "' . $param . '": ' . $value;
        }
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'API-OrderAction',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/order_action/call_tracking',
                array('parameters' => $paramList)
            ),
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
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }

    /**
     * Find active actions by order id
     *
     * @param integer $orderId Shopware order id
     * @param string|null $actionType action type (ship or cancel)
     *
     * @return array|false
     */
    public static function getActiveActionByOrderId($orderId, $actionType = null)
    {
        $params = array(
            'orderId' => $orderId,
            'state' => self::STATE_NEW,
        );
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('la.id', 'la.actionId', 'la.actionType', 'la.orderId')
            ->from('Shopware\CustomModels\Lengow\Action', 'la')
            ->where('la.state = :state')
            ->andWhere('la.orderId = :orderId');
        if ($actionType) {
            $builder->andWhere('la.actionType = :actionType');
            $params['actionType'] = $actionType;
        }
        $builder->setParameters($params);
        $results = $builder->getQuery()->getResult();
        if (count($results) > 0) {
            return $results;
        }
        return false;
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
            ->from('Shopware\CustomModels\Lengow\Action', 's_lengow_action')
            ->where('s_lengow_action.orderId = :orderId')
            ->orderBy('s_lengow_action.id', 'DESC')
            ->setParameter('orderId', $orderId);
        $result = $builder->getQuery()->getArrayResult();
        return $result[0]['actionType'] ? $result[0]['actionType'] : false;
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
        if (count($results) > 0) {
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
        $lastActionSynchronisation = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowLastActionSync'
        );
        if ($lastActionSynchronisation) {
            $lastIntervalTime = time() - (int)$lastActionSynchronisation;
            $lastIntervalTime = $lastIntervalTime + self::SECURITY_INTERVAL_TIME;
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
        $preprodMode = (bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowImportPreprodEnabled'
        );
        if ($preprodMode) {
            return false;
        }
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'API-OrderAction',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/order_action/check_completed_action'
            ),
            $logOutput
        );
        $processStateFinish = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getOrderProcessState('closed');
        // get all active actions by shop
        $activeActions = self::getActiveActions();
        if (!$activeActions) {
            return true;
        }
        // get all actions with API for 3 days
        $page = 1;
        $apiActions = array();
        $intervalTime = self::getIntervalTime();
        $dateFrom = date('c', (time() - $intervalTime));
        $dateTo = date('c');
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'API-OrderAction',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/order_action/connector_get_all_action',
                array(
                    'date_from' => date('Y-m-d H:i:s', strtotime($dateFrom)),
                    'date_to' => date('Y-m-d H:i:s', strtotime($dateTo)),
                )
            ),
            $logOutput
        );
        do {
            $results = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
                Shopware_Plugins_Backend_Lengow_Components_LengowConnector::GET,
                Shopware_Plugins_Backend_Lengow_Components_LengowConnector::API_ORDER_ACTION,
                array(
                    'updated_from' => $dateFrom,
                    'updated_to' => $dateTo,
                    'page' => $page,
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
            if (isset($apiActions[$action['actionId']]->queued)
                && isset($apiActions[$action['actionId']]->processed)
                && isset($apiActions[$action['actionId']]->errors)
            ) {
                if ($apiActions[$action['actionId']]->queued == false) {
                    // order action is waiting to return from the marketplace
                    if ($apiActions[$action['actionId']]->processed == false
                        && empty($apiActions[$action['actionId']]->errors)
                    ) {
                        continue;
                    }
                    // finish action in lengow_action table
                    self::finishAction($action['id']);
                    /** @var Shopware\CustomModels\Lengow\Order $lengowOrder */
                    $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
                        ->findOneBy(array('orderId' => $action['orderId']));
                    if ($lengowOrder) {
                        // finish all order logs send
                        Shopware_Plugins_Backend_Lengow_Components_LengowOrderError::finishOrderErrors(
                            $lengowOrder->getId(),
                            'send'
                        );
                        if ($lengowOrder->getOrderProcessState() != $processStateFinish) {
                            try {
                                // if action is accepted -> close order and finish all order actions
                                if ($apiActions[$action['actionId']]->processed == true
                                    && empty($apiActions[$action['actionId']]->errors)
                                ) {
                                    $lengowOrder->setOrderProcessState($processStateFinish);
                                    self::finishAllActions($lengowOrder->getOrder()->getId());
                                } else {
                                    // if action is denied -> create order logs and finish all order actions
                                    Shopware_Plugins_Backend_Lengow_Components_LengowOrderError::createOrderError(
                                        $lengowOrder,
                                        $apiActions[$action['actionId']]->errors,
                                        'send'
                                    );
                                    $lengowOrder->setInError(true);
                                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                                        'API-OrderAction',
                                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                            'log/order_action/call_action_failed',
                                            array('decoded_message' => $apiActions[$action['actionId']]->errors)
                                        ),
                                        $logOutput,
                                        $lengowOrder->getMarketplaceSku()
                                    );
                                }
                                $lengowOrder->setUpdatedAt(new DateTime());
                                Shopware()->Models()->flush($lengowOrder);
                            } catch (Exception $e) {
                                $doctrineError = '[Doctrine error] "' . $e->getMessage()
                                    . '" ' . $e->getFile() . ' | ' . $e->getLine();
                                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                                    'Orm',
                                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
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
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig('lengowLastActionSync', time());
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
        $preprodMode = (bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowImportPreprodEnabled'
        );
        if ($preprodMode) {
            return false;
        }
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'API-OrderAction',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/order_action/check_old_action'),
            $logOutput
        );
        // get all old order action (+ 3 days)
        $processStateFinish = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getOrderProcessState('closed');
        $actions = self::getOldActions();
        if ($actions) {
            foreach ($actions as $action) {
                self::finishAction($action['id']);
                /** @var Shopware\CustomModels\Lengow\Order $lengowOrder */
                $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
                    ->findOneBy(array('orderId' => $action['orderId']));
                if ($lengowOrder) {
                    if ($lengowOrder->getOrderProcessState() != $processStateFinish && !$lengowOrder->isInError()) {
                        // if action is denied -> create order error
                        $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'lengow_log/exception/action_is_too_old'
                        );
                        Shopware_Plugins_Backend_Lengow_Components_LengowOrderError::createOrderError(
                            $lengowOrder,
                            $errorMessage,
                            'send'
                        );
                        try {
                            $lengowOrder->setInError(true)
                                ->setUpdatedAt(new DateTime());
                            Shopware()->Models()->flush($lengowOrder);
                        } catch (Exception $e) {
                            $doctrineError = '[Doctrine error] "' . $e->getMessage()
                                . '" ' . $e->getFile() . ' | ' . $e->getLine();
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                                'Orm',
                                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                    'log/exception/order_insert_failed',
                                    array('decoded_message' => $doctrineError)
                                ),
                                $logOutput,
                                $lengowOrder->getMarketplaceSku()
                            );
                        }
                        $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                            $errorMessage
                        );
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                            'API-OrderAction',
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                'log/order_action/call_action_failed',
                                array('decoded_message' => $decodedMessage)
                            ),
                            false,
                            $lengowOrder->getMarketplaceSku()
                        );
                    }
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
            'createdAt' => date('Y-m-d h:m:i', strtotime('-3 days', time())),
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
        $preprodMode = (bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowImportPreprodEnabled'
        );
        if ($preprodMode) {
            return false;
        }
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'API-OrderAction',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/order_action/check_action_not_sent'
            ),
            $logOutput
        );
        // get unsent orders
        $unsentOrders = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getUnsentOrders();
        if ($unsentOrders) {
            foreach ($unsentOrders as $idOrder => $actionType) {
                /** @var Shopware\Models\Order\Order $order */
                $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($idOrder);
                Shopware_Plugins_Backend_Lengow_Components_LengowOrder::callAction($order, $actionType);
            }
            return true;
        }
        return false;
    }
}
