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
    public static $imgFolder = '/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/';

    /**
     * Get Header html
     *
     * @return array
     */
    public static function getHeader()
    {
        $accountStatus = LengowSync::getStatusAccount();
        $html = array();
        $isPreProdActive = LengowConfiguration::getConfig('lengowImportPreprodEnabled');
        $locale = LengowMain::getLocale();
        $preprodTranslation = LengowMain::decodeLogMessage('menu/preprod_active', $locale);
        $counterTranslation = LengowMain::decodeLogMessage(
            'menu/counter',
            $locale,
            array('counter' => $accountStatus['day'])
        );
        $upgradeTranslation = LengowMain::decodeLogMessage('menu/upgrade_account', $locale);
        if ($isPreProdActive) {
            $html['lgw-preprod-label'] = '<div id="lgw-preprod" class="adminlengowhome">'
                . $preprodTranslation . '</div>';
        }
        if ($accountStatus['type'] === 'free_trial' && $accountStatus['expired'] !== true) {
            $html['lgw-trial-label'] =
                '<p class="text-right" id="menucountertrial">' . $counterTranslation .
                '<a href="http://my.lengow.io" target="_blank">' . $upgradeTranslation . '</a>
                </p>';
        }
        return $html;
    }

    /**
     * Get Footer html
     *
     * @return string
     */
    public static function getFooter()
    {
        $keys = array('footer/' => array('legals', 'plugin_lengow', 'lengow_url'));
        $translations = LengowTranslation::getTranslationsFromArray($keys);
        return '<div class="lgw-container lgw-footer-vold clear">
                <div class="lgw-content-section text-center">
                    <div id="lgw-footer">
                        <p class="pull-right">
                            <a href="#" id="lengowLegalsTab" class="sub-link" title="Legal">
                            ' . $translations['legals'] . '</a> | '
            . $translations['plugin_lengow']
            . ' - v.' . Shopware()->Plugins()->Backend()->Lengow()->getVersion()
            . ' | copyright © ' . date('Y') . '  <a href=' . $translations['lengow_url']
            . ' target="_blank" class="sub-link" title="Lengow.com">
                            Lengow
                            </a>
                        </p>
                    </div>
                </div>
            </div>';
    }

    /**
     * Get Legals html
     *
     * @return string
     */
    public static function getLegals()
    {
        $keys = array(
            'legals/screen/' => array(
                'simplified_company',
                'social_capital',
                'cnil_declaration',
                'company_registration_number',
                'vat_identification_number',
                'address',
                'contact',
                'hosting',
            ),
        );
        $translations = LengowTranslation::getTranslationsFromArray($keys);
        return '<div class="lgw-container">
            <div class="lgw-box lengow_legals_wrapper">
                <h3>SAS Lengow</h3> ' . $translations['simplified_company'] . '<br />
                ' . $translations['social_capital'] . '368 778 € <br />
                ' . $translations['cnil_declaration'] . '1748784 v 0 <br />
                ' . $translations['company_registration_number'] . '513 381 434 <br />
                ' . $translations['vat_identification_number'] . 'FR42513381434 <br />
                <h3>' . $translations['address'] . '</h3>6 rue René Viviani <br /> 44200 Nantes
                <h3>' . $translations['contact'] . '</h3> contact@lengow.com <br /> +33 (0)2 85 52 64 14
                <h3>' . $translations['hosting'] . '</h3>OXALIDE<br />
                RCS Paris : 803 816 529<br />
                25 Boulevard de Strasbourg – 75010 Paris<br />
                +33 (0)1 75 77 16 66
            </div>
        </div>';
    }

    /**
     * Get dashboard html
     *
     * @return string
     */
    public static function getDashboard()
    {
        $keys = array(
            'dashboard/screen/' => array(
                'welcome_back',
                'go_to_lengow',
                'products_title',
                'products_text',
                'orders_title',
                'orders_text',
                'settings_title',
                'settings_text',
                'partner_business',
                'stat_turnover',
                'stat_nb_orders',
                'nb_order',
                'stat_more_stats',
                'some_help_title',
                'get_in_touch',
                'visit_help_center',
                'help_center_link',
                'configure_plugin',
            ),
        );
        $translations = LengowTranslation::getTranslationsFromArray($keys);
        // get Lengow statistics
        $stats = LengowSync::getStatistic();
        $statsHtml = '';
        if ($stats['available']) {
            $statsHtml = '
                <div class="lgw-box text-center">
                    <div class="lgw-col-12 center-block">
                        <img src="' . self::$imgFolder . 'picto-stats.png" class="img-responsive">
                    </div>
                    <h2>' . $translations['partner_business'] . '</h2>
                    <div class="lgw-row lgw-home-stats">
                        <div class="lgw-col-4 lgw-col-offset-2">
                            <h5>' . $translations['stat_turnover'] . '</h5>
                            <span class="stats-big-value">'
                                . $stats['total_order'] . ' ' . $stats['currency']
                            . '</span>
                        </div>
                        <div class="lgw-col-4">
                            <h5>' . $translations['stat_nb_orders'] . '</h5>
                            <span class="stats-big-value">' . $stats['nb_order'] . '</span>
                        </div>
                    </div>
                    <p>
                        <a href="http://my.lengow.io" target="_blank" class="lgw-btn lgw-btn-white">
                            ' . $translations['stat_more_stats'] . '
                        </a>
                    </p>
                </div>
            ';
        }
        $numberOrderToBeSent = LengowOrder::countOrderToBeSent();
        $alertOrderToBeSent = $numberOrderToBeSent > 0
            ? ' <span class="lgw-label red">' . $numberOrderToBeSent . '</span>'
            : '';
        // get Lengow Dashboard
        return '
            <div id="lengow_home_wrapper">
                <div class="lgw-container">
                    <div class="lgw-box lgw-home-header text-center">
                        <img src="' . self::$imgFolder . 'lengow-white-big.png" alt="lengow">
                        <h1>' . $translations['welcome_back'] . '</h1>
                        <a href="http://my.lengow.io" class="lgw-btn" target="_blank">
                            ' . $translations['go_to_lengow'] . '
                        </a>
                    </div>
                    <div class="lgw-row lgw-home-menu text-center">
                        <div class="lgw-col-4">
                            <a id="lengowExportTab" href="#" class="lgw-box-link">
                                <div class="lgw-box">
                                    <img src="' . self::$imgFolder . 'home-products.png" class="img-responsive">
                                    <h2>' . $translations['products_title'] . '</h2>
                                    <p>' . $translations['products_text'] . '</p>
                                </div>
                            </a>
                        </div>
                        <div class="lgw-col-4">
                            <a id="lengowImportTab" href="#" class="lgw-box-link">
                                <div class="lgw-box">
                                    <img src="' . self::$imgFolder . 'home-orders.png" class="img-responsive">
                                    <h2>' . $translations['orders_title'] . $alertOrderToBeSent . '</h2>
                                    <p>' . $translations['orders_text'] . '</p>
                                </div>
                            </a>
                        </div>
                        <div class="lgw-col-4">
                            <a id="lengowSettingsTab" href="#" class="lgw-box-link">
                                <div class="lgw-box">
                                    <img src="' . self::$imgFolder . 'home-settings.png" class="img-responsive">
                                    <h2>' . $translations['settings_title'] . '</h2>
                                    <p>' . $translations['settings_text'] . '</p>
                                </div>
                            </a>
                        </div>
                    </div>'
                . $statsHtml
                . '<div class="lgw-box">
                        <h2>' . $translations['some_help_title'] . '</h2>
                        <p>
                            <a href="#" id="lengowHelpTab">' . $translations['get_in_touch'] . ' </a>
                        </p>
                        <p>
                            <a href="' . $translations['help_center_link'] . '" target="_blank">'
                . $translations['visit_help_center'] .
                '</a> ' . $translations['configure_plugin'] . '
                        </p>
                    </div>
                </div>
                ' . self::getFooter() . '
            </div>';
    }

    /**
     * Get end of free trial html
     *
     * @return string
     */
    public static function getEndFreeTrial()
    {
        $keys = array(
            'status/screen/' => array(
                'title_end_free_trial',
                'subtitle_end_free_trial',
                'first_description_end_free_trial',
                'second_description_end_free_trial',
                'third_description_end_free_trial',
                'upgrade_account_button',
                'refresh_action',
            ),
        );
        $translations = LengowTranslation::getTranslationsFromArray($keys);
        return '
            <div class="lgw-container">
                <div class="lgw-box">
                    <div class="lgw-row">
                        <div class="lgw-col-6 display-inline-block">
                            <h2 class="text-center">' . $translations['title_end_free_trial'] . '</h2>
                            <h3 class="text-center">' . $translations['subtitle_end_free_trial'] . '</h3>
                            <p class="text-center">' . $translations['first_description_end_free_trial'] . '</p>
                            <p class="text-center">' . $translations['second_description_end_free_trial'] . '</p>
                            <p class="text-center">' . $translations['third_description_end_free_trial'] . '</p>
                            <div class="text-center">
                                <a href="http://my.lengow.io/" class="lgw-btn" target="_blank">
                                    ' . $translations['upgrade_account_button'] . '
                                </a>
                            </div>
                            <div class="text-center">
                                <a href="#" id="lgw-refresh" class="lgw-box-link">'
                                    . $translations['refresh_action'] . '
                                </a>
                            </div>
                        </div>
                        <div class="lgw-col-6">
                            <div class="vertical-center">
                                <img src="' . self::$imgFolder . 'logo-blue.png" class="center-block" alt="lengow"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>' . self::getFooter();
    }

    /**
     * Get bad payer html
     *
     * @return string
     */
    public static function getBadPayer()
    {
        $keys = array(
            'status/screen/' => array(
                'subtitle_bad_payer',
                'first_description_bad_payer',
                'second_description_bad_payer',
                'phone_bad_payer',
                'third_description_bad_payer',
                'facturation_button',
                'refresh_action',
            ),
        );
        $translations = LengowTranslation::getTranslationsFromArray($keys);
        return '
            <div class="lgw-container">
                <div class="lgw-box">
                    <div class="lgw-row">
                        <div class="lgw-col-6">
                            <h3 class="text-center">' . $translations['subtitle_bad_payer'] . '</h3>
                        <p class="text-center">' . $translations['first_description_bad_payer'] . '</p>
                        <p class="text-center">' . $translations['second_description_bad_payer'] . '
                            <a href="mailto:backoffice@lengow.com">backoffice@lengow.com</a>
                            ' . $translations['phone_bad_payer'] . '
                        </p>
                        <p class="text-center">' . $translations['third_description_bad_payer'] . '</p>
                        <div class="text-center">
                            <a href="http://my.lengow.io/" class="lgw-btn" target="_blank">
                                ' . $translations['facturation_button'] . '
                            </a>
                        </div>
                        <div class="text-center">
                            <a href="#" id="lgw-refresh" class="lgw-box-link">'
                                . $translations['refresh_action']
                            . '</a>
                        </div>
                    </div>
                    <div class="lgw-col-6">
                        <div class="vertical-center">
                            <img src="' . self::$imgFolder . 'logo-blue.png" class="center-block" alt="lengow"/>
                        </div>
                    </div>
                </div>
            </div>
            ' . self::getFooter();
    }
}
