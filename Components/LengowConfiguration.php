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

 class Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration
 {
 	/**
 	 * Get config from Shopware database
 	 *
 	 * @param $configName string Name of the setting to get
 	 * @param $shop Shopware\Models\Shop\Shop The shop the setting belongs to
 	 * @return mixed Config value
 	 */
 	public static function getConfig($configName, $shop = null)
 	{
        // If shop no shop, get default one
        if ($shop == null) {
        	$shop = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getDefaultShop();
        }

        $configWriter = self::getConfigWriter();
        return $configWriter->get($configName, null, $shop->getId());
 	}

 	/**
 	 * Set config new value in database
 	 * @param $configName string Name of the setting to edit/add
 	 * @param $value mixed Value to set for the setting
 	 * @param $shop Shopware\Models\Shop\Shop The shop the setting has to be added
 	 */
 	public static function setConfig($configName, $value, $shop)
 	{
        $configWriter = self::getConfigWriter();
        $configWriter->save($configName, $value, null, $shop->getId());
 	}

 	/**
 	 * Get Shopware database config writer
 	 * @return Shopware\Components\ConfigWriter writer
 	 */
 	private static function getConfigWriter()
 	{
 		return Shopware()->Plugins()->Backend()->Lengow()->get('config_writer');
 	}
 }