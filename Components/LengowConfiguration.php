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
        'lengowGlobalToken' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
        'lengowShopToken' => array(
            'shop' => true,
            'lengow_settings' => true,
        ),
        'lengowAccountId' => array(
            'global' => true,
        ),
        'lengowAccessToken' => array(
            'global' => true,
            'secret' => true,
        ),
        'lengowSecretToken' => array(
            'global' => true,
            'secret' => true,
        ),
        'lengowAuthorizationToken' => array(
            'lengow_settings' => true,
            'global' => true,
            'export' => false,
        ),
        'lengowLastAuthorizationTokenUpdate' => array(
            'lengow_settings' => true,
            'global' => true,
            'export' => false,
        ),
        'lengowShopActive' => array(
            'shop' => true,
            'type' => 'boolean',
        ),
        'lengowCatalogId' => array(
            'shop' => true,
            'update' => true,
        ),
        'lengowIpEnabled' => array(
            'global' => true,
            'type' => 'boolean',
        ),
        'lengowAuthorizedIp' => array(
            'global' => true,
        ),
        'lengowTrackingEnable' => array(
            'global' => true,
            'type' => 'boolean',
        ),
        'lengowTrackingId' => array(
            'global' => true,
        ),
        'lengowAccountStatus' => array(
            'lengow_settings' => true,
            'global' => true,
            'export' => false,
        ),
        'lengowAccountStatusUpdate' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
        'lengowOrderStat' => array(
            'lengow_settings' => true,
            'global' => true,
            'export' => false,
        ),
        'lengowOrderStatUpdate' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
        'lengowOptionCmsUpdate' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
        'lengowCatalogUpdate' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
        'lengowMarketplaceUpdate' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
        'lengowLastSettingUpdate' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
        'lengowExportSelectionEnabled' => array(
            'shop' => true,
            'type' => 'boolean',
        ),
        'lengowExportDisabledProduct' => array(
            'shop' => true,
            'type' => 'boolean',
        ),
        'lengowDefaultDispatcher' => array(
            'shop' => true,
        ),
        'lengowLastExport' => array(
            'lengow_settings' => true,
            'shop' => true,
        ),
        'lengowImportDays' => array(
            'global' => true,
            'update' => true,
        ),
        'lengowImportDefaultDispatcher' => array(
            'shop' => true,
        ),
        'lengowImportReportMailEnabled' => array(
            'global' => true,
            'type' => 'boolean',
        ),
        'lengowImportReportMailAddress' => array(
            'global' => true,
        ),
        'lengowImportShipMpEnabled' => array(
            'global' => true,
            'type' => 'boolean',
        ),
        'lengowImportStockMpEnabled' => array(
            'global' => true,
            'type' => 'boolean',
        ),
        'lengowImportPreprodEnabled' => array(
            'global' => true,
            'type' => 'boolean',
        ),
        'lengowImportInProgress' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
        'lengowLastImportCron' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
        'lengowLastImportManual' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
        'lengowIdWaitingShipment' => array(
            'global' => true,
        ),
        'lengowIdShipped' => array(
            'global' => true,
        ),
        'lengowIdCanceled' => array(
            'global' => true,
        ),
        'lengowIdShippedByMp' => array(
            'global' => true,
        ),
        'lengowLastActionSync' => array(
            'lengow_settings' => true,
            'global' => true,
        ),
    );

    /**
     * Get config from Shopware database
     *
     * @param string $configName name of the setting to get
     * @param Shopware\Models\Shop\Shop|integer|null $shop Shopware shop instance
     *
     * @return mixed
     */
    public static function getConfig($configName, $shop = null)
    {
        $value = null;
        // force plugin to register custom models thanks to afterInit() method
        // avoid issue when synchronizing account
        Shopware()->Plugins()->Backend()->Lengow();
        if (array_key_exists($configName, self::$lengowSettings)) {
            $setting = self::$lengowSettings[$configName];
            // if Lengow setting
            if (isset($setting['lengow_settings']) && $setting['lengow_settings']) {
                $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
                $criteria = array('name' => $configName);
                if ($shop !== null) {
                    $criteria['shopId'] = is_integer($shop) ? $shop : $shop->getId();
                }
                // @var Shopware\CustomModels\Lengow\Settings $config
                $config = $em->getRepository('Shopware\CustomModels\Lengow\Settings')->findOneBy($criteria);
                if ($config !== null) {
                    $value = $config->getValue();
                }
            } else {
                // if shop no shop, get default one
                if ($shop === null) {
                    $shop = self::getDefaultShop();
                }
                $shopId = is_integer($shop) ? $shop : $shop->getId();
                $lengowConf = new Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration();
                $value = $lengowConf->get($configName, $shopId);
            }
        }
        return $value;
    }

    /**
     * Set config new value in database
     *
     * @param string $configName name of the setting to edit/add
     * @param mixed $value value to set for the setting
     * @param Shopware\Models\Shop\Shop|integer|null $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function setConfig($configName, $value, $shop = null)
    {
        // force plugin to register custom models thanks to afterInit() method
        // avoid issue when synchronizing account
        Shopware()->Plugins()->Backend()->Lengow();
        if (array_key_exists($configName, self::$lengowSettings)) {
            $setting = self::$lengowSettings[$configName];
            // if Lengow global setting
            if (isset($setting['lengow_settings']) && $setting['lengow_settings']) {
                $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
                $criteria = array('name' => $configName);
                if ($shop != null) {
                    if (is_integer($shop)) {
                        $shop = $em->getRepository('Shopware\Models\Shop\Shop')->find($shop);
                    }
                    $criteria['shopId'] = $shop->getId();
                }
                try {
                    // @var Shopware\CustomModels\Lengow\Settings $config
                    $config = $em->getRepository('Shopware\CustomModels\Lengow\Settings')->findOneBy($criteria);
                    // If null, create a new lengow config
                    if ($config === null) {
                        $config = new \Shopware\CustomModels\Lengow\Settings();
                        $config->setName($configName)
                            ->setShop($shop)
                            ->setDateAdd(new DateTime());
                    }
                    $config->setValue($value)
                        ->setDateUpd(new DateTime());
                    $em->persist($config);
                    $em->flush($config);
                } catch (Exception $e) {
                    return false;
                }
            } else {
                // if shop no shop, get default one
                if ($shop === null) {
                    $shop = self::getDefaultShop();
                }
                $shopId = is_integer($shop) ? $shop : $shop->getId();
                $lengowConf = new Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration();
                return $lengowConf->save($configName, $value, $shopId);
            }
        }
        return true;
    }

    /**
     * Get Shopware default shop
     *
     * @return Shopware\Models\Shop\Shop
     */
    public static function getDefaultShop()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        /** @var Shopware\Models\Shop\Shop $shop */
        $shop = $em->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('default' => 1));
        return $shop;
    }

    /**
     * Get Valid Account id / Access token / Secret token
     *
     * @return array
     */
    public static function getAccessIds()
    {
        $accountId = (int)self::getConfig('lengowAccountId');
        $accessToken = self::getConfig('lengowAccessToken');
        $secretToken = self::getConfig('lengowSecretToken');
        if ($accountId !== 0 && $accessToken !== '0' && $secretToken !== '0') {
            return array($accountId, $accessToken, $secretToken);
        } else {
            return array(null, null, null);
        }
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
     * Check if new merchant
     *
     * @return boolean
     */
    public static function isNewMerchant()
    {
        $accessIds = self::getAccessIds();
        list($accountId, $accessToken, $secretToken) = $accessIds;
        if ($accountId !== null && $accessToken !== null && $secretToken !== null) {
            return false;
        }
        return true;
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
     *
     * @return boolean
     */
    public static function setCatalogIds($catalogIds, $shop)
    {
        $valueChange = false;
        $shopCatalogIds = self::getCatalogIds($shop);
        foreach ($catalogIds as $catalogId) {
            if (!in_array($catalogId, $shopCatalogIds) && is_numeric($catalogId) && $catalogId > 0) {
                $shopCatalogIds[] = (int)$catalogId;
                $valueChange = true;
            }
        }
        self::setConfig(
            'lengowCatalogId',
            count($shopCatalogIds) > 0 ? implode(';', $shopCatalogIds) : 0,
            $shop
        );
        return $valueChange;
    }

    /**
     * Recovers if a shop is active or not
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function shopIsActive($shop = null)
    {
        return (bool)self::getConfig('lengowShopActive', $shop);
    }

    /**
     * Set active shop or not
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function setActiveShop($shop)
    {
        $shopIsActive = self::shopIsActive($shop);
        $shopHasCatalog = count(self::getCatalogIds($shop)) > 0;
        self::setConfig('lengowShopActive', $shopHasCatalog, $shop);
        return $shopIsActive !== $shopHasCatalog ? true : false;
    }

    /**
     * Get all report mails
     *
     * @return array
     */
    public static function getReportEmailAddress()
    {
        $reportEmailAddress = array();
        $emails = self::getConfig('lengowImportReportMailAddress');
        $emails = trim(str_replace(array("\r\n", ',', ' '), ';', $emails), ';');
        $emails = explode(';', $emails);
        foreach ($emails as $email) {
            if (strlen($email) > 0 && (bool)preg_match('/^\S+\@\S+\.\S+$/', $email)) {
                $reportEmailAddress[] = $email;
            }
        }
        if (empty($reportEmailAddress)) {
            $reportEmailAddress[] = self::getConfig('mail');
        }
        return $reportEmailAddress;
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
     *
     * @return boolean
     */
    public function save($name, $value, $shopId = 1)
    {
        try {
            $query = $this->getConfigValueByNameQuery($name, $shopId);
            $result = $query->execute()->fetch(\PDO::FETCH_ASSOC);
            if (isset($result['valueId']) && $result['valueId']) {
                $this->update($value, $result['valueId']);
            } else {
                $this->insert($value, $shopId, $result['elementId']);
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
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
        /** @var Shopware\Models\Config\Element $element */
        $element = $em->getReference('Shopware\Models\Config\Element', $elementId);
        /** @var Shopware\Models\Shop\Shop $shop */
        $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);
        $option = new Shopware\Models\Config\Value();
        $option->setElement($element)
            ->setShop($shop)
            ->setValue($value);
        $em->persist($option);
        $em->flush($option);
    }

    /**
     * Get all values for Lengow settings (used for synchronisation)
     *
     * @param \Shopware\Models\Shop\Shop|null $shop Shopware shop instance
     *
     * @return array
     */
    public static function getAllValues($shop = null)
    {
        $rows = array();
        $lengowSettings = self::$lengowSettings;
        foreach ($lengowSettings as $key => $value) {
            if (isset($value['export']) && !$value['export']) {
                continue;
            }
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

    /**
     * Check value and create a log if necessary
     *
     * @param string $key name of Lengow setting
     * @param mixed $value setting value
     * @param Shopware\Models\Shop\Shop|integer|null $shop Shopware shop instance
     */
    public static function checkAndLog($key, $value, $shop = null)
    {
        if (array_key_exists($key, self::$lengowSettings)) {
            $setting = self::$lengowSettings[$key];
            $oldValue = self::getConfig($key, $shop);
            if ($oldValue != $value) {
                if (isset($setting['secret']) && $setting['secret']) {
                    $value = preg_replace("/[a-zA-Z0-9]/", '*', $value);
                    $oldValue = preg_replace("/[a-zA-Z0-9]/", '*', $oldValue);
                } elseif (isset($setting['type']) && $setting['type'] === 'boolean') {
                    $value = (int)$value;
                    $oldValue = (int)$oldValue;
                }
                if (!is_null($shop)) {
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        Shopware_Plugins_Backend_Lengow_Components_LengowLog::CODE_SETTING,
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/setting/setting_change_for_shop',
                            array(
                                'key' => $key,
                                'old_value' => $oldValue,
                                'value' => $value,
                                'shop_id' => is_integer($shop) ? $shop : $shop->getId(),
                            )
                        )
                    );
                } else {
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        Shopware_Plugins_Backend_Lengow_Components_LengowLog::CODE_SETTING,
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/setting/setting_change',
                            array(
                                'key' => $key,
                                'old_value' => $oldValue,
                                'value' => $value,
                            )
                        )
                    );
                }
                // save last update date for a specific settings (change synchronisation interval time)
                if (isset($setting['update']) && $setting['update']) {
                    self::setConfig('lengowLastSettingUpdate', time());
                }
            }
        }
    }
}
