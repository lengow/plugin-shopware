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
        $id_customer = Shopware()->Db()->fetchOne($sql, $sqlParams);  
		if ($id_customer) {
			$this->customer = Shopware()->Models()->getReference('Shopware\Models\Customer\Customer', (int) $id_customer);
		} else {
			$this->customer = new Shopware\Models\Customer\Customer();
		}
    }

 	/**
	 * Get ID of a user Shopware
	 * 
	 * @return mixed 
	 */
	public function getId()
	{
		return $this->customer->getId();
	}

	/**
	 * @see iLengowObject::assign()
	 */
	public function assign($data = array())
	{
		$this->customer->setEmail($data['email']);
		$this->customer->onSave();
	}

	/**
	 * Save a new user Shopware
	 *
	 */
	public function save()
	{
		Shopware()->Models()->persist($this->customer);
        Shopware()->Models()->flush();
	}



}