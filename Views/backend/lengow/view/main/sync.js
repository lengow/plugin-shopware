//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/sync"}
Ext.define('Shopware.apps.Lengow.view.main.Sync', {
    extend: 'Enlight.app.Window',
    title: 'Lengow',
    layout: 'fit',
    width: "70%",
    height: "90%",

    initComponent: function () {
        var me = this;
        // Display Lengow login panel
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
//{/block}