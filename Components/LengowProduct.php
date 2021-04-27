<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * It is available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/agpl-3.0
 *
 * @category    Lengow
 * @package     Lengow
 * @subpackage  Components
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware\Models\Article\Article as ArticleModel;
use Shopware\Models\Article\Configurator\Group as ArticleConfiguratorGroupModel;
use Shopware\Models\Article\Detail as ArticleDetailModel;
use Shopware\Models\Article\Element as ArticleElementModel;
use Shopware\Models\Article\Price as ArticlePriceModel;
use Shopware\Models\Attribute\Configuration as AttributeConfigurationModel;
use Shopware\Models\Dispatch\Dispatch as DispatchModel;
use Shopware\Models\Media\Media as MediaModel;
use Shopware\Models\Category\Category as CategoryModel;
use Shopware\Models\Shop\Currency as ShopCurrencyModel;
use Shopware\Models\Shop\Shop as ShopModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;

/**
 * Lengow Product Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowProduct
{
    /**
     * @var array API nodes containing relevant data
     */
    public static $productApiNodes = array(
        'marketplace_product_id',
        'marketplace_status',
        'merchant_product_id',
        'marketplace_order_line_id',
        'quantity',
        'amount',
    );
    /**
     * @var ArticleModel Shopware article instance
     */
    protected $article;

    /**
     * @var ArticleDetailModel Shopware article detail instance
     */
    protected $detail;

    /**
     * @var ShopModel Shopware shop instance
     */
    protected $shop;

    /**
     * @var string article type
     */
    protected $type;

    /**
     * @var boolean enable/disable log output
     */
    protected $logOutput;

    /**
     * @var boolean is this article a simple product (true) or a variation (false)
     */
    protected $isVariation = false;

    /**
     * @var ShopCurrencyModel Shopware currency instance
     */
    protected $currency;

    /**
     * @var float currency factor (compare to Euro)
     */
    protected $factor;

    /**
     * @var array Shopware article translation
     */
    protected $translations;

    /**
     * @var array specific variations for the product
     */
    protected $variations;

    /**
     * @var array specific attributes for the product
     */
    protected $attributes;

    /**
     * @var array specific properties for the product
     */
    protected $properties;

    /**
     * @var array all product prices
     */
    protected $prices;

    /**
     * @var array all product images
     */
    protected $images;

    /**
     * Construct
     *
     * @param ArticleDetailModel $detail Shopware article detail instance
     * @param ShopModel $shop Shopware shop instance
     * @param string $type article type
     * @param ShopCurrencyModel $currency Shopware currency instance
     * @param boolean $logOutput display logs or not
     */
    public function __construct($detail, $shop, $type, $currency, $logOutput)
    {
        $this->article = $detail->getArticle();
        $this->detail = $detail;
        $this->shop = $shop;
        $this->type = $type;
        $this->logOutput = $logOutput;
        $this->isVariation = $type === 'child';
        $this->currency = $currency;
        $this->factor = $this->currency->getFactor();
        $this->variations = self::getArticleVariations($this->detail->getId());
        $this->attributes = self::getArticleAttributes($this->detail->getId());
        $this->properties = $this->getArticleProperties($this->article->getId());
        $this->translations = $this->getArticleTranslations();
        $this->prices = $this->getPrices();
        $this->images = $this->getImages();
    }

    /**
     * Retrieve Lengow product data
     *
     * @param string $name name of the data to get
     *
     * @throws Exception
     *
     * @return string
     */
    public function getData($name)
    {
        switch ($name) {
            case 'id':
                if ($this->isVariation) {
                    return $this->article->getId() . '_' . $this->detail->getId();
                }
                return $this->article->getId();
            case 'sku':
                return $this->detail->getNumber();
            case 'sku_supplier':
                return $this->detail->getSupplierNumber();
            case 'ean':
                return $this->detail->getEan();
            case 'name':
                $name = isset($this->translations['name']) ? $this->translations['name'] : $this->article->getName();
                return LengowMain::cleanData($name);
            case 'quantity':
                if ($this->isVariation) {
                    return $this->detail->getInStock() > 0 ? $this->detail->getInStock() : 0;
                }
                return $this->getTotalStock();
            case 'category':
                return $this->getBreadcrumb();
            case 'status':
                return $this->detail->getActive() ? 'Enabled' : 'Disabled';
            case 'url':
                $sep = '/';
                $categoryId = 0;
                $articleId = $this->article->getId();
                $parentCategoryId = $this->shop->getCategory()->getId();
                $categories = $this->article->getCategories();
                foreach ($categories as $category) {
                    $pathCategory = explode('|', $category->getPath());
                    if (in_array($parentCategoryId, $pathCategory, true)) {
                        $categoryId = $category->getId();
                        break;
                    }
                }
                return LengowMain::getShopUrl($this->shop) . $sep . 'detail' . $sep . 'index' . $sep
                    . 'sArticle' . $sep . $articleId . $sep . 'sCategory' . $sep . $categoryId;
            case 'price_excl_tax':
            case 'price_incl_tax':
            case 'price_before_discount_excl_tax':
            case 'price_before_discount_incl_tax':
                return number_format($this->prices[$name] * $this->factor, 2);
            case 'discount_percent':
                return number_format($this->prices[$name], 2);
            case 'discount_start_date':
            case 'discount_end_date':
                return '';
            case 'shipping_cost':
                return $this->getShippingCost();
            case 'currency':
                return $this->currency->getCurrency();
            case (preg_match('`image_url_([0-9]+)`', $name) ? true : false):
                return $this->images[$name];
            case 'type':
                return $this->type;
            case 'parent_id':
                return $this->article->getId();
            case 'variation':
                $result = '';
                foreach ($this->variations as $key => $variation) {
                    $result .= $key . ', ';
                }
                return rtrim($result, ', ');
            case 'language':
                return $this->shop->getLocale()->getLocale();
                break;
            case 'shipping_delay':
                return $this->detail->getShippingTime();
            case 'weight':
                return $this->detail->getWeight();
            case 'height':
                return $this->detail->getHeight();
            case 'width':
                return $this->detail->getWidth();
            case 'length':
                return $this->detail->getLen();
            case 'minimal_quantity':
                return $this->detail->getMinPurchase();
            case 'description_short':
                $description = isset($this->translations['description'])
                    ? $this->translations['description']
                    : $this->article->getDescription();
                return LengowMain::cleanHtml(LengowMain::cleanData($description));
            case 'description':
                $descriptionLong = isset($this->translations['descriptionLong'])
                    ? $this->translations['descriptionLong']
                    : $this->article->getDescriptionLong();
                return LengowMain::cleanHtml(LengowMain::cleanData($descriptionLong));
            case 'description_html':
                $descriptionLong = isset($this->translations['descriptionLong'])
                    ? $this->translations['descriptionLong']
                    : $this->article->getDescriptionLong();
                return LengowMain::cleanData($descriptionLong);
            case 'meta_title':
                $metaTitle = isset($this->translations['metaTitle'])
                    ? $this->translations['metaTitle']
                    : $this->article->getMetaTitle();
                return LengowMain::cleanData($metaTitle);
            case 'meta_keyword':
                $keywords = isset($this->translations['keywords'])
                    ? $this->translations['keywords']
                    : $this->article->getKeywords();
                return LengowMain::cleanData($keywords);
            case 'supplier':
                return LengowMain::cleanData($this->article->getSupplier()->getName());
            default:
                $result = '';
                if (array_key_exists($name, $this->variations) && $this->isVariation) {
                    $result = LengowMain::cleanData($this->variations[$name]);
                }
                // get the text of a free text field
                if (strstr($name, 'free_')) {
                    $noPrefAttribute = str_replace('free_', '', $name);
                    if (array_key_exists($noPrefAttribute, $this->attributes)) {
                        $attribute = $this->attributes[$noPrefAttribute];
                        // get attribute translation
                        $columnName = LengowMain::compareVersion('5.2')
                            ? '__attribute_' . $attribute['columnName']
                            : $attribute['columnName'];
                        $attributeValue = isset($this->translations[$columnName])
                            ? $this->translations[$columnName]
                            : $attribute['value'];
                        $result = LengowMain::cleanData($attributeValue);
                    }
                }
                // get the text of a property
                if (strstr($name, 'prop_')) {
                    $noPrefProperty = str_replace('prop_', '', $name);
                    if (array_key_exists($noPrefProperty, $this->properties)) {
                        $result = LengowMain::cleanData($this->properties[$noPrefProperty]);
                    }
                }
                return $result;
        }
    }

    /**
     * Get article translations to export
     *
     * @return array
     */
    private function getArticleTranslations()
    {
        $translation = LengowMain::getTranslationComponent();
        if ($this->shop->getFallback()) {
        	return $translation->read($this->shop->getFallback()->getId(), 'article', $this->article->getId());
        }
        return $translation->read($this->shop->getId(), 'article', $this->article->getId());
    }

    /**
     * Get article variations
     *
     * @param integer $detailId Shopware article detail id
     *
     * @return array
     */
    public static function getArticleVariations($detailId)
    {
        $variations = array();
        $select = array(
            'options.name AS value',
            'groups.name AS name',
        );
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select($select)
            ->from(ArticleConfiguratorGroupModel::class, 'groups')
            ->leftJoin('groups.options', 'options')
            ->leftJoin('options.articles', 'articles')
            ->where('articles.id = :detailId')
            ->setParameter('detailId', $detailId);
        $result = $builder->getQuery()->getArrayResult();
        foreach ($result as $options) {
            $variations[strtolower($options['name'])] = $options['value'];
        }
        return $variations;
    }

    /**
     * Return products custom variations
     *
     * @return array
     */
    public static function getAllVariations()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('groups.name AS name'))
            ->from(ArticleConfiguratorGroupModel::class, 'groups');
        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Get article attributes
     *
     * @param integer $detailId Shopware article detail id
     *
     * @return array
     */
    public static function getArticleAttributes($detailId)
    {
        // get all field names of free text fields configured and display in backend
        $tableFieldsAttributes = self::getAllAttributes();
        // get the text of these free text fields
        $tableValuesAttributes = Shopware()->Db()->fetchRow('
            SELECT *
            FROM s_articles_attributes
            WHERE s_articles_attributes.articledetailsID = ?
        ', array($detailId));
        // match name with text of these free text fields
        $attributes = array();
        foreach ($tableFieldsAttributes as $fieldAttribute => $fieldAttributeText) {
            foreach ($tableValuesAttributes as $valueAttribute => $valueAttributeText) {
                if ($fieldAttributeText['columnName'] === $valueAttribute) {
                    $attributes[strtolower($fieldAttributeText['label'])] = array(
                        'columnName' => $fieldAttributeText['columnName'],
                        'value' => $valueAttributeText,
                    );
                }
            }
        }
        return $attributes;
    }

    /**
     * Return products custom attributes
     *
     * @return array
     */
    public static function getAllAttributes()
    {
        // use "core_engine_elements" table up to 5.2 version and "attribute_configuration" table later
        if (LengowMain::compareVersion('5.2'))  {
            $select = array(
                'attributs.columnName',
                'attributs.label',
            );
            $builder = Shopware()->Models()->createQueryBuilder();
            $builder->select($select)
                ->from(AttributeConfigurationModel::class, 'attributs')
                ->where('attributs.displayInBackend = 1')
                ->groupBy('attributs.columnName')
                ->orderBy('attributs.columnName', 'ASC');
            $attributes = $builder->getQuery()->getArrayResult();
        } else {
            $select = array(
                'attributs.name as columnName',
                'attributs.label',
                'attributs.position',
            );
            $builder = Shopware()->Models()->createQueryBuilder();
            $builder->select($select)
                ->from(ArticleElementModel::class, 'attributs')
                ->groupBy('attributs.name')
                ->orderBy('attributs.position', 'ASC');
            $attributes =  $builder->getQuery()->getArrayResult();
        }
        return $attributes;
    }

    /**
     * Get article properties
     *
     * @param integer $articleId Shopware article id
     *
     * @return array
     */
    private function getArticleProperties($articleId)
    {
        $properties = array();
        $tableProperties = Shopware()->Db()->fetchAll('
            SELECT opt.name, val.id, val.value FROM s_filter_articles AS art
            LEFT JOIN s_filter_values AS val ON art.valueID = val.id 
            LEFT JOIN s_filter_options AS opt ON val.optionID = opt.id
            WHERE art.articleID = ?
        ', array($articleId));
        foreach ($tableProperties as $property => $propertyValue) {
            $translation = LengowMain::getPropertyValueTranslation($propertyValue['id'], $this->shop->getId());
            $lowerPropertyName = strtolower($propertyValue['name']);
            $propertyTranslation = $translation ?: $propertyValue['value'];
            if (array_key_exists($lowerPropertyName, $properties)) {
                $properties[$lowerPropertyName] .= ', ' . $propertyTranslation;
            } else {
                $properties[$lowerPropertyName] = $propertyTranslation;
            }
        }
        return $properties;
    }

    /**
     * Return products properties
     *
     * @return array
     */
    public static function getAllProperties()
    {
        return Shopware()->Db()->fetchAll('SELECT name FROM s_filter_options');
    }

    /**
     * Create the breadcrumb for this product
     *
     * @throws Exception
     *
     * @return string
     */
    private function getBreadcrumb()
    {
        $parentCategoryId = $this->shop->getCategory()->getId();
        $categories = $this->article->getCategories();
        $breadcrumb = null;
        foreach ($categories as $category) {
            $categoryPath = explode('|', $category->getPath());
            $countCategoryPath = count($categoryPath);
            if (in_array($parentCategoryId, $categoryPath, true)) {
                $breadcrumb = $category->getName();
                $categoryId = (int) $category->getParentId();
                for ($i = 0; $i < $countCategoryPath - 2; $i++) {
                    /** @var CategoryModel $category */
                    $category = Shopware()->Models()->getReference(CategoryModel::class, $categoryId);
                    $breadcrumb = $category->getName() . ' > ' . $breadcrumb;
                    $categoryId = (int) $category->getParentId();
                }
                break;
            }
        }
        return LengowMain::replaceAccentedChars($breadcrumb);
    }

    /**
     * Get main price of a detail
     *
     * @return ArticlePriceModel|false
     */
    private function getDetailPrice()
    {
        $shopArticlePrice = false;
        $defaultArticlePrice = false;
        $shopCustomerGroupId = $this->shop->getCustomerGroup()->getId();
        $detailPrices = $this->detail->getPrices();
        foreach ($detailPrices as $price) {
            if ($price->getCustomerGroup() !== null) {
                if ($price->getCustomerGroup()->getId() === $shopCustomerGroupId) {
                    $shopArticlePrice = $price;
                }
                // get default article price from EK customer group
                if ($price->getCustomerGroup()->getKey() === 'EK') {
                    $defaultArticlePrice = $price;
                }
            }
        }
        return $shopArticlePrice ?: $defaultArticlePrice;
    }

    /**
     * Get prices for a product
     *
     * @return array
     */
    private function getPrices()
    {
        $detailPrice = $this->getDetailPrice();
        $tax = $this->article->getTax() ? $this->article->getTax()->getTax() : 0;
        // get original price before discount
        $priceExclTax = $detailPrice ? $detailPrice->getPrice() : 0;
        $priceInclTax = $priceExclTax * (100 + $tax) / 100;
        // get price with discount
        $discount = $detailPrice ? $detailPrice->getPercent() : 0;
        $discountPriceExclTax = $priceExclTax * (1 - ($discount / 100));
        $discountPriceInclTax = $discountPriceExclTax * (100 + $tax) / 100;
        return array(
            'price_excl_tax' => round($discountPriceExclTax, 2),
            'price_incl_tax' => round($discountPriceInclTax, 2),
            'price_before_discount_excl_tax' => round($priceExclTax, 2),
            'price_before_discount_incl_tax' => round($priceInclTax, 2),
            'discount_percent' => $discount,
        );
    }

    /**
     * Get images for a product
     *
     * @return array
     */
    private function getImages()
    {
        $urls = array();
        $imageUrls = array();
        $variationHasImage = false;
        // create image urls array
        for ($i = 1; $i < 11; $i++) {
            $imageUrls['image_url_' . $i] = '';
        }
        // get variation or parent images
        if ($this->isVariation) {
            $variationHasImage = !$this->detail->getImages()->isEmpty();
            $images = $variationHasImage ? $this->detail->getImages() : $this->article->getImages();
        } else {
            $images =  $this->article->getImages();
        }
        // get url for each image
        $isMediaManagerSupported = LengowMain::compareVersion('5.1.0');
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://';
        $domain = $isHttps . $_SERVER['SERVER_NAME'];
        foreach ($images as $image) {
            try {
                /** @var MediaModel $media */
                $media = $variationHasImage ? $image->getParent()->getMedia() : $image->getMedia();
                if ($media !== null) {
                    if ($isMediaManagerSupported) {
                        if ($media->getPath() !== null) {
                            $mediaService = Shopware()->Container()->get('shopware_media.media_service');
                            // get image virtual path (ie : .../media/image/0a/20/03/my-image.png)
                            $imagePath = $mediaService->getUrl($media->getPath());
                            $firstOccurrence = strpos($imagePath, '/media');
                            $urls[] = $domain . substr($imagePath, $firstOccurrence);
                        }
                    } else {
                        if ($media->getPath() !== null) {
                            $urls[] = $domain . '/' . $media->getPath();
                        }
                    }
                }
            } catch (Exception $e) {
                LengowMain::log(
                    LengowLog::CODE_EXPORT,
                    LengowMain::setLogMessage(
                        'log/export/error_media_not_found',
                        array(
                            'detailId' => $this->detail->getNumber(),
                            'articleName' => $this->article->getName(),
                            'message' => $e->getMessage(),
                        )
                    ),
                    $this->logOutput
                );
                continue;
            }
        }
        // retrieves up to 10 images per product
        $counter = 1;
        foreach ($urls as $url) {
            $imageUrls['image_url_' . $counter] = $url;
            if ($counter === 10) {
                break;
            }
            $counter++;
        }

        return $imageUrls;
    }

    /**
     * Get article shipping cost
     *
     * @throws Exception
     *
     * @return float
     */
    private function getShippingCost()
    {
        $shippingCost = 0;
        $articlePrice = $this->getData('price_before_discount_incl_tax');
        // if article has not been manually set with free shipping
        if (!$this->detail->getShippingFree()) {
            $em = LengowBootstrap::getEntityManager();
            $dispatchId = LengowConfiguration::getConfig(LengowConfiguration::DEFAULT_EXPORT_CARRIER_ID, $this->shop);
            /** @var DispatchModel $dispatch */
            $dispatch = $em->getReference(DispatchModel::class, $dispatchId);
            /** @var CategoryModel[] $blockedCategories */
            $blockedCategories = $dispatch->getCategories();
            if ($this->getCategoryStatus($blockedCategories)) {
                // check that article price is in bind prices
                $startPrice = $dispatch->getBindPriceFrom() === null ? 0 : $dispatch->getBindPriceFrom();
                $endPrice = $dispatch->getBindPriceTo() === null ? $articlePrice : $dispatch->getBindPriceTo();
                $calculationType = 0;
                if ($articlePrice >= $startPrice && $articlePrice <= $endPrice) {
                    $calculation = $dispatch->getCalculation();
                    switch ($calculation) {
                        case 0: // dispatch based on weight
                            $calculationType = $this->detail->getWeight();
                            break;
                        case 1: // dispatch based on price
                            $calculationType = $articlePrice;
                            break;
                        case 2: // dispatch based on quantity
                            $calculationType = 1;
                            break;
                        case 3: // dispatch based on calculation
                            $calculationType = $this->detail->getWeight();
                            break;
                        default:
                            $calculationType = 0;
                            break;
                    }
                }
                // if free shipping has been set
                if ($dispatch->getShippingFree() !== null && $calculationType >= $dispatch->getShippingFree()) {
                    $shippingCost = 0;
                } else {
                    if ($dispatch->getCostsMatrix()) {
                        $shippingCosts = $dispatch->getCostsMatrix();
                        $count = count($shippingCosts);
                        for ($i = $count - 1; $i >= 0; $i--) {
                            if ($calculationType >= $shippingCosts[$i]->getFrom()) {
                                $shippingCost = $shippingCosts[$i]->getValue();
                                break;
                            }
                        }
                    }
                }
            }
            return number_format($shippingCost * $this->factor, 2);
        }
        return number_format(0, 2);
    }

    /**
     * Check if the category the article belongs is blocked for this dispatch
     *
     * @param CategoryModel[] $blockedCategories Shopware category instance
     *
     * @return boolean
     */
    private function getCategoryStatus($blockedCategories)
    {
        /** @var CategoryModel[] $articleCategories */
        $articleCategories = $this->article->getCategories();
        $result = true;
        foreach ($articleCategories as $aCategory) {
            foreach ($blockedCategories as $bCategory) {
                if ($aCategory->getId() === $bCategory->getId()) {
                    $result = false;
                } elseif (!$bCategory->isLeaf()) {
                    /** @var CategoryModel[] $categories */
                    $categories = $bCategory->getChildren();
                    $result = $result && $this->getCategoryStatus($categories);
                }
            }
        }
        return $result;
    }

    /**
     * Get total stock of a product
     * Used to count number of articles for parents
     *
     * @throws Exception
     */
    private function getTotalStock()
    {
        $em = LengowBootstrap::getEntityManager();
        $builder = $em->createQueryBuilder();
        $builder->select(array('SUM(detail.inStock)'))
            ->from(ArticleDetailModel::class, 'detail')
            ->where('detail.articleId = :articleId')
            ->setParameter('articleId', $this->article->getId());
        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Extract cart data from API
     *
     * @param mixed $api product datas
     *
     * @return array
     */
    public static function extractProductDataFromAPI($api)
    {
        $temp = array();
        foreach (self::$productApiNodes as $node) {
            $temp[$node] = $api->{$node};
        }
        $temp['price_unit'] = (float) $temp['amount'] / (float) $temp['quantity'];
        return $temp;
    }

    /**
     * Check whether or not an article is a parent
     *
     * @param string $articleId Lengow article id
     *
     * @return boolean
     */
    public static function checkIsParentProduct($articleId)
    {
        $ids = explode('_', $articleId);
        // check existing parent product
        if (count($ids) === 1) {
            $articleId = $ids[0];
            if ($articleId !== null && preg_match('/^[0-9]*$/', $articleId) && substr($articleId, 0, 1) != 0) {
                try {
                    $em = LengowBootstrap::getEntityManager();
                    /** @var ArticleModel $article */
                    $article = $em->find(ArticleModel::class, $articleId);
                } catch (Exception $e) {
                    $article = null;
                }
                if ($article && count($article->getDetails()) > 1) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Search a product by number, ean and id
     *
     * @param string $articleId Lengow article id
     *
     * @return array|null
     */
    public static function findArticle($articleId)
    {
        $result = null;
        $ids = explode('_', $articleId);
        $parentId = $ids[0];
        if ($parentId !== null && preg_match('/^[0-9]*$/', $parentId) && substr($parentId, 0, 1) != 0) {
            $em = LengowBootstrap::getEntityManager();
            try {
                /** @var ArticleModel $article */
                $article = $em->find(ArticleModel::class, $parentId);
            } catch (Exception $e) {
                $article = null;
            }
            // if parent article is found
            if ($article !== null) {
                $isConfigurable = count($article->getDetails()) > 1;
                // if simple product
                if (!$isConfigurable && count($ids) === 1) {
                    // get article main detail id
                    $mainDetail = $article->getMainDetail();
                    $result = array(
                        'id' => $mainDetail->getId(),
                        'number' => $mainDetail->getNumber(),
                    );
                } elseif ($isConfigurable && count($ids) === 2) {
                    // if product is configurable and articleId contains detail reference
                    $detailId = $ids[1];
                    $criteria = array(
                        'id' => $detailId,
                        'articleId' => $parentId,
                    );
                    /** @var ArticleDetailModel $variation */
                    $variation = $em->getRepository(ArticleDetailModel::class)->findOneBy($criteria);
                    $result = array(
                        'id' => $variation->getId(),
                        'number' => $variation->getNumber(),
                    );
                }
            }
        }
        return $result;
    }

    /**
     * Search a product by number, ean and id
     *
     * @param string $field field of Shopware\Models\Article\Detail to search in
     * @param string $value searched value
     * @param boolean $logOutput display log or not
     *
     * @return array|null
     */
    public static function advancedSearch($field, $value, $logOutput)
    {
        $em = LengowBootstrap::getEntityManager();
        /** @var ArticleDetailModel[] $result */
        $result = $em->getRepository(ArticleDetailModel::class)->findBy(array($field => $value));
        $total = count($result);
        if ($total === 1) {
            return array(
                'id' => $result[0]->getId(),
                'number' => $result[0]->getNumber(),
            );
        }
        if ($total > 1) {
            // if more than one article found, display warning
            LengowMain::log(
                LengowLog::CODE_IMPORT,
                LengowMain::setLogMessage(
                    'log/import/multiple_article_found',
                    array(
                        'total_product' => $total,
                        'searched_field' => $field,
                        'searched_value' => $value,
                    )
                ),
                $logOutput
            );
        }
        return null;
    }
}
