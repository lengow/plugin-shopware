<?php

/**
 * LengowAddress.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */

class Shopware_Plugins_Backend_Lengow_Components_LengowAddress
{

	public static $ADDRESS_API_NODES = array(
									'society',
									'civility',
									'lastname',
									'firstname',
									'email',
									'address',
									'address_2',
									'address_complement',
									'zipcode',
									'city',
									'country',
									'country_iso',
									'phone_home',
									'phone_office',
									'phone_mobile',
									);

	const BILLING = 'billing';

	const SHIPPING = 'delivery';


	/**
	 * Extract address data from API
	 * 
	 * @param array 			$data 	API nodes name
	 * @param SimpleXmlElement 	$api 	API nodes containing the data
	 * @param string 			$type 	address type (billing or delivery)
	 * @return array
	 */
	public static function extractAddressDataFromAPI($api, $type)
	{
		$temp = array();
		foreach (self::$ADDRESS_API_NODES as $node) {
			$temp[$node] = (string) $api->{$type.'_'.$node};
		}
		return $temp;
	}

}