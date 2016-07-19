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
            'author' => 'Lengow SAS',
            'supplier' => 'Lengow SAS',
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
        $this->log('log/install/start');

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

        $this->log('log/install/add_menu');

        $this->createConfig();
        $this->updateSchema();
        $this->registerMyEvents();
        $this->registerCustomModels();
        $this->createCustomModels();
        $this->Plugin()->setActive(true);

        $this->log('log/install/end');

        return array('success' => true, 'invalidateCache' => array('backend'));
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
    public static function getEntityManager()
    {
        return Shopware()->Models();
    }

    /**
     * @inheritdoc
     */
    public function uninstall()
    {
        $this->log('log/uninstall/start');

        $this->removeCustomModels();

        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getShopsIds();

        foreach ($shops as $shopId) {
            $columnName = 'shop' . $shopId . '_active';
            $this->Application()->Models()->removeAttribute(
                's_articles_attributes',
                'lengow',
                $columnName
            );

            $this->log('log/uninstall/remove_column', 
                array(
                    'column' => $columnName,
                    'table' => 's_articles_attributes'
                )
            );
        }

        $this->getEntityManager()->generateAttributeModels(array(
            's_articles_attributes'
        ));

        $this->log('log/uninstall/end');

        return true;
    }

    /**
     * Update Shopware models.
     * Add lengowActive attribute for each shop in Attributes model
     */
    protected function updateSchema()
    {
        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getShopsIds();

        foreach ($shops as $shopId) {
            $attributeName = 'shop' . $shopId . '_active';
            $this->Application()->Models()->addAttribute(
                's_articles_attributes',
                'lengow',
                $attributeName,
                'boolean'
            );

            $this->log('log/install/add_column', 
                array(
                    'column' => $attributeName,
                    'table' => 's_articles_attributes'
                )
            );
        }

        $this->getEntityManager()->generateAttributeModels(array(
            's_articles_attributes'
        ));
    }

    /**
     * Add custom models used by Lengow in the database
     */
    protected function createCustomModels()
    {
        $em = self::getEntityManager();
        $schemaTool = new Doctrine\ORM\Tools\SchemaTool($em);

        $models = array(
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Order')
        );

        foreach ($models as $model) {
            $schemaTool->createSchema(array($model));
            $this->log('log/install/add_model', array('name' => $model->getName()));
        }
    }

    /**
     * Remove custom models used by Lengow from the database
     */
    protected function removeCustomModels()
    {
        $em = self::getEntityManager();
        $schemaTool = new Doctrine\ORM\Tools\SchemaTool($em);

        $models = array(
            $em->getClassMetadata('Shopware\CustomModels\Lengow\Order')
        );

        foreach ($models as $model) {
            $schemaTool->dropSchema(array($model));
            $this->log('log/uninstall/remove_model', array('name' => $model->getName()));
        }
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
        $view->extendsTemplate('backend/lengow/ressources/Lengow.tpl');
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
        $this->Application()->Loader()->registerNamespace(
            'Shopware\Models\Lengow',
            $this->Path() . 'Models/'
        );
    }

    /**
     * Register events
     */
    private function registerMyEvents()
    {
        // Main controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Lengow',
            'onGetMainControllerPath'
        );

        // Export controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowExport',
            'onGetExportControllerPath'
        );

        // Iframe
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Iframe',
            'onGetSubscribeControllerPath'
        );

        // Log controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowLogs',
            'onGetLogControllerPath'
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
    public function onGetMainControllerPath()
    {
        return $this->Path(). 'Controllers/Backend/Lengow.php';
    }

    /**
     * Returns the path to Lengow export controller
     * @return string
     */
    public function onGetExportControllerPath()
    {
        return $this->Path(). 'Controllers/Backend/LengowExport.php';
    }

    /**
     * Returns the path to Lengow log controller
     * @return string
     */
    public function onGetLogControllerPath()
    {
        return $this->Path(). 'Controllers/Backend/LengowLogs.php';
    }

    /**
     * Returns the path to the login iframe controller
     * @return string
     */
    public function onGetSubscribeControllerPath()
    {
        return $this->Path(). 'Controllers/Backend/Iframe.php';
    }

    /**
     * Create basic settings for the plugin
     * Accessible in Configuration/Basic Settings/Additional settings menu
     */
    private function createConfig()
    {
        $mainForm = $this->Form();

        // Main settings
        $mainSettingsElements = array(
            'lengowEnableShop' => array(
                'type'      => 'boolean',
                'label'     => 'settings/lengow_main_settings/enable/label',
                'editable'  => false,
                'value'     => 0,
                'description' => 'settings/lengow_main_settings/enable/description',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowAccountId' => array(
                'type'      => 'text',
                'label'     => 'settings/lengow_main_settings/account/label',
                'required'  => true,
                'description' => 'settings/lengow_main_settings/account/description',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowAccessToken' => array(
                'type'      => 'text',
                'label'     => 'settings/lengow_main_settings/access/label',
                'required'  => true,
                'description' => 'settings/lengow_main_settings/access/description',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowSecretToken' => array(
                'type'      => 'text',
                'label'     => 'settings/lengow_main_settings/secret/label',
                'required'  => true,
                'description' => 'settings/lengow_main_settings/secret/description',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowAuthorizedIps' => array(
                'type'      => 'text',
                'label'     => 'settings/lengow_main_settings/ip/label',
                'required'  => true,
                'value'     => '127.0.0.1',
                'description' => 'settings/lengow_main_settings/ip/description'
            )
        );

        $mainSettingForm = $this->createSettingForm('lengow_main_settings', $mainSettingsElements);
        $mainSettingForm->setParent($mainForm);

        // Export settings
        $dispatches = self::getEntityManager()->getRepository('Shopware\Models\Dispatch\Dispatch')->findBy(array('type' => 0));
        $selection = array();
        $defaultValue = null;

        if (count($dispatches) > 0) {
            $defaultValue = $dispatches[0]->getId();
        }

        foreach ($dispatches as $dispatch) {
            $selection[] = array($dispatch->getId(), $dispatch->getName());
        }

        $exportFormElements = array(
            'lengowExportVariation' => array(
                'type'      => 'boolean',
                'label'     => 'settings/lengow_export_settings/variation/label',
                'required'  => true,
                'editable'  => false,
                'value'     => 1,
                'description' => 'settings/lengow_export_settings/variation/description',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowExportOutOfStock' => array(
                'type'      => 'boolean',
                'label'     => 'settings/lengow_export_settings/out_stock/label',
                'required'  => true,
                'editable'  => false,
                'value'     => 0,
                'description' => 'settings/lengow_export_settings/out_stock/description',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowExportDisabledProduct' => array(
                'type'      => 'boolean',
                'label'     => 'settings/lengow_export_settings/disabled_products/label',
                'required'  => true,
                'editable'  => false,
                'value'     => 0,
                'description' => 'settings/lengow_export_settings/disabled_products/description',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowExportLengowSelection' => array(
                'type'      => 'boolean',
                'label'     => 'settings/lengow_export_settings/lengow_selection/label',
                'required'  => true,
                'editable'  => false,
                'value'     => 1,
                'description' => 'settings/lengow_export_settings/lengow_selection/description',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowDefaultDispatcher' => array(
                'type'      => 'select',
                'label'     => 'settings/lengow_export_settings/dispatcher/label',
                'required'  => true,
                'editable'  => false,
                'value'     => $defaultValue,
                'store'     => $selection,
                'description' => 'settings/lengow_export_settings/dispatcher/description',
                'scope'     => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $exportSettingForm = $this->createSettingForm('lengow_export_settings', $exportFormElements);
        $exportSettingForm->setParent($mainForm);

        // Import settings
        $importFormElements = array(
            'lengowDecreaseStock' => array(
                'type'      => 'boolean',
                'label'     => 'settings/lengow_import_settings/decrease_stock/label',
                'editable'  => false,
                'value'     => 0,
                'required'  => false,
                'description' => 'settings/lengow_import_settings/decrease_stock/description'
            ),
            'lengowImportDays' => array(
                'type'      => 'number',
                'label'     => 'settings/lengow_import_settings/import_days/label',
                'value'     => 5,
                'description' => 'settings/lengow_import_settings/import_days/description'
            ),
            'lengowPreprodMode' => array(
                'type'      => 'boolean',
                'label'     => 'settings/lengow_import_settings/preprod_mode/label',
                'value'     => 0,
                'description' => 'settings/lengow_import_settings/preprod_mode/description'
            )
        );


        $importSettingForm = $this->createSettingForm('lengow_import_settings', $importFormElements);
        $importSettingForm->setParent($mainForm);

        $forms = array(
            $mainSettingForm,
            $exportSettingForm,
            $importSettingForm
        );

        $mainForm->setChildren($forms);

        // Translate sub categories (sub-forms names)
        $locales = self::getEntityManager()->getRepository('\Shopware\Models\Shop\Locale')->findAll();
        foreach ($forms as $form) {
            $formName = $form->getName();
            foreach ($locales as $locale) {
                $isoCode = $locale->getLocale();

                if (Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::containsIso($isoCode)) {
                    $formLabel = $this->getTranslation('settings/' . $formName . '/label', $isoCode);
                    $formDescription = $this->getTranslation('settings/' . $formName . '/description', $isoCode);
                    $translationModel = new \Shopware\Models\Config\FormTranslation();
                    $translationModel->setLabel($formLabel);
                    $translationModel->setDescription($formDescription);
                    $translationModel->setLocale($locale);
                    $form->addTranslation($translationModel);
                }
            }
        }

        $this->log('log/install/add_form', array('formName' => $this->getName()));
    }

    /**
     * Log when installing/uninstalling the plugin
     * @param $key Translation key
     * @param $params Parameters to put in the translations
     */
    protected function log($key, $params = array())
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'Install',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage($key, $params)
        );
    }

    /**
     * Create settings forms for the plugin (basic settings)
     * @param $name Name of the form
     * @param $elements Options for this form
     * @return \Shopware\Models\Config\Form
     */
    protected function createSettingForm($name, $elements)
    {
        // Main settings form
        $form = new \Shopware\Models\Config\Form;
        $form->setName($name);
        $form->setLabel($this->getTranslation('settings/' . $name . '/label'));
        $form->setDescription($this->getTranslation('settings/' . $name . '/description'));

        $locales = self::getEntityManager()->getRepository('\Shopware\Models\Shop\Locale')->findAll();

        foreach ($elements as $key => $options) {
            $type = $options['type'];
            array_shift($options);

            $form->setElement(
                $type,
                $key,
                $options
            );

            //get the form element by name
            $elementModel = $form->getElement($key);

            // Translate fields for this form
            foreach ($locales as $locale) {
                $isoCode = $locale->getLocale();

                if (Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::containsIso($isoCode)) {
                    $label = $this->getTranslation($options['label'], $isoCode);
                    $description = $this->getTranslation($options['description'], $isoCode);
                    $translationModel = new \Shopware\Models\Config\ElementTranslation();
                    $translationModel->setLabel($label);
                    $translationModel->setDescription($description);
                    $translationModel->setLocale($locale);
                    $elementModel->addTranslation($translationModel);
                }
            }
        }

        $this->log('log/install/settings', array('settingName' => $name));

        return $form;
    }

    /**
     * Get translations for basic settings
     * @param $key Key of the translation
     * @param $isoCode Locale iso code (English by default)
     */
    protected function getTranslation($key, $isoCode = null)
    {
        $translation = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($key, $isoCode);
        return stripslashes($translation);
    }
}