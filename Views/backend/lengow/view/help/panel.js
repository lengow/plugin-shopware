//{namespace name="backend/lengow/view/help"}
//{block name="backend/lengow/view/help/panel"}
Ext.define('Shopware.apps.Lengow.view.help.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-help-panel',
    autoScroll: true,
    title: '{s name="window/tab/help" namespace="backend/Lengow/translation"}Help{/s}',
    style: {
        background: '#F7F7F7'
    },

    /**
     * Init main component; set main title and tabs for the window
     */
    initComponent: function() {
        var me = this;
        me.fireEvent('loadHelpContent');
        me.callParent();
    }
});
//{/block}