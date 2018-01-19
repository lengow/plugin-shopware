//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/panel"}
Ext.define('Shopware.apps.Lengow.view.import.Panel', {
    extend: 'Ext.form.Panel',
    alias:  'widget.lengow-import-panel',

    /**
     * Contains all snippets for the view component
     * @object
     */
    snippets:{
        details: {
            title: '{s name="order/details/title" namespace="backend/Lengow/translation"}{/s}',
            no_order_selected: '{s name="order/details/no_order_selected" namespace="backend/Lengow/translation"}{/s}',
            marketplace_sku: '{s name="order/grid/column/marketplace_sku" namespace="backend/Lengow/translation"}{/s}',
            marketplace_label: '{s name="order/grid/column/marketplace" namespace="backend/Lengow/translation"}{/s}',
            order_lengow_state: '{s name="order/grid/column/lengow_status" namespace="backend/Lengow/translation"}{/s}',
            delivery_address_id: '{s name="order/details/delivery_address_id" namespace="backend/Lengow/translation"}{/s}',
            currency: '{s name="order/details/currency" namespace="backend/Lengow/translation"}{/s}',
            total_paid: '{s name="order/grid/column/total_paid" namespace="backend/Lengow/translation"}{/s}',
            commission: '{s name="order/details/commission" namespace="backend/Lengow/translation"}{/s}',
            customer_name: '{s name="order/grid/column/customer_name" namespace="backend/Lengow/translation"}{/s}',
            customer_email: '{s name="order/details/customer_email" namespace="backend/Lengow/translation"}{/s}',
            carrier: '{s name="order/details/carrier" namespace="backend/Lengow/translation"}{/s}',
            carrier_method: '{s name="order/details/carrier_method" namespace="backend/Lengow/translation"}{/s}',
            carrier_tracking: '{s name="order/details/carrier_tracking" namespace="backend/Lengow/translation"}{/s}',
            carrier_id_relay: '{s name="order/details/carrier_id_relay" namespace="backend/Lengow/translation"}{/s}',
            sent_by_mkp: '{s name="order/details/sent_by_mkp" namespace="backend/Lengow/translation"}{/s}',
            order_date: '{s name="order/grid/column/order_date" namespace="backend/Lengow/translation"}{/s}',
            created_at: '{s name="order/details/created_at" namespace="backend/Lengow/translation"}{/s}',
            message: '{s name="order/details/message" namespace="backend/Lengow/translation"}{/s}',
            extra: '{s name="order/details/extra" namespace="backend/Lengow/translation"}{/s}',
            say_yes: '{s name="order/details/say_yes" namespace="backend/Lengow/translation"}{/s}',
            say_no: '{s name="order/details/say_no" namespace="backend/Lengow/translation"}{/s}'
        },
        buttons: {
            action_ship: '{s name="order/buttons/action_ship" namespace="backend/Lengow/translation"}{/s}',
            action_cancel: '{s name="order/buttons/action_cancel" namespace="backend/Lengow/translation"}{/s}',
            synchronize: '{s name="order/buttons/synchronize" namespace="backend/Lengow/translation"}{/s}',
            reimport: '{s name="order/buttons/reimport" namespace="backend/Lengow/translation"}{/s}'
        },
        status: {
            accepted: '{s name="order/grid/status_accepted" namespace="backend/Lengow/translation"}{/s}',
            waiting_shipment: '{s name="order/grid/status_waiting_shipment" namespace="backend/Lengow/translation"}{/s}',
            shipped: '{s name="order/grid/status_shipped" namespace="backend/Lengow/translation"}{/s}',
            closed: '{s name="order/grid/status_closed" namespace="backend/Lengow/translation"}{/s}',
            canceled: '{s name="order/grid/status_canceled" namespace="backend/Lengow/translation"}{/s}'
        }
    },

    maxWidth: 400,

    bodyPadding: 10,

    layout: 'anchor',

    defaults: {
        anchor: '100%'
    },

    margin: '10 0',

    initComponent: function() {
        var me = this;
        me.title = me.snippets.details.title;
        me.items = [
            me.createSummaryElementContainer(),
            me.createDetailElementContainer(),
            me.createToolbarButton()
        ];
        me.callParent(arguments);
    },

    createSummaryElementContainer: function() {
        var me = this;
        return Ext.create('Ext.container.Container', {
            id: 'lgw-summary-element',
            padding: '10 0 20 0',
            items: [
                {
                    xtype: 'label',
                    html: "<span id='lgw-summary-text'>" + me.snippets.details.no_order_selected + "</span>"
                }
            ]
        });
    },

    createDetailElementContainer: function() {
        var me = this;
        return Ext.create('Ext.container.Container', {
            id: 'lgw-details-element',
            hidden: true,
            defaults: {
                xtype: 'displayfield',
                labelSeparator: ' ',
                labelAlign: 'top',
                labelCls: 'lgw-details-label',
                fieldCls: 'lgw-details-field'
            },
            items: me.createDetailElements()
        });
    },

    createDetailElements: function() {
        var me = this,
            fields;
        fields = [
            {
                name: 'marketplaceSku',
                fieldLabel: me.snippets.details.marketplace_sku
            },
            {
                name: 'marketplaceLabel',
                fieldLabel: me.snippets.details.marketplace_label
            },
            {
                name: 'deliveryAddressId',
                fieldLabel: me.snippets.details.delivery_address_id
            },
            {
                name: 'orderLengowState',
                fieldLabel: me.snippets.details.order_lengow_state,
                renderer : function(value, metadata, record) {
                    return me.snippets.status[value];
                }
            },
            {
                name: 'totalPaid',
                fieldLabel: me.snippets.details.total_paid
            },
            {
                name: 'commission',
                fieldLabel: me.snippets.details.commission
            },
            {
                name: 'currency',
                fieldLabel: me.snippets.details.currency
            },
            {
                name: 'customerName',
                fieldLabel: me.snippets.details.customer_name
            },
            {
                name: 'customerEmail',
                fieldLabel: me.snippets.details.customer_email
            },
            {
                name: 'carrier',
                fieldLabel: me.snippets.details.carrier
            },
            {
                name: 'carrierMethod',
                fieldLabel: me.snippets.details.carrier_method
            },
            {
                name: 'carrierTracking',
                fieldLabel: me.snippets.details.carrier_tracking
            },
            {
                name: 'carrierIdRelay',
                fieldLabel: me.snippets.details.carrier_id_relay
            },
            {
                name: 'sentByMarketplace',
                fieldLabel: me.snippets.details.sent_by_mkp,
                renderer : function(value, metadata, record) {
                    if (value === 'true') {
                        value = me.snippets.details.say_yes;
                    } else if (value === 'false') {
                        value = me.snippets.details.say_no;
                    }
                    return value;
                }
            },
            {
                name: 'orderDate',
                fieldLabel: me.snippets.details.order_date
            },
            {
                name: 'createdAt',
                fieldLabel: me.snippets.details.created_at
            },
            {
                name: 'message',
                fieldLabel: me.snippets.details.message
            },
            {
                name: 'extra',
                xtype: 'textarea',
                width: 360,
                height: 150,
                fieldLabel: me.snippets.details.extra
            }
        ];
        return fields;
    },

    createToolbarButton: function() {
        var me = this;

        return Ext.create('Ext.container.Container', {
            margin: '20 0',
            id: 'lgw-toolbar-buttons',
            layout: 'column',
            hidden: true,
            items: [
                me.getResendButton(),
                me.getActionButton()
            ]
        });
    },

    getResendButton: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            id: 'lgw-resend-buttons',
            margin: '0 0 10 0',
            items: [
                {
                    xtype: 'button',
                    id: 'lgw-send-ship-action',
                    name: me.snippets.buttons.action_ship,
                    text: Ext.String.capitalize(me.snippets.buttons.action_ship),
                    cls: 'primary',
                    handler: function() {
                        me.fireEvent('reSendAction', 'ship');
                    }
                },
                {
                    xtype: 'button',
                    id: 'lgw-send-cancel-action',
                    name: me.snippets.buttons.action_cancel,
                    text: Ext.String.capitalize(me.snippets.buttons.action_cancel),
                    cls: 'primary',
                    handler: function() {
                        me.fireEvent('reSendAction', 'cancel');
                    }
                }
            ]
        });
    },

    getActionButton: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            id: 'lgw-action-buttons',
            items: [
                {
                    xtype: 'button',
                    id: 'lgw-synchronize-order',
                    name: me.snippets.buttons.synchronize,
                    text: Ext.String.capitalize(me.snippets.buttons.synchronize),
                    cls: 'primary',
                    handler: function() {
                        me.fireEvent('synchronize');
                    }
                },
                {
                    xtype: 'button',
                    id: 'lgw-reimport-order',
                    name: me.snippets.buttons.reimport,
                    text: Ext.String.capitalize(me.snippets.buttons.reimport),
                    cls: 'primary',
                    handler: function() {
                        me.fireEvent('cancelAndReImport');
                    }
                }
            ]
        });
    }
});
//{/block}