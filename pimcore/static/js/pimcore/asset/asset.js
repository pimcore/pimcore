/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.asset.asset");
pimcore.asset.asset = Class.create(pimcore.element.abstract, {

    getData: function () {
        Ext.Ajax.request({
            url: "/admin/asset/get-data-by-id/",
            success: this.getDataComplete.bind(this),
            params: {
                id: this.id,
                type: this.type
            }
        });
    },

    getDataComplete: function (response) {

        try {
            this.data = Ext.decode(response.responseText);

            if (typeof this.data.editlock == "object") {
                pimcore.helpers.lockManager(this.id, "asset", this.type, this.data);
                throw "asset is locked";
            }

            this.addTab();
            this.startChangeDetector();
        }
        catch (e) {
            console.log(e);
            pimcore.helpers.closeAsset(this.id);
        }
    },

    selectInTree: function () {
        try {
            Ext.getCmp("pimcore_panel_tree_assets").expand();
            var tree = pimcore.globalmanager.get("layout_asset_tree");
            pimcore.helpers.selectPathInTree(tree.tree, this.data.idPath);
        } catch (e) {
            console.log(e);
        }
    },

    addTab: function () {

        var tabTitle = this.data.filename;
        if (this.id == 1) {
            tabTitle = "home";
        }

        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var tabId = "asset_" + this.id;

        var iconClass = "pimcore_icon_asset";
        if (this.data.type == "folder") {
            iconClass = "pimcore_icon_folder";
        }

        this.tab = new Ext.Panel({
            id: tabId,
            title: tabTitle,
            closable:true,
            layout: "border",
            items: [this.getLayoutToolbar(),this.getTabPanel()],
            asset: this,
            iconCls: iconClass
        });

        this.tab.on("activate", function () {
            this.tab.doLayout();
            pimcore.layout.refresh();
        }.bind(this));


        this.tab.on("beforedestroy", function () {
            Ext.Ajax.request({
                url: "/admin/element/unlock-element",
                params: {
                    id: this.data.id,
                    type: "asset"
                }
            });
        }.bind(this));

        // remove this instance when the panel is closed
        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove("asset_" + this.id);
            pimcore.helpers.forgetOpenTab("asset_" + this.id + "_" + this.getType());
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.activate(tabId);
            pimcore.plugin.broker.fireEvent("postOpenAsset", this, this.getType());
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.tabPanel.add(this.tab);

        if (this.getAddToHistory()) {
            pimcore.helpers.recordElement(this.id, "asset", this.data.path + this.data.filename);
        }

        // recalculate the layout
        pimcore.layout.refresh();
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            var buttons = [];

            this.toolbarButtons = {};


            if (this.isAllowed("publish")) {

                this.toolbarButtons.publish = new Ext.SplitButton({
                    text: t("save_and_publish"),
                    iconCls: "pimcore_icon_publish_medium",
                    scale: "medium",
                    handler: this.save.bind(this),
                    menu: [{
                        text: t('save_pubish_close'),
                        iconCls: "pimcore_icon_save",
                        handler: this.saveClose.bind(this)
                    },{
                        text: t('save_only_scheduled_tasks'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this, "scheduler")
                    }
                    ]
                });


                buttons.push(this.toolbarButtons.publish);
            }

            if (this.isAllowed("delete") && !this.data.locked) {
                this.toolbarButtons.remove = new Ext.Button({
                    text: t('delete'),
                    iconCls: "pimcore_icon_delete_medium",
                    scale: "medium",
                    handler: this.remove.bind(this)
                });
                buttons.push(this.toolbarButtons.remove);
            }

            buttons.push("-");

            if (this.isAllowed("publish")) {
                this.toolbarButtons.upload = new Ext.Button({
                    text: t("upload"),
                    iconCls: "pimcore_icon_upload_medium",
                    scale: "medium",
                    handler: this.upload.bind(this)
                });
                buttons.push(this.toolbarButtons.upload);
            }

            buttons.push("-");

            buttons.push({
                text: t('reload'),
                iconCls: "pimcore_icon_reload_medium",
                scale: "medium",
                handler: this.reload.bind(this)
            });

            buttons.push({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_download_showintree",
                scale: "medium",
                handler: this.selectInTree.bind(this)
            });


            buttons.push({
                text: t("show_metainfo"),
                scale: "medium",
                iconCls: "pimcore_icon_info_large",
                handler: this.showMetaInfo.bind(this)
            });


            buttons.push("-");

            buttons.push({
                text: this.data.path + this.data.filename,
                iconCls: "pimcore_icon_download_medium",
                scale: "medium",
                handler: function () {
                    pimcore.helpers.download("/admin/asset/download/id/" + this.data.id);
                }.bind(this)
            });

            // only for videos and images
            if (this.isAllowed("publish") && in_array(this.data.type,["image","video"])) {
                buttons.push({
                    text: t("clear_thumbnails"),
                    iconCls: "pimcore_icon_menu_clear_thumbnails",
                    scale: "medium",
                    handler: function () {
                        Ext.Ajax.request({
                            url: "/admin/asset/clear-thumbnail",
                            params: {
                                id: this.data.id
                            }
                        });
                    }.bind(this)
                });
            }

            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: t("id") + " " + this.data.id,
                scale: "medium"
            });


            this.toolbar = new Ext.Toolbar({
                id: "asset_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "document_toolbar",
                items: buttons
            });
        }

        return this.toolbar;
    },

    activate: function () {
        var tabId = "asset_" + this.id;
        this.tabPanel.activate(tabId);
    },

    getSaveData : function (only) {
        var parameters = {};

        parameters.id = this.id;


        // get only scheduled tasks
        if (only == "scheduler") {
            try {
                parameters.scheduler = Ext.encode(this.scheduler.getValues());
                return parameters;
            }
            catch (e) {
                console.log("scheduler not available");
                return;
            }
        }


        // meta-data
        try {
            parameters.metadata = Ext.encode(this.metadata.getValues());
        }
        catch (e2) {
            //console.log(e);
        }

        // properties
        try {
            parameters.properties = Ext.encode(this.properties.getValues());
        }
        catch (e2) {
            //console.log(e);
        }

        // scheduler
        try {
            if (this.scheduler) {
                parameters.scheduler = Ext.encode(this.scheduler.getValues());
            }
        }
        catch (e3) {
            //console.log(e);
        }


        return parameters;
    },

    save : function (only, callback) {

        if(this.tab.disabled) {
            return;
        }

        this.tab.disable();
        Ext.Ajax.request({
            url: '/admin/asset/save/',
            method: "post",
            success: function (response) {
                try{
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        pimcore.helpers.showNotification(t("save"), t("successful_saved_asset"), "success");
                        this.resetChanges();
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_saving_asset"), "error",t(rdata.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("error_saving_asset"), "error");
                }
                // reload versions
                if (this.isAllowed("versions")) {
                    if (this["versions"] && typeof this.versions.reload == "function") {
                        this.versions.reload();
                    }
                }

                this.tab.enable();

                if(typeof callback == "function") {
                    callback();
                }
            }.bind(this),
            failure: function () {
                this.tab.enable();
            },
            params: this.getSaveData(only)
        });
    },

    saveClose: function(){
        this.save(null, function () {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove(this.tab);
        }.bind(this));
    },

    remove: function () {
        pimcore.helpers.deleteAsset(this.id);
    },

    upload: function () {

        pimcore.helpers.uploadDialog('/admin/asset/replace-asset/?pimcore_admin_sid='
            + pimcore.settings.sessionId + "&id=" + this.data.id, "Filedata", function() {
            this.reload();
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    },

    isAllowed : function (key) {
        return this.data.userPermissions[key];
    },

    setType: function (type) {
        this.type = type;
    },

    getType: function () {
        return this.type;
    },

    reload: function () {
        window.setTimeout(function (id, type) {
            pimcore.helpers.openAsset(id, type);
        }.bind(window, this.id, this.getType()), 500);

        pimcore.helpers.closeAsset(this.id);
    },

    showMetaInfo: function() {

        new pimcore.element.metainfo([
            {
                name: "id",
                value: this.data.id
            },
            {
                name: "path",
                value: this.data.path + this.data.filename
            }, {
                name: "type",
                value: this.data.type
            }, {
                name: "mimetype",
                value: this.data.mimetype
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
                value: window.location.protocol + "//" + window.location.hostname + "/admin/login/deeplink?asset_" + this.data.id + "_" + this.data.type
            }
        ], "asset");
    }

});