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
require_once('../Components/LengowException.php');
require_once('../Components/LengowExport.php');
require_once('../Components/LengowFeed.php');
require_once('../Components/LengowTranslation.php');
require_once('../Components/LengowProduct.php');
require_once('../Components/LengowFile.php');
require_once('../Components/LengowLog.php');
require_once('../Components/LengowConfiguration.php');

$environment = getenv('ENV') ?: getenv('REDIRECT_ENV') ?: 'production';

$kernel = new Kernel($environment, $environment !== 'production');
$kernel->boot();
if ($kernel->isHttpCacheEnabled()) {
    $kernel = new AppCache($kernel, $kernel->getHttpCacheConfig());
}
require_once('../Components/LengowSync.php');
var_dump(Shopware_Plugins_Backend_Lengow_Components_LengowSync::getOptionData());
die();

if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::checkIp()) {
    $mode                   = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
    $format                 = isset($_REQUEST["format"]) ? $_REQUEST["format"] : 'csv';
    $stream                 = isset($_REQUEST["stream"]) ? (bool)$_REQUEST["stream"] : true;
    $offset                 = isset($_REQUEST["offset"]) ? (int)$_REQUEST["offset"] : null;
    $limit                  = isset($_REQUEST["limit"]) ? (int)$_REQUEST["limit"] : null;
    $exportLengowSelection  = isset($_REQUEST["selection"]) ? (bool)$_REQUEST["selection"] : null;
    $outStock               = isset($_REQUEST["out_of_stock"]) ? (bool)$_REQUEST["out_of_stock"] : null;
    $productsIds            = isset($_REQUEST["product_ids"]) ? $_REQUEST["product_ids"] : null;
    $logOutput              = isset($_REQUEST["log_output"]) ? (bool)$_REQUEST["log_output"] : !$stream;
    $exportVariation        = isset($_REQUEST["variation"]) ? (bool)$_REQUEST["variation"] : null;
    $exportDisabledProduct  = isset($_REQUEST["inactive"]) ? (bool)$_REQUEST["inactive"] : null;
    $languageId             = isset($_REQUEST["language"]) ? $_REQUEST["language"] : null;
    $shopId                 = isset($_REQUEST['shop']) ? $_REQUEST['shop'] : null;
    $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
    // If shop name has been filled
    if ($shopId) {
        $shop = $em->getRepository('Shopware\Models\Shop\Shop')->find($shopId);
        // A shop with this name exist
        if ($shop) {
            $selectedProducts = array();
            if ($productsIds) {
                $ids    = str_replace(array(';','|',':'), ',', $productsIds);
                $ids    = preg_replace('/[^0-9\,]/', '', $ids);
                $selectedProducts  = explode(',', $ids);
            }
            $params = array(
                'format'                 => $format,
                'mode'                   => $mode,
                'stream'                 => $stream,
                'productIds'             => $selectedProducts,
                'limit'                  => $limit,
                'offset'                 => $offset,
                'exportOutOfStock'       => $outStock,
                'exportVariationEnabled' => $exportVariation,
                'exportDisabledProduct'  => $exportDisabledProduct,
                'exportLengowSelection'  => $exportLengowSelection,
                'languageId'             => $languageId,
                'logOutput'              => $logOutput
            );
            try {
                $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, $params);
                $export->exec();
            } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
                $error_message = $e->getMessage();
                $decoded_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    $error_message
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/export/export_failed',
                        array('decoded_message' => $decoded_message)
                    ),
                    $logOutput
                );
            }
        } else {
            $shops = $em->getRepository('Shopware\Models\Shop\Shop')->findBy(array('active' => 1));
            $index = count($shops);
            $shopsIds = '[';
            foreach ($shops as $shop) {
                $shopsIds.= $shop->getId();
                $index--;
                $shopsIds.= ($index == 0) ? '' : ', ';
            }
            $shopsIds.= ']';
            die('The following shop ('.$shopId.') does not exist. Please specify a valid shop name in : '.$shopsIds);
        }
    } else {
        die('Please specify a shop to export (ie. : ?shop=1)');
    }
} else {
    die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);
}
