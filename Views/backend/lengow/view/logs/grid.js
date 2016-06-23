Ext.define('Shopware.apps.Lengow.view.logs.Grid', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.lengow-log-grid',

    configure: function() {
        var me = this;

        return {
            addButton: false,
            deleteButton: false
        };
    },

    initComponent: function() {
        var me = this;
        me.callParent(arguments);
    }
});
