//{namespace name="backend/lengow/controller/order"}
//{block name="backend/order/controller/detail" append}
Ext.define('Shopware.apps.Lengow.controller.Order', {
    override: 'Shopware.apps.Order.controller.Detail',

    /**
     * Contains all snippets for the view component
     * @object
     */
    snippets:{
        details: {
            title: '{s name="order/details/title" namespace="backend/Lengow/translation"}{/s}',
            marketplace_sku: '{s name="order/details/marketplace_sku" namespace="backend/Lengow/translation"}{/s}',
            marketplace_label: '{s name="order/details/marketplace_label" namespace="backend/Lengow/translation"}{/s}',
            delivery_address_id: '{s name="order/details/delivery_address_id" namespace="backend/Lengow/translation"}{/s}',
            currency: '{s name="order/details/currency" namespace="backend/Lengow/translation"}{/s}',
            total_paid: '{s name="order/details/total_paid" namespace="backend/Lengow/translation"}{/s}',
            commission: '{s name="order/details/commission" namespace="backend/Lengow/translation"}{/s}',
            customer_name: '{s name="order/details/customer_name" namespace="backend/Lengow/translation"}{/s}',
            customer_email: '{s name="order/details/customer_email" namespace="backend/Lengow/translation"}{/s}',
            carrier: '{s name="order/details/carrier" namespace="backend/Lengow/translation"}{/s}',
            carrier_method: '{s name="order/details/carrier_method" namespace="backend/Lengow/translation"}{/s}',
            carrier_tracking: '{s name="order/details/carrier_tracking" namespace="backend/Lengow/translation"}{/s}',
            carrier_id_relay: '{s name="order/details/carrier_id_relay" namespace="backend/Lengow/translation"}{/s}',
            sent_by_mkp: '{s name="order/details/sent_by_mkp" namespace="backend/Lengow/translation"}{/s}',
            created_at: '{s name="order/details/created_at" namespace="backend/Lengow/translation"}{/s}',
            message: '{s name="order/details/message" namespace="backend/Lengow/translation"}{/s}',
            extra: '{s name="order/details/extra" namespace="backend/Lengow/translation"}{/s}',
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
        var shopwareVersion = '{Shopware::VERSION}';
        var url;
        if (shopwareVersion >= '5.2.0') {
            url = '{url controller=LengowOrder action=getOrderDetail}';
        } else {
            url = '{url controller=LengowOrderLegacy action=getOrderDetail}';
        }
        me.callParent(arguments);
        Ext.Ajax.request({
            url: url,
            method: 'POST',
            type: 'json',
            params: {
                orderId: record.get('id')
            },
            success: function(response) {
                var data = Ext.decode(response.responseText)['data'];
                var lengowTab = Ext.define('Shopware.apps.Lengow.view.order.LengowOrderTab', {
                    extend: 'Ext.container.Container',
                    alias: 'widget.lengow-order-panel',
                    padding: 10,
                    title: me.snippets.details.title,
                    autoScroll: true,

                    initComponent: function() {
                        var me = this;
                        me.items = [
                            me.createDetailsContainer()
                        ];
                        me.callParent(arguments);
                    },

                    createDetailsContainer: function() {
                        var me = this;
                        var item;
                        if (data.orderId) {
                            item = me.createInnerDetailContainer();
                        } else {
                            item = {
                                html: data,
                                border: 0
                            };
                        }
                        return Ext.create('Ext.form.Panel', {
                            bodyPadding: 10,
                            layout: 'anchor',
                            defaults: {
                                anchor: '100%'
                            },
                            margin: '10 0',
                            title: 'Lengow',
                            items: [
                                item
                            ]
                        });
                    },

                    createInnerDetailContainer: function() {
                        var me = this;

                        return Ext.create('Ext.container.Container', {
                            layout: 'column',
                            items: [
                                me.createDetailElementContainer(me.createDetailElements())
                            ]
                        });
                    },

                    createDetailElementContainer: function(items) {
                        return Ext.create('Ext.container.Container', {
                            columnWidth: 0.5,
                            defaults: {
                                xtype: 'displayfield',
                                labelWidth: 155
                            },
                            items: items
                        });
                    },

                    createDetailElements: function() {
                        var fields;
                        var sentByMkp = data.sentByMarketplace == true ? 'Yes' : 'No';
                        fields = [
                            { value: data.marketplaceSku, fieldLabel: me.snippets.details.marketplace_sku },
                            { value: data.marketplaceLabel, fieldLabel: me.snippets.details.marketplace_label },
                            { value: data.deliveryAddressId, fieldLabel: me.snippets.details.delivery_address_id },
                            { value: data.currency, fieldLabel: me.snippets.details.currency },
                            { value: data.totalPaid, fieldLabel: me.snippets.details.total_paid },
                            { value: data.commission, fieldLabel: me.snippets.details.commission },
                            { value: data.customerName, fieldLabel: me.snippets.details.customer_name },
                            { value: data.customerEmail, fieldLabel: me.snippets.details.customer_email },
                            { value: data.carrier, fieldLabel: me.snippets.details.carrier },
                            { value: data.carrierMethod, fieldLabel: me.snippets.details.carrier_method },
                            { value: data.carrierTracking, fieldLabel: me.snippets.details.carrier_tracking },
                            { value: data.carrierIdRelay, fieldLabel: me.snippets.details.carrier_id_relay },
                            { value: sentByMkp, fieldLabel: me.snippets.details.sent_by_mkp },
                            { value: data.createdAt, fieldLabel: me.snippets.details.created_at },
                            { value: data.message, xtype: 'textarea', width: 800, height: 50, fieldLabel: me.snippets.details.message },
                            { value: data.extra, xtype: 'textarea', width: 800, height: 200, fieldLabel: me.snippets.details.extra },
                        ];
                        return fields;
                    },

                });
                Ext.define('Shopware.apps.Lengow.view.order.Window', {
                    override: 'Shopware.apps.Order.view.detail.Window',

                    initComponent:function () {
                        var me = this;
                        me.callParent(arguments);
                    },

                    createTabPanel: function () {
                        var me = this;
                        var tabPanel = me.callParent(arguments);
                        Ext.each(tabPanel.items.items, function (tab) {
                            if (tab.id.indexOf('lengow', 0) === 0) {
                                tabPanel.remove(tab);
                            }
                        });
                        tabPanel.add(lengowTab);
                        tabPanel.doLayout();
                        return tabPanel;
                    }
                });
            }
        });
    },

});
//{/block}