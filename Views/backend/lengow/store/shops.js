//{block name="backend/lengow/store/shops"}
Ext.define('Shopware.apps.Lengow.store.Shops', {
    extend:'Ext.data.TreeStore',
    alias: 'store.lengow-shops',

    configure: function() {
        return { controller: 'LengowExport' };
    },

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller="LengowExport" action="getShopsTree"}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}