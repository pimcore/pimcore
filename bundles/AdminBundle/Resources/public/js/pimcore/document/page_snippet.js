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
            iconCls: this.getIconClass(),
            document: this
        });

        // remove this instance when the panel is closed
        this.tab.on("beforedestroy", function () {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_element_unlockelement'),
                method: 'PUT',
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

        this.addToMainTabPanel();

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
                handler: this.save.bind(this, null),
                menu: [
                    {
                        text: t('save_close'),
                        iconCls: "pimcore_icon_save",
                        handler: this.saveClose.bind(this)
                    },
                    {
                        text: t('save_only_scheduled_tasks'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this, "scheduler","scheduler"),
                        hidden: !this.isAllowed("settings") || this.data.published
                    }
                ]
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
                        handler: this.save.bind(this, null),
                        hidden: !this.isAllowed("save") || !this.data.published
                    },
                    {
                        text: t('save_only_scheduled_tasks'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this, "scheduler","scheduler"),
                        hidden: !this.isAllowed("settings") || !this.data.published
                    }
                ]
            });


            this.toolbarButtons.unpublish = new Ext.Button({
                text: t('unpublish'),
                iconCls: "pimcore_material_icon_unpublish pimcore_material_icon",
                scale: "medium",
                handler: this.unpublish.bind(this)
            });

            this.toolbarButtons.remove = new Ext.Button({
                tooltip: t('delete'),
                iconCls: "pimcore_material_icon_delete pimcore_material_icon",
                scale: "medium",
                handler: this.remove.bind(this)
            });

            this.toolbarButtons.rename = new Ext.Button({
                tooltip: t('rename'),
                iconCls: "pimcore_material_icon_rename pimcore_material_icon",
                scale: "medium",
                handler: this.rename.bind(this)
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
                iconCls: "pimcore_material_icon_reload pimcore_material_icon",
                scale: "medium",
                handler: this.reload.bind(this)
            });

            if (pimcore.elementservice.showLocateInTreeButton("document")) {
                buttons.push({
                    tooltip: t('show_in_tree'),
                    iconCls: "pimcore_material_icon_locate pimcore_material_icon",
                    scale: "medium",
                    handler: this.selectInTree.bind(this)
                });
            }

            buttons.push({
                xtype: "splitbutton",
                tooltip: t("show_metainfo"),
                iconCls: "pimcore_material_icon_info pimcore_material_icon",
                scale: "medium",
                handler: this.showMetaInfo.bind(this),
                menu: this.getMetaInfoMenuItems()
            });

            buttons.push(this.getTranslationButtons());

            if(this.data["url"]) {
                buttons.push("-");
                buttons.push({
                    tooltip: t("open_in_new_window"),
                    iconCls: "pimcore_material_icon_open_window pimcore_material_icon",
                    scale: "medium",
                    handler: function () {
                        window.open(this.data.url);
                    }.bind(this)
                });

                buttons.push({
                    tooltip: t("open_preview_in_new_window"),
                    iconCls: "pimcore_material_icon_preview pimcore_material_icon",
                    scale: "medium",
                    handler: function () {
                        var date = new Date();
                        var link = this.data.path + this.data.key;
                        var linkParams = [];

                        linkParams.push("pimcore_preview=true");
                        linkParams.push("_dc=" + date.getTime());

                        // add target group parameter if available
                        if(this["edit"] && this.edit["targetGroup"]) {
                            if(this.edit.targetGroup && this.edit.targetGroup.getValue()) {
                                linkParams.push("_ptg=" + this.edit.targetGroup.getValue());
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
            }

            if (pimcore.globalmanager.get("user").isAllowed('notifications_send')) {
                buttons.push({
                    tooltip: t('share_via_notifications'),
                    iconCls: "pimcore_icon_share",
                    scale: "medium",
                    handler: this.shareViaNotifications.bind(this)
                });
            }

            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: t("id") + " " + this.data.id,
                scale: "medium"
            });

            //workflow management
            pimcore.elementservice.integrateWorkflowManagement('document', this.data.id, this, buttons);


            // version notification
            this.newerVersionNotification = new Ext.Toolbar.TextItem({
                xtype: 'tbtext',
                text: '&nbsp;&nbsp;<img src="/bundles/pimcoreadmin/img/flat-color-icons/medium_priority.svg" style="height: 16px;" align="absbottom" />&nbsp;&nbsp;'
                    + t("this_is_a_newer_not_published_version"),
                scale: "medium",
                hidden: true
            });

            buttons.push(this.newerVersionNotification);

            // check for newer version than the published
            if (this.data.versions.length > 0) {
                if (this.data.documentFromVersion) {
                    this.newerVersionNotification.show();
                }
            }


            this.toolbar = new Ext.Toolbar({
                id: "document_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "pimcore_main_toolbar",
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
            url: Routing.getBaseUrl() + '/admin/' + this.getType() + '/save-to-session',
            method: "post",
            params: this.getSaveData(),
            success: onComplete
        });
    },

    removeFromSession: function () {
        Ext.Ajax.request({
            url: Routing.getBaseUrl() + '/admin/' + this.getType() + '/remove-from-session',
            method: 'DELETE',
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

    getMetaInfo: function() {
        return {
            id: this.data.id,
            path: this.data.path + this.data.key,
            parentid: this.data.parentId,
            type: this.data.type,
            modificationdate: this.data.modificationDate,
            creationdate: this.data.creationDate,
            usermodification: this.data.userModification,
            userowner: this.data.userOwner,
            deeplink: pimcore.helpers.getDeeplink("document", this.data.id, this.data.type)
        };
    },

    showMetaInfo: function() {
        var metainfo = this.getMetaInfo();

        new pimcore.element.metainfo([
            {
                name: "id",
                value: metainfo.id
            },
            {
                name: "path",
                value: metainfo.path
            }, {
                name: "parentid",
                value: metainfo.parentid
            }, {
                name: "type",
                value: metainfo.type
            }, {
                name: "modificationdate",
                type: "date",
                value: metainfo.modificationdate
            }, {
                name: "creationdate",
                type: "date",
                value: metainfo.creationdate
            }, {
                name: "usermodification",
                type: "user",
                value: metainfo.usermodification
            }, {
                name: "userowner",
                type: "user",
                value: metainfo.userowner
            },
            {
                name: "deeplink",
                value: metainfo.deeplink
            }
        ], "document");
    },

    rename: function () {
        if(this.isAllowed("rename") && !this.data.locked && this.data.id != 1) {
            var options = {
                elementType: "document",
                elementSubType: this.getType(),
                id: this.id,
                default: this.data.key
            };
            pimcore.elementservice.editElementKey(options);
        }
    },

    shareViaNotifications: function () {
        if (pimcore.globalmanager.get("user").isAllowed('notifications_send')) {
            var elementData = {
                id:this.data.id,
                type:'document',
                published:this.data.published,
                path:this.data.path + this.data.key
            };
            if (pimcore.globalmanager.get("new_notifications")) {
                pimcore.globalmanager.get("new_notifications").getWindow().destroy();
            }
            pimcore.globalmanager.add("new_notifications", new pimcore.notification.modal(elementData));        }
    },

    publish: function($super, only, callback) {
        /* It is needed to have extra validateRequiredEditables check here
         * so as to stop propagating Admin UI changes in case of required content = true */
        if (this.validateRequiredEditables()) {
            return false;
        }

        $super(only, callback);
    },

    save: function ($super, task, only, callback, successCallback) {
        if (task !== "publish") {
            this.validateRequiredEditables(true);
        }

        $super(task, only, callback, successCallback);
    },

    validateRequiredEditables: function (dismissAlert) {
        //validate required editables against missing values
        try {
            /* No validation in case of changing system settings as template can be changed
             * if template is changed, then document editables be validated on server side
             */
            var settingsForm = Ext.getCmp("pimcore_document_settings_" + this.id);
            if(settingsForm.dirty) {
                this.data.missingRequiredEditable = null;
                return;
            }

            var emptyRequiredEditables = this.edit.getEmptyRequiredEditables();
            if (emptyRequiredEditables.length > 0) {
                if (!dismissAlert) {
                    Ext.MessageBox.show({
                        title: t("error"),
                        width: 500,
                        msg: t("complete_required_fields")
                            + '<br /><br /><textarea style="width:100%; min-height:100px; resize:none" readonly="readonly">'
                            + emptyRequiredEditables.join(", ") + "</textarea>",
                        buttons: Ext.Msg.OK
                    });
                }

                this.data.missingRequiredEditable = true;

                return true;
            }

            if(this.data.missingRequiredEditable == true) {
                this.data.missingRequiredEditable = false;
            }
        } catch(e) {
        }
    }
});
