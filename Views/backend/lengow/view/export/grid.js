//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/grid"}
Ext.define('Shopware.apps.Lengow.view.export.Grid', {

    extend: 'Ext.grid.Panel',

    alias: 'widget.lengow-export-grid',


    /**
     * Sets up the ui component
     *
     * @return void
     */
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
            'saveActiveProduct'
        );

        me.callParent(arguments);
    },

    createPlugins: function() {
        var me = this,
            rowEditor = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToEdit: 1,
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
     *
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
                header: 'Number',
                dataIndex: 'number',
                flex: 2
            }, {
                header: 'Product name',
                dataIndex: 'name',
                flex: 4
            }, {
                header: 'Supplier',
                dataIndex: 'supplier',
                flex: 3
            }, {
                header: 'Active',
                dataIndex: 'active',
                xtype: 'booleancolumn',
                width: 40,
                renderer: me.activeColumnRenderer
            }, {
                xtype: 'numbercolumn',
                header: 'Price',
                dataIndex: 'price',
                align: 'right',
                width: 55
            }, {
                xtype: 'numbercolumn',
                header: 'Tax',
                dataIndex: 'tax',
                flex: 1
            }, {
                header: 'Stock',
                dataIndex: 'inStock',
                flex: 1
            }, {
                header: 'Lengow Product',
                dataIndex: 'activeLengow',
                xtype: 'booleancolumn',
                width: 100,
                renderer: me.activeColumnRenderer,
                editor: {
                    width: 100,
                    xtype: 'checkbox',
                    uncheckedValue: false,
                    inputValue: true
                }
            }
        ];
        return columns;
    }, 

    /**
     * Creates the grid toolbar
     *
     * @return [Ext.toolbar.Toolbar] grid toolbar
     */
    getToolbar: function() {
        var me = this, buttons = [];

        me.publishProductsBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-plus-circle',
            text: 'Publish Lengow\'s products',
            disabled: true,
            handler: function() {
                var selectionModel = me.getSelectionModel(),
                    records = selectionModel.getSelection();
                me.fireEvent('publishProducts', this, records);
            }
        });
        buttons.push(me.publishProductsBtn);

        me.unpublishProductsBtn = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-minus-circle',
            text: 'Unpublish Lengow\'s products',
            disabled: true,
            handler: function() {
                var selectionModel = me.getSelectionModel(),
                    records = selectionModel.getSelection();
                me.fireEvent('unpublishProducts', this, records);
            }
        });
        buttons.push(me.unpublishProductsBtn);

        //creates the delete button to remove all selected esds in one request.
        me.exportProductsBtn = Ext.create('Ext.button.Button', {
            iconCls:'sprite-plus-circle',
            text: 'Export products',
            handler: function() {
                me.fireEvent('exportProducts');
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
            width: 170,
            cls: 'searchfield',
            enableKeyEvents: true,
            checkChangeBuffer: 500,
            emptyText: 'Search...',
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
     * a entry in the "number of orders"-combo box.
     *
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
    }

});
//{/block}