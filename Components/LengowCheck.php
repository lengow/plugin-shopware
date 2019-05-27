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
 * @subpackage  Components
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Lengow Check Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowCheck
{
    /**
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowTranslation Lengow translation instance
     */
    protected $locale;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->locale = new Shopware_Plugins_Backend_Lengow_Components_LengowTranslation();
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
     * @return string
     */
    public function getCheckList()
    {
        $checklist = array();
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/curl_message'),
            'help' => $this->locale->t('toolbox/index/curl_help'),
            'help_link' => $this->locale->t('toolbox/index/curl_help_link'),
            'help_label' => $this->locale->t('toolbox/index/curl_help_label'),
            'new_tab' => true,
            'state' => (int)self::isCurlActivated()
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/simple_xml_message'),
            'help' => $this->locale->t('toolbox/index/simple_xml_help'),
            'help_link' => $this->locale->t('toolbox/index/simple_xml_help_link'),
            'help_label' => $this->locale->t('toolbox/index/simple_xml_help_label'),
            'new_tab' => true,
            'state' => (int)self::isSimpleXMLActivated()
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/json_php_message'),
            'help' => $this->locale->t('toolbox/index/json_php_help'),
            'help_link' => $this->locale->t('toolbox/index/json_php_help_link'),
            'help_label' => $this->locale->t('toolbox/index/json_php_help_label'),
            'new_tab' => true,
            'state' => (int)self::isJsonActivated()
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/checksum_message'),
            'help' => $this->locale->t('toolbox/index/checksum_help'),
            'help_link' => 'checksum.php',
            'new_tab' => false,
            'help_label' => $this->locale->t('toolbox/index/checksum_help_label'),
            'state' => (int)self::getFileModified()
        );
        return $this->getAdminContent($checklist);
    }

    /**
     * Get array of requirements and their status
     *
     * @return string
     */
    public function getGlobalInformation()
    {
        $checklist = array();
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/shopware_version'),
            'message' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopwareVersion()
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/plugin_version'),
            'message' => Shopware()->Plugins()->Backend()->Lengow()->getVersion()
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/ip_server'),
            'message' => $_SERVER['SERVER_ADDR']
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/ip_enabled'),
            'state' => (int)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowIpEnabled')
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/ip_authorized'),
            'message' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowAuthorizedIp')
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/preprod_disabled'),
            'state' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowImportPreprodEnabled'
            ) ? 0 : 1
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
        $fileName = $pluginPath . 'Toolbox' . DIRECTORY_SEPARATOR . 'checkmd5.csv';
        if (file_exists($fileName)) {
            if (($file = fopen($fileName, "r")) !== false) {
                while (($data = fgetcsv($file, 1000, "|")) !== false) {
                    $filePath = $pluginPath . $data[0];
                    $fileMd = md5_file($filePath);
                    if ($fileMd !== $data[1]) {
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
     * @return string
     */
    public function getImportInformation()
    {
        $lastImport = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLastImport();
        $lastImportDate = (
        $lastImport['timestamp'] == 'none'
            ? $this->locale->t('toolbox/index/last_import_none')
            : date('Y-m-d H:i:s', $lastImport['timestamp'])
        );
        if ($lastImport['type'] == 'none') {
            $lastImportType = $this->locale->t('toolbox/index/last_import_none');
        } elseif ($lastImport['type'] == 'cron') {
            $lastImportType = $this->locale->t('toolbox/index/last_import_cron');
        } else {
            $lastImportType = $this->locale->t('toolbox/index/last_import_manual');
        }
        if (Shopware_Plugins_Backend_Lengow_Components_LengowImport::isInProcess()) {
            $importInProgress = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                'toolbox.index.rest_time_to_import',
                null,
                array('rest_time' => Shopware_Plugins_Backend_Lengow_Components_LengowImport::restTimeToImport())
            );
        } else {
            $importInProgress = $this->locale->t('toolbox/index/no_import');
        }
        $checklist = array();
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/global_token'),
            'message' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowGlobalToken')
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/url_import'),
            'message' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getImportUrl()
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/import_in_progress'),
            'message' => $importInProgress
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/shop_last_import'),
            'message' => $lastImportDate
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/shop_type_import'),
            'message' => $lastImportType
        );
        return $this->getAdminContent($checklist);
    }

    /**
     * Get array of requirements and their status
     *
     * @param \Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return string
     */
    public function getInformationByStore($shop)
    {
        $lengowExport = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, array());
        $lastExport = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowLastExport',
            $shop
        );
        if (is_null($lastExport) || $lastExport == '' || $lastExport == 0) {
            $lastExport = $this->locale->t('toolbox/index/last_import_none');
        }
        $checklist = array();
        $shopDomain = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopUrl($shop);
        $checklist[] = array(
            'header' => $shop->getName() . ' (' . $shop->getId() . ')' . ' - ' . $shopDomain
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/shop_active'),
            'state' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowShopActive',
                $shop
            )
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/shop_catalogs_id'),
            'message' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowCatalogId',
                $shop
            )
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/shop_product_total'),
            'message' => $lengowExport->getTotalProducts()
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/shop_product_exported'),
            'message' => $lengowExport->getExportedProducts()
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/shop_export_token'),
            'message' => Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowShopToken',
                $shop
            )
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/url_export'),
            'message' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::getExportUrl($shop)
        );
        $checklist[] = array(
            'title' => $this->locale->t('toolbox/index/shop_last_export'),
            'message' => $lastExport
        );
        return $this->getAdminContent($checklist);
    }

    /**
     * Get files checksum
     *
     * @return string
     */
    public function checkFileMd5()
    {
        $checklist = array();
        $pluginPath = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowFolder();
        $fileName = $pluginPath . 'Toolbox' . DIRECTORY_SEPARATOR . 'checkmd5.csv';
        $html = '<h3><i class="fa fa-commenting"></i> ' . $this->locale->t('toolbox/checksum/summary') . '</h3>';
        $fileCounter = 0;
        if (file_exists($fileName)) {
            $fileErrors = array();
            $fileDeletes = array();
            if (($file = fopen($fileName, "r")) !== false) {
                while (($data = fgetcsv($file, 1000, "|")) !== false) {
                    $fileCounter++;
                    $filePath = $pluginPath . $data[0];
                    if (file_exists($filePath)) {
                        $fileMd = md5_file($filePath);
                        if ($fileMd !== $data[1]) {
                            $fileErrors[] = array(
                                'title' => $filePath,
                                'state' => 0
                            );
                        }
                    } else {
                        $fileDeletes[] = array(
                            'title' => $filePath,
                            'state' => 0
                        );
                    }
                }
                fclose($file);
            }
            $totalFileInError = count($fileErrors);
            $totalFileDeleted = count($fileDeletes);
            $checklist[] = array(
                'title' => $this->locale->t(
                    'toolbox/checksum/file_checked',
                    array('nb_file' => $fileCounter)
                ),
                'state' => 1
            );
            $checklist[] = array(
                'title' => $this->locale->t(
                    'toolbox/checksum/file_modified',
                    array('nb_file' => count($fileErrors))
                ),
                'state' => (count($fileErrors) > 0 ? 0 : 1)
            );
            $checklist[] = array(
                'title' => $this->locale->t(
                    'toolbox/checksum/file_deleted',
                    array('nb_file' => count($fileDeletes))
                ),
                'state' => (count($fileDeletes) > 0 ? 0 : 1)
            );
            $html .= $this->getAdminContent($checklist);
            if ($totalFileInError > 0) {
                $html .= '<h3><i class="fa fa-list"></i> '
                    . $this->locale->t('toolbox/checksum/list_modified_file') . '</h3>';
                $html .= $this->getAdminContent($fileErrors);
            }
            if ($totalFileDeleted > 0) {
                $html .= '<h3><i class="fa fa-list"></i> '
                    . $this->locale->t('toolbox/checksum/list_deleted_file') . '</h3>';
                $html .= $this->getAdminContent($fileDeletes);
            }
        } else {
            $checklist[] = array(
                'title' => $this->locale->t('toolbox/checksum/file_not_exists'),
                'state' => 0
            );
            $html .= $this->getAdminContent($checklist);
        }
        return $html;
    }

    /**
     * Get toolbox files status (if files have been deleted/edited or ok)
     *
     * @param array $checklist list of elements to generate
     *
     * @return string
     */
    private function getAdminContent($checklist = array())
    {
        if (empty($checklist)) {
            return '';
        }
        $out = '<table class="table" cellpadding="0" cellspacing="0">';
        foreach ($checklist as $check) {
            $out .= '<tr>';
            if (isset($check['header'])) {
                $out .= '<td colspan="2" align="center" style="border:0"><h4>' . $check['header'] . '</h4></td>';
            } else {
                $out .= '<td><b>' . $check['title'] . '</b></td>';
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
                                    . $check['help_link'] . '">' . $check['help_label'] . '</a>';
                            }
                            $out .= '</p></td></tr>';
                        }
                    }
                } else {
                    $out .= '<td align="right"><b>' . $check['message'] . '</b></td>';
                }
            }
            $out .= '</tr>';
        }
        $out .= '</table>';
        return $out;
    }
}
