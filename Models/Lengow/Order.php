<?php

/**
 * Order.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */

namespace Shopware\CustomModels\Lengow;

use Shopware\Components\Model\ModelEntity,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Shopware\CustomModels\Lengow\Order
 *
 * @ORM\Entity
 * @ORM\Table(name="s_lengow_order")
 */
class Order extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="integer", nullable=true)
     */
    private $orderId = null;

    /**
     * @var \Shopware\Models\Order\Order
     *
     * @ORM\OneToOne(targetEntity="\Shopware\Models\Order\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    protected $order;

    /**
     * @var string
     *
     * @ORM\Column(name="order_sku", type="string", length=40, nullable=true)
     */
    private $orderSku = null;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="integer", nullable=false)
     */
    private $shopId;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_address_id", type="integer", nullable=false)
     */
    private $deliveryAddressId;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_country_iso", type="string", length=3, nullable=true)
     */
    private $deliveryCountryIso = null;

    /**
     * @var string
     *
     * @ORM\Column(name="marketplace_sku", type="string", length=100, nullable=false)
     */
    private $marketplaceSku;

    /**
     * @var string
     *
     * @ORM\Column(name="marketplace_name", type="string", length=100, nullable=false)
     */
    private $marketplaceName;

    /**
     * @var string
     *
     * @ORM\Column(name="marketplace_label", type="string", length=100, nullable=true)
     */
    private $marketplaceLabel = null;

    /**
     * @var string
     *
     * @ORM\Column(name="order_lengow_state", type="string", length=100, nullable=false)
     */
    private $orderLengowState;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_process_state", type="integer", nullable=false)
     */
    private $orderProcessState = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="order_date", type="datetime", nullable=false)
     */
    private $orderDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_item", type="integer", nullable=true)
     */
    private $orderItem = null;

    /**
     * @var string
     *
     * @ORM\Column(name="order_types", type="text", nullable=true)
     */
    private $orderTypes = null;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency = null;

    /**
     * @var float
     *
     * @ORM\Column(name="total_paid", type="decimal", precision=17, scale=2, nullable=true)
     */
    private $totalPaid = null;

    /**
     * @var string
     *
     * @ORM\Column(name="customer_vat_number", type="text", nullable=true)
     */
    private $customerVatNumber;

    /**
     * @var float
     *
     * @ORM\Column(name="commission", type="decimal", precision=17, scale=2, nullable=true)
     */
    private $commission = null;

    /**
     * @var string
     *
     * @ORM\Column(name="customer_name", type="string", length=255, nullable=true)
     */
    private $customerName = null;

    /**
     * @var string
     *
     * @ORM\Column(name="customer_email", type="string", length=255, nullable=true)
     */
    private $customerEmail = null;

    /**
     * @var string
     *
     * @ORM\Column(name="carrier", type="string", length=100, nullable=true)
     */
    private $carrier = null;

    /**
     * @var string
     *
     * @ORM\Column(name="carrier_method", type="string", length=100, nullable=true)
     */
    private $carrierMethod = null;

    /**
     * @var string
     *
     * @ORM\Column(name="carrier_tracking", type="string", length=100, nullable=true)
     */
    private $carrierTracking = null;

    /**
     * @var string
     *
     * @ORM\Column(name="carrier_id_relay", type="string", length=100, nullable=true)
     */
    private $carrierIdRelay = null;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sent_marketplace", type="boolean", nullable=false)
     */
    private $sentByMarketplace = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_in_error", type="boolean", nullable=false)
     */
    private $inError = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reimported", type="boolean", nullable=false)
     */
    private $reimported = false;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt = null;

    /**
     * @var string
     *
     * @ORM\Column(name="extra", type="text", nullable=true)
     */
    private $extra = null;

    /**
     * Gets the value of id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the value of id
     *
     * @param integer $id the id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Gets the value of Shopware order instance
     *
     * @return \Shopware\Models\Order\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Sets the value of Shopware order instance
     *
     * @param \Shopware\Models\Order\Order $order Shopware order instance
     *
     * @return self
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Gets the value of Shopware order sku
     *
     * @return string
     */
    public function getOrderSku()
    {
        return $this->orderSku;
    }

    /**
     * Sets the value of Shopware order sku
     *
     * @param string $orderSku Shopware order sku
     *
     * @return self
     */
    public function setOrderSku($orderSku)
    {
        $this->orderSku = $orderSku;
        return $this;
    }

    /**
     * Gets the value of shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * Sets the value of shopId
     *
     * @param integer $shopId the shop id
     *
     * @return self
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
        return $this;
    }

    /**
     * Gets the value of delivery address id
     *
     * @return string
     */
    public function getDeliveryAddressId()
    {
        return $this->deliveryAddressId;
    }

    /**
     * Sets the value of delivery address id
     *
     * @param string $deliveryAddressId the delivery address id
     *
     * @return self
     */
    public function setDeliveryAddressId($deliveryAddressId)
    {
        $this->deliveryAddressId = $deliveryAddressId;
        return $this;
    }

    /**
     * Gets the value of delivery country iso
     *
     * @return string
     */
    public function getDeliveryCountryIso()
    {
        return $this->deliveryCountryIso;
    }

    /**
     * Sets the value of delivery country iso
     *
     * @param string $deliveryCountryIso delivery country iso
     *
     * @return self
     */
    public function setDeliveryCountryIso($deliveryCountryIso)
    {
        $this->deliveryCountryIso = $deliveryCountryIso;
        return $this;
    }

    /**
     * Gets the value of marketplace sku
     *
     * @return string
     */
    public function getMarketplaceSku()
    {
        return $this->marketplaceSku;
    }

    /**
     * Sets the value of marketplace sku
     *
     * @param string $marketplaceSku the marketplace sku
     *
     * @return self
     */
    public function setMarketplaceSku($marketplaceSku)
    {
        $this->marketplaceSku = $marketplaceSku;
        return $this;
    }

    /**
     * Gets the value of marketplace name
     *
     * @return string
     */
    public function getMarketplaceName()
    {
        return $this->marketplaceName;
    }

    /**
     * Sets the value of marketplace name
     *
     * @param string $marketplaceName the marketplace name
     *
     * @return self
     */
    public function setMarketplaceName($marketplaceName)
    {
        $this->marketplaceName = $marketplaceName;
        return $this;
    }

    /**
     * Gets the value of marketplace label
     *
     * @return string
     */
    public function getMarketplaceLabel()
    {
        return $this->marketplaceLabel;
    }

    /**
     * Sets the value of marketplace label
     *
     * @param string $marketplaceLabel
     *
     * @return self
     */
    public function setMarketplaceLabel($marketplaceLabel)
    {
        $this->marketplaceLabel = $marketplaceLabel;
        return $this;
    }

    /**
     * Gets the value of order lengow state
     *
     * @return string
     */
    public function getOrderLengowState()
    {
        return $this->orderLengowState;
    }

    /**
     * Sets the value of order lengow state
     *
     * @param string $orderLengowState order lengow state
     *
     * @return self
     */
    public function setOrderLengowState($orderLengowState)
    {
        $this->orderLengowState = $orderLengowState;
        return $this;
    }

    /**
     * Gets the value of order process state
     *
     * @return integer
     */
    public function getOrderProcessState()
    {
        return $this->orderProcessState;
    }

    /**
     * Sets the value of order process state
     *
     * @param integer $orderProcessState order process state
     *
     * @return self
     */
    public function setOrderProcessState($orderProcessState)
    {
        $this->orderProcessState = $orderProcessState;
        return $this;
    }

    /**
     * Gets the value of order date
     *
     * @return \DateTime
     */
    public function getOrderDate()
    {
        return $this->orderDate;
    }

    /**
     * Sets the value of order date
     *
     * @param \DateTime $orderDate the order date
     *
     * @return self
     */
    public function setOrderDate($orderDate)
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    /**
     * Gets the value of order item
     *
     * @return integer
     */
    public function getOrderItem()
    {
        return $this->orderItem;
    }

    /**
     * Sets the value of order item
     *
     * @param integer $orderItem the order item
     *
     * @return self
     */
    public function setOrderItem($orderItem)
    {
        $this->orderItem = $orderItem;
        return $this;
    }

    /**
     * Gets the value of order types
     *
     * @return string
     */
    public function getOrderTypes()
    {
        return $this->orderTypes;
    }

    /**
     * Sets the value of order types
     *
     * @param string $orderTypes the order types
     *
     * @return self
     */
    public function setOrderTypes($orderTypes)
    {
        $this->orderTypes = $orderTypes;
        return $this;
    }

    /**
     * Gets the value of currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Sets the value of currency
     *
     * @param string $currency the currency
     *
     * @return self
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Gets the value of total paid
     *
     * @return float
     */
    public function getTotalPaid()
    {
        return $this->totalPaid;
    }

    /**
     * Sets the value of total paid
     *
     * @param float $totalPaid the total paid
     *
     * @return self
     */
    public function setTotalPaid($totalPaid)
    {
        $this->totalPaid = $totalPaid;
        return $this;
    }

    /**
     * Get the value of customer vat number
     *
     * @return string
     */
    public function getCustomerVatNumber() {
        return $this->customerVatNumber;
    }

    /**
     * @param string $customerVatNumber the customer vat number
     *
     * @return self
     */
    public function setCustomerVatNumber($customerVatNumber) {
        $this->customerVatNumber = $customerVatNumber;
        return $this;
    }

    /**
     * Gets the value of commission
     *
     * @return float
     */
    public function getCommission()
    {
        return $this->commission;
    }

    /**
     * Sets the value of commission
     *
     * @param float $commission the commission
     *
     * @return self
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;
        return $this;
    }

    /**
     * Gets the value of customer name
     *
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * Sets the value of customer name
     *
     * @param string $customerName the customer name
     *
     * @return self
     */
    public function setCustomerName($customerName)
    {
        $this->customerName = $customerName;
        return $this;
    }

    /**
     * Gets the value of customer email
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->customerEmail;
    }

    /**
     * Sets the value of customer email
     *
     * @param string $customerEmail the customer email
     *
     * @return self
     */
    public function setCustomerEmail($customerEmail)
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    /**
     * Gets the value of carrier
     *
     * @return string
     */
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * Sets the value of carrier
     *
     * @param string $carrier the carrier
     *
     * @return self
     */
    public function setCarrier($carrier)
    {
        $this->carrier = $carrier;
        return $this;
    }

    /**
     * Gets the value of carrier method
     *
     * @return string
     */
    public function getCarrierMethod()
    {
        return $this->carrierMethod;
    }

    /**
     * Sets the value of carrier method
     *
     * @param string $carrierMethod the carrier method
     *
     * @return self
     */
    public function setCarrierMethod($carrierMethod)
    {
        $this->carrierMethod = $carrierMethod;
        return $this;
    }

    /**
     * Gets the value of carrier tracking
     *
     * @return string
     */
    public function getCarrierTracking()
    {
        return $this->carrierTracking;
    }

    /**
     * Sets the value of carrier tracking
     *
     * @param string $carrierTracking the carrier tracking
     *
     * @return self
     */
    public function setCarrierTracking($carrierTracking)
    {
        $this->carrierTracking = $carrierTracking;
        return $this;
    }

    /**
     * Gets the value of carrier id relay
     *
     * @return string
     */
    public function getCarrierIdRelay()
    {
        return $this->carrierIdRelay;
    }

    /**
     * Sets the value of carrier id relay
     *
     * @param string $carrierIdRelay the carrier id relay
     *
     * @return self
     */
    public function setCarrierIdRelay($carrierIdRelay)
    {
        $this->carrierIdRelay = $carrierIdRelay;
        return $this;
    }

    /**
     * Gets the value of sent by marketplace
     *
     * @return boolean
     */
    public function isSentByMarketplace()
    {
        return $this->sentByMarketplace;
    }

    /**
     * Sets the value of sent by marketplace
     *
     * @param boolean $sentByMarketplace order sent by marketplace
     *
     * @return self
     */
    public function setSentByMarketplace($sentByMarketplace)
    {
        $this->sentByMarketplace = $sentByMarketplace;
        return $this;
    }

    /**
     * Gets the value of is in error
     *
     * @return boolean
     */
    public function isInError()
    {
        return $this->inError;
    }

    /**
     * Sets the value of is in error
     *
     * @param boolean $inError
     *
     * @return self
     */
    public function setInError($inError)
    {
        $this->inError = $inError;
        return $this;
    }

    /**
     * Gets the value of order is reimported
     *
     * @return boolean
     */
    public function isReimported()
    {
        return $this->reimported;
    }

    /**
     * Sets the value of order is reimported
     *
     * @param boolean $reimported order is reimported
     *
     * @return self
     */
    public function setReimported($reimported)
    {
        $this->reimported = $reimported;
        return $this;
    }

    /**
     * Gets the value of message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets the value of message
     *
     * @param string $message the message
     *
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Gets the value of created at
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets the value of created at
     *
     * @param \DateTime $createdAt the created at
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Gets the value of updated at
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Sets the value of updated at
     *
     * @param \DateTime $updatedAt updated at
     *
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Gets the value of extra
     *
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Sets the value of extra
     *
     * @param string $extra the extra
     *
     * @return self
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
        return $this;
    }
}
