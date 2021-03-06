//{block name="backend/lengow/model/article"}
Ext.define('Shopware.apps.Lengow.model.Article', {
    extend: 'Ext.data.Model',
    alias:  'model.article',
    idProperty: 'id',

    // fields displayed in the grid
    fields: [
        { name : 'id', type: 'int' },
        { name : 'number', type: 'string' },
        { name : 'name', type: 'string' },
        { name : 'supplier', type: 'string' },
        { name : 'status', type: 'boolean' },
        { name : 'price', type: 'float' },
        { name : 'vat', type: 'string' },
        { name : 'inStock', type: 'int' },
        { name : 'lengowActive', type: 'boolean', componentCls: 'sword'}
    ]
});
//{/block}