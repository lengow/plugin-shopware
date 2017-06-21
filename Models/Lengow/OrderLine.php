<?php

/**
 * OrderLine.php
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
 * Shopware\CustomModels\Lengow\OrderLine
 *
 * @ORM\Entity
 * @ORM\Table(name="s_lengow_order_line")
 */
class OrderLine extends ModelEntity
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
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    private $orderId;

    /**
     * @var \Shopware\Models\Order\Order
     *
     * @ORM\ManyToOne(targetEntity="\Shopware\Models\Order\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    protected $order;

    /**
     * @var integer
     *
     * @ORM\Column(name="detail_id", type="integer", nullable=false)
     */
    private $detailId;

    /**
     * @var \Shopware\Models\Article\Detail
     *
     * @ORM\ManyToOne(targetEntity="\Shopware\Models\Article\Detail")
     * @ORM\JoinColumn(name="detail_id", referencedColumnName="id")
     */
    protected $detail;

    /**
     * @var string
     *
     * @ORM\Column(name="order_line_id", type="string", length=100, nullable=false)
     */
    private $orderLineId;

    /**
     * Gets the value of id
     *
     * @return integer
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
     * Gets the value of Shopware order
     *
     * @return \Shopware\Models\Order\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Sets the value of Shopware order
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
     * Gets the value of Shopware detail
     *
     * @return \Shopware\Models\Article\Detail
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * Sets the value of Shopware detail
     *
     * @param \Shopware\Models\Article\Detail $detail Shopware detail instance
     *
     * @return self
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }

    /**
     * Gets the value of order line id
     *
     * @return string
     */
    public function getOrderLineId()
    {
        return $this->orderLineId;
    }

    /**
     * Sets the value of order line id
     *
     * @param string $orderLineId Lengow order line id
     *
     * @return self
     */
    public function setOrderLineId($orderLineId)
    {
        $this->orderLineId = $orderLineId;
        return $this;
    }
}
