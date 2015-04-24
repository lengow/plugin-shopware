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
    public $product;

    /**
     * Instance of article_detail
     */
    public $variation;


    public function __construct($id_product = null, $id_variation = null) {
        $this->product = Shopware()->Models()->find('Shopware\Models\Article\Article', $id_product);
       	$this->variation = Shopware()->Models()->find('Shopware\Models\Article\Detail', $id_variation);
    }

    /**
     * Get data of product
     *
     * @param string $name
     * @return string the data
     */
    public function getData($name) {
        switch ($name) {
            case 'id_article':
            	if ($this->variation) {
            		return $this->product->getId().'_'.$this->variation->getId();
            	}
            	return $this->product->getId() . $this->variation->getId();
                break;
            case 'name_article':
            	
                break;
            case 'number_article':
               
                break;
            case 'supplier':
                
                break;
            case 'price':
                
                break;
            case 'price_wt':
               
                break;
            case 'tax':
               
                break;
            case 'in_stock':
                
                break;
            case 'weight':
                
                break;
            case 'description':
               
                break;
            case 'long_description':
               
                break;
            case 'url_article':
               
                break;
            case 'category':
              
                break;
            case 'available_product':
              
                break;
            case 'quantity':
                
                break;
            case 'unit':
               
                break;
            case 'unit_reference':
               
                break;
            case 'unit_pack':
                
                break;
            case 'unit_purchase':
                
                break;
            case 'min_purchase':
                
                break;
            case 'max_purchase':
               
                break;
            case 'shipping_time':
                
                break;
            case 'ean':
                
                break;
            case 'width':
               
                break;
            case 'height':
                
                break;
            case 'length':

                break;
            case 'id_parent':

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
	public static function getExportIds($all = true, $all_product = false)
	{
		$LengowProduct = '';
		$filterSql = 'WHERE 1 = 1';

		if ($all_product == false) {
			$filterSql .= " AND article.active = 1 ";
		}
		if ($all == false) {
			$LengowProduct = " LEFT JOIN s_articles_attributes AS attributes ON attributes.articleID = article.id ";
			$filterSql .= " AND attributes.lengow_lengowActive = 1 ";
		}
		$sql = "
            SELECT DISTINCT SQL_CALC_FOUND_ROWS article.id as article
         	FROM s_articles as article
         	$LengowProduct
            $filterSql
            ORDER BY article.id ASC
        ";

        return Shopware()->Db()->fetchAll($sql);		
	}
 
}