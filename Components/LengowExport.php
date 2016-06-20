<?php

/**
 * LengowExport.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class LengowExport
{

    /**
     * Default fields.
     */
    public static $DEFAULT_FIELDS = array(
        'id'    => 'id',
        'sku'   => 'sku',
        'ean'   => 'ean',
        'name'  => 'name',
        'quantity'  => 'quantity',
        'breadcrumb' => 'breadcrumb',
        'status' => 'status',
        'url' => 'url',
        'price_excl_tax' => 'price_excl_tax',
        'price_incl_tax' => 'price_incl_tax',
        'discount_percent' => 'discount_percent',
        'discount_start_date' => 'discount_start_date',
        'discount_end_date' => 'discount_end_date',
        'currency'  => 'currency',
        'shipping_cost' => 'shipping_cost',
        'shipping_delay' => 'shipping_delay',
        'weight' => 'weight',
        'width' => 'width',
        'height' => 'height',
        'supplier_sku' => 'supplier_sku',
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
        'description_short' => 'description_short',
        'description_html' => 'description_html',
        'meta_keyword' => 'meta_keyword',
    );

    /**
     * File ressource
     */
    private $fields = array();

    private $configFields = array(
        'exportVariation',
        'exportOutOfStock',
        'exportDisabledProduct'
    );

    /**
     * Export format
     */
    private $format;

    /**
     * Display result on screen
     */
    private $stream;

    private $productIds = array();

    private $limit = 0;

    private $offset = 0;

    private $exportOutOfStock;

    private $exportVariation;

    private $exportLengowSelection;

    private $languageId;

    private $exportDisabledProduct;

    private $mode;

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

        $this->setFormat();
        $this->loadDefaultConfig();
    }

    /**
     * Check whether or not the format exists
     * If not specified (null), get the default format of the configuration
     * @return bool true if specified format is supported
     * @throws Exception If the format isn't supported by Lengow
     */
    private function setFormat()
    {
        if (!in_array($this->format, Shopware_Plugins_Backend_Lengow_Components_LengowFeed::$AVAILABLE_FORMATS)) {
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log.export.error_illegal_export_format')
                );
        }
        return true;
    }

    /**
     * Get default values if params has not been set
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
     * @throws Exception
     * @throws \Doctrine\ORM\ORMException
     */
    public function exec()
    {
        $shop = Shopware()->Models()->getReference('Shopware\Models\Shop\Shop', $this->shop->getId());

        // If the shop exists
        if ($shop) {

            $products = $this->exportIds();

            if ($this->mode != 'size') {

                /*LengowMain::log('Export', LengowMain::setLogMessage('log.export.start'), $this->log_output);
                LengowMain::log(
                    'Export',
                    LengowMain::setLogMessage('log.export.start_for_shop', array(
                        'name_shop' => $this->shop->getName(),
                        'id_shop'   => $this->shop->getId()
                    )),
                    $this->log_output
                );*/

                $this->feed = new Shopware_Plugins_Backend_Lengow_Components_LengowFeed(
                    $this->stream,
                    $this->format,
                    $this->shop->getName()
                );

                $header = array_keys(self::$DEFAULT_FIELDS);
                $this->feed->write('header', $header);

                $numberOfProducts = 0;

                /*LengowMain::log(
                    'Export',
                    LengowMain::setLogMessage('log.export.nb_product_found', array("nb_product" => count($products))),
                    $this->log_output
                );*/

                foreach ($products as $product) {
                    $details = Shopware()->Models()->getReference(
                        'Shopware\Models\Article\Detail',
                        $product['detailId']
                    );

                    $lengowProduct = new Shopware_Plugins_Backend_Lengow_Components_Product(
                        $details,
                        $this->shop
                    );
                    $data = $this->getFields($lengowProduct);
                    $this->feed->write('body', $data, false);
                }

                $this->feed->write('footer');

                /*LengowMain::log(
                    'Export',
                    LengowMain::setLogMessage('log.export.end'),
                    $this->log_output
                );*/
            } else {
                return count($products);
            }
        }
    }

    private function export($products, $fields)
    {

        foreach ($products as $p) {
            $article = Shopware()->Models()->getReference(
                'Shopware\Models\Article\Details',
                $p['articleId']
            );

            $lengowProduct = new Shopware_Plugins_Backend_Lengow_Components_Product(
                $article,
                $shop
            );
            $data = $this->getFields($lengowProduct);

            $this->feed->write('body', $data, false);
        }
    }

    private function exportIds()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('articles.id AS articleId', 'details.id AS detailId'))
            ->from('Shopware\Models\Shop\Shop', 'shop')
            ->leftJoin('shop.category', 'categories')
            ->leftJoin('categories.allArticles', 'articles')
            ->leftJoin('articles.attribute', 'attributes')
            ->leftJoin('articles.details', 'details')
            ->where('shop.id = :shopId')
            ->setParameter('shopId', $this->shop->getId());

        $condition = '(';
        $idx = 0;
        foreach ($this->productIds as $productId) {
            $condition.= ' articles.id = ' . $productId;
            if ($idx < count($this->productIds)) {
                $condition.= ' OR ';
            }
            $idx++;
        }

        if ($condition != '(') {
            $condition.= ')';
            $builder->andWhere($condition);
        }

        // Export only Lengow products
        if ($this->exportLengowSelection) {
            $builder->andWhere('attributes.lengowLengowActive = 1');
        }

        // Export out of stock products
        if (!$this->exportOutOfStock) {
            $builder->andWhere('details.inStock > 0');
        }

        // If no variation, get only parent products
        if (!$this->exportVariation) {
            $builder->andWhere('details.kind = 1');
        }

        // Offset option
        if ($this->offset > 0) {
            $builder->setFirstResult($this->offset);
        }

        // Limit option
        if ($this->limit > 0) {
            $builder->setMaxResults($this->limit);
        }

        $builder->orderBy('articles.id, details.kind')
                ->groupBy('articles.id', 'details.id');

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Make the export for a product with current format
     *
     * @param object $lengow_product The product to export
     * @return array Product data
     */
    private function getFields($lengow_product = null)
    {
        $array_product = array();
        // Default fields
        foreach (self::$DEFAULT_FIELDS as $key => $field) {
            if ($lengow_product->getData('sku') == 'SW10170') {
                var_dump($lengow_product->getData($field));
            }
            $array_product[$field] = $lengow_product->getData($field);
        }

        // Product attributes
        foreach ($lengow_product->getAttributes() as $attribute) {
            // Make sure to replace whitespaces for custom attributes
            $name = $attribute['name'];
            $array_product[$name] = $attribute['value'];
        }
        return $array_product;
    }

    public function getFileName()
    {
        return $this->feed->getFilename();
    }
}