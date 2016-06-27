/**
 * Created by nicolasmaugendre on 26/05/16.
 */
Ext.define('Shopware.apps.Lengow.view.export.Container', {
    extend: 'Ext.container.Container',
    alias: 'widget.lengow-export-container',

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
                width: 300,
                layout: 'fit'
            },
            me.productGrid()
        ];

        me.callParent(arguments);
    },

    /**
     * Constructs the list where articles are displayed
     * @returns [Ext.panel.Panel]
     */
    productGrid: function() {
        var me = this;

        var grid = Ext.create('Shopware.apps.Lengow.view.export.Grid', {
            id: 'exportGrid',
            region: 'center',
            store: me.store,
            layout: 'fit',
            bodyStyle: 'background:#fff;'
        });

        return grid;
    }
});