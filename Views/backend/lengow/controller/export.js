//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/export"}
Ext.define('Shopware.apps.Lengow.controller.Export', {

    extend:'Ext.app.Controller',

    refs: [
        { ref: 'articleGrid', selector: 'lengow-export-grid' }
    ],

    snippets: {
        message: {
            alertExportTitle:   '{s name=export/message/alert_export_title}Export shop product(s)!{/s}',
            alertExport:        '{s name=export/message/alert_export}Please choose a shop to export.{/s}'
        }
    },

    init:function () {
        var me = this;
        me.control({
            'lengow-export-grid': {
                publishProducts: me.onPublishProducts,
                unpublishProducts: me.onPublishProducts, 
                activeProduct: me.onActiveProduct,
                desactiveProduct: me.onActiveProduct,
                saveActiveProduct: me.onSaveActiveProduct,
                exportProducts: me.onExportProducts
            }
        });
        me.callParent(arguments);
    },

    onPublishProducts: function(records, value) {
        var me          = this,
            store       = me.getArticleGrid().getStore(),
            articleGrid = me.getArticleGrid(); 

        if (records.length > 0) {
            articleGrid.setLoading(true);
            for (var i = 0; i < records.length; i++) {
                records[i].set('activeLengow', value);
                records[i].save();
            };
            store.load({
                callback: function() {
                    articleGrid.setLoading(false);
                }
            });
        }
    },

    onActiveProduct: function(record, value) {
        var me          = this,
            store       = me.getArticleGrid().getStore(),
            articleGrid = me.getArticleGrid(); 

        record.set('activeLengow', value);
        record.save();
        store.load(); 
    },

    onSaveActiveProduct: function(editor, event, store) {
        var me          = this,
            store       = me.getArticleGrid().getStore(),
            articleGrid = me.getArticleGrid();

        var record = store.getAt(event.rowIdx);
        if(record == null) {
            return;
        }
        record.save();
        store.load();
    },

    onExportProducts: function(record) {
        var me      = this,
            shop    = record.getValue();

        if (shop) {
            Ext.Ajax.request({
                url: '{url controller="LengowExport" action="export"}',
                method: 'POST',
                params: {},
                success: function(response, opts) {
                    var strJson  = response.responseText;
                    var obj = Ext.JSON.decode(strJson);
                    var url = obj.url;
                    window.open(url + '?shop=' + shop, '_blank');
                }
            });   
        } else {
            Ext.MessageBox.alert(me.snippets.message.alertExportTitle, me.snippets.message.alertExport);
        }
    }

});
//{/block}