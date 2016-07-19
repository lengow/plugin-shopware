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
    Symfony\Component\Validator\Constraints as Assert,
    Doctrine\Common\Collections\ArrayCollection;

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
     * @var integer $shopId
     *
     * @ORM\Column(name="shop_id", type="integer", nullable=false)
     */
    private $shopId;

    /**
     * @var string $deliveryAddressId
     *
     * @ORM\Column(name="delivery_address_id", type="integer", nullable=false)
     */
    private $deliveryAddressId;

    /**
     * @var string $marketplaceSku
     *
     * @ORM\Column(name="marketplace_sku", type="string", length=100, nullable=false)
     */
    private $marketplaceSku;

    /**
     * @var string $marketplaceName
     *
     * @ORM\Column(name="markeplace_name", type="string", length=100, nullable=false)
     */
    private $marketplaceName;

    /**
     * @var string $orderDate
     *
     * @ORM\Column(name="order_date", type="datetime", nullable=false)
     */
    private $orderDate;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var string $extra
     *
     * @ORM\Column(name="extra", type="text", nullable=true)
     */
    private $extra;

    /**
     * Gets the value of id.
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the value of id.
     *
     * @param integer $id $id the id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Gets the value of shopId.
     *
     * @return integer $shopId
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * Sets the value of shopId.
     *
     * @param integer $shopId $shopId the shop id
     *
     * @return self
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
        return $this;
    }

    /**
     * Gets the value of deliveryAddressId.
     *
     * @return string $deliveryAddressId
     */
    public function getDeliveryAddressId()
    {
        return $this->deliveryAddressId;
    }

    /**
     * Sets the value of deliveryAddressId.
     *
     * @param string $deliveryAddressId $deliveryAddressId the delivery address id
     *
     * @return self
     */
    public function setDeliveryAddressId($deliveryAddressId)
    {
        $this->deliveryAddressId = $deliveryAddressId;
        return $this;
    }

    /**
     * Gets the value of marketplaceSku.
     *
     * @return string $marketplaceSku
     */
    public function getMarketplaceSku()
    {
        return $this->marketplaceSku;
    }

    /**
     * Sets the value of marketplaceSku.
     *
     * @param string $marketplaceSku $marketplaceSku the marketplace sku
     *
     * @return self
     */
    public function setMarketplaceSku($marketplaceSku)
    {
        $this->marketplaceSku = $marketplaceSku;
        return $this;
    }

    /**
     * Gets the value of marketplaceName.
     *
     * @return string $marketplaceName
     */
    public function getMarketplaceName()
    {
        return $this->marketplaceName;
    }

    /**
     * Sets the value of marketplaceName.
     *
     * @param string $marketplaceName $marketplaceName the marketplace name
     *
     * @return self
     */
    public function setMarketplaceName($marketplaceName)
    {
        $this->marketplaceName = $marketplaceName;
        return $this;
    }

    /**
     * Gets the value of createdAt.
     *
     * @return \DateTime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets the value of createdAt.
     *
     * @param \DateTime $createdAt $createdAt the created at
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Gets the value of extra.
     *
     * @return string $extra
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Sets the value of extra.
     *
     * @param string $extra $extra the extra
     *
     * @return self
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
        return $this;
    }
}
