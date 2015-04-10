//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/window"}
Ext.define('Shopware.apps.Lengow.view.main.Window', {
    /**
     * Define that the order main window is an extension of the enlight application window
     * @string
     */
    extend:'Enlight.app.Window',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias:'widget.lengow-main-window',
    /**
     * Set no border for the window
     * @boolean
     */
    // border:false,
    /**
     * True to automatically show the component upon creation.
     * @boolean
     */
    // autoShow:true,
    /**
     * True to display the 'maximize' tool button and allow the user to maximize the window, false to hide the button and disallow maximizing the window.
     * @boolean
     */
    // maximizable:true,
    /**
     * True to display the 'minimize' tool button and allow the user to minimize the window, false to hide the button and disallow minimizing the window.
     * @boolean
     */
    //minimizable:true,
    
    width: 800,
    height: 600,

    snippets: {
        title: '{s name=window/title}Lengow{/s}'
    },

    /**
     * @return void
     */
    initComponent: function () {
        var me = this;
        me.title = me.snippets.title;
        me.callParent(arguments);
    }

});
//{/block}