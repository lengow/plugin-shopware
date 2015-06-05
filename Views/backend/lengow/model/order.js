//{block name="backend/lengow/model/order"}
Ext.define('Shopware.apps.Lengow.model.Order', {
    /**
     * Extends the standard Ext Model
     * @string
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @array
     */
    fields: [
        { name: 'id', type: 'int' },
        { name: 'idOrderLengow', type: 'string' },
        { name: 'idFlux', type: 'int' },
        { name: 'marketplace', type: 'string' },
        { name: 'totalPaid', type: 'float' },
        { name: 'carrier', type: 'string' },
        { name: 'trackingNumber', type: 'string' },
        { name: 'orderDateLengow', type: 'date'},
        { name: 'extra', type: 'string' },
        { name: 'orderId', type: 'int' },
        { name: 'orderDate', type: 'date' },
        { name: 'orderNumber', type: 'string' },
        { name: 'invoiceAmount', type: 'float' },
        { name: 'nameShop', type: 'string' },
        { name: 'shipping', type: 'string' },
        { name: 'status', type: 'string' },
        { name: 'nameCustomer', type: 'string' }
    ],

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',   
        api: {
            read: '{url controller="LengowImport" action="getList"}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
    
});
//{/block}