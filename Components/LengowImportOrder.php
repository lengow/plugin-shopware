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
class Shopware_Plugins_Backend_Lengow_Components_LengowImportOrder
{
    /**
     * @var Shopware\Models\Shop\Shop Shopware shop
     */
    protected $shop;

    /**
     * @var boolean use preprod mode
     */
    protected $preprod_mode = false;

    /**
     * @var boolean display log messages
     */
    protected $log_output = false;

    /**
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace
     */
    protected $marketplace;

    /**
     * @var string id lengow of current order
     */
    protected $marketplace_sku;

    /**
     * @var integer id of delivery address for current order
     */
    protected $delivery_address_id;

    /**
     * @var mixed
     */
    protected $order_data;

    /**
     * @var mixed
     */
    protected $package_data;

    /**
     * @var boolean
     */
    protected $first_package;

    /**
     * @var string
     */
    protected $order_state_marketplace;

    /**
     * @var string
     */
    protected $order_state_lengow;

    /**
     * @var integer id of the record Lengow order table
     */
    protected $lengow_order_id;

    /**
     * @var array
     */
    protected $articles;

    /**
     * @var boolean True if order is send by the marketplace
     */
    protected $shipped_by_mp = false;

    /**
     * Construct the import manager
     *
     * @param $params array Optional options
     * Shopware\Models\Shop\Shop $shop                Id shop for current order
     * boolean                   $preprod_mode        preprod mode
     * boolean                   $log_output          display log messages
     * string                    $marketplace_sku     order marketplace sku
     * integer                   $delivery_address_id order delivery address id
     * mixed                     $order_data          order data
     * mixed                     $package_data        package data
     * boolean                   $first_package       it is the first package
     */
    public function __construct($params = array())
    {
        $this->shop                 = $params['shop'];
        $this->preprod_mode         = $params['preprod_mode'];
        $this->log_output           = $params['log_output'];
        $this->marketplace_sku      = $params['marketplace_sku'];
        $this->delivery_address_id  = $params['delivery_address_id'];
        $this->order_data           = $params['order_data'];
        $this->package_data         = $params['package_data'];
        $this->first_package        = $params['first_package'];
        // get marketplace and Lengow order state
        $this->marketplace = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getMarketplaceSingleton(
            (string)$this->order_data->marketplace,
            $this->shop
        );
        $this->order_state_marketplace = (string)$this->order_data->marketplace_status;
        $this->order_state_lengow = $this->marketplace->getStateLengow($this->order_state_marketplace);
    }

    /**
     * Create or update order
     *
     * @return mixed
     */
    public function importOrder()
    {
        // get a record in the lengow order table
        $this->lengow_order_id = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getIdFromLengowOrders(
            $this->marketplace_sku,
            (string)$this->marketplace->name,
            $this->delivery_address_id
        );
        // If order does not already exist
        if ($this->lengow_order_id !== false) {
            $this->log('log/import/order_already_decremented');
            return false;
        }
        // if order is cancelled or new -> skip
        if (!Shopware_Plugins_Backend_Lengow_Components_LengowImport::checkState(
            $this->order_state_marketplace,
            $this->marketplace
        )) {
            $this->log(
                'log/import/current_order_state_unavailable',
                array(
                    'order_state_marketplace' => $this->order_state_marketplace,
                    'marketplace_name'        => $this->marketplace->name
                )
            );
            return false;
        }
        // checks if the required order data is present
        if (!$this->checkOrderData()) {
            return $this->returnResult('error', $this->lengow_order_id);
        }
        // load tracking data
        $this->loadTrackingData();
        try {
            // check if the order is shipped by marketplace
            if ($this->shipped_by_mp) {
                $importMpOrdersOption = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                    'lengowDecreaseStock'
                );
                // If decrease stocks from mp option is activated
                if (!$importMpOrdersOption) {
                    $this->log(
                        'log/import/order_shipped_by_marketplace',
                        array('marketplace_name' => $this->marketplace->name)
                    );
                    return false;
                }
            }
            // get products
            $products = $this->getProducts();
            $this->decreaseStocks($products);
        } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
            $error_message = $e->getMessage();
        } catch (Exception $e) {
            $error_message = '[Shopware error] "'.$e->getMessage().'" '.$e->getFile().' | '.$e->getLine();
        }
        if (isset($error_message)) {
            $decoded_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                $error_message
            );
            $this->log('log/import/order_import_failed', array('decoded_message' => $decoded_message));
            return $this->returnResult('error', $this->lengow_order_id);
        }
        // created a record in the lengow order table
        if (!empty($products)) {
            if (!$this->createLengowOrder()) {
                $this->log('log/import/lengow_order_not_saved');
                return $this->returnResult('error', $this->lengow_order_id);
            } else {
                $this->log('log/import/lengow_order_saved');
                return $this->returnResult('new', $this->lengow_order_id);
            }
        } else {
            // Empty cart
            return $this->returnResult('error', $this->lengow_order_id);
        }
    }

    /**
     * Get products from the API and check that they exist in Shopware database
     *
     * @return array List of products found in Shopware
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException
     *      If product : not found|canceled|refused|is parent|
     */
    protected function getProducts()
    {
        $products = array();
        $advancedSearchFields = array('number', 'ean');
        foreach ($this->package_data->cart as $article) {
            $articleData = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::extractProductDataFromAPI(
                $article
            );
            if (!is_null($articleData['marketplace_status'])) {
                $state_product = $this->marketplace->getStateLengow((string)$articleData['marketplace_status']);
                if ($state_product == 'canceled' || $state_product == 'refused') {
                    $articleId = (!is_null($articleData['merchant_product_id']->id)
                        ? (string)$articleData['merchant_product_id']->id
                        : (string)$articleData['marketplace_product_id']
                    );
                    $this->log(
                        'log/import/product_state_canceled',
                        array(
                            'product_id'    => $articleId,
                            'state_product' => $state_product
                        )
                    );
                    continue;
                }
            }
            $articleIds = array(
                'idMerchant' => (string)$articleData['merchant_product_id']->id,
                'idMP'       => (string)$articleData['marketplace_product_id']
            );
            $found = false;
            foreach ($articleIds as $attribute_name => $attribute_value) {
                // remove _FBA from product id
                $attribute_value = preg_replace('/_FBA$/', '', $attribute_value);
                if (empty($attribute_value)) {
                    continue;
                }
                $isParentProduct = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::checkIsParentProduct(
                    $attribute_value
                );
                // If found, id does not concerns a variation but a parent
                if ($isParentProduct) {
                    $error_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/product_is_a_parent',
                        array('product_id' => $attribute_value)
                    );
                    throw new Shopware_Plugins_Backend_Lengow_Components_LengowException($error_message);
                }
                $shopwareDetailId = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::findArticle(
                    $attribute_value
                );
                if ($shopwareDetailId == null) {
                    $this->log(
                        'log/import/product_advanced_search',
                        array(
                            'attribute_name'  => $attribute_name,
                            'attribute_value' => $attribute_value
                        )
                    );
                    foreach ($advancedSearchFields as $field) {
                        $shopwareDetailId = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::advancedSearch(
                            $field,
                            $attribute_value,
                            $this->log_output
                        );
                        if ($shopwareDetailId != null) {
                            break;
                        }
                    }
                }
                if ($shopwareDetailId != null) {
                    $articleDetailId = $shopwareDetailId;
                    if (array_key_exists($articleDetailId, $products)) {
                        $products[$articleDetailId]['quantity'] += (integer)$articleData['quantity'];
                        $products[$articleDetailId]['amount'] += (float)$articleData['amount'];
                    } else {
                        $products[$articleDetailId] = $articleData;
                    }
                    $this->log(
                        'log/import/product_be_found',
                        array(
                            'id_full'         => $articleDetailId,
                            'attribute_name'  => $attribute_name,
                            'attribute_value' => $attribute_value
                        )
                    );
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $articleId = (!is_null($articleData['merchant_product_id']->id)
                    ? (string)$articleData['merchant_product_id']->id
                    : (string)$articleData['marketplace_product_id']
                );
                $error_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/exception/product_not_be_found',
                    array('product_id' => $articleId)
                );
                $this->log($error_message);
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException($error_message);
            }
        }
        return $products;
    }

    /**
     * Get tracking data and update Lengow order record
     */
    protected function loadTrackingData()
    {
        $tracking = $this->package_data->delivery->trackings;
        if (count($tracking) > 0) {
            if (!is_null($tracking[0]->is_delivered_by_marketplace) && $tracking[0]->is_delivered_by_marketplace) {
                $this->shipped_by_mp = true;
            }
        }
    }

    /**
     * Decrease stocks for a giving product
     * @param $products
     */
    protected function decreaseStocks($products)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        foreach ($products as $key => $product) {
            /** @var Shopware\Models\Article\Detail $shopwareArticle */
            $shopwareArticle = $em->getReference('Shopware\Models\Article\Detail', $key);
            $quantity = $shopwareArticle->getInStock();
            $newStock = $quantity - $product['quantity'];
            $shopwareArticle->setInStock($newStock);
            $em->persist($shopwareArticle);
            $em->flush($shopwareArticle);
            $this->log(
                'log/import/stock_decreased',
                array(
                    'article_id'    => $key,
                    'initial_stock' => $quantity,
                    'new_stock'     => $newStock
                )
            );
        }
    }

    /**
     * Create a order in lengow orders table
     *
     * @return boolean
     */
    protected function createLengowOrder()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        if (!is_null($this->order_data->marketplace_order_date)) {
            $order_date = (string)$this->order_data->marketplace_order_date;
        } else {
            $order_date = (string)$this->order_data->imported_at;
        }
        try {
            $dateTime = new DateTime(date('Y-m-d H:i:s', strtotime($order_date)));
            $lengowOrder = new Shopware\CustomModels\Lengow\Order();
            $lengowOrder->setShopId($this->shop->getId())
                ->setDeliveryAddressId($this->delivery_address_id)
                ->setMarketplaceSku($this->marketplace_sku)
                ->setMarketplaceName(strtolower($this->order_data->marketplace))
                ->setOrderDate($dateTime)
                ->setCreatedAt(new DateTime())
                ->setExtra(json_encode($this->order_data));
            $this->lengow_order_id = $lengowOrder->getId();
            $em->persist($lengowOrder);
            $em->flush($lengowOrder);
            return true;
        } catch (Exception $e) {
            $error_message = '[Shopware error] "'.$e->getMessage().'" '.$e->getFile().' | '.$e->getLine();
            $decoded_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                $error_message
            );
            $this->log('log/exception/order_insert_failed', array('decoded_message' => $decoded_message));
            return false;
        }
    }

    /**
     * Return an array of result for each order
     *
     * @param string  $type_result     Type of result (new, update, error)
     * @param integer $id_order_lengow ID of the lengow order record
     * @param integer $order_id        Shopware order id
     *
     * @return array
     */
    protected function returnResult($type_result, $id_order_lengow, $order_id = null)
    {
        $result = array(
            'order_id'         => $order_id,
            'id_order_lengow'  => $id_order_lengow,
            'marketplace_sku'  => $this->marketplace_sku,
            'marketplace_name' => (string)$this->marketplace->name,
            'lengow_state'     => $this->order_state_lengow,
            'order_new'        => ($type_result == 'new' ? true : false),
            'order_error'      => ($type_result == 'error' ? true : false)
        );
        return $result;
    }

    /**
     * Checks if order data are present
     *
     * @return boolean
     */
    protected function checkOrderData()
    {
        $error_messages = array();
        if (count($this->package_data->cart) == 0) {
            $error_messages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/no_product'
            );
        }
        if (is_null($this->order_data->billing_address)) {
            $error_messages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/no_billing_address'
            );
        } elseif (is_null($this->order_data->billing_address->common_country_iso_a2)) {
            $error_messages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/no_country_for_billing_address'
            );
        }
        if (is_null($this->package_data->delivery->common_country_iso_a2)) {
            $error_messages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/no_country_for_delivery_address'
            );
        }
        if (count($error_messages) > 0) {
            foreach ($error_messages as $error_message) {
                $decoded_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    $error_message
                );
                $this->log('order_import_failed', array('decoded_message' => $decoded_message));
            };
            return false;
        }
        return true;
    }

    /**
     * Set log
     *
     * @param string $key    Key for translation
     * @param array  $params Params
     */
    protected function log($key, $params = array())
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'Import',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage($key, $params),
            $this->log_output,
            $this->marketplace_sku
        );
    }
}
