//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/controller/lengow"}
Ext.define('Shopware.apps.Lengow.controller.Lengow', {
    /**
     * The parent class that this class extends.
     * @string
     */
    extend:'Ext.app.Controller',

    // refs: [
    //     { ref: 'articleList', selector: 'favorite-list-window favorite-article-list' }
    // ],

    /**
     * A template method that is called when your application boots.
     *
     * @return Ext.window.Window
     */
    init:function () {
        var me = this;
        me.mainWindow = me.getView('main.Window').create({}).show();
        me.callParent(arguments);
    }

});
//{/block}