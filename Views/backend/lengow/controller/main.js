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

    snippets: {
        message: {
            saveSettingsTitle:  '{s name=main/message/save_settings_title}Save Lengow\'s settings{/s}',
            saveSetting:        '{s name=main/message/save_setting}Configuration was saved{/s}'
        }
    },

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
                formPanel.loadRecord(record);
                if (record.get('newSetting')) {
                    me.getLengowExportImages().setValue();
                }
            }
        });
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
                Shopware.Notification.createGrowlMessage(me.snippets.message.saveSettingsTitle, me.snippets.message.saveSetting);
                me.loadRecord();
            }
        });
    }

});
//{/block}