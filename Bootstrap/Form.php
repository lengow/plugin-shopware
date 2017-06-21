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
     * Create basic settings for the plugin
     * Accessible in Configuration/Basic Settings/Additional settings menu
     */
    public function createConfig()
    {
        // @var Shopware\Models\Config\Form $mainForm
        $mainForm = Shopware()->Plugins()->Backend()->Lengow()->Form();
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        // Main settings
        $mainSettingsElements = array(
            'lengowShopActive' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_main_settings/enable/label',
                'editable' => false,
                'value' => 0,
                'description' => 'settings/lengow_main_settings/enable/description',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowAccountId' => array(
                'type' => 'text',
                'label' => 'settings/lengow_main_settings/account/label',
                'required' => true,
                'value' => 0,
                'description' => 'settings/lengow_main_settings/account/description',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowAccessToken' => array(
                'type' => 'text',
                'label' => 'settings/lengow_main_settings/access/label',
                'required' => true,
                'value' => 0,
                'description' => 'settings/lengow_main_settings/access/description',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            ),
            'lengowSecretToken' => array(
                'type' => 'text',
                'label' => 'settings/lengow_main_settings/secret/label',
                'required' => true,
                'value' => 0,
                'description' => 'settings/lengow_main_settings/secret/description',
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
            )
        );
        // Auto-generate form
        $mainSettingForm = $this->createSettingForm('lengow_main_settings', $mainSettingsElements);
        $mainSettingForm->setParent($mainForm);
        // Export settings
        // @var Shopware\Models\Dispatch\Dispatch[] $dispatches
        $dispatches = $em->getRepository('Shopware\Models\Dispatch\Dispatch')->findBy(array('type' => 0));
        $selection = array();
        $defaultValue = null;
        // Default dispatcher used to get shipping fees in export
        if (count($dispatches) > 0) {
            $defaultValue = $dispatches[0]->getId();
        }
        foreach ($dispatches as $dispatch) {
            $selection[] = array($dispatch->getId(), $dispatch->getName());
        }
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
                'value' => $defaultValue,
                'store' => $selection,
                'description' => 'settings/lengow_export_settings/dispatcher/description',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        // Auto-generate form
        $exportSettingForm = $this->createSettingForm('lengow_export_settings', $exportFormElements);
        $exportSettingForm->setParent($mainForm);
        // Import settings
        $importFormElements = array(
            'lengowEnableImport' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_import_settings/enable_import/label',
                'editable' => false,
                'value' => false,
                'required' => false,
                'description' => 'settings/lengow_import_settings/enable_import/description'
            ),
            'lengowImportShipMpEnabled' => array(
                'type' => 'boolean',
                'label' => 'settings/lengow_import_settings/decrease_stock/label',
                'editable' => false,
                'value' => false,
                'required' => false,
                'description' => 'settings/lengow_import_settings/decrease_stock/description'
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
            )
        );
        // Auto-generate form
        $importSettingForm = $this->createSettingForm('lengow_import_settings', $importFormElements);
        $importSettingForm->setParent($mainForm);
        $forms = array($mainSettingForm, $exportSettingForm, $importSettingForm);
        $mainForm->setChildren($forms);
        // Translate sub categories (sub-forms settings names)
        // @var \Shopware\Models\Shop\Locale[] $locales
        $locales = $em->getRepository('\Shopware\Models\Shop\Locale')->findAll();
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
     * Create settings forms for the plugin (basic settings)
     *
     * @param string $name name of the form
     * @param array $elements options for this form
     *
     * @return \Shopware\Models\Config\Form
     */
    protected function createSettingForm($name, $elements)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $form = $em->getRepository('\Shopware\Models\Config\Form')->findOneBy(array('name' => $name));
        if (is_null($form)) {
            $form = new \Shopware\Models\Config\Form;
            $form->setName($name);
            $form->setLabel($this->getTranslation('settings/' . $name . '/label'));
            $form->setDescription($this->getTranslation('settings/' . $name . '/description'));
        }
        // @var Shopware\Models\Shop\Locale[] $locales
        $locales = $em->getRepository('\Shopware\Models\Shop\Locale')->findAll();
        foreach ($elements as $key => $options) {
            $type = $options['type'];
            array_shift($options);
            // Create main element
            $form->setElement($type, $key, $options);
            // Get the form element by name
            $elementModel = $form->getElement($key);
            // Translate fields for this form
            foreach ($locales as $locale) {
                $isoCode = $locale->getLocale();
                if (Shopware_Plugins_Backend_Lengow_Components_LengowTranslation::containsIso($isoCode)) {
                    $label = $this->getTranslation($options['label'], $isoCode);
                    $description = $this->getTranslation($options['description'], $isoCode);
                    $translation = $em->getRepository('\Shopware\Models\Config\ElementTranslation')
                        ->findOneBy(array('element' => $elementModel, 'locale' => $locale));
                    if (is_null($translation)) {
                        $translationModel = new \Shopware\Models\Config\ElementTranslation();
                        $translationModel->setLabel($label);
                        $translationModel->setDescription($description);
                        $translationModel->setLocale($locale);
                        $elementModel->addTranslation($translationModel);
                    }
                }
            }
        }
        Shopware_Plugins_Backend_Lengow_Bootstrap::log('log/install/settings', array('settingName' => $name));
        return $form;
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
