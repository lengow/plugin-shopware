//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/logs"}
Ext.define('Shopware.apps.Lengow.view.main.Logs', {

    extend: 'Ext.grid.Panel',

    alias:'widget.lengow-main-logs',

    border: 0,
    autoScroll: true,

    /**
     * Called when the component will be initialed.
     */
    initComponent: function() {
        var me = this;
        me.registerEvents();

        me.dockedItems = [];
        me.store = me.logsStore;
        me.selModel = me.getGridSelModel();
        me.columns = me.getColumns();
        me.toolbar = me.getToolbar();
        me.dockedItems.push(me.toolbar);

        // Add paging toolbar to the bottom of the grid panel
        me.bbar = me.createPagingToolbar();

        me.callParent(arguments);
    },

    /**
     * Registers additional component events.
     */
    registerEvents: function() {
        this.addEvents();
    },

    /**
     * Creates the selectionModel of the grid with a listener to enable the delete-button
     */
    getGridSelModel: function(){
        var selModel = Ext.create('Ext.selection.CheckboxModel',{
            listeners: {
                selectionchange: function(sm, selections){
                    var owner = this.view.ownerCt,
                            btn = owner.down('button[action=deleteSelectedLogs]');
                    //If no article is marked
                    if(btn){
                        btn.setDisabled(selections.length == 0);
                    }
                }
            }
        });
        return selModel;
    },

    /**
     * Creates the paging toolbar
     */
    createPagingToolbar: function() {
        var me = this,
            toolbar = Ext.create('Ext.toolbar.Paging', {
            store: me.store
        });

        return toolbar;
    },

    /**
     *  Creates the columns
     */
    getColumns: function(){
        var me = this;
        var buttons = new Array();

        buttons.push(Ext.create('Ext.button.Button', {
            iconCls: 'sprite-minus-circle',
            action: 'delete',
            cls: 'delete',
            tooltip: 'Delete Log',
            handler:function (view, rowIndex, colIndex, item) {
                me.fireEvent('deleteColumn', view, rowIndex,  item, colIndex);
            }
        }));

        var columns = [
            {
                header: '{s name=logs/column/idLog}ID{/s}',
                dataIndex: 'id',
                flex: 1
            },{
                header: '{s name=logs/column/created}Created{/s}',
                dataIndex: 'created',
                flex: 2,
                renderer: me.modifiedCreated
            }, {
                header: '{s name=logs/column/message}Message{/s}',
                dataIndex: 'message',
                flex: 5
            }, {
                xtype: 'actioncolumn',
                width: 30,
                items: buttons
            }
        ];

        return columns;
    },

    /**
     * Creates the toolbar with two buttons and a searchfield
     */
    getToolbar: function(){

        var searchField = Ext.create('Ext.form.field.Text',{
            name : 'searchfield',
            cls : 'searchfield',
            action : 'searchLogs',
            width : 170,
            enableKeyEvents : true,
            emptyText : '{s name=logs/topToolbar/searchLogs}Search...{/s}',
            listeners: {
                buffer: 500,
                keyup: function() {
                    if(this.getValue().length >= 3 || this.getValue().length<1) {
                        /**
                         * @param this Contains the searchfield
                         */
                        this.fireEvent('fieldchange', this);
                    }
                }
            }
        });

        searchField.addEvents('fieldchange');

        var items = [];
        
        items.push(Ext.create('Ext.button.Button',{
            iconCls: 'sprite-minus-circle',
            text: '{s name=logs/topToolbar/deleteSelectedLogs}Delele selected Logs{/s}',
            disabled: true,
            action: 'deleteSelectedLogs'
        }));
        
        items.push(Ext.create('Ext.button.Button',{
            iconCls: 'sprite-minus-circle',
            text: '{s name=logs/topToolbar/flushLogs}Flush Logs{/s}',
            action: 'flushLogs'
        }));

        items.push('->');
        items.push(searchField);
        items.push({
            xtype: 'tbspacer',
            width: 6
        });

        var toolbar = Ext.create('Ext.toolbar.Toolbar', {
            dock: 'top',
            ui: 'shopware-ui',
            padding: 5,
            items: items
        });
        return toolbar;
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