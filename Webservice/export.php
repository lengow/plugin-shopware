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

$environment = getenv('ENV') ?: getenv('REDIRECT_ENV') ?: 'production';

$kernel = new Kernel($environment, $environment !== 'production');
$kernel->boot();
if ($kernel->isHttpCacheEnabled()) {
    $kernel = new AppCache($kernel, $kernel->getHttpCacheConfig());
}

require_once('../Components/LengowCore.php');
require_once('../Components/LengowException.php');
require_once('../Components/LengowExport.php');
require_once('../Components/LengowFeed.php');
require_once('../Components/LengowMain.php');
require_once('../Components/LengowProduct.php');
require_once('../Components/LengowFile.php');
require_once('../Components/LengowLog.php');

if (Shopware_Plugins_Backend_Lengow_Components_LengowCore::checkIp())
{
    $format                 = isset($_REQUEST["format"]) ? $_REQUEST["format"] : 'csv';
    $languageId             = isset($_REQUEST["lang"]) ? $_REQUEST["lang"] : null;
    $mode                   = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
    $productsIds            = isset($_REQUEST["product_ids"]) ? $_REQUEST["product_ids"] : null;
    $limit                  = isset($_REQUEST["limit"]) ? (int)$_REQUEST["limit"] : null;
    $offset                 = isset($_REQUEST["offset"]) ? (int)$_REQUEST["offset"] : null;
    $stream                 = isset($_REQUEST["stream"]) ? (bool)$_REQUEST["stream"] : null;
    $outStock               = isset($_REQUEST["out_stock"]) ? (bool)$_REQUEST["out_stock"] : null;
    $exportVariation        = isset($_REQUEST["export_variation"]) ? (bool)$_REQUEST["export_variation"] : null;
    $exportLengowSelection  = isset($_REQUEST["selection"]) ? (bool)$_REQUEST["selection"] : null;
    $exportDisabledProduct  = isset($_REQUEST["show_inactive_product"]) ? (bool)$_REQUEST["show_inactive_product"] : null;
    $logOutput              = isset($_REQUEST["log_output"]) ? (bool)$_REQUEST["log_output"] : !$stream;
    $shopName               = isset($_REQUEST['shop']) ? $_REQUEST['shop'] : null;

    $em = Shopware()->Models();

    // If shop name has been filled
    if ($shopName) {
        $shop = $em->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('name' => $shopName));

        // A shop with this name exist
        if ($shop) {
            $selectedProducts = array();

            if ($productsIds) {
                $ids    = str_replace(array(';','|',':'), ',', $productsIds);
                $ids    = preg_replace('/[^0-9\,]/', '', $ids);
                $selectedProducts  = explode(',', $ids);
            }

            $params = array(
                'format' => $format,
                'mode' => $mode,
                'stream' => $stream,
                'productIds' => $selectedProducts,
                'limit' => $limit,
                'offset' => $offset,
                'exportOutOfStock' => $outStock,
                'exportVariation' => $exportVariation,
                'exportDisabledProduct' => $exportDisabledProduct,
                'exportLengowSelection' => $exportLengowSelection,
                'languageId' => $languageId,
                'logOutput' => $logOutput
            );

            $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, $params);

            $export->exec();
        } else {
            $shops = $em->getRepository('Shopware\Models\Shop\Shop')->findBy(array('active' => 1));
            $index = count($shops);
            $shopsName = '[';
            foreach ($shops as $shop) {
                $shopsName.= $shop->getName();
                $index--;
                $shopsName.= ($index == 0) ? '' : ', ';
            }
            $shopsName.= ']';
            die('The following shop (' . $shopName . ') does not exist. Please specify a valid shop name in : ' . $shopsName);
        }
    } else {
        die('Please specify a shop to export (ie. : ?shop=Deutsh)');
    }
} else {
    die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);
}