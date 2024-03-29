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
     * Create html which contains Lengow connection process
     */
    public function getConnectionAction()
    {
        $this->View()->assign(
            array(
                'success' => true,
                'data' => array(
                    'panelHtml' =>  LengowElements::getConnectionHome(),
                    'isNewMerchant' => LengowConfiguration::isNewMerchant(),
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

    /**
     * Get toolbox tab content
     * Display all information for plugin support
     */
    public function getToolboxTabContentAction()
    {
        $this->View()->assign(
            array(
                'success' => true,
                'data' => LengowElements::getToolbox(),
            )
        );
    }
}
