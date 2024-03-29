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

use Shopware\Components\Model\ModelManager;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_Event_EventArgs as EventArgs;
use Shopware_Plugins_Backend_Lengow_Bootstrap_Database as LengowBootstrapDatabase;
use Shopware_Plugins_Backend_Lengow_Bootstrap_Form as LengowBootstrapForm;
use Shopware_Plugins_Backend_Lengow_Components_LengowEvent as LengowEvent;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;

/**
 * Bootstrap Class
 */
class Shopware_Plugins_Backend_Lengow_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Returns plugin version
     *
     * @return string|false
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if ($info) {
            return $info['currentVersion'];
        }
        return false;
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
            'changes' => $info['changes'],
        );
    }

    /**
     * Install plugin method
     *
     * @throws Exception
     *
     * @return array
     */
    public function install()
    {
        self::log('log/install/start');
        if (!$this->assertMinimumVersion('5.5.0')) {
            throw new \RuntimeException('At least Shopware 5.5.0 is required');
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
                'class' => 'lengow--icon',
            )
        );
        self::log('log/install/add_menu');
        $lengowForm = new LengowBootstrapForm();
        $lengowForm->createConfig($this->Form());
        $lengowForm->removeOldSettings();
        $lengowDatabase = new LengowBootstrapDatabase();
        $lengowDatabase->updateSchema();
        $lengowDatabase->createCustomModels();
        $lengowDatabase->updateCustomModels();
        $lengowDatabase->setLengowSettings();
        $lengowDatabase->updateOrderAttribute();
        $lengowDatabase->addLengowTechnicalErrorStatus();
        $this->createLengowPayment();
        $this->registerMyEvents();
        $this->registerCustomModels();
        $this->Plugin()->setActive(true);
        self::log('log/install/end');
        return array(
            'success' => true,
            'invalidateCache' => array('backend'),
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
        $newVersion  = $this->getVersion();
        self::log('log/update/start', array('old_version' => $version, 'new_version' => $newVersion));
        $lengowForm = new LengowBootstrapForm();
        $lengowForm->createConfig($this->Form(), $version);
        $lengowForm->removeOldSettings();
        $lengowDatabase = new LengowBootstrapDatabase();
        $lengowDatabase->updateSchema();
        $lengowDatabase->createCustomModels();
        $lengowDatabase->updateCustomModels();
        $lengowDatabase->setLengowSettings();
        $lengowDatabase->updateOrderAttribute();
        $lengowDatabase->addLengowTechnicalErrorStatus();
        $this->createLengowPayment();
        $this->registerMyEvents();
        $this->registerCustomModels();
        self::log('log/update/end', array('old_version' => $version, 'new_version' => $newVersion));
        return array(
            'success' => true,
            'invalidateCache' => array('backend'),
        );
    }

    /**
     * Get Shopware entity manager
     *
     * @return ModelManager
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
        // remove custom attributes
        $lengowDatabase = new LengowBootstrapDatabase();
        $lengowDatabase->removeAllLengowColumns();
        $lengowDatabase->removeCustomModels();
        self::log('log/uninstall/end');
        return true;
    }

    /**
     * Creates and save the payment row
     */
    private function createLengowPayment()
    {
        $payment = LengowMain::getLengowPayment();
        if ($payment === null) {
            $this->createPayment(
                array(
                    'active' => 0,
                    'name' => 'lengow',
                    'description' => 'Lengow',
                    'additionalDescription' => 'Default payment for Lengow orders',
                )
            );
            self::log('log/install/add_payment');
        }
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
        // main controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Lengow',
            'getDefaultControllerPath'
        );
        // connection controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowConnection',
            'onGetConnectionControllerPath'
        );
        // dashboard controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowDashboard',
            'onGetDashboardControllerPath'
        );
        // export controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowExport',
            'onGetExportControllerPath'
        );
        // import controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowImport',
            'onGetImportControllerPath'
        );
        // log controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowLogs',
            'onGetLogControllerPath'
        );
        // help controller
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LengowHelp',
            'onGetHelpControllerPath'
        );
        // backend events
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Index',
            'onPostDispatchBackendIndex'
        );
        // basic settings events
        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch_Backend_Config',
            'onPreDispatchBackendConfig'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Config',
            'onPostDispatchBackendConfig'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch_Backend_Order',
            'onPreDispatchBackendOrder'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Order',
            'onPostDispatchBackendOrder'
        );
        // api events
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Api_Orders',
            'onApiOrderPostDispatch'
        );
        // front events
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout',
            'onFrontendCheckoutPostDispatch'
        );
    }

    /**
     * Returns the path to Lengow connection controller
     *
     * @return string
     */
    public function onGetConnectionControllerPath()
    {
        return $this->Path() . 'Controllers/Backend/LengowConnection.php';
    }

    /**
     * Returns the path to Lengow dashboard controller
     *
     * @return string
     */
    public function onGetDashboardControllerPath()
    {
        return $this->Path() . 'Controllers/Backend/LengowDashboard.php';
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
     * Returns the path to Lengow import controller
     *
     * @return string
     */
    public function onGetImportControllerPath()
    {
        return $this->Path() . 'Controllers/Backend/LengowImport.php';
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
     * Load Lengow icon. Triggered when Shopware backend is loaded
     *
     * @param ActionEventArgs $args Shopware Enlight Controller Action instance
     */
    public function onPostDispatchBackendIndex(ActionEventArgs $args)
    {
        $this->registerMyTemplateDir();
        $ctrl = $args->getSubject();
        $view = $ctrl->View();
        $view->extendsTemplate('backend/lengow/resources/lengow-template.tpl');
    }

    /**
     * Listen to basic settings changes. Log of Lengow settings when they were updated
     *
     * @param EventArgs $args Shopware Enlight Controller Action instance
     */
    public function onPreDispatchBackendConfig(EventArgs $args)
    {
        LengowEvent::onPreDispatchBackendConfig($args);
    }

    /**
     * Listen to basic settings changes. Add/remove lengow column from s_articles_attributes
     *
     * @param EventArgs $args Shopware Enlight Controller Action instance
     */
    public function onPostDispatchBackendConfig(EventArgs $args)
    {
        LengowEvent::onPostDispatchBackendConfig($args);
    }

    /**
     * Listen to order changes before save
     *
     * @param EventArgs $args Shopware Enlight Controller Action instance
     */
    public function onPreDispatchBackendOrder(EventArgs $args)
    {
        LengowEvent::onPreDispatchBackendOrder($args);
    }

    /**
     * Listen to order changes after save / send call action if necessary
     *
     * @param EventArgs $args Shopware Enlight Controller Action instance
     */
    public function onPostDispatchBackendOrder(EventArgs $args)
    {
        LengowEvent::onPostDispatchBackendOrder($args);
    }

    /**
     * Listen to api orders changes after save / send call action if necessary
     *
     * @param EventArgs $args
     */
    public function onApiOrderPostDispatch(EventArgs $args)
    {
        LengowEvent::onApiOrderPostDispatch($args);
    }

    /**
     * Adding simple tracker Lengow on footer when order is confirmed
     *
     * @param EventArgs $args
     */
    public function onFrontendCheckoutPostDispatch(EventArgs $args)
    {
        LengowEvent::onFrontendCheckoutPostDispatch($args);
    }

    /**
     * Log when installing / uninstalling the plugin
     *
     * @param string $key translation key
     * @param array $params parameters to put in the translations
     */
    public static function log($key, $params = array())
    {
        LengowMain::log(LengowLog::CODE_INSTALL, LengowMain::setLogMessage($key, $params));
    }
}
