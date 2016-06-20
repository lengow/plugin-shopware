Ext.define('Shopware.apps.Iframe.controller.Main', {
    /**
     * Extends the Enlight controller.
     * @string
     */
    extend: 'Enlight.app.Controller',

    /**
     * Settings for the controller.
     */
    settings: {
        postMessageOrigin: null
    },

    /**
     * Initializes the controller.
     *
     * @return void
     */
    init: function () {
        var me = this;
        me.showWindow();
        me.loadSettings();
        me.postMessageListener();
    },

    /**
     * Shows the main window.
     *
     * @return void
     */
    showWindow: function () {
        var me = this;
        me.accountStore = me.getStore('Article');
        me.mainWindow = me.getView('Main').create({
            accountStore: me.accountStore
        });
        me.mainWindow.show();
        me.accountStore.load({
            callback: function(records, op, success) {
                me.mainWindow.setLoading(false);
                if (success) {
                    me.mainWindow.initAccountTabs();
                } else {
                    throw new Error('Nosto: failed to load accounts.');
                }
            }
        });
    },

    /**
     * Loads controller settings.
     *
     * @return void
     */
    loadSettings: function () {
        var me = this;
        Ext.Ajax.request({
            method: 'GET',
            url: '{url controller=Iframe action=loadSettings}',
            success: function(response) {
                var op = Ext.decode(response.responseText);
                if (op.success && op.data) {
                    me.settings = op.data;
                } else {
                    throw new Error('Nosto: failed to load settings.');
                }
            }
        });
    },

    /**
     * Register event handler for window.postMessage() messages from Nosto through which we handle account creation,
     * connection and deletion.
     *
     * @return void
     */
    postMessageListener: function () {
        var me = this;
        window.addEventListener('message', Ext.bind(me.receiveMessage, me), false);
    }
});