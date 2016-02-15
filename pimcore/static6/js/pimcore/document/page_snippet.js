/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.document.page_snippet");
pimcore.document.page_snippet = Class.create(pimcore.document.document, {

    addTab: function () {

        var tabTitle = this.data.key;
        if (tabTitle.length < 1) {
            tabTitle = "home";
        }

        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var tabId = "document_" + this.id;

        this.tab = new Ext.Panel({
            id: tabId,
            title: tabTitle,
            closable:true,
            hideMode: "offsets",
            layout: "border",
            items: [
                this.getLayoutToolbar(),
                this.getTabPanel()
            ],
            iconCls: "pimcore_icon_" + this.data.type,
            document: this
        });

        // remove this instance when the panel is closed
        this.tab.on("beforedestroy", function () {
            Ext.Ajax.request({
                url: "/admin/element/unlock-element",
                params: {
                    id: this.data.id,
                    type: "document"
                }
            });

            this.cleanUpOnDestroy();
        }.bind(this));

        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove("document_" + this.id);
            pimcore.helpers.forgetOpenTab("document_" + this.id + "_" + this.data.type);
        }.bind(this));


        this.tab.on("activate", function () {
            this.tab.updateLayout();
            pimcore.layout.refresh();
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.setActiveItem(tabId);
            pimcore.plugin.broker.fireEvent("postOpenDocument", this, this.data.type);
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.tabPanel.add(this.tab);


        // recalculate the layout
        pimcore.layout.refresh();
    },

    cleanUpOnDestroy: function () {
        if (this.edit) {
            if (typeof this.edit.onClose == "function") {
                this.edit.onClose();
            }
        }
        if (this.preview) {
            if (typeof this.preview.onClose == "function") {
                this.preview.onClose();
            }
        }
        if (this.settings) {
            if (typeof this.settings.onClose == "function") {
                this.settings.onClose();
            }
        }
        if (this.properties) {
            if (typeof this.properties.onClose == "function") {
                this.properties.onClose();
            }
        }
        this.removeFromSession();
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            this.toolbarButtons = {};

            this.toolbarButtons.save = new Ext.SplitButton({
                text: t('save'),
                iconCls: "pimcore_icon_save",
                scale: "small",
                handler: this.unpublish.bind(this),
                menu: [{
                    text: t('save_close'),
                    iconCls: "pimcore_icon_save",
                    handler: this.unpublishClose.bind(this)
                }]
            });


            this.toolbarButtons.publish = new Ext.SplitButton({
                text: t('save_and_publish'),
                iconCls: "pimcore_icon_publish",
                scale: "small",
                handler: this.publish.bind(this),
                menu: [
                    {
                        text: t('save_pubish_close'),
                        iconCls: "pimcore_icon_save",
                        handler: this.publishClose.bind(this)
                    },{
                        text: t('save_only_new_version'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this)
                    },
                    {
                        text: t('save_only_scheduled_tasks'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this, "scheduler","scheduler")
                    }
                ]
            });


            this.toolbarButtons.unpublish = new Ext.Button({
                text: t('unpublish'),
                iconCls: "pimcore_icon_unpublish",
                scale: "small",
                handler: this.unpublish.bind(this)
            });

            this.toolbarButtons.remove = new Ext.Button({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                scale: "small",
                handler: this.remove.bind(this)
            });


            var buttons = [];

            if (this.isAllowed("save")) {
                buttons.push(this.toolbarButtons.save);
            }
            if (this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }
            if (this.isAllowed("unpublish") && !this.data.locked) {
                buttons.push(this.toolbarButtons.unpublish);
            }

            if(this.isAllowed("delete") && !this.data.locked && this.data.id != 1) {
                buttons.push(this.toolbarButtons.remove);
            }

            buttons.push("-");

            var moreButtons = [];

            moreButtons.push({
                text: t('reload'),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            });

            moreButtons.push({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_show_in_tree",
                handler: this.selectInTree.bind(this)
            });

            moreButtons.push({
                text: t("show_metainfo"),
                iconCls: "pimcore_icon_info",
                handler: this.showMetaInfo.bind(this)
            });

            var translationsMenu = [];
            if(this.data["translations"]) {
                Ext.iterate(this.data["translations"], function (language, documentId, myself) {
                    translationsMenu.push({
                        text: pimcore.available_languages[language],
                        iconCls: "pimcore_icon_language_" + language,
                        handler: function () {
                            pimcore.helpers.openElement(documentId, "document");
                        }
                    });
                });
            }

            moreButtons.push({
                text: t("translation"),
                iconCls: "pimcore_icon_translations",
                menu: [{
                    text: t("new_document"),
                    menu: [{
                        text: t("using_inheritance"),
                        handler: this.createTranslation.bind(this, true)
                    },{
                        text: t("empty_document"),
                        handler: this.createTranslation.bind(this, false)
                    }]
                }, {
                    text: t("link_existing_document"),
                    handler: this.linkTranslation.bind(this)
                }, {
                    text: t("open_translation"),
                    menu: translationsMenu,
                    hidden: !translationsMenu.length
                }]
            });

            buttons.push({
                text: t("more"),
                iconCls: "pimcore_icon_more",
                scale: "small",
                menu: moreButtons
            });

            buttons.push("-");
            buttons.push({
                text: t("open"),
                iconCls: "pimcore_icon_cursor",
                scale: "small",
                handler: function () {
                    var date = new Date();
                    var link = this.data.path + this.data.key + "?pimcore_preview=true&time=" + date.getTime();

                    // add persona parameter if available
                    if(this["edit"] && this.edit["persona"]) {
                        if(this.edit.persona && this.edit.persona.getValue()) {
                            link += "&_ptp=" + this.edit.persona.getValue();
                        }
                    }

                    window.open(link);
                }.bind(this)
            });
            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: this.data.id,
                scale: "small"
            });

            // version notification
            this.newerVersionNotification = new Ext.Toolbar.TextItem({
                xtype: 'tbtext',
                text: '&nbsp;&nbsp;<img src="/pimcore/static6/img/icon/error.png" align="absbottom" />&nbsp;&nbsp;'
                    + t("this_is_a_newer_not_published_version"),
                scale: "small",
                hidden: true
            });

            buttons.push(this.newerVersionNotification);

            // check for newer version than the published
            if (this.data.versions.length > 0) {
                if (this.data.modificationDate < this.data.versions[0].date) {
                    this.newerVersionNotification.show();
                }
            }


            this.toolbar = new Ext.Toolbar({
                id: "document_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "main-toolbar",
                items: buttons,
                overflowHandler: 'menu'
            });

            this.toolbar.on("afterrender", function () {
                window.setTimeout(function () {
                    if (!this.data.published) {
                        this.toolbarButtons.unpublish.hide();
                    } else if (this.isAllowed("publish")) {
                        this.toolbarButtons.save.hide();
                    }
                }.bind(this), 500);
            }.bind(this));
        }

        return this.toolbar;
    },

    saveToSession: function (onComplete) {

        if (typeof onComplete != "function") {
            onComplete = function () {
            };
        }

        Ext.Ajax.request({
            url: this.urlprefix + this.getType() + '/save-to-session/',
            method: "post",
            params: this.getSaveData(),
            success: onComplete
        });
    },

    removeFromSession: function () {
        Ext.Ajax.request({
            url: this.urlprefix + this.getType() + '/remove-from-session/',
            params: {id: this.data.id}
        });
    },

    reloadEditmode: function () {

        this.saveToSession(function () {
            if (this.edit && this.edit.layout.rendered) {
                this.edit.reload(true);
            }

            if (this.preview && this.preview.layout.rendered) {
                this.preview.loadCurrentPreview();
            }

        }.bind(this));
    },

    showMetaInfo: function() {

        new pimcore.element.metainfo([
            {
                name: "id",
                value: this.data.id
            },
            {
                name: "path",
                value: this.data.path + this.data.key
            }, {
                name: "parentid",
                value: this.data.parentId
            }, {
                name: "type",
                value: this.data.type
            }, {
                name: "modificationdate",
                type: "date",
                value: this.data.modificationDate
            }, {
                name: "creationdate",
                type: "date",
                value: this.data.creationDate
            }, {
                name: "usermodification",
                type: "user",
                value: this.data.userModification
            }, {
                name: "userowner",
                type: "user",
                value: this.data.userOwner
            },
            {
                name: "deeplink",
                value: window.location.protocol + "//" + window.location.hostname + "/admin/login/deeplink?document_" + this.data.id + "_" + this.data.type
            }
        ], "document");
    },

    linkTranslation: function () {
        var win = new Ext.Window({
            width: 600,
            bodyStyle: "padding:10px",
            items: [{
                xtype: "textfield",
                name: "translation",
                itemId: "translation",
                width: "100%",
                cls: "input_drop_target",
                fieldLabel: t("translation"),
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

                                win.close();
                            } else {
                                Ext.MessageBox.alert(t("error"), t("element_not_found"));
                            }
                        }.bind(this)
                    });
                }.bind(this)
            }]
        });

        win.show();
    }
});
