/**
 * Created by nicolasmaugendre on 26/05/16.
 */
Ext.define('Shopware.apps.Lengow.view.export.Container', {
    extend: 'Ext.container.Container',
    alias: 'widget.product-export-container',

    /**
     * Main controller
     * @returns [controller:string]
     */
    configure: function() {
        return {
            controller: 'LengowExport'
        };
    },

    /**
     * Init components used by the container
     */
    initComponent: function() {
        var me = this;

        me.items = [
            {
                xtype: 'category-tree',
                region: 'west',
                width: 300,
                layout: 'fit'
            },
            me.productListPanel()
        ];

        me.callParent(arguments);
    },

    /**
     * Constructs the list where articles are displayed
     * @returns [Ext.panel.Panel]
     */
    productListPanel: function() {
        var me = this;

        var grid = Ext.create('Shopware.apps.Lengow.view.export.Grid', {
            region: 'center',
            margins: '2 0 2 0',
            layout: 'fit',
            bodyStyle: 'background:#fff;',
            listeners: {
                itemclick: function(dv, record, item, index, e) {
                    console.log("show");
                }
            }
        });

        return grid;
    }
});