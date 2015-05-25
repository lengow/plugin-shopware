//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/panel"}
Ext.define('Shopware.apps.Lengow.view.import.Panel', {

    extend: 'Ext.form.Panel',

    alias: 'widget.lengow-import-panel',

    snippets: {
        title:          '{s name=import/panel/panel_title}Additional information{/s}',
        fieldsetTitle:  '{s name=import/panel/fieldset_title}Order\'s information{/s}',
        orderJson:      '{s name=import/panel/order_json}Order JSON{/s}'
    },

    collapsed: true,
    collapsible: true,
    flex: 1,
    height: 300,
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    bodyPadding: 5,
    bodyStyle: 'background:#fff;',

    initComponent: function() {
        var me = this;

        me.title = me.snippets.title;
        me.items = [
            me.createItems()
        ];

        me.callParent(arguments);
    },
    
    createItems: function() {
        var me = this;

        me.extraField = Ext.create('Ext.form.field.TextArea', {
            name: 'extra',
            fieldLabel: me.snippets.orderJson,
            anchor: '100%',
            grow: false,
            height: 250
        });

        return Ext.create('Ext.form.FieldSet', {
            title: me.snippets.fieldsetTitle,
            flex: 1,
            items: [
                me.extraField
            ]
        });
    }
   
});
//{/block}