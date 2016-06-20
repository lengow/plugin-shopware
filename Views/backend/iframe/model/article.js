Ext.define('Shopware.apps.Iframe.model.Article', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields: [
        { name: 'id', type: 'int' },
        { name: 'name', type: 'string' },
        { name: 'url', type: 'string' },
        { name: 'email', type: 'string' },
        { name: 'shopId', type: 'int' },
        { name: 'shopName', type: 'string' },
        { name: 'details', type: 'string' }
    ],
    proxy: {
        type: 'ajax',
        api: {
            create: '{url action=createAccount}'
        },
        reader: {
            idProperty: 'id',
            type: 'json',
            root: 'data'
        },
        writer: {
            type: 'json',
            writeAllFields: true
        }
    }
});