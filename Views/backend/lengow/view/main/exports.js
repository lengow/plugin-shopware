//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/exports"}
Ext.define('Shopware.apps.Lengow.view.main.Exports', {

    extend:'Ext.grid.Panel',

    alias:'widget.lengow-main-exports',

    border: 0,
    autoScroll: true,

    /**
     * Sets up the ui component
     *
     * @return void
     */
    initComponent: function() {
        var me = this;
        me.store = me.articleStore;
        me.columns = me.getColumns();
        me.bbar = me.createPagingToolbar();
        me.callParent(arguments);
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
     *  Creates the columns
     */
    getColumns: function(){
        var me = this;

        var columns = [
            {
                header: 'Product name',
                dataIndex: 'name',
                flex: 1
            }
        ];
        return columns;
    }

});
//{/block}