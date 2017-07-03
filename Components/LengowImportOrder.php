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
     * @var string marketplace label
     */
    protected $marketplaceLabel;

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
     * @var float order processing fee
     */
    protected $processingFee;

    /**
     * @var float order shipping cost
     */
    protected $shippingCost;

    /**
     * @var float order total amount
     */
    protected $orderAmount;

    /**
     * @var integer number of order items
     */
    protected $orderItems;

    /**
     * @var array order articles
     */
    protected $articles;

    /**
     * @var string carrier name
     */
    protected $carrierName = null;

    /**
     * @var string carrier method
     */
    protected $carrierMethod = null;

    /**
     * @var string carrier tracking number
     */
    protected $trackingNumber = null;

    /**
     * @var string carrier relay id
     */
    protected $relayId = null;

    /**
     * @var boolean if order is send by the marketplace
     */
    protected $shippedByMp = false;

    /**
     * @var \Shopware\Components\Model\ModelManager Shopware entity manager
     */
    protected $entityManager;

    /**
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowAddress Lengow address instance
     */
    protected $lengowAddress;

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
        $this->marketplaceLabel = $this->marketplace->labelName;
        $this->orderStateMarketplace = (string)$this->orderData->marketplace_status;
        $this->orderStateLengow = $this->marketplace->getStateLengow($this->orderStateMarketplace);
        $this->entityManager = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
    }

    /**
     * Create or update order
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException no product to cart / customer not saved
     *         order not saved
     *
     * @return array|false
     */
    public function importOrder()
    {
        // get a Shopware order id in the lengow order table
        $orderId = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getOrderIdFromLengowOrder(
            $this->marketplaceSku,
            (string)$this->marketplace->name,
            $this->deliveryAddressId
        );
        // if order is already exist
        if ($orderId) {

            // TODO Update Shopware order

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

        // TODO checks if an external id already exists

        // if order is canceled or new -> skip
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
        // get a record in the lengow order table
        $lengowOrder = $this->entityManager->getRepository('\Shopware\CustomModels\Lengow\Order')
            ->findOneBy(
                array(
                    'marketplaceSku' => $this->marketplaceSku,
                    'deliveryAddressId' => $this->deliveryAddressId
                )
            );
        if (is_null($lengowOrder)) {
            // created a record in the lengow order table
            $lengowOrder = $this->createLengowOrder();
            if (!$lengowOrder) {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/import/lengow_order_not_saved'
                    ),
                    $this->logOutput,
                    $this->marketplaceSku
                );
                return false;
            } else {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/import/lengow_order_saved'
                    ),
                    $this->logOutput,
                    $this->marketplaceSku
                );
            }
        }
        // checks if the required order data is present
        if (!$this->checkOrderData()) {
            return $this->returnResult('error', $lengowOrder->getId());
        }
        // get order amount and load processing fees and shipping cost
        $this->orderAmount = $this->getOrderAmount();
        // load tracking data
        $this->loadTrackingData();
        // get customer name
        $customerName = $this->getCustomerName();
        $customerEmail = (!is_null($this->orderData->billing_address->email)
            ? (string)$this->orderData->billing_address->email
            : (string)$this->packageData->delivery->email
        );
        // update Lengow order with new data
        $lengowOrder->setTotalPaid($this->orderAmount)
            ->setOrderItem($this->orderItems)
            ->setCustomerName($customerName)
            ->setCustomerEmail($customerEmail)
            ->setCarrier($this->carrierName)
            ->setCarrierMethod($this->carrierMethod)
            ->setCarrierTracking($this->trackingNumber)
            ->setCarrierIdRelay($this->relayId)
            ->setSentByMarketplace($this->shippedByMp)
            ->setDeliveryCountryIso($this->packageData->delivery->common_country_iso_a2)
            ->setOrderLengowState($this->orderStateLengow);
        $this->entityManager->flush($lengowOrder);
        // try to import order
        try {
            // check if the order is shipped by marketplace
            if ($this->shippedByMp) {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Import',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/import/order_shipped_by_marketplace',
                        array('marketplace_name' => $this->marketplace->name)
                    ),
                    $this->logOutput,
                    $this->marketplaceSku
                );
                $importShipMpEnabled = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                    'lengowImportShipMpEnabled'
                );
                if (!$importShipMpEnabled) {
                    $lengowOrder->setOrderProcessState(2)
                        ->setExtra(json_encode($this->orderData));
                    $this->entityManager->flush($lengowOrder);
                    return false;
                }
            }
            // get all Shopware articles
            $articles = $this->getArticles();
            if (count($articles) === 0) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/no_product_to_cart'
                    )
                );
            }
            // get lengow address to create all specific Shopware addresses for customer and order
            $this->lengowAddress = new Shopware_Plugins_Backend_Lengow_Components_LengowAddress(
                array(
                    'billing_datas' => $this->orderData->billing_address,
                    'shipping_datas' => $this->packageData->delivery,
                    'relay_id' => $this->relayId,
                )
            );
            // get or create Shopware customer
            $customerEmail = $this->getCustomerEmail();
            $customer = $this->entityManager
                ->getRepository('\Shopware\Models\Customer\Customer')
                ->findOneBy(
                    array(
                        'email' => $customerEmail,
                        'shop' => $this->shop
                    )
                );
            if (is_null($customer)) {
                $customer = $this->createCustomer($customerEmail);
            }
            if (!$customer) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/shopware_customer_not_saved'
                    )
                );
            }
            // create a Shopware order
            $order = $this->createOrder($customer, $articles);
            if (!$order) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'lengow_log/exception/shopware_order_not_saved'
                    )
                );
            }
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
            $lengowOrder->setOrderLengowState($this->orderStateLengow)
                ->setReimported(false)
                ->setExtra(json_encode($this->orderData));
            $this->entityManager->flush($lengowOrder);
            return $this->returnResult('error', $lengowOrder->getId());
        }
        return $this->returnResult('new', $lengowOrder->getId());
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
        if (!isset($this->orderData->currency->iso_a3)) {
            $errorMessages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/no_currency'
            );
        } else {
            $currency = $this->entityManager->getRepository('Shopware\Models\Shop\Currency')
                ->findOneBy(array('currency' => $this->orderData->currency->iso_a3));
            if (is_null($currency)) {
                $errorMessages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'lengow_log/error/currency_not_available',
                    array('currency_iso' => $this->orderData->currency->iso_a3)
                );
            }
        }
        if ($this->orderData->total_order == -1) {
            $errorMessages[] = Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'lengow_log/error/no_change_rate'
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
     * Get order amount
     *
     * @return float
     */
    protected function getOrderAmount()
    {
        $this->processingFee = (float)$this->orderData->processing_fee;
        $this->shippingCost = (float)$this->orderData->shipping;
        // rewrite processing fees and shipping cost
        if (!$this->firstPackage) {
            $this->processingFee = 0;
            $this->shippingCost = 0;
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/rewrite_processing_fee'
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/rewrite_shipping_cost'
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        // get total amount and the number of items
        $nbItems = 0;
        $totalAmount = 0;
        foreach ($this->packageData->cart as $product) {
            // check whether the product is canceled for amount
            if (!is_null($product->marketplace_status)) {
                $stateProduct = $this->marketplace->getStateLengow((string)$product->marketplace_status);
                if ($stateProduct == 'canceled' || $stateProduct == 'refused') {
                    continue;
                }
            }
            $nbItems += (int)$product->quantity;
            $totalAmount += (float)$product->amount;
        }
        $this->orderItems = $nbItems;
        $orderAmount = $totalAmount + $this->processingFee + $this->shippingCost;
        return $orderAmount;
    }

    /**
     * Get tracking data and update Lengow order record
     */
    protected function loadTrackingData()
    {
        $trackings = $this->packageData->delivery->trackings;
        if (count($trackings) > 0) {
            $this->carrierName = !is_null($trackings[0]->carrier) ? (string)$trackings[0]->carrier : null;
            $this->carrierMethod = !is_null($trackings[0]->method) ? (string)$trackings[0]->method : null;
            $this->trackingNumber = !is_null($trackings[0]->number) ? (string)$trackings[0]->number : null;
            $this->relayId = !is_null($trackings[0]->relay->id) ? (string)$trackings[0]->relay->id : null;
            if (!is_null($trackings[0]->is_delivered_by_marketplace) && $trackings[0]->is_delivered_by_marketplace) {
                $this->shippedByMp = true;
            }
        }
    }

    /**
     * Get customer name
     *
     * @return string
     */
    protected function getCustomerName()
    {
        $firstName = ucfirst(strtolower((string)$this->orderData->billing_address->first_name));
        $lastName = ucfirst(strtolower((string)$this->orderData->billing_address->last_name));
        if (empty($firstName) && empty($lastName)) {
            return (string)$this->orderData->billing_address->full_name;
        } else {
            return $firstName . ' ' . $lastName;
        }
    }

    /**
     * Get fictitious email for customer creation
     *
     * @return string
     */
    protected function getCustomerEmail()
    {
        $domain = $this->shop->getHost() ? $this->shop->getHost() : 'shopware.shop';
        $email = $this->marketplaceSku . '-' . $this->marketplace->name . '@' . $domain;
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'Import',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/import/generate_unique_email',
                array('email' => $email)
            ),
            $this->logOutput,
            $this->marketplaceSku
        );
        return $email;
    }

    /**
     * Get articles from the API
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException article is a parent / article no be found
     *
     * @return array
     */
    protected function getArticles()
    {
        $articles = array();
        $advancedSearchFields = array('number', 'ean');
        foreach ($this->packageData->cart as $article) {
            $articleData = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::extractProductDataFromAPI(
                $article
            );
            if (!is_null($articleData['marketplace_status'])) {
                $stateProduct = $this->marketplace->getStateLengow((string)$articleData['marketplace_status']);
                if ($stateProduct == 'canceled' || $stateProduct == 'refused') {
                    $articleId = !is_null($articleData['merchant_product_id']->id)
                        ? (string)$articleData['merchant_product_id']->id
                        : (string)$articleData['marketplace_product_id'];
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
                    throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                        Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                            'lengow_log/exception/product_is_a_parent',
                            array('product_id' => $attributeValue)
                        )
                    );
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
                    if (array_key_exists($articleDetailId, $articles)) {
                        $articles[$articleDetailId]['quantity'] += (int)$articleData['quantity'];
                        $articles[$articleDetailId]['amount'] += (float)$articleData['amount'];
                    } else {
                        $articles[$articleDetailId] = $articleData;
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
                $articleId = !is_null($articleData['merchant_product_id']->id)
                    ? (string)$articleData['merchant_product_id']->id
                    : (string)$articleData['marketplace_product_id'];
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/exception/product_not_be_found',
                        array('product_id' => $articleId)
                    )
                );
            }
        }
        return $articles;
    }


    /**
     * Create a order in lengow orders table
     *
     * @return Shopware\CustomModels\Lengow\Order|false
     */
    protected function createLengowOrder()
    {
        $orderDate = !is_null($this->orderData->marketplace_order_date)
            ? (string)$this->orderData->marketplace_order_date
            : (string)$this->orderData->imported_at;
        $message = is_array($this->orderData->comments)
            ? join(',', $this->orderData->comments)
            : (string)$this->orderData->comments;
        try {
            // Create Lengow order entity
            $lengowOrder = new Shopware\CustomModels\Lengow\Order();
            $lengowOrder->setShopId($this->shop->getId())
                ->setDeliveryAddressId($this->deliveryAddressId)
                ->setMarketplaceSku($this->marketplaceSku)
                ->setMarketplaceName(strtolower($this->orderData->marketplace))
                ->setMarketplaceLabel($this->marketplaceLabel)
                ->setOrderLengowState($this->orderStateLengow)
                ->setMessage($message)
                ->setOrderDate(new DateTime(date('Y-m-d H:i:s', strtotime($orderDate))))
                ->setCreatedAt(new DateTime())
                ->setExtra(json_encode($this->orderData));
            $this->entityManager->persist($lengowOrder);
            $this->entityManager->flush($lengowOrder);
            return $lengowOrder;
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

    /**
     * Create customer based on API data
     *
     * @param string $customerEmail fictitious customer email
     *
     * @return Shopware\Models\Customer\Customer|false
     */
    protected function createCustomer($customerEmail)
    {
        $newSchema = Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.2.0');
        try {
            // get Lengow payment method
            $lengowPayment = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowPayment();
            if (is_null($lengowPayment)) {
                return false;
            }
            // get new customer number
            $number = Shopware()->Models()
                ->getRepository('Shopware\Models\Order\Number')
                ->findOneBy(array('name' => 'user'));
            $customerNumber = $number->getNumber() + 1;
            // create a Shopware customer
            $customer = new Shopware\Models\Customer\Customer();
            $customerAttribute = new Shopware\Models\Attribute\Customer();
            // get new address object for Shopware version > 5.2.0
            if ($newSchema) {
                $address = $this->lengowAddress->getCustomerAddress();
                if ($address) {
                    $address->setCustomer($customer);
                    $customer->setNumber($customerNumber);
                    $customer->setSalutation($address->getSalutation());
                    $customer->setFirstname($address->getFirstname());
                    $customer->setLastname($address->getLastname());
                    $customer->setDefaultBillingAddress($address);
                    $customer->setDefaultShippingAddress($address);
                    $this->entityManager->persist($address);
                } else {
                    return false;
                }
            }
            // get old billing and shipping addresses objects for all versions of Shopware
            $billingAddress = $this->lengowAddress->getCustomerAddress(false);
            $shippingAddress = $this->lengowAddress->getCustomerAddress(false, 'shipping');
            if ($billingAddress && $shippingAddress) {
                $billingAddress->setCustomer($customer);
                if (!$newSchema) {
                    $billingAddress->setNumber($customerNumber);
                }
                $shippingAddress->setCustomer($customer);
                $customer->setBilling($billingAddress);
                $customer->setShipping($shippingAddress);
            } else {
                return false;
            }
            // set generic data for all versions of Shopware
            $customer->setEmail($customerEmail);
            $customer->setShop($this->shop);
            $customer->setGroup($this->shop->getCustomerGroup());
            $customer->setPaymentId($lengowPayment->getId());
            $customer->setAttribute($customerAttribute);
            // saves the customer data
            $this->entityManager->persist($customer);
            // update value for global customer number
            if (is_integer($customerNumber) && $customerNumber > $number->getNumber()) {
                $number->setNumber($customerNumber);
            }
            $this->entityManager->flush();
            return $customer;
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

    /**
     * Create order based on API data
     *
     * @param Shopware\Models\Customer\Customer $customer Shopware customer instance
     * @param array $articles Shopware articles
     *
     * @return Shopware\Models\Order\Order|false
     */
    protected function createOrder($customer, $articles)
    {
        try {
            // get Lengow payment method
            $payment = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowPayment();
            if (is_null($payment)) {
                return false;
            }
            // get default dispatch for import
            $dispatchId = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowImportDefaultDispatcher',
                $this->shop
            );
            $dispatch = $this->entityManager->getReference('Shopware\Models\Dispatch\Dispatch', $dispatchId);
            $dispatchTax = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getDispatchTax($dispatch);
            $taxPercent = (float)$dispatchTax->getTax();
            // get currency for order amount
            $currency = $this->entityManager->getRepository('Shopware\Models\Shop\Currency')
                ->findOneBy(array('currency' => $this->orderData->currency->iso_a3));
            // get current order status
            $orderStatus = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopwareOrderStatus(
                $this->orderStateMarketplace,
                $this->marketplace,
                $this->shippedByMp
            );
            // get order date
            $orderDate = !is_null($this->orderData->marketplace_order_date)
                ? date('Y-m-d H:i:s', strtotime((string)$this->orderData->marketplace_order_date))
                : date('Y-m-d H:i:s', strtotime((string)$this->orderData->imported_at));
            // get shipping cost
            $shippingCost = $this->shippingCost + $this->processingFee;
            // get new order number
            $number = Shopware()->Models()
                ->getRepository('Shopware\Models\Order\Number')
                ->findOneBy(array('name' => 'invoice'));
            $orderNumber = $number->getNumber() + 1;
            // create a temporary order
            $orderParams = array(
                'ordernumber' => $orderNumber,
                'userID' => $customer->getId(),
                'invoice_amount' => '',
                'invoice_amount_net' => '',
                'invoice_shipping' => $shippingCost,
                'invoice_shipping_net' => $shippingCost * ((100 - $taxPercent) / 100),
                'ordertime' => $orderDate,
                'status' => $orderStatus->getId(),
                'cleared' => 12,
                'paymentID' => $payment->getId(),
                'transactionID' => '',
                'customercomment' => '',
                'net' => '',
                'taxfree' => '',
                'partnerID' => '',
                'temporaryID' => '',
                'referer' => '',
                'cleareddate' => $orderDate,
                'trackingcode' => (string)$this->trackingNumber,
                'language' => $this->shop->getId(),
                'dispatchID' => $dispatch->getId(),
                'currency' => $currency->getCurrency(),
                'currencyFactor' => $currency->getFactor(),
                'subshopID' => $this->shop->getId(),
                'remote_addr' => (string)$_SERVER['REMOTE_ADDR']
            );
            Shopware()->Db()->insert('s_order', $orderParams);
            // get temporary order
            $order = Shopware()->Models()
                ->getRepository('Shopware\Models\Order\Order')
                ->findOneBy(array('number' => $orderNumber));
            // get and set order attributes
            $orderAttribute = new Shopware\Models\Attribute\Order();
            $order->setAttribute($orderAttribute);
            // get and set billing and shipping addresses
            $billingAddress = $this->lengowAddress->getOrderAddress();
            $billingAddress->setCustomer($customer);
            $billingAddress->setOrder($order);
            $order->setBilling($billingAddress);
            $shippingAddress = $this->lengowAddress->getOrderAddress('shipping');
            $shippingAddress->setCustomer($customer);
            $shippingAddress->setOrder($order);
            $order->setShipping($shippingAddress);
            // saves the order data
            $this->entityManager->persist($order);
            // update value for global order number
            if (is_integer($orderNumber) && $orderNumber > $number->getNumber()) {
                $number->setNumber($orderNumber);
            }
            $this->entityManager->flush();
            // create order detail foreach article
            foreach ($articles as $articleDetailId => $articleDetailData) {
                $articleDetail = $this->entityManager->getReference('Shopware\Models\Article\Detail', $articleDetailId);
                $this->createOrderDetails($order, $articleDetail, $articleDetailData);
            }
            // create payment instance
            $this->createPaymentInstance($order, $billingAddress, $customer, $payment);
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
        return $order;
    }

    /**
     * Create order details based on API data
     *
     * @param Shopware\Models\Order\Order $order Shopware order instance
     * @param Shopware\Models\Article\Detail $articleDetail Shopware article detail instance
     * @param array $articleDetailData article detail data based on API data
     *
     * @return boolean
     */
    protected function createOrderDetails($order, $articleDetail, $articleDetailData)
    {
        try {
            // create name for a variation
            $detailName = '';
            $attributes = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::getArticleAttributes(
                $articleDetail->getId()
            );
            foreach ($attributes as $attribute) {
                $detailName .= ' ' . $attribute;
            }
            // create order detail
            $orderDetail = new Shopware\Models\Order\Detail();
            $orderDetail->setOrder($order);
            $orderDetail->setNumber($order->getNumber());
            $orderDetail->setArticleId($articleDetail->getArticle()->getId());
            $orderDetail->setArticleNumber($articleDetail->getNumber());
            $orderDetail->setPrice($articleDetailData['price_unit']);
            $orderDetail->setQuantity($articleDetailData['quantity']);
            $orderDetail->setArticleName($articleDetail->getArticle()->getName() . $detailName);
            $orderDetail->setTaxRate($articleDetail->getArticle()->getTax()->getTax());
            $orderDetail->setEan($articleDetail->getEan());
            $orderDetail->setUnit($articleDetail->getUnit() ? $articleDetail->getUnit()->getName() : '');
            $orderDetail->setPackUnit($articleDetail->getPackUnit());
            $orderDetail->setTax($articleDetail->getArticle()->getTax());
            $orderDetail->setStatus($this->entityManager->getReference('Shopware\Models\Order\DetailStatus', 0));
            // decreases article detail stock
            $quantity = $articleDetail->getInStock();
            $newStock = $quantity - $articleDetailData['quantity'];
            $articleDetail->setInStock($newStock);
            $this->entityManager->persist($orderDetail);
            $this->entityManager->flush();
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/stock_decreased',
                    array(
                        'article_number' => $articleDetail->getNumber(),
                        'initial_stock' => $quantity,
                        'new_stock' => $newStock
                    )
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return true;
        } catch (Exception $e) {
            $errorMessage = '[Shopware error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($errorMessage);
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

    /**
     * Create payment instance based on API data
     *
     * @param Shopware\Models\Order\Order $order Shopware customer instance
     * @param Shopware\Models\Order\Billing $billingAddress Shopware billing address instance
     * @param Shopware\Models\Customer\Customer $customer Shopware customer instance
     * @param Shopware\Models\Payment\Payment $payment Shopware payment instance
     *
     * @return boolean
     */
    protected function createPaymentInstance($order, $billingAddress, $customer, $payment)
    {
        try {
            $paymentInstance = new Shopware\Models\Payment\PaymentInstance();
            $paymentInstance->setOrder($order);
            $paymentInstance->setCustomer($customer);
            $paymentInstance->setPaymentMean($payment);
            $paymentInstance->setFirstName($billingAddress->getFirstName());
            $paymentInstance->setLastName($billingAddress->getLastName());
            $paymentInstance->setAddress($billingAddress->getStreet());
            $paymentInstance->setZipCode($billingAddress->getZipCode());
            $paymentInstance->setCity($billingAddress->getCity());
            $paymentInstance->setAmount($this->orderAmount);
            $this->entityManager->persist($paymentInstance);
            $this->entityManager->flush();
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/create_payment_instance'
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return true;
        } catch (Exception $e) {
            $errorMessage = '[Shopware error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($errorMessage);
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
