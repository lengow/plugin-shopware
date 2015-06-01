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
            $this->_registerCronJobs();
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

            $sql = "CREATE TABLE IF NOT EXISTS lengow_config("
               . "id int(1) NOT NULL UNIQUE,"
               . "LENGOW_MP_CONF varchar(100)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
               . "INSERT IGNORE INTO lengow_config (id, LENGOW_MP_CONF) VALUES(1, NULL);";
            Shopware()->Db()->query($sql);
            
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
            $form = $this->Form();
            $form->setElement('text', 'lengowIdUser', array(
                'label' => 'Customer ID', 
                'required' => true,
                'description' => 'Your Customer ID of Lengow'
            ));
            $form->setElement('text', 'lengowApiKey', array(
                'label' => 'Token API', 
                'required' => true,
                'description' => 'Your Token API of Lengow'
            ));
            $form->setElement('text', 'lengowAuthorisedIp', array(
                'label' => 'IP authorised to export', 
                'required' => true,
                'value' => '127.0.0.1',
                'description' => 'Authorized access to catalog export by IP, separated by ;'
            ));
            $form->setElement('boolean', 'lengowDebugMode', array(
                'label' => 'Debug mode', 
                'value' => false,
                'description' => 'Use it only during tests'
            ));

            $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
            //contains all translations
            $translations = array(
                'de_DE' => array(
                    'lengowIdUser' => array(
                        'label' => 'Benutzer-ID',
                        'description' => 'Ihre Benutzer-ID Lengow'
                    ), 
                    'lengowApiKey' => array(
                        'label' => 'Token API',
                        'description' => 'Ihre Token API Lengow'
                    ),
                    'lengowAuthorisedIp' => array(
                        'label' => 'IP für den Export zugelassen',
                        'description' => 'IP erlaubt, um den Katalog zu exportieren, getrennt durch ;'
                    ),
                    'lengowDebugMode' => array(
                        'label' => 'Debug mode',
                        'description' => 'Verwenden Sie nur bei Test'
                    )
                )
            );
            //iterate the languages
            foreach($translations as $locale => $snippets) {
                $localeModel = $shopRepository->findOneBy(array(
                    'locale' => $locale
                ));
                //not found? continue with next language
                if($localeModel === null){
                    continue;
                }
                //iterate all snippets of the current language
                foreach($snippets as $element => $snippet) {
                    //get the form element by name
                    $elementModel = $form->getElement($element);
                    //not found? continue with next snippet
                    if($elementModel === null) {
                        continue;
                    }  
                    //create new translation model
                    $translationModel = new \Shopware\Models\Config\ElementTranslation();
                    $translationModel->setDescription($snippet['description']);
                    $translationModel->setLabel($snippet['label']);
                    $translationModel->setLocale($localeModel);
                    //add the translation to the form element
                    $elementModel->addTranslation($translationModel);
                }
            }

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

    private function _registerCronJobs()
    {
        $this->subscribeEvent('Shopware_CronJob_LengowCron', 'onRunLengowCronJob');
        $this->createCronJob('Lengow', 'LengowCron', 86400, true);
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
        $dispatchs = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getDispatch();
        $importOrderStates = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getAllOrderStates();
        $importPayments = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getShippingName();
        $host = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getBaseUrl();
        $pathPlugin = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getPathPlugin();
        $exportUrl = $host . $pathPlugin . 'Webservice/export.php?shop=';
        $importUrl = $host . $pathPlugin . 'Webservice/import.php?shop=';

        foreach ($shops as $idShop) {
            if (!in_array($idShop['id'], $settingIDs)) {
                $shop = Shopware()->Models()->getReference('Shopware\Models\Shop\Shop',(int) $idShop['id']);
                $dispatch = Shopware()->Models()->getReference('Shopware\Models\Dispatch\Dispatch',(int) $dispatchs[0]->id);
                $orderStatus = Shopware()->Models()->getReference('Shopware\Models\Order\Status',(int) $importOrderStates[0]->id);
                $setting = new Shopware\CustomModels\Lengow\Setting();
                $setting->setLengowExportAllProducts(true)
                        ->setLengowExportDisabledProducts(false)
                        ->setLengowExportVariantProducts(true)
                        ->setLengowExportAttributes(false)
                        ->setLengowExportAttributesTitle(true)
                        ->setLengowExportOutStock(false)
                        ->setLengowExportImageSize($exportImagesSize[0]->id)
                        ->setLengowExportImages($exportImages[0]->id)
                        ->setLengowExportFormat($exportFormats[0]->id)
                        ->setLengowShippingCostDefault($dispatch)
                        ->setLengowExportFile(false)
                        ->setLengowExportUrl($exportUrl)
                        ->setLengowExportCron(false)
                        ->setLengowCarrierDefault($dispatch)
                        ->setLengowOrderProcess($orderStatus)
                        ->setLengowOrderShipped($orderStatus)
                        ->setLengowOrderCancel($orderStatus)
                        ->setLengowImportDays(3)
                        ->setLengowMethodName($importPayments[0]->id)
                        ->setLengowForcePrice(true)
                        ->setLengowReportMail(true)
                        ->setLengowImportUrl($importUrl)
                        ->setLengowImportCron(false)
                        ->setShop($shop);       
                Shopware()->Models()->persist($setting);
                Shopware()->Models()->flush();
            }
        }     
    }

    public function onRunLengowCronJob(Shopware_Components_Cron_CronJob $job)
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowCore::updateMarketPlaceConfiguration();
        Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanLog();
        Shopware_Plugins_Backend_Lengow_Components_LengowCore::exportCron();
        Shopware_Plugins_Backend_Lengow_Components_LengowCore::importCron();
        return true;
    }

}