/**
 * Created by nicolasmaugendre on 26/05/16.
 */
Ext.define('Shopware.apps.Lengow.view.main.Window', {
    extend: 'Enlight.app.Window',

    alias: 'widget.product-main-window',

    // Window properties
    border: false,
    autoShow: true,
    layout: 'border',
    tabPanel : null,

    /**
     * Init main component; set main title and tabs for the window
     */
    initComponent: function() {
        var me = this;

        me.title = '{s name="main.window.title"}Lengow{/s} ';
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
            split: true,
            items: [
                // Export tab
                {
                    title: '{s name="main.window.tab.export"}Export products{/s}',
                    xtype: 'lengow-export-container',
                    store: me.exportStore,
                    layout: 'border'
                },
                // Log tab
                {
                    title: '{s name="main.window.tab.log"}Lengow logs{/s}',
                    xtype: 'lengow-logs-panel',
                    store: me.logStore,
                    layout: 'border'
                }
            ]
        });

        return me.tabPanel;
    }
});