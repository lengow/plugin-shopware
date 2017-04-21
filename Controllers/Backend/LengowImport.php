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
 * @category    Lengow
 * @package     Lengow
 * @subpackage  Controllers
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Backend Lengow Import Controller
 */
class Shopware_Controllers_Backend_LengowImport extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Check if Lengow import setting is enabled
     */
    public function getImportSettingStatusAction()
    {
        $status = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowEnableImport');
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $status
            )
        );
    }

    /**
     * Get translations and create labels displayed in import panel
     * Used despite Shopware translation tool because of parameters which are not settable
     */
    public function getPanelContentsAction()
    {
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();
        $nbDays = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowImportDays');
        $data['importDescription'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
            'order/panel/description',
            $locale,
            array('nb_days' => $nbDays)
        );
        // Get last import date
        $lastImport = Shopware_Plugins_Backend_Lengow_Components_LengowImport::getLastImport();
        $data['lastImport'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
            'order/panel/last_import',
            $locale,
            array('import_date' => $lastImport)
        );
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $data
            )
        );
    }

    /**
     * Execute import process and get result/errors
     */
    public function launchImportProcessAction()
    {
        $import = new Shopware_Plugins_Backend_Lengow_Components_LengowImport();
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();
        $result = $import->exec();
        $data = array();
        $success = !empty($result['error']) ? false : true;
        // Get number of processed orders. If equals zero, display "no_notification" message
        $totalOrder = $result['order_error'] + $result['order_new'];
        // Retrieve log of the day
        $logUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getPathPlugin() .
            'Logs/logs-' . date('Y-m-d') . '.txt';
        // If error during import process
        if (!$success) {
            $data['error'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'order/panel/order_import_failed',
                $locale,
                array('log_url' => $logUrl)
            );
        } elseif ($totalOrder > 0) {
            foreach ($result as $key => $nbOrders) {
                switch ($key) {
                    case 'error':
                        continue;
                        break;
                    case 'order_error':
                        $params = array('nb_order' => $nbOrders);
                        // If more than one error, display link to log file
                        if ($nbOrders > 0) {
                            $translationKey = 'order/panel/order_error_link';
                            $params['log_url'] = $logUrl;
                        } else {
                            $translationKey = 'order/panel/no_error';
                        }
                        $data['order_error'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                            $translationKey,
                            $locale,
                            $params
                        );
                        break;
                    case 'order_new':
                        $data['order_new'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                            'order/panel/order_new',
                            $locale,
                            array('nb_order' => $nbOrders)
                        );
                        break;
                }
            }
        } else {
            $data['no_notification'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'order/panel/no_notification',
                $locale
            );
        }
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $data
            )
        );
    }
}
