<?php

/**
 * LengowConnector.php
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
				'id_group' => Shopware_Plugins_Backend_Lengow_Components_LengowCore::getGroupCustomer()
			);
			self::$force_log_output = -1;
		}
		else
		{
			$args_order = array(
				'dateFrom' => $args['dateFrom'],
				'dateTo' => $args['dateTo'],
				'id_group' => Shopware_Plugins_Backend_Lengow_Components_LengowCore::getGroupCustomer(),
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


	
}	