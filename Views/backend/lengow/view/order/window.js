//{block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.Lengow.view.order.Window', {
    override: 'Shopware.apps.Order.view.detail.Window',

    createTabPanel: function() {
        var me = this;
        var tabPanel = me.callParent(arguments);
        tabPanel.insert(Ext.create('Shopware.apps.Lengow.view.order.LengowOrderTab'));
        return tabPanel;
    }
});
//{/block}