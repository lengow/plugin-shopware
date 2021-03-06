//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/grid"}
Ext.define('Shopware.apps.Lengow.view.import.Grid', {
    extend: 'Ext.grid.Panel',
    alias:  'widget.order-listing-grid',

    loadMask:true,

    // translations
    snippets: {
        column: {
            actions: '{s name="order/grid/column/actions" namespace="backend/Lengow/translation"}{/s}',
            lengow_status: '{s name="order/grid/column/lengow_status" namespace="backend/Lengow/translation"}{/s}',
            order_types: '{s name="order/grid/column/order_types" namespace="backend/Lengow/translation"}{/s}',
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
            refunded: '{s name="order/grid/status_refunded" namespace="backend/Lengow/translation"}{/s}',
            closed: '{s name="order/grid/status_closed" namespace="backend/Lengow/translation"}{/s}',
            canceled: '{s name="order/grid/status_canceled" namespace="backend/Lengow/translation"}{/s}',
            shipped_by_mkp: '{s name="order/grid/status_shipped_by_mkp" namespace="backend/Lengow/translation"}{/s}'
        },
        search: {
            empty: '{s name="export/grid/search/empty" namespace="backend/Lengow/translation"}{/s}'
        },
        errors: {
            not_imported: '{s name="order/grid/errors/not_imported" namespace="backend/Lengow/translation"}{/s}',
            not_sent: '{s name="order/grid/errors/not_sent" namespace="backend/Lengow/translation"}{/s}',
            import: '{s name="order/grid/errors/import" namespace="backend/Lengow/translation"}{/s}',
            action: '{s name="order/grid/errors/action" namespace="backend/Lengow/translation"}{/s}'
        },
        buttons: {
            send_action: '{s name="order/buttons/mass_action_resend" namespace="backend/Lengow/translation"}{/s}',
            import_order: '{s name="order/buttons/mass_action_reimport" namespace="backend/Lengow/translation"}{/s}'
        },
        action_sent: '{s name="order/grid/action_sent" namespace="backend/Lengow/translation"}{/s}',
        action_waiting_return: '{s name="order/grid/action_waiting_return" namespace="backend/Lengow/translation"}{/s}',
        nb_product: '{s name="order/grid/nb_product" namespace="backend/Lengow/translation"}{/s}'
    },

    listeners : {
        cellclick : function(view, cell, cellIndex, record) {
            var me = this,
                errorType = parseInt(record.raw.orderProcessState) === 0 ? 'import' : 'send',
                clickedColumnName = view.panel.headerCt.getHeaderAtIndex(cellIndex).dataIndex;
            if (clickedColumnName === 'inError' && record.raw.inError) {
                me.fireEvent('reSendActionGrid', record.raw.id, errorType);
            }
        }
    },

    registerEvents: function() {
        this.addEvents(
            'showDetail',
            'reSendActionGrid'
        )
    },

    /**
     * Init components used by the container
     */
    initComponent: function() {
        var me = this;

        me.store = me.importStore;
        me.selModel = me.getGridSelModel();
        me.orderStatusStore = Ext.create('Shopware.apps.Base.store.OrderStatus');
        me.columns = me.getColumns();
        me.tbar = me.getToolbar();
        me.bbar = me.createPagingToolbar();

        me.callParent(arguments);
    },

    /**
     * Creates the grid selection model for checkboxes
     * @return Ext.selection.CheckboxModel grid selection model
     */
    getGridSelModel: function () {
        var me = this;

        return Ext.create('Ext.selection.CheckboxModel', {
            listeners:{
                selectionchange: function (view, selections) {
                    if (selections.length === 1) {
                        me.fireEvent('selectOrder', selections[0]);
                    }
                    var status = selections.length === 0;
                    me.sendActionBtn.setVisible(!status);
                    me.importOrderBtn.setVisible(!status);

                    // if mass selection, display combobox to apply action on all articles
                    if (view.selectionMode === 'MULTI') {
                        var checkbox = Ext.getCmp('editAll');
                        if (!status) {
                            checkbox.show();
                        } else {
                            checkbox.hide();
                            checkbox.setValue(false);
                        }
                    }
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
                header: me.snippets.column.actions,
                tdCls: 'custom-grid-overflow',
                dataIndex: 'inError',
                flex: 2,
                renderer: function(value, metadata, record) {
                    var orderIdShopware = record.get('orderId'),
                        orderProcessState = parseInt(record.get('orderProcessState')),
                        lastActionType = record.get('lastActionType'),
                        errorMessages = record.get('errorMessage');
                    if (value && orderProcessState !== 2) {
                        var errorType = orderProcessState === 0 ? 're_import' : 're_send';
                        if (errorType === 're_import') {
                            var tootlip = me.snippets.errors.import + errorMessages;
                            return '<div class=" x-btn primary small lengow_action_button_grid">' +
                                '<span class="lengow_action lengow_tooltip lgw_order_action_grid-js"'
                                + ' data-href="#">' + me.snippets.errors.not_imported
                                + '<span class="lengow_order_action">' + tootlip + '</span></span></div>';
                        } else {
                            var tootlip = me.snippets.errors.action + errorMessages;
                            return '<div class=" x-btn primary small lengow_action_button_grid">' +
                                '<span class="lengow_action lengow_tooltip lgw_order_action_grid-js"'
                                + ' data-href="#">' + me.snippets.errors.not_sent
                                + '<span class="lengow_order_action">' + tootlip + '</span></span></div>';
                        }
                    } else {
                        if (null != orderIdShopware && orderProcessState === 1) {
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
                align: 'center',
                flex: 2,
                renderer : function(value) {
                    return '<span class="lgw-label lgw-label-' + value + '">'
                        + me.snippets.status[value] + '</span>';
                }
            }, {
                header: me.snippets.column.order_types,
                tdCls: 'custom-grid-overflow',
                align: 'center',
                dataIndex: 'orderTypes',
                flex: 1.2,
                renderer : function(value, metadata, record) {
                    return record.get('orderTypesContent');
                }
            }, {
                header: me.snippets.column.marketplace_sku,
                dataIndex: 'marketplaceSku',
                flex: 2
            }, {
                header: me.snippets.column.marketplace,
                dataIndex: 'marketplaceLabel',
                flex: 1.5
            }, {
                header: me.snippets.column.store_name,
                dataIndex: 'storeName',
                flex: 1.5
            }, {
                header: me.snippets.column.shopware_status,
                dataIndex: 'orderStatus',
                flex: 1.8,
                renderer : function(value, metadata, record) {
                    var orderStatusDescription = record.get('orderStatusDescription');
                    if (orderStatusDescription) return orderStatusDescription;
                    else if (value) return value;
                    return '';
                }
            }, {
                header: me.snippets.column.shopware_sku,
                dataIndex: 'orderSku',
                flex: 1.8
            }, {
                header: me.snippets.column.customer_name,
                dataIndex: 'customerName',
                flex: 2
            }, {
                header: me.snippets.column.order_date,
                dataIndex: 'orderDate',
                flex: 2,
                renderer : function(value) {
                    var date = new Date(value);
                    return Ext.Date.format(date, 'd-M-Y G:i');
                }
            }, {
                header: me.snippets.column.country,
                tdCls: 'custom-grid-overflow',
                dataIndex: 'countryIso',
                flex: 1,
                align: 'center',
                renderer : function(value, metadata, record) {
                    var countryName = record.get('countryName'),
                        countryIsoA2 = value.substr(0,2).toUpperCase();
                    return '<a class="lengow_tooltip" href="#"><img src="/engine/Shopware/Plugins/Community/Backend/Lengow/Views/backend/lengow/resources/img/flag/'
                        + countryIsoA2 + '.png" alt="' + countryName + '" title="'
                        + countryName + '" /><span class="lengow_order_country">' + countryName + '</span></a>';
                }
            }, {
                header: me.snippets.column.total_paid,
                tdCls: 'custom-grid-overflow',
                dataIndex: 'totalPaid',
                flex: 1,
                renderer : function(value, metadata, record) {
                    var nbProduct = Ext.String.format(me.snippets.nb_product, record.get('orderItem'));
                    return '<div class="lengow_tooltip">' +  Ext.util.Format.currency(value)
                        + '<span class="lengow_order_amount">' + nbProduct + '</span></div>';
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
            handler: function (view, rowIndex) {
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

        // un-publish button - remove mass selection from export
        me.importOrderBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-drive-download',
            margins: '5 0 0 0',
            text: me.snippets.buttons.import_order,
            hidden: true,
            handler: function() {
                me.sendMassActionButtonHandler('import');
            }
        });

        // publish button - add mass selection to export
        me.sendActionBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-arrow-circle-225-left',
            text: me.snippets.buttons.send_action,
            hidden: true,
            margins: '5 0 0 0',
            handler: function() {
                me.sendMassActionButtonHandler('send');
            }
        });

        return [{
            xtype: 'panel',
            layout: {
                type: 'hbox',
                pack: 'bottom'
            },
            width: '100%',
            border: false,
            items: [
                me.importOrderBtn,
                me.sendActionBtn,
                {
                    xtype: 'tbfill'
                },
                {
                    xtype : 'textfield',
                    name : 'searchfield',
                    action : 'search',
                    cls: 'searchfield',
                    margins: '7 10 2 0',
                    width: 230,
                    enableKeyEvents: true,
                    checkChangeBuffer: 500,
                    emptyText: me.snippets.search.empty,
                    listeners: {
                        change: function(field, value) {
                            var store        = me.store,
                                searchString = Ext.String.trim(value);
                            // scroll the store to first page
                            store.currentPage = 1;
                            // if the search-value is empty, reset the filter
                            if (searchString.length === 0 ) {
                                store.clearFilter();
                            } else {
                                // this won't reload the store
                                store.filters.clear();
                                // loads the store with a special filter
                                store.filter('search', searchString);
                            }
                        }
                    }
                }
            ]
        }];
    },

    sendMassActionButtonHandler: function(type) {
        var me = this,
            selectionModel = me.getSelectionModel(),
            records = selectionModel.getSelection(),
            lengowOrderIds = [],
            checkbox = Ext.getCmp('editAll');

        // enable mask on main container while the process is not finished
        Ext.getCmp('lengowImportTab').getEl().mask();

        // if select all products checkbox is not checked, get articles ids
        if (!checkbox.getValue()) {
            Ext.each(records, function(record) {
                lengowOrderIds.push(record.raw['id']);
            });
        }

        me.fireEvent('sendMassActionGrid', lengowOrderIds, type);
    }
});
//{/block}