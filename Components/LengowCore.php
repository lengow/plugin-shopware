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
	* Lengow export format.
	*/
	public static $FORMAT_LENGOW = array(
		'csv',
		'xml',
		'json',
		'yaml',
	);

	public static $EXPORT_IMAGE = array(3, 4, 5, 6, 7, 8, 9, 10);

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
		$array_ImageCount = array();
		foreach (self::$EXPORT_IMAGE as $value)
			$array_ImageCount[] = new Shopware_Plugins_Backend_Lengow_Components_LengowOption($value, $value);
		return $array_ImageCount;
	}

    /**
    * The images size to export.
    *
    * @return array Images size option
    */
    public static function getImagesSize()
    {
        $sql = '
            SELECT DISTINCT SQL_CALC_FOUND_ROWS 
            album.id as id
            FROM s_media_album album
            WHERE album.name = \'Artikel\'
        ';
        $idAlbum = Shopware()->Db()->fetchOne($sql);
        $articleAlbum = Shopware()->Models()->find('Shopware\Models\Media\Album', $idAlbum);
        $imageSizes = $articleAlbum->getSettings()->getThumbnailSize();
        $array_ImageSize = array();
        foreach ($imageSizes as $value)
            $array_ImageSize[] = new Shopware_Plugins_Backend_Lengow_Components_LengowOption($value, $value);
        return $array_ImageSize;
    }

	/**
	* The export format aivalable.
	*
	* @return array Formats
	*/
	public static function getExportFormats()
	{
		$array_formats = array();
		foreach (self::$FORMAT_LENGOW as $value)
			$array_formats[] = new Shopware_Plugins_Backend_Lengow_Components_LengowOption($value, $value);
		return $array_formats;
	}



    /**
    * Get all carriers.
    *
    * @return array Carrier
    */
    public static function getCarriers()
    {
        $sql = '
            SELECT DISTINCT SQL_CALC_FOUND_ROWS 
            dispatch.name as name
            FROM s_premium_dispatch dispatch
            WHERE dispatch.type = 0 AND dispatch.active = 1
        ';
        $carriers = Shopware()->Db()->fetchAll($sql);
        $array_carriers = array();
        foreach ($carriers as $value)
            $array_carriers[] = new Shopware_Plugins_Backend_Lengow_Components_LengowOption($value['name'], $value['name']);
        return $array_carriers;
    }

    /**
     * Get the path of the module
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
     * Check if current IP is authorized.
     *
     * @return boolean.
     */
    public static function checkIp() 
    {
        $ips = self::getConfig()->get('lengowAuthorisedIp');
        $ips = trim(str_replace(array("\r\n", ',', '-', '|', ' '), ';', $ips), ';');
        $ips = explode(';', $ips);
        $authorized_ips = array_merge($ips, self::$IPS_LENGOW);
        $hostname_ip = $_SERVER['REMOTE_ADDR'];
        if (in_array($hostname_ip, $authorized_ips))
            return true;
        return false;
    }

    /**
     * Get id Customer
     *
     * @return int
     */
    public static function getIdCustomer() 
    {
        return self::getConfig()->get('lengowIdUser');
    }

    /**
     * Get group id
     *
     * @return int
     */
    public static function getGroupCustomer() 
    {
        return self::getConfig()->get('lengowIdGroup');
    }

    /**
     * Get token API
     *
     * @return string
     */
    public static function getTokenCustomer() 
    {
        return self::getConfig()->get('lengowApiKey');
    }

    /**
     * Export all product or only selected
     *
     * @return boolean
     */
    public static function isExportAllProducts() 
    {
        return (self::getConfig()->get('lengowExportAllProducts') == 1 ? true : false);
    }

    /**
     * Export all product or only selected
     *
     * @return boolean
     */
    public static function exportAllProducts() 
    {
        return (self::getConfig()->get('lengowExportDisabledProducts') == 1 ? true : false);
    }

    /**
     * Export variant products
     *
     * @return boolean
     */
    public static function isExportFullmode() 
    {
        return (self::getConfig()->get('lengowExportVariantProducts') == 1 ? true : false);
    }

    /**
     * Export attributes
     *
     * @return boolean
     */
    public static function getExportAttributes() 
    {
        return (self::getConfig()->get('lengowExportAttributes') == 1 ? true : false);
    }

    /**
    * Export only title or title + attribute
    *
    * @return boolean
    */
    public static function exportTitle()
    {
        return (self::getConfig()->get('lengowExportAttributesTitle') == 1 ? true : false);
    }

    /**
    * Export out of stock product
    *
    * @return boolean
    */
    public static function exportOutOfStockProduct()
    {
        return (self::getConfig()->get('lengowExportOutStock') == 1 ? true : false);
    }

    /**
     * Export image size
     *
     * @return string
     */
    public static function getExportImagesSize() 
    {
        return self::getConfig()->get('lengowExportImageSize');
    }

    /**
     * Export max images
     *
     * @return string
     */
    public static function getExportImages() 
    {
        return self::getConfig()->get('lengowExportImages');
    }

    /**
	* The export format used.
	*
	* @return varchar Format
	*/
	public static function getExportFormat()
	{
		return self::getConfig()->get('lengowExportFormat');
	}

    /**
     * Export product in file
     *
     * @return boolean
     */
    public static function getExportInFile() 
    {
        return (self::getConfig()->get('lengowExportFile') == 1 ? true : false);
    }

    /**
     * Default Carrier
     *
     * @return string
     */
    public static function getDefaultCarrier() 
    {
        return self::getConfig()->get('lengowCarrierDefault');
    }

    /**
     * Export with cron
     *
     * @return boolean
     */
    public static function getExportCron() 
    {
        return (self::getConfig()->get('lengowExportCron') == 1 ? true : false);
    }

    /**
    * Clean html.
    *
    * @param string $html The html content
    *
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
    * Replace all accented chars by their equivalent non accented chars.
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