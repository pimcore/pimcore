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
        this.classId = config.classId;
        this.className = config.className;

        this.getFileInfo();
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

    showWindow: function (config) {

        // --
        this.config = config;
        this.importConfigId = config.importConfigId;
        this.config.selectedGridColumns = this.config.selectedGridColumns || [];
        this.importJobTotal = config.rows;

        // --

        if (!this.importConfigId) {
            this.buildDefaultSelection();
        }

        this.previewPanel = new pimcore.object.helpers.import.previewTab(this.config, this);
        this.columnConfigPanel = new pimcore.object.helpers.import.columnConfigurationTab(this.config, this);
        this.resolverSettingsPanel = new pimcore.object.helpers.import.resolverSettingsTab(this.config, this);
        this.csvSettingsPanel = new pimcore.object.helpers.import.csvSettingsTab(this.config, this);
        this.saveAndSharePanel = new pimcore.object.helpers.import.saveAndShareTab(this.config, this);

        var tabs = [
            this.previewPanel.getPanel(),
            this.columnConfigPanel.getPanel(),
            this.resolverSettingsPanel.getPanel(),
            this.csvSettingsPanel.getPanel(),
            this.saveAndSharePanel.getPanel()
        ];

        this.tabPanel = new Ext.TabPanel({
            activeTab: 0,
            forceLayout: true,
            items: tabs
        });

        buttons = [];

        buttons.push({
            text: t("cancel"),
            iconCls: "pimcore_icon_cancel",
            handler: function () {
                this.window.close();
            }.bind(this)
        });

        buttons.push({
            text: t("save_configuration"),
            iconCls: "pimcore_icon_save",
            handler: function () {
                this.saveConfig();
            }.bind(this)
        });

        buttons.push({
            text: t("import"),
            iconCls: "pimcore_icon_import",
            handler: function () {
                console.log("not implemented");
            }.bind(this)
        });


        this.window = new Ext.Window({
            width: 1000,
            height: 700,
            modal: true,
            title: t('import_configuration'),
            layout: "fit",
            items: [this.tabPanel],
            buttons: buttons
        });

        this.window.show();
    },

    getFileInfo: function () {
        Ext.Ajax.request({
            url: "/admin/object-helper/import-get-file-info-new",
            params: {
                importId: this.uniqueImportId,
                method: "post",
                className: this.className,          //TODO really needed ?
                classId: this.classId
            },
            success: this.getFileInfoComplete.bind(this)
        });
    },

    getFileInfoComplete: function (response) {

        var data = Ext.decode(response.responseText);

        if (data.success) {
            this.showWindow(data.config);
        }
        else {
            Ext.MessageBox.alert(t("error"), t("unsupported_filetype"));
        }
    },

    prepareSaveData: function () {
        var config = Ext.encode(this.config);
        config = Ext.decode(config);
        delete config['dataPreview'];
        delete config['mappingPreview'];
        delete config['mappingStore'];
        return config;
    },

    commitEverything: function() {
        this.columnConfigPanel.commitData();
        this.resolverSettingsPanel.commitData();
        this.saveAndSharePanel.commitData();
    },

    saveConfig: function () {
        this.commitEverything();

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

    preview: function(rowIndex) {
        this.commitEverything();
        var config = this.prepareSaveData();

        var data = {
            importId: this.uniqueImportId,
            importConfigId: this.importConfigId,
            rowIndex: rowIndex,
            classId: this.classId,
            config: config
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

    showVersionPreview: function() {
        var frameId = 'object_importpreview_iframe_' + this.uniqueImportId;
        var previewFrame = new Ext.Panel({
            region: "center",
            // bodyCls: "pimcore_overflow_scrolling",
            html: '<iframe src="about:blank" frameborder="0" style="width:100%; height: 100%;" id="' + frameId  + '"></iframe>'
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
    }

});
