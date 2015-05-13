//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/window"}
Ext.define('Shopware.apps.Lengow.view.main.Window', {

    extend:'Enlight.app.Window',

    title: '{s name=window/title_window}Lengow{/s}',

    alias:'widget.lengow-main-window',

    border: false,
    autoShow: true,
    layout: 'fit',
    height: '90%',
    width: 1200,

    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        tab: {
            exports:    '{s name=window/tab/exports}Products export{/s}',
            imports:    '{s name=window/tab/imports}Lengow\'s orders{/s}',
            logs:       '{s name=window/tab/logs}Logs{/s}'
        }
    },

    /**
     * Initializes the component and builds up the main interface
     *
     * @return void
     */
    initComponent: function() {
        var me = this;
        me.items = me.createTabPanel();
        me.callParent(arguments);
    },

    /**
     * Creates the tab panel which holds off the different areas of the lengow module.
     * @returns { Ext.tab.Panel } the tab panel which contains the different areas
     */
    createTabPanel: function() {
        var me = this;
        me.tabPanel = Ext.create('Ext.tab.Panel', {
            items: [ 
                {
                    title: me.snippets.tab.exports,
                    xtype: 'lengow-export-exports',
                    region: 'center',
                    articlesStore: me.articlesStore,
                    layout: 'border'
                },{
                    title: me.snippets.tab.imports,
                    xtype: 'lengow-import-imports',
                    region: 'center',
                    ordersStore: me.ordersStore,
                    layout: 'border'
                },
                me.createLogsTab() 
            ]
        });

        return me.tabPanel;
    },

    /**
     * Creates a container "imports"
     * The container will be used as the "imports" tab.
     * @returns { Ext.container.Container }
     */
    // createImportsTab: function() {
    //     var me = this;
    //     me.importsContainer = Ext.create('Ext.container.Container', {
    //         layout: 'border',
    //         title: me.snippets.tab.imports,
    //         items: {
    //             xtype: 'lengow-main-imports',
    //             region: 'center',
    //             ordersStore: me.ordersStore
    //         }
    //     });
    //     return me.importsContainer;
    // },

    /**
     * Creates a container "logs"
     * The container will be used as the "logs" tab.
     * @returns { Ext.container.Container }
     */
    createLogsTab: function() {
        var me = this;
        me.logsContainer = Ext.create('Ext.container.Container', {
            layout: 'border',
            title: me.snippets.tab.logs,
            items: {
                xtype: 'lengow-main-logs',
                region: 'center', 
                logsStore: me.logsStore
            }
        });
        return me.logsContainer;
    }

});
//{/block}
