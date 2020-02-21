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

use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowConnector as LengowConnector;
use Shopware_Plugins_Backend_Lengow_Components_LengowException as LengowException;
use Shopware_Plugins_Backend_Lengow_Components_LengowExport as LengowExport;
use Shopware_Plugins_Backend_Lengow_Components_LengowFile as LengowFile;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace as LengowMarketplace;

/**
 * Lengow Sync Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowSync
{
    /**
     * @var string cms type
     */
    const CMS_TYPE = 'shopware';

    /**
     * @var string sync catalog action
     */
    const SYNC_CATALOG = 'catalog';

    /**
     * @var string sync cms option action
     */
    const SYNC_CMS_OPTION = 'cms_option';

    /**
     * @var string sync status account action
     */
    const SYNC_STATUS_ACCOUNT = 'status_account';

    /**
     * @var string sync marketplace action
     */
    const SYNC_MARKETPLACE = 'marketplace';

    /**
     * @var string sync order action
     */
    const SYNC_ORDER = 'order';

    /**
     * @var string sync action action
     */
    const SYNC_ACTION = 'action';

    /**
     * @var string sync plugin version action
     */
    const SYNC_PLUGIN_DATA = 'plugin';

    /**
     * @var array cache time for catalog, account status, cms options and marketplace synchronisation
     */
    protected static $cacheTimes = array(
        self::SYNC_CATALOG => 21600,
        self::SYNC_CMS_OPTION => 86400,
        self::SYNC_STATUS_ACCOUNT => 86400,
        self::SYNC_MARKETPLACE => 43200,
        self::SYNC_PLUGIN_DATA => 86400,
    );

    /**
     * @var array valid sync actions
     */
    public static $syncActions = array(
        self::SYNC_ORDER,
        self::SYNC_CMS_OPTION,
        self::SYNC_STATUS_ACCOUNT,
        self::SYNC_MARKETPLACE,
        self::SYNC_ACTION,
        self::SYNC_CATALOG,
        self::SYNC_PLUGIN_DATA,
    );

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public static function getSyncData()
    {
        $data = array(
            'domain_name' => $_SERVER['SERVER_NAME'],
            'token' => LengowMain::getToken(),
            'type' => self::CMS_TYPE,
            'version' => LengowMain::getShopwareVersion(),
            'plugin_version' => Shopware()->Plugins()->Backend()->Lengow()->getVersion(),
            'email' => LengowConfiguration::getConfig('mail'),
            'cron_url' => LengowMain::getImportUrl(),
            'return_url' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
            'shops' => array(),
        );
        $activeShops = LengowMain::getActiveShops();
        foreach ($activeShops as $shop) {
            $export = new LengowExport($shop, array());
            $data['shops'][$shop->getId()] = array(
                'token' => LengowMain::getToken($shop),
                'shop_name' => $shop->getName(),
                'domain_url' => LengowMain::getShopUrl($shop),
                'feed_url' => LengowMain::getExportUrl($shop),
                'total_product_number' => $export->getTotalProducts(),
                'exported_product_number' => $export->getExportedProducts(),
                'enabled' => LengowConfiguration::shopIsActive($shop),
            );
        }
        return $data;
    }

    /**
     * Set shop configuration key from Lengow
     *
     * @param array $params Lengow API credentials
     */
    public static function sync($params)
    {
        LengowConfiguration::setAccessIds(
            array(
                'lengowAccountId' => $params['account_id'],
                'lengowAccessToken' => $params['access_token'],
                'lengowSecretToken' => $params['secret_token'],
            )
        );
        if (isset($params['shops'])) {
            foreach ($params['shops'] as $shopToken => $shopCatalogIds) {
                $shop = LengowMain::getShopByToken($shopToken);
                if ($shop) {
                    LengowConfiguration::setCatalogIds($shopCatalogIds['catalog_ids'], $shop);
                    LengowConfiguration::setActiveShop($shop);
                }
            }
        }
        // save last update date for a specific settings (change synchronisation interval time)
        LengowConfiguration::setConfig('lengowLastSettingUpdate', time());
    }

    /**
     * Sync Lengow catalogs for order synchronisation
     *
     * @param boolean $force force cache Update
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public static function syncCatalog($force = false, $logOutput = false)
    {
        $settingUpdated = false;
        if (LengowConfiguration::isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = LengowConfiguration::getConfig('lengowCatalogUpdate');
            if ($updatedAt !== null && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_CATALOG]) {
                return false;
            }
        }
        $result = LengowConnector::queryApi(LengowConnector::GET, LengowConnector::API_CMS, array(), '', $logOutput);
        if (isset($result->cms)) {
            $cmsToken = LengowMain::getToken();
            foreach ($result->cms as $cms) {
                if ($cms->token === $cmsToken) {
                    foreach ($cms->shops as $cmsShop) {
                        $shop = LengowMain::getShopByToken($cmsShop->token);
                        if ($shop) {
                            $idsChange = LengowConfiguration::setCatalogIds($cmsShop->catalog_ids, $shop);
                            $shopChange = LengowConfiguration::setActiveShop($shop);
                            if (!$settingUpdated && ($idsChange || $shopChange)) {
                                $settingUpdated = true;
                            }
                        }
                    }
                    break;
                }
            }
        }
        // save last update date for a specific settings (change synchronisation interval time)
        if ($settingUpdated) {
            LengowConfiguration::setConfig('lengowLastSettingUpdate', time());
        }
        LengowConfiguration::setConfig('lengowCatalogUpdate', time());
        return true;
    }

    /**
     * Get options for all shops
     *
     * @return array
     */
    public static function getOptionData()
    {
        $data = array(
            'token' => LengowMain::getToken(),
            'version' => LengowMain::getShopwareVersion(),
            'plugin_version' => Shopware()->Plugins()->Backend()->Lengow()->getVersion(),
            'options' => LengowConfiguration::getAllValues(),
            'shops' => array(),
        );
        $activeShops = LengowMain::getActiveShops();
        foreach ($activeShops as $shop) {
            $export = new LengowExport($shop, array());
            $data['shops'][] = array(
                'token' => LengowMain::getToken($shop),
                'enabled' => LengowConfiguration::shopIsActive($shop),
                'total_product_number' => $export->getTotalProducts(),
                'exported_product_number' => $export->getExportedProducts(),
                'options' => LengowConfiguration::getAllValues($shop),
            );
        }
        return $data;
    }

    /**
     * Set CMS options
     *
     * @param boolean $force force cache Update
     * @param boolean $logOutput see log or no
     *
     * @return boolean
     */
    public static function setCmsOption($force = false, $logOutput = false)
    {
        if (LengowConfiguration::isNewMerchant()
            || (bool)LengowConfiguration::getConfig('lengowImportPreprodEnabled')
        ) {
            return false;
        }
        if (!$force) {
            $updatedAt = LengowConfiguration::getConfig('lengowOptionCmsUpdate');
            if ($updatedAt !== null && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_CMS_OPTION]) {
                return false;
            }
        }
        $options = json_encode(self::getOptionData());
        LengowConnector::queryApi(LengowConnector::PUT, LengowConnector::API_CMS, array(), $options, $logOutput);
        LengowConfiguration::setConfig('lengowOptionCmsUpdate', time());
        return true;
    }

    /**
     * Get Status Account
     *
     * @param boolean $force force cache Update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public static function getStatusAccount($force = false, $logOutput = false)
    {
        if (!$force) {
            $updatedAt = LengowConfiguration::getConfig('lengowAccountStatusUpdate');
            if ($updatedAt !== null && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_STATUS_ACCOUNT]) {
                $config = LengowConfiguration::getConfig('lengowAccountStatus');
                return json_decode($config, true);
            }
        }
        $result = LengowConnector::queryApi(LengowConnector::GET, LengowConnector::API_PLAN, array(), '', $logOutput);
        if (isset($result->isFreeTrial)) {
            $status = array(
                'type' => $result->isFreeTrial ? 'free_trial' : '',
                'day' => (int)$result->leftDaysBeforeExpired < 0 ? 0 : (int)$result->leftDaysBeforeExpired,
                'expired' => (bool)$result->isExpired,
            );
            LengowConfiguration::setConfig('lengowAccountStatus', json_encode($status));
            LengowConfiguration::setConfig('lengowAccountStatusUpdate', time());
            return $status;
        } else {
            if (LengowConfiguration::getConfig('lengowAccountStatusUpdate')) {
                return json_decode(LengowConfiguration::getConfig('lengowAccountStatus'), true);
            }
        }
        return false;
    }

    /**
     * Get marketplace data
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public static function getMarketplaces($force = false, $logOutput = false)
    {
        $filePath = LengowMarketplace::getFilePath();
        if (!$force) {
            $updatedAt = LengowConfiguration::getConfig('lengowMarketplaceUpdate');
            if ($updatedAt !== null
                && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_MARKETPLACE]
                && file_exists($filePath)
            ) {
                // recovering data with the marketplaces.json file
                $marketplacesData = file_get_contents($filePath);
                if ($marketplacesData) {
                    return json_decode($marketplacesData);
                }
            }
        }
        // recovering data with the API
        $result = LengowConnector::queryApi(
            LengowConnector::GET,
            LengowConnector::API_MARKETPLACE,
            array(),
            '',
            $logOutput
        );
        if ($result && is_object($result) && !isset($result->error)) {
            // updated marketplaces.json file
            try {
                $marketplaceFile = new LengowFile(
                    LengowMain::$lengowConfigFolder,
                    LengowMarketplace::$marketplaceJson,
                    'w+'
                );
                $marketplaceFile->write(json_encode($result));
                $marketplaceFile->close();
                LengowConfiguration::setConfig('lengowMarketplaceUpdate', time());
            } catch (LengowException $e) {
                $decodedMessage = LengowMain::decodeLogMessage($e->getMessage());
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage(
                        'log/import/marketplace_update_failed',
                        array('decoded_message' => $decodedMessage)
                    ),
                    $logOutput
                );
            }
            return $result;
        } else {
            // if the API does not respond, use marketplaces.json if it exists
            if (file_exists($filePath)) {
                $marketplacesData = file_get_contents($filePath);
                if ($marketplacesData) {
                    return json_decode($marketplacesData);
                }
            }
        }
        return false;
    }

    /**
     * Get Lengow plugin data (last version and download link)
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public static function getPluginData($force = false, $logOutput = false)
    {
        if (LengowConfiguration::isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = LengowConfiguration::getConfig('lengowPluginDataUpdate');
            if ($updatedAt !== null && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_PLUGIN_DATA]) {
                return json_decode(LengowConfiguration::getConfig('lengowPluginData'), true);
            }
        }
        $plugins = LengowConnector::queryApi(
            LengowConnector::GET,
            LengowConnector::API_PLUGIN,
            array(),
            '',
            $logOutput
        );
        if ($plugins) {
            $pluginData = false;
            foreach ($plugins as $plugin) {
                if ($plugin->type === self::CMS_TYPE) {
                    $pluginData = array(
                        'version' => $plugin->version,
                        'download_link' => $plugin->archive,
                    );
                    break;
                }
            }
            if ($pluginData) {
                LengowConfiguration::setConfig('lengowPluginData', json_encode($pluginData));
                LengowConfiguration::setConfig('lengowPluginDataUpdate', time());
                return $pluginData;
            }
        } else {
            if (LengowConfiguration::getConfig('lengowPluginData')) {
                return json_decode(LengowConfiguration::getConfig('lengowPluginData'), true);
            }
        }
        return false;
    }
}
