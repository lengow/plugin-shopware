<?php

use Doctrine\ORM\Query\Expr;

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
        $translations = $this->getTranslations();
        $htmlContent = '
        <div id="lengow_home_wrapper">
            <div class="lgw-container">
                <div class="lgw-box lgw-home-header text-center">
                    <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/lengow-white-big.png" alt="lengow">
                    <h1>' . $translations['welcome_back'] . '</h1>
                    <a href="http://solution.lengow.com" class="lgw-btn" target="_blank">
                        ' . $translations['go_to_lengow'] . '
                    </a>
                </div>
                <div class="lgw-row lgw-home-menu text-center">
                    <div class="lgw-col-6">
                        <div class="shopware-menu-link">
                            <a id="lengowExportTab" href="#" class="lgw-box-link">
                                <div class="lgw-box">
                                    <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/home-products.png" class="img-responsive">
                                    <h2>' . $translations['products_title'] . '</h2>
                                    <p>' . $translations['products_text'] . '</p>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="lgw-col-6">
                        <div class="shopware-menu-link">
                            <a id="lengowSettingsTab" href="#" class="lgw-box-link">
                                <div class="lgw-box">
                                    <img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/home-settings.png" class="img-responsive">
                                    <h2>' . $translations['settings_title'] . '</h2>
                                    <p>' . $translations['settings_text'] . '</p>
                                </div>
                            </a>
                        </div>
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
                            <span class="stats-big-value">' . $stats->total_order . '</span>
                        </div>
                        <div class="lgw-col-4">
                            <h5>' . $translations['stat_nb_orders'] . '</h5>
                            <span class="stats-big-value">' . $stats->nb_order . '</span>
                        </div>
                    </div>
                    <p>
                        <a href="http://solution.lengow.com" target="_blank" class="lgw-btn lgw-btn-white">
                            ' . $translations['stat_more_stats'] . '
                        </a>
                    </p>
                </div>
                <div class="lgw-box">
                    <h2>' . $translations['some_help_title'] . '</h2>
                    <p>
                        <a href="#">' . $translations['get_in_touch'] . ' </a>
                    </p>
                    <p>
                        <a href="https://en.knowledgeowl.com/help/article/link/prestashopv2" target="_blank">' . $translations['visit_help_center'] . '</a>
                        ' . $translations['configure_plugin'] . '
                    </p>
                </div>
            </div>
            <div class="lgw-container lgw-footer-vold clear">
                <div class="lgw-content-section text-center">
                    <div id="lgw-footer">
                        <p class="pull-right">
                            <a href="#" class="sub-link" title="Legal">' . $translations['legals'] . '</a>
                             | ' . $translations['plugin_lengow'] . ' -
                            <a href="http://www.lengow.com" target="_blank" class="sub-link" title="Lengow.com">Lengow.com</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>';
        $this->View()->assign(
            array(
                'success' => true,
                'data'    => $htmlContent
            )
        );
    }

    /**
     * Get translations relative to the home page
     * @return array List of translations
     */
    protected function getTranslations()
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
                'configure_plugin'),
            'footer/' => array(
                'legals',
                'plugin_lengow')
        );
        // Get locale from session
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();
        $translations = array();
        foreach ($keys as $path => $key) {
            foreach ($key as $value) {
                $translations[$value] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    $path . $value,
                    $locale
                );
            }
        }
        return $translations;
    }
}