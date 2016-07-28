//{namespace name="backend/lengow/view/home"}
//{block name="backend/lengow/view/home/panel"}
Ext.define('Shopware.apps.Lengow.view.home.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-home-panel',
    autoScroll: true,
    title: '{s name="window/tab/home" namespace="backend/Lengow/translation"}Home{/s}',
    style: {
        background: '#F7F7F7'
    },

    constructor: function () {
        var me = this;
        me.getHomeContent();
        me.callParent();
    },

    /**
     * Display home page in the panel
     */
    getHomeContent: function() {
        var me = this;
        Ext.Ajax.request({
            url: '{url controller="LengowHome" action="getHomeContent"}',
            method: 'POST',
            type: 'json',
            success: function(response) {
                var html = Ext.decode(response.responseText)['data'];
                // Load html in the panel
                me.update(html, me.loadScripts);
                // Get home page boxes (products & settings)
                var productBox = Ext.query("*[class^=shopware-menu-link]");
                // For each one, listen on click and trigger concerned tab
                Ext.each(productBox, function(item) {
                    var el = Ext.get(item);
                    el.on('click', function() {
                        var tabId = el.dom.firstElementChild.id,
                            tabEl = Ext.getCmp(tabId);
                        Ext.getCmp('lengowTabPanel').setActiveTab(tabEl);
                    });
                });
            }
        });
    },
    loadScripts: function()
    {
        alert('rtetre');
    }
});
//{/block}