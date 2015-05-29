//{namespace name="backend/lengow/view/main"}
//{block name="backend/lengow/view/main/settings"}
Ext.define('Shopware.apps.Lengow.view.main.Settings', {

    extend: 'Ext.form.Panel',

    alias:'widget.lengow-main-settings',

    name: 'lengow-settings-form-panel',

    bodyPadding: 5,

    autoScroll: true,

    snippets: {
        topToolbar: {
            selectShop:      '{s name=main/settings/topToolbar/select_shop}Choose a shop settings{/s}',
            selectShopEmpty: '{s name=main/settings/topToolbar/select_shop_empty}Select a shop...{/s}'
        },
        bottomToolbar: {
            save: '{s name=main/settings/bottomToolbar/save}Save settings{/s}'
        },
        account: {
            title: '{s name=main/settings/account/title}Account settings{/s}',
            customerId: {
                label:      '{s name=main/settings/account/customer_id/label}Customer ID{/s}',
                support:    '{s name=main/settings/account/customer_id/support}To edit this data, please go at : Configuration / Basic Settings / Additional settings / Lengow{/s}'
            },
            groupId: {
                label:      '{s name=main/settings/account/group_id/label}Group ID{/s}',
                support:    '{s name=main/settings/account/group_id/support}Your Group ID of Lengow{/s}'
            },
            apiKey: {
                label:      '{s name=main/settings/account/api_key/label}Token API{/s}',
                support:    '{s name=main/settings/account/api_key/support}To edit this data, please go at : Configuration / Basic Settings / Additional settings / Lengow{/s}'
            }
        }, 
        security: {
            title: '{s name=main/settings/security/title}Security settings{/s}',
            authorisedIp: {
                label:      '{s name=main/settings/security/customer_id/label}IP authorised to export{/s}',
                support:    '{s name=main/settings/security/customer_id/support}To edit this data, please go at : Configuration / Basic Settings / Additional settings / Lengow{/s}'
            }
        },
        exportation: {
            title: '{s name=main/settings/exportation/title}Exportation settings{/s}',
            allProducts: {
                label:      '{s name=main/settings/exportation/all_products/label}Export all products{/s}',
                boxLabel:   '{s name=main/settings/exportation/all_products/box_label}Uncheck this option to export only selected products{/s}'
            },
            disabledProducts: {
                label:      '{s name=main/settings/exportation/disabled_products/label}Export disabled products{/s}',
                boxLabel:   '{s name=main/settings/exportation/disabled_products/box_label}Check this option to export disabled products{/s}'
            },
            variantProducts: {
                label:      '{s name=main/settings/exportation/variant_products/label}Export variant products{/s}',
                boxLabel:   '{s name=main/settings/exportation/variant_products/box_label}Check this option to export all your products\' variations{/s}'
            },
            attributes: {
                label:      '{s name=main/settings/exportation/attributes/label}Export attributes{/s}',
                boxLabel:   '{s name=main/settings/exportation/attributes/box_label}Check this option to export your products with attributes{/s}'
            },
            attributesTitle: {
                label:      '{s name=main/settings/exportation/attributes_title/label}Title + attributes + features{/s}',
                boxLabel:   '{s name=main/settings/exportation/attributes_title/box_label}Check this option if you want a variation product title as title + attributes + feature. By default the title will be the product name{/s}'
            },
            outStock: {
                label:      '{s name=main/settings/exportation/out_stock/label}Export out of stock products{/s}',
                boxLabel:   '{s name=main/settings/exportation/out_stock/box_label}Check this option to export out of stock products{/s}'
            },
            imageSize: {
                label:      '{s name=main/settings/exportation/image_size/label}Image size to export{/s}',
                emptyText:  '{s name=main/settings/exportation/image_size/empty_text}Select a format...{/s}'
            },
            imageExport: {
                label:      '{s name=main/settings/exportation/image_export/label}Number of images to export{/s}',
                emptyText:  '{s name=main/settings/exportation/image_export/empty_text}Select a number...{/s}'
            },
            exportFormat: {
                label:      '{s name=main/settings/exportation/export_format/label}Export format{/s}',
                emptyText:  '{s name=main/settings/exportation/export_format/empty_text}Select a format...{/s}'
            },
            shippingCost: {
                label:      '{s name=main/settings/exportation/shipping_cost/label}Default shipping cost{/s}',
                emptyText:  '{s name=main/settings/exportation/shipping_cost/empty_text}Select a shipping cost...{/s}'
            },
            exportFile: {
                label:      '{s name=main/settings/exportation/export_file/label}Save feed on file{/s}',
                boxLabel:   '{s name=main/settings/exportation/export_file/box_label}Check this option if you have more than 3,000 products{/s}'
            },
            exportUrl: {
                label:      '{s name=main/settings/exportation/export_url/label}Our export URL{/s}'   
            },
            exportCron: {
                label:      '{s name=main/settings/exportation/export_cron/label}Active export cron{/s}',
                boxLabel:   '{s name=main/settings/exportation/export_cron/box_label}Check this option to export products automatically{/s}'
            }
        },
        importation: {
            title: '{s name=main/settings/importation/title}Importation settings{/s}',
            carrierDefault: {
                label:      '{s name=main/settings/importation/carrier_default/label}Default carrier{/s}',
                emptyText:  '{s name=main/settings/importation/carrier_default/empty_text}Select a carrier...{/s}'
            },
            orderProcess: {
                label:      '{s name=main/settings/importation/order_process/label}Status of process orders{/s}',
                emptyText:  '{s name=main/settings/importation/order_process/empty_text}Select a order status...{/s}'
            },
            orderShipped: {
                label:      '{s name=main/settings/importation/order_shipped/label}Status of shipped orders{/s}',
                emptyText:  '{s name=main/settings/importation/order_shipped/empty_text}Select a order status...{/s}'
            },
            orderCancel: {
                label:      '{s name=main/settings/importation/order_cancel/label}Status of cancel orders{/s}',
                emptyText:  '{s name=main/settings/importation/order_cancel/empty_text}Select a order status...{/s}'
            },
            importDay: {
                label:      '{s name=main/settings/importation/import_day/label}Import from x days{/s}'   
            },
            methodName: {
                label:      '{s name=main/settings/importation/method_name/label}Associated payment method{/s}',
                emptyText:  '{s name=main/settings/importation/method_name/empty_text}Select a payment method...{/s}'
            },
            // forcedPrice: {
            //     label:      '{s name=main/settings/importation/forced_price/label}Forced price{/s}',
            //     boxLabel:   '{s name=main/settings/importation/forced_price/box_label}Check this option to force the product prices of the marketplace orders during the import{/s}'
            // },
            reportMail: {
                label:      '{s name=main/settings/importation/report_mail/label}Report email{/s}',
                boxLabel:   '{s name=main/settings/importation/report_mail/box_label}Check this option for receive a report with every import{/s}'
            },
            emailAddress: {
                label:      '{s name=main/settings/importation/email_address/label}Send reports to{/s}',
                support:    '{s name=main/settings/importation/email_address/support}If report email are activated, the reports will be send to the specified address. Otherwise it will be your default shop email address{/s}'
            },
            importUrl: {
                label:      '{s name=main/settings/importation/import_url/label}Our import URL{/s}'   
            }, 
            importCron: {
                label:      '{s name=main/settings/importation/import_cron/label}Active import cron{/s}',
                boxLabel:   '{s name=main/settings/importation/import_cron/box_label}Check this option to import orders automatically{/s}'
            }
        }
    },

    initComponent: function() {
        var me = this;

        me.createStores();

        me.items = [ 
            me.createAccountFieldSet(),
            me.createSecurityFieldSet(),
            me.createExportFieldSet(), 
            me.createImportFieldSet()
        ]; 

        me.tbar = me.getTopToolbar();
        me.bbar = me.getBottomToolbar();

        me.addEvents(
            'changeShop',
            'saveSettings'
        ); 

        me.callParent(arguments);
    },

    createStores: function() {
        var me = this;
   
        me.dispathStore = Ext.create('Shopware.apps.Base.store.Dispatch');
        me.dispathStore.load();
        me.orderStatusStore = Ext.create('Shopware.apps.Base.store.OrderStatus');
        me.orderStatusStore.load();
        me.imageFormatsStore = Ext.create('Shopware.apps.Lengow.store.ImageFormats');
        me.imageFormatsStore.load();
        me.exportImagesStore = Ext.create('Shopware.apps.Lengow.store.ExportImages');
        me.exportImagesStore.load();
        me.exportFormatsStore = Ext.create('Shopware.apps.Lengow.store.ExportFormats');
        me.exportFormatsStore.load();  
        me.paymentMethodsStore = Ext.create('Shopware.apps.Lengow.store.PaymentMethods');
        me.paymentMethodsStore.load(); 
    },

    createAccountFieldSet: function() {
        var accountFieldSet,
            me = this;

        accountFieldSet = Ext.create('Ext.form.FieldSet', {
            title: me.snippets.account.title,
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
            title: me.snippets.security.title,
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
            title: me.snippets.exportation.title,
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
            title: me.snippets.importation.title,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },  
            items: me.createImportField()
        });
        return importFieldSet;
    },

    createAccountField: function() {
        var me = this;

        me.customerIdField = Ext.create('Ext.form.field.Text', {
            name: 'lengowIdUser',
            fieldLabel: me.snippets.account.customerId.label,
            supportText: me.snippets.account.customerId.support,
            disabled: true,
            labelWidth: 170
        });

        me.groupIdField = Ext.create('Ext.form.field.Text', {
            name: 'lengowIdGroup',
            fieldLabel: me.snippets.account.groupId.label,
            supportText: me.snippets.account.groupId.support,
            labelWidth: 170
        });

        me.apiKeyField = Ext.create('Ext.form.field.Text', {
            name: 'lengowApiKey',
            fieldLabel: me.snippets.account.apiKey.label,
            supportText: me.snippets.account.apiKey.support,
            disabled: true,
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
            fieldLabel: me.snippets.security.authorisedIp.label,
            supportText: me.snippets.security.authorisedIp.support,
            disabled: true,
            labelWidth: 170
        });

        return [ me.authorisedIpField ];
    },

    createExportField: function() {
        var me = this;

        me.exportAllProductsCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportAllProducts',
            fieldLabel: me.snippets.exportation.allProducts.label,
            inputValue: true,
            uncheckedValue: false,
            boxLabel: me.snippets.exportation.allProducts.boxLabel,
            labelWidth: 170
        });

        me.exportDisabledProductsCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportDisabledProducts',
            fieldLabel: me.snippets.exportation.disabledProducts.label,
            inputValue: true,
            uncheckedValue: false,
            boxLabel: me.snippets.exportation.disabledProducts.boxLabel,
            labelWidth: 170
        });

        me.exportVariantProductsCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportVariantProducts',
            fieldLabel: me.snippets.exportation.variantProducts.label,
            inputValue: true,
            uncheckedValue: false,
            boxLabel: me.snippets.exportation.variantProducts.boxLabel,
            labelWidth: 170
        });

        me.exportAttributesCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportAttributes',
            fieldLabel: me.snippets.exportation.attributes.label,
            inputValue: true,
            uncheckedValue: false,
            boxLabel: me.snippets.exportation.attributes.boxLabel,
            labelWidth: 170
        });

        me.exportAttributesTitleCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportAttributesTitle',
            fieldLabel: me.snippets.exportation.attributesTitle.label,
            inputValue: true,
            uncheckedValue: false,
            boxLabel: me.snippets.exportation.attributesTitle.boxLabel,
            labelWidth: 170
        });

        me.exportOutStockCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportOutStock',
            fieldLabel: me.snippets.exportation.outStock.label,
            inputValue: true,
            uncheckedValue: false,
            boxLabel: me.snippets.exportation.outStock.boxLabel,
            labelWidth: 170
        });

        me.imageSizeCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowExportImageSize',
            queryMode: 'remote',
            store: me.imageFormatsStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: me.snippets.exportation.imageSize.emptyText,
            allowBlank: false,
            fieldLabel: me.snippets.exportation.imageSize.label,
            labelWidth: 170
        });

        me.imageExportCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowExportImages',
            triggerAction:'all',
            queryMode: 'remote',
            store: me.exportImagesStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: me.snippets.exportation.imageExport.emptyText,
            allowBlank: false,
            fieldLabel: me.snippets.exportation.imageExport.label,
            labelWidth: 170
        });

        me.exportFormatCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowExportFormat',
            queryMode: 'remote',
            store: me.exportFormatsStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: me.snippets.exportation.exportFormat.emptyText,
            allowBlank: false,
            fieldLabel: me.snippets.exportation.exportFormat.label,
            labelWidth: 170
        });

        me.shippingCostDefaultCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowShippingCostDefault',
            queryMode: 'remote',
            store: me.dispathStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: me.snippets.exportation.shippingCost.emptyText,
            allowBlank: false,
            fieldLabel: me.snippets.exportation.shippingCost.label,
            labelWidth: 170
        });

        me.exportFileCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportFile',
            fieldLabel: me.snippets.exportation.exportFile.label,
            inputValue: true,
            uncheckedValue: false,
            boxLabel: me.snippets.exportation.exportFile.boxLabel,
            labelWidth: 170
        });

        me.exportUrlDisplay = Ext.create('Ext.form.field.Display', {
            name: 'lengowExportUrl',
            fieldLabel: me.snippets.exportation.exportUrl.label,
            labelWidth: 170
        });

        me.exportCronCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowExportCron',
            fieldLabel: me.snippets.exportation.exportCron.label,
            inputValue: true,
            uncheckedValue: false,
            boxLabel: me.snippets.exportation.exportCron.boxLabel,
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
            me.shippingCostDefaultCombo,
            me.exportFileCheck,
            me.exportUrlDisplay,
            me.exportCronCheck 
        ];
    },

    createImportField: function() {
        var me = this;

        me.carrierDefaultCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowCarrierDefault',
            queryMode: 'remote',
            store: me.dispathStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: me.snippets.importation.carrierDefault.emptyText,
            allowBlank: false,
            fieldLabel: me.snippets.importation.carrierDefault.label,
            labelWidth: 170
        });

        me.orderProcessCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowOrderProcess',
            queryMode: 'remote',
            store: me.orderStatusStore,
            valueField: 'id',
            displayField: 'description',
            emptyText: me.snippets.importation.orderProcess.emptyText,
            allowBlank: false,
            fieldLabel: me.snippets.importation.orderProcess.label,
            labelWidth: 170
        });

        me.orderShippedCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowOrderShipped',
            queryMode: 'remote',
            store: me.orderStatusStore,
            valueField: 'id',
            displayField: 'description',
            emptyText: me.snippets.importation.orderShipped.emptyText,
            allowBlank: false,
            fieldLabel: me.snippets.importation.orderShipped.label,
            labelWidth: 170
        });

        me.orderCancelCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowOrderCancel',
            queryMode: 'remote',
            store: me.orderStatusStore,
            valueField: 'id',
            displayField: 'description',
            emptyText: me.snippets.importation.orderCancel.emptyText,
            allowBlank: false,
            fieldLabel: me.snippets.importation.orderCancel.label,
            labelWidth: 170
        });

        me.importDayNumber = Ext.create('Ext.form.field.Number', {
            name: 'lengowImportDays',
            fieldLabel: me.snippets.importation.importDay.label,
            maxValue: 99,
            minValue: 1,
            labelWidth: 170 
        });

        me.methodNameCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'lengowMethodName',
            queryMode: 'remote',
            store: me.paymentMethodsStore,
            valueField: 'id',
            displayField: 'name',
            emptyText: me.snippets.importation.methodName.emptyText,
            allowBlank: false,
            fieldLabel: me.snippets.importation.methodName.label,
            labelWidth: 170
        });

        // me.forcedPriceCheck = Ext.create('Ext.form.field.Checkbox', {
        //     name: 'lengowForcePrice',
        //     fieldLabel: me.snippets.importation.forcedPrice.label,
        //     inputValue: true,
        //     uncheckedValue: false,
        //     boxLabel: me.snippets.importation.forcedPrice.boxLabel,
        //     labelWidth: 170
        // });

        me.reportMailCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowReportMail',
            fieldLabel: me.snippets.importation.reportMail.label,
            inputValue: true,
            uncheckedValue: false,
            boxLabel: me.snippets.importation.reportMail.boxLabel,
            labelWidth: 170
        });

        me.emailAddressField = Ext.create('Ext.form.field.Text', {
            name: 'lengowEmailAddress',
            fieldLabel: me.snippets.importation.emailAddress.label,
            supportText: me.snippets.importation.emailAddress.support,
            vtype: 'email',
            labelWidth: 170
        });

        me.importUrlDisplay = Ext.create('Ext.form.field.Display', {
            name: 'lengowImportUrl',
            fieldLabel: me.snippets.importation.importUrl.label,
            labelWidth: 170
        });

        me.importCronCheck = Ext.create('Ext.form.field.Checkbox', {
            name: 'lengowImportCron',
            fieldLabel: me.snippets.importation.importCron.label,
            inputValue: true,
            uncheckedValue: false,
            boxLabel: me.snippets.importation.importCron.boxLabel,
            labelWidth: 170
        });

        return [ 
            me.carrierDefaultCombo,
            me.orderProcessCombo,
            me.orderShippedCombo,
            me.orderCancelCombo,
            me.importDayNumber,
            me.methodNameCombo,
            // me.forcedPriceCheck,
            me.reportMailCheck,
            me.emailAddressField,
            me.importUrlDisplay,
            me.importCronCheck
        ];
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
            fieldLabel: me.snippets.topToolbar.selectShop,
            labelWidth: 150,
            triggerAction:'all',
            emptyText: me.snippets.topToolbar.selectShopEmpty,
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
            text: me.snippets.bottomToolbar.save,
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