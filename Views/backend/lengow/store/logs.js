/**
 * Created by nicolasmaugendre on 17/06/16.
 */

Ext.define('Shopware.apps.Lengow.store.Logs', {
    extend:'Ext.data.Store',
    alias: 'store.lengow-logs',
    model: 'Shopware.apps.Lengow.model.Logs',

    configure: function() {
        return { controller: 'LengowLogs' };
    },

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller="LengowLogs" action="list"}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});