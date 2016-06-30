Ext.define('Shopware.apps.Iframe.view.Main', {
    extend: 'Enlight.app.Window',
    title: 'Lengow',
    layout: 'fit',
    width: "70%",
    height: "90%",

    initComponent: function () {
        var me = this;
        me.items = me.tabPanel = Ext.create('Ext.tab.Panel', {
            layout: 'fit',
            items: []
        });
        me.callParent(arguments);
    },

    initAccountTabs: function () {
        var me = this,
            i = 0,
            tab;

        me.accountStore.each(function(account) {
            tab = me.tabPanel.add({
                title: account.get('name'),
                xtype: 'component',
                autoEl: {
                    'tag': 'iframe',
                    'src': account.get('url')
                }
            });
            me.tabPanel.setActiveTab(tab);
        });
    }
});