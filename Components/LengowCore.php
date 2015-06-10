<?php

/**
 * LengowCore.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowCore
{

	/**
     * Version.
     */
    const VERSION = '1.0.0';

    /**
    * Registers.
    */
    public static $registers = array();

    /**
	 * Lengow export format.
	 */
	public static $FORMAT_LENGOW = array(
		'csv',
		'xml',
		'json',
		'yaml',
	);

    /**
     * Number of images to export
     */
    public static $EXPORT_IMAGE = array(3, 4, 5, 6, 7, 8, 9, 10);

    /**
     * Log file instance.
     */
    public static $log_instance;

    /**
     * integer life of log files in days
     */
    public static $LOG_LIFE = 7;

    /**
    * Lengow shipping name.
    */
    public static $SHIPPING_LENGOW = array(
        'lengow' => 'Lengow',
        'marketplace' => 'Marketplace\'s name',
    );

    /**
    * Lengow XML Marketplace configuration.
    */
    public static $MP_CONF_LENGOW = 'http://kml.lengow.com/mp.xml';

	/**
     * Lengow IP.
     */
    public static $IPS_LENGOW = array(
        '95.131.137.18',
        '95.131.137.19',
        '95.131.137.21',
        '95.131.137.26',
        '95.131.137.27',
        '88.164.17.227',
        '88.164.17.216',
        '109.190.78.5',
        '95.131.141.168',
        '95.131.141.169',
        '95.131.141.170',
        '95.131.141.171',
        '82.127.207.67',
        '80.14.226.127',
        '80.236.15.223'
    );

	/**
	 * The images number to export.
     * 
	 * @return array Images count option
	 */
	public static function getImagesCount()
	{
		$arrayImageCount = array();
		foreach (self::$EXPORT_IMAGE as $value)
			$arrayImageCount[] = new Shopware_Plugins_Backend_Lengow_Components_LengowOption($value, $value);
		return $arrayImageCount;
	}

    /**
     * The images size to export.
     * 
     * @return array Images size option
    */
    public static function getImagesSize()
    {
        $sqlParams['name'] = 'Artikel';
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS album.id as id
                FROM s_media_album album
                WHERE album.name = :name";
        $idAlbum = Shopware()->Db()->fetchOne($sql, $sqlParams);
        $articleAlbum = Shopware()->Models()->getReference('Shopware\Models\Media\Album',(int) $idAlbum);
        $imageSizes = $articleAlbum->getSettings()->getThumbnailSize();
        $arrayImageSize = array();
        foreach ($imageSizes as $value)
            $arrayImageSize[] = new Shopware_Plugins_Backend_Lengow_Components_LengowOption($value, $value);
        return $arrayImageSize;
    }

	/**
	 * The export format aivalable.
     * 
	 * @return array Formats
	 */
	public static function getExportFormats()
	{
		$arrayFormats = array();
		foreach (self::$FORMAT_LENGOW as $value)
			$arrayFormats[] = new Shopware_Plugins_Backend_Lengow_Components_LengowOption($value, $value);
		return $arrayFormats;
	}

    /**
     * Get all dispatch (Shipping cost and carrier).
     * 
     * @return array Dispatch
     */
    public static function getDispatch()
    {
        $sqlParams['active'] = 1;
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS dispatch.id as id, dispatch.name as name
                FROM s_premium_dispatch dispatch
                WHERE dispatch.active = :active";
        $carriers = Shopware()->Db()->fetchAll($sql, $sqlParams);
        $arrayCarriers = array();
        foreach ($carriers as $value)
            $arrayCarriers[] = new Shopware_Plugins_Backend_Lengow_Components_LengowOption($value['id'], $value['name']);
        return $arrayCarriers;
    }

    /**
     * The shipping names options.
     *
     * @return array Lengow shipping names option
     */
    public static function getShippingName() 
    {
        $arrayShipping = array();
        foreach (self::$SHIPPING_LENGOW as $name => $value) {
            $arrayShipping[] = new Shopware_Plugins_Backend_Lengow_Components_LengowOption($name, $value);
        }
        return $arrayShipping;
    }

    /**
     * Get all order states.
     * 
     * @return array order states
     */
    public static function getAllOrderStates()
    {
        $sqlParams['group'] = 'state';
        $sqlParams['id'] = -1;
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS states.id as id, states.description as description
                FROM s_core_states states
                WHERE states.group = :group
                AND states.id != :id ";
        $states = Shopware()->Db()->fetchAll($sql, $sqlParams);
        $arrayStates = array();
        foreach ($states as $value)
            $arrayStates[] = new Shopware_Plugins_Backend_Lengow_Components_LengowOption($value['id'], $value['description']);
        return $arrayStates;
    }

    /**
     * Get all admin users
     * 
     * @return array  
     */
    public static function getAllAdminUsers()
    {
        $sqlParams = array();
        $sqlParams['name'] = 'local_admins';
        $sqlParams['active'] = 1;
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS sca.id, sca.username, sca.name, sca.email
                FROM s_core_auth sca
                LEFT JOIN s_core_auth_roles scar ON sca.roleID = scar.id
                WHERE sca.active = :active
                AND scar.name = :name ";
        return Shopware()->Db()->fetchAll($sql, $sqlParams);
    }

    /**
     * Get host for generated email.
     *
     * @return string Hostname
     */
    public static function getHost()
    {   
        $shop = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('default' => 1));
        $domain = $shop->getHost();
        preg_match('`([a-zàâäéèêëôöùûüîïç0-9-]+\.[a-z]+$)`', $domain, $out);
        if ($out[1]) {
            return $out[1];
        }
        return $domain;
    }

    /**
     * Get the base url of the plugin
     * 
     * @return string
     */
    public static function getBaseUrl()
    {
        $shop = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('default' => 1));
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
        $host = $shop->getHost();
        $path = ($shop->getBasePath() ? $shop->getBasePath() : '');
        $url = 'http' . $is_https . '://' . $host . $path;
        return $url;
    }

    /**
     * Get the path of the plugin
     * 
     * @return string
     */
    public static function getPathPlugin()
    {
        $path = Shopware()->Plugins()->Backend()->Lengow()->Path();
        $index = strpos($path, '/engine');
        return substr($path, $index);
    }

    /**
     * Get the configuration of the module
     * 
     * @return int
     */
    public static function getConfig()
    {
       return Shopware()->Plugins()->Backend()->Lengow()->Config();
    }

    /**
     * Get settings of the module
     * 
     * @return int
     */
    public static function getSetting($idShop)
    {
        $sqlParams['shopId'] = (int) $idShop;
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS settings.id 
                FROM lengow_settings as settings 
                WHERE settings.shopID = :shopId";
        $settingId = Shopware()->Db()->fetchOne($sql, $sqlParams);  
        return Shopware()->Models()->getReference('Shopware\CustomModels\Lengow\Setting',(int) $settingId);
    }

    /**
     * Get Lengow Configuration of the module
     *
     * @param integer $name
     * @return mixed
     */
    public static function getConfigLengow($name)
    {
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS $name FROM lengow_config WHERE id = 1";
        return Shopware()->Db()->fetchOne($sql);  
    }

    /**
     * Set Lengow Configuration of the module
     *
     * @param integer $name
     */
    public static function setConfigLengow($name, $value)
    {
        $sqlParams['value'] = htmlspecialchars($value);
        $sql = "UPDATE lengow_config SET $name = :value WHERE id = 1";
        Shopware()->Db()->query($sql, $sqlParams);  
    }

    /**
     * Check if current IP is authorized.
     * 
     * @return boolean.
     */
    public static function checkIp() 
    {
        $ips = self::getConfig()->get('lengowAuthorisedIp');
        $ips = trim(str_replace(array("\r\n", ',', '-', '|', ' '), ';', $ips), ';');
        $ips = explode(';', $ips);
        $authorizedIps = array_merge($ips, self::$IPS_LENGOW);
        $hostnameIp = $_SERVER['REMOTE_ADDR'];
        if (in_array($hostnameIp, $authorizedIps))
            return true;
        return false;
    }

    /**
    * Check and update xml of marketplace's configuration.
    */
    public static function updateMarketPlaceConfiguration()
    {
        $sep = '/';
        $mpUpdate = self::getConfigLengow('LENGOW_MP_CONF');
        if (!$mpUpdate || $mpUpdate != date('Y-m-d')) {
            if ($xml = fopen(self::$MP_CONF_LENGOW, 'r')) {
                $handle = fopen(dirname(__FILE__) . $sep . '..' . $sep. 'Config' . $sep . Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace::$XML_MARKETPLACES . '', 'w');
                stream_copy_to_stream($xml, $handle);
                fclose($handle);
                self::setConfigLengow('LENGOW_MP_CONF', date('Y-m-d'));
            }
        }
    }

    /**
     * Get id Customer
     *
     * @param integer $idShop
     * @return string
     */
    public static function getIdCustomer() 
    {
        return self::getConfig()->get('lengowIdUser');
    }

    /**
     * Get group id
     *
     * @param integer $idShop
     * @return string
     */
    public static function getGroupCustomer($idShop, $all = true)
    {
        if ($all) {
            return self::getSetting($idShop)->getLengowIdGroup();
        }
        $group = self::getSetting($idShop)->getLengowIdGroup();
        $arrayGroup = explode(',', $group);
        return $arrayGroup[0];
    }

    /**
     * Get token API
     *
     * @param integer $idShop
     * @return string
     */
    public static function getTokenCustomer() 
    {
        return self::getConfig()->get('lengowApiKey');
    }

    /**
     * Get IPs Authorised
     *
     * @return string
     */
    public static function getIPs() 
    {
        return self::getConfig()->get('lengowAuthorisedIp');
    }

    /**
     * Export all product or only selected
     *
     * @param integer $idShop
     * @return boolean
     */
    public static function isExportAllProducts($idShop) 
    {
        return (self::getSetting($idShop)->getLengowExportAllProducts() == 1 ? true : false);
    }

    /**
     * Export all product or only selected
     *
     * @param integer $idShop
     * @return boolean
     */
    public static function exportAllProducts($idShop) 
    {
        return (self::getSetting($idShop)->getLengowExportDisabledProducts() == 1 ? true : false);
    }

    /**
     * Export variant products
     *
     * @param integer $idShop
     * @return boolean
     */
    public static function isExportFullmode($idShop) 
    {
        return (self::getSetting($idShop)->getLengowExportVariantProducts() == 1 ? true : false);
    }

    /**
     * Export attributes
     *
     * @param integer $idShop
     * @return boolean
     */
    public static function getExportAttributes($idShop) 
    {
        return (self::getSetting($idShop)->getLengowExportAttributes() == 1 ? true : false);
    }

    /**
    * Export only title or title + attribute
    *
    * @param integer $idShop
    * @return boolean
    */
    public static function exportTitle($idShop)
    {
        return (self::getSetting($idShop)->getLengowExportAttributesTitle() == 1 ? true : false);
    }

    /**
    * Export out of stock product
    *
    * @param integer $idShop
    * @return boolean
    */
    public static function exportOutOfStockProduct($idShop)
    {
        return (self::getSetting($idShop)->getLengowExportOutStock() == 1 ? true : false);
    }

    /**
     * Export image size
     *
     * @param integer $idShop
     * @return string
     */
    public static function getExportImagesSize($idShop) 
    {
        return self::getSetting($idShop)->getLengowExportImageSize();
    }

    /**
     * Export max images
     *
     * @param integer $idShop
     * @return string
     */
    public static function getExportImages($idShop) 
    {
        return self::getSetting($idShop)->getLengowExportImages();
    }

    /**
	* The export format used
    *
    * @param integer $idShop
	* @return varchar Format
	*/
	public static function getExportFormat($idShop)
	{
        return self::getSetting($idShop)->getLengowExportFormat();
	}

    /**
     * Export with cron
     *
     * @param integer $idShop
     * @return boolean
     */
    public static function getExportCron($idShop) 
    {
        return (self::getSetting($idShop)->getLengowExportCron() == 1 ? true : false);
    }

    /**
     * Default Shipping Cost
     *
     * @param integer $idShop
     * @return object Dispatch Shopware
     */
    public static function getDefaultShippingCost($idShop) 
    {
        return self::getSetting($idShop)->getLengowShippingCostDefault();
    }

    /**
     * Export product in file
     *
     * @param integer $idShop
     * @return boolean
     */
    public static function getExportInFile($idShop) 
    {
        return (self::getSetting($idShop)->getLengowExportFile() == 1 ? true : false);
    }

    /**
     * Default Carrier
     *
     * @param integer $idShop
     * @return object Dispatch Shopware
     */
    public static function getDefaultCarrier($idShop) 
    {
        return self::getSetting($idShop)->getLengowCarrierDefault();
    }

    /**
     * Get the Id of new status order.
     *
     * @param varchar $version The version to compare
     * @param integer $idShop
     * @return object State Shopware
     */
    public static function getOrderState($state, $idShop) 
    {
        switch ($state) {
            case 'process' :
            case 'processing' :
                return self::getSetting($idShop)->getLengowOrderProcess();
            case 'shipped' :
                return self::getSetting($idShop)->getLengowOrderShipped();
            case 'cancel' :
            case 'canceled' :
                return self::getSetting($idShop)->getLengowOrderCancel();
        }
        return false;
    }

    /**
     * Get number days of import
     *
     * @param integer $idShop
     * @return integer
     */
    public static function getCountDaysToImport($idShop) 
    {
        return self::getSetting($idShop)->getLengowImportDays();
    }

    /**
     * Get Payement Method Name
     *
     * @param integer $idShop
     * @return string The method name
     */
    public static function getPaymentMethodName($idShop)
    {
        return self::getSetting($idShop)->getLengowMethodName();
    }

    /**
     * Get Force Price
     *
     * @param integer $idShop
     * @return boolean
     */
    public static function getForcePrice($idShop)
    {
        return (self::getSetting($idShop)->getLengowForcePrice() == 1 ? true : false);
    }

    /**
     * Send admin email when order is imported
     *
     * @param integer $idShop
     * @return boolean
     */
    public static function sendEmailAdmin($idShop)
    {
        return (self::getSetting($idShop)->getLengowReportMail() == 1 ? true : false);
    }

    /**
     * The email address for sending
     *
     * @param integer $idShop
     * @return string
     */
    public static function getEmailAddress($idShop)
    {
        return self::getSetting($idShop)->getLengowEmailAddress();
    }

    /**
     * Export with cron
     *
     * @param integer $idShop
     * @return boolean
     */
    public static function getImportCron($idShop) 
    {
        return (self::getSetting($idShop)->getLengowImportCron() == 1 ? true : false);
    }

    /**
     * Debug mode actived
     *
     * @param integer $idShop
     * @return boolean
     */
    public static function isDebug() 
    {
        return (self::getConfig()->get('lengowDebugMode') == 1 ? true : false);
    }

    /**
     * The shipping names options.
     *
     * @return array Lengow shipping names option
     */
    public static function getMarketplaceSingleton($name)
    {
        if (!isset(self::$registers[$name]))
            self::$registers[$name] = new Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace($name);
        return self::$registers[$name];
    }

    /**
     * Load Lengow Payment Shopware
     *
     * @return Payment Shopware
     */
    public static function getLengowPayment()
    {
        return Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment')->findOneBy(array('name' => 'Lengow'));
    }

    /**
     * Log
     * 
     * @param string $txt The float to format
     * @param boolean $force_output
     * @param boolean $force_output
     */
    public static function log($txt, $forceOutput = false, $logInterface = false) 
    {
        $sep = '/';
        $debug = self::getConfig()->get('lengowDebugMode');
        if ($forceOutput !== -1) {
            if ($debug || $forceOutput) {
                echo date('Y-m-d : H:i:s') . ' - ' . $txt . '<br />' . "\r\n";
                flush();
            }
        }
        if ($logInterface) {
            $log = new Shopware\CustomModels\Lengow\Log();
            $log->setMessage($txt);
            Shopware()->Models()->persist($log);
            Shopware()->Models()->flush();
        }
        if (!is_resource(self::$log_instance)) {
            self::$log_instance = fopen(dirname(__FILE__) . $sep . '..' . $sep . 'Logs' . $sep . 'logs-' . date('Y-m-d') . '.txt', 'a+');
        }
        fwrite(self::$log_instance, date('Y-m-d : H:i:s - ') . $txt . "\r\n");
    }

    /**
     * Suppress log files when too old.
     */
    public static function cleanLog() 
    {
        $sep = '/';
        $days = array();
        $days[] = 'logs-'.date('Y-m-d').'.txt';
        for ($i = 1; $i < self::$LOG_LIFE; $i++) {
            $days[] = 'logs-'.date('Y-m-d', strtotime('-'.$i.'day')).'.txt';
        }
        if ($handle = opendir(dirname(__FILE__) . $sep . '..' . $sep . 'Logs' . $sep)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if (!in_array($entry, $days))
                        unlink(dirname(__FILE__) . $sep . '..' . $sep . 'Logs' . $sep . $entry);
                }
            }
            closedir($handle);
        }
    }

    /**
     * Clean phone number
     *
     * @param string $phone Phone to clean
     */
    public static function cleanPhone($phone)
    {
        $replace = array('.', ' ', '-', '/');
        if (!$phone) {
            return null;
        }
        return str_replace($replace, '', preg_replace('/[^0-9]*/', '', $phone));
    }

    /**
     * Export Cron
     */
    public static function exportCron() 
    {
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS shops.id AS id FROM s_core_shops shops";
        $shops = Shopware()->Db()->fetchAll($sql);
        foreach ($shops as $shop) {
            if(self::getExportCron($shop['id'])) {
                $shop = Shopware()->Models()->getReference('Shopware\Models\Shop\Shop',(int) $shop['id']); 
                $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport(null, null, null, null, null, null, null, null, $shop);
                $export->exec();
            }
        }   
    }

    /**
     * Import Cron
     */
    public static function importCron() 
    {
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS shops.id AS id FROM s_core_shops shops";
        $shops = Shopware()->Db()->fetchAll($sql);
        foreach ($shops as $shop) {
            if(self::getImportCron($shop['id'])) {
                $shop = Shopware()->Models()->getReference('Shopware\Models\Shop\Shop',(int) $shop['id']); 
                $dateTo = date('Y-m-d');
                $days = (int) Shopware_Plugins_Backend_Lengow_Components_LengowCore::getCountDaysToImport($shop->getId());
                $dateFrom = date('Y-m-d', strtotime(date('Y-m-d').' -'.$days.'days'));
                $import = new Shopware_Plugins_Backend_Lengow_Components_LengowImport($shop);
                $import->exec('orders', array('dateFrom' => $dateFrom, 'dateTo' => $dateTo));
            }
        }   
    }

    /**
    * Clean html
    * 
    * @param string $html The html content
    * @return string Text cleaned.
    */
    public static function cleanHtml($html)
    {
        $string = str_replace('<br />', '', nl2br($html));
        $string = trim(strip_tags(htmlspecialchars_decode($string)));
        $string = preg_replace('`[\s]+`sim', ' ', $string);
        $string = preg_replace('`"`sim', '', $string);
        $string = nl2br($string);
        $pattern = '@<[\/\!]*?[^<>]*?>@si'; //nettoyage du code HTML
        $string = preg_replace($pattern, ' ', $string);
        $string = preg_replace('/[\s]+/', ' ', $string); //nettoyage des espaces multiples
        $string = trim($string);
        $string = str_replace('&nbsp;', ' ', $string);
        $string = str_replace('|', ' ', $string);
        $string = str_replace('"', '\'', $string);
        $string = str_replace('’', '\'', $string);
        $string = str_replace('&#39;', '\' ', $string);
        $string = str_replace('&#150;', '-', $string);
        $string = str_replace(chr(9), ' ', $string);
        $string = str_replace(chr(10), ' ', $string);
        $string = str_replace(chr(13), ' ', $string);
        return $string;
    }

    /**
    * Replace all accented chars by their equivalent non accented chars
    * 
    * @param string $str
    * @return string
    */
    public static function replaceAccentedChars($str)
    {
        /* One source among others:
          http://www.tachyonsoft.com/uc0000.htm
          http://www.tachyonsoft.com/uc0001.htm
        */
        $patterns = array(
            /* Lowercase */
            /* a */ '/[\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}\x{0101}\x{0103}\x{0105}]/u',
            /* c */ '/[\x{00E7}\x{0107}\x{0109}\x{010D}]/u',
            /* d */ '/[\x{010F}\x{0111}]/u',
            /* e */ '/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}]/u',
            /* g */ '/[\x{011F}\x{0121}\x{0123}]/u',
            /* h */ '/[\x{0125}\x{0127}]/u',
            /* i */ '/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}]/u',
            /* j */ '/[\x{0135}]/u',
            /* k */ '/[\x{0137}\x{0138}]/u',
            /* l */ '/[\x{013A}\x{013C}\x{013E}\x{0140}\x{0142}]/u',
            /* n */ '/[\x{00F1}\x{0144}\x{0146}\x{0148}\x{0149}\x{014B}]/u',
            /* o */ '/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}\x{014D}\x{014F}\x{0151}]/u',
            /* r */ '/[\x{0155}\x{0157}\x{0159}]/u',
            /* s */ '/[\x{015B}\x{015D}\x{015F}\x{0161}]/u',
            /* ss */ '/[\x{00DF}]/u',
            /* t */ '/[\x{0163}\x{0165}\x{0167}]/u',
            /* u */ '/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{0169}\x{016B}\x{016D}\x{016F}\x{0171}\x{0173}]/u',
            /* w */ '/[\x{0175}]/u',
            /* y */ '/[\x{00FF}\x{0177}\x{00FD}]/u',
            /* z */ '/[\x{017A}\x{017C}\x{017E}]/u',
            /* ae */ '/[\x{00E6}]/u',
            /* oe */ '/[\x{0153}]/u',
            /* Uppercase */
            /* A */ '/[\x{0100}\x{0102}\x{0104}\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}]/u',
            /* C */ '/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}]/u',
            /* D */ '/[\x{010E}\x{0110}]/u',
            /* E */ '/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}]/u',
            /* G */ '/[\x{011C}\x{011E}\x{0120}\x{0122}]/u',
            /* H */ '/[\x{0124}\x{0126}]/u',
            /* I */ '/[\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}]/u',
            /* J */ '/[\x{0134}]/u',
            /* K */ '/[\x{0136}]/u',
            /* L */ '/[\x{0139}\x{013B}\x{013D}\x{0139}\x{0141}]/u',
            /* N */ '/[\x{00D1}\x{0143}\x{0145}\x{0147}\x{014A}]/u',
            /* O */ '/[\x{00D3}\x{014C}\x{014E}\x{0150}]/u',
            /* R */ '/[\x{0154}\x{0156}\x{0158}]/u',
            /* S */ '/[\x{015A}\x{015C}\x{015E}\x{0160}]/u',
            /* T */ '/[\x{0162}\x{0164}\x{0166}]/u',
            /* U */ '/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{0168}\x{016A}\x{016C}\x{016E}\x{0170}\x{0172}]/u',
            /* W */ '/[\x{0174}]/u',
            /* Y */ '/[\x{0176}]/u',
            /* Z */ '/[\x{0179}\x{017B}\x{017D}]/u',
            /* AE */ '/[\x{00C6}]/u',
            /* OE */ '/[\x{0152}]/u');
        // ö to oe
        // å to aa
        // ä to ae
        $replacements = array(
            'a', 'c', 'd', 'e', 'g', 'h', 'i', 'j', 'k', 'l', 'n', 'o', 'r', 's', 'ss', 't', 'u', 'y', 'w', 'z', 'ae', 'oe',
            'A', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'L', 'N', 'O', 'R', 'S', 'T', 'U', 'Z', 'AE', 'OE'
        );
        return preg_replace($patterns, $replacements, $str);
    }


}