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
 * Backend Lengow Controller
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
                    <iframe id="lengow_iframe" 
                        scrolling="yes"
                        style="display: none; overflow-y: hidden;"
                        frameborder="0"></iframe>
                </div>
            </div>
            <input type="hidden" id="lengow_ajax_link">
            <input type="hidden" id="lengow_sync_link">
        ';
        $this->View()->assign(
            array(
                'success' => true,
                'data' => array(
                    'panelHtml' => $panelHtml,
                    'isNewMerchant' => Shopware_Plugins_Backend_Lengow_Components_LengowConnector::isNewMerchant(),
                    'langIsoCode' => substr(Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLocale(), 0, 2)
                )
            )
        );
    }

    /**
     * Create toolbar html content. Used to display preprod mod and trial version
     */
    public function getToolbarContentAction()
    {
        $this->View()->assign(
            array(
                'success' => true,
                'data' => Shopware_Plugins_Backend_Lengow_Components_LengowElements::getHeader()
            )
        );
    }

    /**
     * Get legal tab content
     * Display information about Lengow SAS
     */
    public function getLegalsTabContentAction()
    {
        $this->View()->assign(
            array(
                'success' => true,
                'data' => Shopware_Plugins_Backend_Lengow_Components_LengowElements::getLegals()
            )
        );
    }
}
