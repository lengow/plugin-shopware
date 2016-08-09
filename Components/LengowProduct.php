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
    /**
     * array API nodes containing relevant data
     */
    public static $PRODUCT_API_NODES = array(
        'marketplace_product_id',
        'marketplace_status',
        'merchant_product_id',
        'marketplace_order_line_id',
        'quantity',
        'amount'
    );
    /**
     * @var $product Shopware\Models\Article\Article Shopware article
     */
    protected $product;

    /**
     * @var $isVariation boolean Is this article a simple product (true) or a variation (false)
     */
    protected $isVariation = false;

    /**
     * @var $details Shopware\Models\Article\Detail Article details
     */
    protected $details;

    /**
     * @var $attributes String[] Specific attributes for the product
     */
    protected $attributes;

    /**
     * @var $price Shopware\Models\Article\Price Article price
     */
    protected $price;

    /**
     * @var $shop \Shopware\Models\Shop\Shop Shop the article belongs to
     */
    protected $shop;

    /**
     * Shopware_Plugins_Backend_Lengow_Components_LengowProduct constructor.
     * @param $details Shopware\Models\Article\Detail Article detail
     * @param $shop Shopware\Models\Shop\Shop Shop the article belongs to
     * @param $type String simple|parent|child
     * @param $logOutput boolean Display logs
     */
    public function __construct($details, $shop, $type, $logOutput)
    {
        $this->product = $details->getArticle();
        $this->details = $details;
        $this->shop = $shop;
        $this->attributes = array();
        $this->type = $type;
        $this->logOutput = $logOutput;
        $this->isVariation = $type == 'child' ? true : false;
        $this->getOptions();
        $this->getPrice();
    }

    /**
     * Retrieve Lengow product data
     * @param $name String name of the data to get
     * @return string Data value
     */
    public function getData($name)
    {
        switch ($name) {
            case 'id':
                if ($this->isVariation) {
                    return $this->product->getId().'_'.$this->details->getId();
                } else {
                    return $this->product->getId();
                }
                break;
            case 'sku':
                return $this->details->getNumber();
                break;
            case 'sku_supplier':
                return $this->details->getSupplierNumber();
                break;
            case 'ean':
                return $this->details->getEan();
                break;
            case 'name':
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($this->product->getName());
                break;
            case 'quantity':
                if ($this->isVariation) {
                    return $this->details->getInStock() > 0 ? $this->details->getInStock() : 0;
                } else {
                    return $this->getTotalStock();
                }
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
                $host = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getBaseUrl();
                $baseUrl = ($this->shop->getBaseUrl() ? $this->shop->getBaseUrl() : '');
                $idCategoryParent = $this->shop->getCategory()->getId();
                $categories = $this->product->getCategories();
                foreach ($categories as $category) {
                    $pathCategory = explode("|", $category->getPath());
                    if (in_array($idCategoryParent, $pathCategory)) {
                        $idCategory = $category->getId();
                        break;
                    }
                }
                return $host.$baseUrl.$sep.'detail'.$sep.'index'.$sep
                .'sArticle'.$sep.$idProduct.$sep.'sCategory'.$sep.$idCategory;
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
                return number_format($productPrice->getPercent(), 2);
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
                return $this->shop->getCurrency()->getCurrency();
                break;
            case (preg_match('`image_url_([0-9]+)`', $name) ? true : false):
                $index = explode('_', $name);
                $index = $index[2];
                return $this->getImagePath($index);
                break;
            case 'type':
                return $this->type;
                break;
            case 'parent_id':
                return $this->product->getId();
                break;
            case 'variation':
                $result = '';
                foreach ($this->attributes as $key => $variation) {
                    $result.= $key.', ';
                }
                return $result;
                break;
            case 'language':
                return $this->shop->getLocale()->getLocale();
                break;
            case 'shipping_delay':
                return $this->details->getShippingTime();
                break;
            case 'weight':
                return $this->details->getWeight();
                break;
            case 'height':
                return $this->details->getHeight();
                break;
            case 'width':
                return $this->details->getWidth();
                break;
            case 'length':
                return $this->details->getLen();
                break;
            case 'minimal_quantity':
                return $this->details->getMinPurchase();
                break;
            case 'description_short':
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanHtml(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($this->product->getDescription())
                );
                break;
            case 'description':
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanHtml(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData(
                        $this->product->getDescriptionLong()
                    )
                );
                break;
            case 'description_html':
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData(
                    $this->product->getDescriptionLong()
                );
                break;
            case 'meta_title':
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($this->product->getMetaTitle());
                break;
            case 'meta_keyword':
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($this->product->getKeywords());
                break;
            default:
                $result = '';
                if (array_key_exists($name, $this->attributes) && $this->isVariation) {
                    $result = Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData(
                        $this->attributes[$name]
                    );
                }
                return $result;
                break;
        }
    }

    /**
     * Get path images for the current product
     *
     * @param $index int Index of the image to get
     *
     * @return string Image path
     */
    private function getImagePath($index)
    {
        try {
            /** @var Shopware\Models\Article\Image[] $product_images */
            $product_images = $this->product->getImages();
            $image = $product_images[$index - 1];
            // Get image for parent product
            if (!$this->isVariation && $image != null) {
                return $this->formatImagePath($image);
            } else {
                /** @var Shopware\Models\Article\Image[] $variation_images */
                $variation_images = $this->details->getImages();
                $index_variation = $index - count($product_images) - 1;
                $image = $variation_images[$index_variation];
                if ($image != null) {
                    return $this->formatImagePath($image);
                }
            }
        } catch (Exception $e) {
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Warning',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/export/error_media_not_found',
                    array(
                        'detailsId' => $this->details->getId(),
                        'detailsName' => $this->product->getName(),
                        'message' => $e->getMessage()
                    )
                ),
                $this->logOutput
            );
        }
        return '';
    }

    /**
     * @param $image Shopware\Models\Article\Image $product_image
     * @return string
     * @throws Exception
     */
    private function formatImagePath($image)
    {
        $isMediaManagerSupported = Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.1.0');
        $result = '';
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://';
        $domain = $is_https.$_SERVER['SERVER_NAME'];
        if ($isMediaManagerSupported) {
            if ($image->getMedia() != null) {
                /** @var Shopware\Models\Media\Media $media */
                $media = $image->getMedia();
                if ($media->getPath() != null) {
                    $mediaService = Shopware()->Container()->get('shopware_media.media_service');
                    // Get image virtual path (ie : .../media/image/0a/20/03/my-image.png)
                    $imagePath = $mediaService->getUrl($media->getPath());
                    $firstOccurrence = strpos($imagePath, '/media');
                    $result = $domain.substr($imagePath, $firstOccurrence);
                }
            }
        } else {
            $result = $domain.'/media/image/'.$image->getPath().'.'.$image->getExtension();
        }
        return $result;
    }

    /**
     * Get product options
     */
    private function getOptions()
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
            $this->attributes[strtolower($options['name'])] = $options['value'];
        }
    }

    /**
     * Return products custom attributes
     *
     * @return array List of attributes
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Create the breadcrumb for this product
     *
     * @return string The breadcrumb of the product
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function getBreadcrumb()
    {
        $parentCategoryId = $this->shop->getCategory()->getId();
        $categories = $this->product->getCategories();
        $breadcrumb = null;
        foreach ($categories as $category) {
            $categoryPath = explode("|", $category->getPath());
            if (in_array($parentCategoryId, $categoryPath)) {
                $breadcrumb = $category->getName();
                $categoryId = (int)$category->getParentId();
                for ($i=0; $i < count($categoryPath) - 2; $i++) {
                    $category = Shopware()->Models()->getReference(
                        'Shopware\Models\Category\Category',
                        (int)$categoryId
                    );
                    $breadcrumb = $category->getName().' > '.$breadcrumb;
                    $categoryId = (int)$category->getParentId();
                }
                break;
            }
        }
        return Shopware_Plugins_Backend_Lengow_Components_LengowMain::replaceAccentedChars($breadcrumb);
    }

    /**
     * Get main price of a product
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

    /**
     * Get article shipping cost
     *
     * @return float Shipping cost
     */
    private function getShippingCost()
    {
        $shippingCost = 0;
        $articlePrice = $this->getData('price_before_discount_incl_tax');
        // If article has not been manually set with free shipping
        if (!$this->details->getShippingFree()) {
            $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
            $dispatchId = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowDefaultDispatcher',
                $this->shop
            );
            /** @var Shopware\Models\Dispatch\Dispatch $dispatch */
            $dispatch = $em->getReference('Shopware\Models\Dispatch\Dispatch', $dispatchId);
            $blockedCategories = $dispatch->getCategories();
            if ($this->getCategoryStatus($blockedCategories)) {
                // Check that article price is in bind prices
                $startPrice = $dispatch->getBindPriceFrom() == null ? 0 : $dispatch->getBindPriceFrom();
                $endPrice = $dispatch->getBindPriceTo() == null ? $articlePrice : $dispatch->getBindPriceTo();
                $calculationType = 0;
                if ($articlePrice >= $startPrice && $articlePrice <= $endPrice) {
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
        } else {
            return number_format(0, 2);
        }
    }

    /**
     * Check if the category the article belongs to
     * is blocked for this dispatch
     *
     * @param $blockedCategories Doctrine\Common\Collections\ArrayCollection Categories which are blocked
     *
     * @return boolean True if the category is active for dispatch
     */
    private function getCategoryStatus($blockedCategories)
    {
        /** @var Shopware\Models\Category\Category[] $productCategories */
        $productCategories = $this->product->getCategories();
        $result = true;
        foreach ($productCategories as $pCategory) {
            foreach ($blockedCategories as $bCategory) {
                if ($pCategory->getId() == $bCategory->getId()) {
                    $result = false;
                } elseif (!$bCategory->isLeaf()) {
                    $result = $result && $this->getCategoryStatus($bCategory->getChildren());
                }
            }
        }
        return $result;
    }

    /**
     * Get total stock of a product
     * Used to count number of articles for parents
     */
    private function getTotalStock()
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $builder = $em->createQueryBuilder();
        $builder->select(array('SUM(details.inStock)'))
            ->from('Shopware\Models\Article\Detail', 'details')
            ->where('details.articleId = :articleId')
            ->setParameter('articleId', $this->product->getId());
        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Extract cart data from API
     *
     * @param mixed $api
     *
     * @return array
     */
    public static function extractProductDataFromAPI($api)
    {
        $temp = array();
        foreach (self::$PRODUCT_API_NODES as $node) {
            $temp[$node] = $api->{$node};
        }
        $temp['price_unit'] = (float)$temp['amount'] / (float)$temp['quantity'];
        return $temp;
    }

    /**
     * Check whether or not an article is a parent
     *
     * @param $articleId string articleId_detailId
     *
     * @return bool true if article is a parent
     */
    public static function checkIsParentProduct($articleId)
    {
        $ids = explode('_', $articleId);
        $articleId = $ids[0];
        $result = null;
        // Check existing parent product
        if (count($ids) == 1) {
            $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
            $repository = $em->getRepository('Shopware\Models\Article\Article');
            $result = $repository->find($articleId);
        }
        return $result != null;
    }

    /**
     * Search a product by number, ean and id
     *
     * @param $articleId string Article id
     *
     * @return integer Shopware\Models\Article\Detail id|null if not found
     */
    public static function findArticle($articleId)
    {
        $result = null;
        $ids = explode('_', $articleId);
        $parentId = $ids[0];
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $article = $em->find('Shopware\Models\Article\Article', $parentId);
        // If parent article is found
        if ($article != null) {
            $isConfigurable = count($article->getDetails()) > 1;
            // If simple product
            if (!$isConfigurable) {
                // Get article main detail id
                $mainDetail = $article->getMainDetail();
                $result = array(
                    'id'     => $mainDetail->getId(),
                    'number' => $mainDetail->getNumber()
                );
            } elseif ($isConfigurable && count($ids) == 2) {
                // If product is configurable and articleId contains detail reference
                $detailId = $ids[1];
                $criteria = array(
                    'id'        => $detailId,
                    'articleId' => $parentId
                );
                $variation = $em->getRepository('Shopware\Models\Article\Detail')->findOneBy($criteria);
                $result = array(
                    'id'     => $variation->getId(),
                    'number' => $variation->getNumber()
                );
            }
        }
        return $result;
    }

    /**
     * Search a product by number, ean and id
     *
     * @param $field String Field of Shopware\Models\Article\Detail to search in
     * @param $value String Searched value
     * @param $logOutput boolean True if error are displayed on stream
     *
     * @return integer Shopware\Models\Article\Detail id|null if not found
     */
    public static function advancedSearch($field, $value, $logOutput)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $criteria = array($field => $value);
        $result = $em->getRepository('Shopware\Models\Article\Detail')->findBy($criteria);
        $total = count($result);
        if ($total == 1) {
            return array(
                'id'        => $result[0]->getId(),
                'number'    => $result[0]->getNumber()
            );
        } elseif ($total > 1) {
            // If more than one article found, display warning
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/multiple_article_found',
                    array(
                        'total_product'  => $total,
                        'searched_field' => $field,
                        'searched_value' => $value
                    )
                ),
                $logOutput
            );
        }
        return null;
    }
}
