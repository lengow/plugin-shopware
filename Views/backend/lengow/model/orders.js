//{block name="backend/lengow/model/orders"}
Ext.define('Shopware.apps.Lengow.model.Orders', {
    extend: 'Ext.data.Model',
	alias: 'model.orders',
	idProperty: 'id',

	// fields displayed in the grid
	fields: [
        { name : 'id', type: 'int' },
        { name : 'orderId', type: 'string' },
        { name : 'orderSku', type: 'string' },
        { name : 'deliveryAddressId', type: 'int' },
        { name : 'deliveryCountryIso', type: 'string' },
        { name : 'marketplaceSku', type: 'string' },
        { name : 'marketplaceLabel', type: 'string' },
        { name : 'orderLengowState', type: 'string' },
        { name : 'orderProcessState', type: 'int' },
        { name : 'orderDate', type: 'datetime' },
        { name : 'orderItem', type: 'int' },
        { name : 'currency', type: 'string' },
        { name : 'totalPaid', type: 'float' },
        { name : 'commission', type: 'float' },
        { name : 'customerName', type: 'string' },
        { name : 'customerEmail', type: 'string' },
        { name : 'carrier', type: 'string' },
        { name : 'carrierMethod', type: 'string' },
        { name : 'carrierTracking', type: 'string' },
        { name : 'carrierIdRelay', type: 'string' },
        { name : 'sentByMarketplace', type: 'bool' },
        { name : 'inError', type: 'bool' },
        { name : 'message', type: 'string' },
        { name : 'createdAt', type: 'datetime' },
        { name : 'extra', type: 'string' },
        { name : 'orderStatus', type: 'string' },
        { name : 'orderStatusDescription', type: 'string' },
        { name : 'storeName', type: 'string' },
        { name : 'orderShopwareSku', type: 'string' },
        { name : 'errorMessage', type: 'string' },
        { name : 'countryIso', type: 'string' },
        { name : 'countryName', type: 'string' },
        { name : 'lastActionType', type: 'string' }
	]
});
//{/block}