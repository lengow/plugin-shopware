//{block name="backend/lengow/store/orders"}
Ext.define('Shopware.apps.Lengow.store.Orders', {
    extend:'Ext.data.Store',
    alias: 'store.lengow-orders',
    model: 'Shopware.apps.Lengow.model.Orders',

    // Define how much rows loaded with one request
    pageSize: 40,

    /**
     * Enable remote filtering
     */
    remoteFilter: true,

    /**
     * Enable remote sorting
     */
    remoteSort: true,

    /**
     * Auto load the store after the component is initialized
     * @boolean
     */
    autoLoad: true,

    configure: function() {
        return { controller: 'LengowImport' };
    },

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller="LengowImport" action="getList"}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}