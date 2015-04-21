//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/exports"}
Ext.define('Shopware.apps.Lengow.view.export.Exports', {

    extend: 'Ext.container.Container',

    alias: 'widget.lengow-export-exports',

    snippets: {
        categoryTitle:  '{s name=export/exports/category_title}Categories{/s}',
        filterTitle:    '{s name=export/exports/filter_title}Filter{/s}',
        noFilter:       '{s name=export/exports/no_filter}No filter{/s}',
        lengowProducts: '{s name=export/exports/lengow_products}Lengow\'s products{/s}',
        activeProducts: '{s name=export/exports/active_products}Active products{/s}',
        inStock:        '{s name=export/exports/in_stock}In stock{/s}',
        noCategory:     '{s name=export/exports/no_category}No categories{/s}'
    },

    initComponent: function() {
        var me = this;

        me.categoryStore = Ext.create('Shopware.store.CategoryTree');

        me.items = [{
            xtype: 'lengow-export-grid',
            articlesStore: me.articlesStore,
            region: 'center',
            bodyStyle: 'background:#fff;'
        }];

        me.sidebarPanel = Ext.create('Ext.panel.Panel', {
            title: me.snippets.categoryTitle,
            collapsible: true,
            width: 230,
            layout: {
                type: 'vbox',
                pack: 'start',
                align: 'stretch'
            },
            region: 'west',
            bodyStyle: 'background:#fff;',
            items: [
                me.createTree(),
                me.createFilterPanel()
            ]
        });

        me.items.push(me.sidebarPanel);

        me.callParent(arguments);
    },

    createFilterPanel: function() {
        var me = this;

        return new Ext.create('Ext.form.Panel', {
            title: me.snippets.filterTitle,
            bodyPadding: 5,
            items: [{
                xtype: 'radiogroup',
                listeners: {
                    change: {
                        fn: function(view, newValue, oldValue) {
                            var me    = this,
                                store =  me.articlesStore;
                            store.getProxy().extraParams.filterBy = newValue.filter;
                            store.load();
                        },
                        scope: me
                    }
                },
                columns: 1,
                vertical: true,
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

    /**
     * Creates the category tree
     *
     * @return [Ext.tree.Panel]
     */
    createTree: function() {
        var me = this;

        var tree = Ext.create('Ext.tree.Panel', {
            rootVisible: true,
            flex: 1,
            expanded: true,
            useArrows: false,
            store: me.categoryStore,
            root: {
                text: me.snippets.categoryTitle,
                expanded: true
            },
            listeners: {
                itemclick: {
                    fn: function(view, record) {
                        var me    = this,
                            store =  me.articlesStore;

                        if (record.get('id') === 'root') {
                            store.getProxy().extraParams.categoryId = null;
                        } else {
                            store.getProxy().extraParams.categoryId = record.get('id');
                        }

                        //scroll the store to first page
                        store.currentPage = 1;
                        store.load({
                            callback: function() {
                            }
                        });
                    },
                    scope: me
                }
            }
        });

        return tree;
    }
});
//{/block}