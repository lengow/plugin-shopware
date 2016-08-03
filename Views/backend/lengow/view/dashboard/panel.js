//{namespace name="backend/lengow/view/dashboard"}
//{block name="backend/lengow/view/dashboard/panel"}
Ext.define('Shopware.apps.Lengow.view.dashboard.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lengow-dashboard-panel',
    autoScroll: true,
    title: '{s name="window/tab/home" namespace="backend/Lengow/translation"}Home{/s}',
    style: {
        background: '#F7F7F7'
    }
});
//{/block}