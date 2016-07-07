//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/panel"}
Ext.define('Shopware.apps.Lengow.view.export.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-category-panel',

    layout: 'fit',
    bodyStyle: 'background:#fff;',

    // Translations
    snippets: {
        export: {
            label: {
                shop: '{s name="export/panel/label/shop" namespace="backend/Lengow/translation"}{/s}'
            },
            button: {
                settings: '{s name="export/button/settings" namespace="backend/Lengow/translation"}{/s}',
                register: '{s name="export/button/register" namespace="backend/Lengow/translation"}{/s}'
            }
        }
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
     * Returns the tree panel with and a toolbar
     */
    getPanels: function () {
        var me = this;

        var activeStores = Ext.create('Ext.data.Store',{
            model: 'Shopware.apps.Lengow.model.Shops',
            autoLoad: true,
            proxy: {
                type: 'ajax',
                api: {
                    read: '{url controller="LengowExport" action="getActiveShop"}'
                },
                reader: {
                    type: 'json',
                    root: 'data'
                }
            }
        });

        me.treePanel = Ext.create('Ext.panel.Panel', {
            margin : '2px',
            bodyStyle: 'background:#fff;',
            layout: {
                type: 'vbox',
                pack: 'start',
                align: 'stretch'
            },
            items: [
                // List of available shops
                Ext.create('Ext.form.field.ComboBox', {
                    id: 'shopId',
                    padding : '5px',
                    fieldLabel: me.snippets.export.label.shop,
                    displayField: 'name',
                    layout: 'fit',
                    store: activeStores,
                    editable: false,
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
                    text: me.snippets.export.button.settings,
                    layout: 'fit',
                    region: 'top',
                    handler: function() {
                        Shopware.app.Application.addSubApplication({
                            name: 'Shopware.apps.Config'
                        });
                    }
                }),
                // Iframe button
                Ext.create('Ext.button.Button', {
                    text: me.snippets.export.button.register,
                    layout: 'fit',
                    region: 'top',
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

        tree = Ext.create('Shopware.apps.Lengow.view.export.Tree', {
            listeners: {
                itemclick: {
                    fn: function (view, record) {
                        var me = this,
                            store =  me.store,
                            grid = Ext.getCmp('exportGrid');

                        if (record.get('id') === 'root') {
                            store.getProxy().extraParams.categoryId = null;
                            return false;
                        } else {
                            store.getProxy().extraParams.categoryId = record.get('id');
                        }

                        //scroll the store to first page
                        store.currentPage = 1;
                        grid.setNumberOfProductExported();
                        grid.setLengowShopStatus();
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
            text: '{s name="export/button/shop" namespace="backend/Lengow/translation"}{/s}',
            enabled: false,
            disabled:true,
            handler: function(e) {
                var selectedShop = Ext.getCmp('shopId').getRawValue();
                me.fireEvent('exportShop', selectedShop);
            }
        });
    }

});
//{/block}