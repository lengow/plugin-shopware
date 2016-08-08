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
class Shopware_Plugins_Backend_Lengow_Bootstrap_Database
{
    /**
     * Add custom models used by Lengow in the database
     */
    public function createCustomModels()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $schemaTool = new Doctrine\ORM\Tools\SchemaTool($em);
        $models = array(
            's_lengow_order'    => $em->getClassMetadata('Shopware\CustomModels\Lengow\Order'),
            's_lengow_settings' => $em->getClassMetadata('Shopware\CustomModels\Lengow\Settings')
        );
        foreach ($models as $tableName => $model) {
            // Check that the table does not exist
            if (!$this->tableExist($tableName)) {
                $schemaTool->createSchema(array($model));
                Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                    'log/install/add_model',
                    array('name' => $model->getName())
                );
            } else {
                Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                    'log/install/model_already_exists',
                    array('name' => $model->getName())
                );
            }
        }
    }

    /**
     * Remove custom models from database
     */
    public function removeCustomModels()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $schemaTool = new Doctrine\ORM\Tools\SchemaTool($em);
        // List of models to remove when uninstalling the plugin
        $models = array(
            's_lengow_settings' => $em->getClassMetadata('Shopware\CustomModels\Lengow\Settings')
        );
        foreach ($models as $tableName => $model) {
            // Check that the table does not exist
            if ($this->tableExist($tableName)) {
                $schemaTool->dropSchema(array($model));
                Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                    'log/uninstall/remove_model',
                    array('name' => $model->getName())
                );
            }
        }
    }

    /**
     * Update Shopware models.
     * Add lengowActive attribute for each shop in Attributes model
     */
    public function updateSchema()
    {
        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShops();
        /** @var Shopware_Plugins_Backend_Lengow_Bootstrap $lengowBootstrap */
        $lengowBootstrap = Shopware()->Plugins()->Backend()->Lengow();
        $tableName = 's_articles_attributes';
        foreach ($shops as $shop) {
            $attributeName = 'shop'.$shop->getId().'_active';
            if (!$this->columnExists($tableName, 'lengow_'.$attributeName)) {
                $lengowBootstrap->Application()->Models()->addAttribute(
                    $tableName,
                    'lengow',
                    $attributeName,
                    'boolean'
                );
                $lengowBootstrap::log('log/install/add_column', array(
                    'column' => $attributeName,
                    'table' => $tableName
                ));
            }
        }
        $lengowBootstrap::getEntityManager()->generateAttributeModels(array('s_articles_attributes'));
    }

    /**
     * Create Lengow settings and add them in s_lengow_settings table
     */
    public function setLengowSettings()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $lengowSettings = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::$LENGOW_SETTINGS;
        $repository = $em->getRepository('Shopware\CustomModels\Lengow\Settings');
        $defaultShop = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getDefaultShop();
        foreach ($lengowSettings as $key) {
            $setting = $repository->findOneBy(array('name' => $key));
            // If the setting does not already exist, create it
            if ($setting == null) {
                $setting = new Shopware\CustomModels\Lengow\Settings;
                $setting->setName($key)
                    ->setShop($defaultShop)
                    ->setValue(0)
                    ->setDateAdd(new DateTime())
                    ->setDateUpd(new DateTime());
                $em->persist($setting);
                $em->flush($setting);
            }
        }
    }

    /**
     * Check if a database table exists
     *
     * @param string $tableName Table name
     *
     * @return bool True if table exists in db
     */
    protected function tableExist($tableName)
    {
        $sql = "SHOW TABLES LIKE '".$tableName."'";
        $result = Shopware()->Db()->fetchRow($sql);
        return !empty($result);
    }

    /**
     * @param $tableName
     * @param $columnName
     * @return mixed
     */
    protected function columnExists($tableName, $columnName)
    {
        $sql= "SHOW COLUMNS FROM ".$tableName." LIKE '".$columnName . "'";
        $result = Shopware()->Db()->fetchRow($sql);
        return !empty($result);
    }
}
