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
                'mail_lengow_support',
            ),
            'dashboard/screen/' => array(
                'help_center_link'
            )
        );
        $translations = Shopware_Plugins_Backend_Lengow_Components_LengowElements::getTranslationsFromArray($keys);
        $html = '<div class="lgw-container">
                <div class="lgw-box lengow_help_wrapper text-center">
                    <h2>'.$translations['title'].'</h2>
                    <p>'.$translations['contain_text_support'].' '.$this->getMailTo().'</p>
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

    /**
     * Generate mailTo link for help page
     * @return string
     */
    public function getMailTo()
    {
        $keys = array(
            'help/screen/' => array(
                'mailto_subject',
                'mail_lengow_support_title',
                'need_some_help',
                'mail_lengow_support'
            )
        );
        $translations = Shopware_Plugins_Backend_Lengow_Components_LengowElements::getTranslationsFromArray($keys);
        $mailTo = Shopware_Plugins_Backend_Lengow_Components_LengowSync::getSyncData();
        $mail = 'support.lengow.zendesk@lengow.com';
        $subject = $translations['mailto_subject'];
        $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi('get', '/v3.0/cms');
        $body = '%0D%0A%0D%0A%0D%0A%0D%0A%0D%0A'
            . $translations['mail_lengow_support_title'] . '%0D%0A';
        if (isset($result->cms)) {
            $body .= 'commun_account : '.$result->cms->common_account.'%0D%0A';
        }
        foreach ($mailTo as $key => $value) {
            if ($key == 'domain_name' || $key == 'token' || $key == 'return_url' || $key == 'shops') {
                continue;
            }
            $body .= $key.' : '.$value.'%0D%0A';
        }
        $shops = $mailTo['shops'];
        $i = 1;
        foreach ($shops as $shop) {
            foreach ($shop as $item => $value) {
                if ($item == 'name') {
                    $body .= 'Store '.$i.' : '.$value.'%0D%0A';
                } elseif ($item == 'feed_url') {
                    $body .= $value . '%0D%0A';
                }
            }
            $i++;
        }
        $html = '<a href="mailto:'. $mail;
        $html.= '?subject='. $subject;
        $html.= '&body='. $body .'" ';
        $html.= 'title="'. $translations['need_some_help'].'" target="_blank">';
        $html.=  $translations['mail_lengow_support'];
        $html.= '</a>';
        return $html;
    }
}
