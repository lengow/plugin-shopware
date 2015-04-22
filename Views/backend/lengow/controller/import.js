//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/controller/import"}
Ext.define('Shopware.apps.Lengow.controller.Import', {

    extend:'Ext.app.Controller',

    refs: [
        { ref: 'orderGrid', selector: 'lengow-main-imports' }
    ],

    snippets: {
        message: {
            manualImportTitle:    '{s name=import/message/manual_import_title}Import order(s)?{/s}',
            manualImport:         '{s name=import/message/manual_import}Are you sure you want to import order(s) from marketplaces?{/s}'
        }
    },

    init:function () {
        var me = this;
        me.control({
            'lengow-main-imports': {
                manualImport: me.onManualImport
            }
        });
        me.callParent(arguments);
    },

    onManualImport: function() {
        console.log('Manual Import');

        var me          = this,
            store       = me.getOrderGrid().getStore(),
            orderGrid = me.getOrderGrid();

        Ext.MessageBox.confirm(me.snippets.message.manualImportTitle, me.snippets.message.manualImport, function (response) {
            if ( response !== 'yes' ) {
                return;
            }
            orderGrid.setLoading(true);
            Ext.Ajax.request({
                url: '{url controller="LengowImport" action="import"}',
                method: 'POST',
                params: {},
                success: function(response, opts) {
                    store.load({
                        callback: function() {
                            orderGrid.setLoading(false);
                        }
                    }); 
                }
            });
        });
    }

});
//{/block}