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
 * @subpackage  Bootstrap
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Database Class
 */
class Shopware_Plugins_Backend_Lengow_Bootstrap_Database
{
    /**
     * @var \Shopware\Components\Model\ModelManager Shopware entity manager
     */
    protected $entityManager;

    /**
     * @var \Doctrine\ORM\Tools\SchemaTool Doctrine Schema Tool
     */
    protected $schemaTool;

    /**
     * @var array custom models used by Lengow in the database
     */
    protected $customModels = array(
        's_lengow_order' => array(
            'entity' => 'Shopware\CustomModels\Lengow\Order',
            'remove' => false,
        ),
        's_lengow_order_error' => array(
            'entity' => 'Shopware\CustomModels\Lengow\OrderError',
            'remove' => false,
        ),
        's_lengow_order_line' => array(
            'entity' => 'Shopware\CustomModels\Lengow\OrderLine',
            'remove' => false,
        ),
        's_lengow_action' => array(
            'entity' => 'Shopware\CustomModels\Lengow\Action',
            'remove' => false,
        ),
        's_lengow_settings' => array(
            'entity' => 'Shopware\CustomModels\Lengow\Settings',
            'remove' => true,
        ),
    );

    /**
     * @var boolean installation status
     */
    protected static $installationStatus;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->entityManager = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $this->schemaTool = new Doctrine\ORM\Tools\SchemaTool($this->entityManager);
    }

    /**
     * Get custom models used by Lengow in the database
     *
     * @param boolean $remove get only models to remove
     *
     * @return array
     */
    public function getCustomModels($remove = false)
    {
        $customModels = array();
        foreach ($this->customModels as $table => $model) {
            if ($remove) {
                if ($model['remove']) {
                    $customModels[$table] = $this->entityManager->getClassMetadata($model['entity']);
                }
            } else {
                $customModels[$table] = $this->entityManager->getClassMetadata($model['entity']);
            }
        }

        return $customModels;
    }

    /**
     * Add custom models used by Lengow in the database
     */
    public function createCustomModels()
    {
        $allModels = $this->getCustomModels();
        foreach ($allModels as $tableName => $model) {
            // check that the table does not exist
            if (!self::tableExist($tableName)) {
                try {
                    $this->schemaTool->createSchema(array($model));
                    Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                        'log/install/add_model',
                        array('name' => $model->getName())
                    );
                } catch (Exception $e) {
                    Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                        'log/install/add_model_error',
                        array('name' => $model->getName())
                    );
                }
            } else {
                Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                    'log/install/model_already_exists',
                    array('name' => $model->getName())
                );
            }
        }
    }

    /**
     * Update custom models used by Lengow in the database
     */
    public function updateCustomModels()
    {
        self::setInstallationStatus(true);
        $pluginPath = Shopware()->Plugins()->Backend()->Lengow()->Path();
        $upgradeFiles = array_diff(scandir($pluginPath . 'Upgrade'), array('..', '.'));
        foreach ($upgradeFiles as $file) {
            include $pluginPath . 'Upgrade/' . $file;
            $numberVersion = preg_replace('/update_|\.php$/', '', $file);
            Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                'log/install/add_upgrade_version',
                array('version' => $numberVersion)
            );
        }
        self::setInstallationStatus(false);
    }

    /**
     * Update Shopware models
     * Add lengowActive attribute for each shop in Attributes model
     * Add isFromLengow attribute for any shop in Attributes model
     *
     * @return boolean
     */
    public function updateSchema()
    {
        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShops();
        $shopIds = array();
        foreach ($shops as $shop) {
            $shopIds[] = $shop->getId();
        }
        try {
            $this->addLengowColumns($shopIds);
            $this->addFromLengowColumns();
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Remove custom models from database
     */
    public function removeCustomModels()
    {
        // list of models to remove when uninstalling the plugin
        $removeModels = $this->getCustomModels(true);
        foreach ($removeModels as $tableName => $model) {
            // check that the table does not exist
            if (self::tableExist($tableName)) {
                $this->schemaTool->dropSchema(array($model));
                Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                    'log/uninstall/remove_model',
                    array('name' => $model->getName())
                );
            }
        }
    }

    /**
     * Delete all Lengow columns (lengowShopXActive) from table s_articles_attributes
     *
     * @return boolean
     */
    public function removeAllLengowColumns()
    {
        $shopIds = array();
        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShops();
        foreach ($shops as $shop) {
            $shopIds[] = $shop->getId();
        }
        try {
            $this->removeLengowColumn($shopIds);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Delete list of columns from s_articles_attributes table
     *
     * @throws Exception
     *
     * @param $shopIds array list of shop ids
     */
    public function removeLengowColumn($shopIds)
    {
        $tableName = 's_articles_attributes';
        // foreach article attributes, remove lengow columns
        foreach ($shopIds as $shopId) {
            $attributeName = 'shop' . $shopId . '_active';
            if (self::columnExists($tableName, 'lengow_' . $attributeName)) {
                // check Shopware\Bundle\AttributeBundle\Service\CrudService::delete compatibility
                if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.2.2')) {
                    $crudService = Shopware()->Container()->get('shopware_attribute.crud_service');
                    $crudService->delete($tableName, 'lengow_' . $attributeName);
                } else {
                    $this->entityManager->removeAttribute($tableName, 'lengow', $attributeName);
                }
                Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                    'log/uninstall/remove_column',
                    array('column' => $attributeName, 'table' => $tableName)
                );
            } else {
                Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                    'log/uninstall/column_not_exists',
                    array('column_name' => $attributeName, 'table_name' => $tableName)
                );
            }
        }
        $this->entityManager->generateAttributeModels(array($tableName));
    }

    /**
     * Add a new column to the s_articles_attributes table (if does not exist)
     *
     * @throws Exception
     *
     * @param $shopIds array list of shops to add
     */
    public function addLengowColumns($shopIds)
    {
        // check Shopware\Bundle\AttributeBundle\Service\CrudService::update compatibility
        $crudCompatibility = Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.2.2');
        $tableName = 's_articles_attributes';
        foreach ($shopIds as $shopId) {
            $attributeName = 'shop' . $shopId . '_active';
            if (!self::columnExists($tableName, 'lengow_' . $attributeName)) {
                if ($crudCompatibility) {
                    $crudService = Shopware()->Container()->get('shopware_attribute.crud_service');
                    $crudService->update($tableName, 'lengow_' . $attributeName, 'boolean');
                } else {
                    // @legacy Shopware < 5.2
                    $this->entityManager->addAttribute($tableName, 'lengow', $attributeName, 'boolean');
                }
                Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                    'log/install/add_column',
                    array('column' => $attributeName, 'table' => $tableName)
                );
            }
        }
        $this->entityManager->generateAttributeModels(array($tableName));
    }

    /**
     * Add a new column to the s_order_attributes table (if does not exist)
     *
     * @throws Exception
     */
    public function addFromLengowColumns()
    {
        // check Shopware\Bundle\AttributeBundle\Service\CrudService::update compatibility
        $crudCompatibility = Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.2.2');
        $tableName = 's_order_attributes';
        $attributeName = 'is_from_lengow';
        if (!self::columnExists($tableName, $attributeName)) {
            if ($crudCompatibility) {
                $crudService = Shopware()->Container()->get('shopware_attribute.crud_service');
                $crudService->update($tableName, 'lengow_'.$attributeName, 'boolean');
            } else {
                // @legacy Shopware < 5.2
                $this->entityManager->addAttribute($tableName, 'lengow', $attributeName, 'boolean');
            }
            Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                'log/install/add_column',
                array('column' => $attributeName, 'table' => $tableName)
            );
        }
        $this->entityManager->generateAttributeModels(array($tableName));
    }

    /**
     * Create Lengow settings and add them in s_lengow_settings table
     *
     * @return boolean
     */
    public function setLengowSettings()
    {
        $lengowSettings = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::$lengowSettings;
        $repository = $this->entityManager->getRepository('Shopware\CustomModels\Lengow\Settings');
        $defaultShop = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getDefaultShop();
        try {
            foreach ($lengowSettings as $key => $lengowSetting) {
                if (isset($lengowSetting['lengow_settings']) && $lengowSetting['lengow_settings']) {
                    $setting = $repository->findOneBy(array('name' => $key));
                    // if the setting does not already exist, create it
                    if ($setting === null) {
                        $setting = new Shopware\CustomModels\Lengow\Settings;
                        $setting->setName($key)
                            ->setShop($defaultShop)
                            ->setValue(0)
                            ->setDateAdd(new DateTime())
                            ->setDateUpd(new DateTime());
                        $this->entityManager->persist($setting);
                        $this->entityManager->flush($setting);
                    }
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Check and update order attribute
     *
     * @return boolean
     */
    public function updateOrderAttribute()
    {
        if (self::tableExist('s_lengow_order') && self::columnExists('s_lengow_order', 'order_id')) {
            $sql = 'SELECT oa.id FROM s_order_attributes oa
                LEFT JOIN s_lengow_order lo ON lo.order_id = oa.orderID
                WHERE lo.order_id IS NOT NULL AND oa.lengow_is_from_lengow IS NULL';
            $results = Shopware()->Db()->fetchAll($sql);
            try {
                if (count($results) > 0) {
                    foreach ($results as $result) {
                        Shopware()->Db()->exec(
                            'UPDATE s_order_attributes SET lengow_is_from_lengow = 1 WHERE id = ' . $result['id']
                        );
                    }
                }
            } catch (Exception $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add Lengow technical error status
     *
     * @return boolean
     */
    public function addLengowTechnicalErrorStatus()
    {
        $lengowTechnicalError = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowTechnicalErrorStatus();
        if (self::tableExist('s_core_states') && is_null($lengowTechnicalError)) {
            try {
                // get id max for new order status - id is not auto-increment
                $idMax = (int)Shopware()->Db()->fetchOne('SELECT MAX(id) FROM `s_core_states`');
                // position max for new order status - exclude cancelled order status
                $positionMax = (int)Shopware()->Db()->fetchOne(
                    'SELECT MAX(position) FROM `s_core_states`
                    WHERE `group` = \'state\' AND `description` != \'Abgebrochen\''
                );
                $params = array(
                    'id' => $idMax + 1,
                    'description' => 'Technischer Fehler - Lengow',
                    'position' => $positionMax === 24 ? 26 : $positionMax + 1,
                    'group' => 'state',
                    'mail' => 0,
                );
                // compatibility with 4.3 version - the name field did not exist
                if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.1.0')) {
                    $sql = 'INSERT INTO `s_core_states` (`id`, `name`, `description`, `position`, `group`, `mail`)
                        VALUES (:id, :name , :description, :position, :group, :mail)';
                    $params['name'] = 'lengow_technical_error';
                } else {
                    $sql = 'INSERT INTO `s_core_states` (`id`, `description`, `position`, `group`, `mail`)
                        VALUES (:id, :description, :position, :group, :mail)';
                }
                // insert lengow technical error status in database
                Shopware()->Db()->query($sql, $params);
                Shopware_Plugins_Backend_Lengow_Bootstrap::log('log/install/add_technical_error_status');
            } catch (Exception $e) {
                $errorMessage = '[Shopware error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
                Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                    'log/install/add_technical_error_status_error',
                    array('error_message' => $errorMessage)
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Set Installation Status
     *
     * @param boolean $status installation status
     */
    public static function setInstallationStatus($status)
    {
        self::$installationStatus = $status;
    }

    /**
     * Is Installation in progress
     *
     * @return boolean
     */
    public static function isInstallationInProgress()
    {
        return self::$installationStatus;
    }

    /**
     * Check if a database table exists
     *
     * @param string $tableName Lengow table name
     *
     * @return boolean
     */
    public static function tableExist($tableName)
    {
        $sql = 'SHOW TABLES LIKE \'' . $tableName . '\'';
        $result = Shopware()->Db()->fetchRow($sql);
        return !empty($result);
    }

    /**
     * Check if a column exists
     *
     * @param string $tableName Lengow table name
     * @param string $columnName Lengow column name
     *
     * @return boolean
     */
    public static function columnExists($tableName, $columnName)
    {
        $sql = 'DESCRIBE ' . $tableName;
        $result = Shopware()->Db()->fetchAll($sql);
        foreach ($result as $data) {
            if ($data['Field'] === $columnName) {
                return true;
            }
        }
        return false;
    }
}
