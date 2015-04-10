<?php

/**
 * Form.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class Shopware_Plugins_Backend_Lengow_Components_Form
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
        $this->form->setElement('text', 'lengowIdUser', array(
            'label' => 'Customer ID', 
            'required' => true,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('text', 'lengowIdGroup', array(
            'label' => 'Group ID', 
            'required' => true,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('text', 'lengowApiKey', array(
            'label' => 'Token API', 
            'required' => true,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('text', 'lengowAuthorisedIp', array(
            'label' => 'IP authorised to export', 
            'required' => true,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('boolean', 'lengowExportAllProduct', array(
            'label' => 'Export only selected product',
            'value' => false,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('select', 'lengowExportStatusProduct', array(
            'label' => 'Status of product to export',
            'store' => array(
                    array(1, 'Enable'),
                    array(2, 'Disable'),
                    array(3, 'All')
                ),
            'value' => 1,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('boolean', 'lengowExportAttributes', array(
            'label' => 'Export attributes',
            'value' => false,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('select', 'lengowExportImageSize', array(
            'label' => 'Product images size',
            'store' => array(
                    array(1, 'Small'),
                    array(2, 'Medium'),
                    array(3, 'Big')
                ),
            'value' => 1,
            'required' => true,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('select', 'lengowExportImages', array(
            'label' => 'Image max',
            'store' => array(
                    array(1, '3 images'),
                    array(2, '4 images'),
                    array(3, '5 images'),
                    array(4, '6 images'),
                    array(5, '7 images'),
                    array(6, '8 images'),
                    array(7, '9 images'),
                    array(8, '10 images')
                ),
            'value' => 1,
            'required' => true,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('select', 'lengowExportFormat', array(
            'label' => 'Export format',
            'store' => array(
                    array(1, 'csv'),
                    array(2, 'xml'),
                    array(3, 'json'),
                    array(4, 'yaml')
                ),
            'value' => 1,
            'required' => true,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('boolean', 'lengowExportFile', array(
            'label' => 'Save feed on file',
            'value' => false,
            'description' => 'Bla Bla'
        ));
        $this->form->setElement('text', 'lengowExportUrl', array(
            'label' => 'Our export URL'
        ));
        $this->form->setElement('boolean', 'lengowExportCron', array(
            'label' => 'Active cron',
            'value' => false,
            'description' => 'Bla Bla'
        ));
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Config\Form');
        $this->form->setParent($repository->findOneBy(array('name' => 'Interface')));
    }

}