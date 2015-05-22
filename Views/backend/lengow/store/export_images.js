//{block name="backend/lengow/store/export_images"}
Ext.define('Shopware.apps.Lengow.store.ExportImages', {

    extend: 'Ext.data.Store',

    remoteFilter: true,
    remoteSort: true, 
    autoLoad: false,
    model: 'Shopware.apps.Lengow.model.ExportImage',
    pageSize: 40

});
//{/block}