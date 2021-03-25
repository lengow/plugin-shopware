//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/connection"}
Ext.define('Shopware.apps.Lengow.view.main.Connection', {
    extend: 'Enlight.app.Window',

    alias: 'widget.lengow-main-connection',

    // window properties
    id: 'lengowConnectionWindow',
    autoScroll: true,
    border: false,
    layout: 'fit',

    // translations
    snippets: {
        title: '{s name="title" namespace="backend/Lengow/translation"}Lengow{/s}'
    },

    initComponent: function () {
        var me = this;

        me.title = me.snippets.title;
        me.items = [
            me.createPanel()
        ];
        me.callParent(arguments);
    },

    /**
     * Create connection panel
     * @returns { Ext.panel.Panel }
     */
    createPanel: function() {
        var me = this;

        me.panel = Ext.create('Ext.panel.Panel', {
            id: 'connectionPanel',
            html: me.panelHtml,
            autoScroll: true,
            layout: 'fit',
            bodyStyle: {
                'background-color': '#F7F7F7'
            },
        });
        return me.panel;
    }
});
//{/block}