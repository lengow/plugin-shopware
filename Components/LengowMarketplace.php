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
class Shopware_Plugins_Backend_Lengow_Components_LengowMarketplace
{

    /**
     * @var mixed all marketplaces allowed for an account ID
     */
    public static $marketplaces = array();

    /**
     * @var string the name of the marketplace
     */
    public $name;

    /**
     * @var boolean if the marketplace is loaded
     */
    public $isLoaded = false;

    /**
     * @var array Lengow states => marketplace states
     */
    public $statesLengow = array();

    /**
     * @var array marketplace states => Lengow states
     */
    public $states = array();

    /**
     * @var array all possible actions of the marketplace
     */
    public $actions = array();

    /**
     * @var array all carriers of the marketplace
     */
    public $carriers = array();

    /**
     * @var array all possible values for actions of the marketplace
     */
    public $argValues = array();

    /**
     * @var \Shopware\Models\Shop\Shop Shopware Shop
     */
    public $shop;

    /**
     * @var integer Shop id
     */
    public $idShop;

    /**
     * Construct a new Marketplace instance with xml configuration.
     *
     * @param string  $name                   The name of the marketplace
     * @param Shopware\Models\Shop\Shop $shop Shop object used for Connector
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException
     */
    public function __construct($name, $shop = null)
    {
        $this->shop = $shop;
        $this->idShop = $shop->getId();
        $this->loadApiMarketplace();
        $this->name = strtolower($name);
        if (!isset(self::$marketplaces[$this->idShop]->{$this->name})) {
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'lengow_log/exception/marketplace_not_present',
                    array('marketplace_name' => $this->name)
                )
            );
        }
        $this->marketplace = self::$marketplaces[$this->idShop]->{$this->name};
        if (!empty($this->marketplace)) {
            foreach ($this->marketplace->orders->status as $key => $state) {
                foreach ($state as $value) {
                    $this->statesLengow[(string)$value] = (string)$key;
                    $this->states[(string)$key][(string)$value] = (string)$value;
                }
            }
            foreach ($this->marketplace->orders->actions as $key => $action) {
                foreach ($action->status as $state) {
                    $this->actions[(string)$key]['status'][(string)$state] = (string)$state;
                }
                foreach ($action->args as $arg) {
                    $this->actions[(string)$key]['args'][(string)$arg] = (string)$arg;
                }
                foreach ($action->optional_args as $optionalArg) {
                    $this->actions[(string)$key]['optional_args'][(string)$optionalArg] = $optionalArg;
                }
                foreach ($action->args_description as $key => $argDescription) {
                    $validValues = array();
                    if (isset($argDescription->valid_values)) {
                        foreach ($argDescription->valid_values as $code => $validValue) {
                            $validValues[(string)$code] = (string)$validValue->label;
                        }
                    }
                    $this->argValues[(string)$key] = array(
                        'default_value'      => (string)$argDescription->default_value,
                        'accept_free_values' => (bool)$argDescription->accept_free_values,
                        'valid_values'       => $validValues
                    );
                }
            }
            if (isset($this->marketplace->orders->carriers)) {
                foreach ($this->marketplace->orders->carriers as $key => $carrier) {
                    $this->carriers[(string)$key] = (string)$carrier->label;
                }
            }
            $this->isLoaded = true;
        }
    }

    /**
     * Load the json configuration of all marketplaces
     */
    public function loadApiMarketplace()
    {
        if (!array_key_exists($this->idShop, self::$marketplaces)) {
            $result = Shopware_Plugins_Backend_Lengow_Components_LengowConnector::queryApi(
                'get',
                '/v3.0/marketplaces',
                $this->shop
            );
            self::$marketplaces[$this->idShop] = $result;
        }
    }

    /**
     * Get the real lengow's state
     *
     * @param string $name The marketplace state
     *
     * @return string The lengow state
     */
    public function getStateLengow($name)
    {
        if (array_key_exists($name, $this->statesLengow)) {
            return $this->statesLengow[$name];
        }
        return null;
    }
}
