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
		text: 'Shops',
		expanded: true
	},

    initComponent: function () {
        var me = this;

        me.store = Ext.create('Shopware.apps.Lengow.store.Shops');

        me.callParent(arguments);
    },
});
//{/block}