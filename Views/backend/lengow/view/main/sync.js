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
                //synchronisation des boutiques, à modifier lorsque l'API sera disponible
                // sync_iframe.src = 'http://cms.lengow.io/sync/';
                me.url = 'http://cms.lengow.dev/sync/';
            } else {
                // sync_iframe.src = 'http://cms.lengow.io/';
                me.url = 'http://cms.lengow.dev/';
            }
            sync_iframe.src = me.url;
            sync_iframe.onload = function() {
                Ext.Ajax.request({
                    url: '{url controller="LengowSync" action="getIsSync"}',
                    method: 'POST',
                    type: 'json',
                    params: {
                        action: 'get_sync_data'
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
            console.log(event.data);

            /*switch (event.data.function) {
                case 'sync':
                    Ext.Ajax.request({
                        url: href,
                        method: 'POST',
                        type: 'script'
                    });
                    break;
                case 'sync_and_reload':
                    Ext.Ajax.request({
                        url: href,
                        method: 'POST',
                        type: 'script',
                        success: function() {
                            location.reload();
                        }
                    });
                    break;
                case 'reload':
                    location.reload();
                    break;
            }*/
        }
    }
});
//{/block}