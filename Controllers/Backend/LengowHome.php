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
class Shopware_Controllers_Backend_LengowHome extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Construct home page html with translations
     */
    public function getHomeContentAction()
    {
        $stats = Shopware_Plugins_Backend_Lengow_Components_LengowStatistic::get();
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
                'configure_plugin')
        );
        $translations = Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::getTranslationsFromArray($keys);
        $htmlContent = '
        <div id="lengow_home_wrapper">
            <div class="lgw-container">
                <div class="lgw-box lgw-home-header text-center">
                    <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/lengow-white-big.png" alt="lengow">
                    <h1>' . $translations['welcome_back'] . '</h1>
                    <a href="http://my.lengow.io" class="lgw-btn" target="_blank">
                        ' . $translations['go_to_lengow'] . '
                    </a>
                </div>
                <div class="lgw-row lgw-home-menu text-center">
                    <div class="lgw-col-6">
                        <a id="lengowExportTab" href="#" class="lgw-box-link">
                            <div class="lgw-box">
                                <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/home-products.png" class="img-responsive">
                                <h2>' . $translations['products_title'] . '</h2>
                                <p>' . $translations['products_text'] . '</p>
                            </div>
                        </a>
                    </div>
                    <div class="lgw-col-6">
                        <a id="lengowSettingsTab" href="#" class="lgw-box-link">
                            <div class="lgw-box">
                                <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/home-settings.png" class="img-responsive">
                                <h2>' . $translations['settings_title'] . '</h2>
                                <p>' . $translations['settings_text'] . '</p>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="lgw-box text-center">
                    <div class="lgw-col-12 center-block">
                        <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/picto-stats.png" class="img-responsive">
                    </div>
                    <h2>' . $translations['partner_business'] . '</h2>
                    <div class="lgw-row lgw-home-stats">
                        <div class="lgw-col-4 lgw-col-offset-2">
                            <h5>' . $translations['stat_turnover'] . '</h5>
                            <span class="stats-big-value">' . $stats['total_order'] . ' ' . $stats['currency'] . '</span>
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
                <div class="lgw-box">
                    <h2>' . $translations['some_help_title'] . '</h2>
                    <p>
                        <a href="#" id="lengowHelpTab">' . $translations['get_in_touch'] . ' </a>
                    </p>
                    <p>
                        <a href="' . $translations['help_center_link'] . '" target="_blank">'
                            .$translations['visit_help_center'].
                        '</a> ' . $translations['configure_plugin'] . '
                    </p>
                </div>
            </div>
            ' . Shopware_Plugins_Backend_Lengow_Components_LengowElements::getFooter() . '
        </div>';
        $this->View()->assign(
            array(
                'success' => true,
                'data'    => $htmlContent
            )
        );
    }
}