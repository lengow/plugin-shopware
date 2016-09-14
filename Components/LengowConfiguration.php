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
     * @var $LENGOW_SETTINGS array Specific Lengow settings in s_lengow_settings table
     */
    public static $LENGOW_SETTINGS = array(
        'lengowImportInProgress',
        'lengowLastImportCron',
        'lengowLastImportManual',
        'lengowGlobalToken',
        'lengowShopToken',
        'lengowAccountStatusUpdate',
        'lengowAccountStatus',
        'lengowOrderStat',
        'lengowOrderStatUpdate',
        'lengowOptionCmsUpdate',
        'lengowLastExport'
    );

    /**
     * Get config from Shopware database
     *
     * @param string                    $configName Name of the setting to get
     * @param Shopware\Models\Shop\Shop $shop       The shop the setting belongs to
     *
     * @return mixed Config value
     */
    public static function getConfig($configName, $shop = null)
    {
        $value = null;
        // Force plugin to register custom models thanks to afterInit() method.
        // Avoid issue when synchronizing account
        Shopware()->Plugins()->Backend()->Lengow();
        // If Lengow setting
        if (in_array($configName, self::$LENGOW_SETTINGS)) {
            $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
            $criteria = array('name' => $configName);
            if ($shop != null) {
                $criteria['shopId'] = $shop->getId();
            }
            /** @var Shopware\CustomModels\Lengow\Settings $config */
            $config = $em->getRepository('Shopware\CustomModels\Lengow\Settings')->findOneBy($criteria);
            if ($config != null) {
                $value = $config->getValue();
            }
        } else {
            // If shop no shop, get default one
            if (!($shop instanceof \Shopware\Models\Shop\Shop)) {
                $shop = self::getDefaultShop();
            }
            $lengowConf = new Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration();
            $value = $lengowConf->get($configName, $shop->getId());
        }

        return $value;
    }

    /**
     * Set config new value in database
     *
     * @param string                    $configName Name of the setting to edit/add
     * @param mixed                     $value      Value to set for the setting
     * @param Shopware\Models\Shop\Shop $shop       The shop the setting has to be added
     */
    public static function setConfig($configName, $value, $shop = null)
    {
        // Force plugin to register custom models thanks to afterInit() method.
        // Avoid issue when synchronizing account
        Shopware()->Plugins()->Backend()->Lengow();
        // If Lengow global setting
        if (in_array($configName, self::$LENGOW_SETTINGS)) {
            $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
            $criteria = array('name' => $configName);
            if ($shop != null) {
                $criteria['shopId'] = $shop->getId();
            }
            /** @var Shopware\CustomModels\Lengow\Settings $config */
            $config = $em->getRepository('Shopware\CustomModels\Lengow\Settings')->findOneBy($criteria);
            // If null, create a new lengow config
            if ($config == null) {
                $config = new \Shopware\CustomModels\Lengow\Settings();
                $config->setName($configName)
                    ->setShop($shop)
                    ->setDateAdd(new DateTime());
            }
            $config->setValue($value)
                ->setDateUpd(new DateTime());
            $em->persist($config);
            $em->flush($config);
        } else {
            // If shop no shop, get default one
            if (!($shop instanceof \Shopware\Models\Shop\Shop)) {
                $shop = self::getDefaultShop();
            }
            $lengowConf = new Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration();
            $lengowConf->save($configName, $value, $shop->getId());
        }
    }

    /**
     * Get Shopware default shop
     *
     * @return Shopware\Models\Shop\Shop Default shop
     */
    public static function getDefaultShop()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        return $em->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('default' => 1));
    }

    /**
     * Get config from db
     * Shopware < 5.0.0 compatibility
     * > 5.0.0 : Use Shopware()->Plugins()->Backend()->Lengow()->get('config_writer')->get() instead
     *
     * @param string  $name   Config name
     * @param integer $shopId Shop id
     *
     * @return mixed Config value|null
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
     * @param string   $name   New config name
     * @param mixed    $value  Config value
     * @param null|int $shopId Shop concerned by this config
     */
    public function save($name, $value, $shopId = 1)
    {
        $query = $this->getConfigValueByNameQuery($name, $shopId);
        $result = $query->execute()->fetch(\PDO::FETCH_ASSOC);
        if (isset($result['valueId']) && $result['valueId']) {
            $this->update($value, $result['valueId']);
            return;
        }
        $this->insert($value, $shopId, $result['elementId']);
    }

    /**
     * Search element config by name
     *
     * @param string   $name   Config name to search
     * @param int|null $shopId Shop id
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getConfigValueByNameQuery($name, $shopId = 1)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $connection = $em->getConnection();
        $query = $connection->createQueryBuilder();
        $query->select(array(
            'element.id as elementId',
            'element.value',
            'elementValues.id as valueId',
            'elementValues.value as configured',
        ));
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
     * @param mixed   $value   New config value
     * @param integer $valueId Shopware\Models\Config\Value id
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function update($value, $valueId)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $option = $em->getReference('Shopware\Models\Config\Value', $valueId);
        $option->setValue($value);
        $em->persist($option);
        $em->flush($option);
    }

    /**
     * Insert new configuration in the db
     *
     * @param mixed   $value     Config value
     * @param integer $shopId    Shop id
     * @param integer $elementId Shopware\Models\Config\Element id
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function insert($value, $shopId, $elementId)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        /** @var Shopware\Models\Config\Element $element */
        $element = $em->getReference('Shopware\Models\Config\Element', $elementId);
        $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);
        $option = new Shopware\Models\Config\Value();
        $option->setElement($element)
            ->setShop($shop)
            ->setValue($value);
        $em->persist($option);
        $em->flush($option);
    }

    /**
     * Get all Lengow Keys for option synchronisation
     *
     * @return array
     */
    public static function getKeys()
    {
        static $keys = null;
        $keys = array(
            'LENGOW_ACCOUNT_ID'               => array('shop' => true),
            'LENGOW_ACCESS_TOKEN'             => array('shop' => true),
            'LENGOW_SECRET_TOKEN'             => array('shop' => true),
            'LENGOW_SHOP_ACTIVE'              => array('shop' => true),
            'LENGOW_SHOP_TOKEN'               => array('shop' => true),
            'LENGOW_EXPORT_SELECTION_ENABLED' => array('shop' => true),
            'LENGOW_EXPORT_VARIATION_ENABLED' => array('shop' => true),
            'LENGOW_LAST_EXPORT'              => array('shop' => true),
            'LENGOW_IMPORT_DAYS'              => array('global' => true),
            'LENGOW_IMPORT_PREPROD_ENABLED'   => array('global' => true),
            'LENGOW_IMPORT_SHIP_MP_ENABLED'   => array('global' => true),
            'LENGOW_IMPORT_IN_PROGRESS'       => array('global' => true),
            'LENGOW_LAST_IMPORT_CRON'         => array('global' => true),
            'LENGOW_LAST_IMPORT_MANUAL'       => array('global' => true),
            'LENGOW_GLOBAL_TOKEN'             => array('global' => true),
            'LENGOW_AUTHORIZED_IP'            => array('global' => true),
            'LENGOW_OPTION_CMS_UPDATE'        => array('global' => true)
        );
        return $keys;
    }

    /**
     * Get all values for Lengow settings (used for synchronisation)
     * @param $shop \Shopware\Models\Shop\Shop Shop to get config from
     * @return array List of settings for this shop
     */
    public static function getAllValues($shop = null)
    {
        $rows = array();
        $keys = self::getKeys();
        foreach ($keys as $key => $value) {
            $toCamelCase = self::camelCase(strtolower($key));
            if ($shop) {
                if (isset($value['shop']) && $value['shop']) {
                    $rows[$key] = self::getConfig($toCamelCase, $shop);
                }
            } else {
                if (isset($value['global']) && $value['global']) {
                    $rows[$key] = self::getConfig($toCamelCase);
                }
            }
        }
        return $rows;
    }

    /**
     * Transform from Snake_Case to camelCase
     *
     * @param string $str     String to convert
     * @param array  $noStrip
     *
     * @return string Converted camelCase string
     */
    public static function camelCase($str, array $noStrip = array())
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9'.implode("", $noStrip).']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);
        $str = lcfirst($str);
        return $str;
    }
}
