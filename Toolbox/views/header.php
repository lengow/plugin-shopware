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
 * @subpackage  Toolbox
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware_Plugins_Backend_Lengow_Components_LengowCheck as LengowCheck;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

require_once('./config.inc.php');

$check = new LengowCheck();
$locale = new LengowTranslation(LengowTranslation::DEFAULT_ISO_CODE);
if (LengowMain::checkIp(true)) {
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
                            <i class="fa fa-rocket"></i> ' . $locale->t('toolbox/menu/lengow_toolbox') . '
                        </a>
                    </div>
                    <div id="navbar" class="collapse navbar-collapse">
                        <ul class="nav navbar-nav">
                            <li>
                                <a href="checksum.php">
                                    <i class="fa fa-search"></i> ' . $locale->t('toolbox/menu/checksum') . '
                                </a>
                            </li>
                            <li>
                                <a href="log.php">
                                    <i class="fa fa-file-text-o"></i> ' . $locale->t('toolbox/menu/log') . '
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>';
} else {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://';
    $url = $isHttps . $_SERVER['SERVER_NAME'];
    header('Location: ' . $url);
}
