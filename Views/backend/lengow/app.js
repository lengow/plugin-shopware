//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/application"}
Ext.define('Shopware.apps.Lengow', {

    /**
     * The name of the module. Used for internal purpose
     * @string
     */
    name:'Shopware.apps.Lengow',

    /**
     * Extends from our special controller, which handles the sub-application behavior and the event bus
     * @string
     */
    extend:'Enlight.app.SubApplication',

    /**
     * Enable bulk loading
     * @boolean
     */
    bulkLoad:true,

    /**
     * Sets the loading path for the sub-application.
     *
     * @string
     */
    loadPath:'{url action="load"}',

    /**
     * Array of views to require from AppName.view namespace.
     * @array
     */
    views: [ 
        'main.Window',
        'main.Logs',
        'main.Settings',
        'export.Exports',
        'export.Grid',
        'import.Imports',
        'import.Grid',
        'import.Panel'
    ],

    /**
     * Array of stores to require from AppName.store namespace.
     * @array
     */
    stores: [
        'Articles',
        'Orders', 
        'Logs',
        'Settings', 
        'ExportFormats',
        'ExportImages',
        'ImageFormats',
        'PaymentMethods' 
    ],

    /**
     * Array of models to require from AppName.model namespace.
     * @array
     */
    models: [
        'Article',
        'Order', 
        'Log',
        'Setting',
        'ExportFormat',
        'ExportImage',
        'ImageFormat',
        'PaymentMethod'  
    ],

    /**
     * Requires controllers for sub-application
     * @array
     */
    controllers: [ 
        'Main',
        'Export',
        'Import',
        'Log'
    ],

    /**
     * @private
     * @return [object] mainWindow - the main application window based on Enlight.app.Window
     */
    launch: function() {
        var me = this,
            mainController = me.getController('Main');
        return mainController.mainWindow;
    }
});
//{/block}