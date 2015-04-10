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
     * Install the plugin
     * @return boolean
     */
    public function install() 
    {   
        try {
            $this->createConfiguration();
            $this->createMenu();
            $this->registerController();
            return array('success' => true, 'invalidateCache' => array('backend'));
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }


    public function createConfiguration()
    {
        try {
            $form = new Shopware_Plugins_Backend_Lengow_Components_Form($this->Form());
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
     * Registers the plugin controller event for the backend controller SwagFavorites
     */
    public function registerController()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Lengow',
            'onGetBackendController'
        );
    }

    /**
     * Uninstall the plugin
     * @return boolean
     */
    public function uninstall() {
        try {
            $this->removeSnippets($removeDirty = false); 
            return array('success' => true, 'invalidateCache' => array('backend'));
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Returns the path to the controller.
     * @return string
     */
    public function onGetBackendController()
    {
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/'
        );
        return $this->Path(). 'Controllers/Backend/Lengow.php';
    }

}