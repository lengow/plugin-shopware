
Ext.define('Shopware.apps.Lengow', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.Lengow',

    loadPath: '{url action=load}',

    controllers: [
        'Main',
        'Export',
        'Logs'
    ],

    views: [
        'main.Window',
        'export.Panel',
        'export.Container',
        'export.Grid',
        'logs.Panel',
        'logs.Container'
    ],

    models: [
        'Article',
        'Logs'
    ],
    stores: [
        'Article',
        'Logs'
    ],

    launch: function() {
        return this.getController('Main').mainWindow;
    },

    onBeforeLaunch: function() {
        var me = this;

        me.destroyOtherModuleInstances();

        me.callParent(arguments);
    },

    /**
     * Limit Lengow plugin to a unique instance
     * Avoid conflicts when minimizing the window and opening a new instance 
     */
    destroyOtherModuleInstances: function (cb, cbArgs) {
        var me = this, activeWindows = [], subAppId = me.$subAppId;
        cbArgs = cbArgs || [];

        Ext.each(Shopware.app.Application.subApplications.items, function (subApp) {
            if (!subApp || !subApp.windowManager || subApp.$subAppId === subAppId || !subApp.windowManager.hasOwnProperty('zIndexStack')) {
                return;
            }
            Ext.each(subApp.windowManager.zIndexStack, function (item) {
                if (typeof(item) !== 'undefined') {
                    activeWindows.push(item);
                }
            });
        });

        if (activeWindows && activeWindows.length) {
            Ext.each(activeWindows, function (win) {
                win.destroy();
            });

            if (Ext.isFunction(cb)) {
                cb.apply(me, cbArgs);
            }
        } else {
            if (Ext.isFunction(cb)) {
                cb.apply(me, cbArgs);
            }
        }
    }
});