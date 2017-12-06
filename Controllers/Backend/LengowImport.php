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
        $result = $import->exec();
        $messages = $this->loadMessage($result);
        $data = array();
        $data['messages'] = join( '<br/>', $messages);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $data
            )
        );
    }

    /**
     * Generate message array (new, update and errors)
     *
     * @param array $return
     *
     * @return array
     */
    public function loadMessage($return)
    {
        $messages = array();
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();
        // if global error return this
        if (isset($return['error'][0])) {
            $messages['error'] =  Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                $return['error'][0],
                $locale
            );
            return $messages;
        }
        if (isset($return['order_new']) && $return['order_new'] > 0) {
            $messages['order_new'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'lengow_log/error/nb_order_imported',
                $locale,
                array('nb_order' => (int)$return['order_new'])
            );
        }
        if (isset($return['order_update']) && $return['order_update'] > 0) {
            $messages['order_update'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'lengow_log/error/nb_order_updated',
                $locale,
                array('nb_order' => (int)$return['order_update'])
            );
        }
        if (isset($return['order_error']) && $return['order_error'] > 0) {
            $logUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getPathPlugin() .
                'Logs/logs-' . date('Y-m-d') . '.txt';
            $messages['order_error'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'order/panel/order_error_link',
                $locale,
                array(
                    'nb_order' => (int)$return['order_error'],
                    'log_url' => $logUrl
                )
            );
        }
        if (count($messages) == 0) {
            $messages['no_notification'] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'lengow_log/error/no_notification',
                $locale
            );
        }
        if (isset($return['error'])) {
            foreach ($return['error'] as $shopId => $values) {
                if ((int)$shopId > 0) {
                    $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
                    $shop = $em->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('id' => (int)$shopId));
                    $shopName = !is_null($shop) ? $shop->getName() . ' : ' :  '';
                    $error = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($values, $locale);
                    $messages[] = $shopName . $error;
                }
            }
        }
        return $messages;
    }
}
