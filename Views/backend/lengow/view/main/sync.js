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

        // loading message
        Ext.getCmp('syncPanel').getEl().mask();
        var syncIframe = document.getElementById("lengow_iframe");
        if (syncIframe) {
            if (me.syncLink) {
                // me.url = '//cms.lengow.io/sync/';
                // me.url = '//cms.lengow.net/sync/';
                me.url = '//cms.lengow.rec/sync/';
                // me.url = '//cms.lengow.dev/sync/';
            } else {
                // me.url = '//cms.lengow.io/';
                // me.url = '//cms.lengow.net/';
                me.url = '//cms.lengow.rec/';
                // me.url = '//cms.lengow.dev/';
            }
            syncIframe.src = me.url+'?lang='+me.langIsoCode+'&clientType=shopware';
            syncIframe.onload = function() {
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
                        // unmask waiting message
                        Ext.getCmp('syncPanel').getEl().unmask();
                    }
                });
            };
            // show iframe content
            syncIframe.style.display = "block";
        }

        window.addEventListener('message', receiveMessage, false);

        function receiveMessage(event) {
            switch (event.data.function) {
                case 'sync':
                    // store lengow information into Shopware :
                    // account_id
                    // access_token
                    // secret_token
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
                    // store lengow information into Shopware and reload it
                    // account_id
                    // access_token
                    // secret_token
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
                case 'cancel':
                    // reload the parent page (after sync is ok)
                    Shopware.app.Application.addSubApplication({
                        name: 'Shopware.apps.Lengow'
                    });
                    break;
            }
        }
    }
});
//{/block}