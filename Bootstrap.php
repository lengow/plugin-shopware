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
 * @category   Lengow
 * @package    Lengow
 * @author     Team module <team-module@lengow.com>
 * @copyright  2017 Lengow SAS
 * @license    https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Bootstrap Class
 */
class Shopware_Plugins_Backend_Lengow_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Returns plugin version
     *
     * @throws Exception
     *
     * @return string
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * Returns plugin info
     *
     * @return array
     */
    public function getInfo()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        return array(
            'version' => $this->getVersion(),
            'label' => $info['label'],
            'source' => $this->getSource(),
            'author' => $info['author'],
            'copyright' => $info['copyright'],
            'description' => $info['description'],
            'support' => $info['support_mail'],
            'link' => $info['link'],
            'changes' => $info['changes']
        );
    }

    /**
     * Install plugin method
     *
     * @return array
     */
    public function install()
    {
        self::log('log/install/start');
        if (!$this->assertMinimumVersion('4.3.0')) {
            throw new \RuntimeException('At least Shopware 4.3.0 is required');
        }
        $this->registerController('Backend', 'Lengow');
        $this->registerController('Frontend', 'LengowController');
        $this->createMenuItem(
            array(
                'label' => 'Lengow',
                'controller' => 'Lengow',
                'action' => 'Index',
                'active' => 1,
                'parent' => $this->Menu()->findOneBy(array('label' => 'Einstellungen')),
                'class' => 'lengow--icon'
            )
        );
        self::log('log/install/add_menu');
        $lengowForm = new Shopware_Plugins_Backend_Lengow_Bootstrap_Form();
        $lengowForm->createConfig($this->Form());
        $lengowDatabase = new Shopware_Plugins_Backend_Lengow_Bootstrap_Database();
        $lengowDatabase->updateSchema();
        $lengowDatabase->createCustomModels();
        $lengowDatabase->setLengowSettings();
        $lengowDatabase->updateOrderAttribute();
        $this->createLengowPayment();
        $this->registerMyEvents();
        $this->registerCustomModels();
        $this->Plugin()->setActive(true);
        self::log('log/install/end');
        return array(
            'success' => true,
            'invalidateCache' => array('backend')
        );
    }

    /**
     * Register custom models after init
     */
    public function afterInit()
    {
        $this->registerCustomModels();
    }

    /**
     * Update plugin method
     *
     * @param string $version version number
     *
     * @return array
     */
    public function update($version)
    {
        $lengowForm = new Shopware_Plugins_Backend_Lengow_Bootstrap_Form();
        $lengowForm->createConfig($this->Form());
        $lengowDatabase = new Shopware_Plugins_Backend_Lengow_Bootstrap_Database();
        $lengowDatabase->updateSchema();
        $lengowDatabase->createCustomModels();
        $lengowDatabase->updateCustomModels($version);
        $lengowDatabase->setLengowSettings();
        $lengowDatabase->updateOrderAttribute();
        $this->createLengowPayment();
        $this->registerMyEvents();
        $this->registerCustomModels();
        return array(
            'success' => true,
            'invalidateCache' => array('backend')
        );
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
     * Uninstall plugin method
     *
     * @return boolean
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
        Shopware()->Template()->addTemplateDir($this->Path() . 'Views');
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
        // Order controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowOrder',
            'onGetOrderControllerPath'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowOrderLegacy',
            'onGetOrderControllerPath'
        );
        // Backend events
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Index',
            'onPostDispatchBackendIndex'
        );
        // Basic settings events
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Config',
            'onPostDispatchBackendConfig'
        );
        // Order events
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order',
            'onOrderPostDispatch'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch_Backend_Order',
            'onPreDispatchBackendOrder'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Order',
            'onPostDispatchBackendOrder'
        );
    }

    /**
     * Creates and save the payment row
     */
    private function createLengowPayment()
    {
        $payment = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowPayment();
        if (is_null($payment)) {
            $this->createPayment(
                array(
                    'active' => 0,
                    'name' => 'lengow',
                    'description' => 'Lengow',
                    'additionalDescription' => 'Default payment for Lengow orders'
                )
            );
            self::log('log/install/add_payment');
        }
    }

    /**
     * Returns the path to Lengow home controller
     *
     * @return string
     */
    public function onGetHomeControllerPath()
    {
        return $this->Path() . 'Controllers/Backend/LengowHome.php';
    }

    /**
     * Returns the path to Lengow export controller
     *
     * @return string
     */
    public function onGetExportControllerPath()
    {
        return $this->Path() . 'Controllers/Backend/LengowExport.php';
    }

    /**
     * Return the path to Lengow import controller
     *
     * @return string
     */
    public function onGetImportControllerPath()
    {
        return $this->Path() . 'Controllers/Backend/LengowImport.php';
    }

    /**
     * Return the path to Lengow sync controller
     *
     * @return string
     */
    public function onGetSyncControllerPath()
    {
        return $this->Path() . 'Controllers/Backend/LengowSync.php';
    }

    /**
     * Returns the path to Lengow log controller
     *
     * @return string
     */
    public function onGetLogControllerPath()
    {
        return $this->Path() . 'Controllers/Backend/LengowLogs.php';
    }

    /**
     * Returns the path to Lengow help controller
     *
     * @return string
     */
    public function onGetHelpControllerPath()
    {
        return $this->Path() . 'Controllers/Backend/LengowHelp.php';
    }

    /**
     * Returns the path to Lengow order controller
     *
     * @return string
     */
    public function onGetOrderControllerPath()
    {
        if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.2.0')) {
            return $this->Path() . 'Controllers/Backend/LengowOrder.php';
        } else {
            return $this->Path() . 'Controllers/Backend/LengowOrderLegacy.php';
        }
    }

    /**
     * Load Lengow icon. Triggered when Shopware backend is loaded
     *
     * @param Enlight_Controller_ActionEventArgs $args Shopware Enlight Controller Action instance
     */
    public function onPostDispatchBackendIndex(Enlight_Controller_ActionEventArgs $args)
    {
        $this->registerMyTemplateDir();
        $ctrl = $args->getSubject();
        $view = $ctrl->View();
        $view->extendsTemplate('backend/lengow/resources/lengow-template.tpl');
    }

    /**
     * Listen to basic settings changes. Add/remove lengow column from s_articles_attributes
     *
     * @param Enlight_Event_EventArgs $args Shopware Enlight Controller Action instance
     */
    public function onPostDispatchBackendConfig($args)
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowEvent::onPostDispatchBackendConfig($args);
    }

    /**
     * Listen to order changes before save
     *
     * @param Enlight_Event_EventArgs $args Shopware Enlight Controller Action instance
     */
    public function onPreDispatchBackendOrder($args)
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowEvent::onPreDispatchBackendOrder($args);
    }

    /**
     * Listen to order changes after save / send call action if necessary
     *
     * @param Enlight_Event_EventArgs $args Shopware Enlight Controller Action instance
     */
    public function onPostDispatchBackendOrder($args)
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowEvent::onPostDispatchBackendOrder($args);
    }

    /**
     * Log when installing / uninstalling the plugin
     *
     * @param string $key translation key
     * @param array $params parameters to put in the translations
     */
    public static function log($key, $params = array())
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'Install',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage($key, $params)
        );
    }

    /**
     * Listen to order details
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onOrderPostDispatch(Enlight_Event_EventArgs $args)
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowEvent::onOrderPostDispatch($args);
    }
}
