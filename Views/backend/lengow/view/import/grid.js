//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/grid"}
Ext.define('Shopware.apps.Lengow.view.import.Grid', {

    extend:'Ext.grid.Panel',

    alias:'widget.lengow-import-grid',

    snippets: {
        column: {
            orderNumber:    '{s name=import/grid/column/order_number}Order number{/s}',
            orderDate:      '{s name=import/grid/column/order_date}Order date{/s}',
            amount:         '{s name=import/grid/column/amount}Amount{/s}',
            shipping:       '{s name=import/grid/column/shipping}Shipping{/s}',
            shop:           '{s name=import/grid/column/shop}Shop{/s}',
            customer:       '{s name=import/grid/column/customer}Customer{/s}',
            currentStatus:  '{s name=import/grid/column/status}Current order status{/s}',
            idOrderLengow:  '{s name=import/grid/column/id_order_lengow}ID Lengow{/s}',
            idFlux:         '{s name=import/grid/column/id_flux}ID Flux{/s}',
            marketplace:    '{s name=import/grid/column/marketplace}Marketplace{/s}'
        },
        topToolbar: {
            selectShopEmpty:    '{s name=import/grid/topToolbar/select_shop_empty}Select a shop...{/s}',
            manualImport:       '{s name=import/grid/topToolbar/manual_import}Manual Import{/s}',
            searchOrder:        '{s name=import/grid/topToolbar/search_order}Search...{/s}'
        }
    },

    initComponent: function() {
        var me = this;

        me.store = me.ordersStore;
        me.selModel = me.getGridSelModel();
        me.columns = me.getColumns();
        me.tbar = me.getToolbar();
        me.bbar = me.createPagingToolbar();

        me.addEvents(
            'manualImport'
        );

        me.callParent(arguments);
    },

    /**
     * Creates the grid selection model for checkboxes
     * @return [Ext.selection.CheckboxModel] grid selection model
     */
    getGridSelModel: function () {
        var me = this;

        return Ext.create('Ext.selection.RowModel', {
            listeners:{
                selectionchange: function (view, selected) {
                    me.fireEvent('selectOrder', selected[0]);
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
            action:'viewOrder',
            tooltip: 'View order',
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.Order',
                    action: 'detail',
                    params: {
                        orderId: record.get('orderId')
                    }
                });
            }
        });

        var columns = [
            {
                header: me.snippets.column.orderNumber,
                dataIndex: 'orderNumber',
                width: 100
            },{
                header: me.snippets.column.orderDate,
                dataIndex: 'orderDate',
                flex: 3,
                renderer:  me.modifiedCreated
            },{
                header: me.snippets.column.amount,
                dataIndex: 'invoiceAmount',
                flex: 2
            },{
                header: me.snippets.column.shipping,
                dataIndex: 'shipping',
                flex: 4
            },{
                header: me.snippets.column.shop,
                dataIndex: 'nameShop',
                flex: 3
            },{
                header: me.snippets.column.customer,
                dataIndex: 'nameCustomer',
                flex: 4
            },{
                header: me.snippets.column.currentStatus,
                dataIndex: 'status',
                flex: 3
            },{
                header: me.snippets.column.idOrderLengow,
                dataIndex: 'idOrderLengow',
                flex: 4
            },{
                header: me.snippets.column.idFlux,
                dataIndex: 'idFlux',
                width: 60
            },{
                header: me.snippets.column.marketplace,
                dataIndex: 'marketplace',
                flex: 3
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
        var me      = this,
            buttons = [];

        var shopStore = Ext.create('Shopware.apps.Base.store.Shop');
        shopStore.filters.clear();

        me.shopCombo = Ext.create('Ext.form.field.ComboBox', {
            triggerAction:'all',
            emptyText: me.snippets.topToolbar.selectShopEmpty,
            store: shopStore,
            width: 130,
            name: 'shopImport',
            valueField: 'name',
            displayField: 'name',
            queryMode: 'remote',
        });
        buttons.push(me.shopCombo);

        me.manualImportBtn = Ext.create('Ext.button.Button',{
            iconCls: 'sprite-plus-circle',
            text: me.snippets.topToolbar.manualImport,
            handler: function() {
                me.fireEvent('manualImport', me.shopCombo);
            }
        });
        buttons.push(me.manualImportBtn);

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
            emptyText: me.snippets.topToolbar.searchOrder,
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
        var me      = this,
            record  = records[0];

        me.store.pageSize = record.get('value');
        me.store.loadPage(1);
    },

    /**
     * Column renderer function for the created column
     * @param [string] value    - The field value
     * @param [string] metaData - The model meta data
     * @param [string] record   - The whole data model
     */
    modifiedCreated: function(value, metaData, record) {
       return Ext.util.Format.date(record.get('orderDate')) + ' ' + Ext.util.Format.date(record.get('orderDate'), 'H:i:s');
    }

});
//{/block}