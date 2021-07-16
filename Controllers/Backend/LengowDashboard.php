<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @copyright   2021 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowElements as LengowElements;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowSync as LengowSync;

/**
 * Backend Lengow Dashboard Controller
 */
class Shopware_Controllers_Backend_LengowDashboard extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Construct home page html with translations
     */
    public function getDashboardContentAction()
    {
        $accountStatusData = LengowSync::getStatusAccount();
        $freeTrialExpired = $accountStatusData
            && $accountStatusData['type'] === 'free_trial'
            && $accountStatusData['expired'];
        // recovery of all plugin data for plugin update
        $newVersionIsAvailable = false;
        $showUpdateModal = false;
        $pluginVersion = Shopware()->Plugins()->Backend()->Lengow()->getVersion();
        $pluginData = LengowSync::getPluginData();
        if (!$freeTrialExpired && $pluginData && version_compare($pluginData['version'], $pluginVersion, '>')) {
            $newVersionIsAvailable = true;
            // show upgrade plugin modal or not
            $showUpdateModal = $this->showPluginUpgradeModal();
        }
        if ($freeTrialExpired) {
            $htmlContent = LengowElements::getEndFreeTrial();
        } else {
            $htmlContent = LengowElements::getDashboard($pluginData, $accountStatusData, $showUpdateModal);
        }
        $this->View()->assign(
            array(
                'success' => true,
                'freeTrialExpired' => $freeTrialExpired,
                'newVersionIsAvailable' => $newVersionIsAvailable,
                'showUpdateModal' => $showUpdateModal,
                'data' => $htmlContent,
            )
        );
    }

    /**
     * Refresh status account action
     */
    public function refreshStatusAction()
    {
        LengowSync::getStatusAccount(true);
        $this->View()->assign(array('success' => true));
    }

    /**
     * Set back the display date of the update modal by 7 days
     */
    public function remindMeLaterAction()
    {
        $timestamp = time() + (7 * 86400);
        LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_PLUGIN_MODAL, $timestamp);
        $this->View()->assign(array('success' => true));
    }


    /**
     * Checks if the plugin upgrade modal should be displayed or not
     *
     * @return boolean
     */
    private function showPluginUpgradeModal()
    {
        $updatedAt = LengowConfiguration::getConfig(LengowConfiguration::LAST_UPDATE_PLUGIN_MODAL);
        if ($updatedAt !== null && (time() - (int) $updatedAt) < 86400) {
            return false;
        }
        LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_PLUGIN_MODAL, time());
        return true;
    }
}
