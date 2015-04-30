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
        return 'Lengow 1.0';
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
     *
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
     * @return boolean
     */
    public function install() 
    {   
        try {
            $this->createDatabase();
            $this->createAttribute();
            $this->createConfiguration();
            $this->createMenu();
            $this->createEvents();
            return array('success' => true, 'invalidateCache' => array('backend'));
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Creates the plugin database tables over the doctrine schema tool.
     */
    private function createDatabase()
    {
        $em = $this->Application()->Models();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = array(
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Order'),
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Log')
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
    private function createAttribute()
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
    public function createConfiguration()
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
    public function createMenu()
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
    public function createEvents()
    {
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_Lengow', 'onGetControllerPath');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowExport', 'lengowBackendControllerExport');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowImport', 'lengowBackendControllerImport');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowLog', 'lengowBackendControllerLog');
    }

    /**
     * Uninstall the plugin - Remove attribute and re-generate articles models
     * @return boolean
     */
    public function uninstall() {
        try {
            // $this->removeDatabaseTables();
            // $this->Application()->Models()->removeAttribute(
            //     's_articles_attributes',
            //     'lengow',
            //     'lengowActive'
            // );
            // $this->getEntityManager()->generateAttributeModels(array(
            //     's_articles_attributes'
            // ));
            return array('success' => true, 'invalidateCache' => array('backend'));
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Removes the plugin database tables
     */
    public function removeDatabaseTables()
    {
        $em = $this->Application()->Models();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = array(
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Order')
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

}