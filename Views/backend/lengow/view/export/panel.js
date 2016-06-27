Ext.define('Shopware.apps.Lengow.view.export.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-category-panel',

    layout: 'fit',

    title: '{s name=categories}Categories{/s}',

    initComponent: function () {
        var me = this;

        me.items = me.getPanels();

        me.addEvents(
            'filterByCategory'
        );

        me.callParent(arguments);
    },

    /**
     * Returns the tree panel with and a toolbar
     */
    getPanels: function () {
        var me = this;

        me.treePanel = Ext.create('Ext.panel.Panel', {
            border: false,
            layout: {
                type: 'vbox',
                pack: 'start',
                align: 'stretch'
            },
            items: [
                // List of available shops
                Ext.create('Ext.form.field.ComboBox', {
                    id: 'shopId',
                    fieldLabel: 'Export shop',
                    displayField: 'name',
                    layout: 'fit',
                    editable: false,
                    store: Ext.create('Shopware.apps.Base.store.Shop').load(),
                    listeners: {
                        select: function() {
                            Ext.getCmp('exportButton').enable();
                        }
                    }
                }),
                me.getExportButton(),
                me.createTree(),
                // Settings button 
                Ext.create('Ext.button.Button', {
                    text: 'Configure Lengow',
                    layout: 'fit',
                    region: 'top',
                    renderTo: Ext.getBody(),
                    handler: function() {
                        Shopware.app.Application.addSubApplication({
                            name: 'Shopware.apps.Config'
                        });
                    }
                }),
                // Iframe button
                Ext.create('Ext.button.Button', {
                    text: 'Get registered',
                    layout: 'fit',
                    region: 'top',
                    renderTo: Ext.getBody(),
                    handler: function() {
                        Shopware.app.Application.addSubApplication({
                            name: 'Shopware.apps.Iframe'
                        });
                    }
                })
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

        me.categoryStore = Ext.create('Shopware.store.CategoryTree');

        tree = Ext.create('Ext.tree.Panel', {
            border: false,
            rootVisible: true,
            expanded: true,
            useArrows: false,
            layout: 'fit',
            flex: 1,
            bodyStyle: 'background:#fff;',
            store: me.categoryStore,
            root: {
                text: '{s name=categories}Shops{/s}',
                expanded: true
            },
            listeners: {
                itemclick: {
                    fn: function (view, record) {
                        var me = this,
                            store =  me.store;

                        if (record.get('id') === 'root') {
                            store.getProxy().extraParams.categoryId = null;
                        } else {
                            store.getProxy().extraParams.categoryId = record.get('id');
                        }

                        //scroll the store to first page
                        store.currentPage = 1;
                        store.load({
                           scope: this,
                            callback: function(records, operation, success) {
                                var data = Ext.decode(operation.response.responseText);
                                var total = data['total'];
                                var lengowProducts = data['exportedProduct'];
                                var label = lengowProducts + ' products exported over ' + total + ' products available.';
                                Ext.getCmp('cpt').setText(label);
                            }
                        });
                    }
                },
                scope: me
            }
        });

        return tree;
    },

    getExportButton: function() {
        var me = this;
        return Ext.create('Ext.button.Button', {
            id: 'exportButton',
            text: 'Export shop',
            enabled: false,
            disabled:true,
            handler: function(e) {
                var selectedShop = Ext.getCmp('shopId').getRawValue();
                me.fireEvent('exportShop', selectedShop);
            }
        });
    }

});
