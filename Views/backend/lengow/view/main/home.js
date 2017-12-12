//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/home"}
Ext.define('Shopware.apps.Lengow.view.main.Home', {
    extend: 'Enlight.app.Window',

    alias: 'widget.lengow-main-home',

    // Window properties
    id: 'lengowMainWindow',
    border: false,
    layout: 'border',

    // Translations
    snippets: {
        title: '{s name="title" namespace="backend/Lengow/translation"}Lengow{/s}',
        tab: {
            export: '{s name="window/tab/export" namespace="backend/Lengow/translation"}Export{/s}',
            import: '{s name="window/tab/import" namespace="backend/Lengow/translation"}Import{/s}',
            logs: '{s name="window/tab/logs" namespace="backend/Lengow/translation"}Logs{/s}',
            settings: '{s name="window/tab/settings" namespace="backend/Lengow/translation"}Settings{/s}',
            help: '{s name="window/tab/help" namespace="backend/Lengow/translation"}Help{/s}'
        }
    },

    /**
     * Init main component; set main title and tabs for the window
     */
    initComponent: function() {
        var me = this;

        me.title = me.snippets.title;
        me.fireEvent('loadDashboardContent');
        me.fireEvent('initToolbar');
        me.fireEvent('initLegalsTab');
        me.items = [
            me.createTabPanel()
        ];
        me.tbar = me.getToolbar();
        me.callParent(arguments);
    },

    /**
     * Create Lengow's tabs
     * @returns { Ext.tab.Panel } List of tabs used by the module
     */
    createTabPanel: function() {
        var me = this;

        me.tabPanel = Ext.create('Ext.tab.Panel', {
            id: 'lengowTabPanel',
            region: 'center',
            items: [
                // Home tab
                {
                    id: 'lengowDashboardTab',
                    xtype: 'lengow-dashboard-panel',
                    layout: 'border'
                },
                // Export tab
                {
                    title: me.snippets.tab.export,
                    id: 'lengowExportTab',
                    xtype: 'lengow-export-container',
                    store: me.exportStore,
                    layout: 'border'
                },
                // Import tab
                {
                    title: me.snippets.tab.import,
                    layout: 'border',
                    hidden: true,
                    xtype: 'lengow-import-container',
                    importStore: me.importStore,
                    id: 'lengowImportTab'
                },
                // Log tab
                {
                    title: me.snippets.tab.logs,
                    id: 'lengowLogsTab',
                    layout: 'border'
                },
                // Config tab
                {
                    title: me.snippets.tab.settings,
                    layout: 'border',
                    id: 'lengowSettingsTab'
                },
                // Help tab
                {
                    layout: 'border',
                    xtype: 'lengow-help-panel',
                    id: 'lengowHelpTab'
                },
                // Legals tab, hidden
                {
                    layout: 'border',
                    id: 'lengowLegalsTab',
                    style: {
                        background: '#F7F7F7'
                    },
                    overflowY: 'scroll',
                    hidden: true
                }
            ],

            listeners: {
                // Listen to click on tabs
                'beforetabchange': function(tabPanel, tab) {
                    var tabId = tab.id;
                    if (tabId == 'lengowSettingsTab') {
                        // Open Shopware basic settings sub-application
                        Shopware.app.Application.addSubApplication({
                            name: 'Shopware.apps.Config'
                        });
                        return false; // avoid switching tab
                    } else if (tabId == 'lengowLogsTab') {
                        me.showLogsWindowTab();
                        return false; // avoid switching tab
                    }
                },
                'tabchange': function() {
                    me.fireEvent('initLinkListener');
                }
            }
        });

        return me.tabPanel;
    },


    /**
     * Display decrease stocks window
     */
    showImportWindow: function() {
        var me = this;
        Ext.define('LengowImportWindow', {
            id: 'lengowImportWindow',
            modal: true,
            draggable: false,
            resizable: false,
            extend: 'Ext.window.Window',
            title: me.snippets.tab.import,
            items: [{
                xtype: 'lengow-import-panel'
            }]
        });
        me.importWindow = new LengowImportWindow;
        // Issue when opening settings tab and coming back to import tab
        // Needed to reset listeners each time we click on import tab
        me.fireEvent('initImportPanels');
        Ext.getCmp('importButton').on('click', function(){
            me.fireEvent('launchImportProcess');
        });

        me.importWindow.show();
    },

    /**
     * Display logs window
     */
    showLogsWindowTab: function() {
        var me = this;
        Ext.define('LogWindow',{
            id: 'logWindow',
            modal:true,
            draggable: false,
            resizable: false,
            extend:'Ext.window.Window',
            title: me.snippets.tab.logs,
            items: [{
                xtype: 'lengow-logs-panel',
                store: me.logStore
            }]
        });
        var logs = new LogWindow;
        logs.show();
    },

    /**
     * Get main window toolbar to display preprod mod and trial days left
     */
    getToolbar: function() {
        return {
            id: 'lengowMainToolbar',
            height: 28,
            style: {
                background: '#F7F7F7'
            },
            items: [{
                xtype: 'label',
                id: 'lgw-preprod-label'
            },
            {
                xtype: 'tbfill'
            },
            {
                xtype: 'label',
                id: 'lgw-trial-label'
            }]
        };
    }
});
//{/block}