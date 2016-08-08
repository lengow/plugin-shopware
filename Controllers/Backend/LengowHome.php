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
                'data'    => $htmlContent
            )
        );
    }
}
