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
class Shopware_Plugins_Backend_Lengow_Components_LengowStatistic
{
    /**
     * Get Statistic with all shop every 5 hours
     */
    protected static $cacheTime = 18000;

    /**
     * Get Statistic with all shop
     *
     * @param boolean $force Force cache Update
     *
     * @return array
     */
    public static function get($force = false)
    {
        if (!$force) {
            $updatedAt = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowOrderStatUpdate');
            if ((time() - strtotime($updatedAt)) < self::$cacheTime) {
                $stats = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowOrderStat');
                return json_decode($stats);
            }
        }
        $return = array();
        $return['total_order'] = 0;
        $return['nb_order'] = 0;
        //get stats by shop
        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getActiveShops();
        $i = 0;
        $account_ids = array();
        foreach ($shops as $shop) {
            $account_id = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowAccountId',
                $shop);
            if (!$account_id || in_array($account_id, $account_ids) || empty($account_id)) {
                continue;
            }
            // TODO test call API for return statistics
            $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
                'get',
                '/v3.0/stats',
                $shop->getId(),
                array(
                    'date_from' => date('c', strtotime(date('Y-m-d').' -10 years')),
                    'date_to'   => date('c'),
                    'metrics'   => 'year',
                )
            );
            if (isset($result->level0)) {
                $return['total_order'] += $result->level0->revenue;
                $return['nb_order'] += $result->level0->transactions;
            }
            $account_ids[] = $account_id;
            $i++;
        }
        $return['total_order'] = number_format($return['total_order'], 2, ',', ' ');
        $return['nb_order'] = (int)$return['nb_order'];
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
            'lengowOrderStat',
            json_encode($return));
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
            'lengowOrderStatUpdate',
            date('Y-m-d H:i:s'));
        return $return;
    }
}