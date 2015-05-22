//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/grid"}
Ext.define('Shopware.apps.Lengow.view.export.Grid', {

    extend: 'Ext.grid.Panel',

    alias: 'widget.lengow-export-grid',

    snippets: {
        column: {
            number:         '{s name=export/grid/column/number}Number{/s}',
            name:           '{s name=export/grid/column/name}Product name{/s}',
            supplier:       '{s name=export/grid/column/supplier}Supplier{/s}',
            active:         '{s name=export/grid/column/active}Active{/s}',
            price:          '{s name=export/grid/column/price}Price{/s}',
            tax:            '{s name=export/grid/column/tax}Tax{/s}',
            stock:          '{s name=export/grid/column/stock}Stock{/s}',
            activeLengow:   '{s name=export/grid/column/activeLengow}Lengow\'s products{/s}'
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

        me.store = me.articlesStore;
        me.selModel = me.getGridSelModel();
        me.columns = me.getColumns();
        me.tbar = me.getToolbar();
        me.bbar = me.createPagingToolbar();
        me.plugins = me.createPlugins();
       
        me.addEvents(
            'publishProducts',
            'unpublishProducts',
            'exportProducts',
            'saveActiveProduct',
            'activeProduct',
            'desactiveProduct'
        );

        me.callParent(arguments);
    },

    createPlugins: function() {
        var me = this,
            rowEditor = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToEdit: 2,
            autoCancel: true,
            listeners: {
                scope: me,
                edit: function(editor, e) {
                    me.fireEvent('saveActiveProduct', editor, e, me.articlesStore)
                }
            }
        });
        return [ rowEditor ];
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
        var me = this,
            actionColumItems = [];

        actionColumItems.push({
            iconCls:'sprite-plus-circle-frame',
            action:'activeProduct',
            tooltip: 'Publish product',
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                var value = true;
                me.fireEvent('activeProduct', record, value);
            }
        });

        actionColumItems.push({
            iconCls:'sprite-minus-circle-frame',
            action:'desactiveProduct',
            tooltip: 'Unpublish Product',
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                var value = false;
                me.fireEvent('desactiveProduct', record, value);
            }
        });

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
            }, {
                header: me.snippets.column.active,
                dataIndex: 'active',
                xtype: 'booleancolumn',
                width: 50,
                renderer: me.activeColumnRenderer
            }, {
                header: me.snippets.column.price,
                dataIndex: 'price',
                xtype: 'numbercolumn',
                width: 60
            }, { 
                header: me.snippets.column.tax,
                dataIndex: 'tax',
                xtype: 'numbercolumn',
                width: 60
            }, {
                header: me.snippets.column.stock,
                dataIndex: 'inStock',
                flex: 1,
                renderer: me.colorColumnRenderer
            }, {
                header: me.snippets.column.activeLengow,
                dataIndex: 'activeLengow',
                xtype: 'booleancolumn',
                width: 110,
                renderer: me.activeColumnRenderer,
                editor: {
                    width: 110,
                    xtype: 'checkbox',
                    uncheckedValue: false,
                    inputValue: true
                }
            }, {
                xtype: 'actioncolumn',
                width: 26 * actionColumItems.length,
                items: actionColumItems
            }
        ];
        return columns;
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
            text: me.snippets.topToolbar.publishProducts,
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
            text: me.snippets.topToolbar.unpublishProducts,
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

        var shopStore = Ext.create('Shopware.apps.Base.store.Shop');
        shopStore.filters.clear();

        me.shopCombo = Ext.create('Ext.form.field.ComboBox', {
            triggerAction:'all',
            emptyText: me.snippets.topToolbar.selectShopEmpty,
            store: shopStore,
            width: 130,
            name: 'shopExport',
            valueField: 'name',
            displayField: 'name',
            queryMode: 'remote',
        });
        buttons.push(me.shopCombo);

        me.exportProductsBtn = Ext.create('Ext.button.Button', {
            iconCls:'sprite-plus-circle',
            text: me.snippets.topToolbar.exportProducts,
            handler: function() {
                me.fireEvent('exportProducts', me.shopCombo);
            }
        });
        buttons.push(me.exportProductsBtn);

        buttons.push({
            xtype: 'tbfill'
        });

        buttons.push({
            xtype : 'textfield',
            name : 'searchfield',
            action : 'search',
            width: 150,
            cls: 'searchfield',
            enableKeyEvents: true,
            checkChangeBuffer: 500,
            emptyText: me.snippets.topToolbar.searchProducts,
            listeners: {
                'change': function(field, value) {
                    var store        = me.store,
                        searchString = Ext.String.trim(value);
                    //scroll the store to first page
                    store.currentPage = 1;
                    //If the search-value is empty, reset the filter
                    if (searchString.length === 0 ) {
                        store.clearFilter();
                    } else {
                        //This won't reload the store
                        store.filters.clear();
                        //Loads the store with a special filter
                        store.filter('search', searchString);
                    }
                }
            }
        });

        buttons.push({
            xtype: 'tbspacer',
            width: 6
        });

        return Ext.create('Ext.toolbar.Toolbar', {
            ui: 'shopware-ui',
            items: buttons
        });
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
     * @param [object] - value
     */
    activeColumnRenderer: function(value) {
        if (value) {
            return '<div class="sprite-tick-small"  style="width: 25px; height: 25px">&nbsp;</div>';
        } else {
            return '<div class="sprite-cross-small" style="width: 25px; height: 25px">&nbsp;</div>';
        }
    },

    colorColumnRenderer: function(value) {
        if (value > 0){
            return '<span style="color:green;">' + value + '</span>';
        } else {
            return '<span style="color:red;">' + value + '</span>';
        }
    }

});
//{/block}