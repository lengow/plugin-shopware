//{namespace name="backend/lengow/view/logs"}
//{block name="backend/lengow/view/logs/panel"}
Ext.define('Shopware.apps.Lengow.view.logs.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-logs-panel',

    layout: 'fit',

    // Translations
    snippets: {
        label: '{s name="log/download/label" namespace="backend/Lengow/translation"}{/s}',
        button: '{s name="log/download/button" namespace="backend/Lengow/translation"}{/s}'
    },

    initComponent: function () {
        var me = this;

        me.store.load();

        me.items = Ext.create('Ext.panel.Panel', {
            border: false,
            layout: {
                type: 'vbox',
                pack: 'start',
                align: 'stretch'
            },
            items: [
                me.getComboBox(),
                me.getDownloadButton()
            ]
        });

        me.callParent(arguments);
    },

    getComboBox: function () {
        var me = this;
        me.comboBox = Ext.create('Ext.form.field.ComboBox', {
            id: 'selectedName',
            fieldLabel: me.snippets.label,
            displayField: 'name',
            layout: 'fit',
            editable: false,
            store: me.store,
            queryMode: 'local'
        });

        return me.comboBox;
    },

    getDownloadButton: function () {
        var me = this;

        var downloadButton = Ext.create('Ext.button.Button', {
            text: me.snippets.button,
            handler: function(e) {
                var selectedFile = Ext.getCmp('selectedName').getRawValue();
                var url = '{url controller="LengowLogs" action="download"}';

                // Create form panel. It contains a basic form that we need for the file download.
                var form = Ext.create('Ext.form.Panel', {
                    standardSubmit: true,
                    url: url,
                    method: 'POST'
                });

                // Call the submit to begin the file download.
                form.submit({
                    target: '_blank', // Avoids leaving the page.
                    params: {
                        fileName: selectedFile
                    }
                });

                // Clean-up the form after 100 milliseconds.
                // Once the submit is called, the browser does not care anymore with the form object.
                Ext.defer(function(){
                    form.close();
                }, 100);
            }
        });

        return downloadButton;
    }
});
//{/block}