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
class Shopware_Controllers_Backend_LengowSync extends Shopware_Controllers_Backend_ExtJs
{
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
