//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/window"}
Ext.define('Shopware.apps.Lengow.view.main.Window', {

    extend:'Enlight.app.Window',

    title: '{s name=main/window/title_window}Lengow{/s}',

    alias:'widget.lengow-main-window',

    border: false,
    autoShow: true,
    layout: 'border',
    height: '90%',
    width: 1200,
    tabPanel : null,

    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        tab: {
            exports:    '{s name=main/window/tab/exports}Products export{/s}',
            imports:    '{s name=main/window/tab/imports}Lengow\'s orders{/s}',
            logs:       '{s name=main/window/tab/logs}Logs{/s}'
        }
    },

    /**
     * Initializes the component and builds up the main interface
     *
     * @return void
     */
    initComponent: function() {
        var me = this;

        me.items = [
            me.createTabPanel()
        ];

        me.callParent(arguments);
    },

    /**
     * Creates the tab panel which holds off the different areas of the lengow module.
     * @returns { Ext.tab.Panel } the tab panel which contains the different areas
     */
    createTabPanel: function() {
        var me = this;
        me.tabPanel = Ext.create('Ext.tab.Panel', {
            region: 'center',
            split: true,
            items: [ 
                {
                    title: me.snippets.tab.exports,
                    xtype: 'lengow-export-exports',
                    articlesStore: me.articlesStore,
                    layout: 'border'
                },{
                    title: me.snippets.tab.imports,
                    xtype: 'lengow-import-imports',
                    ordersStore: me.ordersStore,
                    layout: 'border'
                },{
                    title: me.snippets.tab.logs,
                    xtype: 'lengow-main-logs',
                    logsStore: me.logsStore
                },{
                    tabConfig: {
                        xtype: 'tbfill'
                    }
                },{
                    title: 'Settings',
                    xtype: 'lengow-main-settings' 
                }
            ]
        });
        return me.tabPanel;
    }

});
//{/block}
