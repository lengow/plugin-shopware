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
    /* Import Order construct params */
    const PARAM_SHOP = 'shop';
    const PARAM_FORCE_SYNC = 'force_sync';
    const PARAM_DEBUG_MODE = 'debug_mode';
    const PARAM_LOG_OUTPUT = 'log_output';
    const PARAM_MARKETPLACE_SKU = 'marketplace_sku';
    const PARAM_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    const PARAM_ORDER_DATA = 'order_data';
    const PARAM_PACKAGE_DATA = 'package_data';
    const PARAM_FIRST_PACKAGE = 'first_package';
    const PARAM_IMPORT_ONE_ORDER = 'import_one_order';

    /* Import Order data */
    const MERCHANT_ORDER_ID = 'merchant_order_id';
    const MERCHANT_ORDER_REFERENCE = 'merchant_order_reference';
    const LENGOW_ORDER_ID = 'lengow_order_id';
    const MARKETPLACE_SKU = 'marketplace_sku';
    const MARKETPLACE_NAME = 'marketplace_name';
    const DELIVERY_ADDRESS_ID = 'delivery_address_id';
    const SHOP_ID = 'shop_id';
    const CURRENT_ORDER_STATUS = 'current_order_status';
    const PREVIOUS_ORDER_STATUS = 'previous_order_status';
    const ERRORS = 'errors';
    const RESULT_TYPE = 'result_type';

    /* Synchronisation results */
    const RESULT_CREATED = 'created';
    const RESULT_UPDATED = 'updated';
    const RESULT_FAILED = 'failed';
    const RESULT_IGNORED = 'ignored';

    /**
     * @var ShopModel Shopware shop instance
     */
    private $shop;

    /**
     * @var boolean force import order even if there are errors
     */
    private $forceSync;

    /**
     * @var boolean use debug mode
     */
    private $debugMode;

    /**
     * @var boolean display log messages
     */
    private $logOutput;

    /**
     * @var LengowMarketplace Lengow marketplace instance
     */
    private $marketplace;

    /**
     * @var string marketplace label
     */
    private $marketplaceLabel;

    /**
     * @var string id lengow of current order
     */
    private $marketplaceSku;

    /**
     * @var integer id of delivery address for current order
     */
    private $deliveryAddressId;

    /**
     * @var mixed API order data
     */
    private $orderData;

    /**
     * @var mixed API package data
     */
    private $packageData;

    /**
     * @var boolean is first package
     */
    private $firstPackage;

    /**
     * @var boolean import one order var from lengow import
     */
    private $importOneOrder;

    /**
     * @var LengowOrderModel Lengow order model instance
     */
    private $lengowOrder;

    /**
     * @var integer id of the record Shopware order table
     */
    private $orderId;

    /**
     * @var integer Shopware order reference
     */
    private $orderReference;

    /**
     * @var DateTime order date in GMT format
     */
    private $orderDate;

    /**
     * @var string marketplace order state
     */
    private $orderStateMarketplace;

    /**
     * @var string Lengow order state
     */
    private $orderStateLengow;

    /**
     * @var string Previous Lengow order state
     */
    private $previousOrderStateLengow;

    /**
     * @var float order processing fee
     */
    private $processingFee;

    /**
     * @var float order shipping cost
     */
    private $shippingCost;

    /**
     * @var float order total amount
     */
    private $orderAmount;

    /**
     * @var string customer VAT number
     */
    private $customerVatNumber;

    /**
     * @var integer number of order items
     */
    private $orderItems;

    /**
     * @var array order types (is_express, is_prime...)
     */
    private $orderTypes;

    /**
     * @var string|null carrier name
     */
    private $carrierName;

    /**
     * @var string|null carrier method
     */
    private $carrierMethod;

    /**
     * @var string|null carrier tracking number
     */
    private $trackingNumber;

    /**
     * @var string|null carrier relay id
     */
    private $relayId;

    /**
     * @var boolean if order is send by the marketplace
     */
    private $shippedByMp = false;

    /**
     * @var boolean re-import order
     */
    private $isReimported = false;

    /**
     * @var ModelManager Shopware's entity manager
     */
    private $entityManager;

    /**
     * @var LengowAddress Lengow address instance
     */
    private $lengowAddress;

    /**
     * @var array order errors
     */
    private $errors = array();

    /**
     * Construct the import manager
     *
     * @param $params array optional options
     *
     * ShopModel shop                Shopware shop instance
     * boolean   debug_mode          debug mode
     * boolean   log_output          display log messages
     * string    marketplace_sku     order marketplace sku
     * integer   delivery_address_id order delivery address id
     * mixed     order_data          order data
     * mixed     package_data        package data
     * boolean   first_package       it is the first package
     * boolean   import_one_order    import one order
     */
    public function __construct($params = array())
    {
        $this->shop = $params[self::PARAM_SHOP];
        $this->forceSync = $params[self::PARAM_FORCE_SYNC];
        $this->debugMode = $params[self::PARAM_DEBUG_MODE];
        $this->logOutput = $params[self::PARAM_LOG_OUTPUT];
        $this->marketplaceSku = $params[self::PARAM_MARKETPLACE_SKU];
        $this->deliveryAddressId = $params[self::PARAM_DELIVERY_ADDRESS_ID];
        $this->orderData = $params[self::PARAM_ORDER_DATA];
        $this->packageData = $params[self::PARAM_PACKAGE_DATA];
        $this->firstPackage = $params[self::PARAM_FIRST_PACKAGE];
        $this->importOneOrder = $params[self::PARAM_IMPORT_ONE_ORDER];
        $this->entityManager = LengowBootstrap::getEntityManager();
    }

    /**
     * Create or update order
     *
     * @return array
     */
    public function importOrder()
    {
        // load marketplace singleton and marketplace data
        if (!$this->loadMarketplaceData()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // checks if a record already exists in the lengow order table
        $this->lengowOrder = $this->entityManager->getRepository('Shopware\CustomModels\Lengow\Order')
            ->findOneBy(
                array(
                    'marketplaceSku' => $this->marketplaceSku,
                    'marketplaceName' => $this->marketplace->name,
                    'deliveryAddressId' => $this->deliveryAddressId,
                )
            );
        // checks if an order already has an error in progress
        if ($this->lengowOrder && $this->orderErrorAlreadyExist()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // recovery id if the order has already been imported
        $order = LengowOrder::getOrderFromLengowOrder(
            $this->marketplaceSku,
            $this->marketplace->name,
            $this->deliveryAddressId
        );
        // update order state if already imported
        if ($order) {
            $orderUpdated = $this->checkAndUpdateOrder($order);
            if ($orderUpdated) {
                return $this->returnResult(self::RESULT_UPDATED);
            }
            if (!$this->isReimported) {
                return $this->returnResult(self::RESULT_IGNORED);
            }
        }
        // checks if the order is not anonymized or too old
        if ($this->lengowOrder === null && !$this->canCreateOrder()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // checks if an external id already exists
        if ($this->lengowOrder === null && $this->externalIdAlreadyExist()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // Checks if the order status is valid for order creation
        if (!$this->orderStatusIsValid()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // load data and create a new record in lengow order table if not exist
        if (!$this->createLengowOrder()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // checks if the required order data is present and update Lengow order record
        if (!$this->checkAndUpdateLengowOrderData()) {
            return $this->returnResult(self::RESULT_FAILED);
        }
        // checks if an order sent by the marketplace must be created or not
        if (!$this->canCreateOrderShippedByMarketplace()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // create PrestaShop order
        if (!$this->createOrder()) {
            return $this->returnResult(self::RESULT_FAILED);
        }
        return $this->returnResult(self::RESULT_CREATED);
    }

    /**
     * Load marketplace singleton and marketplace data
     *
     * @return boolean
     */
    private function loadMarketplaceData()
    {
        try {
            // get marketplace and Lengow order state
            $this->marketplace = LengowMain::getMarketplaceSingleton((string) $this->orderData->marketplace);
            $this->marketplaceLabel = $this->marketplace->labelName;
            $this->orderStateMarketplace = (string) $this->orderData->marketplace_status;
            $this->orderStateLengow = $this->marketplace->getStateLengow($this->orderStateMarketplace);
            $this->previousOrderStateLengow = $this->orderStateLengow;
            return true;
        } catch (LengowException $e) {
            $this->errors[] = LengowMain::decodeLogMessage($e->getMessage(), LengowTranslation::DEFAULT_ISO_CODE);
            LengowMain::log(LengowLog::CODE_IMPORT, $e->getMessage(), $this->logOutput, $this->marketplaceSku);
        }
        return false;
    }

    /**
     * Return an array of result for each order
     *
     * @param string $resultType Type of result (created, updated, failed or ignored)
     *
     * @return array
     */
    private function returnResult($resultType)
    {
        return array(
            self::MERCHANT_ORDER_ID => $this->orderId,
            self::MERCHANT_ORDER_REFERENCE => $this->orderReference,
            self::LENGOW_ORDER_ID => $this->lengowOrder ? $this->lengowOrder->getId() : null,
            self::MARKETPLACE_SKU => $this->marketplaceSku,
            self::MARKETPLACE_NAME => $this->marketplace ? $this->marketplace->name : null,
            self::DELIVERY_ADDRESS_ID => $this->deliveryAddressId,
            self::SHOP_ID => $this->shop->getId(),
            self::CURRENT_ORDER_STATUS => $this->orderStateLengow,
            self::PREVIOUS_ORDER_STATUS => $this->previousOrderStateLengow,
            self::ERRORS => $this->errors,
            self::RESULT_TYPE => $resultType,
        );
    }

    /**
     * Checks if an order already has an error in progress
     *
     * @return boolean
     */
    private function orderErrorAlreadyExist()
    {
        // if order error exist and not finished
        $orderError = LengowOrder::orderIsInError($this->marketplaceSku, $this->deliveryAddressId);
        if (!$orderError) {
            return false;
        }
        // force order synchronization by removing pending errors
        if ($this->forceSync && $this->lengowOrder) {
            LengowOrderError::finishOrderErrors($this->lengowOrder->getId());
            return false;
        }
        $decodedMessage = LengowMain::decodeLogMessage($orderError['message'], LengowTranslation::DEFAULT_ISO_CODE);
        /** @var DateTime $dateMessage */
        $dateMessage = $orderError['createdAt'];
        $message = LengowMain::setLogMessage(
            'log/import/error_already_created',
            array(
                'decoded_message' => $decodedMessage,
                'date_message' => $dateMessage->format(LengowMain::DATE_FULL),
            )
        );
        $this->errors[] = LengowMain::decodeLogMessage($message, LengowTranslation::DEFAULT_ISO_CODE);
        LengowMain::log(LengowLog::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
        return true;
    }

    /**
     * Check the order and updates data if necessary
     *
     * @param OrderModel $order Shopware order instance
     *
     * @return boolean
     */
    private function checkAndUpdateOrder($order)
    {
        $orderUpdated = false;
        LengowMain::log(
            LengowLog::CODE_IMPORT,
            LengowMain::setLogMessage('log/import/order_already_imported', array('order_id' => $order->getNumber())),
            $this->logOutput,
            $this->marketplaceSku
        );
        if ($this->lengowOrder === null) {
            return false;
        }
        // Lengow -> Cancel and reimport order
        if ($this->lengowOrder && $this->lengowOrder->isReimported()) {
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
        // load data for return
        $this->orderId = $order->getId();
        $this->orderReference = $order->getNumber();
        $this->previousOrderStateLengow = $this->lengowOrder->getOrderLengowState();
        try {
            // try to update Shopware order, lengow order and finish actions if necessary
            $orderUpdated = LengowOrder::updateState(
                $order,
                $this->lengowOrder,
                $this->orderStateLengow,
                $this->packageData,
                $this->logOutput
            );
            if ($orderUpdated) {
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage('log/import/order_state_updated', array('state_name' => $orderUpdated)),
                    $this->logOutput,
                    $this->marketplaceSku
                );
                $orderUpdated = true;
            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage() . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/error_order_state_updated',
                    array('error_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        return $orderUpdated;
    }

    /**
     * Checks if the order is not anonymized or too old
     *
     * @return boolean
     */
    private function canCreateOrder()
    {
        if ($this->importOneOrder) {
            return true;
        }
        // skip import if the order is anonymize
        if ($this->orderData->anonymized) {
            $message = LengowMain::setLogMessage('log/import/anonymized_order');
            $this->errors[] = LengowMain::decodeLogMessage($message, LengowTranslation::DEFAULT_ISO_CODE);
            LengowMain::log(LengowLog::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
            return false;
        }
        // skip import if the order is older than 3 months
        try {
            $dateTimeOrder = new DateTime($this->orderData->marketplace_order_date);
            $interval = $dateTimeOrder->diff(new DateTime());
            $monthsInterval = $interval->m + ($interval->y * 12);
            if ($monthsInterval >= LengowImport::MONTH_INTERVAL_TIME) {
                $message = LengowMain::setLogMessage('log/import/old_order');
                $this->errors[] = LengowMain::decodeLogMessage($message, LengowTranslation::DEFAULT_ISO_CODE);
                LengowMain::log(LengowLog::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
                return false;
            }
        } catch (Exception $e) {
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/unable_verify_date'),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        return true;
    }

    /**
     * Checks if an external id already exists
     *
     * @return boolean
     */
    private function externalIdAlreadyExist()
    {
        if (empty($this->orderData->merchant_order_id) || $this->debugMode || $this->isReimported) {
            return false;
        }
        foreach ($this->orderData->merchant_order_id as $externalId) {
            if (LengowOrder::getIdFromLengowDeliveryAddress((int) $externalId, $this->deliveryAddressId)) {
                $message = LengowMain::setLogMessage('log/import/external_id_exist', array('order_id' => $externalId));
                $this->errors[] = LengowMain::decodeLogMessage($message, LengowTranslation::DEFAULT_ISO_CODE);
                LengowMain::log(LengowLog::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the order status is valid for order creation
     *
     * @return boolean
     */
    private function orderStatusIsValid()
    {
        // if order is canceled or new -> skip
        if (LengowImport::checkState($this->orderStateMarketplace, $this->marketplace)) {
            return true;
        }
        $orderProcessState = LengowOrder::getOrderProcessState($this->orderStateLengow);
        // check and complete an order not imported if it is canceled or refunded
        if ($this->lengowOrder && $orderProcessState === LengowOrder::PROCESS_STATE_FINISH) {
            LengowOrderError::finishOrderErrors($this->lengowOrder->getId());
            try {
                $this->lengowOrder->setInError(false)
                    ->setOrderLengowState($this->orderStateLengow)
                    ->setOrderProcessState($orderProcessState);
                Shopware()->Models()->flush($this->lengowOrder);
            } catch (Exception $e) {
                $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                    . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                LengowMain::log(
                    LengowLog::CODE_ORM,
                    LengowMain::setLogMessage(
                        'log/exception/order_insert_failed',
                        array('decoded_message' => $errorMessage)
                    ),
                    $this->logOutput,
                    $this->marketplaceSku
                );
            }
        }
        $message = LengowMain::setLogMessage(
            'log/import/current_order_state_unavailable',
            array(
                'order_state_marketplace' => $this->orderStateMarketplace,
                'marketplace_name' => $this->marketplace->name,
            )
        );
        $this->errors[] = LengowMain::decodeLogMessage($message, LengowTranslation::DEFAULT_ISO_CODE);
        LengowMain::log(LengowLog::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
        return false;
    }

    /**
     * Create an order in lengow orders table
     *
     * @return boolean
     */
    private function createLengowOrder()
    {
        // load order date
        $this->loadOrderDate();
        // load order types data
        $this->loadOrderTypesData();
        // load customer VAT number
        $this->loadVatNumberFromOrderData();
        // if the Lengow order already exists do not recreate it
        if ($this->lengowOrder) {
            return true;
        }
        try {
            // create Lengow order entity
            $this->lengowOrder = new LengowOrderModel();
            $this->lengowOrder->setShopId($this->shop->getId())
                ->setMarketplaceSku($this->marketplaceSku)
                ->setMarketplaceName($this->marketplace->name)
                ->setMarketplaceLabel($this->marketplaceLabel)
                ->setDeliveryAddressId($this->deliveryAddressId)
                ->setOrderDate($this->orderDate)
                ->setOrderLengowState($this->orderStateLengow)
                ->setOrderTypes(json_encode($this->orderTypes))
                ->setMessage($this->getOrderComment())
                ->setCustomerVatNumber($this->customerVatNumber)
                ->setExtra(json_encode($this->orderData))
                ->setCreatedAt(new DateTime())
                ->setInError(true);
            $this->entityManager->persist($this->lengowOrder);
            $this->entityManager->flush($this->lengowOrder);
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/lengow_order_saved'),
                $this->logOutput,
                $this->marketplaceSku
            );
            return true;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            $message = LengowMain::setLogMessage('log/import/lengow_order_not_saved');
            $this->errors[] = LengowMain::decodeLogMessage($message, LengowTranslation::DEFAULT_ISO_CODE);
            LengowMain::log(LengowLog::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
            return false;
        }
    }

    /**
     * Load order date in GMT format
     */
    private function loadOrderDate()
    {
        $orderDate = $this->orderData->marketplace_order_date !== null
            ? (string) $this->orderData->marketplace_order_date
            : (string) $this->orderData->imported_at;
        try {
            $this->orderDate = new DateTime(date(LengowMain::DATE_FULL, strtotime($orderDate)));
        } catch (Exception $e) {
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage('log/import/unable_load_order_date'),
                $this->logOutput,
                $this->marketplaceSku
            );
            $this->orderDate = new DateTime();
        }
    }

    /**
     * Get order types data and update Lengow order record
     */
    private function loadOrderTypesData()
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
     * Load customer VAT number
     */
    private function loadVatNumberFromOrderData()
    {
        $this->customerVatNumber = '';
        if (isset($this->orderData->billing_address->vat_number)) {
            $this->customerVatNumber = $this->orderData->billing_address->vat_number;
        }
        if (isset($this->packageData->delivery->vat_number)) {
            $this->customerVatNumber = $this->packageData->delivery->vat_number;
        }
    }

    /**
     * Get order comment from marketplace
     */
    private function getOrderComment()
    {
        if (isset($this->orderData->comments) && is_array($this->orderData->comments)) {
            return implode(',', $this->orderData->comments);
        }
        return (string) $this->orderData->comments;
    }

    /**
     * Checks if the required order data is present and update Lengow order record
     *
     * @return boolean
     */
    private function checkAndUpdateLengowOrderData()
    {
        // checks if the required order data is present
        if (!$this->checkOrderData()) {
            return false;
        }
        // load order amount, processing fees and shipping costs
        $this->loadOrderAmount();
        // load tracking data
        $this->loadTrackingData();
        // update Lengow order with new data
        try  {
            $this->lengowOrder->setCurrency($this->orderData->currency->iso_a3)
                ->setTotalPaid($this->orderAmount)
                ->setOrderItem($this->orderItems)
                ->setCustomerName($this->getCustomerName())
                ->setCustomerEmail($this->getCustomerEmail())
                ->setCarrier($this->carrierName)
                ->setCarrierMethod($this->carrierMethod)
                ->setCarrierTracking($this->trackingNumber)
                ->setCarrierIdRelay($this->relayId)
                ->setSentByMarketplace($this->shippedByMp)
                ->setDeliveryCountryIso((string) $this->packageData->delivery->common_country_iso_a2)
                ->setOrderLengowState($this->orderStateLengow)
                ->setExtra(json_encode($this->orderData))
                ->setUpdatedAt(new DateTime());
            $this->entityManager->flush($this->lengowOrder);
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            $message = LengowMain::setLogMessage('log/import/lengow_order_not_updated');
            $this->errors[] = LengowMain::decodeLogMessage($message, LengowTranslation::DEFAULT_ISO_CODE);
            LengowMain::log(LengowLog::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
            return false;
        }
        return true;
    }

    /**
     * Checks if all necessary order data are present
     *
     * @return boolean
     */
    private function checkOrderData()
    {
        $errorMessages = array();
        if (empty($this->packageData->cart)) {
            $errorMessages[] = LengowMain::setLogMessage('lengow_log/error/no_product');
        }
        if (!isset($this->orderData->currency->iso_a3)) {
            $errorMessages[] = LengowMain::setLogMessage('lengow_log/error/no_currency');
        } else {
            /** @var CurrencyModel $currency */
            $currency = $this->entityManager->getRepository('Shopware\Models\Shop\Currency')
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
        if (empty($errorMessages)) {
            return true;
        }
        foreach ($errorMessages as $errorMessage) {
            LengowOrderError::createOrderError($this->lengowOrder, $errorMessage);
            $decodedMessage = LengowMain::decodeLogMessage($errorMessage, LengowTranslation::DEFAULT_ISO_CODE);
            $this->errors[] = $decodedMessage;
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/order_import_failed',
                    array('decoded_message' => $decodedMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        return false;
    }

    /**
     * Load order amount, processing fees and shipping costs
     */
    private function loadOrderAmount()
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
        $this->orderAmount = $totalAmount + $this->processingFee + $this->shippingCost;
    }

    /**
     * Get tracking data and update Lengow order record
     */
    private function loadTrackingData()
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
    private function getCustomerName()
    {
        $firstName = ucfirst(strtolower((string) $this->orderData->billing_address->first_name));
        $lastName = ucfirst(strtolower((string) $this->orderData->billing_address->last_name));
        if (empty($firstName) && empty($lastName)) {
            return (string) $this->orderData->billing_address->full_name;
        }
        if (empty($firstName)) {
            return $lastName;
        }
        if (empty($lastName)) {
            return $firstName;
        }
        return $firstName . ' ' . $lastName;
    }

    /**
     * Get customer email
     *
     * @return string
     */
    private function getCustomerEmail()
    {
        return $this->orderData->billing_address->email !== null
            ? (string) $this->orderData->billing_address->email
            : (string) $this->packageData->delivery->email;
    }

    /**
     * Checks if an order sent by the marketplace must be created or not
     *
     * @return boolean
     */
    private function canCreateOrderShippedByMarketplace()
    {
        // check if the order is shipped by marketplace
        if ($this->shippedByMp) {
            $message = LengowMain::setLogMessage(
                'log/import/order_shipped_by_marketplace',
                array('marketplace_name' => $this->marketplace->name)
            );
            LengowMain::log(LengowLog::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
            if (!LengowConfiguration::getConfig(LengowConfiguration::SHIPPED_BY_MARKETPLACE_ENABLED)) {
                try {
                    $this->lengowOrder->setOrderProcessState(LengowOrder::PROCESS_STATE_FINISH)
                        ->setInError(false)
                        ->setExtra(json_encode($this->orderData));
                    $this->entityManager->flush($this->lengowOrder);
                } catch (Exception $e) {
                    $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                        . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                    LengowMain::log(
                        LengowLog::CODE_ORM,
                        LengowMain::setLogMessage(
                            'log/exception/order_insert_failed',
                            array('decoded_message' => $errorMessage)
                        ),
                        $this->logOutput,
                        $this->marketplaceSku
                    );
                }
                return false;
            }
        }
        return true;
    }

    /**
     * Create a Shopware order
     *
     * @return boolean
     */
    private function createOrder()
    {
        try {
            // get all Shopware articles
            $articles = $this->getArticles();
            if (empty($articles)) {
                throw new LengowException(LengowMain::setLogMessage('lengow_log/exception/no_product_to_cart'));
            }
            // load lengow address to create all specific Shopware addresses for customer and order
            $this->loadLengowAddress();
            // get or create Shopware customer
            $customer = $this->getOrCreateCustomer();
            // create a Shopware order
            $order = $this->createShopwareOrder($customer, $articles, $this->lengowOrder);
            if (!$order) {
                throw new LengowException(LengowMain::setLogMessage('lengow_log/exception/shopware_order_not_saved'));
            }
            // load order data for return
            $this->orderId = $order->getId();
            $this->orderReference = $order->getNumber();
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
            $this->addQuantityBack($articles);
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Shopware error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        if (!isset($errorMessage)) {
            return true;
        }
        LengowOrderError::createOrderError($this->lengowOrder, $errorMessage);
        $decodedMessage = LengowMain::decodeLogMessage($errorMessage, LengowTranslation::DEFAULT_ISO_CODE);
        $this->errors[] = $decodedMessage;
        LengowMain::log(
            LengowLog::CODE_IMPORT,
            LengowMain::setLogMessage(
                'log/import/order_import_failed',
                array('decoded_message' => $decodedMessage)
            ),
            $this->logOutput,
            $this->marketplaceSku
        );
        try {
            $this->lengowOrder->setOrderLengowState($this->orderStateLengow)
                ->setReimported(false)
                ->setUpdatedAt(new DateTime())
                ->setExtra(json_encode($this->orderData));
            $this->entityManager->flush($this->lengowOrder);
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        return false;
    }

    /**
     * Get articles from the API
     *
     * @throws LengowException article is a parent / article not be found
     *
     * @return array
     */
    private function getArticles()
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
     * Load lengow address to create all specific Shopware addresses for customer and order
     *
     * @throws LengowException
     */
    private function loadLengowAddress()
    {
        $this->lengowAddress = new LengowAddress(
            array(
                'billing_data' => $this->orderData->billing_address,
                'shipping_data' => $this->packageData->delivery,
                'relay_id' => $this->relayId,
                'marketplace_sku' => $this->marketplaceSku,
                'log_output' => $this->logOutput,
                'vat_number' => $this->customerVatNumber,
            )
        );
    }

    /**
     * Get an existing customer or create a new one
     *
     * @throws LengowException Shopware customer not be saved
     *
     * @return Shopware\Models\Customer\Customer
     */
    private function getOrCreateCustomer()
    {
        // get fictitious email for customer creation
        $customerEmail = $this->generateCustomerEmail();
        /** @var CustomerModel $customer */
        $customer = $this->entityManager
            ->getRepository('Shopware\Models\Customer\Customer')
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
        return $customer;
    }

    /**
     * Get fictitious email for customer creation
     *
     * @return string
     */
    private function generateCustomerEmail()
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
     * Create customer based on API data
     *
     * @param string $customerEmail fictitious customer email
     *
     * @return Shopware\Models\Customer\Customer|false
     */
    private function createCustomer($customerEmail)
    {
        try {
            // get Lengow payment method
            $lengowPayment = LengowMain::getLengowPayment();
            if ($lengowPayment === null) {
                return false;
            }
            // get new customer number
            /** @var OrderNumberModel $number */
            $number = Shopware()->Models()
                ->getRepository('Shopware\Models\Order\Number')
                ->findOneBy(array('name' => 'user'));
            $customerNumber = $number->getNumber() + 1;
            // create a Shopware customer
            $customer = new CustomerModel();
            $customerAttribute = new AttributeCustomerModel();
            // get new address object to create default address
            $defaultAddress = $this->lengowAddress->getCustomerAddress();
            if ($defaultAddress) {
                $customer->setNumber($customerNumber);
                $customer->setSalutation($defaultAddress->getSalutation());
                $customer->setFirstname($defaultAddress->getFirstname());
                $customer->setLastname($defaultAddress->getLastname());
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
            $number->setNumber($customerNumber);
            // save default address
            $defaultAddress->setCustomer($customer);
            $customer->setDefaultBillingAddress($defaultAddress);
            $customer->setDefaultShippingAddress($defaultAddress);
            $this->entityManager->persist($defaultAddress);
            $this->entityManager->flush();
            return $customer;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
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
    private function createShopwareOrder($customer, $articles, $lengowOrder)
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
            $dispatch = $this->entityManager->getReference('Shopware\Models\Dispatch\Dispatch', $dispatchId);
            $dispatchTax = LengowMain::getDispatchTax($dispatch);
            $taxPercent = (float) $dispatchTax->getTax();
            // get currency for order amount
            /** @var CurrencyModel $currency */
            $currency = $this->entityManager->getRepository('Shopware\Models\Shop\Currency')
                ->findOneBy(array('currency' => $this->orderData->currency->iso_a3));
            // get current order status
            $orderStatus = LengowMain::getShopwareOrderStatus(
                $this->orderStateMarketplace,
                $this->marketplace,
                $this->shippedByMp
            );
            // get order date
            $orderDate = $this->orderData->marketplace_order_date !== null
                ? date(LengowMain::DATE_FULL, strtotime((string) $this->orderData->marketplace_order_date))
                : date(LengowMain::DATE_FULL, strtotime((string) $this->orderData->imported_at));
            // get shipping cost
            $shippingCost = $this->shippingCost + $this->processingFee;
            // get new order number
            /** @var OrderNumberModel $number */
            $number = Shopware()->Models()
                ->getRepository('Shopware\Models\Order\Number')
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
                ->getRepository('Shopware\Models\Order\Order')
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
            $number->setNumber($orderNumber);
            $this->entityManager->persist($number);
            $this->entityManager->flush();
            // create order detail foreach article
            $this->createOrderDetails($order, $articles);
            // create payment instance
            $this->createPaymentInstance($order, $billingAddress, $customer, $payment);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
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
     * Create order details based on API data
     *
     * @param OrderModel $order Shopware order instance
     * @param array $articles Shopware articles
     */
    private function createOrderDetails($order, $articles)
    {
        try {
            $taxFree = false;
            // If order is B2B and import B2B without tax is enabled => set the order to taxFree
            if (isset($this->orderTypes[LengowOrder::TYPE_BUSINESS])
                && (bool) LengowConfiguration::getConfig(LengowConfiguration::B2B_WITHOUT_TAX_ENABLED)
            ) {
                $taxFree = true;
            }
            foreach ($articles as $articleDetailId => $articleDetailData) {
                /** @var ArticleDetailModel $articleDetail */
                $articleDetail = $this->entityManager->getReference('Shopware\Models\Article\Detail', $articleDetailId);
                // create name for a variation
                $detailName = '';
                $variations = LengowProduct::getArticleVariations($articleDetail->getId());
                foreach ($variations as $variation) {
                    $detailName .= ' ' . $variation;
                }
                /** @var OrderDetailStatusModel $detailStatus */
                $detailStatus = $this->entityManager->getReference('Shopware\Models\Order\DetailStatus', 0);
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
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
    }

    /**
     * Create payment instance based on API data
     *
     * @param OrderModel $order Shopware customer instance
     * @param OrderBillingModel $billingAddress Shopware billing address instance
     * @param CustomerModel $customer Shopware customer instance
     * @param PaymentModel $payment Shopware payment instance
     */
    private function createPaymentInstance($order, $billingAddress, $customer, $payment)
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
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
    }

    /**
     * Create lines in lengow order line table
     *
     * @param OrderModel $order Shopware order instance
     * @param array $articles Shopware articles
     */
    private function createLengowOrderLines($order, $articles)
    {
        try {
            $orderLineSaved = '';
            foreach ($articles as $articleDetailId => $articleDetailData) {
                /** @var ArticleDetailModel $articleDetail */
                $articleDetail = $this->entityManager->getReference('Shopware\Models\Article\Detail', $articleDetailId);
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
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
    }

    /**
     * Add quantity back to stock
     *
     * @param array $articles list of article
     */
    private function addQuantityBack($articles)
    {
        // add quantity back for re-import order and order shipped by marketplace
        if ($this->isReimported
            || ($this->shippedByMp && !(bool) LengowConfiguration::getConfig(
                    LengowConfiguration::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED
            ))
        ) {
            $logMessage = $this->isReimported
                ? LengowMain::setLogMessage('log/import/quantity_back_reimported_order')
                : LengowMain::setLogMessage('log/import/quantity_back_shipped_by_marketplace');
            LengowMain::log(LengowLog::CODE_IMPORT, $logMessage, $this->logOutput, $this->marketplaceSku);
            try {
                foreach ($articles as $articleDetailId => $articleDetailData) {
                    /** @var ArticleDetailModel $articleDetail */
                    $articleDetail = $this->entityManager->getReference(
                        'Shopware\Models\Article\Detail',
                        $articleDetailId
                    );
                    $quantity = $articleDetail->getInStock();
                    $newStock = $quantity + $articleDetailData['quantity'];
                    $articleDetail->setInStock($newStock);
                }
                $this->entityManager->flush();
            } catch (Exception $e) {
                $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                    . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                LengowMain::log(
                    LengowLog::CODE_ORM,
                    LengowMain::setLogMessage(
                        'log/exception/order_insert_failed',
                        array('decoded_message' => $errorMessage)
                    ),
                    $this->logOutput,
                    $this->marketplaceSku
                );
            }
        }
    }
}
