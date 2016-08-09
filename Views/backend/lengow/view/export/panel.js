//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/panel"}
Ext.define('Shopware.apps.Lengow.view.export.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-category-panel',

    layout: 'fit',
    bodyStyle: 'background:#fff;',

    snippets: {
        filterTitle:    '{s name=export/filter/filter_title namespace="backend/Lengow/translation"}Filter{/s}',
        noFilter:       '{s name=export/filter/no_filter namespace="backend/Lengow/translation"}No filter{/s}',
        lengowProducts: '{s name=export/filter/lengow_products namespace="backend/Lengow/translation"}Lengow\'s products{/s}',
        activeProducts: '{s name=export/filter/active_products namespace="backend/Lengow/translation"}Active products{/s}',
        inStock:        '{s name=export/filter/in_stock namespace="backend/Lengow/translation"}In stock{/s}',
        noCategory:     '{s name=export/filter/no_category namespace="backend/Lengow/translation"}No categories{/s}'
    },

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
                me.createTree(),
                me.createFilterPanel()
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
    },

    createFilterPanel: function() {
        var me = this;

        return Ext.create('Ext.form.Panel', {
            title: me.snippets.filterTitle,
            bodyPadding: 5,
            id: 'lengowFilterPanel',
            hidden: true,
            items: [{
                xtype: 'radiogroup',
                listeners: {
                    change: {
                        fn: function(view, newValue) {
                            me.store.getProxy().extraParams.filterBy = newValue.filter;
                            me.store.load();
                        },
                        scope: me
                    }
                },
                columns: 1,
                vertical: true,
                collapsible: true,
                items: [
                    { boxLabel: me.snippets.noFilter, name: 'filter', inputValue: 'none', checked: true  },
                    { boxLabel: me.snippets.lengowProducts, name: 'filter', inputValue: 'lengowProduct' },
                    { boxLabel: me.snippets.activeProducts, name: 'filter', inputValue: 'activeProduct' },
                    { boxLabel: me.snippets.inStock, name: 'filter', inputValue: 'inStock'  },
                    { boxLabel: me.snippets.noCategory, name: 'filter', inputValue: 'noCategory' }
                ]
            }]
        });
    },

});
//{/block}