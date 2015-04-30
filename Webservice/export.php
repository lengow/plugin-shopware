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
/*
$request = Request::createFromGlobals();
$kernel->handle($request)
       ->send();*/
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
    $stream = null;
    $shop = null;

    if (array_key_exists('format', $_GET) && $_GET['format'] != '' && in_array($_GET['format'], Shopware_Plugins_Backend_Lengow_Components_LengowCore::$FORMAT_LENGOW)) {
        $format = $_GET['format'];
    }

    if(array_key_exists('all', $_GET)) {
        if($_GET['all'] == 1)
            $all = true;
        elseif($_GET['all'] == 0) {
            $all = false;
        }
    }

    if(array_key_exists('all_products', $_GET)) {
        if($_GET['all_products'] == 1)
            $all_products = true;
        elseif($_GET['all_products'] == 0) {
            $all_products = false;
        }
    }

    if(array_key_exists('export_attributes', $_GET)) {
        if($_GET['export_attributes'] == 1)
            $export_attributes = true;
        elseif($_GET['export_attributes'] == 0)
            $export_attributes = false;
    }

    if(array_key_exists('fullmode', $_GET)) {
        if($_GET['fullmode'] == 'full')
            $fullmode = true;
        elseif($_GET['fullmode'] == 'simple')
            $fullmode = false;
    }

    if(array_key_exists('stream', $_GET)) {
        if($_GET['stream'] == 1)
            $stream = true;
        elseif($_GET['stream'] == 0)
            $stream = false;
    }

    if (array_key_exists('shop', $_GET) && $_GET['shop'] != '') {
        $sqlParams = array();
        $sqlParams["nameShop"] = $_GET['shop'];
        $sql = "
            SELECT DISTINCT SQL_CALC_FOUND_ROWS
            shops.id
            FROM s_core_shops shops
            WHERE shops.name = :nameShop
        ";
        $idShop = Shopware()->Db()->fetchOne($sql, $sqlParams);
        if ($idShop) {
            $shop = Shopware()->Models()->find('Shopware\Models\Shop\Shop', $idShop);

            $product = Shopware()->Models()->find('Shopware\Models\Article\Article', 18);

            $idCategoryParent = $shop->getCategory()->getId();
            $categories = $product->getCategories();

            foreach($categories as $category) {
                $pathCategory = explode("|",$category->getPath());

                if(in_array($idCategoryParent, $pathCategory)) {
                    $breadcrumb = $category->getName();
                    $idCategory = (int) $category->getParentId();

                    for ($i=0; $i < count($pathCategory) - 2 ; $i++) { 
                        $category = Shopware()->Models()->find('Shopware\Models\Category\Category', $idCategory);
                        $breadcrumb = $category->getName() . ' > ' . $breadcrumb;
                        $idCategory = (int) $category->getParentId();
                    }

                    print_r(Shopware_Plugins_Backend_Lengow_Components_LengowCore::replaceAccentedChars($breadcrumb));
                    break;
                }
            }

            die();


        } else {
            die('Invalid Shop for '. $_GET['shop'] );
        }
    }

    if ($shop) {
        $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($format, $all, $all_products, $fullmode, $export_attributes, $stream, $shop);
        $export->exec();
        die();
    } else {
        die('Thank you to specify the name of the shop to export like this : export.php?shop=Deutsch for example.');
    }

} else {
	die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);
}


