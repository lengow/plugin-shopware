<?php

/**
 * LengowOrder.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */

class Shopware_Plugins_Backend_Lengow_Components_LengowOrder
{

	/**
     * Instance of order
     */
    private $order = null;

    /**
     * Instance of state
     */
    private $currentState = null;

    /**
     * Instance of order Lengow
     */
    private $orderLengow = null;

 	/**
    * Construct a new LengowOrder
    */
    public function __construct($idOrder = null) 
    {
    	if ($idOrder !== null) {
    		$this->order = Shopware()->Models()->getReference('Shopware\Models\Order\Order', (int) $idOrder);
    		$this->currentState = $this->order->getOrderStatus();
    		$this->orderLengow = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')->findOneBy(array('order' => $this->order));
    	}
    }

    /**
	 * Get order Shopware
	 * 
	 * @return object order Shopware 
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * Get order number
	 * 
	 * @return string
	 */
	public function getOrderNumber()
	{
		return $this->order->getNumber();
	}

    /**
	 * Create a new order Shopware
	 *
	 * @param object 			$customer 		LengowCustomer 
	 * @param object 			$shop 			shop Shopware
	 * @param object 			$orderStatus 	states Shopware
	 * @param SimpleXmlElement 	$order_data 	API order
	 * @param array  			$billingData 	billing address data 
	 * @param array  			$shippingData 	billing address data 
	 */
	public function assign($customer, $shop, $orderStatus, $order_data, $billingData = array(), $shippingData = array())
	{
		// Get the default carrier with its tax 
		$dispatch = $this->_getDispatch((string) $order_data->tracking_informations->tracking_carrier, $shop);
		$taxDispatch = $this->_getTaxDispatch($dispatch);
		$tax = (float) $taxDispatch->getTax();
		// Create a temporary order
		$orderParams = array(
            'ordernumber'          	=> $this->_getOrderNumber(),
            'userID'               	=> (int) $customer->getCustomer()->getId(),
            'invoice_amount'       	=> '',
            'invoice_amount_net'   	=> '',
            'invoice_shipping'     	=> (float) $order_data->order_shipping,
            'invoice_shipping_net' 	=> (float) $order_data->order_shipping*((100-$tax)/100),
            'ordertime'            	=> new Zend_Db_Expr('NOW()'),
            'status'               	=> (int) $orderStatus->getId(),
            'cleared'              	=> 12,
            'paymentID'            	=> (int) Shopware_Plugins_Backend_Lengow_Components_LengowCore::getLengowPayment()->getId(),
            'transactionID'        	=> '',
            'customercomment'      	=> '',
            'net'                  	=> '',
            'taxfree'              	=> '',
            'partnerID'            	=> '',
            'temporaryID'          	=> '',
            'referer'              	=> '',
            'cleareddate'		   	=> new Zend_Db_Expr('NOW()'),
            'trackingcode'         	=> (string) $order_data->tracking_informations->tracking_number,
            'language'             	=> $shop->getId(),
            'dispatchID'           	=> (int) $dispatch->getId(),
            'currency'             	=> (string) $shop->getCurrency()->getCurrency(),
            'currencyFactor'      	=> $shop->getCurrency()->getFactor(),
            'subshopID'            	=> (int) $shop->getId(),
            'remote_addr'          	=> (string) $_SERVER['REMOTE_ADDR']
        );
		Shopware()->Db()->insert('s_order', $orderParams);
		$this->order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(array('number' => $this->_getOrderNumber()));
		// The type for the address
		$type = 'order';
		// Creation of all objects related to an order
		$billingAddress = Shopware_Plugins_Backend_Lengow_Components_LengowAddress::createAddress($billingData, $type, 'billing', $customer, (string) $order_data->order_id);
		$shippingAddress = Shopware_Plugins_Backend_Lengow_Components_LengowAddress::createAddress($shippingData, $type, 'shipping', $customer, (string) $order_data->order_id);
		$orderAttribute = new Shopware\Models\Attribute\Order();
		// Set the order data
		$this->order->setShipping($shippingAddress);
		$this->order->setBilling($billingAddress);
		$this->order->setAttribute($orderAttribute);
		// Saves the order data
		Shopware()->Models()->persist($this->order);
        Shopware()->Models()->flush();
        // Updates the order number for the next order 
        $this->_updateOrderNumber();
	}

	/**
	 * Create a new order Shopware
	 *
	 * @param object $marketplace 		LengowMarketplace
	 * @param string $idOrderState
	 * @param object $shop 				Shop Shopware
	 * @param string $trackingNumber  
	 * @return bool 
	 */
	public function updateState($marketplace, $idOrderState, $shop, $trackingNumber)
	{
		// if state is different between API and Prestashop
		$apiState = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getOrderState($idOrderState, $shop->getId());
		if ($this->currentState != $apiState) {
			if ($this->currentState == Shopware_Plugins_Backend_Lengow_Components_LengowCore::getOrderState('processing', $shop->getId()) && $idOrderState == 'shipped') {
				// Update order status
				$this->_updateOrderStatus($this->order->getId(), $apiState->getId()); 
				// Create an order history with new order status
				$this->_createOrderHistory($apiState);
        		// Import tracking number
				if (!empty($trackingNumber)) {
					$this->order->setTrackingCode($trackingNumber);
					$this->orderLengow->setTrackingNumber($trackingNumber);
					Shopware()->Models()->persist($this->order);
					Shopware()->Models()->persist($this->orderLengow);
        			Shopware()->Models()->flush();
				}
				Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
					'Order ' . $this->orderLengow->getIdOrderLengow() . ': state updated to shipped', true, true
				);
				return true;
			}
			// Change state process or shipped to cancel
			elseif (($this->currentState == Shopware_Plugins_Backend_Lengow_Components_LengowCore::getOrderState('processing', $shop->getId()) 
						|| $this->currentState == Shopware_Plugins_Backend_Lengow_Components_LengowCore::getOrderState('shipped', $shop->getId()))
					&& $idOrderState == 'canceled')
			{
				// Update order status
				$this->_updateOrderStatus($this->order->getId(), $apiState->getId()); 
				// Create an order history with new order status
				$this->_createOrderHistory($apiState);
				Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
					'Order ' . $this->orderLengow->getIdOrderLengow() . ': state updated to cancel', true, true
				);
				return true;
			}
		}
		Shopware_Plugins_Backend_Lengow_Components_LengowCore::log(
			'Order ' . $this->orderLengow->getIdOrderLengow() . ': already imported in Shopware with order ID '. $this->order->getNumber(), true, true
		);
		return false;
	}

	/**
	 * Add product to order
	 * 
	 * @param array   $products List of products to be added
	 * @param object  $shop 	Shopware Shop\Shop
	 * @return bool 
	 */
	public function addProducts($products, $shop)
	{
		if (!$products) {
			throw new Exception('No products to be added to order');
		}
		
		foreach ($products as $id => $product) {
			$ids = explode('_', $id);
			if (count($ids) > 2) {
				throw new Exception('Cannot add product ' . $id . ' to order (ID format invalid)');
			}
			$productId = $ids[0];
			$productdetailId = isset($ids[1]) ? $ids[1] : null;
			// Create a new LengowOrderDetail
			$orderDetail = new Shopware_Plugins_Backend_Lengow_Components_LengowOrderDetail();
			$orderDetail->assign($this->order, $productId, $productdetailId, $product, $shop);	
		}

        Shopware()->Models()->flush();

		return true;
	}

	/**
	 * Load information from lengow_orders table
	 *
	 * @param string $lengowId
	 * @param string $feedId 
	 * @return mixed 
	 */
	public static function getOrderIdFromLengowOrders($lengowId, $feedId)
	{
		$sqlParams['lengowId'] = $lengowId;
		$sqlParams['feedId'] = (int) $feedId;
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS so.id 
                FROM lengow_orders as lo 
                INNER JOIN s_order as so ON lo.orderID = so.id
                WHERE lo.idOrderLengow = :lengowId 
                AND lo.idFlux =  :feedId ";
        $orderId = Shopware()->Db()->fetchOne($sql, $sqlParams);  
		if ($orderId) {
			return $orderId;
		}
		return false;
	}

	/**
	 * Update order Status
	 *
	 * @param int $idOrder
	 * @param int $idState 
	 */
	private function _updateOrderStatus($idOrder, $idState) 
	{
		$sqlParams['idOrder'] = (int) $idOrder;
		$sqlParams['idStatus'] = (int) $idState;
		$sql = "UPDATE s_order
	            SET  status = :idStatus
	            WHERE id = :idOrder ";
	    Shopware()->Db()->query($sql, $sqlParams);
	}

	/**
	 * Create an order history with a new order status
	 *
	 * @param object @newStatus Shopware Order\Status
	 */
	private function _createOrderHistory($newStatus) 
	{				
		$paymentStatus = Shopware()->Models()->getReference('Shopware\Models\Order\Status', 12);
		$history = new Shopware\Models\Order\History();
		$history->setOrder($this->order)
				->setPreviousOrderStatus($this->currentState)
				->setOrderStatus($newStatus)
				->setPreviousPaymentStatus($paymentStatus)
				->setPaymentStatus($paymentStatus)
				->setChangeDate(new \datetime());
		// Get admin user
		$users = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getAllAdminUsers();
		if(count($users) !== 0) {
			$user = Shopware()->Models()->getReference('Shopware\Models\User\User', $users[0]['id']);
			$history->setUser($user);
		}
		// Persit and flush order history
		Shopware()->Models()->persist($history);
		Shopware()->Models()->flush();
	}

	/**
	 * Get carrier calibrated by the user
	 *
	 * @param string $nameCarrier 	name of the carrier
	 * @param object $shop 			Shopware Shop\Shop
	 * @return object Shopware Dispatch\Dispatch
	 */
	private function _getDispatch($nameCarrier, $shop)
	{
		// Check the carrier
		$builder = Shopware()->Models()->createQueryBuilder();
        $query = $builder->select('dispatches')
        				->from('Shopware\Models\Dispatch\Dispatch', 'dispatches')
        				->where('dispatches.name LIKE :value')
                        ->setParameter('value', "%" . $nameCarrier . "%")
        				->getQuery();
        $carriers = $query->getArrayResult();
  		if (count($carriers) !== 0) {
  			return shopware()->Models()->getReference('Shopware\Models\Dispatch\Dispatch', (int) $carriers[0]['id']);
  		}
  		// Get default carrier
		return Shopware_Plugins_Backend_Lengow_Components_LengowCore::getDefaultCarrier($shop->getId());
	}

	/**
	 * Get tax associated with a carrier
	 *
	 * @param object $dispatch Shopware Dispatch\Dispatch
	 * @return object Shopware Tax\Tax
	 */
	private function _getTaxDispatch($dispatch)
	{
		if ($dispatch->getTaxCalculation() !== 0 ) {
			$idTax = (int) $dispatch->getTaxCalculation(); 
		} else {
			$sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS sct.id 
	     		FROM s_core_tax as sct
	            WHERE sct.tax = ( SELECT MAX(tax) from s_core_tax )";
	    	$idTax = Shopware()->Db()->fetchOne($sql);
		}
		return Shopware()->Models()->getReference('Shopware\Models\Tax\Tax', $idTax);
	}

	/**
	 * Get a new order number
	 *
	 * @return int orderNumber
	 */
	private function _getOrderNumber() 
	{
		$sqlParams['name'] = 'invoice';
		$sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS son.number 
	            FROM s_order_number as son
	            WHERE son.name = :name ";
	    $orderNumber = Shopware()->Db()->fetchOne($sql, $sqlParams);
	    return $orderNumber + 1;
	}

	/**
	 * Update order number
	 */
	private function _updateOrderNumber() 
	{
		$sqlParams['name'] = 'invoice';
		$sql = "UPDATE s_order_number
	            SET  number = number + 1
	            WHERE name = :name ";
	    $orderNumber = Shopware()->Db()->query($sql, $sqlParams);
	}
}