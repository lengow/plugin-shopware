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
class Shopware_Plugins_Backend_Lengow_Components_LengowImport
{
    /**
     * Version.
     */
    const VERSION = '1.0.1';

    /**
     * @var integer shop id
     */
    protected $id_shop = null;

    /**
     * @var boolean use preprod mode
     */
    protected $preprod_mode = false;

    /**
     * @var boolean display log messages
     */
    protected $log_output = false;

    /**
     * @var string marketplace order sku
     */
    protected $marketplace_sku = null;

    /**
     * @var string markeplace name
     */
    protected $marketplace_name = null;

    /**
     * @var integer delivery address id
     */
    protected $delivery_address_id = null;

    /**
     * @var integer number of orders to import
     */
    protected $limit = 0;

    /**
     * @var string start import date
     */
    protected $date_from = null;

    /**
     * @var string end import date
     */
    protected $date_to = null;

    /**
     * @var string account ID
     */
    protected $account_id;

    /**
     * @var string access token
     */
    protected $access_token;

    /**
     * @var string secret
     */
    protected $secret;

    /**
     * @var LengowConnector Lengow connector
     */
    protected $connector;

    /**
     * @var Context Context for import order
     */
    protected $context;

    /**
     * @var string type import (manual or cron)
     */
    protected $type_import;

    /**
     * @var boolean import one order
     */
    protected $import_one_order = false;

    /**
     * @var array account ids already imported
     */
    protected $account_ids = array();

    /**
     * @var boolean import is processing
     */
    public static $processing;

    /**
     * @var string order id being imported
     */
    public static $current_order = -1;

    /**
     * @var array valid states lengow to create a Lengow order
     */
    public static $LENGOW_STATES = array(
        'accepted',
        'waiting_shipment',
        'shipped',
        'closed'
    );

    /**
     * Construct the import manager
     *
     * @param array params optional options
     * string    $marketplace_sku    lengow marketplace order id to import
     * string    $marketplace_name   lengow marketplace name to import
     * integer   $shop_id            Id shop for current import
     * boolean   $force_product      force import of products
     * boolean   $preprod_mode       preprod mode
     * string    $date_from          starting import date
     * string    $date_to            ending import date
     * integer   $limit              number of orders to import
     * boolean   $log_output         display log messages
     */
    public function __construct($params = array())
    {
        // params for re-import order
        if (array_key_exists('marketplace_sku', $params)
            && array_key_exists('marketplace_name', $params)
            && array_key_exists('shop_id', $params)
        ) {
            $this->marketplace_sku  = (string)$params['marketplace_sku'];
            $this->marketplace_name = (string)$params['marketplace_name'];
            $this->limit            = 1;
            $this->import_one_order = true;
            if (array_key_exists('delivery_address_id', $params) && $params['delivery_address_id'] != '') {
                $this->delivery_address_id = $params['delivery_address_id'];
            }
        } else {
            $this->marketplace_sku = null;
            // recovering the time interval
            $days = (
                isset($params['days'])
                ? (int)$params['days']
                : (int)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowImportDays')
            );
            $this->date_from = date('c', strtotime(date('Y-m-d').' -'.$days.'days'));
            $this->date_to = date('c');
            $this->limit = (isset($params['limit']) ? (int)$params['limit'] : 0);
        }
        // get other params
        $this->preprod_mode = (
            isset($params['preprod_mode'])
            ? (bool)$params['preprod_mode']
            : (bool)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('lengowPreprodMode')
        );
        $this->type_import = (isset($params['type']) ? $params['type'] : 'manual');
        $this->log_output = (isset($params['log_output']) ? (bool)$params['log_output'] : false);
        $this->id_shop = (isset($params['shop_id']) ? (int)$params['shop_id'] : null);
    }

    /**
     * Excute import : fetch orders and import them
     *
     * @return array
     */
    public function exec()
    {
        $order_new      = 0;
        $order_update   = 0;
        $order_error    = 0;
        $error          = array();
        $global_error   = false;
        // clean logs
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanLog();
        if (self::isInProcess() && !$this->preprod_mode && !$this->import_one_order) {
            $global_error = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('lengow_log.error.import_in_progress');
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log('Import', $global_error, $this->log_output);
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('lengow_log.error.rest_time_to_import', array(
                    'rest_time' => self::restTimeToImport()
                )),
                $this->log_output
            );
            $error[0] = $global_error;
            // if (isset($this->id_order_lengow) && $this->id_order_lengow) {
            //     LengowOrder::finishOrderLogs($this->id_order_lengow, 'import');
            //     LengowOrder::addOrderLog($this->id_order_lengow, $global_error, 'import');
            // }
        } else {
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log.import.start', array('type' => $this->type_import)),
                $this->log_output
            );
            if ($this->preprod_mode) {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log.import.preprod_mode_active'),
                    $this->log_output
                );
            }
            if (!$this->import_one_order) {
                self::setInProcess();
                // udpate last import date
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::updateDateImport($this->type_import);
            }
            // Shopware_Plugins_Backend_Lengow_Components_LengowMain::disableMail();
            // get all shops for import
            $shops = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShops();
            foreach ($shops as $shop) {
                if (!is_null($this->id_shop) && $shop->getId() != $this->id_shop) {
                    continue;
                }
                if ($shop->getActive()) {
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        'Import',
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log.import.start_for_shop', 
                            array(
                                'name_shop' => $shop->getName(),
                                'id_shop'   => $shop->getId()
                            )
                        ),
                        $this->log_output
                    );
                    try {
                        // check account ID, Access Token and Secret
                        $error_credential = $this->checkCredentials($shop);
                        if ($error_credential !== true) {
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                                'Import', 
                                $error_credential, 
                                $this->log_output
                            );
                            $error[$shop->getId()] = $error_credential;
                            continue;
                        }
                        // // get orders from Lengow API
                        $orders = $this->getOrdersFromApi($shop);
                        $total_orders = count($orders);
                        if ($this->import_one_order) {
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                                'Import',
                                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                    'log.import.find_one_order', 
                                    array(
                                    'nb_order'          => $total_orders,
                                    'marketplace_sku'   => $this->marketplace_sku,
                                    'markeplace_name'   => $this->marketplace_name,
                                    'account_id'        => $this->account_id
                                    )
                                ),
                                $this->log_output
                            );
                        } else {
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                                'Import',
                                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                    'log.import.find_all_orders', 
                                    array(
                                    'nb_order'   => $total_orders,
                                    'account_id' => $this->account_id
                                    )
                                ),
                                $this->log_output
                            );
                        }
                        if ($total_orders<=0 && $this->import_one_order) {
                            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException('lengow_log.error.order_not_found');
                        } elseif ($total_orders <= 0) {
                            continue;
                        }
                        // if (isset($this->id_order_lengow) && $this->id_order_lengow) {
                        //     LengowOrder::finishOrderLogs($this->id_order_lengow, 'import');
                        // }
                        // // import orders in prestashop
                        // $result = $this->importOrders($orders, (int)$shop->id);
                        if (!$this->import_one_order) {
                            $order_new      += $result['order_new'];
                            $order_update   += $result['order_update'];
                            $order_error    += $result['order_error'];
                        }
                    } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
                        $error_message = $e->getMessage();
                    } catch (Exception $e) {
                        $error_message = '[Shopware error] "'.$e->getMessage().'" '.$e->getFile().' | '.$e->getLine();
                    }
                    if (isset($error_message)) {
                        // if (isset($this->id_order_lengow) && $this->id_order_lengow) {
                        //     LengowOrder::finishOrderLogs($this->id_order_lengow, 'import');
                        //     LengowOrder::addOrderLog($this->id_order_lengow, $error_message, 'import');
                        // }
                        $decoded_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($error_message);
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                            'Import',
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                                'log.import.import_failed', 
                                array(
                                    'decoded_message' => $decoded_message
                                )
                            ),
                            $this->log_output
                        );
                        $error[$shop->getId()] = $error_message;
                        unset($error_message);
                        continue;
                    }
                }
                unset($shop);
            }
            if (!$this->import_one_order) {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log.error.nb_order_imported', 
                        array(
                            'nb_order' => $order_new
                        )
                    ),
                    $this->log_output
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log.error.nb_order_updated', array(
                        'nb_order' => $order_update
                    )),
                    $this->log_output
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('lengow_log.error.nb_order_with_error', array(
                        'nb_order' => $order_error
                    )),
                    $this->log_output
                );
            }
            // finish import process
            self::setEnd();
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log.import.end', array('type' => $this->type_import)),
                $this->log_output
            );
            // //check if order action is finish (Ship / Cancel)
            // if (!Shopware_Plugins_Backend_Lengow_Components_LengowMain::inTest()
            //     && !$this->preprod_mode
            //     && !$this->import_one_order
            //     && $this->type_import == 'manual'
            // ) {
            //     // LengowAction::checkFinishAction();
            // }
        }
        if ($this->import_one_order) {
            $result['error'] = $error;
            return $result;
        } else {
            return array(
                'order_new'     => $order_new,
                'order_update'  => $order_update,
                'order_error'   => $order_error,
                'error'         => $error
            );
        }
    }

    /**
     * Check credentials for a shop
     *
     * @param Shopware\Models\Shop\Shop $shop Shop
     *
     * @return boolean
     */
    protected function checkCredentials($shop)
    {
        $shopId = $shop->getId();
        $shopName = $shop->getName();
        $this->account_id = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowAccountId', 
            $shop
        );
        $this->access_token = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowAccessToken', 
            $shop
        );
        $this->secret = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
            'lengowSecretToken', 
            $shop
        );
        if (!$this->account_id || !$this->access_token || !$this->secret) {
            $message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log.error.account_id_empty', 
                array(
                    'shopName' => $shopName,
                    'shopId'   => $shopId
                )
            );
            return $message;
        }
        if (array_key_exists($this->account_id, $this->account_ids)) {
            $message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log.error.account_id_already_used', 
                array(
                    'account_id' => $this->account_id,
                    'shopName'  => $this->account_ids[$this->account_id]['name'],
                    'shopId'    => $this->account_ids[$this->account_id]['shopId'],
                )
            );
            return $message;
        }
        $this->account_ids[$this->account_id] = array('shopId' => $shopId, 'name' => $shopName);
        return true;
    }

    /**
     * Call Lengow order API
     *
     * @param  LengowShop $shop
     *
     * @return mixed
     */
    protected function getOrdersFromApi($shop)
    {
        $page = 1;
        $orders = array();



        // if (LengowCheck::isValidAuth((int)$shop->id)) {
        //     $this->connector  = new LengowConnector($this->access_token, $this->secret);
        //     if ($this->import_one_order) {
        //         LengowMain::log(
        //             'Import',
        //             LengowMain::setLogMessage('log.import.connector_get_order', array(
        //                 'marketplace_sku' => $this->marketplace_sku,
        //                 'markeplace_name' => $this->marketplace_name
        //             )),
        //             $this->log_output
        //         );
        //     } else {
        //         LengowMain::log(
        //             'Import',
        //             LengowMain::setLogMessage('log.import.connector_get_all_order', array(
        //                 'date_from'  => date('Y-m-d', strtotime((string)$this->date_from)),
        //                 'date_to'    => date('Y-m-d', strtotime((string)$this->date_to)),
        //                 'account_id' => $this->account_id
        //             )),
        //             $this->log_output
        //         );
        //     }
        //     do {
        //         if ($this->import_one_order) {
        //             $results = $this->connector->get(
        //                 '/v3.0/orders',
        //                 array(
        //                     'marketplace_order_id' => $this->marketplace_sku,
        //                     'marketplace'          => $this->marketplace_name,
        //                     'account_id'           => $this->account_id,
        //                     'page'                 => $page
        //                 ),
        //                 'stream'
        //             );
        //         } else {
        //             $results = $this->connector->get(
        //                 '/v3.0/orders',
        //                 array(
        //                     'updated_from' => $this->date_from,
        //                     'updated_to'   => $this->date_to,
        //                     'account_id'   => $this->account_id,
        //                     'page'         => $page
        //                 ),
        //                 'stream'
        //             );
        //         }
        //         if (is_null($results)) {
        //             throw new LengowException(
        //                 LengowMain::setLogMessage('lengow_log.exception.no_connection_webservice', array(
        //                     'name_shop' => $shop->name,
        //                     'id_shop'   => (int)$shop->id
        //                 ))
        //             );
        //         }
        //         $results = Tools::jsonDecode($results);
        //         if (!is_object($results)) {
        //             throw new LengowException(
        //                 LengowMain::setLogMessage('lengow_log.exception.no_connection_webservice', array(
        //                     'name_shop' => $shop->name,
        //                     'id_shop'   => (int)$shop->id
        //                 ))
        //             );
        //         }
        //         if (isset($results->error)) {
        //             throw new LengowException(
        //                 LengowMain::setLogMessage('lengow_log.exception.error_lengow_webservice', array(
        //                     'error_code'    => $results->error->code,
        //                     'error_message' => $results->error->message,
        //                     'name_shop'     => $shop->name,
        //                     'id_shop'       => (int)$shop->id
        //                 ))
        //             );
        //         }
        //         // Construct array orders
        //         foreach ($results->results as $order) {
        //             $orders[] = $order;
        //         }
        //         $page++;
        //     } while ($results->next != null);
        // } else {
        //     throw new LengowException(
        //         LengowMain::setLogMessage('lengow_log.exception.crendentials_not_valid', array(
        //             'name_shop' => $shop->name,
        //             'id_shop'   => (int)$shop->id
        //         ))
        //     );
        // }
        return $orders;
    }

    /**
     * Check if import is already in process
     *
     * @return boolean
     */
    public static function isInProcess()
    {
        $timestamp = (int)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('LENGOW_IMPORT_IN_PROGRESS');
        if ($timestamp > 0) {
            // security check : if last import is more than 10 min old => authorize new import to be launched
            if (($timestamp + (60 * 1)) < time()) {
                self::setEnd();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Get Rest time to make re import order
     *
     * @return boolean
     */
    public static function restTimeToImport()
    {
        $timestamp = (int)Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig('LENGOW_IMPORT_IN_PROGRESS');
        if ($timestamp > 0) {
            return $timestamp + (60 * 1) - time();
        }
        return false;
    }

    /**
     * Set import to "in process" state
     */
    public static function setInProcess()
    {
        self::$processing = true;
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig('LENGOW_IMPORT_IN_PROGRESS', time());
    }

    /**
     * Set import to finished
     */
    public static function setEnd()
    {
        self::$processing = false;
        Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig('LENGOW_IMPORT_IN_PROGRESS', time());
    }
}