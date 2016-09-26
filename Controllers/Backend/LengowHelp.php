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
class Shopware_Controllers_Backend_LengowHelp extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Construct help page content
     */
    public function getHelpContentAction()
    {
        $keys = array(
            'help/screen/' => array(
                'title',
                'go_to_lengow',
                'contain_text_support',
                'contain_text_support_hour',
                'find_answer',
                'link_shopware_guide',
                'mailto_subject',
                'mail_lengow_support_title',
                'need_some_help',
                'link_lengow_support',
                'title_lengow_support',
            ),
            'dashboard/screen/' => array(
                'help_center_link'
            )
        );
        $translations = Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::getTranslationsFromArray($keys);
        $html = '<div class="lgw-container">
                <div class="lgw-box lengow_help_wrapper text-center">
                    <h2>'.$translations['title'].'</h2>
                    <p>'.$translations['contain_text_support'].' 
                        <a href="'.$translations['link_lengow_support'].'" target="_blank" title="Lengow Support">
                        '.$translations['title_lengow_support'].'
                        </a>
                    </p>
                    <p>'.$translations['contain_text_support_hour'].'</p>
                    <p>'.$translations['find_answer'].'
                        <a href="'.$translations['help_center_link'].'" target="_blank" title="Help Center">
                        '.$translations['link_shopware_guide'].'
                        </a>
                    </p>
                </div>
            </div>';
        $html.= Shopware_Plugins_Backend_Lengow_Components_LengowElements::getFooter();
        $this->View()->assign(
            array(
                'success' => true,
                'data'    => $html
            )
        );
    }
}
