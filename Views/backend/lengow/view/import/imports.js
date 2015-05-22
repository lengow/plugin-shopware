//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/imports"}
Ext.define('Shopware.apps.Lengow.view.import.Imports', {

    extend: 'Ext.container.Container',

    alias: 'widget.lengow-import-imports',

    initComponent: function() {
        var me = this;

        me.items = [
            {
                xtype: 'lengow-import-grid',
                ordersStore: me.ordersStore,
                region: 'center'
            },{
                xtype: 'lengow-import-panel',
                region: 'south'
            }
        ];

        me.callParent(arguments);
    }

});
//{/block}