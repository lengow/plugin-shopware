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
class Shopware_Plugins_Backend_Lengow_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * @inheritdoc
     */
    public function getVersion() {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR .'plugin.json'), true);
        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return 'Lengow';
    }

    /**
     * @inheritdoc
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'source' => $this->getSource(),
            'author' => 'Lengow',
            'supplier' => 'Lengow',
            'copyright' => 'Copyright (c) 2016, Lengow',
            'description' => '',
            'support' => 'support.lengow.zendesk@lengow.com',
            'link' => 'http://lengow.com'
        );
    }

    /**
     * @inheritdoc
     */
    public function install()
    {
        if (!$this->assertMinimumVersion('4.0.0')) {
            throw new \RuntimeException('At least Shopware 4.0.0 is required');
        }

        $this->createMenuItem(array(
            'label' => 'Lengow',
            'controller' => 'Lengow',
            'action' => 'Index',
            'active' => 1,
            'parent' => $this->Menu()->findOneBy('label', 'Einstellungen'),
			'class' => 'lengow--icon'
        ));
        $this->createConfig();
        $this->updateSchema();
        $this->registerMyEvents();
        $this->Plugin()->setActive(true);
        return array('success' => true, 'invalidateCache' => array('frontend', 'backend'));
    }

    /**
     * @inheritdoc
     */
    public function update($oldVersion)
    {
        return true;
    }

    /**
     * Get Shopware entity manager
     * @return \Shopware\Components\Model\ModelManager
     */
    protected function getEntityManager()
    {
        return Shopware()->Models();
    }

    /**
     * @inheritdoc
     */
    public function uninstall()
    {
        $this->Application()->Models()->removeAttribute(
            's_articles_attributes',
            'lengow',
            'lengowActive'
        );

        $this->getEntityManager()->generateAttributeModels(array(
            's_articles_attributes'
        ));

        return true;
    }

    /**
     * Update Shopware models.
     * Add lengowActive attribute in Attributes model
     */
    protected function updateSchema()
    {
        $this->Application()->Models()->addAttribute(
            's_articles_attributes',
            'lengow',
            'lengowActive',
            'boolean',
            true,
            '0'
        );
        $this->getEntityManager()->generateAttributeModels(array(
            's_articles_attributes'
        ));
    }

    /**
     * This callback function is triggered at the very beginning of the dispatch process and allows
     * us to register additional events on the fly. This way you won't ever need to reinstall you
     * plugin for new events - any event and hook can simply be registerend in the event subscribers
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $this->registerMyComponents();
        $this->registerCustomModels();
        $this->registerMyTemplateDir();
        $this->registerMySnippets();
        $this->registerMyEvents();
    }

    public function onPostDispatchBackendIndex(Enlight_Controller_ActionEventArgs $args)
    {
        $ctrl = $args->getSubject();
        $view = $ctrl->View();
        $view->extendsTemplate('backend/plugins/lengow/index/header.tpl');
    }

    /**
     * Registers templates
     */
    private function registerMyTemplateDir()
    {
        Shopware()->Template()->addTemplateDir($this->Path() . 'Views');
    }

    /**
     * Registers snippets
     */
    private function registerMySnippets()
    {
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );
    }

    /**
     * Registers components
     */
    private function registerMyComponents()
    {
        $this->Application()->Loader()->registerNamespace(
            'Shopware\Lengow',
            $this->Path()
        );
        $this->Application()->Loader()->registerNamespace(
            'Shopware\Lengow\Components',
            $this->Path() . 'Components/'
        );
    }

    /**
     * Register events
     */
    private function registerMyEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Lengow',
            'onGetControllerPath'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Iframe',
            'onGetControllerIframePath'
        );


        $this->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopStartup',
            'onStartDispatch'
        );

        // Backend events
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Index',
            'onPostDispatchBackendIndex'
        );
    }

    /**
     * Returns the path to Lengow main controller
     * @return string
     */
    public function onGetControllerPath()
    {
        return $this->Path(). 'Controllers/Backend/Lengow.php';
    }

    /**
     * Returns the path to the login iframe controller
     * @return string
     */
    public function onGetControllerIframePath()
    {
        return $this->Path(). 'Controllers/Backend/Iframe.php';
    }

    /**
     * Create basic settings for the plugin
     * Accessible in Configuration/Basic Settings/Additional settings menu
     */
    private function createConfig()
    {
        $selectOptions = array(
                array(1, 'Yes'),
                array(0, 'No')
        );

        $mainForm = $this->Form();

        // Main settings form
        $mainSettingForm = new \Shopware\Models\Config\Form;
        $mainSettingForm->setName('lengowMainSettings');
        $mainSettingForm->setLabel('Main settings');
        $mainSettingForm->setDescription('Set settings for Lengow');
        $mainSettingForm->setParent($mainForm);

        $mainSettingForm->setElement(
            'select',
            'lengowDebugMode',
            array(
                'label' => 'Enable shop',
                'editable' => false,
                'store' => $selectOptions,
                'value' => 0,
                'description' => 'Enable this shop for Lengow',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $mainSettingForm->setElement(
            'text',
            'lengowAccountId',
            array(
                'label'     => 'Account ID',
                'required'  => true,
                'description' => 'Your account ID of Lengow'
            )
        );
        $mainSettingForm->setElement(
            'text',
            'lengowAccessToken',
            array(
                'label'     => 'Access token',
                'required'  => true,
                'description' => 'Your access token'
            )
        );
        $mainSettingForm->setElement(
            'text',
            'lengowSecretToken',
            array(
                'label'     => 'Secret token',
                'required'  => true,
                'description' => 'Your secret token'
            )
        );
        $mainSettingForm->setElement(
            'text',
            'lengowAuthorizedIps',
            array(
                'label'     => 'IP authorised to export',
                'required'  => true,
                'value' => '127.0.0.1',
                'description' => 'Authorized access to catalog export by IP, separated by ";"'
            )
        );

        // Import form
        $importForm = new \Shopware\Models\Config\Form;
        $importForm->setName('lengowImportSettings');
        $importForm->setLabel('Import settings');
        $importForm->setParent($mainForm);

        $importForm->setElement(
            'select',
            'lengowDecreaseStock',
            array(
                'label'     => 'I want to decrease my stock',
                'editable' => false,
                'store'     => $selectOptions,
                'value'     => 0,
                'required'  => false,
                'description' => 'Use this option to take into account your marketplaces orders on your stock in your Shopware backoffice',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        // Export form
        $exportForm = new \Shopware\Models\Config\Form;
        $exportForm->setName('lengowExportSettings');
        $exportForm->setLabel('Export settings');
        $exportForm->setParent($mainForm);

        $exportForm->setElement(
            'select',
            'lengowExportVariation',
            array(
                'label' => 'Export variant products',
                'required' => true,
                'editable' => false,
                'store' => $selectOptions,
                'value' => 1,
                'description' => 'Export variant products',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $exportForm->setElement(
            'select',
            'lengowExportOutOfStock',
            array(
                'label' => 'Export out of stock products',
                'required' => true,
                'editable' => false,
                'store' => $selectOptions,
                'value' => 0,
                'description' => 'Export out of stock products',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $exportForm->setElement(
            'select',
            'lengowExportDisabledProduct',
            array(
                'label' => 'Export inactive products',
                'required' => true,
                'editable' => false,
                'store' => $selectOptions,
                'value' => 0,
                'description' => 'Export disabled products',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $exportForm->setElement(
            'select',
            'lengowExportLengowSelection',
            array(
                'label' => 'Export Lengow products',
                'required' => true,
                'editable' => false,
                'store' => $selectOptions,
                'value' => 0,
                'description' => 'Export products that you have selected in Lengow',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $forms = array(
            $mainSettingForm,
            $exportForm,
            $importForm
        );

        $mainForm->setChildren($forms);
    }
}