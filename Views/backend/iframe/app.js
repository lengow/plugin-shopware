
Ext.define('Shopware.apps.Iframe', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.Iframe',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Main' ],

    views: [
        'Main'
    ],

    launch: function() {
        return this.getController('Main').mainWindow;
    }
});