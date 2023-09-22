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

use Shopware\Models\Attribute\CustomerAddress as AttributeCustomerAddressModel;
use Shopware\Models\Attribute\CustomerBilling as AttributeCustomerBillingModel;
use Shopware\Models\Attribute\CustomerShipping as AttributeCustomerShippingModel;
use Shopware\Models\Attribute\OrderBilling as AttributeOrderBillingModel;
use Shopware\Models\Attribute\OrderShipping as AttributeOrderShippingModel;
use Shopware\Models\Country\Country as CountryModel;
use Shopware\Models\Country\State as CountryStateModel;
use Shopware\Models\Customer\Address as CustomerAddressModel;
use Shopware\Models\Customer\Billing as CustomerBillingModel;
use Shopware\Models\Customer\Shipping as CustomerShippingModel;
use Shopware\Models\Order\Billing as OrderBillingModel;
use Shopware\Models\Order\Shipping as OrderShippingModel;
use Shopware_Plugins_Backend_Lengow_Components_LengowException as LengowException;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;

/**
 * Lengow Address Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowAddress
{
    /* Country iso A2 codes */
    const ISO_A2_FR = 'FR';
    const ISO_A2_ES = 'ES';
    const ISO_A2_IT = 'IT';

    /**
     * @var array API fields for an address
     */
    private $addressApiNodes = array(
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
        'state_region',
        'common_country_iso_a2',
        'phone_home',
        'phone_office',
        'phone_mobile',
    );

    /**
     * @var array current alias of mister
     */
    private $currentMale = array(
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
    private $currentFemale = array(
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
     * @var array All region codes for correspondence
     */
    private $regionCodes = array(
        self::ISO_A2_ES => array(
            '01' => 'Alava',
            '02' => 'Albacete',
            '03' => 'Alicante',
            '04' => 'Almeria',
            '05' => 'Avila',
            '06' => 'Badajoz',
            '07' => 'Baleares',
            '08' => 'Barcelona',
            '09' => 'Burgos',
            '10' => 'Caceres',
            '11' => 'Cadiz',
            '12' => 'Castellon',
            '13' => 'Ciudad Real',
            '14' => 'Cordoba',
            '15' => 'A Coruсa',
            '16' => 'Cuenca',
            '17' => 'Girona',
            '18' => 'Granada',
            '19' => 'Guadalajara',
            '20' => 'Guipuzcoa',
            '21' => 'Huelva',
            '22' => 'Huesca',
            '23' => 'Jaen',
            '24' => 'Leon',
            '25' => 'Lleida',
            '26' => 'La Rioja',
            '27' => 'Lugo',
            '28' => 'Madrid',
            '29' => 'Malaga',
            '30' => 'Murcia',
            '31' => 'Navarra',
            '32' => 'Ourense',
            '33' => 'Asturias',
            '34' => 'Palencia',
            '35' => 'Las Palmas',
            '36' => 'Pontevedra',
            '37' => 'Salamanca',
            '38' => 'Santa Cruz de Tenerife',
            '39' => 'Cantabria',
            '40' => 'Segovia',
            '41' => 'Sevilla',
            '42' => 'Soria',
            '43' => 'Tarragona',
            '44' => 'Teruel',
            '45' => 'Toledo',
            '46' => 'Valencia',
            '47' => 'Valladolid',
            '48' => 'Vizcaya',
            '49' => 'Zamora',
            '50' => 'Zaragoza',
            '51' => 'Ceuta',
            '52' => 'Melilla',
        ),
        self::ISO_A2_IT => array(
            '00' => 'RM',
            '01' => 'VT',
            '02' => 'RI',
            '03' => 'FR',
            '04' => 'LT',
            '05' => 'TR',
            '06' => 'PG',
            '07' => array(
                '07000-07019' => 'SS',
                '07020-07029' => 'OT',
                '07030-07049' => 'SS',
                '07050-07999' => 'SS',
            ),
            '08' => array(
                '08000-08010' => 'OR',
                '08011-08012' => 'NU',
                '08013-08013' => 'OR',
                '08014-08018' => 'NU',
                '08019-08019' => 'OR',
                '08020-08020' => 'OT',
                '08021-08029' => 'NU',
                '08030-08030' => 'OR',
                '08031-08032' => 'NU',
                '08033-08033' => 'CA',
                '08034-08034' => 'OR',
                '08035-08035' => 'CA',
                '08036-08039' => 'NU',
                '08040-08042' => 'OG',
                '08043-08043' => 'CA',
                '08044-08049' => 'OG',
                '08050-08999' => 'NU',
            ),
            '09' => array(
                '09000-09009' => 'CA',
                '09010-09017' => 'CI',
                '09018-09019' => 'CA',
                '09020-09041' => 'VS',
                '09042-09069' => 'CA',
                '09070-09099' => 'OR',
                '09100-09169' => 'CA',
                '09170-09170' => 'OR',
                '09171-09999' => 'CA',
            ),
            '10' => 'TO',
            '11' => 'AO',
            '12' => array(
                '12000-12070' => 'CN',
                '12071-12071' => 'SV',
                '12072-12999' => 'CN',
            ),
            '13' => array(
                '13000-13799' => 'VC',
                '13800-13999' => 'BI',
            ),
            '14' => 'AT',
            '15' => 'AL',
            '16' => 'GE',
            '17' => 'SV',
            '18' => array(
                '18000-18024' => 'IM',
                '18025-18025' => 'CN',
                '18026-18999' => 'IM',
            ),
            '19' => 'SP',
            '20' => array(
                '20000-20799' => 'MI',
                '20800-20999' => 'MB',
            ),
            '21' => 'VA',
            '22' => 'CO',
            '23' => array(
                '23000-23799' => 'SO',
                '23800-23999' => 'LC',
            ),
            '24' => 'BG',
            '25' => 'BS',
            '26' => array(
                '26000-26799' => 'CR',
                '26800-26999' => 'LO',
            ),
            '27' => 'PV',
            '28' => array(
                '28000-28799' => 'NO',
                '28800-28999' => 'VB',
            ),
            '29' => 'PC',
            '30' => 'VE',
            '31' => 'TV',
            '32' => 'BL',
            '33' => array(
                '33000-33069' => 'UD',
                '33070-33099' => 'PN',
                '33100-33169' => 'UD',
                '33170-33999' => 'PN',
            ),
            '34' => array(
                '34000-34069' => 'TS',
                '34070-34099' => 'GO',
                '34100-34169' => 'TS',
                '34170-34999' => 'GO',
            ),
            '35' => 'PD',
            '36' => 'VI',
            '37' => 'VR',
            '38' => 'TN',
            '39' => 'BZ',
            '40' => 'BO',
            '41' => 'MO',
            '42' => 'RE',
            '43' => 'PR',
            '44' => 'FE',
            '45' => 'RO',
            '46' => 'MN',
            '47' => array(
                '47000-47799' => 'FC',
                '47800-47999' => 'RN',
            ),
            '48' => 'RA',
            '50' => 'FI',
            '51' => 'PT',
            '52' => 'AR',
            '53' => 'SI',
            '54' => 'MS',
            '55' => 'LU',
            '56' => 'PI',
            '57' => 'LI',
            '58' => 'GR',
            '59' => 'PO',
            '60' => 'AN',
            '61' => 'PU',
            '62' => 'MC',
            '63' => array(
                '63000-63799' => 'AP',
                '63800-63999' => 'FM',
            ),
            '64' => 'TE',
            '65' => 'PE',
            '66' => 'CH',
            '67' => 'AQ',
            '70' => 'BA',
            '71' => 'FG',
            '72' => 'BR',
            '73' => 'LE',
            '74' => 'TA',
            '75' => 'MT',
            '76' => 'BT',
            '80' => 'NA',
            '81' => 'CE',
            '82' => 'BN',
            '83' => 'AV',
            '84' => 'SA',
            '85' => 'PZ',
            '86' => array(
                '86000-86069' => 'CB',
                '86070-86099' => 'IS',
                '86100-86169' => 'CB',
                '86170-86999' => 'IS',
            ),
            '87' => 'CS',
            '88' => array(
                '88000-88799' => 'CZ',
                '88800-88999' => 'KR',
            ),
            '89' => array(
                '89000-89799' => 'RC',
                '89800-89999' => 'VV',
            ),
            '90' => 'PA',
            '91' => 'TP',
            '92' => 'AG',
            '93' => 'CL',
            '94' => 'EN',
            '95' => 'CT',
            '96' => 'SR',
            '97' => 'RG',
            '98' => 'ME',
        ),
    );

    /**
     * @var array billing data
     */
    private $billingData = array();

    /**
     * @var array shipping data
     */
    private $shippingData = array();

    /**
     * @var string carrier relay id
     */
    private $relayId;

    /**
     * @var string id lengow of current order
     */
    private $marketplaceSku;

    /**
     * @var boolean display log messages
     */
    private $logOutput;

    /**
     * @var string vatNumber of current order
     */
    private $vatNumber;

    /**
     * Construct the address
     *
     * @param $params array optional options
     * array  billing_data  API billing data
     * array  shipping_data API shipping data
     * string relay_id       carrier id relay
     *
     * @throws LengowException
     */
    public function __construct($params = array())
    {
        $this->relayId = isset($params['relay_id']) ? $params['relay_id'] : null;
        $this->marketplaceSku = isset($params['marketplace_sku']) ? $params['marketplace_sku'] : null;
        $this->logOutput = isset($params['log_output']) ? $params['log_output'] : false;
        $this->vatNumber = isset($params['vat_number']) ? $params['vat_number'] : null;
        if (isset($params['billing_data'])) {
            $billingAddressData = $this->extractAddressDataFromAPI($params['billing_data']);
            $this->billingData = $this->setShopwareAddressFields($billingAddressData);
        }
        if (isset($params['shipping_data'])) {
            $shippingAddressData = $this->extractAddressDataFromAPI($params['shipping_data']);
            $this->shippingData = $this->setShopwareAddressFields($shippingAddressData, 'shipping');
        }
    }

    /**
     * Get customer address
     *
     * @return CustomerAddressModel|false
     */
    public function getCustomerAddress()
    {
        // get address if exist
        $address = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address')->findOneBy(
            array(
                'street' => $this->billingData['street'],
                'firstname' => $this->billingData['firstname'],
                'lastname' => $this->billingData['firstname'],
                'zipcode' => $this->billingData['zipcode'],
            )
        );
        if ($address === null) {
            $address = $this->createCustomerAddress();
        }
        return $address;
    }

    /**
     * Get Order address
     *
     * @param string $typeAddress Address type (billing or shipping)
     *
     * @return OrderBillingModel|OrderShippingModel|false
     */
    public function getOrderAddress($typeAddress = 'billing')
    {
        $addressFields = $typeAddress === 'billing' ? $this->billingData : $this->shippingData;
        // get address repository for specific Shopware version
        $model = $typeAddress === 'billing' ? 'Shopware\Models\Order\Billing' : 'Shopware\Models\Order\Shipping';
        // get address if exist
        $address = Shopware()->Models()->getRepository($model)->findOneBy(
            array(
                'street' => $addressFields['street'],
                'firstName' => $addressFields['firstname'],
                'lastName' => $addressFields['firstname'],
                'zipCode' => $addressFields['zipcode'],
            )
        );
        if ($address === null) {
            $address = $this->createOrderAddress($addressFields, $typeAddress);
        }
        return $address;
    }

    /**
     * Extract address data from API
     *
     * @param array $apiData API nodes containing data
     *
     * @return array
     */
    private function extractAddressDataFromAPI($apiData)
    {
        $temp = array();
        foreach ($this->addressApiNodes as $node) {
            $temp[$node] = (string) $apiData->{$node};
        }
        return $temp;
    }

    /**
     * Prepare API address data for Shopware address object
     *
     * @param array $addressData API address data
     * @param string $typeAddress address type (billing or shipping)
     *
     * @throws LengowException
     *
     * @return array
     */
    private function setShopwareAddressFields($addressData, $typeAddress = 'billing')
    {
        $country = $this->getCountryByIso($addressData['common_country_iso_a2']);
        if ($country === null) {
            throw new LengowException(
                LengowMain::setLogMessage(
                    'lengow_log/exception/country_not_found',
                    array('iso_code' => $addressData['common_country_iso_a2'])
                )
            );
        }
        $state = $this->getState($country, $addressData['zipcode'], $addressData['state_region']);
        $names = $this->getNames($addressData);
        $addressFields = $this->getAddressFields($addressData, $typeAddress);
        return array(
            'company' => $addressData['company'],
            'salutation' => $this->getSalutation($addressData),
            'firstname' => ucfirst(strtolower($names['firstname'])),
            'lastname' => ucfirst(strtolower($names['lastname'])),
            'street' => strtolower($addressFields['street']),
            'additional_address_line_1' => strtolower($addressFields['additional_address_line_1']),
            'additional_address_line_2' => strtolower($addressFields['additional_address_line_2']),
            'full_street' => strtolower($addressFields['full_address']),
            'zipcode' => $addressData['zipcode'],
            'city' => ucfirst(strtolower(preg_replace('/[!<>?=+@{}_$%]/sim', '', $addressData['city']))),
            'country' => $country,
            'country_id' => $country->getId(),
            'state' => $state,
            'state_id' => $state ? $state->getId() : false,
            'phone' => $this->getPhoneNumber($addressData),
        );
    }

    /**
     * Create customer address
     *
     * @return CustomerAddressModel|false
     */
    private function createCustomerAddress()
    {
        try {
            $address = new CustomerAddressModel();
            $addressAttribute = new AttributeCustomerAddressModel();
            // set all data for all type of address Shopware
            $address->setCompany($this->billingData['company']);
            $address->setSalutation($this->billingData['salutation']);
            $address->setFirstName($this->billingData['firstname']);
            $address->setLastName($this->billingData['lastname']);
            $address->setStreet($this->billingData['street']);
            $address->setAdditionalAddressLine1($this->billingData['additional_address_line_1']);
            $address->setAdditionalAddressLine2($this->billingData['additional_address_line_2']);
            $address->setZipCode($this->billingData['zipcode']);
            $address->setCity($this->billingData['city']);
            $address->setCountry($this->billingData['country']);
            if ($this->billingData['state']) {
                $address->setState($this->billingData['state']);
            }
            $address->setAttribute($addressAttribute);
            $phone = !empty($this->billingData['phone']) ? $this->billingData['phone'] : $this->shippingData['phone'];
            $address->setPhone($phone);
            return $address;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
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
     * @return OrderBillingModel|OrderShippingModel|false
     */
    private function createOrderAddress($addressFields, $typeAddress)
    {
        try {
            // get address object for specific Shopware version
            if ($typeAddress === 'billing') {
                $address = new OrderBillingModel();
                $addressAttribute = new AttributeOrderBillingModel();
                if ($this->vatNumber) {
                    $address->setVatId($this->vatNumber);
                }
            } else {
                $address = new OrderShippingModel();
                $addressAttribute = new AttributeOrderShippingModel();
            }
            // set all data for all type of address Shopware
            $address->setCompany($addressFields['company']);
            $address->setSalutation($addressFields['salutation']);
            $address->setFirstName($addressFields['firstname']);
            $address->setLastName($addressFields['lastname']);
            $address->setStreet($addressFields['street']);
            $address->setAdditionalAddressLine1($addressFields['additional_address_line_1']);
            $address->setAdditionalAddressLine2($addressFields['additional_address_line_2']);
            $address->setZipCode($addressFields['zipcode']);
            $address->setCity($addressFields['city']);
            $address->setCountry($addressFields['country']);
            if ($addressFields['state']) {
                $address->setState($addressFields['state']);
            }
            $address->setAttribute($addressAttribute);
            if ($typeAddress === 'billing') {
                $phone = !empty($addressFields['phone']) ? $addressFields['phone'] : $this->shippingData['phone'];
                $address->setPhone($phone);
            }
            return $address;
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            LengowMain::log(
                LengowLog::CODE_ORM,
                LengowMain::setLogMessage(
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
     * @return CountryModel|null
     */
    private function getCountryByIso($countryIso)
    {
        $isoCode = strtoupper(substr(str_replace(' ', '', $countryIso), 0, 2));
        /** @var CountryModel $country */
        return Shopware()->Models()
            ->getRepository('Shopware\Models\Country\Country')
            ->findOneBy(array('iso' => $isoCode));
    }

    /**
     * Get country state if exist
     *
     * @param CountryModel $country Shopware country instance
     * @param string $postcode address postcode
     * @param string $stateRegion address state region
     *
     * @return CountryStateModel|false
     */
    private function getState($country, $postcode, $stateRegion)
    {
        $state = false;
        if (in_array($country->getIso(), array(self::ISO_A2_FR, self::ISO_A2_ES, self::ISO_A2_IT))) {
            $state = $this->searchStateByPostcode($country, $postcode);
        } elseif (!empty($stateRegion)) {
            $state = $this->searchStateByStateRegion($country, $stateRegion);
        }
        return $state;
    }

    /**
     * Check if firstname or lastname are empty
     *
     * @param array $addressData API address data
     *
     * @return array
     */
    private function getNames($addressData)
    {
        $names = array(
            'firstname' => trim($addressData['first_name']),
            'lastname' => trim($addressData['last_name']),
            'fullname' => $this->cleanFullName($addressData['full_name']),
        );
        // Check if either firstname or lastname is empty and fullname is not empty
        if ((empty($names['firstname']) || empty($names['lastname'])) && !empty($names['fullname'])) {
            $names = $this->splitNames($names['fullname']);        }
        if (empty($names['firstname']) && !empty($names['lastname'])) {
            $names = $this->splitNames($names['lastname']);
        }
        if (empty($names['lastname']) && !empty($names['firstname'])) {
            $names = $this->splitNames($names['firstname']);
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
    private function cleanFullName($fullname)
    {
        $split = explode(' ', $fullname);
        if (!empty($split)) {
            $fullname = (in_array($split[0], $this->currentMale, true)
                || in_array($split[0], $this->currentFemale, true)
            ) ? '' : $split[0];
            $countSplit = count($split);
            for ($i = 1; $i < $countSplit; $i++) {
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
    private function splitNames($fullname)
    {
        $split = explode(' ', $fullname);
        if (!empty($split)) {
            $names['firstname'] = $split[0];
            $names['lastname'] = '';
            $countSplit = count($split);
            for ($i = 1; $i < $countSplit; $i++) {
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
     * @param array $addressData API address data
     *
     * @return string
     */
    private function getSalutation($addressData)
    {

        $salutation = $addressData['civility'];
        if (empty($salutation)) {
            $split = explode(' ', $addressData['full_name']);
            if (!empty($split)) {
                $salutation = $split[0];
            }
        }
        if (!empty($addressData['society'])) {
            return 'company';
        }
        if (in_array($salutation, $this->currentMale, true)) {
            return 'mr';
        }
        if (in_array($salutation, $this->currentFemale, true)) {
            return 'ms';
        }
        return '';
    }

    /**
     * Get clean address fields
     *
     * @param array $addressData API address data
     * @param string $typeAddress address type (billing or shipping)
     *
     * @return array
     */
    private function getAddressFields($addressData, $typeAddress)
    {
        $street = trim($addressData['first_line']);
        $additionalAddressLine1 = trim($addressData['second_line']);
        $additionalAddressLine2 = trim($addressData['complement']);
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
        $relayId = $this->relayId !== null ? 'Relay id: ' . $this->relayId : '';
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
     * @param array $addressData API address data
     *
     * @return string
     */
    private function getPhoneNumber($addressData = array())
    {
        $phoneNumber = '';
        if (!empty($addressData['phone_home'])) {
            $phoneNumber = $addressData['phone_home'];
        } elseif (!empty($addressData['phone_mobile'])) {
            $phoneNumber = $addressData['phone_mobile'];
        } elseif (!empty($addressData['phone_office'])) {
            $phoneNumber = $addressData['phone_office'];
        }
        return LengowMain::cleanPhone($phoneNumber);
    }

    /**
     * Search state by postcode for specific countries
     *
     * @param CountryModel $country Shopware country instance
     * @param string $postcode address postcode
     *
     * @return CountryStateModel|false
     */
    private function searchStateByPostcode($country, $postcode)
    {
        $state = false;
        $countryIsoA2 = $country->getIso();
        $postcodeSubstr = substr(str_pad($postcode, 5, '0', STR_PAD_LEFT), 0, 2);
        switch ($countryIsoA2) {
            case self::ISO_A2_FR:
                $shortCode = ltrim($postcodeSubstr, '0');
                break;
            case self::ISO_A2_ES:
                $shortCode = isset($this->regionCodes[$countryIsoA2][$postcodeSubstr])
                    ? $this->regionCodes[$countryIsoA2][$postcodeSubstr]
                    : false;
                break;
            case self::ISO_A2_IT:
                $shortCode = isset($this->regionCodes[$countryIsoA2][$postcodeSubstr])
                    ? $this->regionCodes[$countryIsoA2][$postcodeSubstr]
                    : false;
                if (is_array($shortCode) && !empty($shortCode)) {
                    $shortCode = $this->getShortCodeFromIntervalPostcodes((int) $postcode, $shortCode);
                }
                break;
            default:
                $shortCode = false;
                break;
        }
        if ($shortCode) {
            $state = Shopware()->Models()
                ->getRepository('Shopware\Models\Country\State')
                ->findOneBy(array('country' => $country, 'shortCode' => $shortCode));
        }
        return $state ?: false;
    }

    /**
     * Get short code from interval postcodes
     *
     * @param integer $postcode address postcode
     * @param array $intervalPostcodes postcode intervals
     *
     * @return string|false
     */
    private function getShortCodeFromIntervalPostcodes($postcode, $intervalPostcodes)
    {
        foreach ($intervalPostcodes as $intervalPostcode => $shortCode) {
            $intervalPostcodes = explode('-', $intervalPostcode);
            if (!empty($intervalPostcodes) && count($intervalPostcodes) === 2) {
                $minPostcode = is_numeric($intervalPostcodes[0]) ? (int) $intervalPostcodes[0] : false;
                $maxPostcode = is_numeric($intervalPostcodes[1]) ? (int) $intervalPostcodes[1] : false;
                if (($minPostcode && $maxPostcode) && ($postcode >= $minPostcode && $postcode <= $maxPostcode)) {
                    return $shortCode;
                }
            }
        }
        return false;
    }

    /**
     * Search Magento region id by state return by api
     *
     * @param CountryModel $country Shopware country instance
     * @param string $stateRegion address state region
     *
     * @return CountryStateModel|false
     */
    private function searchStateByStateRegion($country, $stateRegion)
    {
        $state = false;
        /** @var CountryStateModel[] $countryStates */
        $countryStates = Shopware()->Models()
            ->getRepository('Shopware\Models\Country\State')
            ->findBy(array('country' => $country));
        $stateRegionCleaned = $this->cleanString($stateRegion);
        if (!empty($countryStates) && !empty($stateRegion)) {
            // strict search on the region code
            foreach ($countryStates as $countryState) {
                $shortCodeCleaned = $this->cleanString($countryState->getShortCode());
                if ($stateRegionCleaned === $shortCodeCleaned) {
                    $state = $countryState;
                    break;
                }
            }
            // approximate search on the state name
            if (!$state) {
                $results = array();
                foreach ($countryStates as $countryState) {
                    $nameCleaned = $this->cleanString($countryState->getName());
                    similar_text($stateRegionCleaned, $nameCleaned, $percent);
                    if ($percent > 70) {
                        $results[(int) $percent] = $countryState;
                    }
                }
                if (!empty($results)) {
                    krsort($results);
                    $state = current($results);
                }
            }
        }
        return $state;
    }

    /**
     * Cleaning a string before search
     *
     * @param string $string string to clean
     *
     * @return string
     */
    private function cleanString($string)
    {
        $string = strtolower(str_replace(array(' ', '-', '_', '.'), '', trim($string)));
        return LengowMain::replaceAccentedChars(html_entity_decode($string));
    }
}
