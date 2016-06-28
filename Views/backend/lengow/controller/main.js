//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/main"}
Ext.define('Shopware.apps.Lengow.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;

        me.mainWindow = me.getView('main.Window').create({
            exportStore: Ext.create('Shopware.apps.Lengow.store.Article'),
            logStore: Ext.create('Shopware.apps.Lengow.store.Logs')
        }).show();

        me.mainWindow.maximize();
        me.callParent(arguments);
    }
});
//{/block}