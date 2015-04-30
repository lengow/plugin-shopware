//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/controller/export"}
Ext.define('Shopware.apps.Lengow.controller.Export', {

    extend:'Ext.app.Controller',

    refs: [
        { ref: 'articleGrid', selector: 'lengow-export-grid' }
    ],

    snippets: {
        message: {
            exportProductsTitleFirst:   '{s name=export/message/export_products_title_first}Export the {/s}',
            exportProductsTitleEnd:     '{s name=export/message/export_products_title_end} shop?{/s}',
            exportProductsFirst:        '{s name=export/message/export_products_first}Are you sure you want to export the {/s}',
            exportProductsEnd:          '{s name=export/message/export_products_end} shop product(s)?{/s}',
            alertExportTitle:           '{s name=export/message/alert_export_title}Export shop product(s)!{/s}',
            alertExport:                '{s name=export/message/alert_export}Thank you to choose a shop for export.{/s}',
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
        console.log('Publish Products');
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
        console.log('Active product');
        var me          = this,
            store       = me.getArticleGrid().getStore(),
            articleGrid = me.getArticleGrid(); 

        record.set('activeLengow', value);
        record.save();
        store.load(); 
    },

    onSaveActiveProduct: function(editor, event, store) {
        console.log('Save active product');
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
        console.log('Export products');

        var me          = this,
            store       = me.getArticleGrid().getStore(),
            articleGrid = me.getArticleGrid();

        var shop = record.getValue();
        var http = location.protocol;
        var slashes = http.concat("//");
        var host = slashes.concat(window.location.hostname);

        if (shop) {
            Ext.MessageBox.confirm(
            me.snippets.message.exportProductsTitleFirst + shop + me.snippets.message.exportProductsTitleEnd,
            me.snippets.message.exportProductsFirst + shop + me.snippets.message.exportProductsEnd,
            function (response) {
                if ( response !== 'yes' ) {
                    return;
                }
                window.open(host + '/engine/Shopware/Plugins/Local/Backend/Lengow/Webservice/export.php?shop=' + shop, '_blank');
            });
        } else {
            Ext.MessageBox.alert(me.snippets.message.alertExportTitle, me.snippets.message.alertExport);
        }
    }

});
//{/block}