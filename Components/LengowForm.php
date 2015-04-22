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
	 * initialises $form
	 *
	 * @param \Shopware\Models\Config\Form $form
	 */
	public function __construct(\Shopware\Models\Config\Form $form)
	{
		$this->form = $form;
	}

	/**
     * creates the plugin configuration form
     *
     * @throws Exception
     * @return void
     */
    public function create()
    {
        $exportFormats = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportFormats();
        $formats = array();
        foreach ($exportFormats as $format) {
            $formats[] = array($format->id, $format->name);
        }

        $exportImage = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getImagesCount();
        $nbImage = array();
        foreach ($exportImage as $value) {
            if ($value->id === 'all') {
                $nbImage[] = array($value->id, $value->name);
            } else {
                $nbImage[] = array($value->id, $value->name. ' images'); 
            }  
        }

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
        $this->form->setElement('boolean', 'lengowExportAttributes', array(
            'label' => 'Export attributes',
            'value' => false,
            'description' => 'If you select "yes", your product(s) will be exported with attributes.'
        ));
        $this->form->setElement('select', 'lengowExportImageSize', array(
            'label' => 'Image type to export',
            'store' => array(
                    array(1, 'Small'),
                    array(2, 'Medium'),
                    array(3, 'Big')
                ),
            'value' => 1,
            'required' => true
        ));
        $this->form->setElement('select', 'lengowExportImages', array(
            'label' => 'Number of images to export',
            'store' => $nbImage,
            'value' => 3,
            'required' => true
        ));
        $this->form->setElement('select', 'lengowExportFormat', array(
            'label' => 'Export format',
            'store' => $formats,
            'value' => 'csv',
            'required' => true
        ));
        $this->form->setElement('boolean', 'lengowExportFile', array(
            'label' => 'Save feed on file',
            'value' => false,
            'description' => 'You should use this option if you have more than 10,000 products'
        ));
        $this->form->setElement('text', 'lengowExportUrl', array(
            'label' => 'Our export URL'
        ));
        $this->form->setElement('boolean', 'lengowExportCron', array(
            'label' => 'Active cron',
            'value' => false,
            'description' => 'If you select "yes", orders will be automatically imported.'
        ));
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Config\Form');
        $this->form->setParent($repository->findOneBy(array('name' => 'Interface')));
    }

}