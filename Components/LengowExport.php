<?php

/**
 * LengowExport.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowExport
{

	/**
     * Version
     */
    const VERSION = '1.0';

    /**
     * Default fields.
     */
    public static $DEFAULT_FIELDS = array(
    	'id_article',
        'name_article',
        'number_article',
        'manufacturer_number',
        'supplier',
        'category',
        'category_parent',
        'price',
        'price_wt',
        'price_discount',
        'percent_discount',
        'purchase_price',
    	'tax',
        'currency',
        'available_article',
        'in_stock',
        'quantity',
        'ean',
        'url_article',
        'meta_title',
        'meta_keywords',
        'description',
        'long_description',
        'unit',
        'unit_reference',
        'unit_pack',
        'unit_purchase',
        'min_purchase',
        'max_purchase',
        'shipping_time',
        'shipping_price',
        'weight',
        'width',
        'height',
        'length',
        'type_article',
        'id_parent',
        'variant_article'
    );

    /**
     * CSV separator.
     */
    public static $CSV_SEPARATOR = '|';

    /**
     * CSV protection.
     */
    public static $CSV_PROTECTION = '"';

    /**
     * CSV End of line.
     */
    public static $CSV_EOL = "\r\n";

    /**
     * Additional head attributes export.
     */
    private $head_attributes_export;

    /**
     * Additional head image export.
     */
    private $head_images_export;

    /**
     * File ressource
     */
    private $handle;

    /**
     * File name
     */
    private $filename;

    /**
     * Temp file name
     */
    private $filename_temp;

    /**
	* File ressource
	*/
	private $fields = array();

	/**
     * Product properties
     */
    private $attributes = array();

    /**
     * Export format
     */
    private $format = 'csv';

    /**
    * Full export products + variant.
    */
    private $full = true;

    /**
	* Export all products.
	*/
	private $all = true;

	/**
	* Max images.
	*/
	private $max_images = 0;

    /**
     * Export enable and disable products
     */
    private $all_products = false;

    /**
     * Export product attributes
     */
    private $export_attributes = false;

    /**
    * Title with attributes and features
    */
    public static $full_title = true;

    /**
    * Export out of stock product
    */
    private $export_out_stock = true;

    /**
     * Export on file
     */
    private $stream = true;

    /**
     * Shop to export
     */
    private $shop = null;

    /**
    * Construct new Lengow export
    * 
    * @return Exception Error
    */
    public function __construct($format = null, $all = null , $all_products = null, $fullmode = null, $export_attributes = null, $full_title = null, $out_stock = null, $stream = null, $shop = null)  
    {
        try {
            $this->setShop($shop);
			$this->setFormat($format);
			$this->setProducts($all);
			$this->setAllProducts($all_products);
            $this->setFullmode($fullmode);
			$this->setExportAttributes($export_attributes);
            $this->setTitle($full_title);
            $this->setExportOutOfStock($out_stock);
			$this->setStream($stream);
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	* Set format to export
    * 
	* @param string $format The format to export
	* @return boolean.
	*/
	public function setFormat($format)
	{
		if ($format !== null)
		{
			$available_formats = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportFormats();
			$array_formats = array();
			foreach ($available_formats as $f) {
				$array_formats[] = $f->name;
			}
			if (in_array($format, $array_formats))
			{
				$this->format = $format;
				return true;
			}
			throw new Exception('Illegal export format');
		} else {
			$this->format = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportFormat($this->shop->getId());
		}
		return false;
	}

	/**
	* Set selected or not products to export
    * 
	* @param boolean $all True for all products, False for selected products by Lengow
	* @return boolean.
	*/
	public function setProducts($all)
	{
		if ($all !== null && is_bool($all)) {
			$this->all = $all;
		} else {
			$this->all = Shopware_Plugins_Backend_Lengow_Components_LengowCore::isExportAllProducts($this->shop->getId());
		}
	}

	/**
	* Set disabled or not products to export
    * 
	* @param boolean $all_product True for all products, False for only enabled products
	* @return boolean.
	*/
	public function setAllProducts($all_products)
	{
		if ($all_products !== null && is_bool($all_products)) {
			$this->all_products = $all_products;
		} else {
			$this->all_products = Shopware_Plugins_Backend_Lengow_Components_LengowCore::exportAllProducts($this->shop->getId());
		}
	}

    /**
    * Set FullMode
    * 
    * @param boolean $fullmode True for variant products, False for only parent Products
    * @return boolean.
    */
    public function setFullmode($fullmode)
    {
        if ($fullmode !== null) {
            $this->full = $fullmode;
        } else {
            $this->full = Shopware_Plugins_Backend_Lengow_Components_LengowCore::isExportFullmode($this->shop->getId());
        }
    }

	/**
	* Set attributes product or not to export
    * 
	* @param boolean $export_attributes True for export attributes, False for no attributes
	* @return boolean.
	*/
	public function setExportAttributes($export_attributes) 
    {
        if($export_attributes !== null && is_bool($export_attributes)) {
            $this->export_attributes = $export_attributes;
        } else {
            $this->export_attributes = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportAttributes($this->shop->getId());
        }
    }

    /**
    * Set title param export
    * 
    * @param boolean $title False for only title, True for title + attribute
    * @return boolean.
    */
    public function setTitle($full_title)
    {
        if ($full_title !== null) {
            self::$full_title = $full_title;
        } else {
            self::$full_title = Shopware_Plugins_Backend_Lengow_Components_LengowCore::exportTitle($this->shop->getId());
        }
    }

    /**
    * Set export out of stock products
    * 
    * @param boolean $export_out_stock True for export out of stock product, False for only in stock products
    * @return boolean.
    */
    public function setExportOutOfStock($out_stock)
    {
        if ($out_stock != null) {
            $this->export_out_stock = $out_stock;
        } else {
            $this->export_out_stock = Shopware_Plugins_Backend_Lengow_Components_LengowCore::exportOutOfStockProduct($this->shop->getId());
        }
    }

    /**
	* Set stream export
    * 
	* @param boolean $export_attributes True for export in a file, False for direct export
	* @return boolean.
	*/
	public function setStream($stream)
	{
		if ($stream !== null && is_bool($stream)) {
			$this->stream = $stream;
		} else {
			$this->stream = (Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportInFile($this->shop->getId()) ? false : true);
		}
	}

    /**
    * Set the shop to export
    * 
    * @param boolean $all True for all products, False for selected products by Lengow
    * @return boolean.
    */
    public function setShop($shop)
    {
        if ($shop !== null) {
            $this->shop = $shop;
        }
    }
    

	/**
     * Execute the export
     * 
     * @return mixed
     */
    public function exec() 
    {
        try {
            Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Export : init');
            
            $i = 0; // Count Product
            $y = 0; // Count parent product
            $z = 0; // Count variant product

            //Make header
            $this->max_images = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportImages($this->shop->getId());           
            if($this->export_attributes) {
                foreach(Shopware_Plugins_Backend_Lengow_Components_LengowProduct::getAttributes() as $attribute) {
                    $this->attributes[$attribute['name']] = $this->_toFieldname($attribute['name']);
                }
            }
            $this->_makeFields();
            $this->_write('header');            
            // Get Product
            $products = Shopware_Plugins_Backend_Lengow_Components_LengowProduct::getExportIds($this->all, $this->all_products, $this->shop);
            $last = end($products);
            // Build product line
            if(!empty($products)) {
                foreach($products as $p) {
                    $lengow_product = new Shopware_Plugins_Backend_Lengow_Components_LengowProduct($p['article'], null, $this->shop);
                    $is_last = false;
                    if($p['article'] == $last['article']) {
                        $is_last = true;
                    }
                    // Export out of stock products
                    if($this->export_out_stock) {
                        $this->_write('data', $this->_make($lengow_product), $is_last);
                    } else {
                        if ($lengow_product->getInStockProduct()) {
                            $this->_write('data', $this->_make($lengow_product), $is_last);
                        }                                   
                    } 
                    // Export variation product
                    if($lengow_product->getConfiguratorSet() !== NULL) {
                        if($this->full) {    
                            // Get Variations
                            $variations = $lengow_product->getDetails();
                            $total_variations = count($variations);
                            $count = 0;
                            $id_product = $lengow_product->getId();
                            // Get data variations
                            if(!empty($variations)) {
                                foreach($variations as $variation) {
                                    $count++;
                                    if($count == $total_variations) {
                                        $is_last = true;
                                    }
                                    $lengow_variation = new Shopware_Plugins_Backend_Lengow_Components_LengowProduct($id_product, $variation->getId(), $this->shop);
                                    if($this->export_out_stock) {
                                        $this->_write('data', $this->_make($lengow_variation, $variation->getId()), $is_last);
                                    } else {
                                        if ($lengow_variation->getInStockProduct($variation->getId())) {
                                            $this->_write('data', $this->_make($lengow_variation, $variation->getId()), $is_last);
                                        }                                   
                                    }
                                $z++;
                                }
                            }
                        }
                        $y++;   
                    }
                    $lengow_product = null;
                    if ($i % 10 == 0) {
                        Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Export : ' . $i . ' products');
                    }
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                    $i++;
                }
                // Create a Lengow Log
                $txt = 'Export : ' . $i . ' products (' . ($i-$y) . ' simples and ' . $y . ' parents) from the shop : ' . $this->shop->getName();
                if($this->full) {
                    $txt = 'Export : ' . $i . ' products (' . ($i-$y) . ' simples, ' . $y . ' parents with ' . $z . ' variants) from the shop : ' . $this->shop->getName();
                } 
                Shopware_Plugins_Backend_Lengow_Components_LengowCore::log($txt, false, true);
            }
            $this->_write('footer');
            if (!$this->stream) {
                rename($this->filename_temp, $this->filename);
                echo $this->_getFileLink($this->format);
            }
            Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Write final export file');
            Shopware_Plugins_Backend_Lengow_Components_LengowCore::log('Export : end');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Make fields to export
     */
    private function _makeFields() 
    {
        foreach (self::$DEFAULT_FIELDS as $field) {
            $this->fields[$field] = $field;
        }
        // Attributes
        if ($this->export_attributes) {
            foreach ($this->attributes as $key => $attr) {
                $this->fields[$key] = $attr;
            }
        }
        //Images
        if ($this->max_images) {
            for ($i = 1; $i <= ($this->max_images); $i++) {
                $this->fields['image_' . $i] = 'image_' . $i;
            }
        }
    }

    /**
     * Make the export for a product with current format
     * 
     * @param object $lengow_product The product to export
     * @param $id_variation is a id of a variation
     * @return array Product data
     */
    private function _make($lengow_product, $id_variation = null) 
    {
        $array_product = array();
        // Default fields
        foreach (self::$DEFAULT_FIELDS as $key => $field) {
            $array_product[$field] = $lengow_product->getData($field, $id_variation);
        }
        // Attributes
        foreach($this->attributes as $key => $field) {
            $array_product[$field] = $lengow_product->getAttributeData($key, $id_variation);
        }
        // Images
        if ($this->max_images) {
            for ($i = 1; $i <= ($this->max_images); $i++) {
                $array_product['image_' . $i] = $lengow_product->getData('image_' . $i);
            }
        }
        return $array_product;
    }

    /**
     * Write the export on file or screen
     * 
     * @param string $type
     * @param array $data Produt data
     * @param boolean $last True if product is last
     * @return mixed
     */
    private function _write($type, $data = null, $last = false) 
    {
        switch ($type) {
            case 'header' :
                $head = $this->_getHeader();
                if ($this->stream) {
                    switch ($this->format) {
                        case 'csv':
                            header('Content-Type: text/plain; charset=utf-8');
                            echo $head;
                            break;
                        case 'xml':
                            header('Content-Type: text/xml; charset=utf-8');
                            echo $head;
                            break;
                        case 'json':
                            header('Content-Type: application/json; charset=utf-8');
                            echo $head;
                            break;
                        case 'yaml':
                            header('Content-Type: text/x-yaml; charset=utf-8');
                            echo $head;
                            break;
                    }
                }
                if (!$this->stream)
                    $this->_writeOnFile($head);
                break;
            case 'data' :
                if (!$this->stream) {
                    $content = '';
                }
                $line = '';
                switch ($this->format) {
                    case 'csv':
                        foreach ($this->fields as $name) {
                            $line .= self::$CSV_PROTECTION . str_replace(array(self::$CSV_PROTECTION, '\\'), '', (isset($data[$name]) ? $data[$name] : '')) . self::$CSV_PROTECTION . self::$CSV_SEPARATOR;
                        }
                        $line = rtrim($line, self::$CSV_SEPARATOR) . self::$CSV_EOL;
                        break;
                    case 'xml' :
                        $line .= '<product>' . "\r\n";
                        foreach ($this->fields as $name) {
                            $line .= '<' . $name . '><![CDATA[' . (isset($data[$name]) ? $data[$name] : '') . ']]></' . $name . '>' . "\r\n";
                        }
                        $line .= '</product>' . "\r\n";
                        break;
                    case 'json' :
                        foreach ($this->fields as $name) {
                            $json_array[$name] = $data[$name];
                        }
                        $line .= json_encode($json_array) . (!$last ? ',' : '');
                        break;
                    case 'yaml' :
                        $line .= '  ' . '"product":' . "\r\n";
                        foreach ($this->fields as $name) {
                            $line .= '    ' . '"' . $name . '":' . $this->_addSpaces($name, 22) . (isset($data[$name]) ? $data[$name] : '') . "\r\n";
                        }
                        break;
                }
                if (!$this->stream) {
                    $this->_writeOnFile($line);
                } else {
                    echo $line;
                }
                flush();
                break;
            case 'footer' :
                $footer = $this->_getFooter();
                if (!$this->stream) {
                    $this->_writeOnFile($footer);
                    $this->_closeFile();
                    // 
                } else {
                    echo $footer;
                }
                break;
        }
    }

    /**
     * Open and write data on file
     * 
     * @param string $data The data
     */
    private function _writeOnFile($data) 
    {
        if (!$this->handle) {
            $folder = mb_strtolower(str_replace(' ', '_', $this->shop->getName()));
            if (!file_exists(Shopware()->Plugins()->Backend()->Lengow()->Path() . 'Export/' . $folder)) {
                mkdir(Shopware()->Plugins()->Backend()->Lengow()->Path() . 'Export/' . $folder);
            }
            $this->filename_temp = Shopware()->Plugins()->Backend()->Lengow()->Path() . 'Export/' . $folder . '/flux-temp.' . $this->format;
            $this->filename = Shopware()->Plugins()->Backend()->Lengow()->Path() . 'Export/' . $folder . '/flux.' . $this->format;
            $this->handle = fopen($this->filename_temp, 'w+');
        }
        fwrite($this->handle, $data);
    }

    /**
     * Close export file
     */
    private function _closeFile()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }

 	/**
     * Get header
     * 
     * @return varchar The header
     */
    private function _getHeader() 
    {
        $head = '';
        switch ($this->format) {
            case 'csv' :
                foreach ($this->fields as $name) {
                    $head .= self::$CSV_PROTECTION . $this->_toUpperCase($name) . self::$CSV_PROTECTION . self::$CSV_SEPARATOR;
                }
                return rtrim($head, self::$CSV_SEPARATOR) . self::$CSV_EOL;
            case 'xml' :
                return '<?xml version="1.0" ?>' . "\r\n"
                        . '<catalog>' . "\r\n";
            case 'json' :
                return '{"catalog":[';
            case 'yaml' :
                return '"catalog":' . "\r\n";
        }
    }

    /**
     * Get footer
     * 
     * @return varchar The footer
     */
    private function _getFooter() 
    {
        switch ($this->format) {
            case 'csv' :
                return '';
            case 'xml' :
                return '</catalog>';
            case 'json' :
                return ']}';
            case 'yaml' :
                return '';
        }
    }

    /**
	* For CSV, transform header to uppercase without accent
    * 
	* @param string $str The fieldname
	* @return string The formated header
	*/
	private function _toUpperCase($str)
	{
		return substr(strtoupper(preg_replace(
			'/[^a-zA-Z0-9_]+/', '', str_replace(
				array(' ', '\''),
				'_', 
				Shopware_Plugins_Backend_Lengow_Components_LengowCore::replaceAccentedChars($str)
			)
		)), 0, 58);
	}

	/**
     * For YAML, JSON, XML, transform fieldname without accent and spaces
     * 
     * @param string $str The fieldname
     * @return string The formated fieldname
     */
    private function _toFieldname($str) 
    {
        return strtolower(preg_replace(
        	'/[^a-zA-Z0-9_]+/', '', str_replace(
        		array(' ', '\''),
        		'_',
        		Shopware_Plugins_Backend_Lengow_Components_LengowCore::replaceAccentedChars($str)
        	)
        ));
    }

    /**
     * For YAML, add spaces to have good indentation
     * 
     * @param string $name The fielname
     * @param string $size The max spaces
     * @return string Spaces.
     */
    private function _addSpaces($name, $size) 
    {
        $strlen = strlen($name);
        $spaces = '';
        for ($i = $strlen; $i < $size; $i++) {
            $spaces .= ' ';
        }
        return $spaces;
    }

    /**
     * Get export file link
     * 
     * @param string $format
     * @return mixed False is stream export, string if file exists
     */
    private function _getFileLink($format = null) 
    {
        if(!$this->stream) {
            $format = ($format == null) ? Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportFormat() : $format;
            $folder = mb_strtolower(str_replace(' ', '_', $this->shop->getName()));
            $fileExist = file_exists(Shopware()->Plugins()->Backend()->Lengow()->Path() . 'Export/' . $folder . '/flux.' . $format);
            if ($fileExist) {
                $pathPlugin = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getPathPlugin();
                $fileExportUrl = 'http://' . $_SERVER['SERVER_NAME'] . $pathPlugin . 'Export/' . $folder . '/flux.' . $format;
                return ('Your export file is available here') . ' : <a href="' . $fileExportUrl . '" target="_blank">' . $fileExportUrl . '</a>';
            } else {
                return ('Your file export is not yet created. Click on the link below to generate it.');
            }
        }
        return false;
    }

}