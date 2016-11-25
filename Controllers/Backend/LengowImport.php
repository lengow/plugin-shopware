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
                'data'    => $status
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
                'data'    => $data
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
            'Logs/logs-'.date('Y-m-d').'.txt';
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
                'data'    => $data
            )
        );
    }
}
