<?php

/**
 * LengowMarketplace.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace
{

	public static $XML_MARKETPLACES = 'marketplaces.xml';

	public static $DOM;

	public static $VALID_ACTIONS = array(
								'shipped' ,
								'refuse' ,
								'link' ,
								);

	public static $WSDL_LINK_ORDER = 'https://wsdl.lengow.com/wsdl/link/#MP#/#ID_CLIENT#/#ID_FLUX#/#ORDER_ID#/#INTERNAL_ORDER_ID#/update.xml';

	public $name;

	public $object;

	public $is_loaded = false;

	public $states_lengow = array();

	public $states = array();

	public $actions = array();


	/**
	* Construct a new Markerplace instance with xml configuration.
	*
	* @param string $name The name of the marketplace
	*/
	public function __construct($name)
	{
		$this->_loadXml();
		$this->name = strtolower($name);
		$object = self::$DOM->xpath('/marketplaces/marketplace[@name=\''.$this->name.'\']');
		if (!empty($object)) {
			$this->object = $object[0];
			$this->api_url = (string) $this->object->api;
			foreach ($this->object->states->state as $state) {
				$this->states_lengow[(string) $state['name']] = (string) $state->lengow;
				$this->states[(string) $state->lengow] = (string) $state['name'];
				if (isset($state->actions)) {
					foreach ($state->actions->action as $action) {
						$this->actions[(string) $action['type']] = array();
						$this->actions[(string) $action['type']]['name'] = (string) $action;
						$params = self::$DOM->xpath('/marketplaces/marketplace[@name=\'' . $this->name . '\']/additional_params/param[@usedby=\'' . (string) $action['type'] . '\']');
						if (count($params)) {
							foreach ($params as $param) {
								$this->actions[(string) $action['type']]['params'][(string) $param->type]['name'] = (string) $param->name;
								foreach ($param->attributes() as $key => $value) {
									$this->actions[(string) $action['type']]['params'][ (string)$param->type][$key] = (string)$value;
								}
								if (isset($param->accepted_values)) {
									$this->actions[(string)$action['type']]['params'][(string)$param->type]['accepted_values'] = $param->accepted_values->value;
									$default = self::$DOM->xpath('/marketplaces/marketplace[@name=\'' . $this->name . '\']/additional_params/param[@usedby=\'' . (string) $action['type'] . '\']/accepted_values/value[@default=\'true\']');
									if ($default) {
										$this->actions[(string) $action['type']]['params'][(string) $param->type]['accepted_values_default'] = (string) $default[0];
									}
								}
							}
						}
					}
				}
			}
			$this->is_loaded = true;
		}
	}

	/**
      * If marketplace exist in xml configuration file
      *
      * @return boolean
      */
    public function isLoaded() {
        return $this->is_loaded;
    }

    /**
	* Get the real lengow's state
	*
	* @param string $name The marketplace state
	* @return string The lengow state
	*/
	public function getStateLengow($name)
	{
		return $this->states_lengow[$name];
	}

    /**
      * Get the marketplace's state
      *
      * @param string $name The lengow state
      * @return string The marketplace state
      */
    public function getState($name) {
        return $this->states[$name];
    }

    /**
	* Get the action with parameters
	*
	* @param string $name The action's name
	* @return array
	*/
	public function getAction($name)
	{
		return $this->actions[$name];
	}

	/**
	* If action exist
	*
	* @param string $name The marketplace state
	* @return boolean
	*/
	public function isAction($name)
	{
		return isset($this->actions[$name]) ? true : false;
	}

	/**
	* Call the Lengow WSDL for current marketplace
	*
	* @param string $action The name of the action
	* @param string $id_flux The flux ID
	* @param string $id_order The order ID
	* @param string $args An array of arguments
	*/
	public function wsdl($action, $id_flux, $id_lengow_order, $args = array())
	{
		if (!in_array($action, self::$VALID_ACTIONS)) {
			return false;
		}
		if (!$this->isAction($action)) {
			return false;
		}
		$order = new Shopware_Plugins_Backend_Lengow_Components_LengowOrder($args['id_order']);

		$call_url = false;
		switch ($action) {
			case 'shipped' :
				$call_url = $this->api_url;
				$call_url = str_replace('#ID_FLUX#', $id_flux, $call_url);
				$call_url = str_replace('#ORDER_ID#', $id_lengow_order, $call_url);
				$action_array = $this->getAction($action);
				$action_callback = $action_array['name'];
				$call_url = str_replace('#ACTION#', $action_callback, $call_url);
				if (isset($action_array['params'])) {
					$gets = array();
					foreach ($action_array['params'] as $type => $param) {
						switch ($type) {
							case 'tracking' :
								// $gets[$type] = array(
								// 			'value' => $tracking_number,
								// 			'name' => $param['name'],
								// 			'require' => (array_key_exists('require', $param) ? explode(' ', $param['require']) : array())
								// 		);
								break;
							case 'carrier' :
								// $carrier = new Carrier($order->id_carrier);
								// $gets[$type] = array(
								// 			'value' => $this->_matchCarrier($param, $carrier->name),
								// 			'name' => $param['name'],
								// 			'require' => (array_key_exists('require', $param) ? explode(' ', $param['require']) : array())
								// 		);
								break;
							case 'tracking_url' :
								// $gets[$type] = array(
								// 		'value' => str_replace('@', $tracking_number, $carrier->url),
								// 		'name' => $param['name'],
								// 		'require' => (array_key_exists('require', $param) ? explode(' ', $param['require']) : array())
								// 	);
								break;
							case 'shipping_price' :
								// $gets[$type] = array(
								// 			'value' => $order->total_shipping,
								// 			'name' => $param['name'],
								// 			'require' => (array_key_exists('require', $param) ? explode(' ', $param['require']) : array())
								// 		);
								break;
						}
					}
					if (count($gets) > 0) {
						// Check dependencies in parameters
						foreach ($gets as $param_name => $param_attr) {
							if (!empty($param_attr['require'])) {
								// Check if value of require is not null
								foreach ($param_attr['require'] as $required) {
									if ($gets[$required]['value'] == '') {
										unset($gets[$param_name]);
										unset($gets[$required]);
									}
								}
							}
						}
					}
					// Build URL
					$url = array();
					foreach ($gets as $key => $value) {
						$key = $key;
						$url[] = $value['name'].'='.urlencode($value['value']);
					}
					$call_url .= '?'.implode('&', $url);
				}
				break;
			case 'refuse' :
				$call_url = $this->api_url;
				$call_url = str_replace('#ID_FLUX#', $id_flux, $call_url);
				$call_url = str_replace('#ORDER_ID#', $id_lengow_order, $call_url);
				$action_array = $this->getAction($action);
				$action_callback = $action_array['name'];
				$call_url = str_replace('#ACTION#', $action_callback, $call_url);
				if (isset($action_array['params'])) {
					$gets = array();
					foreach ($action_array['params'] as $type => $param) {
						switch ($type) {
							case 'refused_reason' :
								break;
						}
					}
					if (count($gets) > 0) {
						$call_url .= '?'.implode('&', $gets);
					}
				}
				break;
			case 'link' :
				$call_url = self::$WSDL_LINK_ORDER;
				$call_url = str_replace('#MP#', $this->name, $call_url);
				$call_url = str_replace('#ID_CLIENT#', $args['id_client'], $call_url);
				$call_url = str_replace('#ID_FLUX#', $id_flux, $call_url);
				$call_url = str_replace('#ORDER_ID#', $id_lengow_order, $call_url);
				$call_url = str_replace('#INTERNAL_ORDER_ID#', $args['id_order_internal'], $call_url);
		}
		try {
			if ($call_url) {
				if (!Shopware_Plugins_Backend_Lengow_Components_LengowCore::isDebug()) {
					$this->_makeRequest($call_url);
				}
				Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Order ' . $id_lengow_order . ' : call Lengow WSDL ' . $call_url, -1);
			}
		} catch(LengowWsdlException $e) {
			Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Order ' . $id_lengow_order . ' : call error WSDL ' . $call_url, -1);
            Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Order ' . $id_lengow_order . ' : exception ' . $e->getMessage(), -1);
		}
	}

	/**
	 * Load the xml configuration of all marketplaces
	 */
	private function _loadXml()
	{
		$sep = '/';
		if (!self::$DOM) {
			self::$DOM = simplexml_load_file(dirname(__FILE__). $sep .'..'. $sep .'Config'. $sep . self::$XML_MARKETPLACES);
		}
	}

	/**
      * Makes an HTTP request.
      *
      * @param string $url The URL to make the request to
      * @return string The response text
      */
    protected function _makeRequest($url) 
    {
        $ch = curl_init();
        // Options
        $opts = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::$CURL_OPTS;
        $opts[CURLOPT_URL] = $url;
        // Exectute url request
        curl_setopt_array($ch, $opts);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        if ($result === false) {
            throw new LengowWsdlException(
                array('message' => curl_error($ch),
                      'type' => 'CurlException',
                ),
                curl_errno($ch)
            );
        }
        curl_close($ch);
        return $result;
    }

}


/**
 * Thrown when an WSDL call returns an exception.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 */
class LengowWsdlException extends Exception {

    /**
     * The result from the WSDL server that represents the exception information.
     */
    protected $result;

    /**
     * Make a new WSDL Exception with the given result.
     *
     * @param array $result The error result
     */
    public function __construct($result, $noerror) 
    {
        $this->result = $result;
        if(is_array($result))
            $msg = $result['message'];
        else
            $msg = $result;
        parent::__construct($msg, $noerror);
    }

    /**
     * Return the associated result object returned by the WSDL server.
     *
     * @return array The result from the WSDL server
     */
    public function getResult() 
    {
        return $this->result;
    }

    /**
     * Returns the associated type for the error.
     *
     * @return string
     */
    public function getType() 
    {
        if(isset($this->result['type']))
            return $this->result['type'];
        return 'LengowWsdlException';
    }

    /**
     * To make debugging easier.
     *
     * @return string The string representation of the error
     */
    public function __toString() 
    {
        if(isset($this->result['message']))
            return $this->result['message'];
    return $this->message;
    }
}