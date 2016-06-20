
Ext.define('Shopware.apps.Lengow.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;

        me.mainWindow = me.getView('main.Window').create({ }).show();
        me.mainWindow.maximize();
        me.callParent(arguments);
    }
});