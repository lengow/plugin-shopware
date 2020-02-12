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
use Shopware\CustomModels\Lengow\Order as LengowOrderModel;
use Shopware_Plugins_Backend_Lengow_Components_LengowAction as LengowAction;
use Shopware_Plugins_Backend_Lengow_Components_LengowException as LengowException;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrderError as LengowOrderError;
use Shopware_Plugins_Backend_Lengow_Components_LengowSync as LengowSync;

/**
 * Lengow Marketplace Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace
{
    /**
     * @var string marketplace file name
     */
    public static $marketplaceJson = 'marketplaces.json';

    /**
     * @var array all valid actions
     */
    public static $validActions = array(
        LengowAction::TYPE_SHIP,
        LengowAction::TYPE_CANCEL,
    );

    /**
     * @var array|false all marketplaces allowed for an account ID
     */
    public static $marketplaces = false;

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
     * @throws LengowException marketplace not present
     */
    public function __construct($name)
    {
        $this->loadApiMarketplace();
        $this->name = strtolower($name);
        if (!isset(self::$marketplaces->{$this->name})) {
            throw new LengowException(
                LengowMain::setLogMessage(
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
                        'valid_values' => $validValues,
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
        if (!self::$marketplaces) {
            self::$marketplaces = LengowSync::getMarketplaces();
        }
    }

    /**
     * Get marketplaces.json path
     *
     * @return string
     */
    public static function getFilePath()
    {
        $sep = DIRECTORY_SEPARATOR;
        $folderPath = LengowMain::getLengowFolder();
        $configFolder = LengowMain::$lengowConfigFolder;
        return $folderPath . $sep . $configFolder . $sep . self::$marketplaceJson;
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
     * Get the action with parameters
     *
     * @param string $name action's name
     *
     * @return array|false
     */
    public function getAction($name)
    {
        if (array_key_exists($name, $this->actions)) {
            return $this->actions[$name];
        }
        return false;
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
                if (in_array(LengowAction::ARG_LINE, $actions['args'])) {
                    return true;
                }
            }
            if (isset($actions['optional_args']) && is_array($actions['optional_args'])) {
                if (in_array(LengowAction::ARG_LINE, $actions['optional_args'])) {
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
     * @param OrderModel $order Shopware order instance
     * @param LengowOrderModel $lengowOrder Lengow order instance
     * @param string|null $orderLineId Lengow order line id
     *
     * @throws Exception|LengowException
     *
     * @return boolean
     */
    public function callAction($action, $order, $lengowOrder, $orderLineId = null)
    {
        try {
            // check the action and order data
            $this->checkAction($action);
            $this->checkOrderData($lengowOrder);
            // get all required and optional arguments for a specific marketplace
            $marketplaceArguments = $this->getMarketplaceArguments($action);
            // get all available values from an order
            $params = $this->getAllParams($action, $order, $lengowOrder, $marketplaceArguments);
            // check required arguments and clean value for empty optionals arguments
            $params = $this->checkAndCleanParams($action, $params);
            // complete the values with the specific values of the account
            if ($orderLineId !== null) {
                $params[LengowAction::ARG_LINE] = $orderLineId;
            }
            $params['marketplace_order_id'] = $lengowOrder->getMarketplaceSku();
            $params['marketplace'] = $lengowOrder->getMarketplaceName();
            $params[LengowAction::ARG_ACTION_TYPE] = $action;
            // checks whether the action is already created to not return an action
            $canSendAction = LengowAction::canSendAction($params, $order);
            if ($canSendAction) {
                // send a new action on the order via the Lengow API
                LengowAction::sendAction($params, $order, $lengowOrder);
            }
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Shopware error]: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ($lengowOrder->getOrderProcessState() !== LengowOrder::getOrderProcessState(LengowOrder::STATE_CLOSED)) {
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
            return false;
        }
        return true;
    }

    /**
     * Check if the action is valid and present on the marketplace
     *
     * @param string $action Lengow order actions type (ship or cancel)
     *
     * @throws LengowException
     */
    protected function checkAction($action)
    {
        if (!in_array($action, self::$validActions)) {
            throw new LengowException(
                LengowMain::setLogMessage('lengow_log/exception/action_not_valid', array('action' => $action))
            );
        }
        if (!$this->getAction($action)) {
            throw new LengowException(
                LengowMain::setLogMessage(
                    'lengow_log/exception/marketplace_action_not_present',
                    array('action' => $action)
                )
            );
        }
    }

    /**
     * Check if the essential data of the order are present
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @throws LengowException
     */
    protected function checkOrderData($lengowOrder)
    {
        if (strlen($lengowOrder->getMarketplaceSku()) === 0) {
            throw new LengowException(LengowMain::setLogMessage('lengow_log/exception/marketplace_sku_require'));
        }
        if (strlen($lengowOrder->getMarketplaceName()) === 0) {
            throw new LengowException(LengowMain::setLogMessage('lengow_log/exception/marketplace_name_require'));
        }
    }

    /**
     * Get all marketplace arguments for a specific action
     *
     * @param string $action Lengow order actions type (ship or cancel)
     *
     * @return array
     */
    protected function getMarketplaceArguments($action)
    {
        $actions = $this->getAction($action);
        if (isset($actions['args']) && isset($actions['optional_args'])) {
            $marketplaceArguments = array_merge($actions['args'], $actions['optional_args']);
        } elseif (!isset($actions['args']) && isset($actions['optional_args'])) {
            $marketplaceArguments = $actions['optional_args'];
        } elseif (isset($actions['args'])) {
            $marketplaceArguments = $actions['args'];
        } else {
            $marketplaceArguments = array();
        }
        return $marketplaceArguments;
    }

    /**
     * Get all available values from an order
     *
     * @param string $action Lengow order actions type (ship or cancel)
     * @param OrderModel $order Shopware order instance
     * @param LengowOrderModel $lengowOrder Lengow order instance
     * @param array $marketplaceArguments All marketplace arguments for a specific action
     *
     * @return array
     */
    protected function getAllParams($action, $order, $lengowOrder, $marketplaceArguments)
    {
        $params = array();
        $actions = $this->getAction($action);
        // get all order data
        foreach ($marketplaceArguments as $arg) {
            switch ($arg) {
                case LengowAction::ARG_TRACKING_NUMBER:
                    $params[$arg] = $order->getTrackingCode();
                    break;
                case LengowAction::ARG_CARRIER:
                case LengowAction::ARG_CARRIER_NAME:
                case LengowAction::ARG_SHIPPING_METHOD:
                case LengowAction::ARG_CUSTOM_CARRIER:
                    $carrierName = $lengowOrder->getCarrier() != ''
                        ? $lengowOrder->getCarrier()
                        : $this->matchDispatch($order->getDispatch()->getName());
                    $params[$arg] = $carrierName;
                    break;
                case LengowAction::ARG_TRACKING_URL:
                    $params[$arg] = $order->getDispatch()->getStatusLink();
                    break;
                case LengowAction::ARG_SHIPPING_PRICE:
                    $params[$arg] = $order->getInvoiceShipping();
                    break;
                case LengowAction::ARG_SHIPPING_DATE:
                case LengowAction::ARG_DELIVERY_DATE:
                    $params[$arg] = date('c');
                    break;
                default:
                    if (isset($actions['optional_args']) && in_array($arg, $actions['optional_args'])) {
                        break;
                    }
                    $defaultValue = $this->getDefaultValue((string)$arg);
                    $paramValue = $defaultValue ? $defaultValue : $arg . ' not available';
                    $params[$arg] = $paramValue;
                    break;
            }
        }
        return $params;
    }

    /**
     * Check required parameters and delete empty parameters
     *
     * @param string $action Lengow order actions type (ship or cancel)
     * @param array $params all available values
     *
     * @throws LengowException
     *
     * @return array
     */
    protected function checkAndCleanParams($action, $params)
    {
        $actions = $this->getAction($action);
        if (isset($actions['args'])) {
            foreach ($actions['args'] as $arg) {
                if (!isset($params[$arg]) || strlen($params[$arg]) === 0) {
                    throw new LengowException(
                        LengowMain::setLogMessage('lengow_log/exception/arg_is_required', array('arg_name' => $arg))
                    );
                }
            }
        }
        if (isset($actions['optional_args'])) {
            foreach ($actions['optional_args'] as $arg) {
                if (isset($params[$arg]) && strlen($params[$arg]) === 0) {
                    unset($params[$arg]);
                }
            }
        }
        return $params;
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
        if (!empty($this->carriers)) {
            $nameCleaned = $this->cleanString($name);
            // strict search for a chain
            $result = $this->searchCarrierCode($nameCleaned);
            // approximate search for a chain
            if (!$result) {
                $result = $this->searchCarrierCode($nameCleaned, false);
            }
            if ($result) {
                return $result;
            }
        }
        return $name;
    }

    /**
     * Cleaning a string before search
     *
     * @param string $string string to clean
     *
     * @return string
     */
    private function cleanString($string)
    {
        $cleanFilters = array(' ', '-', '_', '.');
        return strtolower(str_replace($cleanFilters, '', trim($string)));
    }

    /**
     * Search carrier code in a chain
     *
     * @param string $nameCleaned carrier code cleaned
     * @param boolean $strict strict search
     *
     * @return string|false
     */
    private function searchCarrierCode($nameCleaned, $strict = true)
    {
        $result = false;
        foreach ($this->carriers as $key => $label) {
            $keyCleaned = $this->cleanString($key);
            $labelCleaned = $this->cleanString($label);
            // search on the carrier key
            $found = $this->searchValue($keyCleaned, $nameCleaned, $strict);
            // search on the carrier label if it is different from the key
            if (!$found && $labelCleaned !== $keyCleaned) {
                $found = $this->searchValue($labelCleaned, $nameCleaned, $strict);
            }
            if ($found) {
                $result = $key;
            }
        }
        return $result;
    }

    /**
     * Strict or approximate search for a chain
     *
     * @param string $pattern search pattern
     * @param string $subject string to search
     * @param boolean $strict strict search
     *
     * @return boolean
     */
    private function searchValue($pattern, $subject, $strict = true)
    {
        if ($strict) {
            $found = $pattern === $subject ? true : false;
        } else {
            $found = preg_match('`.*?' . $pattern . '.*?`i', $subject) ? true : false;
        }
        return $found;
    }
}
