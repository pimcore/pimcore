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

pimcore.registerNS("pimcore.object.folder");
pimcore.object.folder = Class.create(pimcore.object.abstract, {

    type: "folder",

    initialize: function(id, options) {

        this.options = options;
        this.id = intval(id);
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenObject", this, "folder");
        this.getData();
    },

    init: function () {

        var user = pimcore.globalmanager.get("user");

        this.search = new pimcore.object.search(this, "folder");

        if (this.isAllowed("properties")) {
            this.properties = new pimcore.element.properties(this, "object");
        }

        if (user.isAllowed("notes_events")) {
            this.notes = new pimcore.element.notes(this, "object");
        }

        this.dependencies = new pimcore.element.dependencies(this, "object");
        this.tagAssignment = new pimcore.element.tag.assignment(this, "object");
        this.workflows = new pimcore.element.workflows(this, "object");
    },


    getData: function () {
        var options = this.options || {};
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_dataobject_getfolder'),
            params: {id: this.id},
            ignoreErrors: options.ignoreNotFoundError,
            success: this.getDataComplete.bind(this),
            failure: function() {
                this.forgetOpenTab();
            }.bind(this)
        });
    },

    forgetOpenTab: function() {
        pimcore.globalmanager.remove("object_" + this.id);
        pimcore.helpers.forgetOpenTab("object_" + this.id + "_folder");
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
            console.log(e);
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
            title: htmlspecialchars(tabTitle),
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
                url: Routing.generate('pimcore_admin_element_unlockelement'),
                method: 'PUT',
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
            this.tab.updateLayout();
            pimcore.layout.refresh();
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.setActiveItem(tabId);
            pimcore.plugin.broker.fireEvent("postOpenObject", this, "folder");

            // load selected class if available
            if(this.data["selectedClass"]) {
                this.search.setClass(this.data["selectedClass"]);
            }

        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.addToMainTabPanel();

        if (this.getAddToHistory()) {
            pimcore.helpers.recordElement(this.id, "object", this.data.general.o_path + this.data.general.o_key);
        }

        // recalculate the layout
        pimcore.layout.refresh();
    },

    activate: function () {
        var tabId = "object_" + this.id;
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem(tabId);
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.Button({
                text: t('save'),
                iconCls: "pimcore_icon_save_white",
                cls: "pimcore_save_button",
                scale: "medium",
                handler: this.save.bind(this, "publish")
            });

            this.toolbarButtons.remove = new Ext.Button({
                tooltip: t('delete_folder'),
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

            if (this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }

            buttons.push("-");

            if(this.isAllowed("delete") && !this.data.general.o_locked && this.data.general.o_id != 1) {
                buttons.push(this.toolbarButtons.remove);
            }
            if(this.isAllowed("rename") && !this.data.general.o_locked && this.data.general.o_id != 1) {
                buttons.push(this.toolbarButtons.rename);
            }

            buttons.push({
                tooltip: t('reload'),
                iconCls: "pimcore_material_icon_reload pimcore_material_icon",
                scale: "medium",
                handler: this.reload.bind(this)
            });

            if (pimcore.elementservice.showLocateInTreeButton("object")) {
                buttons.push({
                    tooltip: t('show_in_tree'),
                    iconCls: "pimcore_material_icon_locate pimcore_material_icon",
                    scale: "medium",
                    handler: this.selectInTree.bind(this, "folder")
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

            buttons.push({
                tooltip: t("search_and_move"),
                iconCls: "pimcore_material_icon_download_zip pimcore_material_icon",
                scale: "medium",
                handler: pimcore.helpers.searchAndMove.bind(this, this.data.general.o_id,
                    function () {
                        if (this.search.grid) {
                            this.search.grid.getStore().reload();
                        } else {
                            this.reload();
                        }
                        //refresh complete object tree as moved object(s) source is unknown
                        pimcore.elementservice.refreshRootNodeAllTrees("object");
                    }.bind(this), "object")
            });

            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: t("id") + " " + this.data.general.o_id,
                scale: "medium"
            });

            //workflow management
            pimcore.elementservice.integrateWorkflowManagement('object', this.id, this, buttons);

            this.toolbar = new Ext.Toolbar({
                id: "object_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "pimcore_main_toolbar",
                items: buttons,
                overflowHandler: 'scroller'
            });
        }

        return this.toolbar;
    },

    getTabPanel: function () {

        var items = [];
        var user = pimcore.globalmanager.get("user");

        var search = this.search.getLayout();
        if (search) {
            items.push(search);
        }
        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        items.push(this.dependencies.getLayout());

        if (user.isAllowed("notes_events")) {
            items.push(this.notes.getLayout());
        }

        if (user.isAllowed("tags_assignment")) {
            items.push(this.tagAssignment.getLayout());
        }

        if (user.isAllowed("workflow_details") && this.data.workflowManagement && this.data.workflowManagement.hasWorkflowManagement === true) {
            items.push(this.workflows.getLayout());
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
        return data;
    },

    save : function (task) {

        if(this.tab.disabled || this.tab.isMasked()) {
            return;
        }

        this.tab.mask();

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_dataobject_savefolder', {task: task}),
            method: "PUT",
            params: this.getSaveData(),
            success: function (response) {
                try{
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
                        this.resetChanges();
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("saving_failed"),
                            "error",t(rdata.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
                }

                this.tab.unmask();
            }.bind(this),
            failure: function () {
                this.tab.unmask();
            }
        });

    },


    remove: function () {
        var options = {
            "elementType" : "object",
            "id": this.id
        };
        pimcore.elementservice.deleteElement(options);
    },

    isAllowed : function (key) {
        return this.data.userPermissions[key];
    },

    reload: function () {
        this.tab.on("close", function() {
            var currentTabIndex = this.tab.ownerCt.items.indexOf(this.tab);
            window.setTimeout(function (id) {
                pimcore.helpers.openObject(id, "folder", {tabIndex: currentTabIndex});
            }.bind(window, this.id), 500);
        }.bind(this));

        pimcore.helpers.closeObject(this.id);
    },

    getMetaInfo: function() {
        return {
            id: this.data.general.o_id,
            path: this.data.general.fullpath,
            modificationdate: this.data.general.o_modificationDate,
            creationdate: this.data.general.o_creationDate,
            usermodification: this.data.general.o_userModification,
            userowner: this.data.general.o_userOwner,
            deeplink: pimcore.helpers.getDeeplink("object", this.data.general.o_id, "folder")
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
        ], "folder");
    },

    rename: function () {
        if(this.isAllowed("rename") && !this.data.general.o_locked && this.data.general.o_id != 1) {
            var options = {
                elementType: "object",
                elementSubType: this.data.general.o_type,
                id: this.id,
                default: this.data.general.o_key
            }
            pimcore.elementservice.editElementKey(options);
        }
    }

});

