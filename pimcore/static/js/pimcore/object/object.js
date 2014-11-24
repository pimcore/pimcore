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

pimcore.registerNS("pimcore.object.object");
pimcore.object.object = Class.create(pimcore.object.abstract, {

    initialize: function(id, options) {
        pimcore.plugin.broker.fireEvent("preOpenObject", this, "object");

        this.id = intval(id);
        this.addLoadingPanel();

        this.options = options;

        this.edit = new pimcore.object.edit(this);
        this.preview = new pimcore.object.preview(this);
        this.properties = new pimcore.element.properties(this, "object");
        this.versions = new pimcore.object.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "object");
        this.dependencies = new pimcore.element.dependencies(this, "object");
        this.notes = new pimcore.element.notes(this, "object");
        this.reports = new pimcore.report.panel("object_concrete", this);
        this.variants = new pimcore.object.variantsTab(this);
        this.getData();
    },

    getData: function () {
        var params = {id: this.id};
        if (this.options !== undefined) {
            params.layoutId = this.options.layoutId;
        }

        Ext.Ajax.request({
            url: "/admin/object/get/",
            params: params,
            success: this.getDataComplete.bind(this)
        });
    },

    getDataComplete: function (response) {


        try {
            this.data = Ext.decode(response.responseText);

            if (typeof this.data.editlock == "object") {
                pimcore.helpers.lockManager(this.id, "object", "object", this.data);
                throw "object is locked";
            }

            this.addTab();

            this.startChangeDetector();
            this.setupInheritanceDetector();
        }
        catch (e) {
            console.log(e);
            pimcore.helpers.closeObject(this.id);
        }
    },

    inheritedFields: {},
    setupInheritanceDetector: function() {
        this.tab.on("deactivate", this.stopInheritanceDetector.bind(this));
        this.tab.on("activate", this.startInheritanceDetector.bind(this));
        this.tab.on("destroy", this.stopInheritanceDetector.bind(this));
        this.startInheritanceDetector();
    },

    startInheritanceDetector: function () {
        var dataKeys = Object.keys(this.data.metaData);
        for (var i = 0; i < dataKeys.length; i++) {
            if(this.data.metaData[dataKeys[i]].inherited == true) {
                this.inheritedFields[dataKeys[i]] = true;
            }
        }

        if(!this.inheritanceDetectorInterval) {
            this.inheritanceDetectorInterval = window.setInterval(this.checkForInheritance.bind(this),1000);
        }
    },

    stopInheritanceDetector: function () {
        window.clearInterval(this.inheritanceDetectorInterval);
        this.inheritanceDetectorInterval = null;
    },

    checkForInheritance: function () {
        if (!this.edit.layout.rendered) {
            throw "edit not available";
        }

        var dataKeys = Object.keys(this.inheritedFields);
        var currentField;

        if(dataKeys.length == 0) {
            this.stopInheritanceDetector();
        }

        for (var i = 0; i < dataKeys.length; i++) {
            var field = dataKeys[i];
            if(this.data.metaData && this.data.metaData[field] && this.data.metaData[field].inherited == true) {
                if (this.edit.dataFields[field] && typeof this.edit.dataFields[field] == "object") {
                    currentField = this.edit.dataFields[field];

                    if(currentField.dataIsNotInherited()) {
                        currentField.unmarkInherited();
                        this.data.metaData[field].inherited = false;
                        delete this.inheritedFields[field];
                    }
                }
            }

        }
    },


    addTab: function () {

        // icon class
        var iconClass = "pimcore_icon_object";
        if(this.data.general["iconCls"]) {
            iconClass = this.data.general["iconCls"];
        } else if (this.data.general["icon"]) {
            iconClass = pimcore.helpers.getClassForIcon(this.data.general["icon"]);
        }


        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var tabId = "object_" + this.id;
        this.tab = new Ext.Panel({
            id: tabId,
            title: this.data.general.o_key,
            closable:true,
            layout: "border",
            items: [this.getLayoutToolbar(),this.getTabPanel()],
            object: this,
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
                    id: this.id,
                    type: "object"
                }
            });
        }.bind(this));

        // remove this instance when the panel is closed
        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove("object_" + this.id);
            pimcore.helpers.forgetOpenTab("object_" + this.id + "_object");
            pimcore.helpers.forgetOpenTab("object_" + this.id + "_variant");
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.activate(tabId);
            pimcore.plugin.broker.fireEvent("postOpenObject", this, "object");
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.tabPanel.add(this.tab);

        if (this.getAddToHistory()) {
            pimcore.helpers.recordElement(this.id, "object", this.data.general.fullpath);
        }

        // recalculate the layout
        pimcore.layout.refresh();
    },

    getTabPanel: function () {

        var items = [];

        items.push(this.edit.getLayout(this.data.layout));

        if(!empty(this.data.previewUrl)) {
            items.push(this.preview.getLayout());
        }

        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("versions")) {
            items.push(this.versions.getLayout());
        }
        if (this.isAllowed("settings")) {
            items.push(this.scheduler.getLayout());
        }

        items.push(this.dependencies.getLayout());

        var reportLayout = this.reports.getLayout();
        if(reportLayout) {
            items.push(reportLayout);
        }



        var user = pimcore.globalmanager.get("user");
        if (user.admin || (this.isAllowed("settings") && in_array("notes_events", user.permissions))) {
            if (this.isAllowed("settings")) {
                items.push(this.notes.getLayout());
            }
        }


        if(this.data.childdata.data.classes.length > 0) {
            this.search = new pimcore.object.search(this.data.childdata);
            this.search.title = t('children_grid');
            this.search.onlyDirectChildren = true;
            items.push(this.search.getLayout());
        }

        if(this.data.general.allowVariants) {
            items.push(this.variants.getLayout());
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

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            var buttons = [];

            this.toolbarButtons = {};


            this.toolbarButtons.save = new Ext.SplitButton({
                text: t('save'),
                iconCls: "pimcore_icon_save_medium",
                scale: "medium",
                handler: this.unpublish.bind(this),
                menu:[{
                    text: t('save_close'),
                    iconCls: "pimcore_icon_save",
                    handler: this.unpublishClose.bind(this)
                }]
            });


            this.toolbarButtons.publish = new Ext.SplitButton({
                text: t('save_and_publish'),
                iconCls: "pimcore_icon_publish_medium",
                scale: "medium",
                handler: this.publish.bind(this),
                menu: [{
                    text: t('save_pubish_close'),
                    iconCls: "pimcore_icon_save",
                    handler: this.publishClose.bind(this)
                },
                    {
                        text: t('save_only_new_version'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this, "version")
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
                iconCls: "pimcore_icon_unpublish_medium",
                scale: "medium",
                handler: this.unpublish.bind(this)
            });

            var reloadConfig = {
                text: t('reload'),
                iconCls: "pimcore_icon_reload_medium",
                scale: "medium",
                handler: this.reload.bind(this, this.data.currentLayoutId)
            }

            if (this.data["validLayouts"] && this.data.validLayouts.length > 1) {
                var menu = [];
                for (var i = 0; i < this.data.validLayouts.length; i++) {
                    var menuLabel = ts(this.data.validLayouts[i].name);
                    if (Number(this.data.currentLayoutId) == this.data.validLayouts[i].id) {
                        menuLabel = "<b>" + menuLabel + "</b>";
                    }
                    menu.push(
                        {
                            text: menuLabel,
                            iconCls: "pimcore_icon_reload",
                            handler: this.reload.bind(this, this.data.validLayouts[i].id)
                        });
                    reloadConfig.menu = menu;
                }
                this.toolbarButtons.reload = new Ext.SplitButton(reloadConfig);
            } else {
                this.toolbarButtons.reload = new Ext.Button(reloadConfig);
            }

            this.toolbarButtons.remove = new Ext.Button({
                text: t("delete"),
                iconCls: "pimcore_icon_delete_medium",
                scale: "medium",
                handler: this.remove.bind(this)
            });

            if (this.isAllowed("save")) {
                buttons.push(this.toolbarButtons.save);
            }
            if (this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }
            if (this.isAllowed("unpublish") && !this.data.general.o_locked) {
                buttons.push(this.toolbarButtons.unpublish);
            }

            if(this.isAllowed("delete") && !this.data.general.o_locked) {
                buttons.push(this.toolbarButtons.remove);
            }

            buttons.push("-");

            buttons.push(this.toolbarButtons.reload);

            if(this.data.general.o_type != "variant" || this.data.general.showVariants) {
                buttons.push({
                    text: t('show_in_tree'),
                    iconCls: "pimcore_icon_download_showintree",
                    scale: "medium",
                    handler: this.selectInTree.bind(this, this.data.general.o_type)
                });
            }


            buttons.push({
                text: t("show_metainfo"),
                scale: "medium",
                iconCls: "pimcore_icon_info_large",
                handler: this.showMetaInfo.bind(this)
            });


            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: t("id") + " " + this.data.general.o_id,
                scale: "medium"
            });

            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: ts(this.data.general.o_className),
                scale: "medium"
            });

            // version notification
            this.newerVersionNotification = new Ext.Toolbar.TextItem({
                xtype: 'tbtext',
                text: '&nbsp;&nbsp;<img src="/pimcore/static/img/icon/error.png" align="absbottom" />&nbsp;&nbsp;'
                    + t("this_is_a_newer_not_published_version"),
                scale: "medium",
                hidden: true
            });

            buttons.push(this.newerVersionNotification);

            // check for newer version than the published
            if (this.data.versions.length > 0) {
                if (this.data.general.o_modificationDate < this.data.versions[0].date) {
                    this.newerVersionNotification.show();
                }
            }

            this.toolbar = new Ext.Toolbar({
                id: "object_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "document_toolbar",
                items: buttons
            });

            this.toolbar.on("afterrender", function () {
                window.setTimeout(function () {
                    if (!this.data.general.o_published) {
                        this.toolbarButtons.unpublish.hide();
                    } else if (this.isAllowed("publish")) {
                        this.toolbarButtons.save.hide();
                    }
                }.bind(this), 500);
            }.bind(this));
        }

        return this.toolbar;
    },

    activate: function () {
        var tabId = "object_" + this.id;
        this.tabPanel.activate(tabId);
    },

    getSaveData : function (only, omitMandatoryCheck) {
        var data = {};

        data.id = this.id;

        // get only scheduled tasks
        if (only == "scheduler") {
            try {
                data.scheduler = Ext.encode(this.scheduler.getValues());
                return data;
            }
            catch (e) {
                console.log("scheduler not available");
                return;
            }
        }

        // data
        try {
            data.data = Ext.encode(this.edit.getValues(omitMandatoryCheck));
        }
        catch (e1) {
            //console.log(e1)
        }

        // properties
        try {
            data.properties = Ext.encode(this.properties.getValues());
        }
        catch (e2) {
            //console.log(e2);
        }

        try {
            data.general = Ext.apply({}, this.data.general);
            // object shouldn't be relocated, renamed, or anything else that is evil
            delete data.general["o_parentId"];
            delete data.general["o_type"];
            delete data.general["o_key"];
            delete data.general["o_locked"];
            delete data.general["o_classId"];

            data.general = Ext.encode(data.general);
        }
        catch (e3) {
            //console.log(e3);
        }

        // scheduler
        try {
            data.scheduler = Ext.encode(this.scheduler.getValues());
        }
        catch (e4) {
            //console.log(e4);
        }


        return data;
    },

    saveClose: function(only){
        if(this.save()) {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove(this.tab);
        }
    },

    publishClose: function(){
        this.publish(null, function () {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove(this.tab);
        }.bind(this))
    },


    publish: function (only, callback) {
        this.data.general.o_published = true;
        var state = this.save("publish", only, callback);

        if(state) {
            // toogle buttons
            this.toolbarButtons.unpublish.show();
            this.toolbarButtons.save.hide();

            // remove class in tree panel
            try {
                pimcore.globalmanager.get("layout_object_tree").tree.getNodeById(this.id).getUI()
                    .removeClass("pimcore_unpublished");
            } catch (e) { }
        }

        return state;
    },

    unpublish: function () {
        this.data.general.o_published = false;

        if(this.save("unpublish")) {
            // toogle buttons
            this.toolbarButtons.unpublish.hide();
            this.toolbarButtons.save.show();

            // set class in tree panel
            try {
                pimcore.globalmanager.get("layout_object_tree").tree.getNodeById(this.id).getUI()
                    .addClass("pimcore_unpublished");
            } catch (e) {}
        }
    },

    unpublishClose: function () {
        this.unpublish();
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.remove(this.tab);
    },

    saveToSession: function (callback) {
        this.save("session", null, callback);
    },

    save : function (task, only, callback) {

        var omitMandatoryCheck = false;

        // unpublish and save version is possible without checking mandatory fields
        if(task == "version" || task == "unpublish") {
            omitMandatoryCheck = true;
        }

        if(this.tab.disabled) {
            return;
        }

        this.tab.disable();

        var saveData = this.getSaveData(only, omitMandatoryCheck);

        if (saveData.data != false && saveData.data != "false") {

            // check for version notification
            if(this.newerVersionNotification) {
                if(task == "publish" || task == "unpublish") {
                    this.newerVersionNotification.hide();
                } else if(task != "session") {
                    this.newerVersionNotification.show();
                }
            }

            Ext.Ajax.request({
                url: '/admin/object/save/task/' + task,
                method: "post",
                params: saveData,
                success: function (response) {

                    if(task != "session") {
                        try{
                            var rdata = Ext.decode(response.responseText);
                            if (rdata && rdata.success) {
                                pimcore.helpers.showNotification(t("success"), t("your_object_has_been_saved"),
                                    "success");
                                this.resetChanges();
                            }
                            else {
                                pimcore.helpers.showNotification(t("error"), t("error_saving_object"),
                                    "error",t(rdata.message));
                            }
                        } catch(e){
                            pimcore.helpers.showNotification(t("error"), t("error_saving_object"), "error");
                        }
                        // reload versions
                        if (this.isAllowed("versions")) {
                            if (typeof this.versions.reload == "function") {
                                this.versions.reload();
                            }
                        }
                    }

                    this.tab.enable();

                    if(typeof callback == "function") {
                        callback();
                    }
                }.bind(this),
                failure: function (response) {
                    this.tab.enable();
                }.bind(this)
            });

            return true;
        } else {
            this.tab.enable();
        }
        return false;
    },


    remove: function () {
        pimcore.helpers.deleteObject(this.id);
    },

    isAllowed: function (key) {
        return this.data.userPermissions[key];
    },

    reload: function (layoutId) {
        var options = {};
        options.layoutId = layoutId;
        window.setTimeout(function (id) {
            pimcore.helpers.openObject(id, "object", options);
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
                name: "parentid",
                value: this.data.general.o_parentId
            }, {
                name: "classid",
                value: this.data.general.o_classId
            }, {
                name: "class",
                value: this.data.general.o_className
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
                value: window.location.protocol + "//" + window.location.hostname + "/admin/login/deeplink?object_" + this.data.general.o_id + "_object"
            }
        ], "object");
    }
});