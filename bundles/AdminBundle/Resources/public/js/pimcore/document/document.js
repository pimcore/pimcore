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

pimcore.registerNS("pimcore.document.document");
pimcore.document.document = Class.create(pimcore.element.abstract, {

    getData: function () {
        var options = this.options || {};
        Ext.Ajax.request({
            url: Routing.getBaseUrl() + "/admin/" + this.getType() + "/get-data-by-id",
            params: {id: this.id},
            ignoreErrors: options.ignoreNotFoundError,
            success: this.getDataComplete.bind(this),
            failure: function () {
                pimcore.helpers.forgetOpenTab("document_" + this.id + "_" + this.type);
                pimcore.helpers.closeDocument(this.id);
            }.bind(this)
        });
    },

    getDataComplete: function (response) {
        try {
            this.data = Ext.decode(response.responseText);

            if (typeof this.data.editlock == "object") {
                pimcore.helpers.lockManager(this.id, "document", this.getType(), this.data);
                throw "document is locked";
            }

            if (this.isAllowed("view")) {
                this.init();
                this.addTab();

                if (this.getAddToHistory()) {
                    pimcore.helpers.recordElement(this.id, "document", this.data.path + this.data.key);
                }

                //update published state in trees
                pimcore.elementservice.setElementPublishedState({
                    elementType: "document",
                    id: this.id,
                    published: this.data.published
                });

                this.startChangeDetector();
            } else {
                pimcore.helpers.closeDocument(this.id);
            }
        } catch (e) {
            console.log(e);
            pimcore.helpers.closeDocument(this.id);
        }

    },

    selectInTree: function () {
        try {
            pimcore.treenodelocator.showInTree(this.id, "document");
        } catch (e) {
            console.log(e);
        }
    },

    activate: function () {
        var tabId = "document_" + this.id;
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem(tabId);
    },

    save: function (task, only, callback, successCallback) {

        if (this.tab.disabled || this.tab.isMasked()) {
            return;
        }

        this.tab.mask();
        var saveData = this.getSaveData(only);

        if (saveData) {
            if (this.data.missingRequiredEditable !== null) {
                saveData.missingRequiredEditable = this.data.missingRequiredEditable;
            }

            try {
                pimcore.plugin.broker.fireEvent("preSaveDocument", this, this.getType(), task, only);
            } catch (e) {
                if (e instanceof pimcore.error.ValidationException) {
                    this.tab.unmask();
                    pimcore.helpers.showPrettyError('document', t("error"), t("saving_failed"), e.message);
                    return false;
                }

                if (e instanceof pimcore.error.ActionCancelledException) {
                    this.tab.unmask();
                    pimcore.helpers.showNotification(t("Info"), 'Document not saved: ' + e.message, 'info');
                    return false;
                }
            }

            Ext.Ajax.request({
                url: Routing.getBaseUrl() + "/admin/" + this.getType() + '/save?task=' + task,
                method: "PUT",
                params: saveData,
                success: function (response) {
                    try {
                        var rdata = Ext.decode(response.responseText);
                        if (typeof successCallback == 'function') {
                            // the successCallback function retrieves response data information
                            successCallback(rdata);
                        }
                        if (rdata && rdata.success) {
                            // check for version notification
                            if (this.newerVersionNotification) {
                                if (task == "publish" || task == "unpublish") {
                                    this.newerVersionNotification.hide();
                                } else {
                                    this.newerVersionNotification.show();
                                }
                            }

                            pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
                            this.resetChanges();
                            Ext.apply(this.data, rdata.data);

                            if (typeof this["createScreenshot"] == "function") {
                                this.createScreenshot();
                            }
                            pimcore.plugin.broker.fireEvent("postSaveDocument", this, this.getType(), task, only);
                            pimcore.helpers.updateTreeElementStyle('document', this.id, rdata.treeData);
                        }
                    } catch (e) {
                        pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
                    }

                    // reload versions
                    if (this.versions) {
                        if (typeof this.versions.reload == "function") {
                            this.versions.reload();
                        }
                    }

                    this.tab.unmask();

                    if (typeof callback == "function") {
                        callback();
                    }
                }.bind(this),
                failure: function () {
                    this.tab.unmask();
                }.bind(this),
            });
        } else {
            this.tab.unmask();
        }
    },

    isAllowed: function (key) {
        return this.data.userPermissions[key];
    },

    remove: function () {
        var options = {
            "elementType": "document",
            "id": this.id
        };
        pimcore.elementservice.deleteElement(options);
    },

    close: function() {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.remove(this.tab);
    },

    saveClose: function (only) {
        this.save(null, only, function () {
            this.close();
        }.bind(this));
    },

    publishClose: function () {
        this.publish(null, function () {
            this.close();
        }.bind(this));
    },

    publish: function (only, callback) {
        this.save("publish", only, callback, function (rdata) {
            if (rdata && rdata.success) {
                this.data.published = true;

                // toggle buttons
                this.toolbarButtons.unpublish.show();

                if (this.toolbarButtons.save) {
                    this.toolbarButtons.save.hide();
                }

                pimcore.elementservice.setElementPublishedState({
                    elementType: "document",
                    id: this.id,
                    published: true
                });
            }
        }.bind(this));
    },

    unpublish: function (only, callback) {
        this.save("unpublish", only, callback, function (rdata) {
            if (rdata && rdata.success) {
                this.data.published = false;

                // toggle buttons
                this.toolbarButtons.unpublish.hide();

                if (this.toolbarButtons.save) {
                    this.toolbarButtons.save.show();
                }

                pimcore.elementservice.setElementPublishedState({
                    elementType: "document",
                    id: this.id,
                    published: false
                });
            }
        }.bind(this));
    },

    unpublishClose: function () {
        this.unpublish(null, function () {
            this.close();
        }.bind(this));
    },

    reload: function () {

        this.tab.on("close", function () {
            var currentTabIndex = this.tab.ownerCt.items.indexOf(this.tab);
            window.setTimeout(function (id, type) {
                pimcore.helpers.openDocument(id, type, {tabIndex: currentTabIndex});
            }.bind(window, this.id, this.getType()), 500);
        }.bind(this));

        pimcore.helpers.closeDocument(this.id);
    },

    setType: function (type) {
        this.type = type;
    },

    getType: function () {
        return this.type;
    },

    linkTranslation: function () {

        var win = null;

        var checkLanguage = function (el) {

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_document_document_translationchecklanguage'),
                params: {
                    path: el.getValue()
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);
                    if (data["success"]) {
                        win.getComponent("language").setValue(pimcore.available_languages[data["language"]] + " [" + data["language"] + "]");
                        win.getComponent("language").show();
                        win.getComponent("info").hide();
                    } else {
                        win.getComponent("language").setValue("").hide();
                        win.getComponent("info").show();
                    }
                }
            });
        };

        win = new Ext.Window({
            width: 600,
            bodyStyle: "padding:10px",
            items: [{
                xtype: "textfield",
                name: "translation",
                itemId: "translation",
                width: "100%",
                fieldCls: "input_drop_target",
                fieldLabel: t("translation"),
                enableKeyListeners: true,
                listeners: {
                    "render": function (el) {
                        new Ext.dd.DropZone(el.getEl(), {
                            reference: this,
                            ddGroup: "element",
                            getTargetFromEvent: function (e) {
                                return this.getEl();
                            }.bind(el),

                            onNodeOver: function (target, dd, e, data) {
                                if (data.records.length === 1 && data.records[0].data.elementType === "document") {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                            },

                            onNodeDrop: function (target, dd, e, data) {

                                if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                    return false;
                                }

                                data = data.records[0].data;
                                if (data.elementType === "document") {
                                    this.setValue(data.path);
                                    return true;
                                }
                                return false;
                            }.bind(el)
                        });
                    },
                    "change": checkLanguage,
                    "keyup": checkLanguage
                }
            }, {
                xtype: "displayfield",
                name: "language",
                itemId: "language",
                value: "",
                hidden: true,
                fieldLabel: t("language")
            }, {
                xtype: "displayfield",
                name: "language",
                itemId: "info",
                fieldLabel: t("info"),
                value: t("target_document_needs_language")
            }],
            buttons: [{
                text: t("cancel"),
                iconCls: "pimcore_icon_cancel",
                handler: function () {
                    win.close();
                }
            }, {
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    if (!win.getComponent("translation").getValue() || !win.getComponent("language").getValue()) {
                        Ext.MessageBox.alert(t("error"), t("target_document_invalid"));
                        return false;
                    }

                    Ext.Ajax.request({
                        url: Routing.generate('pimcore_admin_document_document_translationadd'),
                        method: 'POST',
                        params: {
                            sourceId: this.id,
                            targetPath: win.getComponent("translation").getValue()
                        },
                        success: function (response) {
                            this.reload();
                        }.bind(this)
                    });

                    win.close();
                }.bind(this)
            }]
        });

        win.show();
    },

    showDocumentOverview: function () {

        new pimcore.document.document_language_overview(this);
    },

    createTranslation: function (inheritance) {

        var languagestore = [];
        var websiteLanguages = pimcore.settings.websiteLanguages;
        var selectContent = "";
        for (var i = 0; i < websiteLanguages.length; i++) {
            if (this.data.properties["language"]["data"] != websiteLanguages[i]) {
                selectContent = pimcore.available_languages[websiteLanguages[i]] + " [" + websiteLanguages[i] + "]";
                languagestore.push([websiteLanguages[i], selectContent]);
            }
        }

        var pageForm = new Ext.form.FormPanel({
            border: false,
            defaults: {
                labelWidth: 170
            },
            items: [{
                xtype: "combo",
                name: "language",
                store: languagestore,
                editable: false,
                triggerAction: 'all',
                mode: "local",
                fieldLabel: t('language'),
                listeners: {
                    select: function (el) {
                        pageForm.getComponent("parent").disable();
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_document_document_translationdetermineparent'),
                            params: {
                                language: el.getValue(),
                                id: this.id
                            },
                            success: function (response) {
                                var data = Ext.decode(response.responseText);
                                if (data["success"]) {
                                    pageForm.getComponent("parent").setValue(data["targetPath"]);
                                }
                                pageForm.getComponent("parent").enable();
                            }
                        });
                    }.bind(this)
                }
            }, {
                xtype: "textfield",
                name: "parent",
                itemId: "parent",
                width: "100%",
                fieldCls: "input_drop_target",
                fieldLabel: t("parent"),
                listeners: {
                    "render": function (el) {
                        new Ext.dd.DropZone(el.getEl(), {
                            reference: this,
                            ddGroup: "element",
                            getTargetFromEvent: function (e) {
                                return this.getEl();
                            }.bind(el),

                            onNodeOver: function (target, dd, e, data) {
                                if (data.records.length === 1 && data.records[0].data.elementType === "document") {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                            },

                            onNodeDrop: function (target, dd, e, data) {

                                if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                    return false;
                                }

                                data = data.records[0].data;
                                if (data.elementType === "document") {
                                    this.setValue(data.path);
                                    return true;
                                }
                                return false;
                            }.bind(el)
                        });
                    }
                }
            }, {
                xtype: "textfield",
                width: "100%",
                fieldLabel: t('key'),
                itemId: "key",
                name: 'key',
                enableKeyEvents: true,
                listeners: {
                    keyup: function (el) {
                        pageForm.getComponent("name").setValue(el.getValue());
                    }
                }
            }, {
                xtype: "textfield",
                itemId: "name",
                fieldLabel: t('navigation'),
                name: 'name',
                width: "100%"
            }, {
                xtype: "textfield",
                itemId: "title",
                fieldLabel: t('title'),
                name: 'title',
                width: "100%"
            }]
        });

        var win = new Ext.Window({
            width: 600,
            bodyStyle: "padding:10px",
            items: [pageForm],
            buttons: [{
                text: t("cancel"),
                iconCls: "pimcore_icon_cancel",
                handler: function () {
                    win.close();
                }
            }, {
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {

                    var params = pageForm.getForm().getFieldValues();
                    win.disable();

                    Ext.Ajax.request({
                        url: Routing.generate('pimcore_admin_element_getsubtype'),
                        params: {
                            id: pageForm.getComponent("parent").getValue(),
                            type: "document"
                        },
                        success: function (response) {
                            var res = Ext.decode(response.responseText);
                            if (res.success) {
                                if (params["key"].length >= 1) {
                                    params["parentId"] = res["id"];
                                    params["type"] = this.getType();
                                    params["translationsBaseDocument"] = this.id;
                                    if (inheritance) {
                                        params["inheritanceSource"] = this.id;
                                    }

                                    Ext.Ajax.request({
                                        url: Routing.generate('pimcore_admin_document_document_add'),
                                        method: 'POST',
                                        params: params,
                                        success: function (response) {
                                            response = Ext.decode(response.responseText);
                                            if (response && response.success) {
                                                pimcore.helpers.openDocument(response.id, response.type);
                                            }
                                        }
                                    });
                                }
                            } else {
                                Ext.MessageBox.alert(t("error"), t("element_not_found"));
                            }

                            win.close();
                        }.bind(this)
                    });
                }.bind(this)
            }]
        });

        win.show();
    },

    getTranslationButtons: function () {

        var translationsMenu = [];
        var unlinkTranslationsMenu = [];
        if (this.data["translations"]) {
            var me = this;
            Ext.iterate(this.data["translations"], function (language, documentId, myself) {
                translationsMenu.push({
                    text: pimcore.available_languages[language] + " [" + language + "]",
                    iconCls: "pimcore_icon_language_" + language,
                    handler: function () {
                        pimcore.helpers.openElement(documentId, "document");
                    }
                });
            });

            if (Object.keys(me.data["translations"]).length) {
                //add menu for All Translations
                translationsMenu.push({
                    text: t("all_translations"),
                    iconCls: "pimcore_icon_translations",
                    handler: function () {
                        Ext.iterate(me.data["translations"], function (language, documentId) {
                            pimcore.helpers.openElement(documentId, "document");
                        });
                    }
                });
            }
        }

        if (this.data["unlinkTranslations"]) {
            var me = this;
            Ext.iterate(this.data["unlinkTranslations"], function (language, documentId, myself) {
                unlinkTranslationsMenu.push({
                    text: pimcore.available_languages[language] + " [" + language + "]",
                    handler: function () {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_document_document_translationremove'),
                            method: 'DELETE',
                            params: {
                                sourceId: me.id,
                                targetId: documentId
                            },
                            success: function (response) {
                                me.reload();
                            }.bind(this)
                        });
                    }.bind(this),
                    iconCls: "pimcore_icon_language_" + language
                });
            });
        }

        return {
            tooltip: t("translation"),
            iconCls: "pimcore_material_icon_translation pimcore_material_icon",
            scale: "medium",
            menu: [{
                text: t("new_document"),
                hidden: !in_array(this.getType(), ["page", "snippet", "email", "printpage", "printcontainer"]),
                iconCls: "pimcore_icon_page pimcore_icon_overlay_add",
                menu: [{
                    text: t("using_inheritance"),
                    hidden: !in_array(this.getType(), ["page", "snippet", "printpage", "printcontainer"]),
                    handler: this.createTranslation.bind(this, true),
                    iconCls: "pimcore_icon_clone"
                }, {
                    text: "&gt; " + t("blank"),
                    handler: this.createTranslation.bind(this, false),
                    iconCls: "pimcore_icon_file_plain"
                }]
            }, {
                text: t("link_existing_document"),
                handler: this.linkTranslation.bind(this),
                iconCls: "pimcore_icon_page pimcore_icon_overlay_reading"
            }, {
                text: t("open_translation"),
                menu: translationsMenu,
                hidden: !translationsMenu.length,
                iconCls: "pimcore_icon_open"
            }, {
                text: t("unlink_existing_document"),
                menu: unlinkTranslationsMenu,
                hidden: !unlinkTranslationsMenu.length,
                iconCls: "pimcore_icon_delete"
            }, {
                text: t("document_language_overview"),
                handler: this.showDocumentOverview.bind(this),
                iconCls: "pimcore_icon_page"
            }]
        };
    },

    resetPath: function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_document_getdatabyid'),
            params: {id: this.id},
            success: function (response) {
                var rdata = Ext.decode(response.responseText);
                this.data.path = rdata.path;
                this.data.key = rdata.key;
            }.bind(this)
        });
    }
});
