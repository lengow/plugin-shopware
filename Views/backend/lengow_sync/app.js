
Ext.define('Shopware.apps.LengowSync', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.LengowSync',

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