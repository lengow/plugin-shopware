//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/grid"}
Ext.define('Shopware.apps.Lengow.view.import.Grid', {
    extend: 'Ext.grid.Panel',
    alias:  'widget.order-listing-grid',

    loadMask:true,

    // Translations
    snippets: {
        column: {
            actions: '{s name="order/grid/column/actions" namespace="backend/Lengow/translation"}{/s}',
            lengow_status: '{s name="order/grid/column/lengow_status" namespace="backend/Lengow/translation"}{/s}',
            marketplace: '{s name="order/grid/column/marketplace" namespace="backend/Lengow/translation"}{/s}',
            store_name: '{s name="order/grid/column/store_name" namespace="backend/Lengow/translation"}{/s}',
            marketplace_sku: '{s name="order/grid/column/marketplace_sku" namespace="backend/Lengow/translation"}{/s}',
            shopware_sku: '{s name="order/grid/column/shopware_sku" namespace="backend/Lengow/translation"}{/s}',
            shopware_status: '{s name="order/grid/column/shopware_status" namespace="backend/Lengow/translation"}{/s}',
            order_date: '{s name="order/grid/column/order_date" namespace="backend/Lengow/translation"}{/s}',
            customer_name: '{s name="order/grid/column/customer_name" namespace="backend/Lengow/translation"}{/s}',
            country: '{s name="order/grid/column/country" namespace="backend/Lengow/translation"}{/s}',
            nb_items: '{s name="order/grid/column/nb_items" namespace="backend/Lengow/translation"}{/s}',
            total_paid: '{s name="order/grid/column/total_paid" namespace="backend/Lengow/translation"}{/s}'
        },
        status: {
            accepted: '{s name="order/grid/status_accepted" namespace="backend/Lengow/translation"}{/s}',
            waiting_shipment: '{s name="order/grid/status_waiting_shipment" namespace="backend/Lengow/translation"}{/s}',
            shipped: '{s name="order/grid/status_shipped" namespace="backend/Lengow/translation"}{/s}',
            closed: '{s name="order/grid/status_closed" namespace="backend/Lengow/translation"}{/s}',
            shipped_by_mkp: '{s name="order/grid/status_shipped_by_mkp" namespace="backend/Lengow/translation"}{/s}',
            canceled: '{s name="order/grid/status_canceled" namespace="backend/Lengow/translation"}{/s}'
        },
        search: {
            empty: '{s name="export/grid/search/empty" namespace="backend/Lengow/translation"}{/s}'
        },
        errors: {
            import: '{s name="order/grid/errors/import" namespace="backend/Lengow/translation"}{/s}',
            action: '{s name="order/grid/errors/action" namespace="backend/Lengow/translation"}{/s}'
        },
        action_sent: '{s name="order/grid/action_sent" namespace="backend/Lengow/translation"}{/s}',
        action_waiting_return: '{s name="order/grid/action_waiting_return" namespace="backend/Lengow/translation"}{/s}'
    },

    listeners : {
        cellclick : function(view, cell, cellIndex, record, row, rowIndex, e) {
            var errorType = record.raw.orderProcessState == 0 ? 'import' : 'send';
            var clickedColumnName = view.panel.headerCt.getHeaderAtIndex(cellIndex).dataIndex;
            if (clickedColumnName == 'inError') {
                console.log('plop' + errorType);
                // me.fireEvent('sendAction', errorType);
            }
        }
    },

    registerEvents: function() {
        this.addEvents(
            'showDetail',
            'sendAction'
        )
    },

    /**
     * Init components used by the container
     */
    initComponent: function() {
        var me = this;

        me.store = me.importStore;
        me.orderStatusStore = Ext.create('Shopware.apps.Base.store.OrderStatus');
        me.columns = me.getColumns();
        me.tbar = me.getToolbar();
        me.bbar = me.createPagingToolbar();

        me.callParent(arguments);
    },

    /**
     *  Creates the columns
     */
    getColumns: function(){
        var me = this;

        var columns = [
            {
                header: me.snippets.column.actions,
                tdCls: 'custom-grid-action',
                dataIndex: 'inError',
                flex: 1,
                renderer: function(value, metadata, record) {
                    var orderIdShopware = record.get('orderId');
                    var orderIdLengow = record.get('id');
                    var orderProcessState = record.get('orderProcessState');
                    var lastActionType = record.get('lastActionType');
                    var errorMessages = record.get('errorMessage');
                    if (value) {
                        var errorType = record.get('orderProcessState') == 0 ? 'import' : 'send';
                        // var errorMessages = me.fireEvent('getErrors', orderIdLengow, errorType);//TODO
                        if (errorType == 'import') {
                            var tootlip = me.snippets.errors.import + errorMessages;
                            return '<span class="lengow_action lengow_tooltip lgw-btn lgw-btn-white lgw-label lgw_order_action_grid-js"'
                                + ' data-href="#">not imported'
                                + '<span class="lengow_order_action">' + tootlip + '</span></span>';
                        } else {
                            var tootlip = me.snippets.errors.action + errorMessages;
                            return '<span class="lengow_action lengow_tooltip lgw-btn lgw-label lgw-btn-white lgw_order_action_grid-js"'
                                + ' data-href="#">not sent'
                                + '<span class="lengow_order_action">' + tootlip + '</span></span>';
                        }
                    } else {
                        if (null != orderIdShopware && orderProcessState == 1) {
                            if (lastActionType) {
                                var lengowMessage = Ext.String.format(
                                    me.snippets.action_sent,
                                    lastActionType
                                );
                                return '<a class="lengow_action lengow_tooltip lgw-btn lgw-label lgw-btn-white">' +
                                    lengowMessage + '<span class="lengow_order_action">' +
                                    me.snippets.action_waiting_return + '</span></a>';
                            } else {
                                return '';
                            }
                        } else {
                            return '';
                        }
                    }
                }
            }, {
                header: me.snippets.column.lengow_status,
                dataIndex: 'orderLengowState',
                flex: 1,
                renderer : function(value, metadata, record) {
                    if(record.get('sentByMarketplace')) {
                        value = 'shipped_by_mkp';
                    }
                    return '<span class="lgw-label lgw-label-' + value + '">'
                        + me.snippets.status[value] + '</span>';
                }
            }, {
                header: me.snippets.column.marketplace,
                dataIndex: 'marketplaceLabel',
                flex: 1
            }, {
                header: me.snippets.column.store_name,
                dataIndex: 'storeName',
                flex: 1
            }, {
                header: me.snippets.column.marketplace_sku,
                dataIndex: 'marketplaceSku',
                flex: 1
            }, {
                header: me.snippets.column.shopware_status,
                dataIndex: 'orderStatus',
                renderer : function(value, metadata, record) {
                    var orderStatusDescription = record.get('orderStatusDescription');
                    if (orderStatusDescription) return orderStatusDescription;
                    else if (value) return value;
                    return '';
                },
                flex: 1
            }, {
                header: me.snippets.column.shopware_sku,
                dataIndex: 'orderSku',
                flex: 1
            }, {
                header: me.snippets.column.order_date,
                dataIndex: 'orderDate',
                flex: 1
            }, {
                header: me.snippets.column.customer_name,
                dataIndex: 'customerName',
                flex: 1
            }, {
                header: me.snippets.column.country,
                dataIndex: 'countryIso',
                flex: 1,
                renderer : function(value, metadata, record) {
                    return '<img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/flag/'
                        + value.substr(0,2).toUpperCase() + '.png" alt="' + record.get('countryName') + '" title="'
                        + record.get('countryName') + '" />';
                }
            }, {
                header: me.snippets.column.nb_items,
                dataIndex: 'orderItem',
                flex: 1
            }, {
                header: me.snippets.column.total_paid,
                dataIndex: 'totalPaid',
                flex: 1,
                renderer : function(value) {
                    return Ext.util.Format.currency(value);
                }
            },
            me.createActionColumn()
        ];
        return columns;
    },

    createActionColumn: function() {
        var me = this;

        return Ext.create('Ext.grid.column.Action', {
            width:30,
            items:[
                me.createEditOrderColumn()
            ]
        });
    },

    createEditOrderColumn: function () {
        var me = this;

        return {
            iconCls: 'sprite-pencil',
            action: 'editOrder',
            /**
             * Add button handler to fire the showDetail event which is handled
             * in the list controller.
             */
            handler: function (view, rowIndex, colIndex, item) {
                var store = view.getStore(),
                    record = store.getAt(rowIndex);

                if (record.raw.orderShopwareSku > 0) {
                    me.fireEvent('showDetail', record);
                }

            },
            getClass: function(value, metadata, record) {
                if (record.raw.orderShopwareSku < 1) {
                    return Ext.baseCSSPrefix + 'hidden';
                }
            }
        }
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
     * Event listener method which fires when the user selects a nwe page size
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
        var me = this;

        return [{
            xtype: 'panel',
            layout: {
                type: 'hbox',
                pack: 'bottom'
            },
            width: '100%',
            border: false,
            items: [
                {
                    xtype : 'textfield',
                    name : 'searchfield',
                    action : 'search',
                    cls: 'searchfield',
                    margins: '7 0 2 0',
                    width: 230,
                    enableKeyEvents: true,
                    checkChangeBuffer: 500,
                    emptyText: me.snippets.search.empty,
                    listeners: {
                        change: function(field, value) {
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
                }
            ]
        }];
    }
});
//{/block}