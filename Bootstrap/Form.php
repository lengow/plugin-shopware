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

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Config\Form as ConfigFormModel;
use Shopware\Models\Config\FormTranslation as ConfigFormTranslationModel;
use Shopware\Models\Config\Element as ConfigElementModel;
use Shopware\Models\Config\ElementTranslation as ConfigElementTranslationModel;
use Shopware\Models\Dispatch\Dispatch as DispatchModel;
use Shopware\Models\Order\Status as OrderStatusModel;
use Shopware\Models\Shop\Locale as ShopLocaleModel;
use Shopware\Models\Snippet\Snippet as SnippetModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Bootstrap_Database as LengowBootstrapDatabase;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowImport as LengowImport;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

/**
 * Form Class
 */
class Shopware_Plugins_Backend_Lengow_Bootstrap_Form
{
    /* Options type */
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_NUMBER = 'number';
    const TYPE_SELECT = 'select';
    const TYPE_TEXT = 'text';

    /* Array data for form option creation */
    const OPTION_TYPE = 'type';
    const OPTION_LABEL = 'label';
    const OPTION_REQUIRED = 'required';
    const OPTION_EDITABLE = 'editable';
    const OPTION_VALUE = 'value';
    const OPTION_MIN_VALUE = 'minValue';
    const OPTION_MAX_VALUE = 'maxValue';
    const OPTION_DESCRIPTION = 'description';
    const OPTION_SCOPE = 'scope';
    const OPTION_STORE = 'store';

    /**
     * @var ModelManager Shopware's entity manager
     */
    protected $entityManager;

    /**
     * @var array old Lengow settings
     */
    protected $oldSettings = array(
        'lengowExportVariationEnabled',
        'lengowExportOutOfStock',
        'lengowEnableImport',
        'lengowOrderStat',
        'lengowOrderStatUpdate',
        'lengowImportPreprodEnabled',
    );

    /**
     * Construct
     */
    public function __construct()
    {
        $this->entityManager = LengowBootstrap::getEntityManager();
    }

    /**
     * Create basic settings for the plugin
     * Accessible in Configuration/Basic Settings/Additional settings menu
     *
     * @param ConfigFormModel $mainForm Lengow main form
     * @param string|false $version current plugin version installed
     */
    public function createConfig($mainForm, $version = false)
    {
        // get tracking ids, dispatches and order states for settings
        $trackingIds = $this->getTrackingIds();
        $dispatches = $this->getDispatches();
        $orderStates = $this->getOrderStates();
        $trackingEnable = $this->getTrackingEnable($version);
        // main settings
        $mainSettingsElements = array(
            LengowConfiguration::ACCOUNT_ID => array(
                self::OPTION_TYPE => self::TYPE_TEXT,
                self::OPTION_LABEL => 'settings/lengow_main_settings/account/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_VALUE => 0,
                self::OPTION_DESCRIPTION => 'settings/lengow_main_settings/account/description',
            ),
            LengowConfiguration::ACCESS_TOKEN => array(
                self::OPTION_TYPE => self::TYPE_TEXT,
                self::OPTION_LABEL => 'settings/lengow_main_settings/access/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_VALUE => 0,
                self::OPTION_DESCRIPTION => 'settings/lengow_main_settings/access/description',
            ),
            LengowConfiguration::SECRET => array(
                self::OPTION_TYPE => self::TYPE_TEXT,
                self::OPTION_LABEL => 'settings/lengow_main_settings/secret/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_VALUE => 0,
                self::OPTION_DESCRIPTION => 'settings/lengow_main_settings/secret/description',
            ),
            LengowConfiguration::SHOP_ACTIVE => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_main_settings/enable/label',
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => 0,
                self::OPTION_DESCRIPTION => 'settings/lengow_main_settings/enable/description',
                self::OPTION_SCOPE => ConfigElementModel::SCOPE_SHOP,
            ),
            LengowConfiguration::CATALOG_IDS => array(
                self::OPTION_TYPE => self::TYPE_TEXT,
                self::OPTION_LABEL => 'settings/lengow_main_settings/catalog/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_VALUE => 0,
                self::OPTION_DESCRIPTION => 'settings/lengow_main_settings/catalog/description',
                self::OPTION_SCOPE => ConfigElementModel::SCOPE_SHOP,
            ),
            LengowConfiguration::AUTHORIZED_IP_ENABLED => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_main_settings/ip_enable/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_VALUE => false,
                self::OPTION_DESCRIPTION => 'settings/lengow_main_settings/ip_enable/description',
            ),
            LengowConfiguration::AUTHORIZED_IPS => array(
                self::OPTION_TYPE => self::TYPE_TEXT,
                self::OPTION_LABEL => 'settings/lengow_main_settings/ip/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_VALUE => '127.0.0.1',
                self::OPTION_DESCRIPTION => 'settings/lengow_main_settings/ip/description',
            ),
            LengowConfiguration::TRACKING_ENABLED => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_main_settings/tracking_enable/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_VALUE => $trackingEnable,
                self::OPTION_DESCRIPTION => 'settings/lengow_main_settings/tracking_enable/description',
            ),
            LengowConfiguration::TRACKING_ID => array(
                self::OPTION_TYPE => self::TYPE_SELECT,
                self::OPTION_LABEL => 'settings/lengow_main_settings/tracking_id/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => $trackingIds['default_value'],
                self::OPTION_STORE => $trackingIds['selection'],
                self::OPTION_DESCRIPTION => 'settings/lengow_main_settings/tracking_id/description',
            ),
        );
        // auto-generate form
        $mainSettingForm = $this->createSettingForm('lengow_main_settings', $mainSettingsElements);
        $mainSettingForm->setParent($mainForm);
        // export settings
        $exportFormElements = array(
            LengowConfiguration::INACTIVE_ENABLED => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_export_settings/disabled_products/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => false,
                self::OPTION_DESCRIPTION => 'settings/lengow_export_settings/disabled_products/description',
                self::OPTION_SCOPE => ConfigElementModel::SCOPE_SHOP,
            ),
            LengowConfiguration::SELECTION_ENABLED => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_export_settings/lengow_selection/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => false,
                self::OPTION_DESCRIPTION => 'settings/lengow_export_settings/lengow_selection/description',
                self::OPTION_SCOPE => ConfigElementModel::SCOPE_SHOP,
            ),
            LengowConfiguration::DEFAULT_EXPORT_CARRIER_ID => array(
                self::OPTION_TYPE => self::TYPE_SELECT,
                self::OPTION_LABEL => 'settings/lengow_export_settings/dispatcher/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => $dispatches['default_value'],
                self::OPTION_STORE => $dispatches['selection'],
                self::OPTION_DESCRIPTION => 'settings/lengow_export_settings/dispatcher/description',
                self::OPTION_SCOPE => ConfigElementModel::SCOPE_SHOP,
            ),
        );
        // auto-generate form
        $exportSettingForm = $this->createSettingForm('lengow_export_settings', $exportFormElements);
        $exportSettingForm->setParent($mainForm);
        // import settings
        $importFormElements = array(
            LengowConfiguration::SHIPPED_BY_MARKETPLACE_ENABLED => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_import_settings/ship_mp_enabled/label',
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => false,
                self::OPTION_REQUIRED => false,
            ),
            LengowConfiguration::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_import_settings/decrease_stock/label',
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => false,
                self::OPTION_REQUIRED => false,
                self::OPTION_DESCRIPTION => 'settings/lengow_import_settings/decrease_stock/description',
            ),
            LengowConfiguration::DEFAULT_IMPORT_CARRIER_ID => array(
                self::OPTION_TYPE => self::TYPE_SELECT,
                self::OPTION_LABEL => 'settings/lengow_import_settings/dispatcher/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => $dispatches['default_value'],
                self::OPTION_STORE => $dispatches['selection'],
                self::OPTION_DESCRIPTION => 'settings/lengow_import_settings/dispatcher/description',
                self::OPTION_SCOPE => ConfigElementModel::SCOPE_SHOP,
            ),
            LengowConfiguration::SYNCHRONIZATION_DAY_INTERVAL => array(
                self::OPTION_TYPE => self::TYPE_NUMBER,
                self::OPTION_LABEL => 'settings/lengow_import_settings/import_days/label',
                self::OPTION_VALUE => 3,
                self::OPTION_MIN_VALUE => (LengowImport::MIN_INTERVAL_TIME / 86400),
                self::OPTION_MAX_VALUE => (LengowImport::MAX_INTERVAL_TIME / 86400),
                self::OPTION_EDITABLE => false,
                self::OPTION_DESCRIPTION => 'settings/lengow_import_settings/import_days/description',
            ),
            LengowConfiguration::DEBUG_MODE_ENABLED => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_import_settings/debug_mode/label',
                self::OPTION_VALUE => false,
                self::OPTION_DESCRIPTION => 'settings/lengow_import_settings/debug_mode/description',
            ),
            LengowConfiguration::REPORT_MAIL_ENABLED => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_import_settings/report_mail_enabled/label',
                self::OPTION_VALUE => true,
            ),
            LengowConfiguration::REPORT_MAILS => array(
                self::OPTION_TYPE => self::TYPE_TEXT,
                self::OPTION_LABEL => 'settings/lengow_import_settings/report_mail_address/label',
                self::OPTION_REQUIRED => false,
                self::OPTION_DESCRIPTION => 'settings/lengow_import_settings/report_mail_address/description',
            ),
            LengowConfiguration::CURRENCY_CONVERSION_ENABLED => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_import_settings/currency_conversion_title/label',
                self::OPTION_DESCRIPTION => 'settings/lengow_import_settings/currency_conversion_title/description',
                self::OPTION_VALUE => true,
            ),
            LengowConfiguration::B2B_WITHOUT_TAX_ENABLED => array(
                self::OPTION_TYPE => self::TYPE_BOOLEAN,
                self::OPTION_LABEL => 'settings/lengow_import_settings/import_btob/label',
                self::OPTION_DESCRIPTION => 'settings/lengow_import_settings/import_btob/description',
                self::OPTION_VALUE => false,
            ),
        );
        // auto-generate form
        $importSettingForm = $this->createSettingForm('lengow_import_settings', $importFormElements);
        $importSettingForm->setParent($mainForm);
        // matching import settings
        $orderStatusFormElements = array(
            LengowConfiguration::WAITING_SHIPMENT_ORDER_ID => array(
                self::OPTION_TYPE => self::TYPE_SELECT,
                self::OPTION_LABEL => 'settings/lengow_order_status_settings/id_waiting_shipment/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => $orderStates[LengowOrder::STATE_WAITING_SHIPMENT],
                self::OPTION_STORE => $orderStates['selection'],
            ),
            LengowConfiguration::SHIPPED_ORDER_ID => array(
                self::OPTION_TYPE => self::TYPE_SELECT,
                self::OPTION_LABEL => 'settings/lengow_order_status_settings/id_shipped/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => $orderStates[LengowOrder::STATE_SHIPPED],
                self::OPTION_STORE => $orderStates['selection'],
            ),
            LengowConfiguration::CANCELED_ORDER_ID => array(
                self::OPTION_TYPE => self::TYPE_SELECT,
                self::OPTION_LABEL => 'settings/lengow_order_status_settings/id_canceled/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => $orderStates[LengowOrder::STATE_CANCELED],
                self::OPTION_STORE => $orderStates['selection'],
            ),
            LengowConfiguration::SHIPPED_BY_MARKETPLACE_ORDER_ID => array(
                self::OPTION_TYPE => self::TYPE_SELECT,
                self::OPTION_LABEL => 'settings/lengow_order_status_settings/id_shipped_by_mp/label',
                self::OPTION_REQUIRED => true,
                self::OPTION_EDITABLE => false,
                self::OPTION_VALUE => $orderStates[LengowOrder::STATE_SHIPPED],
                self::OPTION_STORE => $orderStates['selection'],
            ),
        );
        // auto-generate form
        $orderStatusSettingForm = $this->createSettingForm('lengow_order_status_settings', $orderStatusFormElements);
        $orderStatusSettingForm->setParent($mainForm);
        $forms = array($mainSettingForm, $exportSettingForm, $importSettingForm, $orderStatusSettingForm);
        $mainForm->setChildren($forms);
        // translate sub categories (sub-forms settings names)
        /** @var ShopLocaleModel[] $locales */
        $locales = $this->entityManager->getRepository('Shopware\Models\Shop\Locale')->findAll();
        foreach ($forms as $form) {
            $formName = $form->getName();
            // available locales in Shopware
            foreach ($locales as $locale) {
                $isoCode = $locale->getLocale();
                // if the locale has been translated in Lengow
                if (LengowTranslation::containsIso($isoCode)) {
                    $formLabel = $this->getTranslation('settings/' . $formName . '/label', $isoCode);
                    $formDescription = $this->getTranslation('settings/' . $formName . '/description', $isoCode);
                    $translationModel = new ConfigFormTranslationModel();
                    $translationModel->setLabel($formLabel);
                    $translationModel->setDescription($formDescription);
                    $translationModel->setLocale($locale);
                    $form->addTranslation($translationModel);
                }
            }
        }
        LengowBootstrap::log('log/install/add_form', array('formName' => 'Lengow'));
    }

    /**
     * Remove old settings for old plugin versions
     */
    public function removeOldSettings()
    {
        foreach ($this->oldSettings as $setting) {
            $element = $this->entityManager->getRepository('Shopware\Models\Config\Element')
                ->findOneBy(array('name' => $setting));
            if ($element === null && LengowBootstrapDatabase::tableExist(LengowBootstrapDatabase::TABLE_SETTINGS)) {
                $element = $this->entityManager->getRepository('Shopware\CustomModels\Lengow\Settings')
                    ->findOneBy(array('name' => $setting));
            }
            if ($element) {
                try {
                    $this->entityManager->remove($element);
                    $this->entityManager->flush();
                    LengowBootstrap::log('log/install/delete_old_setting', array('name' => $setting));
                } catch (Exception $e) {
                    LengowBootstrap::log('log/install/delete_old_setting_error', array('name' => $setting));
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
     * @return ConfigFormModel
     */
    protected function createSettingForm($name, $elements)
    {
        $form = $this->entityManager->getRepository('Shopware\Models\Config\Form')->findOneBy(array('name' => $name));
        if ($form === null) {
            $form = new ConfigFormModel();
            $form->setName($name);
            $form->setLabel($this->getTranslation('settings/' . $name . '/label'));
            $form->setDescription($this->getTranslation('settings/' . $name . '/description'));
        }
        /** @var ShopLocaleModel[] $locales */
        $locales = $this->entityManager->getRepository('Shopware\Models\Shop\Locale')->findAll();
        foreach ($elements as $key => $options) {
            $type = $options[self::OPTION_TYPE];
            array_shift($options);
            // create main element
            $form->setElement($type, $key, $options);
            // get the form element by name
            $elementModel = $form->getElement($key);
            try {
                $this->entityManager->persist($elementModel);
            } catch (Exception $e) {
                $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                    . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                LengowBootstrap::log(
                    'log/install/settings_failed',
                    array(
                        'setting_name' => $key,
                        'decoded_message' => $errorMessage,
                    )
                );
            }
            // translate fields for this form
            foreach ($locales as $locale) {
                $isoCode = $locale->getLocale();
                if (LengowTranslation::containsIso($isoCode)) {
                    $label = $this->getTranslation($options[self::OPTION_LABEL], $isoCode);
                    $description = isset($options[self::OPTION_DESCRIPTION])
                        ? $this->getTranslation($options[self::OPTION_DESCRIPTION], $isoCode)
                        : '';
                    $translation = $this->entityManager->getRepository('Shopware\Models\Config\ElementTranslation')
                        ->findOneBy(array('element' => $elementModel, 'locale' => $locale));
                    if ($translation === null) {
                        $translation = new ConfigElementTranslationModel();
                        try {
                            $this->entityManager->persist($translation);
                        } catch (Exception $e) {
                            $errorMessage = '[Doctrine error]: "' . $e->getMessage()
                                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                            LengowBootstrap::log(
                                'log/install/settings_translation_failed',
                                array(
                                    'setting_name' => $key,
                                    'decoded_message' => $errorMessage,
                                )
                            );
                        }
                        if ($elementModel) {
                            $elementModel->addTranslation($translation);
                        }
                    }
                    $translation->setLabel($label);
                    $translation->setDescription($description);
                    $translation->setLocale($locale);
                }
            }
        }
        LengowBootstrap::log('log/install/settings', array('setting_name' => $name));
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
                array('id', 'Product Id'),
            ),
        );
    }

    /**
     * Get all dispatches for form
     *
     * @return array
     */
    protected function getDispatches()
    {
        /** @var DispatchModel[] $dispatches */
        $dispatches = $this->entityManager->getRepository('Shopware\Models\Dispatch\Dispatch')
            ->findBy(array('type' => 0));
        $selection = array();
        $defaultValue = null;
        // default dispatcher used to get shipping fees in export
        if (!empty($dispatches)) {
            $defaultValue = $dispatches[0]->getId();
        }
        foreach ($dispatches as $dispatch) {
            $selection[] = array($dispatch->getId(), $dispatch->getName());
        }
        return array(
            'default_value' => $defaultValue,
            'selection' => $selection,
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
        $shop = LengowConfiguration::getDefaultShop();
        /** @var OrderStatusModel[] $orderStates */
        $orderStates = $this->entityManager->getRepository('Shopware\Models\Order\Status')
            ->findBy(array('group' => 'state'));
        // default dispatcher used to get shipping fees in export
        foreach ($orderStates as $orderState) {
            if ($orderState->getId() != -1) {
                if (LengowMain::compareVersion('5.5.0')) {
                    /** @var SnippetModel $orderStateSnippet */
                    $orderStateSnippet = $this->entityManager->getRepository('Shopware\Models\Snippet\Snippet')
                        ->findOneBy(
                            array(
                                'localeId' => $shop->getLocale()->getId(),
                                'namespace' => 'backend/static/order_status',
                                'name' => $orderState->getName(),
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
            LengowOrder::STATE_WAITING_SHIPMENT => 1,
            LengowOrder::STATE_SHIPPED => 2,
            LengowOrder::STATE_CANCELED => 4,
            'selection' => $selection,
        );
    }

    /**
     * Active Lengow tracker for versions 1.0.0 - 1.3.3
     *
     * @param string $version current plugin version installed
     *
     * @return boolean
     */
    protected function getTrackingEnable($version)
    {
        $value = false;
        if ($version && version_compare($version, '1.4.0', '<') && !LengowConfiguration::isNewMerchant()) {
            $value = true;
        }
        return $value;
    }

    /**
     * Get translations for basic settings
     *
     * @param string $key key of the translation
     * @param string|null $isoCode locale iso code (English by default)
     *
     * @return string
     */
    protected function getTranslation($key, $isoCode = null)
    {
        $translation = LengowMain::decodeLogMessage($key, $isoCode);
        return stripslashes($translation);
    }
}
