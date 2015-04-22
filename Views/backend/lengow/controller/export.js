//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/controller/export"}
Ext.define('Shopware.apps.Lengow.controller.Export', {

    extend:'Ext.app.Controller',

    refs: [
        { ref: 'articleGrid', selector: 'lengow-export-grid' }
    ],

    snippets: {
        message: {
            exportProductsTitle:    '{s name=export/message/export_products_title}Export product(s)?{/s}',
            exportProducts:         '{s name=export/message/export_products}Are you sure you want to export product(s)?{/s}'
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

    onExportProducts: function() {
        console.log('Export products');

        var me          = this,
            store       = me.getArticleGrid().getStore(),
            articleGrid = me.getArticleGrid();

        Ext.MessageBox.confirm(me.snippets.message.exportProductsTitle, me.snippets.message.exportProducts, function (response) {
            if ( response !== 'yes' ) {
                return;
            }
            articleGrid.setLoading(true);
            Ext.Ajax.request({
                url: '{url controller="LengowExport" action="export"}',
                method: 'POST',
                params: {},
                success: function(response, opts) {
                    store.load({
                        callback: function() {
                            articleGrid.setLoading(false);
                        }
                    }); 
                }
            });
        });
    }

});
//{/block}