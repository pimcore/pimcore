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


pimcore.registerNS("pimcore.helpers.x");


pimcore.helpers.openAsset = function (id, type) {

    if (pimcore.globalmanager.exists("asset_" + id) == false) {

        pimcore.helpers.addTreeNodeLoadingIndicator("asset", id);

        if (!pimcore.asset[type]) {
            pimcore.globalmanager.add("asset_" + id, new pimcore.asset.unknown(id));
        }
        else {
            pimcore.globalmanager.add("asset_" + id, new pimcore.asset[type](id));
        }

        pimcore.helpers.rememberOpenTab("asset_" + id + "_" + type);
    }
    else {
        pimcore.globalmanager.get("asset_" + id).activate();
    }
};

pimcore.helpers.closeAsset = function (id) {

    var tabPanel = Ext.getCmp("pimcore_panel_tabs");
    var tabId = "asset_" + id;
    tabPanel.remove(tabId);

    pimcore.helpers.removeTreeNodeLoadingIndicator("asset", id);
    pimcore.globalmanager.remove("asset_" + id);
};

pimcore.helpers.openDocument = function (id, type) {
    if (pimcore.globalmanager.exists("document_" + id) == false) {
        if (pimcore.document[type]) {
            pimcore.helpers.addTreeNodeLoadingIndicator("document", id);
            pimcore.globalmanager.add("document_" + id, new pimcore.document[type](id));
            pimcore.helpers.rememberOpenTab("document_" + id + "_" + type);
        }
    }
    else {
        pimcore.globalmanager.get("document_" + id).activate();
    }
};


pimcore.helpers.closeDocument = function (id) {

    var tabPanel = Ext.getCmp("pimcore_panel_tabs");
    var tabId = "document_" + id;
    tabPanel.remove(tabId);

    pimcore.helpers.removeTreeNodeLoadingIndicator("document", id);
    pimcore.globalmanager.remove("document_" + id);
};

pimcore.helpers.openObject = function (id, type) {
    if (pimcore.globalmanager.exists("object_" + id) == false) {
        pimcore.helpers.addTreeNodeLoadingIndicator("object", id);

        if(type != "folder" && type != "variant" && type != "object") {
            type = "object";
        }

        pimcore.globalmanager.add("object_" + id, new pimcore.object[type](id));
        pimcore.helpers.rememberOpenTab("object_" + id + "_" + type);
    }
    else {
        var tab = pimcore.globalmanager.get("object_" + id);
        tab.activate();
    }
};

pimcore.helpers.closeObject = function (id) {

    var tabPanel = Ext.getCmp("pimcore_panel_tabs");
    var tabId = "object_" + id;
    tabPanel.remove(tabId);

    pimcore.helpers.removeTreeNodeLoadingIndicator("object", id);
    pimcore.globalmanager.remove("object_" + id);
}


pimcore.helpers.openElement = function (id, type, subtype) {
    if(typeof subtype != "undefined") {
        if (type == "document") {
            pimcore.helpers.openDocument(id, subtype);
        }
        else if (type == "asset") {
            pimcore.helpers.openAsset(id, subtype);
        }
        else if (type == "object") {
            pimcore.helpers.openObject(id, subtype);
        }
    } else {
        Ext.Ajax.request({
            url: "/admin/element/get-subtype",
            params: {
                id: id,
                type:  type
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                if(res.success) {
                    pimcore.helpers.openElement(res.id, res.type, res.subtype);
                } else {
                    Ext.MessageBox.alert(t("error"), t("element_not_found"))
                }
            }
        });
    }
};


pimcore.helpers.addTreeNodeLoadingIndicator = function (type, id) {
    // display loading indicator on treenode
    try {
        var tree = pimcore.globalmanager.get("layout_" + type + "_tree");
        var node = tree.tree.getNodeById(id);
        if (node) {

            node.originalIconSrc = Ext.get(node.getUI().getIconEl()).getAttribute("src");
            Ext.get(node.getUI().getIconEl()).dom.setAttribute("src", "/pimcore/static/img/panel-loader.gif");

            /*node.originalIconClass = Ext.get(node.getUI().getIconEl()).getAttribute("class");
             Ext.get(node.getUI().getIconEl()).dom.setAttribute("class", "x-tree-node-icon pimcore_icon_loading");*/

            Ext.get(node.getUI().getIconEl()).repaint();
        }
    }
    catch (e) {
        console.log(e);
    }
}

pimcore.helpers.removeTreeNodeLoadingIndicator = function (type, id) {
    // remove loading indicator on treenode
    try {
        var tree = pimcore.globalmanager.get("layout_" + type + "_tree");
        var node = tree.tree.getNodeById(id);

        if (node.originalIconSrc) {
            Ext.get(node.getUI().getIconEl()).dom.setAttribute("src", node.originalIconSrc);
        }

        /*if (node.originalIconClass) {
         Ext.get(node.getUI().getIconEl()).dom.setAttribute("class", node.originalIconClass);
         }*/

        Ext.get(node.getUI().getIconEl()).repaint();
    }
    catch (e) {
    }
}

pimcore.helpers.openSeemode = function () {
    if (pimcore.globalmanager.exists("pimcore_seemode")) {
        pimcore.globalmanager.get("pimcore_seemode").start();
    }
    else {
        pimcore.globalmanager.add("pimcore_seemode", new pimcore.document.seemode());
    }
}

pimcore.helpers.dndMaskFrames = function () {
    var tabpanel = Ext.getCmp("pimcore_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // check for opened document
        if (activeTab.initialConfig.document) {
            if (typeof activeTab.initialConfig.document.maskFrames == "function") {
                activeTab.initialConfig.document.maskFrames();
            }
        }
        // check for opened object
        if (activeTab.initialConfig.object) {
            if (typeof activeTab.initialConfig.object.maskFrames == "function") {
                activeTab.initialConfig.object.maskFrames();
            }
        }
    }
};

pimcore.helpers.dndUnmaskFrames = function () {
    var tabpanel = Ext.getCmp("pimcore_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // check for opened document
        if (activeTab.initialConfig.document) {
            if (typeof activeTab.initialConfig.document.unmaskFrames == "function") {
                activeTab.initialConfig.document.unmaskFrames();
            }
        }
        // check for opened object
        if (activeTab.initialConfig.object) {
            if (typeof activeTab.initialConfig.object.unmaskFrames == "function") {
                activeTab.initialConfig.object.unmaskFrames();
            }
        }
    }

};

pimcore.helpers.isValidFilename = function (value) {
    var result = value.match(/[a-zA-Z0-9_.\-~]+/);
    if (result == value) {
        // key must be at least one character, an maximum 30 characters
        if (value.length < 1 && value.length > 30) {
            return false;
        }
        return true;
    }
    return false;
};


pimcore.helpers.getValidFilenameCache = {};

pimcore.helpers.getValidFilename = function (value) {

    if(pimcore.helpers.getValidFilenameCache[value]) {
        return pimcore.helpers.getValidFilenameCache[value];
    }

    // we use jQuery for the synchronous xhr request, because ExtJS doesn't provide this
    var response = jQuery.ajax({
        url: "/admin/misc/get-valid-filename",
        data: {
            value: value
        },
        async: false
    });

    var res = Ext.decode(response.responseText);

    pimcore.helpers.getValidFilenameCache[value] = res["filename"];

    return res["filename"];

};

pimcore.helpers.showNotification = function (title, text, type, errorText) {
    // icon types: info,error,success
    if(type == "error"){

        if(errorText != null && errorText != undefined){
            text = text + '<br /><br /><textarea style="width:300px; height:100px; font-size:11px;">' + strip_tags(errorText) + "</textarea>";
        }
        Ext.MessageBox.show({
            title:title,
            msg: text,
            buttons: Ext.Msg.OK ,
            icon: Ext.MessageBox.ERROR
        });
    } else {
        var notification = new Ext.ux.Notification({
            iconCls: 'icon_notification_' + type,
            title: title,
            html: text,
            autoDestroy: true,
            hideDelay:  1000
        });
        notification.show(document);
    }

};


pimcore.helpers.handleCtrlS = function () {

    var tabpanel = Ext.getCmp("pimcore_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // for document
        if (activeTab.initialConfig.document) {
            activeTab.initialConfig.document.publish();
        }
        else if (activeTab.initialConfig.object) {
            activeTab.initialConfig.object.publish();
        }
        else if (activeTab.initialConfig.asset) {
            activeTab.initialConfig.asset.save();
        }
    }
};


pimcore.helpers.handleF5 = function () {

    var tabpanel = Ext.getCmp("pimcore_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // for document
        if (activeTab.initialConfig.document) {
            activeTab.initialConfig.document.reload();
            return;
        }
        else if (activeTab.initialConfig.object) {
            activeTab.initialConfig.object.reload();
            return;
        }
    }

    var date = new Date();
    location.href = "/admin/?_dc=" + date.getTime();

    mapF5.stopEvent = false;
};

pimcore.helpers.lockManager = function (cid, ctype, csubtype, data) {

    var lockDate = new Date(data.editlock.date * 1000);
    var lockDetails = "<br /><br />";
    lockDetails += "<b>" + t("path") + ": <i>" + data.editlock.cpath + "</i></b><br />";
    lockDetails += "<b>" + t("type") + ": </b>" + t(ctype) + "<br />";
    if(data.editlock.user) {
        lockDetails += "<b>" + t("user") + ":</b> " + data.editlock.user.name + "<br />";
    }
    lockDetails += "<b>" + t("since") + ": </b>" + Ext.util.Format.date(lockDate);
    lockDetails += "<br /><br />" + t("element_lock_question");

    Ext.MessageBox.confirm(t("element_is_locked"), t("element_lock_message") + lockDetails, function (lock, buttonValue) {
        if (buttonValue == "yes") {
            Ext.Ajax.request({
                url: "/admin/element/unlock-element",
                params: {
                    id: lock[0],
                    type:  lock[1]
                },
                success: function () {
                    pimcore.helpers.openElement(lock[0], lock[1], lock[2]);
                }
            });
        }
    }.bind(this, arguments));
};


pimcore.helpers.closeAllElements = function () {
    var tabs = Ext.getCmp("pimcore_panel_tabs").items;
    if (tabs.getCount() > 0) {
        if (tabs.getCount() > 1) {
            window.setTimeout(pimcore.helpers.closeAllElements, 200);
        }
        Ext.getCmp("pimcore_panel_tabs").remove(tabs.first());
    }
};


pimcore.helpers.loadingShow = function () {
    pimcore.globalmanager.get("loadingmask").show();
}

pimcore.helpers.loadingHide = function () {
    pimcore.globalmanager.get("loadingmask").hide();
}

pimcore.helpers.itemselector = function (muliselect, callback, restrictions, config) {
    var itemselector = new pimcore.element.selector.selector(muliselect, callback, restrictions, config);
}


pimcore.helpers.activateMaintenance = function () {

    Ext.Ajax.request({
        url: "/admin/misc/maintenance/activate/true"
    });

    if(!Ext.getCmp("pimcore_maintenance_disable_button")) {
        pimcore.helpers.showMaintenanceDisableButton();
    }
}

pimcore.helpers.deactivateMaintenance = function () {

    Ext.Ajax.request({
        url: "/admin/misc/maintenance/deactivate/true"
    });

    var toolbar = pimcore.globalmanager.get("layout_toolbar").toolbar;
    toolbar.remove(Ext.getCmp("pimcore_maintenance_disable_button"));
    toolbar.doLayout();
}

pimcore.helpers.showMaintenanceDisableButton = function () {
    var toolbar = pimcore.globalmanager.get("layout_toolbar").toolbar;

    var deactivateButton = new Ext.Button({
        id: "pimcore_maintenance_disable_button",
        text: "DEACTIVATE MAINTENANCE",
        iconCls: "pimcore_icon_maintenance",
        cls: "pimcore_main_menu",
        handler: pimcore.helpers.deactivateMaintenance
    });

    toolbar.insertButton(5, [deactivateButton]);
    toolbar.doLayout();
}

pimcore.helpers.download = function (url) {
    pimcore.settings.showCloseConfirmation = false;
    window.setTimeout(function () {
        pimcore.settings.showCloseConfirmation = true;
    },1000);

    location.href = url;
}

pimcore.helpers.getFileExtension = function (filename) {
    var extensionP = filename.split("\.");
    return extensionP[extensionP.length - 1];
}


pimcore.helpers.deleteAsset = function (id, callback) {
    // check for dependencies
    Ext.Ajax.request({
        url: "/admin/asset/delete-info/",
        params: {id: id},
        success: pimcore.helpers.deleteAssetCheckDependencyComplete.bind(window, id, callback)
    });
};

pimcore.helpers.deleteAssetCheckDependencyComplete = function (id, callback, response) {

    try {
        var res = Ext.decode(response.responseText);
        var message = t('delete_message');
        if (res.hasDependencies) {
            message = t('delete_message_dependencies');
        }
        Ext.MessageBox.show({
            title:t('delete'),
            msg: message,
            buttons: Ext.Msg.OKCANCEL ,
            icon: Ext.MessageBox.INFO ,
            fn: pimcore.helpers.deleteAssetFromServer.bind(window, id, res, callback)
        });
    }
    catch (e) {
    }
};

pimcore.helpers.deleteAssetFromServer = function (id, r, callback, button) {

    if (button == "ok" && r.deletejobs) {

        var node = pimcore.globalmanager.get("layout_asset_tree").tree.getNodeById(id);
        pimcore.helpers.addTreeNodeLoadingIndicator("asset", id);

        if(node) {
            node.getUI().addClass("pimcore_delete");
        }
        /*this.originalClass = Ext.get(this.getUI().getIconEl()).getAttribute("class");
         Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", "x-tree-node-icon pimcore_icon_loading");*/


        if (pimcore.globalmanager.exists("asset_" + id)) {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove("asset_" + id);
        }

        if(r.deletejobs.length > 2) {
            this.deleteProgressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            this.deleteWindow = new Ext.Window({
                title: t("delete"),
                layout:'fit',
                width:500,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                modal: true,
                items: [this.deleteProgressBar]
            });

            this.deleteWindow.show();
        }


        var pj = new pimcore.tool.paralleljobs({
            success: function (id, callback) {

                var node = pimcore.globalmanager.get("layout_asset_tree").tree.getNodeById(id);
                try {
                    if(node) {
                        node.getUI().removeClass("pimcore_delete");
                    }
                    //Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", this.originalClass);
                    pimcore.helpers.removeTreeNodeLoadingIndicator("asset", id);

                    if(node) {
                        node.remove();
                    }
                } catch(e) {
                    console.log(e);
                    pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_during_deleting"), "error");
                    if(node) {
                        node.parentNode.reload();
                    }
                }

                if(this.deleteWindow) {
                    this.deleteWindow.close();
                }

                this.deleteProgressBar = null;
                this.deleteWindow = null;

                if(typeof callback == "function") {
                    callback();
                }
            }.bind(this, id, callback),
            update: function (currentStep, steps, percent) {
                if(this.deleteProgressBar) {
                    var status = currentStep / steps;
                    this.deleteProgressBar.updateProgress(status, percent + "%");
                }
            }.bind(this),
            failure: function (id, message) {
                this.deleteWindow.close();

                pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_during_deleting"), "error", t(message));

                var node = pimcore.globalmanager.get("layout_asset_tree").tree.getNodeById(id);
                if(node) {
                    node.parentNode.reload();
                }
            }.bind(this, id),
            jobs: r.deletejobs
        });
    }
};



pimcore.helpers.deleteDocument = function (id, callback) {

    // check for dependencies
    Ext.Ajax.request({
        url: "/admin/document/delete-info/",
        params: {id: id},
        success: pimcore.helpers.deleteDocumentCheckDependencyComplete.bind(window, id, callback)
    });
};

pimcore.helpers.deleteDocumentCheckDependencyComplete = function (id, callback, response) {

    try {
        var res = Ext.decode(response.responseText);
        var message = t('delete_message');
        if (res.hasDependencies) {
            message = t('delete_message_dependencies');
        }
        Ext.MessageBox.show({
            title:t('delete'),
            msg: message,
            buttons: Ext.Msg.OKCANCEL ,
            icon: Ext.MessageBox.INFO ,
            fn: pimcore.helpers.deleteDocumentFromServer.bind(window, id, res, callback)
        });
    }
    catch (e) {
        console.log(e);
    }
};

pimcore.helpers.deleteDocumentFromServer = function (id, r, callback, button) {

    if (button == "ok" && r.deletejobs) {
        var node = pimcore.globalmanager.get("layout_document_tree").tree.getNodeById(id);
        pimcore.helpers.addTreeNodeLoadingIndicator("document", id);

        if(node) {
            node.getUI().addClass("pimcore_delete");
        }
        /*this.originalClass = Ext.get(this.getUI().getIconEl()).getAttribute("class");
         Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", "x-tree-node-icon pimcore_icon_loading");*/


        if (pimcore.globalmanager.exists("document_" + id)) {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove("document_" + id);
        }

        if(r.deletejobs.length > 2) {
            this.deleteProgressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            this.deleteWindow = new Ext.Window({
                title: t("delete"),
                layout:'fit',
                width:500,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                modal: true,
                items: [this.deleteProgressBar]
            });

            this.deleteWindow.show();
        }


        var pj = new pimcore.tool.paralleljobs({
            success: function (id, callback) {

                var node = pimcore.globalmanager.get("layout_document_tree").tree.getNodeById(id);
                try {
                    if(node) {
                        node.getUI().removeClass("pimcore_delete");
                    }
                    //Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", this.originalClass);
                    pimcore.helpers.removeTreeNodeLoadingIndicator("document", id);

                    if(node) {
                        node.remove();
                    }
                } catch(e) {
                    console.log(e);
                    pimcore.helpers.showNotification(t("error"), t("error_deleting_document"), "error");

                    if(node) {
                        node.parentNode.reload();
                    }
                }

                if(this.deleteWindow) {
                    this.deleteWindow.close();
                }

                this.deleteProgressBar = null;
                this.deleteWindow = null;

                if(typeof callback == "function") {
                    callback();
                }
            }.bind(this, id, callback),
            update: function (currentStep, steps, percent) {
                if(this.deleteProgressBar) {
                    var status = currentStep / steps;
                    this.deleteProgressBar.updateProgress(status, percent + "%");
                }
            }.bind(this),
            failure: function (message) {
                this.deleteWindow.close();

                pimcore.helpers.showNotification(t("error"), t("error_deleting_document"), "error", t(message));

                var node = pimcore.globalmanager.get("layout_document_tree").tree.getNodeById(id);
                if(node) {
                    node.parentNode.reload();
                }
            }.bind(this, id),
            jobs: r.deletejobs
        });
    }
};


pimcore.helpers.deleteObject = function (id, callback) {

    // check for dependencies
    Ext.Ajax.request({
        url: "/admin/object/delete-info/",
        params: {id: id},
        success: pimcore.helpers.deleteObjectCheckDependencyComplete.bind(window, id, callback)
    });
};

pimcore.helpers.deleteObjectCheckDependencyComplete = function (id, callback, response) {

    try {
        var res = Ext.decode(response.responseText);
        var message = t('delete_message');
        if (res.hasDependencies) {
            var message = t('delete_message_dependencies');
        }
        Ext.MessageBox.show({
            title:t('delete'),
            msg: message,
            buttons: Ext.Msg.OKCANCEL ,
            icon: Ext.MessageBox.INFO ,
            fn: pimcore.helpers.deleteObjectFromServer.bind(window, id, res, callback)
        });
    }
    catch (e) {
    }
};

pimcore.helpers.deleteObjectFromServer = function (id, r, callback, button) {

    if (button == "ok" && r.deletejobs) {

        var node = pimcore.globalmanager.get("layout_object_tree").tree.getNodeById(id);
        pimcore.helpers.addTreeNodeLoadingIndicator("object", id);

        if(node) {
            node.getUI().addClass("pimcore_delete");
        }
        /*this.originalClass = Ext.get(this.getUI().getIconEl()).getAttribute("class");
         Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", "x-tree-node-icon pimcore_icon_loading");*/


        if (pimcore.globalmanager.exists("object_" + id)) {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove("object_" + id);
        }

        if(r.deletejobs.length > 2) {
            this.deleteProgressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            this.deleteWindow = new Ext.Window({
                title: t("delete"),
                layout:'fit',
                width:500,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                modal: true,
                items: [this.deleteProgressBar]
            });

            this.deleteWindow.show();
        }


        var pj = new pimcore.tool.paralleljobs({
            success: function (id, callback) {

                var node = pimcore.globalmanager.get("layout_object_tree").tree.getNodeById(id);
                try {
                    if(node) {
                        node.getUI().removeClass("pimcore_delete");
                    }
                    //Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", this.originalClass);
                    pimcore.helpers.removeTreeNodeLoadingIndicator("object", id);

                    if(node) {
                        node.remove();
                    }
                } catch(e) {
                    console.log(e);
                    pimcore.helpers.showNotification(t("error"), t("error_deleting_object"), "error");
                    if(node) {
                        node.parentNode.reload();
                    }
                }

                if(this.deleteWindow) {
                    this.deleteWindow.close();
                }

                this.deleteProgressBar = null;
                this.deleteWindow = null;

                if(typeof callback == "function") {
                    callback();
                }
            }.bind(this, id, callback),
            update: function (currentStep, steps, percent) {
                if(this.deleteProgressBar) {
                    var status = currentStep / steps;
                    this.deleteProgressBar.updateProgress(status, percent + "%");
                }
            }.bind(this),
            failure: function (id, message) {
                this.deleteWindow.close();

                pimcore.helpers.showNotification(t("error"), t("error_deleting_object"), "error", t(message));

                var node = pimcore.globalmanager.get("layout_object_tree").tree.getNodeById(id);
                if(node) {
                    node.parentNode.reload();
                }
            }.bind(this, id),
            jobs: r.deletejobs
        });
    }
};

pimcore.helpers.getOpenTab = function () {
    var openTabs = localStorage.getItem("pimcore_opentabs");
    if(!openTabs) {
        openTabs = [];
    } else {
        openTabs = JSON.parse(openTabs); // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
    }

    return openTabs;
}

pimcore.helpers.rememberOpenTab = function (item) {
    var openTabs = pimcore.helpers.getOpenTab();

    if(!in_array(item, openTabs)) {
        openTabs.push(item);
    }

    // limit to the latest 10
    openTabs.reverse();
    openTabs.splice(10, 1000);
    openTabs.reverse();

    localStorage.setItem("pimcore_opentabs", JSON.stringify(openTabs)); // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
}

pimcore.helpers.forgetOpenTab = function (item) {

    var openTabs = pimcore.helpers.getOpenTab();

    var pos = array_search(item, openTabs);
    openTabs.splice(pos, 1);

    localStorage.setItem("pimcore_opentabs", JSON.stringify(openTabs)); // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
}

pimcore.helpers.openMemorizedTabs = function () {
    var openTabs = pimcore.helpers.getOpenTab();
    var openedTabs = [];

    for(var i=0; i<openTabs.length; i++) {
        if(!empty(openTabs[i])) {
            if(!in_array(openTabs[i], openedTabs)) {
                parts = openTabs[i].split("_");
                window.setTimeout(function (parts) {
                    if(parts[1] && parts[2]) {
                        if(parts[0] == "asset") {
                            pimcore.helpers.openAsset(parts[1], parts[2]);
                        } else if(parts[0] == "document") {
                            pimcore.helpers.openDocument(parts[1], parts[2]);
                        } else if(parts[0] == "object") {
                            pimcore.helpers.openObject(parts[1], parts[2]);
                        }
                    }
                }.bind(this, parts), 200);
            }
            openedTabs.push(openTabs[i]);
        }
    }
}

pimcore.helpers.assetSingleUploadDialog = function (parent, parentType, success, failure) {

    if(typeof success != "function") {
        var success = function () {};
    }

    if(typeof failure != "function") {
        var failure = function () {};
    }

    var url = '/admin/asset/add-asset-compatibility/?parent' + ucfirst(parentType) + '=' + parent;

    var uploadWindowCompatible = new Ext.Window({
        autoHeight: true,
        title: t('add_assets'),
        closeAction: 'close',
        width:400,
        modal: true
    });

    var uploadForm = new Ext.form.FormPanel({
        layout: "pimcoreform",
        fileUpload: true,
        width: 400,
        bodyStyle: 'padding: 10px;',
        items: [{
            xtype: 'fileuploadfield',
            emptyText: t("select_a_file"),
            fieldLabel: t("asset"),
            width: 230,
            name: 'Filedata',
            buttonText: "",
            buttonCfg: {
                iconCls: 'pimcore_icon_upload_single'
            },
            listeners: {
                fileselected: function () {
                    uploadForm.getForm().submit({
                        url: url,
                        waitMsg: t("please_wait"),
                        success: function (el, res) {
                            success(res);
                            uploadWindowCompatible.close();
                        },
                        failure: function (el, res) {
                            failure(res);
                            uploadWindowCompatible.close();
                        }
                    });
                }
            }
        }]
    });

    uploadWindowCompatible.add(uploadForm);
    uploadWindowCompatible.show();
    uploadWindowCompatible.setWidth(401);
    uploadWindowCompatible.doLayout();
};

pimcore.helpers.uploadDialog = function (url, filename, success, failure) {

    if(typeof success != "function") {
        var success = function () {};
    }

    if(typeof failure != "function") {
        var failure = function () {};
    }

    if(typeof filename != "function") {
        var filename = "file";
    }

    var uploadWindowCompatible = new Ext.Window({
        autoHeight: true,
        title: t('upload'),
        closeAction: 'close',
        width:400,
        modal: true
    });

    var uploadForm = new Ext.form.FormPanel({
        layout: "pimcoreform",
        fileUpload: true,
        width: 400,
        bodyStyle: 'padding: 10px;',
        items: [{
            xtype: 'fileuploadfield',
            emptyText: t("select_a_file"),
            fieldLabel: t("file"),
            width: 230,
            name: 'Filedata',
            buttonText: "",
            buttonCfg: {
                iconCls: 'pimcore_icon_upload_single'
            },
            listeners: {
                fileselected: function () {
                    uploadForm.getForm().submit({
                        url: url,
                        waitMsg: t("please_wait"),
                        success: function (el, res) {
                            // content-type in response has to be text/html, otherwise (when application/json is sent) chrome will complain in
                            // Ext.form.Action.Submit and mark the submission as failed
                            success(res);
                            uploadWindowCompatible.close();
                        },
                        failure: function (el, res) {
                            failure(res);
                            uploadWindowCompatible.close();
                        }
                    });
                }
            }
        }]
    });

    uploadWindowCompatible.add(uploadForm);
    uploadWindowCompatible.show();
    uploadWindowCompatible.setWidth(401);
    uploadWindowCompatible.doLayout();
};

pimcore.helpers.selectPathInTreeActiveSelections = {};
pimcore.helpers.selectPathInTree = function (tree, path, callback) {
    try {

        var hash = tree.getId() + "~" + path;
        if(typeof pimcore.helpers.selectPathInTreeActiveSelections[hash] != "undefined") {
            if(typeof callback == "function") {
                callback(false);
            }
            return false;
        }
        pimcore.helpers.selectPathInTreeActiveSelections[hash] = hash;

        var initialData = {
            tree: tree,
            path: path,
            callback: callback
        };

        tree.selectPath(path, null, function (success, node) {
            if(!success) {
                Ext.MessageBox.alert(t("error"), t("not_possible_with_paging"));
            } else {
                if(typeof initialData["callback"] == "function") {
                    initialData["callback"]();
                }
            }

            delete pimcore.helpers.selectPathInTreeActiveSelections[hash];
        });

    } catch (e) {
        delete pimcore.helpers.selectPathInTreeActiveSelections[hash];
        console.log(e);
    }
}

pimcore.helpers.getClassForIcon = function (icon) {

    var styleContainerId = "pimcore_dynamic_class_for_icon";
    var styleContainer = Ext.get(styleContainerId);
    if(!styleContainer) {
        styleContainer = Ext.getBody().insertHtml("beforeEnd", '<style type="text/css" id="' + styleContainerId + '"></style>', true);
    }

    var content = styleContainer.dom.innerHTML;
    var classname = "pimcore_dynamic_class_for_icon_" + uniqid();
    content += ("." + classname + " { background: url(" + icon + ") left center no-repeat !important; }\n");
    styleContainer.dom.innerHTML = content;

    return classname;
}


pimcore.helpers.openElementByIdDialog = function (type) {
    Ext.MessageBox.prompt(t('open_' + type + '_by_id'), t('please_enter_the_id_of_the_' + type), function (button, value, object) {
        if(button == "ok") {
            pimcore.helpers.openElement(value, type);
        }
    });
}

pimcore.helpers.openDocumentByPathDialog = function () {
    Ext.MessageBox.prompt(t("open_document_by_url"), t("path_or_url_incl_http"), function (button, value, object) {
        if (button == "ok") {
            Ext.Ajax.request({
                url: "/admin/document/open-by-url/",
                method: "get",
                params: {
                    url: value
                },
                success: function (response) {
                    var res = Ext.decode(response.responseText);
                    if(res.success) {
                        pimcore.helpers.openDocument(res.id, res.type);
                    } else {
                        Ext.MessageBox.alert(t("error"), t("no_matching_document_found_for") + ": " + value);
                    }
                }.bind(this)
            });
        }
    });
}