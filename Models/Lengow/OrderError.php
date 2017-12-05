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
 * Shopware\CustomModels\Lengow\OrderError
 *
 * @ORM\Entity
 * @ORM\Table(name="s_lengow_order_error")
 */
class OrderError extends ModelEntity
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
     * @ORM\Column(name="lengow_order_id", type="integer", nullable=false)
     */
    private $lengowOrderId;

    /**
     * @var \Shopware\CustomModels\Lengow\Order
     *
     * @ORM\ManyToOne(targetEntity="\Shopware\CustomModels\Lengow\Order")
     * @ORM\JoinColumn(name="lengow_order_id", referencedColumnName="id")
     */
    protected $lengowOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message = null;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_finished", type="boolean", nullable=false)
     */
    private $isFinished = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="mail", type="boolean", nullable=false)
     */
    private $mail = false;

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
     * Gets the value of Lengow order
     *
     * @return \Shopware\CustomModels\Lengow\Order
     */
    public function getLengowOrder()
    {
        return $this->lengowOrder;
    }

    /**
     * Sets the value of Lengow order
     *
     * @param \Shopware\CustomModels\Lengow\Order $lengowOrder Lengow order instance
     *
     * @return self
     */
    public function setLengowOrder($lengowOrder)
    {
        $this->lengowOrder = $lengowOrder;
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
     * @param string $message the order error message
     *
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Gets the value of type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the value of type
     *
     * @param integer $type order error type (1 == import / 2 == action)
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Gets the value of is finished
     *
     * @return boolean
     */
    public function isFinished()
    {
        return $this->isFinished;
    }

    /**
     * Sets the value of is finished
     *
     * @param boolean $isFinished order is finished or not
     *
     * @return self
     */
    public function setIsFinished($isFinished)
    {
        $this->isFinished = $isFinished;
        return $this;
    }

    /**
     * Gets the value of mail
     *
     * @return boolean
     */
    public function isMail()
    {
        return $this->mail;
    }

    /**
     * Sets the value of mail
     *
     * @param boolean $mail mail is sent or not
     *
     * @return self
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
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
