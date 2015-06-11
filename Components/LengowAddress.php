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

	/**
	* Current alias of mister.
	*/
	public static $CURRENT_MALE = array('M' ,
										'M.' ,
										'Mr' ,
										'Mr.' ,
										'Mister' ,
										'Monsieur' ,
										'monsieur' ,
										'mister' ,
										'm.' ,
										'mr ' ,
									);

	/**
	* Current alias of miss.
	*/
	public static $CURRENT_FEMALE = array('Mme' ,
										'mme' ,
										'Mm' ,
										'mm' ,
										'Mlle' ,
										'mlle' ,
										'Madame' ,
										'madame' ,
										'Mademoiselle' ,
										'madamoiselle' ,
										'Mrs' ,
										'mrs' ,
										'Mrs.' ,
										'mrs.' ,
										'Miss' ,
										'miss' ,
										'Ms' ,
										'ms' ,
										);

	const BILLING = 'billing';

	const SHIPPING = 'delivery';

	/**
	 * Extract address data from API
	 * 
	 * @param SimpleXmlElement 	$api 	API nodes containing the data
	 * @param string 			$type 	address type (billing or delivery)
	 * @return array
	 */
	public static function extractAddressDataFromAPI($api, $type)
	{
		$temp = array();
		foreach (self::$ADDRESS_API_NODES as $node) {
			$temp[$node] = (string) $api->{$type . '_' . $node};
		}
		return $temp;
	}

	/**
	 * Create Billing Address
	 * 	
	 * @param array   $data  	  	API nodes name
	 * @param string  $type        	Type of object (customer or order)
	 * @param string  $typeAddress 	Address type (billing or shipping)
	 * @param object  $customer 	LengowCustomer
	 * @param string  $orderId 		Order number Lengow
	 * @return object Shopware Address
	 */
	public static function createAddress($data = array(), $type, $typeAddress, $customer = null, $orderId)
	{
		switch ($type) {
			case 'customer':
				if ($typeAddress === 'billing') {
					$address = new Shopware\Models\Customer\Billing();
					$addressAttribute = new Shopware\Models\Attribute\CustomerBilling();
					$address->setPhone(self::getPhoneNumber($data));
					// Generate a unique number for a new customer
					$address->onSave();
				} elseif ($typeAddress === 'shipping') {
					$address = new Shopware\Models\Customer\Shipping();
					$addressAttribute = new Shopware\Models\Attribute\CustomerShipping();
				}
				$address->setCountryId(self::getCountryByIso($data['country_iso'], $type));
				break;
			case 'order':
				if ($typeAddress === 'billing') {
					$address = new Shopware\Models\Order\Billing();					
					$addressAttribute = new Shopware\Models\Attribute\OrderBilling();
					$address->setPhone(self::getPhoneNumber($data));
					$address->setNumber($customer->getNumber());
				} elseif ($typeAddress === 'shipping') {
					$address = new Shopware\Models\Order\Shipping();
					$addressAttribute = new Shopware\Models\Attribute\OrderBilling();
				}
				$address->setCustomer($customer->getCustomer());
				$address->setCountry(self::getCountryByIso($data['country_iso'], $type));
				break;
			default:
				break;
		}
		// Set all data for a new address Shopware 	
		$address->setCompany($data['society']);
		$address->setSalutation(self::getGender($data));
		$address->setFirstName($data['firstname']);
		$address->setLastName($data['lastname']);
		$address->setStreet(self::prepareFieldAddress($data, $orderId));
		$address->setZipCode($data['zipcode']);
		$address->setCity(preg_replace('/[!<>?=+@{}_$%]/sim', '', $data['city']));
		$address->setAttribute($addressAttribute);		
		return $address;
	}

	/**
	 * Prepares fields postal address
	 * 
	 * @param array   $data 	Address data
	 * @param string  $orderId 	Order number Lengow
	 * @return array
	 */
	public static function prepareFieldAddress($data = array(), $orderId) 
	{
		if (empty($data['address']) && empty($data['address_2'])) {
			return 'no address';
		} else {
			$address = preg_replace('/[!<>?=+@{}_$%]/sim', '', $data['address']);
			if (!empty($data['address_2'])) {
				$address .= ' ' . preg_replace('/[!<>?=+@{}_$%]/sim', '', $data['address_2']);
			}
			if (!empty($data['address_complement'])) {
				$address .= ' ' . preg_replace('/[!<>?=+@{}_$%]/sim', '', $data['address_complement']);
			}
			// Check if the address is less than 100 caracters
			if (strlen($address) > 100) {
				$address = substr($address, 0, 100);
				Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
					'Order ' . $orderId . ': (Warning) Address line too long. It has been truncated to 100 caracters', 
					Shopware_Plugins_Backend_Lengow_Components_LengowImport::$force_log_output, 
					true
				);
			}
			return $address;
		}
	}

	/**
	 * Get Phone Number
	 * 
	 * @param array $data 
	 * @return string
	 */
	public static function getPhoneNumber($data = array()) 
	{
		$phone = '';
		if (!empty($data['phone_home'])) {
			return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanPhone($data['phone_home']);
		} elseif (!empty($data['phone_mobile'])) {
			return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanPhone($data['phone_mobile']);
		} elseif (!empty($data['phone_office'])) {
			return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanPhone($data['phone_office']);
		} else {
			return $phone;
		}
	}

	/**
	* Get the real gender
	*
	* @param array $data 
	* @return string
	*/
	public static function getGender($data = array())
	{
		if (!empty($data['society'])) {
			return 'company';
		} elseif (in_array($data['civility'], self::$CURRENT_MALE)) {
			return 'mr';
		} elseif (in_array($data['civility'], self::$CURRENT_FEMALE)) {
			return 'ms';
		} else {
			return '';
		}
	}

	/**
	* Get country id
	*
	* @param string $countryIso
	* @param string $type
	* @return mixed
	*/
	public static function getCountryByIso($countryIso, $type)
	{
		$iso = strtoupper(substr(str_replace(' ', '', $countryIso), 0, 2));
		$country = Shopware()->Models()->getRepository('Shopware\Models\Country\Country')->findOneBy(array('iso' => $iso));
		if ($type === 'customer') {
			return (int) $country->getId();
		}
		return $country;
	}

}