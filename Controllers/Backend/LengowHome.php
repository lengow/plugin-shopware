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
 * Backend Lengow Home Controller
 */
class Shopware_Controllers_Backend_LengowHome extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Construct home page html with translations
     */
    public function getHomeContentAction()
    {
        $status = Shopware_Plugins_Backend_Lengow_Components_LengowSync::getStatusAccount();
        $showTabBar = false;
        if ($status['type'] == 'free_trial' && $status['day'] == 0) {
            $htmlContent = Shopware_Plugins_Backend_Lengow_Components_LengowElements::getEndFreeTrial();
        } elseif ($status['type'] == 'bad_payer') {
            $htmlContent = Shopware_Plugins_Backend_Lengow_Components_LengowElements::getBadPayer();
        } else {
            $htmlContent = Shopware_Plugins_Backend_Lengow_Components_LengowElements::getDashboard();
            $showTabBar = true;
        }
        $this->View()->assign(
            array(
                'success' => true,
                'displayTabBar' => $showTabBar,
                'data' => $htmlContent
            )
        );
    }
}
