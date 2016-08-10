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
    public function getInfo()
    {
        return array(
            'version'     => $this->getVersion(),
            'label'       => 'Lengow',
            'source'      => $this->getSource(),
            'author'      => 'Lengow',
            'supplier'    => 'Lengow',
            'copyright'   => 'Lengow',
            'description' => 'Lengow',
            'support'     => 'Lengow',
            'link'        => 'Lengow'
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
        $this->registerController('Backend', 'Lengow');
        $this->createMenuItem(array(
            'label'      => 'Lengow',
            'controller' => 'Lengow',
            'action'     => 'Index',
            'active'     => 1,
            'parent'     => $this->Menu()->findOneBy(['label' => 'Einstellungen']),
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
        // Remove custom attributes
        $lengowDatabase = new Shopware_Plugins_Backend_Lengow_Bootstrap_Database();
        $lengowDatabase->removeAllLengowColumns();
        $lengowDatabase->removeCustomModels();
        self::log('log/uninstall/end');
        return true;
    }

    /**
     * Registers templates
     */
    private function registerMyTemplateDir()
    {
        Shopware()->Template()->addTemplateDir($this->Path().'Views');
    }

    /**
     * Register events
     */
    private function registerMyEvents()
    {
        // Main controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Lengow',
            'getDefaultControllerPath'
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
        // Backend events
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Index',
            'onPostDispatchBackendIndex'
        );
        // Backend events
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Config',
            'onPostDispatchBackendConfig'
        );
    }

    /**
     * Listen to basic settings changes. Add/remove lengow column from s_articles_attributes
     * @param $args Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchBackendConfig($args)
    {
        $request = $args->getSubject()->Request();
        $controllerName = $request->getControllerName();
        $repositoryName = $request->get('_repositoryClass');
        if ($controllerName == 'Config' && $repositoryName == 'shop') {
            $action = $request->getActionName();
            $lengowDatabase = new Shopware_Plugins_Backend_Lengow_Bootstrap_Database();
            $data = $request->getPost();
            // If new shop, get last entity put in db
            if ($action == 'saveValues') {
                $shop = self::getEntityManager()->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array(), array('id' => 'DESC'));
                $lengowDatabase->addLengowColumns(array($shop->getId()));
            } elseif ($action == 'deleteValues') {
                $shopId = isset($data['id']) ? $data['id'] : null;
                if (!empty($shopId)) {
                    $lengowDatabase->removeLengowColumn(array($shopId));
                }
            }
        }
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
     * Load Lengow icon
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchBackendIndex(Enlight_Controller_ActionEventArgs $args)
    {
        $this->registerMyTemplateDir();
        $ctrl = $args->getSubject();
        $view = $ctrl->View();
        $view->extendsTemplate('backend/lengow/resources/lengow-template.tpl');
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
