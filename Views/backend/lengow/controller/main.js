//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/main"}
Ext.define('Shopware.apps.Lengow.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;
        me.displayMainWindow();
        me.callParent(arguments);
    },

    /**
     * Display window when plugin is launched
     * If user has no account id, show login iframe instead of the plugin
     */
    displayMainWindow: function() {
        var me = this;
        Ext.Ajax.request({
            url: '{url controller="Lengow" action="getIsNewMerchant"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var data = Ext.decode(response.responseText)['data'];
                // If not a new merchant, display Lengow plugin
                if (!data['isSync']) {
                    me.mainWindow = me.getView('main.Home').create({
                        exportStore: Ext.create('Shopware.apps.Lengow.store.Article'),
                        logStore: Ext.create('Shopware.apps.Lengow.store.Logs')
                    }).show();

                    me.initImportTab();
                } else {
                    // Display sync iframe
                    me.mainWindow = me.getView('main.Sync').create({
                        panelHtml: data['panelHtml'],
                        isSync: data['isSync'],
                        syncLink: false
                    });
                    me.mainWindow.initFrame();
                }
                // Show main window
                me.mainWindow.show();
                me.mainWindow.maximize();
            }
        });
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