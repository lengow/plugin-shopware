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
 * Lengow Marketplace Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace
{
    /**
     * @var array all valid actions
     */
    public static $validActions = array(
        'ship',
        'cancel'
    );

    /**
     * @var array all marketplaces allowed for an account ID
     */
    public static $marketplaces = array();

    /**
     * @var mixed the current marketplace
     */
    public $marketplace;

    /**
     * @var string the code of the marketplace
     */
    public $name;

    /**
     * @var string the name of the marketplace
     */
    public $labelName;

    /**
     * @var boolean if the marketplace is loaded
     */
    public $isLoaded = false;

    /**
     * @var array Lengow states => marketplace states
     */
    public $statesLengow = array();

    /**
     * @var array marketplace states => Lengow states
     */
    public $states = array();

    /**
     * @var array all possible actions of the marketplace
     */
    public $actions = array();

    /**
     * @var array all carriers of the marketplace
     */
    public $carriers = array();

    /**
     * @var array all possible values for actions of the marketplace
     */
    public $argValues = array();

    /**
     * Construct a new Marketplace instance with xml configuration
     *
     * @param string $name name of the marketplace
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException marketplace not present
     */
    public function __construct($name)
    {
        $this->loadApiMarketplace();
        $this->name = strtolower($name);
        if (!isset(self::$marketplaces->{$this->name})) {
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'lengow_log/exception/marketplace_not_present',
                    array('marketplace_name' => $this->name)
                )
            );
        }
        $this->marketplace = self::$marketplaces->{$this->name};
        if (!empty($this->marketplace)) {
            $this->labelName = $this->marketplace->name;
            foreach ($this->marketplace->orders->status as $key => $state) {
                foreach ($state as $value) {
                    $this->statesLengow[(string)$value] = (string)$key;
                    $this->states[(string)$key][(string)$value] = (string)$value;
                }
            }
            foreach ($this->marketplace->orders->actions as $key => $action) {
                foreach ($action->status as $state) {
                    $this->actions[(string)$key]['status'][(string)$state] = (string)$state;
                }
                foreach ($action->args as $arg) {
                    $this->actions[(string)$key]['args'][(string)$arg] = (string)$arg;
                }
                foreach ($action->optional_args as $optionalArg) {
                    $this->actions[(string)$key]['optional_args'][(string)$optionalArg] = $optionalArg;
                }
                foreach ($action->args_description as $argKey => $argDescription) {
                    $validValues = array();
                    if (isset($argDescription->valid_values)) {
                        foreach ($argDescription->valid_values as $code => $validValue) {
                            $validValues[(string)$code] = isset($validValue->label)
                                ? (string)$validValue->label
                                : (string)$validValue;
                        }
                    }
                    $defaultValue = isset($argDescription->default_value)
                        ? (string)$argDescription->default_value
                        : '';
                    $acceptFreeValue = isset($argDescription->accept_free_values)
                        ? (bool)$argDescription->accept_free_values
                        : true;
                    $this->argValues[(string)$argKey] = array(
                        'default_value' => $defaultValue,
                        'accept_free_values' => $acceptFreeValue,
                        'valid_values' => $validValues
                    );
                }
            }
            if (isset($this->marketplace->orders->carriers)) {
                foreach ($this->marketplace->orders->carriers as $key => $carrier) {
                    $this->carriers[(string)$key] = (string)$carrier->label;
                }
            }
            $this->isLoaded = true;
        }
    }

    /**
     * Load the json configuration of all marketplaces
     */
    public function loadApiMarketplace()
    {
        if (count(self::$marketplaces) === 0) {
            self::$marketplaces = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
                'get',
                '/v3.0/marketplaces'
            );
        }
    }

    /**
     * Get the real lengow's order state
     *
     * @param string $name marketplace order state
     *
     * @return string
     */
    public function getStateLengow($name)
    {
        if (array_key_exists($name, $this->statesLengow)) {
            return $this->statesLengow[$name];
        }
        return null;
    }

    /**
     * Get the default value for argument
     *
     * @param string $name argument's name
     *
     * @return string|false
     */
    public function getDefaultValue($name)
    {
        if (array_key_exists($name, $this->argValues)) {
            $defaultValue = $this->argValues[$name]['default_value'];
            if (!empty($defaultValue)) {
                return $defaultValue;
            }
        }
        return false;
    }

    /**
     * Is marketplace contain order Line
     *
     * @param string $action (ship / cancel / refund)
     *
     * @return boolean
     */
    public function containOrderLine($action)
    {
        if (isset($this->actions[$action])) {
            $actions = $this->actions[$action];
            if (isset($actions['args']) && is_array($actions['args'])) {
                if (in_array('line', $actions['args'])) {
                    return true;
                }
            }
            if (isset($actions['optional_args']) && is_array($actions['optional_args'])) {
                if (in_array('line', $actions['optional_args'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Call Action with marketplace
     *
     * @param string $action order action (ship or cancel)
     * @param \Shopware\Models\Order\Order $order Shopware order instance
     * @param \Shopware\CustomModels\Lengow\Order $lengowOrder Lengow order instance
     * @param string $orderLineId Lengow order line id
     *
     * @throws Exception|Shopware_Plugins_Backend_Lengow_Components_LengowException action not valid
     *      marketplace action not present / store id is required /marketplace name is required
     *      argument is required / action not created
     *
     * @return boolean
     */
    public function callAction($action, $order, $lengowOrder, $orderLineId = null)
    {
        try {
            if (!in_array($action, self::$validActions)) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/action_not_valid',
                        array('action' => $action)
                    )
                );
            }
            if (!isset($this->actions[$action])) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/marketplace_action_not_present',
                        array('action' => $action)
                    )
                );
            }
            if ($lengowOrder->getShopId() == 0) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/shop_id_require'
                    )
                );
            }
            if (strlen($lengowOrder->getMarketplaceName()) == 0) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/marketplace_name_require'
                    )
                );
            }
            // Get all arguments from API
            $params = array();
            $actions = $this->actions[$action];
            if (isset($actions['args']) && isset($actions['optional_args'])) {
                $allArgs = array_merge($actions['args'], $actions['optional_args']);
            } elseif (!isset($actions['args']) && isset($actions['optional_args'])) {
                $allArgs = $actions['optional_args'];
            } elseif (isset($actions['args'])) {
                $allArgs = $actions['args'];
            } else {
                $allArgs = array();
            }
            // Get all order informations
            foreach ($allArgs as $arg) {
                switch ($arg) {
                    case 'tracking_number':
                        $params[$arg] = $order->getTrackingCode();
                        break;
                    case 'carrier':
                    case 'carrier_name':
                    case 'shipping_method':
                        $carrierName = $lengowOrder->getCarrier() != ''
                            ? $lengowOrder->getCarrier()
                            : $this->matchDispatch($order->getDispatch()->getName());
                        $params[$arg] = $carrierName;
                        break;
                    case 'tracking_url':
                        $params[$arg] = $order->getDispatch()->getStatusLink();
                        break;
                    case 'shipping_price':
                        $params[$arg] = $order->getInvoiceShipping();
                        break;
                    case 'shipping_date':
                        $params[$arg] = date('c');
                        break;
                    default:
                        if (isset($actions['optional_args']) && in_array($arg, $actions['optional_args'])) {
                            continue;
                        }
                        $defaultValue = $this->getDefaultValue((string)$arg);
                        $paramValue = $defaultValue ? $defaultValue : $arg . ' not available';
                        $params[$arg] = $paramValue;
                        break;
                }
            }
            if (!is_null($orderLineId)) {
                $params['line'] = $orderLineId;
            }
            // Check all required arguments
            if (isset($actions['args'])) {
                foreach ($actions['args'] as $arg) {
                    if (!isset($params[$arg]) || strlen($params[$arg]) == 0) {
                        throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                'lengow_log/exception/arg_is_required',
                                array('arg_name' => $arg)
                            )
                        );
                    }
                }
            }
            // Clean empty optional arguments
            if (isset($actions['optional_args'])) {
                foreach ($actions['optional_args'] as $arg) {
                    if (isset($params[$arg]) && strlen($params[$arg]) == 0) {
                        unset($params[$arg]);
                    }
                }
            }
            // Set identification parameters
            $params['marketplace_order_id'] = $lengowOrder->getMarketplaceSku();
            $params['marketplace'] = $lengowOrder->getMarketplaceName();
            $params['action_type'] = $action;
            $sendAction = true;
            // check if action is already created
            $getParams = array_merge($params, array('queued' => 'True'));
            // array key deletion for verification in get
            if (isset($getParams['shipping_date'])) {
                unset($getParams['shipping_date']);
            }
            $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
                'get',
                '/v3.0/orders/actions/',
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
                        Shopware_Plugins_Backend_Lengow_Components_LengowAction::createOrderAction(
                            $order,
                            $action,
                            $row->id,
                            $orderLineId,
                            $params
                        );
                        $sendAction = false;
                    }
                }
            }
            if ($sendAction) {
                $preprodMode = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                    'lengowImportPreprodEnabled'
                );
                if (!$preprodMode) {
                    $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
                        'post',
                        '/v3.0/orders/actions/',
                        $params
                    );
                    if (isset($result->id)) {
                        Shopware_Plugins_Backend_Lengow_Components_LengowAction::createOrderAction(
                            $order,
                            $action,
                            $result->id,
                            $orderLineId,
                            $params
                        );
                    } else {
                        throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                'lengow_log/exception/action_not_created',
                                array('error_message' => json_encode($result))
                            )
                        );
                    }
                }
                // Create log for call action
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
            return true;
        } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Shopware error]: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            $processStateFinish = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getOrderProcessState(
                'closed'
            );
            if ($lengowOrder->getOrderProcessState() != $processStateFinish) {
                Shopware_Plugins_Backend_Lengow_Components_LengowOrderError::createOrderError(
                    $lengowOrder,
                    $errorMessage,
                    'send'
                );
                try {
                    $lengowOrder->setInError(true);
                    Shopware()->Models()->flush($lengowOrder);
                } catch (Exception $e) {
                    $doctrineError = '[Doctrine error] "' . $e->getMessage() . '" '
                        . $e->getFile() . ' | ' . $e->getLine();
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
            return false;
        }
    }

    /**
     * Match dispatch's name with accepted values
     *
     * @param string $name carrier code
     *
     * @return string
     */
    protected function matchDispatch($name)
    {
        if (count($this->carriers) > 0) {
            foreach ($this->carriers as $key => $carrier) {
                if (preg_match('`' . $key . '`i', trim($name))) {
                    return $key;
                } elseif (preg_match('`.*?' . $key . '.*?`i', $name)) {
                    return $key;
                }
            }
        }
        return $name;
    }
}
