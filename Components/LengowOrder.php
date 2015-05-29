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
    * Construct new Lengow order
    */
    public function __construct($id_order = null) 
    {
        $this->order = Shopware()->Models()->getReference('Shopware\Models\Order\Order', (int) $id_order);
    }

	/**
	 * Load information from lengow_orders table
	 * 
	 * @return mixed 
	 */
	public static function getOrderIdFromLengowOrders($lengow_id, $feed_id)
	{
		$sqlParams['lengowId'] = $lengow_id;
		$sqlParams['feedId'] = (int) $feed_id;
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



}