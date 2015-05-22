//{block name="backend/lengow/model/image_format"}
Ext.define('Shopware.apps.Lengow.model.ImageFormat', {

    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @array
     */
    fields: [
        { name: 'id', type: 'string' },
        { name: 'name', type: 'string' }
    ],

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',   
        api: {
            read: '{url controller="Lengow" action="getImageFormats"}',
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
    
});
//{/block}