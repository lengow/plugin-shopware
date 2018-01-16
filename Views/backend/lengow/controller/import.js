//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/import"}
Ext.define('Shopware.apps.Lengow.controller.Import', {
    extend: 'Enlight.app.Controller',

    // Translations
    snippets: {
        order_error: '{s name="order/panel/order_error" namespace="backend/Lengow/translation"}{/s}',
        last_import: '{s name="order/panel/last_import" namespace="backend/Lengow/translation"}{/s}',
        to_be_sent: '{s name="order/panel/to_be_sent" namespace="backend/Lengow/translation"}{/s}',
        ok: '{s name="order/panel/ok" namespace="backend/Lengow/translation"}{/s}',
        synchronisation_report: '{s name="order/panel/synchronisation_report" namespace="backend/Lengow/translation"}{/s}',
        success_message: '{s name="order/details/success_message" namespace="backend/Lengow/translation"}{/s}',
        fail_message: '{s name="order/details/fail_message" namespace="backend/Lengow/translation"}{/s}',
        ship_confirmation_title: '{s name="order/details/ship_confirmation_title" namespace="backend/Lengow/translation"}{/s}',
        cancel_confirmation_title: '{s name="order/details/cancel_confirmation_title" namespace="backend/Lengow/translation"}{/s}'
    },

    init: function () {
        var me = this;

        me.control({
            'order-listing-grid': {
                showDetail: me.onShowDetail,
                reSendActionGrid: me.reSendActionGrid
            },
            'lengow-import-container': {
                launchImportProcess: me.onLaunchImportProcess,
                initImportPanels: me.onInitImportPanels
            }
        });

        me.callParent(arguments);
    },

    reSendActionGrid: function (id, type, lastActionType) {
        var me = this;
        if (type == 're_send') {
            url = '{url controller=LengowImport action=reSendAction}';
        } else {
            url = '{url controller=LengowImport action=reImportAction}';
        }
        var loading = new Ext.LoadMask(Ext.getBody(), {
            hideModal: true
        });
        loading.show();
        Ext.Ajax.request({
            url: url,
            method: 'POST',
            type: 'json',
            params: {
                orderId: id,
                actionName: lastActionType
            },
            success: function (response) {
                var grid = Ext.getCmp('importGrid');
                loading.hide();
                grid.getStore().load();
                grid.getView().refresh();
                me.onInitImportPanels();
            }
        });
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
                    '<p>' + data['mail_report'] + '</p>'
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
                    grid = Ext.getCmp('importGrid');
                // Update last synchronization date
                me.onInitImportPanels();
                // Hide waiting message
                Ext.MessageBox.hide();
                grid.getStore().load();
                grid.getView().refresh();
                Ext.MessageBox.show({
                    title: me.snippets.synchronisation_report,
                    msg: data.messages,
                    width: 600,
                    buttons: Ext.Msg.YES,
                    buttonText :
                    {
                        yes : me.snippets.ok
                    }
                });
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
            params: {
                orderId: record.get('orderId')
            }
        });
    }
});
// {/block}