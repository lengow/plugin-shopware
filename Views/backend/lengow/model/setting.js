//{block name="backend/lengow/model/setting"}
Ext.define('Shopware.apps.Lengow.model.Setting', {
    /**
     * Extends the standard Ext Model
     * @string
     */
    extend: 'Ext.data.Model',

    idProperty: 'shopId',

    /**
     * The fields used for this model
     * @array
     */
    fields: [
        { name: 'shopId', type: 'int' },
        { name: 'id', type: 'int' },
        { name: 'lengowIdUser', type: 'string' },
        { name: 'lengowIdGroup', type: 'string' },
        { name: 'lengowApiKey', type: 'string' },
        { name: 'lengowAuthorisedIp', type: 'string' },
        { name: 'lengowExportAllProducts', type: 'boolean' },
        { name: 'lengowExportDisabledProducts', type: 'boolean' },
        { name: 'lengowExportVariantProducts', type: 'boolean' },
        { name: 'lengowExportAttributes', type: 'boolean' },
        { name: 'lengowExportAttributesTitle', type: 'boolean' },
        { name: 'lengowExportOutStock', type: 'boolean' },
        { name: 'lengowExportImageSize', type: 'string' },
        { name: 'lengowExportImages', type: 'int' },
        { name: 'lengowExportFormat', type: 'string' },
        { name: 'lengowExportFile', type: 'boolean' },
        { name: 'lengowExportUrl', type: 'string' },
        { name: 'lengowCarrierDefault', type: 'int' },
        { name: 'lengowOrderProcess', type: 'int' },
        { name: 'lengowOrderShipped', type: 'int' },
        { name: 'lengowOrderCancel', type: 'int' },
        { name: 'lengowImportDays', type: 'int' },
        { name: 'lengowMethodName', type: 'string' },
        { name: 'lengowForcePrice', type: 'boolean' },
        { name: 'lengowReportMail', type: 'boolean' },
        { name: 'lengowEmailAddress', type: 'string' },
        { name: 'lengowImportUrl', type: 'string' },
        { name: 'lengowExportCron', type: 'boolean' },
        { name: 'lengowDebug', type: 'boolean' },
        { name: 'newSetting', type: 'boolean' }
    ],

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',   
        api: {
            read: '{url controller="Lengow" action="getSettings"}',
            update: '{url controller="Lengow" action="updateSettings"}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
    
});
//{/block}