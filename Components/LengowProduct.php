<?php
/**
 * Copyright 2016 Lengow SAS.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * @author    Team Connector <team-connector@lengow.com>
 * @copyright 2016 Lengow SAS
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowProduct
{
    // Shopware article
    protected $product;
    // Is this article a simple product (true) or a variation (false)
    protected $isVariation = false;
    protected $details;
    // Specific attributes for the product
    protected $attributes;

    public function __construct($details, $shop) {
        $this->product = $details->getArticle();
        $this->details = $details;
        $this->shop = $shop;
        $this->attributes = array();

        $this->isVariation = $this->details->getKind() != 1 ? true : false;
        $this->getOptions();
    }

    public function getData($name)
    {
        switch ($name) {
            case 'id':
                if ($this->isVariation) {
                    return $this->product->getId() . '_' . $this->details->getId();
                } else {
                    return $this->product->getId();
                }
                break;
            case 'sku':
                return $this->details->getNumber();
                break;
            case 'ean':
                return $this->details->getEan();
                break;
            case 'name':
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($this->product->getName());
                break;
            case 'quantity':
                return $this->details->getInStock();
                break;
            case 'breadcrumb':
                return $this->getBreadcrumb();
                break;
            case 'status':
                return $this->details->getActive() ? 'Enabled' : 'Disabled';
                break;
            case 'url':
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
            case 'price_excl_tax':
                $productPrice = $this->details->getPrices()[0];
                $price = $productPrice->getPrice();
                $priceExclTax = round($price, 2);
                return number_format($priceExclTax, 2);
                break;
            case 'price_incl_tax':
                $productPrice = $this->details->getPrices()[0];
                $price = $productPrice->getPrice();
                $tax = $this->product->getTax()->getTax();
                $priceInclTax = round($price*(100+$tax)/100, 2);
                return number_format($priceInclTax, 2);
                break;
            case 'discount_percent':
                $productPrice = $this->details->getPrices()[0];
                return $productPrice->getPercent();
                break;
            case 'discount_start_date':
                return '';
            case 'discount_end_date':
                return '';
            case 'shipping_cost':
                return $this->getShippingCost();
                break;
            case 'currency':
                return $this->shop->getCurrency()->getName();
                break;
            case (preg_match('`image_url_([0-9]+)`', $name) ? true : false):
                $index = explode('_', $name);
                $index = $index[2];
                return $this->getImagePath($index);
                break;
            case 'type':
                return $this->isVariation ? 'child' : 'parent';
                break;
            case 'parent_id':
                return $this->product->getId();
                break;
            case 'variation':
                $result = '';
                foreach ($this->attributes as $key => $variation) {
                    $result.= $key . ', ';
                }
                return $result;
                break;
            // TODO : complete with selected language
            case 'language':
                return '';
                break;
            case 'shipping_delay':
                return $this->details->getShippingTime();
                break;
            case 'weight':
                return $this->details->getWeight();
                break;
            case 'supplier_sku':
                return $this->details->getSupplierNumber();
                break;
            case 'minimal_quantity':
                return $this->details->getMinPurchase();
                break;
            case 'description_long':
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml(
                    Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanData($this->product->getDescription())
                    );
                break;
            case 'description':
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanHtml(
                    Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanData($this->product->getDescriptionLong())
                    );
                break;
            case 'description_html':
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanData($this->product->getDescriptionLong());
                break;
            case 'meta_keyword':
                return $this->product->getKeywords();
                break;
            default:
                $result = '';
                if (array_key_exists($name, $this->attributes)) {
                    $result = $this->attributes[$name];
                }
                return $result;
                break;
        }
    }

    private function getImagePath($index)
    {
        $host = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getBaseUrl() . '/';
        $product_images = $this->product->getImages();
        $image = $product_images[$index - 1];
        try {
            // Get image for parent product
            if ($image != null) {
                if ($image->getMedia() != null) {
                    $media = $image->getMedia();
                    if ($media->getPath() != null) {
                        $mediaPath = $media->getPath();
                        return $host . $mediaPath;
                    }
                }
            } else if ($this->isVariation) {
                $variation_images = $this->details->getImages();
                $index_variation = $index - count($product_images) - 1;
                $image = $variation_images[$index_variation];
                if ($image != null) {
                    if ($image->getMedia() != null) {
                        $media = $image->getMedia();
                        if ($media->getPath() != null) {
                            $mediaPath = $media->getPath();
                            return $host . $mediaPath;
                        }
                    }
                }
            }
        } catch (Exception $e) {

        }
        return '';
    }

    /**
     *
     */
    public function getOptions()
    {
        $select = array(
            'options.name AS value',
            'groups.name AS name'
        );

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select($select)
            ->from('Shopware\Models\Article\Configurator\Group', 'groups')
            ->leftJoin('groups.options', 'options')
            ->leftJoin('options.articles', 'articles')
            ->where('articles.id = :productId')
            ->setParameter('productId', $this->details->getId());

        $result = $builder->getQuery()->getArrayResult();

        foreach ($result as $options) {
            $this->attributes[$options['name']] = $options['value'];
        }
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Create the breadcrumb for this product
     * @return string The breadcrumb of the product
     * @throws \Doctrine\ORM\ORMException
     */
    private function getBreadcrumb()
    {
        $parentCategoryId = $this->shop->getCategory()->getId();
        $categories = $this->product->getCategories();
        foreach($categories as $category) {
            $categoryPath = explode("|", $category->getPath());

            if(in_array($parentCategoryId, $categoryPath)) {
                $breadcrumb = $category->getName();
                $categoryId = (int) $category->getParentId();
                for ($i=0; $i < count($categoryPath) - 2 ; $i++) {
                    $category = Shopware()->Models()->getReference('Shopware\Models\Category\Category',(int) $categoryId);
                    $breadcrumb = $category->getName() . ' > ' . $breadcrumb;
                    $categoryId = (int) $category->getParentId();
                }
                break;
            }
        }

        return Shopware_Plugins_Backend_Lengow_Components_LengowCore::replaceAccentedChars($breadcrumb);
    }

    private function getShippingCost()
    {
        // Get the default dispatch
        /*$dispatch = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getDefaultShippingCost($this->shop->getId());
        $shippingPrice  = 0;
        $weight         = 0;
        $price          = 0;
        // Get the weight and the price of a product
        if ($this->variation->getShippingFree()) {
            return $shippingPrice;
        }
        $weight = (float) $this->variation->getWeight();
        $productPrice = $this->variation->getPrices()[0];
        $price = round((float) $productPrice->getPrice(), 2);
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
        }*/
        return 0;
    }
}