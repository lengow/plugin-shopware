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
        { name: 'carrierMethod', type: 'string' },
        { name: 'orderDate', type: 'date' },
        { name: 'extra', type: 'string' }
    ]
    
});
//{/block}