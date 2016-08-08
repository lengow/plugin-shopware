//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/panel"}
Ext.define('Shopware.apps.Lengow.view.import.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-import-panel',

    layout: 'fit',

    // Translations
    snippets: {
        button: '{s name="order/panel/button" namespace="backend/Lengow/translation"}{/s}'
    },

    initComponent: function () {
        var me = this;
        me.items = Ext.create('Ext.panel.Panel', {
            border: false,
            width: 450,
            margins: 15,
            layout: {
                type: 'vbox',
                pack: 'start',
                align: 'stretch'
            },
            items: [
                { // Import description with number of days selected in settings
                    xtype: 'panel',
                    id: 'importDescriptionPanel',
                    border: false
                },
                { // Last import date (manual or cron)
                    xtype: 'panel',
                    id: 'lastImportPanel',
                    border: false
                },
                { // Display import error messages
                    xtype: 'panel',
                    id: 'importStatusPanel',
                    border: false
                },
                // Manuel import button
                Ext.create('Ext.button.Button', {
                    id: 'importButton',
                    text: me.snippets.button,
                    style: { marginTop: '10px' }
                })
            ]
        });

        me.callParent(arguments);
    }
});
//{/block}