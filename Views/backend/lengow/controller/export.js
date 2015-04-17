//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/controller/export"}
Ext.define('Shopware.apps.Lengow.controller.Export', {

    extend:'Ext.app.Controller',

    refs: [
        { ref: 'articleGrid', selector: 'lengow-export-grid' }
    ],

    init:function () {
        var me = this;
        me.control({
            'lengow-export-grid': {
                publishProducts: me.onPublishProducts,
                unpublishProducts: me.onUnpublishProducts,
                exportProducts: me.onExportProducts,
                saveActiveProduct: me.onSaveActiveProduct
            }
        });
        me.callParent(arguments);
    },

    onPublishProducts: function() {
        console.log('Publish Products');
    },

    onUnpublishProducts: function() {
        console.log('Unpublish Products');
    },

    onExportProducts: function() {
        console.log('Export products');
    },

    onSaveActiveProduct: function(editor, event, store) {
        console.log('Save active product');
        var me = this,
            record, rawData,
            grid = me.getArticleGrid();
        record = store.getAt(event.rowIdx);
        if(record == null) {
            return;
        }
        record.save(); 
    }

});
//{/block}