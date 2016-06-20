<?php

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
require_once('../Components/LengowExport.php');
//require_once('../Components/Product.php');

if (Shopware_Plugins_Backend_Lengow_Components_LengowCore::checkIP())
{
    $format             = isset($_REQUEST["format"]) ? $_REQUEST["format"] : null;
    $languageId         = isset($_REQUEST["languageId"]) ? $_REQUEST["lang"] : null;
    $mode               = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
    $productsIds        = isset($_REQUEST["product_ids"]) ? $_REQUEST["product_ids"] : null;
    $limit              = isset($_REQUEST["limit"]) ? (int)$_REQUEST["limit"] : null;
    $offset             = isset($_REQUEST["offset"]) ? (int)$_REQUEST["offset"] : null;
    $stream             = isset($_REQUEST["stream"]) ? (bool)$_REQUEST["stream"] : null;
    $outStock           = isset($_REQUEST["out_stock"]) ? (bool)$_REQUEST["out_stock"] : null;
    $exportVariation    = isset($_REQUEST["export_variation"]) ? (bool)$_REQUEST["export_variation"] : null;
    $exportLengowSelection = isset($_REQUEST["selection"]) ? (bool)$_REQUEST["selection"] : null;
    $exportDisabledProduct = isset($_REQUEST["show_inactive_product"]) ? (bool)$_REQUEST["show_inactive_product"] : null;
    $shop = isset($_REQUEST['shop']) ? $_REQUEST['shop'] : null;

    // If the shop hasn't been filled
    if ($shop) {
        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select(array('shop.id'));
        $builder->from('Shopware\Models\Shop\Shop', 'shop');
        $builder->where('shop.name = :shop');
        $builder->setParameter('shop', $shop);

        $shopId = $builder->getQuery()->getResult()[0]['id'];

        // If the shop exists
        if ($shopId) {
            $shop = Shopware()->Models()->getReference('Shopware\Models\Shop\Shop',(int) $shopId);

            /*$builder = Shopware()->Models()->createQueryBuilder();
            $builder->select(array('settings.id'));
            $builder->from('Shopware\CustomModels\Lengow\Setting', 'settings');
            $builder->where('settings.shop = :shopId');
            $builder->setParameter('shopId', $shopId);

            $settingId = $builder->getQuery()->getResult()[0]['id'];

            if (!$settingId) {
                die('For export, the settings must be completed for the shop '. $_GET['shop'] );
            }*/
        } else {
            die('Invalid Shop for '. $_GET['shop'] );
        }
    }

    $selectedProducts = array();

    if ($productsIds) {
        $ids    = str_replace(array(';','|',':'), ',', $productsIds);
        $ids    = preg_replace('/[^0-9\,]/', '', $ids);
        $selectedProducts  = explode(',', $ids);
    }

    if ($shop) {
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
            'language_id' => $languageId
        );

        $export = new LengowExport($shop, $params);

        if ($mode == 'size') {
            echo $export->exec();
        } else {
            $export->exec();
        }
    } else {
        die('Thank you to specify the name of the shop to export like this : export.php?shop=Deutsch for example.');
    }

} else {
    die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);
}