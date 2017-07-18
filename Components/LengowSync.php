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
     * @var integer cache time for statistic, account status and cms options
     */
    protected static $cacheTime = 18000;

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public static function getSyncData()
    {
        $data = array();
        $data['domain_name'] = $_SERVER["SERVER_NAME"];
        $data['token'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken();
        $data['type'] = 'shopware';
        $data['version'] = Shopware::VERSION;
        $data['plugin_version'] = Shopware()->Plugins()->Backend()->Lengow()->getVersion();
        $data['email'] = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('mail');
        $data['return_url'] = 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        $activeShops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getActiveShops();
        foreach ($activeShops as $shop) {
            $shopId = $shop->getId();
            $exportUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getExportUrl($shop);
            $importUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getImportUrl();
            $token = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken($shop);
            $domain = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopUrl($shop);
            $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, array());
            $data['shops'][$shopId]['token'] = $token;
            $data['shops'][$shopId]['name'] = $shop->getName();
            $data['shops'][$shopId]['domain'] = $domain;
            $data['shops'][$shopId]['feed_url'] = $exportUrl;
            $data['shops'][$shopId]['cron_url'] = $importUrl;
            $data['shops'][$shopId]['total_product_number'] = $export->getTotalProducts();
            $data['shops'][$shopId]['exported_product_number'] = $export->getExportedProducts();
            $data['shops'][$shopId]['configured'] = self::checkSyncShop($shop);
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
        foreach ($params as $shopToken => $values) {
            $shop = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopByToken($shopToken);
            if ($shop) {
                $listKey = array(
                    'account_id' => false,
                    'access_token' => false,
                    'secret_token' => false
                );
                foreach ($values as $k => $v) {
                    if (!in_array($k, array_keys($listKey))) {
                        continue;
                    }
                    if (strlen($v) > 0) {
                        $listKey[$k] = true;
                        $translationKey = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::camelCase(
                            'lengow_' . strtolower($k)
                        );
                        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                            $translationKey,
                            $v,
                            $shop
                        );
                    }
                }
                $findFalseValue = false;
                foreach ($listKey as $k => $v) {
                    if (!$v) {
                        $findFalseValue = true;
                        break;
                    }
                }
                if (!$findFalseValue) {
                    Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                        'lengowShopActive',
                        true,
                        $shop
                    );
                } else {
                    Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                        'lengowShopActive',
                        false,
                        $shop
                    );
                }
            }
        }
    }

    /**
     * Check that a shop is activated and has account id and tokens non-empty
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function checkSyncShop($shop)
    {
        return Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowShopActive', $shop)
            && Shopware_Plugins_Backend_Lengow_Components_LengowConnector::isValidAuth();
    }

    /**
     * Get options for all shops
     *
     * @return array
     */
    public static function getOptionData()
    {
        $data = array();
        $data['cms'] = array(
            'token' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken(),
            'type' => 'shopware',
            'version' => Shopware::VERSION,
            'plugin_version' => Shopware()->Plugins()->Backend()->Lengow()->getVersion(),
            'options' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getAllValues()
        );
        $activeShops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getActiveShops();
        foreach ($activeShops as $shop) {
            $lengowStatus = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowShopActive',
                $shop
            );
            $token = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken($shop);
            $exportUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getExportUrl($shop);
            $importUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getImportUrl();
            $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, array());
            $data['shops'][] = array(
                'enabled' => $lengowStatus,
                'token' => $token,
                'store_name' => $shop->getName(),
                'domain_url' => $shop->getHost() . $shop->getBaseUrl(),
                'feed_url' => $exportUrl,
                'cron_url' => $importUrl,
                'exported_product_number' => $export->getTotalProducts(),
                'total_product_number' => $export->getExportedProducts(),
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
        if (Shopware_Plugins_Backend_Lengow_Components_LengowConnector::isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowOptionCmsUpdate'
            );
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < self::$cacheTime) {
                return false;
            }
        }
        $options = json_encode(self::getOptionData());
        Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
            'put',
            '/v3.0/cms',
            array(),
            $options
        );
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
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < self::$cacheTime) {
                $config = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                    'lengowAccountStatus'
                );
                return json_decode($config, true);
            }
        }
        $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
            'get',
            '/v3.0/plans'
        );
        if (isset($result->isFreeTrial)) {
            $status = array();
            $status['type'] = $result->isFreeTrial ? 'free_trial' : '';
            $status['day'] = (int)$result->leftDaysBeforeExpired;
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
            if ((time() - strtotime($updatedAt)) < self::$cacheTime) {
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
}
