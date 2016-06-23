Ext.define('Shopware.apps.Lengow.view.logs.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-logs-panel',

    layout: 'fit',

    title: '{s name=categories}Logs{/s}',

    initComponent: function () {
        var me = this;

        me.items = me.getPanels();

        me.callParent(arguments);
    },

    /**
     * Returns the tree panel with and a toolbar
     */
    getPanels: function () {
        var me = this;

        me.treePanel = Ext.create('Ext.panel.Panel', {
            border: false,
            width: 300,
            layout: {
                type: 'vbox',
                pack: 'start',
                align: 'stretch'
            },
            items: [
                me.createTree()
            ]
        });

        return [me.treePanel];
    },

    /**
     * Creates the category tree
     *
     * @return [Ext.tree.Panel]
     */
    createTree: function () {
        var me = this,
                tree;

        me.categoryStore = Ext.create('Shopware.apps.Lengow.store.Logs');

        tree = Ext.create('Ext.tree.Panel', {
            border: true,
            rootVisible: true,
            expanded: true,
            useArrows: false,
            flex: 1,
            store: me.categoryStore,
            root: {
                text: '{s name=categories}Categories{/s}',
                expanded: true
            }
        });

        me.categoryStore.load();

        return tree;
    }
});