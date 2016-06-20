Ext.define('Shopware.apps.Iframe.store.Article', {
    extend: 'Ext.data.Store',
    model: 'Shopware.apps.Iframe.model.Article',
    autoLoad: false,
    proxy: {
        type: 'ajax',
        url: '{url action=getAccounts}',
        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        }
    }
});
