<?php

/**
 * Copyright 2016 Lengow SAS.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * @author    Team Connector <team-connector@lengow.com>
 * @copyright 2016 Lengow SAS
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowConnector
{
    /**
     * @var string connector version
     */
    const VERSION = '1.0';

    /**
     * @var mixed error returned by the API
     */
    public $error;

    /**
     * @var string the access token to connect
     */
    protected $access_token;

    /**
     * @var string the secret to connect
     */
    protected $secret;

    /**
     * @var string temporary token for the authorization
     */
    protected $token;

    /**
     * @var integer ID account
     */
    protected $account_id;

    /**
     * @var integer the user Id
     */
    protected $user_id;

    /**
     * @var string
     */
    protected $request;

    /**
     * @var string URL of the API Lengow
     */
    const LENGOW_API_URL = 'http://api.lengow.net:80';

    /**
     * @var string URL of the SANDBOX Lengow
     */
    const LENGOW_API_SANDBOX_URL = 'http://api.lengow.net:80';

    /**
     * @var array default options for curl
     */
    public static $CURL_OPTS = array (
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 300,
        CURLOPT_USERAGENT      => 'lengow-php-sdk',
    );

    /**
     * Make a new Lengow API Connector.
     *
     * @param string $access_token Your access token
     * @param string $secret       Your secret
     */
    public function __construct($access_token, $secret)
    {
        $this->access_token = $access_token;
        $this->secret = $secret;
    }

    /**
     * Connection to the API
     *
     * @param string $user_token The user token if is connected
     *
     * @return mixed array [authorized token + account_id + user_id] or false
     */
    public function connect($user_token = '')
    {
        $data = $this->callAction(
            '/access/get_token',
            array(
                'access_token' => $this->access_token,
                'secret'       => $this->secret,
                'user_token'   => $user_token
            ),
            'POST'
        );
        if (isset($data['token'])) {
            $this->token = $data['token'];
            $this->account_id = $data['account_id'];
            $this->user_id = $data['user_id'];
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Get API call
     *
     * @param string $method Lengow method API call
     * @param array  $array  Lengow method API parameters
     * @param string $format return format of API
     * @param string $body
     *
     * @return array The formated data response
     */
    public function get($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'GET', $format, $body);
    }

    /**
     * Call API action
     *
     * @param string $api    Lengow method API call
     * @param array  $args   Lengow method API parameters
     * @param string $type   type of request GET|POST|PUT|HEAD|DELETE|PATCH
     * @param string $format return format of API
     * @param string $body
     *
     * @return array The format data response
     */
    private function callAction($api, $args, $type, $format = 'json', $body = '')
    {
        $result = $this->makeRequest($type, self::LENGOW_API_URL.$api, $args, $this->token, $body);
        return $this->format($result, $format);
    }

    /**
     * The API method
     *
     * @param string $method Lengow method API call
     * @param array  $array  Lengow method API parameters
     * @param string $type   type of request GET|POST|PUT|HEAD|DELETE|PATCH
     * @param string $format return format of API
     * @param string $body
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException
     * @return array The format data response
     */
    public function call($method, $array = array(), $type = 'GET', $format = 'json', $body = '')
    {
        $this->connect();
        try {
            if (!array_key_exists('account_id', $array)) {
                $array['account_id'] = $this->account_id;
            }
            $data = $this->callAction($method, $array, $type, $format, $body);
        } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
            return $e->getMessage();
        }
        return $data;
    }

    /**
     * Get data in specific format
     *
     * @param mixed  $data
     * @param string $format
     *
     * @return array The format data response
     */
    private function format($data, $format)
    {
        switch ($format) {
            case 'json':
                return json_decode($data, true);
            case 'csv':
                return $data;
            case 'xml':
                return simplexml_load_string($data);
            case 'stream':
                return $data;
            default:
                return array();
        }
    }

    /**
     * Make Curl request
     *
     * @param string $type  Lengow method API call
     * @param string $url   Lengow API url
     * @param array  $args  Lengow method API parameters
     * @param string $token temporary access token
     * @param string $body
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException
     * @return array The format data response
     */
    protected function makeRequest($type, $url, $args, $token, $body = '')
    {
        $ch = curl_init();
        // Options
        $opts = self::$CURL_OPTS;
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($type);
        $url = parse_url($url);
        $opts[CURLOPT_PORT] = $url['port'];
        $opts[CURLOPT_HEADER] = false;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_VERBOSE] = false;
        if (isset($token)) {
            $opts[CURLOPT_HTTPHEADER] = array(
                'Authorization: '.$token,
            );
        }
        $url = $url['scheme'].'://'.$url['host'].$url['path'];
        switch ($type) {
            case "GET":
                $opts[CURLOPT_URL] = $url.'?'.http_build_query($args);
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Connector',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    	'log.connector.call_api',
                    	array(
                    		'curl_url' => $opts[CURLOPT_URL]
                		)
            		)
                );
                break;
            case "PUT":
                $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], array(
                    'Content-Type: application/json',
                    'Content-Length: '.strlen($body)
                ));
                $opts[CURLOPT_URL] = $url.'?'.http_build_query($args);
                $opts[CURLOPT_POSTFIELDS] = $body;
                break;
            default:
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = count($args);
                $opts[CURLOPT_POSTFIELDS] = http_build_query($args);
                break;
        }
        // Execute url request
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $error = curl_errno($ch);
        if (in_array($error, array(CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED))) {
            $timeout = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log.exception.timeout_api'
            );
            $error_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
            	'log.connector.error_api', 
            	array(
                	'error_code' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($timeout)
            	)
        	);
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log('Connector', $error_message);
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException($timeout);
        }
        curl_close($ch);
        if ($result === false) {
            $error_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
            	'log.connector.error_api', 
            	array(
                	'error_code' => $error
            	)
        	);
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log('Connector', $error_message);
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException($error);
        }
        return $result;
    }

    /**
     * Get Valid Account / Access / Secret
     *
     * @param Shopware\Models\Shop\Shop $shop
     *
     * @return array
     */
    public static function getAccessId($shop = null)
    {
        $account_id = null;
        $access_token = null;
        $secret_token = null;
        if ($shop == null) {
            $shopCollection = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowActiveShops();
            if (count($shopCollection) > 0) {
                $shop = $shopCollection[0];
            }
        }
        if ($shop != null) {
            $account_id = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowAccountId',
                $shop
            );
            $access_token = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowAccessToken',
                $shop
            );
            $secret_token = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowSecretToken',
                $shop
            );
        }
        return array($account_id, $access_token, $secret_token);
    }

    /**
     * Get result for a query Api
     *
     * @param string  $type   (GET / POST / PUT / PATCH)
     * @param string  $url
     * @param Shopware\Models\Shop\Shop $shop
     * @param array   $params
     * @param string  $body
     *
     * @return array api result as array
     */
    public static function queryApi($type, $url, $shop = null, $params = array(), $body = '')
    {
        if (!in_array($type, array('get', 'post', 'put', 'patch'))) {
            return false;
        }
        try {
            list($account_id, $access_token, $secret_token) = self::getAccessId($shop);
            $connector  = new Shopware_Plugins_Backend_Lengow_Components_LengowConnector(
                $access_token,
                $secret_token
            );
            $results = $connector->$type(
                $url,
                array_merge(array('account_id' => $account_id), $params),
                'stream',
                $body
            );
        } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
            return false;
        }
        return json_decode($results);
    }
}