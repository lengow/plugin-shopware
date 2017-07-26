//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/export"}
Ext.define('Shopware.apps.Lengow.controller.Export', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;

        me.control({
            'product-listing-grid': {
                setStatusInLengow: me.onSetStatusInLengow,
                getConfigValue: me.onGetConfigValue
            },
            'lengow-export-container': {
                getFeed: me.onGetFeed,
                setConfigValue: me.onSetConfigValue
            },
            'lengow-category-panel': {
                getDefaultShop: me.onGetDefaultShop
            }
        });

        me.callParent(arguments);
    },

    /**
     * Download shop feed
     * @param selectedShop integer Id of the shop to export
     */
    onGetFeed: function(selectedShop) {
    	if (selectedShop) {
            Ext.Ajax.request({
                url: '{url controller="LengowExport" action="getShopToken"}',
                method: 'POST',
                type: 'json',
                params: {
                    shopId: selectedShop
                },
                success: function(response) {
                    var shopToken = Ext.decode(response.responseText)['data'];
                    var url = '{url controller="LengowExport" action="export"}';
                    // Create form panel. Contains a basic form to download the file.
                    var form = Ext.create('Ext.form.Panel').getForm().submit({
                        url: url,
                        method: 'POST',
                        target: '_blank', // Avoids leaving the page
                        success: function(response, opts){
                            var url = opts.result.url;
                            window.open(
                                url + '?shop=' + selectedShop + '&token=' + shopToken + '&stream=1&update_export_date=0'
                            );
                        }
                    });
                }
            });
    	}
    },

    /**
     * Change article Lengow status
     * @param ids array|null List of article ids to edit. 
                If null, change for all article in the category
     * @param status boolean True if articles have to be activated
     * @param categoryId int Category (shop main category or shopId_subCategoryId) 
     *      the article belongs to
     */
    onSetStatusInLengow: function(ids, status, categoryId) {
        Ext.Ajax.request({
            url: '{url controller="LengowExport" action="setStatusInLengow"}',
            method: 'POST',
            type: 'json',
            params: {
                ids: ids,
                status: status,
                categoryId: categoryId
            },
            success: function() {
                Ext.getCmp('exportGrid').updateCounter();
                Ext.getCmp('lengowExportTab').getEl().unmask();
            }
        });
    },

    /**
     * Change settings values (variations, out of stocks or selection)
     * @param shopId int Shop id to edit
     * @param settingName string Name of the setting to edit
     * @param value boolean Status of this setting
     */
    onSetConfigValue: function(shopId, settingName, value) {
        Ext.Ajax.request({
            url: '{url controller="LengowExport" action="setConfigValue"}',
            method: 'POST',
            type: 'json',
            params: {
                id: shopId,
                name: settingName,
                status: value
            }
        });
    },

    /**
     * Get setting value from db
     * @param configList array List of configs
     * @param shopId int Shop id
     */
    onGetConfigValue: function(configList, shopId) {
        Ext.Ajax.request({
            url: '{url controller="LengowExport" action="getConfigValue"}',
            method: 'POST',
            type: 'json',
            params: {
                id: shopId,
                configList: Ext.encode(configList)
            },
            success: function(response) {
                var values = Ext.decode(response.responseText)['data'];
                // Set shop config for checkboxes
                Ext.each(configList, function(config) {
                    var status = values[config];
                    var checkbox = Ext.getCmp(config);
                    // Avoid launching listener updateCounter function
                    // when checking option get from db
                    checkbox.skipCounterUpdate = true;
                    checkbox.setValue(status);
                    checkbox.skipCounterUpdate = false;
                });

                if (!Ext.getCmp('lengowExportSelectionEnabled').getValue()) {
                    Ext.getCmp('exportGrid').setDisabled(true);
                }
            }
        });
    },

    /**
     * Get Shopware default shop 
     * Auto select the shop in the tree when launching the plugin
     * @param view Tree view (shop list) needed to select default shop
     */
    onGetDefaultShop: function(view) {
        Ext.Ajax.request({
            url: '{url controller="LengowExport" action="getDefaultShop"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var tree = Ext.getCmp('shopTree'),
                    defaultShopId = Ext.decode(response.responseText)['data'],
                    childNodes = tree.getRootNode().childNodes;

                // Look for default shop in the tree
                Ext.each(childNodes, function(child) {
                    if (child.get('id') == defaultShopId) {
                        // Simulate click
                        tree.getSelectionModel().select(child);
                        tree.fireEvent('itemclick', view, child);
                        return true;
                    }
                });
            }
        });
    }
});
//{/block}