// This tab will be shown in the customer module

Ext.define('Shopware.apps.Lengow.view.order.LengowOrderTab', {
    extend: 'Ext.container.Container',
    padding: 10,
    title: 'Lengow',

    /**
     * Contains all snippets for the view component
     * @object
     */
    snippets:{
        details: {
            title: '{s name=order/details/lengow/title}Lengow details{/s}',
            currency: '{s name=order/details/lengow/currency}Currency{/s}',
            shop: '{s name=order/details/lengow/shop}Shop{/s}',
            language: '{s name=order/details/lengow/language}Language{/s}',
        }
    },

    initComponent: function() {
        var me = this;

        me.items  =  [
            me.createDetailsContainer(),
        ];
        me.callParent(arguments);
    },

    /**
     * Creates the container for the detail form panel.
     * @return Ext.form.Panel
     */
    createDetailsContainer: function() {
        var me = this;

        me.detailsForm = Ext.create('Ext.form.Panel', {
            title: me.snippets.details.title,
            bodyPadding: 10,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            margin: '10 0',
            items: [
                me.createInnerDetailContainer()
            ]
        });
        return me.detailsForm;
    },

    /**
     * Creates the outer container for the detail panel which
     * has a column layout to display the detail information in two columns.
     *
     * @return Ext.container.Container
     */
    createInnerDetailContainer: function() {
        var me = this;

        return Ext.create('Ext.container.Container', {
            layout: 'column',
            items: [
                me.createDetailElementContainer(me.createDetailElements()),
            ]
        });
    },

    /**
     * Creates the column container for the detail elements which displayed
     * in two columns.
     * @param [array] items - The container items.
     */
    createDetailElementContainer: function(items) {
        return Ext.create('Ext.container.Container', {
            defaults: {
                xtype: 'displayfield',
                labelWidth: 155
            },
            items: items
        });
    },

    /**
     * Creates the elements for the left column container which displays the
     * fields in two columns.
     * @return any - Contains the form fields
     */
    createDetailElements: function() {
        var me = this, fields;
        fields = [
            { name:'currency', fieldLabel:me.snippets.details.currency },
            { name:'shop[name]', fieldLabel:me.snippets.details.shop },
            { name:'locale[name]', fieldLabel:me.snippets.details.language},
        ];
        return fields;
    },
});