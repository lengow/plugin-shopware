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
    protected $preprodMode = false;

    /**
     * @var boolean display log messages
     */
    protected $logOutput = false;

    /**
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace
     */
    protected $marketplace;

    /**
     * @var string id lengow of current order
     */
    protected $marketplaceSku;

    /**
     * @var integer id of delivery address for current order
     */
    protected $deliveryAddressId;

    /**
     * @var mixed
     */
    protected $orderData;

    /**
     * @var mixed
     */
    protected $packageData;

    /**
     * @var boolean
     */
    protected $firstPackage;

    /**
     * @var string
     */
    protected $orderStateMarketplace;

    /**
     * @var string
     */
    protected $orderStateLengow;

    /**
     * @var integer id of the record Lengow order table
     */
    protected $lengowOrderId;

    /**
     * @var array
     */
    protected $articles;

    /**
     * @var boolean True if order is send by the marketplace
     */
    protected $shippedByMp = false;

    /**
     * Construct the import manager
     *
     * @param $params array Optional options
     * Shopware\Models\Shop\Shop shop                Id shop for current order
     * boolean                   preprod_mode        preprod mode
     * boolean                   log_output          display log messages
     * string                    marketplace_sku     order marketplace sku
     * integer                   delivery_address_id order delivery address id
     * mixed                     order_data          order data
     * mixed                     package_data        package data
     * boolean                   first_package       it is the first package
     */
    public function __construct($params = array())
    {
        $this->shop              = $params['shop'];
        $this->preprodMode       = $params['preprod_mode'];
        $this->logOutput         = $params['log_output'];
        $this->marketplaceSku    = $params['marketplace_sku'];
        $this->deliveryAddressId = $params['delivery_address_id'];
        $this->orderData         = $params['order_data'];
        $this->packageData       = $params['package_data'];
        $this->firstPackage      = $params['first_package'];
        // get marketplace and Lengow order state
        $this->marketplace = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getMarketplaceSingleton(
            (string)$this->orderData->marketplace,
            $this->shop
        );
        $this->orderStateMarketplace = (string)$this->orderData->marketplace_status;
        $this->orderStateLengow = $this->marketplace->getStateLengow($this->orderStateMarketplace);
    }

    /**
     * Create or update order
     *
     * @return mixed
     */
    public function importOrder()
    {
        // get a record in the lengow order table
        $this->lengowOrderId = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getIdFromLengowOrders(
            $this->marketplaceSku,
            (string)$this->marketplace->name,
            $this->deliveryAddressId
        );
        // If order does not already exist
        if ($this->lengowOrderId !== false) {
            $this->log('log/import/order_already_decremented');
            return false;
        }
        // if order is cancelled or new -> skip
        if (!Shopware_Plugins_Backend_Lengow_Components_LengowImport::checkState(
            $this->orderStateMarketplace,
            $this->marketplace
        )) {
            $this->log(
                'log/import/current_order_state_unavailable',
                array(
                    'order_state_marketplace' => $this->orderStateMarketplace,
                    'marketplace_name'        => $this->marketplace->name
                )
            );
            return false;
        }
        // checks if the required order data is present
        if (!$this->checkOrderData()) {
            return $this->returnResult('error', $this->lengowOrderId);
        }
        // load tracking data
        $this->loadTrackingData();
        try {
            // check if the order is shipped by marketplace
            if ($this->shippedByMp) {
                $importMpOrdersOption = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                    'lengowImportShipMpEnabled'
                );
                // If decrease stocks from mp option is disabled
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
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Shopware error] "'.$e->getMessage().'" '.$e->getFile().' | '.$e->getLine();
        }
        if (isset($errorMessage)) {
            $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                $errorMessage
            );
            $this->log('log/import/order_import_failed', array('decoded_message' => $decodedMessage));
            return $this->returnResult('error', $this->lengowOrderId);
        }
        // created a record in the lengow order table
        if (!empty($products)) {
            if (!$this->createLengowOrder()) {
                $this->log('log/import/lengow_order_not_saved');
                return $this->returnResult('error', $this->lengowOrderId);
            } else {
                $this->log('log/import/lengow_order_saved');
                return $this->returnResult('new', $this->lengowOrderId);
            }
        } else {
            // No orders
            $this->log('log/import/no_orders_to_process');
            return false;
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
        foreach ($this->packageData->cart as $article) {
            $articleData = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::extractProductDataFromAPI(
                $article
            );
            if (!is_null($articleData['marketplace_status'])) {
                $stateProduct = $this->marketplace->getStateLengow((string)$articleData['marketplace_status']);
                if ($stateProduct == 'canceled' || $stateProduct == 'refused') {
                    $articleId = (!is_null($articleData['merchant_product_id']->id)
                        ? (string)$articleData['merchant_product_id']->id
                        : (string)$articleData['marketplace_product_id']
                    );
                    $this->log(
                        'log/import/product_state_canceled',
                        array(
                            'product_id'    => $articleId,
                            'state_product' => $stateProduct
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
            foreach ($articleIds as $attributeName => $attributeValue) {
                // remove _FBA from product id
                $attributeValue = preg_replace('/_FBA$/', '', $attributeValue);
                if (empty($attributeValue)) {
                    continue;
                }
                $isParentProduct = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::checkIsParentProduct(
                    $attributeValue
                );
                // If found, id does not concerns a variation but a parent
                if ($isParentProduct) {
                    $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/product_is_a_parent',
                        array('product_id' => $attributeValue)
                    );
                    throw new Shopware_Plugins_Backend_Lengow_Components_LengowException($errorMessage);
                }
                $shopwareDetailId = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::findArticle(
                    $attributeValue
                );
                if ($shopwareDetailId == null) {
                    $this->log(
                        'log/import/product_advanced_search',
                        array(
                            'attribute_name'  => $attributeName,
                            'attribute_value' => $attributeValue
                        )
                    );
                    foreach ($advancedSearchFields as $field) {
                        $shopwareDetailId = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::advancedSearch(
                            $field,
                            $attributeValue,
                            $this->logOutput
                        );
                        if ($shopwareDetailId != null) {
                            break;
                        }
                    }
                }
                if ($shopwareDetailId != null) {
                    $articleDetailId = $shopwareDetailId['id'];
                    $articleDetailNumber = $shopwareDetailId['number'];
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
                            'article_number'  => $articleDetailNumber,
                            'attribute_name'  => $attributeName,
                            'attribute_value' => $attributeValue
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
                $errorMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/exception/product_not_be_found',
                    array('product_id' => $articleId)
                );
                $this->log($errorMessage);
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException($errorMessage);
            }
        }
        return $products;
    }

    /**
     * Get tracking data and update Lengow order record
     */
    protected function loadTrackingData()
    {
        $tracking = $this->packageData->delivery->trackings;
        if (count($tracking) > 0) {
            if (!is_null($tracking[0]->is_delivered_by_marketplace) && $tracking[0]->is_delivered_by_marketplace) {
                $this->shippedByMp = true;
            }
        }
    }

    /**
     * Decrease stocks for a giving product
     * @param $products array Product which needs stocks to be decreased
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
                    'article_number' => $shopwareArticle->getNumber(),
                    'initial_stock'  => $quantity,
                    'new_stock'      => $newStock
                )
            );
        }
    }

    /**
     * Create a order in lengow orders table
     *
     * @return boolean True if Order has been successfully created in database
     */
    protected function createLengowOrder()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        if (!is_null($this->orderData->marketplace_order_date)) {
            $orderDate = (string)$this->orderData->marketplace_order_date;
        } else {
            $orderDate = (string)$this->orderData->imported_at;
        }
        try {
            $dateTime = new DateTime(date('Y-m-d H:i:s', strtotime($orderDate)));
            // Create Lengow order entity
            $lengowOrder = new Shopware\CustomModels\Lengow\Order();
            $lengowOrder->setShopId($this->shop->getId())
                ->setDeliveryAddressId($this->deliveryAddressId)
                ->setMarketplaceSku($this->marketplaceSku)
                ->setMarketplaceName(strtolower($this->orderData->marketplace))
                ->setOrderDate($dateTime)
                ->setCreatedAt(new DateTime())
                ->setExtra(json_encode($this->orderData));
            $this->lengowOrderId = $lengowOrder->getId();
            $em->persist($lengowOrder);
            $em->flush($lengowOrder);
            return true;
        } catch (Exception $e) {
            $errorMessage = '[Shopware error] "'.$e->getMessage().'" '.$e->getFile().' | '.$e->getLine();
            $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                $errorMessage
            );
            $this->log('log/exception/order_insert_failed', array('decoded_message' => $decodedMessage));
            return false;
        }
    }

    /**
     * Return an array of result for each order
     *
     * @param string  $typeResult    Type of result (new, update, error)
     * @param integer $lengowOrderId ID of the lengow order record
     * @param integer $orderId       Shopware order id
     *
     * @return array
     */
    protected function returnResult($typeResult, $lengowOrderId, $orderId = null)
    {
        $result = array(
            'order_id'         => $orderId,
            'id_order_lengow'  => $lengowOrderId,
            'marketplace_sku'  => $this->marketplaceSku,
            'marketplace_name' => (string)$this->marketplace->name,
            'lengow_state'     => $this->orderStateLengow,
            'order_new'        => ($typeResult == 'new' ? true : false),
            'order_error'      => ($typeResult == 'error' ? true : false)
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
        $errorMessages = array();
        if (count($this->packageData->cart) == 0) {
            $errorMessages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/no_product'
            );
        }
        if (is_null($this->orderData->billing_address)) {
            $errorMessages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/no_billing_address'
            );
        } elseif (is_null($this->orderData->billing_address->common_country_iso_a2)) {
            $errorMessages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/no_country_for_billing_address'
            );
        }
        if (is_null($this->packageData->delivery->common_country_iso_a2)) {
            $errorMessages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/no_country_for_delivery_address'
            );
        }
        if (count($errorMessages) > 0) {
            foreach ($errorMessages as $errorMessage) {
                $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    $errorMessage
                );
                $this->log('order_import_failed', array('decoded_message' => $decodedMessage));
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
            $this->logOutput,
            $this->marketplaceSku
        );
    }
}
