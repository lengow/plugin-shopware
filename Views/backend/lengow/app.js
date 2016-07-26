//{namespace name="backend/lengow/view"}
//{block name="backend/lengow/application"}
Ext.define('Shopware.apps.Lengow', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.Lengow',

    loadPath: '{url action=load}',

    controllers: [
        'Main',
        'Export',
        'Import'
    ],

    views: [
        'main.Home',
        'main.Sync',
        'export.Panel',
        'export.Container',
        'export.Grid',
        'export.Tree',
        'import.Panel',
        'logs.Panel'
    ],

    models: [
        'Article',
        'Logs',
        'Shops'
    ],
    stores: [
        'Article',
        'Logs',
        'Shops'
    ],

    launch: function() {
        return this.getController('Main').mainWindow;
    },

    /**
     * Before launch app listener
     * Destroy opened Lengow instances
     */
    onBeforeLaunch: function() {
        var me = this;
        me.destroyOtherModuleInstances();
        me.callParent(arguments);
    },

    /**
     * Limit Lengow plugin to a unique instance
     * Avoid conflicts when minimizing the window and opening a new instance 
     */
    destroyOtherModuleInstances: function () {
        var me = this,  subAppId = me.$subAppId;

        // Iterate over open sub-applications
        Ext.each(Shopware.app.Application.subApplications.items, function (subApp) {
            if (!subApp
                || !subApp.windowManager
                || subApp.$subAppId === subAppId
                || !subApp.windowManager.hasOwnProperty('zIndexStack')) {
                return;
            }
            Ext.each(subApp.windowManager.zIndexStack, function (item) {
                var title = new String(item.header.title).valueOf();
                if (title !== 'undefined' && (title.lastIndexOf('Lengow', 0) === 0)) {
                    item.destroy();
                    return true;
                }
            });
        });
    }
});
//{/block}