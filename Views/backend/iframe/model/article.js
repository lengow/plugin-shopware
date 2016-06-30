Ext.define('Shopware.apps.Iframe.model.Article', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields: [
        { name: 'name', type: 'string' },
        { name: 'url', type: 'string' }
    ]
});