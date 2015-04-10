//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/export"}
Ext.define('Shopware.apps.Lengow.view.main.Exports', {
    /**
     * Define that the order main window is an extension of the enlight application window
     * @string
     */
    extend:'Ext.container.Container',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias:'widget.lengow-main-exports',

    layout: 'anchor',
    padding: 10,
    defaults: {
        anchor: '100%',
        labelWidth: 200
    },

    /**
     * Sets up the ui component
     *
     * @return void
     */
    initComponent: function() {
        var me = this;
        me.callParent(arguments);
    }

});
//{/block}