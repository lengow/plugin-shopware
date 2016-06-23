/**
 * Created by nicolasmaugendre on 17/06/16.
 */

Ext.define('Shopware.apps.Lengow.store.Logs', {
    extend:'Ext.data.TreeStore',
    alias: 'store.lengow-logs',
    model: 'Shopware.apps.Lengow.model.Logs',

    id: 'lengow-logs',

    defaultRootId: 1,
    autoLoad: true, 
    autoSync: true,

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