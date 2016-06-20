/**
 * Created by nicolasmaugendre on 17/06/16.
 */

Ext.define('Shopware.apps.Lengow.model.Logs', {
    extend: 'Shopware.data.Model',
    alias:  'model.logs',

    fields: [
        { name : 'id', type: 'int' },
        { name : 'name', type: 'string' }
    ],

    configure: function() {
        return {
            controller: 'Lengow'
        };
    }
});

