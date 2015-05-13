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

if (Shopware_Plugins_Backend_Lengow_Components_LengowCore::checkIP())
{
	$import = new Shopware_Plugins_Backend_Lengow_Components_LengowImport();

    if (array_key_exists('idFlux', $_GET) && is_numeric($_GET['idFlux']) && array_key_exists('idOrder', $_GET))
	{		
		// if (Tools::getValue('force') && Tools::getValue('force') > 0) {
		// 	$import->forceImport = true;
		// }

		$import->exec('singleOrder', array(
			'feed_id' => (int) $_GET['idFlux'],
			'orderid' => $_GET['idOrder']
		));
	}
	elseif (!array_key_exists('idFlux', $_GET) && !array_key_exists('idOrder', $_GET))
	{
		$date_to = date('Y-m-d');
		$days = (int) Shopware_Plugins_Backend_Lengow_Components_LengowCore::getCountDaysToImport();

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
	else
		die('Invalid arguments');
}
else
	die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);