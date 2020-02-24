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

use Shopware_Plugins_Backend_Lengow_Components_LengowConnector as LengowConnector;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowElements as LengowElements;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;

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
            <div class="lgw-container" style="height: 100%;">
                <div class="lgw-content-section text-center" style="height: 100%;">
                    <iframe id="lengow_iframe" 
                        scrolling="yes"
                        style="display: none;"
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
                    'isNewMerchant' => LengowConfiguration::isNewMerchant(),
                    'langIsoCode' => substr(LengowMain::getLocale(), 0, 2),
                    'lengowUrl' => LengowConnector::LENGOW_URL,
                ),
            )
        );
    }

    /**
     * Create toolbar html content. Used to display debug mode and trial version
     */
    public function getToolbarContentAction()
    {
        $this->View()->assign(
            array(
                'success' => true,
                'data' => LengowElements::getHeader(),
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
                'data' => LengowElements::getLegals(),
            )
        );
    }
}
