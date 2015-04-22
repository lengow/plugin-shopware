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
     * Export format
     */
    private $format = null;

    /**
	* Export all products.
	*/
	private $all = true;

    /**
     * Export active products
     */
    private $all_products = null;

    /**
     * Export product attributes
     */
    private $export_attributes = null;

    /**
     * Export on file
     */
    private $stream = null;

    /**
     * Max images.
     */
    private $max_images = 0;

    public function __construct($format = null, $all = null , $all_products = null, $steam = null, $export_attributes = null) {
        try {
			$this->setFormat($format);
			$this->setProducts($all);
			$this->setAllProducts($all_products);
			$this->setExportAttributes($export_attributes);
			$this->setStream($stream);
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	* Set format to export.
	*
	* @param string $format The format to export
	*
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
			$this->format = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportFormat();
		}
		return false;
	}

	/**
	* Set selected or not products to export.
	*
	* @param boolean $all True for all products, False for selected products by Lengow
	*
	* @return boolean.
	*/
	public function setProducts($all)
	{
		if ($all !== null && is_bool($all)) {
			$this->all = $all;
		} else {
			$this->all = Shopware_Plugins_Backend_Lengow_Components_LengowCore::isExportAllProducts();
		}
	}

	/**
	* Set disabled or not products to export.
	*
	* @param boolean $all_product True for all products, False for only enabled products
	*
	* @return boolean.
	*/
	public function setAllProducts($all_products)
	{
		if ($all_products !== null && is_bool($all_products)) {
			$this->all_products = $all_products;
		} else {
			$this->all_products = Shopware_Plugins_Backend_Lengow_Components_LengowCore::exportAllProducts();
		}
	}

	/**
	* Set attributes product or not to export.
	*
	* @param boolean $export_attributes True for export attributes, False for no attributes
	*
	* @return boolean.
	*/
	public function setExportAttributes($export_attributes) {
        if($export_attributes !== null && is_bool($export_attributes)) {
            $this->export_attributes = $export_attributes;
        } else {
            $this->export_attributes = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportAttributes();
        }
    }

    /**
	* Set stream export.
	*
	* @param boolean $export_attributes True for export in a file, False for direct export
	*
	* @return boolean.
	*/
	public function setStream($stream)
	{
		if ($stream !== null && is_bool($stream)) {
			$this->stream = $stream;
		}
		else {
			$this->stream = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportInFile();
		}
	}


}