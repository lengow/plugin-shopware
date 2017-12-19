//{block name="backend/lengow/model/orders"}
Ext.define('Shopware.apps.Lengow.model.Orders', {
    extend: 'Ext.data.Model',
	alias: 'model.orders',
	idProperty: 'id',

	// Fields displayed in the grid
	fields: [
		{ name : 'id', type: 'int' },
		{ name : 'orderId', type: 'string' },
		{ name : 'totalPaid', type: 'float' },
		{ name : 'currency', type: 'string' },
		{ name : 'inError', type: 'int' },
        { name : 'marketplaceSku', type: 'string' },
        { name : 'marketplaceName', type: 'string' },
        { name : 'orderLengowState', type: 'string' },
        { name : 'orderStatus', type: 'string' },
        { name : 'orderStatusDescription', type: 'string' },
        { name : 'orderDate', type: 'string' },
        { name : 'customerName', type: 'string' },
        { name : 'orderItem', type: 'int' },
        { name : 'deliveryCountryIso', type: 'string' },
        { name : 'storeName', type: 'string' },
        { name : 'orderShopwareSku', type: 'string' }
	]
});
//{/block}