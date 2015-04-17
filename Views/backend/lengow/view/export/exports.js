//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/exports"}
Ext.define('Shopware.apps.Lengow.view.export.Exports', {

    extend: 'Ext.container.Container',

    alias: 'widget.lengow-export-exports',

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
            title: 'Categories',
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
            title: 'Filter',
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
                    { boxLabel: 'No filter', name: 'filter', inputValue: 'none', checked: true  },
                    { boxLabel: 'Lengow products', name: 'filter', inputValue: 'lengowProduct' },
                    { boxLabel: 'Active products', name: 'filter', inputValue: 'activeProduct' },
                    { boxLabel: 'In stock', name: 'filter', inputValue: 'inStock'  },
                    { boxLabel: 'No category', name: 'filter', inputValue: 'noCategory' }
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
                text: 'Categories',
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