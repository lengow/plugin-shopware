//{block name="backend/order/controller/detail" append}
Ext.define('Shopware.apps.Lengow.controller.Order', {
    override: 'Shopware.apps.Order.controller.Detail',

    /**
     * Contains all snippets for the view component
     * @object
     */
    snippets:{
        details: {
            title: '{s name=order/details/lengow/title}Lengow details{/s}',
            currency: '{s name=order/details/lengow/currency}Currency{/s}',
            shop: '{s name=order/details/lengow/shop}Shop{/s}',
            language: '{s name=order/details/lengow/language}Language{/s}',
        }
    },

    init: function() {
        var me = this;

        me.control({
            'lengow-order-panel': {
                onShowDetail: me.onShowDetail
            }
        });
        me.callParent(arguments);
    },

    onShowDetail: function (record) {
        var me = this;
        me.callParent(arguments);
        Ext.Ajax.request({
            url: '{url controller=LengowOrder action=getOrderDetail}',
            method: 'POST',
            type: 'json',
            params: {
                orderId: record.get('id')
            },
            success: function(response) {
                var lengowPanel = Ext.widget('lengow-order-panel');
                var data = Ext.decode(response.responseText)['data'];
                var html;
                if (data.orderId) {
                    html = '<ul>' +
                        '<li>Order Id: ' + data.orderId +'</li>' +
                        '<li>Order Sku: ' + data.orderSku +'</li>' +
                        '<li>Marketplace Sku: ' + data.marketplaceSku +'</li>' +
                        '<li>Marketplace Name: ' + data.marketplaceName +'</li>' +
                        '</ul>';
                } else {
                    html = data;
                }
                lengowPanel.add(
                    Ext.create('Ext.Panel', {
                        title: me.snippets.details.title,
                        bodyPadding: 10,
                        height: 200,
                        html: html
                }));
                lengowPanel.doLayout();
            }
        });
    },

});
//{/block}