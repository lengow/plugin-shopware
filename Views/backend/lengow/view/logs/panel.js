//{namespace name="backend/lengow/view/logs"}
//{block name="backend/lengow/view/logs/panel"}
Ext.define('Shopware.apps.Lengow.view.logs.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-logs-panel',

    layout: 'fit',

    // Translations
    snippets: {
        emptySelection: '{s name="log/panel/empty_selection" namespace="backend/Lengow/translation"}{/s}',
        button: '{s name="log/panel/button" namespace="backend/Lengow/translation"}{/s}'
    },

    initComponent: function () {
        var me = this;

        me.store.load();

        me.items = Ext.create('Ext.panel.Panel', {
            border: false,
            width: 250,
            margins: 15,
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

    /**
     * Get combobox which displays list of available log files
     */
    getComboBox: function () {
        var me = this;
        me.comboBox = Ext.create('Ext.form.field.ComboBox', {
            id: 'selectedName',
            displayField: 'date',
            valueField: 'name',
            emptyText: me.snippets.emptySelection,
            editable: false,
            store: me.store,
            listeners : {
                select : function() {
                    // When a record is selected, enable download button
                    Ext.getCmp('downloadButton').enable();
                }
            }
        });

        return me.comboBox;
    },

    /**
     * Download button used to get log file
     */
    getDownloadButton: function () {
        var me = this;

        var downloadButton = Ext.create('Ext.button.Button', {
            id: 'downloadButton',
            disabled: true,
            text: me.snippets.button,
            style: { marginTop: '10px' },
            handler: function() {
                var selectedFile = Ext.getCmp('selectedName').getValue();
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

                Ext.getCmp('logWindow').close();
            }
        });

        return downloadButton;
    }
});
//{/block}