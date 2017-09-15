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
                iconCls: "pimcore_icon_save_white",
                cls: "pimcore_save_button",
                scale: "medium",
                handler: this.unpublish.bind(this),
                menu: [{
                    text: t('save_close'),
                    iconCls: "pimcore_icon_save",
                    handler: this.unpublishClose.bind(this)
                }]
            });


            this.toolbarButtons.publish = new Ext.SplitButton({
                text: t('save_and_publish'),
                iconCls: "pimcore_icon_save_white",
                cls: "pimcore_save_button",
                scale: "medium",
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
                scale: "medium",
                handler: this.unpublish.bind(this)
            });

            this.toolbarButtons.remove = new Ext.Button({
                tooltip: t('delete'),
                iconCls: "pimcore_icon_delete",
                scale: "medium",
                handler: this.remove.bind(this)
            });

            this.toolbarButtons.rename = new Ext.Button({
                tooltip: t('rename'),
                iconCls: "pimcore_icon_key pimcore_icon_overlay_go",
                scale: "medium",
                handler: function () {
                    var options = {
                        elementType: "document",
                        elementSubType: this.getType(),
                        id: this.id,
                        default: this.data.key
                    };
                    pimcore.elementservice.editElementKey(options);
                }.bind(this)
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

            buttons.push("-");

            if(this.isAllowed("delete") && !this.data.locked && this.data.id != 1) {
                buttons.push(this.toolbarButtons.remove);
            }
            if(this.isAllowed("rename") && !this.data.locked && this.data.id != 1) {
                buttons.push(this.toolbarButtons.rename);
            }


            buttons.push({
                tooltip: t('reload'),
                iconCls: "pimcore_icon_reload",
                scale: "medium",
                handler: this.reload.bind(this)
            });

            if (pimcore.elementservice.showLocateInTreeButton("document")) {
                buttons.push({
                    tooltip: t('show_in_tree'),
                    iconCls: "pimcore_icon_show_in_tree",
                    scale: "medium",
                    handler: this.selectInTree.bind(this)
                });
            }

            buttons.push({
                tooltip: t("show_metainfo"),
                iconCls: "pimcore_icon_info",
                scale: "medium",
                handler: this.showMetaInfo.bind(this)
            });

            buttons.push(this.getTranslationButtons());

            buttons.push("-");
            buttons.push({
                tooltip: t("open"),
                iconCls: "pimcore_icon_open",
                scale: "medium",
                handler: function () {
                    var date = new Date();
                    var link = this.data.path + this.data.key;
                    var linkParams = [];

                    linkParams.push("pimcore_preview=true");
                    linkParams.push("_dc=" + date.getTime());

                    // add persona parameter if available
                    if(this["edit"] && this.edit["persona"]) {
                        if(this.edit.persona && this.edit.persona.getValue()) {
                            linkParams.push("_ptp=" + this.edit.persona.getValue());
                        }
                    }

                    if(linkParams.length) {
                        link += "?" + linkParams.join("&");
                    }

                    if(this.isDirty()) {
                        this.saveToSession(function () {
                            window.open(link);
                        });
                    } else {
                        window.open(link);
                    }
                }.bind(this)
            });
            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: this.data.id,
                scale: "medium"
            });

            //workflow management
            pimcore.elementservice.integrateWorkflowManagement('document', this.data.id, this, buttons);


            // version notification
            this.newerVersionNotification = new Ext.Toolbar.TextItem({
                xtype: 'tbtext',
                text: '&nbsp;&nbsp;<img src="/pimcore/static6/img/flat-color-icons/medium_priority.svg" style="height: 16px;" align="absbottom" />&nbsp;&nbsp;'
                    + t("this_is_a_newer_not_published_version"),
                scale: "medium",
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
                overflowHandler: 'scroller'
            });

            if (!this.data.published) {
                this.toolbarButtons.unpublish.hide();
            } else if (this.isAllowed("publish")) {
                this.toolbarButtons.save.hide();
            }
        }

        return this.toolbar;
    },

    saveToSession: function (onComplete) {

        if (typeof onComplete != "function") {
            onComplete = function () {
            };
        }

        Ext.Ajax.request({
            url: this.urlprefix + this.getType() + '/save-to-session',
            method: "post",
            params: this.getSaveData(),
            success: onComplete
        });
    },

    removeFromSession: function () {
        Ext.Ajax.request({
            url: this.urlprefix + this.getType() + '/remove-from-session',
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
    }
});
