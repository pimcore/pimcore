/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.helpers.import.configDialog");
pimcore.object.helpers.import.configDialog = Class.create({

    initialize: function (config) {

        this.uniqueImportId = uniqid();
        this.config = {};

        this.tree = config.tree;
        if (config.parentNode) {
            this.parentId = config.parentNode.id;
            this.parentNode = config.parentNode;
        }
        this.classId = config.classId;
        this.className = config.className;
        this.additionalData = config.additionalData || {};

        if (config.mode == "direct") {
            this.uniqueImportId = config.uniqueImportId;
            this.parentId = config.parentId;
            this.getFileInfo(false, config.importConfigId);
        } else {
            this.showUpload();
        }
    },

    showUpload: function () {

        pimcore.helpers.uploadDialog('/admin/object-helper/import-upload?importId=' + this.uniqueImportId, "Filedata", function (res) {
            this.getFileInfo(false, null);
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    },

    buildDefaultSelection: function () {
        this.config.selectedGridColumns = [];
        var ignoreImpl = pimcore.object.importcolumn.operator.ignore.prototype;

        if (this.config.dataFields) {
            for (var i = 0; i < this.config.dataFields.length; i++) {
                this.config.selectedGridColumns.push(
                    {
                        isOperator: true,
                        attributes: {
                            type: ignoreImpl.type,
                            class: ignoreImpl.class,
                            isOperator: true
                        }
                    }
                );
            }
        }
    },


    showWindow: function (data) {
        var config = data.config;

        if (!this.importConfigId) {
            this.buildDefaultSelection();
        }

        this.csvPreviewPanel = new pimcore.object.helpers.import.csvPreviewTab(this.config, this);
        this.columnConfigPanel = new pimcore.object.helpers.import.columnConfigurationTab(this.config, this);
        this.resolverSettingsPanel = new pimcore.object.helpers.import.resolverSettingsTab(this.config, this);
        this.csvSettingsPanel = new pimcore.object.helpers.import.csvSettingsTab(this.config, this);
        this.saveAndSharePanel = new pimcore.object.helpers.import.saveAndShareTab(this.config, this);
        this.reportPanel = new pimcore.object.helpers.import.reportTab(this.config, this);

        var tabs = [
            this.csvPreviewPanel.getPanel(),
            this.columnConfigPanel.getPanel(),
            this.resolverSettingsPanel.getPanel(),
            this.csvSettingsPanel.getPanel(),
            this.saveAndSharePanel.getPanel(),
            this.reportPanel.getPanel()

        ];

        this.tabPanel = new Ext.TabPanel({
            activeTab: 0,
            forceLayout: true,
            deferredRender: false,
            items: tabs
        });

        buttons = [];

        buttons.push({
            text: t("close"),
            iconCls: "pimcore_icon_cancel",
            handler: function () {
                this.containerPanel.close();
            }.bind(this)
        });

        this.saveAsCopyButton = new Ext.menu.Item(
            {
                text: t("save_as"),
                iconCls: "pimcore_icon_save",
                handler: function () {
                    this.saveConfig(true);
                }.bind(this)
            }
        );

        this.deleteButton = new Ext.button.Button({
            text: t('remove_config'),
            iconCls: "pimcore_icon_delete",
            disabled: !this.config.importConfigId,
            handler: this.deleteConfig.bind(this)
        });

        buttons.push(this.deleteButton);

        this.loadButton = new Ext.button.Split({

                text: t("load_configuration"),
                iconCls: "pimcore_icon_load_import_config",
                handler: function () {
                    this.showLoadDialog();
                }.bind(this),
                menu: [
                    {
                        text: t("import_export_configuration"),
                        iconCls: "pimcore_icon_import",
                        handler: function () {
                            this.importConfig();
                        }.bind(this)
                    }
                ]
            }
        );
        buttons.push(this.loadButton);

        this.saveButton = new Ext.button.Split({
            text: t("save_configuration"),
            iconCls: "pimcore_icon_save",
            handler: function () {
                this.saveConfig(false);
            }.bind(this),
            menu: [this.saveAsCopyButton]
        });

        buttons.push(this.saveButton);

        buttons.push({
            text: t("import"),
            iconCls: "pimcore_icon_start_import",
            handler: function () {
                this.importStart();
            }.bind(this)
        });

        this.containerPanel = new Ext.panel.Panel({
            title: this.getWindowTitle(),
            layout: "fit",
            iconCls: "pimcore_icon_import",
            items: [this.tabPanel],
            buttons: buttons,
            closable: true
        });

        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.add(this.containerPanel);
        tabPanel.setActiveTab(this.containerPanel);
    },

    getWindowTitle: function () {
        var title = t('import_configuration');
        if (this.importConfigId) {
            title += " - " + this.importConfigId;
        }
        if (this.config && this.config.shareSettings && this.config.shareSettings.configName) {
            title += " - " + this.config.shareSettings.configName;
        }
        return title;
    },

    getFileInfo: function (isReload, importConfigId) {
        Ext.Ajax.request({
            url: "/admin/object-helper/import-get-file-info",
            params: {
                importConfigId: importConfigId,
                importId: this.uniqueImportId,
                method: "post",
                className: this.className,          //TODO really needed ?
                classId: this.classId
            },
            success: this.getFileInfoComplete.bind(this, isReload)
        });
    },

    reloadPanels: function () {
        this.csvPreviewPanel.rebuildPanel();
        this.columnConfigPanel.rebuildPanel();
        this.resolverSettingsPanel.rebuildPanel();
        this.saveAndSharePanel.rebuildPanel();
        this.containerPanel.setTitle(this.getWindowTitle());
        this.deleteButton.setDisabled(this.config.isShared);
    },


    getFileInfoComplete: function (isReload, response) {

        var data = Ext.decode(response.responseText);

        if (data.success) {
            Ext.apply(this.config, {});
            Ext.apply(this.config, data.config);
            this.importConfigId = this.config.importConfigId;
            this.config.selectedGridColumns = this.config.selectedGridColumns || [];

            this.availableConfigs = data.availableConfigs;


            if (isReload) {
                this.reloadPanels(data);
            } else {
                this.showWindow(data);
            }
        }
        else {
            Ext.MessageBox.alert(t("error"), t("unsupported_filetype"));
        }
    },

    prepareSaveData: function () {
        var config = Ext.encode(this.config);
        config = Ext.decode(config);
        delete config['dataPreview'];
        return config;
    },

    commitEverything: function () {
        this.columnConfigPanel.commitData();
        this.resolverSettingsPanel.commitData();
        this.saveAndSharePanel.commitData();
    },

    saveConfig: function (asCopy) {
        this.commitEverything();
        if (!this.importConfigId || this.config.isShared) {
            asCopy = true;
        }

        if (this.config.shareSettings.configName && !asCopy) {
            this.doSave();
        } else {
            this.getSaveAsDialog(asCopy);
        }
    },

    doSave: function () {
        var config = this.prepareSaveData();
        config = Ext.encode(config);
        try {
            var data = {
                importConfigId: this.importConfigId,
                classId: this.classId,
                config: config
            };

            Ext.Ajax.request({
                url: '/admin/object-helper/import-save-config',
                method: "post",
                params: data,
                success: function (response) {
                    try {
                        var rdata = Ext.decode(response.responseText);
                        if (rdata && rdata.success) {
                            this.importConfigId = rdata.importConfigId;
                            this.availableConfigs = rdata.availableConfigs;
                            this.config.isShared = false;
                            this.deleteButton.setDisabled(false);
                            this.containerPanel.setTitle(this.getWindowTitle());
                            pimcore.helpers.showNotification(t("success"), t("your_configuration_has_been_saved"), "success");
                        }
                        else {
                            pimcore.helpers.showNotification(t("error"), t("error_saving_configuration"),
                                "error", t(rdata.message));
                        }
                    } catch (e) {
                        pimcore.helpers.showNotification(t("error"), t("error_saving_configuration"), "error");
                    }
                }.bind(this),
                failure: function () {
                    pimcore.helpers.showNotification(t("error"), t("error_saving_configuration"), "error");
                }
            });

        } catch (e3) {
            pimcore.helpers.showNotification(t("error"), t("error_saving_configuration"), "error");
        }

    },

    preview: function() {
        if (this.importConfigId) {
            this.doPreview();
        } else {
            Ext.Msg.show({
                title: t('no_configuration'),
                message: t('no_configuration_message'),
                buttons: Ext.Msg.YESNO,
                icon: Ext.Msg.QUESTION,
                fn: function(btn) {
                    if (btn === 'yes') {
                        this.doPreview();
                    }
                }.bind(this)
            });
        }

    },

    doPreview: function (rowIndex) {
        this.commitEverything();
        var config = this.prepareSaveData();

        var data = {
            importId: this.uniqueImportId,
            importConfigId: this.importConfigId,
            parentId: this.parentId ? this.parentId : "",
            rowIndex: rowIndex,
            classId: this.classId,
            config: config,
            additionalData: this.additionalData
        };

        Ext.Ajax.request({
            url: '/admin/object-helper/prepare-import-preview',
            method: "post",
            params: {
                data: Ext.encode(data)
            },
            success: function (response) {
                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        this.showVersionPreview();
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("preview_error"),
                            "error", t(rdata.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("preview_error"), "error");
                }
            }.bind(this),
            failure: function () {
                pimcore.helpers.showNotification(t("error"), t("preview_error"), "error");
            }
        });
    },

    showVersionPreview: function () {
        var frameId = 'object_importpreview_iframe_' + this.uniqueImportId;
        var previewFrame = new Ext.Panel({
            region: "center",
            html: '<iframe src="about:blank" frameborder="0" style="width:100%; height: 100%;" id="' + frameId + '"></iframe>'
        });

        this.versionPreviewWindow = new Ext.Window({
            width: 1000,
            height: 700,
            modal: true,
            title: t('preview'),
            layout: "fit",
            items: [previewFrame]
        });

        this.versionPreviewWindow.show();

        var path = "/admin/object-helper/import-preview?importId=" + this.uniqueImportId;
        Ext.get(frameId).dom.src = path;
    },

    getSaveAsDialog: function (asCopy) {
        var defaultName = new Date();

        var nameField = new Ext.form.TextField({
            fieldLabel: t('name'),
            length: 50,
            allowBlank: false,
            value: this.config.shareSettings.name ? this.config.shareSettings.name : defaultName
        });

        var descriptionField = new Ext.form.TextArea({
            fieldLabel: t('description'),
            height: 400,
            value: this.config.shareSettings.description
        });

        var configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [nameField, descriptionField],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    if (asCopy) {
                        this.importConfigId = null;
                    }
                    this.config.shareSettings.configName = nameField.getValue();
                    this.config.shareSettings.configDescription = descriptionField.getValue();

                    this.saveAndSharePanel.nameField.setValue(nameField.getValue());
                    this.saveAndSharePanel.descriptionField.setValue(descriptionField.getValue());

                    this.doSave();
                    this.saveWindow.close();
                }.bind(this)
            }]
        });

        this.saveWindow = new Ext.Window({
            width: 600,
            height: 300,
            modal: true,
            title: t('save_as'),
            layout: "fit",
            items: [configPanel]
        });

        this.saveWindow.show();
        nameField.focus();
        nameField.selectText();
        return this.window;
    },

    showLoadDialog: function () {

        var store = new Ext.data.JsonStore({
            proxy: {
                type: 'memory'
            },
            data: this.availableConfigs,
            fields: ['id', 'name'],
            autoLoad: true
        });

        var configsCombo = new Ext.form.field.ComboBox(
            {
                name: "configuration",
                store: store,
                queryMode: "local",
                triggerAction: "all",
                forceSelection: true,
                fieldLabel: t("configuration"),
                valueField: 'id',
                typeAhead: true,
                editable: true,
                displayField: 'name',
                width: '100%',
                listeners: {
                    change: function () {

                    }.bind(this)
                }
            }
        );

        var configPanel = new Ext.panel.Panel({
            bodyStyle: "padding: 10px;",
            items: [configsCombo]
        });

        this.loadWindow = new Ext.Window({
            width: 600,
            height: 200,
            modal: true,
            title: t('load_configuration'),
            layout: "fit",
            items: [configPanel],
            buttons: [
                {
                    text: t("cancel"),
                    iconCls: "pimcore_icon_cancel",
                    handler: function () {
                        this.loadWindow.close();
                    }.bind(this)
                },
                {
                    text: t("OK"),
                    iconCls: "pimcore_icon_apply",
                    handler: function (configsCombo) {
                        if (configsCombo.getValue()) {
                            this.getFileInfo(true, configsCombo.getValue());
                            this.loadWindow.close();
                            this.tabPanel.setActiveTab(this.columnConfigPanel.getPanel());
                        }
                    }.bind(this, configsCombo)
                }
            ]
        });
        this.loadWindow.show();
        configsCombo.focus();
        configsCombo.expand();
    },

    deleteConfig: function () {

        Ext.MessageBox.show({
            title: t('delete'),
            msg: t('delete_importconfig_dblcheck'),
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.MessageBox.INFO,
            fn: this.deleteImportConfigConfirmed.bind(this)
        });
    },

    deleteImportConfigConfirmed: function (btn) {
        if (btn == 'ok') {
            Ext.Ajax.request({
                url: "/admin/object-helper/delete-import-config",
                params: {
                    importConfigId: this.importConfigId

                },
                success: function (response) {

                    var decodedResponse = Ext.decode(response.responseText);
                    if (decodedResponse.deleteSuccess) {

                        this.importConfigId = null;
                        this.deleteButton.disable();
                        this.containerPanel.setTitle(this.getWindowTitle());

                        pimcore.helpers.showNotification(t("success"), t("importconfig_removed"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("importconfig_not_removed"), "error");
                    }


                }.bind(this)
            });
        }
    },

    importConfig: function () {

        Ext.Ajax.request({
            url: "/admin/object-helper/get-export-configs",
            params: {
                classId: this.classId
            },
            success: function (response) {

                decodedResponse = Ext.decode(response.responseText);
                if (decodedResponse.success) {
                    this.showImportDialog(decodedResponse);
                } else {
                    pimcore.helpers.showNotification(t("error"), t("error"), "error");
                }


            }.bind(this)
        });

    },

    doImportConfig: function (gridConfigId) {

        Ext.Ajax.request({
            url: "/admin/object-helper/import-export-config",
            params: {
                gridConfigId: gridConfigId
            },
            success: function (response) {

                var decodedResponse = Ext.decode(response.responseText);
                if (decodedResponse.success) {
                    this.config.selectedGridColumns = decodedResponse.selectedGridColumns;
                    this.reloadPanels();
                    this.tabPanel.setActiveTab(this.columnConfigPanel.getPanel());
                } else {
                    pimcore.helpers.showNotification(t("error"), t("error"), "error");
                }


            }.bind(this)
        });
    },

    showImportDialog: function (response) {

        var store = new Ext.data.JsonStore({
            proxy: {
                type: 'memory'
            },
            data: response.data,
            fields: ['id', 'name'],
            autoLoad: true
        });

        var configsCombo = new Ext.form.field.ComboBox(
            {
                name: "configuration",
                store: store,
                queryMode: "local",
                triggerAction: "all",
                forceSelection: true,
                fieldLabel: t("configuration"),
                valueField: 'id',
                typeAhead: true,
                editable: true,
                displayField: 'name',
                width: '100%'
            }
        );

        var configPanel = new Ext.panel.Panel({
            bodyStyle: "padding: 10px;",
            items: [configsCombo]
        });

        this.importWindow = new Ext.Window({
            width: 600,
            height: 200,
            modal: true,
            title: t('import_configuration'),
            layout: "fit",
            items: [configPanel],
            buttons: [
                {
                    text: t("cancel"),
                    iconCls: "pimcore_icon_cancel",
                    handler: function () {
                        this.importWindow.close();
                    }.bind(this)
                },
                {
                    text: t("OK"),
                    iconCls: "pimcore_icon_apply",
                    handler: function (configsCombo) {
                        if (configsCombo.getValue()) {
                            this.doImportConfig(configsCombo.getValue());
                            this.importWindow.close();
                        }
                    }.bind(this, configsCombo)
                }
            ]
        });
        this.importWindow.show();
        configsCombo.focus();
        configsCombo.expand();
    },

    importStart: function () {

        this.commitEverything();
        var config = this.prepareSaveData();

        this.importJobTotal = this.config.rows;
        if (this.config.resolverSettings.skipHeadRow) {
            this.importJobTotal--;
        }

        this.jobRequest = {
            config: Ext.encode(config),
            importId: this.uniqueImportId,
            className: this.className,
            classId: this.classId,
            job: 1,
            parentId: this.parentId,
            additionalData: this.additionalData
        };

        this.stopIt = false;

        this.reportPanel.stopButton.setDisabled(false);
        this.reportPanel.clearData();

        this.reportPanel.getPanel().setDisabled(false);
        this.tabPanel.setActiveTab(this.reportPanel.getPanel());

        this.importErrors = [];
        this.importJobCurrent = 1;

        window.setTimeout(function () {
            this.importProcess();
        }.bind(this), 1000);
    },

    importProcess: function () {

        if (this.importJobCurrent > this.importJobTotal || this.stopIt) {
            this.reportPanel.stopButton.setDisabled(true);
            // error handling
            if (this.importErrors.length > 0) {
                Ext.Msg.alert(t("error"), t("import_errors"));
            } else {
                Ext.Msg.alert(t("success"), t("import_is_done"));
            }

            if (this.tree) {
                this.tree.getStore().load({
                    node: this.parentNode
                });
            }

            return;
        }

        var status = (this.importJobCurrent / this.importJobTotal);
        var percent = Math.ceil(status * 100);
        this.reportPanel.importProgressBar.updateProgress(status, percent + "%");

        this.jobRequest.job = this.importJobCurrent;
        this.jobRequest.importJobTotal = this.importJobTotal;

        Ext.Ajax.request({
            url: "/admin/object-helper/import-process",
            params: this.jobRequest,
            method: "post",
            success: function (response) {

                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata) {
                        this.reportPanel.logData(rdata.rowId, rdata.message, rdata.success, rdata.objectId);
                        if (!rdata.success) {
                            this.importErrors.push({
                                job: rdata.message
                            });
                        }
                    }
                    else {
                        this.importErrors.push({
                            job: response.request.parameters.job
                        });
                    }

                    window.setTimeout(function () {
                        this.importJobCurrent++;
                        this.importProcess();
                    }.bind(this), 400);
                } catch (e) {
                    this.reportPanel.stopButton.setDisabled(true);
                    pimcore.helpers.showNotification(t("error"), e, "error");
                }
            }.bind(this),
            failure: function (response) {
                var message = t("error");

                try {
                    var json = Ext.decode(response.responseText);
                    if (json.message) {
                        message += ': ' + json.message;
                    }
                } catch (e) {
                }

                this.reportPanel.stopButton.setDisabled(true);
                pimcore.helpers.showNotification(t("error"), message, "error");
            }.bind(this)
        });
    }
});
