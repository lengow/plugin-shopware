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
 * Lengow Import Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowImport
{
    /**
     * @var integer Shopware shop id
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
     * @var string marketplace order sku
     */
    protected $marketplaceSku = null;

    /**
     * @var string marketplace name
     */
    protected $marketplaceName = null;

    /**
     * @var integer Lengow order id
     */
    protected $lengowOrderId = null;

    /**
     * @var integer delivery address id
     */
    protected $deliveryAddressId = null;

    /**
     * @var integer number of orders to import
     */
    protected $limit = 0;

    /**
     * @var string start import date
     */
    protected $dateFrom = null;

    /**
     * @var string end import date
     */
    protected $dateTo = null;

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
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowConnector Lengow connector instance
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
        'waiting_shipment',
        'shipped',
        'closed'
    );

    /**
     * Construct the import manager
     *
     * @param $params array optional options
     * string  marketplace_sku     lengow marketplace order id to import
     * string  marketplace_name    lengow marketplace name to import
     * string  type                type of current import
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
        // params for re-import order
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
                $this->deliveryAddressId = $params['delivery_address_id'];
            }
        } else {
            $this->marketplaceSku = null;
            // recovering the time interval
            $days = isset($params['days'])
                ? (int)$params['days']
                : (int)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowImportDays');
            $this->dateFrom = date('c', strtotime(date('Y-m-d') . ' -' . $days . 'days'));
            $this->dateTo = date('c');
            $this->limit = (isset($params['limit']) ? (int)$params['limit'] : 0);
        }
        // get other params
        $this->preprodMode = isset($params['preprod_mode'])
            ? (bool)$params['preprod_mode']
            : (bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowImportPreprodEnabled'
            );
        $this->typeImport = (isset($params['type']) ? $params['type'] : 'manual');
        $this->logOutput = (isset($params['log_output']) ? (bool)$params['log_output'] : false);
        $this->shopId = (isset($params['shop_id']) ? (int)$params['shop_id'] : null);
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
        $isImportActivated = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowEnableImport'
        );
        // Import option enabled in plugin options
        if (!$isImportActivated) {
            // Required to display error in the frontend window
            $error['error'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'lengow_log/error/import_not_active'
            );
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'lengow_log/error/import_not_active'
                ),
                $this->logOutput
            );
            return $error;
        }
        // clean logs
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanLog();
        if (self::isInProcess() && !$this->preprodMode && !$this->importOneOrder) {
            $globalError = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/rest_time_to_import',
                array('rest_time' => self::restTimeToImport())
            );
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log('Import', $globalError, $this->logOutput);
        } elseif (!self::checkCredentials()) {
            $globalError = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/credentials_not_valid'
            );
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log('Import', $globalError, $this->logOutput);
        } else {
            // check Lengow catalogs for order synchronisation
            if (!$this->preprodMode && !$this->importOneOrder && $this->typeImport === 'manual') {
                Shopware_Plugins_Backend_Lengow_Components_LengowSync::syncCatalog();
            }
            // start order synchronisation
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/start',
                    array('type' => $this->typeImport)
                ),
                $this->logOutput
            );
            if ($this->preprodMode) {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/import/preprod_mode_active'
                    ),
                    $this->logOutput
                );
            }
            if (!$this->importOneOrder) {
                self::setInProcess();
                // update last import date
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::updateDateImport($this->typeImport);
            }
            // get all shops for import
            /** @var Shopware\Models\Shop\Shop[] $shops */
            $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowActiveShops();
            foreach ($shops as $shop) {
                if (!is_null($this->shopId) && $shop->getId() != $this->shopId) {
                    continue;
                }
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/import/start_for_shop',
                        array(
                            'name_shop' => $shop->getName(),
                            'id_shop' => $shop->getId()
                        )
                    ),
                    $this->logOutput
                );
                try {
                    // check shop catalog ids
                    if (!$this->checkCatalogIds($shop)) {
                        $errorCatalogIds = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'lengow_log/error/no_catalog_for_shop',
                            array(
                                'name_shop' => $shop->getName(),
                                'id_shop' => $shop->getId(),
                            )
                        );
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                            'Import',
                            $errorCatalogIds,
                            $this->logOutput
                        );
                        $error[$shop->getId()] = $errorCatalogIds;
                        continue;
                    }
                    // get orders from Lengow API
                    $orders = $this->getOrdersFromApi($shop);
                    $totalOrders = count($orders);
                    if ($this->importOneOrder) {
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                            'Import',
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                'log/import/find_one_order',
                                array(
                                    'nb_order' => $totalOrders,
                                    'marketplace_sku' => $this->marketplaceSku,
                                    'marketplace_name' => $this->marketplaceName,
                                    'account_id' => $this->accountId
                                )
                            ),
                            $this->logOutput
                        );
                    } else {
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                            'Import',
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                'log/import/find_all_orders',
                                array(
                                    'nb_order' => $totalOrders,
                                    'name_shop' => $shop->getName(),
                                    'id_shop' => $shop->getId()
                                )
                            ),
                            $this->logOutput
                        );
                    }
                    if ($totalOrders <= 0 && $this->importOneOrder) {
                        throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                            'lengow_log/error/order_not_found'
                        );
                    } elseif ($totalOrders <= 0) {
                        continue;
                    }
                    if (!is_null($this->lengowOrderId)) {
                        Shopware_Plugins_Backend_Lengow_Components_LengowOrderError::finishOrderErrors(
                            $this->lengowOrderId
                        );
                    }
                    $result = $this->importOrders($orders, $shop);
                    if (!$this->importOneOrder) {
                        $orderNew += $result['order_new'];
                        $orderUpdate += $result['order_update'];
                        $orderError += $result['order_error'];
                    }
                } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
                    $errorMessage = $e->getMessage();
                } catch (Exception $e) {
                    $errorMessage = '[Shopware error] "' . $e->getMessage() . '" '
                        . $e->getFile() . ' | ' . $e->getLine();
                }
                if (isset($errorMessage)) {
                    if (!is_null($this->lengowOrderId)) {
                        Shopware_Plugins_Backend_Lengow_Components_LengowOrderError::finishOrderErrors(
                            $this->lengowOrderId
                        );
                        Shopware_Plugins_Backend_Lengow_Components_LengowOrderError::createOrderError(
                            $this->lengowOrderId,
                            $errorMessage
                        );
                    }
                    $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                        $errorMessage
                    );
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        'Import',
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
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
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/error/nb_order_imported',
                        array('nb_order' => $orderNew)
                    ),
                    $this->logOutput
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/error/nb_order_updated',
                        array('nb_order' => $orderUpdate)
                    ),
                    $this->logOutput
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/error/nb_order_with_error',
                        array('nb_order' => $orderError)
                    ),
                    $this->logOutput
                );
            }
            // finish import process
            self::setEnd();
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/end',
                    array('type' => $this->typeImport)
                ),
                $this->logOutput
            );
            //check if order action is finish (Ship / Cancel)
            if (!$this->preprodMode && !$this->importOneOrder && $this->typeImport === 'manual') {
                Shopware_Plugins_Backend_Lengow_Components_LengowAction::checkFinishAction();
                Shopware_Plugins_Backend_Lengow_Components_LengowAction::checkOldAction();
                Shopware_Plugins_Backend_Lengow_Components_LengowAction::checkActionNotSent();
            }
        }
        if ($globalError) {
            $error[0] = $globalError;
            if (!is_null($this->lengowOrderId)) {
                Shopware_Plugins_Backend_Lengow_Components_LengowOrderError::finishOrderErrors(
                    $this->lengowOrderId
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowOrderError::createOrderError(
                    $this->lengowOrderId,
                    $globalError
                );
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
                'error' => $error
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
        if (Shopware_Plugins_Backend_Lengow_Components_LengowConnector::isValidAuth()) {
            $accessIds = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getAccessIds();
            list($this->accountId, $this->accessToken, $this->secretToken) = $accessIds;
            $this->connector = new Shopware_Plugins_Backend_Lengow_Components_LengowConnector(
                $this->accessToken,
                $this->secretToken
            );
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
        $catalogIds = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getCatalogIds($shop);
        foreach ($catalogIds as $catalogId) {
            if (array_key_exists($catalogId, $this->catalogIds)) {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
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
        if (count($shopCatalogIds) > 0) {
            $this->shopCatalogIds = $shopCatalogIds;
            return true;
        }
        return false;
    }

    /**
     * Call Lengow order API
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException no connection with Lengow webservice
     *                                                                    error on lengow_webservice
     *
     * @return array
     */
    protected function getOrdersFromApi($shop)
    {
        $page = 1;
        $orders = array();
        if ($this->importOneOrder) {
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/connector_get_order',
                    array(
                        'marketplace_sku' => $this->marketplaceSku,
                        'marketplace_name' => $this->marketplaceName
                    )
                ),
                $this->logOutput
            );
        } else {
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/connector_get_all_order',
                    array(
                        'date_from' => date('Y-m-d', strtotime((string)$this->dateFrom)),
                        'date_to' => date('Y-m-d', strtotime((string)$this->dateTo)),
                        'catalog_id' => implode(', ', $this->shopCatalogIds)
                    )
                ),
                $this->logOutput
            );
        }
        do {
            if ($this->importOneOrder) {
                $results = $this->connector->get(
                    '/v3.0/orders',
                    array(
                        'marketplace_order_id' => $this->marketplaceSku,
                        'marketplace' => $this->marketplaceName,
                        'account_id' => $this->accountId
                    ),
                    'stream'
                );
            } else {
                $results = $this->connector->get(
                    '/v3.0/orders',
                    array(
                        'updated_from' => $this->dateFrom,
                        'updated_to' => $this->dateTo,
                        'catalog_ids' => implode(',', $this->shopCatalogIds),
                        'account_id' => $this->accountId,
                        'page' => $page
                    ),
                    'stream'
                );
            }
            if (is_null($results)) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/no_connection_webservice',
                        array(
                            'name_shop' => $shop->getName(),
                            'id_shop' => $shop->getId()
                        )
                    )
                );
            }
            $results = json_decode($results);
            if (!is_object($results)) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/no_connection_webservice',
                        array(
                            'name_shop' => $shop->getName(),
                            'id_shop' => $shop->getId()
                        )
                    )
                );
            }
            if (isset($results->error)) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/error_lengow_webservice',
                        array(
                            'error_code' => $results->error->code,
                            'error_message' => $results->error->message,
                            'name_shop' => $shop->getName(),
                            'id_shop' => $shop->getId()
                        )
                    )
                );
            }
            // Construct array orders
            foreach ($results->results as $order) {
                $orders[] = $order;
            }
            $page++;
            $finish = (is_null($results->next) || $this->importOneOrder) ? true : false;
        } while ($finish != true);
        return $orders;
    }

    /**
     * Create or update order in Shopware
     *
     * @param mixed $orders API orders
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
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
            if (count($orderData->packages) == 0) {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/import/error_no_package'
                    ),
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
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        'Import',
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/import/error_no_delivery_address'
                        ),
                        $this->logOutput,
                        $marketplaceSku
                    );
                    continue;
                }
                $packageDeliveryAddressId = (int)$packageData->delivery->id;
                $firstPackage = ($nbPackage > 1 ? false : true);
                // check the package for re-import order
                if ($this->importOneOrder) {
                    if (!is_null($this->deliveryAddressId)
                        && $this->deliveryAddressId != $packageDeliveryAddressId
                    ) {
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                            'Import',
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                'log/import/error_wrong_package_number'
                            ),
                            $this->logOutput,
                            $marketplaceSku
                        );
                        continue;
                    }
                }
                try {
                    // try to import or update order
                    $importOrder = new Shopware_Plugins_Backend_Lengow_Components_LengowImportOrder(
                        array(
                            'shop' => $shop,
                            'preprod_mode' => $this->preprodMode,
                            'log_output' => $this->logOutput,
                            'marketplace_sku' => $marketplaceSku,
                            'delivery_address_id' => $packageDeliveryAddressId,
                            'order_data' => $orderData,
                            'package_data' => $packageData,
                            'first_package' => $firstPackage
                        )
                    );
                    $order = $importOrder->importOrder();
                } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
                    $errorMessage = $e->getMessage();
                } catch (Exception $e) {
                    $errorMessage = '[Shopware error]: "' . $e->getMessage() . '" '
                        . $e->getFile() . ' | ' . $e->getLine();
                }
                if (isset($errorMessage)) {
                    $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                        $errorMessage
                    );
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        'Import',
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/import/order_import_failed',
                            array('decoded_message' => $decodedMessage)
                        ),
                        $this->logOutput,
                        $marketplaceSku
                    );
                    unset($errorMessage);
                    continue;
                }
                // Sync to lengow if no preprod_mode
                if (!$this->preprodMode && isset($order['order_new']) && $order['order_new'] == true) {
                    $shopwareOrder = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')
                        ->findOneBy(array('id' => $order['order_id']));
                    $synchro = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::synchronizeOrder(
                        $shopwareOrder,
                        $this->connector
                    );
                    if ($synchro) {
                        $synchroMessage =  Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/import/order_synchronized_with_lengow',
                            array('order_id' => $shopwareOrder->getNumber())
                        );
                    } else {
                        $synchroMessage =  Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/import/order_not_synchronized_with_lengow',
                            array('order_id' => $shopwareOrder->getNumber())
                        );
                    }
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        'Import',
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
                    if (isset($order['order_new']) && $order['order_new'] == true) {
                        $orderNew++;
                    } elseif (isset($order['order_update']) && $order['order_update'] == true) {
                        $orderUpdate++;
                    } elseif (isset($order['order_error']) && $order['order_error'] == true) {
                        $orderError++;
                    }
                }
                // clean process
                unset($importOrder, $order);
                // if limit is set
                if ($this->limit > 0 && $orderNew == $this->limit) {
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
            'order_error' => $orderError
        );
    }

    /**
     * Check if import is already in process
     *
     * @return boolean
     */
    public static function isInProcess()
    {
        $timestamp = (int)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowImportInProgress'
        );
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
        $timestamp = (int)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowImportInProgress'
        );
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
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig('lengowImportInProgress', time());
    }

    /**
     * Set import to finished
     */
    public static function setEnd()
    {
        self::$processing = false;
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig('lengowImportInProgress', -1);
    }

    /**
     * Check if order status is valid for import
     *
     * @param string $orderStateMarketplace order state
     * @param Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace $marketplace marketplace instance
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

    /**
     * Get last import launched manually or by the cron
     *
     * @return string
     */
    public static function getLastImport()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $repository = $em->getRepository('Shopware\CustomModels\Lengow\Settings');
        /** @var Shopware\CustomModels\Lengow\Settings $cron */
        $cron = $repository->findOneBy(array('name' => 'lengowLastImportCron'));
        /** @var Shopware\CustomModels\Lengow\Settings $manual */
        $manual = $repository->findOneBy(array('name' => 'lengowLastImportManual'));
        if ($cron->getDateUpd() > $manual->getDateUpd()) {
            return $cron->getDateUpd()->format('l d F Y @ H:i');
        } else {
            return $manual->getDateUpd()->format('l d F Y @ H:i');
        }
    }
}
