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
class Shopware_Plugins_Backend_Lengow_Components_LengowElements
{
    /**
     * Get Header html
     *
     * @return string Header page html
     */
    public static function getHeader()
    {
        $accountStatus = Shopware_Plugins_Backend_Lengow_Components_LengowSync::getStatusAccount();
        $html = array();
        $isPreProdActive = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowImportPreprodEnabled'
        );
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();
        $preprodTranslation = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
            'menu/preprod_active',
            $locale
        );
        $counterTranslation = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
            'menu/counter',
            $locale,
            array('counter' => $accountStatus['day'])
        );
        $upgradeTranslation = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
            'menu/upgrade_account',
            $locale
        );
        if ($isPreProdActive) {
            $html['lgw-preprod-label'] = '<div id="lgw-preprod" class="adminlengowhome">'.$preprodTranslation.'</div>';
        }
        if ($accountStatus['type'] == 'free_trial' && $accountStatus['day'] != 0) {
            $html['lgw-trial-label'] =
                '<p class="text-right" id="menucountertrial">'.$counterTranslation.
                '<a href="http://my.lengow.io" target="_blank">'.$upgradeTranslation.'</a>
                </p>';
        }
        return $html;
    }

    /**
     * Get Footer html
     *
     * @return string Footer page html
     */
    public static function getFooter()
    {
        $keys = array('footer/' => array('legals', 'plugin_lengow', 'lengow_url'));
        $translations = Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::getTranslationsFromArray($keys);
        $html = '<div class="lgw-container lgw-footer-vold clear">
                <div class="lgw-content-section text-center">
                    <div id="lgw-footer">
                        <p class="pull-right">
                            <a href="#" id="lengowLegalsTab" class="sub-link" title="Legal">
                            '.$translations['legals']
                            .'</a>
                             | '.$translations['plugin_lengow'].' -
                            <a href='.$translations['lengow_url'].' target="_blank" class="sub-link" title="Lengow.com">
                            Lengow.com
                            </a>
                        </p>
                    </div>
                </div>
            </div>';
        return $html;
    }

    /**
     * Get Legals html
     *
     * @return string Legals page html
     */
    public static function getLegals()
    {
        $keys = array('legals/screen/' => array(
            'simplified_company',
            'social_capital',
            'cnil_declaration',
            'company_registration_number',
            'vat_identification_number',
            'address',
            'contact',
            'hosting'));
        $translations = Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::getTranslationsFromArray($keys);
        $html = '<div class="lgw-container">
            <div class="lgw-box lengow_legals_wrapper">
                <h3>SAS Lengow</h3> ' . $translations['simplified_company'].'<br />
                '.$translations['social_capital'].'368 778 € <br />
                '.$translations['cnil_declaration'].'1748784 v 0 <br />
                '.$translations['company_registration_number'].'513 381 434 <br />
                '.$translations['vat_identification_number'] . 'FR42513381434 <br />
                <h3>'.$translations['address'].'</h3>6 rue René Viviani <br /> 44200 Nantes
                <h3>'.$translations['contact'].'</h3> contact@lengow.com <br /> +33 (0)2 85 52 64 14
                <h3>'.$translations['hosting'].'</h3>Linkbynet<br />
                RCS Bobigny : 430 359 927<br />
                5-9 Rue, de l’Industrie – 93200 Saint-Denis<br />
                +33 (0)1 48 13 00 00
            </div>
        </div>';
        return $html;
    }

    /**
     * Get dashboard html
     * @return string Dashboard page html
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
            )
        );
        $stats = Shopware_Plugins_Backend_Lengow_Components_LengowStatistic::get();
        $translations = Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::getTranslationsFromArray($keys);
        $dashboardHtml = '
        <div id="lengow_home_wrapper">
            <div class="lgw-container">
                <div class="lgw-box lgw-home-header text-center">
                    <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/lengow-white-big.png" alt="lengow">
                    <h1>'.$translations['welcome_back'].'</h1>
                    <a href="http://my.lengow.io" class="lgw-btn" target="_blank">
                        '.$translations['go_to_lengow'].'
                    </a>
                </div>
                <div class="lgw-row lgw-home-menu text-center">
                    <div class="lgw-col-6">
                        <a id="lengowExportTab" href="#" class="lgw-box-link">
                            <div class="lgw-box">
                                <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/home-products.png" class="img-responsive">
                                <h2>'.$translations['products_title'].'</h2>
                                <p>'.$translations['products_text'].'</p>
                            </div>
                        </a>
                    </div>
                    <div class="lgw-col-6">
                        <a id="lengowSettingsTab" href="#" class="lgw-box-link">
                            <div class="lgw-box">
                                <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/home-settings.png" class="img-responsive">
                                <h2>'.$translations['settings_title'].'</h2>
                                <p>'.$translations['settings_text'].'</p>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="lgw-box text-center">
                    <div class="lgw-col-12 center-block">
                        <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/picto-stats.png" class="img-responsive">
                    </div>
                    <h2>'.$translations['partner_business'].'</h2>
                    <div class="lgw-row lgw-home-stats">
                        <div class="lgw-col-4 lgw-col-offset-2">
                            <h5>'.$translations['stat_turnover'].'</h5>
                            <span class="stats-big-value">'.$stats['total_order'].' '.$stats['currency'].'</span>
                        </div>
                        <div class="lgw-col-4">
                            <h5>'.$translations['stat_nb_orders'].'</h5>
                            <span class="stats-big-value">'.$stats['nb_order'].'</span>
                        </div>
                    </div>
                    <p>
                        <a href="http://my.lengow.io" target="_blank" class="lgw-btn lgw-btn-white">
                            '.$translations['stat_more_stats'].'
                        </a>
                    </p>
                </div>
                <div class="lgw-box">
                    <h2>'.$translations['some_help_title'].'</h2>
                    <p>
                        <a href="#" id="lengowHelpTab">'.$translations['get_in_touch'].' </a>
                    </p>
                    <p>
                        <a href="'.$translations['help_center_link'].'" target="_blank">'
        .$translations['visit_help_center'].
        '</a> '.$translations['configure_plugin'].'
                    </p>
                </div>
            </div>
            '.Shopware_Plugins_Backend_Lengow_Components_LengowElements::getFooter().'
        </div>';
        return $dashboardHtml;
    }

    /**
     * Get end of free trial html
     * @return string Free trial page html
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
                'refresh_action'
            )
        );
        $translations = Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::getTranslationsFromArray($keys);
        $endFreeTrialHtml = '
            <div class="lgw-container">
                <div class="lgw-box">
                    <div class="lgw-row">
                        <div class="lgw-col-6 display-inline-block">
                            <h2 class="text-center">'.$translations['title_end_free_trial'].'</h2>
                            <h3 class="text-center">'.$translations['subtitle_end_free_trial'].'</h3>
                            <p class="text-center">'.$translations['first_description_end_free_trial'].'</p>
                            <p class="text-center">'.$translations['second_description_end_free_trial'].'</p>
                            <p class="text-center">'.$translations['third_description_end_free_trial'].'</p>
                            <div class="text-center">
                                <a href="http://my.lengow.io/" class="lgw-btn" target="_blank">
                                    '.$translations['upgrade_account_button'].'
                                </a>
                            </div>
                            <div class="text-center">
                                <a href="#" id="lgw-refresh" class="lgw-box-link">'.$translations['refresh_action'].'</a>
                            </div>
                        </div>
                        <div class="lgw-col-6">
                            <div class="vertical-center">
                                <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/logo-blue.png" class="center-block" alt="lengow"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>'.Shopware_Plugins_Backend_Lengow_Components_LengowElements::getFooter();
        return $endFreeTrialHtml;
    }

    /**
     * Get bad payer html
     * @return string Bad payer page html
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
                'refresh_action'
            )
        );
        $translations = Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::getTranslationsFromArray($keys);
        $badPayerHtml = '
            <div class="lgw-container">
                <div class="lgw-box">
                    <div class="lgw-row">
                        <div class="lgw-col-6">
                            <h3 class="text-center">'.$translations['subtitle_bad_payer'].'</h3>
                        <p class="text-center">'.$translations['first_description_bad_payer'].'</p>
                        <p class="text-center">'.$translations['second_description_bad_payer'].'
                            <a href="mailto:backoffice@lengow.com">backoffice@lengow.com</a>
                            '.$translations['phone_bad_payer'].'
                        </p>
                        <p class="text-center">'.$translations['third_description_bad_payer'].'</p>
                        <div class="text-center">
                            <a href="http://my.lengow.io/" class="lgw-btn" target="_blank">
                                '.$translations['facturation_button'].'
                            </a>
                        </div>
                        <div class="text-center">
                            <a href="#" id="lgw-refresh" class="lgw-box-link">'.$translations['refresh_action'].'</a>
                        </div>
                    </div>
                    <div class="lgw-col-6">
                        <div class="vertical-center">
                            <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/logo-blue.png" class="center-block" alt="lengow"/>
                        </div>
                    </div>
                </div>
            </div>
            '.Shopware_Plugins_Backend_Lengow_Components_LengowElements::getFooter();
        return $badPayerHtml;
    }
}
