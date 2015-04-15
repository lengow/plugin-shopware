//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/imports"}
Ext.define('Shopware.apps.Lengow.view.main.Imports', {

    extend:'Ext.grid.Panel',

    alias:'widget.lengow-main-imports',

    border: 0,
    autoScroll: true,

    /**
     * Sets up the ui component
     *
     * @return void
     */
    initComponent: function() {
        var me = this;
        me.registerEvents();

        me.dockedItems = [];
        me.store = me.ordersStore;
        me.columns = me.getColumns();
        me.toolbar = me.getToolbar();
        me.dockedItems.push(me.toolbar);
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

        var columns = [
            {
                header: 'ID',
                dataIndex: 'id',
                flex: 1
            },{
                header: 'Order date',
                dataIndex: 'orderDate',
                flex: 3,
                renderer:  me.modifiedCreated
            },{
                header: 'ID Lengow',
                dataIndex: 'idOrderLengow',
                flex: 4
            },{
                header: 'ID Flux',
                dataIndex: 'idFlux',
                flex: 1
            },{
                header: 'Price',
                dataIndex: 'totalPaid',
                flex: 2
            },{
                header: 'Marketplace',
                dataIndex: 'marketplace',
                flex: 3
            },{
                header: 'Carrier',
                dataIndex: 'carrier',
                flex: 2
            },{
                header: 'Carrier Method',
                dataIndex: 'carrierMethod',
                flex: 2
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
            action : 'searchImport',
            width : 170,
            enableKeyEvents : true,
            emptyText : 'search...',
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
            iconCls: 'sprite-plus-circle',
            text: 'Manual Import',
            action: 'manualImport'
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
       return Ext.util.Format.date(record.get('orderDate')) + ' ' + Ext.util.Format.date(record.get('orderDate'), 'H:i:s');
    }

});
//{/block}