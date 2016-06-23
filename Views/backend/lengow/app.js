
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
    }
});