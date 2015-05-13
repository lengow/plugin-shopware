//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/imports"}
Ext.define('Shopware.apps.Lengow.view.import.Imports', {

    extend: 'Ext.container.Container',

    alias: 'widget.lengow-import-imports',

    initComponent: function() {
        var me = this;

        me.items = [{
            xtype: 'lengow-import-grid',
            ordersStore: me.ordersStore,
            region: 'center'
        }];

        me.sidebarPanel = Ext.create('Ext.panel.Panel', {
            title: 'Additional information',
            collapsed: true,
            collapsible: true,
            flex: 1,
            height: 300,
            layout: {
                type: 'vbox',
                pack: 'start',
                align: 'stretch'
            },
            region: 'south',
            bodyStyle: 'background:#fff;',
            items: [
                me.createPanel()
            ]
        });

        me.items.push(me.sidebarPanel);

        me.callParent(arguments);
    },

    createPanel: function() {
        var me = this;

        me.extraField = Ext.create('Ext.form.field.TextArea', {
            name: 'extra',
            anchor: '100%',
            grow: false,
            height: 300
        });

        return Ext.create('Ext.form.Panel', {
            title: 'The order information in JSON',
            bodyPadding: 5,
            items: [
                me.extraField
            ]
        });
    }
   
});
//{/block}