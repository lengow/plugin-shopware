<?php

/**
 * LengowProduct.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowProduct 
{

    /**
     * Instance of article
     */
    public $product = null;

    /**
     * Instance of article_detail, the main detail
     */
    public $detail_product = null;

    /**
     * Instance of article_detail
     */
    public $variant_product = null;

    /**
    * Images of produtcs
    */
    public $images;


    public function __construct($id_product = null, $id_variation = null) {

        $this->product = Shopware()->Models()->find('Shopware\Models\Article\Article', $id_product);
        $this->detail_product = Shopware()->Models()->find('Shopware\Models\Article\Detail', $this->product->getMainDetail()->getId());
        if ($id_variation) {
            $this->variant_product = Shopware()->Models()->find('Shopware\Models\Article\Detail', $id_variation);
        } 

        if ($id_variation) {
            $images = $this->variant_product->getImages();
        } else {
            $images = $this->product->getImages();
        }
        $array_images = array();
        foreach ($images as $image)
        {
            $array_images[] = $image;
        }
        $this->images = $array_images;
    }

    /**
     * Get data of product
     *
     * @param string $name
     * @return string the data
     */
    public function getData($name, $id_variation = null) {
        switch ($name) {
            case 'id_article':
            	if ($id_variation) {
            		return $this->product->getId().'_'.$this->variant_product->getId();
            	} else {
                    return $this->product->getId();
                }
                break;
            case 'name_article':
                if ($id_variation) {
                    return $this->product->getName().' - '.$this->variant_product->getAdditionalText();
                } else {
                    return $this->product->getName();
                }
                break;
            case 'number_article':
                if ($id_variation) {
                    return $this->variant_product->getNumber();
                } else {
                    return $this->detail_product->getNumber();
                }
                break;
            case 'supplier':
                return $this->product->getSupplier()->getName();
                break;
            case 'price':
                $sqlParams = array();
                $sql = "
                    SELECT DISTINCT SQL_CALC_FOUND_ROWS
                    prices.price as price
                    FROM s_articles_prices prices
                    WHERE prices.`to`= 'beliebig'
                    AND prices.pricegroup='EK'
                    AND prices.articledetailsID = :detailId
                ";
                if ($id_variation) {
                    $sqlParams["detailId"] = $this->variant_product->getId();
                    $price = Shopware()->Db()->fetchOne($sql, $sqlParams);
                    return round($price, 2);
                } else {
                    $sqlParams["detailId"] = $this->detail_product->getId();
                    $price = Shopware()->Db()->fetchOne($sql, $sqlParams);
                    return round($price, 2);
                }
                break;
            case 'price_wt':
                $tax = $this->product->getTax()->getTax();
                $sqlParams = array();
                $sql = "
                    SELECT DISTINCT SQL_CALC_FOUND_ROWS
                    prices.price as price
                    FROM s_articles_prices prices
                    WHERE prices.`to`= 'beliebig'
                    AND prices.pricegroup='EK'
                    AND prices.articledetailsID = :detailId
                ";
                if ($id_variation) {
                    $sqlParams["detailId"] = $this->variant_product->getId();
                    $price = Shopware()->Db()->fetchOne($sql, $sqlParams);
                    return $price*(100+$tax)/100;
                } else {
                    $sqlParams["detailId"] = $this->detail_product->getId();
                    $price = Shopware()->Db()->fetchOne($sql, $sqlParams);
                    return $price*(100+$tax)/100;
                }
                break;
            case 'tax':
                return (float) $this->product->getTax()->getTax();
                break;
            case 'in_stock':
                if ($id_variation) {
                    return ($this->variant_product->getInStock() > 0 ? 1 : 0);
                } else {
                    return ($this->detail_product->getInStock() > 0 ? 1 : 0);
                }
                break;
            case 'weight':
                if ($id_variation) {
                    return $this->variant_product->getWeight();
                } else {
                    return $this->detail_product->getWeight();
                }
                break;
            case 'description':
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($this->product->getDescription());
                break;
            case 'long_description':
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($this->product->getDescriptionLong());
                break;
            case 'url_article':
                return 'url_article';
                break;
            case 'category':
                return 'cataegory';
                break;
            case 'available_product':
                if($this->product->getActive()) {
                    if ($id_variation) {
                        return ($this->variant_product->getActive() ? 1 : 0);
                    } else {
                        return ($this->detail_product->getActive() ? 1 : 0);
                    }
                } else {
                    return 0;
                }
                break;
            case 'quantity':
                if ($id_variation) {
                    return ($this->variant_product->getInStock() > 0 ? $this->variant_product->getInStock() : 0);
                } else {
                    return ($this->detail_product->getInStock() > 0 ? $this->detail_product->getInStock() : 0);
                }
                break;
            case 'unit':
                if ($id_variation) {
                    return ($this->variant_product->getUnit() ? $this->variant_product->getUnit()->getUnit() : '' );
                } else {
                    return ($this->detail_product->getUnit() ? $this->detail_product->getUnit()->getUnit() : '' );
                }
                break;
            case 'unit_reference':
                if ($id_variation) {
                    return ($this->variant_product->getReferenceUnit() ? $this->variant_product->getReferenceUnit() : '' );
                } else {
                    return ($this->detail_product->getReferenceUnit() ? $this->detail_product->getReferenceUnit() : '' );
                }
                break;
            case 'unit_pack':
                if ($id_variation) {
                    return ($this->variant_product->getPackUnit() ? $this->variant_product->getPackUnit() : '' );
                } else {
                    return ($this->detail_product->getPackUnit() ? $this->detail_product->getPackUnit() : '' );
                }
                break;
            case 'unit_purchase':
                if ($id_variation) {
                    return ($this->variant_product->getPurchaseUnit() ? $this->variant_product->getPurchaseUnit() : '' );
                } else {
                    return ($this->detail_product->getPurchaseUnit() ? $this->detail_product->getPurchaseUnit() : '' );
                }
                break;
            case 'min_purchase':
                if ($id_variation) {
                    return ($this->variant_product->getMinPurchase() ? $this->variant_product->getMinPurchase() : '' );
                } else {
                    return ($this->detail_product->getMinPurchase() ? $this->detail_product->getMinPurchase() : '' );
                }
                break;
            case 'max_purchase':
                if ($id_variation) {
                    return ($this->variant_product->getMaxPurchase() ? $this->variant_product->getMaxPurchase() : '' );
                } else {
                    return ($this->detail_product->getMaxPurchase() ? $this->detail_product->getMaxPurchase() : '' );
                }
                break;
            case 'shipping_time':
                if ($id_variation) {
                    return $this->variant_product->getShippingTime();
                } else {
                    return $this->detail_product->getShippingTime();
                }
                break;
            case 'ean':
                if ($id_variation) {
                    return ($this->variant_product->getEan() ? $this->variant_product->getEan() : '' );
                } else {
                    return ($this->detail_product->getEan() ? $this->detail_product->getEan() : '' );
                }
                break;
            case 'width':
                if ($id_variation) {
                    return ($this->variant_product->getWidth() ? $this->variant_product->getWidth() : '' );
                } else {
                    return ($this->detail_product->getWidth() ? $this->detail_product->getWidth() : '' );
                }
                break;
            case 'height':
                if ($id_variation) {
                    return ($this->variant_product->getHeight() ? $this->variant_product->getHeight() : '' );
                } else {
                    return ($this->detail_product->getHeight() ? $this->detail_product->getHeight() : '' );
                }
                break;
            case 'length':
                if ($id_variation) {
                    return ($this->variant_product->getLen() ? $this->variant_product->getLen() : '' );
                } else {
                    return ($this->detail_product->getLen() ? $this->detail_product->getLen() : '' );
                }
                break;
            case 'id_parent':
                return $this->product->getId();
                break;
            case 'image':
                return $this->images;
                break;
            case (preg_match('`image_([0-9]+)`', $name) ? true : false):
                $index = explode('_', $name);
                $index = $index[1];
                $imagePath = '';
                if(isset($this->images[$index - 1]) && $this->images[$index - 1]) {
                    if($this->images[$index - 1]->getMedia() !== null) {
                        $imagePath = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $this->images[$index - 1]->getMedia()->getPath();
                    }
                    return $imagePath;
                }
                break;
            default:
                break;
        }
    }

    /**
	* Get the products to export.
	*
	* @return varchar IDs product.
	*/
	public static function getExportIds($all = true, $all_product = false, $shop = null)
	{
		if ($shop) {
            $id_shop = $shop->getCategory()->getId();
            $LengowProduct = '';
            $filterSql = 'WHERE ac.categoryID = ' . $id_shop . ' ';

            if ($all_product == false) {
                $filterSql .= ' AND article.active = 1 ';
            }
            if ($all == false) {
                $LengowProduct = ' LEFT JOIN s_articles_attributes AS attributes ON attributes.articleID = article.id ';
                $filterSql .= ' AND attributes.lengow_lengowActive = 1 ';
            }
            $sql = '
                SELECT DISTINCT SQL_CALC_FOUND_ROWS article.id as article
                FROM s_articles as article
                LEFT JOIN s_articles_categories_ro ac
                ON ac.articleID = article.id '
                . $LengowProduct
                . $filterSql
                . 'ORDER BY article.id ASC
            ';

            return Shopware()->Db()->fetchAll($sql);
        } else {
            return array();
        }     		
	}

    /**
     * Get the ID product
     *
     * @return int id
     */
    public function getId() {
        return $this->product->getId();
    }

    /**
     * Get Configuration set for variation of one product
     *
     * @return integer
     */
    public function getConfiguratorSet() {
        return $this->product->getConfiguratorSet();
    }

    /**
     * Get all variations product
     *
     * @return 
     */
    public function getDetails() {
        return $this->product->getDetails();
    }

    /**
    * Get max number of images
    *
    * @return int max number of images for one product
    */
    public function getMaxImages()
    {
        $images = $this->product->getImages();
        return count($images);
    }
 
 
}