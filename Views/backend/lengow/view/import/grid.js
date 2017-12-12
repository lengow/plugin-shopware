//{namespace name="backend/lengow/view/import"}
//{block name="backend/lengow/view/import/grid"}
Ext.define('Shopware.apps.Lengow.view.import.Grid', {
    extend: 'Ext.grid.Panel',
    alias:  'widget.order-listing-grid',

    loadMask:true,

    // Translations
    snippets: {
        column: {
            actions: '{s name="order/grid/column/actions" namespace="backend/Lengow/translation"}{/s}',
            lengow_status: '{s name="order/grid/column/lengow_status" namespace="backend/Lengow/translation"}{/s}',
            marketplace: '{s name="order/grid/column/marketplace" namespace="backend/Lengow/translation"}{/s}',
            store_name: '{s name="order/grid/column/store_name" namespace="backend/Lengow/translation"}{/s}',
            marketplace_sku: '{s name="order/grid/column/marketplace_sku" namespace="backend/Lengow/translation"}{/s}',
            shopware_sku: '{s name="order/grid/column/shopware_sku" namespace="backend/Lengow/translation"}{/s}',
            shopware_status: '{s name="order/grid/column/shopware_status" namespace="backend/Lengow/translation"}{/s}',
            order_date: '{s name="order/grid/column/order_date" namespace="backend/Lengow/translation"}{/s}',
            customer_name: '{s name="order/grid/column/customer_name" namespace="backend/Lengow/translation"}{/s}',
            country: '{s name="order/grid/column/country" namespace="backend/Lengow/translation"}{/s}',
            nb_items: '{s name="order/grid/column/nb_items" namespace="backend/Lengow/translation"}{/s}',
            total_paid: '{s name="order/grid/column/total_paid" namespace="backend/Lengow/translation"}{/s}'
        }
    },

    /**
     * Init components used by the container
     */
    initComponent: function() {
        var me = this;

        me.store = me.importStore;
        me.columns = me.getColumns();

        me.callParent(arguments);
    },

    /**
     *  Creates the columns
     */
    getColumns: function(){
        var me = this;

        var columns = [
            {
                header: me.snippets.column.actions,
                dataIndex: 'id',
                flex: 1
            }, {
                header: me.snippets.column.lengow_status,
                dataIndex: 'orderLengowState',
                flex: 1
            }, {
                header: me.snippets.column.marketplace,
                dataIndex: 'marketplaceName',
                flex: 1
            }, {
                header: me.snippets.column.store_name,
                dataIndex: 'storeName',
                flex: 1
            }, {
                header: me.snippets.column.marketplace_sku,
                dataIndex: 'marketplaceSku',
                flex: 1
            }, {
                header: me.snippets.column.shopware_status,
                dataIndex: 'orderStatus',
                flex: 1
            }, {
                //TODO link to shopware order
                header: me.snippets.column.shopware_id,
                dataIndex: 'orderId',
                flex: 1
            }, {
                header: me.snippets.column.order_date,
                dataIndex: 'orderDate',
                flex: 1
            }, {
                header: me.snippets.column.customer_name,
                dataIndex: 'customerName',
                flex: 1
            }, {
                header: me.snippets.column.country,
                dataIndex: 'deliveryCountryIso',
                flex: 1
            }, {
                header: me.snippets.column.nb_items,
                dataIndex: 'orderItem',
                flex: 1
            }, {
                header: me.snippets.column.total_paid,
                dataIndex: 'totalPaid',
                flex: 1
            }
        ];
        return columns;
    }
});
//{/block}