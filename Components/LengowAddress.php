<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * It is available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/agpl-3.0
 *
 * @category    Lengow
 * @package     Lengow
 * @subpackage  Components
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Lengow Address Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowAddress
{
    /**
     * @var array API fields for an address
     */
    protected $addressApiNodes = array(
        'company',
        'civility',
        'email',
        'last_name',
        'first_name',
        'full_name',
        'first_line',
        'second_line',
        'complement',
        'zipcode',
        'city',
        'common_country_iso_a2',
        'phone_home',
        'phone_office',
        'phone_mobile',
    );

    /**
     * @var array current alias of mister
     */
    protected $currentMale = array(
        'M',
        'M.',
        'Mr',
        'Mr.',
        'Mister',
        'Monsieur',
        'monsieur',
        'mister',
        'm.',
        'mr ',
    );

    /**
     * @var array current alias of miss
     */
    protected $currentFemale = array(
        'Mme',
        'mme',
        'Mm',
        'mm',
        'Mlle',
        'mlle',
        'Madame',
        'madame',
        'Mademoiselle',
        'madamoiselle',
        'Mrs',
        'mrs',
        'Mrs.',
        'mrs.',
        'Miss',
        'miss',
        'Ms',
        'ms',
    );

    /**
     * @var array billing datas
     */
    protected $billingDatas = array();

    /**
     * @var array shipping datas
     */
    protected $shippingDatas = array();

    /**
     * @var string carrier relay id
     */
    protected $relayId;

    /**
     * @var string id lengow of current order
     */
    protected $marketplaceSku;

    /**
     * @var boolean display log messages
     */
    protected $logOutput;

    /**
     * Construct the address
     *
     * @param $params array optional options
     * array  billing_datas  API billing datas
     * array  shipping_datas API shipping datas
     * string relay_id       carrier id relay
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException
     */
    public function __construct($params = array())
    {
        $this->relayId = isset($params['relay_id']) ? $params['relay_id'] : null;
        $this->marketplaceSku = isset($params['marketplace_sku']) ? $params['marketplace_sku'] : null;
        $this->logOutput = isset($params['log_output']) ? $params['log_output'] : false;
        if (isset($params['billing_datas'])) {
            $billingAddressDatas = $this->extractAddressDataFromAPI($params['billing_datas']);
            $this->billingDatas = $this->setShopwareAddressFields($billingAddressDatas);
        }
        if (isset($params['shipping_datas'])) {
            $shippingAddressDatas = $this->extractAddressDataFromAPI($params['shipping_datas']);
            $this->shippingDatas = $this->setShopwareAddressFields($shippingAddressDatas, 'shipping');
        }
    }

    /**
     * Get customer address
     *
     * @param boolean $newSchema Address type (billing or shipping)
     * @param string $typeAddress Address type (billing or shipping)
     *
     * @return Shopware\Models\Customer\Address|Shopware\Models\Customer\Billing|Shopware\Models\Customer\Shipping|false
     */
    public function getCustomerAddress($newSchema = true, $typeAddress = 'billing')
    {
        $addressFields = ($newSchema || $typeAddress === 'billing') ? $this->billingDatas : $this->shippingDatas;
        $params = Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.0.0')
            ? array('street' => $addressFields['street'])
            : array('street' => $addressFields['full_street']);
        // get address repository for specific Shopware version
        if ($newSchema) {
            $model = 'Shopware\Models\Customer\Address';
            $params['firstname'] = $addressFields['firstname'];
            $params['lastname'] = $addressFields['firstname'];
            $params['zipcode'] = $addressFields['zipcode'];
        } else {
            $model = $typeAddress === 'billing'
                ? 'Shopware\Models\Customer\Billing'
                : 'Shopware\Models\Customer\Shipping';
            $params['firstName'] = $addressFields['firstname'];
            $params['lastName'] = $addressFields['firstname'];
            $params['zipCode'] = $addressFields['zipcode'];
        }
        // get address if exist
        $address = Shopware()->Models()->getRepository($model)->findOneBy($params);
        if (is_null($address)) {
            $address = $this->createCustomerAddress($addressFields, $newSchema, $typeAddress);
        }
        return $address;
    }

    /**
     * Get Order address
     *
     * @param string $typeAddress Address type (billing or shipping)
     *
     * @return Shopware\Models\Order\Billing|Shopware\Models\Order\Shipping|false
     */
    public function getOrderAddress($typeAddress = 'billing')
    {
        $addressFields = $typeAddress === 'billing' ? $this->billingDatas : $this->shippingDatas;
        $params = Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.0.0')
            ? array('street' => $addressFields['street'])
            : array('street' => $addressFields['full_street']);
        // get address repository for specific Shopware version
        $model = $typeAddress === 'billing' ? 'Shopware\Models\Order\Billing' : 'Shopware\Models\Order\Shipping';
        $params['firstName'] = $addressFields['firstname'];
        $params['lastName'] = $addressFields['firstname'];
        $params['zipCode'] = $addressFields['zipcode'];
        // get address if exist
        $address = Shopware()->Models()->getRepository($model)->findOneBy($params);
        if (is_null($address)) {
            $address = $this->createOrderAddress($addressFields, $typeAddress);
        }
        return $address;
    }

    /**
     * Extract address data from API
     *
     * @param array $apiDatas API nodes containing data
     *
     * @return array
     */
    protected function extractAddressDataFromAPI($apiDatas)
    {
        $temp = array();
        foreach ($this->addressApiNodes as $node) {
            $temp[$node] = (string)$apiDatas->{$node};
        }
        return $temp;
    }

    /**
     * Prepare API address data for Shopware address object
     *
     * @param array $addressDatas API address data
     * @param string $typeAddress address type (billing or shipping)
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException
     * 
     * @return array
     */
    protected function setShopwareAddressFields($addressDatas, $typeAddress = 'billing')
    {
        $country = $this->getCountryByIso($addressDatas['common_country_iso_a2']);
        if (is_null($country)) {
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'lengow_log/exception/country_not_found',
                    array('iso_code' => $addressDatas['common_country_iso_a2'])
                )
            );
        }
        $names = $this->getNames($addressDatas);
        $addressFields = $this->getAddressFields($addressDatas, $typeAddress);
        return array(
            'company' => (string)$addressDatas['society'],
            'salutation' => $this->getSalutation($addressDatas),
            'firstname' => ucfirst(strtolower($names['firstname'])),
            'lastname' => ucfirst(strtolower($names['lastname'])),
            'street' => strtolower($addressFields['street']),
            'additional_address_line_1' => strtolower($addressFields['additional_address_line_1']),
            'additional_address_line_2' => strtolower($addressFields['additional_address_line_2']),
            'full_street' => strtolower($addressFields['full_address']),
            'zipcode' => (string)$addressDatas['zipcode'],
            'city' => ucfirst(strtolower(preg_replace('/[!<>?=+@{}_$%]/sim', '', $addressDatas['city']))),
            'country' => $country,
            'country_id' => $country->getId(),
            'phone' => $this->getPhoneNumber($addressDatas),
        );
    }

    /**
     * Create customer address
     *
     * @param array $addressFields field for Shopware order
     * @param boolean $newSchema use new address schema or not
     * @param string $typeAddress address type (billing or shipping)
     *
     * @return Shopware\Models\Customer\Address|Shopware\Models\Customer\Billing|Shopware\Models\Customer\Shipping|false
     */
    protected function createCustomerAddress($addressFields, $newSchema, $typeAddress)
    {
        try {
            // get address object for specific Shopware version
            if ($newSchema) {
                $address = new Shopware\Models\Customer\Address();
                $addressAttribute = new Shopware\Models\Attribute\CustomerAddress();
            } else {
                if ($typeAddress === 'billing') {
                    $address = new Shopware\Models\Customer\Billing();
                    $addressAttribute = new Shopware\Models\Attribute\CustomerBilling();
                } else {
                    $address = new Shopware\Models\Customer\Shipping();
                    $addressAttribute = new Shopware\Models\Attribute\CustomerShipping();
                }
            }
            // set all data for all type of address Shopware
            $address->setCompany($addressFields['company']);
            $address->setSalutation($addressFields['salutation']);
            $address->setFirstName($addressFields['firstname']);
            $address->setLastName($addressFields['lastname']);
            if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.0.0')) {
                $address->setStreet($addressFields['street']);
                $address->setAdditionalAddressLine1($addressFields['additional_address_line_1']);
                $address->setAdditionalAddressLine2($addressFields['additional_address_line_2']);
            } else {
                $address->setStreet($addressFields['full_street']);
            }
            $address->setZipCode($addressFields['zipcode']);
            $address->setCity($addressFields['city']);
            if ($newSchema) {
                $address->setCountry($addressFields['country']);
            } else {
                $address->setCountryId($addressFields['country_id']);
            }
            $address->setAttribute($addressAttribute);
            if ($typeAddress === 'billing' || $newSchema) {
                $phone = !empty($addressFields['phone']) ? $addressFields['phone'] : $this->shippingDatas['phone'];
                $address->setPhone($phone);
            }
            return $address;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Orm',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
    }

    /**
     * Create order address
     *
     * @param array $addressFields field for Shopware order
     * @param string $typeAddress Address type (billing or shipping)
     *
     * @return Shopware\Models\Order\Billing|Shopware\Models\Order\Shipping|false
     */
    protected function createOrderAddress($addressFields, $typeAddress)
    {
        try {
            // get address object for specific Shopware version
            if ($typeAddress === 'billing') {
                $address = new Shopware\Models\Order\Billing();
                $addressAttribute = new Shopware\Models\Attribute\OrderBilling();
            } else {
                $address = new Shopware\Models\Order\Shipping();
                $addressAttribute = new Shopware\Models\Attribute\OrderShipping();
            }
            // set all data for all type of address Shopware
            $address->setCompany($addressFields['company']);
            $address->setSalutation($addressFields['salutation']);
            $address->setFirstName($addressFields['firstname']);
            $address->setLastName($addressFields['lastname']);
            if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.0.0')) {
                $address->setStreet($addressFields['street']);
                $address->setAdditionalAddressLine1($addressFields['additional_address_line_1']);
                $address->setAdditionalAddressLine2($addressFields['additional_address_line_2']);
            } else {
                $address->setStreet($addressFields['full_street']);
            }
            $address->setZipCode($addressFields['zipcode']);
            $address->setCity($addressFields['city']);
            $address->setCountry($addressFields['country']);
            $address->setAttribute($addressAttribute);
            if ($typeAddress === 'billing') {
                $phone = !empty($addressFields['phone']) ? $addressFields['phone'] : $this->shippingDatas['phone'];
                $address->setPhone($phone);
            }
            return $address;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Orm',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            return false;
        }
    }

    /**
     * Get country by iso code
     *
     * @param string $countryIso Country iso code
     *
     * @return Shopware\Models\Country\Country|null
     */
    protected function getCountryByIso($countryIso)
    {
        $isoCode = strtoupper(substr(str_replace(' ', '', $countryIso), 0, 2));
        /** @var Shopware\Models\Country\Country $country */
        $country = Shopware()->Models()
            ->getRepository('Shopware\Models\Country\Country')
            ->findOneBy(array('iso' => $isoCode));
        return $country;
    }

    /**
     * Check if firstname or lastname are empty
     *
     * @param array $addressDatas API address data
     *
     * @return array
     */
    protected function getNames($addressDatas)
    {
        $names = array(
            'firstname' => trim($addressDatas['first_name']),
            'lastname' => trim($addressDatas['last_name']),
            'fullname' => $this->cleanFullName($addressDatas['full_name'])
        );
        if (empty($names['firstname'])) {
            if (!empty($names['lastname'])) {
                $names = $this->splitNames($names['lastname']);
            }
        }
        if (empty($names['lastname'])) {
            if (!empty($names['firstname'])) {
                $names = $this->splitNames($names['firstname']);
            }
        }
        // check full name if last_name and first_name are empty
        if (empty($names['lastname']) && empty($names['firstname'])) {
            $names = $this->splitNames($names['fullname']);
        }
        if (empty($names['lastname'])) {
            $names['lastname'] = '__';
        }
        if (empty($names['firstname'])) {
            $names['firstname'] = '__';
        }
        return $names;
    }

    /**
     * Clean fullname field without salutation
     *
     * @param string $fullname fullname of the customer
     *
     * @return string
     */
    protected function cleanFullName($fullname)
    {
        $split = explode(' ', $fullname);
        if ($split && count($split) > 0) {
            $fullname = (in_array($split[0], $this->currentMale) || in_array($split[0], $this->currentFemale))
                ? ''
                : $split[0];
            for ($i = 1; $i < count($split); $i++) {
                if (!empty($fullname)) {
                    $fullname .= ' ';
                }
                $fullname .= $split[$i];
            }
        }
        return $fullname;
    }

    /**
     * Split fullname
     *
     * @param string $fullname fullname of the customer
     *
     * @return array
     */
    protected function splitNames($fullname)
    {
        $split = explode(' ', $fullname);
        if ($split && count($split) > 0) {
            $names['firstname'] = $split[0];
            $names['lastname'] = '';
            for ($i = 1; $i < count($split); $i++) {
                if (!empty($names['lastname'])) {
                    $names['lastname'] .= ' ';
                }
                $names['lastname'] .= $split[$i];
            }
        } else {
            $names['firstname'] = '__';
            $names['lastname'] = empty($fullname) ? '__' : $fullname;
        }
        return $names;
    }

    /**
     * Get the real salutation
     *
     * @param array $addressDatas API address data
     *
     * @return string
     */
    protected function getSalutation($addressDatas)
    {

        $salutation = $addressDatas['civility'];
        if (empty($salutation)) {
            $split = explode(' ', $addressDatas['full_name']);
            if ($split && count($split) > 0) {
                $salutation = $split[0];
            }
        }
        if (!empty($addressDatas['society'])) {
            return 'company';
        } elseif (in_array($salutation, $this->currentMale)) {
            return 'mr';
        } elseif (in_array($salutation, $this->currentFemale)) {
            return 'ms';
        } else {
            return '';
        }
    }

    /**
     * Get clean address fields
     *
     * @param array $addressDatas API address data
     * @param string $typeAddress address type (billing or shipping)
     *
     * @return array
     */
    protected function getAddressFields($addressDatas, $typeAddress)
    {
        $street = trim($addressDatas['first_line']);
        $additionalAddressLine1 = trim($addressDatas['second_line']);
        $additionalAddressLine2 = trim($addressDatas['complement']);
        if (empty($street)) {
            if (!empty($additionalAddressLine1)) {
                $street = $additionalAddressLine1;
                $additionalAddressLine1 = '';
            } elseif (!empty($additionalAddressLine2)) {
                $street = $additionalAddressLine2;
                $additionalAddressLine2 = '';
            }
        }
        // get relay id for shipping addresses
        $relayId = !is_null($this->relayId) ? 'Relay id: ' . $this->relayId : '';
        if ($typeAddress === 'shipping') {
            $additionalAddressLine2 .= !empty($additionalAddressLine2) ? ' - ' . $relayId : $relayId;
        }
        // get full address for Shopware version < 5.0.0
        $fullAddress = $street;
        if (!empty($additionalAddressLine1)) {
            $fullAddress .= ' ' . $additionalAddressLine1;
        }
        if (!empty($additionalAddressLine2)) {
            $fullAddress .= ' ' . $additionalAddressLine2;
        }
        return array(
            'street' => $street,
            'additional_address_line_1' => $additionalAddressLine1,
            'additional_address_line_2' => $additionalAddressLine2,
            'full_address' => $fullAddress,
        );
    }

    /**
     * Get clean phone number
     *
     * @param array $addressDatas API address data
     *
     * @return string
     */
    protected function getPhoneNumber($addressDatas = array())
    {
        $phoneNumber = '';
        if (!empty($addressDatas['phone_home'])) {
            $phoneNumber = $addressDatas['phone_home'];
        } elseif (!empty($addressDatas['phone_mobile'])) {
            $phoneNumber = $addressDatas['phone_mobile'];
        } elseif (!empty($addressDatas['phone_office'])) {
            $phoneNumber = $addressDatas['phone_office'];
        }
        return Shopware_Plugins_Backend_Lengow_Components_LengowMain::cleanPhone($phoneNumber);
    }
}
