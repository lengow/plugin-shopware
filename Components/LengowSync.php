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
 * Lengow Sync Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowSync
{
    /**
     * @var array cache time for statistic, account status, cms options and marketplace synchronisation
     */
    protected static $cacheTimes = array(
        'cms_option' => 86400,
        'status_account' => 86400,
        'statistic' => 43200,
        'marketplace' => 21600,
    );

    /**
     * @var array valid sync actions
     */
    public static $syncActions = array(
        'order',
        'cms_option',
        'status_account',
        'statistic',
        'marketplace',
        'action',
        'catalog',
    );

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public static function getSyncData()
    {
        $data = array(
            'domain_name' => $_SERVER["SERVER_NAME"],
            'token' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken(),
            'type' => 'shopware',
            'version' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopwareVersion(),
            'plugin_version' => Shopware()->Plugins()->Backend()->Lengow()->getVersion(),
            'email' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('mail'),
            'cron_url' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getImportUrl(),
            'return_url' => 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"],
            'shops' => array()
        );
        $activeShops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getActiveShops();
        foreach ($activeShops as $shop) {
            $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, array());
            $enabled = (bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowShopActive',
                $shop
            );
            $data['shops'][$shop->getId()] = array(
                'token' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken($shop),
                'shop_name' =>  $shop->getName(),
                'domain_url' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopUrl($shop),
                'feed_url' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getExportUrl($shop),
                'total_product_number' => $export->getTotalProducts(),
                'exported_product_number' => $export->getExportedProducts(),
                'enabled' => $enabled
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
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setAccessIds(
            array(
                'lengowAccountId' => $params['account_id'],
                'lengowAccessToken' => $params['access_token'],
                'lengowSecretToken' => $params['secret_token']
            )
        );
        if (isset($params['shops'])) {
            foreach ($params['shops'] as $shopToken => $shopCatalogIds) {
                $shop = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopByToken($shopToken);
                if ($shop) {
                    Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setCatalogIds(
                        $shopCatalogIds['catalog_ids'],
                        $shop
                    );
                    Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setActiveShop($shop);
                }
            }
        }
    }

    /**
     * Sync Lengow catalogs for order synchronisation
     */
    public static function syncCatalog()
    {
        if (Shopware_Plugins_Backend_Lengow_Components_LengowConnector::isNewMerchant()) {
            return false;
        }
        $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi('get', '/v3.1/cms');
        if (isset($result->cms)) {
            $cmsToken = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken();
            foreach ($result->cms as $cms) {
                if ($cms->token === $cmsToken) {
                    foreach ($cms->shops as $cmsShop) {
                        $shop = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopByToken($cmsShop->token);
                        if ($shop) {
                            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setCatalogIds(
                                $cmsShop->catalog_ids,
                                $shop
                            );
                            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setActiveShop($shop);
                        }
                    }
                    break;
                }
            }

        }
    }

    /**
     * Get options for all shops
     *
     * @return array
     */
    public static function getOptionData()
    {
        $data = array(
            'token' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken(),
            'version' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopwareVersion(),
            'plugin_version' => Shopware()->Plugins()->Backend()->Lengow()->getVersion(),
            'options' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getAllValues(),
            'shops' => array()
        );
        $activeShops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getActiveShops();
        foreach ($activeShops as $shop) {
            $enabled = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowShopActive',
                $shop
            );
            $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, array());
            $data['shops'][] = array(
                'token' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken($shop),
                'enabled' => $enabled,
                'total_product_number' => $export->getTotalProducts(),
                'exported_product_number' => $export->getExportedProducts(),
                'options' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getAllValues($shop)
            );
        }
        return $data;
    }

    /**
     * Set CMS options
     *
     * @param boolean $force force cache Update
     *
     * @return boolean
     */
    public static function setCmsOption($force = false)
    {
        $preprodMode = (bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowImportPreprodEnabled'
        );
        if (Shopware_Plugins_Backend_Lengow_Components_LengowConnector::isNewMerchant() || $preprodMode) {
            return false;
        }
        if (!$force) {
            $updatedAt = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowOptionCmsUpdate'
            );
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < self::$cacheTimes['cms_option']) {
                return false;
            }
        }
        $options = json_encode(self::getOptionData());
        Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi('put', '/v3.1/cms', array(), $options);
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
            'lengowOptionCmsUpdate',
            date('Y-m-d H:i:s')
        );
        return true;
    }

    /**
     * Get Status Account
     *
     * @param boolean $force force cache Update
     *
     * @return array|false
     */
    public static function getStatusAccount($force = false)
    {
        if (!$force) {
            $updatedAt = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowAccountStatusUpdate'
            );
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < self::$cacheTimes['status_account']) {
                $config = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                    'lengowAccountStatus'
                );
                return json_decode($config, true);
            }
        }
        $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi('get', '/v3.0/plans');
        if (isset($result->isFreeTrial)) {
            $status = array();
            $status['type'] = $result->isFreeTrial ? 'free_trial' : '';
            $status['day'] = (int)$result->leftDaysBeforeExpired;
            $status['expired'] = (bool)$result->isExpired;
            if ($status['day'] < 0) {
                $status['day'] = 0;
            }
            if ($status) {
                Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                    'lengowAccountStatus',
                    json_encode($status)
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                    'lengowAccountStatusUpdate',
                    date('Y-m-d H:i:s')
                );
                return $status;
            }
        } else {
            $updatedAt = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowAccountStatusUpdate'
            );
            if ($updatedAt) {
                $config = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                    'lengowAccountStatus'
                );
                return json_decode($config, true);
            }
        }
        return false;
    }

    /**
     * Get Statistic
     *
     * @param boolean $force force cache Update
     *
     * @return array
     */
    public static function getStatistic($force = false)
    {
        if (!$force) {
            $updatedAt = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowOrderStatUpdate'
            );
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < self::$cacheTimes['statistic']) {
                $stats = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowOrderStat');
                return json_decode($stats, true);
            }
        }
        $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
            'get',
            '/v3.0/stats',
            array(
                'date_from' => date('c', strtotime(date('Y-m-d') . ' -10 years')),
                'date_to' => date('c'),
                'metrics' => 'year',
            )
        );
        if (isset($result->level0)) {
            $stats = $result->level0[0];
            $return = array(
                'total_order' => number_format($stats->revenue, 2, ',', ' '),
                'nb_order' => (int)$stats->transactions,
                'currency' => $result->currency->iso_a3,
                'available' => false
            );
        } else {
            $updatedAt = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowOrderStatUpdate'
            );
            if ($updatedAt) {
                return json_decode(
                    Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowOrderStat'),
                    true
                );
            } else {
                return array(
                    'total_order' => 0,
                    'nb_order' => 0,
                    'currency' => '',
                    'available' => false
                );
            }
        }
        if ($return['total_order'] > 0 || $return['nb_order'] > 0) {
            $return['available'] = true;
        }
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
            'lengowOrderStat',
            json_encode($return)
        );
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
            'lengowOrderStatUpdate',
            date('Y-m-d H:i:s')
        );
        return $return;
    }

    /**
     * Get marketplace data
     *
     * @param boolean $force force cache update
     *
     * @return array|false
     */
    public static function getMarketplaces($force = false)
    {
        $filePath = Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace::getFilePath();
        if (!$force) {
            $updatedAt = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowMarketplaceUpdate'
            );
            if (!is_null($updatedAt)
                && (time() - strtotime($updatedAt)) < self::$cacheTimes['marketplace']
                && file_exists($filePath)
            ) {
                // Recovering data with the marketplaces.json file
                $marketplacesData = file_get_contents($filePath);
                if ($marketplacesData) {
                    return json_decode($marketplacesData);
                }
            }
        }
        // Recovering data with the API
        $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi('get', '/v3.0/marketplaces');
        if ($result && is_object($result) && !isset($result->error)) {
            // Updated marketplaces.json file
            try {
                $marketplaceFile = new Shopware_Plugins_Backend_Lengow_Components_LengowFile(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::$lengowConfigFolder,
                    Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace::$marketplaceJson,
                    'w+'
                );
                $marketplaceFile->write(json_encode($result));
                $marketplaceFile->close();
                Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                    'lengowMarketplaceUpdate',
                    date('Y-m-d H:i:s')
                );
            } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
                $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    $e->getMessage(),
                    'en'
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/import/marketplace_update_failed',
                        array('decoded_message' => $decodedMessage)
                    )
                );
            }
            return $result;
        } else {
            // If the API does not respond, use marketplaces.json if it exists
            if (file_exists($filePath)) {
                $marketplacesData = file_get_contents($filePath);
                if ($marketplacesData) {
                    return json_decode($marketplacesData);
                }
            }
        }
        return false;
    }
}
