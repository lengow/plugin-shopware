<?php
/**
 * Copyright 2016 Lengow SAS.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * @author    Team Connector <team-connector@lengow.com>
 * @copyright 2016 Lengow SAS
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

/**
 * Lengow Sync Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowSync
{
    /**
     * Get Account Status every 5 hours
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
            $data['shops'][$shopId]['token']                    = $token;
            $data['shops'][$shopId]['name']                     = $shop->getName();
            $data['shops'][$shopId]['domain']                   = $domain;
            $data['shops'][$shopId]['feed_url']                 = $exportUrl;
            $data['shops'][$shopId]['cron_url']                 = $importUrl;
            $data['shops'][$shopId]['total_product_number']     = $export->getTotalProducts();
            $data['shops'][$shopId]['exported_product_number']  = $export->getExportedProducts();
            $data['shops'][$shopId]['configured']               = self::checkSyncShop($shop);
        }
        return $data;
    }

    /**
     * Store Configuration Key From Lengow
     *
     * @param $params
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
                            'lengow_'.strtolower($k)
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
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public static function getOptionData()
    {
        $data = array();
        $data['cms'] = array(
            'token'          => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken(),
            'type'           => 'shopware',
            'version'        => Shopware::VERSION,
            'plugin_version' => Shopware()->Plugins()->Backend()->Lengow()->getVersion(),
            'options'        => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getAllValues()
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
                'enabled'                 => $lengowStatus,
                'token'                   => $token,
                'store_name'              => $shop->getName(),
                'domain_url'              => $shop->getHost() . $shop->getBaseUrl(),
                'feed_url'                => $exportUrl,
                'cron_url'                => $importUrl,
                'exported_product_number' => $export->getTotalProducts(),
                'total_product_number'    => $export->getExportedProducts(),
                'options' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getAllValues($shop)
                    
            );
        }
        return $data;
    }

    /**
     * Set CMS options
     *
     * @param boolean $force Force cache Update
     *
     * @return boolean
     */
    public static function setCmsOption($force = false)
    {
        if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt =  Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
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
            null,
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
     * Check that a shop is activated and has account id and tokens non-empty
     *
     * @param $shop Shopware\Models\Shop\Shop Shop to check
     *
     * @return bool true if the shop is ready to be sync
     */
    public static function checkSyncShop($shop)
    {
        return Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowShopActive', $shop)
            && Shopware_Plugins_Backend_Lengow_Components_LengowCheck::isValidAuth($shop);
    }

    /**
     * Get Status Account
     *
     * @param boolean $force Force cache Update
     *
     * @return mixed
     */
    public static function getStatusAccount($force = false)
    {
        if (!$force) {
            $updatedAt =  Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
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
            '/v3.0/subscriptions'
        );
        if (isset($result->subscription)) {
            $status = array();
            $status['type'] = $result->subscription->billing_offer->type;
            $status['day'] = - round((strtotime(date("c")) - strtotime($result->subscription->renewal)) / 86400);
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
        }
        return false;
    }

    /**
     * Get Statistic with all shop
     *
     * @param boolean $force Force cache Update
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
        $return = array();
        $return['total_order'] = 0;
        $return['nb_order'] = 0;
        $return['currency'] = '';
        $return['available'] = false;
        //get stats by shop
        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getActiveShops();
        $i = 0;
        $accountIds = array();
        foreach ($shops as $shop) {
            $accountId = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowAccountId',
                $shop
            );
            if (!$accountId || in_array($accountId, $accountIds) || empty($accountId)) {
                continue;
            }
            $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
                'get',
                '/v3.0/stats',
                $shop,
                array(
                    'date_from' => date('c', strtotime(date('Y-m-d').' -10 years')),
                    'date_to'   => date('c'),
                    'metrics'   => 'year',
                )
            );
            if (isset($result->level0)) {
                $stats = $result->level0[0];
                $return['total_order'] += $stats->revenue;
                $return['nb_order'] += $stats->transactions;
                $return['currency'] = $result->currency->iso_a3;
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
                        'nb_order'    => 0,
                        'currency'    => '',
                        'available'   => false
                    );
                }
            }
            $accountIds[] = $accountId;
            $i++;
        }
        if ($return['total_order'] > 0 || $return['nb_order'] > 0) {
            $return['available'] = true;
        }
        $return['total_order'] = number_format($return['total_order'], 2, ',', ' ');
        $return['nb_order'] = (int)$return['nb_order'];
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
