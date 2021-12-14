<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @copyright   2021 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware\Models\Order\History as OrderHistoryModel;
use Shopware\Models\Order\Order as OrderModel;
use Shopware\CustomModels\Lengow\Order as LengowOrderModel;
use Shopware\CustomModels\Lengow\OrderError as LengowOrderErrorModel;
use Shopware_Plugins_Backend_Lengow_Components_LengowAction as LengowAction;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowConnector as LengowConnector;
use Shopware_Plugins_Backend_Lengow_Components_LengowExport as LengowExport;
use Shopware_Plugins_Backend_Lengow_Components_LengowImport as LengowImport;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrderError as LengowOrderError;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

/**
 * Lengow Toolbox Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowToolbox
{
    /* Toolbox GET params */
    const PARAM_CREATED_FROM = 'created_from';
    const PARAM_CREATED_TO = 'created_to';
    const PARAM_DATE = 'date';
    const PARAM_DAYS = 'days';
    const PARAM_FORCE = 'force';
    const PARAM_MARKETPLACE_NAME = 'marketplace_name';
    const PARAM_MARKETPLACE_SKU = 'marketplace_sku';
    const PARAM_PROCESS = 'process';
    const PARAM_SHOP_ID = 'shop_id';
    const PARAM_TOKEN = 'token';
    const PARAM_TOOLBOX_ACTION = 'toolbox_action';
    const PARAM_TYPE = 'type';

    /* Toolbox Actions */
    const ACTION_DATA = 'data';
    const ACTION_LOG = 'log';
    const ACTION_ORDER = 'order';

    /* Data type */
    const DATA_TYPE_ACTION = 'action';
    const DATA_TYPE_ALL = 'all';
    const DATA_TYPE_CHECKLIST = 'checklist';
    const DATA_TYPE_CHECKSUM = 'checksum';
    const DATA_TYPE_CMS = 'cms';
    const DATA_TYPE_ERROR = 'error';
    const DATA_TYPE_EXTRA = 'extra';
    const DATA_TYPE_LOG = 'log';
    const DATA_TYPE_PLUGIN = 'plugin';
    const DATA_TYPE_OPTION = 'option';
    const DATA_TYPE_ORDER = 'order';
    const DATA_TYPE_ORDER_STATUS = 'order_status';
    const DATA_TYPE_SHOP = 'shop';
    const DATA_TYPE_SYNCHRONIZATION = 'synchronization';

    /* Toolbox process type */
    const PROCESS_TYPE_GET_DATA = 'get_data';
    const PROCESS_TYPE_SYNC = 'sync';

    /* Toolbox Data  */
    const CHECKLIST = 'checklist';
    const CHECKLIST_CURL_ACTIVATED = 'curl_activated';
    const CHECKLIST_SIMPLE_XML_ACTIVATED = 'simple_xml_activated';
    const CHECKLIST_JSON_ACTIVATED = 'json_activated';
    const CHECKLIST_MD5_SUCCESS = 'md5_success';
    const PLUGIN = 'plugin';
    const PLUGIN_CMS_VERSION = 'cms_version';
    const PLUGIN_VERSION = 'plugin_version';
    const PLUGIN_DEBUG_MODE_DISABLE = 'debug_mode_disable';
    const PLUGIN_WRITE_PERMISSION = 'write_permission';
    const PLUGIN_SERVER_IP = 'server_ip';
    const PLUGIN_AUTHORIZED_IP_ENABLE = 'authorized_ip_enable';
    const PLUGIN_AUTHORIZED_IPS = 'authorized_ips';
    const PLUGIN_TOOLBOX_URL = 'toolbox_url';
    const SYNCHRONIZATION = 'synchronization';
    const SYNCHRONIZATION_CMS_TOKEN = 'cms_token';
    const SYNCHRONIZATION_CRON_URL = 'cron_url';
    const SYNCHRONIZATION_NUMBER_ORDERS_IMPORTED = 'number_orders_imported';
    const SYNCHRONIZATION_NUMBER_ORDERS_WAITING_SHIPMENT = 'number_orders_waiting_shipment';
    const SYNCHRONIZATION_NUMBER_ORDERS_IN_ERROR = 'number_orders_in_error';
    const SYNCHRONIZATION_SYNCHRONIZATION_IN_PROGRESS = 'synchronization_in_progress';
    const SYNCHRONIZATION_LAST_SYNCHRONIZATION = 'last_synchronization';
    const SYNCHRONIZATION_LAST_SYNCHRONIZATION_TYPE = 'last_synchronization_type';
    const CMS_OPTIONS = 'cms_options';
    const SHOPS = 'shops';
    const SHOP_ID = 'shop_id';
    const SHOP_NAME = 'shop_name';
    const SHOP_DOMAIN_URL = 'domain_url';
    const SHOP_TOKEN = 'shop_token';
    const SHOP_FEED_URL = 'feed_url';
    const SHOP_ENABLED = 'enabled';
    const SHOP_CATALOG_IDS = 'catalog_ids';
    const SHOP_NUMBER_PRODUCTS_AVAILABLE = 'number_products_available';
    const SHOP_NUMBER_PRODUCTS_EXPORTED = 'number_products_exported';
    const SHOP_LAST_EXPORT = 'last_export';
    const SHOP_OPTIONS = 'shop_options';
    const CHECKSUM = 'checksum';
    const CHECKSUM_AVAILABLE = 'available';
    const CHECKSUM_SUCCESS = 'success';
    const CHECKSUM_NUMBER_FILES_CHECKED = 'number_files_checked';
    const CHECKSUM_NUMBER_FILES_MODIFIED = 'number_files_modified';
    const CHECKSUM_NUMBER_FILES_DELETED = 'number_files_deleted';
    const CHECKSUM_FILE_MODIFIED = 'file_modified';
    const CHECKSUM_FILE_DELETED = 'file_deleted';
    const LOGS = 'logs';

    /* Toolbox order data  */
    const ID = 'id';
    const ORDERS = 'orders';
    const ORDER_MARKETPLACE_SKU = 'marketplace_sku';
    const ORDER_MARKETPLACE_NAME = 'marketplace_name';
    const ORDER_MARKETPLACE_LABEL = 'marketplace_label';
    const ORDER_MERCHANT_ORDER_ID = 'merchant_order_id';
    const ORDER_MERCHANT_ORDER_REFERENCE = 'merchant_order_reference';
    const ORDER_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    const ORDER_DELIVERY_COUNTRY_ISO = 'delivery_country_iso';
    const ORDER_PROCESS_STATE = 'order_process_state';
    const ORDER_STATUSES = 'order_statuses';
    const ORDER_STATUS = 'order_status';
    const ORDER_MERCHANT_ORDER_STATUS = 'merchant_order_status';
    const ORDER_TOTAL_PAID = 'total_paid';
    const ORDER_MERCHANT_TOTAL_PAID = 'merchant_total_paid';
    const ORDER_COMMISSION= 'commission';
    const ORDER_CURRENCY = 'currency';
    const ORDER_DATE = 'order_date';
    const ORDER_ITEMS = 'order_items';
    const ORDER_IS_REIMPORTED = 'is_reimported';
    const ORDER_IS_IN_ERROR = 'is_in_error';
    const ORDER_ACTION_IN_PROGRESS = 'action_in_progress';
    const CUSTOMER = 'customer';
    const CUSTOMER_NAME = 'name';
    const CUSTOMER_EMAIL = 'email';
    const CUSTOMER_VAT_NUMBER = 'vat_number';
    const ORDER_TYPES = 'order_types';
    const ORDER_TYPE_EXPRESS = 'is_express';
    const ORDER_TYPE_PRIME = 'is_prime';
    const ORDER_TYPE_BUSINESS = 'is_business';
    const ORDER_TYPE_DELIVERED_BY_MARKETPLACE = 'is_delivered_by_marketplace';
    const TRACKING = 'tracking';
    const TRACKING_CARRIER = 'carrier';
    const TRACKING_METHOD = 'method';
    const TRACKING_NUMBER = 'tracking_number';
    const TRACKING_RELAY_ID = 'relay_id';
    const TRACKING_MERCHANT_CARRIER = 'merchant_carrier';
    const TRACKING_MERCHANT_TRACKING_NUMBER = 'merchant_tracking_number';
    const TRACKING_MERCHANT_TRACKING_URL = 'merchant_tracking_url';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const IMPORTED_AT = 'imported_at';
    const ERRORS = 'errors';
    const ERROR_TYPE = 'type';
    const ERROR_MESSAGE = 'message';
    const ERROR_CODE = 'code';
    const ERROR_FINISHED = 'is_finished';
    const ERROR_REPORTED = 'is_reported';
    const ACTIONS = 'actions';
    const ACTION_ID = 'action_id';
    const ACTION_PARAMETERS = 'parameters';
    const ACTION_RETRY = 'retry';
    const ACTION_FINISH = 'is_finished';

    /* Process state labels */
    const PROCESS_STATE_NEW = 'new';
    const PROCESS_STATE_IMPORT = 'import';
    const PROCESS_STATE_FINISH = 'finish';

    /* Error type labels */
    const TYPE_ERROR_IMPORT = 'import';
    const TYPE_ERROR_SEND = 'send';

    /* PHP extensions */
    const PHP_EXTENSION_CURL = 'curl_version';
    const PHP_EXTENSION_SIMPLEXML = 'simplexml_load_file';
    const PHP_EXTENSION_JSON = 'json_decode';

    /* Toolbox files */
    const FILE_CHECKMD5 = 'checkmd5.csv';
    const FILE_TEST = 'test.txt';

    /**
     * @var array valid toolbox actions
     */
    public static $toolboxActions = array(
        self::ACTION_DATA,
        self::ACTION_LOG,
        self::ACTION_ORDER,
    );

    /**
     * Get all toolbox data
     *
     * @param string $type Toolbox data type
     *
     * @return array
     */
    public static function getData($type = self::DATA_TYPE_CMS)
    {
        switch ($type) {
            case self::DATA_TYPE_ALL:
                return self::getAllData();
            case self::DATA_TYPE_CHECKLIST:
                return self::getChecklistData();
            case self::DATA_TYPE_CHECKSUM:
                return self::getChecksumData();
            case self::DATA_TYPE_LOG:
                return self::getLogData();
            case self::DATA_TYPE_OPTION:
                return self::getOptionData();
            case self::DATA_TYPE_PLUGIN:
                return self::getPluginData();
            case self::DATA_TYPE_SHOP:
                return self::getShopData();
            case self::DATA_TYPE_SYNCHRONIZATION:
                return self::getSynchronizationData();
            default:
            case self::DATA_TYPE_CMS:
                return self::getCmsData();
        }
    }

    /**
     * Download log file individually or globally
     *
     * @param string|null $date name of file to download
     */
    public static function downloadLog($date = null)
    {
        LengowLog::download($date);
    }

    /**
     * Start order synchronization based on specific parameters
     *
     * @param array $params synchronization parameters
     *
     * @return array
     */
    public static function syncOrders($params = array())
    {
        // get all params for order synchronization
        $params = self::filterParamsForSync($params);
        $import = new LengowImport($params);
        $result = $import->exec();
        // if global error return error message and request http code
        if (isset($result[LengowImport::ERRORS][0])) {
            return self::generateErrorReturn(LengowConnector::CODE_403, $result[LengowImport::ERRORS][0]);
        }
        unset($result[LengowImport::ERRORS]);
        return $result;
    }

    /**
     * Get all order data from a marketplace reference
     *
     * @param string|null $marketplaceSku marketplace order reference
     * @param string|null $marketplaceName marketplace code
     * @param string $type Toolbox order data type
     *
     * @return array
     */
    public static function getOrderData($marketplaceSku = null, $marketplaceName = null, $type = self::DATA_TYPE_ORDER)
    {
        $lengowOrders = $marketplaceSku && $marketplaceName
            ? LengowOrder::getAllLengowOrders($marketplaceSku, $marketplaceName)
            : array();
        // if no reference is found, process is blocked
        if (empty($lengowOrders)) {
            return self::generateErrorReturn(
                LengowConnector::CODE_404,
                LengowMain::setLogMessage('log/import/unable_find_order')
            );
        }
        $orders = array();
        foreach ($lengowOrders as $lengowOrder) {
            if ($type === self::DATA_TYPE_EXTRA) {
                return self::getOrderExtraData($lengowOrder);
            }
            $marketplaceLabel = $lengowOrder->getMarketplaceLabel();
            $orders[] = self::getOrderDataByType($lengowOrder, $type);
        }
        return array(
            self::ORDER_MARKETPLACE_SKU => $marketplaceSku,
            self::ORDER_MARKETPLACE_NAME => $marketplaceName,
            self::ORDER_MARKETPLACE_LABEL => isset($marketplaceLabel) ? $marketplaceLabel : null,
            self::ORDERS => $orders,
        );
    }

    /**
     * Check if PHP Curl is activated
     *
     * @return boolean
     */
    public static function isCurlActivated()
    {
        return function_exists(self::PHP_EXTENSION_CURL);
    }

    /**
     * Get all data
     *
     * @return array
     */
    private static function getAllData()
    {
        return array(
            self::CHECKLIST => self::getChecklistData(),
            self::PLUGIN => self::getPluginData(),
            self::SYNCHRONIZATION => self::getSynchronizationData(),
            self::CMS_OPTIONS => LengowConfiguration::getAllValues(null, true),
            self::SHOPS => self::getShopData(),
            self::CHECKSUM => self::getChecksumData(),
            self::LOGS => self::getLogData(),
        );
    }

    /**
     * Get cms data
     *
     * @return array
     */
    private static function getCmsData()
    {
        return array(
            self::CHECKLIST => self::getChecklistData(),
            self::PLUGIN => self::getPluginData(),
            self::SYNCHRONIZATION => self::getSynchronizationData(),
            self::CMS_OPTIONS => LengowConfiguration::getAllValues(null, true),
        );
    }

    /**
     * Get array of requirements
     *
     * @return array
     */
    private static function getChecklistData()
    {
        $checksumData = self::getChecksumData();
        return array(
            self::CHECKLIST_CURL_ACTIVATED => self::isCurlActivated(),
            self::CHECKLIST_SIMPLE_XML_ACTIVATED => self::isSimpleXMLActivated(),
            self::CHECKLIST_JSON_ACTIVATED => self::isJsonActivated(),
            self::CHECKLIST_MD5_SUCCESS => $checksumData[self::CHECKSUM_SUCCESS],
        );
    }

    /**
     * Get array of plugin data
     *
     * @return array
     */
    private static function getPluginData()
    {
        return array(
            self::PLUGIN_CMS_VERSION => LengowMain::getShopwareVersion(),
            self::PLUGIN_VERSION => Shopware()->Plugins()->Backend()->Lengow()->getVersion(),
            self::PLUGIN_DEBUG_MODE_DISABLE => !LengowConfiguration::debugModeIsActive(),
            self::PLUGIN_WRITE_PERMISSION => self::testWritePermission(),
            self::PLUGIN_SERVER_IP => $_SERVER['SERVER_ADDR'],
            self::PLUGIN_AUTHORIZED_IP_ENABLE => (bool) LengowConfiguration::getConfig(
                LengowConfiguration::AUTHORIZED_IP_ENABLED
            ),
            self::PLUGIN_AUTHORIZED_IPS => LengowConfiguration::getAuthorizedIps(),
            self::PLUGIN_TOOLBOX_URL => LengowMain::getToolboxUrl(),
        );
    }

    /**
     * Get array of synchronization data
     *
     * @return array
     */
    private static function getSynchronizationData()
    {
        $lastImport = LengowMain::getLastImport();
        return array(
            self::SYNCHRONIZATION_CMS_TOKEN => LengowMain::getToken(),
            self::SYNCHRONIZATION_CRON_URL => LengowMain::getCronUrl(),
            self::SYNCHRONIZATION_NUMBER_ORDERS_IMPORTED => LengowOrder::countOrderImportedByLengow(),
            self::SYNCHRONIZATION_NUMBER_ORDERS_WAITING_SHIPMENT => LengowOrder::countOrderToBeSent(),
            self::SYNCHRONIZATION_NUMBER_ORDERS_IN_ERROR => LengowOrder::countOrderWithError(),
            self::SYNCHRONIZATION_SYNCHRONIZATION_IN_PROGRESS => LengowImport::isInProcess(),
            self::SYNCHRONIZATION_LAST_SYNCHRONIZATION => $lastImport['type'] === 'none' ? 0 : $lastImport['timestamp'],
            self::SYNCHRONIZATION_LAST_SYNCHRONIZATION_TYPE => $lastImport['type'],
        );
    }

    /**
     * Get array of export data
     *
     * @return array
     */
    private static function getShopData()
    {
        $exportData = array();
        $shops = LengowMain::getActiveShops();
        if (empty($shops)) {
            return $exportData;
        }
        foreach ($shops as $shop) {
            $lengowExport = new LengowExport($shop);
            $lastExport = LengowConfiguration::getConfig(LengowConfiguration::LAST_UPDATE_EXPORT, $shop);
            $exportData[] = array(
                self::SHOP_ID => $shop->getId(),
                self::SHOP_NAME => $shop->getName(),
                self::SHOP_DOMAIN_URL => LengowMain::getShopUrl($shop),
                self::SHOP_TOKEN => LengowMain::getToken($shop),
                self::SHOP_FEED_URL => LengowMain::getExportUrl($shop),
                self::SHOP_ENABLED => LengowConfiguration::shopIsActive($shop),
                self::SHOP_CATALOG_IDS => LengowConfiguration::getCatalogIds($shop),
                self::SHOP_NUMBER_PRODUCTS_AVAILABLE => $lengowExport->getTotalProduct(),
                self::SHOP_NUMBER_PRODUCTS_EXPORTED => $lengowExport->getTotalExportProduct(),
                self::SHOP_LAST_EXPORT => empty($lastExport) ? 0 : (int) $lastExport,
                self::SHOP_OPTIONS => LengowConfiguration::getAllValues($shop, true),
            );
        }
        return $exportData;
    }

    /**
     * Get array of export data
     *
     * @return array
     */
    private static function getOptionData()
    {
        $optionData = array(
            self::CMS_OPTIONS => LengowConfiguration::getAllValues(),
            self::SHOP_OPTIONS => array(),
        );
        $shops = LengowMain::getActiveShops();
        foreach ($shops as $shop) {
            $optionData[self::SHOP_OPTIONS][] = LengowConfiguration::getAllValues($shop);
        }
        return $optionData;
    }

    /**
     * Get files checksum
     *
     * @return array
     */
    private static function getChecksumData()
    {
        $fileCounter = 0;
        $fileModified = array();
        $fileDeleted = array();
        $sep = DIRECTORY_SEPARATOR;
        $fileName = LengowMain::getLengowFolder() . $sep . LengowMain::FOLDER_TOOLBOX . $sep . self::FILE_CHECKMD5;
        if (file_exists($fileName)) {
            $md5Available = true;
            if (($file = fopen($fileName, 'r')) !== false) {
                while (($data = fgetcsv($file, 1000, '|')) !== false) {
                    $fileCounter++;
                    $shortPath = $data[0];
                    $filePath = LengowMain::getLengowFolder() . $data[0];
                    if (file_exists($filePath)) {
                        $fileMd = md5_file($filePath);
                        if ($fileMd !== $data[1]) {
                            $fileModified[] = $shortPath;
                        }
                    } else {
                        $fileDeleted[] = $shortPath;
                    }
                }
                fclose($file);
            }
        } else {
            $md5Available = false;
        }
        $fileModifiedCounter = count($fileModified);
        $fileDeletedCounter = count($fileDeleted);
        $md5Success = $md5Available && !($fileModifiedCounter > 0) && !($fileDeletedCounter > 0);
        return array(
            self::CHECKSUM_AVAILABLE => $md5Available,
            self::CHECKSUM_SUCCESS => $md5Success,
            self::CHECKSUM_NUMBER_FILES_CHECKED => $fileCounter,
            self::CHECKSUM_NUMBER_FILES_MODIFIED => $fileModifiedCounter,
            self::CHECKSUM_NUMBER_FILES_DELETED => $fileDeletedCounter,
            self::CHECKSUM_FILE_MODIFIED => $fileModified,
            self::CHECKSUM_FILE_DELETED => $fileDeleted,
        );
    }

    /**
     * Get all log files available
     *
     * @return array
     */
    private static function getLogData()
    {
        $logs = LengowLog::getPaths();
        if (!empty($logs)) {
            $logs[] = array(
                LengowLog::LOG_DATE => null,
                LengowLog::LOG_LINK => LengowMain::getToolboxUrl()
                    . '&' . self::PARAM_TOOLBOX_ACTION . '=' . self::ACTION_LOG,
            );
        }
        return $logs;
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return boolean
     */
    private static function isSimpleXMLActivated()
    {
        return function_exists(self::PHP_EXTENSION_SIMPLEXML);
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return boolean
     */
    private static function isJsonActivated()
    {
        return function_exists(self::PHP_EXTENSION_JSON);
    }

    /**
     * Test write permission for log and export in file
     *
     * @return boolean
     */
    private static function testWritePermission()
    {
        $sep = DIRECTORY_SEPARATOR;
        $filePath = LengowMain::getLengowFolder() . $sep . LengowMain::FOLDER_CONFIG . $sep . self::FILE_TEST;
        try {
            $file = fopen($filePath, 'wb+');
            if (!$file) {
                return false;
            }
            unlink($filePath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Filter parameters for order synchronization
     *
     * @param array $params synchronization params
     *
     * @return array
     */
    private static function filterParamsForSync($params = array())
    {
        $paramsFiltered = array(LengowImport::PARAM_TYPE => LengowImport::TYPE_TOOLBOX);
        if (isset(
            $params[self::PARAM_MARKETPLACE_SKU],
            $params[self::PARAM_MARKETPLACE_NAME],
            $params[self::PARAM_SHOP_ID]
        )) {
            // get all parameters to synchronize a specific order
            $paramsFiltered[LengowImport::PARAM_MARKETPLACE_SKU] = $params[self::PARAM_MARKETPLACE_SKU];
            $paramsFiltered[LengowImport::PARAM_MARKETPLACE_NAME] = $params[self::PARAM_MARKETPLACE_NAME];
            $paramsFiltered[LengowImport::PARAM_SHOP_ID] = (int) $params[self::PARAM_SHOP_ID];
        } elseif (isset($params[self::PARAM_CREATED_FROM], $params[self::PARAM_CREATED_TO])) {
            // get all parameters to synchronize over a fixed period
            $paramsFiltered[LengowImport::PARAM_CREATED_FROM] = $params[self::PARAM_CREATED_FROM];
            $paramsFiltered[LengowImport::PARAM_CREATED_TO] = $params[self::PARAM_CREATED_TO];
        } elseif (isset($params[self::PARAM_DAYS])) {
            // get all parameters to synchronize over a time interval
            $paramsFiltered[LengowImport::PARAM_DAYS] = (int) $params[self::PARAM_DAYS];
        }
        // force order synchronization by removing pending errors
        if (isset($params[self::PARAM_FORCE])) {
            $paramsFiltered[LengowImport::PARAM_FORCE_SYNC] = (bool) $params[self::PARAM_FORCE];
        }
        return $paramsFiltered;
    }

    /**
     * Get array of all the data of the order
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     * @param string $type Toolbox order data type
     *
     * @return array
     */
    private static function getOrderDataByType($lengowOrder, $type)
    {
        $order = $lengowOrder->getOrder();
        $orderReferences = array(
            self::ID => $lengowOrder->getId(),
            self::ORDER_MERCHANT_ORDER_ID  => $order ? $order->getId() : null,
            self::ORDER_MERCHANT_ORDER_REFERENCE  => $order ? $order->getNumber() : null,
            self::ORDER_DELIVERY_ADDRESS_ID => $lengowOrder->getDeliveryAddressId(),
        );
        switch ($type) {
            case self::DATA_TYPE_ACTION:
                $orderData = array(
                    self::ACTIONS => $order ? self::getOrderActionData($order) : array(),
                );
                break;
            case self::DATA_TYPE_ERROR:
                $orderData = array(
                    self::ERRORS => self::getOrderErrorsData($lengowOrder),
                );
                break;
            case self::DATA_TYPE_ORDER_STATUS:
                $orderData = array(
                    self::ORDER_STATUSES => $order ? self::getOrderStatusesData($order) : array(),
                );
                break;
            case self::DATA_TYPE_ORDER:
            default:
                $orderData = self::getAllOrderData($lengowOrder, $order);
        }
        return array_merge($orderReferences, $orderData);
    }

    /**
     * Get array of all the data of the order
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     * @param OrderModel|null $order Shopware order instance
     *
     * @return array
     */
    private static function getAllOrderData($lengowOrder, $order = null)
    {
        $orderDispatch = $order ? $order->getDispatch() : null;
        $hasActionInProgress = $order && LengowAction::getActionByOrderId($order->getId());
        $orderPayment = $order ? $order->getPaymentInstances()->first() : null;
        $merchantOrderStatus = null;
        if ($order) {
            $merchantOrderStatus = LengowMain::compareVersion('5.1')
                ? $order->getOrderStatus()->getName()
                : $order->getOrderStatus()->getDescription();
        }
        $orderTypes = json_decode($lengowOrder->getOrderTypes(), true);
        return array(
            self::ORDER_DELIVERY_COUNTRY_ISO => $lengowOrder->getDeliveryCountryIso(),
            self::ORDER_PROCESS_STATE => self::getOrderProcessLabel($lengowOrder->getOrderProcessState()),
            self::ORDER_STATUS => $lengowOrder->getOrderLengowState(),
            self::ORDER_MERCHANT_ORDER_STATUS => $merchantOrderStatus,
            self::ORDER_STATUSES => $order ? self::getOrderStatusesData($order) : array(),
            self::ORDER_TOTAL_PAID => $lengowOrder->getTotalPaid(),
            self::ORDER_MERCHANT_TOTAL_PAID => $order ? $order->getInvoiceAmount() : null,
            self::ORDER_COMMISSION => $lengowOrder->getCommission(),
            self::ORDER_CURRENCY => $lengowOrder->getCurrency(),
            self::CUSTOMER => array(
                self::CUSTOMER_NAME => !empty($lengowOrder->getCustomerName()) ? $lengowOrder->getCustomerName() : null,
                self::CUSTOMER_EMAIL => !empty($lengowOrder->getCustomerEmail())
                    ? $lengowOrder->getCustomerEmail()
                    : null,
                self::CUSTOMER_VAT_NUMBER => !empty($lengowOrder->getCustomerVatNumber())
                    ? $lengowOrder->getCustomerVatNumber()
                    : null,
            ),
            self::ORDER_DATE => $lengowOrder->getOrderDate()->getTimestamp(),
            self::ORDER_TYPES => array(
                self::ORDER_TYPE_EXPRESS => isset($orderTypes[LengowOrder::TYPE_EXPRESS]),
                self::ORDER_TYPE_PRIME => isset($orderTypes[LengowOrder::TYPE_PRIME]),
                self::ORDER_TYPE_BUSINESS => isset($orderTypes[LengowOrder::TYPE_BUSINESS]),
                self::ORDER_TYPE_DELIVERED_BY_MARKETPLACE => isset(
                    $orderTypes[LengowOrder::TYPE_DELIVERED_BY_MARKETPLACE]
                ),
            ),
            self::ORDER_ITEMS => $lengowOrder->getOrderItem(),
            self::TRACKING => array(
                self::TRACKING_CARRIER => !empty($lengowOrder->getCarrier())
                    ? $lengowOrder->getCarrier()
                    : null,
                self::TRACKING_METHOD => !empty($lengowOrder->getCarrierMethod())
                    ? $lengowOrder->getCarrierMethod()
                    : null,
                self::TRACKING_NUMBER => !empty($lengowOrder->getCarrierTracking())
                    ? $lengowOrder->getCarrierTracking()
                    : null,
                self::TRACKING_RELAY_ID => !empty($lengowOrder->getCarrierIdRelay())
                    ? $lengowOrder->getCarrierIdRelay()
                    : null,
                self::TRACKING_MERCHANT_CARRIER => $orderDispatch ? $orderDispatch->getName() : null,
                self::TRACKING_MERCHANT_TRACKING_NUMBER => $order && !empty($order->getTrackingCode())
                    ? $order->getTrackingCode()
                    : null,
                self::TRACKING_MERCHANT_TRACKING_URL => $orderDispatch && !empty($orderDispatch->getStatusLink())
                    ? $orderDispatch->getStatusLink()
                    : null,
            ),
            self::ORDER_IS_REIMPORTED => $lengowOrder->isReimported(),
            self::ORDER_IS_IN_ERROR => $lengowOrder->isInError(),
            self::ERRORS => self::getOrderErrorsData($lengowOrder),
            self::ORDER_ACTION_IN_PROGRESS => $hasActionInProgress,
            self::ACTIONS => $order ? self::getOrderActionData($order) : array(),
            self::CREATED_AT => $lengowOrder->getCreatedAt()->getTimestamp(),
            self::UPDATED_AT => $lengowOrder->getUpdatedAt() ? $lengowOrder->getUpdatedAt()->getTimestamp() : 0,
            self::IMPORTED_AT => $orderPayment ? $orderPayment->getCreatedAt()->getTimestamp() : 0,
        );
    }

    /**
     * Get array of all the errors of a Lengow order
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @return array
     */
    private static function getOrderErrorsData($lengowOrder)
    {
        $orderErrors = array();
        /** @var LengowOrderErrorModel[] $errors */
        $errors = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\OrderError')
            ->findBy(array('lengowOrder' => $lengowOrder));
        if ($errors) {
            foreach ($errors as $error) {
                $orderErrors[] = array(
                    self::ID => $error->getId(),
                    self::ERROR_TYPE => $error->getType() === LengowOrderError::TYPE_ERROR_IMPORT
                        ? self::TYPE_ERROR_IMPORT
                        : self::TYPE_ERROR_SEND,
                    self::ERROR_MESSAGE => LengowMain::decodeLogMessage(
                        $error->getMessage(),
                        LengowTranslation::DEFAULT_ISO_CODE
                    ),
                    self::ERROR_FINISHED => $error->isFinished(),
                    self::ERROR_REPORTED => $error->isMail(),
                    self::CREATED_AT => $error->getCreatedAt()->getTimestamp(),
                    self::UPDATED_AT => $error->getUpdatedAt() ? $error->getUpdatedAt()->getTimestamp() : 0,
                );
            }
        }
        return $orderErrors;
    }

    /**
     * Get array of all the actions of a Lengow order
     *
     * @param OrderModel $order Shopware order instance
     *
     * @return array
     */
    private static function getOrderActionData($order)
    {
        $orderActions = array();
        $actions = LengowAction::getActionByOrderId($order->getId());
        if ($actions) {
            foreach ($actions as $action) {
                $orderActions[] = array(
                    self::ID => $action->getId(),
                    self::ACTION_ID => $action->getActionId(),
                    self::ACTION_PARAMETERS => json_decode($action->getParameters(), true),
                    self::ACTION_RETRY => $action->getRetry(),
                    self::ACTION_FINISH => $action->getState() === LengowAction::STATE_FINISH,
                    self::CREATED_AT => $action->getCreatedAt()->getTimestamp(),
                    self::UPDATED_AT => $action->getUpdatedAt() ? $action->getUpdatedAt()->getTimestamp() : 0,
                );
            }
        }
        return $orderActions;
    }

    /**
     * Get array of all the statuses of an order
     *
     * @param OrderModel $order Shopware order instance
     *
     * @return array
     */
    private static function getOrderStatusesData($order)
    {
        $orderPayment = $order ? $order->getPaymentInstances()->first() : null;
        if ($order->getHistory()->isEmpty()) {
            return array(
                self::ORDER_MERCHANT_ORDER_STATUS => LengowMain::compareVersion('5.1')
                    ? $order->getOrderStatus()->getName()
                    : $order->getOrderStatus()->getDescription(),
                self::ORDER_STATUS => self::getOrderStatusCorrespondence($order->getOrderStatus()->getId()),
                self::CREATED_AT => $orderPayment ? $orderPayment->getCreatedAt()->getTimestamp() : 0,
            );
        }
        $orderStatuses = array();
        $firstOrderHistory = true;
        /** @var OrderHistoryModel $orderHistory */
        foreach ($order->getHistory() as $orderHistory) {
            if ($orderHistory->getOrderStatus() === null
                || ($firstOrderHistory && $orderHistory->getPreviousOrderStatus() === null)
            ) {
                continue;
            }
            if ($firstOrderHistory) {
                $orderStatuses[] = array(
                    self::ORDER_MERCHANT_ORDER_STATUS => LengowMain::compareVersion('5.1')
                        ? $orderHistory->getPreviousOrderStatus()->getName()
                        : $orderHistory->getPreviousOrderStatus()->getDescription(),
                    self::ORDER_STATUS => self::getOrderStatusCorrespondence(
                        $orderHistory->getPreviousOrderStatus()->getId()
                    ),
                    self::CREATED_AT => $orderPayment ? $orderPayment->getCreatedAt()->getTimestamp() : 0,
                );
            }
            $orderStatuses[] = array(
                self::ORDER_MERCHANT_ORDER_STATUS => LengowMain::compareVersion('5.1')
                    ? $orderHistory->getOrderStatus()->getName()
                    : $orderHistory->getOrderStatus()->getDescription(),
                self::ORDER_STATUS => self::getOrderStatusCorrespondence($orderHistory->getOrderStatus()->getId()),
                self::CREATED_AT => $orderHistory->getChangeDate()->getTimestamp(),
            );
            $firstOrderHistory = false;
        }
        return $orderStatuses;
    }

    /**
     * Get all the data of the order at the time of import
     *
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @return array
     */
    private static function getOrderExtraData($lengowOrder)
    {
        return json_decode($lengowOrder->getExtra(), true);
    }

    /**
     * Get order process label
     *
     * @param integer $orderProcess Lengow order process (new, import or finish)
     *
     * @return string
     */
    private static function getOrderProcessLabel($orderProcess)
    {
        switch ($orderProcess) {
            case LengowOrder::PROCESS_STATE_NEW:
                return self::PROCESS_STATE_NEW;
            case LengowOrder::PROCESS_STATE_IMPORT:
                return self::PROCESS_STATE_IMPORT;
            case LengowOrder::PROCESS_STATE_FINISH:
            default:
                return self::PROCESS_STATE_FINISH;
        }
    }

    /**
     * Retrieves the correspondence between the PrestaShop and Lengow status
     *
     * @param integer $idOrderState PrestaShop order state id
     *
     * @return string|null
     */
    private static function getOrderStatusCorrespondence($idOrderState)
    {
        $statusWaitingShipment = LengowMain::getOrderStatus(LengowOrder::STATE_WAITING_SHIPMENT);
        $statusShipped = LengowMain::getOrderStatus(LengowOrder::STATE_SHIPPED);
        $statusCanceled = LengowMain::getOrderStatus(LengowOrder::STATE_CANCELED);
        switch ($idOrderState) {
            case $statusWaitingShipment->getId():
                return LengowOrder::STATE_WAITING_SHIPMENT;
            case $statusShipped->getId():
                return LengowOrder::STATE_SHIPPED;
            case $statusCanceled->getId():
                return LengowOrder::STATE_CANCELED;
            default:
                return null;
        }
    }

    /**
     * Generates an error return for the Toolbox webservice
     *
     * @param integer $httpCode request http code
     * @param string $error error message
     *
     * @return array
     */
    private static function generateErrorReturn($httpCode, $error)
    {
        return array(
            self::ERRORS => array(
                self::ERROR_MESSAGE => LengowMain::decodeLogMessage($error, LengowTranslation::DEFAULT_ISO_CODE),
                self::ERROR_CODE => $httpCode,
            ),
        );
    }
}
