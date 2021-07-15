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
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

/**
 * Lengow Sync Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowSync
{
    /**
     * @var string cms type
     */
    const CMS_TYPE = 'shopware';

    /* Sync actions */
    const SYNC_CATALOG = 'catalog';
    const SYNC_CMS_OPTION = 'cms_option';
    const SYNC_STATUS_ACCOUNT = 'status_account';
    const SYNC_MARKETPLACE = 'marketplace';
    const SYNC_ORDER = 'order';
    const SYNC_ACTION = 'action';
    const SYNC_PLUGIN_DATA = 'plugin';

    /* Plugin link types */
    const LINK_TYPE_HELP_CENTER = 'help_center';
    const LINK_TYPE_CHANGELOG = 'changelog';
    const LINK_TYPE_UPDATE_GUIDE = 'update_guide';
    const LINK_TYPE_SUPPORT = 'support';

    /* Default plugin links */
    const LINK_HELP_CENTER = 'https://support.lengow.com/kb/guide/en/shopware-WPhak8Nc3U/Steps/25870';
    const LINK_CHANGELOG = 'https://support.lengow.com/kb/guide/en/shopware-WPhak8Nc3U/Steps/25870,113313,261688';
    const LINK_UPDATE_GUIDE = 'https://support.lengow.com/kb/guide/en/shopware-WPhak8Nc3U/Steps/25870,123274';
    const LINK_SUPPORT = 'https://help-support.lengow.com/hc/en-us/requests/new';

    /* Api iso codes */
    const API_ISO_CODE_EN = 'en';
    const API_ISO_CODE_FR = 'fr';
    const API_ISO_CODE_DE = 'de';

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
     * @var array iso code correspondence for plugin links
     */
    public static $genericIsoCodes = array(
        self::API_ISO_CODE_EN => LengowTranslation::ISO_CODE_EN,
        self::API_ISO_CODE_FR => LengowTranslation::ISO_CODE_FR,
        self::API_ISO_CODE_DE => LengowTranslation::ISO_CODE_DE,
    );

    /**
     * @var array default plugin links when the API is not available
     */
    public static $defaultPluginLinks = array(
        self::LINK_TYPE_HELP_CENTER => self::LINK_HELP_CENTER,
        self::LINK_TYPE_CHANGELOG => self::LINK_CHANGELOG,
        self::LINK_TYPE_UPDATE_GUIDE => self::LINK_UPDATE_GUIDE,
        self::LINK_TYPE_SUPPORT => self::LINK_SUPPORT,
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
            'cron_url' => LengowMain::getCronUrl(),
            'toolbox_url' => LengowMain::getToolboxUrl(),
            'shops' => array(),
        );
        $activeShops = LengowMain::getActiveShops();
        foreach ($activeShops as $shop) {
            $export = new LengowExport($shop);
            $data['shops'][] = array(
                'token' => LengowMain::getToken($shop),
                'shop_name' => $shop->getName(),
                'domain_url' => LengowMain::getShopUrl($shop),
                'feed_url' => LengowMain::getExportUrl($shop),
                'total_product_number' => $export->getTotalProduct(),
                'exported_product_number' => $export->getTotalExportProduct(),
                'enabled' => LengowConfiguration::shopIsActive($shop),
            );
        }
        return $data;
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
        $success = false;
        $settingUpdated = false;
        if (LengowConfiguration::isNewMerchant()) {
            return $success;
        }
        if (!$force) {
            $updatedAt = LengowConfiguration::getConfig(LengowConfiguration::LAST_UPDATE_CATALOG);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < self::$cacheTimes[self::SYNC_CATALOG]) {
                return $success;
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
                    $success = true;
                    break;
                }
            }
        }
        // save last update date for a specific settings (change synchronisation interval time)
        if ($settingUpdated) {
            LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_SETTING, time());
        }
        LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_CATALOG, time());
        return $success;
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
            $export = new LengowExport($shop);
            $data['shops'][] = array(
                'token' => LengowMain::getToken($shop),
                'enabled' => LengowConfiguration::shopIsActive($shop),
                'total_product_number' => $export->getTotalProduct(),
                'exported_product_number' => $export->getTotalExportProduct(),
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
        if (LengowConfiguration::isNewMerchant() || LengowConfiguration::debugModeIsActive()) {
            return false;
        }
        if (!$force) {
            $updatedAt = LengowConfiguration::getConfig(LengowConfiguration::LAST_UPDATE_OPTION_CMS);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < self::$cacheTimes[self::SYNC_CMS_OPTION]) {
                return false;
            }
        }
        $options = json_encode(self::getOptionData());
        LengowConnector::queryApi(LengowConnector::PUT, LengowConnector::API_CMS, array(), $options, $logOutput);
        LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_OPTION_CMS, time());
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
            $updatedAt = LengowConfiguration::getConfig(LengowConfiguration::LAST_UPDATE_ACCOUNT_STATUS_DATA);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < self::$cacheTimes[self::SYNC_STATUS_ACCOUNT]) {
                $config = LengowConfiguration::getConfig(LengowConfiguration::ACCOUNT_STATUS_DATA);
                return json_decode($config, true);
            }
        }
        $result = LengowConnector::queryApi(LengowConnector::GET, LengowConnector::API_PLAN, array(), '', $logOutput);
        if (isset($result->isFreeTrial)) {
            $status = array(
                'type' => $result->isFreeTrial ? 'free_trial' : '',
                'day' => (int) $result->leftDaysBeforeExpired < 0 ? 0 : (int) $result->leftDaysBeforeExpired,
                'expired' => (bool) $result->isExpired,
            );
            LengowConfiguration::setConfig(LengowConfiguration::ACCOUNT_STATUS_DATA, json_encode($status));
            LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_ACCOUNT_STATUS_DATA, time());
            return $status;
        }
        if (LengowConfiguration::getConfig(LengowConfiguration::ACCOUNT_STATUS_DATA)) {
            return json_decode(LengowConfiguration::getConfig(LengowConfiguration::ACCOUNT_STATUS_DATA), true);
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
            $updatedAt = LengowConfiguration::getConfig(LengowConfiguration::LAST_UPDATE_MARKETPLACE);
            if ($updatedAt !== null
                && (time() - (int) $updatedAt) < self::$cacheTimes[self::SYNC_MARKETPLACE]
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
                    LengowMain::FOLDER_CONFIG,
                    LengowMarketplace::FILE_MARKETPLACE,
                    'w+'
                );
                $marketplaceFile->write(json_encode($result));
                $marketplaceFile->close();
                LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_MARKETPLACE, time());
            } catch (LengowException $e) {
                $decodedMessage = LengowMain::decodeLogMessage($e->getMessage(), LengowTranslation::DEFAULT_ISO_CODE);
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
        if (!$force) {
            $updatedAt = LengowConfiguration::getConfig(LengowConfiguration::LAST_UPDATE_PLUGIN_DATA);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < self::$cacheTimes[self::SYNC_PLUGIN_DATA]) {
                return json_decode(LengowConfiguration::getConfig(LengowConfiguration::PLUGIN_DATA), true);
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
                    $cmsMinVersion = '';
                    $cmsMaxVersion = '';
                    $pluginLinks = array();
                    $currentVersion = $plugin->version;
                    if (!empty($plugin->versions)) {
                        foreach ($plugin->versions as $version) {
                            if ($version->version === $currentVersion) {
                                $cmsMinVersion = $version->cms_min_version;
                                $cmsMaxVersion = $version->cms_max_version;
                                break;
                            }
                        }
                    }
                    if (!empty($plugin->links)) {
                        foreach ($plugin->links as $link) {
                            if (array_key_exists($link->language->iso_a2, self::$genericIsoCodes)) {
                                $genericIsoCode = self::$genericIsoCodes[$link->language->iso_a2];
                                $pluginLinks[$genericIsoCode][$link->link_type] = $link->link;
                            }
                        }
                    }
                    $pluginData = array(
                        'version' => $currentVersion,
                        'download_link' => $plugin->archive,
                        'cms_min_version' => $cmsMinVersion,
                        'cms_max_version' => $cmsMaxVersion,
                        'links' => $pluginLinks,
                        'extensions' => $plugin->extensions,
                    );
                    break;
                }
            }
            if ($pluginData) {
                LengowConfiguration::setConfig(LengowConfiguration::PLUGIN_DATA, json_encode($pluginData));
                LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_PLUGIN_DATA, time());
                return $pluginData;
            }
        } else {
            if (LengowConfiguration::getConfig(LengowConfiguration::PLUGIN_DATA)) {
                return json_decode(LengowConfiguration::getConfig(LengowConfiguration::PLUGIN_DATA), true);
            }
        }
        return false;
    }

    /**
     * Get an array of plugin links for a specific iso code
     *
     * @param string|null $isoCode
     *
     * @return array
     */
    public static function getPluginLinks($isoCode = null)
    {
        $pluginData = self::getPluginData();
        if (!$pluginData) {
            return self::$defaultPluginLinks;
        }
        // check if the links are available in the locale
        $isoCode = $isoCode ?: LengowTranslation::DEFAULT_ISO_CODE;
        $localeLinks = isset($pluginData['links'][$isoCode]) ? $pluginData['links'][$isoCode] : false;
        $defaultLocaleLinks = isset($pluginData['links'][LengowTranslation::DEFAULT_ISO_CODE])
            ? $pluginData['links'][LengowTranslation::DEFAULT_ISO_CODE]
            : false;
        // for each type of link, we check if the link is translated
        $pluginLinks = array();
        foreach (self::$defaultPluginLinks as $linkType => $defaultLink) {
            if ($localeLinks && isset($localeLinks[$linkType])) {
                $pluginLinks[$linkType] = $localeLinks[$linkType];
            } elseif ($defaultLocaleLinks && isset($defaultLocaleLinks[$linkType])) {
                $pluginLinks[$linkType] = $defaultLocaleLinks[$linkType];
            } else {
                $pluginLinks[$linkType] = $defaultLink;
            }
        }
        return $pluginLinks;
    }
}
