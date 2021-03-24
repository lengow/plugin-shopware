//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/main"}
Ext.define('Shopware.apps.Lengow.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;
        me.displayMainWindow();
        me.callParent(arguments);

        me.control({
            'lengow-main-home': {
                initToolbar: me.onInitToolbar,
                initLinkListener: me.onInitLinkListener,
                initLegalsTab: me.onInitLegalsTab
            }
        });
    },

    /**
     * Display window when plugin is launched
     * If user has no account id, show login iframe instead of the plugin
     */
    displayMainWindow: function() {
        var me = this;
        Ext.Ajax.request({
            url: '{url controller="Lengow" action="getConnection"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var data = Ext.decode(response.responseText)['data'];
                // if not a new merchant, display Lengow plugin
                if (!data['isNewMerchant']) {
                    me.mainWindow = me.getView('main.Home').create({
                        exportStore: Ext.create('Shopware.apps.Lengow.store.Article'),
                        importStore: Ext.create('Shopware.apps.Lengow.store.Orders'),
                        logStore: Ext.create('Shopware.apps.Lengow.store.Logs')
                    }).show();
                } else {
                    // display connection process
                    me.mainWindow = me.getView('main.Connection').create({
                        panelHtml: data['panelHtml']
                    }).show();
                    me.initGoToCredentialsButton();
                }
                // show main window
                me.mainWindow.maximize();
            }
        });
    },

    /**
     * Get debug/trial translations and html before updating concerned labels created in the toolbar
     */
    onInitToolbar: function() {
        Ext.Ajax.request({
            url: '{url controller="Lengow" action="getToolbarContent"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var data = Ext.decode(response.responseText)['data'],
                    count = Object.keys(data).length;
                if (count > 0) {
                    Ext.iterate(data, function (selector, htmlContent) {
                        Ext.getCmp(selector).update(htmlContent);
                    });
                } else {
                    var toolbar = Ext.getCmp('lengowMainToolbar');
                    if (toolbar !== 'undefined') {
                        // hide toolbar if nothing to show
                        toolbar.hide();
                    }
                }
            }
        });
    },

    /**
     * Listen to Lengow links and display concerned tab
     * (legals, export/settings blocks on the dashboard, help link, ...)
     */
    onInitLinkListener: function() {
        // get Lengow links (products & settings dashboard boxes, help link, ...)
        var tabShortcuts = Ext.query("a[id^=lengow][id$=Tab]");
        // for each one, listen on click and trigger concerned tab
        Ext.each(tabShortcuts, function(item) {
            item.onclick = function() {
                // get tab reference
                var tabEl = Ext.getCmp(item.id);
                Ext.getCmp('lengowTabPanel').setActiveTab(tabEl);
            };
        });
    },

    /**
     * Load legals tab content
     */
    onInitLegalsTab: function() {
        Ext.Ajax.request({
            url: '{url controller="Lengow" action="getLegalsTabContent"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var html = Ext.decode(response.responseText)['data'];
                Ext.getCmp('lengowLegalsTab').update(html);
            }
        });
    },

    /**
     * Listen to "Go to credentials" button for connection
     */
    initGoToCredentialsButton: function() {
        var me = this;
        var button = Ext.query('button[id=js-go-to-credentials]')[0];
        button.onclick = function() {
            Ext.Ajax.request({
                url: '{url controller="LengowConnection" action="goToCredentials"}',
                method: 'POST',
                type: 'json',
                success: function(response) {
                    var data = Ext.decode(response.responseText)['data'];
                    Ext.get('lgw-connection-content').update(data['html']);
                    me.initCheckCredentialsButton();
                    me.initConnectCmsButton();
                }
            });
        };
    },

    /**
     * active check credentials button
     */
    initCheckCredentialsButton: function() {
        var inputs = document.getElementsByClassName('js-credentials-input');
        Ext.Array.each(inputs, function(input) {
            input.onchange = function() {
                var button = Ext.select('button[id=js-connect-cms]');
                var accessToken = Ext.get('lgw-access-token').getValue();
                var secret = Ext.get('lgw-secret').getValue();
                if (accessToken !== '' && secret !== '') {
                    button.removeCls('lgw-btn-disabled')
                        .addCls('lgw-btn-green');
                } else{
                    button.addCls('lgw-btn-disabled')
                        .removeCls('lgw-btn-green');
                }
            };
        });
    },

    /**
     * Listen to "Connect cms" button for connection
     */
    initConnectCmsButton: function() {
        var me = this;
        var button = Ext.query('button[id=js-connect-cms]')[0];
        button.onclick = function() {
            var accessToken = document.getElementById('lgw-access-token');
            var secret = document.getElementById('lgw-secret');
            Ext.select('button[id=js-connect-cms]').addCls('loading');
            accessToken.disabled = true;
            secret.disabled = true;
            Ext.Ajax.request({
                url: '{url controller="LengowConnection" action="connectCms"}',
                method: 'POST',
                type: 'json',
                params: {
                    accessToken: accessToken.value,
                    secret: secret.value,
                },
                success: function(response) {
                    var data = Ext.decode(response.responseText)['data'];
                    Ext.get('lgw-connection-content').update(data['html']);
                    if (data['cmsConnected']) {
                        if (data['hasCatalogToLink']) {
                            me.initGoToCatalogButton();
                        } else {
                            me.initGoToDashboardButton();
                        }
                    } else {
                        me.initGoToCredentialsButton();
                    }
                }
            });
        };
    },

    /**
     * Listen to "Go to catalog" button for connection
     */
    initGoToCatalogButton: function() {
        var me = this;
        var button = Ext.query('button[id=js-go-to-catalog]')[0];
        button.onclick = function() {
            var retry = this.getAttribute('data-retry') !== 'false';
            Ext.Ajax.request({
                url: '{url controller="LengowConnection" action="goToCatalog"}',
                method: 'POST',
                type: 'json',
                params: {
                    retry: retry,
                },
                success: function(response) {
                    var data = Ext.decode(response.responseText)['data'];
                    Ext.get('lgw-connection-content').update(data['html']);
                    me.initLinkCatalogButton();
                    me.initDisableCatalogOption();
                }
            });
        };
    },

    /**
     * active check credentials button
     */
    initDisableCatalogOption: function() {
        var selects = document.getElementsByClassName('js-catalog-linked');
        Ext.Array.each(selects, function(select) {
            select.onchange = function() {
                var currentShopId = this.getAttribute('name');
                // get all catalogs selected by shop
                var catalogSelected = [];
                var shopSelect = document.getElementsByClassName('js-catalog-linked');
                Ext.Array.each(shopSelect, function(select) {
                    var shopId = select.getAttribute('name');
                    for (var i = 0, len = select.length; i < len; i++) {
                        var opt = select.options[i];
                        if (opt.value !== '' && opt.selected === true) {
                            catalogSelected.push({
                                shopId: shopId,
                                catalogId: opt.value
                            })
                        }
                    }
                });
                // disable catalog option for other shop
                Ext.Array.each(shopSelect, function(select) {
                    var shopId = select.getAttribute('name');
                    if (shopId !== currentShopId) {
                        var catalogLinked = [];
                        Ext.Array.each(catalogSelected, function(selection) {
                            if (selection.shopId !== shopId) {
                                catalogLinked.push(selection.catalogId);
                            }
                        });
                        for (var i = 0, len = select.length; i < len; i++) {
                            var opt = select.options[i];
                            opt.disabled = catalogLinked.includes(opt.value);
                        }
                    }
                });
            };
        });
    },

    /**
     * Listen to "Link catalog" button for connection
     */
    initLinkCatalogButton: function() {
        var me = this;
        var button = Ext.query('button[id=js-link-catalog]')[0];
        button.onclick = function() {
            Ext.select('button[id=js-link-catalog]').addCls('loading');
            var catalogSelected = [];
            var shopSelect = document.getElementsByClassName('js-catalog-linked');
            Ext.Array.each(shopSelect, function(select) {
                select.disabled = true;
                var catalogIds = [];
                for (var i = 0, len = select.length; i < len; i++) {
                    var opt = select.options[i];
                    if (opt.value !== '' && opt.selected === true) {
                        catalogIds.push(parseInt(opt.value, 10));
                    }
                }
                if (catalogIds.length > 0) {
                    catalogSelected.push({
                        shopId: parseInt(select.getAttribute('name'), 10),
                        catalogId: catalogIds,
                    });
                }
            });
            Ext.Ajax.request({
                url: '{url controller="LengowConnection" action="linkCatalog"}',
                method: 'POST',
                type: 'json',
                params: {
                    catalogSelected: JSON.stringify(catalogSelected),
                },
                success: function(response) {
                    var data = Ext.decode(response.responseText);
                    if (data['success']) {
                        // refresh Lengow by launching a new instance of the plugin
                        Shopware.app.Application.addSubApplication({
                            name: 'Shopware.apps.Lengow'
                        });
                    } else {
                        Ext.get('lgw-connection-content').update(data['data']['html']);
                        me.initGoToCatalogButton();
                        me.initGoToDashboardButton();
                    }
                }
            });
        };
    },

    /**
     * Listen to "Go to dashboard" button for connection
     */
    initGoToDashboardButton: function () {
        var button = Ext.query('button[id=js-go-to-dashboard]')[0];
        button.onclick = function () {
            // refresh Lengow by launching a new instance of the plugin
            Shopware.app.Application.addSubApplication({
                name: 'Shopware.apps.Lengow'
            });
        };
    }
});
//{/block}