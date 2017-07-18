<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * It is available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/agpl-3.0
 *
 * @category    Lengow
 * @package     Lengow
 * @subpackage  Components
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Lengow Connector Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowConnector
{
    /**
     * @var string url of the API Lengow
     */
    // const LENGOW_API_URL = 'http://api.lengow.io:80';
    // const LENGOW_API_URL = 'http://api.lengow.net:80';
    const LENGOW_API_URL = 'http://api.lengow.rec:80';
    // const LENGOW_API_URL = 'http://10.100.1.82:8081';

    /**
     * @var string url of the SANDBOX Lengow
     */
    const LENGOW_API_SANDBOX_URL = 'http://api.lengow.net:80';

    /**
     * @var array default options for Curl
     */
    public static $curlOpts = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'lengow-php-sdk',
    );

    /**
     * @var string access token to connect
     */
    protected $accessToken;

    /**
     * @var string secret to connect
     */
    protected $secret;

    /**
     * @var string temporary token for the authorization
     */
    protected $token;

    /**
     * @var integer account id
     */
    protected $accountId;

    /**
     * @var integer user id
     */
    protected $userId;

    /**
     * @var array lengow url for Curl timeout
     */
    protected $lengowUrls = array(
        '/v3.0/orders' => 15,
        '/v3.0/orders/moi/' => 5,
        '/v3.0/orders/actions/' => 10,
        '/v3.0/marketplaces' => 10,
        '/v3.0/plans' => 3,
        '/v3.0/stats' => 3,
        '/v3.0/cms' => 3,
    );

    /**
     * Make a new Lengow API Connector
     *
     * @param string $accessToken your access token
     * @param string $secret your secret
     */
    public function __construct($accessToken, $secret)
    {
        $this->accessToken = $accessToken;
        $this->secret = $secret;
    }

    /**
     * Connection to the API
     *
     * @param string $userToken The user token if is connected
     *
     * @return array|false
     */
    public function connect($userToken = '')
    {
        $data = $this->callAction(
            '/access/get_token',
            array(
                'access_token' => $this->accessToken,
                'secret' => $this->secret,
                'user_token' => $userToken
            ),
            'POST'
        );
        if (isset($data['token'])) {
            $this->token = $data['token'];
            $this->accountId = $data['account_id'];
            $this->userId = $data['user_id'];
            return $data;
        } else {
            return false;
        }
    }

    /**
     * The API method
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $type type of request GET|POST|PUT|HEAD|DELETE|PATCH
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function call($method, $array = array(), $type = 'GET', $format = 'json', $body = '')
    {
        $this->connect();
        try {
            if (!array_key_exists('account_id', $array)) {
                $array['account_id'] = $this->accountId;
            }
            $data = $this->callAction($method, $array, $type, $format, $body);
        } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
            return $e->getMessage();
        }
        return $data;
    }

    /**
     * Get API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array The format data response
     */
    public function get($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'GET', $format, $body);
    }

    /**
     * Post API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function post($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'POST', $format, $body);
    }

    /**
     * Head API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function head($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'HEAD', $format, $body);
    }

    /**
     * Put API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array The format data response
     */
    public function put($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'PUT', $format, $body);
    }

    /**
     * Delete API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function delete($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'DELETE', $format, $body);
    }

    /**
     * Patch API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function patch($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'PATCH', $format, $body);
    }

    /**
     * Call API action
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $type type of request GET|POST|PUT|HEAD|DELETE|PATCH
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array The format data response
     */
    private function callAction($api, $args, $type, $format = 'json', $body = '')
    {
        $result = $this->makeRequest($type, $api, $args, $this->token, $body);
        return $this->format($result, $format);
    }

    /**
     * Get data in specific format
     *
     * @param mixed $data Curl response data
     * @param string $format return format of API
     *
     * @return mixed
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
     * @param string $type Lengow method API call
     * @param string $url Lengow API url
     * @param array $args Lengow method API parameters
     * @param string $token temporary access token
     * @param string $body body datas for request
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException get Curl error
     *
     * @return array
     */
    protected function makeRequest($type, $url, $args, $token, $body = '')
    {
        // Define CURLE_OPERATION_TIMEDOUT for old php versions
        defined('CURLE_OPERATION_TIMEDOUT') || define('CURLE_OPERATION_TIMEDOUT', CURLE_OPERATION_TIMEOUTED);
        $ch = curl_init();
        // Get default Curl options
        $opts = self::$curlOpts;
        // get special timeout for specific Lengow API
        if (array_key_exists($url, $this->lengowUrls)) {
            $opts[CURLOPT_TIMEOUT] = $this->lengowUrls[$url];
        }
        // get url for a specific environment
        $url = self::LENGOW_API_URL . $url;
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($type);
        $url = parse_url($url);
        $opts[CURLOPT_PORT] = $url['port'];
        $opts[CURLOPT_HEADER] = false;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_VERBOSE] = false;
        if (isset($token)) {
            $opts[CURLOPT_HTTPHEADER] = array(
                'Authorization: ' . $token,
            );
        }
        $url = $url['scheme'] . '://' . $url['host'] . $url['path'];
        switch ($type) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($args);
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Connector',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/connector/call_api',
                        array('curl_url' => $opts[CURLOPT_URL])
                    )
                );
                break;
            case 'PUT':
                if (isset($token)) {
                    $opts[CURLOPT_HTTPHEADER] = array_merge(
                        $opts[CURLOPT_HTTPHEADER],
                        array(
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($body)
                        )
                    );
                }
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($args);
                $opts[CURLOPT_POSTFIELDS] = $body;
                break;
            case 'PATCH':
                if (isset($token)) {
                    $opts[CURLOPT_HTTPHEADER] = array_merge(
                        $opts[CURLOPT_HTTPHEADER],
                        array('Content-Type: application/json')
                    );
                }
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = count($args);
                $opts[CURLOPT_POSTFIELDS] = json_encode($args);
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
        $errorNumber = curl_errno($ch);
        $errorText = curl_error($ch);
        if (in_array($errorNumber, array(CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED))) {
            $timeout = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/exception/timeout_api'
            );
            $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/connector/error_api',
                array(
                    'error_code' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($timeout)
                )
            );
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log('Connector', $errorMessage);
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException($timeout);
        }
        curl_close($ch);
        if ($result === false) {
            $errorCurl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/exception/error_curl',
                array(
                    'error_code' => $errorNumber,
                    'error_message' => $errorText
                )
            );
            $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/connector/error_api',
                array(
                    'error_code' => Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($errorCurl)
                )
            );
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log('Connector', $errorMessage);
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException($errorCurl);
        }
        return $result;
    }

    /**
     * Check if new merchant
     *
     * @return boolean
     */
    public static function isNewMerchant()
    {
        $accessIds = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getAccessId();
        list($accountId, $accessToken, $secretToken) = $accessIds;
        if ($accountId != 0 && $accessToken !== 0 && $secretToken !== 0) {
           return false;
        }
        return true;
    }

    /**
     * Check API authentication
     *
     * @return boolean
     */
    public static function isValidAuth()
    {
        if (!Shopware_Plugins_Backend_Lengow_Components_LengowCheck::isCurlActivated()) {
            return false;
        }
        $accessIds = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getAccessId();
        list($accountId, $accessToken, $secretToken) = $accessIds;
        if (is_null($accountId) || $accountId == 0 || !is_numeric($accountId)) {
            return false;
        }
        $connector = new Shopware_Plugins_Backend_Lengow_Components_LengowConnector($accessToken, $secretToken);
        try {
            $result = $connector->connect();
        } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
            return false;
        }
        if (isset($result['token'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get result for a query Api
     *
     * @param string $type request type (GET / POST / PUT / PATCH)
     * @param string $url request url
     * @param array $params request params
     * @param string $body body datas for request
     *
     * @return mixed
     */
    public static function queryApi($type, $url, $params = array(), $body = '')
    {
        if (!in_array($type, array('get', 'post', 'put', 'patch'))) {
            return false;
        }
        try {
            $accessIds = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getAccessId();
            list($accountId, $accessToken, $secretToken) = $accessIds;
            if ($accountId != 0 && $accessToken !== 0 && $secretToken !== 0) {
                $connector = new Shopware_Plugins_Backend_Lengow_Components_LengowConnector(
                    $accessToken,
                    $secretToken
                );
                $results = $connector->$type(
                    $url,
                    array_merge(array('account_id' => $accountId), $params),
                    'stream',
                    $body
                );
            } else {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Connector',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/error/account_id_empty'
                    )
                );
                return false;
            }
        } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
            return $e->getMessage();
        }
        return json_decode($results);
    }
}
