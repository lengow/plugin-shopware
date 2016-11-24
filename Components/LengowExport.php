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
class Shopware_Plugins_Backend_Lengow_Components_LengowExport
{
    /**
     * All available params for export
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
     * Default fields.
     */
    public static $DEFAULT_FIELDS = array(
        'id'                             => 'id',
        'sku'                            => 'sku',
        'sku_supplier'                   => 'sku_supplier',
        'ean'                            => 'ean',
        'name'                           => 'name',
        'quantity'                       => 'quantity',
        'category'                       => 'category',
        'status'                         => 'status',
        'url'                            => 'url',
        'price_excl_tax'                 => 'price_excl_tax',
        'price_incl_tax'                 => 'price_incl_tax',
        'price_before_discount_excl_tax' => 'price_before_discount_excl_tax',
        'price_before_discount_incl_tax' => 'price_before_discount_incl_tax',
        'discount_percent'               => 'discount_percent',
        'discount_start_date'            => 'discount_start_date',
        'discount_end_date'              => 'discount_end_date',
        'currency'                       => 'currency',
        'shipping_cost'                  => 'shipping_cost',
        'shipping_delay'                 => 'shipping_delay',
        'weight'                         => 'weight',
        'width'                          => 'width',
        'height'                         => 'height',
        'length'                         => 'length',
        'minimal_quantity'               => 'minimal_quantity',
        'image_url_1'                    => 'image_url_1',
        'image_url_2'                    => 'image_url_2',
        'image_url_3'                    => 'image_url_3',
        'image_url_4'                    => 'image_url_4',
        'image_url_5'                    => 'image_url_5',
        'image_url_6'                    => 'image_url_6',
        'image_url_7'                    => 'image_url_7',
        'image_url_8'                    => 'image_url_8',
        'image_url_9'                    => 'image_url_9',
        'image_url_10'                   => 'image_url_10',
        'type'                           => 'type',
        'parent_id'                      => 'parent_id',
        'variation'                      => 'variation',
        'language'                       => 'language',
        'description_short'              => 'description_short',
        'description'                    => 'description',
        'description_html'               => 'description_html',
        'meta_title'                     => 'meta_title',
        'meta_keyword'                   => 'meta_keyword',
    );

    /**
     * @var string Export format
     */
    private $format;

    /**
     * @var boolean Display result on screen
     */
    private $stream;

    /**
     * @var array List of articles to display
     */
    private $productIds = array();

    /**
     * @var integer Limit number of results
     */
    private $limit;

    /**
     * @var integer Get results from specific index
     */
    private $offset;

    /**
     * @var boolean Export out of stock articles
     */
    private $exportOutOfStock;

    /**
     * @var boolean Update export date.
     */
    private $updateExportDate;

    /**
     * @var boolean Export variant articles
     */
    private $exportVariationEnabled;

    /**
     * @var boolean Export Lengow products only
     */
    private $exportSelectionEnabled;

    /**
     * @var boolean Enable/disable log output
     */
    private $logOutput;

    /**
     * @var boolean Export disabled articles
     */
    private $exportDisabledProduct;

    /**
     * @var string Export mode (size|null)
     */
    private $mode;

    /**
     * Shop to export
     * @var \Shopware\Models\Shop\Shop Shopware Shop
     */
    private $shop;

    /**
     * Currency to use for the export
     * @var Shopware\Models\Shop\Currency
     */
    private $currency;

    /**
     * LengowExport constructor.
     *
     * @param Shopware\Models\Shop\Shop $shop   Shop to export
     * @param array                     $params optional options
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
        $this->exportSelectionEnabled = isset($params['selection'])
            ? (bool)$params['selection']
            : Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowExportSelectionEnabled',
                $this->shop
            );
        $this->exportOutOfStock = isset($params['out_of_stock']) ? (bool)$params['out_of_stock'] : true; 
        $this->exportVariationEnabled = isset($params['variation']) ? (bool)$params['variation'] : true;
        $this->exportDisabledProduct = isset($params['inactive'])
            ? (bool)$params['inactive']
            : Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getConfig(
                'lengowExportDisabledProduct',
                $this->shop
            );
        $this->currency = isset($params['currency']) ? $params['currency'] : true;
        $this->mode = isset($params['mode']) ? $params['mode'] : false;
        $this->updateExportDate = isset($params['update_export_date']) ? (bool)$params['update_export_date'] : true;
        $this->setFormat(isset($params['format']) ? $params['format'] : 'csv');
        $this->setProductIds(isset($params['product_ids']) ? $params['product_ids'] : false);
        $this->setCurrency(isset($params['currency']) ? $params['currency'] : false);
        $this->setLogOutput(isset($params['log_output']) ? $params['log_output'] : true);
    }

    /**
     * Check whether or not the format exists
     * If not specified (null), get the default format of the configuration
     *
     * @param string $format The export format
     */
    private function setFormat($format)
    {
        $this->format = !in_array($format, Shopware_Plugins_Backend_Lengow_Components_LengowFeed::$AVAILABLE_FORMATS)
            ? 'csv'
            : $format;
    }

    /**
     * Set product ids to export
     *
     * @param string $productIds The product ids to export
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
     * @param mixed $currencyCode currency code or not
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
     * @param boolean $log_output See logs or not
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
                            'id_shop'   => $this->shop->getId()
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
                $errorMessage = '[Shopware error] "'.$e->getMessage().'" '.$e->getFile().' | '.$e->getLine();
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
     * @param $articles array List of articles to export
     * @param $fields   array List of fields
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException
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
            /** @var \Shopware\Models\Article\Detail $details */
            $details = $this->em->getReference(
                'Shopware\Models\Article\Detail',
                $article['detailId']
            );
            $product = new Shopware_Plugins_Backend_Lengow_Components_LengowProduct(
                $details,
                $this->shop,
                $article['type'],
                $this->currency,
                $this->logOutput
            );
            foreach ($fields as $field) {
                if (isset(self::$DEFAULT_FIELDS[$field])) {
                    $productData[$field] = $product->getData(self::$DEFAULT_FIELDS[$field]);
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
            unset($details, $product, $productData);
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
        foreach (self::$DEFAULT_FIELDS as $key => $value) {
            $fields[] = $key;
        }
        $attributes = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::getAllAttributes();
        foreach ($attributes as $attribute) {
            $fields[] = strtolower($attribute['name']);
        }
        return $fields;
    }

    /**
     * Build the query from the params
     *
     * @return array List of ids to export
    */
    private function getIdToExport()
    {
        $articlesByParent = array();
        $articleToExport = array();
        $selection = array(
            'articles.id AS parentId',
            'configurator.id AS isParent',
            'details.id AS detailId',
            'details.kind AS kind',
        );
        $builder = $this->em->createQueryBuilder();
        $builder->select($selection)
            ->from('Shopware\Models\Shop\Shop', 'shop')
            ->leftJoin('shop.category', 'mainCategory')
            ->leftJoin('mainCategory.allArticles', 'categories')
            ->leftJoin('categories.attribute', 'attributes')
            ->leftJoin('categories.details', 'details')
            ->leftJoin('details.article', 'articles')
            ->leftJoin('articles.configuratorSet', 'configurator')
            ->innerJoin('articles.allCategories', 'allCategories')
            ->where('shop.id = :shopId')
            ->setParameter('shopId', $this->shop->getId());
        // Product ids selection
        if (count($this->productIds) > 0) {
            $condition = '(';
            $idx = 0;
            foreach ($this->productIds as $productId) {
                $condition.= 'articles.id = '.$productId;
                if ($idx < (count($this->productIds) - 1)) {
                    $condition.= ' OR ';
                }
                $idx++;
            }
            $condition.= ')';
            $builder->andWhere($condition);
        }
        // Export disabled product
        if (!$this->exportDisabledProduct) {
            $builder->andWhere('articles.active = 1');
        }
        // Export only Lengow products
        if ($this->exportSelectionEnabled) {
            $builder->andWhere('attributes.lengowShop'.$this->shop->getId().'Active = 1');
        }
        // Export out of stock products
        if (!$this->exportOutOfStock) {
            $builder->andWhere('details.inStock > 0');
        }
        // If no variation, get only parent products
        if (!$this->exportVariationEnabled) {
            $builder->andWhere('details.kind = 1');
        }
        $builder->distinct()
            ->orderBy('categories.id')
            ->groupBy('categories.id', 'details.id');
        $articles = $builder->getQuery()->getArrayResult();

        // Get parent foreach article
        foreach ($articles as $article) {
            if (is_null($article['isParent'])) {
                $articlesByParent[$article['parentId']] = array(
                    'type'     => 'simple',
                    'detailId' => $article['detailId']
                );
            } else {
                if (!array_key_exists($article['parentId'], $articlesByParent)) {
                    $articlesByParent[$article['parentId']] = array(
                        'type'     => 'parent',
                        'childs'   => array($article),
                        'detailId' => $article['detailId'],
                    );
                } else {
                    $articlesByParent[$article['parentId']]['childs'][] = $article;
                }
                if ($article['kind'] == 1) {
                    $articlesByParent[$article['parentId']]['detailId'] = $article['detailId'];
                }
            }
            
        }
        foreach ($articlesByParent as $key => $parentArticle) {
            if ($parentArticle['type'] == 'parent') {
                $articleToExport[] = array(
                    'type'     => 'parent',
                    'detailId' => $parentArticle['detailId']
                );
                if ($this->exportVariationEnabled) {
                    foreach ($parentArticle['childs'] as $child) {
                        $articleToExport[] = array(
                            'type'     => 'child',
                            'detailId' => $child['detailId'],
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
     * @return int Number of products found
     */
    public function getTotalProducts()
    {
        $exportOutOfStockDefaultValue = $this->exportOutOfStock;
        $exportLengowSelectionDefaultValue = $this->exportSelectionEnabled;
        $exportVariationDefaultValue = $this->exportVariationEnabled;
        $this->exportOutOfStock = true; // Force out of stock products
        $this->exportSelectionEnabled = false;
        $this->exportVariationEnabled = true;
        $products = $this->getIdToExport();
        $total = count($products);
        // Reset default values
        $this->exportOutOfStock = $exportOutOfStockDefaultValue;
        $this->exportSelectionEnabled = $exportLengowSelectionDefaultValue;
        $this->exportVariationEnabled = $exportVariationDefaultValue;
        return $total;
    }

    /**
     * Get number of products exported in Lengow
     *
     * @return int Number of product exported
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
                    $authorizedValue = Shopware_Plugins_Backend_Lengow_Components_LengowFeed::$AVAILABLE_FORMATS;
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
            $params[ $param ] = array(
                'authorized_values' => $authorizedValue,
                'type'              => $type,
                'example'           => $example
            );
        }
        return json_encode($params);
    }
}
