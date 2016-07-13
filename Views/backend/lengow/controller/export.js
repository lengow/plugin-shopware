//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/export"}
Ext.define('Shopware.apps.Lengow.controller.Export', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;

        me.control({
            'product-listing-grid': {
                setStatusInLengow: me.onSetStatusInLengow
            },
            'lengow-category-panel': {
                getConfigValue: me.onGetConfigValue
            },
            'lengow-export-container': {
                getFeed: me.onGetFeed,
                changeSettingsValue: me.onChangeSettingsValue,
            }
        });

        me.callParent(arguments);
    },

    /**
     * Download shop feed
     * @param selectedShop Name of the shop to export
     */
    onGetFeed: function(selectedShop) {
    	var me = this;

    	if (selectedShop) {
            var url = '{url controller="LengowExport" action="export"}';

            // Create form panel. It contains a basic form that we need for the file download.
            var form = Ext.create('Ext.form.Panel').getForm().submit({
                url: url,
                method: 'POST',
                target: '_blank', // Avoids leaving the page.,
                success: function(response, opts){
                    var url = opts.result.url;
                    window.open(url + '?stream=1&shop=' + selectedShop);
                }
            });
    	}
    },

    /**
     * Change article Lengow status
     * @param ids List of article ids to edit
     * @param status boolean True if articles have to be activated
     * @param categoryId int|null Category (shop main category or shopId_subCategoryId) 
     *      the article belongs to
     */
    onSetStatusInLengow: function(ids, status, categoryId) {
        var me = this;

        Ext.Ajax.request({
            url: '{url controller="LengowExport" action="setStatusInLengow"}',
            method: 'POST',
            type: 'json',
            params: {
                ids: ids,
                status: status,
                categoryId: categoryId
            },
            success: function(response, opts) {
                Ext.getCmp('exportGrid').getStore().load();
                Ext.getCmp('exportContainer').getEl().unmask();
            }
        });
    },

    onChangeSettingsValue: function(shopId, settingName, value) {
        var me = this;

        Ext.Ajax.request({
            url: '{url controller="LengowExport" action="changeSettingsValue"}',
            method: 'POST',
            type: 'json',
            params: {
                id: shopId,
                name: settingName,
                status: value
            },
            success: function(response, opts) {
            }
        });
    },

    onGetConfigValue: function(configName, shopId) {
        var me = this;

        Ext.Ajax.request({
            url: '{url controller="LengowExport" action="getConfigValue"}',
            method: 'POST',
            type: 'json',
            params: {
                id: shopId,
                name: configName
            },
            success: function(response, opts) {
                var status = Ext.decode(response.responseText);
                Ext.getCmp(configName).setValue(status.data);
            }
        });
    }
});
//{/block}