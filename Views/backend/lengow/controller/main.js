//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/main"}
Ext.define('Shopware.apps.Lengow.controller.Main', {
    /**
     * The parent class that this class extends.
     * @string
     */
    extend:'Ext.app.Controller',

    refs: [
        { ref: 'settingsForm', selector: 'lengow-main-settings' },
        { ref: 'shopCombo', selector: 'lengow-main-settings field[name=shopSetting]' },
        { ref: 'lengowAuthorisedIp', selector: 'lengow-main-settings field[name=lengowAuthorisedIp]' },
        { ref: 'lengowExportImages', selector: 'lengow-main-settings field[name=lengowExportImages]' },
        { ref: 'lengowCarrierDefault', selector: 'lengow-main-settings field[name=lengowCarrierDefault]' },
        { ref: 'lengowOrderProcess', selector: 'lengow-main-settings field[name=lengowOrderProcess]' },
        { ref: 'lengowOrderShipped', selector: 'lengow-main-settings field[name=lengowOrderShipped]' },
        { ref: 'lengowOrderCancel', selector: 'lengow-main-settings field[name=lengowOrderCancel]' },
        { ref: 'lengowImportDays', selector: 'lengow-main-settings field[name=lengowImportDays]' }
    ],

    /**
     * A template method that is called when your application boots.
     *
     * @return Ext.window.Window
     */
    init:function () {
        var me = this;
        me.mainWindow = me.getView('main.Window').create({
            articlesStore: Ext.create('Shopware.apps.Lengow.store.Articles').load(),
            ordersStore: Ext.create('Shopware.apps.Lengow.store.Orders').load(),
            logsStore: Ext.create('Shopware.apps.Lengow.store.Logs').load()
        }).show();

        me.loadRecord();

        me.control({
            'lengow-main-settings': {
                changeShop: me.onChangeShop,
                saveSettings: me.onSaveSettings
            }
        });

        me.callParent(arguments);
    },

    loadRecord: function() {
        var me = this,
            shopId = me.getShopCombo().getValue(),
            store = Ext.create('Shopware.apps.Lengow.store.Settings'),
            formPanel = me.getSettingsForm(),
            record;

        if (!shopId) {
            shopId = 1;
        }

        store.getProxy().extraParams.shopId = parseInt(shopId);

        store.load({
            callback: function() {
                record = store.getAt(0);
                formPanel.setLoading(true);
                setTimeout(function() {
                    formPanel.setLoading(false);
                }, 250);
                if (record) {
                    formPanel.loadRecord(record);
                } else {
                    me.loadNewSetting(shopId);
                }
            }
        });
    },

    loadNewSetting: function(shopId) {
        var me = this,
            formPanel = me.getSettingsForm(),
            iRecord = Ext.create('Shopware.apps.Lengow.model.Setting');
            formPanel.loadRecord(iRecord);
            me.getLengowExportImages().setValue();
            me.getLengowAuthorisedIp().setValue('127.0.0.1');
            me.getLengowCarrierDefault().setValue();
            me.getLengowOrderProcess().setValue();
            me.getLengowOrderShipped().setValue();
            me.getLengowOrderCancel().setValue();
            me.getLengowImportDays().setValue(3);
            me.getShopCombo().setValue(shopId);
    },

    onChangeShop: function() {
        this.loadRecord();
    },

    onSaveSettings: function() {
        var me = this,
            view = me.getSettingsForm(),
            form = view.getForm(),
            record = form.getRecord();
       
        if (!form.isValid()) {
            return;
        }

        form.updateRecord(record);

        record.save({
            callback: function() {
                Shopware.Notification.createGrowlMessage('Save', 'Configuration was saved');
                me.loadRecord();
            }
        });
    }


});
//{/block}