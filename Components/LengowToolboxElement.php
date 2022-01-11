<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @copyright   2021 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowImport as LengowImport;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowToolbox as LengowToolbox;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

/**
 * Lengow Toolbox Element Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowToolboxElement
{
    /* Array data for toolbox content creation */
    const DATA_HEADER = 'header';
    const DATA_TITLE = 'title';
    const DATA_STATE = 'state';
    const DATA_MESSAGE = 'message';
    const DATA_SIMPLE = 'simple';
    const DATA_HELP = 'help';
    const DATA_HELP_LINK = 'help_link';
    const DATA_HELP_LABEL = 'help_label';

    /**
     * @var LengowTranslation Lengow translation instance
     */
    protected $locale;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->locale = new LengowTranslation();
    }

    /**
     * Get array of requirements for toolbox
     *
     * @return string
     */
    public function getCheckList()
    {
        $checklistData = LengowToolbox::getData(LengowToolbox::DATA_TYPE_CHECKLIST);
        $checklist = array(
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/curl_message'),
                self::DATA_HELP => $this->locale->t('toolbox/screen/curl_help'),
                self::DATA_HELP_LINK => $this->locale->t('toolbox/screen/curl_help_link'),
                self::DATA_HELP_LABEL => $this->locale->t('toolbox/screen/curl_help_label'),
                self::DATA_STATE => (int) $checklistData[LengowToolbox::CHECKLIST_CURL_ACTIVATED],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/simple_xml_message'),
                self::DATA_HELP => $this->locale->t('toolbox/screen/simple_xml_help'),
                self::DATA_HELP_LINK => $this->locale->t('toolbox/screen/simple_xml_help_link'),
                self::DATA_HELP_LABEL => $this->locale->t('toolbox/screen/simple_xml_help_label'),
                self::DATA_STATE => (int) $checklistData[LengowToolbox::CHECKLIST_SIMPLE_XML_ACTIVATED],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/json_php_message'),
                self::DATA_HELP => $this->locale->t('toolbox/screen/json_php_help'),
                self::DATA_HELP_LINK => $this->locale->t('toolbox/screen/json_php_help_link'),
                self::DATA_HELP_LABEL => $this->locale->t('toolbox/screen/json_php_help_label'),
                self::DATA_STATE => (int) $checklistData[LengowToolbox::CHECKLIST_JSON_ACTIVATED],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/checksum_message'),
                self::DATA_HELP => $this->locale->t('toolbox/screen/checksum_help'),
                self::DATA_STATE => (int) $checklistData[LengowToolbox::CHECKLIST_MD5_SUCCESS],
            ),
        );
        return $this->getContent($checklist);
    }

    /**
     * Get all global information for toolbox
     *
     * @return string
     */
    public function getGlobalInformation()
    {

        $pluginData = LengowToolbox::getData(LengowToolbox::DATA_TYPE_PLUGIN);
        $checklist = array(
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/shopware_version'),
                self::DATA_MESSAGE => $pluginData[LengowToolbox::PLUGIN_CMS_VERSION],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/plugin_version'),
                self::DATA_MESSAGE => $pluginData[LengowToolbox::PLUGIN_VERSION],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/ip_server'),
                self::DATA_MESSAGE => $pluginData[LengowToolbox::PLUGIN_SERVER_IP],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/authorized_ip_enable'),
                self::DATA_STATE => (int) $pluginData[LengowToolbox::PLUGIN_AUTHORIZED_IP_ENABLE],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/ip_authorized'),
                self::DATA_MESSAGE => implode(', ', $pluginData[LengowToolbox::PLUGIN_AUTHORIZED_IPS]),
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/debug_disabled'),
                self::DATA_STATE => (int) $pluginData[LengowToolbox::PLUGIN_DEBUG_MODE_DISABLE],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/write_permission'),
                self::DATA_STATE => (int) $pluginData[LengowToolbox::PLUGIN_WRITE_PERMISSION],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/toolbox_url'),
                self::DATA_MESSAGE => $pluginData[LengowToolbox::PLUGIN_TOOLBOX_URL],
            ),
        );
        return $this->getContent($checklist);
    }

    /**
     * Get all import information for toolbox
     *
     * @return string
     */
    public function getImportInformation()
    {
        $synchronizationData = LengowToolbox::getData(LengowToolbox::DATA_TYPE_SYNCHRONIZATION);
        $lastSynchronization = $synchronizationData[LengowToolbox::SYNCHRONIZATION_LAST_SYNCHRONIZATION];
        if ($lastSynchronization === 0) {
            $lastImportDate = $this->locale->t('toolbox/screen/last_import_none');
            $lastImportType = $this->locale->t('toolbox/screen/last_import_none');
        } else {
            $lastImportDate = LengowMain::getDateInCorrectFormat($lastSynchronization, true);
            $lastSynchronizationType = $synchronizationData[LengowToolbox::SYNCHRONIZATION_LAST_SYNCHRONIZATION_TYPE];
            $lastImportType = $lastSynchronizationType === LengowImport::TYPE_CRON
                ? $this->locale->t('toolbox/screen/last_import_cron')
                : $this->locale->t('toolbox/screen/last_import_manual');
        }
        if ($synchronizationData[LengowToolbox::SYNCHRONIZATION_SYNCHRONIZATION_IN_PROGRESS]) {
            $importInProgress = LengowMain::decodeLogMessage(
                'toolbox/screen/rest_time_to_import',
                null,
                array('rest_time' => LengowImport::restTimeToImport())
            );
        } else {
            $importInProgress = $this->locale->t('toolbox/screen/no_import');
        }
        $checklist = array(
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/global_token'),
                self::DATA_MESSAGE => $synchronizationData[LengowToolbox::SYNCHRONIZATION_CMS_TOKEN],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/url_import'),
                self::DATA_MESSAGE => $synchronizationData[LengowToolbox::SYNCHRONIZATION_CRON_URL],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/nb_order_imported'),
                self::DATA_MESSAGE => $synchronizationData[LengowToolbox::SYNCHRONIZATION_NUMBER_ORDERS_IMPORTED],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/nb_order_to_be_sent'),
                self::DATA_MESSAGE => $synchronizationData[
                    LengowToolbox::SYNCHRONIZATION_NUMBER_ORDERS_WAITING_SHIPMENT
                ],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/nb_order_with_error'),
                self::DATA_MESSAGE => $synchronizationData[LengowToolbox::SYNCHRONIZATION_NUMBER_ORDERS_IN_ERROR],
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/import_in_progress'),
                self::DATA_MESSAGE => $importInProgress,
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/shop_last_import'),
                self::DATA_MESSAGE => $lastImportDate,
            ),
            array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/shop_type_import'),
                self::DATA_MESSAGE => $lastImportType,
            ),
        );
        return $this->getContent($checklist);
    }

    /**
     * Get all shop information for toolbox
     *
     * @return string
     */
    public function getExportInformation()
    {
        $content = '';
        $exportData = LengowToolbox::getData(LengowToolbox::DATA_TYPE_SHOP);
        foreach ($exportData as $data) {
            if ($data[LengowToolbox::SHOP_LAST_EXPORT] !== 0) {
                $lastExport = LengowMain::getDateInCorrectFormat($data[LengowToolbox::SHOP_LAST_EXPORT], true);
            } else {
                $lastExport = $this->locale->t('toolbox/screen/last_import_none');
            }
            $shopOptions = $data[LengowToolbox::SHOP_OPTIONS];
            $selectionEnabledKey = LengowConfiguration::$genericParamKeys[LengowConfiguration::SELECTION_ENABLED];
            $inactiveEnabledKey = LengowConfiguration::$genericParamKeys[LengowConfiguration::INACTIVE_ENABLED];
            $checklist = array(
                array(
                    self::DATA_HEADER => $data[LengowToolbox::SHOP_NAME]
                        . ' (' . $data[LengowToolbox::SHOP_ID] . ')'
                        . ' - ' . $data[LengowToolbox::SHOP_DOMAIN_URL],
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/shop_active'),
                    self::DATA_STATE => (int) $data[LengowToolbox::SHOP_ENABLED],
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/shop_catalogs_id'),
                    self::DATA_MESSAGE => implode (', ' , $data[LengowToolbox::SHOP_CATALOG_IDS]),
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/shop_product_total'),
                    self::DATA_MESSAGE => $data[LengowToolbox::SHOP_NUMBER_PRODUCTS_AVAILABLE],
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/shop_product_exported'),
                    self::DATA_MESSAGE => $data[LengowToolbox::SHOP_NUMBER_PRODUCTS_EXPORTED],
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/export_selection_enabled'),
                    self::DATA_STATE => (int) $shopOptions[$selectionEnabledKey],
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/export_variation_enabled'),
                    self::DATA_STATE => 1,
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/export_out_stock_enabled'),
                    self::DATA_STATE => 1,
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/export_inactive_enabled'),
                    self::DATA_STATE => (int) $shopOptions[$inactiveEnabledKey],
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/shop_export_token'),
                    self::DATA_MESSAGE => $data[LengowToolbox::SHOP_TOKEN],
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/url_export'),
                    self::DATA_MESSAGE => $data[LengowToolbox::SHOP_FEED_URL],
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/shop_last_export'),
                    self::DATA_MESSAGE => $lastExport,
                ),
            );
            $content .= $this->getContent($checklist);
        }
        return $content;
    }

    /**
     * Get all file information for toolbox
     *
     * @return string
     */
    public function getFileInformation()
    {
        $content = '';
        $exportData = LengowToolbox::getData(LengowToolbox::DATA_TYPE_SHOP);
        foreach ($exportData as $data) {
            $sep = DIRECTORY_SEPARATOR;
            $shopNameCleaned = LengowMain::getShopNameCleaned($data[LengowToolbox::SHOP_NAME]);
            $shopPath = LengowMain::FOLDER_EXPORT . $sep . $shopNameCleaned . $sep;
            $folderPath = LengowMain::getLengowFolder() . $shopPath;
            $folderUrl = LengowMain::getBaseUrl() . $sep . LengowMain::getPathPlugin() .$shopPath ;
            $files = file_exists($folderPath) ? array_diff(scandir($folderPath), array('..', '.')) : array();
            $checklist = array(
                array(
                    self::DATA_HEADER => $data[LengowToolbox::SHOP_NAME]
                                         . ' (' . $data[LengowToolbox::SHOP_ID] . ')'
                                         . ' - ' . $data[LengowToolbox::SHOP_DOMAIN_URL],
                ),
                array(
                    self::DATA_TITLE => $this->locale->t('toolbox/screen/folder_path'),
                    self::DATA_MESSAGE => $folderPath,
                ),
            );
            if (!empty($files)) {
                $checklist[] = array(self::DATA_SIMPLE => $this->locale->t('toolbox/screen/file_list'));
                foreach ($files as $file) {
                    $fileTimestamp = filectime($folderPath . $file);
                    $fileLink = '<a href="' . $folderUrl . $file . '" target="_blank">' . $file . '</a>';
                    $checklist[] = array(
                        self::DATA_TITLE => $fileLink,
                        self::DATA_MESSAGE => LengowMain::getDateInCorrectFormat($fileTimestamp, true),
                    );
                }
            } else {
                $checklist[] = array(self::DATA_SIMPLE => $this->locale->t('toolbox/screen/no_file_exported'));
            }
            $content .= $this->getContent($checklist);
        }
        return $content;
    }

    /**
     * Get files checksum information
     *
     * @return string
     */
    public function checkFileMd5()
    {
        $checklist = array();
        $checksumData = LengowToolbox::getData(LengowToolbox::DATA_TYPE_CHECKSUM);
        $html = '<h3><i class="fa fa-commenting"></i> ' . $this->locale->t('toolbox/screen/summary') . '</h3>';
        if ($checksumData[LengowToolbox::CHECKSUM_AVAILABLE]) {
            $checklist[] = array(
                self::DATA_TITLE => $this->locale->t(
                    'toolbox/screen/file_checked',
                    array('nb_file' => $checksumData[LengowToolbox::CHECKSUM_NUMBER_FILES_CHECKED])
                ),
                self::DATA_STATE => 1,
            );
            $checklist[] = array(
                self::DATA_TITLE => $this->locale->t(
                    'toolbox/screen/file_modified',
                    array('nb_file' => $checksumData[LengowToolbox::CHECKSUM_NUMBER_FILES_MODIFIED])
                ),
                self::DATA_STATE => (int) ($checksumData[LengowToolbox::CHECKSUM_NUMBER_FILES_MODIFIED] === 0),
            );
            $checklist[] = array(
                self::DATA_TITLE => $this->locale->t(
                    'toolbox/screen/file_deleted',
                    array('nb_file' => $checksumData[LengowToolbox::CHECKSUM_NUMBER_FILES_DELETED])
                ),
                self::DATA_STATE => (int) ($checksumData[LengowToolbox::CHECKSUM_NUMBER_FILES_DELETED] === 0),
            );
            $html .= $this->getContent($checklist);
            if (!empty($checksumData[LengowToolbox::CHECKSUM_FILE_MODIFIED])) {
                $fileModified = array();
                foreach ($checksumData[LengowToolbox::CHECKSUM_FILE_MODIFIED] as $file) {
                    $fileModified[] = array(
                        self::DATA_TITLE => $file,
                        self::DATA_STATE => 0,
                    );
                }
                $html .= '<h3><i class="fa fa-list"></i> '
                    . $this->locale->t('toolbox/screen/list_modified_file') . '</h3>';
                $html .= $this->getContent($fileModified);
            }
            if (!empty($checksumData[LengowToolbox::CHECKSUM_FILE_DELETED])) {
                $fileDeleted = array();
                foreach ($checksumData[LengowToolbox::CHECKSUM_FILE_DELETED] as $file) {
                    $fileDeleted[] = array(
                        self::DATA_TITLE => $file,
                        self::DATA_STATE => 0,
                    );
                }
                $html .= '<h3><i class="fa fa-list"></i> '
                    . $this->locale->t('toolbox/screen/list_deleted_file') . '</h3>';
                $html .= $this->getContent($fileDeleted);
            }
        } else {
            $checklist[] = array(
                self::DATA_TITLE => $this->locale->t('toolbox/screen/file_not_exists'),
                self::DATA_STATE => 0,
            );
            $html .= $this->getContent($checklist);
        }
        return $html;
    }

    /**
     * Get HTML Table content of checklist
     *
     * @param array $checklist all information for toolbox
     *
     * @return string
     */
    private function getContent($checklist = array())
    {
        if (empty($checklist)) {
            return null;
        }
        $out = '<table class="table" cellpadding="0" cellspacing="0">';
        foreach ($checklist as $check) {
            $out .= '<tr>';
            if (isset($check[self::DATA_HEADER])) {
                $out .= '<td colspan="2" align="center" style="border:0"><h4>'
                    . $check[self::DATA_HEADER] . '</h4></td>';
            } elseif (isset($check[self::DATA_SIMPLE])) {
                $out .= '<td colspan="2" align="center"><h5>' . $check[self::DATA_SIMPLE] . '</h5></td>';
            } else {
                $out .= '<td><b>' . $check[self::DATA_TITLE] . '</b></td>';
                if (isset($check[self::DATA_STATE])) {
                    if ($check[self::DATA_STATE] === 1) {
                        $out .= '<td align="right"><i class="fa fa-check lgw-check-green"></i></td>';
                    } else {
                        $out .= '<td align="right"><i class="fa fa-times lgw-check-red"></i></td>';
                    }
                    if (($check[self::DATA_STATE] === 0) && isset($check[self::DATA_HELP])) {
                        $out .= '<tr><td colspan="2"><p>' . $check[self::DATA_HELP];
                        if (array_key_exists(self::DATA_HELP_LINK, $check) && $check[self::DATA_HELP_LINK] !== '') {
                            $out .= '<br /><a target="_blank" href="'
                                . $check[self::DATA_HELP_LINK] . '">' . $check[self::DATA_HELP_LABEL] . '</a>';
                        }
                        $out .= '</p></td></tr>';
                    }
                } else {
                    $out .= '<td align="right"><b>' . $check[self::DATA_MESSAGE] . '</b></td>';
                }
            }
            $out .= '</tr>';
        }
        $out .= '</table>';
        return $out;
    }
}
