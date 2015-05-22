<?php

/**
 * Bootstrap.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */

class Shopware_Plugins_Backend_Lengow_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    /**
     * Define the actions that must be carried for the plugin
     * @return array
     */
    public function getCapabilities() 
    {
        return array(
            'install' => true,
            'update' => true,
            'enable' => true
        );
    }

    /**
     * Name of the plugin
     *
     * @return string
     */
    public function getLabel()
    {
        return 'Lengow';
    }


    /**
     * Version of the plugin
     * @return string
     */
    public function getVersion() 
    {
        return '1.0.0';
    }

    /**
     * Information of the plugin
     * @return array
     */
    public function getInfo() 
    {   
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'author' => 'Lengow',
            'supplier' => 'Lengow',
            'description' => '<h2>The new module of Lengow for Shopware.</h2>',
            'support' => 'Lengow',
            'copyright' => 'Copyright (c) 2015, Lengow',
            'link' => 'http://www.lengow.fr'
        );
    }

    /**
     * After init event of the bootstrap class.
     * The afterInit function registers the custom plugin models.
     */
    public function afterInit()
    {
        $this->registerCustomModels();
    }

    /**
     * @return \Shopware\Components\Model\ModelManager
     */
    protected function getEntityManager()
    {
        return Shopware()->Models();
    }

    /**
     * Install the plugin
     * @return array
     */
    public function install() 
    {   
        try {
            $this->_createDatabase();
            $this->_createAttribute();
            $this->_createConfiguration();
            $this->_createDefaultConfiguration();
            $this->_createMenu();
            $this->_createEvents();
            $this->Plugin()->setActive(true);
            return array('success' => true, 'invalidateCache' => array('backend'));
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Creates the plugin database tables over the doctrine schema tool.
     */
    private function _createDatabase()
    {
        $em = $this->Application()->Models();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = array(
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Order'),
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Log'),
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Setting')
        );
        try {
            $tool->createSchema($classes);
        } catch (\Doctrine\ORM\Tools\ToolsException $e) {
            // ignore
        }
    }

    /**
     * create additional attributes in s_user_attributes and re-generate attribute models
     */
    private function _createAttribute()
    {
        $this->Application()->Models()->addAttribute(
            's_articles_attributes',
            'lengow',
            'lengowActive',
            'int(1)',
            true,
            0
        );
        $this->getEntityManager()->generateAttributeModels(array(
            's_articles_attributes'
        ));
    }

    /**
     * Creates the plugin configuration.
     */
    private function _createConfiguration()
    {
        try {
            $form = new Shopware_Plugins_Backend_Lengow_Components_LengowForm($this->Form());
            $form->create();
        } catch (Exception $exception) {
            Shopware()->Log()->Err("There was an error creating the plugin configuration. " . $exception->getMessage());
            throw new Exception("There was an error creating the plugin configuration. " . $exception->getMessage());
        }
    }

    /**
     * Creates the Lengow backend menu item.
     */
    private function _createMenu()
    {
        $this->createMenuItem(array(
            'label' => 'Lengow',
            'controller' => 'Lengow',
            'class' => 'sprite-star',
            'action' => 'Index',
            'active' => 1,
            'parent' => $this->Menu()->findOneBy(array('label' => 'Einstellungen'))
        ));
    }

    /**
     * Create Events
     */
    private function _createEvents()
    {
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_Lengow', 'onGetControllerPath');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowExport', 'lengowBackendControllerExport');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowImport', 'lengowBackendControllerImport');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowLog', 'lengowBackendControllerLog');
    }

    /**
     * Uninstall the plugin - Remove attribute and re-generate articles models
     * @return array
     */
    public function uninstall() {
        try {
            $this->_removeDatabaseTables();
            $this->Application()->Models()->removeAttribute(
                's_articles_attributes',
                'lengow',
                'lengowActive'
            );
            $this->getEntityManager()->generateAttributeModels(array(
                's_articles_attributes'
            ));
            return array('success' => true, 'invalidateCache' => array('backend'));
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Removes the plugin database tables
     */
    private function _removeDatabaseTables()
    {
        $em = $this->Application()->Models();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = array(
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Order'),
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Log'),
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Setting')
        );
        $tool->dropSchema($classes);
    }


    /**
     * Returns the path to the controller Lengow
     * @return string
     */
    public function onGetControllerPath()
    {
        $this->Application()->Snippets()->addConfigDir($this->Path() . 'Snippets/');
        $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/');
        return $this->Path(). 'Controllers/Backend/Lengow.php';
    }

    /**
     * Returns the path to the controller LengowExport
     * @return string
     */
    public function lengowBackendControllerExport()
    {
        $this->Application()->Snippets()->addConfigDir($this->Path() . 'Snippets/');
        $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/');
        return $this->Path(). 'Controllers/Backend/LengowExport.php';
    }

    /**
     * Returns the path to the controller LengowImport
     * @return string
     */
    public function lengowBackendControllerImport()
    {
        $this->Application()->Snippets()->addConfigDir($this->Path() . 'Snippets/');
        $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/');
        return $this->Path(). 'Controllers/Backend/LengowImport.php';
    }

    /**
     * Returns the path to the controller LengowLog
     * @return string
     */
    public function lengowBackendControllerLog()
    {
        $this->Application()->Snippets()->addConfigDir($this->Path() . 'Snippets/');
        $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/');
        return $this->Path(). 'Controllers/Backend/LengowLog.php';
    }

    /**
     * Create default confuguration for all shops
     */
    private function _createDefaultConfiguration()
    {
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS shops.id AS id 
                FROM s_core_shops shops
        ";
        $shops = Shopware()->Db()->fetchAll($sql);

        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS setting.shopID AS id 
                FROM lengow_settings setting
        ";
        $settingShopIDs = Shopware()->Db()->fetchAll($sql);
        
        $settingIDs = array();
        foreach ($settingShopIDs as $value) {
            $settingIDs[] = $value['id'];
        }

        $exportFormats = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportFormats();
        $exportImages = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getImagesCount();
        $exportImagesSize = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getImagesSize();
        $exportCarriers = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getCarriers();
        $importOrderStates = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getAllOrderStates();
        $importPayments = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getShippingName();
        $pathPlugin = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getPathPlugin();
        $exportUrl = 'http://' . $_SERVER['SERVER_NAME'] . $pathPlugin . 'Webservice/export.php?shop=';
        $importUrl = 'http://' . $_SERVER['SERVER_NAME'] . $pathPlugin . 'Webservice/import.php?shop=';

        foreach ($shops as $idShop) {
            if (!in_array($idShop['id'], $settingIDs)) {
                $shop = Shopware()->Models()->getReference('Shopware\Models\Shop\Shop', $idShop['id']);
                $dispatch = Shopware()->Models()->getReference('Shopware\Models\Dispatch\Dispatch', $exportCarriers[0]->id);
                $orderStatus = Shopware()->Models()->getReference('Shopware\Models\Order\Status', $importOrderStates[0]->id);
                $setting = new Shopware\CustomModels\Lengow\Setting();
                $setting->setLengowAuthorisedIp('127.0.0.1')
                        ->setLengowExportAllProducts(true)
                        ->setLengowExportDisabledProducts(false)
                        ->setLengowExportVariantProducts(true)
                        ->setLengowExportAttributes(false)
                        ->setLengowExportAttributesTitle(true)
                        ->setLengowExportOutStock(false)
                        ->setLengowExportImageSize($exportImagesSize[0]->id)
                        ->setLengowExportImages($exportImages[0]->id)
                        ->setLengowExportFormat($exportFormats[0]->id)
                        ->setLengowExportFile(false)
                        ->setLengowExportUrl($exportUrl)
                        ->setLengowCarrierDefault($dispatch)
                        ->setLengowOrderProcess($orderStatus)
                        ->setLengowOrderShipped($orderStatus)
                        ->setLengowOrderCancel($orderStatus)
                        ->setLengowImportDays(3)
                        ->setLengowMethodName($importPayments[0]->id)
                        ->setLengowForcePrice(true)
                        ->setLengowReportMail(true)
                        ->setLengowImportUrl($importUrl)
                        ->setLengowExportCron(false)
                        ->setLengowDebug(false)
                        ->setShop($shop);       
                Shopware()->Models()->persist($setting);
                Shopware()->Models()->flush();
            }
        }     
    }

}