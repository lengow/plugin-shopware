//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/window"}
Ext.define('Shopware.apps.Lengow.view.main.Window', {
    extend: 'Enlight.app.Window',

    alias: 'widget.product-main-window',

    // Window properties
    border: false,
    layout: 'border',

    // Translations
    snippets: {
        title: '{s name="main.window.title"}{/s}',
        tab: {
            export: '{s name="window/tab/export" namespace="backend/Lengow/translation"}{/s}',
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
            region: 'center',
            items: [
                // Export tab
                {
                    title: me.snippets.tab.export,
                    xtype: 'lengow-export-container',
                    store: me.exportStore,
                    layout: 'border'
                },
                // Log tab
                {
                    title: me.snippets.tab.logs,
                    xtype: 'lengow-logs-container',
                    store: me.logStore,
                    layout: 'border'
                },{
                    tabConfig: {
                        xtype: 'tbfill'
                    }
                },
                {
                    title: me.snippets.tab.register,
                    layout: 'border',
                    tabConfig: {
                        listeners: {
                            click: function(tab, e) {
                                Shopware.app.Application.addSubApplication({
                                    name: 'Shopware.apps.Iframe'
                                });
                                e.stopEvent();
                            }
                        }
                    }
                },
                {
                    title: me.snippets.tab.settings,
                    layout: 'border',
                    tabConfig: {
                        listeners: {
                            click: function(tab, e) {
                                Shopware.app.Application.addSubApplication({
                                    name: 'Shopware.apps.Config'
                                });
                                e.stopEvent();
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