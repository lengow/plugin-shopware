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
     * Load main tab content
     * Display dashboard or end of free trial/bad payer page
     */
    onLoadDashboardContent: function() {
        var me = this;
        Ext.Ajax.request({
            url: '{url controller="LengowDashboard" action="getDashboardContent"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var data = Ext.decode(response.responseText),
                    displayTabBar = data['displayTabBar'],
                    html = data['data'],
                    dashboardPanel = Ext.getCmp('lengowDashboardTab');
                // waiting message while dashboard is not loaded
                dashboardPanel.getEl().mask('Loading...', 'x-mask-loading');
                // load html in the panel
                dashboardPanel.update(html);
                // if bad payer or end of free trial
                if (!displayTabBar) {
                    // hide toolbar and tabs
                    Ext.getCmp('lengowMainToolbar').hide();
                    Ext.getCmp('lengowTabPanel').getTabBar().hide();
                    me.initRefreshLink();
                }
                // make sure to listen on links after html is loaded
                Ext.getCmp('lengowMainWindow').fireEvent('initLinkListener');
                dashboardPanel.getEl().unmask();
            }
        });
    },

    /**
     * Listen to "Refresh my account" link
     */
    initRefreshLink: function() {
        // get Lengow refresh links (end of trial & bad payer views)
        var refreshLink = Ext.query("a[id=lgw-refresh]")[0];
        refreshLink.onclick = function() {
            Ext.Ajax.request({
                url: '{url controller="LengowDashboard" action="refreshStatus"}',
                method: 'POST',
                type: 'json',
                success: function() {
                    // refresh Lengow by launching a new instance of the plugin
                    Shopware.app.Application.addSubApplication({
                        name: 'Shopware.apps.Lengow'
                    });
                }
            });
        };
    }
});
//{/block}