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
 * @subpackage  Controllers
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware\Models\Shop\Shop as ShopModel;
use Shopware_Plugins_Backend_Lengow_Components_LengowAction as LengowAction;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowExport as LengowExport;
use Shopware_Plugins_Backend_Lengow_Components_LengowImport as LengowImport;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowSync as LengowSync;
use Shopware_Plugins_Backend_Lengow_Components_LengowToolbox as LengowToolbox;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

/**
 * Frontend Lengow Controller
 */
class Shopware_Controllers_Frontend_LengowController extends Enlight_Controller_Action
{
    /**
     * Export Lengow feed
     */
    public function exportAction()
    {
        /**
         * List params
         * string  mode               Number of products exported
         * string  token              Shop token for authorisation
         * string  format             Format of exported files ('csv','yaml','xml','json')
         * boolean stream             Stream file (1) or generate a file on server (0)
         * integer offset             Offset of total product
         * integer limit              Limit number of exported product
         * boolean selection          Export product selection (1) or all products (0)
         * boolean out_of_stock       Export out of stock product (1) Export only product in stock (0)
         * string  product_ids        List of product id separate with comma (1,2,3)
         * boolean variation          Export product Variation (1) Export parent product only (0)
         * boolean inactive           Export inactive product (1) or not (0)
         * integer shop               Export a specific shop
         * string  currency           Convert prices with a specific currency
         * boolean log_output         See logs (1) or not (0)
         * boolean update_export_date Change last export date in data base (1) or not (0)
         * boolean get_params         See export parameters and authorized values in json format (1) or not (0)
         */

        // disable template for export
        $this->view->setTemplate(null);
        // get all GET params for export
        $mode = $this->Request()->getParam(LengowExport::PARAM_MODE);
        $token = $this->Request()->getParam(LengowExport::PARAM_TOKEN);
        $format = $this->Request()->getParam(LengowExport::PARAM_FORMAT);
        $stream = $this->Request()->getParam(LengowExport::PARAM_STREAM);
        $offset = $this->Request()->getParam(LengowExport::PARAM_OFFSET);
        $limit = $this->Request()->getParam(LengowExport::PARAM_LIMIT);
        $selection = $this->Request()->getParam(LengowExport::PARAM_SELECTION);
        $outOfStock = $this->Request()->getParam(LengowExport::PARAM_OUT_OF_STOCK);
        $productsIds = $this->Request()->getParam(LengowExport::PARAM_PRODUCT_IDS);
        $logOutput = $this->Request()->getParam(LengowExport::PARAM_LOG_OUTPUT);
        $variation = $this->Request()->getParam(LengowExport::PARAM_VARIATION);
        $inactive = $this->Request()->getParam(LengowExport::PARAM_INACTIVE);
        $shopId = $this->Request()->getParam(LengowExport::PARAM_SHOP);
        $updateExportDate = $this->Request()->getParam(LengowExport::PARAM_UPDATE_EXPORT_DATE);
        $currency = $this->Request()->getParam(LengowExport::PARAM_CURRENCY);
        // if shop name has been filled
        if ($shopId === null) {
            header('HTTP/1.1 400 Bad Request');
            die(LengowMain::decodeLogMessage('log/export/specify_shop', LengowTranslation::DEFAULT_ISO_CODE));
        }
        $em = Shopware()->Models();
        /** @var ShopModel $shop */
        $shop = $em->getRepository(ShopModel::class)->find($shopId);
        // a shop with this name exist
        if ($shop === null) {
            /** @var ShopModel[] $shops */
            $shops = $em->getRepository(ShopModel::class)->findBy(array('active' => 1));
            $index = count($shops);
            $shopsIds = '[';
            foreach ($shops as $shop) {
                $shopsIds .= $shop->getId();
                $index--;
                $shopsIds .= $index === 0 ? '' : ', ';
            }
            $shopsIds .= ']';
            header('HTTP/1.1 400 Bad Request');
            die(
                LengowMain::decodeLogMessage(
                    'log/export/shop_dont_exist',
                    LengowTranslation::DEFAULT_ISO_CODE,
                    array(
                        'shop_id' => $shopId,
                        'shop_ids' => $shopsIds,
                    )
                )
            );
        }
        // check webservices access
        $accessErrorMessage = $this->checkAccess($token, $shop);
        if ($accessErrorMessage !== null) {
            header('HTTP/1.1 403 Forbidden');
            die($accessErrorMessage);
        }
        // see all export params
        if ($this->Request()->getParam(LengowExport::PARAM_GET_PARAMS) === '1') {
            echo LengowExport::getExportParams();
        } else {
            try {
                $export = new LengowExport(
                    $shop,
                    array(
                        LengowExport::PARAM_FORMAT => $format,
                        LengowExport::PARAM_MODE => $mode,
                        LengowExport::PARAM_STREAM => $stream,
                        LengowExport::PARAM_PRODUCT_IDS => $productsIds,
                        LengowExport::PARAM_LIMIT => $limit,
                        LengowExport::PARAM_OFFSET => $offset,
                        LengowExport::PARAM_OUT_OF_STOCK => $outOfStock,
                        LengowExport::PARAM_VARIATION => $variation,
                        LengowExport::PARAM_INACTIVE => $inactive,
                        LengowExport::PARAM_SELECTION => $selection,
                        LengowExport::PARAM_LOG_OUTPUT => $logOutput,
                        LengowExport::PARAM_UPDATE_EXPORT_DATE => $updateExportDate,
                        LengowExport::PARAM_CURRENCY => $currency,
                    )
                );
                $export->exec();
            } catch (Exception $e) {
                $errorMessage = '[Shopware error] "' . $e->getMessage()
                    . '" ' . $e->getFile() . ' | ' . $e->getLine();
                LengowMain::log(
                    LengowLog::CODE_EXPORT,
                    LengowMain::setLogMessage(
                        'log/export/export_failed',
                        array('decoded_message' => $errorMessage)
                    ),
                    $logOutput
                );
            }
        }
    }

    /**
     * Synchronize stock and cms options
     */
    public function cronAction()
    {
        /**
         * List params
         * string  token               Global token for authorisation
         * string  sync                Number of products exported
         * integer days                Import period
         * integer limit               Number of orders to import
         * integer shop_id             Shop id to import
         * string  marketplace_sku     Lengow marketplace order id to import
         * string  marketplace_name    Lengow marketplace name to import
         * string  created_from        import of orders since
         * string  created_to          import of orders until
         * integer delivery_address_id Lengow delivery address id to import
         * boolean debug_mode          Activate debug mode
         * boolean log_output          See logs (1) or not (0)
         * boolean get_sync            See synchronisation parameters in json format (1) or not (0)
         */

        // disable template for cron
        $this->view->setTemplate(null);
        $token = $this->Request()->getParam(LengowImport::PARAM_TOKEN);
        // check webservices access
        $accessErrorMessage = $this->checkAccess($token);
        if ($accessErrorMessage !== null) {
            header('HTTP/1.1 403 Forbidden');
            die($accessErrorMessage);
        }
        // get all store data for synchronisation with Lengow
        if ($this->Request()->getParam(LengowImport::PARAM_GET_SYNC) === '1') {
            echo json_encode(LengowSync::getSyncData());
        } else {
            $force = $this->Request()->getParam(LengowImport::PARAM_FORCE) === '1';
            $logOutput = $this->Request()->getParam(LengowImport::PARAM_LOG_OUTPUT) === '1';
            // get sync action if exists
            $sync = $this->Request()->getParam(LengowImport::PARAM_SYNC, false);
            // sync catalogs id between Lengow and Shopware
            if (!$sync || $sync === LengowSync::SYNC_CATALOG) {
                LengowSync::syncCatalog($force, $logOutput);
            }
            // sync orders between Lengow and Shopware
            if (!$sync || $sync === LengowSync::SYNC_ORDER) {
                $params = array(
                    LengowImport::PARAM_TYPE => LengowImport::TYPE_CRON,
                    LengowImport::PARAM_LOG_OUTPUT => $logOutput
                );
                if ($this->Request()->getParam(LengowImport::PARAM_DEBUG_MODE) !== null) {
                    $params[LengowImport::PARAM_DEBUG_MODE] = $this->Request()
                            ->getParam(LengowImport::PARAM_DEBUG_MODE) === '1';
                }
                if ($this->Request()->getParam(LengowImport::PARAM_DAYS)) {
                    $params[LengowImport::PARAM_DAYS] = (int) $this->Request()
                        ->getParam(LengowImport::PARAM_DAYS);
                }
                if ($this->Request()->getParam(LengowImport::PARAM_CREATED_FROM)) {
                    $params[LengowImport::PARAM_CREATED_FROM] = $this->Request()
                        ->getParam(LengowImport::PARAM_CREATED_FROM);
                }
                if ($this->Request()->getParam(LengowImport::PARAM_CREATED_TO)) {
                    $params[LengowImport::PARAM_CREATED_TO] = $this->Request()
                        ->getParam(LengowImport::PARAM_CREATED_TO);
                }
                if ($this->Request()->getParam(LengowImport::PARAM_LIMIT)) {
                    $params[LengowImport::PARAM_LIMIT] = (int) $this->Request()
                        ->getParam(LengowImport::PARAM_LIMIT);
                }
                if ($this->Request()->getParam(LengowImport::PARAM_MARKETPLACE_SKU)) {
                    $params[LengowImport::PARAM_MARKETPLACE_SKU] = $this->Request()
                        ->getParam(LengowImport::PARAM_MARKETPLACE_SKU);
                }
                if ($this->Request()->getParam(LengowImport::PARAM_MARKETPLACE_NAME)) {
                    $params[LengowImport::PARAM_MARKETPLACE_NAME] = $this->Request()
                        ->getParam(LengowImport::PARAM_MARKETPLACE_NAME);
                }
                if ($this->Request()->getParam(LengowImport::PARAM_DELIVERY_ADDRESS_ID)) {
                    $params[LengowImport::PARAM_DELIVERY_ADDRESS_ID] = $this->Request()
                        ->getParam(LengowImport::PARAM_DELIVERY_ADDRESS_ID);
                }
                if ($this->Request()->getParam(LengowImport::PARAM_SHOP_ID)) {
                    $params[LengowImport::PARAM_SHOP_ID] = (int) $this->Request()
                        ->getParam(LengowImport::PARAM_SHOP_ID);
                }
                // synchronise orders
                $import = new LengowImport($params);
                $import->exec();
            }
            // sync actions between Lengow and Shopware
            if (!$sync || $sync === LengowSync::SYNC_ACTION) {
                LengowAction::checkFinishAction($logOutput);
                LengowAction::checkOldAction($logOutput);
                LengowAction::checkActionNotSent($logOutput);
            }
            // sync options between Lengow and Shopware
            if (!$sync || $sync === LengowSync::SYNC_CMS_OPTION) {
                LengowSync::setCmsOption($force, $logOutput);
            }
            // sync marketplaces between Lengow and Shopware
            if ($sync === LengowSync::SYNC_MARKETPLACE) {
                LengowSync::getMarketplaces($force, $logOutput);
            }
            // sync status account between Lengow and Shopware
            if ($sync === LengowSync::SYNC_STATUS_ACCOUNT) {
                LengowSync::getStatusAccount($force, $logOutput);
            }
            // sync plugin data between Lengow and Shopware
            if ($sync === LengowSync::SYNC_PLUGIN_DATA) {
                LengowSync::getPluginData($force, $logOutput);
            }
            // sync parameter is not valid
            if ($sync && !in_array($sync, LengowSync::$syncActions, true)) {
                header('HTTP/1.1 400 Bad Request');
                die(
                    LengowMain::decodeLogMessage(
                        'log/import/not_valid_action',
                        LengowTranslation::DEFAULT_ISO_CODE,
                        array('action' => $sync)
                    )
                );
            }
        }
    }

    /**
     * Get all plugin data for toolbox
     */
    public function toolboxAction()
    {
        /**
         * List params
         * string toolbox_action toolbox specific action
         * string type           type of data to display
         * string date           date of the log to export
         */

        // disable template for cron
        $this->view->setTemplate(null);
        $token = $this->Request()->getParam(LengowToolbox::PARAM_TOKEN);
        // check webservices access
        $accessErrorMessage = $this->checkAccess($token);
        if ($accessErrorMessage !== null) {
            header('HTTP/1.1 403 Forbidden');
            die($accessErrorMessage);
        }
        // check if toolbox action is valid
        $action = $this->Request()->getParam(LengowToolbox::PARAM_TOOLBOX_ACTION) ?: LengowToolbox::ACTION_DATA;
        if (!in_array($action, LengowToolbox::$toolboxActions, true)) {
            header('HTTP/1.1 400 Bad Request');
            die(
                LengowMain::decodeLogMessage(
                    'log/import/not_valid_action',
                    LengowTranslation::DEFAULT_ISO_CODE,
                    array('action' => $action)
                )
            );
        }
        switch ($action) {
            case LengowToolbox::ACTION_LOG:
                $date = $this->Request()->getParam(LengowToolbox::PARAM_DATE);
                LengowToolbox::downloadLog($date);
                break;
            default:
                $type = $this->Request()->getParam(LengowToolbox::PARAM_TYPE);
                echo json_encode(LengowToolbox::getData($type));
                break;
        }
    }

    /**
     * Check access by token or ip
     *
     * @param string $token shop token
     * @param ShopModel|null $shop Shopware shop instance
     *
     * @return string|null
     */
    private function checkAccess($token, $shop = null)
    {
        $errorMessage = null;
        if (!LengowMain::checkWebservicesAccess($token, $shop)) {
            if (LengowConfiguration::getConfig(LengowConfiguration::AUTHORIZED_IP_ENABLED)) {
                $errorMessage = LengowMain::decodeLogMessage(
                    'log/export/unauthorised_ip',
                    LengowTranslation::DEFAULT_ISO_CODE,
                    array('ip' => $_SERVER['REMOTE_ADDR'])
                );
            } else {
                $errorMessage = $token !== ''
                    ? LengowMain::decodeLogMessage(
                        'log/export/unauthorised_token',
                        LengowTranslation::DEFAULT_ISO_CODE,
                        array('token' => $token)
                    )
                    : LengowMain::decodeLogMessage(
                        'log/export/empty_token',
                        LengowTranslation::DEFAULT_ISO_CODE
                    );
            }
        }
        return $errorMessage;
    }
}
