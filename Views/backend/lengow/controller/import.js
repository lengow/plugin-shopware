//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/import"}
Ext.define('Shopware.apps.Lengow.controller.Import', {
    extend: 'Enlight.app.Controller',

    // Translations
    snippets: {
        order_error: '{s name="order/panel/order_error" namespace="backend/Lengow/translation"}{/s}',
        last_import: '{s name="order/panel/last_import" namespace="backend/Lengow/translation"}{/s}',
        to_be_sent: '{s name="order/panel/to_be_sent" namespace="backend/Lengow/translation"}{/s}',
        mail_report: '{s name="order/panel/mail_report" namespace="backend/Lengow/translation"}{/s}'
    },

    init: function () {
        var me = this;

        me.control({
            'order-listing-grid': {
                showDetail: me.onShowDetail
            },
            'lengow-import-container': {
                launchImportProcess: me.onLaunchImportProcess,
                initImportPanels: me.onInitImportPanels
            }
        });

        me.callParent(arguments);
    },

    /**
     * Init/update import window labels (description and last synchronization date)
     */
    onInitImportPanels: function () {
        var me = this;
        Ext.Ajax.request({
            url: '{url controller="LengowImport" action="getPanelContents"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var data = Ext.decode(response.responseText)['data'];
                Ext.getCmp('nb_order_in_error').update(
                    '<p>' + Ext.String.format(me.snippets.order_error, data['nb_order_in_error']) + '</p>'
                );
                Ext.getCmp('nb_order_to_be_sent').update(
                    '<p>' + Ext.String.format(me.snippets.to_be_sent,  data['nb_order_to_be_sent']) + '</p>'
                );
                Ext.getCmp('last_import').update(
                    '<p>' + Ext.String.format(me.snippets.last_import, data['last_import']) + '</p>'
                );
                Ext.getCmp('mail_report').update(
                    '<p>' + Ext.String.format(me.snippets.mail_report, data['mail_report']) + '</p>'
                );
            }
        });
    },

    /**
     * Start import listener
     */
    onLaunchImportProcess: function () {
        var me = this;
        // Display waiting message
       Ext.MessageBox.show({
            msg: '{s name="order/screen/import_charge_second" namespace="backend/Lengow/translation"}{/s}',
            width:300,
            wait:true
        });

        Ext.Ajax.request({
            url: '{url controller="LengowImport" action="launchImportProcess"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var result = Ext.decode(response.responseText),
                    success = result['success'],
                    data = result['data'],
                    statusLabel = Ext.getCmp('importStatusPanel');
                statusLabel.update(data.messages);
                // Update last synchronization date
                me.onInitImportPanels();
                // Hide waiting message
                Ext.MessageBox.hide();
            }
        });
    },

    /**
     * Event listener method which fired when the user clicks the pencil button
     * in the order list to show the order detail page.
     * @param record
     */
    onShowDetail: function(record) {
        Shopware.app.Application.addSubApplication({
            name: 'Shopware.apps.Order',
            action: 'showOrder',
            params: {
                orderId: record.get('id')
            }
        });
    }
});
// {/block}