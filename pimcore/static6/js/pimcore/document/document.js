/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.document.document");
pimcore.document.document = Class.create(pimcore.element.abstract, {

    urlprefix: "/admin/",

    getData: function () {        
        Ext.Ajax.request({
            url: this.urlprefix + this.getType() + "/get-data-by-id/",
            params: {id: this.id},
            success: this.getDataComplete.bind(this)
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

                this.startChangeDetector();
            }
            else {
                pimcore.helpers.closeDocument(this.id);
            }
        }
        catch (e) {
            console.log(e);
            pimcore.helpers.closeDocument(this.id);
        }

    },

    selectInTree: function () {
        try {
            Ext.getCmp("pimcore_panel_tree_documents").expand();
            var tree = pimcore.globalmanager.get("layout_document_tree");
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

    save : function (task, only, callback) {

        if(this.tab.disabled || this.tab.isMasked()) {
            return;
        }

        this.tab.mask();
        var saveData = this.getSaveData(only);

        if (saveData) {
            // check for version notification
            if(this.newerVersionNotification) {
                if(task == "publish" || task == "unpublish") {
                    this.newerVersionNotification.hide();
                } else {
                    this.newerVersionNotification.show();
                }

            }

            pimcore.plugin.broker.fireEvent("preSaveDocument", this, this.getType(), task, only);

            Ext.Ajax.request({
                url: this.urlprefix + this.getType() + '/save/task/' + task,
                method: "post",
                params: saveData,
                success: function (response) {
                    try{
                        var rdata = Ext.decode(response.responseText);
                        if (rdata && rdata.success) {
                            pimcore.helpers.showNotification(t("success"), t("successful_saved_document"), "success");
                            this.resetChanges();

                            if(typeof this["createScreenshot"] == "function") {
                                this.createScreenshot();
                            }
                            pimcore.plugin.broker.fireEvent("postSaveDocument", this, this.getType(), task, only);
                        }
                        else {
                            pimcore.helpers.showPrettyError(rdata.type, t("error"), t("error_saving_document"),
                                rdata.message, rdata.stack, rdata.code);
                        }
                    } catch (e) {
                        pimcore.helpers.showNotification(t("error"), t("error_saving_document"), "error");
                    }


                    // reload versions
                    if (this.versions) {
                        if (typeof this.versions.reload == "function") {
                            this.versions.reload();
                        }
                    }

                    this.tab.unmask();

                    if(typeof callback == "function") {
                        callback();
                    }
                }.bind(this),
                failure: function () {
                    this.tab.unmask();
                }
            });
        } else {
            this.tab.unmask();
        }
    },
    
    
    isAllowed : function (key) {
        return this.data.userPermissions[key];
    },

    remove: function () {
        var options = {
            "elementType" : "document",
            "id": this.id
        };
        pimcore.elementservice.deleteElement(options);
    },

    saveClose: function(only){
        this.save(null, only, function () {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove(this.tab);
        });
    },

    publishClose: function(){
        this.publish(null, function () {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove(this.tab);
        }.bind(this));
    },

    publish: function (only, callback) {
        this.data.published = true;

        // toogle buttons
        this.toolbarButtons.unpublish.show();

        if(this.toolbarButtons.save) {
            this.toolbarButtons.save.hide();
        }

        pimcore.elementservice.setElementPublishedState({
            elementType: "document",
            id: this.id,
            published: true
        });

        this.save("publish", only, callback);
    },

    unpublish: function (only, callback) {
        this.data.published = false;

        // toogle buttons
        this.toolbarButtons.unpublish.hide();

        if(this.toolbarButtons.save) {
            this.toolbarButtons.save.show();
        }

        pimcore.elementservice.setElementPublishedState({
            elementType: "document",
            id: this.id,
            published: false
        });

        this.save("unpublish", only, callback);
    },

    unpublishClose: function () {
        this.unpublish(null, function () {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove(this.tab);
        });
    },

    reload: function () {

        this.tab.on("close", function() {
            window.setTimeout(function (id, type) {
                pimcore.helpers.openDocument(id, type);
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
                url: "/admin/document/translation-check-language",
                params: {
                    path: el.getValue()
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);
                    if(data["success"]) {
                        win.getComponent("language").setValue(pimcore.available_languages[data["language"]] + " [" + data["language"] + "]");
                        win.getComponent("language").show();
                        win.getComponent("info").hide();
                    } else {
                        win.getComponent("language").hide();
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
                cls: "input_drop_target",
                fieldLabel: t("translation"),
                enableKeyListeners: true,
                listeners: {
                    "render": function (el) {
                        new Ext.dd.DropZone(el.getEl(), {
                            reference: this,
                            ddGroup: "element",
                            getTargetFromEvent: function(e) {
                                return this.getEl();
                            }.bind(el),

                            onNodeOver : function(target, dd, e, data) {
                                return Ext.dd.DropZone.prototype.dropAllowed;
                            },

                            onNodeDrop : function (target, dd, e, data) {
                                data = data.records[0].data;
                                if (data.elementType == "document") {
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
            },{
                xtype: "displayfield",
                name: "language",
                itemId: "language",
                value: "",
                hidden: true,
                fieldLabel: t("language")
            },{
                xtype: "displayfield",
                name: "language",
                itemId: "info",
                fieldLabel: t("info"),
                value: t("target_document_needs_language")
            }],
            buttons: [{
                text: t("cancel"),
                iconCls: "pimcore_icon_delete",
                handler: function () {
                    win.close();
                }
            }, {
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {

                    Ext.Ajax.request({
                        url: "/admin/document/translation-add",
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

    createTranslation: function (inheritance) {

        var languagestore = [];
        var websiteLanguages = pimcore.settings.websiteLanguages;
        var selectContent = "";
        for (var i=0; i<websiteLanguages.length; i++) {
            if(this.data.properties["language"]["data"] != websiteLanguages[i]) {
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
                            url: "/admin/document/translation-determine-parent",
                            params: {
                                language: el.getValue(),
                                id: this.id
                            },
                            success: function (response) {
                                var data = Ext.decode(response.responseText);
                                if(data["success"]) {
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
                cls: "input_drop_target",
                fieldLabel: t("parent_document"),
                listeners: {
                    "render": function (el) {
                        new Ext.dd.DropZone(el.getEl(), {
                            reference: this,
                            ddGroup: "element",
                            getTargetFromEvent: function(e) {
                                return this.getEl();
                            }.bind(el),

                            onNodeOver : function(target, dd, e, data) {
                                return Ext.dd.DropZone.prototype.dropAllowed;
                            },

                            onNodeDrop : function (target, dd, e, data) {
                                data = data.records[0].data;
                                if (data.elementType == "document") {
                                    this.setValue(data.path);
                                    return true;
                                }
                                return false;
                            }.bind(el)
                        });
                    }
                }
            },{
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
            },{
                xtype: "textfield",
                itemId: "name",
                fieldLabel: t('navigation'),
                name: 'name',
                width: "100%"
            },{
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
                iconCls: "pimcore_icon_delete",
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
                        url: "/admin/element/get-subtype",
                        params: {
                            id: pageForm.getComponent("parent").getValue(),
                            type: "document"
                        },
                        success: function (response) {
                            var res = Ext.decode(response.responseText);
                            if(res.success) {
                                if(params["key"].length >= 1) {
                                    params["parentId"] = res["id"];
                                    params["type"] = this.getType();
                                    params["translationsBaseDocument"] = this.id;
                                    if(inheritance) {
                                        params["inheritanceSource"] = this.id;
                                    }

                                    Ext.Ajax.request({
                                        url: "/admin/document/add/",
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
        if(this.data["translations"]) {
            Ext.iterate(this.data["translations"], function (language, documentId, myself) {
                translationsMenu.push({
                    text: pimcore.available_languages[language] + " [" + language + "]",
                    iconCls: "pimcore_icon_language_" + language,
                    handler: function () {
                        pimcore.helpers.openElement(documentId, "document");
                    }
                });
            });
        }

        return {
            tooltip: t("translation"),
            iconCls: "pimcore_icon_translations",
            scale: "medium",
            menu: [{
                text: t("new_document"),
                hidden: !in_array(this.getType(), ["page","snippet","email","printpage","printcontainer"]),
                iconCls: "pimcore_icon_page pimcore_icon_overlay_add",
                menu: [{
                    text: t("using_inheritance"),
                    hidden: !in_array(this.getType(), ["page","snippet","printpage","printcontainer"]),
                    handler: this.createTranslation.bind(this, true),
                    iconCls: "pimcore_icon_clone"
                },{
                    text: t("empty_document"),
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
            }]
        };
    }
});