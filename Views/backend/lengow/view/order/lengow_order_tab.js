// This tab will be shown in the customer module

Ext.define('Shopware.apps.Lengow.view.order.LengowOrderTab', {
    extend: 'Ext.container.Container',
    id:'lengow-order-panel',
    alias: 'widget.lengow-order-panel',
    padding: 10,
    title: 'Lengow',


    initComponent: function() {
        var me = this;

        me.callParent(arguments);
    }
});