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
 * Lengow Main Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowMain
{
    /**
     * @var array Lengow Authorized IPs
     */
    protected static $ipsLengow = array(
        '127.0.0.1',
        '10.0.4.150',
        '46.19.183.204',
        '46.19.183.217',
        '46.19.183.218',
        '46.19.183.219',
        '46.19.183.222',
        '52.50.58.130',
        '89.107.175.172',
        '89.107.175.185',
        '89.107.175.186',
        '89.107.175.187',
        '90.63.241.226',
        '109.190.189.175',
        '146.185.41.180',
        '146.185.41.177',
        '185.61.176.129',
        '185.61.176.130',
        '185.61.176.131',
        '185.61.176.132',
        '185.61.176.133',
        '185.61.176.134',
        '185.61.176.137',
        '185.61.176.138',
        '185.61.176.139',
        '185.61.176.140',
        '185.61.176.141',
        '185.61.176.142',
    );

    /**
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowLog Lengow log instance
     */
    public static $log;

    /**
     * @var array marketplace registers
     */
    public static $registers;

    /**
     * @var integer life of log files in days
     */
    public static $logLife = 20;

    /**
     * Get export web services links
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return string
     */
    public static function getExportUrl($shop)
    {
        $shopBaseUrl = self::getShopUrl($shop);
        return $shopBaseUrl . '/LengowController/export?shop=' . $shop->getId() . '&token=' . self::getToken($shop);
    }

    /**
     * Get import web services link
     *
     * @return string
     */
    public static function getImportUrl()
    {
        return self::getBaseUrl() . '/LengowController/cron?token=' . self::getToken();
    }

    /**
     * Check if Shopware current version is older than the specified one
     *
     * @param string $versionToCompare version to compare
     *
     * @return boolean
     */
    public static function compareVersion($versionToCompare)
    {
        return version_compare(Shopware::VERSION, $versionToCompare, ">=");
    }

    /**
     * Check webservice access (export and import)
     *
     * @param string $token shop token
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function checkWebservicesAccess($token, $shop = null)
    {
        $ipEnabled = (bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowIpEnabled');
        if (!$ipEnabled && self::checkToken($token, $shop)) {
            return true;
        }
        if (self::checkIp()) {
            return true;
        }
        return false;
    }

    /**
     * Check if token is correct
     *
     * @param string $token shop token
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function checkToken($token, $shop = null)
    {
        $storeToken = self::getToken($shop);
        if ($token === $storeToken) {
            return true;
        }
        return false;
    }

    /**
     * Check if current IP is authorized
     *
     * @param boolean $toolbox force check ip for toolbox
     *
     * @return boolean
     */
    public static function checkIp($toolbox = false)
    {
        $ips = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowAuthorizedIp');
        $ipEnabled = (bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowIpEnabled');
        if (strlen($ips) > 0 && ($ipEnabled || $toolbox)) {
            $ips = trim(str_replace(array("\r\n", ',', '-', '|', ' '), ';', $ips), ';');
            $ips = array_filter(explode(';', $ips));
            $authorizedIps = count($ips) > 0 ? array_merge($ips, self::$ipsLengow) : self::$ipsLengow;
        } else {
            $authorizedIps = self::$ipsLengow;
        }
        if (isset($_SERVER['SERVER_ADDR'])) {
            $authorizedIps[] = $_SERVER['SERVER_ADDR'];
        }
        $hostnameIp = $_SERVER['REMOTE_ADDR'];
        if (in_array($hostnameIp, $authorizedIps)) {
            return true;
        }
        return false;
    }

    /**
     * Generate token
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return string
     */
    public static function getToken($shop = null)
    {
        // If no shop, get global value
        if (is_null($shop)) {
            $token = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowGlobalToken');
            if ($token && strlen($token) > 0) {
                return $token;
            } else {
                $token = bin2hex(openssl_random_pseudo_bytes(16));
                Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig('lengowGlobalToken', $token);
            }
        } else {
            $token = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowShopToken',
                $shop
            );
            if ($token && strlen($token) > 0) {
                return $token;
            } else {
                $token = bin2hex(openssl_random_pseudo_bytes(16));
                Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                    'lengowShopToken',
                    $token,
                    $shop
                );
            }
        }
        return $token;
    }

    /**
     * Get user locale language
     *
     * @return string
     */
    public static function getLocale()
    {
        return Shopware()->Auth()->getIdentity()->locale->getLocale();
    }

    /**
     * Get the path of the plugin
     *
     * @return string
     */
    public static function getPathPlugin()
    {
        $path = self::getLengowFolder();
        $index = strpos($path, '/engine');
        return substr($path, $index);
    }

    /**
     * Get list of shops (active or not)
     *
     * @return array
     */
    public static function getShops()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        return $em->getRepository('Shopware\Models\Shop\Shop')->findAll();
    }

    /**
     * Get Shopware active shops
     *
     * @return array
     */
    public static function getActiveShops()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        return $em->getRepository('Shopware\Models\Shop\Shop')->findBy(array('active' => 1));
    }

    /**
     * Get list of shops that have been activated in Lengow
     *
     * @return array
     */
    public static function getLengowActiveShops()
    {
        $result = array();
        $shops = self::getActiveShops();
        foreach ($shops as $shop) {
            // Get Lengow config for this shop
            $enabledInLengow = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowShopActive',
                $shop
            );
            if ($enabledInLengow) {
                $result[] = $shop;
            }
        }
        return $result;
    }

    /**
     * Get a shop with a given token
     *
     * @param string $token shop token
     *
     * @return \Shopware\Models\Shop\Shop|false
     */
    public static function getShopByToken($token)
    {
        $shops = self::getActiveShops();
        foreach ($shops as $shop) {
            $shopToken = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowShopToken',
                $shop
            );
            if ($shopToken === $token) {
                return $shop;
            }
        }
        return false;
    }

    /**
     * Get shop url for export
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return string
     */
    public static function getShopUrl($shop)
    {
        return self::getBaseUrl($shop) . $shop->getBaseUrl();
    }

    /**
     * Get the base url of the plugin
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     *
     * @return string
     */
    public static function getBaseUrl($shop = null)
    {
        if ($shop == null) {
            $shop = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getDefaultShop();
        }
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
        $host = $shop->getHost() ? $shop->getHost() : $_SERVER['SERVER_NAME'];
        $path = $shop->getBasePath() ? $shop->getBasePath() : '';
        $url = 'http' . $isHttps . '://' . $host . $path;
        return $url;
    }

    /**
     * Record the date of the last import
     *
     * @param string $type (cron or manual)
     *
     * @return boolean
     */
    public static function updateDateImport($type)
    {
        if ($type === 'cron') {
            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                'lengowLastImportCron',
                time()
            );
        } else {
            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                'lengowLastImportManual',
                time()
            );
        }
    }

    /**
     * Get last import (type and timestamp)
     *
     * @return array
     */
    public static function getLastImport()
    {
        $timestampCron = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowLastImportCron'
        );
        $timestampManual = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowLastImportManual'
        );
        if ($timestampCron && $timestampManual) {
            if ((int)$timestampCron > (int)$timestampManual) {
                return array('type' => 'cron', 'timestamp' => (int)$timestampCron);
            } else {
                return array('type' => 'manual', 'timestamp' => (int)$timestampManual);
            }
        } elseif ($timestampCron && !$timestampManual) {
            return array('type' => 'cron', 'timestamp' => (int)$timestampCron);
        } elseif ($timestampManual && !$timestampCron) {
            return array('type' => 'manual', 'timestamp' => (int)$timestampManual);
        }
        return array('type' => 'none', 'timestamp' => 'none');
    }

    /**
     * Get Lengow folder path
     *
     * @return string
     */
    public static function getLengowFolder()
    {
        return Shopware()->Plugins()->Backend()->Lengow()->Path();
    }

    /**
     * Writes log
     *
     * @param string $category log category
     * @param string $txt log message
     * @param boolean $logOutput output on screen
     * @param string $marketplaceSku Lengow marketplace sku
     */
    public static function log($category, $txt, $logOutput = false, $marketplaceSku = null)
    {
        $log = self::getLogInstance();
        $log->write($category, $txt, $logOutput, $marketplaceSku);
    }

    /**
     * Get log Instance
     *
     * @return Shopware_Plugins_Backend_Lengow_Components_LengowLog
     */
    public static function getLogInstance()
    {
        if (is_null(self::$log)) {
            self::$log = new Shopware_Plugins_Backend_Lengow_Components_LengowLog();
        }
        return self::$log;
    }

    /**
     * Suppress log files when too old
     */
    public static function cleanLog()
    {
        // @var Shopware_Plugins_Backend_Lengow_Components_LengowFile[] $logFiles
        $logFiles = Shopware_Plugins_Backend_Lengow_Components_LengowLog::getFiles();
        $days = array();
        $days[] = 'logs-' . date('Y-m-d') . '.txt';
        for ($i = 1; $i < self::$logLife; $i++) {
            $days[] = 'logs-' . date('Y-m-d', strtotime('-' . $i . 'day')) . '.txt';
        }
        if (empty($logFiles)) {
            return;
        }
        foreach ($logFiles as $log) {
            if (!in_array($log->fileName, $days)) {
                $log->delete();
            }
        }
    }

    /**
     * Decode message with params for translation
     *
     * @param string $message key to translate
     * @param string $isoCode language translation iso code
     * @param mixed $params array parameters to display in the translation message
     *
     * @return string
     */
    public static function decodeLogMessage($message, $isoCode = null, $params = null)
    {
        if (preg_match('/^(([a-z\_]*\/){1,3}[a-z\_]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
                if (isset($result[4]) && is_null($params)) {
                    $strParam = $result[4];
                    $allParams = explode('|', $strParam);
                    foreach ($allParams as $param) {
                        $result = explode('==', $param);
                        $params[$result[0]] = $result[1];
                    }
                }
                $locale = new Shopware_Plugins_Backend_Lengow_Components_LengowTranslation();
                $message = $locale->t($key, $params, $isoCode);
            }
        }
        return $message;
    }

    /**
     * Set message with params for translation
     *
     * @param string $key log key
     * @param array $params log parameters
     *
     * @return string
     */
    public static function setLogMessage($key, $params = null)
    {
        if (is_null($params) || (is_array($params) && count($params) == 0)) {
            return $key;
        }
        $allParams = array();
        foreach ($params as $param => $value) {
            $value = str_replace(array('|', '=='), array('', ''), $value);
            $allParams[] = $param . '==' . $value;
        }
        $message = $key . '[' . join('|', $allParams) . ']';
        return $message;
    }

    /**
     * Get a specific marketplace
     *
     * @param string $name Marketplace name
     *
     * @return Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace
     */
    public static function getMarketplaceSingleton($name)
    {
        if (!isset(self::$registers[$name])) {
            self::$registers[$name] = new Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace($name);
        }
        return self::$registers[$name];
    }

    /**
     * Load Lengow Payment Shopware
     *
     * @return Shopware\Models\Payment\Payment|null
     */
    public static function getLengowPayment()
    {
        $payment = Shopware()->Models()
            ->getRepository('Shopware\Models\Payment\Payment')
            ->findOneBy(array('name' => 'lengow'));
        if (is_null($payment)) {
            $plugin = Shopware()->Models()
                ->getRepository('Shopware\Models\Plugin\Plugin')
                ->findOneBy(array('name' => 'Lengow'));
            if (!is_null($plugin) && !$plugin->getPayments()->isEmpty()) {
                $payment = $plugin->getPayments()->first();
            }
        }
        return $payment;
    }

    /**
     * Get Shopware order status corresponding to the current order state
     *
     * @param string $orderStateMarketplace order state marketplace
     * @param Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace $marketplace Lengow marketplace instance
     * @param boolean $shipmentByMp order shipped by marketplace
     *
     * @return \Shopware\Models\Order\Status|false
     */
    public static function getShopwareOrderStatus($orderStateMarketplace, $marketplace, $shipmentByMp = false)
    {
        if ($shipmentByMp) {
            $orderState = 'shipped_by_marketplace';
        } elseif ($marketplace->getStateLengow($orderStateMarketplace) === 'shipped'
            || $marketplace->getStateLengow($orderStateMarketplace) === 'closed'
        ) {
            $orderState = 'shipped';
        } else {
            $orderState = 'accepted';
        }
        return self::getOrderStatus($orderState);
    }

    /**
     * Get the matching Shopware order status to the one given
     *
     * @param string $orderState state to be matched
     *
     * @return \Shopware\Models\Order\Status|false
     */
    public static function getOrderStatus($orderState)
    {
        switch ($orderState) {
            case 'accepted':
            case 'waiting_shipment':
                $settingName = 'lengowIdWaitingShipment';
                break;
            case 'shipped':
            case 'closed':
                $settingName = 'lengowIdShipped';
                break;
            case 'refused':
            case 'canceled':
                $settingName = 'lengowIdCanceled';
                break;
            case 'shipped_by_marketplace':
                $settingName = 'lengowIdShippedByMp';
                break;
            default:
                $settingName = false;
                break;
        }
        if ($settingName) {
            $orderStatusId = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig($settingName);
            $orderStatus = Shopware()->Models()->getReference('Shopware\Models\Order\Status', (int)$orderStatusId);
            if (!is_null($orderStatus)) {
                return $orderStatus;
            }
        }
        return false;
    }

    /**
     * Get tax associated with a dispatch
     *
     * @param Shopware\Models\Dispatch\Dispatch $dispatch Shopware dispatch instance
     *
     * @return Shopware\Models\Tax\Tax
     */
    public static function getDispatchTax($dispatch)
    {
        if ($dispatch->getTaxCalculation() !== 0 ) {
            $taxId = (int)$dispatch->getTaxCalculation();
        } else {
            $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS sct.id 
	     		FROM s_core_tax as sct
	            WHERE sct.tax = (SELECT MAX(tax) from s_core_tax)";
            $taxId = (int)Shopware()->Db()->fetchOne($sql);
        }
        return Shopware()->Models()->getReference('Shopware\Models\Tax\Tax', $taxId);
    }

    /**
     * Get all admin users
     *
     * @return array
     */
    public static function getAllAdminUsers()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('user')
            ->from('Shopware\Models\User\User', 'user')
            ->leftJoin('Shopware\Models\User\Role', 'role')
            ->where('user.active = :active')
            ->andWhere('role.name = :name')
            ->setParameters(
                array(
                    'active' => 1,
                    'name' => 'local_admins'
                )
            );
        return $builder->getQuery()->getResult();
    }

    /**
     * Clean phone number
     *
     * @param string $phone phone number to clean
     *
     * @return string
     */
    public static function cleanPhone($phone)
    {
        $replace = array('.', ' ', '-', '/');
        if (!$phone) {
            return '';
        }
        return str_replace($replace, '', preg_replace('/[^0-9]*/', '', $phone));
    }

    /**
     * Clean html
     *
     * @param string $html The html content
     *
     * @return string
     */
    public static function cleanHtml($html)
    {
        $string = str_replace('<br />', ' ', nl2br($html));
        $string = trim(strip_tags(htmlspecialchars_decode($string)));
        $string = preg_replace('`[\s]+`sim', ' ', $string);
        $string = preg_replace('`"`sim', '', $string);
        $string = nl2br($string);
        $pattern = '@<[\/\!]*?[^<>]*?>@si';
        $string = preg_replace($pattern, ' ', $string);
        $string = preg_replace('/[\s]+/', ' ', $string);
        $string = trim($string);
        $string = str_replace('&nbsp;', ' ', $string);
        $string = str_replace('|', ' ', $string);
        $string = str_replace('"', '\'', $string);
        $string = str_replace('’', '\'', $string);
        $string = str_replace('&#39;', '\' ', $string);
        $string = str_replace('&#150;', '-', $string);
        $string = str_replace(chr(9), ' ', $string);
        $string = str_replace(chr(10), ' ', $string);
        $string = str_replace(chr(13), ' ', $string);
        return $string;
    }

    /**
     * Clean data
     *
     * @param string $value The content
     *
     * @return string
     */
    public static function cleanData($value)
    {
        $value = preg_replace(
            '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' .
            '|[\x00-\x7F][\x80-\xBF]+' .
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '',
            $value
        );
        $value = preg_replace(
            '/\xE0[\x80-\x9F][\x80-\xBF]' .
            '|\xED[\xA0-\xBF][\x80-\xBF]/S',
            '',
            $value
        );
        $value = preg_replace('/[\s]+/', ' ', $value);
        $value = trim($value);
        $value = str_replace(
            array(
                '&nbsp;',
                '|',
                '"',
                '’',
                '&#39;',
                '&#150;',
                chr(9),
                chr(10),
                chr(13),
                chr(31),
                chr(30),
                chr(29),
                chr(28),
                "\n",
                "\r"
            ),
            array(
                ' ',
                ' ',
                '\'',
                '\'',
                ' ',
                '-',
                ' ',
                ' ',
                ' ',
                '',
                '',
                '',
                '',
                '',
                ''
            ),
            $value
        );
        return $value;
    }

    /**
     * Replace all accented chars by their equivalent non accented chars
     *
     * @param string $str string to have its characters replaced
     *
     * @return string
     */
    public static function replaceAccentedChars($str)
    {
        /* One source among others:
          http://www.tachyonsoft.com/uc0000.htm
          http://www.tachyonsoft.com/uc0001.htm
        */
        $patterns = array(
            /* Lowercase */
            /* a */
            '/[\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}\x{0101}\x{0103}\x{0105}]/u',
            /* c */
            '/[\x{00E7}\x{0107}\x{0109}\x{010D}]/u',
            /* d */
            '/[\x{010F}\x{0111}]/u',
            /* e */
            '/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}]/u',
            /* g */
            '/[\x{011F}\x{0121}\x{0123}]/u',
            /* h */
            '/[\x{0125}\x{0127}]/u',
            /* i */
            '/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}]/u',
            /* j */
            '/[\x{0135}]/u',
            /* k */
            '/[\x{0137}\x{0138}]/u',
            /* l */
            '/[\x{013A}\x{013C}\x{013E}\x{0140}\x{0142}]/u',
            /* n */
            '/[\x{00F1}\x{0144}\x{0146}\x{0148}\x{0149}\x{014B}]/u',
            /* o */
            '/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}\x{014D}\x{014F}\x{0151}]/u',
            /* r */
            '/[\x{0155}\x{0157}\x{0159}]/u',
            /* s */
            '/[\x{015B}\x{015D}\x{015F}\x{0161}]/u',
            /* ss */
            '/[\x{00DF}]/u',
            /* t */
            '/[\x{0163}\x{0165}\x{0167}]/u',
            /* u */
            '/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{0169}\x{016B}\x{016D}\x{016F}\x{0171}\x{0173}]/u',
            /* w */
            '/[\x{0175}]/u',
            /* y */
            '/[\x{00FF}\x{0177}\x{00FD}]/u',
            /* z */
            '/[\x{017A}\x{017C}\x{017E}]/u',
            /* ae */
            '/[\x{00E6}]/u',
            /* oe */
            '/[\x{0153}]/u',
            /* Uppercase */
            /* A */
            '/[\x{0100}\x{0102}\x{0104}\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}]/u',
            /* C */
            '/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}]/u',
            /* D */
            '/[\x{010E}\x{0110}]/u',
            /* E */
            '/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}]/u',
            /* G */
            '/[\x{011C}\x{011E}\x{0120}\x{0122}]/u',
            /* H */
            '/[\x{0124}\x{0126}]/u',
            /* I */
            '/[\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}]/u',
            /* J */
            '/[\x{0134}]/u',
            /* K */
            '/[\x{0136}]/u',
            /* L */
            '/[\x{0139}\x{013B}\x{013D}\x{0139}\x{0141}]/u',
            /* N */
            '/[\x{00D1}\x{0143}\x{0145}\x{0147}\x{014A}]/u',
            /* O */
            '/[\x{00D3}\x{014C}\x{014E}\x{0150}]/u',
            /* R */
            '/[\x{0154}\x{0156}\x{0158}]/u',
            /* S */
            '/[\x{015A}\x{015C}\x{015E}\x{0160}]/u',
            /* T */
            '/[\x{0162}\x{0164}\x{0166}]/u',
            /* U */
            '/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{0168}\x{016A}\x{016C}\x{016E}\x{0170}\x{0172}]/u',
            /* W */
            '/[\x{0174}]/u',
            /* Y */
            '/[\x{0176}]/u',
            /* Z */
            '/[\x{0179}\x{017B}\x{017D}]/u',
            /* AE */
            '/[\x{00C6}]/u',
            /* OE */
            '/[\x{0152}]/u'
        );
        // ö to oe
        // å to aa
        // ä to ae
        $replacements = array(
            'a',
            'c',
            'd',
            'e',
            'g',
            'h',
            'i',
            'j',
            'k',
            'l',
            'n',
            'o',
            'r',
            's',
            'ss',
            't',
            'u',
            'y',
            'w',
            'z',
            'ae',
            'oe',
            'A',
            'C',
            'D',
            'E',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'N',
            'O',
            'R',
            'S',
            'T',
            'U',
            'Z',
            'AE',
            'OE'
        );
        return preg_replace($patterns, $replacements, $str);
    }
}
