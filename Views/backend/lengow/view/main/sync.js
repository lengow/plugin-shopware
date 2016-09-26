//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/sync"}
Ext.define('Shopware.apps.Lengow.view.main.Sync', {
    extend: 'Enlight.app.Window',
    title: 'Lengow',
    layout: 'fit',

    initComponent: function () {
        var me = this;
        me.items = Ext.create('Ext.panel.Panel', {
            id : 'syncPanel',
            html: me.panelHtml,
            layout: 'fit'
        });
        me.callParent(arguments);
    },

    initFrame: function() {
        var me = this;

        // Loading message
        Ext.getCmp('syncPanel').getEl().mask();
        var sync_iframe = document.getElementById("lengow_iframe");
        if (sync_iframe) {
            if (me.syncLink) {
                // me.url = 'http://cms.lengow.io/sync/';
                me.url = 'http://cms.lengow.net/sync/';
            } else {
                // me.url = 'http://cms.lengow.io/';
                me.url = 'http://cms.lengow.net/';
            }
            sync_iframe.src = me.url;
            sync_iframe.onload = function() {
                Ext.Ajax.request({
                    url: '{url controller="LengowSync" action="getIsSync"}',
                    method: 'POST',
                    type: 'json',
                    params: {
                        syncAction: 'get_sync_data'
                    },
                    success: function (data) {
                        var response = Ext.decode(data.responseText).data;
                        document.getElementById("lengow_iframe").contentWindow.postMessage(response, '*');
                        // Unmask waiting message
                        Ext.getCmp('syncPanel').getEl().unmask();
                    }
                });
            };
            // Show iframe content
            sync_iframe.style.display = "block";
        }

        window.addEventListener('message', receiveMessage, false);

        function receiveMessage(event) {
            //if (event.origin !== "http://solution.lengow.com")
            //    return;

            switch (event.data.function) {
                case 'sync':
                    Ext.Ajax.request({
                        url: '{url controller="LengowSync" action="getIsSync"}',
                        method: 'POST',
                        type: 'json',
                        params: {
                            syncAction: 'sync',
                            data: Ext.encode(event.data.parameters)
                        }
                    });
                    break;
                case 'sync_and_reload':
                    Ext.Ajax.request({
                        url: '{url controller="LengowSync" action="getIsSync"}',
                        method: 'POST',
                        type: 'json',
                        params: {
                            syncAction: 'sync',
                            data: Ext.encode(event.data.parameters)
                        },
                        success: function() {
                            Shopware.app.Application.addSubApplication({
                                name: 'Shopware.apps.Lengow'
                            });
                        }
                    });
                    break;
                case 'reload':
                    Shopware.app.Application.addSubApplication({
                        name: 'Shopware.apps.Lengow'
                    });
                    break;
            }
        }
    }
});
//{/block}