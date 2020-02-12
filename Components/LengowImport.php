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

use Shopware\Models\Shop\Shop as ShopModel;
use Shopware\Models\Order\Order as OrderModel;
use Shopware_Plugins_Backend_Lengow_Components_LengowAction as LengowAction;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowConnector as LengowConnector;
use Shopware_Plugins_Backend_Lengow_Components_LengowException as LengowException;
use Shopware_Plugins_Backend_Lengow_Components_LengowImportOrder as LengowImportOrder;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace as LengowMarketplace;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrderError as LengowOrderError;
use Shopware_Plugins_Backend_Lengow_Components_LengowSync as LengowSync;

/**
 * Lengow Import Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowImport
{
    /**
     * @var integer max interval time for order synchronisation old versions (1 day)
     */
    const MIN_INTERVAL_TIME = 86400;

    /**
     * @var integer max import days for old versions (10 days)
     */
    const MAX_INTERVAL_TIME = 864000;

    /**
     * @var integer security interval time for cron synchronisation (2 hours)
     */
    const SECURITY_INTERVAL_TIME = 7200;

    /**
     * @var string manual import type
     */
    const TYPE_MANUAL = 'manual';

    /**
     * @var string cron import type
     */
    const TYPE_CRON = 'cron';

    /**
     * @var integer|null Shopware shop id
     */
    protected $shopId = null;

    /**
     * @var boolean use preprod mode
     */
    protected $preprodMode = false;

    /**
     * @var boolean display log messages
     */
    protected $logOutput = false;

    /**
     * @var string|null marketplace order sku
     */
    protected $marketplaceSku = null;

    /**
     * @var string|null marketplace name
     */
    protected $marketplaceName = null;

    /**
     * @var integer|null Lengow order id
     */
    protected $lengowOrderId = null;

    /**
     * @var integer|null delivery address id
     */
    protected $deliveryAddressId = null;

    /**
     * @var integer number of orders to import
     */
    protected $limit = 0;

    /**
     * @var integer|false imports orders updated since (timestamp)
     */
    protected $updatedFrom = false;

    /**
     * @var integer|false imports orders updated until (timestamp)
     */
    protected $updatedTo = false;

    /**
     * @var integer|false imports orders created since (timestamp)
     */
    protected $createdFrom = false;

    /**
     * @var integer|false imports orders created until (timestamp)
     */
    protected $createdTo = false;

    /**
     * @var string account ID
     */
    protected $accountId;

    /**
     * @var string access token
     */
    protected $accessToken;

    /**
     * @var string secret token
     */
    protected $secretToken;

    /**
     * @var LengowConnector Lengow connector instance
     */
    protected $connector;

    /**
     * @var string type import (manual or cron)
     */
    protected $typeImport;

    /**
     * @var boolean import one order
     */
    protected $importOneOrder = false;

    /**
     * @var array shop catalog ids for import
     */
    protected $shopCatalogIds = array();

    /**
     * @var array catalog ids already imported
     */
    protected $catalogIds = array();

    /**
     * @var boolean import is processing
     */
    public static $processing;

    /**
     * @var array valid states lengow to create a Lengow order
     */
    public static $lengowStates = array(
        LengowOrder::STATE_WAITING_SHIPMENT,
        LengowOrder::STATE_SHIPPED,
        LengowOrder::STATE_CLOSED,
    );

    /**
     * Construct the import manager
     *
     * @param $params array optional options
     * string  marketplace_sku     lengow marketplace order id to import
     * string  marketplace_name    lengow marketplace name to import
     * string  type                type of current import
     * string  created_from        import of orders since
     * string  created_to          import of orders until
     * integer lengow_order_id     Lengow order id in Shopware
     * integer delivery_address_id Lengow delivery address id to import
     * integer shop_id             shop id for current import
     * integer days                import period
     * integer limit               number of orders to import
     * boolean log_output          display log messages
     * boolean preprod_mode        preprod mode
     */
    public function __construct($params = array())
    {
        // get generic params for synchronisation
        $this->preprodMode = isset($params['preprod_mode'])
            ? (bool)$params['preprod_mode']
            : (bool)LengowConfiguration::getConfig('lengowImportPreprodEnabled');
        $this->typeImport = isset($params['type']) ? $params['type'] : self::TYPE_MANUAL;
        $this->logOutput = isset($params['log_output']) ? (bool)$params['log_output'] : false;
        $this->shopId = isset($params['shop_id']) ? (int)$params['shop_id'] : null;
        // get params for synchronise one or all orders
        if (array_key_exists('marketplace_sku', $params)
            && array_key_exists('marketplace_name', $params)
            && array_key_exists('shop_id', $params)
        ) {
            if (isset($params['lengow_order_id'])) {
                $this->lengowOrderId = (int)$params['lengow_order_id'];
            }
            $this->marketplaceSku = (string)$params['marketplace_sku'];
            $this->marketplaceName = (string)$params['marketplace_name'];
            $this->limit = 1;
            $this->importOneOrder = true;
            if (array_key_exists('delivery_address_id', $params) && $params['delivery_address_id'] != '') {
                $this->deliveryAddressId = (int)$params['delivery_address_id'];
            }
        } else {
            $this->marketplaceSku = null;
            // set the time interval
            $this->setIntervalTime(
                isset($params['days']) ? (int)$params['days'] : false,
                isset($params['created_from']) ? $params['created_from'] : false,
                isset($params['created_to']) ? $params['created_to'] : false
            );
            $this->limit = isset($params['limit']) ? (int)$params['limit'] : 0;
        }
    }

    /**
     * Execute import : fetch orders and import them
     *
     * @return array
     */
    public function exec()
    {
        $orderNew = 0;
        $orderUpdate = 0;
        $orderError = 0;
        $error = array();
        $globalError = false;
        $syncOk = true;
        // clean logs
        LengowMain::cleanLog();
        if (self::isInProcess() && !$this->preprodMode && !$this->importOneOrder) {
            $globalError = LengowMain::setLogMessage(
                'lengow_log/error/rest_time_to_import',
                array('rest_time' => self::restTimeToImport())
            );
            LengowMain::log(LengowLog::CODE_IMPORT, $globalError, $this->logOutput);
        } elseif (!self::checkCredentials()) {
            $globalError = LengowMain::setLogMessage('lengow_log/error/credentials_not_valid');
            LengowMain::log(LengowLog::CODE_IMPORT, $globalError, $this->logOutput);
        } else {
            if (!$this->importOneOrder) {
                self::setInProcess();
            }
            // check Lengow catalogs for order synchronisation
            if (!$this->importOneOrder && $this->typeImport === self::TYPE_MANUAL) {
                LengowSync::syncCatalog();
            }
            // start order synchronisation
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/start', array('type' => $this->typeImport)),
                $this->logOutput
            );
            if ($this->preprodMode) {
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage('log/import/preprod_mode_active'),
                    $this->logOutput
                );
            }
            // get all shops for import
            /** @var ShopModel[] $shops */
            $shops = LengowMain::getLengowActiveShops();
            foreach ($shops as $shop) {
                if ($this->shopId !== null && $shop->getId() !== $this->shopId) {
                    continue;
                }
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage(
                        'log/import/start_for_shop',
                        array(
                            'name_shop' => $shop->getName(),
                            'id_shop' => $shop->getId(),
                        )
                    ),
                    $this->logOutput
                );
                try {
                    // check shop catalog ids
                    if (!$this->checkCatalogIds($shop)) {
                        $errorCatalogIds = LengowMain::setLogMessage(
                            'lengow_log/error/no_catalog_for_shop',
                            array(
                                'name_shop' => $shop->getName(),
                                'id_shop' => $shop->getId(),
                            )
                        );
                        LengowMain::log(LengowLog::CODE_IMPORT, $errorCatalogIds, $this->logOutput);
                        $error[$shop->getId()] = $errorCatalogIds;
                        continue;
                    }
                    // get orders from Lengow API
                    $orders = $this->getOrdersFromApi($shop);
                    $totalOrders = count($orders);
                    if ($this->importOneOrder) {
                        LengowMain::log(
                            LengowLog::CODE_IMPORT,
                            LengowMain::setLogMessage(
                                'log/import/find_one_order',
                                array(
                                    'nb_order' => $totalOrders,
                                    'marketplace_sku' => $this->marketplaceSku,
                                    'marketplace_name' => $this->marketplaceName,
                                    'account_id' => $this->accountId,
                                )
                            ),
                            $this->logOutput
                        );
                    } else {
                        LengowMain::log(
                            LengowLog::CODE_IMPORT,
                            LengowMain::setLogMessage(
                                'log/import/find_all_orders',
                                array(
                                    'nb_order' => $totalOrders,
                                    'name_shop' => $shop->getName(),
                                    'id_shop' => $shop->getId(),
                                )
                            ),
                            $this->logOutput
                        );
                    }
                    if ($totalOrders <= 0 && $this->importOneOrder) {
                        throw new LengowException('lengow_log/error/order_not_found');
                    } elseif ($totalOrders <= 0) {
                        continue;
                    }
                    if ($this->lengowOrderId !== null) {
                        LengowOrderError::finishOrderErrors($this->lengowOrderId);
                    }
                    $result = $this->importOrders($orders, $shop);
                    if (!$this->importOneOrder) {
                        $orderNew += $result['order_new'];
                        $orderUpdate += $result['order_update'];
                        $orderError += $result['order_error'];
                    }
                } catch (LengowException $e) {
                    $errorMessage = $e->getMessage();
                } catch (Exception $e) {
                    $errorMessage = '[Shopware error] "' . $e->getMessage() . '" '
                        . $e->getFile() . ' | ' . $e->getLine();
                }
                if (isset($errorMessage)) {
                    $syncOk = false;
                    if ($this->lengowOrderId !== null) {
                        LengowOrderError::finishOrderErrors($this->lengowOrderId);
                        LengowOrderError::createOrderError($this->lengowOrderId, $errorMessage);
                    }
                    $decodedMessage = LengowMain::decodeLogMessage($errorMessage);
                    LengowMain::log(
                        LengowLog::CODE_IMPORT,
                        LengowMain::setLogMessage(
                            'log/import/import_failed',
                            array('decoded_message' => $decodedMessage)
                        ),
                        $this->logOutput
                    );
                    $error[$shop->getId()] = $errorMessage;
                    unset($errorMessage);
                    continue;
                }
                unset($shop);
            }
            if (!$this->importOneOrder) {
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage('lengow_log/error/nb_order_imported', array('nb_order' => $orderNew)),
                    $this->logOutput
                );
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage('lengow_log/error/nb_order_updated', array('nb_order' => $orderUpdate)),
                    $this->logOutput
                );
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage('lengow_log/error/nb_order_with_error', array('nb_order' => $orderError)),
                    $this->logOutput
                );
            }
            // update last import date
            if (!$this->importOneOrder && $syncOk) {
                LengowMain::updateDateImport($this->typeImport);
            }
            // finish import process
            self::setEnd();
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/end', array('type' => $this->typeImport)),
                $this->logOutput
            );
            // sending email in error for orders
            if (
                (bool)LengowConfiguration::getConfig('lengowImportReportMailEnabled')
                && !$this->preprodMode
                && !$this->importOneOrder
            ) {
                LengowMain::sendMailAlert($this->logOutput);
            }
            // check if order action is finish (Ship / Cancel)
            if (!$this->preprodMode && !$this->importOneOrder && $this->typeImport === self::TYPE_MANUAL) {
                LengowAction::checkFinishAction($this->logOutput);
                LengowAction::checkOldAction($this->logOutput);
                LengowAction::checkActionNotSent($this->logOutput);
            }
        }
        if ($globalError) {
            $error[0] = $globalError;
            if ($this->lengowOrderId !== null) {
                LengowOrderError::finishOrderErrors($this->lengowOrderId);
                LengowOrderError::createOrderError($this->lengowOrderId, $globalError);
            }
        }
        if ($this->importOneOrder) {
            $result['error'] = $error;
            return $result;
        } else {
            return array(
                'order_new' => $orderNew,
                'order_update' => $orderUpdate,
                'order_error' => $orderError,
                'error' => $error,
            );
        }
    }

    /**
     * Check credentials and get Lengow connector
     *
     * @return boolean
     */
    protected function checkCredentials()
    {
        if (LengowConnector::isValidAuth($this->logOutput)) {
            $accessIds = LengowConfiguration::getAccessIds();
            list($this->accountId, $this->accessToken, $this->secretToken) = $accessIds;
            $this->connector = new LengowConnector($this->accessToken, $this->secretToken);
            return true;
        }
        return false;
    }

    /**
     * Check catalog ids for a shop
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return boolean
     */
    protected function checkCatalogIds($shop)
    {
        if ($this->importOneOrder) {
            return true;
        }
        $shopCatalogIds = array();
        $catalogIds = LengowConfiguration::getCatalogIds($shop);
        foreach ($catalogIds as $catalogId) {
            if (array_key_exists($catalogId, $this->catalogIds)) {
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage(
                        'log/import/catalog_id_already_used',
                        array(
                            'catalog_id' => $catalogId,
                            'name_shop' => $this->catalogIds[$catalogId]['name'],
                            'id_shop' => $this->catalogIds[$catalogId]['shopId'],
                        )
                    ),
                    $this->logOutput
                );
            } else {
                $this->catalogIds[$catalogId] = array('shopId' => $shop->getId(), 'name' => $shop->getName());
                $shopCatalogIds[] = $catalogId;
            }
        }
        if (!empty($shopCatalogIds)) {
            $this->shopCatalogIds = $shopCatalogIds;
            return true;
        }
        return false;
    }

    /**
     * Call Lengow order API
     *
     * @param ShopModel $shop Shopware shop instance
     *
     * @throws LengowException
     *
     * @return array
     */
    protected function getOrdersFromApi($shop)
    {
        $page = 1;
        $orders = array();
        if ($this->importOneOrder) {
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/connector_get_order',
                    array(
                        'marketplace_sku' => $this->marketplaceSku,
                        'marketplace_name' => $this->marketplaceName,
                    )
                ),
                $this->logOutput
            );
        } else {
            $dateFrom = $this->createdFrom ? $this->createdFrom : $this->updatedFrom;
            $dateTo = $this->createdTo ? $this->createdTo : $this->updatedTo;
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/connector_get_all_order',
                    array(
                        'date_from' => date('Y-m-d H:i:s', $dateFrom),
                        'date_to' => date('Y-m-d H:i:s', $dateTo),
                        'catalog_id' => implode(', ', $this->shopCatalogIds),
                    )
                ),
                $this->logOutput
            );
        }
        do {
            try {
                if ($this->importOneOrder) {
                    $results = $this->connector->get(
                        LengowConnector::API_ORDER,
                        array(
                            'marketplace_order_id' => $this->marketplaceSku,
                            'marketplace' => $this->marketplaceName,
                            'account_id' => $this->accountId,
                        ),
                        LengowConnector::FORMAT_STREAM,
                        '',
                        $this->logOutput
                    );
                } else {
                    if ($this->createdFrom && $this->createdTo) {
                        $timeParams = array(
                            'marketplace_order_date_from' => date('c', $this->createdFrom),
                            'marketplace_order_date_to' => date('c', $this->createdTo),
                        );
                    } else {
                        $timeParams = array(
                            'updated_from' => date('c', $this->updatedFrom),
                            'updated_to' => date('c', $this->updatedTo),
                        );
                    }
                    $results = $this->connector->get(
                        LengowConnector::API_ORDER,
                        array_merge(
                            $timeParams,
                            array(
                                'catalog_ids' => implode(',', $this->shopCatalogIds),
                                'account_id' => $this->accountId,
                                'page' => $page,
                            )
                        ),
                        LengowConnector::FORMAT_STREAM,
                        '',
                        $this->logOutput
                    );
                }
            } catch (Exception $e) {
                $message = LengowMain::decodeLogMessage($e->getMessage());
                throw new LengowException(
                    LengowMain::setLogMessage(
                        'lengow_log/exception/error_lengow_webservice',
                        array(
                            'error_code' => $e->getCode(),
                            'error_message' => $message,
                            'name_shop' => $shop->getName(),
                            'id_shop' => $shop->getId(),
                        )
                    )
                );
            }
            if ($results === null) {
                throw new LengowException(
                    LengowMain::setLogMessage(
                        'lengow_log/exception/no_connection_webservice',
                        array(
                            'name_shop' => $shop->getName(),
                            'id_shop' => $shop->getId(),
                        )
                    )
                );
            }
            $results = json_decode($results);
            if (!is_object($results)) {
                throw new LengowException(
                    LengowMain::setLogMessage(
                        'lengow_log/exception/no_connection_webservice',
                        array(
                            'name_shop' => $shop->getName(),
                            'id_shop' => $shop->getId(),
                        )
                    )
                );
            }
            // construct array orders
            foreach ($results->results as $order) {
                $orders[] = $order;
            }
            $page++;
            $finish = ($results->next === null || $this->importOneOrder) ? true : false;
        } while ($finish != true);
        return $orders;
    }

    /**
     * Create or update order in Shopware
     *
     * @param mixed $orders API orders
     * @param ShopModel $shop Shopware shop instance
     *
     * @return array|false
     */
    protected function importOrders($orders, $shop)
    {
        $orderNew = 0;
        $orderUpdate = 0;
        $orderError = 0;
        $importFinished = false;
        foreach ($orders as $orderData) {
            if (!$this->importOneOrder) {
                self::setInProcess();
            }
            $nbPackage = 0;
            $marketplaceSku = (string)$orderData->marketplace_order_id;
            if ($this->preprodMode) {
                $marketplaceSku .= '--' . time();
            }
            // if order contains no package
            if (empty($orderData->packages)) {
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage('log/import/error_no_package'),
                    $this->logOutput,
                    $marketplaceSku
                );
                continue;
            }
            // start import
            foreach ($orderData->packages as $packageData) {
                $nbPackage++;
                // check whether the package contains a shipping address
                if (!isset($packageData->delivery->id)) {
                    LengowMain::log(
                        LengowLog::CODE_IMPORT,
                        LengowMain::setLogMessage('log/import/error_no_delivery_address'),
                        $this->logOutput,
                        $marketplaceSku
                    );
                    continue;
                }
                $packageDeliveryAddressId = (int)$packageData->delivery->id;
                $firstPackage = $nbPackage > 1 ? false : true;
                // check the package for re-import order
                if ($this->importOneOrder) {
                    if ($this->deliveryAddressId !== null && $this->deliveryAddressId !== $packageDeliveryAddressId) {
                        LengowMain::log(
                            LengowLog::CODE_IMPORT,
                            LengowMain::setLogMessage('log/import/error_wrong_package_number'),
                            $this->logOutput,
                            $marketplaceSku
                        );
                        continue;
                    }
                }
                try {
                    // try to import or update order
                    $importOrder = new LengowImportOrder(
                        array(
                            'shop' => $shop,
                            'preprod_mode' => $this->preprodMode,
                            'log_output' => $this->logOutput,
                            'marketplace_sku' => $marketplaceSku,
                            'delivery_address_id' => $packageDeliveryAddressId,
                            'order_data' => $orderData,
                            'package_data' => $packageData,
                            'first_package' => $firstPackage,
                        )
                    );
                    $order = $importOrder->importOrder();
                } catch (LengowException $e) {
                    $errorMessage = $e->getMessage();
                } catch (Exception $e) {
                    $errorMessage = '[Shopware error]: "' . $e->getMessage() . '" '
                        . $e->getFile() . ' | ' . $e->getLine();
                }
                if (isset($errorMessage)) {
                    $decodedMessage = LengowMain::decodeLogMessage($errorMessage);
                    LengowMain::log(
                        LengowLog::CODE_IMPORT,
                        LengowMain::setLogMessage(
                            'log/import/order_import_failed',
                            array('decoded_message' => $decodedMessage)
                        ),
                        $this->logOutput,
                        $marketplaceSku
                    );
                    unset($errorMessage);
                    continue;
                }
                // sync to lengow if no preprod_mode
                if (!$this->preprodMode && isset($order['order_new']) && $order['order_new']) {
                    /** @var OrderModel $shopwareOrder */
                    $shopwareOrder = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')
                        ->findOneBy(array('id' => $order['order_id']));
                    $synchro = LengowOrder::synchronizeOrder($shopwareOrder, $this->connector);
                    if ($synchro) {
                        $synchroMessage = LengowMain::setLogMessage(
                            'log/import/order_synchronized_with_lengow',
                            array('order_id' => $shopwareOrder->getNumber())
                        );
                    } else {
                        $synchroMessage = LengowMain::setLogMessage(
                            'log/import/order_not_synchronized_with_lengow',
                            array('order_id' => $shopwareOrder->getNumber())
                        );
                    }
                    LengowMain::log(
                        LengowLog::CODE_IMPORT,
                        $synchroMessage,
                        $this->logOutput,
                        $marketplaceSku
                    );
                    unset($shopwareOrder);
                }
                // if re-import order -> return order information
                if (isset($order) && $this->importOneOrder) {
                    return $order;
                }
                if (isset($order)) {
                    if (isset($order['order_new']) && $order['order_new']) {
                        $orderNew++;
                    } elseif (isset($order['order_update']) && $order['order_update']) {
                        $orderUpdate++;
                    } elseif (isset($order['order_error']) && $order['order_error']) {
                        $orderError++;
                    }
                }
                // clean process
                unset($importOrder, $order);
                // if limit is set
                if ($this->limit > 0 && $orderNew === $this->limit) {
                    $importFinished = true;
                    break;
                }
            }
            if ($importFinished) {
                break;
            }
        }
        return array(
            'order_new' => $orderNew,
            'order_update' => $orderUpdate,
            'order_error' => $orderError,
        );
    }

    /**
     * Set interval time for order synchronisation
     *
     * @param integer|false $days Import period
     * @param string|false $createdFrom Import of orders since
     * @param string|false $createdTo Import of orders until
     */
    protected function setIntervalTime($days, $createdFrom, $createdTo)
    {
        if ($createdFrom && $createdTo) {
            // retrieval of orders created from ... until ...
            $createdFromTimestamp = strtotime($createdFrom);
            $createdToTimestamp = strtotime($createdTo) + 86399;
            $intervalTime = (int)($createdToTimestamp - $createdFromTimestamp);
            $this->createdFrom = $createdFromTimestamp;
            $this->createdTo = $intervalTime > self::MAX_INTERVAL_TIME
                ? $createdFromTimestamp + self::MAX_INTERVAL_TIME
                : $createdToTimestamp;
        } else {
            if ($days) {
                $intervalTime = $days * 86400;
                $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
            } else {
                // order recovery updated since ... days
                $importDays = (int)LengowConfiguration::getConfig('lengowImportDays');
                $intervalTime = $importDays * 86400;
                // add security for older versions of the plugin
                $intervalTime = $intervalTime < self::MIN_INTERVAL_TIME ? self::MIN_INTERVAL_TIME : $intervalTime;
                $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
                // get dynamic interval time for cron synchronisation
                $lastImport = LengowMain::getLastImport();
                $lastSettingUpdate = (int)LengowConfiguration::getConfig('lengowLastSettingUpdate');
                if ($this->typeImport !== self::TYPE_MANUAL
                    && $lastImport['timestamp'] !== 'none'
                    && $lastImport['timestamp'] > $lastSettingUpdate
                ) {
                    $lastIntervalTime = (time() - $lastImport['timestamp']) + self::SECURITY_INTERVAL_TIME;
                    $intervalTime = $lastIntervalTime > $intervalTime ? $intervalTime : $lastIntervalTime;
                }
            }
            $this->updatedFrom = time() - $intervalTime;
            $this->updatedTo = time();
        }
    }

    /**
     * Check if import is already in process
     *
     * @return boolean
     */
    public static function isInProcess()
    {
        $timestamp = (int)LengowConfiguration::getConfig('lengowImportInProgress');
        if ($timestamp > 0) {
            // security check : if last import is more than 60 seconds old => authorize new import to be launched
            if (($timestamp + (60 * 1)) < time()) {
                self::setEnd();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Get Rest time to make re import order
     *
     * @return boolean
     */
    public static function restTimeToImport()
    {
        $timestamp = (int)LengowConfiguration::getConfig('lengowImportInProgress');
        if ($timestamp > 0) {
            return $timestamp + (60 * 1) - time();
        }
        return false;
    }

    /**
     * Set import to "in process" state
     */
    public static function setInProcess()
    {
        self::$processing = true;
        LengowConfiguration::setConfig('lengowImportInProgress', time());
    }

    /**
     * Set import to finished
     */
    public static function setEnd()
    {
        self::$processing = false;
        LengowConfiguration::setConfig('lengowImportInProgress', -1);
    }

    /**
     * Check if order status is valid for import
     *
     * @param string $orderStateMarketplace order state
     * @param LengowMarketplace $marketplace marketplace instance
     *
     * @return boolean
     */
    public static function checkState($orderStateMarketplace, $marketplace)
    {
        if (empty($orderStateMarketplace)) {
            return false;
        }
        if (!in_array($marketplace->getStateLengow($orderStateMarketplace), self::$lengowStates)) {
            return false;
        }
        return true;
    }
}
