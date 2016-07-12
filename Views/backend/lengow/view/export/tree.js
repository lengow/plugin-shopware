//{namespace name="backend/lengow/view/export"}
//{block name="backend/lengow/view/export/tree"}
Ext.define('Shopware.apps.Lengow.view.export.Tree', {
    extend: 'Ext.tree.Panel',
    alias:  'widget.product-shop-tree',

    id: 'shopTree',
    border: false,
    rootVisible: true,
    expanded: true,
    useArrows: false,
    layout: 'fit',
    flex: 1,
    bodyStyle: 'background:#fff;',

	root: {
		text: '{s name="export/panel/tree/root" namespace="backend/Lengow/translation"}{/s}',
		expanded: true
	},

    listeners: {
        beforeselect: function(e) {
            console.log(e);
        }
    },

    initComponent: function () {
        var me = this;

        me.store = Ext.create('Shopware.apps.Lengow.store.Shops');

        me.callParent(arguments);
    },
});
//{/block}