//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/dashboard"}
Ext.define('Shopware.apps.Lengow.controller.Dashboard', {
    extend: 'Enlight.app.Controller',

    init: function () {
        var me = this;

        me.control({
            'lengow-main-home': {
                loadDashboardContent: me.onLoadDashboardContent
            }
        });

        me.callParent(arguments);
    },

    /**
     * Load dashboard content
     */
    onLoadDashboardContent: function() {
        Ext.Ajax.request({
            url: '{url controller="LengowHome" action="getHomeContent"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var html = Ext.decode(response.responseText)['data'],
                    dashboardPanel = Ext.getCmp('lengowDashboardTab');
                // Load html in the panel
                dashboardPanel.update(html);
                // Make sure to listen on links after html is loaded
                Ext.getCmp('lengowMainWindow').fireEvent('initLinkListener');
            }
        });
    }
});
//{/block}