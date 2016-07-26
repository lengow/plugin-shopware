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
class Shopware_Plugins_Backend_Lengow_Components_LengowMain
{
    /**
     * Lengow Authorized IPs
     */
    protected static $IPS_LENGOW = array(
        '46.19.183.204',
        '46.19.183.218',
        '46.19.183.222',
        '89.107.175.172',
        '89.107.175.186',
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
        '95.131.137.18',
        '95.131.137.19',
        '95.131.137.21',
        '95.131.137.26',
        '95.131.137.27',
        '88.164.17.227',
        '88.164.17.216',
        '109.190.78.5',
        '95.131.141.168',
        '95.131.141.169',
        '95.131.141.170',
        '95.131.141.171',
        '82.127.207.67',
        '80.14.226.127',
        '80.236.15.223',
        '92.135.36.234',
        '81.64.72.170',
        '80.11.36.123',
        '127.0.0.1'
    );

    /**
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowLog Lengow log file instance
     */
    public static $log;

    /**
     * Registers.
     */
    public static $registers;

    /**
     * @var integer life of log files in days
     */
    public static $LOG_LIFE = 20;

    /**
     * Check if current IP is authorized.
     *
     * @return boolean true if user is authorized
     */
    public static function checkIp()
    {
        $ips = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowAuthorizedIps');
        $ips = trim(str_replace(array("\r\n", ',', '-', '|', ' '), ';', $ips), ';');
        $ips = explode(';', $ips);
        $authorizedIps = array_merge($ips, self::$IPS_LENGOW);
        $hostnameIp = $_SERVER['REMOTE_ADDR'];
        if (in_array($hostnameIp, $authorizedIps)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get user locale language
     *
     * @return string Locale
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
     * @return Shopware\Models\Shop\Shop[] List of Shopware shops
     */
    public static function getShops()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        return $em->getRepository('Shopware\Models\Shop\Shop')->findAll();
    }

    /**
     * Get list of shops that have been activated in Lengow
     * @return \Shopware\Models\Shop\Shop[] List of shops
     */
    public static function getLengowActiveShops()
    {
        $result = array();
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $shops = $em->getRepository('Shopware\Models\Shop\Shop')->findBy(array('active' => 1));
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
     * Get the base url of the plugin
     *
     * @return string
     */
    public static function getBaseUrl()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $shop = $em->getRepository('Shopware\Models\Shop\Shop')->findOneBy(array('default' => 1));
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
        $host = $shop->getHost() ? $shop->getHost() : $_SERVER['SERVER_NAME'];
        $path = $shop->getBasePath() ? $shop->getBasePath() : '';
        $url = 'http'.$is_https.'://'.$host.$path;
        return $url;
    }

    /**
     * Get Lengow folder path
     *
     * @return string Module path
     */
    public static function getLengowFolder()
    {
        return Shopware()->Plugins()->Backend()->Lengow()->Path();
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
        $log_files = Shopware_Plugins_Backend_Lengow_Components_LengowLog::getFiles();
        $days = array();
        $days[] = 'logs-'.date('Y-m-d').'.txt';
        for ($i = 1; $i < self::$LOG_LIFE; $i++) {
            $days[] = 'logs-'.date('Y-m-d', strtotime('-'.$i.'day')).'.txt';
        }
        if (empty($log_files)) {
            return;
        }
        foreach ($log_files as $log) {
            if (!in_array($log->file_name, $days)) {
                $log->delete();
            }
        }
    }

    /**
     * Decode message with params for translation
     *
     * @param string $message  Key to translate
     * @param string $iso_code Language translation iso code
     * @param mixed  $params   array Parameters to display in the translation message
     *
     * @return string
     */
    public static function decodeLogMessage($message, $iso_code = null, $params = null)
    {
        if (preg_match('/^(([a-z\_]*\/){1,3}[a-z\_]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
            }
            if (isset($result[4]) && is_null($params)) {
                $str_param = $result[4];
                $all_params = explode('|', $str_param);
                foreach ($all_params as $param) {
                    $result = explode('==', $param);
                    $params[$result[0]] = $result[1];
                }
            }
            $locale = new Shopware_Plugins_Backend_Lengow_Components_LengowTranslation();
            $message = $locale->t($key, $params, $iso_code);
        }
        return $message;
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
                'LENGOW_LAST_IMPORT_CRON',
                time()
            );
        } else {
            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                'LENGOW_LAST_IMPORT_MANUAL',
                time()
            );
        }
    }

    /**
     * Writes log
     *
     * @param string  $category        Category log
     * @param string  $txt             log message
     * @param boolean $force_output    output on screen
     * @param string  $marketplace_sku lengow marketplace sku
     */
    public static function log($category, $txt, $force_output = false, $marketplace_sku = null)
    {
        $log = self::getLogInstance();
        $log->write($category, $txt, $force_output, $marketplace_sku);
    }

    /**
     * Set message with params for translation
     *
     * @param string $key
     * @param array  $params
     *
     * @return string
     */
    public static function setLogMessage($key, $params = null)
    {
        if (is_null($params) || (is_array($params) && count($params) == 0)) {
            return $key;
        }
        $all_params = array();
        foreach ($params as $param => $value) {
            $value = str_replace(array('|', '=='), array('', ''), $value);
            $all_params[] = $param.'=='.$value;
        }
        $message = $key.'['.join('|', $all_params).']';
        return $message;
    }

    /**
     * The shipping names options
     *
     * @param string                    $name Marketplace name
     * @param Shopware\Models\Shop\Shop $shop Shop
     *
     * @return Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace Lengow shipping names option
     */
    public static function getMarketplaceSingleton($name, $shop = null)
    {
        if (!isset(self::$registers[$name])) {
            self::$registers[$name] = new Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace($name, $shop);
        }
        return self::$registers[$name];
    }

    /**
     * Clean html
     *
     * @param string $html The html content
     *
     * @return string Text cleaned.
     */
    public static function cleanHtml($html)
    {
        $string = str_replace('<br />', '', nl2br($html));
        $string = trim(strip_tags(htmlspecialchars_decode($string)));
        $string = preg_replace('`[\s]+`sim', ' ', $string);
        $string = preg_replace('`"`sim', '', $string);
        $string = nl2br($string);
        $pattern = '@<[\/\!]*?[^<>]*?>@si'; //nettoyage du code HTML
        $string = preg_replace($pattern, ' ', $string);
        $string = preg_replace('/[\s]+/', ' ', $string); //nettoyage des espaces multiples
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
            '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
            '|[\x00-\x7F][\x80-\xBF]+'.
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '',
            $value
        );
        $value = preg_replace(
            '/\xE0[\x80-\x9F][\x80-\xBF]'.
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
     * Replace all accented chars by their equivalent non accented chars.
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

    /**
     * Check if new merchant
     *
     * @return boolean True if new merchant
     */
    public static function isNewMerchant()
    {
        $shops = self::getShops();
        foreach ($shops as $shop) {
            if ($shop->getActive()) {
                $accountId = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                    'lengowAccountId',
                    $shop
                );
                if ($accountId != 0) {
                    return true;
                }
            }
        }
        return false;
    }
}
