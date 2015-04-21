//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/imports"}
Ext.define('Shopware.apps.Lengow.view.main.Imports', {

    extend:'Ext.grid.Panel',

    alias:'widget.lengow-main-imports',

    border: 0,
    autoScroll: true,

    snippets: {
        column: {
            idOrder:        '{s name=imports/column/id_Order}ID{/s}',
            orderDate:      '{s name=imports/column/order_date}Order date{/s}',
            idOrderLengow:  '{s name=imports/column/id_order_lengow}ID Lengow{/s}',
            idFlux:         '{s name=imports/column/id_flux}ID Flux{/s}',
            totalPaid:      '{s name=imports/column/total_paid}Price{/s}',
            marketplace:    '{s name=imports/column/marketplace}Marketplace{/s}',
            carrier:        '{s name=imports/column/carrier}Carrier{/s}',
            carrierMethod:  '{s name=imports/column/carrier_method}Carrier Method{/s}'
        },
        topToolbar: {
            manualImport:   '{s name=imports/topToolbar/manual_import}Manual Import{/s}',
            searchOrder:    '{s name=imports/topToolbar/search_order}Search...{/s}'
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

        return Ext.create('Ext.selection.CheckboxModel', {
            listeners:{
                selectionchange: function (sm, selections) {
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
                header: me.snippets.column.idOrder,
                dataIndex: 'id',
                width: 60
            },{
                header: me.snippets.column.orderDate,
                dataIndex: 'orderDate',
                flex: 3,
                renderer:  me.modifiedCreated
            },{
                header: me.snippets.column.idOrderLengow,
                dataIndex: 'idOrderLengow',
                flex: 4
            },{
                header: me.snippets.column.idFlux,
                dataIndex: 'idFlux',
                width: 60
            },{
                header: me.snippets.column.totalPaid,
                dataIndex: 'totalPaid',
                width: 60
            },{
                header: me.snippets.column.marketplace,
                dataIndex: 'marketplace',
                flex: 3
            },{
                header: me.snippets.column.carrier,
                dataIndex: 'carrier',
                flex: 2
            },{
                header: me.snippets.column.carrierMethod,
                dataIndex: 'carrierMethod',
                flex: 2
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

        me.manualImportBtn = Ext.create('Ext.button.Button',{
            iconCls: 'sprite-plus-circle',
            text: me.snippets.topToolbar.manualImport,
            handler: function() {
                me.fireEvent('manualImport');
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
            width: 170,
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
                    { value: '100' },
                    { value: '250' }
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