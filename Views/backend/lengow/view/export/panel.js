Ext.define('Shopware.apps.Lengow.view.export.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-category-panel',

    layout: 'fit',

    title: '{s name=categories}Categories{/s}',

    initComponent: function () {
        var me = this;

        me.items = me.getPanels();

        me.addEvents(
            'filterByCategory'
        );

        me.callParent(arguments);
    },

    /**
     * Returns the tree panel with and a toolbar
     */
    getPanels: function () {
        var me = this;

        me.treePanel = Ext.create('Ext.panel.Panel', {
            border: false,
            layout: {
                type: 'vbox',
                pack: 'start',
                align: 'stretch'
            },
            items: [
                // Settings button 
                Ext.create('Ext.button.Button', {
                    text: 'Configure Lengow',
                    layout: 'fit',
                    region: 'top',
                    renderTo: Ext.getBody(),
                    handler: function() {
                        Shopware.app.Application.addSubApplication({
                            name: 'Shopware.apps.Config'
                        });
                    }
                }),
                // Iframe button
                Ext.create('Ext.button.Button', {
                    text: 'Get registered',
                    layout: 'fit',
                    region: 'top',
                    renderTo: Ext.getBody(),
                    handler: function() {
                        Shopware.app.Application.addSubApplication({
                            name: 'Shopware.apps.Iframe'
                        });
                    }
                }),
                // Log button
                Ext.create('Ext.button.Button', {
                    text: 'Download Log',
                    layout: 'fit',
                    region: 'top',
                    renderTo: Ext.getBody(),
                    handler: function() {
                        var config = config || {};
                        var url = 'Lengow?action=downloadLog',
                            method = config.method || 'POST',// Either GET or POST. Default is POST.
                            params = config.params || {};

                        // Create form panel. It contains a basic form that we need for the file download.
                        var form = Ext.create('Ext.form.Panel', {
                            standardSubmit: true,
                            url: url,
                            method: method
                        });

                        // Call the submit to begin the file download.
                        form.submit({
                            target: '_blank', // Avoids leaving the page.
                            params: params
                        });

                        // Clean-up the form after 100 milliseconds.
                        // Once the submit is called, the browser does not care anymore with the form object.
                        Ext.defer(function(){
                            form.close();
                        }, 100);
                    }
                }),
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

        me.categoryStore = Ext.create('Shopware.store.CategoryTree');

        tree = Ext.create('Ext.tree.Panel', {
            border: false,
            rootVisible: true,
            expanded: true,
            useArrows: false,
            layout: 'fit',
            flex: 1,
            store: me.categoryStore,
            root: {
                text: '{s name=categories}Categories{/s}',
                expanded: true
            },
            listeners: {
                itemclick: {
                    fn: function (view, record) {
                        var me = this,
                            store =  me.store;

                        if (record.get('id') === 'root') {
                            store.getProxy().extraParams.categoryId = null;
                        } else {
                            store.getProxy().extraParams.categoryId = record.get('id');
                        }

                        //scroll the store to first page
                        store.currentPage = 1;
                        store.load();
                    }
                },
                scope: me
            }
        });

        return tree;
    }

});
