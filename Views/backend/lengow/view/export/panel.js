//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/panel"}
Ext.define('Shopware.apps.Lengow.view.export.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-category-panel',

    layout: 'fit',
    bodyStyle: 'background:#fff;',

    initComponent: function () {
        var me = this;

        me.items = me.getPanels();

        me.addEvents(
            'filterByCategory'
        );

        me.callParent(arguments);
    },

    /**
     * Returns the panel which contains shop tree
     */
    getPanels: function () {
        var me = this;

        me.treePanel = Ext.create('Ext.panel.Panel', {
            margin : '2px',
            bodyStyle: 'background:#fff;',
            layout: {
                type: 'vbox',
                pack: 'start',
                align: 'stretch'
            },
            items: [
                me.createTree()
            ]
        });

        return [me.treePanel];
    },

    /**
     * Creates the category tree
     *
     * @return [Ext.tree.Panel]
     */
    createTree: function () {
        var me = this,
                tree;

        tree = Ext.create('Shopware.apps.Lengow.view.export.Tree', {
            listeners: {
                load: function(view){
                    me.fireEvent('getDefaultShop', view);
                },
                itemclick: {
                    fn: function (view, record) {
                        var me = this,
                            store =  me.store,
                            grid = Ext.getCmp('exportGrid');

                        if (record.get('id') === 'root') {
                            return false; // Do nothing if root is selected
                        } 

                        store.getProxy().extraParams.categoryId = record.get('id');

                        // If a shop (root child) is selected
                        if (record.get('parentId') === 'root') {
                            // Update shop name and shop id
                            var label = record.get('text') + ' (' + record.get('id') + ')';
                            Ext.getCmp('shopName').update(label);
                            // Update selected shop status
                            grid.setLengowShopStatus();
                            // Init checkbox options listeners
                            grid.initConfigCheckboxes();
                        }

                        // Update number of exported products
                        grid.updateCounter();

                        //scroll the store to first page
                        store.currentPage = 1;
                    }
                },
                scope: me
            }
        });

        return tree;
    }

});
//{/block}