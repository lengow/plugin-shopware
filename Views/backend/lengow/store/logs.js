//{block name="backend/lengow/store/logs"}
Ext.define('Shopware.apps.Lengow.store.Logs', {

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
    model: 'Shopware.apps.Lengow.model.Log',

    /**
     * Define how much rows loaded with one request
     */
    pageSize: 20,

    proxy:{
        type:'ajax',

        /**
         * Configure the url mapping for the different store operations based on
         * @object
         */
        url:'{url controller="LengowLog" action="getList"}',

        /**
         * Configure the data reader
         * @object
         */
        reader:{
            type:'json',
            root:'data',
            totalProperty:'total'
        }
    }

});
//{/block}