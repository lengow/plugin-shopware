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
 * @subpackage  Bootstrap
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Form Class
 */
class Shopware_Plugins_Backend_Lengow_Bootstrap_Form
{
    /**
     * @var \Shopware\Components\Model\ModelManager Shopware entity manager
     */
    protected $entityManager;

    /**
     * @var array old Lengow settings
     */
    protected $oldSettings = array(
        'lengowExportVariationEnabled',
        'lengowExportOutOfStock',
        'lengowEnableImport',
    );

    /**
     * Construct
     */
    public function __construct()
    {
        $this->entityManager = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
    }

    /**
     * Create basic settings for the plugin
     * Accessible in Configuration/Basic Settings/Additional settings menu
     *
     * @param \Shopware\Models\Config\Form $mainForm Lengow main form
     */
    public function createConfig($mainForm)
    {
        // Get tracking ids, dispatches and order states for settings
        $trackingIds = $this->getTrackingIds();
        $dispatches = $this->getDispatches();
        $orderStates = $this->getOrderStates();
        // Main settings
        $mainSettingsElements = array(
            'lengowAccountId' => array(
                'type' => 'text',
                'label' => 'settings/lengow_main_settings/account/label',
                'required' => true,
                'value' => 0,
                'description' => 'settings/lengow_main_settings/account/description'
            ),
            'lengowAccessToken' => array(
                'type' => 'text',
                'label' => 'settings/lengow_main_settings/access/label',
                'required' => true,
                'value' => 0,
                'description' => 'settings/lengow_main_settings/access/description'
            ),
            'lengowSecretToken' => array(
                'type' => 'text',
                'label' => 'settings/lengow_main_settings/secret/label',
                'required' => true,
                'value' => 0,
                'description' => 'settings/lengow_main_settings/secret/description'
            ),
            'lengowShopActive' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_main_settings/enable/label',
                'editable' => false,
                'value' => 0,
                'description' => 'settings/lengow_main_settings/enable/description',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowCatalogId' => array(
                'type' => 'text',
                'label' => 'settings/lengow_main_settings/catalog/label',
                'required' => true,
                'value' => 0,
                'description' => 'settings/lengow_main_settings/catalog/description',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowIpEnabled' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_main_settings/ip_enable/label',
                'required' => true,
                'value' => false,
                'description' => 'settings/lengow_main_settings/ip_enable/description'
            ),
            'lengowAuthorizedIp' => array(
                'type' => 'text',
                'label' => 'settings/lengow_main_settings/ip/label',
                'required' => true,
                'value' => '127.0.0.1',
                'description' => 'settings/lengow_main_settings/ip/description'
            ),
            'lengowTrackingId' => array(
                'type' => 'select',
                'label' => 'settings/lengow_main_settings/tracking/label',
                'required' => true,
                'editable' => false,
                'value' => $trackingIds['default_value'],
                'store' => $trackingIds['selection'],
                'description' => 'settings/lengow_main_settings/tracking/description'
            )
        );
        // Auto-generate form
        $mainSettingForm = $this->createSettingForm('lengow_main_settings', $mainSettingsElements);
        $mainSettingForm->setParent($mainForm);
        // Export settings
        $exportFormElements = array(
            'lengowExportDisabledProduct' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_export_settings/disabled_products/label',
                'required' => true,
                'editable' => false,
                'value' => false,
                'description' => 'settings/lengow_export_settings/disabled_products/description',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowExportSelectionEnabled' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_export_settings/lengow_selection/label',
                'required' => true,
                'editable' => false,
                'value' => false,
                'description' => 'settings/lengow_export_settings/lengow_selection/description',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowDefaultDispatcher' => array(
                'type' => 'select',
                'label' => 'settings/lengow_export_settings/dispatcher/label',
                'required' => true,
                'editable' => false,
                'value' => $dispatches['default_value'],
                'store' => $dispatches['selection'],
                'description' => 'settings/lengow_export_settings/dispatcher/description',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        // Auto-generate form
        $exportSettingForm = $this->createSettingForm('lengow_export_settings', $exportFormElements);
        $exportSettingForm->setParent($mainForm);
        // Import settings
        $importFormElements = array(
            'lengowImportShipMpEnabled' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_import_settings/ship_mp_enabled/label',
                'editable' => false,
                'value' => false,
                'required' => false,
            ),
            'lengowImportStockMpEnabled' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_import_settings/decrease_stock/label',
                'editable' => false,
                'value' => false,
                'required' => false,
                'description' => 'settings/lengow_import_settings/decrease_stock/description'
            ),
            'lengowImportDefaultDispatcher' => array(
                'type' => 'select',
                'label' => 'settings/lengow_import_settings/dispatcher/label',
                'required' => true,
                'editable' => false,
                'value' => $dispatches['default_value'],
                'store' => $dispatches['selection'],
                'description' => 'settings/lengow_import_settings/dispatcher/description',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowImportDays' => array(
                'type' => 'number',
                'label' => 'settings/lengow_import_settings/import_days/label',
                'value' => 5,
                'minValue' => 0,
                'maxValue' => 99,
                'editable' => false,
                'description' => 'settings/lengow_import_settings/import_days/description'
            ),
            'lengowImportPreprodEnabled' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_import_settings/preprod_mode/label',
                'value' => false,
                'description' => 'settings/lengow_import_settings/preprod_mode/description'
            ),
            'lengowImportReportMailEnabled' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_import_settings/report_mail_enabled/label',
                'value' => true
            ),
            'lengowImportReportMailAddress' => array(
                'type' => 'text',
                'label' => 'settings/lengow_import_settings/report_mail_address/label',
                'required' => false,
                'description' => 'settings/lengow_import_settings/report_mail_address/description'
            )
        );
        // Auto-generate form
        $importSettingForm = $this->createSettingForm('lengow_import_settings', $importFormElements);
        $importSettingForm->setParent($mainForm);
        // Matching import settings
        $orderStatusFormElements = array(
            'lengowIdWaitingShipment' => array(
                'type' => 'select',
                'label' => 'settings/lengow_order_status_settings/id_waiting_shipment/label',
                'required' => true,
                'editable' => false,
                'value' => $orderStates['waiting_shipment'],
                'store' => $orderStates['selection'],
            ),
            'lengowIdShipped' => array(
                'type' => 'select',
                'label' => 'settings/lengow_order_status_settings/id_shipped/label',
                'required' => true,
                'editable' => false,
                'value' => $orderStates['shipped'],
                'store' => $orderStates['selection'],
            ),
            'lengowIdCanceled' => array(
                'type' => 'select',
                'label' => 'settings/lengow_order_status_settings/id_canceled/label',
                'required' => true,
                'editable' => false,
                'value' => $orderStates['canceled'],
                'store' => $orderStates['selection'],
            ),
            'lengowIdShippedByMp' => array(
                'type' => 'select',
                'label' => 'settings/lengow_order_status_settings/id_shipped_by_mp/label',
                'required' => true,
                'editable' => false,
                'value' => $orderStates['shipped'],
                'store' => $orderStates['selection'],
            )
        );
        // Auto-generate form
        $orderStatusSettingForm = $this->createSettingForm('lengow_order_status_settings', $orderStatusFormElements);
        $orderStatusSettingForm->setParent($mainForm);
        $forms = array($mainSettingForm, $exportSettingForm, $importSettingForm, $orderStatusSettingForm);
        $mainForm->setChildren($forms);
        // Translate sub categories (sub-forms settings names)
        // @var \Shopware\Models\Shop\Locale[] $locales
        $locales = $this->entityManager->getRepository('\Shopware\Models\Shop\Locale')->findAll();
        foreach ($forms as $form) {
            $formName = $form->getName();
            // Available locales in Shopware
            foreach ($locales as $locale) {
                $isoCode = $locale->getLocale();
                // If the locale has been translated in Lengow
                if (Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::containsIso($isoCode)) {
                    $formLabel = $this->getTranslation('settings/' . $formName . '/label', $isoCode);
                    $formDescription = $this->getTranslation('settings/' . $formName . '/description', $isoCode);
                    $translationModel = new \Shopware\Models\Config\FormTranslation();
                    $translationModel->setLabel($formLabel);
                    $translationModel->setDescription($formDescription);
                    $translationModel->setLocale($locale);
                    $form->addTranslation($translationModel);
                }
            }
        }
        Shopware_Plugins_Backend_Lengow_Bootstrap::log('log/install/add_form', array('formName' => 'Lengow'));
    }

    /**
     * Remove old settings for old plugin versions
     */
    public function removeOldSettings()
    {
        foreach ($this->oldSettings as $setting) {
            $element = $this->entityManager->getRepository('\Shopware\Models\Config\Element')
                ->findOneBy(array('name' => $setting));
            if ($element) {
                try {
                    $this->entityManager->remove($element);
                    $this->entityManager->flush();
                    Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                        'log/install/delete_old_setting',
                        array('name' => $setting)
                    );
                } catch (Exception $e) {
                    Shopware_Plugins_Backend_Lengow_Bootstrap::log(
                        'log/install/delete_old_setting_error',
                        array('name' => $setting)
                    );
                }
            }
        }
    }

    /**
     * Create settings forms for the plugin (basic settings)
     *
     * @param string $name name of the form
     * @param array $elements options for this form
     *
     * @return \Shopware\Models\Config\Form
     */
    protected function createSettingForm($name, $elements)
    {
        $form = $this->entityManager->getRepository('\Shopware\Models\Config\Form')->findOneBy(array('name' => $name));
        if (is_null($form)) {
            $form = new \Shopware\Models\Config\Form;
            $form->setName($name);
            $form->setLabel($this->getTranslation('settings/' . $name . '/label'));
            $form->setDescription($this->getTranslation('settings/' . $name . '/description'));
        }
        // @var Shopware\Models\Shop\Locale[] $locales
        $locales = $this->entityManager->getRepository('\Shopware\Models\Shop\Locale')->findAll();
        foreach ($elements as $key => $options) {
            $type = $options['type'];
            array_shift($options);
            // Create main element
            $form->setElement($type, $key, $options);
            // Get the form element by name
            $elementModel = $form->getElement($key);
            $this->entityManager->persist($elementModel);
            // Translate fields for this form
            foreach ($locales as $locale) {
                $isoCode = $locale->getLocale();
                if (Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::containsIso($isoCode)) {
                    $label = $this->getTranslation($options['label'], $isoCode);
                    $description = $this->getTranslation($options['description'], $isoCode);
                    $translation = $this->entityManager->getRepository('\Shopware\Models\Config\ElementTranslation')
                        ->findOneBy(array('element' => $elementModel, 'locale' => $locale));
                    if (is_null($translation)) {
                        $translation = new \Shopware\Models\Config\ElementTranslation();
                        $this->entityManager->persist($translation);
                        $elementModel->addTranslation($translation);
                    }
                    $translation->setLabel($label);
                    $translation->setDescription($description);
                    $translation->setLocale($locale);
                }
            }
        }
        Shopware_Plugins_Backend_Lengow_Bootstrap::log('log/install/settings', array('settingName' => $name));
        return $form;
    }

    /**
     * Get all tracking ids for form
     *
     * @return array
     */
    protected function getTrackingIds()
    {
        return array(
            'default_value' => 'ordernumber',
            'selection' => array(
                array('ordernumber', 'Product Number'),
                array('id', 'Product Id')
            )
        );
    }

    /**
     * Get all dispatches for form
     *
     * @return array
     */
    protected function getDispatches()
    {
        // @var Shopware\Models\Dispatch\Dispatch[] $dispatches
        $dispatches = $this->entityManager->getRepository('Shopware\Models\Dispatch\Dispatch')
            ->findBy(array('type' => 0));
        $selection = array();
        $defaultValue = null;
        // Default dispatcher used to get shipping fees in export
        if (count($dispatches) > 0) {
            $defaultValue = $dispatches[0]->getId();
        }
        foreach ($dispatches as $dispatch) {
            $selection[] = array($dispatch->getId(), $dispatch->getName());
        }
        return array(
            'default_value' => $defaultValue,
            'selection' => $selection
        );
    }

    /**
     * Get all order status for form
     *
     * @return array
     */
    protected function getOrderStates()
    {
        $selection = array();
        $shop = Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration::getDefaultShop();
        // @var Shopware\Models\Dispatch\Dispatch[] $dispatches
        $orderStates = $this->entityManager->getRepository('Shopware\Models\Order\Status')
            ->findBy(array('group' => 'state'));
        // Default dispatcher used to get shipping fees in export
        foreach ($orderStates as $orderState) {
            if ($orderState->getId() != -1) {
                if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.5.0')) {
                    // @var Shopware\Models\Snippet\Snippet $orderStateSnippet
                    $orderStateSnippet = $this->entityManager->getRepository('Shopware\Models\Snippet\Snippet')
                        ->findOneBy(
                            array(
                                'localeId' => $shop->getLocale()->getId(),
                                'namespace' => 'backend/static/order_status',
                                'name' => $orderState->getName()
                            )
                        );
                    $orderStateDescription = $orderStateSnippet
                        ? $orderStateSnippet->getValue()
                        : $orderState->getName();
                } else {
                    $orderStateDescription = $orderState->getDescription();
                }
                $selection[] = array($orderState->getId(), $orderStateDescription);
            }
        }
        return array(
            'waiting_shipment' => 1,
            'shipped' => 2,
            'canceled' => 4,
            'selection' => $selection
        );
    }

    /**
     * Get translations for basic settings
     *
     * @param string $key key of the translation
     * @param string $isoCode locale iso code (English by default)
     *
     * @return string
     */
    protected function getTranslation($key, $isoCode = null)
    {
        $translation = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($key, $isoCode);
        return stripslashes($translation);
    }
}
