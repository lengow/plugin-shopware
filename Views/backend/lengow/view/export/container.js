//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/container"}
Ext.define('Shopware.apps.Lengow.view.export.Container', {
    extend: 'Ext.container.Container',
    alias: 'widget.lengow-export-container',
    renderTo: Ext.getBody(),

    snippets: {
        checkbox: {
            variations: '{s name="export/grid/checkbox/variations" namespace="backend/Lengow/translation"}{/s}',
            out_stock: '{s name="export/grid/checkbox/out_stock" namespace="backend/Lengow/translation"}{/s}',
            selection: '{s name="export/grid/checkbox/selection" namespace="backend/Lengow/translation"}{/s}'
        }
    },

    /**
     * Main controller
     * @returns [controller:string]
     */
    configure: function() {
        return {
            controller: 'Main'
        };
    },

    /**
     * Init components used by the container
     */
    initComponent: function() {
        var me = this;

        me.items = [
            {
                xtype: 'lengow-category-panel',
                region: 'west',
                store: me.store,
                width: 250,
                layout: 'fit'
            },
            {
                xtype: 'panel',
                region: 'center',
                id: 'topPanel',
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                items: [
                    me.getFirstLine(),
                    me.getSecondLine(),
                    Ext.create('Shopware.apps.Lengow.view.export.Grid', {
                        id: 'exportGrid',
                        store: me.store,
                        flex: 1,                     
                        autoScroll : true,
                        style: 'border: none',
                        bodyStyle: 'background:#fff;'
                    })
                ]
            },
        ];

        me.callParent(arguments);
    },

    /**
     * Constructs the list where articles are displayed
     * @returns [Ext.panel.Panel]
     */
    productGrid: function() {
        var me = this;

        var panel = Ext.create('Ext.Panel.Panel', {
            layout: {
                type:'vbox',
                align:'stretch'
            },
            items: [
                me.getFirstLine(),
                me.getSecondLine()
            ]
        });

        return panel;
    },

    /**
     * Display first line on top of the grid
     * Contains shop status, shop name and icon to download feed
     */
    getFirstLine: function() {
        var me = this;
        return {
            xtype: 'container',
            layout: {
                type: 'hbox'
            },
            margins: '7 0 7 0',
            items: [
                {
                    id: 'shopStatus',
                    xtype: 'component',
                    autoEl: {
                        tag: 'a'
                    },
                },
                {
                    xtype: 'tbfill'
                },
                {
                    xtype: 'label',
                    id: 'shopName',
                },
                {
                    xtype: 'label',
                    html: "<a href='#' id='downloadFeed' class='lengow_export_feed'></a>",
                    listeners: {
                        render: function(component){
                            component.getEl().on('click', function(){
                                var selectedShop = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id');
                                me.fireEvent('getFeed', selectedShop);
                            });
                        }
                    }
                }
            ]
        };
    },

    /**
     * Display second line on top of the grid
     * Contains shop settings and counter
     */
    getSecondLine: function() {
        var me = this;
        return {
            xtype: 'container',
            layout: 'hbox',
            margins: '7 0 7 0',
            items: [
                {
                    xtype: 'checkboxfield',
                    id: 'lengowExportVariation',
                    boxLabel: me.snippets.checkbox.variations,
                    listeners: {
                        change: function(checkbox){
                            var selectedShop = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id')
                                value = checkbox.getValue(),
                                id = checkbox.getId();
                            me.fireEvent('changeSettingsValue', selectedShop, id, value);
                            Ext.getCmp('exportGrid').updateCounter();
                        }
                    }
                },
                {
                    xtype: 'checkboxfield',
                    id: 'lengowExportOutOfStock',
                    boxLabel: me.snippets.checkbox.out_stock,
                    listeners: {
                        change: function(checkbox){
                            var selectedShop = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id')
                                value = checkbox.getValue(),
                                id = checkbox.getId();
                            me.fireEvent('changeSettingsValue', selectedShop, id, value);
                            Ext.getCmp('exportGrid').updateCounter();
                        }
                    }
                },
                {
                    xtype: 'checkboxfield',
                    id: 'lengowExportLengowSelection',
                    boxLabel: me.snippets.checkbox.selection,
                    listeners: {
                        change: function(checkbox){
                            var selectedShop = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id')
                                value = checkbox.getValue(),
                                id = checkbox.getId();

                            Ext.getCmp('exportGrid').setDisabled(!value);

                            me.fireEvent('changeSettingsValue', selectedShop, id, value);
                            Ext.getCmp('exportGrid').updateCounter();
                        }
                    }
                },
                {
                    xtype: 'tbfill'
                },
                {
                    xtype: 'label',
                    align: 'right',
                    cls: 'lengow_shop_status_label',
                    id: 'productCounter'
                }
            ]
        }
    },
});
//{/block}