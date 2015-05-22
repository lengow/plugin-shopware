//{block name="backend/lengow/store/image_formats"}
Ext.define('Shopware.apps.Lengow.store.ImageFormats', {

    extend: 'Ext.data.Store',

    remoteFilter: true,
    remoteSort: true, 
    autoLoad: false,
    model: 'Shopware.apps.Lengow.model.ImageFormat',
    pageSize: 40

});
//{/block}