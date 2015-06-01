<?php
/**
 * Lengow.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */

class Shopware_Controllers_Backend_Lengow extends Shopware_Controllers_Backend_ExtJs
{

    /**
     * Event listener function of settings store
     * 
     * @return mixed
     */
    public function getSettingsAction()
    {        
        $sqlParams['shopId'] = $this->Request()->getParam('shopId');
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS
                    settings.id,
                    settings.lengowIdGroup,
                    settings.lengowExportAllProducts,
                    settings.lengowExportDisabledProducts,
                    settings.lengowExportVariantProducts,
                    settings.lengowExportAttributes,
                    settings.lengowExportAttributesTitle,
                    settings.lengowExportOutStock,
                    settings.lengowExportImageSize,
                    settings.lengowExportImages,
                    settings.lengowExportFormat,
                    dscd.id as lengowShippingCostDefault,
                    settings.lengowExportFile,
                    settings.lengowExportCron,
                    dcd.id as lengowCarrierDefault,
                    sp.id as lengowOrderProcess,
                    ssh.id as lengowOrderShipped,
                    sc.id as lengowOrderCancel,
                    settings.lengowImportDays,
                    settings.lengowMethodName,
                    settings.lengowForcePrice,
                    settings.lengowReportMail,
                    settings.lengowEmailAddress,
                    settings.lengowImportCron,
                    CONCAT( settings.lengowExportUrl, shops.name ) as lengowExportUrl,
                    CONCAT( settings.lengowImportUrl, shops.name ) as lengowImportUrl 
                FROM lengow_settings as settings
                LEFT JOIN s_core_shops as shops
                    ON settings.shopID = shops.id
                LEFT JOIN s_premium_dispatch as dscd
                    ON settings.lengowShippingCostDefault = dscd.id
                LEFT JOIN s_premium_dispatch as dcd
                    ON settings.lengowCarrierDefault = dcd.id
                LEFT JOIN s_core_states as sp
                    ON settings.lengowOrderProcess = sp.id
                LEFT JOIN s_core_states as ssh
                    ON settings.lengowOrderShipped = ssh.id
                LEFT JOIN s_core_states as sc
                    ON settings.lengowOrderCancel = sc.id
                WHERE shops.id = :shopId";
        $data = Shopware()->Db()->fetchAll($sql, $sqlParams);

        if (!$data) {
            $exportFormats = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportFormats();
            $dispatchs = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getDispatch();
            $orderStates = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getAllOrderStates();
            $importPayments = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getShippingName();
            $data[0]['newSetting'] = true;
            $data[0]['lengowExportAllProducts'] = true; 
            $data[0]['lengowExportVariantProducts'] = true; 
            $data[0]['lengowExportAttributesTitle'] = true;
            $data[0]['lengowExportFormat'] = $exportFormats[0]->id;
            $data[0]['lengowShippingCostDefault'] = $dispatchs[0]->id;
            $data[0]['lengowCarrierDefault'] = $dispatchs[0]->id;
            $data[0]['lengowOrderProcess'] = $orderStates[0]->id;
            $data[0]['lengowOrderShipped'] = $orderStates[0]->id;
            $data[0]['lengowOrderCancel'] = $orderStates[0]->id;
            $data[0]['lengowImportDays'] = 3;
            $data[0]['lengowMethodName'] = $importPayments[0]->id;
            $data[0]['lengowReportMail'] = true;
        }

        $data[0]['lengowIdUser'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getIdCustomer();
        $data[0]['lengowApiKey'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getTokenCustomer();
        $data[0]['lengowAuthorisedIp'] = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getIPs();
        
        $this->View()->assign(array(
            'success' => true,
            'data' => $data
        ));
    }

    /**
     * Event listener function of settings store
     * 
     * @return mixed
     */
    public function updateSettingsAction()
    {
        $request = $this->Request();
        $settingsParams = $request->getParams();

        $sqlParams['shopId'] = (int) $settingsParams['shopId'];
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS settings.id 
                FROM lengow_settings as settings 
                WHERE settings.shopID = :shopId";
        $settingId = Shopware()->Db()->fetchOne($sql, $sqlParams);      

        if (!$settingId) {
            $shop = Shopware()->Models()->getReference('Shopware\Models\Shop\Shop',(int) $settingsParams['shopId']);
            $host = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getBaseUrl();
            $pathPlugin = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getPathPlugin();
            $exportUrl = $host . $pathPlugin . 'Webservice/export.php?shop=';
            $importUrl = $host . $pathPlugin . 'Webservice/import.php?shop=';
            $setting = new Shopware\CustomModels\Lengow\Setting;
            $setting->setShop($shop)
                    ->setLengowExportUrl($exportUrl)
                    ->setLengowImportUrl($importUrl);
        } else {
            $setting = Shopware()->Models()->getReference('Shopware\CustomModels\Lengow\Setting',(int) $settingId);
        } 

        $shippingCost = Shopware()->Models()->getReference('Shopware\Models\Dispatch\Dispatch', (int) $settingsParams['lengowShippingCostDefault']);
        $carrier = Shopware()->Models()->getReference('Shopware\Models\Dispatch\Dispatch', (int) $settingsParams['lengowCarrierDefault']);
        $orderProcessStatus = Shopware()->Models()->getReference('Shopware\Models\Order\Status', (int) $settingsParams['lengowOrderProcess']);
        $orderShippedStatus = Shopware()->Models()->getReference('Shopware\Models\Order\Status', (int) $settingsParams['lengowOrderShipped']);
        $orderCancelStatus = Shopware()->Models()->getReference('Shopware\Models\Order\Status', (int) $settingsParams['lengowOrderCancel']);
        $setting->setLengowIdGroup($settingsParams['lengowIdGroup'])
                ->setLengowExportAllProducts($settingsParams['lengowExportAllProducts'])
                ->setLengowExportDisabledProducts($settingsParams['lengowExportDisabledProducts'])
                ->setLengowExportVariantProducts($settingsParams['lengowExportVariantProducts'])
                ->setLengowExportAttributes($settingsParams['lengowExportAttributes'])
                ->setLengowExportAttributesTitle($settingsParams['lengowExportAttributesTitle'])
                ->setLengowExportOutStock($settingsParams['lengowExportOutStock'])
                ->setLengowExportImageSize($settingsParams['lengowExportImageSize'])
                ->setLengowExportImages((int) $settingsParams['lengowExportImages'])
                ->setLengowExportFormat($settingsParams['lengowExportFormat'])
                ->setLengowShippingCostDefault($shippingCost)
                ->setLengowExportFile($settingsParams['lengowExportFile'])
                ->setLengowExportCron($settingsParams['lengowExportCron'])  
                ->setLengowCarrierDefault($carrier)
                ->setLengowOrderProcess($orderProcessStatus)
                ->setLengowOrderShipped($orderShippedStatus)
                ->setLengowOrderCancel($orderCancelStatus)
                ->setLengowImportDays((int) $settingsParams['lengowImportDays'])
                ->setLengowMethodName($settingsParams['lengowMethodName'])
                ->setLengowReportMail($settingsParams['lengowReportMail'])
                ->setLengowEmailAddress($settingsParams['lengowEmailAddress'])
                ->setLengowImportCron($settingsParams['lengowImportCron']);    
        Shopware()->Models()->persist($setting);
        Shopware()->Models()->flush();

        $this->View()->assign(array(
            'data' => $settingsParams['shopId'],
            'test' => $settingId, 
            'success' => true
        ));    
    }

	/**
     * Event listener function of export_formats store
     * 
     * @return mixed
     */
    public function getExportFormatsAction()
    {
        $exportFormats = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getExportFormats();
        $formats = array();
        foreach ($exportFormats as $format) {
            $formats[] = array('id' => $format->id, 'name' => $format->name);
        }

        $count = count($formats);

        $this->View()->assign(array(
            'success' => true,
            'data'    => $formats,
            'total'   => $count
        ));
    }

    /**
     * Event listener function of image_formats store
     * 
     * @return mixed
     */
    public function getImageFormatsAction()
    {
        $exportImageFormats = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getImagesSize();
        $imageFormats = array();
        foreach ($exportImageFormats as $value) {
            $imageFormats[] = array('id' => $value->id, 'name' => $value->name);  
        }

        $count = count($imageFormats);

        $this->View()->assign(array(
            'success' => true,
            'data'    => $imageFormats,
            'total'   => $count
        ));
    }

    /**
     * Event listener function of export_images store
     * 
     * @return mixed
     */
    public function getExportImagesAction()
    {
        $exportImage = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getImagesCount();
        $nbImage = array();
        foreach ($exportImage as $value) {
            $nbImage[] = array('id' =>  $value->id, 'name' => $value->name.' images');  
        }

        $count = count($nbImage);

        $this->View()->assign(array(
            'success' => true,
            'data'    => $nbImage,
            'total'   => $count
        ));
    }

    /**
     * Event listener function of payment_methods store
     * 
     * @return mixed
     */
    public function getPaymentMethodsAction()
    {
        $importPayments = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getShippingName();
        $payments = array();
        foreach ($importPayments as $value) {
            $payments[] = array('id' =>  $value->id, 'name' => $value->name);  
        }
        
        $count = count($payments);

        $this->View()->assign(array(
            'success' => true,
            'data'    => $payments,
            'total'   => $count
        ));
    }

}

