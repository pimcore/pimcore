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

pimcore.registerNS("pimcore.object.object");
pimcore.object.object = Class.create(pimcore.object.abstract, {

    initialize: function (id, options) {
        this.id = intval(id);
        this.options = options;

        pimcore.plugin.broker.fireEvent("preOpenObject", this, "object");

        this.addLoadingPanel();

        var user = pimcore.globalmanager.get("user");

        //TODO why do we create all this stuff and decide whether we want to display it or not ????????????????
        this.edit = new pimcore.object.edit(this);
        this.preview = new pimcore.object.preview(this);
        this.properties = new pimcore.element.properties(this, "object");
        this.versions = new pimcore.object.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "object");
        this.dependencies = new pimcore.element.dependencies(this, "object");
        this.workflows = new pimcore.element.workflows(this, "object");

        if (user.isAllowed("notes_events")) {
            this.notes = new pimcore.element.notes(this, "object");
        }

        this.reports = new pimcore.report.panel("object_concrete", this);
        this.variants = new pimcore.object.variantsTab(this);
        this.appLogger = new pimcore.log.admin({
            localMode: true,
            searchParams: {
                relatedobject: this.id
            }
        });
        this.tagAssignment = new pimcore.element.tag.assignment(this, "object");

        this.getData();
    },

    getData: function () {
        var params = {id: this.id};
        if (this.options !== undefined) {
            params.layoutId = this.options.layoutId;
        }

        var options = this.options || {};

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_dataobject_get'),
            params: params,
            ignoreErrors: options.ignoreNotFoundError,
            success: this.getDataComplete.bind(this),
            failure: function () {
                this.forgetOpenTab();
            }.bind(this)
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

            //update published state in trees
            pimcore.elementservice.setElementPublishedState({
                elementType: "object",
                id: this.id,
                published: this.data.general.o_published
            });

        }
        catch (e) {
            console.log(e);

            this.forgetOpenTab();

            if (this.toolbar) {
                this.toolbar.destroy();
            }
            pimcore.helpers.closeObject(this.id);
        }
    },

    inheritedFields: {},
    setupInheritanceDetector: function () {
        this.tab.on("deactivate", this.stopInheritanceDetector.bind(this));
        this.tab.on("activate", this.startInheritanceDetector.bind(this));
        this.tab.on("destroy", this.stopInheritanceDetector.bind(this));
        this.startInheritanceDetector();
    },

    startInheritanceDetector: function () {
        if(this.data.metaData) {
            var dataKeys = Object.keys(this.data.metaData);
            for (var i = 0; i < dataKeys.length; i++) {
                if (this.data.metaData[dataKeys[i]].inherited == true) {
                    this.inheritedFields[dataKeys[i]] = true;
                }
            }
        }

        if (!this.inheritanceDetectorInterval) {
            this.inheritanceDetectorInterval = window.setInterval(this.checkForInheritance.bind(this), 1000);
        }
    },

    stopInheritanceDetector: function () {
        window.clearInterval(this.inheritanceDetectorInterval);
        this.inheritanceDetectorInterval = null;
    },

    checkForInheritance: function () {

        // do not run when tab is not active
        if(document.hidden) {
            return;
        }

        if (!this.edit.layout.rendered) {
            throw "edit not available";
        }


        var dataKeys = Object.keys(this.inheritedFields);
        var currentField;

        if (dataKeys.length == 0) {
            this.stopInheritanceDetector();
        }

        for (var i = 0; i < dataKeys.length; i++) {
            var field = dataKeys[i];
            if (this.data.metaData && this.data.metaData[field] && this.data.metaData[field].inherited == true) {
                if (this.edit.dataFields[field] && typeof this.edit.dataFields[field] == "object") {
                    currentField = this.edit.dataFields[field];

                    if (currentField.dataIsNotInherited()) {
                        currentField.unmarkInherited();
                        this.data.metaData[field].inherited = false;
                        delete this.inheritedFields[field];
                    }
                }
            }

        }
    },


    addTab: function () {

        if (this.data.general["iconCls"]) {
            iconClass = this.data.general["iconCls"];
        } else if (this.data.general["icon"]) {
            iconClass = pimcore.helpers.getClassForIcon(this.data.general["icon"]);
        }

        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var tabId = "object_" + this.id;
        this.tab = new Ext.Panel({
            id: tabId,
            title: htmlspecialchars(this.data.general.o_key),
            closable: true,
            layout: "border",
            items: [this.getLayoutToolbar(), this.getTabPanel()],
            object: this,
            cls: "pimcore_class_" + this.data.general.o_className,
            iconCls: iconClass
        });

        this.tab.on("activate", function () {
            this.tab.updateLayout();
            pimcore.layout.refresh();
        }.bind(this));

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
            this.forgetOpenTab();
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.setActiveItem(tabId);
            pimcore.plugin.broker.fireEvent("postOpenObject", this, "object");

            if(this.options && this.options['uiState']) {
                this.setUiState(this.tabbar, this.options['uiState']);
            }
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.addToMainTabPanel();

        if (this.getAddToHistory()) {
            pimcore.helpers.recordElement(this.id, "object", this.data.general.fullpath);
        }

        // recalculate the layout
        pimcore.layout.refresh();
    },

    forgetOpenTab: function () {
        pimcore.globalmanager.remove("object_" + this.id);
        pimcore.helpers.forgetOpenTab("object_" + this.id + "_object");
        pimcore.helpers.forgetOpenTab("object_" + this.id + "_variant");

    },

    getTabPanel: function () {

        var items = [];
        var user = pimcore.globalmanager.get("user");

        //try {
        items.push(this.edit.getLayout(this.data.layout));
        //} catch (e) {
        //    console.log(e);
        //}

        if (this.data.hasPreview) {
            try {
                items.push(this.preview.getLayout());
            } catch (e) {

            }
        }

        if (this.isAllowed("properties")) {
            try {
                items.push(this.properties.getLayout());
            } catch (e) {

            }
        }
        try {
            if (this.isAllowed("versions")) {
                items.push(this.versions.getLayout());
            }
        } catch (e) {

        }

        if (this.isAllowed("settings")) {
            try {
                items.push(this.scheduler.getLayout());
            } catch (e) {
                console.log(e);

            }
        }

        try {
            items.push(this.dependencies.getLayout());
        } catch (e) {

        }

        try {
            var reportLayout = this.reports.getLayout();
            if (reportLayout) {
                items.push(reportLayout);
            }
        } catch (e) {
            console.log(e);

        }

        if (user.isAllowed("notes_events")) {
            items.push(this.notes.getLayout());
        }

        if (user.isAllowed("tags_assignment")) {
            items.push(this.tagAssignment.getLayout());
        }

        if (user.isAllowed("workflow_details") && this.data.workflowManagement && this.data.workflowManagement.hasWorkflowManagement === true) {
            items.push(this.workflows.getLayout());
        }

        //
        if (this.data.childdata.data.classes.length > 0) {
            try {
                this.search = new pimcore.object.search(this.data.childdata, "children");
                this.search.title = t('children_grid');
                this.search.onlyDirectChildren = true;
                items.push(this.search.getLayout());
            } catch (e) {

            }
        }

        if (this.data.general.allowVariants) {
            try {
                items.push(this.variants.getLayout());
            } catch (e) {
                console.log(e);
            }
        }

        if (user.isAllowed("application_logging") && this.data.general.showAppLoggerTab) {
            try {
                var appLoggerTab = this.appLogger.getTabPanel();
                items.push(appLoggerTab);
            } catch (e) {
                console.log(e);
            }
        }


        this.tabbar = Ext.create('Ext.tab.Panel', {
            tabPosition: "top",
            region: 'center',
            enableTabScroll: true,
            border: false,
            items: items
        });

        return this.tabbar;
    },

    getLayoutToolbar: function () {

        if (!this.toolbar) {

            var buttons = [];

            this.toolbarButtons = {};


            this.toolbarButtons.save = new Ext.SplitButton({
                text: t('save'),
                iconCls: "pimcore_icon_save_white",
                cls: "pimcore_save_button",
                scale: "medium",
                handler: this.save.bind(this, "version"),
                menu: [
                    {
                        text: t('save_close'),
                        iconCls: "pimcore_icon_save",
                        handler: function() {
                            this.save("version");
                            this.close();
                        }.bind(this)
                    },
                    {
                        text: t('save_only_scheduled_tasks'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this, "scheduler", "scheduler"),
                        hidden: !this.isAllowed("settings") || this.data.general.o_published
                    }
                ]
            });


            this.toolbarButtons.publish = new Ext.SplitButton({
                text: t('save_and_publish'),
                iconCls: "pimcore_icon_save_white",
                cls: "pimcore_save_button",
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
                        handler: this.save.bind(this, "version"),
                        hidden: !this.isAllowed("save") || !this.data.general.o_published
                    },
                    {
                        text: t('save_only_scheduled_tasks'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this, "scheduler", "scheduler"),
                        hidden: !this.isAllowed("settings") || !this.data.general.o_published
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
                tooltip: t("delete"),
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

            if (this.isAllowed("save")) {
                buttons.push(this.toolbarButtons.save);
            }
            if (this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }
            if (this.isAllowed("unpublish") && !this.data.general.o_locked) {
                buttons.push(this.toolbarButtons.unpublish);
            }

            buttons.push("-");

            if (this.isAllowed("delete") && !this.data.general.o_locked) {
                buttons.push(this.toolbarButtons.remove);
            }
            if (this.isAllowed("rename") && !this.data.general.o_locked) {
                buttons.push(this.toolbarButtons.rename);
            }

            var reloadConfig = {
                tooltip: t('reload'),
                iconCls: "pimcore_material_icon_reload pimcore_material_icon",
                scale: "medium",
                handler: this.reload.bind(this, {
                    layoutId: this.data.currentLayoutId
                })
            };

            if (this.data["validLayouts"] && this.data.validLayouts.length > 1) {
                reloadConfig.xtype = "splitbutton";

                var menu = [];
                for (var i = 0; i < this.data.validLayouts.length; i++) {
                    var menuLabel = t(this.data.validLayouts[i].name);
                    if (this.data.currentLayoutId == this.data.validLayouts[i].id) {
                        menuLabel = "<b>" + menuLabel + "</b>";
                    }
                    menu.push({
                        text: menuLabel,
                        iconCls: "pimcore_icon_reload",
                        handler: this.reload.bind(this, {
                            layoutId: this.data.validLayouts[i].id
                        })
                    });
                }
                reloadConfig.menu = menu;
            } else {
                reloadConfig.xtype = "button";
            }

            buttons.push(reloadConfig);

            if (pimcore.elementservice.showLocateInTreeButton("object")) {
                if (this.data.general.o_type != "variant" || this.data.general.showVariants) {
                    buttons.push({
                        tooltip: t('show_in_tree'),
                        iconCls: "pimcore_material_icon_locate pimcore_material_icon",
                        scale: "medium",
                        handler: this.selectInTree.bind(this, this.data.general.o_type)
                    });
                }
            }

            buttons.push({
                xtype: "splitbutton",
                tooltip: t("show_metainfo"),
                iconCls: "pimcore_material_icon_info pimcore_material_icon",
                scale: "medium",
                handler: this.showMetaInfo.bind(this),
                menu: this.getMetaInfoMenuItems()
            });

            if (this.data.general.showFieldLookup) {
                buttons.push({
                    xtype: "button",
                    tooltip: t("fieldlookup"),
                    iconCls: "pimcore_material_fieldlookup pimcore_material_icon",
                    scale: "medium",
                    handler: function() {
                        var object = this.edit.object;
                        var config = {
                            classid: object.data.general.o_classId
                        }
                        var dialog = new pimcore.object.fieldlookup.filterdialog(config, null, object);
                        dialog.show();
                    }.bind(this)
                });
            }


            if (this.data.hasPreview) {
                buttons.push("-");
                buttons.push({
                    tooltip: t("open"),
                    iconCls: "pimcore_material_icon_preview pimcore_material_icon",
                    scale: "medium",
                    handler: function () {
                        var date = new Date();
                        var path = Routing.generate('pimcore_admin_dataobject_dataobject_preview', {id: this.data.general.o_id, time: date.getTime()});
                        this.saveToSession(function () {
                            window.open(path);
                        });
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
                text: t("id") + " " + this.data.general.o_id,
                scale: "medium"
            });

            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: t(this.data.general.o_className),
                scale: "medium"
            });

            // version notification
            this.newerVersionNotification = new Ext.Toolbar.TextItem({
                xtype: 'tbtext',
                text: '&nbsp;&nbsp;<img src="/bundles/pimcoreadmin/img/flat-color-icons/medium_priority.svg" style="height: 16px;" align="absbottom" />&nbsp;&nbsp;'
                + t("this_is_a_newer_not_published_version"),
                scale: "medium",
                hidden: true
            });

            buttons.push(this.newerVersionNotification);

            //workflow management
            pimcore.elementservice.integrateWorkflowManagement('object', this.id, this, buttons);

            // check for newer version than the published
            if (this.data.versions.length > 0) {
                if (this.data.general.objectFromVersion) {
                    this.newerVersionNotification.show();
                }
            }

            this.toolbar = new Ext.Toolbar({
                id: "object_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "pimcore_main_toolbar",
                items: buttons,
                overflowHandler: 'scroller'
            });

            if (!this.data.general.o_published) {
                this.toolbarButtons.unpublish.hide();
            } else if (this.isAllowed("publish")) {
                this.toolbarButtons.save.hide();
            }
        }

        return this.toolbar;
    },

    activate: function () {
        var tabId = "object_" + this.id;
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem(tabId);
    },

    getSaveData: function (only, omitMandatoryCheck) {
        var data = {};

        data.id = this.id;
        data.modificationDate = this.data.general.o_modificationDate;

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
            console.log(e1);
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
            console.log(e3);
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

    close: function() {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.remove(this.tab);
    },

    saveClose: function (only) {
        this.save(null, only, function () {
            this.close();
        }.bind(this))
    },

    publishClose: function () {
        this.publish(null, function () {
            this.close();
        }.bind(this))
    },

    publish: function (only, callback) {
        return this.save("publish", only, callback, function (rdata) {
            if (rdata && rdata.success) {
                //set the object as published only if in the response error doesn't exist
                this.data.general.o_published = true;
                // toggle buttons
                this.toolbarButtons.unpublish.show();
                this.toolbarButtons.save.hide();

                pimcore.elementservice.setElementPublishedState({
                    elementType: "object",
                    id: this.id,
                    published: true
                });
            }
        }.bind(this));
    },

    unpublish: function (only, callback) {
        this.save("unpublish", only, callback, function (rdata) {
            if (rdata && rdata.success) {
                this.data.general.o_published = false;

                // toggle buttons
                this.toolbarButtons.unpublish.hide();
                this.toolbarButtons.save.show();

                pimcore.elementservice.setElementPublishedState({
                    elementType: "object",
                    id: this.id,
                    published: false
                });
            }
        }.bind(this))
    },

    unpublishClose: function () {
        this.unpublish(null, function () {
            this.close();
        }.bind(this));
    },

    saveToSession: function (callback) {
        this.save("session", null, callback);
    },

    save: function (task, only, callback, successCallback) {

        var omitMandatoryCheck = false;

        // unpublish and save version is possible without checking mandatory fields
        if (task == "version" || task == "unpublish") {
            omitMandatoryCheck = true;
        }

        if (this.tab.disabled || this.tab.isMasked()) {
            return;
        }

        this.tab.mask();

        var saveData = this.getSaveData(only, omitMandatoryCheck);

        if (saveData && saveData.data != false && saveData.data != "false") {
            try {
                pimcore.plugin.broker.fireEvent('preSaveObject', this, 'object');
            } catch (e) {
                if (e instanceof pimcore.error.ValidationException) {
                    this.tab.unmask();
                    pimcore.helpers.showPrettyError('object', t("error"), t("saving_failed"), e.message);
                    return false;
                }

                if (e instanceof pimcore.error.ActionCancelledException) {
                    this.tab.unmask();
                    pimcore.helpers.showNotification(t("Info"), 'Object not saved: ' + e.message, 'info');
                    return false;
                }
            }

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_dataobject_save', {task: task}),
                method: "PUT",
                params: saveData,
                success: function (response) {
                    if (task != "session") {
                        try {
                            var rdata = Ext.decode(response.responseText);
                            if (typeof successCallback == 'function') {
                                // the successCallback function retrieves response data information
                                successCallback(rdata);
                            }
                            if (rdata && rdata.success) {
                                // check for version notification
                                if (this.newerVersionNotification) {
                                    if (task == "publish" || task == "unpublish") {
                                        this.newerVersionNotification.hide();
                                    } else {
                                        this.newerVersionNotification.show();
                                    }
                                }

                                pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
                                this.resetChanges();
                                Ext.apply(this.data.general, rdata.general);

                                pimcore.helpers.updateTreeElementStyle('object', this.id, rdata.treeData);
                                pimcore.plugin.broker.fireEvent("postSaveObject", this);

                                // for internal use ID.
                                pimcore.eventDispatcher.fireEvent("postSaveObject", this, task);
                            }
                        } catch (e) {
                            pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
                        }
                        // reload versions
                        if (this.isAllowed("versions")) {
                            if (typeof this.versions.reload == "function") {
                                try {
                                    //TODO remove this as soon as it works
                                    this.versions.reload();
                                } catch (e) {
                                    console.log(e);
                                }
                            }
                        }
                    }

                    this.tab.unmask();

                    if (typeof callback == "function") {
                        callback();
                    }
                }.bind(this),
                failure: function (response) {
                    this.tab.unmask();
                }.bind(this)
            });

            return true;
        } else {
            this.tab.unmask();
        }
        return false;
    },

    remove: function () {
        var options = {
            "elementType": "object",
            "id": this.id
        };
        pimcore.elementservice.deleteElement(options);
    },

    isAllowed: function (key) {
        return this.data.userPermissions[key];
    },

    reload: function (params) {
        params = params || {};
        var uiState = null;

        // Reload layout when explicitly set to false
        if (params['layoutId'] === false) {
            params['layoutId'] = null;
        } else if (params['layoutId'] !== 0 && !params['layoutId']) {
            params['layoutId'] = this.data.currentLayoutId;
        }

        if(this.data.currentLayoutId == params['layoutId'] && !params['ignoreUiState']) {
            uiState = this.getUiState(this.tabbar);
        }

        this.tab.on("close", function () {
            var currentTabIndex = this.tab.ownerCt.items.indexOf(this.tab);
            var options = {
                layoutId: params['layoutId'],
                tabIndex: currentTabIndex,
                uiState: uiState
            };

            window.setTimeout(function (id) {
                pimcore.helpers.openObject(id, "object", options);
            }.bind(window, this.id), 500);
        }.bind(this));

        pimcore.helpers.closeObject(this.id);
    },

    getMetaInfo: function() {
        return {
            id: this.data.general.o_id,
            path: this.data.general.fullpath,
            parentid: this.data.general.o_parentId,
            classid: this.data.general.o_classId,
            "class": this.data.general.o_className,
            modificationdate: this.data.general.o_modificationDate,
            creationdate: this.data.general.o_creationDate,
            usermodification: this.data.general.o_userModification,
            userowner: this.data.general.o_userOwner,
            deeplink: pimcore.helpers.getDeeplink("object", this.data.general.o_id, "object")
        };
    },

    showMetaInfo: function () {
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
                name: "classid",
                value: metainfo.classid
            }, {
                name: "class",
                value: metainfo.class
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
        ], "object");
    },

    rename: function () {
        if (this.isAllowed("rename") && !this.data.general.o_locked) {
            var options = {
                elementType: "object",
                elementSubType: this.data.general.o_type,
                id: this.id,
                default: this.data.general.o_key
            };
            pimcore.elementservice.editElementKey(options);
        }
    },

    getUiState: function (extJsObject) {
        var visible = extJsObject.isVisible();
        if (extJsObject.hasOwnProperty('collapsed')) {
            visible = !extJsObject.collapsed;
        }
        var states = {visible: visible, children: []};

        if (extJsObject.hasOwnProperty('items')) {
            extJsObject.items.each(function (item, index) {
                if(!item.hasOwnProperty('excludeFromUiStateRestore')) {
                    states.children[index] = this.getUiState(item);
                }
            }.bind(this));
        }
        return states;
    },

    setUiState: function (extJsObject, savedState) {
        if (savedState.visible) {
            if (!extJsObject.hasOwnProperty('collapsed')) {
                extJsObject.setVisible(savedState.visible);
            } else {
                // without timeout the accordion panel's state gets confused and thus panels are not toggleable
                setTimeout(function () {
                    extJsObject.expand(false);
                }, 50);
            }
        }
        if (extJsObject.hasOwnProperty('items')) {
            extJsObject.items.each(function (item, index) {
                if(savedState.children[index]) {
                    this.setUiState(item, savedState.children[index]);
                }
            }.bind(this));
        }
    },

    shareViaNotifications: function () {
        if (pimcore.globalmanager.get("user").isAllowed('notifications_send')) {
            var elementData = {
                id:this.id,
                type:'object',
                published:this.data.general.o_published,
                path:this.data.general.fullpath
            };
            if (pimcore.globalmanager.get("new_notifications")) {
                pimcore.globalmanager.get("new_notifications").getWindow().destroy();
            }
            pimcore.globalmanager.add("new_notifications", new pimcore.notification.modal(elementData));        }
    }
});
