//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/controller/main"}
Ext.define('Shopware.apps.Lengow.controller.Main', {
    /**
     * The parent class that this class extends.
     * @string
     */
    extend:'Ext.app.Controller',

    /**
     * A template method that is called when your application boots.
     *
     * @return Ext.window.Window
     */
    init:function () {
        var me = this;
        me.mainWindow = me.getView('main.Window').create({
            articleStore: Ext.create('Shopware.apps.Base.store.Article').load(),
            ordersStore: Ext.create('Shopware.apps.Lengow.store.Orders').load(),
            logsStore: Ext.create('Shopware.apps.Lengow.store.Logs').load()
        }).show();
        me.callParent(arguments);
    }

});
//{/block}