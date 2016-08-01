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
            $host = $shop->getHost() ? $shop->getHost() : $_SERVER['SERVER_NAME'];
            $path = $shop->getBaseUrl() ? $shop->getBaseUrl() : '';
            $data['shops'][$shopId]['token'] = $token;
            $data['shops'][$shopId]['name'] = $shop->getName();
            $data['shops'][$shopId]['domain'] = $host . $path;
            $data['shops'][$shopId]['feed_url'] = $exportUrl;
            $data['shops'][$shopId]['cron_url'] = $importUrl;
            $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, array());
            $data['shops'][$shopId]['nb_product_total'] = $export->getTotalProducts();
            $data['shops'][$shopId]['nb_product_exported'] = $export->getExportedProducts();
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
                        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                            'LENGOW_'.strtoupper($k),
                            $v,
                            $shop);
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
                        $shop);
                } else {
                    Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                        'lengowShopActive',
                        false,
                        $shop);
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
//            'options'        => LengowConfiguration::getAllValues()
        );
        $activeShops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getActiveShops();
        foreach ($activeShops as $shop) {
            $lengowStatus = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowShopActive', $shop);
            $token = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getToken($shop);
            $exportUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getExportUrl($shop);
            $importUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getImportUrl($shop);
            $data['shops'][] = array(
                'enabled'    => $lengowStatus,
                'token'      => $token,
                'store_name' => $shop->getName(),
                'domain_url' => $shop->getHost() . $shop->getBaseUrl(),
                'feed_url'   => $exportUrl,
                'cron_url'   => $importUrl,
//                'options'    => LengowConfiguration::getAllValues($shop->id)
            );
        }
        return $data;
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
            $updated_at =  Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'LENGOW_ACCOUNT_STATUS_UPDATE');
            if (!is_null($updated_at) && (time() - strtotime($updated_at)) < self::$cacheTime) {
                $config = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('LENGOW_ACCOUNT_STATUS');
                return json_decode($config);
            }
        }
        // TODO call API for return a customer id or false
        //$result = LengowConnector::queryApi('get', '/v3.0/cms');
        $result = true;
        if ($result) {
            // TODO call API with customer id parameter for return status account
            //$status = LengowConnector::queryApi('get', '/v3.0/cms');
            $status = array();
            $status['type'] = 'free_trial';
            $status['day'] = 10;
            if ($status) {
                $jsonStatus = json_encode($status);
                $date = date('Y-m-d H:i:s');
                Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                    'LENGOW_ACCOUNT_STATUS',
                    $jsonStatus);
                Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                    'LENGOW_ACCOUNT_STATUS_UPDATE',
                    $date);
                return $status;
            }
        }
        return false;
    }
}