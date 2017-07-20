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
 * @subpackage  Components
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Lengow Configuration Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration
{
    /**
     * @var array $lengowSettings specific Lengow settings in s_lengow_settings table
     */
    public static $lengowSettings = array(
        'lengowGlobalToken',
        'lengowShopToken',
        'lengowLastExport',
        'lengowImportInProgress',
        'lengowLastImportCron',
        'lengowLastImportManual',
        'lengowAccountStatusUpdate',
        'lengowAccountStatus',
        'lengowOrderStat',
        'lengowOrderStatUpdate',
        'lengowOptionCmsUpdate'
    );

    /**
     * Get config from Shopware database
     *
     * @param string $configName name of the setting to get
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return mixed
     */
    public static function getConfig($configName, $shop = null)
    {
        $value = null;
        // Force plugin to register custom models thanks to afterInit() method
        // Avoid issue when synchronizing account
        Shopware()->Plugins()->Backend()->Lengow();
        // If Lengow setting
        if (in_array($configName, self::$lengowSettings)) {
            $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
            $criteria = array('name' => $configName);
            if ($shop != null) {
                $criteria['shopId'] = $shop->getId();
            }
            // @var Shopware\CustomModels\Lengow\Settings $config
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
     * @param string $configName name of the setting to edit/add
     * @param mixed $value value to set for the setting
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     */
    public static function setConfig($configName, $value, $shop = null)
    {
        // Force plugin to register custom models thanks to afterInit() method
        // Avoid issue when synchronizing account
        Shopware()->Plugins()->Backend()->Lengow();
        // If Lengow global setting
        if (in_array($configName, self::$lengowSettings)) {
            $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
            $criteria = array('name' => $configName);
            if ($shop != null) {
                $criteria['shopId'] = $shop->getId();
            }
            // @var Shopware\CustomModels\Lengow\Settings $config
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
     * @return Shopware\Models\Shop\Shop
     */
    public static function getDefaultShop()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        return $em->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('default' => 1));
    }

    /**
     * Get Valid Account id / Access token / Secret token
     *
     * @return array
     */
    public static function getAccessIds()
    {
        $accountId = self::getConfig('lengowAccountId');
        $accessToken = self::getConfig('lengowAccessToken');
        $secretToken = self::getConfig('lengowSecretToken');
        return array($accountId, $accessToken, $secretToken);
    }

    /**
     * Set Valid Account id / Access token / Secret token
     *
     * @param array $accessIds Account id / Access token / Secret token
     */
    public static function setAccessIds($accessIds)
    {
        $listKey = array('lengowAccountId', 'lengowAccessToken', 'lengowSecretToken');
        foreach ($accessIds as $key => $value) {
            if (!in_array($key, array_keys($listKey))) {
                continue;
            }
            if (strlen($value) > 0) {
                self::setConfig($key, $value);
            }
        }
    }

    /**
     * Get catalog ids for a specific shop
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return array
     */
    public static function getCatalogIds($shop)
    {
        $catalogIds = array();
        $shopCatalogIds = self::getConfig('lengowCatalogId', $shop);
        if (strlen($shopCatalogIds) > 0 && $shopCatalogIds != 0) {
            $ids = trim(str_replace(array("\r\n", ',', '-', '|', ' ', '/'), ';', $shopCatalogIds), ';');
            $ids = array_filter(explode(';', $ids));
            foreach ($ids as $id) {
                if (is_numeric($id) && $id > 0) {
                    $catalogIds[] = (int)$id;
                }
            }
        }
        return $catalogIds;
    }

    /**
     * Set catalog ids for a specific shop
     *
     * @param array $catalogIds Lengow catalog ids
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     */
    public static function setCatalogIds($catalogIds, $shop)
    {
        $shopCatalogIds = self::getCatalogIds($shop);
        foreach ($catalogIds as $catalogId) {
            if (!in_array($catalogId, $shopCatalogIds) && is_numeric($catalogId) && $catalogId > 0) {
                $shopCatalogIds[] = (int)$catalogId;
            }
        }
        self::setConfig(
            'lengowCatalogId',
            count($shopCatalogIds) > 0 ? implode(';', $shopCatalogIds) : 0,
            $shop
        );
    }

    /**
     * Set active shop or not
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     */
    public static function setActiveShop($shop)
    {
        $active = true;
        $shopCatalogIds = self::getCatalogIds($shop);
        if (count($shopCatalogIds) === 0) {
            $active = false;
        }
        self::setConfig('lengowShopActive', $active, $shop);
    }

    /**
     * Get config from db
     * Shopware < 5.0.0 compatibility
     * > 5.0.0 : Use Shopware()->Plugins()->Backend()->Lengow()->get('config_writer')->get() instead
     *
     * @param string $name config name
     * @param integer $shopId Shopware shop id
     *
     * @return mixed
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
     * @param string $name new config name
     * @param mixed $value config value
     * @param integer $shopId Shopware shop id
     */
    public function save($name, $value, $shopId = 1)
    {
        $query = $this->getConfigValueByNameQuery($name, $shopId);
        $result = $query->execute()->fetch(\PDO::FETCH_ASSOC);
        if (isset($result['valueId']) && $result['valueId']) {
            $this->update($value, $result['valueId']);
        } else {
            $this->insert($value, $shopId, $result['elementId']);
        }
    }

    /**
     * Search element config by name
     *
     * @param string $name config name to search
     * @param integer $shopId Shopware shop id
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getConfigValueByNameQuery($name, $shopId = 1)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $connection = $em->getConnection();
        $query = $connection->createQueryBuilder();
        $query->select(
            array(
                'element.id as elementId',
                'element.value',
                'elementValues.id as valueId',
                'elementValues.value as configured',
            )
        );
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
     * @param mixed $value new config value
     * @param integer $valueId Shopware models config value id
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
     * @param mixed $value Config value
     * @param integer $shopId Shopware shop id
     * @param integer $elementId Shopware models config element id
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function insert($value, $shopId, $elementId)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        // @var Shopware\Models\Config\Element $element
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
            'lengowAccountId' => array('global' => true),
            'lengowAccessToken' => array('global' => true),
            'lengowSecretToken' => array('global' => true),
            'lengowIpEnabled' => array('global' => true),
            'lengowAuthorizedIp' => array('global' => true),
            'lengowShopToken' => array('shop' => true),
            'lengowShopActive' => array('shop' => true),
            'lengowCatalogId' => array('shop' => true),
            'lengowExportDisabledProduct' => array('shop' => true),
            'lengowExportSelectionEnabled' => array('shop' => true),
            'lengowDefaultDispatcher' => array('shop' => true),
            'lengowLastExport' => array('shop' => true),
            'lengowGlobalToken' => array('global' => true),
            'lengowEnableImport' => array('global' => true),
            'lengowImportShipMpEnabled' => array('global' => true),
            'lengowImportStockMpEnabled' => array('global' => true),
            'lengowImportDefaultDispatcher' => array('shop' => true),
            'lengowImportDays' => array('global' => true),
            'lengowImportPreprodEnabled' => array('global' => true),
            'lengowImportInProgress' => array('global' => true),
            'lengowLastImportCron' => array('global' => true),
            'lengowLastImportManual' => array('global' => true),
            'lengowIdWaitingShipment' => array('global' => true),
            'lengowIdShipped' => array('global' => true),
            'lengowIdCanceled' => array('global' => true),
            'lengowIdShippedByMp' => array('global' => true),
        );
        return $keys;
    }

    /**
     * Get all values for Lengow settings (used for synchronisation)
     *
     * @param \Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return array
     */
    public static function getAllValues($shop = null)
    {
        $rows = array();
        $keys = self::getKeys();
        foreach ($keys as $key => $value) {
            if ($shop) {
                if (isset($value['shop']) && $value['shop']) {
                    $rows[$key] = self::getConfig($key, $shop);
                }
            } else {
                if (isset($value['global']) && $value['global']) {
                    $rows[$key] = self::getConfig($key);
                }
            }
        }
        return $rows;
    }
}
