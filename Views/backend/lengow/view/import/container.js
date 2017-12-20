//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/container"}
Ext.define('Shopware.apps.Lengow.view.import.Container', {
    extend: 'Ext.container.Container',
    alias: 'widget.lengow-import-container',
    renderTo: Ext.getBody(),

    /**
     * Main controller
     */
    configure: function() {
        return {
            controller: 'Main'
        };
    },

    /**
     * Init components used by the container
     */
    initComponent: function() {
        var me = this;

        me.items = [
            {
                xtype: 'panel',
                region: 'center',
                id: 'topPanelImport',
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                items: [
                    Ext.create('Shopware.apps.Lengow.view.import.Grid', {
                        id: 'importGrid',
                        importStore: me.importStore,
                        flex: 1,
                        autoScroll : true,
                        style: 'border: none',
                        bodyStyle: 'background:#fff;'
                    })
                ]
            }
        ];
        me.callParent(arguments);
    }

});
//{/block}