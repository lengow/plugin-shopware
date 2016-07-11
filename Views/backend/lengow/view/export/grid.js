//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/grid"}
Ext.define('Shopware.apps.Lengow.view.export.Grid', {
    extend: 'Ext.grid.Panel',
    alias:  'widget.product-listing-grid',

    // Translations
    snippets: {
        column: {
            number: '{s name="export/grid/column/number" namespace="backend/Lengow/translation"}{/s}',
            name: '{s name="export/grid/column/name" namespace="backend/Lengow/translation"}{/s}',
            supplier: '{s name="export/grid/column/supplier" namespace="backend/Lengow/translation"}{/s}',
            active: '{s name="export/grid/column/active" namespace="backend/Lengow/translation"}{/s}',
            price: '{s name="export/grid/column/price" namespace="backend/Lengow/translation"}{/s}',
            vat: '{s name="export/grid/column/tax" namespace="backend/Lengow/translation"}{/s}',
            stock: '{s name="export/grid/column/stock" namespace="backend/Lengow/translation"}{/s}',
            lengowStatus: '{s name="export/grid/column/export" namespace="backend/Lengow/translation"}{/s}'
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
            },
            shop: {
                synchronized: '{s name="export/grid/label/status/synchronized" namespace="backend/Lengow/translation"}{/s}',
                not_synchronized: '{s name="export/grid/label/status/not_synchronized" namespace="backend/Lengow/translation"}{/s}'
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
            flex : 1
        }, {
            header: me.snippets.column.supplier,
            dataIndex: 'supplier',
            flex: 2
        }, 
        this.getActiveColumn('status', me.snippets.column.active), 
        {
            header: me.snippets.column.price,
            dataIndex: 'price',
            xtype: 'numbercolumn'
        }, { 
            header: me.snippets.column.vat,
            dataIndex: 'vat',
            width: 70
        }, {
            header: me.snippets.column.stock,
            dataIndex: 'inStock'
        },
        this.getActiveColumn('lengowActive', me.snippets.column.lengowStatus),
        this.getActionColumn()
        ];
        return columns;
    }, 

    /**
     * Create a checkbock (v4)/enabled-disabled form(v5) column form
     * @param name Field key of the Article model
     * @param header Header to display for this column
     */
     getActiveColumn: function(name, header) {
        var me = this,
        items = [],
        lengowColumn = name == 'lengowActive' ? true : false;

        items.push({
            handler: function(grid, rowIndex, colIndex, item, eOpts, record) {
                if (lengowColumn) {
                    var attributeId = record.raw['attributeId']
                    categoryId = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id'),
                    status = !record.get('lengowActive');
                    me.fireEvent('setStatusInLengow', Ext.encode([attributeId]), status, categoryId);
                    var counter = Ext.get('products-exported').dom.innerHTML;

                    if (status == 'true') {
                        Ext.get('products-exported').dom.innerHTML = parseInt(counter) + 1;
                    } else {
                        Ext.get('products-exported').dom.innerHTML = parseInt(counter) - 1;
                    }
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
                // Generate tooltip enable/disable article for Lengow status
                if (lengowColumn) {
                    var status = record.get('lengowActive'),
                    tooltip = status ? me.snippets.line.remove : me.snippets.line.add;
                    metadata.tdAttr = 'data-qtip="' + tooltip + '"';
                }

                return value;
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
        store = me.store,
        buttons = [];

        // Publish button - Add mass selection to export
        me.publishProductsBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-plus-circle',
            text: me.snippets.button.add,
            margins: '15 0 0 3',
            disabled: true,
            handler: function() {
                var selectionModel = me.getSelectionModel(),
                records = selectionModel.getSelection(),
                categoryId = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id')
                attributeIds = [],
                variance = 0;

                Ext.each(records, function(record) {
                    attributeIds.push(record.raw['attributeId']);
                    // If record status has changed, register the occurence
                    // to change counter value
                    if (record.get('lengowActive') == false) {
                        variance++;
                    }
                });

                me.fireEvent('setStatusInLengow', Ext.encode(attributeIds), true, categoryId);
                // Update counter
                var counter = Ext.get('products-exported').dom.innerHTML;
                Ext.get('products-exported').dom.innerHTML = parseInt(counter) + variance;
            }
        });

        // Unpublish button - Remove mass selection from export
        me.unpublishProductsBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-minus-circle',
            text: me.snippets.button.remove,
            margins: '15 0 0 5',
            disabled: true,
            handler: function() {
                var selectionModel = me.getSelectionModel(),
                records = selectionModel.getSelection(),
                categoryId = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('id'),
                attributeIds = [],
                variance = 0;

                Ext.each(records, function(record) {
                    attributeIds.push(record.raw['attributeId']);
                    // If record status has changed, register the occurence
                    // to change counter value
                    if (record.get('lengowActive') == true) {
                        variance--;
                    }
                });

                me.fireEvent('setStatusInLengow', Ext.encode(attributeIds), false, categoryId);
                // Update counter
                var counter = Ext.get('products-exported').dom.innerHTML;
                Ext.get('products-exported').dom.innerHTML = parseInt(counter) + variance;
            }
        });

        return [{
            xtype: 'panel',
            layout: 'anchor',
            width: '100%',
            border: false,
            margins: '15 0 0 0',
            items: [
            {
                xtype: 'container',
                layout: 'hbox',
                items: [
                {
                    id: 'shopStatus',
                    xtype: 'component',
                    autoEl: {
                        tag: 'a'
                    },
                    margins: '0 5 0 0'
                },
                {
                    xtype: 'tbfill'
                },
                {
                    xtype: 'label',
                    hidden: true,
                    cls: 'lengow_shop_status_label',
                    id: 'productCounter'
                },
                {
                    xtype: 'component',
                    autoEl: {
                        tag: 'a'
                    },
                    margins: '0 0 0 20'
                }
                ]
            },
            me.getSearchFieldComponent()
            ]
        }];
    },

    /**
     * Update counter - display number of articles exported in Lengow
     */
    setNumberOfProductExported: function() {
        var me = this,
        store = me.store;
        store.load({
            scope: this,
            callback: function(records, operation, success) {
                var data = Ext.decode(operation.response.responseText),
                    total = data['total'],
                    lengowProducts = data['nbLengowProducts'],
                    label = '<span>' + 
                                '<span id="products-exported">' + lengowProducts + '</span> ' 
                                    + me.snippets.label.counter.count + ' ' + 
                                    '<span id="total-products">' + total + '</span> ' 
                                    + me.snippets.label.counter.total + '. ' + 
                                    "<a href='#' id='downloadFeed' class='lengow_export_feed'></a></span>",
                    counter = Ext.getCmp('productCounter');
                counter.el.update(label);
                counter.show();

                var downloadFeedLink = Ext.get('downloadFeed');
                if (downloadFeedLink) {
                    downloadFeedLink.on('click', function() {
                        var selectedShop = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].get('text');
                        me.fireEvent('getFeed', selectedShop);
                    });
                }
            }
        });
    },

    /**
     * Set shop status label
     * If the shop is not synchronized, display a link
     */
    setLengowShopStatus: function() {
        var me = this,
        status = Ext.getCmp('shopTree').getSelectionModel().getSelection()[0].raw['lengowStatus'];

        var field = Ext.getCmp('shopStatus');

        if (status) {
            field.el.update("<span class='lengow_check_shop lengow_check_shop_sync'></span>" +
                        "<label class='lengow_shop_status_label'>" + 
                        "<span>" + me.snippets.label.shop.synchronized + "</span></label>");
        } else {
            var message = "<span class='lengow_check_shop lengow_check_shop_no_sync'></span>" +
                        "<label class='lengow_shop_status_label'>" + 
                        "<a id='synchronizeShop' href='#'><span>" + me.snippets.label.shop.not_synchronized + "</span></a></label>";
            field.el.update(message);

            // Listen to click on the link to synchronize the shop
            var link = Ext.get('synchronizeShop');
            if (link) {
                link.on('click', me.getSynchronizeIframe);
            }
        }
    },

    /**
     * Display synchronize shop iframe
     */
    getSynchronizeIframe: function () {
        Shopware.app.Application.addSubApplication({
            name: 'Shopware.apps.Iframe'
        });
    },

    /**
     * Search field grid component
     */
    getSearchFieldComponent: function() {
        var me = this;
        return {
            xtype: 'container',
            layout: 'hbox',
            items: [
            me.publishProductsBtn,
            me.unpublishProductsBtn,
            {
                xtype: 'tbfill'
            },
            {
                xtype : 'textfield',
                name : 'searchfield',
                margins: '15 0 0 3',
                action : 'search',
                cls: 'searchfield',
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
                    },
                }
            }
            ]
        };
    }
});
//{/block}