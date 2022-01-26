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
use Shopware_Plugins_Backend_Lengow_Components_LengowToolboxElement as LengowToolboxElement;
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
        if (LengowConfiguration::debugModeIsActive()) {
            $html['lgw-debug-label'] = self::getDebugModeWarning();
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
                <a href="#" id="lengowToolboxTab" class="sub-link" title="' . $locale->t('footer/toolbox') . '">'
                     . $locale->t('footer/toolbox') .
                '</a> |
                <a href="#" id="lengowLegalsTab" class="sub-link" title="' . $locale->t('footer/legals') . '">'
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
        // get actual plugin urls in current language
        $pluginLinks = LengowSync::getPluginLinks(LengowMain::getLocale());
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
                        ' <a href="' . $pluginLinks[LengowSync::LINK_TYPE_HELP_CENTER] . '" target="_blank">'
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
        // get actual plugin urls in current language
        $pluginLinks = LengowSync::getPluginLinks(LengowMain::getLocale());
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
                        ' <a href="' . $pluginLinks[LengowSync::LINK_TYPE_HELP_CENTER] . '" target="_blank">'
                            . $locale->t('connection/cms/failed_help_center') .
                        '</a> '
                        . $locale->t('connection/cms/failed_help_or') .
                        ' <a href="' . $pluginLinks[LengowSync::LINK_TYPE_SUPPORT] . '" target="_blank">'
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
        // get actual plugin urls in current language
        $pluginLinks = LengowSync::getPluginLinks(LengowMain::getLocale());
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
                    ' <a href="' . $pluginLinks[LengowSync::LINK_TYPE_HELP_CENTER] . '" target="_blank">'
                        . $locale->t('connection/cms/failed_help_center') .
                    '</a> '
                    . $locale->t('connection/cms/failed_help_or') .
                    ' <a href="' . $pluginLinks[LengowSync::LINK_TYPE_SUPPORT] . '" target="_blank">'
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
        // get actual plugin urls in current language
        $pluginLinks = LengowSync::getPluginLinks(LengowMain::getLocale());
        return '
            <div id="lengow_help_wrapper">
                <div class="lgw-container">
                    <div class="lgw-box lengow_help_wrapper text-center">
                        <h2>' . $locale->t('help/screen/title') . '</h2>
                        <p>' . $locale->t('help/screen/contain_text_support') . ' 
                            <a href="' . $pluginLinks[LengowSync::LINK_TYPE_SUPPORT] . '"
                               target="_blank"
                               title="Lengow Support">'
                                . $locale->t('help/screen/title_lengow_support') .
                            ' </a>
                        </p>
                        <p>' . $locale->t('help/screen/contain_text_support_hour') . '</p>
                        <p>' . $locale->t('help/screen/find_answer') . '
                            <a href="' . $pluginLinks[LengowSync::LINK_TYPE_HELP_CENTER] . '"
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
     * @param array|false $pluginData all plugin data
     * @param array|false $accountStatusData all account status data
     * @param bool $showUpdateModal display update modal or not
     *
     * @return string
     */
    public static function getDashboard($pluginData, $accountStatusData, $showUpdateModal)
    {
        $locale = new LengowTranslation();
        // get update and free trial warning
        $freeTrialWarning = $accountStatusData
            && $accountStatusData['type'] === 'free_trial'
            && !$accountStatusData['expired']
                ? self::getFreeTrialWarning($accountStatusData['day'])
                : '';
        $pluginVersion = Shopware()->Plugins()->Backend()->Lengow()->getVersion();
        $pluginUpdateWarning = $pluginData && version_compare($pluginData['version'], $pluginVersion, '>')
            ? self::getUpdateWarning($pluginData['version'])
            : '';
        // get actual plugin urls in current language
        $pluginLinks = LengowSync::getPluginLinks(LengowMain::getLocale());
        // get other data for dashboard
        $numberOrderToBeSent = LengowOrder::countOrderToBeSent();
        $alertOrderToBeSent = $numberOrderToBeSent > 0
            ? ' <span class="lgw-label red">' . $numberOrderToBeSent . '</span>'
            : '';
        // get Lengow Dashboard
        return '
            <div id="lengow_home_wrapper">
                <div class="lgw-container">
                    <div class="lgw-row">
                        <div class="text-left pull-left lgw-col-6">
                            ' . $pluginUpdateWarning . '
                        </div>
                        <div class="text-right pull-right lgw-col-6">
                            ' . $freeTrialWarning . '
                        </div>
                    </div>
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
                            <a href="' . $pluginLinks[LengowSync::LINK_TYPE_HELP_CENTER] . '" target="_blank">'
                                . $locale->t('dashboard/screen/visit_help_center') .
                            '</a> ' . $locale->t('dashboard/screen/configure_plugin') . '
                        </p>
                    </div>
                    ' . self::getUpdateModal($pluginData, $pluginLinks, $showUpdateModal) . ' 
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
            <div id="lengow_home_wrapper">
                <div class="lgw-container">
                    <div class="lgw-box">
                        <div class="lgw-row">
                            <div class="lgw-col-6 display-inline-block text-center">
                                <h2>' . $locale->t('status/screen/title_end_free_trial') . '</h2>
                                <h3>' . $locale->t('status/screen/subtitle_end_free_trial') . '</h3>
                                <p>'
                                    . $locale->t('status/screen/first_description_end_free_trial') .
                                '</p>
                                <p>'
                                    . $locale->t('status/screen/second_description_end_free_trial') .
                                '</p>
                                <p>'
                                    . $locale->t('status/screen/third_description_end_free_trial') .
                                '</p>
                                <div>
                                    <a href="//my.' . LengowConnector::LENGOW_URL . '" class="lgw-btn" target="_blank">'
                                        . $locale->t('status/screen/upgrade_account_button') .
                                    '</a>
                                </div>
                                <div>
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
                </div>' . self::getFooter() . '
            </div>';
    }

    /**
     * Get debug mode warning html
     *
     * @return string
     */
    public static function getDebugModeWarning()
    {
        $locale = new LengowTranslation();
        return '
            <div class="lgw-debug-warning">'
                . $locale->t('menu/debug_active') .
            '</div>';
    }

    /**
     * Get update warning html
     *
     * @param string $version new plugin version
     *
     * @return string
     */
    public static function getUpdateWarning($version)
    {
        $locale = new LengowTranslation();
        return '
            <div class="lgw-update-warning">
                ' . $locale->t('menu/new_version_available', array('version' => $version)) . '
                <button id="js-open-update-modal" class="btn-link mod-blue mod-inline">'
                    . $locale->t('menu/download_plugin') .
                '</button>
            </div>';
    }

    /**
     * Get free trial warning html
     *
     * @param string $days free trial days
     *
     * @return string
     */
    public static function getFreeTrialWarning($days)
    {
        $locale = new LengowTranslation();
        return '
            <div class="lgw-free-trial-warning">
                ' . $locale->t('menu/counter', array('counter' => $days)) . '
                <a href="//my.' . LengowConnector::LENGOW_URL . '" target="_blank">'
                    . $locale->t('menu/upgrade_account') .
                '</a>
            </div>';
    }

    /**
     * Get update modal html
     *
     * @param array $pluginData all plugin data
     * @param array $pluginLinks all plugin links in current locale
     * @param bool $showUpdateModal display update modal or not
     *
     * @return string
     */
    public static function getUpdateModal($pluginData, $pluginLinks, $showUpdateModal)
    {
        $locale = new LengowTranslation();
        $pluginDownloadLink = '//my.' . LengowConnector::LENGOW_URL . $pluginData['download_link'];
        $extensionHtml = '';
        foreach ($pluginData['extensions'] as $extension) {
            $extensionRequired = $locale->t(
                'update/extension_required',
                array(
                    'name' => $extension['name'],
                    'min_version' => $extension['min_version'],
                    'max_version' => $extension['max_version'],
                )
            );
            $extensionHtml .= '<br /> ' . $extensionRequired;
        }
        $isOpen = '';
        $remindMeLaterButton = '';
        if ($showUpdateModal) {
            $isOpen = 'is-open';
            $remindMeLaterButton = '
                <button id="js-remind-me-later" class="btn-link sub-link no-margin-top text-small">'
                    . $locale->t('update/button_remind_me_later') .
                '</button>';
        }
        return '
            <div id="js-update-modal" class="lgw-modalbox mod-size-medium ' . $isOpen . '">
                <div class="lgw-modalbox-content">
                    <span id="js-close-update-modal" class="lgw-modalbox-close"></span>
                    <div class="lgw-modalbox-body">
                        <div class="lgw-row flexbox-vertical-center">
                            <div class="lgw-col-5 text-center">
                                <img src="' . self::IMG_FOLDER . 'plugin-update.png" alt="">
                            </div>
                            <div class="lgw-col-7">
                                <h1>' . $locale->t('update/version_available') . '</h1>
                                <p>'
                                    . $locale->t('update/start_now') . '
                                    <a href="' . $pluginLinks[LengowSync::LINK_TYPE_CHANGELOG] . '" target="_blank">'
                                        . $locale->t('update/link_changelog') .
                                    '</a>
                                </p>
                                <div class="lgw-content-section mod-small">
                                    <h2 class="no-margin-bottom">' . $locale->t('update/step_one') . '</h2>
                                    <p class="no-margin-bottom">
                                        ' . $locale->t('update/download_last_version') . '
                                    </p>
                                    <p class="text-lesser text-italic">'
                                        . $locale->t(
                                            'update/plugin_compatibility',
                                            array(
                                                'cms_min_version' => $pluginData['cms_min_version'],
                                                'cms_max_version' => $pluginData['cms_max_version'],
                                            )
                                        ) . $extensionHtml .
                                    '</p>
                                </div>
                                <div class="lgw-content-section mod-small">
                                    <h2 class="no-margin-bottom">' . $locale->t('update/step_two') . '</h2>
                                    <p class="no-margin-bottom">
                                        <a href="' . $pluginLinks[LengowSync::LINK_TYPE_UPDATE_GUIDE] . '"
                                           target="_blank">'
                                            . $locale->t('update/link_follow') .
                                        '</a>
                                        ' . $locale->t('update/update_procedure') .
                                    '</p>
                                    <p class="text-lesser text-italic">'
                                        . $locale->t('update/not_working') . '
                                        <a href="' . $pluginLinks[LengowSync::LINK_TYPE_SUPPORT] . '"
                                            target="_blank">'
                                            . $locale->t('update/customer_success_team') .
                                        '</a>
                                    </p>
                                </div>
                                <div class="flexbox-vertical-center margin-standard">
                                    <a class="lgw-btn no-margin-top"
                                       href="' . $pluginDownloadLink . '"
                                       target="_blank">'
                                        . $locale->t(
                                            'update/button_download_version',
                                            array('version' => $pluginData['version'])
                                        ) .
                                    '</a>
                                    ' . $remindMeLaterButton . '
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
    }

    /**
     * Get toolbox content
     *
     * @return string
     */
    public static function getToolbox()
    {
        $locale = new LengowTranslation();
        $toolboxElement = new LengowToolboxElement();
        return '
            <div id="lengow_toolbox_wrapper">
                <div class="lgw-container">
                    <h2>
                        <i class="fa fa-rocket"></i>
                        ' . $locale->t('toolbox/screen/title') . '
                    </h2>
                    <div class="lgw-box">
                        <h2>' . $locale->t('toolbox/screen/global_information') . '</h2>
                        <div class="js-lgw-global-content">
                            <div class="lgw-box-content">
                                <h3>
                                    <i class="fa fa-check"></i>
                                    ' . $locale->t('toolbox/screen/checklist_information') . '
                                </h3>
                                ' . $toolboxElement->getCheckList() . '
                            </div>
                            <div class="lgw-box-content">
                                <h3>
                                    <i class="fa fa-cog"></i>
                                    ' . $locale->t('toolbox/screen/plugin_information') . '
                                </h3>
                                ' . $toolboxElement->getGlobalInformation() . '
                            </div>
                            <div class="lgw-box-content">
                                <h3>
                                    <i class="fa fa-download"></i>
                                    ' . $locale->t('toolbox/screen/synchronization_information') . '
                                </h3>
                                ' . $toolboxElement->getImportInformation() . '
                            </div>
                        </div>
                    </div>
                    <div class="lgw-box">
                        <h2>' . $locale->t('toolbox/screen/shop_information') . '</h2>
                        <div class="lgw-export-content">
                            <div class="lgw-box-content">
                                <h3>
                                    <i class="fa fa-upload"></i>
                                    ' . $locale->t('toolbox/screen/export_information') . '
                                </h3>
                                ' . $toolboxElement->getExportInformation() . '
                            </div>
                            <div class="lgw-box-content">
                                <h3>
                                    <i class="fa fa-list"></i>
                                    ' . $locale->t('toolbox/screen/content_folder_media') . '
                                </h3>
                                ' . $toolboxElement->getFileInformation() . '
                            </div>
                        </div>
                    </div>
                    <div class="lgw-box">
                        <h2>' . $locale->t('toolbox/screen/checksum_integrity') . '</h2>
                        <div class="lgw-box-content js-lgw-checksum-content">
                            ' . $toolboxElement->checkFileMd5() . '
                        </div>
                    </div>
                </div>
                ' . self::getFooter() . '
            </div>';
    }
}
