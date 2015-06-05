//{block name="backend/lengow/store/status_orders"}
Ext.define('Shopware.apps.Lengow.store.StatusOrders', {

    extend: 'Ext.data.Store',

    remoteFilter: true,
    remoteSort: true, 
    autoLoad: false,
    model: 'Shopware.apps.Lengow.model.StatusOrder',
    pageSize: 40

});
//{/block}