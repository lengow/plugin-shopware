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
require_once('../Components/LengowImport.php');

if (Shopware_Plugins_Backend_Lengow_Components_LengowCore::checkIP())
{
	if (array_key_exists('shop', $_GET) && $_GET['shop'] != '') {   
        // Checking if the shop exist
        $sqlParams = array();
        $sqlParams["nameShop"] = $_GET['shop'];
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS shops.id
                FROM s_core_shops shops
                WHERE shops.name = :nameShop";
        $idShop = Shopware()->Db()->fetchOne($sql, $sqlParams);
       
        if ($idShop) {
            $shop = Shopware()->Models()->getReference('Shopware\Models\Shop\Shop',(int) $idShop);    

            // Checking if the settings exist
            $sqlParamSetting = array();
            $sqlParamSetting['shopId'] = $idShop;
            $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS settings.id 
                    FROM lengow_settings as settings 
                    WHERE settings.shopID = :shopId ";
            $settingId = Shopware()->Db()->fetchOne($sql, $sqlParamSetting);
            
            if (!$settingId) {
                die('For export, the settings must be completed for the shop '. $_GET['shop'] );
            }

            $import = new Shopware_Plugins_Backend_Lengow_Components_LengowImport($shop);

            if (array_key_exists('idFlux', $_GET) && is_numeric($_GET['idFlux']) && array_key_exists('idOrder', $_GET)) {		
				// if (Tools::getValue('force') && Tools::getValue('force') > 0) {
				// 	$import->forceImport = true;
				// }

				$import->exec('singleOrder', array(
					'feed_id' => (int) $_GET['idFlux'],
					'orderid' => $_GET['idOrder']
				));
			}
			elseif (!array_key_exists('idFlux', $_GET) && !array_key_exists('idOrder', $_GET)) {
				$date_to = date('Y-m-d');
				$days = (int) Shopware_Plugins_Backend_Lengow_Components_LengowCore::getCountDaysToImport($idShop);

				if (array_key_exists('days', $_GET)) {
					$days = (int) $_GET['days'];
				}

				$date_from = date('Y-m-d', strtotime(date('Y-m-d').' -'.$days.'days'));

				$import->exec('orders', array(
					'dateFrom' => $date_from,
					'dateTo' => $date_to,
				));
				exit();	
			}
			else {
				die('Invalid arguments');
			}
        } else {
            die('Invalid Shop for '. $_GET['shop'] );
        }
    } else {
        die('Thank you to specify the name of the shop to export like this : export.php?shop=Deutsch for example.');
    }  
}
else {
	die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);
}
