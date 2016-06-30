Ext.define('Shopware.apps.Iframe.store.Article', {
    extend: 'Ext.data.Store',
    model: 'Shopware.apps.Iframe.model.Article',
    autoLoad: false,
    proxy: {
        type: 'ajax',
        url: '{url action=getUrl}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
