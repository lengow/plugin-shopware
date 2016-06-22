

Ext.define('Shopware.apps.Lengow.view.export.Grid', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.product-listing-grid',

    configure: function() {
        var me = this;

        return {
            addButton: false,
            deleteButton: false
        };
    },

    initComponent: function() {
        var me = this;

        me.addCustomFields();

        me.callParent(arguments);
    },

    /**
     * Add custom fields to the grid
     */
    addCustomFields: function() {
        var me = this;
        var articleModel = me.store.model,
            fields = articleModel.prototype.fields.getRange();

        articleModel.setFields(fields);
    }
});
