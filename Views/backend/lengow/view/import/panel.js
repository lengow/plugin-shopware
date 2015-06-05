//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/panel"}
Ext.define('Shopware.apps.Lengow.view.import.Panel', {

    extend: 'Ext.form.Panel',

    alias: 'widget.lengow-import-panel',

    snippets: {
        title:                  '{s name=import/panel/panel_title}Additional information{/s}',
        fieldsetTitle:          '{s name=import/panel/fieldset_title}Order\'s information{/s}',
        orderDateMarketplace:   '{s name=import/panel/order_date_marketplacet}Order date on marketplace{/s}',
        carrierMarketplace:     '{s name=import/panel/carrier_marketplace}Carrier on marketplace{/s}',
        trackingNumber:         '{s name=import/panel/tracking_number}Tracking number{/s}',
        orderJson:              '{s name=import/panel/order_json}Order JSON{/s}',
        emptyText:              '{s name=import/panel/empty_text}No information for this order{/s}'
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

        me.orderDateMarketplaceDate = Ext.create('Ext.form.field.Date', {
            name: 'orderDateLengow',
            fieldLabel: me.snippets.orderDateMarketplace,
            anchor: '100%',
            emptyText: me.snippets.emptyText,
            labelWidth: 170,
            format: 'd/m/Y H:i:s'
        });

        me.carrierMarketplaceText = Ext.create('Ext.form.field.Text', {
            name: 'carrier',
            fieldLabel: me.snippets.carrierMarketplace,
            anchor: '100%',
            emptyText: me.snippets.emptyText,
            labelWidth: 170
        });

        me.trackingNumberText = Ext.create('Ext.form.field.Text', {
            name: 'trackingNumber',
            fieldLabel: me.snippets.trackingNumber,
            anchor: '100%',
            emptyText: me.snippets.emptyText,
            labelWidth: 170
        });

        me.extraTextArea = Ext.create('Ext.form.field.TextArea', {
            name: 'extra',
            fieldLabel: me.snippets.orderJson,
            anchor: '100%',
            grow: false,
            height: 170,
            labelWidth: 170,
            emptyText: me.snippets.emptyText
        });

        return Ext.create('Ext.form.FieldSet', {
            title: me.snippets.fieldsetTitle,
            flex: 1,
            items: [
                me.orderDateMarketplaceDate,
                me.carrierMarketplaceText,
                me.trackingNumberText,
                me.extraTextArea
            ]
        });
    },

    /**
     * Column renderer function for the created column
     * @param [string] value    - The field value
     * @param [string] metaData - The model meta data
     * @param [string] record   - The whole data model
     */
    modifiedCreated: function(value, metaData, record) {
       return Ext.util.Format.date(record.get('orderDate')) + ' ' + Ext.util.Format.date(record.get('orderDate'), 'H:i:s');
    }
   
});
//{/block}