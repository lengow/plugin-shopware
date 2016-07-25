//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/import"}
Ext.define('Shopware.apps.Lengow.controller.Import', {
    extend: 'Enlight.app.Controller',

    init: function () {
        var me = this;

        me.control({
            'lengow-main-window': {
                initImportPanels: me.onInitImportPanels,
                launchImportProcess: me.onLaunchImportProcess
            }
        });

        me.callParent(arguments);
    },

    /**
     * Init/update import window labels (description and last synchronization date)
     */
    onInitImportPanels: function () {
        Ext.Ajax.request({
            url: '{url controller="LengowImport" action="getPanelContents"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var data = Ext.decode(response.responseText)['data'];
                Ext.getCmp('importDescriptionPanel').update(data['importDescription']);
                Ext.getCmp('lastImportPanel').update(data['lastImport']);
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
                // If no main error, display number of new orders/orders in error
                if (success) {
                    var order_new = data['order_new'],
                        order_error = data['order_error'];
                    statusLabel.update(order_new + '<br/>' + order_error);
                } else {
                    // Display main error message and a link to the log
                    statusLabel.update(data['error']);
                }
                // Update last synchronization date
                me.onInitImportPanels();
                // Hide waiting message
                Ext.MessageBox.hide();
            }
        });
    }
});
// {/block}