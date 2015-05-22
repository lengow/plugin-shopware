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
                    settings.lengowIdUser,
                    settings.lengowIdGroup,
                    settings.lengowApiKey,
                    settings.lengowAuthorisedIp,
                    settings.lengowExportAllProducts,
                    settings.lengowExportDisabledProducts,
                    settings.lengowExportVariantProducts,
                    settings.lengowExportAttributes,
                    settings.lengowExportAttributesTitle,
                    settings.lengowExportOutStock,
                    settings.lengowExportImageSize,
                    settings.lengowExportImages,
                    settings.lengowExportFormat,
                    settings.lengowExportFile,
                    dispatchs.id as lengowCarrierDefault,
                    sp.id as lengowOrderProcess,
                    ssh.id as lengowOrderShipped,
                    sc.id as lengowOrderCancel,
                    settings.lengowImportDays,
                    settings.lengowMethodName,
                    settings.lengowForcePrice,
                    settings.lengowReportMail,
                    settings.lengowEmailAddress,
                    settings.lengowExportCron,
                    settings.lengowDebug,
                    CONCAT( settings.lengowExportUrl, shops.name ) as lengowExportUrl,
                    CONCAT( settings.lengowImportUrl, shops.name ) as lengowImportUrl 
                FROM lengow_settings as settings
                LEFT JOIN s_core_shops as shops
                    ON settings.shopID = shops.id
                LEFT JOIN s_premium_dispatch as dispatchs
                    ON settings.lengowCarrierDefault = dispatchs.id
                LEFT JOIN s_core_states as sp
                    ON settings.lengowOrderProcess = sp.id
                LEFT JOIN s_core_states as ssh
                    ON settings.lengowOrderShipped = ssh.id
                LEFT JOIN s_core_states as sc
                    ON settings.lengowOrderCancel = sc.id
                WHERE shops.id = :shopId";
        $data = Shopware()->Db()->fetchAll($sql, $sqlParams);

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
            $shop = Shopware()->Models()->getReference('Shopware\Models\Shop\Shop', (int) $settingsParams['shopId']);
            $pathPlugin = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getPathPlugin();
            $exportUrl = 'http://' . $_SERVER['SERVER_NAME'] . $pathPlugin . 'Webservice/export.php?shop=';
            $importUrl = 'http://' . $_SERVER['SERVER_NAME'] . $pathPlugin . 'Webservice/import.php?shop=';

            $setting = new Shopware\CustomModels\Lengow\Setting;
            $setting->setShop($shop)
                    ->setLengowExportUrl($exportUrl)
                    ->setLengowImportUrl($importUrl);
        } else {
            $setting = Shopware()->Models()->getReference('Shopware\CustomModels\Lengow\Setting', $settingId);
        } 

        $dispatch = Shopware()->Models()->getReference('Shopware\Models\Dispatch\Dispatch', (int) $settingsParams['lengowCarrierDefault']);
        $orderProcessStatus = Shopware()->Models()->getReference('Shopware\Models\Order\Status', (int) $settingsParams['lengowOrderProcess']);
        $orderShippedStatus = Shopware()->Models()->getReference('Shopware\Models\Order\Status', (int) $settingsParams['lengowOrderShipped']);
        $orderCancelStatus = Shopware()->Models()->getReference('Shopware\Models\Order\Status', (int) $settingsParams['lengowOrderCancel']);

        $setting->setLengowIdUser($settingsParams['lengowIdUser'])
                ->setLengowIdGroup($settingsParams['lengowIdGroup'])
                ->setLengowApiKey($settingsParams['lengowApiKey'])
                ->setLengowAuthorisedIp($settingsParams['lengowAuthorisedIp'])
                ->setLengowExportAllProducts($settingsParams['lengowExportAllProducts'])
                ->setLengowExportDisabledProducts($settingsParams['lengowExportDisabledProducts'])
                ->setLengowExportVariantProducts($settingsParams['lengowExportVariantProducts'])
                ->setLengowExportAttributes($settingsParams['lengowExportAttributes'])
                ->setLengowExportAttributesTitle($settingsParams['lengowExportAttributesTitle'])
                ->setLengowExportOutStock($settingsParams['lengowExportOutStock'])
                ->setLengowExportImageSize($settingsParams['lengowExportImageSize'])
                ->setLengowExportImages((int) $settingsParams['lengowExportImages'])
                ->setLengowExportFormat($settingsParams['lengowExportFormat'])
                ->setLengowExportFile($settingsParams['lengowExportFile'])
                ->setLengowCarrierDefault($dispatch)
                ->setLengowOrderProcess($orderProcessStatus)
                ->setLengowOrderShipped($orderShippedStatus)
                ->setLengowOrderCancel($orderCancelStatus)
                ->setLengowImportDays((int) $settingsParams['lengowImportDays'])
                ->setLengowMethodName($settingsParams['lengowMethodName'])
                ->setLengowForcePrice($settingsParams['lengowForcePrice'])
                ->setLengowReportMail($settingsParams['lengowReportMail'])
                ->setLengowEmailAddress($settingsParams['lengowEmailAddress'])
                ->setLengowExportCron($settingsParams['lengowExportCron'])
                ->setLengowDebug($settingsParams['lengowDebug']);      
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

