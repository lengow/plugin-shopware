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
class Shopware_Controllers_Backend_Lengow extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Create html which contains Lengow iframe. Called when synchronizing a shop or creating a new account
     */
    public function getSyncIframeAction()
    {
        $panelHtml = '
            <div class="lgw-container">
                <div class="lgw-content-section text-center">
                    <iframe id="lengow_iframe" scrolling="no" style="display: none; overflow-y: hidden;" frameborder="0"></iframe>
                </div>
            </div>
            <input type="hidden" id="lengow_ajax_link">
            <input type="hidden" id="lengow_sync_link">';
        $this->View()->assign(
            array(
                'success' => true,
                'data'    => array(
                    'panelHtml' => $panelHtml,
                    'isNewMerchant' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::isNewMerchant()),
                    'isSync' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::isSync()
            )
        );
    }

    /**
     * Create toolbar html content. Used to display preprod mod and trial version
     */
    public function getToolbarContentAction()
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowSync::getStatusAccount();
        $data = array();
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
            $data['lgw-preprod-label'] = '<div id="lgw-preprod" class="adminlengowhome">'.$preprodTranslation.'</div>';
        }
        if ($accountStatus->type == 'free_trial' && $accountStatus->day != 0) {
            $data['lgw-trial-label'] =
                    '<p class="text-right" id="menucountertrial">'.$counterTranslation.
                        '<a href="http://www.lengow.com/" target="_blank">'.$upgradeTranslation.'</a>
                    </p>';
        }
        $this->View()->assign(
            array(
                'success' => true,
                'data'    => $data
            )
        );
    }
}
