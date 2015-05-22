//{namespace name="backend/lengow/controller"}
//{block name="backend/lengow/controller/log"}
Ext.define('Shopware.apps.Lengow.controller.Log', {

    extend:'Ext.app.Controller',

    refs: [
        { ref: 'logGrid', selector: 'lengow-main-logs' }
    ],

    snippets: {
        message: {
            deleteLogTitle: '{s name=log/message/delete_log_title}Delete selected Log(s)?{/s}',
            deleteLog: '{s name=log/message/delete_log}Are you sure you want to delete the selected Log(s)?{/s}',
            flushLogsTitle: '{s name=log/message/flush_logs_title}Flush Logs?{/s}',
            flushLogs: '{s name=log/message/flush_logs}Are you sure you want to delete all Logs?{/s}'
        }
    },

    init:function () {
        var me = this;
        me.control({
            'lengow-main-logs': {
                deleteLog: me.onDeleteLog,
                deleteSelectedLogs: me.onDeleteSelectedLogs,
                flushLogs: me.onFlushLogs
            }
        });
        me.callParent(arguments);
    },

    onDeleteLog: function(record) {
        var me      = this,
            store   = me.getLogGrid().getStore(),
            logGrid = me.getLogGrid();    

        Ext.MessageBox.confirm(me.snippets.message.deleteLogTitle, me.snippets.message.deleteLog, function (response) {
            if (response !== 'yes') {
                return false;
            }
            store.remove(record);
            logGrid.setLoading(true);
            record.destroy({
                success: function() {
                    logGrid.setLoading(false);
                    store.load();
                },
                failure: function(result, operation) {
                    logGrid.setLoading(false);
                    store.load();
                }
            });
        });
    },

    onDeleteSelectedLogs: function(records) {
        var me      = this,
            store   = me.getLogGrid().getStore(),
            logGrid = me.getLogGrid();

        if (records.length > 0) {
            Ext.MessageBox.confirm(me.snippets.message.deleteLogTitle, me.snippets.message.deleteLog, function (response) {
                if ( response !== 'yes' ) {
                    return;
                }
                logGrid.setLoading(true);
                for (var i = 0; i < records.length; i++) {
                    console.log(records[i]);
                    records[i].destroy();
                };
                store.load({
                    callback: function() {
                        store.remove(records);
                        logGrid.setLoading(false);
                    }
                });   
            });
        }
    },

    onFlushLogs: function(records) {
        var me      = this,
            store   = me.getLogGrid().getStore(),
            logGrid = me.getLogGrid();

        Ext.MessageBox.confirm(me.snippets.message.flushLogsTitle, me.snippets.message.flushLogs, function (response) {
            if ( response !== 'yes' ) {
                return;
            }
            logGrid.setLoading(true);
            Ext.Ajax.request({
                url: '{url controller="LengowLog" action="flushLogs"}',
                method: 'POST',
                params: {},
                success: function(response, opts) {
                    store.load({
                        callback: function() {
                            logGrid.setLoading(false);
                        }
                    });    
                }
            });
        });
    }

});
//{/block}