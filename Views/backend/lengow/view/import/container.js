//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/container"}
Ext.define('Shopware.apps.Lengow.view.import.Container', {
    extend: 'Ext.container.Container',
    alias: 'widget.lengow-import-container',
    renderTo: Ext.getBody(),

    // Translations
    snippets: {
        button: '{s name="order/panel/button" namespace="backend/Lengow/translation"}{/s}'
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
        me.fireEvent('initImportPanels');
        me.callParent(arguments);
    },

    getFirstLine: function() {
        var me = this;
        return {
            xtype: 'container',
            layout: {
                type: 'hbox'
            },
            items: [
                {
                    xtype: 'container',
                    padding: '5',
                    html: Ext.String.format(me.snippets.button)
                },
                {
                    xtype: 'container',
                    padding: '5',
                    html: "<a href='#' id='importOrders' class='lengow_import_orders'></a>",
                    listeners: {
                        render: function(component){
                            // On click, import orders
                            component.getEl().on('click', function(){
                                me.fireEvent('launchImportProcess');
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
            left: '10',
            items: [
                {
                    xtype: 'container',
                    id: 'nb_order_in_error',
                    margin: '3'
                }, {
                    xtype: 'container',
                    id: 'nb_order_to_be_sent',
                    margin: '3'
                }, {
                    xtype: 'container',
                    id: 'last_import',
                    margin: '3'
                }, {
                    xtype: 'container',
                    id: 'mail_report',
                    margin: '3',
                    listeners: {
                        render: function(component){
                            // On click, see configuration
                            component.getEl().on('click', function(){
                                Shopware.app.Application.addSubApplication({
                                    name: 'Shopware.apps.Config'
                                });
                            });
                        }
                    }
                }, { // Display import error messages
                    xtype: 'panel',
                    id: 'importStatusPanel',
                    border: false,
                    align: 'center'
                }
            ]
        }
    }

});
//{/block}