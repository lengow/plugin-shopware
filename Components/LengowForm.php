<?php

/**
 * LengowForm.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowForm
{
	/**
	 * @var \Shopware\Models\Config\Form $form
	 */
	private $form;

	/**
	 * Construct new Lengow form
     * 
	 * @param \Shopware\Models\Config\Form $form
	 */
	public function __construct(\Shopware\Models\Config\Form $form)
	{
		$this->form = $form;
	}

	/**
     * creates the plugin configuration form
     */
    public function create()
    {
        // Get a list of export formats
        $exportFormats = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportFormats();
        $formats = array();
        foreach ($exportFormats as $format) {
            $formats[] = array($format->id, $format->name);
        }
        // Get a list of the number of export image
        $exportImage = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getImagesCount();
        $nbImage = array();
        foreach ($exportImage as $value) {
            $nbImage[] = array($value->id, $value->name. ' images');  
        }
        // Get a list of image formats
        $exportImageSize = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getImagesSize();
        $sizeImage = array();
        foreach ($exportImageSize as $value) {
            $sizeImage[] = array($value->id, $value->name);  
        }
        // Get a list of carriers
        $exportCarrier = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getCarriers();
        $carriers = array();
        foreach ($exportCarrier as $value) {
            $carriers[] = array($value->id, $value->name);  
        }
        // Get export URL
        $pathPlugin = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getPathPlugin();
        $exportUrl = 'http://' . $_SERVER['SERVER_NAME'] . $pathPlugin . 'Webservice/export.php';

        // Set the settings plugin
        $this->form->setElement('text', 'lengowIdUser', array(
            'label' => 'Customer ID', 
            'required' => true,
            'description' => 'Your Customer ID of Lengow'
        ));
        $this->form->setElement('text', 'lengowIdGroup', array(
            'label' => 'Group ID', 
            'required' => true,
            'description' => 'Your Group ID of Lengow'
        ));
        $this->form->setElement('text', 'lengowApiKey', array(
            'label' => 'Token API', 
            'required' => true,
            'description' => 'Your Token API of Lengow'
        ));
        $this->form->setElement('text', 'lengowAuthorisedIp', array(
            'label' => 'IP authorised to export', 
            'required' => true,
            'value' => '127.0.0.1',
            'description' => 'Authorized access to catalog export by IP, separated by ;'
        ));
        $this->form->setElement('boolean', 'lengowExportAllProducts', array(
            'label' => 'Export all products',
            'value' => true,
            'description' => 'If don\'t want to export all your available products, select "no" and select yours products in the Lengow plugin.' 
        ));
        $this->form->setElement('boolean', 'lengowExportDisabledProducts', array(
            'label' => 'Export disabled products',
            'value' => false,
            'description' => 'If you want to export disabled products, select "yes".'
        ));
        $this->form->setElement('boolean', 'lengowExportVariantProducts', array(
            'label' => 'Export variant products',
            'value' => true,
            'description' => 'If don\'t want to export all your products\' variations, click "no"'
        ));
        $this->form->setElement('boolean', 'lengowExportAttributes', array(
            'label' => 'Export attributes',
            'value' => false,
            'description' => 'If you select "yes", your product(s) will be exported with attributes.'
        ));
        $this->form->setElement('boolean', 'lengowExportAttributesTitle', array(
            'label' => 'Title + attributes + features',
            'value' => true,
            'description' => 'Select this option if you want a variation product title as title + attributes + feature. By default the title will be the product name'
        ));
        $this->form->setElement('boolean', 'lengowExportOutStock', array(
            'label' => 'Export out of stock product',
            'value' => true,
            'description' => 'Select this option if you want to export out of stock product.'
        ));
        $this->form->setElement('select', 'lengowExportImageSize', array(
            'label' => 'Image size to export',
            'store' => $sizeImage,
            'value' => $sizeImage[0],
            'required' => true
        ));
        $this->form->setElement('select', 'lengowExportImages', array(
            'label' => 'Number of images to export',
            'store' => $nbImage,
            'value' => $nbImage[0],
            'required' => true
        ));
        $this->form->setElement('select', 'lengowExportFormat', array(
            'label' => 'Export format',
            'store' => $formats,
            'value' => $formats[0],
            'required' => true
        ));
        $this->form->setElement('boolean', 'lengowExportFile', array(
            'label' => 'Save feed on file',
            'value' => false,
            'description' => 'You should use this option if you have more than 10,000 products'
        ));
        $this->form->setElement('text', 'lengowExportUrl', array(
            'label' => 'Our export URL',
            'value' => $exportUrl,
            'description' => 'For export, we must put the name of the store (export.php?shop=nameShop) at the end of this address : ' . $exportUrl
        ));
        $this->form->setElement('select', 'lengowCarrierDefault', array(
            'label' => 'Default shipping cost',
            'store' => $carriers,
            'value' => $carriers[0],
            'description' => 'Your default shipping cost',
            'required' => true
        ));
        $this->form->setElement('boolean', 'lengowExportCron', array(
            'label' => 'Active cron',
            'value' => false,
            'description' => 'If you select "yes", orders will be automatically imported.'
        ));
        $this->form->setElement('boolean', 'lengowDebug', array(
            'label' => 'Debug mode',
            'value' => false,
            'description' => 'Use it only during tests.'
        ));
    }

}