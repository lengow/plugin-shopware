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
class Shopware_Controllers_Frontend_LengowController extends Enlight_Controller_Action
{

    /**
     * Export Lengow feed
     */
    public function exportAction()
    {
        // Disable template for export
        $this->view->setTemplate(null);

        if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::checkIp()) {

            // see all export params
            $getParams = (bool) $this->Request()->getParam("get_params", false);
            if ($getParams) {
                echo Shopware_Plugins_Backend_Lengow_Components_LengowExport::getExportParams();
                die();
            }

            // get all GET params for export
            $mode = $this->Request()->getParam("mode");
            $format = $this->Request()->getParam("format", 'csv');
            $stream = (bool) $this->Request()->getParam("stream", true);
            $offset = (int) $this->Request()->getParam("offset");
            $limit = (int) $this->Request()->getParam("limit");
            $exportLengowSelection = $this->Request()->getParam("selection");
            $outStock =  $this->Request()->getParam("out_of_stock");
            $productsIds = $this->Request()->getParam("product_ids");
            $logOutput = (bool) $this->Request()->getParam("log_output", !$stream);
            $exportVariation = $this->Request()->getParam("variation");
            $exportDisabledProduct = $this->Request()->getParam("inactive");
            $shopId = $this->Request()->getParam("shop");
            $updateExportDate = (bool) $this->Request()->getParam("update_export_date", true);
            $currencyCode = $this->Request()->getParam("currency");

            $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();

            // If shop name has been filled
            if ($shopId) {

                $shop = $em->getRepository('Shopware\Models\Shop\Shop')->find($shopId);

                // A shop with this name exist
                if ($shop) {

                    $selectedProducts = array();
                    if ($productsIds) {
                        $ids = str_replace(array(';', '|', ':'), ',', $productsIds);
                        $ids = preg_replace('/[^0-9\,]/', '', $ids);
                        $selectedProducts = explode(',', $ids);
                    }
                    $currency = null;
                    // Look for existing currency with defined param
                    if ($currencyCode != null) {
                        $currency = $em->getRepository('Shopware\Models\Shop\Currency')
                            ->findOneBy(array('currency' => $currencyCode));
                    }
                    $params = array(
                        'format'                 => $format,
                        'mode'                   => $mode,
                        'stream'                 => $stream,
                        'productIds'             => $selectedProducts,
                        'limit'                  => $limit,
                        'offset'                 => $offset,
                        'exportOutOfStock'       => $outStock,
                        'exportVariationEnabled' => $exportVariation,
                        'exportDisabledProduct'  => $exportDisabledProduct,
                        'exportLengowSelection'  => $exportLengowSelection,
                        'logOutput'              => $logOutput,
                        'updateExportDate'       => $updateExportDate,
                        'currency'               => $currency
                    );
                    
                    try {
                        $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport($shop, $params);
                        $export->exec();
                    } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
                        $error_message = $e->getMessage();
                        $decoded_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                            $error_message
                        );
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                            'Export',
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                'log/export/export_failed',
                                array('decoded_message' => $decoded_message)
                            ),
                            $logOutput
                        );
                    }
                } else {
                    $shops = $em->getRepository('Shopware\Models\Shop\Shop')->findBy(array('active' => 1));
                    $index = count($shops);
                    $shopsIds = '[';
                    foreach ($shops as $shop) {
                        $shopsIds .= $shop->getId();
                        $index--;
                        $shopsIds .= ($index == 0) ? '' : ', ';
                    }
                    $shopsIds .= ']';
                    die(
                        'The following shop ('.$shopId.') does not exist. Please specify a valid shop name in : '.$shopsIds
                    );
                }
            } else {
                header('HTTP/1.1 400 Bad Request');
                die('Please specify a shop to export (ie. : ?shop=1)');
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);
        }

    }

    /**
     * Synchronize stock and cms options
     */
    public function cronAction()
    {
        // Disable template for export
        $this->view->setTemplate(null);

        $em = Shopware()->Models();
        $lengowPlugin = $em->getRepository('Shopware\Models\Plugin\Plugin')->findOneBy(array('name' => 'Lengow'));

        // If the plugin has not been installed
        if ($lengowPlugin == null) {
            header('HTTP/1.1 400 Bad Request');
            die('Lengow module is not installed');
        }

        // Lengow module is not active
        if (!$lengowPlugin->getActive()) {
            header('HTTP/1.1 400 Bad Request');
            die('Lengow module is not active');
        }

        // Check ip authorization
        if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::checkIp()) {

            $sync = $this->Request()->getParam("sync", false);

            if (!$sync || $sync === 'order') {
                // array of params for import order
                $params = array();
                if ($this->Request()->getParam("preprod_mode")) {
                    $params['preprod_mode'] = (bool)$this->Request()->getParam("preprod_mode");
                }
                if ($this->Request()->getParam("log_output")) {
                    $params['log_output'] = (bool)$this->Request()->getParam("log_output");
                }
                if ($this->Request()->getParam("days")) {
                    $params['days'] = (int)$this->Request()->getParam("days");
                }
                if ($this->Request()->getParam("limit")) {
                    $params['limit'] = (int)$this->Request()->getParam("limit");
                }
                if ($this->Request()->getParam("marketplace_sku")) {
                    $params['marketplace_sku'] = (string)$this->Request()->getParam("marketplace_sku");
                }
                if ($this->Request()->getParam("marketplace_name")) {
                    $params['marketplace_name'] = (string)$this->Request()->getParam("marketplace_name");
                }
                if ($this->Request()->getParam("delivery_address_id")) {
                    $params['delivery_address_id'] = (string)$this->Request()->getParam("delivery_address_id");
                }
                if ($this->Request()->getParam("shop_id")) {
                    $params['shop_id'] = (int)$this->Request()->getParam("shop_id");
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
            // sync option is not valid
            if ($sync && ($sync !== 'order' && $sync !== 'action' && $sync !== 'option')) {
                header('HTTP/1.1 400 Bad Request');
                die('Action: '.$sync.' is not a valid action');
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            die('Unauthorized access for IP : '.$_SERVER['REMOTE_ADDR']);
        }
    }
}
