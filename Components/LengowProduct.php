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
        'amount'
    );
    /**
     * @var Shopware\Models\Article\Article Shopware article instance
     */
    protected $product;

    /**
     * @var Shopware\Models\Article\Detail Shopware article details instance
     */
    protected $details;

    /**
     * @var Shopware\Models\Shop\Shop Shopware shop instance
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
     * @var Shopware\Models\Shop\Currency Shopware currency instance
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
     * @var Shopware\Models\Article\Price Shopware article price instance
     */
    protected $price;

    /**
     * Construct
     *
     * @param Shopware\Models\Article\Detail $details Shopware article detail instance
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
     * @param string $type article type
     * @param Shopware\Models\Shop\Currency $currency Shopware currency instance
     * @param boolean $logOutput display logs or not
     */
    public function __construct($details, $shop, $type, $currency, $logOutput)
    {
        $this->product = $details->getArticle();
        $this->details = $details;
        $this->shop = $shop;
        $this->type = $type;
        $this->logOutput = $logOutput;
        $this->isVariation = $type === 'child' ? true : false;
        $this->currency = $currency;
        $this->factor = $this->currency->getFactor();
        $this->variations = self::getArticleVariations($this->details->getId());
        $this->attributes = self::getArticleAttributes($this->details->getId());
        $this->translations = $this->getProductTranslations();
        $this->price = $this->getPrice();
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
                    return $this->product->getId() . '_' . $this->details->getId();
                } else {
                    return $this->product->getId();
                }
            case 'sku':
                return $this->details->getNumber();
            case 'sku_supplier':
                return $this->details->getSupplierNumber();
            case 'ean':
                return $this->details->getEan();
            case 'name':
                $name = isset($this->translations['name']) ? $this->translations['name'] : $this->product->getName();
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($name);
            case 'quantity':
                if ($this->isVariation) {
                    return $this->details->getInStock() > 0 ? $this->details->getInStock() : 0;
                } else {
                    return $this->getTotalStock();
                }
            case 'category':
                return $this->getBreadcrumb();
            case 'status':
                return $this->details->getActive() ? 'Enabled' : 'Disabled';
            case 'url':
                $sep = '/';
                $idCategory = 0;
                $idProduct = $this->product->getId();
                $shopUrl = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getShopUrl($this->shop);
                $idCategoryParent = $this->shop->getCategory()->getId();
                $categories = $this->product->getCategories();
                foreach ($categories as $category) {
                    $pathCategory = explode("|", $category->getPath());
                    if (in_array($idCategoryParent, $pathCategory)) {
                        $idCategory = $category->getId();
                        break;
                    }
                }
                return $shopUrl . $sep . 'detail' . $sep . 'index' . $sep
                    . 'sArticle' . $sep . $idProduct . $sep . 'sCategory' . $sep . $idCategory;
            case 'price_excl_tax':
                $price = $this->price->getPrice();
                $discount = $this->price->getPercent();
                $discExclTax = $price * (1 - ($discount / 100));
                return number_format($discExclTax * $this->factor, 2);
            case 'price_incl_tax':
                $price = $this->price->getPrice();
                $discount = $this->price->getPercent();
                $discInclTax = $price * (1 - ($discount / 100));
                $tax = $this->product->getTax()->getTax();
                $priceDiscInclTax = round($discInclTax * (100 + $tax) / 100, 2);
                return number_format($priceDiscInclTax * $this->factor, 2);
            case 'price_before_discount_excl_tax':
                $price = $this->price->getPrice();
                $priceExclTax = round($price, 2);
                return number_format($priceExclTax * $this->factor, 2);
            case 'price_before_discount_incl_tax':
                $price = $this->price->getPrice();
                $tax = $this->product->getTax()->getTax();
                $priceInclTax = round($price * (100 + $tax) / 100, 2);
                return number_format($priceInclTax * $this->factor, 2);
            case 'discount_percent':
                $productPrice = $this->details->getPrices();
                return number_format($productPrice[0]->getPercent(), 2);
            case 'discount_start_date':
                return '';
            case 'discount_end_date':
                return '';
            case 'shipping_cost':
                return $this->getShippingCost();
            case 'currency':
                return $this->currency->getCurrency();
            case (preg_match('`image_url_([0-9]+)`', $name) ? true : false):
                $index = explode('_', $name);
                $index = $index[2];
                return $this->getImagePath($index);
            case 'type':
                return $this->type;
            case 'parent_id':
                return $this->product->getId();
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
                return $this->details->getShippingTime();
            case 'weight':
                return $this->details->getWeight();
            case 'height':
                return $this->details->getHeight();
            case 'width':
                return $this->details->getWidth();
            case 'length':
                return $this->details->getLen();
            case 'minimal_quantity':
                return $this->details->getMinPurchase();
            case 'description_short':
                $description = isset($this->translations['description'])
                    ? $this->translations['description']
                    : $this->product->getDescription();
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanHtml(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($description)
                );
            case 'description':
                $descriptionLong = isset($this->translations['descriptionLong'])
                    ? $this->translations['descriptionLong']
                    : $this->product->getDescriptionLong();
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanHtml(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($descriptionLong)
                );
            case 'description_html':
                $descriptionLong = isset($this->translations['descriptionLong'])
                    ? $this->translations['descriptionLong']
                    : $this->product->getDescriptionLong();
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($descriptionLong);
            case 'meta_title':
                $metaTitle = isset($this->translations['metaTitle'])
                    ? $this->translations['metaTitle']
                    : $this->product->getMetaTitle();
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($metaTitle);
            case 'meta_keyword':
                $keywords = isset($this->translations['keywords'])
                    ? $this->translations['keywords']
                    : $this->product->getKeywords();
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData($keywords);
            case 'supplier':
                return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData(
                    $this->product->getSupplier()->getName()
                );
            default:
                $result = '';
                if (array_key_exists($name, $this->variations) && $this->isVariation) {
                    $result = Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData(
                        $this->variations[$name]
                    );
                }
                // get the text of a free text field
                if (strstr($name, 'free_')) {
                    $noPrefAttribute = str_replace('free_', '', $name);
                    if (array_key_exists($noPrefAttribute, $this->attributes)) {
                        $result = Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanData(
                            $this->attributes[$noPrefAttribute]
                        );
                    }
                }
                return $result;
        }
    }

    /**
     * Get path images for the current product
     *
     * @param integer $index index of the image
     *
     * @return string
     */
    private function getImagePath($index)
    {
        try {
            // @var Shopware\Models\Article\Image[] $productImages
            $images = $this->isVariation ? $this->details->getImages() : $this->product->getImages();
            $image = $images[$index - 1];
            if ($image != null) {
                return $this->formatImagePath($image);
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
     * Get format image path
     *
     * @param Shopware\Models\Article\Image $image Shopware article image instance
     *
     * @throws Exception
     *
     * @return string
     */
    private function formatImagePath($image)
    {
        $isMediaManagerSupported = Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.1.0');
        $result = '';
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://';
        $domain = $isHttps . $_SERVER['SERVER_NAME'];
        // @var Shopware\Models\Media\Media $media
        $media = $this->isVariation ? $image->getParent()->getMedia() : $image->getMedia();
        if ($media != null) {
            if ($isMediaManagerSupported) {
                if ($media->getPath() != null) {
                    $mediaService = Shopware()->Container()->get('shopware_media.media_service');
                    // Get image virtual path (ie : .../media/image/0a/20/03/my-image.png)
                    $imagePath = $mediaService->getUrl($media->getPath());
                    $firstOccurrence = strpos($imagePath, '/media');
                    $result = $domain . substr($imagePath, $firstOccurrence);
                }
            } else {
                if ($media->getPath() != null) {
                    $result = $domain . '/' . $media->getPath();
                }
            }
        }
        return $result;
    }

    /**
     * Get article translations to export
     *
     * @return array
     */
    private function getProductTranslations()
    {
        $translation = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getTranslationComponent();
        return $translation->read($this->shop->getId(), 'article', $this->product->getId());
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
            'groups.name AS name'
        );
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select($select)
            ->from('Shopware\Models\Article\Configurator\Group', 'groups')
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
            ->from('Shopware\Models\Article\Configurator\Group', 'groups');
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
            SELECT  *
            FROM    s_articles_attributes
            WHERE   s_articles_attributes.articledetailsID = ?
        ', array($detailId));
        // match name with text of these free text fields
        $attributes = array();
        foreach ($tableFieldsAttributes as $fieldAttribute => $fieldAttributeText) {
            foreach ($tableValuesAttributes as $valueAttribute => $valueAttributeText) {
                if ($fieldAttributeText['columnName'] == $valueAttribute) {
                    $attributes[strtolower($fieldAttributeText['label'])] = $valueAttributeText;
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
        $isNewTableAttributes = Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.2');
        if ($isNewTableAttributes)  {
            $select = array(
                'attributs.columnName',
                'attributs.label'
            );
            $builder = Shopware()->Models()->createQueryBuilder();
            $builder->select($select)
                ->from('Shopware\Models\Attribute\Configuration', 'attributs')
                ->where('attributs.displayInBackend = 1')
                ->groupBy('attributs.columnName')
                ->orderBy('attributs.columnName', 'ASC');
            $attributes = $builder->getQuery()->getArrayResult();
        } else {
            $select = array(
                'attributs.name as columnName',
                'attributs.label',
                'attributs.position'
            );
            $builder = Shopware()->Models()->createQueryBuilder();
            $builder->select($select)
                ->from('Shopware\Models\Article\Element', 'attributs')
                ->groupBy('attributs.name')
                ->orderBy('attributs.position', 'ASC');
            $attributes =  $builder->getQuery()->getArrayResult();
        }
        return $attributes;
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
        $categories = $this->product->getCategories();
        $breadcrumb = null;
        foreach ($categories as $category) {
            $categoryPath = explode("|", $category->getPath());
            if (in_array($parentCategoryId, $categoryPath)) {
                $breadcrumb = $category->getName();
                $categoryId = (int)$category->getParentId();
                for ($i = 0; $i < count($categoryPath) - 2; $i++) {
                    $category = Shopware()->Models()->getReference(
                        'Shopware\Models\Category\Category',
                        (int)$categoryId
                    );
                    $breadcrumb = $category->getName() . ' > ' . $breadcrumb;
                    $categoryId = (int)$category->getParentId();
                }
                break;
            }
        }
        return Shopware_Plugins_Backend_Lengow_Components_LengowMain::replaceAccentedChars($breadcrumb);
    }

    /**
     * Get main price of a product
     *
     * @return Shopware\Models\Article\Price
     */
    private function getPrice()
    {
        $articlePrice = '';
        $productPrices = $this->details->getPrices();
        foreach ($productPrices as $price) {
            if ($price->getCustomerGroup() == $this->shop->getCustomerGroup()) {
                $articlePrice = $price;
                break;
            }
        }
        return $articlePrice;
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
        // If article has not been manually set with free shipping
        if (!$this->details->getShippingFree()) {
            $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
            $dispatchId = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowDefaultDispatcher',
                $this->shop
            );
            // @var Shopware\Models\Dispatch\Dispatch $dispatch
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
                    && $calculationType >= $dispatch->getShippingFree()
                ) {
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
        } else {
            return number_format(0, 2);
        }
    }

    /**
     * Check if the category the article belongs is blocked for this dispatch
     *
     * @param Shopware\Models\Category\Category $blockedCategories Shopware category instance
     *
     * @return boolean
     */
    private function getCategoryStatus($blockedCategories)
    {
        // @var Shopware\Models\Category\Category[] $productCategories
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
     *
     * @throws Exception
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
        $temp['price_unit'] = (float)$temp['amount'] / (float)$temp['quantity'];
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
     * @param string $articleId Lengow article id
     *
     * @return array|null
     */
    public static function findArticle($articleId)
    {
        $result = null;
        $ids = explode('_', $articleId);
        $parentId = $ids[0];
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        try {
            $article = $em->find('Shopware\Models\Article\Article', $parentId);
        } catch (Exception $e) {
            $article = null;
        }
        // If parent article is found
        if ($article != null) {
            $isConfigurable = count($article->getDetails()) > 1;
            // If simple product
            if (!$isConfigurable) {
                // Get article main detail id
                $mainDetail = $article->getMainDetail();
                $result = array(
                    'id' => $mainDetail->getId(),
                    'number' => $mainDetail->getNumber()
                );
            } elseif ($isConfigurable && count($ids) == 2) {
                // If product is configurable and articleId contains detail reference
                $detailId = $ids[1];
                $criteria = array(
                    'id' => $detailId,
                    'articleId' => $parentId
                );
                $variation = $em->getRepository('Shopware\Models\Article\Detail')->findOneBy($criteria);
                $result = array(
                    'id' => $variation->getId(),
                    'number' => $variation->getNumber()
                );
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
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $criteria = array($field => $value);
        $result = $em->getRepository('Shopware\Models\Article\Detail')->findBy($criteria);
        $total = count($result);
        if ($total == 1) {
            return array(
                'id' => $result[0]->getId(),
                'number' => $result[0]->getNumber()
            );
        } elseif ($total > 1) {
            // If more than one article found, display warning
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Import',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/import/multiple_article_found',
                    array(
                        'total_product' => $total,
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
