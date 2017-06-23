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
 * Lengow Import Order Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowImportOrder
{
    /**
     * @var Shopware\Models\Shop\Shop Shopware shop instance
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
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace Lengow marketplace instance
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
     * @var mixed API order data
     */
    protected $orderData;

    /**
     * @var mixed API package data
     */
    protected $packageData;

    /**
     * @var boolean is first package
     */
    protected $firstPackage;

    /**
     * @var string marketplace order state
     */
    protected $orderStateMarketplace;

    /**
     * @var string Lengow order state
     */
    protected $orderStateLengow;

    /**
     * @var integer Lengow order id
     */
    protected $lengowOrderId;

    /**
     * @var array order articles
     */
    protected $articles;

    /**
     * @var boolean if order is send by the marketplace
     */
    protected $shippedByMp = false;

    /**
     * Construct the import manager
     *
     * @param $params array optional options
     * Shopware\Models\Shop\Shop shop                Shopware shop instance
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
        $this->shop = $params['shop'];
        $this->preprodMode = $params['preprod_mode'];
        $this->logOutput = $params['log_output'];
        $this->marketplaceSku = $params['marketplace_sku'];
        $this->deliveryAddressId = $params['delivery_address_id'];
        $this->orderData = $params['order_data'];
        $this->packageData = $params['package_data'];
        $this->firstPackage = $params['first_package'];
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
     * @return array|false
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
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/order_already_decremented'
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
        // if order is cancelled or new -> skip
        if (!Shopware_Plugins_Backend_Lengow_Components_LengowImport::checkState(
            $this->orderStateMarketplace,
            $this->marketplace
        )
        ) {
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/current_order_state_unavailable',
                    array(
                        'order_state_marketplace' => $this->orderStateMarketplace,
                        'marketplace_name' => $this->marketplace->name
                    )
                ),
                $this->logOutput,
                $this->marketplaceSku
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
                $importStockMpOption = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                    'lengowImportStockMpEnabled'
                );
                // If decrease stocks from mp option is disabled
                if (!$importStockMpOption) {
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        'Import',
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/import/order_shipped_by_marketplace',
                            array('marketplace_name' => $this->marketplace->name)
                        ),
                        $this->logOutput,
                        $this->marketplaceSku
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
            $errorMessage = '[Shopware error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                $errorMessage
            );
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/order_import_failed',
                    array('decoded_message' => $decodedMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return $this->returnResult('error', $this->lengowOrderId);
        }
        // created a record in the lengow order table
        if (!empty($products)) {
            if (!$this->createLengowOrder()) {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/import/lengow_order_not_saved'
                    ),
                    $this->logOutput,
                    $this->marketplaceSku
                );
                return $this->returnResult('error', $this->lengowOrderId);
            } else {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/import/lengow_order_saved'
                    ),
                    $this->logOutput,
                    $this->marketplaceSku
                );
                return $this->returnResult('new', $this->lengowOrderId);
            }
        } else {
            // No orders
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/no_orders_to_process'
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
    }

    /**
     * Return an array of result for each order
     *
     * @param string $typeResult Type of result (new, update, error)
     * @param integer $lengowOrderId Lengow order id
     * @param integer $orderId Shopware order id
     *
     * @return array
     */
    protected function returnResult($typeResult, $lengowOrderId, $orderId = null)
    {
        $result = array(
            'order_id' => $orderId,
            'id_order_lengow' => $lengowOrderId,
            'marketplace_sku' => $this->marketplaceSku,
            'marketplace_name' => (string)$this->marketplace->name,
            'lengow_state' => $this->orderStateLengow,
            'order_new' => ($typeResult == 'new' ? true : false),
            'order_error' => ($typeResult == 'error' ? true : false)
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
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/import/order_import_failed',
                        array('decoded_message' => $decodedMessage)
                    ),
                    $this->logOutput,
                    $this->marketplaceSku
                );
            };
            return false;
        }
        return true;
    }

    /**
     * Get products from the API
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException product is a parent / product no be found
     *
     * @return array
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
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        'Import',
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/import/product_state_canceled',
                            array(
                                'product_id' => $articleId,
                                'state_product' => $stateProduct
                            )
                        ),
                        $this->logOutput,
                        $this->marketplaceSku
                    );
                    continue;
                }
            }
            $articleIds = array(
                'idMerchant' => (string)$articleData['merchant_product_id']->id,
                'idMP' => (string)$articleData['marketplace_product_id']
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
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        'Import',
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/import/product_advanced_search',
                            array(
                                'attribute_name' => $attributeName,
                                'attribute_value' => $attributeValue
                            )
                        ),
                        $this->logOutput,
                        $this->marketplaceSku
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
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                        'Import',
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'log/import/product_be_found',
                            array(
                                'id_full' => $articleDetailId,
                                'article_number' => $articleDetailNumber,
                                'attribute_name' => $attributeName,
                                'attribute_value' => $attributeValue
                            )
                        ),
                        $this->logOutput,
                        $this->marketplaceSku
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
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    $errorMessage,
                    $this->logOutput,
                    $this->marketplaceSku
                );
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
     *
     * @param array $products product which needs stocks to be decreased
     */
    protected function decreaseStocks($products)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        foreach ($products as $key => $product) {
            // @var Shopware\Models\Article\Detail $shopwareArticle
            $shopwareArticle = $em->getReference('Shopware\Models\Article\Detail', $key);
            $quantity = $shopwareArticle->getInStock();
            $newStock = $quantity - $product['quantity'];
            $shopwareArticle->setInStock($newStock);
            $em->persist($shopwareArticle);
            $em->flush($shopwareArticle);
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/stock_decreased',
                    array(
                        'article_number' => $shopwareArticle->getNumber(),
                        'initial_stock' => $quantity,
                        'new_stock' => $newStock
                    )
                ),
                $this->logOutput,
                $this->marketplaceSku
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
            $errorMessage = '[Shopware error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                $errorMessage
            );
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $decodedMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
    }
}
