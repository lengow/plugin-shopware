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
    Doctrine\ORM\Mapping AS ORM,
    Symfony\Component\Validator\Constraints as Assert,
    Doctrine\Common\Collections\ArrayCollection;

/**
 * Shopware\CustomModels\Lengow\Order
 *
 * @ORM\Entity
 * @ORM\Table(name="lengow_orders")
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
     * @var string $idOrderLengow
     *
     * @ORM\Column(name="idOrderLengow", type="string", length=100, nullable=false)
     */
    private $idOrderLengow;

    /**
     * @var integer $idFlux
     *
     * @ORM\Column(name="idFlux", type="integer", nullable=false)
     */
    private $idFlux;

    /**
     * @var string $marketplace
     *
     * @ORM\Column(name="marketplace", type="string", length=100, nullable=false)
     */
    private $marketplace;

    /**
     * @var float $totalPaid
     *
     * @ORM\Column(name="totalPaid", type="float", nullable=false)
     */   
    private $totalPaid;

    /**
     * @var string $carrier
     *
     * @ORM\Column(name="carrier", type="string", length=100, nullable=false)
     */
    private $carrier;

    /**
     * @var string $trackingNumber
     *
     * @ORM\Column(name="trackingNumber", type="string", length=100, nullable=false)
     */
    private $trackingNumber;

    /**
     * @var \DateTime $orderDate
     *
     * @ORM\Column(name="orderDate", type="datetime", nullable=false)
     */
    private $orderDate;

    /**
     * @var float $cost
     *
     * @ORM\Column(name="cost", type="float", nullable=false)
     */   
    private $cost;

    /**
     * @var string $extra
     *
     * @ORM\Column(name="extra", type="text", nullable=true)
     */
    private $extra;

    /**
     * OWNING SIDE
     *
     * @var \Shopware\Models\Order\Order $order
     * @ORM\OneToOne(targetEntity="Shopware\Models\Order\Order")
     * @ORM\JoinColumn(name="orderID", referencedColumnName="id")
     */
    private $order;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idOrderLengow
     *
     * @param string $idOrderLengow
     * @return Order
     */
    public function setIdOrderLengow($idOrderLengow)
    {
        $this->idOrderLengow = $idOrderLengow;
        return $this;
    }

    /**
     * Get idOrderLengow
     *
     * @return string
     */
    public function getIdOrderLengow()
    {
        return $this->idOrderLengow;
    }

    /**
     * Set idFlux
     *
     * @param integer $idFlux
     * @return Order
     */
    public function setIdFlux($idFlux)
    {
        $this->idFlux = $idFlux;
        return $this;
    }

    /**
     * Get idFlux
     *
     * @return integer
     */
    public function getIdFlux()
    {
        return $this->idFlux;
    }

    /**
     * Set marketplace
     *
     * @param string $marketplace
     * @return Order
     */
    public function setMarketplace($marketplace)
    {
        $this->marketplace = $marketplace;
        return $this;
    }

    /**
     * Get marketplace
     *
     * @return string
     */
    public function getMarketplace()
    {
        return $this->marketplace;
    }

    /**
     * Set totalPaid
     *
     * @param float $totalPaid
     * @return Order
     */
    public function setTotalPaid($totalPaid)
    {
        $this->totalPaid = $totalPaid;
        return $this;
    }

    /**
     * Get totalPaid
     *
     * @return float
     */
    public function getTotalPaid()
    {
        return $this->totalPaid;
    }

    /**
     * Set carrier
     *
     * @param string $carrier
     * @return Order
     */
    public function setCarrier($carrier)
    {
        $this->carrier = $carrier;
        return $this;
    }

    /**
     * Get carrier
     *
     * @return string
     */
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * Set trackingNumber
     *
     * @param string $trackingNumber
     * @return Order
     */
    public function setTrackingNumber($trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    /**
     * Get trackingNumber
     *
     * @return string
     */
    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    /**
     * Set orderDate
     *
     * @param \DateTime $orderDate
     * @return Order
     */
    public function setOrderDate($orderDate)
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    /**
     * Get orderDate
     *
     * @return \DateTime
     */
    public function getOrderDate()
    {
        return $this->orderDate;
    }

    /**
     * Set cost
     *
     * @param float $cost
     * @return Order
     */
    public function setCost($cost)
    {
        $this->cost = $cost;
        return $this;
    }

    /**
     * Get cost
     *
     * @return float
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set extra
     *
     * @param string $extra
     * @return Order
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * Get extra
     *
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @return \Shopware\Models\Order\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

}