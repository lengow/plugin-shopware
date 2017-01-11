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
 * Backend Lengow Sync Controller
 */
class Shopware_Controllers_Backend_LengowSync extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Get sync actions
     * integer isSync     show synchronisation (1) or new merchant page (0)
     * array   syncAction sync action
     */
    public function getIsSyncAction()
    {
        $isSync = $this->Request()->getParam('isSync', false);
        $action = $this->Request()->getParam('syncAction', false);
        if ($action) {
            switch ($action) {
                case 'get_sync_data':
                    $data = array();
                    $data['function'] = 'sync';
                    $data['parameters'] = Shopware_Plugins_Backend_Lengow_Components_LengowSync::getSyncData();
                    $this->View()->assign(
                        array(
                            'success' => true,
                            'data'    => $data
                        )
                    );
                    break;
                case 'sync':
                    $data = json_decode($this->Request()->getParam('data', false), true);
                    Shopware_Plugins_Backend_Lengow_Components_LengowSync::sync($data);
                    Shopware_Plugins_Backend_Lengow_Components_LengowSync::getStatusAccount(true);
                    break;
                case 'refresh_status':
                    Shopware_Plugins_Backend_Lengow_Components_LengowSync::getStatusAccount(true);
                    break;
            }
        } else {
            $this->View()->assign(array('isSync' => $isSync));
        }
    }
}
