//{block name="backend/lengow/model/export_image"}
Ext.define('Shopware.apps.Lengow.model.ExportImage', {

    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @array
     */
    fields: [
        { name: 'id', type: 'int' },
        { name: 'name', type: 'string' }
    ],

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',   
        api: {
            read: '{url controller="Lengow" action="getExportImages"}',
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
    
});
//{/block}