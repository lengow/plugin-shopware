//{block name="backend/lengow/model/log"}
Ext.define('Shopware.apps.Lengow.model.Log', {
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
        { name: 'created', type: 'date' },
        { name: 'message', type: 'string' }
    ]
    
});
//{/block}