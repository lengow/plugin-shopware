
Ext.define('Shopware.apps.Lengow.store.Article', {
    extend:'Shopware.store.Listing',
    alias:  'store.article-store',
    model: 'Shopware.apps.Lengow.model.Article',

    // List articles when the window is displayed
    autoLoad: true,

    configure: function() {
        return { controller: 'Lengow' };
    },

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller="Lengow" action="getList"}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    },
});