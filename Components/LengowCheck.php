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
class Shopware_Plugins_Backend_Lengow_Components_LengowCheck
{
    /**
     * @var $locale Shopware_Plugins_Backend_Lengow_Components_LengowTranslation Translation
     */
    protected $locale;

    public function __construct()
    {
        $this->locale = new Shopware_Plugins_Backend_Lengow_Components_LengowTranslation();
    }

    /**
    * Check API authentication
    *
    * @param $shop Shopware\Models\Shop\Shop
    *
    * @return boolean
    */
    public static function isValidAuth($shop)
    {
        if (!self::isCurlActivated()) {
            return false;
        }
        $account_id = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowAccountId',
            $shop
        );
        $connector  = new Shopware_Plugins_Backend_Lengow_Components_LengowConnector(
            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowAccessToken', $shop),
            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowSecretToken', $shop)
        );
        $result = $connector->connect();
        if (isset($result['token']) && $account_id != 0 && is_integer($account_id)) {
            return true;
        } else {
            return false;
        }
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
}
