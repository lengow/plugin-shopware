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

use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowExport as LengowExport;
use Shopware_Plugins_Backend_Lengow_Components_LengowImport as LengowImport;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;

/**
 * Lengow Toolbox Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowToolbox
{
    /* Toolbox GET params */
    const PARAM_TOKEN = 'token';
    const PARAM_TOOLBOX_ACTION = 'toolbox_action';
    const PARAM_DATE = 'date';
    const PARAM_TYPE = 'type';

    /* Toolbox Actions */
    const ACTION_DATA = 'data';
    const ACTION_LOG = 'log';

    /* Data type */
    const DATA_TYPE_ALL = 'all';
    const DATA_TYPE_CHECKLIST = 'checklist';
    const DATA_TYPE_CHECKSUM = 'checksum';
    const DATA_TYPE_CMS = 'cms';
    const DATA_TYPE_LOG = 'log';
    const DATA_TYPE_PLUGIN = 'plugin';
    const DATA_TYPE_OPTION = 'option';
    const DATA_TYPE_SHOP = 'shop';
    const DATA_TYPE_SYNCHRONIZATION = 'synchronization';

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

    /* Toolbox files */
    const FILE_CHECKMD5 = 'checkmd5.csv';
    const FILE_TEST = 'test.txt';

    /**
     * @var array valid toolbox actions
     */
    public static $toolboxActions = array(
        self::ACTION_DATA,
        self::ACTION_LOG,
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
     * Check if PHP Curl is activated
     *
     * @return boolean
     */
    public static function isCurlActivated()
    {
        return function_exists('curl_version');
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
        return function_exists('simplexml_load_file');
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return boolean
     */
    private static function isJsonActivated()
    {
        return function_exists('json_decode');
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
            $file = fopen($filePath, 'w+');
            if (!$file) {
                return false;
            }
            unlink($filePath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
