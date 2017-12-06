<?php

/**
 * Action.php
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
 * Shopware\CustomModels\Lengow\Action
 *
 * @ORM\Entity
 * @ORM\Table(name="s_lengow_action")
 */
class Action extends ModelEntity
{
    /**
     * @var integer
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
     * @ORM\Column(name="action_id", type="integer", nullable=false)
     */
    private $actionId;

    /**
     * @var string
     *
     * @ORM\Column(name="order_line_sku", type="string", length=100, nullable=true)
     */
    private $orderLineSku = null;

    /**
     * @var string
     *
     * @ORM\Column(name="action_type", type="string", length=32, nullable=false)
     */
    private $actionType;

    /**
     * @var integer
     *
     * @ORM\Column(name="retry", type="integer", nullable=false)
     */
    private $retry = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text", nullable=false)
     */
    private $parameters;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer", nullable=false)
     */
    private $state;

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
     * Gets the value of order
     *
     * @return \Shopware\Models\Order\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Sets the value of order
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
     * Gets the value of action id side Lengow
     *
     * @return integer
     */
    public function getActionId()
    {
        return $this->actionId;
    }

    /**
     * Sets the value of action id side Lengow
     *
     * @param integer $actionId the action id side Lengow
     *
     * @return self
     */
    public function setActionId($actionId)
    {
        $this->actionId = $actionId;
        return $this;
    }

    /**
     * Gets the value of order line sku
     *
     * @return string
     */
    public function getOrderLineSku()
    {
        return $this->orderLineSku;
    }

    /**
     * Sets the value of order line sku
     *
     * @param string $orderLineSku the order line sku
     *
     * @return self
     */
    public function setOrderLineSku($orderLineSku)
    {
        $this->orderLineSku = $orderLineSku;
        return $this;
    }

    /**
     * Gets the value of action type
     *
     * @return string
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * Sets the value of action type
     *
     * @param string $actionType the action type
     *
     * @return self
     */
    public function setActionType($actionType)
    {
        $this->actionType = $actionType;
        return $this;
    }

    /**
     * Gets the value of retry
     *
     * @return integer
     */
    public function getRetry()
    {
        return $this->retry;
    }

    /**
     * Sets the value of retry
     *
     * @param integer $retry number of retry action
     *
     * @return self
     */
    public function setRetry($retry)
    {
        $this->retry = $retry;
        return $this;
    }

    /**
     * Gets the value of action parameters
     *
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Sets the value of action parameters
     *
     * @param string $parameters the action parameters
     *
     * @return self
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Gets the value of state action
     *
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Sets the value of state action
     *
     * @param integer $state state action
     *
     * @return self
     */
    public function setState($state)
    {
        $this->state = $state;
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
     * Gets the value of update at
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Sets the value of update at
     *
     * @param \DateTime $updatedAt the created at
     *
     * @return self
     */
    public function setUpdatedDate($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
