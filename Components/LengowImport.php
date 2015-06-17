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
    */
    public function __construct($shop = null) {
    	$this->shop = $shop;
    }

	/**
	* Construct the import manager
	*
	* @param string $command  The command of import
	* @param array  $args 	  The arguments to request at the API
	* @return mixed
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
		$argsOrder = $this->_setArgsOrder($args);
		$orders =  $this->_getImportOrders($this->lengow_connector, $argsOrder, $args);
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
	 * @param array $args The arguments to request at the API
	 * @return array
	 */
	private function _setArgsOrder($args)
	{
		if (array_key_exists('orderid', $args) && $args['orderid'] != '' && array_key_exists('feed_id', $args) && $args['feed_id'] != '') {
			$argsOrder = array(
				'orderid' => $args['orderid'],
				'feed_id' => $args['feed_id'],
				'id_group' => Shopware_Plugins_Backend_Lengow_Components_LengowCore::getGroupCustomer($this->shop->getId())
			);
			self::$force_log_output = -1;
		} else {
			$argsOrder = array(
				'dateFrom' => $args['dateFrom'],
				'dateTo' => $args['dateTo'],
				'id_group' => Shopware_Plugins_Backend_Lengow_Components_LengowCore::getGroupCustomer($this->shop->getId()),
				'state' => 'plugin'
			);
		}
		return $argsOrder;
	}

	/**
	 * Return an array of orders
	 * 
	 * @param array $lengow_connector
	 * @param array $argsOrder        Complete arguments to request at the API 
	 * @param array $args             The arguments to request at the API
	 * @return SimpleXmlElement
	 */
	private function _getImportOrders($lengow_connector, $argsOrder, $args)
	{
		$orders = $lengow_connector->api('commands', $argsOrder);
		if (!is_object($orders)) {
			Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Error on lengow webservice', self::$force_log_output, true);
			// Shopware_Plugins_Backend_Lengow_Components_LengowCore::setImportEnd();
			die();
		}
		else {
			$findCountOrders = count($orders->orders->order);
			Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Find '.$findCountOrders.' order'.($findCountOrders > 1 ? 's' : ''), self::$force_log_output, true);
		}

		$countOrders = (int) $orders->orders_count->count_total;
		if ($countOrders == 0) {
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
						Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
							'Order ' . $lengowId . ': order\'s state has been updated to "'.$stateName.'"', self::$force_log_output, true
						);
						$countOrdersUpdated++;
					}
				}
				catch (Exception $e) {
					Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
						'Order ' . $lengowId . ': error while updating state: ' . $e->getMessage(), self::$force_log_output, true
					);
				}
				unset($order);
				continue;
			}
			// Checks if status is good for import
			if (!self::checkState($orderState, $marketplace)) {
				Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
					'Order ' . $lengowId . ': order\'s state [' . $orderState . '] makes it unavailable to import', self::$force_log_output, true
				);
				continue;
			} 
			// Get and check the products of the order
			$products = $this->_getProducts($order_data->cart, $marketplace, $lengowId, $this->shop->getId());
			if (!$products) {
				Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
					'Order ' . $lengowId . ': no valid product to import', self::$force_log_output, true
				);
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
					Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
						'Order ' . $lengowId . ': generate unique email : ' . $billingData['email'], false
					);
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
			$order = new Shopware_Plugins_Backend_Lengow_Components_LengowOrder();
			$order->assign($customer, $this->shop, $shopwareOrderState, $order_data, $billingData, $shippingData);
			// add products to order
			$order->addProducts($products, $this->shop);
			// Create a new LengowPayment
			$lengowPayment = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getLengowPayment();
			$payment = new Shopware_Plugins_Backend_Lengow_Components_LengowPayment();
			$payment->assign($order->getOrder(), $customer->getCustomer(), $lengowPayment, $billingData);
			// Create a new LengowOrder
			$this->_createLengowOrder($order_data, $order);

			Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
				'Order ' . $lengowId . ': success import on Shopware (ORDER ' . $order->getOrderNumber() . ')', self::$force_log_output, true
			);
	
			$countOrdersAdded++;

			unset($payment);

			unset($billingData);
			unset($shippingData);
			unset($customer);
			unset($shopwareOrderState);
			unset($order);
		}
		Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
			$countOrdersAdded . ' order(s) imported', self::$force_log_output, true
		);
		Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
			$countOrdersUpdated . ' order(s) updated', self::$force_log_output, true
		);
	}

	/**
	 * Create or load customer based on API data
	 * 
	 * @param array    $billingData   	API data
	 * @param array    $shippingData 	API data
	 * @param string   $lengowID  	 	Order number Lengow
	 * @param boolean  $debug 			Debug mode
	 * @return object LengowCustomer 	
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
           			->setCost((float) $order_data->order_commission)
           			->setExtra(json_encode($order_data))
           			->setOrder($order->getOrder());
        Shopware()->Models()->persist($lengowOrder);
        Shopware()->Models()->flush();
    }

    /**
	 * Get products from API data
	 * 
	 * @param SimpleXMLElement	 $cart_data		API cart data
	 * @param LengowMarketplace	 $marketplace	order marketplace
	 * @param string			 $lengowId		lengow order id
 	 * 
	 * @return array list of products
	 */
	private function _getProducts($cart_data, $marketplace, $lengowId, $idShop)
	{
		$products = array();
		foreach ($cart_data->products as $product) {
			$product = $product->product;
			$productData = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::extractProductDataFromAPI($product);
			if (!empty($productData['status'])) {
				if ($marketplace->getStateLengow((string) $productData['status']) == 'canceled') {
					continue;
				}
			}
			$ids = false;
			$productIds = array(
				(string)$product->sku['field'] => $productData['sku'],
				'sku' => $productData['sku'],
				'idLengow' => $productData['idLengow'],
				'idMP' => $productData['idMP'],
				'ean' => $productData['ean'],
			);
			$found = false;
			foreach ($productIds as $attributeName => $attributeValue) {
				if (empty($attributeValue)) {
					continue;
				}
				$ids = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::matchProduct($attributeName, $attributeValue, $productIds);
				// no product found in the "classic" way => use advanced search
				if (!$ids) {
					Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
						'Order ' . $lengowId . ': product not found with field ' .$attributeName.' ('.$attributeValue.'). Using advanced search.', false
					);
					$ids = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::advancedSearch($attributeValue, $productIds);
				}
				if ($ids) {
					$idFull = $ids['idProduct'];
					if (!isset($ids['idProductDetail'])) {
						$product = new Shopware_Plugins_Backend_Lengow_Components_LengowProduct($ids['idProduct']);
						if ($product->getConfiguratorSet() !== NULL) {
							Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
								'Order ' . $lengowId . ': product id ' . $product->getId() . ' is a parent ID. Product variation needed', true, true
							);
							break;
						}
					}
					$idFull .= isset($ids['idProductDetail']) ? '_' . $ids['idProductDetail'] : '';
					$products[$idFull] = $productData;
					Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
						'Order ' . $lengowId . ': product id ' . $idFull . ' found with field ' . $attributeName . ' (' . $attributeValue . ')', false
					);
					$found = true;
					break;				
				}	
			}
			if (!$found) {
				Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
					'Order ' . $lengowId . ': product ' . $productData['sku'] . ' could not be found', true, true
				);
				return false;
			}
		}
		return $products;
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
