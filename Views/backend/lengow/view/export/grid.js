

Ext.define('Shopware.apps.Lengow.view.export.Grid', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.product-listing-grid',

    configure: function() {
        var me = this;
        me.store = Ext.create('store.article-store');

        me.addCustomFields();

        return {
            addButton: false,
            deleteButton: false
        };
    },

    initComponent: function() {
        var me = this;

        me.callParent(arguments);
    },

    /**
     * Add custom fields to the grid
     */
    addCustomFields: function() {
        var me = this;
        var articleModel = me.store.model,
            fields = articleModel.prototype.fields.getRange();

        fields.push({
            name: 'activeLengow',
            type: 'boolean',
            sortable: false,
            active: { width: 60, flex: 0 }
        });

        articleModel.setFields(fields);
    }
});
