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

use Shopware\Models\Article\Detail as ArticleDetailModel;
use Shopware\Models\Shop\Currency as ShopCurrencyModel;
use Shopware\Models\Shop\Shop as ShopModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowException as LengowException;
use Shopware_Plugins_Backend_Lengow_Components_LengowFeed as LengowFeed;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;
use Shopware_Plugins_Backend_Lengow_Components_LengowProduct as LengowProduct;

/**
 * Lengow Export Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowExport
{
    /* Export GET params */
    const PARAM_TOKEN = 'token';
    const PARAM_MODE = 'mode';
    const PARAM_FORMAT = 'format';
    const PARAM_STREAM = 'stream';
    const PARAM_OFFSET = 'offset';
    const PARAM_LIMIT = 'limit';
    const PARAM_SELECTION = 'selection';
    const PARAM_OUT_OF_STOCK = 'out_of_stock';
    const PARAM_PRODUCT_IDS = 'product_ids';
    const PARAM_VARIATION = 'variation';
    const PARAM_INACTIVE = 'inactive';
    const PARAM_SHOP = 'shop';
    const PARAM_CURRENCY = 'currency';
    const PARAM_LOG_OUTPUT = 'log_output';
    const PARAM_UPDATE_EXPORT_DATE = 'update_export_date';
    const PARAM_GET_PARAMS = 'get_params';

    /**
     * @var array all available params for export
     */
    public static $exportParams = array(
        self::PARAM_MODE,
        self::PARAM_FORMAT,
        self::PARAM_STREAM,
        self::PARAM_OFFSET,
        self::PARAM_LIMIT,
        self::PARAM_SELECTION,
        self::PARAM_OUT_OF_STOCK,
        self::PARAM_PRODUCT_IDS,
        self::PARAM_VARIATION,
        self::PARAM_INACTIVE,
        self::PARAM_SHOP,
        self::PARAM_CURRENCY,
        self::PARAM_LOG_OUTPUT,
        self::PARAM_UPDATE_EXPORT_DATE,
        self::PARAM_GET_PARAMS,
    );

    /**
     * @var array default fields
     */
    public static $defaultFields = array(
        'id' => 'id',
        'sku' => 'sku',
        'sku_supplier' => 'sku_supplier',
        'ean' => 'ean',
        'name' => 'name',
        'quantity' => 'quantity',
        'category' => 'category',
        'status' => 'status',
        'url' => 'url',
        'price_excl_tax' => 'price_excl_tax',
        'price_incl_tax' => 'price_incl_tax',
        'price_before_discount_excl_tax' => 'price_before_discount_excl_tax',
        'price_before_discount_incl_tax' => 'price_before_discount_incl_tax',
        'discount_percent' => 'discount_percent',
        'discount_start_date' => 'discount_start_date',
        'discount_end_date' => 'discount_end_date',
        'currency' => 'currency',
        'shipping_cost' => 'shipping_cost',
        'shipping_delay' => 'shipping_delay',
        'weight' => 'weight',
        'width' => 'width',
        'height' => 'height',
        'length' => 'length',
        'minimal_quantity' => 'minimal_quantity',
        'image_url_1' => 'image_url_1',
        'image_url_2' => 'image_url_2',
        'image_url_3' => 'image_url_3',
        'image_url_4' => 'image_url_4',
        'image_url_5' => 'image_url_5',
        'image_url_6' => 'image_url_6',
        'image_url_7' => 'image_url_7',
        'image_url_8' => 'image_url_8',
        'image_url_9' => 'image_url_9',
        'image_url_10' => 'image_url_10',
        'type' => 'type',
        'parent_id' => 'parent_id',
        'variation' => 'variation',
        'language' => 'language',
        'description_short' => 'description_short',
        'description' => 'description',
        'description_html' => 'description_html',
        'meta_title' => 'meta_title',
        'meta_keyword' => 'meta_keyword',
        'supplier' => 'supplier',
    );

    /**
     * @var string export format
     */
    private $format;

    /**
     * @var boolean display result on screen
     */
    private $stream;

    /**
     * @var array list of articles to display
     */
    private $productIds = array();

    /**
     * @var integer limit number of results
     */
    private $limit;

    /**
     * @var integer get results from specific index
     */
    private $offset;

    /**
     * @var boolean export out of stock articles
     */
    private $outOfStock;

    /**
     * @var boolean update export date.
     */
    private $updateExportDate;

    /**
     * @var boolean export variant articles
     */
    private $variation;

    /**
     * @var boolean export Lengow products only
     */
    private $selection;

    /**
     * @var boolean enable/disable log output
     */
    private $logOutput;

    /**
     * @var boolean export disabled articles
     */
    private $inactive;

    /**
     * @var string export mode (size|null)
     */
    private $mode;

    /**
     * @var ShopModel Shopware Shop instance
     */
    private $shop;

    /**
     * @var ShopCurrencyModel Shopware Currency instance
     */
    private $currency;

    /**
     * LengowExport constructor
     *
     * @param ShopModel $shop Shopware shop instance
     * @param array $params optional options
     * string  format             Format of exported files ('csv','yaml','xml','json')
     * boolean stream             Stream file (1) or generate a file on server (0)
     * integer offset             Offset of total product
     * integer limit              Limit number of exported product
     * boolean selection          Export product selection (1) or all products (0)
     * boolean out_of_stock       Export out of stock product (1) Export only product in stock (0)
     * string  product_ids        List of product ids separate with comma (1,2,3)
     * string  currency           Currency iso code for price conversion
     * string  mode               Export mode => size: display only exported products, total: display all products
     * boolean variation          Export product Variation (1) Export parent product only (0)
     * boolean log_output         See logs (1) or not (0)
     * boolean update_export_date Change last export date in database (1) or not (0)
     */
    public function __construct($shop, $params = array())
    {
        $this->shop = $shop;
        $this->em = LengowBootstrap::getEntityManager();
        $this->stream = !isset($params[self::PARAM_STREAM]) || $params[self::PARAM_STREAM];
        $this->offset = isset($params[self::PARAM_OFFSET]) ? (int) $params[self::PARAM_OFFSET] : 0;
        $this->limit = isset($params[self::PARAM_LIMIT]) ? (int) $params[self::PARAM_LIMIT] : 0;
        $this->selection = isset($params[self::PARAM_SELECTION])
            ? (bool) $params[self::PARAM_SELECTION]
            : LengowConfiguration::getConfig(LengowConfiguration::SELECTION_ENABLED, $this->shop);
        $this->outOfStock = !isset($params[self::PARAM_OUT_OF_STOCK]) || $params[self::PARAM_OUT_OF_STOCK];
        $this->variation = !isset($params[self::PARAM_VARIATION]) || $params[self::PARAM_VARIATION];
        $this->inactive = isset($params[self::PARAM_INACTIVE])
            ? (bool) $params[self::PARAM_INACTIVE]
            : LengowConfiguration::getConfig(LengowConfiguration::INACTIVE_ENABLED, $this->shop);
        $this->mode = isset($params[self::PARAM_MODE]) ? $params[self::PARAM_MODE] : false;
        $this->updateExportDate = !isset($params[self::PARAM_UPDATE_EXPORT_DATE])
            || $params[self::PARAM_UPDATE_EXPORT_DATE];
        $this->setFormat(isset($params[self::PARAM_FORMAT]) ? $params[self::PARAM_FORMAT] : LengowFeed::FORMAT_CSV);
        $this->setProductIds(isset($params[self::PARAM_PRODUCT_IDS]) ? $params[self::PARAM_PRODUCT_IDS] : false);
        $this->setCurrency(isset($params[self::PARAM_CURRENCY]) ? $params[self::PARAM_CURRENCY] : false);
        $this->setLogOutput(!isset($params[self::PARAM_LOG_OUTPUT]) || $params[self::PARAM_LOG_OUTPUT]);
    }

    /**
     * Check whether the format exists
     * If not specified (null), get the default format of the configuration
     *
     * @param string $format export format
     */
    private function setFormat($format)
    {
        $this->format = !in_array($format, LengowFeed::$availableFormats, true)
            ? LengowFeed::FORMAT_CSV
            : $format;
    }

    /**
     * Set product ids to export
     *
     * @param string|false $productIds product ids to export
     */
    private function setProductIds($productIds)
    {
        if ($productIds) {
            $exportedIds = explode(',', $productIds);
            foreach ($exportedIds as $id) {
                if (is_numeric($id) && $id > 0) {
                    $this->productIds[] = (int) $id;
                }
            }
        }
    }

    /**
     * Set Currency for export
     *
     * @param string|false $currencyCode currency code or not
     */
    private function setCurrency($currencyCode)
    {
        $currency = null;
        if ($currencyCode) {
            /** @var ShopCurrencyModel $currency */
            $currency = $this->em->getRepository('Shopware\Models\Shop\Currency')
                ->findOneBy(array('currency' => $currencyCode));
        }
        if ($currency === null) {
            $currency = $this->shop->getCurrency();
        }
        $this->currency = $currency;
    }

    /**
     * Set Log output for export
     *
     * @param boolean $logOutput see logs or not
     */
    private function setLogOutput($logOutput)
    {
        $this->logOutput = $this->stream ? false : $logOutput;
    }

    /**
     * Export process
     * Get products to export from the params and create the feed
     */
    public function exec()
    {
        // clean logs
        LengowMain::cleanLog();
        if ($this->mode === 'size') {
            echo $this->getTotalExportProduct();
        } elseif ($this->mode === 'total') {
            echo $this->getTotalProduct();
        } else {
            try {
                LengowMain::log(
                    LengowLog::CODE_EXPORT,
                    LengowMain::setLogMessage('log/export/start'),
                    $this->logOutput
                );
                LengowMain::log(
                    LengowLog::CODE_EXPORT,
                    LengowMain::setLogMessage(
                        'log/export/start_for_shop',
                        array(
                            'shop_name' => $this->shop->getName(),
                            'shop_id' => $this->shop->getId(),
                        )
                    ),
                    $this->logOutput
                );
                // get fields to export
                $fields = $this->getFields();
                // get products to be exported
                $articles = $this->getIdToExport();
                LengowMain::log(
                    LengowLog::CODE_EXPORT,
                    LengowMain::setLogMessage('log/export/nb_product_found', array('nb_product' => count($articles))),
                    $this->logOutput
                );
                $this->export($articles, $fields);
                LengowMain::log(LengowLog::CODE_EXPORT, LengowMain::setLogMessage('log/export/end'), $this->logOutput);
            } catch (LengowException $e) {
                $errorMessage = $e->getMessage();
            } catch (Exception $e) {
                $errorMessage = '[Shopware error]: "' . $e->getMessage()
                    . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            }
            if (isset($errorMessage)) {
                $decodedMessage = LengowMain::decodeLogMessage($errorMessage, LengowTranslation::DEFAULT_ISO_CODE);
                LengowMain::log(
                    LengowLog::CODE_EXPORT,
                    LengowMain::setLogMessage('log/export/export_failed', array('decoded_message' => $decodedMessage)),
                    $this->logOutput
                );
            }
        }
    }

    /**
     * Export products
     *
     * @param $articles array list of articles to export
     * @param $fields array list of fields
     *
     * @throws Exception|LengowException
     */
    private function export($articles, $fields)
    {
        $numberOfProducts = 0;
        $displayedProducts = 0;
        // setup feed
        $feed = new LengowFeed($this->stream, $this->format, $this->shop->getName());
        // write header
        $feed->write(LengowFeed::HEADER, $fields);
        // used for json format
        $isFirst = true;
        // write products in the feed when the header is ready
        foreach ($articles as $article) {
            $productData = array();
            // if offset specified in params
            if ($this->offset !== 0 && $this->offset > $numberOfProducts) {
                $numberOfProducts++;
                continue;
            }
            if ($this->limit !== 0 && $this->limit <= $displayedProducts) {
                break;
            }
            /** @var ArticleDetailModel $detail */
            $detail = $this->em->getReference('Shopware\Models\Article\Detail', $article['detailId']);
            $product = new LengowProduct($detail, $this->shop, $article['type'], $this->currency, $this->logOutput);
            foreach ($fields as $field) {
                if (isset(self::$defaultFields[$field])) {
                    $productData[$field] = $product->getData(self::$defaultFields[$field]);
                } else {
                    $productData[$field] = $product->getData($field);
                }
            }
            $feed->write(LengowFeed::BODY, $productData, $isFirst);
            $isFirst = false;
            $numberOfProducts++;
            $displayedProducts++;
            // log each time 50 products are exported
            if ($displayedProducts > 0 && $displayedProducts % 50 === 0) {
                LengowMain::log(
                    LengowLog::CODE_EXPORT,
                    LengowMain::setLogMessage(
                        'log/export/count_product',
                        array('numberOfProducts' => $displayedProducts)
                    ),
                    $this->logOutput
                );
            }
            // clean data for next product
            unset($detail, $product, $productData);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        $success = $feed->end();
        if ($this->updateExportDate) {
            LengowConfiguration::setConfig(LengowConfiguration::LAST_UPDATE_EXPORT, time(), $this->shop);
        }
        if (!$success) {
            throw new LengowException(LengowMain::setLogMessage('log/export/error_folder_not_writable'));
        }
        if (!$this->stream) {
            $feedUrl = $feed->getUrl();
            if ($feedUrl && php_sapi_name() !== 'cli') {
                LengowMain::log(
                    LengowLog::CODE_EXPORT,
                    LengowMain::setLogMessage('log/export/your_feed_available_here', array('feed_url' => $feedUrl)),
                    $this->logOutput
                );
            }
        }
    }

    /**
     * Get fields to export
     *
     * @return array
     */
    private function getFields()
    {
        $fields = array();
        // check field name to avoid duplicates
        $formattedFields = array();
        foreach (self::$defaultFields as $key => $value) {
            $fields[] = $key;
            $formattedFields[] = LengowFeed::formatFields($key, $this->format);
        }
        // get all article variations
        $variations = LengowProduct::getAllVariations();
        foreach ($variations as $variation) {
            $variationName = strtolower($variation['name']);
            $formattedFeature = LengowFeed::formatFields($variationName, $this->format);
            if (!in_array($formattedFeature, $formattedFields, true)) {
                $fields[] = $variationName;
                $formattedFields[] = $formattedFeature;
            }
        }
        // get all free text fields
        $attributes = LengowProduct::getAllAttributes();
        foreach ($attributes as $attribute) {
            $attributeLabel = 'free_' . strtolower($attribute['label']);
            $formattedAttribute = LengowFeed::formatFields($attributeLabel, $this->format);
            if (!in_array($formattedAttribute, $formattedFields, true)) {
                $fields[] = $attributeLabel;
                $formattedFields[] = $formattedAttribute;
            }
        }
        // get all articles properties
        $properties = LengowProduct::getAllProperties();
        foreach ($properties as $property) {
            $propertyName = 'prop_' . strtolower($property['name']);
            $formattedProperty = LengowFeed::formatFields($propertyName, $this->format);
            if (!in_array($formattedProperty, $formattedFields, true)) {
                $fields[] = $propertyName;
                $formattedFields[] = $formattedProperty;
            }
        }
        return $fields;
    }

    /**
     * Build the query from the params
     *
     * @return array
     */
    private function getIdToExport()
    {
        $articlesByParent = array();
        $articleToExport = array();
        $selection = array(
            'article.id AS articleId',
            'configurator.id AS isParent',
            'details.id AS detailId',
            'details.number AS detailNumber',
            'details.kind AS kind',
        );
        $builder = $this->em->createQueryBuilder();
        $builder->select($selection)
            ->from('Shopware\Models\Shop\Shop', 'shop')
            ->leftJoin('shop.category', 'mainCategory')
            ->leftJoin('mainCategory.allArticles', 'categories')
            ->leftJoin('categories.details', 'details')
            ->leftJoin('details.article', 'article')
            ->leftJoin('categories.mainDetail', 'mainDetail')
            ->leftJoin('mainDetail.attribute', 'attribute')
            ->leftJoin('article.configuratorSet', 'configurator')
            ->where('shop.id = :shopId')
            ->setParameter('shopId', $this->shop->getId());
        // product ids selection
        if (!empty($this->productIds)) {
            $idx = 0;
            $condition = '(';
            $totalProductId = count($this->productIds);
            foreach ($this->productIds as $productId) {
                $condition .= 'article.id = ' . $productId;
                if ($idx < ($totalProductId - 1)) {
                    $condition .= ' OR ';
                }
                $idx++;
            }
            $condition .= ')';
            $builder->andWhere($condition);
        }
        // export disabled product
        if (!$this->inactive) {
            $builder->andWhere('article.active = 1');
        }
        // export only Lengow products
        if ($this->selection) {
            $builder->andWhere('attribute.lengowShop' . $this->shop->getId() . 'Active = 1');
        }
        // export out of stock products
        if (!$this->outOfStock) {
            $builder->andWhere('details.inStock > 0');
        }
        // if no variation, get only parent products
        if (!$this->variation) {
            $builder->andWhere('details.kind = 1');
        } else {
            $builder->andWhere('details.kind <> 3');
        }
        $builder->distinct()
            ->orderBy('categories.id')
            ->groupBy('categories.id', 'details.id');
        $articles = $builder->getQuery()->getArrayResult();
        // get parent foreach article
        foreach ($articles as $article) {
            if ($article['isParent'] === null) {
                // get simple product
                $articlesByParent[$article['articleId']] = array(
                    'type' => 'simple',
                    'articleId' => $article['articleId'],
                    'detailId' => $article['detailId'],
                    'detailNumber' => $article['detailNumber'],
                );
            } else {
                // get parent product and variations
                if (!array_key_exists($article['articleId'], $articlesByParent)) {
                    // Create parent with the first variation if not exist
                    $articlesByParent[$article['articleId']] = array(
                        'type' => 'parent',
                        'childs' => array($article),
                    );
                } else {
                    // insert variation for a specific parent
                    $articlesByParent[$article['articleId']]['childs'][] = $article;
                }
                if ((int) $article['kind'] === 1) {
                    // get detailId and detailNumber for parent
                    $articlesByParent[$article['articleId']]['detailId'] = $article['detailId'];
                    $articlesByParent[$article['articleId']]['detailNumber'] = $article['detailNumber'];
                }
            }
        }
        // add articleId and detailNumber only for debug
        foreach ($articlesByParent as $articleId => $parentArticle) {
            if ($parentArticle['type'] === 'parent') {
                $articleToExport[] = array(
                    'type' => 'parent',
                    'articleId' => $articleId,
                    'detailId' => $parentArticle['detailId'],
                    'detailNumber' => $parentArticle['detailNumber'],
                );
                if ($this->variation) {
                    foreach ($parentArticle['childs'] as $child) {
                        $articleToExport[] = array(
                            'type' => 'child',
                            'articleId' => $articleId,
                            'detailId' => $child['detailId'],
                            'detailNumber' => $child['detailNumber'],
                        );
                    }
                }
            } else {
                $articleToExport[] = $parentArticle;
            }
        }
        return $articleToExport;
    }

    /**
     * Get number of products available for export
     *
     * @return integer
     */
    public function getTotalProduct()
    {
        $outOfStockDefaultValue = $this->outOfStock;
        $selectionDefaultValue = $this->selection;
        $variationDefaultValue = $this->variation;
        $this->outOfStock = true; // force out of stock products
        $this->selection = false;
        $this->variation = true;
        $products = $this->getIdToExport();
        $total = count($products);
        // reset default values
        $this->outOfStock = $outOfStockDefaultValue;
        $this->selection = $selectionDefaultValue;
        $this->variation = $variationDefaultValue;
        return $total;
    }

    /**
     * Get number of products exported in Lengow
     *
     * @return integer
     */
    public function getTotalExportProduct()
    {
        $products = $this->getIdToExport();
        return count($products);
    }

    /**
     * Get all export available parameters
     *
     * @return string
     */
    public static function getExportParams()
    {
        $params = array();
        $em = LengowBootstrap::getEntityManager();
        foreach (self::$exportParams as $param) {
            switch ($param) {
                case self::PARAM_MODE:
                    $authorizedValue = array('size', 'total');
                    $type = 'string';
                    $example = 'size';
                    break;
                case self::PARAM_FORMAT:
                    $authorizedValue = LengowFeed::$availableFormats;
                    $type = 'string';
                    $example = LengowFeed::FORMAT_CSV;
                    break;
                case self::PARAM_OFFSET:
                case self::PARAM_LIMIT:
                    $authorizedValue = 'all integers';
                    $type = 'integer';
                    $example = 100;
                    break;
                case self::PARAM_PRODUCT_IDS:
                    $authorizedValue = 'all integers';
                    $type = 'string';
                    $example = '101,108,215';
                    break;
                case self::PARAM_SHOP:
                    $availableShops = array();
                    /** @var ShopModel[] $shops */
                    $shops = $em->getRepository('Shopware\Models\Shop\Shop')->findAll();
                    foreach ($shops as $shop) {
                        $availableShops[] = $shop->getId();
                    }
                    $authorizedValue = $availableShops;
                    $type = 'integer';
                    $example = 1;
                    break;
                case self::PARAM_CURRENCY:
                    $availableCurrencies = array();
                    /** @var ShopCurrencyModel[] $currencies */
                    $currencies = $em->getRepository('Shopware\Models\Shop\Currency')->findAll();
                    foreach ($currencies as $currency) {
                        $availableCurrencies[] = $currency->getCurrency();
                    }
                    $authorizedValue = $availableCurrencies;
                    $type = 'string';
                    $example = 'EUR';
                    break;
                default:
                    $authorizedValue = array(0, 1);
                    $type = 'integer';
                    $example = 1;
                    break;
            }
            $params[$param] = array(
                'authorized_values' => $authorizedValue,
                'type' => $type,
                'example' => $example,
            );
        }
        return json_encode($params);
    }
}
