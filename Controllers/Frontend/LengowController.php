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
        if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::checkIp()) {
            // see all export params
            if ($this->Request()->getParam("get_params") == 1) {
                echo Shopware_Plugins_Backend_Lengow_Components_LengowExport::getExportParams();
            } else {
                // get all GET params for export
                $mode             = $this->Request()->getParam('mode');
                $format           = $this->Request()->getParam('format');
                $stream           = $this->Request()->getParam('stream');
                $offset           = $this->Request()->getParam('offset');
                $limit            = $this->Request()->getParam('limit');
                $selection        = $this->Request()->getParam('selection');
                $outOfStock       = $this->Request()->getParam('out_of_stock');
                $productsIds      = $this->Request()->getParam('product_ids');
                $logOutput        = $this->Request()->getParam('log_output');
                $variation        = $this->Request()->getParam('variation');
                $inactive         = $this->Request()->getParam('inactive');
                $shopId           = $this->Request()->getParam('shop');
                $updateExportDate = $this->Request()->getParam('update_export_date');
                $currency         = $this->Request()->getParam('currency');
                // get Entity manager
                $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
                // if shop name has been filled
                if ($shopId) {
                    $shop = $em->getRepository('Shopware\Models\Shop\Shop')->find($shopId);
                    // a shop with this name exist
                    if ($shop) {
                        try {
                            $export = new Shopware_Plugins_Backend_Lengow_Components_LengowExport(
                                $shop,
                                array(
                                    'format'             => $format,
                                    'mode'               => $mode,
                                    'stream'             => $stream,
                                    'product_ids'        => $productsIds,
                                    'limit'              => $limit,
                                    'offset'             => $offset,
                                    'out_of_stock'       => $outOfStock,
                                    'variation'          => $variation,
                                    'inactive'           => $inactive,
                                    'selection'          => $selection,
                                    'log_output'         => $logOutput,
                                    'update_export_date' => $updateExportDate,
                                    'currency'           => $currency
                                )
                            );
                            $export->exec();
                        } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
                            $errorMessage = $e->getMessage();
                            $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                                $errorMessage
                            );
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                                'Export',
                                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                    'log/export/export_failed',
                                    array('decoded_message' => $decodedMessage)
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
                        header('HTTP/1.1 400 Bad Request');
                        die(
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                                'log/export/shop_dont_exist',
                                null,
                                array(
                                    'shop_id'  => $shopId,
                                    'shop_ids' => $shopsIds
                                )
                            )
                        );
                    }
                } else {
                    header('HTTP/1.1 400 Bad Request');
                    die(
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                            'log/export/specify_shop'
                        )
                    );
                }
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            die(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    'log/export/unauthorised_ip',
                    null,
                    array('ip' => $_SERVER['REMOTE_ADDR'])
                )
            );
        }
    }

    /**
     * Synchronize stock and cms options
     */
    public function cronAction()
    {
        // Disable template for cron
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
            // get all store datas for synchronisation with Lengow
            if ($this->Request()->getParam('get_sync') == 1) {
                echo json_encode(Shopware_Plugins_Backend_Lengow_Components_LengowSync::getSyncData());
            } else {
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
            }            
        } else {
            header('HTTP/1.1 403 Forbidden');
            die(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    'log/export/unauthorised_ip',
                    null,
                    array('ip' => $_SERVER['REMOTE_ADDR'])
                )
            );
        }
    }
}
