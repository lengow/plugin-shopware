//{block name="backend/lengow/store/logs"}
Ext.define('Shopware.apps.Lengow.store.Logs', {
    extend:'Ext.data.Store',
    alias: 'store.lengow-logs',
    model: 'Shopware.apps.Lengow.model.Logs',

    // translations
    snippets: {
        all: '{s name="log/panel/select_all" namespace="backend/Lengow/translation"}{/s}'
    },

    configure: function() {
        return { controller: 'LengowLogs' };
    },

    listeners: {
        load: function(store){
            var me = this;

            // format to display full date (day month year)
            store.each(function(record) {
                var logDate = record.get('date');
                if (logDate !== '') {
                    var date = Ext.Date.parse(logDate, 'd m Y'),
                        value = Ext.Date.format(date, 'l d F Y');
                    record.set('date', value);
                }
            });

            // add record to download all logs
            var allLogs = { id: '', name: me.snippets.all, date: me.snippets.all};
            store.insert(0, allLogs);
        }
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
//{/block}