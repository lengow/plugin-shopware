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
     * @var \Shopware\Components\Model\ModelManager Lengow module instance
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
     *
     * @param \Shopware\Components\Model\ModelManager $entityManager Shopware entity manager
     */
    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
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
            // Check that the table does not exist
            if (!self::tableExist($tableName)) {
                $this->schemaTool->createSchema(array($model));
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
     * Update custom models used by Lengow in the database
     *
     * @param string $version version number
     */
    public function updateCustomModels($version)
    {
        self::setInstallationStatus(true);
        $pluginPath = Shopware()->Plugins()->Backend()->Lengow()->Path();
        $upgradeFiles = array_diff(scandir($pluginPath . 'Upgrade'), array('..', '.'));
        foreach ($upgradeFiles as $file) {
            $numberVersion = preg_replace('/update_|\.php$/', '', $file);
            if (version_compare($version, $numberVersion, '>=')) {
                continue;
            }
            include $pluginPath . 'Upgrade/' . $file;
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
     */
    public function updateSchema()
    {
        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShops();
        $shopIds = array();
        foreach ($shops as $shop) {
            $shopIds[] = $shop->getId();
        }
        $this->addLengowColumns($shopIds);
    }

    /**
     * Remove custom models from database
     */
    public function removeCustomModels()
    {
        // List of models to remove when uninstalling the plugin
        $removeModels = $this->getCustomModels(true);
        foreach ($removeModels as $tableName => $model) {
            // Check that the table does not exist
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
     */
    public function removeAllLengowColumns()
    {
        $shopIds = array();
        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShops();
        foreach ($shops as $shop) {
            $shopIds[] = $shop->getId();
        }
        $this->removeLengowColumn($shopIds);
    }

    /**
     * Delete list of columns from s_articles_attributes table
     *
     * @param $shopIds array list of shop ids
     */
    public function removeLengowColumn($shopIds)
    {
        // @var Shopware_Plugins_Backend_Lengow_Bootstrap $lengowBootstrap
        $lengowBootstrap = Shopware()->Plugins()->Backend()->Lengow();
        $tableName = 's_articles_attributes';
        // For each article attributes, remove lengow columns
        foreach ($shopIds as $shopId) {
            $attributeName = 'shop' . $shopId . '_active';
            if (self::columnExists($tableName, 'lengow_' . $attributeName)) {
                // Check Shopware\Bundle\AttributeBundle\Service\CrudService::delete compatibility
                if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.2.2')) {
                    $crudService = $lengowBootstrap->get('shopware_attribute.crud_service');
                    $crudService->delete($tableName, 'lengow_' . $attributeName);
                } else {
                    // @legacy Shopware < 5.2
                    $lengowBootstrap->Application()->Models()->removeAttribute(
                        $tableName,
                        'lengow',
                        $attributeName
                    );
                }
                $lengowBootstrap::log(
                    'log/uninstall/remove_column',
                    array(
                        'column' => $attributeName,
                        'table' => $tableName,
                    )
                );
            } else {
                $lengowBootstrap::log(
                    'log/uninstall/column_not_exists',
                    array(
                        'column_name' => $attributeName,
                        'table_name' => $tableName,
                    )
                );
            }
        }
        $lengowBootstrap::getEntityManager()->generateAttributeModels(array($tableName));
    }

    /**
     * Add a new column to the s_articles_attributes table (if does not exist)
     *
     * @param $shopIds array list of shops to add
     */
    public function addLengowColumns($shopIds)
    {
        // @var Shopware_Plugins_Backend_Lengow_Bootstrap $lengowBootstrap
        $lengowBootstrap = Shopware()->Plugins()->Backend()->Lengow();
        // Check Shopware\Bundle\AttributeBundle\Service\CrudService::update compatibility
        $crudCompatibility = Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.2.2');
        $tableName = 's_articles_attributes';
        foreach ($shopIds as $shopId) {
            $attributeName = 'shop' . $shopId . '_active';
            if (!self::columnExists($tableName, 'lengow_' . $attributeName)) {
                if ($crudCompatibility) {
                    $crudService = $lengowBootstrap->get('shopware_attribute.crud_service');
                    $crudService->update($tableName, 'lengow_' . $attributeName, 'boolean');
                } else {
                    // @legacy Shopware < 5.2
                    $lengowBootstrap->Application()->Models()->addAttribute(
                        $tableName,
                        'lengow',
                        $attributeName,
                        'boolean'
                    );
                }
                $lengowBootstrap::log(
                    'log/install/add_column',
                    array(
                        'column' => $attributeName,
                        'table' => $tableName,
                    )
                );
            }
        }
        $lengowBootstrap::getEntityManager()->generateAttributeModels(array($tableName));
    }

    /**
     * Create Lengow settings and add them in s_lengow_settings table
     */
    public function setLengowSettings()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $lengowSettings = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::$lengowSettings;
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
        $sql = "SHOW TABLES LIKE '" . $tableName . "'";
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
        $sql = "DESCRIBE " . $tableName;
        $result = Shopware()->Db()->fetchAll($sql);
        foreach ($result as $data) {
            if ($data['Field'] == $columnName) {
                return true;
            }
        }

        return false;
    }
}
