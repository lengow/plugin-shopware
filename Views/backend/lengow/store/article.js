
Ext.define('Shopware.apps.Lengow.store.Article', {
    extend:'Shopware.store.Listing',
    alias:  'store.article-store',
    model: 'Shopware.apps.Lengow.model.Article',

    // List articles when the window is displayed
    autoLoad: true,

     // Define how much rows loaded with one request
    pageSize: 40,

    defaultRootId: 1,

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
            read: '{url controller="LengowExport" action="getList"}',
            update:  '{url controller="LengowExport" action="update"}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    },
});