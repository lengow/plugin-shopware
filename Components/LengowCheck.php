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
class Shopware_Plugins_Backend_Lengow_Components_LengowCheck
{
    /**
     * @var $locale Shopware_Plugins_Backend_Lengow_Components_LengowTranslation Translation
     */
    protected $locale;

    public function __construct()
    {
        $this->locale = new Shopware_Plugins_Backend_Lengow_Components_LengowTranslation();
    }

    /**
    * Check API authentication
    *
    * @param $shop Shopware\Models\Shop\Shop
    *
    * @return boolean
    */
    public static function isValidAuth($shop)
    {
        if (!self::isCurlActivated()) {
            return false;
        }
        $account_id = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowAccountId',
            $shop
        );
        $connector  = new Shopware_Plugins_Backend_Lengow_Components_LengowConnector(
            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowAccessToken', $shop),
            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowSecretToken', $shop)
        );
        $result = $connector->connect();
        if (isset($result['token']) && $account_id != 0 && is_integer($account_id)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if PHP Curl is activated
     *
     * @return boolean
     */
    public static function isCurlActivated()
    {
        return function_exists('curl_version');
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return boolean
     */
    public static function isSimpleXMLActivated()
    {
        return function_exists('simplexml_load_file');
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return boolean
     */
    public static function isJsonActivated()
    {
        return function_exists('json_decode');
    }

    /**
     * Get array of requirements and their status
     *
     * @return mixed
     */
    public function getCheckList()
    {
        $checklist = array();
        $checklist[] = array(
            'title'         => $this->locale->t('toolbox/index/curl_message'),
            'help'          => $this->locale->t('toolbox/index/curl_help'),
            'help_link'     => $this->locale->t('toolbox/index/curl_help_link'),
            'help_label'    => $this->locale->t('toolbox/index/curl_help_label'),
            'new_tab'       => true,
            'state'         => (int)self::isCurlActivated()
        );
        $checklist[] = array(
            'title'         => $this->locale->t('toolbox/index/simple_xml_message'),
            'help'          => $this->locale->t('toolbox/index/simple_xml_help'),
            'help_link'     => $this->locale->t('toolbox/index/simple_xml_help_link'),
            'help_label'    => $this->locale->t('toolbox/index/simple_xml_help_label'),
            'new_tab'       => true,
            'state'         => (int)self::isSimpleXMLActivated()
        );
        $checklist[] = array(
            'title'         => $this->locale->t('toolbox/index/json_php_message'),
            'help'          => $this->locale->t('toolbox/index/json_php_help'),
            'help_link'     => $this->locale->t('toolbox/index/json_php_help_link'),
            'help_label'    => $this->locale->t('toolbox/index/json_php_help_label'),
            'new_tab'       => true,
            'state'         => (int)self::isJsonActivated()
        );
        $checklist[] = array(
            'title'         => $this->locale->t('toolbox/index/checksum_message'),
            'help'          => $this->locale->t('toolbox/index/checksum_help'),
            'help_link'     => 'checksum.php',
            'new_tab'       => false,
            'help_label'    => $this->locale->t('toolbox/index/checksum_help_label'),
            'state'         => (int)self::getFileModified()
        );
        return $this->getAdminContent($checklist);
    }

    /**
     * Get array of requirements and their status
     *
     * @return mixed
     */
    public function getGlobalInformation()
    {
        $checklist = array();
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/shopware_version'),
            'message'   => Shopware::VERSION
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/plugin_version'),
            'message'   => Shopware()->Plugins()->Backend()->Lengow()->getVersion()
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/ip_server'),
            'message'   => $_SERVER['SERVER_ADDR']
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/ip_authorized'),
            'message'   => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowAuthorizedIp')
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/preprod_disabled'),
            'state'     => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowImportPreprodEnabled') ? 0 : 1
        );
        return $this->getAdminContent($checklist);
    }

    /**
     * Get checksum errors
     *
     * @return boolean
     */
    public static function getFileModified()
    {
        $pluginPath = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowFolder();
        $file_name = $pluginPath.'Toolbox'.DIRECTORY_SEPARATOR.'checkmd5.csv';
        if (file_exists($file_name)) {
            if (($file = fopen($file_name, "r")) !== false) {
                while (($data = fgetcsv($file, 1000, "|")) !== false) {
                    $file_path = $pluginPath.$data[0];
                    $file_md5 = md5_file($file_path);
                    if ($file_md5 !== $data[1]) {
                        return false;
                    }
                }
                fclose($file);
                return true;
            }
        }
        return false;
    }

    /**
     * Get array of requirements and their status
     *
     * @return mixed
     */
    public function getImportInformation()
    {
        $last_import = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLastImport();
        $last_import_date = (
        $last_import['timestamp'] == 'none'
            ? $this->locale->t('toolbox/index/last_import_none')
            : date('Y-m-d H:i:s', $last_import['timestamp'])
        );
        if ($last_import['type'] == 'none') {
            $last_import_type = $this->locale->t('toolbox/index/last_import_none');
        } elseif ($last_import['type'] == 'cron') {
            $last_import_type = $this->locale->t('toolbox/index/last_import_cron');
        } else {
            $last_import_type = $this->locale->t('toolbox/index/last_import_manual');
        }
        if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::isInProcess()) {
            $import_in_progress = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'toolbox.index.rest_time_to_import', null, array(
                    'rest_time' => Shopware_Plugins_Backend_Lengow_Components_LengowImport::restTimeToImport()
            ));
        } else {
            $import_in_progress = $this->locale->t('toolbox/index/no_import');
        }
        $checklist = array();
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/global_token'),
            'message'   => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowGlobalToken')
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/url_import'),
            'message'   => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getImportUrl()
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/import_in_progress'),
            'message'   => $import_in_progress
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/shop_last_import'),
            'message'   => $last_import_date
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/shop_type_import'),
            'message'   => $last_import_type
        );
        return $this->getAdminContent($checklist);
    }

    /**
     * Get array of requirements and their status
     *
     * @param \Shopware\Models\Shop\Shop $shop
     *
     * @return mixed
     */
    public function getInformationByStore($shop)
    {
        $lengowExport = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, array());
        $lastExport = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowLastExport', $shop);
        if (is_null($lastExport) || $lastExport == '') {
            $lastExport = $this->locale->t('toolbox/index/last_import_none');
        }
        $checklist = array();
        $shopDomain = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopUrl($shop);
        $checklist[] = array(
            'header'     => $shop->getName().' ('.$shop->getId().')'.' - ' . $shopDomain
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/shop_active'),
            'state'     => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowShopActive', $shop)
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/shop_product_total'),
            'message'   => $lengowExport->getTotalProducts()
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/shop_product_exported'),
            'message'   => $lengowExport->getExportedProducts()
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/shop_export_token'),
            'message'   => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowShopToken', $shop)
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/url_export'),
            'message'   => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getExportUrl($shop)
        );
        $checklist[] = array(
            'title'     => $this->locale->t('toolbox/index/shop_last_export'),
            'message'   => $lastExport
        );
        return $this->getAdminContent($checklist);
    }

    /**
     * Get files checksum
     *
     * @return mixed
     */
    public function checkFileMd5()
    {
        $checklist = array();
        $pluginPath = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowFolder();
        $file_name = $pluginPath.'Toolbox'.DIRECTORY_SEPARATOR.'checkmd5.csv';
        $html = '<h3><i class="fa fa-commenting"></i> '.$this->locale->t('toolbox/checksum/summary').'</h3>';
        $file_counter = 0;
        if (file_exists($file_name)) {
            $file_errors = array();
            $file_deletes = array();
            if (($file = fopen($file_name, "r")) !== false) {
                while (($data = fgetcsv($file, 1000, "|")) !== false) {
                    $file_counter++;
                    $file_path = $pluginPath.$data[0];
                    if (file_exists($file_path)) {
                        $file_md5 = md5_file($file_path);
                        if ($file_md5 !== $data[1]) {
                            $file_errors[] = array(
                                'title' => $file_path,
                                'state' => 0
                            );
                        }
                    } else {
                        $file_deletes[] = array(
                            'title' => $file_path,
                            'state' => 0
                        );
                    }
                }
                fclose($file);
            }
            $totalFileInError = count($file_errors);
            $totalFileDeleted = count($file_deletes);
            $checklist[] = array(
                'title' => $this->locale->t('toolbox/checksum/file_checked', array(
                    'nb_file' => $file_counter
                )),
                'state' => 1
            );
            $checklist[] = array(
                'title' => $this->locale->t('toolbox/checksum/file_modified', array(
                    'nb_file' => count($file_errors)
                )),
                'state' => (count($file_errors) > 0 ? 0 : 1)
            );
            $checklist[] = array(
                'title' => $this->locale->t('toolbox/checksum/file_deleted', array(
                    'nb_file' => count($file_deletes)
                )),
                'state' => (count($file_deletes) > 0 ? 0 : 1)
            );
            $html.= $this->getAdminContent($checklist);
            if ($totalFileInError > 0) {
                $html.= '<h3><i class="fa fa-list"></i> '
                    .$this->locale->t('toolbox/checksum/list_modified_file').'</h3>';
                $html.= $this->getAdminContent($file_errors);
            }
            if ($totalFileDeleted > 0) {
                $html.= '<h3><i class="fa fa-list"></i> '
                    .$this->locale->t('toolbox/checksum/list_deleted_file').'</h3>';
                $html.= $this->getAdminContent($file_deletes);
            }
        } else {
            $checklist[] = array(
                'title' => $this->locale->t('toolbox/checksum/file_not_exists'),
                'state' => 0
            );
            $html.= $this->getAdminContent($checklist);
        }
        return $html;
    }

    /**
     * Get toolbox files status (if files have been deleted/edited or ok)
     * @param array $checklist List of elements to generate
     * @return null|string Html
     */
    private function getAdminContent($checklist = array())
    {
        if (empty($checklist)) {
            return null;
        }
        $out = '<table class="table" cellpadding="0" cellspacing="0">';
        foreach ($checklist as $check) {
            $out .= '<tr>';
            if (isset($check['header'])) {
                $out .= '<td colspan="2" align="center" style="border:0"><h4>'.$check['header'].'</h4></td>';
            } else {
                $out .= '<td><b>'.$check['title'].'</b></td>';
                if (isset($check['state'])) {
                    if ($check['state'] == 1) {
                        $out .= '<td align="right"><i class="fa fa-check lengow-green"></i></td>';
                    } else {
                        $out .= '<td align="right"><i class="fa fa-times lengow-red"></i></td>';
                    }
                    if ($check['state'] === 0) {
                        if (isset($check['help']) && isset($check['help_link']) && isset($check['help_label'])) {
                            $out .= '<tr><td colspan="2"><p>' . $check['help'];
                            if (array_key_exists('help_link', $check) && $check['help_link'] != '') {
                                $newTab = $check['new_tab'] !== false ? 'target="_blank"' : '';
                                $out .= '<br /><a ' . $newTab . ' href="'
                                    .$check['help_link'].'">'.$check['help_label'].'</a>';
                            }
                            $out .= '</p></td></tr>';
                        }
                    }
                } else {
                    $out .= '<td align="right"><b>'.$check['message'].'</b></td>';
                }
            }
            $out .= '</tr>';
        }
        $out .= '</table>';
        return $out;
    }
}
