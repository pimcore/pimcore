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

pimcore.registerNS("pimcore.object.object");
pimcore.object.object = Class.create(pimcore.object.abstract, {

    initialize: function(id) {
        pimcore.plugin.broker.fireEvent("preOpenObject", this, "object");

        this.addLoadingPanel();

        this.id = intval(id);

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
        Ext.Ajax.request({
            url: "/admin/object/get/",
            params: {id: this.id},
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

                    if(currentField.isDirty()) {
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

        if (this.isAllowed("settings")) {
            items.push(this.notes.getLayout());
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

            this.toolbarButtons.reload = new Ext.Button({
                text: t('reload'),
                iconCls: "pimcore_icon_reload_medium",
                scale: "medium",
                handler: this.reload.bind(this)
            });

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

            buttons.push({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_download_showintree",
                scale: "medium",
                handler: this.selectInTree.bind(this, this.data.general.o_type)
            });


            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: this.data.general.o_id,
                scale: "medium"
            });


            // version notification
            this.newerVersionNotification = new Ext.Toolbar.TextItem({
                xtype: 'tbtext',
                text: '&nbsp;&nbsp;<img src="/pimcore/static/img/icon/error.png" align="absbottom" />&nbsp;&nbsp;' + t("this_is_a_newer_not_published_version"),
                scale: "medium",
                hidden: true
            });

            buttons.push(this.newerVersionNotification);

            // check for newer version than the published
            if (this.data.versions.length > 1) {
                if (this.data.general.o_modificationDate != this.data.versions[0].date) {
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

    maskFrames: function () {
        if (this.edit) {
            this.edit.maskFrames();
        }
    },

    unmaskFrames: function () {
        if (this.edit) {
            this.edit.unmaskFrames();
        }
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
        catch (e) {
            //console.log(e)
        }

        // properties
        try {
            data.properties = Ext.encode(this.properties.getValues());
        }
        catch (e) {
            //console.log(e);
        }

        try {
            data.general = Ext.encode(this.data.general);
        }
        catch (e) {
            //console.log(e);
        }

        // scheduler
        try {
            data.scheduler = Ext.encode(this.scheduler.getValues());
        }
        catch (e) {
            //console.log(e);
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
        if(this.publish()) {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove(this.tab);
        }
    },


    publish: function (only) {
        this.data.general.o_published = true;
        var state = this.save("publish", only);

        if(state) {
            // toogle buttons
            this.toolbarButtons.unpublish.show();
            this.toolbarButtons.save.hide();

            // remove class in tree panel
            try {
                pimcore.globalmanager.get("layout_object_tree").tree.getNodeById(this.id).getUI().removeClass("pimcore_unpublished");
            } catch (e) { };
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
                pimcore.globalmanager.get("layout_object_tree").tree.getNodeById(this.id).getUI().addClass("pimcore_unpublished");
            } catch (e) {};
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

        var callback = callback;
        var saveData = this.getSaveData(only, omitMandatoryCheck);

        if (saveData.data != false && saveData.data != "false") {

            // check for version notification
            if(this.newerVersionNotification) {
                if(task == "publish") {
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
                                pimcore.helpers.showNotification(t("success"), t("your_object_has_been_saved"), "success");
                                this.resetChanges();
                            }
                            else {
                                pimcore.helpers.showNotification(t("error"), t("error_saving_object"), "error",t(rdata.message));
                            }
                        } catch(e){
                            pimcore.helpers.showNotification(t("error"), t("error_saving_object"), "error");
                        }
                        // reload versions
                        if (this.versions) {
                            if (typeof this.versions.reload == "function") {
                                this.versions.reload();
                            }
                        }
                    }

                    if(typeof callback == "function") {
                        callback();
                    }
                }.bind(this)
            });
            
            return true;
        }
        return false;
    },


    remove: function () {
        pimcore.helpers.deleteObject(this.id);
    },

    isAllowed: function (key) {
        return this.data.userPermissions[key];
    },

    reload: function () {
        window.setTimeout(function (id) {
            pimcore.helpers.openObject(id, "object");
        }.bind(window, this.id), 500);

        pimcore.helpers.closeObject(this.id);
    }
});