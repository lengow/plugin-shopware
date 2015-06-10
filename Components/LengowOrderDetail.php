<?php

/**
 * LengowOrderDetail.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowOrderDetail
{

	/**
     * Instance of Shopware Order\Detail
     */
    private $order_detail;

    /**
     * Construct a new LengowOrderDetail
     */
    public function __construct() 
    {
        $this->order_detail = new Shopware\Models\Order\Detail();
    }

    /**
     * Get Order\Detail object
     * 
     * @return object Shopware Order\Detail
     */
    public function getOrderDetail()
    {
        return $this->order_detail;
    }

    /**
     * Create a new detail order Shopware
     *
     * @param object  $order            Shopware Order\Order 
     * @param int     $productId        Product ID
     * @param int     $productdetailId  Detail Product ID
     * @param array   $dataProduct      Data product
     * @param object  $shop             Shopware Shop\Shop
     */
    public function assign($order, $productId, $productDetailId, $dataProduct, $shop)
    {
        // Get Article\Article & Article\Detail instances
        $product = Shopware()->Models()->getReference('Shopware\Models\Article\Article', (int) $productId);
        $productDetail = Shopware()->Models()->getReference('Shopware\Models\Article\Detail', (int) $productDetailId);
        // Create the name of a variation
        $detailName = '';
        $attributes = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::getOptions($productDetailId);
        foreach ($attributes as $attribute) {
            $detailName .= ' ' . $attribute['value'];
        }
        $unit = $productDetail->getUnit() ?  $productDetail->getUnit()->getName() : '';
        // Force Price option
        if (Shopware_Plugins_Backend_Lengow_Components_LengowCore::getForcePrice($shop->getId())) {
            $price = (float) $dataProduct['price_unit'];
        } else {
            $price = (float) $this->_getPrice($product, $productDetail->getId(), $shop); 
        }
        // Set all data for a new Order\Detail   
        $this->order_detail->setNumber($order->getNumber());
        $this->order_detail->setArticleId((int) $product->getId());
        $this->order_detail->setArticleNumber($productDetail->getNumber());
        $this->order_detail->setPrice($price);
        $this->order_detail->setQuantity((int) $dataProduct['quantity']);
        $this->order_detail->setArticleName($product->getName() . $detailName);
        $this->order_detail->setTaxRate((float) $product->getTax()->getTax());
        $this->order_detail->setEan($productDetail->getEan());
        $this->order_detail->setUnit($unit);
        $this->order_detail->setPackUnit($productDetail->getPackUnit());
        $this->order_detail->setTax($product->getTax());
        $this->order_detail->setOrder($order);
        $this->order_detail->setStatus($this->_getOrderDetailStatus());
        // Decreases product amount
        $productDetail->setInStock($productDetail->getInStock() - (int) $dataProduct['quantity']);
        // Persit Article\Detail & Order\Detail
        Shopware()->Models()->persist($productDetail);
        Shopware()->Models()->persist($this->order_detail);
        // Destroys variable for the next Order/Detail
        unset($this->order_detail);
        unset($product);
        unset($productDetail);
    }

    /**
     * Load Default Order\DetailStatus 
     *
     * @return object Shopware Order\DetailStatus
     */
    private function _getOrderDetailStatus()
    {
        return Shopware()->Models()->getReference('Shopware\Models\Order\DetailStatus', 0);
    }

    /**
     * Get price with tax
     *
     * @param object  $product          Shopware Article\Article
     * @param int     $idProductDetail  Id of product detail
     * @param object  $shop             Shopware Shop\Shop
     * @return float
     */
    private function _getPrice($product, $idProductDetail, $shop) 
    {
        $sqlParams["customerGroupKey"] = $shop->getCustomerGroup()->getKey();
        $sqlParams["detailId"] = $idProductDetail;
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS p.price
                FROM s_articles_prices p
                WHERE p.to = 'beliebig'
                AND p.pricegroup = :customerGroupKey
                AND p.articledetailsID = :detailId ";
        $price = (float) Shopware()->Db()->fetchOne($sql, $sqlParams);
        $tax = (float) $product->getTax()->getTax();
        return (float) $price*(100+$tax)/100;
    }

}