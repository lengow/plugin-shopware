<?php

/**
 * LengowOption.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowOption
{
	/**
     * Option ID
     */
    public $id;

    /**
     * Option name
     */
    public $name;

    /**
     * Make a new tracker option
     *
     * @param integer $id The tracker type unique ID
     * @param varchar $token The tracker type name
     */
    public function __construct($id, $name) 
    {
        $this->id = $id;
        $this->name = $name;
    }
 
}