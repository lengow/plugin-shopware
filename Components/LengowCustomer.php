<?php

/**
 * LengowCustomer.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */

class Shopware_Plugins_Backend_Lengow_Components_LengowCustomer
{

	/**
     * Instance of Shopware Customer
     */
    private $customer = null;

    /**
    * Construct new Lengow order
    */
    public function __construct($email = null) 
    {
    	$sqlParams['mail'] = $email;
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS user.id
                FROM s_user as user 
                WHERE user.email = :mail";
        $idCustomer = Shopware()->Db()->fetchOne($sql, $sqlParams); 

		if (!$idCustomer) {
			$this->customer = new Shopware\Models\Customer\Customer();
		} else {
			$this->customer = Shopware()->Models()->getReference('Shopware\Models\Customer\Customer', (int) $idCustomer);		
		}
    }

    /**
	 * Get customer Shopware
	 * 
	 * @return object customer Shopware 
	 */
	public function getCustomer()
	{
		return $this->customer;
	}

 	/**
	 * Get ID of a user Shopware
	 * 
	 * @return int 
	 */
	public function getId()
	{
		return $this->customer->getId();
	}

	/**
	 * Get Number of a user Shopware
	 * 
	 * @return string 
	 */
	public function getNumber()
	{
		return $this->customer->getBilling()->getNumber();
	}

	/**
	 * Create a new customer Shopware
	 * 
	 * @param array  $billingData  	Billing address data
	 * @param array  $shippingData 	Shipping address data
	 * @param string $orderId 		Order number Lengow
	 * @param object $shop 		 	Shopware Shop\Shop
	 */
	public function assign($billingData = array(), $shippingData = array(), $shop)
	{
		$type = 'customer';
		// Creation of shipping and billing addresses
		$billingAddress = Shopware_Plugins_Backend_Lengow_Components_LengowAddress::createAddress($billingData, $type, 'billing');
		$shippingAddress = Shopware_Plugins_Backend_Lengow_Components_LengowAddress::createAddress($shippingData, $type, 'shipping');
		$customerAttribute = new Shopware\Models\Attribute\Customer();
		// Set all data for a new customer Shopware 	
		$this->customer->setEmail($billingData['email']);
		$this->customer->setBilling($billingAddress);
		$this->customer->setShipping($shippingAddress);
		$this->customer->setShop($shop);
		$this->customer->setGroup($shop->getCustomerGroup());
		$this->customer->setPaymentId(Shopware_Plugins_Backend_Lengow_Components_LengowCore::getLengowPayment()->getId());
		$this->customer->setAttribute($customerAttribute);
		// Set firstLogin and lastLogin
		$this->customer->onSave();
		// Saves the customer data
		Shopware()->Models()->persist($this->customer);
        Shopware()->Models()->flush();;
	}

}