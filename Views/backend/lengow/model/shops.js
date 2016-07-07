//{block name="backend/lengow/model/shops"}
Ext.define('Shopware.apps.Lengow.model.Shops', {
    extend: 'Ext.data.Model',
    fields: [
    	'id', 
    	'text', 
    	'leaf',
    	'name' // Needed for shop selection combobox
    	]
});
//{/block}