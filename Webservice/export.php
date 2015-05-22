<?php

use Shopware\Kernel;
use Shopware\Components\HttpCache\AppCache;
use Symfony\Component\HttpFoundation\Request;

include '../../../../../../../autoload.php';

$environment = getenv('ENV') ?: getenv('REDIRECT_ENV') ?: 'production';

$kernel = new Kernel($environment, $environment !== 'production');
$kernel->boot();
if ($kernel->isHttpCacheEnabled()) {
    $kernel = new AppCache($kernel, $kernel->getHttpCacheConfig());
}

require_once('../Components/LengowCore.php');
require_once('../Components/LengowExport.php');
require_once('../Components/LengowProduct.php');

	
if (Shopware_Plugins_Backend_Lengow_Components_LengowCore::checkIP())
{
	/* Force GET parameters */
	$format = null;
	$all = null;
    $all_products = null;
    $fullmode = null;
    $export_attributes = null;
    $full_title = null;
    $out_stock = null;
    $stream = null;
    $shop = null;

    if (array_key_exists('format', $_GET) && $_GET['format'] != '' && in_array($_GET['format'], Shopware_Plugins_Backend_Lengow_Components_LengowCore::$FORMAT_LENGOW)) {
        $format = $_GET['format'];
    }

    if(array_key_exists('all', $_GET)) {
        if($_GET['all'] == 1){
            $all = true;
        }
        elseif($_GET['all'] == 0) {
            $all = false;
        }
    }

    if(array_key_exists('all_products', $_GET)) {
        if($_GET['all_products'] == 1){
            $all_products = true;
        }
        elseif($_GET['all_products'] == 0) {
            $all_products = false;
        }
    }

    if(array_key_exists('fullmode', $_GET)) {
        if($_GET['fullmode'] == 'full'){
            $fullmode = true;
        }
        elseif($_GET['fullmode'] == 'simple'){
            $fullmode = false;
        }
    }

    if(array_key_exists('export_attributes', $_GET)) {
        if($_GET['export_attributes'] == 1){
            $export_attributes = true;
        }
        elseif($_GET['export_attributes'] == 0){
            $export_attributes = false;
        }
    }

    if(array_key_exists('full_title', $_GET)) {
        if($_GET['full_title'] == 1){
            $full_title = true;
        }
        elseif($_GET['full_title'] == 0){
            $full_title = false;
        }
    }

    if(array_key_exists('out_stock', $_GET)) {
        if($_GET['out_stock'] == 1){
            $out_stock = true;
        }
        elseif($_GET['out_stock'] == 0){
            $out_stock = false;
        }
    }

    if(array_key_exists('stream', $_GET)) {
        if($_GET['stream'] == 1){
            $stream = true;
        }
        elseif($_GET['stream'] == 0){
            $stream = false;
        }
    }

    if (array_key_exists('shop', $_GET) && $_GET['shop'] != '') {
        
        // Checking if the shop exist
        $sqlParams = array();
        $sqlParams["nameShop"] = $_GET['shop'];
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS shops.id
                FROM s_core_shops shops
                WHERE shops.name = :nameShop";
        $idShop = Shopware()->Db()->fetchOne($sql, $sqlParams);
       
        if ($idShop) {
            $shop = Shopware()->Models()->find('Shopware\Models\Shop\Shop', $idShop);    

            // Checking if the settings exist
            $sqlParamSetting = array();
            $sqlParamSetting['shopId'] = $idShop;
            $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS settings.id 
                    FROM lengow_settings as settings 
                    WHERE settings.shopID = :shopId ";
            $settingId = Shopware()->Db()->fetchOne($sql, $sqlParamSetting);

            // $settings = array();

            // $settings['TestIP'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getIp($idShop);
            // $settings['lengowIdUser'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getIdCustomer($idShop);
            // $settings['lengowIdGroup'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getGroupCustomer(true, $idShop);
            // $settings['lengowApiKey'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getTokenCustomer($idShop); 
            // $settings['lengowExportAllProducts'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::isExportAllProducts($idShop);
            // $settings['lengowExportDisabledProducts'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::exportAllProducts($idShop);
            // $settings['lengowExportVariantProducts'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::isExportFullmode($idShop);
            // $settings['lengowExportAttributes'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportAttributes($idShop);
            // $settings['lengowExportAttributesTitle'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::exportTitle($idShop);
            // $settings['lengowExportOutStock'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::exportOutOfStockProduct($idShop);
            // $settings['lengowExportImageSize'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportImagesSize($idShop);
            // $settings['lengowExportImages'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportImages($idShop);
            // $settings['lengowExportFormat'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportFormat($idShop);
            // $settings['lengowExportFile'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportInFile($idShop);
            // $settings['lengowCarrierDefault'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getDefaultCarrier($idShop);
            // $settings['lengowOrderProcess'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getOrderState('process', $idShop);
            // $settings['lengowOrderShipped'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getOrderState('shipped', $idShop);
            // $settings['lengowOrderCancel'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getOrderState('cancel', $idShop);
            // $settings['lengowImportDays'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getCountDaysToImport($idShop);
            // $settings['lengowMethodName'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getPaymentMethodName($idShop);
            // $settings['lengowForcePrice'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getForcePrice($idShop);
            // $settings['lengowReportMail'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::sendEmailAdmin($idShop);
            // $settings['lengowEmailAddress'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getEmailAddress($idShop);
            // $settings['lengowExportCron'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportCron($idShop);
            // $settings['lengowDebug'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::isDebug($idShop);
            // print_r($settings);

            // die();

            if (!$settingId) {
                die('For export, the settings must be completed for the shop '. $_GET['shop'] );
            }
        } else {
            die('Invalid Shop for '. $_GET['shop'] );
        }
    }

    if ($shop) {
        $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($format, $all, $all_products, $fullmode, $export_attributes, $full_title, $out_stock, $stream, $shop);
        $export->exec();
        die();
    } else {
        die('Thank you to specify the name of the shop to export like this : export.php?shop=Deutsch for example.');
    }

} else {
 	die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);
}


