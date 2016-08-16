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

require_once('./config.inc.php');

$check = new Shopware_Plugins_Backend_Lengow_Components_LengowCheck();
$locale = new Shopware_Plugins_Backend_Lengow_Components_LengowTranslation();
if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::checkIp()) {
    echo '
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Lengow Toolbox</title>
            <link rel="stylesheet" href="css/toolbox.css">
            <link rel="stylesheet" href="css/bootstrap-3.3.6.css">
            <link rel="stylesheet" href="css/font-awesome.css">
        </head>
        <body>
            <nav class="navbar navbar-inverse navbar-fixed-top">
                <div class="container">
                    <div class="navbar-header">
                        <a class="navbar-brand" href="index.php">
                            <i class="fa fa-rocket"></i> '.$locale->t('toolbox/menu/lengow_toolbox').'
                        </a>
                    </div>
                    <div id="navbar" class="collapse navbar-collapse">
                        <ul class="nav navbar-nav">
                            <li>
                                <a href="checksum.php">
                                    <i class="fa fa-search"></i> '.$locale->t('toolbox/menu/checksum').'
                                </a>
                            </li>
                            <li>
                                <a href="log.php">
                                    <i class="fa fa-file-text-o"></i> '.$locale->t('toolbox/menu/log').'
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>';
} else {
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://';
    $url = $is_https.$_SERVER['SERVER_NAME'];
    header('Location: '.$url);
}
