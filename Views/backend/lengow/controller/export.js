//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/export"}
Ext.define('Shopware.apps.Lengow.controller.Export', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;

        me.control({
        	'lengow-category-panel': {
        		exportShop: me.onExportShop,
        	},
            'product-listing-grid': {
                setStatusInLengow: me.setStatusInLengow
            },
        });

        me.callParent(arguments);
    },

    onExportShop: function(selectedShop) {
    	var me = this;

    	if (selectedShop) {
            Ext.Ajax.request({
                url: '{url controller="LengowExport" action="export"}',
                method: 'POST',
                params: {},
                success: function(response, opts) {
                    var strJson  = response.responseText;
                    var obj = Ext.JSON.decode(strJson);
                    var url = obj.url;
                    window.open(url + '?shop=' + selectedShop, '_blank');
                }
            });
    	}
    },

    /**
     * Change article Lengow status
     *
     */
    setStatusInLengow: function(ids, status, categoryId) {
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
                Ext.getCmp('exportGrid').setNumberOfProductExported();
            }
        });
    }
});
//{/block}