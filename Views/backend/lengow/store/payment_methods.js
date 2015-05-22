//{block name="backend/lengow/store/payment_methods"}
Ext.define('Shopware.apps.Lengow.store.PaymentMethods', {

    extend: 'Ext.data.Store',

    remoteFilter: true,
    remoteSort: true, 
    autoLoad: false,
    model: 'Shopware.apps.Lengow.model.PaymentMethod',
    pageSize: 40

});
//{/block}