//{block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.Lengow.view.order.Window', {
    override: 'Shopware.apps.Order.view.detail.Window',

    getTabs: function() {
        var me = this,
            result = me.callParent();

        result.push(Ext.create('Shopware.apps.Lengow.view.order.MyOwnTab'));

        return result;
    }
});
//{/block}