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
            $importUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getImportUrl($shop);
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
        foreach ($params as $shop_token => $values) {
            $shop = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopByToken($shop_token);
            if ($shop) {
                $list_key = array(
                    'account_id' => false,
                    'access_token' => false,
                    'secret_token' => false
                );
                foreach ($values as $k => $v) {
                    if (!in_array($k, array_keys($list_key))) {
                        continue;
                    }
                    if (strlen($v) > 0) {
                        $list_key[$k] = true;
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
                foreach ($list_key as $k => $v) {
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
            $importUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getImportUrl($shop);
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
            $updated_at =  Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowOptionCmsUpdate'
            );
            if (!is_null($updated_at) && (time() - strtotime($updated_at)) < self::$cacheTime) {
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
    public static function getStatusAccount($force = true)
    {
        $account_id = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowAccountId',
            1
        );
        if (!$force) {
            $updated_at =  Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowAccountStatusUpdate'
            );
            if (!is_null($updated_at) && (time() - strtotime($updated_at)) < self::$cacheTime) {
                $config = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                    'lengowAccountStatus'
                );
                return json_decode($config, true);
            }
        }

        $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi('get', '/api/v3.0/subscriptions?account_id='.$account_id);
        if ($result) {
            $status = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi('get', '/api/v3.0/subscriptions?account_id='.$account_id);

            $statussub['type'] = $status->subscription->billing_offer->type;
            $statussub['day'] = -round((strtotime(date("c")) - strtotime($status->subscription->renewal))/86400);
            if ($statussub) {
                $jsonStatus = json_encode($statussub);
                $date = date('Y-m-d H:i:s');
                Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                    'lengowAccountStatus',
                    $jsonStatus
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                    'lengowAccountStatusUpdate',
                    $date
                );
                return $statussub;
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
        //get stats by shop
        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getActiveShops();
        $i = 0;
        $account_ids = array();
        foreach ($shops as $shop) {
            $account_id = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowAccountId',
                $shop
            );
            if (!$account_id || in_array($account_id, $account_ids) || empty($account_id)) {
                continue;
            }
            // TODO test call API for return statistics
            $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
                'get',
                '/api/v3.0/stats',
                $shop,
                array(
                    'date_from' => date('c', strtotime(date('Y-m-d').' -10 years')),
                    'date_to'   => date('c'),
                    'metrics'   => 'year',
                    'account_id' => $account_id
                )
            );
            if (isset($result->level0)) {
                $return['total_order'] += $result->level0->revenue;
                $return['nb_order'] += $result->level0->transactions;
                $return['currency'] = $result->currency->iso_a3;
            }
            $account_ids[] = $account_id;
            $i++;
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
