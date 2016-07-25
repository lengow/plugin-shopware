//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/window"}
Ext.define('Shopware.apps.Lengow.view.main.Window', {
    extend: 'Enlight.app.Window',

    alias: 'widget.lengow-main-window',

    // Window properties
    border: false,
    layout: 'border',

    // Translations
    snippets: {
        title: '{s name="title" namespace="backend/Lengow/translation"}{/s}',
        tab: {
            export: '{s name="window/tab/export" namespace="backend/Lengow/translation"}{/s}',
            import: '{s name="window/tab/import" namespace="backend/Lengow/translation"}{/s}',
            logs: '{s name="window/tab/logs" namespace="backend/Lengow/translation"}{/s}',
            settings: '{s name="window/tab/settings" namespace="backend/Lengow/translation"}{/s}',
            register: '{s name="window/tab/register" namespace="backend/Lengow/translation"}{/s}'
        }
    },

    /**
     * Init main component; set main title and tabs for the window
     */
    initComponent: function() {
        var me = this;

        me.title = me.snippets.title;
        me.items = [
            me.createTabPanel()
        ];

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
                // Export tab
                {
                    title: me.snippets.tab.export,
                    id: 'exportContainer',
                    xtype: 'lengow-export-container',
                    store: me.exportStore,
                    layout: 'border'
                },
                // Import tab
                {
                    title: me.snippets.tab.import,
                    layout: 'border',
                    id: 'lengowImportTab',
                    tabConfig: {
                        listeners: {
                            click: function (tab, e) {
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
                                e.stopEvent(); // avoid switching tab
                            }
                        }
                    }
                },
                // Log tab
                {
                    title: me.snippets.tab.logs,
                    layout: 'border',
                    tabConfig: {
                        listeners: {
                            click: function(tab, e) {
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
                                e.stopEvent(); // avoid switching tab
                            }
                        }
                    }
                },
                // Config tab
                {
                    title: me.snippets.tab.settings,
                    layout: 'border',
                    tabConfig: {
                        listeners: {
                            click: function(tab, e) {
                                Shopware.app.Application.addSubApplication({
                                    name: 'Shopware.apps.Config'
                                });
                                e.stopEvent(); // avoid switching tab
                            }
                        }
                    }
                }
            ]
        });

        return me.tabPanel;
    }
});
//{/block}