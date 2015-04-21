//{block name="backend/lengow/store/orders"}
Ext.define('Shopware.apps.Lengow.store.Orders', {

    /**
     * Define that this component is an extension of the Ext.data.Store
     */
    extend: 'Ext.data.Store',

    /**
     * Enable remote filtering
     */
    remoteFilter: true,

    /**
     * Enable remote sorting
     */
    remoteSort: true, 

    /**
     * Auto load the store after the component is initialized
     * @boolean
     */
    autoLoad: false,

    /**
     * Define the used model for this store
     * @string
     */
    model: 'Shopware.apps.Lengow.model.Order',

    /**
     * Define how much rows loaded with one request
     */
    pageSize: 40

});
//{/block}