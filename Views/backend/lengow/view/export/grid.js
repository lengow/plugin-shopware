//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/grid"}
Ext.define('Shopware.apps.Lengow.view.export.Grid', {
    extend: 'Ext.grid.Panel',
    alias:  'widget.product-listing-grid',

    snippets: {
        column: {
            number:         '{s name=export/grid/column/number}Number{/s}',
            name:           '{s name=export/grid/column/name}Product name{/s}',
            supplier:       '{s name=export/grid/column/supplier}Supplier{/s}',
            active:         '{s name=export/grid/column/active}Active{/s}',
            price:          '{s name=export/grid/column/price}Price{/s}',
            tax:            '{s name=export/grid/column/tax}Tax{/s}',
            stock:          '{s name=export/grid/column/stock}Stock{/s}',
            lengowActive:   '{s name=export/grid/column/lengowActive}Lengow\'s products{/s}'
        },
        tooltip: {
            activeProduct:      '{s name=export/grid/tooltip/active_product}Publish product{/s}',
            desactiveProduct:   '{s name=export/grid/tooltip/desactive_product}Unpublish product{/s}',
            seeProduct:         '{s name=export/grid/tooltip/see_product}See product{/s}'
        },
        topToolbar: {
            publishProducts:    '{s name=export/grid/topToolbar/publish_products}Publish products{/s}',
            unpublishProducts:  '{s name=export/grid/topToolbar/unpublish_products}Unpublish products{/s}',
            selectShopEmpty:    '{s name=export/grid/topToolbar/select_shop_empty}Select a shop...{/s}',
            exportProducts:     '{s name=export/grid/topToolbar/export_products}Export products{/s}',
            searchProducts:     '{s name=export/grid/topToolbar/search_products}Search...{/s}'
        }
    },

    initComponent: function() {
        var me = this;

        me.selModel = me.getGridSelModel();
        me.columns = me.getColumns();
        me.tbar = me.getToolbar();
        me.bbar = me.createPagingToolbar();

        me.callParent(arguments);
    },

    /**
     * Creates the grid selection model for checkboxes
     * @return [Ext.selection.CheckboxModel] grid selection model
     */
    getGridSelModel: function () {
        var me = this;

        return Ext.create('Ext.selection.CheckboxModel', {
            listeners:{
                // Unlocks the delete button if the user has checked at least one checkbox
                selectionchange: function (sm, selections) {
                    me.publishProductsBtn.setDisabled(selections.length === 0);
                    me.unpublishProductsBtn.setDisabled(selections.length === 0);
                }
            }
        });
    },

    /**
     *  Creates the columns
     */
    getColumns: function(){
        var me = this;

        var columns = [
            {
                header: me.snippets.column.number,
                dataIndex: 'number',
                flex: 2
            }, {
                header: me.snippets.column.name,
                dataIndex: 'name',
                flex: 3
            }, {
                header: me.snippets.column.supplier,
                dataIndex: 'supplier',
                flex: 3
            }, 
            this.createActiveColumn('status', 'Active'), 
            {
                header: me.snippets.column.price,
                dataIndex: 'price',
                xtype: 'numbercolumn',
                width: 60
            }, { 
                header: me.snippets.column.tax,
                dataIndex: 'vat',
                xtype: 'numbercolumn',
                width: 60
            }, {
                header: me.snippets.column.stock,
                dataIndex: 'inStock',
                flex: 1
            },
            this.createActiveColumn('lengowActive', 'Export product')
        ];
        return columns;
    }, 

    /**
     * Create a checkbock (v4)/enabled-disabled form(v5) column form
     * @param name Field key of the Article model
     * @param header Header to display for this column
     */
    createActiveColumn: function(name, header) {
        var me = this,
            items = [],
            lengowColumn = name == 'lengowActive' ? true : false;

        items.push({
            tooltip: lengowColumn ? '{s name="activate_deactivate"}{/s}' : '',
            handler: function(grid, rowIndex, colIndex, item, eOpts, record) {
                if (lengowColumn) {
                    var attributeId = record.raw['attributeId']
                        categoryId = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id');
                    me.fireEvent('setStatusInLengow', Ext.encode([attributeId]), !record.get('lengowActive'), categoryId);
                    me.store.reload();
                }
            },
            getClass: function(value, metaData, record) {
                var status = record.get(name);

                if (status) {
                    return 'sprite-ui-check-box';
                } else {
                    return 'sprite-ui-check-box-uncheck';
                }
            }
        });

        return {
            xtype: 'actioncolumn',
            header: header,
            align: 'center',
            items: items
        };
    },

    /**
     * Creates the paging toolbar
     */
    createPagingToolbar: function() {
        var me = this;
        var pageSize = Ext.create('Ext.form.field.ComboBox', {
            labelWidth: 120,
            cls: Ext.baseCSSPrefix + 'page-size',
            queryMode: 'local',
            width: 80,
            listeners: {
                scope: me,
                select: me.onPageSizeChange
            },
            store: Ext.create('Ext.data.Store', {
                fields: [ 'value' ],
                data: [
                    { value: '20' },
                    { value: '40' },
                    { value: '60' },
                    { value: '80' },
                    { value: '100' }
                ]
            }),
            displayField: 'value',
            valueField: 'value'
        });
        pageSize.setValue(me.store.pageSize);

        var pagingBar = Ext.create('Ext.toolbar.Paging', {
            store: me.store,
            dock:'bottom',
            displayInfo:true
        });

        pagingBar.insert(pagingBar.items.length - 2, [ { xtype: 'tbspacer', width: 6 }, pageSize ]);
        return pagingBar;
    },

    /**
     * Event listener method which fires when the user selects
     * @event select
     * @param [object] combo - Ext.form.field.ComboBox
     * @param [array] records - Array of selected entries
     * @return void
     */
    onPageSizeChange: function(combo, records) {
        var record = records[0],
            me = this;
        me.store.pageSize = record.get('value');
        me.store.loadPage(1);
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
            region: 'north',
            disabled: true,
            handler: function() {
                var selectionModel = me.getSelectionModel(),
                    records = selectionModel.getSelection(),
                    categoryId = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id')
                    attributeIds = [];
                Ext.each(records, function(record) {
                    attributeIds.push(record.raw['attributeId']);
                });
                me.fireEvent('setStatusInLengow', Ext.encode(attributeIds), true, categoryId);
            }
        });

        me.unpublishProductsBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-minus-circle',
            text: 'Remove from export',
            region: 'south',
            disabled: true,
            handler: function() {
                var selectionModel = me.getSelectionModel(),
                    records = selectionModel.getSelection(),
                    categoryId = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id'),
                    attributeIds = [];
                Ext.each(records, function(record) {
                    attributeIds.push(record.raw['attributeId']);
                });
                me.fireEvent('setStatusInLengow', Ext.encode(attributeIds), false, categoryId);
            }
        });

        me.fireEvent('getNumberOfExportedProducts');

        return [ 
                me.publishProductsBtn,
                me.unpublishProductsBtn,
                {
                    xtype: 'tbfill'
                },
                {
                    ui: 'shopware-ui',
                    xtype: 'label',
                    id: 'cpt',
                    region: 'south',
                    forId: 'myFieldId',
                    text: '',
                    margins: '0 0 0 10'
                }
            ];
    }
});
//{/block}