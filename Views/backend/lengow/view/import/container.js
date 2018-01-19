//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/container"}
Ext.define('Shopware.apps.Lengow.view.import.Container', {
    extend: 'Ext.container.Container',
    alias: 'widget.lengow-import-container',
    renderTo: Ext.getBody(),

    // Translations
    snippets: {
        button: '{s name="order/panel/button_import" namespace="backend/Lengow/translation"}{/s}'
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
                xtype: 'container',
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                region: 'center',
                items: [
                    me.getImportHeader(),
                    Ext.create('Shopware.apps.Lengow.view.import.Grid', {
                        id: 'importGrid',
                        importStore: me.importStore,
                        flex: 1,
                        autoScroll : true,
                        region:'center',
                        style: 'border: none',
                        bodyStyle: 'background:#fff;'
                    })
                ]
            },
            {
                xtype: 'lengow-import-panel',
                collapsed: false,
                collapsible: true,
                autoScroll : true,
                flex: 1,
                region: 'east'
            }
        ];
        me.fireEvent('initImportPanels');
        me.callParent(arguments);
    },

    getImportHeader: function() {
        var me = this;
        return {
            xtype: 'container',
                layout: {
                    type: 'hbox'
            },
            margins: '20 7 7 7',
            items: [
                {
                    xtype: 'container',
                    width: 600,
                    items: [
                        {
                            xtype: 'panel',
                            id: 'nb_order_in_error',
                            margin: '3',
                            border: false
                        }, {
                            xtype: 'panel',
                            id: 'nb_order_to_be_sent',
                            margin: '3',
                            border: false
                        }, {
                            xtype: 'panel',
                            id: 'last_import',
                            margin: '3',
                            border: false
                        }, {
                            xtype: 'panel',
                            id: 'mail_report',
                            margin: '15 3 3 3',
                            border: false,
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
                        }
                    ]
                },
                {
                    xtype: 'tbfill'
                },
                {
                    xtype: 'label',
                    align: 'right',
                    margins: '10 30 0 0',
                    html: '<span class="lgw-btn-order">' + Ext.String.format(me.snippets.button) + "</span>",
                    listeners: {
                        render: function (component) {
                            // On click, import orders
                            component.getEl().on('click', function () {
                                me.fireEvent('launchImportProcess');
                            });
                        }
                    }
                }
            ]
        };
    }
});
//{/block}