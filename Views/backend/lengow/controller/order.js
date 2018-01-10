//{namespace name="backend/lengow/controller/order"}
//{block name="backend/order/controller/detail" append}
Ext.define('Shopware.apps.Lengow.controller.Order', {
    override: 'Shopware.apps.Order.controller.Detail',

    /**
     * Contains all snippets for the view component
     * @object
     */
    snippet:{
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
            say_yes: '{s name="order/details/say_yes" namespace="backend/Lengow/translation"}{/s}',
            say_no: '{s name="order/details/say_no" namespace="backend/Lengow/translation"}{/s}',
            ship_confirmation_title: '{s name="order/details/ship_confirmation_title" namespace="backend/Lengow/translation"}{/s}',
            ship_confirmation_message: '{s name="order/details/ship_confirmation_message" namespace="backend/Lengow/translation"}{/s}',
            cancel_confirmation_title: '{s name="order/details/cancel_confirmation_title" namespace="backend/Lengow/translation"}{/s}',
            cancel_confirmation_message: '{s name="order/details/cancel_confirmation_message" namespace="backend/Lengow/translation"}{/s}',
            success_message: '{s name="order/details/success_message" namespace="backend/Lengow/translation"}{/s}',
            fail_message: '{s name="order/details/fail_message" namespace="backend/Lengow/translation"}{/s}',
            synchronize_confirmation_title: '{s name="order/details/synchronize_confirmation_title" namespace="backend/Lengow/translation"}{/s}',
            synchronize_confirmation_message: '{s name="order/details/synchronize_confirmation_message" namespace="backend/Lengow/translation"}{/s}',
            synchronize_success_message: '{s name="order/details/synchronize_success_message" namespace="backend/Lengow/translation"}{/s}',
            synchronize_fail_message: '{s name="order/details/synchronize_fail_message" namespace="backend/Lengow/translation"}{/s}',
            reimport_confirmation_title: '{s name="order/details/reimport_confirmation_title" namespace="backend/Lengow/translation"}{/s}',
            reimport_confirmation_message: '{s name="order/details/reimport_confirmation_message" namespace="backend/Lengow/translation"}{/s}',
            reimport_success_message: '{s name="order/details/reimport_success_message" namespace="backend/Lengow/translation"}{/s}',
            reimport_fail_message: '{s name="order/details/reimport_fail_message" namespace="backend/Lengow/translation"}{/s}'
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
                if (Ext.getCmp('lengow_order_tab') !== undefined) {
                    Ext.getCmp('lengow_order_tab').hide();
                }
                var lengowTab = Ext.define('Shopware.apps.Lengow.view.order.LengowOrderTab', {
                    extend: 'Ext.container.Container',
                    alias: 'widget.lengow-order-panel',
                    padding: 10,
                    title: me.snippet.details.title,
                    autoScroll: true,
                    initComponent: function() {
                        var me = this;
                        me.items = [
                            me.createDetailsContainer()
                        ];
                        if (data.canResendAction !== undefined) {
                            me.items.push(me.createToolbarButton(data));
                        }
                        me.callParent(arguments);
                    },
                    createDetailsContainer: function() {
                        var me = this;
                        var item;
                        if (data.orderId) {
                            item = [
                                me.createInnerDetailContainer()
                            ];
                        } else {
                            item = [{
                                html: data,
                                border: 0
                            }];
                        }
                        return Ext.create('Ext.form.Panel', {
                            bodyPadding: 10,
                            layout: 'anchor',
                            defaults: {
                                anchor: '100%'
                            },
                            margin: '10 0',
                            title: 'Lengow',
                            items: item
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
                        var sentByMkp = data.sentByMarketplace === true
                            ? me.snippet.details.say_yes
                            : me.snippet.details.say_no;
                        fields = [
                            { value: data.marketplaceSku, fieldLabel: me.snippet.details.marketplace_sku },
                            { value: data.marketplaceLabel, fieldLabel: me.snippet.details.marketplace_label },
                            { value: data.deliveryAddressId, fieldLabel: me.snippet.details.delivery_address_id },
                            { value: data.currency, fieldLabel: me.snippet.details.currency },
                            { value: data.totalPaid, fieldLabel: me.snippet.details.total_paid },
                            { value: data.commission, fieldLabel: me.snippet.details.commission },
                            { value: data.customerName, fieldLabel: me.snippet.details.customer_name },
                            { value: data.customerEmail, fieldLabel: me.snippet.details.customer_email },
                            { value: data.carrier, fieldLabel: me.snippet.details.carrier },
                            { value: data.carrierMethod, fieldLabel: me.snippet.details.carrier_method },
                            { value: data.carrierTracking, fieldLabel: me.snippet.details.carrier_tracking },
                            { value: data.carrierIdRelay, fieldLabel: me.snippet.details.carrier_id_relay },
                            { value: sentByMkp, fieldLabel: me.snippet.details.sent_by_mkp },
                            { value: data.createdAt, fieldLabel: me.snippet.details.created_at },
                            { value: data.message, xtype: 'textarea', width: 800, height: 30, fieldLabel: me.snippet.details.message },
                            { value: data.extra, xtype: 'textarea', width: 800, height: 200, fieldLabel: me.snippet.details.extra },
                        ];
                        return fields;
                    },
                    createActionButton: function(action) {
                        var buttonId = 'resend_lengow_'+ action +'_action_button';
                        return Ext.create('Ext.button.Button', {
                            id: buttonId,
                            cls: 'primary',
                            name: action,
                            text: Ext.String.capitalize(action),
                            handler: function () {
                                var confirmationTitle;
                                var confirmationMessage;
                                if (action === 'ship') {
                                    confirmationTitle = me.snippet.details.ship_confirmation_title;
                                    confirmationMessage = me.snippet.details.ship_confirmation_message;
                                } else {
                                    confirmationTitle = me.snippet.details.cancel_confirmation_title;
                                    confirmationMessage = me.snippet.details.cancel_confirmation_message;
                                }
                                Ext.MessageBox.confirm(
                                    confirmationTitle,
                                    confirmationMessage,
                                    function (response) {
                                        if (response !== 'yes') {
                                            return;
                                        }
                                        var loading = new Ext.LoadMask(Ext.getBody(), {
                                            hideModal: true
                                        });
                                        loading.show();
                                        Ext.getCmp(buttonId).disable();
                                        if (shopwareVersion >= '5.2.0') {
                                            url = '{url controller=LengowOrder action=getCallAction}';
                                        } else {
                                            url = '{url controller=LengowOrderLegacy action=getCallAction}';
                                        }
                                        Ext.Ajax.request({
                                            url: url,
                                            method: 'POST',
                                            type: 'json',
                                            params: {
                                                orderId: record.get('id'),
                                                actionName: action
                                            },
                                            success: function (response) {
                                                var success = Ext.decode(response.responseText)['data'],
                                                    lengowMessage;
                                                if (success) {
                                                    lengowMessage = me.snippet.details.success_message;
                                                } else {
                                                    lengowMessage = me.snippet.details.fail_message;
                                                }
                                                Ext.MessageBox.alert(confirmationTitle, lengowMessage);
                                                Ext.getCmp(buttonId).enable();
                                                loading.hide();
                                            }
                                        });
                                    }
                                );
                            }
                        });
                    },
                    createSynchronizeButton: function() {
                        var confirmationTitle = me.snippet.details.synchronize_confirmation_title,
                            confirmationMessage = me.snippet.details.synchronize_confirmation_message,
                            buttonId = 'synchronize_lengow_action_button';
                        return Ext.create('Ext.button.Button', {
                            id: buttonId,
                            cls: 'primary',
                            name: confirmationTitle,
                            text: Ext.String.capitalize(confirmationTitle),
                            handler: function () {
                                Ext.MessageBox.confirm(
                                    confirmationTitle,
                                    confirmationMessage,
                                    function (response) {
                                        if (response !== 'yes') {
                                            return;
                                        }
                                        var loading = new Ext.LoadMask(Ext.getBody(), {
                                            hideModal: true
                                        });
                                        loading.show();
                                        Ext.getCmp(buttonId).disable();
                                        if (shopwareVersion >= '5.2.0') {
                                            url = '{url controller=LengowOrder action=synchronize}';
                                        } else {
                                            url = '{url controller=LengowOrderLegacy action=synchronize}';
                                        }
                                        Ext.Ajax.request({
                                            url: url,
                                            method: 'POST',
                                            type: 'json',
                                            params: {
                                                orderId: record.get('id')
                                            },
                                            success: function (response) {
                                                var success = Ext.decode(response.responseText)['data'],
                                                    lengowMessage;
                                                if (success) {
                                                    lengowMessage = me.snippet.details.synchronize_success_message;
                                                } else {
                                                    lengowMessage = me.snippet.details.synchronize_fail_message;
                                                }
                                                Ext.MessageBox.alert(confirmationTitle, lengowMessage);
                                                Ext.getCmp(buttonId).enable();
                                                loading.hide();
                                            }
                                        });
                                    }
                                );
                            }
                        });
                    },
                    createCancelAndReImportButton: function() {
                        var confirmationTitle = me.snippet.details.reimport_confirmation_title,
                            confirmationMessage = me.snippet.details.reimport_confirmation_message,
                            buttonId = 'reimport_lengow_action_button';
                        return Ext.create('Ext.button.Button', {
                            id: buttonId,
                            cls: 'primary',
                            name: confirmationTitle,
                            text: Ext.String.capitalize(confirmationTitle),
                            handler: function () {
                                Ext.MessageBox.confirm(
                                    confirmationTitle,
                                    confirmationMessage,
                                    function (response) {
                                        if (response !== 'yes') {
                                            return;
                                        }
                                        var loading = new Ext.LoadMask(Ext.getBody(), {
                                            hideModal: true
                                        });
                                        loading.show();
                                        Ext.getCmp(buttonId).disable();
                                        if (shopwareVersion >= '5.2.0') {
                                            url = '{url controller=LengowOrder action=cancelAndReImport}';
                                        } else {
                                            url = '{url controller=LengowOrderLegacy action=cancelAndReImport}';
                                        }
                                        Ext.Ajax.request({
                                            url: url,
                                            method: 'POST',
                                            type: 'json',
                                            params: {
                                                orderId: record.get('id')
                                            },
                                            success: function (response) {
                                                var success = Ext.decode(response.responseText)['data'],
                                                    lengowMessage;
                                                if (success) {
                                                    lengowMessage = Ext.String.format(
                                                        me.snippet.details.reimport_success_message,
                                                        success.marketplace_sku,
                                                        success.order_sku
                                                    );
                                                } else {
                                                    lengowMessage = me.snippet.details.reimport_fail_message;
                                                }
                                                Ext.MessageBox.alert(confirmationTitle, lengowMessage);
                                                Ext.getCmp(buttonId).enable();
                                                loading.hide();
                                            }
                                        });
                                    }
                                );
                            }
                        });

                    },
                    createToolbarButton: function() {
                        var me = this;
                        return Ext.create('Ext.container.Container', {
                            width: '850px',
                            id: 'resend_lengow_action',
                            layout: 'column',
                            items: [
                                me.createActionButton('ship'),
                                me.createActionButton('cancel'),
                                me.createSynchronizeButton(),
                                me.createCancelAndReImportButton()
                            ]
                        });
                    }
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
            },
            fail: function (response) {
                console.log(response);
            }
        });
    }
});
//{/block}