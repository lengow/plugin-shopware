//{block name="backend/lengow/model/article"}
Ext.define('Shopware.apps.Lengow.model.Article', {
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
        { name: 'articleId', type: 'int' },
        { name: 'number',   type: 'string' },
        { name: 'name',     type: 'string' },
        { name: 'supplier', type: 'string' },
        { name: 'tax',      type: 'string' },
        { name: 'price',    type: 'string' },
        { name: 'active',   type: 'boolean' },
        { name: 'inStock',  type: 'int' },
        { name: 'activeLengow',  type: 'boolean' },
        { name: 'hasCategories',    type: 'boolean' }
    ],

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',   
        api: {
            read: '{url controller="LengowExport" action="getList"}',
            update:  '{url controller="LengowExport" action="update"}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
    
});
//{/block}