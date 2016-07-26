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

use Shopware\Kernel;
use Shopware\Components\HttpCache\AppCache;

include '../../../../../../../autoload.php';

require_once('../Bootstrap.php');
require_once('../Components/LengowMain.php');
require_once('../Components/LengowImport.php');
require_once('../Components/LengowConfiguration.php');

$environment = getenv('ENV') ?: getenv('REDIRECT_ENV') ?: 'production';

$kernel = new Kernel($environment, $environment !== 'production');
$kernel->boot();
if ($kernel->isHttpCacheEnabled()) {
    $kernel = new AppCache($kernel, $kernel->getHttpCacheConfig());
}


$em = Shopware()->Models();
$lengowPlugin = $em->getRepository('Shopware\Models\Plugin\Plugin')->findOneBy(array('name' => 'Lengow'));

// If the plugin has not been installed
if ($lengowPlugin == null) {
    header('HTTP/1.1 400 Bad Request');
    die('Lengow module is not installed');
}

// Lengow module is not active
if (!$lengowPlugin->getActive()) {
    header('HTTP/1.1 400 Bad Request');
    die('Lengow module is not active');
}

// Check ip authorization
if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::checkIp()) {
    $sync = isset($_REQUEST["sync"]) ? $_REQUEST["sync"] : false;
    if (!$sync || $sync === 'order') {
        // array of params for import order
        $params = array();
        if (isset($_REQUEST["preprod_mode"])) {
            $params['preprod_mode'] = (bool)$_REQUEST["preprod_mode"];
        }
        if (isset($_REQUEST["log_output"])) {
            $params['log_output'] = (bool)$_REQUEST["log_output"];
        }
        if (isset($_REQUEST["days"])) {
            $params['days'] = (int)$_REQUEST["days"];
        }
        if (isset($_REQUEST["limit"])) {
            $params['limit'] = (int)$_REQUEST["limit"];
        }
        if (isset($_REQUEST["marketplace_sku"])) {
            $params['marketplace_sku'] = (string)$_REQUEST["marketplace_sku"];
        }
        if (isset($_REQUEST["marketplace_name"])) {
            $params['marketplace_name'] = (string)$_REQUEST["marketplace_name"];
        }
        if (isset($_REQUEST["delivery_address_id"])) {
            $params['delivery_address_id'] = (string)$_REQUEST["delivery_address_id"];
        }
        if (isset($_REQUEST["shop_id"])) {
            $params['shop_id'] = (int)$_REQUEST["shop_id"];
        }
        $params['type'] = 'cron';
        // import orders
        $import = new Shopware_Plugins_Backend_Lengow_Components_LengowImport($params);
        $import->exec();
    }
    // sync options between Lengow and Shopware
    // if (!$sync || $sync === 'option') {
    // }
    // sync option is not valid
    if ($sync && ($sync !== 'order' && $sync !== 'action' && $sync !== 'option')) {
        header('HTTP/1.1 400 Bad Request');
        die('Action: '.$sync.' is not a valid action');
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);
}
