//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/help"}
Ext.define('Shopware.apps.Lengow.controller.Help', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;

        me.control({
            'lengow-help-panel': {
                loadHelpContent: me.onLoadHelpContent
            }
        });

        me.callParent(arguments);
    },

    onLoadHelpContent: function() {
        Ext.Ajax.request({
            url: '{url controller="LengowHelp" action="getHelpContent"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var data = Ext.decode(response.responseText)['data'];
                Ext.getCmp('lengowHelpTab').update(data);
            }
        });
    }
});
//{/block}