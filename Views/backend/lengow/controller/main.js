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

        me.initImportTab();

        me.mainWindow.maximize();
        me.callParent(arguments);
    },

    /**
     * Hide import tab if import setting option is not enabled
     */
    initImportTab: function() {
        Ext.Ajax.request({
            url: '{url controller="LengowImport" action="getImportSettingStatus"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var status = Ext.decode(response.responseText)['data'];
                if (!status) {
                    Ext.getCmp('lengowTabPanel').child('#lengowImportTab').tab.hide();
                }
            }
        });
    }
});
//{/block}