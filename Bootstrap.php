<?php

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
            'label' => 'Lengow',
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
            return array('success' => true, 'invalidateCache' => array('backend'));
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Uninstall the plugin
     * @return boolean
     */
    public function uninstall() {
        try {
            return array('success' => true, 'invalidateCache' => array('backend'));
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Creates the configuration fields
     *
     * @throws Exception
     * @return void
     */
    private function createConfiguration()
    {
        try {
            $form = $this->Form();
            $form->setElement('text', 'customerid', array(
                'label' => 'Customer ID', 
                'required' => true,
                'description' => 'Test'          
            ));
            $repository = Shopware()->Models()->getRepository('Shopware\Models\Config\Form');
            $form->setParent($repository->findOneBy(array('name' => 'Interface')));
        } catch (Exception $exception) {
            Shopware()->Log()->Err("There was an error creating the plugin configuration. " . $exception->getMessage());
            throw new Exception("There was an error creating the plugin configuration. " . $exception->getMessage());
        }
    }

}