//{block name="backend/lengow/store/export_formats"}
Ext.define('Shopware.apps.Lengow.store.ExportFormats', {

    extend: 'Ext.data.Store',

    remoteFilter: true,
    remoteSort: true, 
    autoLoad: false,
    model: 'Shopware.apps.Lengow.model.ExportFormat',
    pageSize: 40

});
//{/block}