<?php

/**
 * LengowImport.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowImport
{

	/**
     * Connector API
     */
    private $lengow_connector = null;

	/**
     * Import in progress
     */
	public static $import_start = false;

	/**
     * Show log
     */
    public static $force_log_output = true;

    /**
     * Instance of shop, the article shop
     */
    private $shop = null;

 	/**
    * Construct new Lengow import
    * 
    * @return Exception Error
    */
    public function __construct($shop = null) {
    	$this->shop = $shop;
    }

	/**
	* Construct the import manager
	*
	* @param $command varchar The command of import
	* @param mixed
	*/
	public function exec($command, $args = array())
	{
		switch ($command)
		{
			case 'orders':
				return $this->_importOrders($args);
			case 'singleOrder' :
				return $this->_importOrders($args);
			default:
				return $this->_importOrders($args);
		}
	}

	/**
	 * Makes the Orders API Url.
	 *
	 * @param array $args The arguments to request at the API
	 */
	private function _importOrders($args = array())
	{
		// $this->_setDebug();
		// LengowCore::setImportProcessing();
		// LengowCore::disableSendState();
		self::$import_start = true;

		$this->_setConnector();
		$args_order = $this->_setArgsOrder($args);
		$orders =  $this->_getImportOrders($this->lengow_connector, $args_order, $args);
		$this->_insertOrders($orders->orders);

		self::$import_start = false;
		
		// LengowCore::setImportEnd();

	}

	/**
     * Init connector
     *
     * @return void
     */
    private function _setConnector() 
    {
        $this->lengow_connector = new Shopware_Plugins_Backend_Lengow_Components_LengowConnector(
			(int) Shopware_Plugins_Backend_Lengow_Components_LengowCore::getIdCustomer(), 
			Shopware_Plugins_Backend_Lengow_Components_LengowCore::getTokenCustomer()
		);
        if($this->lengow_connector->error != '') {
            die($this->lengow_connector->error);
        }
    }

    /**
	 * Return the args order
	 * 
	 * @param array $args
	 * @return array
	 */
	private function _setArgsOrder($args)
	{
		if (array_key_exists('orderid', $args) && $args['orderid'] != '' && array_key_exists('feed_id', $args) && $args['feed_id'] != '') {
			$args_order = array(
				'orderid' => $args['orderid'],
				'feed_id' => $args['feed_id'],
				'id_group' => Shopware_Plugins_Backend_Lengow_Components_LengowCore::getGroupCustomer($this->shop->getId())
			);
			self::$force_log_output = -1;
		} else {
			$args_order = array(
				'dateFrom' => $args['dateFrom'],
				'dateTo' => $args['dateTo'],
				'id_group' => Shopware_Plugins_Backend_Lengow_Components_LengowCore::getGroupCustomer($this->shop->getId()),
				'state' => 'plugin'
			);
		}
		return $args_order;
	}

	/**
	 * Return an array of orders
	 * 
	 * @param array $lengow_connector
	 * @param array $args_order
	 * @param array $args
	 * @return array
	 */
	private function _getImportOrders($lengow_connector, $args_order, $args)
	{
		$orders = $lengow_connector->api('commands', $args_order);
		if (!is_object($orders)) {
			Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Error on lengow webservice', self::$force_log_output, true);
			// Shopware_Plugins_Backend_Lengow_Components_LengowCore::setImportEnd();
			die();
		}
		else {
			$find_count_orders = count($orders->orders->order);
			Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Find '.$find_count_orders.' order'.($find_count_orders > 1 ? 's' : ''), self::$force_log_output, true);
		}

		$count_orders = (int) $orders->orders_count->count_total;
		if ($count_orders == 0) {
			Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('No orders to import between '.$args['dateFrom'].' and '.$args['dateTo'], self::$force_log_output, true);
			// Shopware_Plugins_Backend_Lengow_Components_LengowCore::setImportEnd();
			return false;
		}
		return $orders;
	}

	/**
	 * Import orders
	 * 
	 * @param SimpleXmlElement $orders API orders
	 */
	private function _insertOrders($orders)
	{
		$countOrdersUpdated = 0;
		$countOrdersAdded = 0;
		foreach ($orders->order as $order_data) {
			$lengowId = (string) $order_data->order_id;
			$feedId = (string) $order_data->idFlux;
			// Check whether the file marketplace.xml is up to date
			Shopware_Plugins_Backend_Lengow_Components_LengowCore::updateMarketPlaceConfiguration();
			$marketplace = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getMarketplaceSingleton((string) $order_data->marketplace);
			$orderState = (string) $order_data->order_status->marketplace;
			// Update order state if already imported
			$orderId = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getOrderIdFromLengowOrders($lengowId, $feedId);		
			if ($orderId) {
				$order = new Shopware_Plugins_Backend_Lengow_Components_LengowOrder($orderId);
				try {
					$idOrderState = $marketplace->getStateLengow((string)$order_data->order_status->marketplace);
					if ($order->updateState($marketplace, $idOrderState, $this->shop, (string) $order_data->tracking_informations->tracking_number)) {
						$stateName = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getOrderState($idOrderState, $this->shop->getId())->getDescription();
						Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Order ' . $lengowId . ': order\'s state has been updated to "'.$stateName.'"', self::$force_log_output, true);
						$countOrdersUpdated++;
					}
				}
				catch (Exception $e) {
					Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Order ' . $lengowId . ': error while updating state: ' . $e->getMessage(), self::$force_log_output, true);
				}
				unset($order);
				continue;
			}
			// Checks if status is good for import
			if (!self::checkState($orderState, $marketplace)) {
				Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Order ' . $lengowId . ': order\'s state [' . $orderState . '] makes it unavailable to import', self::$force_log_output);
				continue;
			} 
			// Get billing data
			$billingData = Shopware_Plugins_Backend_Lengow_Components_LengowAddress::extractAddressDataFromAPI(
				$order_data->billing_address, 
				Shopware_Plugins_Backend_Lengow_Components_LengowAddress::BILLING
			);
			if (Shopware_Plugins_Backend_Lengow_Components_LengowCore::isDebug() || empty($billingData['email'])) {
				$billingData['email'] = 'generated-email+' . $lengowId . '@' . Shopware_Plugins_Backend_Lengow_Components_LengowCore::getHost();
				if (!Shopware_Plugins_Backend_Lengow_Components_LengowCore::isDebug()) {
					Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Order ' . $lengowId . ' generate unique email : ' . $billingData['email'], false);
				}
			}
			// Get shipping data
			$shippingData = Shopware_Plugins_Backend_Lengow_Components_LengowAddress::extractAddressDataFromAPI(
				$order_data->delivery_address, 
				Shopware_Plugins_Backend_Lengow_Components_LengowAddress::SHIPPING
			);
			// Create customer based on billing and shipping data
			$customer = $this->_getCustomer($billingData, $shippingData);
			// Get Shopware order state from Lengow Order state
			$orderStateLengow = $marketplace->getStateLengow((string) $order_data->order_status->marketplace);
			$shopwareOrderState = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getOrderState($orderStateLengow, $this->shop->getId());
			// Create new order
			$order = $this->_createOrder($customer, $billingData, $shippingData, $shopwareOrderState);		
			// Create a new Lengow Order
			$this->_createLengowOrder($order_data, $order);
			
			$countOrdersAdded++;
			unset($billingData);
			unset($shippingData);
			unset($customer);
			unset($shopwareOrderState);
			unset($order);
		}
		Shopware_Plugins_Backend_Lengow_Components_LengowCore::log($countOrdersAdded . ' order(s) imported', self::$force_log_output, true);
		Shopware_Plugins_Backend_Lengow_Components_LengowCore::log($countOrdersUpdated . ' order(s) updated', self::$force_log_output, true);
	}

	/**
	 * Create or load customer based on API data
	 * 
	 * @param array $billingData 	API data
	 * @param array $shippingData 	API data
	 * @param bool  $debug 			debug mode
	 * @return LengowCustomer 
	 */
	private function _getCustomer($billingData = array(), $shippingData = array(), $debug = false)
	{
		$customer = new Shopware_Plugins_Backend_Lengow_Components_LengowCustomer($billingData['email']);
		if ($customer->getId()) {
		 	return $customer;
		}
		// create new customer
		$customer->assign($billingData, $shippingData, $this->shop);
		return $customer;
	}

	/**
	 * Create order based on API data
	 *	
	 * @param object $customer				LengowCustomer
	 * @param array  $billingData 			API data
	 * @param array  $shippingData 			API data
	 * @param object $shopwareOrderState 	status Shopware
	 * @return LengowOrder
	 */
	private function _createOrder($customer, $billingData, $shippingData, $shopwareOrderState)
	{
		$order = new Shopware_Plugins_Backend_Lengow_Components_LengowOrder();
		// create new order
		$order->assign($customer, $billingData, $shippingData, $this->shop, $shopwareOrderState);
		return $order;
	}

	/**
	 * Create a new Lengow Order
	 *	
	 * @param SimpleXmlElement 	$order_data 	API order
	 * @param object 			$order 			LengowOrder
	 */
	private function _createLengowOrder($order_data, $order)
	{
	 	$lengowOrder = new Shopware\CustomModels\Lengow\Order();
		$orderDate = new \DateTime(trim($order_data->order_purchase_date . ' ' . $order_data->order_purchase_heure));
        $lengowOrder->setIdOrderLengow((string) $order_data->order_id)
           			->setIdFlux((string) $order_data->idFlux)
           			->setMarketplace((string) $order_data->marketplace)
           			->setTotalPaid((float) $order_data->order_amount)
           			->setCarrier((string) $order_data->tracking_informations->tracking_carrier)
           			->setTrackingNumber((string) $order_data->tracking_informations->tracking_number)
           			->setOrderDate($orderDate)
           			->setExtra(json_encode($order_data))
           			->setOrder($order->getOrder());
        Shopware()->Models()->persist($lengowOrder);
        Shopware()->Models()->flush();
    }
	
	/**
	 * Check if order status is valid and is available for import
	 * 
	 * @param SimpleXmlElement 	$orderState
	 * @param object 			$marketplace LengowMarketplace
	 * @return bool
	 */
	public static function checkState($orderState, $marketplace)
	{
		if (empty($orderState)) {
			return false;
		}
		if ($marketplace->getStateLengow($orderState) != 'processing' && $marketplace->getStateLengow($orderState) != 'shipped') {
			return false;
		}
		return true;
	}

}	