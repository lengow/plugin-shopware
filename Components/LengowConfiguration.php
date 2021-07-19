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

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\ORMException;
use Shopware\Models\Config\Element as ConfigElementModel;
use Shopware\Models\Config\Value as ConfigValueModel;
use Shopware\Models\Shop\Shop as ShopModel;
use Shopware\CustomModels\Lengow\Settings as LengowSettingsModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;

/**
 * Lengow Configuration Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration
{
    /* Settings database key */
    const ACCOUNT_ID = 'lengowAccountId';
    const ACCESS_TOKEN = 'lengowAccessToken';
    const SECRET = 'lengowSecretToken';
    const CMS_TOKEN = 'lengowGlobalToken';
    const AUTHORIZED_IP_ENABLED = 'lengowIpEnabled';
    const AUTHORIZED_IPS = 'lengowAuthorizedIp';
    const TRACKING_ENABLED = 'lengowTrackingEnable';
    const TRACKING_ID = 'lengowTrackingId';
    const DEBUG_MODE_ENABLED = 'lengowImportDebugEnabled';
    const REPORT_MAIL_ENABLED = 'lengowImportReportMailEnabled';
    const REPORT_MAILS = 'lengowImportReportMailAddress';
    const AUTHORIZATION_TOKEN = 'lengowAuthorizationToken';
    const PLUGIN_DATA = 'lengowPluginData';
    const ACCOUNT_STATUS_DATA = 'lengowAccountStatus';
    const SHOP_TOKEN = 'lengowShopToken';
    const SHOP_ACTIVE = 'lengowShopActive';
    const CATALOG_IDS = 'lengowCatalogId';
    const SELECTION_ENABLED = 'lengowExportSelectionEnabled';
    const INACTIVE_ENABLED = 'lengowExportDisabledProduct';
    const DEFAULT_EXPORT_CARRIER_ID = 'lengowDefaultDispatcher';
    const WAITING_SHIPMENT_ORDER_ID = 'lengowIdWaitingShipment';
    const SHIPPED_ORDER_ID = 'lengowIdShipped';
    const CANCELED_ORDER_ID = 'lengowIdCanceled';
    const SHIPPED_BY_MARKETPLACE_ORDER_ID = 'lengowIdShippedByMp';
    const SYNCHRONIZATION_DAY_INTERVAL = 'lengowImportDays';
    const CURRENCY_CONVERSION_ENABLED = 'lengowCurrencyConversion';
    const B2B_WITHOUT_TAX_ENABLED = 'lengowImportB2b';
    const SHIPPED_BY_MARKETPLACE_ENABLED = 'lengowImportShipMpEnabled';
    const SHIPPED_BY_MARKETPLACE_STOCK_ENABLED = 'lengowImportStockMpEnabled';
    const SYNCHRONIZATION_IN_PROGRESS = 'lengowImportInProgress';
    const DEFAULT_IMPORT_CARRIER_ID = 'lengowImportDefaultDispatcher';
    const LAST_UPDATE_EXPORT = 'lengowLastExport';
    const LAST_UPDATE_CRON_SYNCHRONIZATION = 'lengowLastImportCron';
    const LAST_UPDATE_MANUAL_SYNCHRONIZATION = 'lengowLastImportManual';
    const LAST_UPDATE_ACTION_SYNCHRONIZATION = 'lengowLastActionSync';
    const LAST_UPDATE_CATALOG = 'lengowCatalogUpdate';
    const LAST_UPDATE_MARKETPLACE = 'lengowMarketplaceUpdate';
    const LAST_UPDATE_ACCOUNT_STATUS_DATA = 'lengowAccountStatusUpdate';
    const LAST_UPDATE_OPTION_CMS = 'lengowOptionCmsUpdate';
    const LAST_UPDATE_SETTING = 'lengowLastSettingUpdate';
    const LAST_UPDATE_PLUGIN_DATA = 'lengowPluginDataUpdate';
    const LAST_UPDATE_AUTHORIZATION_TOKEN = 'lengowLastAuthorizationTokenUpdate';
    const LAST_UPDATE_PLUGIN_MODAL = 'lengowLastPluginModalUpdate';

    /* Configuration parameters */
    const PARAM_EXPORT = 'export';
    const PARAM_EXPORT_TOOLBOX = 'export_toolbox';
    const PARAM_GLOBAL = 'global';
    const PARAM_LENGOW_SETTING = 'lengow_setting';
    const PARAM_RETURN = 'return';
    const PARAM_SECRET = 'secret';
    const PARAM_SHOP = 'shop';
    const PARAM_UPDATE = 'update';

    /* Configuration value return type */
    const RETURN_TYPE_BOOLEAN = 'boolean';
    const RETURN_TYPE_INTEGER = 'integer';
    const RETURN_TYPE_ARRAY = 'array';

    /**
     * @var array params correspondence keys for toolbox
     */
    public static $genericParamKeys = array(
        self::ACCOUNT_ID => 'account_id',
        self::ACCESS_TOKEN => 'access_token',
        self::SECRET => 'secret',
        self::CMS_TOKEN => 'cms_token',
        self::AUTHORIZED_IP_ENABLED => 'authorized_ip_enabled',
        self::AUTHORIZED_IPS => 'authorized_ips',
        self::TRACKING_ENABLED => 'tracking_enabled',
        self::TRACKING_ID => 'tracking_id',
        self::DEBUG_MODE_ENABLED => 'debug_mode_enabled',
        self::REPORT_MAIL_ENABLED => 'report_mail_enabled',
        self::REPORT_MAILS => 'report_mails',
        self::AUTHORIZATION_TOKEN => 'authorization_token',
        self::PLUGIN_DATA => 'plugin_data',
        self::ACCOUNT_STATUS_DATA => 'account_status_data',
        self::SHOP_TOKEN => 'shop_token',
        self::SHOP_ACTIVE => 'shop_active',
        self::CATALOG_IDS => 'catalog_ids',
        self::SELECTION_ENABLED => 'selection_enabled',
        self::INACTIVE_ENABLED => 'inactive_enabled',
        self::DEFAULT_EXPORT_CARRIER_ID => 'default_export_carrier_id',
        self::WAITING_SHIPMENT_ORDER_ID => 'waiting_shipment_order_id',
        self::SHIPPED_ORDER_ID => 'shipped_order_id',
        self::CANCELED_ORDER_ID => 'canceled_order_id',
        self::SHIPPED_BY_MARKETPLACE_ORDER_ID => 'shipped_by_marketplace_order_id',
        self::SYNCHRONIZATION_DAY_INTERVAL => 'synchronization_day_interval',
        self::DEFAULT_IMPORT_CARRIER_ID => 'default_import_carrier_id',
        self::CURRENCY_CONVERSION_ENABLED => 'currency_conversion_enabled',
        self::B2B_WITHOUT_TAX_ENABLED => 'b2b_without_tax_enabled',
        self::SHIPPED_BY_MARKETPLACE_ENABLED => 'shipped_by_marketplace_enabled',
        self::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED => 'shipped_by_marketplace_stock_enabled',
        self::SYNCHRONIZATION_IN_PROGRESS => 'synchronization_in_progress',
        self::LAST_UPDATE_EXPORT => 'last_update_export',
        self::LAST_UPDATE_CRON_SYNCHRONIZATION => 'last_update_cron_synchronization',
        self::LAST_UPDATE_MANUAL_SYNCHRONIZATION => 'last_update_manual_synchronization',
        self::LAST_UPDATE_ACTION_SYNCHRONIZATION => 'last_update_action_synchronization',
        self::LAST_UPDATE_CATALOG => 'last_update_catalog',
        self::LAST_UPDATE_MARKETPLACE => 'last_update_marketplace',
        self::LAST_UPDATE_ACCOUNT_STATUS_DATA => 'last_update_account_status_data',
        self::LAST_UPDATE_OPTION_CMS => 'last_update_option_cms',
        self::LAST_UPDATE_SETTING => 'last_update_setting',
        self::LAST_UPDATE_PLUGIN_DATA => 'last_update_plugin_data',
        self::LAST_UPDATE_AUTHORIZATION_TOKEN => 'last_update_authorization_token',
        self::LAST_UPDATE_PLUGIN_MODAL => 'last_update_plugin_modal',
    );

    /**
     * @var array $lengowSettings specific Lengow settings in s_lengow_settings table
     */
    public static $lengowSettings = array(
        self::ACCOUNT_ID => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT => false,
        ),
        self::ACCESS_TOKEN => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT => false,
            self::PARAM_SECRET => true,
        ),
        self::SECRET => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT => false,
            self::PARAM_SECRET => true,
        ),
        self::CMS_TOKEN => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT_TOOLBOX => false,
        ),
        self::AUTHORIZED_IP_ENABLED => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::AUTHORIZED_IPS => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ),
        self::TRACKING_ENABLED => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::TRACKING_ID => array(
            self::PARAM_GLOBAL => true,
        ),
        self::DEBUG_MODE_ENABLED => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::REPORT_MAIL_ENABLED => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::REPORT_MAILS => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ),
        self::AUTHORIZATION_TOKEN => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT => false,
        ),
        self::PLUGIN_DATA => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT => false,
        ),
        self::ACCOUNT_STATUS_DATA => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT => false,
        ),
        self::SHOP_TOKEN => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_SHOP => true,
            self::PARAM_EXPORT_TOOLBOX => false,
        ),
        self::SHOP_ACTIVE => array(
            self::PARAM_SHOP => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::CATALOG_IDS => array(
            self::PARAM_SHOP => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_UPDATE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ),
        self::SELECTION_ENABLED => array(
            self::PARAM_SHOP => true,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::INACTIVE_ENABLED => array(
            self::PARAM_SHOP => true,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::DEFAULT_EXPORT_CARRIER_ID => array(
            self::PARAM_SHOP => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::WAITING_SHIPMENT_ORDER_ID => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::SHIPPED_ORDER_ID => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::CANCELED_ORDER_ID => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::SHIPPED_BY_MARKETPLACE_ORDER_ID => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::SYNCHRONIZATION_DAY_INTERVAL => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_UPDATE => true,
        ),
        self::DEFAULT_IMPORT_CARRIER_ID => array(
            self::PARAM_SHOP => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::CURRENCY_CONVERSION_ENABLED => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::B2B_WITHOUT_TAX_ENABLED => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::SHIPPED_BY_MARKETPLACE_ENABLED  => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED => array(
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::SYNCHRONIZATION_IN_PROGRESS => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT => false,
        ),
        self::LAST_UPDATE_EXPORT => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_SHOP => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_CRON_SYNCHRONIZATION => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_MANUAL_SYNCHRONIZATION => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_ACTION_SYNCHRONIZATION => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_CATALOG => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_MARKETPLACE => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_ACCOUNT_STATUS_DATA => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_OPTION_CMS => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_SETTING => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_PLUGIN_DATA => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_AUTHORIZATION_TOKEN => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::LAST_UPDATE_PLUGIN_MODAL => array(
            self::PARAM_LENGOW_SETTING => true,
            self::PARAM_GLOBAL => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
    );

    /**
     * Get config from Shopware database
     *
     * @param string $configName name of the setting to get
     * @param ShopModel|integer|null $shop Shopware shop instance
     *
     * @return mixed
     */
    public static function getConfig($configName, $shop = null)
    {
        $value = null;
        // force plugin to register custom models thanks to afterInit() method
        // avoid issue when synchronizing account
        Shopware()->Plugins()->Backend()->Lengow();
        if (array_key_exists($configName, self::$lengowSettings)) {
            $setting = self::$lengowSettings[$configName];
            // if Lengow setting
            if (isset($setting[self::PARAM_LENGOW_SETTING]) && $setting[self::PARAM_LENGOW_SETTING]) {
                $em = LengowBootstrap::getEntityManager();
                $criteria = array('name' => $configName);
                if ($shop !== null) {
                    $criteria['shopId'] = is_int($shop) ? $shop : $shop->getId();
                }
                /** @var LengowSettingsModel $config */
                $config = $em->getRepository('Shopware\CustomModels\Lengow\Settings')->findOneBy($criteria);
                if ($config !== null) {
                    $value = $config->getValue();
                }
                return $value;
            }
        }
        // if shop no shop, get default one
        if ($shop === null) {
            $shop = self::getDefaultShop();
        }
        $shopId = is_int($shop) ? $shop : $shop->getId();
        return (new self())->get($configName, $shopId);
    }

    /**
     * Set config new value in database
     *
     * @param string $configName name of the setting to edit/add
     * @param mixed $value value to set for the setting
     * @param ShopModel|integer|null $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function setConfig($configName, $value, $shop = null)
    {
        // force plugin to register custom models thanks to afterInit() method
        // avoid issue when synchronizing account
        Shopware()->Plugins()->Backend()->Lengow();
        if (array_key_exists($configName, self::$lengowSettings)) {
            $setting = self::$lengowSettings[$configName];
            // if Lengow global setting
            if (isset($setting[self::PARAM_LENGOW_SETTING]) && $setting[self::PARAM_LENGOW_SETTING]) {
                $em = LengowBootstrap::getEntityManager();
                $criteria = array('name' => $configName);
                if ($shop !== null) {
                    if (is_int($shop)) {
                        $shop = $em->getRepository('Shopware\Models\Shop\Shop')->find($shop);
                    }
                    $criteria['shopId'] = $shop->getId();
                }
                try {
                    /** @var LengowSettingsModel $config */
                    $config = $em->getRepository('Shopware\CustomModels\Lengow\Settings')->findOneBy($criteria);
                    // if null, create a new lengow config
                    if ($config === null) {
                        $config = new LengowSettingsModel();
                        $config->setName($configName)
                            ->setShop($shop)
                            ->setDateAdd(new DateTime());
                    }
                    $config->setValue($value)
                        ->setDateUpd(new DateTime());
                    $em->persist($config);
                    $em->flush($config);
                } catch (Exception $e) {
                    return false;
                }
            } else {
                // if shop no shop, get default one
                if ($shop === null) {
                    $shop = self::getDefaultShop();
                }
                $shopId = is_int($shop) ? $shop : $shop->getId();
                return (new self())->save($configName, $value, $shopId);
            }
        }
        return true;
    }

    /**
     * Get Shopware default shop
     *
     * @return ShopModel
     */
    public static function getDefaultShop()
    {
        $em = LengowBootstrap::getEntityManager();
        return $em->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('default' => 1));
    }

    /**
     * Get Valid Account id / Access token / Secret
     *
     * @return array
     */
    public static function getAccessIds()
    {
        $accountId = (int) self::getConfig(self::ACCOUNT_ID);
        $accessToken = self::getConfig(self::ACCESS_TOKEN);
        $secret = self::getConfig(self::SECRET);
        if ($accountId !== 0 && $accessToken !== '0' && $secret !== '0') {
            return array($accountId, $accessToken, $secret);
        }
        return array(null, null, null);
    }

    /**
     * Set Valid Account id / Access token / Secret
     *
     * @param array $accessIds Account id / Access token / Secret
     *
     * @return boolean
     */
    public static function setAccessIds($accessIds)
    {
        $count = 0;
        $listKey = array(self::ACCOUNT_ID, self::ACCESS_TOKEN, self::SECRET);
        foreach ($accessIds as $key => $value) {
            if (!in_array($key, $listKey, true)) {
                continue;
            }
            if ($value !== '') {
                $count++;
                self::setConfig($key, $value);
            }
        }
        return $count === count($listKey);
    }

    /**
     * Reset access ids for old customer (Account id / Access token / Secret)
     */
    public static function resetAccessIds()
    {
        $accessIds = array(self::ACCOUNT_ID, self::ACCESS_TOKEN, self::SECRET);
        foreach ($accessIds as $accessId) {
            $value = self::getConfig($accessId);
            if ($value !== '') {
                self::setConfig($accessId, 0);
            }
        }
    }

    /**
     * Check if new merchant
     *
     * @return boolean
     */
    public static function isNewMerchant()
    {
        list($accountId, $accessToken, $secretToken) = self::getAccessIds();
        return !($accountId !== null && $accessToken !== null && $secretToken !== null);
    }

    /**
     * Get catalog ids for a specific shop
     *
     * @param ShopModel $shop Shopware shop instance
     *
     * @return array
     */
    public static function getCatalogIds($shop)
    {
        $catalogIds = array();
        $shopCatalogIds = self::getConfig(self::CATALOG_IDS, $shop);
        if (!empty($shopCatalogIds)) {
            $ids = trim(str_replace(array("\r\n", ',', '-', '|', ' ', '/'), ';', $shopCatalogIds), ';');
            $ids = array_filter(explode(';', $ids));
            foreach ($ids as $id) {
                if (is_numeric($id) && $id > 0) {
                    $catalogIds[] = (int) $id;
                }
            }
        }
        return $catalogIds;
    }

    /**
     * Set catalog ids for a specific shop
     *
     * @param array $catalogIds Lengow catalog ids
     * @param ShopModel $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function setCatalogIds($catalogIds, $shop)
    {
        $valueChange = false;
        $shopCatalogIds = self::getCatalogIds($shop);
        foreach ($catalogIds as $catalogId) {
            if ($catalogId > 0 && is_numeric($catalogId) && !in_array($catalogId, $shopCatalogIds, true)) {
                $shopCatalogIds[] = (int) $catalogId;
                $valueChange = true;
            }
        }
        self::setConfig(self::CATALOG_IDS, !empty($shopCatalogIds) ? implode(';', $shopCatalogIds) : 0, $shop);
        return $valueChange;
    }

    /**
     * Reset all catalog ids
     */
    public static function resetCatalogIds()
    {
        $shops = LengowMain::getActiveShops();
        foreach ($shops as $shop) {
            if (self::shopIsActive($shop)) {
                self::setConfig(self::CATALOG_IDS, 0, $shop);
                self::setConfig(self::SHOP_ACTIVE, false, $shop);
            }
        }
    }

    /**
     * Recovers if a shop is active or not
     *
     * @param ShopModel $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function shopIsActive($shop = null)
    {
        return (bool) self::getConfig(self::SHOP_ACTIVE, $shop);
    }

    /**
     * Set active shop or not
     *
     * @param ShopModel $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function setActiveShop($shop)
    {
        $shopIsActive = self::shopIsActive($shop);
        $catalogIds = self::getCatalogIds($shop);
        $shopHasCatalog = !empty($catalogIds);
        self::setConfig(self::SHOP_ACTIVE, $shopHasCatalog, $shop);
        return $shopIsActive !== $shopHasCatalog;
    }

    /**
     * Recovers if debug mode is active or not
     *
     * @return boolean
     */
    public static function debugModeIsActive()
    {
        return (bool) self::getConfig(self::DEBUG_MODE_ENABLED);
    }

    /**
     * Get all report mails
     *
     * @return array
     */
    public static function getReportEmailAddress()
    {
        $reportEmailAddress = array();
        $emails = self::getConfig(self::REPORT_MAILS);
        $emails = trim(str_replace(array("\r\n", ',', ' '), ';', $emails), ';');
        $emails = explode(';', $emails);
        foreach ($emails as $email) {
            if ($email !== '' && preg_match('/^\S+\@\S+\.\S+$/', $email)) {
                $reportEmailAddress[] = $email;
            }
        }
        if (empty($reportEmailAddress)) {
            $reportEmailAddress[] = self::getConfig('mail');
        }
        return $reportEmailAddress;
    }

    /**
     * Get authorized IPs
     *
     * @return array
     */
    public static function getAuthorizedIps()
    {
        $authorizedIps = array();
        $ips = self::getConfig(self::AUTHORIZED_IPS);
        if (!empty($ips)) {
            $authorizedIps = trim(str_replace(array("\r\n", ',', '-', '|', ' '), ';', $ips), ';');
            $authorizedIps = array_filter(explode(';', $authorizedIps));
        }
        return $authorizedIps;
    }

    /**
     * Get config from db
     * Shopware < 5.0.0 compatibility
     * > 5.0.0 : Use Shopware()->Plugins()->Backend()->Lengow()->get('config_writer')->get() instead
     *
     * @param string $name config name
     * @param integer $shopId Shopware shop id
     *
     * @return mixed
     */
    public function get($name, $shopId = 1)
    {
        $query = $this->getConfigValueByNameQuery($name, $shopId);
        $result = $query->execute()->fetch(\PDO::FETCH_ASSOC);
        if ($result['configured']) {
            return unserialize($result['configured']);
        }
        return unserialize($result['value']);
    }

    /**
     * Save new config in the db
     * Shopware < 5.0.0 compatibility
     * > 5.0.0 : Use Shopware()->Plugins()->Backend()->Lengow()->get('config_writer')->save() instead
     *
     * @param string $name new config name
     * @param mixed $value config value
     * @param integer $shopId Shopware shop id
     *
     * @return boolean
     */
    public function save($name, $value, $shopId = 1)
    {
        try {
            $query = $this->getConfigValueByNameQuery($name, $shopId);
            $result = $query->execute()->fetch(\PDO::FETCH_ASSOC);
            if (isset($result['valueId']) && $result['valueId']) {
                $this->update($value, $result['valueId']);
            } else {
                $this->insert($value, $shopId, $result['elementId']);
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Search element config by name
     *
     * @param string $name config name to search
     * @param integer $shopId Shopware shop id
     *
     * @return QueryBuilder
     */
    private function getConfigValueByNameQuery($name, $shopId = 1)
    {
        $em = LengowBootstrap::getEntityManager();
        $connection = $em->getConnection();
        $query = $connection->createQueryBuilder();
        $query->select(
            array(
                'element.id as elementId',
                'element.value',
                'elementValues.id as valueId',
                'elementValues.value as configured',
            )
        );
        $query->from('s_core_config_elements', 'element')
            ->leftJoin(
                'element',
                's_core_config_values',
                'elementValues',
                'elementValues.element_id = element.id AND elementValues.shop_id = :shopId'
            )
            ->where('element.name = :name')
            ->setParameter(':shopId', $shopId)
            ->setParameter(':name', $name);

        return $query;
    }

    /**
     * Update existing config
     *
     * @param mixed $value new config value
     * @param integer $valueId Shopware models config value id
     *
     * @throws ORMException
     */
    private function update($value, $valueId)
    {
        $em = LengowBootstrap::getEntityManager();
        /** @var ConfigValueModel $option */
        $option = $em->getReference('Shopware\Models\Config\Value', $valueId);
        $option->setValue($value);
        $em->persist($option);
        $em->flush($option);
    }

    /**
     * Insert new configuration in the db
     *
     * @param mixed $value Config value
     * @param integer $shopId Shopware shop id
     * @param integer $elementId Shopware models config element id
     *
     * @throws ORMException
     */
    private function insert($value, $shopId, $elementId)
    {
        $em = LengowBootstrap::getEntityManager();
        /** @var ConfigElementModel $element */
        $element = $em->getReference('Shopware\Models\Config\Element', $elementId);
        /** @var ShopModel $shop */
        $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);
        $option = new ConfigValueModel();
        $option->setElement($element)
            ->setShop($shop)
            ->setValue($value);
        $em->persist($option);
        $em->flush($option);
    }

    /**
     * Get Values by shop or global
     *
     * @param ShopModel|null $shop Shopware shop instance
     * @param boolean $toolbox get all values for toolbox or not
     *
     * @return array
     */
    public static function getAllValues($shop = null, $toolbox = false)
    {
        $rows = array();
        foreach (self::$lengowSettings as $key => $keyParams) {
            $value = null;
            if ((isset($keyParams[self::PARAM_EXPORT]) && !$keyParams[self::PARAM_EXPORT])
                || ($toolbox
                    && isset($keyParams[self::PARAM_EXPORT_TOOLBOX])
                    && !$keyParams[self::PARAM_EXPORT_TOOLBOX]
                )
            ) {
                continue;
            }
            if ($shop) {
                if (isset($keyParams[self::PARAM_SHOP]) && $keyParams[self::PARAM_SHOP]) {
                    $value = self::getConfig($key, $shop);
                    $rows[self::$genericParamKeys[$key]] = self::getValueWithCorrectType($key, $value);
                }
            } else if (isset($keyParams[self::PARAM_GLOBAL]) && $keyParams[self::PARAM_GLOBAL]) {
                $value = self::getConfig($key);
                $rows[self::$genericParamKeys[$key]] = self::getValueWithCorrectType($key, $value);
            }
        }
        return $rows;
    }

    /**
     * Check value and create a log if necessary
     *
     * @param string $key name of Lengow setting
     * @param mixed $value setting value
     * @param ShopModel|integer|null $shop Shopware shop instance
     */
    public static function checkAndLog($key, $value, $shop = null)
    {
        if (array_key_exists($key, self::$lengowSettings)) {
            $setting = self::$lengowSettings[$key];
            $oldValue = self::getConfig($key, $shop);
            if ($oldValue != $value) {
                if (isset($setting[self::PARAM_SECRET]) && $setting[self::PARAM_SECRET]) {
                    $value = preg_replace("/[a-zA-Z0-9]/", '*', $value);
                    $oldValue = preg_replace("/[a-zA-Z0-9]/", '*', $oldValue);
                } elseif (isset($setting[self::PARAM_RETURN])
                    && $setting[self::PARAM_RETURN] === self::RETURN_TYPE_BOOLEAN
                ) {
                    $value = (int) $value;
                    $oldValue = (int) $oldValue;
                }
                if ($shop !== null) {
                    LengowMain::log(
                        LengowLog::CODE_SETTING,
                        LengowMain::setLogMessage(
                            'log/setting/setting_change_for_shop',
                            array(
                                'key' => self::$genericParamKeys[$key],
                                'old_value' => $oldValue,
                                'value' => $value,
                                'shop_id' => is_integer($shop) ? $shop : $shop->getId(),
                            )
                        )
                    );
                } else {
                    LengowMain::log(
                        LengowLog::CODE_SETTING,
                        LengowMain::setLogMessage(
                            'log/setting/setting_change',
                            array(
                                'key' => self::$genericParamKeys[$key],
                                'old_value' => $oldValue,
                                'value' => $value,
                            )
                        )
                    );
                }
                // save last update date for a specific settings (change synchronisation interval time)
                if (isset($setting[self::PARAM_UPDATE]) && $setting[self::PARAM_UPDATE]) {
                    self::setConfig(self::LAST_UPDATE_SETTING, time());
                }
            }
        }
    }

    /**
     * Get configuration value in correct type
     *
     * @param string $key Lengow configuration key
     * @param string|null $value configuration value for conversion
     *
     * @return array|boolean|integer|string|string[]|null
     */
    private static function getValueWithCorrectType($key, $value = null)
    {
        $keyParams = self::$lengowSettings[$key];
        if (isset($keyParams[self::PARAM_RETURN])) {
            switch ($keyParams[self::PARAM_RETURN]) {
                case self::RETURN_TYPE_BOOLEAN:
                    return (bool) $value;
                case self::RETURN_TYPE_INTEGER:
                    return (int) $value;
                case self::RETURN_TYPE_ARRAY:
                    return !empty($value)
                        ? explode(';', trim(str_replace(array("\r\n", ',', ' '), ';', $value), ';'))
                        : array();
            }
        }
        return $value;
    }
}
