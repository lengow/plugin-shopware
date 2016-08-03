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
     * @return string
     */
    public static function getHeader()
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowSync::getStatusAccount();
        $html = array();
        $accountStatus = json_decode(Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowAccountStatus'
        ));
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
            array('counter' => $accountStatus->day)
        );
        $upgradeTranslation = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
            'menu/upgrade_account',
            $locale
        );
        if ($isPreProdActive) {
            $html['lgw-preprod-label'] = '<div id="lgw-preprod" class="adminlengowhome">'.$preprodTranslation.'</div>';
        }
        if ($accountStatus->type == 'free_trial' && $accountStatus->day != 0) {
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
     * @return string
     */
    public static function getFooter()
    {
        $keys = array('footer/' => array('legals', 'plugin_lengow', 'lengow_url'));
        $translations = self::getTranslationsFromArray($keys);
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
     * @return string
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
        $translations = self::getTranslationsFromArray($keys);
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
     * Get translation from array
     *
     * @param string $keys
     *
     * @return array
     */
    public static function getTranslationsFromArray($keys)
    {
        // Get locale from session
        $locale = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale();
        $translations = array();
        foreach ($keys as $path => $key) {
            foreach ($key as $value) {
                $translations[$value] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    $path.$value,
                    $locale
                );
            }
        }
        return $translations;
    }
}
