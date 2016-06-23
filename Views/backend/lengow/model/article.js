
Ext.define('Shopware.apps.Lengow.model.Article', {
    extend: 'Shopware.data.Model',
    alias:  'model.article',

    // Fields displayed in the grid
    fields: [
        { name : 'id', type: 'int' },
        { name : 'number', type: 'string' },
        { name : 'name', type: 'string' },
        { name : 'supplier', type: 'string' },
        { name : 'status', type: 'boolean' },
        { name : 'price', type: 'float' },
        { name : 'vat', type: 'int' },
        { name : 'inStock', type: 'int' },
        { name : 'activeLengow', type: 'boolean' }
    ]
});

