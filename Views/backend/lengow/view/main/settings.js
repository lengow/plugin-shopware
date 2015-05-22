//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/settings"}
Ext.define('Shopware.apps.Lengow.view.main.Settings', {

    extend: 'Ext.form.Panel',

    alias:'widget.lengow-main-settings',

    name: 'lengow-settings-form-panel',

    bodyPadding: 5,

    autoScroll: true,

    initComponent: function() {
        var me = this;

        me.items = [ 
            me.createAccountFieldSet(),
            me.createSecurityFieldSet(),
            me.createExportFieldSet(), 
            me.createImportFieldSet(),
            me.createDevelopmentFieldSet()
        ]; 

        me.tbar = me.getTopToolbar();
        me.bbar = me.getBottomToolbar();

        me.addEvents(
            'changeShop',
            'saveSettings'
        ); 

        me.callParent(arguments);
    },

    createAccountFieldSet: function() {
        var accountFieldSet,
            me = this;

        accountFieldSet = Ext.create('Ext.form.FieldSet', {
            title: 'Account settings',
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },  
            items: me.createAccountField()
        });

        return accountFieldSet;
    },

    createSecurityFieldSet: function() {
        var securityFieldSet,
            me = this;

        securityFieldSet = Ext.create('Ext.form.FieldSet', {
            title: 'Security settings',
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },  
            items: me.createSecurityField()
        });

        return securityFieldSet;
    },

    createExportFieldSet: function() {
        var exportFieldSet,
            me = this;

        exportFieldSet = Ext.create('Ext.form.FieldSet', {
            title: 'Exportation settings',
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },  
            items: me.createExportField()
        });
        return exportFieldSet;
    },

    createImportFieldSet: function() {
        var importFieldSet,
            me = this;

        importFieldSet = Ext.create('Ext.form.FieldSet', {
            title: 'Importation settings',
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },  
            items: me.createImportField()
        });
        return importFieldSet;
    },

    createDevelopmentFieldSet: function() {
        var developmentFieldSet,
            me = this;

        developmentFieldSet = Ext.create('Ext.form.FieldSet', {
            title: 'Development settings',
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },  
            items: me.createDevelopmentField()
        });
        return developmentFieldSet;
    },

    createAccountField: function() {
        var me = this;

        me.customerIdField = Ext.create('Ext.form.field.Text', {
            name: 'lengowIdUser',
            fieldLabel: 'Customer ID',
            supportText: 'Your Customer ID of Lengow',
            labelWidth: 170
        });

        me.groupIdField = Ext.create('Ext.form.field.Text', {
            name: 'lengowIdGroup',
            fieldLabel: 'Group ID',
            supportText: 'Your Group ID of Lengow',
            labelWidth: 170
        });

        me.apiKeyField = Ext.create('Ext.form.field.Text', {
            name: 'lengowApiKey',
            fieldLabel: 'Token API',
            supportText: 'Your Token API of Lengow',
            labelWidth: 170
        });

        return [
            me.customerIdField,
            me.groupIdField,
            me.apiKeyField
        ];
    },

    createSecurityField: function() {
        var me = this;

        me.authorisedIpField = Ext.create('Ext.form.field.Text', {
            name: 'lengowAuthorisedIp',
            fieldLabel: 'IP authorised to export',
            supportText: 'Authorized access to catalog export by IP, separated by ;',
            labelWidth: 170
        });

        return [ me.authorisedIpField ];
    },

    createExportField: function() {
        var me = this;

        me.exportAllProductsCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportAllProducts',
            fieldLabel: 'Export all products',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'If don\'t want to export all your available products, uncheck and select yours products',
            labelWidth: 170
        });

        me.exportDisabledProductsCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportDisabledProducts',
            fieldLabel: 'Export disabled products',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'Check this option if you want to export disabled products',
            labelWidth: 170
        });

        me.exportVariantProductsCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportVariantProducts',
            fieldLabel: 'Export variant products',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'Check this option if you want to export all your products\' variations',
            labelWidth: 170
        });

        me.exportAttributesCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportAttributes',
            fieldLabel: 'Export disabled products',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'Check this option if you want to export your products with attributes',
            labelWidth: 170
        });

        me.exportAttributesTitleCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportAttributesTitle',
            fieldLabel: 'Title + attributes + features',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'Check this option if you want a variation product title as title + attributes + feature. By default the title will be the product name',
            labelWidth: 170
        });

        me.exportOutStockCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportOutStock',
            fieldLabel: 'Export out of stock product',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'Check this option if you want to export out of stock product',
            labelWidth: 170
        });

        var imageFormatsStore = Ext.create('Shopware.apps.Lengow.store.ImageFormats');
        imageFormatsStore.filters.clear();
        imageFormatsStore.load();

        me.imageSizeCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowExportImageSize',
            queryMode: 'remote',
            store: imageFormatsStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: 'Select a format...',
            allowBlank: false,
            fieldLabel: 'Image size to export',
            labelWidth: 170
        });

        var exportImagesStore = Ext.create('Shopware.apps.Lengow.store.ExportImages');
        exportImagesStore.filters.clear();
        exportImagesStore.load();

        me.imageExportCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowExportImages',
            triggerAction:'all',
            queryMode: 'remote',
            store: exportImagesStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: 'Select a number...',
            allowBlank: false,
            fieldLabel: 'Number of images to export',
            labelWidth: 170
        });

        var exportFormatsStore = Ext.create('Shopware.apps.Lengow.store.ExportFormats');
        exportFormatsStore.filters.clear();
        exportFormatsStore.load();

        me.exportFormatCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowExportFormat',
            queryMode: 'remote',
            store: exportFormatsStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: 'Select a format...',
            allowBlank: false,
            fieldLabel: 'Export format',
            labelWidth: 170
        });

        me.exportFileCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportFile',
            fieldLabel: 'Save feed on file',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'Check this option if you have more than 10,000 products',
            labelWidth: 170
        });

        me.exportUrlDisplay = Ext.create('Ext.form.field.Display', {
            name: 'lengowExportUrl',
            fieldLabel: 'Our export URL',
            labelWidth: 170
        });

        return [
            me.exportAllProductsCheck,
            me.exportDisabledProductsCheck,
            me.exportVariantProductsCheck,
            me.exportAttributesCheck,
            me.exportAttributesTitleCheck,
            me.exportOutStockCheck,
            me.imageSizeCombo,
            me.imageExportCombo,
            me.exportFormatCombo,
            me.exportFileCheck,
            me.exportUrlDisplay 
        ];
    },

    createImportField: function() {
        var me = this;

        var dispathStore = Ext.create('Shopware.apps.Base.store.Dispatch');
        dispathStore.filters.clear();
        dispathStore.load();

        me.carrierDefaultCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowCarrierDefault',
            queryMode: 'remote',
            store: dispathStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: 'Select a shipping cost...',
            allowBlank: false,
            fieldLabel: 'Default shipping cost',
            labelWidth: 170
        });

        var orderStatusStore = Ext.create('Shopware.apps.Base.store.OrderStatus');
        orderStatusStore.filters.clear();
        orderStatusStore.load();

        me.orderProcessCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowOrderProcess',
            queryMode: 'remote',
            store: orderStatusStore,
            valueField: 'id',
            displayField: 'description',
            emptyText: 'Select a order status...',
            allowBlank: false,
            fieldLabel: 'Status of process orders',
            labelWidth: 170
        });

        me.orderShippedCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowOrderShipped',
            queryMode: 'remote',
            store: orderStatusStore,
            valueField: 'id',
            displayField: 'description',
            emptyText: 'Select a state...',
            allowBlank: false,
            fieldLabel: 'Status of shipped orders',
            labelWidth: 170
        });

        me.orderCancelCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowOrderCancel',
            queryMode: 'remote',
            store: orderStatusStore,
            valueField: 'id',
            displayField: 'description',
            emptyText: 'Select a order status...',
            allowBlank: false,
            fieldLabel: 'Status of cancelled orders',
            labelWidth: 170
        });

        me.importDayNumber = Ext.create('Ext.form.field.Number', {
            name: 'lengowImportDays',
            fieldLabel: 'Import from x days',
            maxValue: 99,
            minValue: 1,
            labelWidth: 170 
        });

        var paymentMethodsStore = Ext.create('Shopware.apps.Lengow.store.PaymentMethods');
        paymentMethodsStore.filters.clear();

        me.methodNameCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowMethodName',
            queryMode: 'remote',
            store: paymentMethodsStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: 'Select a payment method...',
            allowBlank: false,
            fieldLabel: 'Associated payment method',
            labelWidth: 170
        });

        me.forcedPriceCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowForcePrice',
            fieldLabel: 'Forced price',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'Check this option to force the product prices of the marketplace orders during the import',
            labelWidth: 170
        });

        me.reportMailCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowReportMail',
            fieldLabel: 'Report email',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'Check this option for receive a report with every import on the email address configured',
            labelWidth: 170
        });

        me.emailAddressField = Ext.create('Ext.form.field.Text', {
            name: 'lengowEmailAddress',
            fieldLabel: 'Send reports to',
            supportText: 'If report emails are activated, the reports will be send to the specified address. Otherwise it will be your default shop email address',
            labelWidth: 170
        });

        me.importUrlDisplay = Ext.create('Ext.form.field.Display', {
            name: 'lengowImportUrl',
            fieldLabel: 'Our import URL',
            labelWidth: 170
        });

        me.exportCronCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportCron',
            fieldLabel: 'Active import cron',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'Check this option to import orders automatically',
            labelWidth: 170
        });

        return [ 
            me.carrierDefaultCombo,
            me.orderProcessCombo,
            me.orderShippedCombo,
            me.orderCancelCombo,
            me.importDayNumber,
            me.methodNameCombo,
            me.forcedPriceCheck,
            me.reportMailCheck,
            me.emailAddressField,
            me.importUrlDisplay,
            me.exportCronCheck
        ];
    },

    createDevelopmentField: function() {
        var me = this;

        me.debugModeCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowDebug',
            fieldLabel: 'Debug mode',
            inputValue: true,
            uncheckedValue: false,
            boxLabel: 'Use it only during tests.',
            labelWidth: 170
        });

        return [ me.debugModeCheck ];
    },


    getTopToolbar: function() {
        var me = this, 
            buttons = [];

        buttons.push({
            xtype: 'tbfill'
        });

        var shopStore = Ext.create('Shopware.apps.Base.store.Shop');
        shopStore.filters.clear();
        shopStore.load({
            callback: function(records) {
                me.shopCombo.setValue(records[0].get('id'));
            }
        });

        me.shopCombo = Ext.create('Ext.form.field.ComboBox', {
            fieldLabel: 'Choose a shop for settings',
            labelWidth: 150,
            triggerAction:'all',
            emptyText: 'select a shop...',
            store: shopStore,
            name: 'shopSetting',
            valueField: 'id',
            displayField: 'name',
            queryMode: 'remote',
            editable: false,
            listeners: {
                'select': function() {
                    if (this.store.getAt('0')) {
                        me.fireEvent('changeShop');
                    }
                }
            }
        });
        buttons.push(me.shopCombo);

        buttons.push({
            xtype: 'tbspacer',
            width: 6
        });

        return Ext.create('Ext.toolbar.Toolbar', {
            ui: 'shopware-ui',
            padding: '5 0 5 0',
            items: buttons
        });
    },

    getBottomToolbar: function() {
        var me = this, 
            buttons = [];

        buttons.push({
            xtype: 'tbfill'
        });

        me.saveSettingButton = Ext.create('Ext.button.Button', {
            cls: 'primary',
            text: 'Save',
            formBind: true,
            handler: function() {
                me.fireEvent('saveSettings');
            }
        });
        buttons.push(me.saveSettingButton);

        buttons.push({
            xtype: 'tbspacer',
            width: 6
        });

        return Ext.create('Ext.toolbar.Toolbar', {
            ui: 'shopware-ui',
            items: buttons
        });

    }
  
});
//{/block}