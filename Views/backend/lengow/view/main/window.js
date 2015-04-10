//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/window"}
Ext.define('Shopware.apps.Lengow.view.main.Window', {
    /**
     * Define that the order main window is an extension of the enlight application window
     * @string
     */
    extend:'Enlight.app.Window',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias:'widget.lengow-main-window',

    // border:false,
    // autoShow:true,
    // maximizable:true,
    // minimizable:true,
    
    // width: 800,
    // height: 600,

    overflowY: 'scroll',

    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        title: '{s name=window/title}Lengow{/s}',
        tab: {
            exports: '{s name=window/tab/exports}Products export{/s}',
            logs: '{s name=window/tab/logs}Logs{/s}'
        }
    },

    /**
     * @return void
     */
    initComponent: function () {
        var me = this;
        me.title = me.snippets.title;

        var tabPanel = Ext.create('Ext.tab.Panel', {
            plain: false,
            items : [
                {
                    title: me.snippets.tab.exports,
                    xtype: 'lengow-main-exports'
                },
                {
                    title: me.snippets.tab.logs,
                    xtype: 'lengow-main-logs'
                },
                {
                    tabConfig: {
                        xtype: 'tbfill'
                    }
                }
            ]
        });

        me.formPanel = Ext.create('Ext.form.Panel', {
            name: 'lengow-form-panel',
            items: [tabPanel],
            border: false
        });

        me.items = [me.formPanel];

        me.callParent(arguments);
    }

});
//{/block}