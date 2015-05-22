//{block name="backend/lengow/model/payment_method"}
Ext.define('Shopware.apps.Lengow.model.PaymentMethod', {

    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @array
     */
    fields: [
        { name: 'id', type: 'string' },
        { name: 'name', type: 'string' }
    ],

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',   
        api: {
            read: '{url controller="Lengow" action="getPaymentMethods"}',
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
    
});
//{/block}