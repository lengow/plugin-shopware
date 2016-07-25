Ext.define('Shopware.apps.Iframe.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function () {
        var me = this;
        me.showWindow();
    },

    /**
     * Display iframe
     */
    showWindow: function () {
        var me = this;
        me.mainWindow = me.getView('Main').create();
        me.mainWindow.maximize();
        me.mainWindow.show();
    }
});