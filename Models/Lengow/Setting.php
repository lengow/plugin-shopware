<?php

/**
 * Setting.php
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
 * Shopware\CustomModels\Lengow\Setting
 *
 * @ORM\Entity
 * @ORM\Table(name="lengow_settings")
 */
class Setting extends ModelEntity
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
     * @var string $lengowIdGroup
     *
     * @ORM\Column(name="lengowIdGroup", type="string", length=255, nullable=true)
     */
    private $lengowIdGroup;

    /**
     * @var integer $lengowExportAllProducts
     *
     * @ORM\Column(name="lengowExportAllProducts", type="boolean", nullable=false)
     */
    private $lengowExportAllProducts;

    /**
     * @var integer $lengowExportDisabledProducts
     *
     * @ORM\Column(name="lengowExportDisabledProducts", type="boolean", nullable=false)
     */
    private $lengowExportDisabledProducts;

    /**
     * @var integer $lengowExportVariantProducts
     *
     * @ORM\Column(name="lengowExportVariantProducts", type="boolean", nullable=false)
     */
    private $lengowExportVariantProducts;

    /**
     * @var integer $lengowExportAttributes
     *
     * @ORM\Column(name="lengowExportAttributes", type="boolean", nullable=false)
     */
    private $lengowExportAttributes;

    /**
     * @var integer $lengowExportAttributesTitle
     *
     * @ORM\Column(name="lengowExportAttributesTitle", type="boolean", nullable=false)
     */
    private $lengowExportAttributesTitle;

    /**
     * @var integer $lengowExportOutStock
     *
     * @ORM\Column(name="lengowExportOutStock", type="boolean", nullable=false)
     */
    private $lengowExportOutStock;

    /**
     * @var string $lengowExportImageSize
     *
     * @ORM\Column(name="lengowExportImageSize", type="string", length=100, nullable=false)
     */
    private $lengowExportImageSize;

    /**
     * @var integer $lengowExportImages
     *
     * @ORM\Column(name="lengowExportImages", type="integer", nullable=false)
     */
    private $lengowExportImages;

    /**
     * @var string $lengowExportFormat
     *
     * @ORM\Column(name="lengowExportFormat", type="string", length=100, nullable=false)
     */
    private $lengowExportFormat;

    /**
     * @var \Shopware\Models\Dispatch\Dispatch $lengowShippingCostDefault
     * 
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Dispatch\Dispatch")
     * @ORM\JoinColumn(name="lengowShippingCostDefault", referencedColumnName="id")
     */
    private $lengowShippingCostDefault;

    /**
     * @var integer $lengowExportFile
     *
     * @ORM\Column(name="lengowExportFile", type="boolean", nullable=false)
     */
    private $lengowExportFile;

    /**
     * @var string $lengowExportUrl
     *
     * @ORM\Column(name="lengowExportUrl", type="string", length=255, nullable=false)
     */
    private $lengowExportUrl;

    /**
     * @var \Shopware\Models\Dispatch\Dispatch $lengowCarrierDefault
     * 
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Dispatch\Dispatch")
     * @ORM\JoinColumn(name="lengowCarrierDefault", referencedColumnName="id")
     */
    private $lengowCarrierDefault;

    /**
     * @var \Shopware\Models\Order\Status $lengowOrderProcess
     * 
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Order\Status")
     * @ORM\JoinColumn(name="lengowOrderProcess", referencedColumnName="id")
     */
    private $lengowOrderProcess;

    /**
     * @var \Shopware\Models\Order\Status $lengowOrderShipped
     * 
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Order\Status")
     * @ORM\JoinColumn(name="lengowOrderShipped", referencedColumnName="id")
     */
    private $lengowOrderShipped;

    /**
     * @var \Shopware\Models\Order\Status $lengowOrderCancel
     * 
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Order\Status")
     * @ORM\JoinColumn(name="lengowOrderCancel", referencedColumnName="id")
     */
    private $lengowOrderCancel;

    /**
     * @var integer $lengowImportDays
     *
     * @ORM\Column(name="lengowImportDays", type="integer", nullable=false)
     */
    private $lengowImportDays;

    /**
     * @var string $lengowMethodName
     *
     * @ORM\Column(name="lengowMethodName", type="string", length=100, nullable=false)
     */
    private $lengowMethodName;

    /**
     * @var integer $lengowForcePrice
     *
     * @ORM\Column(name="lengowForcePrice", type="boolean", nullable=false)
     */
    private $lengowForcePrice = true;

    /**
     * @var integer $lengowReportMail
     *
     * @ORM\Column(name="lengowReportMail", type="boolean", nullable=false)
     */
    private $lengowReportMail;

    /**
     * @var string $lengowEmailAddress
     *
     * @ORM\Column(name="lengowEmailAddress", type="string", length=255, nullable=true)
     */
    private $lengowEmailAddress;

    /**
     * @var string $lengowImportUrl
     *
     * @ORM\Column(name="lengowImportUrl", type="string", length=255, nullable=false)
     */
    private $lengowImportUrl;

    /**
     * @var integer $lengowExportCron
     *
     * @ORM\Column(name="lengowExportCron", type="boolean", nullable=false)
     */
    private $lengowExportCron;

    /**
     * @var integer $lengowDebug
     *
     * @ORM\Column(name="lengowDebug", type="boolean", nullable=false)
     */
    private $lengowDebug;

    /**
     * OWNING SIDE
     *
     * @var \Shopware\Models\Order\Order $shop
     * @ORM\OneToOne(targetEntity="Shopware\Models\Shop\Shop")
     * @ORM\JoinColumn(name="shopID", referencedColumnName="id")
     */
    private $shop;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set lengowIdGroup
     *
     * @param string $lengowIdGroup
     * @return Setting
     */
    public function setLengowIdGroup($lengowIdGroup)
    {
        $this->lengowIdGroup = $lengowIdGroup;
        return $this;
    }

    /**
     * Get lengowIdGroup
     *
     * @return string
     */
    public function getLengowIdGroup()
    {
        return $this->lengowIdGroup;
    }

    /**
     * Set lengowExportAllProducts
     *
     * @param bool $lengowExportAllProducts
     * @return Setting
     */
    public function setLengowExportAllProducts($lengowExportAllProducts)
    {
        $this->lengowExportAllProducts = $lengowExportAllProducts;
        return $this;
    }

    /**
     * Get lengowExportAllProducts
     *
     * @return bool
     */
    public function getLengowExportAllProducts()
    {
        return $this->lengowExportAllProducts;
    }

    /**
     * Set lengowExportDisabledProducts
     *
     * @param bool $lengowExportDisabledProducts
     * @return Setting
     */
    public function setLengowExportDisabledProducts($lengowExportDisabledProducts)
    {
        $this->lengowExportDisabledProducts = $lengowExportDisabledProducts;
        return $this;
    }

    /**
     * Get lengowExportDisabledProducts
     *
     * @return bool
     */
    public function getLengowExportDisabledProducts()
    {
        return $this->lengowExportDisabledProducts;
    }

    /**
     * Set lengowExportVariantProducts
     *
     * @param bool $lengowExportVariantProducts
     * @return Setting
     */
    public function setLengowExportVariantProducts($lengowExportVariantProducts)
    {
        $this->lengowExportVariantProducts = $lengowExportVariantProducts;
        return $this;
    }

    /**
     * Get lengowExportVariantProducts
     *
     * @return bool
     */
    public function getLengowExportVariantProducts()
    {
        return $this->lengowExportVariantProducts;
    }

    /**
     * Set lengowExportAttributes
     *
     * @param bool $lengowExportAttributes
     * @return Setting
     */
    public function setLengowExportAttributes($lengowExportAttributes)
    {
        $this->lengowExportAttributes = $lengowExportAttributes;
        return $this;
    }

    /**
     * Get lengowExportAttributes
     *
     * @return bool
     */
    public function getLengowExportAttributes()
    {
        return $this->lengowExportAttributes;
    }

    /**
     * Set lengowExportAttributesTitle
     *
     * @param bool $lengowExportAttributesTitle
     * @return Setting
     */
    public function setLengowExportAttributesTitle($lengowExportAttributesTitle)
    {
        $this->lengowExportAttributesTitle = $lengowExportAttributesTitle;
        return $this;
    }

    /**
     * Get lengowExportAttributesTitle
     *
     * @return bool
     */
    public function getLengowExportAttributesTitle()
    {
        return $this->lengowExportAttributesTitle;
    }

    /**
     * Set lengowExportOutStock
     *
     * @param bool $lengowExportOutStock
     * @return Setting
     */
    public function setLengowExportOutStock($lengowExportOutStock)
    {
        $this->lengowExportOutStock = $lengowExportOutStock;
        return $this;
    }

    /**
     * Get lengowExportOutStock
     *
     * @return bool
     */
    public function getLengowExportOutStock()
    {
        return $this->lengowExportOutStock;
    }

    /**
     * Set lengowExportImageSize
     *
     * @param string $lengowExportImageSize
     * @return Setting
     */
    public function setLengowExportImageSize($lengowExportImageSize)
    {
        $this->lengowExportImageSize = $lengowExportImageSize;
        return $this;
    }

    /**
     * Get lengowExportImageSize
     *
     * @return string
     */
    public function getLengowExportImageSize()
    {
        return $this->lengowExportImageSize;
    }

    /**
     * Set lengowExportImages
     *
     * @param int $lengowExportImages
     * @return Setting
     */
    public function setLengowExportImages($lengowExportImages)
    {
        $this->lengowExportImages = $lengowExportImages;
        return $this;
    }

    /**
     * Get lengowExportImages
     *
     * @return int
     */
    public function getLengowExportImages()
    {
        return $this->lengowExportImages;
    }

    /**
     * Set lengowExportFormat
     *
     * @param string $lengowExportFormat
     * @return Setting
     */
    public function setLengowExportFormat($lengowExportFormat)
    {
        $this->lengowExportFormat = $lengowExportFormat;
        return $this;
    }

    /**
     * Get lengowExportFormat
     *
     * @return string
     */
    public function getLengowExportFormat()
    {
        return $this->lengowExportFormat;
    }

    /**
     * Set lengowShippingCostDefault
     *
     * @param \Shopware\Models\Dispatch\Dispatch $lengowShippingCostDefault
     * @return Setting
     */
    public function setLengowShippingCostDefault($lengowShippingCostDefault)
    {
        $this->lengowShippingCostDefault = $lengowShippingCostDefault;
        return $this;
    }

    /**
     * Get lengowShippingCostDefault
     *
     * @return \Shopware\Models\Dispatch\Dispatch
     */
    public function getLengowShippingCostDefault()
    {
        return $this->lengowShippingCostDefault;
    }

    /**
     * Set lengowExportFile
     *
     * @param bool $lengowExportFile
     * @return Setting
     */
    public function setLengowExportFile($lengowExportFile)
    {
        $this->lengowExportFile = $lengowExportFile;
        return $this;
    }

    /**
     * Get lengowExportFile
     *
     * @return bool
     */
    public function getLengowExportFile()
    {
        return $this->lengowExportFile;
    }

    /**
     * Set lengowExportUrl
     *
     * @param string $lengowExportUrl
     * @return Setting
     */
    public function setLengowExportUrl($lengowExportUrl)
    {
        $this->lengowExportUrl = $lengowExportUrl;
        return $this;
    }

    /**
     * Get lengowExportUrl
     *
     * @return string
     */
    public function getLengowExportUrl()
    {
        return $this->lengowExportUrl;
    }

    /**
     * Set lengowCarrierDefault
     *
     * @param \Shopware\Models\Dispatch\Dispatch $lengowCarrierDefault
     * @return Setting
     */
    public function setLengowCarrierDefault($lengowCarrierDefault)
    {
        $this->lengowCarrierDefault = $lengowCarrierDefault;
        return $this;
    }

    /**
     * Get lengowCarrierDefault
     *
     * @return \Shopware\Models\Dispatch\Dispatch
     */
    public function getLengowCarrierDefault()
    {
        return $this->lengowCarrierDefault;
    }

    /**
     * Set lengowOrderProcess
     *
     * @param \Shopware\Models\Order\Status $lengowOrderProcess
     * @return Setting
     */
    public function setLengowOrderProcess($lengowOrderProcess)
    {
        $this->lengowOrderProcess = $lengowOrderProcess;
        return $this;
    }

    /**
     * Get lengowOrderProcess
     *
     * @return \Shopware\Models\Order\Status
     */
    public function getLengowOrderProcess()
    {
        return $this->lengowOrderProcess;
    }

    /**
     * Set lengowOrderShipped
     *
     * @param \Shopware\Models\Order\Status $lengowOrderShipped
     * @return Setting
     */
    public function setLengowOrderShipped($lengowOrderShipped)
    {
        $this->lengowOrderShipped = $lengowOrderShipped;
        return $this;
    }

    /**
     * Get lengowOrderShipped
     *
     * @return \Shopware\Models\Order\Status
     */
    public function getLengowOrderShipped()
    {
        return $this->lengowOrderShipped;
    }

    /**
     * Set lengowOrderCancel
     *
     * @param \Shopware\Models\Order\Status $lengowOrderCancel
     * @return Setting
     */
    public function setLengowOrderCancel($lengowOrderCancel)
    {
        $this->lengowOrderCancel = $lengowOrderCancel;
        return $this;
    }

    /**
     * Get lengowOrderCancel
     *
     * @return \Shopware\Models\Order\Status
     */
    public function getLengowOrderCancel()
    {
        return $this->lengowOrderCancel;
    }

    /**
     * Set lengowImportDays
     *
     * @param int $lengowImportDays
     * @return Setting
     */
    public function setLengowImportDays($lengowImportDays)
    {
        $this->lengowImportDays = $lengowImportDays;
        return $this;
    }

    /**
     * Get lengowImportDays
     *
     * @return int
     */
    public function getLengowImportDays()
    {
        return $this->lengowImportDays;
    }

    /**
     * Set lengowMethodName
     *
     * @param string $lengowMethodName
     * @return Setting
     */
    public function setLengowMethodName($lengowMethodName)
    {
        $this->lengowMethodName = $lengowMethodName;
        return $this;
    }

    /**
     * Get lengowMethodName
     *
     * @return string
     */
    public function getLengowMethodName()
    {
        return $this->lengowMethodName;
    }

    /**
     * Set lengowForcePrice
     *
     * @param bool $lengowForcePrice
     * @return Setting
     */
    public function setLengowForcePrice($lengowForcePrice)
    {
        $this->lengowForcePrice = $lengowForcePrice;
        return $this;
    }

    /**
     * Get lengowForcePrice
     *
     * @return bool
     */
    public function getLengowForcePrice()
    {
        return $this->lengowForcePrice;
    }

    /**
     * Set lengowReportMail
     *
     * @param bool $lengowReportMail
     * @return Setting
     */
    public function setLengowReportMail($lengowReportMail)
    {
        $this->lengowReportMail = $lengowReportMail;
        return $this;
    }

    /**
     * Get lengowReportMail
     *
     * @return bool
     */
    public function getLengowReportMail()
    {
        return $this->lengowReportMail;
    }

    /**
     * Set lengowEmailAddress
     *
     * @param string $lengowEmailAddress
     * @return Setting
     */
    public function setLengowEmailAddress($lengowEmailAddress)
    {
        $this->lengowEmailAddress = $lengowEmailAddress;
        return $this;
    }

    /**
     * Get lengowEmailAddress
     *
     * @return string
     */
    public function getLengowEmailAddress()
    {
        return $this->lengowEmailAddress;
    }

    /**
     * Set lengowImportUrl
     *
     * @param string $lengowImportUrl
     * @return Setting
     */
    public function setLengowImportUrl($lengowImportUrl)
    {
        $this->lengowImportUrl = $lengowImportUrl;
        return $this;
    }

    /**
     * Get lengowImportUrl
     *
     * @return string
     */
    public function getLengowImportUrl()
    {
        return $this->lengowImportUrl;
    }

    /**
     * Set lengowExportCron
     *
     * @param bool $lengowExportCron
     * @return Setting
     */
    public function setLengowExportCron($lengowExportCron)
    {
        $this->lengowExportCron = $lengowExportCron;
        return $this;
    }

    /**
     * Get lengowExportCron
     *
     * @return bool
     */
    public function getLengowExportCron()
    {
        return $this->lengowExportCron;
    }

    /**
     * Set lengowDebug
     *
     * @param bool $lengowDebug
     * @return Setting
     */
    public function setLengowDebug($lengowDebug)
    {
        $this->lengowDebug = $lengowDebug;
        return $this;
    }

    /**
     * Get lengowDebug
     *
     * @return bool
     */
    public function getLengowDebug()
    {
        return $this->lengowDebug;
    }

    /**
     * @param \Shopware\Models\Shop\Shop $shop
     */
    public function setShop($shop)
    {
        $this->shop = $shop;
        return $this;
    }

    /**
     * @return \Shopware\Models\Shop\Shop
     */
    public function getShop()
    {
        return $this->shop;
    }

}