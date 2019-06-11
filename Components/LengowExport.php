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
 * Lengow Export Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowExport
{
    /**
     * @var array all available params for export
     */
    public static $exportParams = array(
        'mode',
        'format',
        'stream',
        'offset',
        'limit',
        'selection',
        'out_of_stock',
        'product_ids',
        'variation',
        'inactive',
        'shop',
        'currency',
        'log_output',
        'update_export_date',
        'get_params'
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
    protected $format;

    /**
     * @var boolean display result on screen
     */
    protected $stream;

    /**
     * @var array list of articles to display
     */
    protected $productIds = array();

    /**
     * @var integer limit number of results
     */
    protected $limit;

    /**
     * @var integer get results from specific index
     */
    protected $offset;

    /**
     * @var boolean export out of stock articles
     */
    protected $outOfStock;

    /**
     * @var boolean update export date.
     */
    protected $updateExportDate;

    /**
     * @var boolean export variant articles
     */
    protected $variation;

    /**
     * @var boolean export Lengow products only
     */
    protected $selection;

    /**
     * @var boolean enable/disable log output
     */
    protected $logOutput;

    /**
     * @var boolean export disabled articles
     */
    protected $inactive;

    /**
     * @var string export mode (size|null)
     */
    protected $mode;

    /**
     * @var \Shopware\Models\Shop\Shop Shopware Shop instance
     */
    protected $shop;

    /**
     * @var \Shopware\Models\Shop\Currency Shopware Currency instance
     */
    protected $currency;

    /**
     * LengowExport constructor
     *
     * @param Shopware\Models\Shop\Shop $shop Shopware shop instance
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
     * boolean update_export_date Change last export date in data base (1) or not (0)
     */
    public function __construct($shop, $params)
    {
        $this->shop = $shop;
        $this->em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $this->stream = isset($params['stream']) ? (bool)$params['stream'] : true;
        $this->offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        $this->limit = isset($params['limit']) ? (int)$params['limit'] : 0;
        $this->selection = isset($params['selection'])
            ? (bool)$params['selection']
            : Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowExportSelectionEnabled',
                $this->shop
            );
        $this->outOfStock = isset($params['out_of_stock']) ? (bool)$params['out_of_stock'] : true;
        $this->variation = isset($params['variation']) ? (bool)$params['variation'] : true;
        $this->inactive = isset($params['inactive'])
            ? (bool)$params['inactive']
            : Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowExportDisabledProduct',
                $this->shop
            );
        $this->mode = isset($params['mode']) ? $params['mode'] : false;
        $this->updateExportDate = isset($params['update_export_date']) ? (bool)$params['update_export_date'] : true;
        $this->setFormat(isset($params['format']) ? $params['format'] : 'csv');
        $this->setProductIds(isset($params['product_ids']) ? $params['product_ids'] : false);
        $this->setCurrency(isset($params['currency']) ? $params['currency'] : false);
        $this->setLogOutput(isset($params['log_output']) ? (bool)$params['log_output'] : true);
    }

    /**
     * Check whether or not the format exists
     * If not specified (null), get the default format of the configuration
     *
     * @param string $format export format
     */
    private function setFormat($format)
    {
        $this->format = !in_array($format, Shopware_Plugins_Backend_Lengow_Components_LengowFeed::$availableFormats)
            ? 'csv'
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
                    $this->productIds[] = (int)$id;
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
            $currency = $this->em->getRepository('Shopware\Models\Shop\Currency')
                ->findOneBy(array('currency' => $currencyCode));
        }
        if (is_null($currency)) {
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
        // Clean logs
        Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanLog();
        if ($this->mode == 'size') {
            echo $this->getExportedProducts();
        } elseif ($this->mode == 'total') {
            echo $this->getTotalProducts();
        } else {
            try {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/export/start'),
                    $this->logOutput
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/export/start_for_shop',
                        array(
                            'name_shop' => $this->shop->getName(),
                            'id_shop' => $this->shop->getId()
                        )
                    ),
                    $this->logOutput
                );
                // get fields to export
                $fields = $this->getFields();
                // get products to be exported
                $articles = $this->getIdToExport();
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/export/nb_product_found',
                        array("nb_product" => count($articles))
                    ),
                    $this->logOutput
                );
                $this->export($articles, $fields);
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/export/end'),
                    $this->logOutput
                );
            } catch (Shopware_Plugins_Backend_Lengow_Components_LengowException $e) {
                $errorMessage = $e->getMessage();
            } catch (Exception $e) {
                $errorMessage = '[Shopware error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            }
            if (isset($errorMessage)) {
                $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    $errorMessage
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/export/export_failed',
                        array('decoded_message' => $decodedMessage)
                    ),
                    $this->logOutput
                );
            }
        }
    }

    /**
     * Export products
     *
     * @param $articles array list of articles to export
     * @param $fields   array list of fields
     *
     * @throws Exception|Shopware_Plugins_Backend_Lengow_Components_LengowException folder not writable
     */
    private function export($articles, $fields)
    {
        $numberOfProducts = 0;
        $displayedProducts = 0;
        // Setup feed
        $feed = new Shopware_Plugins_Backend_Lengow_Components_LengowFeed(
            $this->stream,
            $this->format,
            $this->shop->getName()
        );
        // write header
        $feed->write('header', $fields);
        // Used for json format
        $isFirst = true;
        // Write products in the feed when the header is ready
        foreach ($articles as $article) {
            $productData = array();
            // If offset specified in params
            if ($this->offset != null && $this->offset > $numberOfProducts) {
                $numberOfProducts++;
                continue;
            }
            if ($this->limit != null && $this->limit <= $displayedProducts) {
                break;
            }
            /** @var Shopware\Models\Article\Detail $detail */
            $detail = $this->em->getReference('Shopware\Models\Article\Detail', $article['detailId']);
            $product = new Shopware_Plugins_Backend_Lengow_Components_LengowProduct(
                $detail,
                $this->shop,
                $article['type'],
                $this->currency,
                $this->logOutput
            );
            foreach ($fields as $field) {
                if (isset(self::$defaultFields[$field])) {
                    $productData[$field] = $product->getData(self::$defaultFields[$field]);
                } else {
                    $productData[$field] = $product->getData($field);
                }
            }
            $feed->write('body', $productData, $isFirst);
            $isFirst = false;
            $numberOfProducts++;
            $displayedProducts++;
            // Log each time 50 products are exported
            if ($displayedProducts > 0 && $displayedProducts % 50 == 0) {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
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
            Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::setConfig(
                'lengowLastExport',
                date('Y-m-d H:i:s'),
                $this->shop
            );
        }
        if (!$success) {
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/export/error_folder_not_writable'
                )
            );
        }
        if (!$this->stream) {
            $feedUrl = $feed->getUrl();
            if ($feedUrl && php_sapi_name() != "cli") {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/export/your_feed_available_here',
                        array('feed_url' => $feedUrl)
                    ),
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
        // Check field name to avoid duplicates
        $formattedFields = array();
        foreach (self::$defaultFields as $key => $value) {
            $fields[] = $key;
            $formattedFields[] = Shopware_Plugins_Backend_Lengow_Components_LengowFeed::formatFields(
                $key,
                $this->format
            );
        }
        // Get all article variations
        $variations = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::getAllVariations();
        foreach ($variations as $variation) {
            $variationName = strtolower($variation['name']);
            $formattedFeature = Shopware_Plugins_Backend_Lengow_Components_LengowFeed::formatFields(
                $variationName,
                $this->format
            );
            if (!in_array($formattedFeature, $formattedFields)) {
                $fields[] = $variationName;
                $formattedFields[] = $formattedFeature;
            }
        }
        // Get all free text fields
        $attributes = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::getAllAttributes();
        foreach ($attributes as $attribute) {
            $attributeLabel = 'free_' . strtolower($attribute['label']);
            $formattedAttribute = Shopware_Plugins_Backend_Lengow_Components_LengowFeed::formatFields(
                $attributeLabel,
                $this->format
            );
            if (!in_array($formattedAttribute, $formattedFields)) {
                $fields[] = $attributeLabel;
                $formattedFields[] = $formattedAttribute;
            }
        }
        // Get all articles properties
        $properties = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::getAllProperties();
        foreach ($properties as $property) {
            $propertyName = 'prop_' . strtolower($property['name']);
            $formattedProperty = Shopware_Plugins_Backend_Lengow_Components_LengowFeed::formatFields(
                $propertyName,
                $this->format
            );
            if (!in_array($formattedProperty, $formattedFields)) {
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
            ->leftJoin('categories.attribute', 'attribute')
            ->leftJoin('categories.details', 'details')
            ->leftJoin('details.article', 'article')
            ->leftJoin('article.configuratorSet', 'configurator')
            ->where('shop.id = :shopId')
            ->setParameter('shopId', $this->shop->getId());
        // Product ids selection
        if (count($this->productIds) > 0) {
            $condition = '(';
            $idx = 0;
            foreach ($this->productIds as $productId) {
                $condition .= 'article.id = ' . $productId;
                if ($idx < (count($this->productIds) - 1)) {
                    $condition .= ' OR ';
                }
                $idx++;
            }
            $condition .= ')';
            $builder->andWhere($condition);
        }
        // Export disabled product
        if (!$this->inactive) {
            $builder->andWhere('article.active = 1');
        }
        // Export only Lengow products
        if ($this->selection) {
            $builder->andWhere('attribute.lengowShop' . $this->shop->getId() . 'Active = 1');
        }
        // Export out of stock products
        if (!$this->outOfStock) {
            $builder->andWhere('details.inStock > 0');
        }
        // If no variation, get only parent products
        if (!$this->variation) {
            $builder->andWhere('details.kind = 1');
        }
        $builder->distinct()
            ->orderBy('categories.id')
            ->groupBy('categories.id', 'details.id');
        $articles = $builder->getQuery()->getArrayResult();
        // Get parent foreach article
        foreach ($articles as $article) {
            if (is_null($article['isParent'])) {
                // Get simple product
                $articlesByParent[$article['articleId']] = array(
                    'type' => 'simple',
                    'articleId' => $article['articleId'],
                    'detailId' => $article['detailId'],
                    'detailNumber' => $article['detailNumber'],
                );
            } else {
                // Get parent product and variations
                if (!array_key_exists($article['articleId'], $articlesByParent)) {
                    // Create parent with the first variation if not exist
                    $articlesByParent[$article['articleId']] = array(
                        'type' => 'parent',
                        'childs' => array($article),
                    );
                } else {
                    // Insert variation for a specific parent
                    $articlesByParent[$article['articleId']]['childs'][] = $article;
                }
                if ($article['kind'] == 1) {
                    // Get detailId and detailNumber for parent
                    $articlesByParent[$article['articleId']]['detailId'] = $article['detailId'];
                    $articlesByParent[$article['articleId']]['detailNumber'] = $article['detailNumber'];
                }
            }
        }
        // Add articleId and detailNumber only for debug
        foreach ($articlesByParent as $articleId => $parentArticle) {
            if ($parentArticle['type'] == 'parent') {
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
    public function getTotalProducts()
    {
        $outOfStockDefaultValue = $this->outOfStock;
        $selectionDefaultValue = $this->selection;
        $variationDefaultValue = $this->variation;
        $this->outOfStock = true; // Force out of stock products
        $this->selection = false;
        $this->variation = true;
        $products = $this->getIdToExport();
        $total = count($products);
        // Reset default values
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
    public function getExportedProducts()
    {
        $products = $this->getIdToExport();
        $total = count($products);
        return $total;
    }

    /**
     * Get all export available parameters
     *
     * @return string
     */
    public static function getExportParams()
    {
        $params = array();
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        foreach (self::$exportParams as $param) {
            switch ($param) {
                case 'mode':
                    $authorizedValue = array('size', 'total');
                    $type = 'string';
                    $example = 'size';
                    break;
                case 'format':
                    $authorizedValue = Shopware_Plugins_Backend_Lengow_Components_LengowFeed::$availableFormats;
                    $type = 'string';
                    $example = 'csv';
                    break;
                case 'offset':
                case 'limit':
                    $authorizedValue = 'all integers';
                    $type = 'integer';
                    $example = 100;
                    break;
                case 'product_ids':
                    $authorizedValue = 'all integers';
                    $type = 'string';
                    $example = '101,108,215';
                    break;
                case 'shop':
                    $availableShops = array();
                    $shops = $em->getRepository('Shopware\Models\Shop\Shop')->findAll();
                    foreach ($shops as $shop) {
                        $availableShops[] = $shop->getId();
                    }
                    $authorizedValue = $availableShops;
                    $type = 'integer';
                    $example = 1;
                    break;
                case 'currency':
                    $availableCurrencies = array();
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
                'example' => $example
            );
        }
        return json_encode($params);
    }
}
