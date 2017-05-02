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
 * @subpackage  Controllers
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Backend Lengow Help Controller
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
                    <h2>' . $translations['title'] . '</h2>
                    <p>' . $translations['contain_text_support'] . ' 
                        <a href="' . $translations['link_lengow_support'] . '" target="_blank" title="Lengow Support">
                        ' . $translations['title_lengow_support'] . '
                        </a>
                    </p>
                    <p>' . $translations['contain_text_support_hour'] . '</p>
                    <p>' . $translations['find_answer'] . '
                        <a href="' . $translations['help_center_link'] . '" target="_blank" title="Help Center">
                        ' . $translations['link_shopware_guide'] . '
                        </a>
                    </p>
                </div>
            </div>';
        $html .= Shopware_Plugins_Backend_Lengow_Components_LengowElements::getFooter();
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $html
            )
        );
    }
}
