//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/import"}
Ext.define('Shopware.apps.Lengow.controller.Import', {

    extend:'Ext.app.Controller',

    refs: [
        { ref: 'orderGrid', selector: 'lengow-import-grid' },
        { ref: 'importsForm', selector: 'lengow-import-panel' }
    ],

    snippets: {
        message: {
            manualImportTitle:    '{s name=import/message/manual_import_title}Import order(s)?{/s}',
            manualImport:         '{s name=import/message/manual_import}Are you sure you want to import order(s) from marketplaces?{/s}',
            alertImport:          '{s name=import/message/alert_import}Please choose a shop to import.{/s}'
        }
    },

    init:function () {
        var me = this;
        me.control({
            'lengow-import-grid': {
                selectOrder: me.onSelectOrder,
                manualImport: me.onManualImport
            }
        });
        me.callParent(arguments);
    },

    onSelectOrder: function(record) {
        var me = this,
            importsForm = me.getImportsForm();

        if (!(record instanceof Ext.data.Model)) {
            return;
        }

        importsForm.loadRecord(record);

    },

    onManualImport: function(record) {
        var me      = this,
            store   = me.getOrderGrid().getStore(),
            shop    = record.getValue();

        if(shop) {
            Ext.MessageBox.confirm(me.snippets.message.manualImportTitle, me.snippets.message.manualImport, function (response) {
                if ( response !== 'yes' ) {
                    return;
                }
                Ext.Ajax.request({
                    url: '{url controller="LengowImport" action="import"}',
                    method: 'POST',
                    params: {},
                    success: function(response, opts) {
                        var strJson  = response.responseText;
                        var obj = Ext.JSON.decode(strJson);
                        var url = obj.url;
                        window.open(url + '?shop=' + shop, '_blank');
                    }
                });
            });
        } else {
            Ext.MessageBox.alert(me.snippets.message.manualImportTitle, me.snippets.message.alertImport);
        }
    }

});
//{/block}