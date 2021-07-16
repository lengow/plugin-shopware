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

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail as ArticleDetailModel;
use Shopware\Models\Attribute\Customer as AttributeCustomerModel;
use Shopware\Models\Attribute\Order as AttributeOrderModel;
use Shopware\Models\Customer\Customer as CustomerModel;
use Shopware\Models\Dispatch\Dispatch as DispatchModel;
use Shopware\Models\Order\Billing as OrderBillingModel;
use Shopware\Models\Order\Order as OrderModel;
use Shopware\Models\Order\Detail as OrderDetailModel;
use Shopware\Models\Order\DetailStatus as OrderDetailStatusModel;
use Shopware\Models\Order\Number as OrderNumberModel;
use Shopware\Models\Payment\Payment as PaymentModel;
use Shopware\Models\Payment\PaymentInstance as PaymentPaymentInstanceModel;
use Shopware\Models\Shop\Currency as CurrencyModel;
use Shopware\Models\Shop\Shop as ShopModel;
use Shopware\CustomModels\Lengow\Order as LengowOrderModel;
use Shopware\CustomModels\Lengow\OrderLine as LengowOrderLineModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Components_LengowAddress as LengowAddress;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowException as LengowException;
use Shopware_Plugins_Backend_Lengow_Components_LengowImport as LengowImport;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace as LengowMarketplace;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrderError as LengowOrderError;
use Shopware_Plugins_Backend_Lengow_Components_LengowProduct as LengowProduct;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

/**
 * Lengow Import Order Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowImportOrder
{
    /**
     * @var string result for order imported
     */
    const RESULT_NEW = 'new';

    /**
     * @var string result for order updated
     */
    const RESULT_UPDATE = 'update';

    /**
     * @var string result for order in error
     */
    const RESULT_ERROR = 'error';

    /**
     * @var ShopModel Shopware shop instance
     */
    protected $shop;

    /**
     * @var boolean use debug mode
     */
    protected $debugMode = false;

    /**
     * @var boolean display log messages
     */
    protected $logOutput = false;

    /**
     * @var LengowMarketplace Lengow marketplace instance
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
     * @var boolean import one order var from lengow import
     */
    protected $importOneOrder;

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
     * @var array order types (is_express, is_prime...)
     */
    protected $orderTypes;

    /**
     * @var array order articles
     */
    protected $articles;

    /**
     * @var string|null carrier name
     */
    protected $carrierName;

    /**
     * @var string|null carrier method
     */
    protected $carrierMethod;

    /**
     * @var string|null carrier tracking number
     */
    protected $trackingNumber;

    /**
     * @var string|null carrier relay id
     */
    protected $relayId;

    /**
     * @var boolean if order is send by the marketplace
     */
    protected $shippedByMp = false;

    /**
     * @var boolean re-import order
     */
    protected $isReimported = false;

    /**
     * @var ModelManager Shopware entity manager
     */
    protected $entityManager;

    /**
     * @var LengowAddress Lengow address instance
     */
    protected $lengowAddress;

    /**
     * Construct the import manager
     *
     * @param $params array optional options
     * ShopModel shop                Shopware shop instance
     * boolean   debug_mode          debug mode
     * boolean   log_output          display log messages
     * string    marketplace_sku     order marketplace sku
     * integer   delivery_address_id order delivery address id
     * mixed     order_data          order data
     * mixed     package_data        package data
     * boolean   first_package       it is the first package
     * boolean   import_one_order    import one order
     *
     * @throws LengowException
     */
    public function __construct($params = array())
    {
        $this->shop = $params['shop'];
        $this->debugMode = $params['debug_mode'];
        $this->logOutput = $params['log_output'];
        $this->marketplaceSku = $params['marketplace_sku'];
        $this->deliveryAddressId = $params['delivery_address_id'];
        $this->orderData = $params['order_data'];
        $this->packageData = $params['package_data'];
        $this->firstPackage = $params['first_package'];
        $this->importOneOrder = $params['import_one_order'];
        // get marketplace and Lengow order state
        $this->marketplace = LengowMain::getMarketplaceSingleton((string) $this->orderData->marketplace);
        $this->marketplaceLabel = $this->marketplace->labelName;
        $this->orderStateMarketplace = (string) $this->orderData->marketplace_status;
        $this->orderStateLengow = $this->marketplace->getStateLengow($this->orderStateMarketplace);
        $this->entityManager = LengowBootstrap::getEntityManager();
    }

    /**
     * Create or update order
     *
     * @throws Exception|LengowException
     *
     * @return array|false
     */
    public function importOrder()
    {
        // if log import exist and not finished
        $importLog = LengowOrder::orderIsInError($this->marketplaceSku, $this->deliveryAddressId);
        if ($importLog && isset($importLog['message'], $importLog['createdAt'])) {
            /** @var DateTime $dateMessage */
            $dateMessage = $importLog['createdAt'];
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/error_already_created',
                    array(
                        'decoded_message' => LengowMain::decodeLogMessage(
                            $importLog['message'],
                            LengowTranslation::DEFAULT_ISO_CODE
                        ),
                        'date_message' => $dateMessage->format('Y-m-d H:i:s'),
                    )
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
        // get a Shopware order id in the lengow order table
        $order = LengowOrder::getOrderFromLengowOrder(
            $this->marketplaceSku,
            $this->marketplace->name,
            $this->deliveryAddressId
        );
        // if order is already exist
        if ($order) {
            $orderUpdated = $this->checkAndUpdateOrder($order);
            if ($orderUpdated && isset($orderUpdated['update'])) {
                return $this->returnResult(self::RESULT_UPDATE, $orderUpdated['order_lengow_id'], $order->getId());
            }
            if (!$this->isReimported) {
                return false;
            }
        }
        if (!$this->importOneOrder) {
            // skip import if the order is anonymize
            if ($this->orderData->anonymized) {
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage('log/import/anonymized_order'),
                    $this->logOutput,
                    $this->marketplaceSku
                );
                return false;
            }
            //skip import if the order is older than 3 months
            $dateTimeOrder = new DateTime($this->orderData->marketplace_order_date);
            $interval = $dateTimeOrder->diff(new DateTime());
            $monthsInterval = $interval->m + ($interval->y * 12);
            if ($monthsInterval >= LengowImport::MONTH_INTERVAL_TIME) {
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage('log/import/old_order'),
                    $this->logOutput,
                    $this->marketplaceSku
                );
                return false;
            }
        }
        // checks if an external id already exists
        $orderIdShopware = $this->checkExternalIds($this->orderData->merchant_order_id);
        if ($orderIdShopware && !$this->debugMode && !$this->isReimported) {
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/external_id_exist', array('order_id' => $orderIdShopware)),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
        // get a record in the lengow order table
        /** @var LengowOrderModel $lengowOrder */
        $lengowOrder = $this->entityManager->getRepository(LengowOrderModel::class)
            ->findOneBy(
                array(
                    'marketplaceSku' => $this->marketplaceSku,
                    'marketplaceName' => $this->marketplace->name,
                    'deliveryAddressId' => $this->deliveryAddressId,
                )
            );
        // if order is canceled or new -> skip
        if (!LengowImport::checkState($this->orderStateMarketplace, $this->marketplace)) {
            $orderProcessState = LengowOrder::getOrderProcessState($this->orderStateLengow);
            // check and complete an order not imported if it is canceled or refunded
            if ($lengowOrder !== null && $orderProcessState === LengowOrder::PROCESS_STATE_FINISH) {
                LengowOrderError::finishOrderErrors($lengowOrder->getId());
                $lengowOrder->setInError(false)
                    ->setOrderLengowState($this->orderStateLengow)
                    ->setOrderProcessState($orderProcessState);
                Shopware()->Models()->flush($lengowOrder);
            }
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/current_order_state_unavailable',
                    array(
                        'order_state_marketplace' => $this->orderStateMarketplace,
                        'marketplace_name' => $this->marketplace->name,
                    )
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
        // load order types data
        $this->loadOrderTypesData();
        // create a new record in lengow order table if not exist
        if ($lengowOrder === null) {
            // created a record in the lengow order table
            $lengowOrder = $this->createLengowOrder();
            if (!$lengowOrder) {
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage('log/import/lengow_order_not_saved'),
                    $this->logOutput,
                    $this->marketplaceSku
                );
                return false;
            }
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/lengow_order_saved'),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        // checks if the required order data is present
        if (!$this->checkOrderData($lengowOrder)) {
            return $this->returnResult(self::RESULT_ERROR, $lengowOrder->getId());
        }
        // get order amount and load processing fees and shipping cost
        $this->orderAmount = $this->getOrderAmount();
        // load tracking data
        $this->loadTrackingData();
        // get customer name
        $customerName = $this->getCustomerName();
        $customerEmail = (string) ($this->orderData->billing_address->email ?: $this->packageData->delivery->email);
        $customerVatNumber = $this->getVatNumberFromOrderData();
        // update Lengow order with new data
        $lengowOrder->setTotalPaid($this->orderAmount)
            ->setCurrency($this->orderData->currency->iso_a3)
            ->setOrderItem($this->orderItems)
            ->setCustomerName($customerName)
            ->setCustomerEmail($customerEmail)
            ->setCustomerVatNumber($customerVatNumber)
            ->setCarrier($this->carrierName)
            ->setCarrierMethod($this->carrierMethod)
            ->setCarrierTracking($this->trackingNumber)
            ->setCarrierIdRelay($this->relayId)
            ->setSentByMarketplace($this->shippedByMp)
            ->setDeliveryCountryIso($this->packageData->delivery->common_country_iso_a2)
            ->setOrderLengowState($this->orderStateLengow)
            ->setUpdatedAt(new DateTime())
            ->setExtra(json_encode($this->orderData));
        $this->entityManager->flush($lengowOrder);
        // try to import order
        try {
            // check if the order is shipped by marketplace
            if ($this->shippedByMp) {
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage(
                        'log/import/order_shipped_by_marketplace',
                        array('marketplace_name' => $this->marketplace->name)
                    ),
                    $this->logOutput,
                    $this->marketplaceSku
                );
                $importShipMpEnabled = LengowConfiguration::getConfig(
                    LengowConfiguration::SHIPPED_BY_MARKETPLACE_ENABLED
                );
                if (!$importShipMpEnabled) {
                    $lengowOrder->setOrderProcessState(2)
                        ->setInError(false)
                        ->setExtra(json_encode($this->orderData));
                    $this->entityManager->flush($lengowOrder);
                    return false;
                }
            }
            // get all Shopware articles
            $articles = $this->getArticles();
            if (empty($articles)) {
                throw new LengowException(LengowMain::setLogMessage('lengow_log/exception/no_product_to_cart'));
            }
            // get lengow address to create all specific Shopware addresses for customer and order
            $this->lengowAddress = new LengowAddress(
                array(
                    'billing_datas' => $this->orderData->billing_address,
                    'shipping_datas' => $this->packageData->delivery,
                    'relay_id' => $this->relayId,
                    'marketplace_sku' => $this->marketplaceSku,
                    'log_output' => $this->logOutput,
                    'vat_number' => $this->getVatNumberFromOrderData(),
                )
            );
            // get or create Shopware customer
            $customerEmail = $this->getCustomerEmail();
            /** @var CustomerModel $customer */
            $customer = $this->entityManager
                ->getRepository(CustomerModel::class)
                ->findOneBy(
                    array(
                        'email' => $customerEmail,
                        'shop' => $this->shop,
                    )
                );
            if ($customer === null) {
                $customer = $this->createCustomer($customerEmail);
            }
            if (!$customer) {
                throw new LengowException(
                    LengowMain::setLogMessage('lengow_log/exception/shopware_customer_not_saved')
                );
            }
            // create a Shopware order
            $order = $this->createOrder($customer, $articles, $lengowOrder);
            if (!$order) {
                throw new LengowException(LengowMain::setLogMessage('lengow_log/exception/shopware_order_not_saved'));
            }
            // save order line id in lengow order line table
            $this->createLengowOrderLines($order, $articles);
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/order_successfully_imported',
                    array('order_id' => $order->getNumber())
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            // add quantity back for re-import order and order shipped by marketplace
            $importStockMpEnabled = LengowConfiguration::getConfig(
                LengowConfiguration::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED
            );
            if ($this->isReimported || ($this->shippedByMp && !$importStockMpEnabled)) {
                if ($this->isReimported) {
                    $logMessage = LengowMain::setLogMessage('log/import/quantity_back_reimported_order');
                } else {
                    $logMessage = LengowMain::setLogMessage('log/import/quantity_back_shipped_by_marketplace');
                }
                LengowMain::log(LengowLog::CODE_IMPORT, $logMessage, $this->logOutput, $this->marketplaceSku);
                $this->addQuantityBack($articles);
            }
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Shopware error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ($lengowOrder->isInError()) {
                LengowOrderError::createOrderError($lengowOrder, $errorMessage);
            }
            $decodedMessage = LengowMain::decodeLogMessage($errorMessage, LengowTranslation::DEFAULT_ISO_CODE);
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/order_import_failed',
                    array('decoded_message' => $decodedMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            $lengowOrder->setOrderLengowState($this->orderStateLengow)
                ->setReimported(false)
                ->setUpdatedAt(new DateTime())
                ->setExtra(json_encode($this->orderData));
            $this->entityManager->flush($lengowOrder);
            return $this->returnResult(self::RESULT_ERROR, $lengowOrder->getId());
        }
        return $this->returnResult(self::RESULT_NEW, $lengowOrder->getId(), isset($order) ? $order->getId() : null);
    }

    /**
     * Return an array of result for each order
     *
     * @param string $typeResult Type of result (new, update, error)
     * @param integer $lengowOrderId Lengow order id
     * @param integer|null $orderId Shopware order id
     *
     * @return array
     */
    protected function returnResult($typeResult, $lengowOrderId, $orderId = null)
    {
        return array(
            'order_id' => $orderId,
            'lengow_order_id' => $lengowOrderId,
            'marketplace_sku' => $this->marketplaceSku,
            'marketplace_name' => $this->marketplace->name,
            'lengow_state' => $this->orderStateLengow,
            'order_new' => $typeResult === self::RESULT_NEW,
            'order_update' => $typeResult === self::RESULT_UPDATE,
            'order_error' => $typeResult === self::RESULT_ERROR,
        );
    }

    /**
     * Check the order and updates data if necessary
     *
     * @param OrderModel $order Shopware order instance
     *
     * @throws Exception
     *
     * @return array|false
     */
    protected function checkAndUpdateOrder($order)
    {
        LengowMain::log(
            LengowLog::CODE_IMPORT,
            LengowMain::setLogMessage('log/import/order_already_imported', array('order_id' => $order->getNumber())),
            $this->logOutput,
            $this->marketplaceSku
        );
        // get a record in the lengow order table
        /** @var LengowOrderModel $lengowOrder */
        $lengowOrder = $this->entityManager->getRepository(LengowOrderModel::class)
            ->findOneBy(array('order' => $order));
        $result = array('order_lengow_id' => $lengowOrder->getId());
        // Lengow -> Cancel and reimport order
        if ($lengowOrder->isReimported()) {
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/order_ready_to_reimport',
                    array('order_id' => $order->getNumber())
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            $this->isReimported = true;
            return false;
        }
        // try to update Shopware order, lengow order and finish actions if necessary
        $orderUpdated = LengowOrder::updateState(
            $order,
            $lengowOrder,
            $this->orderStateLengow,
            $this->packageData,
            $this->logOutput
        );
        if ($orderUpdated) {
            $result['update'] = true;
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/order_state_updated', array('state_name' => $orderUpdated)),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        unset($order, $lengowOrder);
        return $result;
    }

    /**
     * Checks if order data are present
     *
     * @param LengowOrderModel $lengowOrder Lengow Order instance
     *
     * @return boolean
     */
    protected function checkOrderData($lengowOrder)
    {
        $errorMessages = array();
        if (empty($this->packageData->cart)) {
            $errorMessages[] = LengowMain::setLogMessage('lengow_log/error/no_product');
        }
        if (!isset($this->orderData->currency->iso_a3)) {
            $errorMessages[] = LengowMain::setLogMessage('lengow_log/error/no_currency');
        } else {
            /** @var CurrencyModel $currency */
            $currency = $this->entityManager->getRepository(CurrencyModel::class)
                ->findOneBy(array('currency' => $this->orderData->currency->iso_a3));
            if ($currency === null) {
                $errorMessages[] = LengowMain::setLogMessage(
                    'lengow_log/error/currency_not_available',
                    array('currency_iso' => $this->orderData->currency->iso_a3)
                );
            }
        }
        if ($this->orderData->total_order == -1) {
            $errorMessages[] = LengowMain::setLogMessage('lengow_log/error/no_change_rate');
        }
        if ($this->orderData->billing_address === null) {
            $errorMessages[] = LengowMain::setLogMessage('lengow_log/error/no_billing_address');
        } elseif ($this->orderData->billing_address->common_country_iso_a2 === null) {
            $errorMessages[] = LengowMain::setLogMessage('lengow_log/error/no_country_for_billing_address');
        }
        if ($this->packageData->delivery->common_country_iso_a2 === null) {
            $errorMessages[] = LengowMain::setLogMessage('lengow_log/error/no_country_for_delivery_address');
        }
        if (!empty($errorMessages)) {
            foreach ($errorMessages as $errorMessage) {
                LengowOrderError::createOrderError($lengowOrder, $errorMessage);
                $decodedMessage = LengowMain::decodeLogMessage($errorMessage, LengowTranslation::DEFAULT_ISO_CODE);
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage(
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
     * Checks if an external id already exists
     *
     * @param array $externalIds external ids return by API
     *
     * @return integer|false
     */
    protected function checkExternalIds($externalIds)
    {
        $orderIdShopware = false;
        if ($externalIds !== null && !empty($externalIds)) {
            foreach ($externalIds as $externalId) {
                $lineId = LengowOrder::getIdFromLengowDeliveryAddress((int) $externalId, $this->deliveryAddressId);
                if ($lineId) {
                    $orderIdShopware = $externalId;
                    break;
                }
            }
        }
        return $orderIdShopware;
    }

    /**
     * Get order types data and update Lengow order record
     */
    protected function loadOrderTypesData()
    {
        $orderTypes = array();
        if (!empty($this->orderData->order_types)) {
            foreach ($this->orderData->order_types as $orderType) {
                $orderTypes[$orderType->type] = $orderType->label;
                if ($orderType->type === LengowOrder::TYPE_DELIVERED_BY_MARKETPLACE) {
                    $this->shippedByMp = true;
                }
            }
        }
        $this->orderTypes = $orderTypes;
    }

    /**
     * Get order amount
     *
     * @return float
     */
    protected function getOrderAmount()
    {
        $this->processingFee = (float) $this->orderData->processing_fee;
        $this->shippingCost = (float) $this->orderData->shipping;
        // rewrite processing fees and shipping cost
        if (!$this->firstPackage) {
            $this->processingFee = 0;
            $this->shippingCost = 0;
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/rewrite_processing_fee'),
                $this->logOutput,
                $this->marketplaceSku
            );
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/rewrite_shipping_cost'),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        // get total amount and the number of items
        $nbItems = 0;
        $totalAmount = 0;
        foreach ($this->packageData->cart as $product) {
            // check whether the product is canceled for amount
            if ($product->marketplace_status !== null) {
                $stateProduct = $this->marketplace->getStateLengow((string) $product->marketplace_status);
                if ($stateProduct === LengowOrder::STATE_CANCELED || $stateProduct === LengowOrder::STATE_REFUSED) {
                    continue;
                }
            }
            $nbItems += (int) $product->quantity;
            $totalAmount += (float) $product->amount;
        }
        $this->orderItems = $nbItems;
        return $totalAmount + $this->processingFee + $this->shippingCost;
    }

    /**
     * Get tracking data and update Lengow order record
     */
    protected function loadTrackingData()
    {
        $tracks = $this->packageData->delivery->trackings;
        if (!empty($tracks)) {
            $tracking = $tracks[0];
            $this->carrierName = $tracking->carrier;
            $this->carrierMethod = $tracking->method;
            $this->trackingNumber = $tracking->number;
            $this->relayId = $tracking->relay->id;
        }
    }

    /**
     * Get customer name
     *
     * @return string
     */
    protected function getCustomerName()
    {
        $firstName = ucfirst(strtolower((string) $this->orderData->billing_address->first_name));
        $lastName = ucfirst(strtolower((string) $this->orderData->billing_address->last_name));
        if (empty($firstName) && empty($lastName)) {
            return (string) $this->orderData->billing_address->full_name;
        }
        return $firstName . ' ' . $lastName;
    }

    /**
     * Get fictitious email for customer creation
     *
     * @return string
     */
    protected function getCustomerEmail()
    {
        $domain = $this->shop->getHost() ?: 'shopware.shop';
        $email = $this->marketplaceSku . '-' . $this->marketplace->name . '@' . $domain;
        LengowMain::log(
            LengowLog::CODE_IMPORT,
            LengowMain::setLogMessage('log/import/generate_unique_email', array('email' => $email)),
            $this->logOutput,
            $this->marketplaceSku
        );
        return $email;
    }

    /**
     * Get articles from the API
     *
     * @throws LengowException article is a parent / article no be found
     *
     * @return array
     */
    protected function getArticles()
    {
        $articles = array();
        $advancedSearchFields = array('number', 'ean');
        foreach ($this->packageData->cart as $article) {
            $articleData = LengowProduct::extractProductDataFromAPI($article);
            if ($articleData['marketplace_status'] !== null) {
                $stateProduct = $this->marketplace->getStateLengow((string) $articleData['marketplace_status']);
                if ($stateProduct === LengowOrder::STATE_CANCELED || $stateProduct === LengowOrder::STATE_REFUSED) {
                    $articleId = $articleData['merchant_product_id']->id !== null
                        ? (string) $articleData['merchant_product_id']->id
                        : (string) $articleData['marketplace_product_id'];
                    LengowMain::log(
                        LengowLog::CODE_IMPORT,
                        LengowMain::setLogMessage(
                            'log/import/product_state_canceled',
                            array(
                                'product_id' => $articleId,
                                'state_product' => $stateProduct,
                            )
                        ),
                        $this->logOutput,
                        $this->marketplaceSku
                    );
                    continue;
                }
            }
            $articleIds = array(
                'idMerchant' => (string) $articleData['merchant_product_id']->id,
                'idMP' => (string) $articleData['marketplace_product_id'],
            );
            $found = false;
            foreach ($articleIds as $attributeName => $attributeValue) {
                // remove _FBA from product id
                $attributeValue = preg_replace('/_FBA$/', '', $attributeValue);
                if (empty($attributeValue)) {
                    continue;
                }
                $isParentProduct = LengowProduct::checkIsParentProduct($attributeValue);
                // if found, id does not concerns a variation but a parent
                if ($isParentProduct) {
                    throw new LengowException(
                        LengowMain::setLogMessage(
                            'lengow_log/exception/product_is_a_parent',
                            array('product_id' => $attributeValue)
                        )
                    );
                }
                $shopwareDetailId = LengowProduct::findArticle($attributeValue);
                if ($shopwareDetailId === null) {
                    LengowMain::log(
                        LengowLog::CODE_IMPORT,
                        LengowMain::setLogMessage(
                            'log/import/product_advanced_search',
                            array(
                                'attribute_name' => $attributeName,
                                'attribute_value' => $attributeValue,
                            )
                        ),
                        $this->logOutput,
                        $this->marketplaceSku
                    );
                    foreach ($advancedSearchFields as $field) {
                        $shopwareDetailId = LengowProduct::advancedSearch($field, $attributeValue, $this->logOutput);
                        if ($shopwareDetailId !== null) {
                            break;
                        }
                    }
                }
                if ($shopwareDetailId !== null) {
                    $articleDetailId = $shopwareDetailId['id'];
                    $articleDetailNumber = $shopwareDetailId['number'];
                    if (array_key_exists($articleDetailId, $articles)) {
                        $articles[$articleDetailId]['quantity'] += (int) $articleData['quantity'];
                        $articles[$articleDetailId]['amount'] += (float) $articleData['amount'];
                        $articles[$articleDetailId]['order_line_ids'][] = $articleData['marketplace_order_line_id'];
                    } else {
                        $articles[$articleDetailId] = array(
                            'quantity' => (int) $articleData['quantity'],
                            'amount' => (float) $articleData['amount'],
                            'price_unit' => $articleData['price_unit'],
                            'order_line_ids' => array($articleData['marketplace_order_line_id']),
                        );
                    }
                    LengowMain::log(
                        LengowLog::CODE_IMPORT,
                        LengowMain::setLogMessage(
                            'log/import/product_be_found',
                            array(
                                'id_full' => $articleDetailId,
                                'article_number' => $articleDetailNumber,
                                'attribute_name' => $attributeName,
                                'attribute_value' => $attributeValue,
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
                $articleId = $articleData['merchant_product_id']->id !== null
                    ? (string) $articleData['merchant_product_id']->id
                    : (string) $articleData['marketplace_product_id'];
                throw new LengowException(
                    LengowMain::setLogMessage(
                        'lengow_log/exception/product_not_be_found',
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
     * @return LengowOrderModel|false
     */
    protected function createLengowOrder()
    {
        $orderDate = $this->orderData->marketplace_order_date !== null
            ? (string) $this->orderData->marketplace_order_date
            : (string) $this->orderData->imported_at;
        $message = is_array($this->orderData->comments)
            ? join(',', $this->orderData->comments)
            : (string) $this->orderData->comments;
        try {
            // create Lengow order entity
            $lengowOrder = new LengowOrderModel();
            $lengowOrder->setShopId($this->shop->getId())
                ->setDeliveryAddressId($this->deliveryAddressId)
                ->setMarketplaceSku($this->marketplaceSku)
                ->setMarketplaceName($this->marketplace->name)
                ->setMarketplaceLabel($this->marketplaceLabel)
                ->setOrderLengowState($this->orderStateLengow)
                ->setOrderTypes(json_encode($this->orderTypes))
                ->setMessage($message)
                ->setOrderDate(new DateTime(date('Y-m-d H:i:s', strtotime($orderDate))))
                ->setCreatedAt(new DateTime())
                ->setExtra(json_encode($this->orderData))
                ->setInError(true);
            $this->entityManager->persist($lengowOrder);
            $this->entityManager->flush($lengowOrder);
            return $lengowOrder;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
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
        $newSchema = LengowMain::compareVersion('5.2.0');
        try {
            // get Lengow payment method
            $lengowPayment = LengowMain::getLengowPayment();
            if ($lengowPayment === null) {
                return false;
            }
            // get new customer number
            /** @var OrderNumberModel $number */
            $number = Shopware()->Models()->getRepository(OrderNumberModel::class)->findOneBy(array('name' => 'user'));
            $customerNumber = $number->getNumber() + 1;
            // create a Shopware customer
            $customer = new CustomerModel();
            $customerAttribute = new AttributeCustomerModel();
            // get new address object for Shopware version > 5.2.0
            if ($newSchema) {
                $defaultAddress = $this->lengowAddress->getCustomerAddress();
                if ($defaultAddress) {
                    $customer->setNumber($customerNumber);
                    $customer->setSalutation($defaultAddress->getSalutation());
                    $customer->setFirstname($defaultAddress->getFirstname());
                    $customer->setLastname($defaultAddress->getLastname());
                } else {
                    return false;
                }
            }
            // get old billing and shipping addresses objects for all versions of Shopware
            if (LengowMain::compareVersion('5.5.0', '<')) {
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
            if (is_int($customerNumber) && $customerNumber > $number->getNumber()) {
                $number->setNumber($customerNumber);
            }
            // save default address for Shopware version > 5.2.0
            if ($newSchema && isset($defaultAddress)) {
                $defaultAddress->setCustomer($customer);
                $customer->setDefaultBillingAddress($defaultAddress);
                $customer->setDefaultShippingAddress($defaultAddress);
                $this->entityManager->persist($defaultAddress);
            }
            $this->entityManager->flush();
            return $customer;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
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
     * @param CustomerModel $customer Shopware customer instance
     * @param array $articles Shopware articles
     * @param LengowOrderModel $lengowOrder Lengow order instance
     *
     * @return OrderModel|false
     */
    protected function createOrder($customer, $articles, $lengowOrder)
    {
        try {
            // get Lengow payment method
            $payment = LengowMain::getLengowPayment();
            if ($payment === null) {
                return false;
            }
            // get default dispatch for import
            $dispatchId = LengowConfiguration::getConfig(LengowConfiguration::DEFAULT_IMPORT_CARRIER_ID, $this->shop);
            /** @var DispatchModel $dispatch */
            $dispatch = $this->entityManager->getReference(DispatchModel::class, $dispatchId);
            $dispatchTax = LengowMain::getDispatchTax($dispatch);
            $taxPercent = (float) $dispatchTax->getTax();
            // get currency for order amount
            /** @var CurrencyModel $currency */
            $currency = $this->entityManager->getRepository(CurrencyModel::class)
                ->findOneBy(array('currency' => $this->orderData->currency->iso_a3));
            // get current order status
            $orderStatus = LengowMain::getShopwareOrderStatus(
                $this->orderStateMarketplace,
                $this->marketplace,
                $this->shippedByMp
            );
            // get order date
            $orderDate = $this->orderData->marketplace_order_date !== null
                ? date('Y-m-d H:i:s', strtotime((string) $this->orderData->marketplace_order_date))
                : date('Y-m-d H:i:s', strtotime((string) $this->orderData->imported_at));
            // get shipping cost
            $shippingCost = $this->shippingCost + $this->processingFee;
            // get new order number
            /** @var OrderNumberModel $number */
            $number = Shopware()->Models()
                ->getRepository(OrderNumberModel::class)
                ->findOneBy(array('name' => 'invoice'));
            $orderNumber = $number->getNumber() + 1;
            $taxFree = 0;
            // If order is B2B and import B2B without tax is enabled => set the order to taxFree
            if (isset($this->orderTypes[LengowOrder::TYPE_BUSINESS])
                && (bool) LengowConfiguration::getConfig(LengowConfiguration::B2B_WITHOUT_TAX_ENABLED)) {
                $taxFree = 1;
            }
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
                'taxfree' => $taxFree,
                'partnerID' => '',
                'temporaryID' => '',
                'referer' => '',
                'cleareddate' => $orderDate,
                'trackingcode' => (string) $this->trackingNumber,
                'language' => $this->shop->getId(),
                'dispatchID' => $dispatch->getId(),
                'currency' => $currency->getCurrency(),
                'currencyFactor' => $currency->getFactor(),
                'subshopID' => $this->shop->getId(),
                'remote_addr' => $_SERVER['REMOTE_ADDR'],
            );
            Shopware()->Db()->insert('s_order', $orderParams);
            // get temporary order
            /** @var OrderModel $order */
            $order = Shopware()->Models()
                ->getRepository(OrderModel::class)
                ->findOneBy(array('number' => $orderNumber));
            // update Lengow order with new data
            $orderProcessState = LengowOrder::getOrderProcessState($this->orderStateLengow);
            $lengowOrder->setOrder($order)
                ->setOrderSku($order->getNumber())
                ->setOrderProcessState($orderProcessState)
                ->setOrderLengowState($this->orderStateLengow)
                ->setInError(false)
                ->setUpdatedAt(new DateTime())
                ->setExtra(json_encode($this->orderData));
            $this->entityManager->flush($lengowOrder);
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/lengow_order_updated'),
                $this->logOutput,
                $this->marketplaceSku
            );
            // get and set order attributes is from lengow
            $orderAttribute = new AttributeOrderModel();
            $orderAttribute->setLengowIsFromLengow(true);
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
            if (is_int($orderNumber) && $orderNumber > $number->getNumber()) {
                $number->setNumber($orderNumber);
            }
            $this->entityManager->flush();
            // create order detail foreach article
            $this->createOrderDetails($order, $articles);
            // create payment instance
            $this->createPaymentInstance($order, $billingAddress, $customer, $payment);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
        return $order;
    }

    /**
     * Get vat_number from lengow order data
     *
     * @return string|null
     */
    protected function getVatNumberFromOrderData()
    {
        if (isset($this->orderData->billing_address->vat_number)) {
            return $this->orderData->billing_address->vat_number;
        }
        if (isset($this->packageData->delivery->vat_number)) {
            return $this->packageData->delivery->vat_number;
        }
        return '';
    }

    /**
     * Create order details based on API data
     *
     * @param OrderModel $order Shopware order instance
     * @param array $articles Shopware articles
     *
     * @return boolean
     */
    protected function createOrderDetails($order, $articles)
    {
        try {
            $taxFree = false;
            // If order is B2B and import B2B without tax is enabled => set the order to taxFree
            if (isset($this->orderTypes[LengowOrder::TYPE_BUSINESS])
                && (bool) LengowConfiguration::getConfig(LengowConfiguration::B2B_WITHOUT_TAX_ENABLED)) {
                $taxFree = true;
            }
            foreach ($articles as $articleDetailId => $articleDetailData) {
                /** @var ArticleDetailModel $articleDetail */
                $articleDetail = $this->entityManager->getReference(ArticleDetailModel::class, $articleDetailId);
                // create name for a variation
                $detailName = '';
                $variations = LengowProduct::getArticleVariations($articleDetail->getId());
                foreach ($variations as $variation) {
                    $detailName .= ' ' . $variation;
                }
                /** @var OrderDetailStatusModel $detailStatus */
                $detailStatus = $this->entityManager->getReference(OrderDetailStatusModel::class, 0);
                // create order detail
                $orderDetail = new OrderDetailModel();
                $orderDetail->setOrder($order);
                $orderDetail->setNumber($order->getNumber());
                $orderDetail->setArticleId($articleDetail->getArticle()->getId());
                $orderDetail->setArticleNumber($articleDetail->getNumber());
                $orderDetail->setPrice($articleDetailData['price_unit']);
                $orderDetail->setQuantity($articleDetailData['quantity']);
                $orderDetail->setArticleName($articleDetail->getArticle()->getName() . $detailName);
                $orderDetail->setTaxRate($taxFree ? 0 : $articleDetail->getArticle()->getTax()->getTax());
                $orderDetail->setEan($articleDetail->getEan());
                $orderDetail->setUnit($articleDetail->getUnit() ? $articleDetail->getUnit()->getName() : '');
                $orderDetail->setPackUnit($articleDetail->getPackUnit());
                $orderDetail->setTax($taxFree ? null : $articleDetail->getArticle()->getTax());
                $orderDetail->setStatus($detailStatus);
                // decreases article detail stock
                $quantity = $articleDetail->getInStock();
                $newStock = $quantity - $articleDetailData['quantity'];
                // don't decrease stock -> Shopware decrease automatically
                $this->entityManager->persist($orderDetail);
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage(
                        'log/import/stock_decreased',
                        array(
                            'article_number' => $articleDetail->getNumber(),
                            'initial_stock' => $quantity,
                            'new_stock' => $newStock,
                        )
                    ),
                    $this->logOutput,
                    $this->marketplaceSku
                );
            }
            $this->entityManager->flush();
            return true;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
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
     * @param OrderModel $order Shopware customer instance
     * @param OrderBillingModel $billingAddress Shopware billing address instance
     * @param CustomerModel $customer Shopware customer instance
     * @param PaymentModel $payment Shopware payment instance
     *
     * @return boolean
     */
    protected function createPaymentInstance($order, $billingAddress, $customer, $payment)
    {
        try {
            $paymentInstance = new PaymentPaymentInstanceModel();
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
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/create_payment_instance'),
                $this->logOutput,
                $this->marketplaceSku
            );
            return true;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
    }

    /**
     * Create lines in lengow order line table
     *
     * @param OrderModel $order Shopware order instance
     * @param array $articles Shopware articles
     *
     * @return boolean
     */
    protected function createLengowOrderLines($order, $articles)
    {
        try {
            $orderLineSaved = '';
            foreach ($articles as $articleDetailId => $articleDetailData) {
                /** @var ArticleDetailModel $articleDetail */
                $articleDetail = $this->entityManager->getReference(ArticleDetailModel::class, $articleDetailId);
                // create Lengow order line entity
                foreach ($articleDetailData['order_line_ids'] as $orderLineId) {
                    $lengowOrderLine = new LengowOrderLineModel();
                    $lengowOrderLine->setOrder($order)
                        ->setDetail($articleDetail)
                        ->setOrderLineId($orderLineId);
                    $this->entityManager->persist($lengowOrderLine);
                    $this->entityManager->flush($lengowOrderLine);
                    $orderLineSaved .= empty($orderLineSaved) ? $orderLineId : ' / ' . $orderLineId;
                }
            }
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/lengow_order_line_saved',
                    array('order_line_saved' => $orderLineSaved)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return true;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
    }

    /**
     * Add quantity back to stock
     *
     * @param array $articles list of article
     *
     * @return boolean
     */
    protected function addQuantityBack($articles)
    {
        try {
            foreach ($articles as $articleDetailId => $articleDetailData) {
                /** @var ArticleDetailModel $articleDetail */
                $articleDetail = $this->entityManager->getReference(ArticleDetailModel::class, $articleDetailId);
                $quantity = $articleDetail->getInStock();
                $newStock = $quantity + $articleDetailData['quantity'];
                $articleDetail->setInStock($newStock);
            }
            $this->entityManager->flush();
            return true;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
    }
}
