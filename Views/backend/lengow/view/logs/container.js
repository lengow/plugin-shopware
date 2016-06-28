//{namespace name="backend/lengow/view/logs"}
//{block name="backend/lengow/view/logs/container"}
Ext.define('Shopware.apps.Lengow.view.logs.Container', {
    extend: 'Ext.container.Container',
    alias: 'widget.lengow-logs-container',

    /**
     * Main controller
     * @returns [controller:string]
     */
    configure: function() {
        return {
            controller: 'Main',
            category_tree: 'Shopware.apps.Lengow.view.export.Panel',
            grid: 'Shopware.apps.Lengow.view.export.Grid'
        };
    },

    /**
     * Init components used by the container
     */
    initComponent: function() {
        var me = this;

        me.items = [
            {
                xtype: 'lengow-logs-panel',
                region: 'west',
                store: me.store,
                width: 300,
                layout: 'fit'
            }
        ];

        me.callParent(arguments);
    }
});
//{/block}