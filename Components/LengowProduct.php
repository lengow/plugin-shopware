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
    private $product = null;

    /**
     * Instance of article_detail, the main detail
     */
    private $detail_product = null;

    /**
     * Instance of article_detail
     */
    private $variant_product = null;

    /**
     * Instance of shop, the article shop
     */
    private $shop = null;

    /**
    * Images of produtcs
    */
    private $images;

    /**
    * Construct new Lengow product
    */
    public function __construct($id_product = null, $id_variation = null, $shop = null) 
    {
        // Get the product and its detail
        $this->product = Shopware()->Models()->getReference('Shopware\Models\Article\Article', (int) $id_product);
        $this->detail_product = Shopware()->Models()->getReference('Shopware\Models\Article\Detail', (int) $this->product->getMainDetail()->getId());
        // Get the variation product
        if ($id_variation) {
            $this->variant_product = Shopware()->Models()->getReference('Shopware\Models\Article\Detail', (int) $id_variation);
        } 
        // Get images of a product
        if ($id_variation) {
            $refImages = $this->variant_product->getImages();
            $images = array();
            foreach ($refImages as $ref) {
                $images[] = $ref->getParent();
            }
        } else {
            $images = $this->product->getImages();
        }
        $array_images = array();
        foreach ($images as $image) {
            $array_images[] = $image;
        }
        $this->images = $array_images;
        // Get the shop to export
        $this->shop = $shop;
    }

    /**
     * Get data of product
     * 
     * @param string $name
     * @param int $id_variation
     * @return string the data
     */
    public function getData($name, $id_variation = null)
    {
        switch ($name) {
            case 'id_article':
            	if ($id_variation) {
            		return $this->product->getId().'_'.$this->variant_product->getId();
            	} else {
                    return $this->product->getId();
                }
                break;
            case 'name_article':
                if ($id_variation && Shopware_Plugins_Backend_Lengow_Components_LengowCore::exportTitle($this->shop->getId())) {
                    $variationName = '';
                    $values = $this->_getOptions($id_variation);
                    foreach ($values as $value) {
                        $variationName .= ' - ' . $value['name'] . ' : ' . $value['value'];
                    }
                    return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($this->product->getName() . $variationName);
                } else {
                    return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($this->product->getName());
                }
                break;
            case 'number_article':
                if ($id_variation) {
                    return $this->variant_product->getNumber();
                } else {
                    return $this->detail_product->getNumber();
                }
                break;
            case 'manufacturer_number':
                if ($id_variation) {
                    return $this->variant_product->getSupplierNumber();
                } else {
                    return $this->detail_product->getSupplierNumber();
                }
                break;
            case 'supplier':
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($this->product->getSupplier()->getName());
                break;
             case 'category':
                $idCategoryParent = $this->shop->getCategory()->getId();
                $categories = $this->product->getCategories();
                foreach($categories as $category) {
                    $pathCategory = explode("|",$category->getPath());

                    if(in_array($idCategoryParent, $pathCategory)) {
                        $breadcrumb = $category->getName();
                        $idCategory = (int) $category->getParentId();
                        for ($i=0; $i < count($pathCategory) - 2 ; $i++) { 
                            $category = Shopware()->Models()->getReference('Shopware\Models\Category\Category',(int) $idCategory);
                            $breadcrumb = $category->getName() . ' > ' . $breadcrumb;
                            $idCategory = (int) $category->getParentId();
                        }
                        break;
                    }
                }
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::replaceAccentedChars($breadcrumb);
                break;
            case 'category_parent':
                $idCategoryParent = $this->shop->getCategory()->getId();
                $categoryParent = Shopware()->Models()->getReference('Shopware\Models\Category\Category',(int) $idCategoryParent);
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($categoryParent->getName());
                break;
            case 'price':
                if ($id_variation) {
                    $price = (float) $this->_getPriceField('price', $id_variation);;
                } else {
                    $price = (float) $this->_getPriceField('price');
                }
                return round($price, 2);
                break;
            case 'price_wt':
                $tax = $this->product->getTax()->getTax();
                if ($id_variation) {
                    $price = (float) $this->_getPriceField('price', $id_variation);
                } else {
                    $price = (float) $this->_getPriceField('price');
                }
                return round($price*(100+$tax)/100, 2);
                break;
            case 'price_discount':
                $tax = $this->product->getTax()->getTax();
                if ($id_variation) {
                    $percentDiscount = (float) $this->_getPriceField('percent', $id_variation);
                    $price = (float) $this->_getPriceField('price', $id_variation);
                } else {
                    $percentDiscount = (float) $this->_getPriceField('percent');
                    $price = (float) $this->_getPriceField('price');
                }
                $priceWt = $price*(100+$tax)/100;
                return round($priceWt*(100-$percentDiscount)/100, 2);
                break;
            case 'percent_discount':
                if ($id_variation) {
                    return (float) $this->_getPriceField('percent', $id_variation); 
                } else {
                    return (float) $this->_getPriceField('percent');
                }
                break;
            case 'purchase_price':
                if ($id_variation) {
                    $purchasePrice = (float) $this->_getPriceField('baseprice', $id_variation); 
                } else {
                    $purchasePrice = (float) $this->_getPriceField('baseprice');
                }
                return round($purchasePrice, 2);
                break;
            case 'tax':
                return (float) $this->product->getTax()->getTax();
                break;
            case 'currency':
                return $this->shop->getCurrency()->getName();
                break;
            case 'available_article':
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
            case 'in_stock':
                if ($id_variation) {
                    return ($this->variant_product->getInStock() > 0 ? 1 : 0);
                } else {
                    return ($this->detail_product->getInStock() > 0 ? 1 : 0);
                }
                break;
                        case 'quantity':
                if ($id_variation) {
                    return ($this->variant_product->getInStock() > 0 ? $this->variant_product->getInStock() : 0);
                } else {
                    return ($this->detail_product->getInStock() > 0 ? $this->detail_product->getInStock() : 0);
                }
                break;
            case 'ean':
                if ($id_variation) {
                    return ($this->variant_product->getEan() ? $this->variant_product->getEan() : '' );
                } else {
                    return ($this->detail_product->getEan() ? $this->detail_product->getEan() : '' );
                }
                break;
            case 'url_article':
                $sep = '/';
                $idProduct = $this->product->getId();
                $host = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getBaseUrl();
                $baseUrl = ($this->shop->getBaseUrl() ? $this->shop->getBaseUrl() : '');
                $idCategoryParent = $this->shop->getCategory()->getId();
                $categories = $this->product->getCategories();
                foreach($categories as $category) {
                    $pathCategory = explode("|",$category->getPath());
                    if(in_array($idCategoryParent, $pathCategory)) {
                        $idCategory = $category->getId();
                        break;
                    }
                }
                return $host . $baseUrl .$sep.'detail'.$sep.'index'.$sep.'sArticle'.$sep.$idProduct.$sep.'sCategory'.$sep.$idCategory; 
                break;  
            case 'meta_title':
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($this->product->getMetaTitle());
                break;
            case 'meta_keywords':
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($this->product->getKeywords());
                break;
            case 'description':
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($this->product->getDescription());
                break;
            case 'long_description':
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($this->product->getDescriptionLong());
                break;   
            case 'unit':
                if ($id_variation) {
                    return ($this->variant_product->getUnit() ? $this->variant_product->getUnit()->getName() : '' );
                } else {
                    return ($this->detail_product->getUnit() ? $this->detail_product->getUnit()->getName() : '' );
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
            case 'shipping_price':
                // Get the default dispatch
                $dispatch = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getDefaultShippingCost($this->shop->getId()); 
                $shippingPrice  = 0;
                $weight         = 0;
                $price          = 0;
                // Get the weight and the price of a product
                if ($id_variation) {
                    if ($this->variant_product->getShippingFree()) {
                        return $shippingPrice;
                    }
                    $weight = (float) $this->variant_product->getWeight();
                    $price = round((float) $this->_getPriceField('price', $id_variation), 2);
                } else {
                    if ($this->detail_product->getShippingFree()) {
                        return $shippingPrice;
                    }
                    $weight = (float) $this->detail_product->getWeight();
                    $price = round((float) $this->_getPriceField('price'), 2);
                }
                // Get the calculation base (0->weight, 1->price, 2->quantity, 3->calculation)
                $calculation = $dispatch->getCalculation();
                if ($calculation === 0) {
                    $value = $weight;
                } elseif ($calculation === 1 || $calculation === 3) {
                    $value = $price;
                } else {
                    $value = 1;
                }
                // Calculation of shipping costs
                if($dispatch->getShippingFree() && $price >= $dispatch->getShippingFree()) {
                    $shippingPrice = 0; 
                } 
                else { 
                    if ($dispatch->getCostsMatrix()) {
                        $shippingCosts = $dispatch->getCostsMatrix();
                        for ($i=0; $i < count($shippingCosts) ; $i++) {
                            if ($value >= $shippingCosts[$i]->getFrom()) {
                                $shippingPrice = $shippingCosts[$i]->getValue();
                            }
                        }
                    }          
                }
                return $shippingPrice;
                break;
            case 'weight':
                if ($id_variation) {
                    return (float) $this->variant_product->getWeight();
                } else {
                    return (float) $this->detail_product->getWeight();
                }
                break;
            case 'width':
                if ($id_variation) {
                    return ($this->variant_product->getWidth() ? (float) $this->variant_product->getWidth() : '' );
                } else {
                    return ($this->detail_product->getWidth() ? (float) $this->detail_product->getWidth() : '' );
                }
                break;
            case 'height':
                if ($id_variation) {
                    return ($this->variant_product->getHeight() ? (float) $this->variant_product->getHeight() : '' );
                } else {
                    return ($this->detail_product->getHeight() ? (float) $this->detail_product->getHeight() : '' );
                }
                break;
            case 'length':
                if ($id_variation) {
                    return ($this->variant_product->getLen() ? (float) $this->variant_product->getLen() : '' );
                } else {
                    return ($this->detail_product->getLen() ? (float) $this->detail_product->getLen() : '' );
                }
                break;
            case 'type_article':
                if ($id_variation) {
                    return 'Variant';
                } else {
                    if ($this->product->getConfiguratorSet() !== NULL) {
                        return 'Parent';
                    } else {
                        return 'Simple';
                    }
                }
                break;
            case 'id_parent':
                return $this->product->getId();
                break;
            case 'variant_article':
                $variantOption = '';
                if ($id_variation) {               
                    $idProduct = $id_variation;
                } else {
                    if ($this->product->getConfiguratorSet() !== NULL) {
                        $idProduct = $this->detail_product->getId();
                    } else {
                        $idProduct = $this->product->getId();
                    }
                }
                $values = $this->_getOptions($idProduct);
                foreach ($values as $value) {
                    $variantOption .= $value['name'] . ' - ';
                }   
                Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml($variantOption);
                return rtrim($variantOption, ' - ');
                break;
            case (preg_match('`image_([0-9]+)`', $name) ? true : false):
                $sep = '/';
                $index = explode('_', $name);
                $index = $index[1];
                $imagePath = '';
                $size = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportImagesSize($this->shop->getId());
                if(isset($this->images[$index - 1]) && $this->images[$index - 1]) {
                    if($this->images[$index - 1]->getMedia() !== null) {
                        $thumbnailPaths = $this->images[$index - 1]->getMedia()->getThumbnailFilePaths();
                        $host = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getBaseUrl();
                        $path = $thumbnailPaths[$size];
                        $imagePath = $host . $sep . $path;
                    }
                }
                return $imagePath;
                break;
            default:
                break;
        }
    }

    /**
	* Get products to export
    * 
    * @param boolean $all
    * @param boolean $all_products
    * @param Object $shop
	* @return varchar IDs product
	*/
	public static function getExportIds($all = true, $all_products = false, $shop = null)
	{
		if ($shop) {
            $id_shop = $shop->getCategory()->getId();
            $LengowProduct = '';
            $filterSql = 'WHERE ac.categoryID = ' . $id_shop . ' ';

            if ($all_products == false) {
                $filterSql .= ' AND article.active = 1 ';
            }
            if ($all == false) {
                $LengowProduct = ' LEFT JOIN s_articles_attributes AS attributes ON attributes.articleID = article.id ';
                $filterSql .= ' AND attributes.lengow_lengowActive = 1 ';
            }
            $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS article.id as article
                    FROM s_articles as article
                    LEFT JOIN s_articles_categories_ro ac
                    ON ac.articleID = article.id
                    $LengowProduct
                    $filterSql
                    ORDER BY article.id ASC";
            return Shopware()->Db()->fetchAll($sql);
        } else {
            return array();
        }     		
	}

    /**
    * Get all attributes
    * 
    * @return array
    */
    public static function getAttributes() 
    {
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS groups.id as id, groups.name as name
                FROM s_article_configurator_groups as groups";
        return Shopware()->Db()->fetchAll($sql);
    }

    /**
    * Get the product attributes
    * 
    * @param  string $name
    * @param  int $id_variation
    * @return array
    */
    public function getAttributeData($name = null, $id_variation = null)
    {
        if($name == null) {
            return;
        }
        $sqlParams = array();
        if ($id_variation) {
            $sqlParams["idProduct"] = $this->variant_product->getId();
        } else {
            if ($this->product->getConfiguratorSet() !== NULL) {
                return '';
            } else {
                $sqlParams["idProduct"] = $this->product->getId();
            }   
        }      
        $sqlParams["nameAttribute"] = $name;
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS o.name AS name
                FROM s_article_configurator_options o
                LEFT JOIN s_article_configurator_groups g ON g.id = o.group_id
                LEFT JOIN s_article_configurator_option_relations r ON r.option_id = o.id
                WHERE r.article_id = :idProduct
                AND g.name = :nameAttribute";
        return Shopware()->Db()->fetchOne($sql, $sqlParams);  
    }

    /**
     * Get the ID product
     * 
     * @return int id
     */
    public function getId() 
    {
        return $this->product->getId();
    }

    /**
     * Get Configuration set for variation of one product
     * 
     * @return integer
     */
    public function getConfiguratorSet() 
    {
        return $this->product->getConfiguratorSet();
    }

    /**
     * Get all variations product
     * 
     * @return 
     */
    public function getDetails() 
    {
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

    /**
    * Get in stock product or not
    * 
    * @param int $id_variation
    * @return boolean
    */
    public function getInStockProduct($id_variation =  null)
    {
        if ($id_variation) {
            return ($this->variant_product->getInStock() > 0 ? true : false);
        } else {
            if ($this->product->getConfiguratorSet() !== NULL) {
                $variants = $this->product->getDetails();
                foreach($variants as $variant) {
                    if($variant->getInStock() > 0) {
                        return true;
                    }
                }
                return false;
            } else {
                return ($this->detail_product->getInStock() > 0 ? true : false);
            }
        }
    }

    /**
     * Get the name and value of the options based on a product
     * 
     * @param int $id_product
     * @return array
     */
    private function _getOptions($id_product) 
    {
        $sqlParams = array();
        $sqlParams["idProduct"] = $id_product;
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS
                o.name AS value, g.name AS name
                FROM s_article_configurator_options o
                LEFT JOIN s_article_configurator_option_relations r ON r.option_id = o.id
                LEFT JOIN s_article_configurator_groups g ON g.id = o.group_id
                WHERE r.article_id = :idProduct";
        return Shopware()->Db()->fetchAll($sql, $sqlParams);
    }

    /**
     * Get field Price 
     * 
     * @param string $field
     * @param int $id_variation
     * @return string
     */
    private function _getPriceField($field, $id_variation = null) 
    {
        $sqlParams = array();
        $sqlParams["customerGroupKey"] = $this->shop->getCustomerGroup()->getKey();
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS $field
                FROM s_articles_prices prices
                WHERE prices.to = 'beliebig'
                AND prices.pricegroup = :customerGroupKey
                AND prices.articledetailsID = :detailId ";
        if ($id_variation) {
            $detailId = $this->variant_product->getId();
        } else {
            $detailId = $this->detail_product->getId();
        }
        $sqlParams["customerGroupKey"] = $this->shop->getCustomerGroup()->getKey();
        $sqlParams["detailId"] = $detailId;
        return Shopware()->Db()->fetchOne($sql, $sqlParams);
    }

}