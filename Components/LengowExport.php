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
     * Default fields.
     */
    public static $DEFAULT_FIELDS = array(
        'id' => 'id',
        'sku' => 'sku',
        'sku_supplier' => 'sku_supplier',
        'ean' => 'ean',
        'name' => 'name',
        'quantity' => 'quantity',
        'category' => 'category',
        'status' => 'status',
        'url' => 'url',
        'price_excl_tax' => 'price_excl_tax', // Instant t, avec le discount
        'price_incl_tax' => 'price_incl_tax',
        'price_before_discount_excl_tax' => 'price_before_discount_excl_tax',
        'price_before_discount_incl_tax' => 'price_before_discount_incl_tax',
        'discount_percent' => 'discount_percent',
        'discount_start_date' => 'discount_start_date',
        'discount_end_date' => 'discount_end_date',
        'currency'  => 'currency',
        'shipping_cost' => 'shipping_cost',
        'shipping_delay' => 'shipping_delay',
        'weight' => 'weight',
        'width' => 'width',
        'height' => 'height',
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
        'description' => 'description',
        'description_long' => 'description_long',
        'description_html' => 'description_html',
        'meta_title' => 'meta_keyword',
        'meta_keyword' => 'meta_keyword',
    );

    private $productFields = array();

    /**
     * Config elements
     * Refer to createConfig() in Bootstrap.php
     */
    private $configFields = array(
        'exportVariation',
        'exportOutOfStock',
        'exportDisabledProduct',
        'exportLengowSelection'
    );

    /**
     * Export format
     */
    private $format;

    /**
     * Display result on screen
     */
    private $stream;

    /**
     * List of articles to display
     */
    private $productIds = array();

    /**
     * Limit number of results
     */
    private $limit = 0;

    /**
     * Get results from specific index
     */
    private $offset = 0;

    /**
     * Export out of stock articles
     */
    private $exportOutOfStock;

    /**
     * Export variant articles
     */
    private $exportVariation;

    /**
     * Export Lengow products only
     */
    private $exportLengowSelection;

    /**
     * Language 
     */
    private $languageId;

    /**
     * Export disabled articles
     */
    private $exportDisabledProduct;

    /**
     * Export mode (size|null)
     */
    private $mode;

    /**
     * Feed to display/write data
     */
    private $feed = null;

    /**
     * LengowExport constructor.
     * @param null $shop Shop id
     * @param $params array Request params
     */
    public function __construct($shop = null, $params)
    {
        $this->shop = $shop;

        foreach ($params as $key => $value) {
            $this->$key = $value;
        }

        $this->em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();

        $this->setFormat();
        $this->loadDefaultConfig();

        $this->productFields = self::$DEFAULT_FIELDS;
    }

    /**
     * Check whether or not the format exists
     * If not specified (null), get the default format of the configuration
     * @return bool true if specified format is supported
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException If the format isn't supported by Lengow
     */
    private function setFormat()
    {
       if (!in_array($this->format, Shopware_Plugins_Backend_Lengow_Components_LengowFeed::$AVAILABLE_FORMATS)) {
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/export/error_illegal_export_format')
            );
        }
        return true;
    }

    /**
     * Get values from shop config if params have not been set
     */
    private function loadDefaultConfig()
    {
        foreach ($this->configFields as $field) {
            if (!isset($this->$field)) {
                $configName = 'lengow' . ucwords($field);
                $this->$field = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getConfigValue(
                    $configName,
                    $this->shop->getId()
                );
            }
        }
    }

    /**
     * Export process
     * Get products to export from the params and create the feed
    */
    public function exec()
    {
        $products = $this->getIdToExport();

        if ($this->mode != 'size') {
            try {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export', 
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/export/start'), 
                    $this->logOutput
                );

                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/export/start_for_shop', 
                        array(
                            'name_shop' => $this->shop->getName(),
                            'id_shop'   => $this->shop->getId()
                        )
                    ),
                    $this->logOutput
                );

                // Setup feed
                $this->feed = new Shopware_Plugins_Backend_Lengow_Components_LengowFeed(
                    $this->stream,
                    $this->format,
                    $this->shop->getName()
                );

                $this->export($products);

                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/export/end'),
                    $this->logOutput
                );
            } catch (LengowException $e) {
                $errorMessage = $e->getMessage();
            } catch (Exception $e) {
                $errorMessage = '[Shopware error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            }

            if (isset($errorMessage)) {
                $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage(
                    $errorMessage, 
                    'en_GB'
                );
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/export/export_failed', 
                        array(
                            'decoded_message' => $decodedMessage
                        )
                    ),
                    $this->logOutput
                );
            }
        } else {
            $total = count($products);

            // As Shopware uses default articles as "parents", we need to count it twice
            foreach ($products as $article) {
                if ($article['kind'] == 1 && $article['isParent'] != null) {
                    $total++;
                }
            }

            echo $total;
        }
    }

    /**
     * Export products in feed
     * @param $articles array List of articles to export
     */
    private function export($articles)
    {
        $productsToExport = array();

        // Create Lengow products
        foreach ($articles as $article) {
            $details = $this->em->getReference(
                'Shopware\Models\Article\Detail',
                $article['detailId']
            );

            $lengowVariantProduct = new Shopware_Plugins_Backend_Lengow_Components_LengowProduct(
                $details,
                $this->shop,
                'child',
                $this->logOutput
            );

            $productAttributes = $lengowVariantProduct->getAttributes();

            // Get article attributes for the header
            foreach ($productAttributes as $value => $name) {
                $option = Shopware_Plugins_Backend_Lengow_Components_LengowFeed::formatFields(
                    $value,
                    $this->format
                );
                $this->productFields[$value] = $option;
            }

            $lengowParentProduct = null;
            $isSimple = false;

            // If the current article is the default article, add the parent article to the feed
            if ($details->getArticle()->getConfiguratorSet() === null) { // If it has no variants
                $isSimple = true;
                $lengowParentProduct = new Shopware_Plugins_Backend_Lengow_Components_LengowProduct(
                    $details,
                    $this->shop,
                    'simple',
                    $this->logOutput
                );
                $productsToExport[] = $lengowParentProduct;
            } else if ($details->getKind() == 1) { // If parent
                $lengowParentProduct = new Shopware_Plugins_Backend_Lengow_Components_LengowProduct(
                    $details,
                    $this->shop,
                    'parent',
                    $this->logOutput
                );
                $productsToExport[] = $lengowParentProduct;
            }

            if ($this->exportVariation && !$isSimple) {
                $productsToExport[] = $lengowVariantProduct;
            }
        }

        $header = array_merge(self::$DEFAULT_FIELDS, $this->productFields);
        $header = array_unique($header);

        $this->feed->write('header', $header);

        $numberOfProducts = 0;
        $displayedProducts = 0;
        $isFirst = true; // Used for json format
        $total = count($productsToExport);

        Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
            'Export',
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                'log/export/nb_product_found', 
                array("nb_product" => $total)
            ),
            $this->logOutput
        );

        // Write products in the feed when the header is ready
        foreach ($productsToExport as $product) {
            if (($this->offset != 0 && $this->offset > $numberOfProducts)) {
                $numberOfProducts++;
                continue;
            }

            if ($this->limit != 0 && $this->limit <= $displayedProducts) {
                break;
            }

            $data = $this->getFields($product);
            $this->feed->write('body', $data, $isFirst);
            $isFirst = false;
            $numberOfProducts++;
            $displayedProducts++;

            // Log each time 10 products are exported
            if ($displayedProducts > 0 && $displayedProducts % 10 == 0 && $displayedProducts < $total) {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/export/count_product', array(
                        'numberOfProducts' => $displayedProducts
                    )),
                    $this->logOutput
                );
            }
        }

        $success = $this->feed->end();
        if (!$success) {
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/export/error_folder_not_writable')
            );
        }

        if (!$this->stream) {
            $feed_url = $this->feed->getUrl();
            if ($feed_url && php_sapi_name() != "cli") {
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                    'Export',
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/export/your_feed_available_here', 
                        array(
                            'feed_url' => $feed_url
                        )
                    ),
                    $this->logOutput
                );
            }
        }
    }


    /**
     * Build the query from the params
     *
     * @return array List of ids to export
    */
    private function getIdToExport()
    {
        $selection = array(
            'configurator.id AS isParent',
            'details.id AS detailId',
            'details.kind AS kind'
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
            ->where('shop.id = :shopId')
            ->setParameter('shopId', $this->shop->getId());

        // Product ids selection
        if (count($this->productIds) > 0) {
            $condition = '(';
            $idx = 0;

            foreach ($this->productIds as $productId) {
                $condition.= 'categories.id = ' . $productId;
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
            $builder->andWhere('details.active = 1');
        }

        // Export only Lengow products
        if ($this->exportLengowSelection) {
            $builder->andWhere('attributes.lengowShop' . $this->shop->getId() . 'Active = 1');
        }

        // Export out of stock products
        if (!$this->exportOutOfStock) {
            $builder->andWhere('details.inStock > 0');
        }

        // If no variation, get only parent products
        if (!$this->exportVariation) {
            $builder->andWhere('details.kind = 1');
        }

        $builder->orderBy('categories.id, details.kind')
                ->groupBy('categories.id', 'details.id');

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Get default fields and attribute values for a given product
     *
     * @param object $lengowProduct The product to export
     * @return array Product data
     */
    private function getFields($lengowProduct = null)
    {
        $productFields = array();
        // Default fields
        foreach ($this->productFields as $key => $field) {
            $productFields[$key] = $lengowProduct->getData($field);
        }

        return $productFields;
    }
}