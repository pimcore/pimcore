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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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

            try {
                Ext.getCmp("pimcore_panel_tree_assets").expand();
                var tree = pimcore.globalmanager.get("layout_asset_tree");
                tree.tree.selectPath(this.data.idPath);
            } catch (e) {
                console.log(e);
            }
            
            this.startChangeDetector();
        }
        catch (e) {
            console.log(e);
            pimcore.helpers.closeAsset(this.id);
        }
    },


    addLoadingPanel : function () {

        // DEPRECIATED loadingpanel not active
        return;

        window.setTimeout(this.checkLoadingStatus.bind(this), 5000);

        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");

        this.loadingPanel = new Ext.Panel({
            title: t("loading"),
            closable:false,
            html: "",
            iconCls: "pimcore_icon_loading"
        });

        this.tabPanel.add(this.loadingPanel);
    },

    removeLoadingPanel: function () {

        pimcore.helpers.removeTreeNodeLoadingIndicator("asset", this.id);

        // DEPRECIATED loadingpanel not active
        return;

        if (this.loadingPanel) {
            this.tabPanel.remove(this.loadingPanel);
        }
        this.loadingPanel = null;
    },

    checkLoadingStatus: function () {

        // DEPRECIATED loadingpanel not active
        return;

        if (this.loadingPanel) {
            // loadingpanel is active close the whole asset
            pimcore.helpers.closeAsset(this.id);
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
                url: "/admin/misc/unlock-element",
                params: {
                    id: this.data.id,
                    type: "asset"
                }
            });
        }.bind(this));

        // remove this instance when the panel is closed
        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove("asset_" + this.id);
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.activate(tabId);
            pimcore.plugin.broker.fireEvent("postOpenAsset", this, this.getType());
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.tabPanel.add(this.tab);

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

            buttons.push("-");

            buttons.push({
                text: this.data.path + this.data.filename,
                iconCls: "pimcore_icon_cursor_medium",
                scale: "medium",
                handler: function () {
                    location.href = "/admin/asset/download/id/" + this.data.id;
                }.bind(this)
            });

            buttons.push("-");
            buttons.push({
                text: this.data.id,
                disabled: true
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


        // properties
        try {
            parameters.properties = Ext.encode(this.properties.getValues());
        }
        catch (e) {
            //console.log(e);
        }

        // scheduler
        try {
            if (this.scheduler) {
                parameters.scheduler = Ext.encode(this.scheduler.getValues());
            }
        }
        catch (e) {
            //console.log(e);
        }


        return parameters;
    },

    save : function (only) {
        Ext.Ajax.request({
            url: '/admin/asset/save/',
            method: "post",
            success: function (response) {
                try{
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        pimcore.helpers.showNotification(t("save"), t("successful_saved_asset"), "success");
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_saving_asset"), "error",t(rdata.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("error_saving_asset"), "error");
                }
                // reload versions
                if (this.versions) {
                    if (typeof this.versions.reload == "function") {
                        this.versions.reload();
                    }
                }
            }.bind(this),
            params: this.getSaveData(only)
        });
        
        this.resetChanges();
    },

     saveClose: function(){
        this.save();
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.remove(this.tab);
    },

    remove: function () {

        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.remove(this.tab);

        var documentNode = pimcore.globalmanager.get("layout_asset_tree").tree.getNodeById(this.id)
        var f = pimcore.globalmanager.get("layout_asset_tree").deleteAsset.bind(documentNode);
        f();
    },

    upload: function () {

        var extensionP = this.data.filename.split("\.");
        var extension = "*." + extensionP[extensionP.length - 1];

        if (!this.uploadWindow) {
            this.uploadWindow = new Ext.Window({
                layout: 'fit',
                title: 'Upload',
                closeAction: 'hide',
                width:400,
                height:170,
                modal: true
            });

            var uploadPanel = new Ext.ux.SwfUploadPanel({
                border: false,
                upload_url: '/admin/asset/replace-asset/?pimcore_admin_sid=' + pimcore.settings.sessionId + "&id=" + this.data.id,
                post_params: { parentId: this.id },
                debug: false,
                flash_url: "/pimcore/static/js/lib/ext-plugins/SwfUploadPanel/swfupload.swf",
                single_select: false,
                file_queue_limit: 1,
                file_types: extension,
                single_file_select: true,
                confirm_delete: false,
                remove_completed: true,
                listeners: {
                    "fileUploadComplete": function (asset) {
                        this.hide();
                        asset.reload();
                    }.bind(this.uploadWindow, this)
                }
            });

            this.uploadWindow.add(uploadPanel);
        }

        this.uploadWindow.show();
        this.uploadWindow.setWidth(401);
        this.uploadWindow.doLayout();
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
    }
});