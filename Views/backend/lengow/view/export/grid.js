//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/grid"}
Ext.define('Shopware.apps.Lengow.view.export.Grid', {
    extend: 'Ext.grid.Panel',
    alias:  'widget.product-listing-grid',

    loadMask:true,

    // translations
    snippets: {
        column: {
            number: '{s name="export/grid/column/number" namespace="backend/Lengow/translation"}{/s}',
            name: '{s name="export/grid/column/name" namespace="backend/Lengow/translation"}{/s}',
            supplier: '{s name="export/grid/column/supplier" namespace="backend/Lengow/translation"}{/s}',
            active: '{s name="export/grid/column/active" namespace="backend/Lengow/translation"}{/s}',
            price: '{s name="export/grid/column/price" namespace="backend/Lengow/translation"}{/s}',
            vat: '{s name="export/grid/column/tax" namespace="backend/Lengow/translation"}{/s}',
            stock: '{s name="export/grid/column/stock" namespace="backend/Lengow/translation"}{/s}',
            lengowStatus: '<b>{s name="export/grid/column/export" namespace="backend/Lengow/translation"}{/s}</b>'
        },
        checkbox: {
            edit_all: '{s name="export/grid/checkbox/edit_all" namespace="backend/Lengow/translation"}{/s}'
        },
        line: {
            add: '{s name="export/grid/line/add" namespace="backend/Lengow/translation"}{/s}',
            remove: '{s name="export/grid/line/remove" namespace="backend/Lengow/translation"}{/s}',
            edit: '{s name="export/grid/line/edit" namespace="backend/Lengow/translation"}{/s}'
        },
        button: {
            add: '{s name="export/grid/button/add" namespace="backend/Lengow/translation"}{/s}',
            remove: '{s name="export/grid/button/remove" namespace="backend/Lengow/translation"}{/s}'
        },
        label: {
            counter: {
                count: '{s name="export/grid/label/counter/count" namespace="backend/Lengow/translation"}{/s}',
                total: '{s name="export/grid/label/counter/total" namespace="backend/Lengow/translation"}{/s}'
            }
        },
        search: {
            empty: '{s name="export/grid/search/empty" namespace="backend/Lengow/translation"}{/s}'
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
     * @return Ext.selection.CheckboxModel grid selection model
     */
     getGridSelModel: function () {
        var me = this;

        return Ext.create('Ext.selection.CheckboxModel', {
            listeners:{
                // unlocks the delete button if the user has checked at least one checkbox
                selectionchange: function (sm, selections) {
                    var status = selections.length === 0;
                    me.publishProductsBtn.setVisible(!status);
                    me.unpublishProductsBtn.setVisible(!status);

                    // if mass selection, display combobox to apply action on all articles
                    if (sm.selectionMode === 'MULTI') {
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
                header: me.snippets.column.number,
                dataIndex: 'number',
                flex: 1
            }, {
                header: me.snippets.column.name,
                dataIndex: 'name',
                flex : 2
            }, {
                header: me.snippets.column.supplier,
                dataIndex: 'supplier',
                flex: 1
            },
            this.getActiveColumn('status', me.snippets.column.active),
            {
                header: me.snippets.column.price,
                dataIndex: 'price',
                xtype: 'numbercolumn',
                align: 'right',
                flex: 1
            }, {
                header: me.snippets.column.vat,
                dataIndex: 'vat',
                align: 'right',
                flex: 1
            }, {
                header: me.snippets.column.stock,
                dataIndex: 'inStock',
                align: 'right',
                flex: 1
            },
            this.getActiveColumn('lengowActive', me.snippets.column.lengowStatus),
            this.getActionColumn()
        ];
        return columns;
    },

    /**
     * Create a checkbox (v4)/enabled-disabled form(v5) column form
     * @param name Field key of the Article model
     * @param header Header to display for this column
     */
     getActiveColumn: function(name, header) {
        var me = this,
        items = [],
        lengowColumn = name === 'lengowActive';

        items.push({
            handler: function(grid, rowIndex, colIndex, item, eOpts, record) {
                // if click on include in export column
                if (lengowColumn) {
                    // get the record and change lengow status for the product
                    var attributeId = record.raw['attributeId'],
                    categoryId = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id'),
                    status = !record.get('lengowActive');

                    me.fireEvent('setStatusInLengow', Ext.encode([attributeId]), status, categoryId);
                    me.updateCounter();
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
            dataIndex: name,
            header: header,
            align: 'center',
            items: items,
            flex: 1,
            renderer : function(value, metadata, record) {
                // generate tooltip enable/disable article for Lengow status
                if (lengowColumn) {
                    var status = record.get('lengowActive'),
                    tooltip = status ? me.snippets.line.remove : me.snippets.line.add;
                    metadata.tdAttr = 'data-qtip="' + tooltip + '"';
                }

                return '';
            }
        };
    },

    /**
     * Get column to edit article
     */
    getActionColumn: function() {
        var me = this,
            items = [];

        items.push({
            iconCls:'sprite-pencil',
            action:'seeProduct',
            tooltip: me.snippets.line.edit,
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                // shortcut to edit article
                Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.Article',
                    action: 'detail',
                    params: {
                        articleId: record.raw.articleId
                    }
                });
            }
        });

        return {
            xtype: 'actioncolumn',
            width: 40,
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
     * Listener to add/remove from export buttons
     * @param publishButton boolean True if adding products to export
     */
    exportButtonHandler: function(publishButton) {
        var me = this,
        selectionModel = me.getSelectionModel(),
        records = selectionModel.getSelection(),
        // get selected category in the tree
        categoryId = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id'),
        attributeIds = [],
        checkbox = Ext.getCmp('editAll'),
        ids = null;

        // enable mask on main container while the process is not finished
        Ext.getCmp('lengowExportTab').getEl().mask();

        // if select all products checkbox is not checked, get articles ids
        if (!checkbox.getValue()) {
            Ext.each(records, function(record) {
                attributeIds.push(record.raw['attributeId']);
            });

            ids = Ext.encode(attributeIds);
        }

        me.fireEvent('setStatusInLengow', ids, publishButton, categoryId);
    },

    /**
     * Update counter - display number of articles exported in Lengow
     */
    updateCounter: function() {
        var me = this,
        store = me.store;
        store.load({
            scope: this,
            callback: function(records, operation) {
                var data = Ext.decode(operation.response.responseText),
                    total = data['nbProductsAvailable'],
                    lengowProducts = data['nbExportedProducts'];

                var label = '<span id="products-exported">' + lengowProducts + '</span> ' 
                        + me.snippets.label.counter.count + ' ' +
                        '<span id="total-products">' + total + '</span> '
                        + me.snippets.label.counter.total + '. ',
                    counter = Ext.getCmp('productCounter');
                // update counter
                counter.el.update(label);
                var labelPanel = Ext.getCmp('topPanel');
                // make sure the label fits well in the panel
                labelPanel.doLayout();
            }
        });
    },

    /**
     * Initialize checkboxes
     * Get values in lengow settings
     */
    initConfigCheckboxes: function() {
        var me = this,
            selectedShop = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id'),
            configName = ['lengowExportSelectionEnabled'];

        me.fireEvent('getConfigValue', configName, selectedShop);
    },

    /**
     * Creates the grid toolbar
     * @return [Ext.toolbar.Toolbar] grid toolbar
     */
     getToolbar: function() {
        var me = this;

        // publish button - add mass selection to export
        me.publishProductsBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-plus-circle',
            text: me.snippets.button.add,
            hidden: true,
            margins: '5 0 0 0',
            handler: function() {
                me.exportButtonHandler(true);
            }
        });

        // un-publish button - remove mass selection from export
        me.unpublishProductsBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-minus-circle',
            margins: '5 0 0 0',
            text: me.snippets.button.remove,
            hidden: true,
            handler: function() {
                me.exportButtonHandler(false);
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
                me.publishProductsBtn,
                me.unpublishProductsBtn,
                { // apply action on all articles in the shop
                    xtype: 'checkboxfield',
                    margins: '5 0 0 0',
                    boxLabel: me.snippets.checkbox.edit_all,
                    hidden: true,
                    id: 'editAll'
                },
                {
                    xtype: 'tbfill'
                },
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
    }
});
//{/block}