//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/logs"}
Ext.define('Shopware.apps.Lengow.view.main.Logs', {

    extend: 'Ext.grid.Panel',

    alias:'widget.lengow-main-logs',

    border: 0,
    autoScroll: true,

    snippets: {
        column: {
            idLog:      '{s name=logs/column/id_log}ID{/s}',
            created:    '{s name=logs/column/created}Created{/s}',
            message:    '{s name=logs/column/message}Message{/s}'
        },
        topToolbar: {
            deleteSelectedLogs: '{s name=logs/topToolbar/delete_selected_logs}Delete selected Logs{/s}',
            flushLogs:          '{s name=logs/topToolbar/flush_logs}Flush Logs{/s}',
            searchLogs:         '{s name=logs/topToolbar/search_logs}Search...{/s}'
        }
    },

    initComponent: function() {
        var me = this;

        me.store = me.logsStore;
        me.selModel = me.getGridSelModel();
        me.columns = me.getColumns();
        me.tbar = me.getToolbar();
        me.bbar = me.createPagingToolbar();

        me.addEvents(
            'deleteSelectedLogs',
            'flushLogs',
            'deleteLog'
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
                // Unlocks the delete button if the user has checked at least one checkbox
                selectionchange: function (sm, selections) {
                    me.deleteSelectedLogsBtn.setDisabled(selections.length === 0);
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
            iconCls:'sprite-minus-circle-frame',
            action:'deleteLog',
            tooltip: 'Delete Log',
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                me.fireEvent('deleteLog', record);
            }
        });

        var columns = [
            {
                header: me.snippets.column.idLog,
                dataIndex: 'id',
                width: 60
            },{
                header: me.snippets.column.created,
                dataIndex: 'created',
                width: 150,
                renderer: me.modifiedCreated
            }, {
                header: me.snippets.column.message,
                dataIndex: 'message',
                flex: 1
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

        me.deleteSelectedLogsBtn = Ext.create('Ext.button.Button',{
            iconCls: 'sprite-minus-circle',
            text: me.snippets.topToolbar.deleteSelectedLogs,
            disabled: true,
            handler: function() {
                var selectionModel = me.getSelectionModel(),
                    records = selectionModel.getSelection();
                if (records.length > 0) {
                    me.fireEvent('deleteSelectedLogs', records);
                }
            }
        });
        buttons.push(me.deleteSelectedLogsBtn);

        me.flushLogsBtn = Ext.create('Ext.button.Button',{
            iconCls: 'sprite-minus-circle',
            text: me.snippets.topToolbar.flushLogs, 
            handler: function() {
                me.fireEvent('flushLogs');
            }
        });
        buttons.push(me.flushLogsBtn);

        buttons.push({
            xtype: 'tbfill'
        });

        buttons.push({
            xtype: 'textfield',
            name: 'searchfield',
            action: 'search',
            width: 170,
            cls: 'searchfield',
            enableKeyEvents: true,
            checkChangeBuffer: 500,
            emptyText: me.snippets.topToolbar.searchLogs,
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
       return Ext.util.Format.date(record.get('created')) + ' ' + Ext.util.Format.date(record.get('created'), 'H:i:s');
    }

});

//{/block}