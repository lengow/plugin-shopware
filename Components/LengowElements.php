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

use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowConnector as LengowConnector;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;
use Shopware_Plugins_Backend_Lengow_Components_LengowSync as LengowSync;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

/**
 * Lengow Elements Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowElements
{
    /**
     * @var string Lengow image folder path
     */
    const IMG_FOLDER = '/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/';

    /**
     * Get Header html
     *
     * @return array
     */
    public static function getHeader()
    {
        $html = array();
        $locale = new LengowTranslation();
        $accountStatus = LengowSync::getStatusAccount();
        $pluginData = LengowSync::getPluginData();
        $pluginVersion = Shopware()->Plugins()->Backend()->Lengow()->getVersion();
        if (LengowConfiguration::debugModeIsActive()) {
            $html['lgw-debug-label'] =
                '<div id="lgw-debug" class="adminlengowhome">'
                    . $locale->t('menu/debug_active') .
                '</div>';
        }
        if ($accountStatus['type'] === 'free_trial' && $accountStatus['expired'] !== true) {
            $html['lgw-trial-label'] =
                '<p class="text-right" id="menucountertrial">'
                    . $locale->t('menu/counter', array('counter' => $accountStatus['day'])) .
                    '<a href="//my.' . LengowConnector::LENGOW_URL . '" target="_blank">'
                        . $locale->t('menu/upgrade_account') .
                    '</a>
                </p>';
        }
        if ($pluginData && version_compare($pluginVersion, $pluginData['version'], '<')) {
            $pluginDownloadLink = '//my.' . LengowConnector::LENGOW_URL . $pluginData['download_link'];
            $html['lgw-plugin-available-label'] =
                '<p class="text-right" id="menupluginavailable">'
                    . $locale->t('menu/new_version_available', array('version' => $pluginData['version'])) .
                    '<a href="' . $pluginDownloadLink . '" target="_blank">'
                        . $locale->t('menu/download_plugin', $locale) .
                    '</a>
                </p>';
        }
        return $html;
    }

    /**
     * Get Footer html
     *
     * @param boolean $withLinks Show legals link or not
     *
     * @return string
     */
    public static function getFooter($withLinks = true)
    {
        $locale = new LengowTranslation();
        $pluginPreprod = LengowConnector::LENGOW_URL === 'lengow.net'
            ? '<span class="lgw-label-preprod">preprod</span>'
            : '';
        $links = '';
        if ($withLinks) {
            $links = '
                <a href="#" id="lengowLegalsTab" class="sub-link" title="Legal">'
                    . $locale->t('footer/legals') .
                '</a> |
            ';
        }
        return '
            <div class="lgw-container lgw-footer-vold clear">
                <div class="lgw-content-section text-center">
                    <div id="lgw-footer">
                        <p class="text-center">'
                            . $links . $locale->t('footer/plugin_lengow') .
                            ' - v.' . Shopware()->Plugins()->Backend()->Lengow()->getVersion() . ' '
                            . $pluginPreprod .
                            ' | copyright © ' . date('Y') . '
                            <a href=' . $locale->t('footer/lengow_url') . '
                               target="_blank"
                               class="sub-link"
                               title="Lengow.com">
                                Lengow
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        ';
    }

    /**
     * Get home page html
     *
     * @return string
     */
    public static function getConnectionHome()
    {
        $locale = new LengowTranslation();
        return '
            <div id="lengow_connection_wrapper">
                <div class="lgw-container lgw-connection text-center">
                    <div class="lgw-content-section">
                        <div class="lgw-logo">
                            <img src="' . self::IMG_FOLDER . 'lengow-blue.png" alt="lengow">
                        </div>
                    </div>
                    <div id="lgw-connection-content">
                        <div id="lgw-connection-home">
                            <div class="lgw-content-section">
                                <p>' . $locale->t('connection/home/description_first') . '</p>
                                <p>' . $locale->t('connection/home/description_second') . '</p>
                                <p>' . $locale->t('connection/home/description_third') . '</p>
                            </div>
                            <div class="lgw-module-illu">
                                <img src="' . self::IMG_FOLDER . 'connected-shopware.png"
                                     class="lgw-module-illu-module"
                                     alt="shopware">
                                <img src="' . self::IMG_FOLDER . 'connected-lengow.png"
                                     class="lgw-module-illu-lengow"
                                     alt="lengow">
                                <img src="' . self::IMG_FOLDER . 'plug-grey.png"
                                     class="lgw-module-illu-plug"
                                     alt="plug">
                            </div>
                            <p>' . $locale->t('connection/home/description_fourth') . '</p>
                            <div>
                                <button id="js-go-to-credentials" class="lgw-btn lgw-btn-green">'
                                    . $locale->t('connection/home/button') .
                                '</button>
                                <br/>
                                <p>
                                    ' . $locale->t('connection/home/no_account') . '
                                    <a href="//my.' . LengowConnector::LENGOW_URL . '" target="_blank">'
                                        . $locale->t('connection/home/no_account_sign_up') .
                                    '</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                ' . self::getFooter(false) . '
            </div>
        ';
    }

    /**
     * Get cms page html
     *
     * @return string
     */
    public static function getConnectionCms()
    {
        $locale = new LengowTranslation();
        return '
            <div id="lgw-connection-cms">
                <div class="lgw-content-section">
                    <h2>' . $locale->t('connection/cms/credentials_title') . '</h2>
                </div>
                <div class="lgw-content-input">
                    <input type="text"
                           name="lgwAccessToken"
                           id="lgw-access-token"
                           class="js-credentials-input"
                           placeholder="' . $locale->t('connection/cms/credentials_placeholder_access_token') . '">
                    <input type="text"
                           name="lgwSecret"
                           id="lgw-secret"
                           class="js-credentials-input"
                           placeholder="' . $locale->t('connection/cms/credentials_placeholder_secret') . '">
                </div>
                <div class="lgw-content-section">
                    <p>' . $locale->t('connection/cms/credentials_description') . '</p>
                    <p>'
                        . $locale->t('connection/cms/credentials_help') .
                        ' <a href="' . $locale->t('connection/cms/credentials_help_center_url') . '" target="_blank">'
                            . $locale->t('connection/cms/credentials_help_center') .
                        '</a>
                    </p>
                </div>
                <div>
                    <button id="js-connect-cms" class="lgw-btn lgw-btn-green lgw-btn-progression lgw-btn-disabled">
                        <div class="btn-inner">
                            <div class="btn-step default">'
                                . $locale->t('connection/cms/credentials_button') .
                            '</div>
                            <div class="btn-step loading">'
                                . $locale->t('connection/cms/credentials_button_loading') .
                            '</div>
                        </div>   
                    </button>
                </div>
            </div>
        ';
    }

    /**
     * Get cms result page html
     *
     * @return string
     */
    public static function getConnectionCmsSuccess()
    {
        $locale = new LengowTranslation();
        return '
            <div id="lgw-connection-cms-result">
                <div class="lgw-content-section">
                    <h2>' . $locale->t('connection/cms/success_title') . '</h2>
                </div>
                <div class="lgw-module-illu mod-connected">
                    <img src="' . self::IMG_FOLDER . 'connected-shopware.png"
                         class="lgw-module-illu-module mod-connected"
                         alt="shopware">
                    <img src="' . self::IMG_FOLDER . 'connected-lengow.png"
                         class="lgw-module-illu-lengow mod-connected"
                         alt="lengow">
                    <img src="' . self::IMG_FOLDER . 'connection-module.png"
                         class="lgw-module-illu-plug mod-connected"
                         alt="connection">
                </div>
                <div class="lgw-content-section">
                    <p>' . $locale->t('connection/cms/success_description_first') . '</p>
                    <p>
                        ' . $locale->t('connection/cms/success_description_second') . '
                        <a href="https://my.' . LengowConnector::LENGOW_URL . '" target="_blank">'
                            . $locale->t('connection/cms/success_description_second_go_to_lengow') .
                        '</a>
                    </p>
                </div>
                <div>
                    <button id="js-go-to-dashboard" class="lgw-btn lgw-btn-green">'
                        . $locale->t('connection/cms/success_button') .
                    '</button>
                </div>
            </div>
        ';
    }

    /**
     * Get cms result page html
     *
     * @return string
     */
    public static function getConnectionCmsSuccessWithCatalog()
    {
        $locale = new LengowTranslation();
        return '
            <div id="lgw-connection-cms-result">
                <div class="lgw-content-section">
                    <h2>' . $locale->t('connection/cms/success_title') . '</h2>
                </div>
                <div class="lgw-module-illu mod-connected">
                    <img src="' . self::IMG_FOLDER . 'connected-shopware.png"
                         class="lgw-module-illu-module mod-connected"
                         alt="shopware">
                    <img src="' . self::IMG_FOLDER . 'connected-lengow.png"
                         class="lgw-module-illu-lengow mod-connected"
                         alt="lengow">
                    <img src="' . self::IMG_FOLDER . 'connection-module.png"
                         class="lgw-module-illu-plug mod-connected"
                         alt="connection">
                </div>
                <div class="lgw-content-section">
                    <p>' . $locale->t('connection/cms/success_description_first_catalog') . '</p>
                    <p>' . $locale->t('connection/cms/success_description_second_catalog') . '</p>
                </div>
                <div>
                    <button id="js-go-to-catalog" class="lgw-btn lgw-btn-green" data-retry="false">'
                        . $locale->t('connection/cms/success_button_catalog') .
                    '</button>
                </div>
            </div>
        ';
    }

    /**
     * Get cms result page html
     *
     * @param boolean $credentialsValid Credentials are valid or not
     *
     * @return string
     */
    public static function getConnectionCmsFailed($credentialsValid = false)
    {
        $locale = new LengowTranslation();
        if ($credentialsValid) {
            $error = '<p>' . $locale->t('connection/cms/failed_description') . '</p>';
        } else {
            $error = '<p>' . $locale->t('connection/cms/failed_description_first_credentials') . '</p>';
            if (LengowConnector::LENGOW_URL === 'lengow.net') {
                $error .= '<p>' . $locale->t('connection/cms/failed_description_second_credentials_preprod') . '</p>';
            } else {
                $error .= '<p>' . $locale->t('connection/cms/failed_description_second_credentials_prod') . '</p>';
            }
        }
        return '
            <div id="lgw-connection-cms-result">
                <div class="lgw-content-section">
                    <h2>' . $locale->t('connection/cms/failed_title') . '</h2>
                </div>
                <div class="lgw-module-illu mod-disconnected">
                    <img src="' . self::IMG_FOLDER . 'connected-shopware.png"
                         class="lgw-module-illu-module mod-disconnected"
                         alt="shopware">
                    <img src="' . self::IMG_FOLDER . 'connected-lengow.png"
                         class="lgw-module-illu-lengow mod-disconnected"
                         alt="lengow">
                    <img src="' . self::IMG_FOLDER . 'unplugged.png"
                         class="lgw-module-illu-plug mod-disconnected"
                         alt="unplugged">
                </div>
                <div class="lgw-content-section">'
                    . $error .
                    '<p>'
                        . $locale->t('connection/cms/failed_help') .
                        ' <a href="' . $locale->t('help/screen/knowledge_link_url') . '" target="_blank">'
                            . $locale->t('connection/cms/failed_help_center') .
                        '</a> '
                        . $locale->t('connection/cms/failed_help_or') .
                        ' <a href="' . $locale->t('help/screen/link_lengow_support') . '" target="_blank">'
                            . $locale->t('connection/cms/failed_help_customer_success_team') .
                        '</a>
                    </p>
                </div>
                <div>
                    <button id="js-go-to-credentials" class="lgw-btn lgw-btn-green">'
                         . $locale->t('connection/cms/failed_button') .
                    '</button>
                </div>
            </div>
        ';
    }

    /**
     * Get catalog page html
     *
     * @param array $catalogList
     *
     * @return string
     */
    public static function getConnectionCatalog($catalogList = array())
    {
        $locale = new LengowTranslation();
        $activeShops = LengowMain::getActiveShops();
        $options = '<option value=""></option>';
        foreach ($catalogList as $catalog) {
            $options .= '<option value="' . $catalog['value'] . '">' . $catalog['label'] . '</option>';
        }
        $selects = '';
        foreach ($activeShops as $shop) {
            $selects .= '
                <div class="lgw-catalog-select">
                    <label class="control-label" for="select_catalog_' . $shop->getId() . '">'
                        . $shop->getName() .
                    '</label>
                    <select class="form-control lengow_select js-catalog-linked"
                            id="select_catalog_' . $shop->getId() . '"
                            name="' . $shop->getId() . '"
                            multiple="multiple">
                        ' . $options . '
                    </select>
                </div>
            ';
        }
        return '
            <div class="lgw-content-section">
                <h2>' . $locale->t('connection/catalog/link_title') . '</h2>
                <p>' . $locale->t('connection/catalog/link_description') . '</p>
                <p>
                    <span>' . count($catalogList) . ' </span>
                    ' . $locale->t('connection/catalog/link_catalog_available') . '
                </p>
            </div>
            <div>
                ' . $selects . '     
            </div>
            <div>
                <button id="js-link-catalog" class="lgw-btn lgw-btn-green lgw-btn-progression">
                    <div class="btn-inner">
                        <div class="btn-step default">'
                            . $locale->t('connection/catalog/link_button') .
                        '</div>
                         <div class="btn-step loading">'
                            . $locale->t('connection/catalog/link_button_loading') .
                        '</div>
                    </div>   
                </button>
            </div>
        ';
    }

    /**
     * Get catalog failed html
     *
     * @return string
     */
    public static function getConnectionCatalogFailed()
    {
        $locale = new LengowTranslation();
        return '
            <div class="lgw-content-section">
                <h2>' . $locale->t('connection/catalog/failed_title') . '</h2>
            </div>
            <div class="lgw-module-illu mod-disconnected">
                <img src="' . self::IMG_FOLDER . 'connected-shopware.png"
                     class="lgw-module-illu-module mod-disconnected"
                     alt="shopware">
                <img src="' . self::IMG_FOLDER . 'connected-lengow.png"
                     class="lgw-module-illu-lengow mod-disconnected"
                     alt="lengow">
                <img src="' . self::IMG_FOLDER . 'unplugged.png"
                     class="lgw-module-illu-plug mod-disconnected"
                     alt="unplugged">
            </div>
            <div class="lgw-content-section">
                <p>' . $locale->t('connection/catalog/failed_description_first') . '</p>
                <p>' . $locale->t('connection/catalog/failed_description_second') . '</p>
                <p>'
                    . $locale->t('connection/cms/failed_help') .
                    ' <a href="' . $locale->t('help/screen/knowledge_link_url') . '" target="_blank">'
                        . $locale->t('connection/cms/failed_help_center') .
                    '</a> '
                    . $locale->t('connection/cms/failed_help_or') .
                    ' <a href="' . $locale->t('help/screen/link_lengow_support') . '" target="_blank">'
                        . $locale->t('connection/cms/failed_help_customer_success_team') .
                    '</a>
                </p>
            </div>
            <div>
                <button id="js-go-to-catalog" class="lgw-btn lgw-btn-green" data-retry="true">'
                    . $locale->t('connection/cms/failed_button') .
                '</button>
                <button id="js-go-to-dashboard" href="" class="lgw-btn lgw-btn-green">'
                    . $locale->t('connection/cms/success_button') .
                '</button>
            </div>
        ';
    }

    /**
     * Get help html
     */
    public static function getHelp()
    {
        $locale = new LengowTranslation();
        return '
            <div id="lengow_help_wrapper">
                <div class="lgw-container">
                    <div class="lgw-box lengow_help_wrapper text-center">
                        <h2>' . $locale->t('help/screen/title') . '</h2>
                        <p>' . $locale->t('help/screen/contain_text_support') . ' 
                            <a href="' . $locale->t('help/screen/link_lengow_support') . '"
                               target="_blank"
                               title="Lengow Support">'
                                . $locale->t('help/screen/title_lengow_support') .
                            ' </a>
                        </p>
                        <p>' . $locale->t('help/screen/contain_text_support_hour') . '</p>
                        <p>' . $locale->t('help/screen/find_answer') . '
                            <a href="' . $locale->t('help/screen/knowledge_link_url') . '"
                               target="_blank"
                               title="Help Center">'
                                . $locale->t('help/screen/link_shopware_guide') .
                            '</a>
                        </p>
                    </div>
                </div>
                ' . self::getFooter() . '
            </div>
        ';
    }

    /**
     * Get Legals html
     *
     * @return string
     */
    public static function getLegals()
    {
        $locale = new LengowTranslation();
        return '
            <div id="lengow_legals_wrapper">
                <div class="lgw-container">
                    <div class="lgw-box lengow_legals_wrapper">
                        <h3>SAS Lengow</h3> ' . $locale->t('legals/screen/simplified_company') . '<br />
                        ' . $locale->t('legals/screen/social_capital') . ' 368 778 € <br />
                        ' . $locale->t('legals/screen/cnil_declaration') . ' 1748784 v 0 <br />
                        ' . $locale->t('legals/screen/company_registration_number') . ' 513 381 434 <br />
                        ' . $locale->t('legals/screen/vat_identification_number') . ' FR42513381434 <br />
                        <h3>' . $locale->t('legals/screen/address') . '</h3>
                        6 rue René Viviani<br />
                        44200 Nantes
                        <h3>' . $locale->t('legals/screen/contact') . '</h3>
                        contact@lengow.com <br />
                        +33 (0)2 85 52 64 14
                        <h3>' . $locale->t('legals/screen/hosting') . '</h3>
                        OXALIDE<br />
                        RCS Paris : 803 816 529<br />
                        25 Boulevard de Strasbourg – 75010 Paris<br />
                        +33 (0)1 75 77 16 66
                    </div>
                </div>
                ' . self::getFooter() . '
            </div>
        ';
    }

    /**
     * Get dashboard html
     *
     * @return string
     */
    public static function getDashboard()
    {
        $locale = new LengowTranslation();
        $numberOrderToBeSent = LengowOrder::countOrderToBeSent();
        $alertOrderToBeSent = $numberOrderToBeSent > 0
            ? ' <span class="lgw-label red">' . $numberOrderToBeSent . '</span>'
            : '';
        // get Lengow Dashboard
        return '
            <div id="lengow_home_wrapper">
                <div class="lgw-container">
                    <div class="lgw-box lgw-home-header text-center">
                        <img src="' . self::IMG_FOLDER . 'lengow-white-big.png" alt="lengow">
                        <h1>' . $locale->t('dashboard/screen/welcome_back') . '</h1>
                        <a href="//my.' . LengowConnector::LENGOW_URL . '" class="lgw-btn" target="_blank">
                            ' . $locale->t('dashboard/screen/go_to_lengow') . '
                        </a>
                    </div>
                    <div class="lgw-row lgw-home-menu text-center">
                        <div class="lgw-col-4">
                            <a id="lengowExportTab" href="#" class="lgw-box-link">
                                <div class="lgw-box">
                                    <img src="' . self::IMG_FOLDER . 'home-products.png"
                                         class="img-responsive"
                                         alt="products">
                                    <h2>' . $locale->t('dashboard/screen/products_title') . '</h2>
                                    <p>' . $locale->t('dashboard/screen/products_text'). '</p>
                                </div>
                            </a>
                        </div>
                        <div class="lgw-col-4">
                            <a id="lengowImportTab" href="#" class="lgw-box-link">
                                <div class="lgw-box">
                                    <img src="' . self::IMG_FOLDER . 'home-orders.png"
                                         class="img-responsive"
                                         alt="orders">
                                    <h2>' . $locale->t('dashboard/screen/orders_title') . $alertOrderToBeSent . '</h2>
                                    <p>' . $locale->t('dashboard/screen/orders_text') . '</p>
                                </div>
                            </a>
                        </div>
                        <div class="lgw-col-4">
                            <a id="lengowSettingsTab" href="#" class="lgw-box-link">
                                <div class="lgw-box">
                                    <img src="' . self::IMG_FOLDER . 'home-settings.png"
                                         class="img-responsive"
                                         alt="settings">
                                    <h2>' . $locale->t('dashboard/screen/settings_title') . '</h2>
                                    <p>' . $locale->t('dashboard/screen/settings_text') . '</p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="lgw-box">
                        <h2>' . $locale->t('dashboard/screen/some_help_title') . '</h2>
                        <p>
                            <a href="#" id="lengowHelpTab">' . $locale->t('dashboard/screen/get_in_touch') . '</a>
                        </p>
                        <p>
                            <a href="' . $locale->t('help/screen/knowledge_link_url') . '" target="_blank">'
                                . $locale->t('dashboard/screen/visit_help_center') .
                            '</a> ' . $locale->t('dashboard/screen/configure_plugin') . '
                        </p>
                    </div>
                </div>
                ' . self::getFooter() . '
            </div>
        ';
    }

    /**
     * Get end of free trial html
     *
     * @return string
     */
    public static function getEndFreeTrial()
    {
        $locale = new LengowTranslation();
        return '
            <div class="lgw-container">
                <div class="lgw-box">
                    <div class="lgw-row">
                        <div class="lgw-col-6 display-inline-block">
                            <h2 class="text-center">' . $locale->t('status/screen/title_end_free_trial') . '</h2>
                            <h3 class="text-center">' . $locale->t('status/screen/subtitle_end_free_trial') . '</h3>
                            <p class="text-center">'
                                . $locale->t('status/screen/first_description_end_free_trial') .
                            '</p>
                            <p class="text-center">'
                                . $locale->t('status/screen/second_description_end_free_trial') .
                            '</p>
                            <p class="text-center">'
                                . $locale->t('status/screen/third_description_end_free_trial') .
                            '</p>
                            <div class="text-center">
                                <a href="//my.' . LengowConnector::LENGOW_URL . '" class="lgw-btn" target="_blank">'
                                    . $locale->t('status/screen/upgrade_account_button') .
                                '</a>
                            </div>
                            <div class="text-center">
                                <a href="#" id="lgw-refresh" class="lgw-box-link">'
                                    . $locale->t('status/screen/refresh_action') .
                                '</a>
                            </div>
                        </div>
                        <div class="lgw-col-6">
                            <div class="vertical-center">
                                <img src="' . self::IMG_FOLDER . 'logo-blue.png" class="center-block" alt="lengow"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>' . self::getFooter();
    }
}
