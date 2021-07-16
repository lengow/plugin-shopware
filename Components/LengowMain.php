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

use Shopware\Models\Dispatch\Dispatch as DispatchModel;
use Shopware\Models\Order\Status as OrderStatusModel;
use Shopware\Models\Payment\Payment as PaymentModel;
use Shopware\Models\Plugin\Plugin as PluginModel;
use Shopware\Models\Shop\Shop as ShopModel;
use Shopware\Models\Tax\Tax as TaxModel;
use Shopware\Models\User\User as UserModel;
use Shopware\Models\User\Role as UserRoleModel;
use Shopware_Components_Translation as ShopwareTranslation;
use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowException as LengowException;
use Shopware_Plugins_Backend_Lengow_Components_LengowExport as LengowExport;
use Shopware_Plugins_Backend_Lengow_Components_LengowFile as LengowFile;
use Shopware_Plugins_Backend_Lengow_Components_LengowImport as LengowImport;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace as LengowMarketplace;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrderError as LengowOrderError;
use Shopware_Plugins_Backend_Lengow_Components_LengowSync as LengowSync;
use Shopware_Plugins_Backend_Lengow_Components_LengowToolbox as LengowToolbox;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

/**
 * Lengow Main Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowMain
{
    /* Lengow plugin folders */
    const FOLDER_CONFIG = 'Config';
    const FOLDER_EXPORT = 'Export';
    const FOLDER_LOG = 'Logs';
    const FOLDER_TOOLBOX = 'Toolbox';

    /* Lengow actions controller */
    const ACTION_EXPORT = 'export';
    const ACTION_CRON = 'cron';
    const ACTION_TOOLBOX = 'toolbox';

    /**
     * @var string Name of Lengow front controller
     */
    const LENGOW_CONTROLLER = 'LengowController';

    /**
     * @var integer life of log files in days
     */
    const LOG_LIFE = 20;

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
     * @var LengowLog Lengow log instance
     */
    public static $log;

    /**
     * @var array marketplace registers
     */
    public static $registers;

    /**
     * @var ShopwareTranslation Shopware translation instance
     */
    public static $translation;

    /**
     * @var array property value translation
     */
    public static $propertyValueTranslations = array();

    /**
     * Get export webservice link
     *
     * @param ShopModel $shop Shopware shop instance
     *
     * @return string
     */
    public static function getExportUrl($shop)
    {
        $sep = DIRECTORY_SEPARATOR;
        return self::getShopUrl($shop) . $sep . self::LENGOW_CONTROLLER . $sep . self::ACTION_EXPORT . '?'
            . LengowExport::PARAM_SHOP . '=' . $shop->getId() . '&'
            . LengowExport::PARAM_TOKEN . '=' . self::getToken($shop);
    }

    /**
     * Get cron webservice link
     *
     * @return string
     */
    public static function getCronUrl()
    {
        $sep = DIRECTORY_SEPARATOR;
        return self::getBaseUrl() . $sep . self::LENGOW_CONTROLLER . $sep . self::ACTION_CRON . '?'
            . LengowImport::PARAM_TOKEN . '=' . self::getToken();
    }

    /**
     * Get toolbox webservice link
     *
     * @return string
     */
    public static function getToolboxUrl()
    {
        $sep = DIRECTORY_SEPARATOR;
        return self::getBaseUrl() . $sep . self::LENGOW_CONTROLLER . $sep . self::ACTION_TOOLBOX . '?'
            . LengowToolbox::PARAM_TOKEN . '=' . self::getToken();
    }

    /**
     * Compare Shopware current version with a specified one
     *
     * @param string $versionToCompare version to compare
     * @param string $operator operator to compare
     *
     * @return boolean
     */
    public static function compareVersion($versionToCompare, $operator = '>=')
    {
        return version_compare(self::getShopwareVersion(), $versionToCompare, $operator);
    }

    /**
     * Check webservice access (export and import)
     *
     * @param string $token shop token
     * @param ShopModel|null $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function checkWebservicesAccess($token, $shop = null)
    {
        if (!(bool) LengowConfiguration::getConfig(LengowConfiguration::AUTHORIZED_IP_ENABLED)
            && self::checkToken($token, $shop)
        ) {
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
     * @param ShopModel|null $shop Shopware shop instance
     *
     * @return boolean
     */
    public static function checkToken($token, $shop = null)
    {
        $storeToken = self::getToken($shop);
        return $token === $storeToken;
    }

    /**
     * Check if current IP is authorized
     *
     * @return boolean
     */
    public static function checkIp()
    {
        $authorizedIps = array_merge(LengowConfiguration::getAuthorizedIps(), self::$ipsLengow);
        if (isset($_SERVER['SERVER_ADDR'])) {
            $authorizedIps[] = $_SERVER['SERVER_ADDR'];
        }
        return in_array($_SERVER['REMOTE_ADDR'], $authorizedIps, true);
    }

    /**
     * Generate token
     *
     * @param ShopModel|null $shop Shopware shop instance
     *
     * @return string
     */
    public static function getToken($shop = null)
    {
        // if no shop, get global value
        if ($shop === null) {
            $token = LengowConfiguration::getConfig(LengowConfiguration::CMS_TOKEN);
            if ($token && $token !== '') {
                return $token;
            }
            $token = bin2hex(openssl_random_pseudo_bytes(16));
            LengowConfiguration::setConfig(LengowConfiguration::CMS_TOKEN, $token);
        } else {
            $token = LengowConfiguration::getConfig(LengowConfiguration::SHOP_TOKEN, $shop);
            if ($token && $token !== '') {
                return $token;
            }
            $token = bin2hex(openssl_random_pseudo_bytes(16));
            LengowConfiguration::setConfig(LengowConfiguration::SHOP_TOKEN, $token, $shop);
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
        if (Shopware()->Auth()->getIdentity() !== null) {
            return Shopware()->Auth()->getIdentity()->locale->getLocale();
        }
        return LengowTranslation::DEFAULT_ISO_CODE;
    }

    /**
     * Get Shopware version
     *
     * @return string
     */
    public static function getShopwareVersion()
    {
        $shopwareVersion = '';
        // get release version via parameters for Shopware versions greater than 5.4
        if (Shopware()->Container()->hasParameter('shopware.release.version')) {
            $shopwareVersion = Shopware()->Container()->getParameter('shopware.release.version');
        }
        // get release version via the constant for older versions of Shopware
        if (empty($shopwareVersion) && defined('Shopware::VERSION')) {
            $shopwareVersion = Shopware::VERSION;
        }
        return $shopwareVersion;
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
     * @return ShopModel[]
     */
    public static function getShops()
    {
        $em = LengowBootstrap::getEntityManager();
        return $em->getRepository(ShopModel::class)->findAll();
    }

    /**
     * Get Shopware active shops
     *
     * @return ShopModel[]
     */
    public static function getActiveShops()
    {
        $em = LengowBootstrap::getEntityManager();
        return $em->getRepository(ShopModel::class)->findBy(array('active' => 1));
    }

    /**
     * Get list of shops that have been activated in Lengow
     *
     * @return ShopModel[]
     */
    public static function getLengowActiveShops()
    {
        $result = array();
        $shops = self::getActiveShops();
        foreach ($shops as $shop) {
            // get Lengow config for this shop
            if (LengowConfiguration::shopIsActive($shop)) {
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
     * @return ShopModel|false
     */
    public static function getShopByToken($token)
    {
        $shops = self::getActiveShops();
        foreach ($shops as $shop) {
            if (LengowConfiguration::getConfig(LengowConfiguration::SHOP_TOKEN, $shop) === $token) {
                return $shop;
            }
        }
        return false;
    }

    /**
     * Get shop url for export
     *
     * @param ShopModel $shop Shopware shop instance
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
     * @param ShopModel|null $shop Shopware shop instance
     *
     * @return string
     */
    public static function getBaseUrl($shop = null)
    {
        if ($shop === null) {
            $shop = LengowConfiguration::getDefaultShop();
        }
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
        $mainHost = $shop->getMain() !== null ? $shop->getMain()->getHost() : $_SERVER['SERVER_NAME'];
        $host = $shop->getHost() ?: $mainHost;
        $path = $shop->getBasePath() ?: '';
        return 'http' . $isHttps . '://' . $host . $path;
    }

    /**
     * Record the date of the last import
     *
     * @param string $type (cron or manual)
     */
    public static function updateDateImport($type)
    {
        if ($type === LengowImport::TYPE_CRON) {
            LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_CRON_SYNCHRONIZATION, time());
        } else {
            LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_MANUAL_SYNCHRONIZATION, time());
        }
    }

    /**
     * Get last import (type and timestamp)
     *
     * @return array
     */
    public static function getLastImport()
    {
        $timestampCron = LengowConfiguration::getConfig(LengowConfiguration::LAST_UPDATE_CRON_SYNCHRONIZATION);
        $timestampManual = LengowConfiguration::getConfig(LengowConfiguration::LAST_UPDATE_MANUAL_SYNCHRONIZATION);
        if ($timestampCron && $timestampManual) {
            if ((int) $timestampCron > (int) $timestampManual) {
                return array('type' => LengowImport::TYPE_CRON, 'timestamp' => (int )$timestampCron);
            }
            return array('type' => LengowImport::TYPE_MANUAL, 'timestamp' => (int) $timestampManual);
        }
        if ($timestampCron && !$timestampManual) {
            return array('type' => LengowImport::TYPE_CRON, 'timestamp' => (int) $timestampCron);
        }
        if ($timestampManual && !$timestampCron) {
            return array('type' => LengowImport::TYPE_MANUAL, 'timestamp' => (int) $timestampManual);
        }
        return array('type' => 'none', 'timestamp' => 'none');
    }

    /**
     * Get date in local date
     *
     * @param integer $timestamp linux timestamp
     * @param boolean $second see seconds or not
     *
     * @return string
     */
    public static function getDateInCorrectFormat($timestamp, $second = false)
    {
        if ($second) {
            $format = 'l d F Y @ H:i:s';
        } else {
            $format = 'l d F Y @ H:i';
        }
        return date($format, $timestamp);
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
     * @param string|null $marketplaceSku Lengow marketplace sku
     */
    public static function log($category, $txt, $logOutput = false, $marketplaceSku = null)
    {
        $log = self::getLogInstance();
        if ($log) {
            $log->write($category, $txt, $logOutput, $marketplaceSku);
        }
    }

    /**
     * Get log Instance
     *
     * @return LengowLog|false
     */
    public static function getLogInstance()
    {
        if (self::$log === null) {
            try {
                self::$log = new LengowLog();
            } catch (LengowException $e) {
                return false;
            }
        }
        return self::$log;
    }

    /**
     * Suppress log files when too old
     */
    public static function cleanLog()
    {
        $days = array();
        $days[] = 'logs-' . date('Y-m-d') . '.txt';
        for ($i = 1; $i < self::LOG_LIFE; $i++) {
            $days[] = 'logs-' . date('Y-m-d', strtotime('-' . $i . 'day')) . '.txt';
        }
        /** @var LengowFile[] $logFiles */
        $logFiles = LengowLog::getFiles();
        if (empty($logFiles)) {
            return;
        }
        foreach ($logFiles as $log) {
            if (!in_array($log->fileName, $days, true)) {
                $log->delete();
            }
        }
    }

    /**
     * Decode message with params for translation
     *
     * @param string $message key to translate
     * @param string|null $isoCode language translation iso code
     * @param array|null $params array parameters to display in the translation message
     *
     * @return string
     */
    public static function decodeLogMessage($message, $isoCode = null, $params = null)
    {
        if (preg_match('/^(([a-z\_]*\/){1,3}[a-z\_]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
                if (isset($result[4]) && $params === null) {
                    $strParam = $result[4];
                    $allParams = explode('|', $strParam);
                    foreach ($allParams as $param) {
                        $result = explode('==', $param);
                        $params[$result[0]] = $result[1];
                    }
                }
                $locale = new LengowTranslation();
                $message = $locale->t($key, $params, $isoCode);
            }
        }
        return $message;
    }

    /**
     * Set message with params for translation
     *
     * @param string $key log key
     * @param array|null $params log parameters
     *
     * @return string
     */
    public static function setLogMessage($key, $params = null)
    {
        if ($params === null || (is_array($params) && empty($params))) {
            return $key;
        }
        $allParams = array();
        foreach ($params as $param => $value) {
            $value = str_replace(array('|', '=='), array('', ''), $value);
            $allParams[] = $param . '==' . $value;
        }
        return $key . '[' . join('|', $allParams) . ']';
    }

    /**
     * Get a specific marketplace
     *
     * @param string $name Marketplace name
     *
     * @return LengowMarketplace
     *
     * @throws LengowException
     */
    public static function getMarketplaceSingleton($name)
    {
        if (!isset(self::$registers[$name])) {
            self::$registers[$name] = new LengowMarketplace($name);
        }
        return self::$registers[$name];
    }

    /**
     * Load Lengow Payment Shopware
     *
     * @return PaymentModel|null
     */
    public static function getLengowPayment()
    {
        $payment = Shopware()->Models()->getRepository(PaymentModel::class)->findOneBy(array('name' => 'lengow'));
        if ($payment === null) {
            $plugin = Shopware()->Models()->getRepository(PluginModel::class)->findOneBy(array('name' => 'Lengow'));
            if ($plugin !== null && !$plugin->getPayments()->isEmpty()) {
                $payment = $plugin->getPayments()->first();
            }
        }
        return $payment;
    }

    /**
     * Load Lengow technical error status
     *
     * @return OrderStatusModel|null
     */
    public static function getLengowTechnicalErrorStatus()
    {
        $params = LengowMain::compareVersion('5.1.0')
            ? array('name' => 'lengow_technical_error')
            : array('description' => 'Technischer Fehler - Lengow');
        /** @var OrderStatusModel $orderStatus */
        return Shopware()->Models()->getRepository(OrderStatusModel::class)->findOneBy($params);
    }

    /**
     * Get Shopware order status corresponding to the current order state
     *
     * @param string $orderStateMarketplace order state marketplace
     * @param LengowMarketplace $marketplace Lengow marketplace instance
     * @param boolean $shipmentByMp order shipped by marketplace
     *
     * @return OrderStatusModel|false
     */
    public static function getShopwareOrderStatus($orderStateMarketplace, $marketplace, $shipmentByMp = false)
    {
        if ($shipmentByMp) {
            $orderState = 'shipped_by_marketplace';
        } elseif ($marketplace->getStateLengow($orderStateMarketplace) === LengowOrder::STATE_SHIPPED
            || $marketplace->getStateLengow($orderStateMarketplace) === LengowOrder::STATE_CLOSED
        ) {
            $orderState = LengowOrder::STATE_SHIPPED;
        } else {
            $orderState = LengowOrder::STATE_ACCEPTED;
        }
        return self::getOrderStatus($orderState);
    }

    /**
     * Get the matching Shopware order status to the one given
     *
     * @param string $orderState state to be matched
     *
     * @return OrderStatusModel|false
     */
    public static function getOrderStatus($orderState)
    {
        switch ($orderState) {
            case LengowOrder::STATE_ACCEPTED:
            case LengowOrder::STATE_WAITING_SHIPMENT:
                $settingName = LengowConfiguration::WAITING_SHIPMENT_ORDER_ID;
                break;
            case LengowOrder::STATE_SHIPPED:
            case LengowOrder::STATE_CLOSED:
                $settingName = LengowConfiguration::SHIPPED_ORDER_ID;
                break;
            case LengowOrder::STATE_REFUSED:
            case LengowOrder::STATE_CANCELED:
                $settingName = LengowConfiguration::CANCELED_ORDER_ID;
                break;
            case 'shipped_by_marketplace':
                $settingName = LengowConfiguration::SHIPPED_BY_MARKETPLACE_ORDER_ID;
                break;
            default:
                $settingName = false;
                break;
        }
        if ($settingName) {
            $orderStatusId = LengowConfiguration::getConfig($settingName);
            try {
                /** @var OrderStatusModel $orderStatus */
                $orderStatus = Shopware()->Models()->getReference(OrderStatusModel::class, (int) $orderStatusId);
                if ($orderStatus !== null) {
                    return $orderStatus;
                }
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Get Shopware translation instance
     *
     * @return ShopwareTranslation
     */
    public static function getTranslationComponent()
    {
        if (self::$translation === null) {
            if (LengowMain::compareVersion('5.6', '<')) {
                self::$translation = new ShopwareTranslation();
            } else {
                self::$translation = Shopware()->Container()->get('translation');
            }
        }
        return self::$translation;
    }

    /**
     * Get property value translation
     *
     * @param integer $propertyValueId Shopware property value id
     * @param integer $shopId Shopware Shop Id
     *
     * @return string|false
     */
    public static function getPropertyValueTranslation($propertyValueId, $shopId)
    {
        if (array_key_exists($propertyValueId, self::$propertyValueTranslations)) {
            return self::$propertyValueTranslations[$propertyValueId];
        }
        $translation = self::getTranslationComponent();
        $propertyValueTranslation = $translation->read($shopId, 'propertyValue', $propertyValueId);
        self::$propertyValueTranslations[$propertyValueId] = !empty($propertyValueTranslation)
            ? $propertyValueTranslation['optionValue']
            : false;
        return self::$propertyValueTranslations[$propertyValueId];
    }

    /**
     * Get tax associated with a dispatch
     *
     * @param DispatchModel $dispatch Shopware dispatch instance
     *
     * @throws Exception
     *
     * @return TaxModel
     */
    public static function getDispatchTax($dispatch)
    {
        if ($dispatch->getTaxCalculation() !== 0 ) {
            $taxId = (int) $dispatch->getTaxCalculation();
        } else {
            $sql = 'SELECT DISTINCT SQL_CALC_FOUND_ROWS sct.id 
	     		FROM s_core_tax as sct
	            WHERE sct.tax = (SELECT MAX(tax) from s_core_tax)';
            $taxId = (int) Shopware()->Db()->fetchOne($sql);
        }
        return Shopware()->Models()->getReference(TaxModel::class, $taxId);
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
            ->from(UserModel::class, 'user')
            ->leftJoin(UserRoleModel::class, 'role')
            ->where('user.active = :active')
            ->andWhere('role.name = :name')
            ->setParameters(
                array(
                    'active' => 1,
                    'name' => 'local_admins',
                )
            );
        return $builder->getQuery()->getResult();
    }

    /**
     * Check order error table and send mail for order not imported correctly
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public static function sendMailAlert($logOutput = false)
    {
        $success = true;
        $orderErrors = LengowOrderError::getOrderErrorNotSent();
        if ($orderErrors) {
            $subject = self::decodeLogMessage('lengow_log/mail_report/subject_report_mail');
            $mailBody = self::getMailAlertBody($orderErrors);
            $emails = LengowConfiguration::getReportEmailAddress();
            foreach ($emails as $email) {
                if ($email !== '') {
                    if (self::sendMail($email, $subject, $mailBody)) {
                        self::log(
                            LengowLog::CODE_MAIL_REPORT,
                            self::setLogMessage('log/mail_report/send_mail_to', array('email' => $email)),
                            $logOutput
                        );
                    } else {
                        self::log(
                            LengowLog::CODE_MAIL_REPORT,
                            self::setLogMessage('log/mail_report/unable_send_mail_to', array('email' => $email)),
                            $logOutput
                        );
                        $success = false;
                    }
                }
            }
        }
        return $success;
    }

    /**
     * Get mail alert body and put mail attribute at true in order lengow record
     *
     * @param array $orderErrors order errors ready to be send
     *
     * @return string
     */
    public static function getMailAlertBody($orderErrors = array())
    {
        $mailBody = '';
        if (!empty($orderErrors)) {
            $pluginLinks = LengowSync::getPluginLinks();
            $support = self::decodeLogMessage(
                'lengow_log/mail_report/no_error_in_report_mail',
                null,
                array('support_link' => $pluginLinks[LengowSync::LINK_TYPE_SUPPORT])
            );
            $mailBody = '<h2>' . self::decodeLogMessage('lengow_log/mail_report/subject_report_mail') . '</h2><p><ul>';
            foreach ($orderErrors as $orderError) {
                $order = self::decodeLogMessage(
                    'lengow_log/mail_report/order',
                    null,
                    array('marketplace_sku' => $orderError['marketplaceSku'])
                );
                $message = $orderError['message'] != '' ? self::decodeLogMessage($orderError['message']) : $support;
                $mailBody .= '<li>' . $order . ' - ' . $message . '</li>';
                LengowOrderError::updateOrderError(
                    $orderError['id'],
                    array('mail' => true)
                );
            }
            $mailBody .= '</ul></p>';
        }
        return $mailBody;
    }

    /**
     * Send mail without template
     *
     * @param string $email send mail at
     * @param string $subject subject email
     * @param string $body body email
     *
     * @return boolean
     */
    public static function sendMail($email, $subject, $body)
    {
        try {
            $mail = new \Zend_Mail();
            $mail->setSubject($subject);
            $mail->setBodyHtml($body);
            $mail->setFrom(LengowConfiguration::getConfig('mail'), 'Lengow');
            $mail->addTo($email);
            $mail->send();
        } catch (\Exception $e) {
            return false;
        }
        return true;
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
                "\r",
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
                '',
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
            '/[\x{0152}]/u',
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
            'OE',
        );
        return preg_replace($patterns, $replacements, $str);
    }
}
