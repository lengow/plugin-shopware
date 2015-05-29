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

} else {
	die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);
}