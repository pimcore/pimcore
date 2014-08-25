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

pimcore.registerNS("pimcore.document.document");
pimcore.document.document = Class.create(pimcore.element.abstract, {

    urlprefix: "/admin/",

    getData: function () {        
        Ext.Ajax.request({
            url: this.urlprefix + this.getType() + "/get-data-by-id/",
            params: {id: this.id},
            success: this.getDataComplete.bind(this)
        });
    },

    getDataComplete: function (response) {
        try {
            this.data = Ext.decode(response.responseText);

            if (typeof this.data.editlock == "object") {
                pimcore.helpers.lockManager(this.id, "document", this.getType(), this.data);
                throw "document is locked";
            }

            if (this.isAllowed("view")) {
                this.init();
                this.addTab();

                if (this.getAddToHistory()) {
                    pimcore.helpers.recordElement(this.id, "document", this.data.path + this.data.key);
                }

                this.startChangeDetector();
            }
            else {
                pimcore.helpers.closeDocument(this.id);
            }
        }
        catch (e) {
            console.log(e);
            pimcore.helpers.closeDocument(this.id);
        }

    },

    selectInTree: function () {
        try {
            Ext.getCmp("pimcore_panel_tree_documents").expand();
            var tree = pimcore.globalmanager.get("layout_document_tree");
            pimcore.helpers.selectPathInTree(tree.tree, this.data.idPath);
        } catch (e) {
            console.log(e);
        }
    },

    activate: function () {
        var tabId = "document_" + this.id;
        this.tabPanel.activate(tabId);
    },

    save : function (task, only, callback) {

        if(this.tab.disabled) {
            return;
        }

        this.tab.disable();
        var saveData = this.getSaveData(only);

        if (saveData) {
            // check for version notification
            if(this.newerVersionNotification) {
                if(task == "publish" || task == "unpublish") {
                    this.newerVersionNotification.hide();
                } else {
                    this.newerVersionNotification.show();
                }

            }

            pimcore.plugin.broker.fireEvent("preSaveDocument", this, this.getType(), task, only);

            Ext.Ajax.request({
                url: this.urlprefix + this.getType() + '/save/task/' + task,
                method: "post",
                params: saveData,
                success: function (response) {
                    try{
                        var rdata = Ext.decode(response.responseText);
                        if (rdata && rdata.success) {
                            pimcore.helpers.showNotification(t("success"), t("successful_saved_document"), "success");
                            this.resetChanges();

                            if(typeof this["createScreenshot"] == "function") {
                                this.createScreenshot();
                            }
                            pimcore.plugin.broker.fireEvent("postSaveDocument", this, this.getType(), task, only);
                        }
                        else {
                            pimcore.helpers.showNotification(t("error"), t("error_saving_document"), "error",
                                                                                                    t(rdata.message));
                        }
                    } catch (e) {
                        pimcore.helpers.showNotification(t("error"), t("error_saving_document"), "error");
                    }


                    // reload versions
                    if (this.versions) {
                        if (typeof this.versions.reload == "function") {
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
                }
            });
        } else {
            this.tab.enable();
        }
    },
    
    
    isAllowed : function (key) {
        return this.data.userPermissions[key];
    },

    remove: function () {
        pimcore.helpers.deleteDocument(this.id);
    },

    saveClose: function(only){
        this.save();
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.remove(this.tab);
    },

    publishClose: function(){
        this.publish(null, function () {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove(this.tab);
        }.bind(this));
    },

    publish: function (only, callback) {
        this.data.published = true;

        // toogle buttons
        this.toolbarButtons.unpublish.show();

        if(this.toolbarButtons.save) {
            this.toolbarButtons.save.hide();
        }

        // remove class in tree panel
        try {
            pimcore.globalmanager.get("layout_document_tree").tree.getNodeById(this.data.id).getUI()
                                                                                    .removeClass("pimcore_unpublished");
        } catch (e) {
        }


        this.save("publish", only, callback);
    },

    unpublish: function () {
        this.data.published = false;

        // toogle buttons
        this.toolbarButtons.unpublish.hide();

        if(this.toolbarButtons.save) {
            this.toolbarButtons.save.show();
        }

        // set class in tree panel
        try {
            pimcore.globalmanager.get("layout_document_tree").tree.getNodeById(this.data.id).getUI()
                                                                                    .addClass("pimcore_unpublished");
        } catch (e) {
        }

        this.save("unpublish");
    },

    unpublishClose: function () {
        this.unpublish();
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.remove(this.tab);
    },

    reload: function () {
        window.setTimeout(function (id, type) {
            pimcore.helpers.openDocument(id, type);
        }.bind(window, this.id, this.getType()), 500);

        pimcore.helpers.closeDocument(this.id);
    },

    setType: function (type) {
        this.type = type;
    },

    getType: function () {
        return this.type;
    }
});