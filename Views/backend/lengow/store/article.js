//{block name="backend/lengow/store/article"}
Ext.define('Shopware.apps.Lengow.store.Article', {
    extend:'Ext.data.Store',
    alias: 'store.article-store',
    model: 'Shopware.apps.Lengow.model.Article',

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
            read: '{url controller="LengowExport" action="getList"}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}