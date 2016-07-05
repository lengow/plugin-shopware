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
                header: '{s name="export/grid/column/number" namespace="backend/Lengow/translation"}{/s}',
                dataIndex: 'number',
                flex: 2
            }, {
                header: '{s name="export/grid/column/name" namespace="backend/Lengow/translation"}{/s}',
                dataIndex: 'name',
                flex: 3
            }, {
                header: '{s name="export/grid/column/supplier" namespace="backend/Lengow/translation"}{/s}',
                dataIndex: 'supplier',
                flex: 3
            }, 
            this.createActiveColumn('status', '{s name="export/grid/column/active" namespace="backend/Lengow/translation"}{/s}'), 
            {
                header: '{s name="export/grid/column/price" namespace="backend/Lengow/translation"}{/s}',
                dataIndex: 'price',
                xtype: 'numbercolumn',
                width: 60
            }, { 
                header: '{s name="export/grid/column/tax" namespace="backend/Lengow/translation"}{/s}',
                dataIndex: 'vat',
                xtype: 'numbercolumn',
                width: 60
            }, {
                header: '{s name="export/grid/column/stock" namespace="backend/Lengow/translation"}{/s}',
                dataIndex: 'inStock',
                flex: 1
            },
            this.createActiveColumn('lengowActive', '{s name="export/grid/column/export" namespace="backend/Lengow/translation"}{/s}')
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
            handler: function(grid, rowIndex, colIndex, item, eOpts, record) {
                if (lengowColumn) {
                    var attributeId = record.raw['attributeId']
                        categoryId = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id');
                    me.fireEvent('setStatusInLengow', Ext.encode([attributeId]), !record.get('lengowActive'), categoryId);
                    me.setNumberOfProductExported();
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
            items: items,
            renderer : function(value, metadata, record) {
                if (lengowColumn) {
                    var status = record.get('lengowActive'),
                        tooltip = status ? 
                                '{s name="export/grid/line/remove" namespace="backend/Lengow/translation"}{/s}' :
                                '{s name="export/grid/line/add" namespace="backend/Lengow/translation"}{/s}';
                    metadata.tdAttr = 'data-qtip="' + tooltip + '"';
                }

                return value;
            }
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
            text: '{s name="export/panel/button/add" namespace="backend/Lengow/translation"}{/s}',
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
            text: '{s name="export/panel/button/remove" namespace="backend/Lengow/translation"}{/s}',
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
    },

    setNumberOfProductExported: function() {
        var me = this,
            store = me.store;
        store.load({
            scope: this,
            callback: function(records, operation, success) {
                var data = Ext.decode(operation.response.responseText);
                var total = data['total'];
                var lengowProducts = data['nbLengowProducts'];
                var label = lengowProducts + ' {s name="export/panel/label/count" namespace="backend/Lengow/translation"}{/s} ' + 
                            + total + ' {s name="export/panel/label/total" namespace="backend/Lengow/translation"}{/s}.';
                Ext.getCmp('cpt').setText(label);
            }
        });
    }
});
//{/block}