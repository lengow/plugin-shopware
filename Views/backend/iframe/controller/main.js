Ext.define('Shopware.apps.Iframe.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function () {
        var me = this;
        me.showWindow();
    },

    showWindow: function () {
        var me = this;
        me.accountStore = me.getStore('Article');
        me.mainWindow = me.getView('Main').create({
            accountStore: me.accountStore
        });
        me.mainWindow.show();
        me.accountStore.load({
            callback: function(records, op, success) {
                me.mainWindow.setLoading(false);
                me.mainWindow.initAccountTabs();
            }
        });
    },
});