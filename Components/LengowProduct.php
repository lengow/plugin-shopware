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
    protected $price;
    protected $shop;

    public function __construct($details, $shop) {
        $this->product = $details->getArticle();
        $this->details = $details;
        $this->shop = $shop;
        $this->attributes = array();

        $this->isVariation = $this->details->getKind() != 1 ? true : false;
        $this->getOptions();
        $this->getPrice();
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
            case 'category':
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
                return $host . $baseUrl . $sep . 'detail'.$sep.'index'.$sep.'sArticle'.$sep.$idProduct.$sep.'sCategory'.$sep.$idCategory;
                break;
            case 'price_excl_tax':
                $price = $this->price->getPrice();
                $discount = $this->price->getPercent();
                $discExclTax = $price * (1 - ($discount/100));
                return number_format($discExclTax, 2);
                break;
            case 'price_incl_tax':
                $price = $this->price->getPrice();
                $discount = $this->price->getPercent();
                $discInclTax = $price * (1 - ($discount/100));
                $tax = $this->product->getTax()->getTax();
                $priceDiscInclTax = round($discInclTax*(100+$tax)/100, 2);
                return number_format($priceDiscInclTax, 2);
                break;
            case 'price_before_discount_excl_tax':
                $price = $this->price->getPrice();
                $priceExclTax = round($price, 2);
                return number_format($priceExclTax, 2);
                break;
            case 'price_before_discount_incl_tax':
                $price = $this->price->getPrice();
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
                break;
            case 'discount_end_date':
                return '';
                break;
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
                return Shopware_Plugins_Backend_Lengow_Components_LengowCore::cleanData($this->product->getKeywords());
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

    /**
     * Get main price of a product
     *
     */
    private function getPrice()
    {
        $productPrices = $this->details->getPrices();
        foreach ($productPrices as $price) {
            if ($price->getTo() == 'beliebig') {
                $this->price = $price;
                break;
            }
        }
    }

    private function getShippingCost()
    {
        $weight = $this->details->getWeight();
        $shippingCost = 0;
        $articlePrice = $this->getData('price_before_discount_incl_tax');

        // If article has not been manually set with free shipping
        if (!$this->details->getShippingFree()) {
            $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();

            $dispatchId = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getConfigValue(
                'lengowDefaultDispatcher',
                $this->shop->getId()
                );

            $dispatch = $em->getReference('Shopware\Models\Dispatch\Dispatch', $dispatchId);
            $blockedCategories = $dispatch->getCategories();

            if ($this->getCategoryStatus($blockedCategories)) {
                // Check that article price is in bind prices
                $startPrice = $dispatch->getBindPriceFrom() == null ? 0 : $dispatch->getBindPriceFrom();
                $endPrice = $dispatch->getBindPriceTo() == null ? $articlePrice : $dispatch->getBindPriceTo();

                if ($articlePrice >= $startPrice && $articlePrice <= $endPrice) {
                    $calculationType = 0;
                    $calculation = $dispatch->getCalculation();

                    switch ($calculation) {
                        case 0: // Dispatch based on weight
                            $calculationType = $this->details->getWeight();
                            break;
                        case 1: // Dispatch based on price
                            $calculationType = $articlePrice;
                            break;
                        case 2: // Dispatch based on quantity
                            $calculationType = 1;
                            break;
                        case 3: // Dispatch based on calculation
                            $calculationType = $this->details->getWeight();
                            break;
                        default:
                            $calculationType = 0;
                            break;
                    }
                }

                // If free shipping has been set
                if ($dispatch->getShippingFree() != null
                    && $calculationType >= $dispatch->getShippingFree()) {
                    $shippingCost = 0;
                } else {
                    if ($dispatch->getCostsMatrix()) {
                        $shippingCosts = $dispatch->getCostsMatrix();
                        $count = count($shippingCosts);
                        for ($i = $count-1; $i >= 0; $i--) {
                            if ($calculationType >= $shippingCosts[$i]->getFrom()) {
                                $shippingCost = $shippingCosts[$i]->getValue();
                                break;
                            }
                        }
                    }
                }
            }

            return number_format($shippingCost, 2);
        }
    }

    /**
     * Check if the category the article belongs to 
     * is blocked for this dispatch
     * @param $blockedCategories Categories which are blocked
     */
    private function getCategoryStatus($blockedCategories) {
        $productCategories = $this->product->getCategories();
        $result = true;

        foreach ($productCategories as $pCategory) {
            foreach ($blockedCategories as $bCategory) {
                if ($pCategory->getId() == $bCategory->getId()) {
                    $result = false;
                } else if (!$bCategory->isLeaf()) {
                    $result = $result && $this->getCategoryStatus($bCategory->getChildren());
                }
            }
        }

        return $result;
    }
}