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

pimcore.registerNS("pimcore.object.folder");
pimcore.object.folder = Class.create(pimcore.object.abstract, {

    type: "folder",

    initialize: function(id) {

        this.id = intval(id);
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenObject", this, "folder");
        this.getData();
    },

    init: function () {

        this.search = new pimcore.object.search(this);

        if (this.isAllowed("properties")) {
            this.properties = new pimcore.element.properties(this, "object");
        }
        if (this.isAllowed("settings")) {
            this.notes = new pimcore.element.notes(this, "object");
        }

        this.dependencies = new pimcore.element.dependencies(this, "object");
    },


    getData: function () {
        Ext.Ajax.request({
            url: "/admin/object/get-folder/",
            params: {id: this.id},
            success: this.getDataComplete.bind(this)
        });
    },

    getDataComplete: function (response) {
        try {
            this.data = Ext.decode(response.responseText);

            if (typeof this.data.editlock == "object") {
                pimcore.helpers.lockManager(this.id, "object", "folder", this.data);
                throw "object is locked";
            }

            this.init();
            this.addTab();
            this.startChangeDetector();
        }
        catch (e) {
            pimcore.helpers.closeObject(this.id);
        }
    },


    addTab: function () {

        var tabTitle = this.data.general.o_key;
        if (this.id == 1) {
            tabTitle = "home";
        }

        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var tabId = "object_" + this.id;

        this.tab = new Ext.Panel({
            id: tabId,
            title: tabTitle,
            closable:true,
            layout: "border",
            items: [
                this.getLayoutToolbar(),
                this.getTabPanel()
            ],
            iconCls: "pimcore_icon_folder",
            object: this
        });

        this.tab.on("beforedestroy", function () {
            Ext.Ajax.request({
                url: "/admin/element/unlock-element",
                params: {
                    id: this.id,
                    type: "object"
                }
            });
        }.bind(this));

        // remove this instance when the panel is closed
        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove("object_" + this.id);
            pimcore.helpers.forgetOpenTab("object_" + this.id + "_folder");

        }.bind(this));

        this.tab.on("activate", function () {
            this.tab.doLayout();
            pimcore.layout.refresh();
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.activate(tabId);
            pimcore.plugin.broker.fireEvent("postOpenObject", this, "folder");

            // load selected class if available
            if(this.data["selectedClass"]) {
                this.search.setClass(this.data["selectedClass"]);
            }

        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.tabPanel.add(this.tab);

        if (this.getAddToHistory()) {
            pimcore.helpers.recordElement(this.id, "object", this.data.general.o_path + this.data.general.o_key);
        }

        // recalculate the layout
        pimcore.layout.refresh();
    },

    activate: function () {
        var tabId = "object_" + this.id;
        this.tabPanel.activate(tabId);
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.Button({
                text: t('save'),
                iconCls: "pimcore_icon_publish_medium",
                scale: "medium",
                handler: this.save.bind(this)
            });

            this.toolbarButtons.remove = new Ext.Button({
                text: t('delete_folder'),
                iconCls: "pimcore_icon_delete_medium",
                scale: "medium",
                handler: this.remove.bind(this)
            });

            var buttons = [];

            if (this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }

            if(this.isAllowed("delete") && !this.data.general.o_locked && this.data.general.o_id != 1) {
                buttons.push(this.toolbarButtons.remove);
            }


            this.toolbarButtons.reload = new Ext.Button({
                text: t('reload'),
                iconCls: "pimcore_icon_reload_medium",
                scale: "medium",
                handler: this.reload.bind(this)
            });
            buttons.push("-");
            buttons.push(this.toolbarButtons.reload);

            buttons.push({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_download_showintree",
                scale: "medium",
                handler: this.selectInTree.bind(this, "folder")
            });


            buttons.push({
                text: t("show_metainfo"),
                scale: "medium",
                iconCls: "pimcore_icon_info_large",
                handler: this.showMetaInfo.bind(this)
            });


            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: this.data.general.o_id,
                scale: "medium"
            });

            this.toolbar = new Ext.Toolbar({
                id: "object_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "document_toolbar",
                items: buttons
            });
        }

        return this.toolbar;
    },

    getTabPanel: function () {

        var items = [];

        var search = this.search.getLayout();
        if (search) {
            items.push(search);
        }
        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        items.push(this.dependencies.getLayout());

        if (this.isAllowed("settings")) {
            items.push(this.notes.getLayout());
        }

        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    getSaveData: function () {
        var data = {};

        data.id = this.id;

        // properties
        try {
            data.properties = Ext.encode(this.properties.getValues());
        }
        catch (e1) {
            //console.log(e1);
        }


        try {
            data.general = Ext.apply({}, this.data.general);
            // object shouldn't be relocated, renamed, or anything else that is evil
            delete data.general["o_parentId"];
            delete data.general["o_type"];
            delete data.general["o_key"];
            delete data.general["o_locked"];

            data.general = Ext.encode(data.general);
        }
        catch (e2) {
            //console.log(e2);
        }

        try {
            data.gridconfig = Ext.encode(this.search.getGridConfig());
        } catch (e3) {
            //console.log(e3);
        }

        return data;
    },

    save : function (task) {

        if(this.tab.disabled) {
            return;
        }

        this.tab.disable();

        Ext.Ajax.request({
            url: '/admin/object/save-folder/task/' + task,
            method: "post",
            params: this.getSaveData(),
            success: function (response) {
                try{
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        pimcore.helpers.showNotification(t("success"), t("your_object_has_been_saved"), "success");
                        this.resetChanges();
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_saving_object"),
                            "error",t(rdata.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("error_saving_object"), "error");
                }

                this.tab.enable();
            }.bind(this),
            failure: function () {
                this.tab.enable();
            }
        });

    },


    remove: function () {
        pimcore.helpers.deleteObject(this.id);
    },

    isAllowed : function (key) {
        return this.data.userPermissions[key];
    },

    reload: function () {
        window.setTimeout(function (id) {
            pimcore.helpers.openObject(id, "folder");
        }.bind(window, this.id), 500);

        pimcore.helpers.closeObject(this.id);
    },

    showMetaInfo: function() {

        new pimcore.element.metainfo([
        {
            name: "id",
            value: this.data.general.o_id
        },
        {
            name: "path",
            value: this.data.general.fullpath
        }, {
            name: "modificationdate",
            type: "date",
            value: this.data.general.o_modificationDate
        }, {
            name: "creationdate",
            type: "date",
            value: this.data.general.o_creationDate
        }, {
            name: "usermodification",
            type: "user",
            value: this.data.general.o_userModification
        }, {
            name: "userowner",
            type: "user",
            value: this.data.general.o_userOwner
        },
        {
            name: "deeplink",
            value: window.location.protocol + "//" + window.location.hostname + "/admin/login/deeplink?object_" + this.data.general.o_id + "_folder"
        }
        ], "folder");
    }

});

