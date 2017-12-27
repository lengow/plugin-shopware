//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/container"}
Ext.define('Shopware.apps.Lengow.view.import.Container', {
    extend: 'Ext.container.Container',
    alias: 'widget.lengow-import-container',
    renderTo: Ext.getBody(),

    // Translations
    snippets: {
        button: '{s name="order/panel/button" namespace="backend/Lengow/translation"}{/s}',
        order_error: '{s name="order/panel/order_error" namespace="backend/Lengow/translation"}{/s}',
        last_import: '{s name="order/panel/last_import" namespace="backend/Lengow/translation"}{/s}',
        to_be_sent: '{s name="order/panel/to_be_sent" namespace="backend/Lengow/translation"}{/s}',
        mail_report: '{s name="order/panel/mail_report" namespace="backend/Lengow/translation"}{/s}'
    },

    /**
     * Main controller
     */
    configure: function() {
        return {
            controller: 'Main'
        };
    },

    /**
     * Init components used by the container
     */
    initComponent: function() {
        var me = this;

        me.items = [
            {
                xtype: 'panel',
                region: 'center',
                id: 'topPanelImport',
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                items: [
                    me.getFirstLine(),
                    me.getSecondLine(),
                    Ext.create('Shopware.apps.Lengow.view.import.Grid', {
                        id: 'importGrid',
                        importStore: me.importStore,
                        flex: 1,
                        autoScroll : true,
                        style: 'border: none',
                        bodyStyle: 'background:#fff;'
                    })
                ]
            }
        ];
        me.onInitImportPanels();
        me.callParent(arguments);
    },

    /**
     * Init/update import window labels (description and last synchronization date)
     */
    onInitImportPanels: function () {
        var me = this;
        Ext.Ajax.request({
            url: '{url controller="LengowImport" action="getPanelContents2"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var data = Ext.decode(response.responseText)['data'];
                Ext.getCmp('nb_order_in_error').update(
                    '<p>' + Ext.String.format(me.snippets.order_error, data['nb_order_in_error']) + '</p>'
                );
                Ext.getCmp('nb_order_to_be_sent').update(
                    '<p>' + Ext.String.format(me.snippets.to_be_sent,  data['nb_order_to_be_sent']) + '</p>'
                    );
                Ext.getCmp('last_import').update(
                    '<p>' + Ext.String.format(me.snippets.last_import, data['last_import']) + '</p>'
                    );
                Ext.getCmp('mail_report').update(
                    '<p>' + Ext.String.format(me.snippets.mail_report, data['mail_report'], '#') + '</p>'
                    );
            }
        });
    },

    getFirstLine: function() {
        var me = this;
        return {
            xtype: 'container',
            layout: {
                type: 'hbox'
            },
            margins: '5',
            items: [
                {
                    xtype: 'label',
                    margins: '5',
                    html: Ext.String.format(me.snippets.button)
                },
                {
                    xtype: 'label',
                    margins: '5',
                    html: "<a href='#' id='importOrders' class='lengow_import_orders'></a>",
                    listeners: {
                        render: function(component){
                            // On click, import orders
                            component.getEl().on('click', function(){
                                //TODO
                                // me.fireEvent('getFeed', selectedShop);
                            });
                        }
                    }
                }
            ]
        };
    },

    getSecondLine: function() {
        var me = this;

        return {
            xtype: 'container',
            items: [
                {
                    //TODO
                    xtype: 'label',
                    id: 'nb_order_in_error',
                    margins: '3',
                    // html: '<p>' + Ext.String.format(me.snippets.order_error, '123') + '</p>'
                },
                {
                    //TODO
                    xtype: 'label',
                    id: 'nb_order_to_be_sent',
                    margins: '3',
                    // html: '<p>' + Ext.String.format(me.snippets.to_be_sent, '123') + '</p>'
                },
                {
                    //TODO
                    xtype: 'label',
                    id: 'last_import',
                    margins: '3',
                    // html: '<p>' + Ext.String.format(me.snippets.last_import, '19/11/1119') + '</p>'
                },
                {
                    //TODO
                    xtype: 'label',
                    id: 'mail_report',
                    margins: '3',
                    // html: '<p>' + Ext.String.format(me.snippets.mail_report, 'plop@machin.com', '#') + '</p>',
                    listeners: {
                        render: function(component){
                            // On click, see logs
                            //TODO
                            component.getEl().on('click', function(){
                                // me.fireEvent('getFeed', selectedShop);
                            });
                        }
                    }
                }
            ]
        }
    }

});
//{/block}