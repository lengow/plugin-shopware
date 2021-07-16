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
                    freeTrialExpired = data['freeTrialExpired'],
                    newVersionIsAvailable = data['newVersionIsAvailable'],
                    showUpdateModal = data['showUpdateModal'],
                    html = data['data'],
                    dashboardPanel = Ext.getCmp('lengowDashboardTab');
                // waiting message while dashboard is not loaded
                dashboardPanel.getEl().mask('Loading...', 'x-mask-loading');
                // load html in the panel
                dashboardPanel.update(html);
                // if end of free trial
                if (freeTrialExpired) {
                    // hide toolbar and tabs
                    Ext.getCmp('lengowMainToolbar').hide();
                    Ext.getCmp('lengowTabPanel').getTabBar().hide();
                    me.initRefreshLink();
                }
                if (newVersionIsAvailable) {
                    me.initOpenUpdateModalButton();
                    me.initCloseUpdateModalButton();
                }
                if (showUpdateModal) {
                    me.initRemindMeLaterButton();
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
        // get Lengow refresh links (end of trial view)
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
    },

    /**
     * Listen to "Open update modal" button for update modal
     */
    initOpenUpdateModalButton: function() {
        var button = Ext.query('button[id=js-open-update-modal]')[0];
        button.onclick = function() {
            Ext.get('js-update-modal').addCls('is-open');
        };
    },

    /**
     * Listen to "Close update modal" button for update modal
     */
    initCloseUpdateModalButton: function() {
        var span = Ext.query('span[id=js-close-update-modal]')[0];
        span.onclick = function() {
            Ext.get('js-update-modal').removeCls('is-open');
        };
    },

    /**
     * Listen to "Remind me later" button for update modal
     */
    initRemindMeLaterButton: function() {
        var button = Ext.query('button[id=js-remind-me-later]')[0];
        button.onclick = function() {
            Ext.Ajax.request({
                url: '{url controller="LengowDashboard" action="remindMeLater"}',
                method: 'POST',
                type: 'json',
                success: function() {
                    Ext.get('js-remind-me-later').hide();
                    Ext.get('js-update-modal').removeCls('is-open');
                }
            });
        };
    },
});
//{/block}