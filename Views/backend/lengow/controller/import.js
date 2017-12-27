//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/import"}
Ext.define('Shopware.apps.Lengow.controller.Import', {
    extend: 'Enlight.app.Controller',

    init: function () {
        var me = this;

        me.control({
            'lengow-main-home': {
                initImportPanels: me.onInitImportPanels,
                launchImportProcess: me.onLaunchImportProcess
            },
            'order-listing-grid': {
                showDetail: me.onShowDetail
            }
        });

        me.callParent(arguments);
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