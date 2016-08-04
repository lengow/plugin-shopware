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
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'plugin.json'), true);
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
            'version'     => $this->getVersion(),
            'label'       => $this->getLabel(),
            'source'      => $this->getSource(),
            'author'      => 'Lengow SAS',
            'supplier'    => 'Lengow SAS',
            'copyright'   => 'Copyright (c) 2016, Lengow',
            'description' => '',
            'support'     => 'support.lengow.zendesk@lengow.com',
            'link'        => 'http://lengow.com'
        );
    }

    /**
     * @inheritdoc
     */
    public function install()
    {
        self::log('log/install/start');
        if (!$this->assertMinimumVersion('4.2.0')) {
            throw new \RuntimeException('At least Shopware 4.2.0 is required');
        }
        $this->createMenuItem(array(
            'label'      => 'Lengow',
            'controller' => 'Lengow',
            'action'     => 'Index',
            'active'     => 1,
            'parent'     => $this->Menu()->findOneBy('label', 'Einstellungen'),
            'class'      => 'lengow--icon'
        ));
        self::log('log/install/add_menu');
        $lengowForm = new Shopware_Plugins_Backend_Lengow_Bootstrap_Form();
        $lengowForm->createConfig();
        $lengowDatabase = new Shopware_Plugins_Backend_Lengow_Bootstrap_Database();
        $lengowDatabase->updateSchema();
        $this->registerMyEvents();
        $this->registerCustomModels();
        $lengowDatabase->createCustomModels();
        $lengowDatabase->setLengowSettings();
        $this->Plugin()->setActive(true);
        self::log('log/install/end');
        return array('success' => true, 'invalidateCache' => array('backend'));
    }

    /**
     * @inheritdoc
     */
    public function afterInit()
    {
        $this->registerCustomModels();
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
     *
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
        self::log('log/uninstall/start');
        $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShops();
        // Remove custom attributes
        // For each article attributes, remove lengow columns
        foreach ($shops as $shop) {
            $columnName = 'shop'.$shop->getId().'_active';
            $this->Application()->Models()->removeAttribute(
                's_articles_attributes',
                'lengow',
                $columnName
            );
            self::log('log/uninstall/remove_column', array(
                'column' => $columnName,
                'table'  => 's_articles_attributes'
            ));
        }
        $this->getEntityManager()->generateAttributeModels(array('s_articles_attributes'));
        $lengowDatabase = new Shopware_Plugins_Backend_Lengow_Bootstrap_Database();
        $lengowDatabase->removeCustomModels();
        self::log('log/uninstall/end');
        return true;
    }

    /**
     * This callback function is triggered at the very beginning of the dispatch process and allows
     * us to register additional events on the fly. This way you won't ever need to reinstall you
     * plugin for new events - any event and hook can simply be registerend in the event subscribers
     *
     * @param $args Enlight_Event_EventArgs
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $this->registerMyComponents();
        $this->registerMyTemplateDir();
        $this->registerMySnippets();
        $this->registerMyEvents();
    }

    public function onPostDispatchBackendIndex(Enlight_Controller_ActionEventArgs $args)
    {
        $ctrl = $args->getSubject();
        $view = $ctrl->View();
        $view->extendsTemplate('backend/lengow/resources/lengow-components.tpl');
        $view->extendsTemplate('backend/lengow/resources/lengow-layout.tpl');
        $view->extendsTemplate('backend/lengow/resources/lengow-pages.tpl');
    }

    /**
     * Registers templates
     */
    private function registerMyTemplateDir()
    {
        Shopware()->Template()->addTemplateDir($this->Path().'Views');
    }

    /**
     * Registers snippets used for translation
     */
    private function registerMySnippets()
    {
        $this->Application()->Snippets()->addConfigDir($this->Path().'Snippets/');
    }

    /**
     * Registers components
     */
    private function registerMyComponents()
    {
        $this->Application()->Loader()->registerNamespace('Shopware\Lengow', $this->Path());
        $this->Application()->Loader()->registerNamespace('Shopware\Lengow\Components', $this->Path().'Components/');
        $this->Application()->Loader()->registerNamespace('Shopware\Models\Lengow', $this->Path().'Models/');
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
        // Home controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowHome',
            'onGetHomeControllerPath'
        );
        // Export controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowExport',
            'onGetExportControllerPath'
        );
        // Import controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowImport',
            'onGetImportControllerPath'
        );
        // Sync controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowSync',
            'onGetSyncControllerPath'
        );
        // Log controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowLogs',
            'onGetLogControllerPath'
        );
        // Help controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowHelp',
            'onGetHelpControllerPath'
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
     *
     * @return string
     */
    public function onGetMainControllerPath()
    {
        return $this->Path().'Controllers/Backend/Lengow.php';
    }

    /**
     * Returns the path to Lengow home controller
     * Check s_article_attributes table and create new column if a shop has been created
     * since Lengow plugin has been installed
     *
     * @return string
     */
    public function onGetHomeControllerPath()
    {
        // Force updating schema to make sure Lengow columns are sets for all shops
        $lengowDatabase = new Shopware_Plugins_Backend_Lengow_Bootstrap_Database();
        $lengowDatabase->updateSchema();
        return $this->Path().'Controllers/Backend/LengowHome.php';
    }

    /**
     * Returns the path to Lengow export controller
     *
     * @return string
     */
    public function onGetExportControllerPath()
    {
        return $this->Path().'Controllers/Backend/LengowExport.php';
    }

    /**
     * Return the path to Lengow import controller
     *
     * @return string
     */
    public function onGetImportControllerPath()
    {
        return $this->Path().'Controllers/Backend/LengowImport.php';
    }

    /**
     * Return the path to Lengow sync controller
     *
     * @return string
     */
    public function onGetSyncControllerPath()
    {
        return $this->Path().'Controllers/Backend/LengowSync.php';
    }

    /**
     * Returns the path to Lengow log controller
     *
     * @return string
     */
    public function onGetLogControllerPath()
    {
        return $this->Path().'Controllers/Backend/LengowLogs.php';
    }

    /**
     * Returns the path to Lengow help controller
     *
     * @return string
     */
    public function onGetHelpControllerPath()
    {
        return $this->Path().'Controllers/Backend/LengowHelp.php';
    }

    /**
     * Log when installing/uninstalling the plugin
     *
     * @param $key string Translation key
     * @param $params array Parameters to put in the translations
     */
    public static function log($key, $params = array())
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'Install',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage($key, $params)
        );
    }
}
