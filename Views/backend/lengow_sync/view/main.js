Ext.define('Shopware.apps.LengowSync.view.Main', {
    extend: 'Enlight.app.Window',
    title: 'Lengow',
    layout: 'fit',
    width: "70%",
    height: "90%",

    initComponent: function () {
        var me = this;
        me.items = Ext.create('Ext.panel.Panel', {
            layout: 'fit',
            items: [{
                title: 'Register',
                xtype: 'component',
                autoEl: {
                    'tag': 'iframe',
                    'src': 'http://cms.lengow.int/sync/'
                }
            }]
        });
        me.callParent(arguments);
    }
});