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
	protected function _importOrders($args = array())
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
	protected function _setArgsOrder($args)
	{
		if (array_key_exists('orderid', $args) && $args['orderid'] != '' && array_key_exists('feed_id', $args) && $args['feed_id'] != '')
		{
			$args_order = array(
				'orderid' => $args['orderid'],
				'feed_id' => $args['feed_id'],
				'id_group' => Shopware_Plugins_Backend_Lengow_Components_LengowCore::getGroupCustomer($this->shop->getId())
			);
			self::$force_log_output = -1;
		}
		else
		{
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
	protected function _getImportOrders($lengow_connector, $args_order, $args)
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
	protected function _insertOrders($orders)
	{
		$count_orders_updated = 0;
		$count_orders_added = 0;
		foreach ($orders->order as $order_data) {
			
			$lengow_id = (string) $order_data->order_id;
			$feed_id = (string) $order_data->idFlux;
			$marketplace = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getMarketplaceSingleton((string) $order_data->marketplace);
			$order_state = (string) $order_data->order_status->marketplace;

			if (!self::checkState($order_state, $marketplace)) {
				Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('current order\'s state [' . $order_state . '] makes it unavailable to import', self::$force_log_output, true, $lengow_id);
				continue;
			}

			$test = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getBaseUrl();
			print_r($test);

			die();



			// Update order state if already imported
			$order_id = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getOrderIdFromLengowOrders($lengow_id, $feed_id);

			// Get billing data
			$billing_data = Shopware_Plugins_Backend_Lengow_Components_LengowAddress::extractAddressDataFromAPI(
				$order_data->billing_address, 
				Shopware_Plugins_Backend_Lengow_Components_LengowAddress::BILLING
			);
			if (Shopware_Plugins_Backend_Lengow_Components_LengowCore::isDebug() || empty($billing_data['email'])) {
				$billing_data['email'] = 'generated-email+'.$lengow_id.'@lengow.com';
				if (!Shopware_Plugins_Backend_Lengow_Components_LengowCore::isDebug()) {
					Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Order ' . $lengow_id . ' generate unique email : '.$billing_data['email'], false);
				}
			}
			// Get shipping data
			$shipping_data = Shopware_Plugins_Backend_Lengow_Components_LengowAddress::extractAddressDataFromAPI(
				$order_data->billing_address, 
				Shopware_Plugins_Backend_Lengow_Components_LengowAddress::SHIPPING
			);
			// Create customer based on billing data
			$customer = self::getCustomer($billing_data, $shipping_data);

			// $customer->save();

			print_r($customer);


		}
		// Shopware_Plugins_Backend_Lengow_Components_LengowCore::log($count_orders_added.' order(s) imported', self::$force_log_output, true);
		// Shopware_Plugins_Backend_Lengow_Components_LengowCore::log($count_orders_updated.' order(s) updated', self::$force_log_output, true);
	}

	/**
	 * Create or load customer based on API data
	 * 
	 * @param array $customer_data 	API data
	 * @param bool  $debug 			debug mode
	 * @return LengowCustomer 
	 */
	protected static function getCustomer($billing_data = array(), $shipping_data = array(), $debug = false)
	{
		$customer = new Shopware_Plugins_Backend_Lengow_Components_LengowCustomer($billing_data['email']);
		if ($customer->getId()) {
		 	return $customer;
		}
		// create new customer
		$customer->assign($billing_data, $shipping_data);
		return $customer;
	}
	
	/**
	 * Check if order status is valid and is available for import
	 * 
	 * @param string $order_state
	 * @param LengowMarketplace	$marketplace
	 * @return bool
	 */
	protected static function checkState($order_state, $marketplace)
	{
		if (empty($order_state)) {
			return false;
		}
		if ($marketplace->getStateLengow($order_state) != 'processing' && $marketplace->getStateLengow($order_state) != 'shipped') {
			return false;
		}
		return true;
	}

}	