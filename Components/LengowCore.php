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

	public static $EXPORT_IMAGE = array('all', 3, 4, 5, 6, 7, 8, 9, 10);

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
    public static function check_ip() {
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
    public static function getIdCustomer() {
        return self::getConfig()->get('lengowIdUser');
    }

    /**
     * Get group id
     *
     * @return int
     */
    public static function getGroupCustomer() {
        return self::getConfig()->get('lengowIdGroup');
    }

    /**
     * Get token API
     *
     * @return string
     */
    public static function getTokenCustomer() {
        return self::getConfig()->get('lengowApiKey');
    }

    /**
     * Export all product or only selected
     *
     * @return boolean
     */
    public static function isExportAllProducts() {
        return (self::getConfig()->get('lengowExportAllProducts') == 1 ? true : false);
    }

    /**
     * Export all product or only selected
     *
     * @return boolean
     */
    public static function exportAllProducts() {
        return (self::getConfig()->get('lengowExportDisabledProducts') == 1 ? true : false);
    }

    /**
     * Export attributes
     *
     * @return boolean
     */
    public static function getExportAttributes() {
        return (self::getConfig()->get('lengowExportAttributes') == 1 ? true : false);
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
    public static function getExportInFile() {
        return (self::getConfig()->get('lengowExportFile') == 1 ? true : false);
    }

    /**
     * Export with cron
     *
     * @return boolean
     */
    public static function getExportCron() {
        return (self::getConfig()->get('lengowExportCron') == 1 ? true : false);
    }


}