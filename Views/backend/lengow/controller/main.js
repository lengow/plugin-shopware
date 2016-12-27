//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/main"}
Ext.define('Shopware.apps.Lengow.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;
        me.displayMainWindow();
        me.callParent(arguments);

        me.control({
            'lengow-main-home': {
                initToolbar: me.onInitToolbar,
                initLinkListener: me.onInitLinkListener,
                initLegalsTab: me.onInitLegalsTab
            }
        });
    },

    /**
     * Display window when plugin is launched
     * If user has no account id, show login iframe instead of the plugin
     */
    displayMainWindow: function() {
        var me = this;
        Ext.Ajax.request({
            url: '{url controller="Lengow" action="getSyncIframe"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var data = Ext.decode(response.responseText)['data'];
                // If not a new merchant, display Lengow plugin
                if (!data['isNewMerchant']) {
                    me.mainWindow = me.getView('main.Home').create({
                        exportStore: Ext.create('Shopware.apps.Lengow.store.Article'),
                        logStore: Ext.create('Shopware.apps.Lengow.store.Logs')
                    }).show();

                    me.initImportTab();
                } else {
                    // Display sync iframe
                    me.mainWindow = me.getView('main.Sync').create({
                        panelHtml: data['panelHtml'],
                        isSync: false,
                        syncLink: false,
                        langIsoCode: data['langIsoCode']
                    }).show();
                    me.mainWindow.initFrame();
                }
                // Show main window
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
                if (status) {
                    Ext.getCmp('lengowTabPanel').child('#lengowImportTab').tab.show();
                }
            }
        });
    },

    /**
     * Get preprod/trial translations and html before updating concerned labels created in the toolbar
     */
    onInitToolbar: function() {
        Ext.Ajax.request({
            url: '{url controller="Lengow" action="getToolbarContent"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var data = Ext.decode(response.responseText)['data'],
                    count = Object.keys(data).length;
                if (count > 0) {
                    Ext.iterate(data, function (selector, htmlContent) {
                        Ext.getCmp(selector).update(htmlContent);
                    });
                } else {
                    var toolbar = Ext.getCmp('lengowMainToolbar');
                    if (toolbar !== 'undefined') {
                        // Hide tabbar if nothing to show
                        toolbar.hide();
                    }
                }
            }
        });
    },

    /**
     * Listen to Lengow links and display concerned tab
     * (legals, export/settings blocks on the dashboard, help link, ...)
     */
    onInitLinkListener: function() {
        // Get Lengow links (products & settings dashboard boxes, help link, ...)
        var tabShortcuts = Ext.query("a[id^=lengow][id$=Tab]");
        // For each one, listen on click and trigger concerned tab
        Ext.each(tabShortcuts, function(item) {
            item.onclick = function() {
                var tabEl = Ext.getCmp(item.id); // Get tab reference
                Ext.getCmp('lengowTabPanel').setActiveTab(tabEl);
            };
        });
    },

    /**
     * Load legals tab content
     */
    onInitLegalsTab: function() {
        Ext.Ajax.request({
            url: '{url controller="Lengow" action="getLegalsTabContent"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var html = Ext.decode(response.responseText)['data'];
                Ext.getCmp('lengowLegalsTab').update(html);
            }
        });
    }
});
//{/block}