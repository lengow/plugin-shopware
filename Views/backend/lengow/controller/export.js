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
                if (value) {
                    records[i].set('activeLengow', true);
                } else {
                    records[i].set('activeLengow', false); 
                }
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

        if (value) {
            record.set('activeLengow', true);
        } else {
            record.set('activeLengow', false);
        }
        record.save();
        store.load(); 
    },

    onExportProducts: function() {
        console.log('Export products');

        // Ext.Ajax.request({
        //     url: '{url action="createEsd"}',
        //     method: 'POST',
        //     params: {
        //         articleDetailId: articleDetailId
        //     },
        //     success: function(response, opts) {
        //         Shopware.Notification.createGrowlMessage(me.snippets.success.title, me.snippets.success.esdCreated, me.snippets.growlMessage);
        //         store.load();
        //     }
        // });
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
    }

});
//{/block}