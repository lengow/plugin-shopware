
Ext.define('Shopware.apps.Lengow.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;

        me.mainWindow = me.getView('main.Window').create({
            store: Ext.create('Shopware.apps.Lengow.store.Article').load()
        }).show();
        me.mainWindow.maximize();
        me.callParent(arguments);
    }
});