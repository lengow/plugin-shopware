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

/**
 * Frontend Lengow Controller
 */
class Shopware_Controllers_Frontend_LengowController extends Enlight_Controller_Action
{
    /**
     * Export Lengow feed
     *
     *
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

        // Disable template for export
        $this->view->setTemplate(null);
        // get all GET params for export
        $mode = $this->Request()->getParam('mode');
        $token = $this->Request()->getParam('token');
        $format = $this->Request()->getParam('format');
        $stream = $this->Request()->getParam('stream');
        $offset = $this->Request()->getParam('offset');
        $limit = $this->Request()->getParam('limit');
        $selection = $this->Request()->getParam('selection');
        $outOfStock = $this->Request()->getParam('out_of_stock');
        $productsIds = $this->Request()->getParam('product_ids');
        $logOutput = $this->Request()->getParam('log_output');
        $variation = $this->Request()->getParam('variation');
        $inactive = $this->Request()->getParam('inactive');
        $shopId = $this->Request()->getParam('shop');
        $updateExportDate = $this->Request()->getParam('update_export_date');
        $currency = $this->Request()->getParam('currency');
        // if shop name has been filled
        if (is_null($shopId)) {
            header('HTTP/1.1 400 Bad Request');
            die(Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage('log/export/specify_shop'));
        }
        $em = Shopware()->Models();
        $shop = $em->getRepository('Shopware\Models\Shop\Shop')->find($shopId);
        // a shop with this name exist
        if (is_null($shop)) {
            $shops = $em->getRepository('Shopware\Models\Shop\Shop')->findBy(array('active' => 1));
            $index = count($shops);
            $shopsIds = '[';
            foreach ($shops as $shop) {
                $shopsIds .= $shop->getId();
                $index--;
                $shopsIds .= ($index == 0) ? '' : ', ';
            }
            $shopsIds .= ']';
            header('HTTP/1.1 400 Bad Request');
            die(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    'log/export/shop_dont_exist',
                    null,
                    array(
                        'shop_id' => $shopId,
                        'shop_ids' => $shopsIds
                    )
                )
            );
        }
        if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::checkWebservicesAccess($token, $shop)) {
            // see all export params
            if ($this->Request()->getParam('get_params') == 1) {
                echo Shopware_Plugins_Backend_Lengow_Components_LengowExport::getExportParams();
            } else {
                try {
                    $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport(
                        $shop,
                        array(
                            'format' => $format,
                            'mode' => $mode,
                            'stream' => $stream,
                            'product_ids' => $productsIds,
                            'limit' => $limit,
                            'offset' => $offset,
                            'out_of_stock' => $outOfStock,
                            'variation' => $variation,
                            'inactive' => $inactive,
                            'selection' => $selection,
                            'log_output' => $logOutput,
                            'update_export_date' => $updateExportDate,
                            'currency' => $currency
                        )
                    );
                    $export->exec();
                } catch (Exception $e) {
                    $errorMessage = '[Shopware error] "' . $e->getMessage()
                        . '" ' . $e->getFile() . ' | ' . $e->getLine();
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        'Export',
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/export/export_failed',
                            array('decoded_message' => $errorMessage)
                        ),
                        $logOutput
                    );
                }
            }
        } else {
            if ((bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowIpEnabled')) {
                $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    'log/export/unauthorised_ip',
                    null,
                    array('ip' => $_SERVER['REMOTE_ADDR'])
                );
            } else {
                if (strlen($token) > 0) {
                    $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                        'log/export/unauthorised_token',
                        null,
                        array('token' => $token)
                    );
                } else {
                    $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                        'log/export/empty_token'
                    );
                }
            }
            header('HTTP/1.1 403 Forbidden');
            die($errorMessage);
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
         * string  $marketplace_sku    Lengow marketplace order id to import
         * string  marketplace_name    Lengow marketplace name to import
         * integer delivery_address_id Lengow delivery address id to import
         * boolean preprod_mode        Activate preprod mode
         * boolean log_output          See logs (1) or not (0)
         * boolean get_sync            See synchronisation parameters in json format (1) or not (0)
         */

        // Disable template for cron
        $this->view->setTemplate(null);
        $token = $this->Request()->getParam('token');
        // Check Webservices Access
        if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::checkWebservicesAccess($token)) {
            // get all store datas for synchronisation with Lengow
            if ($this->Request()->getParam('get_sync') == 1) {
                echo json_encode(Shopware_Plugins_Backend_Lengow_Components_LengowSync::getSyncData());
            } else {
                $sync = $this->Request()->getParam('sync', false);
                // sync catalogs id between Lengow and Shopware
                if (!$sync || $sync === 'catalog') {
                    Shopware_Plugins_Backend_Lengow_Components_LengowSync::syncCatalog();
                }
                // sync orders between Lengow and Shopware
                if (!$sync || $sync === 'order') {
                    $params = array();
                    if ($this->Request()->getParam('preprod_mode')) {
                        $params['preprod_mode'] = (bool)$this->Request()->getParam('preprod_mode');
                    }
                    if ($this->Request()->getParam('log_output')) {
                        $params['log_output'] = (bool)$this->Request()->getParam('log_output');
                    }
                    if ($this->Request()->getParam('days')) {
                        $params['days'] = (int)$this->Request()->getParam('days');
                    }
                    if ($this->Request()->getParam('limit')) {
                        $params['limit'] = (int)$this->Request()->getParam('limit');
                    }
                    if ($this->Request()->getParam('marketplace_sku')) {
                        $params['marketplace_sku'] = (string)$this->Request()->getParam('marketplace_sku');
                    }
                    if ($this->Request()->getParam('marketplace_name')) {
                        $params['marketplace_name'] = (string)$this->Request()->getParam('marketplace_name');
                    }
                    if ($this->Request()->getParam('delivery_address_id')) {
                        $params['delivery_address_id'] = (string)$this->Request()->getParam('delivery_address_id');
                    }
                    if ($this->Request()->getParam('shop_id')) {
                        $params['shop_id'] = (int)$this->Request()->getParam('shop_id');
                    }
                    $params['type'] = 'cron';
                    // import orders
                    $import = new Shopware_Plugins_Backend_Lengow_Components_LengowImport($params);
                    $import->exec();
                }
                // sync options between Lengow and Shopware
                if (!$sync || $sync === 'option') {
                    Shopware_Plugins_Backend_Lengow_Components_LengowSync::setCmsOption();
                }
                // sync parameter is not valid
                if ($sync && !in_array($sync, Shopware_Plugins_Backend_Lengow_Components_LengowSync::$syncActions)) {
                    header('HTTP/1.1 400 Bad Request');
                    die(
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                            'log/import/not_valid_action',
                            null,
                            array('action' => $sync)
                        )
                    );
                }
            }
        } else {
            if ((bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowIpEnabled')) {
                $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    'log/export/unauthorised_ip',
                    null,
                    array('ip' => $_SERVER['REMOTE_ADDR'])
                );
            } else {
                if (strlen($token) > 0) {
                    $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                        'log/export/unauthorised_token',
                        null,
                        array('token' => $token)
                    );
                } else {
                    $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                        'log/export/empty_token'
                    );
                }
            }
            header('HTTP/1.1 403 Forbidden');
            die($errorMessage);
        }
    }
}
