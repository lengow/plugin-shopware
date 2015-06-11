<?php

/**
 * LengowPayment.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowPayment
{

	/**
     * Instance of Shopware Payment\PaymentInstance
     */
    private $payment_instance;

    /**
     * Construct a new LengowPayment
     */
    public function __construct() 
    {
        $this->payment_instance = new Shopware\Models\Payment\PaymentInstance();
    }

    /**
     * Get Payment\PaymentInstance object
     * 
     * @return object Shopware Payment\PaymentInstance
     */
    public function getPayment()
    {
        return $payment_instance;
    }

    /**
     * Create a new Payment\PaymentInstance Shopware
     *
     * @param object  $order        Shopware Order\Order 
     * @param object  $customer     Shopware Customer\Customer
     * @param object  $paymentMean  Shopware Payment\Payment
     * @param array   $data         API nodes name
     */
    public function assign($order, $customer, $paymentMean, $data = array(), $orderId)
    {
        $this->payment_instance->setOrder($order);
        $this->payment_instance->setCustomer($customer);
        $this->payment_instance->setPaymentMean($paymentMean);
        $this->payment_instance->setFirstName($data['firstname']);
        $this->payment_instance->setLastName($data['lastname']);
        $this->payment_instance->setAddress(Shopware_Plugins_Backend_Lengow_Components_LengowAddress::prepareFieldAddress($data, $orderId));
        $this->payment_instance->setZipCode($data['zipcode']);
        $this->payment_instance->setCity(preg_replace('/[!<>?=+@{}_$%]/sim', '', $data['city']));
        $this->payment_instance->setAmount((float) $order->getInvoiceAmount());

        Shopware()->Models()->persist($this->payment_instance);
        Shopware()->Models()->flush();
    }

}