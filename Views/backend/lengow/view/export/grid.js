

Ext.define('Shopware.apps.Lengow.view.export.Grid', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.product-listing-grid',

    viewConfig: {
      forceFit: true
    },

    configure: function() {
        var me = this;

        return {
            addButton: true,
            deleteButton: true,
            columns: {
                id: {},
                number: {},
                name: {},
                supplier: {},
                status: {},
                price: {},
                vat: {},
                inStock: {},
                activeLengow: this.createActiveColumn
            }
        };
    },

    initComponent: function() {
        var me = this;

        me.tbar = me.getToolbar();

        me.callParent(arguments);
    },

    createActiveColumn: function() {
        var me = this,
            items = [];

        items.push({
            tooltip: '{s name="activate_deactivate"}{/s}',
            handler: function(grid, rowIndex, colIndex, item, eOpts, record) {
                var articleId = record.raw['articleId'];
                me.fireEvent('setStatusInLengow', articleId, !record.get('activeLengow'));
            },
            getClass: function(value, metaData, record) {
                if (record.get('activeLengow')) {
                    return 'sprite-ui-check-box';
                } else {
                    return 'sprite-ui-check-box-uncheck';
                }
            }
        });

        return {
            xtype: 'actioncolumn',
            header: 'Lengow product',
            align: 'center',
            items: items
        };
    },

    /**
     * Creates the grid toolbar
     * @return [Ext.toolbar.Toolbar] grid toolbar
     */
    getToolbar: function() {
        var me = this, 
            buttons = [];

        me.publishProductsBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-plus-circle',
            text: 'Add to export',
            disabled: true,
            handler: function() {
                var selectionModel = me.getSelectionModel(),
                    records = selectionModel.getSelection();
                if (records.length > 0) {
                    var value = true;
                    me.fireEvent('publishProducts', records, value);
                }
            }
        });
        buttons.push(me.publishProductsBtn);

        me.unpublishProductsBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-minus-circle',
            text: 'Delete from export',
            disabled: true,
            handler: function() {
                var selectionModel = me.getSelectionModel(),
                    records = selectionModel.getSelection();
                if (records.length > 0) {
                    var value = false;
                    me.fireEvent('unpublishProducts', records, value);
                }
            }
        });
        buttons.push(me.unpublishProductsBtn);

        me.fireEvent('getNumberOfExportedProducts');

        return Ext.create('Ext.toolbar.Toolbar', {
            ui: 'shopware-ui',
            items: [
                {
                    xtype: 'label',
                    id: 'cpt',
                    forId: 'myFieldId',
                    text: '10 products exported over 400 available ',
                    margins: '0 0 0 10'
                }
            ]
        });
    }
});
