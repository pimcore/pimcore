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

/*global localStorage */
pimcore.registerNS("pimcore.helpers.x");


pimcore.helpers.registerKeyBindings = function (bindEl, ExtJS) {

    if(!ExtJS) {
        ExtJS = Ext;
    }

    // handler for STRG+S (Save&Publish)
    var mapCtrlS = new ExtJS.KeyMap(bindEl, {
        key:"s",
        fn: top.pimcore.helpers.handleCtrlS,
        ctrl:true,
        alt:false,
        shift:false,
        stopEvent:true
    });

    // handler for F5
    var mapF5 = new ExtJS.KeyMap(bindEl, {
        key:[116],
        fn: top.pimcore.helpers.handleF5,
        stopEvent:true
    });

    var openAssetById = new ExtJS.KeyMap(bindEl, {
        key:"a",
        fn: top.pimcore.helpers.openElementByIdDialog.bind(this, "asset"),
        ctrl:true,
        alt:false,
        shift:true,
        stopEvent:true
    });

    var openObjectById = new ExtJS.KeyMap(bindEl, {
        key:"o",
        fn: top.pimcore.helpers.openElementByIdDialog.bind(this, "object"),
        ctrl:true,
        alt:false,
        shift:true,
        stopEvent:true
    });

    var openDocumentById = new ExtJS.KeyMap(bindEl, {
        key:"d",
        fn: top.pimcore.helpers.openElementByIdDialog.bind(this, "document"),
        ctrl:true,
        alt:false,
        shift:true,
        stopEvent:true
    });

    var openDocumentByPath = new ExtJS.KeyMap(bindEl, {
        key:"f",
        fn: top.pimcore.helpers.openElementByIdDialog.bind(this, "document"),
        ctrl:true,
        alt:false,
        shift:true,
        stopEvent:true
    });

    var openWelcomePage = new ExtJS.KeyMap(bindEl, {
        key:"w",
        fn: top.pimcore.helpers.openWelcomePage.bind(this),
        ctrl:true,
        alt:false,
        shift:true,
        stopEvent:true
    });


};

pimcore.helpers.openWelcomePage = function() {
    try {
        pimcore.globalmanager.get("layout_portal_welcome").activate();
    }
    catch (e) {
        pimcore.globalmanager.add("layout_portal_welcome", new pimcore.layout.portal());
    }
}

pimcore.helpers.openAsset = function (id, type, options) {

    if (pimcore.globalmanager.exists("asset_" + id) == false) {

        if (!pimcore.asset[type]) {
            pimcore.globalmanager.add("asset_" + id, new pimcore.asset.unknown(id));
        }
        else {
            pimcore.globalmanager.add("asset_" + id, new pimcore.asset[type](id));
        }

        pimcore.helpers.rememberOpenTab("asset_" + id + "_" + type);

        if (options != undefined) {
            if (options.ignoreForHistory) {
                var element = pimcore.globalmanager.get("asset_" + id);
                element.setAddToHistory(false);
            }
        }

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

pimcore.helpers.openDocument = function (id, type, options) {
    if (pimcore.globalmanager.exists("document_" + id) == false) {
        if (pimcore.document[type]) {
            pimcore.globalmanager.add("document_" + id, new pimcore.document[type](id));
            pimcore.helpers.rememberOpenTab("document_" + id + "_" + type);

            if (options !== undefined) {
                if (options.ignoreForHistory) {
                    var element = pimcore.globalmanager.get("document_" + id);
                    element.setAddToHistory(false);
                }
            }
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

pimcore.helpers.openObject = function (id, type, options) {
    if (pimcore.globalmanager.exists("object_" + id) == false) {

        if(type != "folder" && type != "variant" && type != "object") {
            type = "object";
        }

        pimcore.globalmanager.add("object_" + id, new pimcore.object[type](id, options));
        pimcore.helpers.rememberOpenTab("object_" + id + "_" + type);

        if (options !== undefined) {
            if (options.ignoreForHistory) {
                var element = pimcore.globalmanager.get("object_" + id);
                element.setAddToHistory(false);
            }
        }
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
};

pimcore.helpers.getHistory = function() {
    var history = localStorage.getItem("pimcore_element_history");
    if (!history) {
        history = [];
    } else {
        history = JSON.parse(history);
    }
    return history;
}

pimcore.helpers.recordElement = function(id, type, name) {

    var history = pimcore.helpers.getHistory();

    var newDate = new Date();

    for(var i = history.length-1; i >= 0; i--){
        var item = history[i];
        if (item.type == type && item.id == id) {
            history.splice(i, 1);
        }
    }


    var historyItem = {
        id: id,
        type: type,
        name: name,
        time: newDate.getTime()
    };
    history.unshift(historyItem);

    history = history.slice(0, 30);

    var json = JSON.stringify(history);
    localStorage.setItem("pimcore_element_history", json);

    try {
        var historyPanel = pimcore.globalmanager.get("element_history");
        if(historyPanel) {
            var thePair = {"id" : id,
                "type": type,
                "name": name,
                "time": newDate };

            var storeCount = historyPanel.store.getCount();
            for(var i = storeCount - 1; i >= 0; i--) {

                var record = historyPanel.store.getAt(i);
                var data = record.data;
                if (i > 100 || (data.id == id && data.type == type)) {
                    historyPanel.store.remove(record);
                }
            }

            historyPanel.store.insert(0, new historyPanel.store.recordType(thePair));
            historyPanel.resultpanel.getView().refresh();
        }
    }
    catch (e) {
        console.log(e);
    }

};

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
                    Ext.MessageBox.alert(t("error"), t("element_not_found"));
                }
            }
        });
    }
};

pimcore.helpers.getElementTypeByObject = function (object) {
    var type = null;
    if(object instanceof pimcore.document.document) {
        type = "document";
    } else if (object instanceof  pimcore.asset.asset) {
        type = "asset";
    } else if (object instanceof pimcore.object.abstract) {
        type = "object";
    }
    return type;
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
};

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
};

pimcore.helpers.openSeemode = function () {
    if (pimcore.globalmanager.exists("pimcore_seemode")) {
        pimcore.globalmanager.get("pimcore_seemode").start();
    }
    else {
        pimcore.globalmanager.add("pimcore_seemode", new pimcore.document.seemode());
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

pimcore.helpers.showNotification = function (title, text, type, errorText, hideDelay) {
    // icon types: info,error,success
    if(type == "error"){

        if(errorText != null && errorText != undefined){
            text = text + '<br /><hr /><br />' +
                '<pre style="font-size:11px;word-wrap: break-word;">'
                + strip_tags(errorText) +
                "</pre>";
        }

        var errWin = new Ext.Window({
            modal: true,
            iconCls: "icon_notification_error",
            title: title,
            width: 700,
            height: 500,
            html: text,
            autoScroll: true,
            bodyStyle: "padding: 10px; background:#fff;",
            buttonAlign: "center",
            shadow: false,
            closable: false,
            buttons: [{
                text: "OK",
                handler: function () {
                    errWin.close();
                }
            }]
        });
        errWin.show();
    } else {
        var notification = new Ext.ux.Notification({
            iconCls: 'icon_notification_' + type,
            title: title,
            html: text,
            autoDestroy: true,
            hideDelay:  hideDelay | 1000
        });
        notification.show(document);
    }

};


pimcore.helpers.handleCtrlS = function () {

    var tabpanel = Ext.getCmp("pimcore_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // for document
        var el = activeTab.initialConfig;
        if (el.document) {
            if(el.document.data.published) {
                el.document.publish();
            } else {
                el.document.unpublish();
            }
        }
        else if (el.object) {
            if(el.object.data.general.o_published) {
                el.object.publish();
            } else {
                el.object.unpublish();
            }
        }
        else if (el.asset) {
            el.asset.save();
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
    lockDetails += "<b>" + t("since") + ": </b>" + Ext.util.Format.date(lockDate, "Y-m-d H:i");
    lockDetails += "<br /><br />" + t("element_lock_question");

    Ext.MessageBox.confirm(t("element_is_locked"), t("element_lock_message") + lockDetails,
        function (lock, buttonValue) {
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


pimcore.helpers.closeAllUnmodified = function () {
    var unmodifiedElements = [];

    var tabs = Ext.getCmp("pimcore_panel_tabs").items;
    if (tabs.getCount() > 0) {
        tabs.each(function (item, index, length) {
            if(item.title.indexOf("*") > -1) {
                unmodifiedElements.push(item);
            }
        });
    };

    pimcore.helpers.closeAllElements(unmodifiedElements);
}

pimcore.helpers.closeAllElements = function (except, tabPanel) {

    var exceptions = [];
    if(except instanceof Ext.Panel) {
        exceptions.push(except);
    } else if (except instanceof Array) {
        exceptions = except;
    }

    if(typeof tabPanel == "undefined") {
        tabPanel = Ext.getCmp("pimcore_panel_tabs");
    }

    var tabs = tabPanel.items;
    if (tabs.getCount() > 0) {
        tabs.each(function (item, index, length) {
            window.setTimeout(function () {
                if(!in_array(item, exceptions)) {
                    tabPanel.remove(item);
                }
            }, 100*index);
        });
    }
};


pimcore.helpers.loadingShow = function () {
    pimcore.globalmanager.get("loadingmask").show();
};

pimcore.helpers.loadingHide = function () {
    pimcore.globalmanager.get("loadingmask").hide();
};

pimcore.helpers.itemselector = function (muliselect, callback, restrictions, config) {
    var itemselector = new pimcore.element.selector.selector(muliselect, callback, restrictions, config);
};


pimcore.helpers.activateMaintenance = function () {

    Ext.Ajax.request({
        url: "/admin/misc/maintenance/activate/true"
    });

    var button = Ext.get("pimcore_menu_maintenance");
    if(!button.isDisplayed()) {
        pimcore.helpers.showMaintenanceDisableButton();
    }
};

pimcore.helpers.deactivateMaintenance = function () {

    Ext.Ajax.request({
        url: "/admin/misc/maintenance/deactivate/true"
    });

    var button = Ext.get("pimcore_menu_maintenance");
    button.setStyle("display", "none");
};

pimcore.helpers.showMaintenanceDisableButton = function () {
    var button = Ext.get("pimcore_menu_maintenance");
    button.show();
    button.removeAllListeners();
    button.on("click", pimcore.helpers.deactivateMaintenance);
};

pimcore.helpers.download = function (url) {
    pimcore.settings.showCloseConfirmation = false;
    window.setTimeout(function () {
        pimcore.settings.showCloseConfirmation = true;
    },1000);

    location.href = url;
};

pimcore.helpers.getFileExtension = function (filename) {
    var extensionP = filename.split("\.");
    return extensionP[extensionP.length - 1];
};


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
        var message = res.batchDelete ? t('delete_message_batch') : t('delete_message');

        if (res.hasDependencies) {
            message += "<br />" + t('delete_message_dependencies');
        }

        if(res["childs"] > 100) {
            message += "<br /><br /><b>" + t("too_many_children_for_recyclebin") + "</b>";
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

                pimcore.helpers.showNotification(t("error"), t("there_was_a_problem_during_deleting"),
                    "error", t(message));

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
            message += "<br />" + t('delete_message_dependencies');
        }

        if(res["childs"] > 100) {
            message += "<br /><br /><b>" + t("too_many_children_for_recyclebin") + "</b>";
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
        var message = res.batchDelete ? t('delete_message_batch') : t('delete_message');
        if (res.hasDependencies) {
            message += "<br />" + t('delete_message_dependencies');
        }

        if(res["childs"] > 100) {
            message += "<br /><br /><b>" + t("too_many_children_for_recyclebin") + "</b>";
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
        // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
        openTabs = JSON.parse(openTabs);
    }

    return openTabs;
};

pimcore.helpers.clearOpenTab = function () {
    localStorage.setItem("pimcore_opentabs", JSON.stringify([]));
};

pimcore.helpers.rememberOpenTab = function (item, forceOpenTab) {
    var openTabs = pimcore.helpers.getOpenTab();

    if(!in_array(item, openTabs)) {
        openTabs.push(item);
    }

    // limit to the latest 10
    openTabs.reverse();
    openTabs.splice(10, 1000);
    openTabs.reverse();

    // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
    localStorage.setItem("pimcore_opentabs", JSON.stringify(openTabs));
    if (forceOpenTab) {
        localStorage.setItem("pimcore_opentabs_forceopenonce", true);
    }
};

pimcore.helpers.forgetOpenTab = function (item) {

    var openTabs = pimcore.helpers.getOpenTab();

    if(in_array(item, openTabs)) {
        var pos = array_search(item, openTabs);
        openTabs.splice(pos, 1);
    }

    // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
    localStorage.setItem("pimcore_opentabs", JSON.stringify(openTabs));
};

pimcore.helpers.forceOpenMemorizedTabsOnce = function() {
    if (localStorage.getItem("pimcore_opentabs_forceopenonce")) {
        localStorage.removeItem("pimcore_opentabs_forceopenonce");
        return true;
    }
    return false;
}

pimcore.helpers.openMemorizedTabs = function () {
    var openTabs = pimcore.helpers.getOpenTab();
    var openedTabs = [];

    for(var i=0; i<openTabs.length; i++) {
        if(!empty(openTabs[i])) {
            if(!in_array(openTabs[i], openedTabs)) {
                var parts = openTabs[i].split("_");
                window.setTimeout(function (parts) {
                    if(parts[1] && parts[2]) {
                        if(parts[0] == "asset") {
                            pimcore.helpers.openAsset(parts[1], parts[2], { ignoreForHistory: true});
                        } else if(parts[0] == "document") {
                            pimcore.helpers.openDocument(parts[1], parts[2], { ignoreForHistory: true});
                        } else if(parts[0] == "object") {
                            pimcore.helpers.openObject(parts[1], parts[2], { ignoreForHistory: true});
                        }
                    }
                }.bind(this, parts), 200);
            }
            openedTabs.push(openTabs[i]);
        }
    }
};

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
        success = function () {};
    }

    if(typeof failure != "function") {
        failure = function () {};
    }

    if(typeof filename != "string") {
        filename = "Filedata";
    }

    if(empty(filename)) {
        filename = "Filedata";
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
            name: filename,
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
                            // content-type in response has to be text/html, otherwise (when application/json is sent)
                            // chrome will complain in Ext.form.Action.Submit and mark the submission as failed
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
};

pimcore.helpers.selectElementInTree = function (type, id) {
    try {
        Ext.Ajax.request({
            url: "/admin/element/get-id-path/",
            params: {
                id: id,
                type: type
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                if(res.success) {
                    Ext.getCmp("pimcore_panel_tree_" + type + "s").expand();
                    var tree = pimcore.globalmanager.get("layout_" + type + "_tree");
                    pimcore.helpers.selectPathInTree(tree.tree, res.idPath);
                }
            }
        });
    } catch (e) {
        console.log(e);
    }
};

pimcore.helpers.getClassForIcon = function (icon) {

    var styleContainerId = "pimcore_dynamic_class_for_icon";
    var styleContainer = Ext.get(styleContainerId);
    if(!styleContainer) {
        styleContainer = Ext.getBody().insertHtml("beforeEnd", '<style type="text/css" id="' + styleContainerId
            + '"></style>', true);
    }

    var content = styleContainer.dom.innerHTML;
    var classname = "pimcore_dynamic_class_for_icon_" + uniqid();
    content += ("." + classname + " { background: url(" + icon + ") left center no-repeat !important; }\n");
    styleContainer.dom.innerHTML = content;

    return classname;
};


pimcore.helpers.openElementByIdDialog = function (type) {
    Ext.MessageBox.prompt(t('open_' + type + '_by_id'), t('please_enter_the_id_of_the_' + type),
        function (button, value, object) {
            if(button == "ok" && !Ext.isEmpty(value)) {
                pimcore.helpers.openElement(value, type);
            }
        });
};

pimcore.helpers.openDocumentByPath = function (path) {
    pimcore.helpers.openElement(path, "document");
};

pimcore.helpers.openDocumentByPathDialog = function () {
    Ext.MessageBox.prompt(t("open_document_by_url"), t("path_or_url_incl_http"), function (button, value, object) {
        if (button == "ok") {
            pimcore.helpers.openDocumentByPath(value);
        }
    });
};

pimcore.helpers.generatePagePreview = function (id, path, callback) {

    var cb = callback;

    if(pimcore.settings.htmltoimage) {
        Ext.Ajax.request({
            url: '/admin/page/generate-screenshot',
            params: {
                id: id
            },
            success: function () {
                if(typeof cb == "function") {
                    cb();
                }
            }
        });
    }
};

pimcore.helpers.treeNodeThumbnailTimeout = null;
pimcore.helpers.treeNodeThumbnailLastClose = 0;

pimcore.helpers.treeNodeThumbnailPreview = function (tree, parent, node, index) {
    if(typeof node.attributes["thumbnail"] != "undefined" ||
        typeof node.attributes["thumbnails"] != "undefined") {
        window.setTimeout(function (node) {
            var el = Ext.get(Ext.get(node.getUI().getEl()).query(".x-tree-node-el")[0]);
            el.on("mouseenter", function (node) {

                // only display thumbnails when dnd is not active
                if(Ext.dd.DragDropMgr.dragCurrent) {
                    return;
                }

                var imageHtml = "";
                var uriPrefix = window.location.protocol + "//" + window.location.host;

                var thumbnails = node.attributes.thumbnails;
                if(thumbnails && thumbnails.length) {
                    imageHtml += '<div class="thumbnails">';
                    for(var i=0; i<thumbnails.length; i++) {
                        imageHtml += '<div class="thumb small"><img src="' + uriPrefix + thumbnails[i]
                            + '" onload="this.parentNode.className += \' complete\';" /></div>';
                    }
                    imageHtml += '</div>';
                }

                var thumbnail = node.attributes.thumbnail;
                if(thumbnail) {
                    imageHtml = '<div class="thumb big"><img src="' + uriPrefix + thumbnail
                                    + '" onload="this.parentNode.className += \' complete\';" /></div>';
                }

                if(imageHtml) {
                    var treeEl = Ext.get("pimcore_panel_tree_" + this.position);
                    var position = treeEl.getOffsetsTo(Ext.getBody());
                    position = position[0];

                    if(this.position == "right") {
                        position = position - 420;
                    } else {
                        position = treeEl.getWidth() + position;
                    }

                    var container = Ext.get("pimcore_tree_preview");
                    if(!container) {
                        container  = Ext.getBody().insertHtml("beforeEnd", '<div id="pimcore_tree_preview"></div>');
                        container = Ext.get(container);
                        container.addClass("hidden");
                    }

                    // check for an existing iframe
                    var existingIframe = container.query("iframe")[0];
                    if(existingIframe) {
                        // stop loading the existing iframe (images, etc.)
                        var existingIframeWin = existingIframe.contentWindow;
                        if(typeof existingIframeWin["stop"] == "function") {
                            existingIframeWin.stop();
                        } else if (typeof existingIframeWin.document["execCommand"] == "function") {
                            existingIframeWin.document.execCommand('Stop');
                        }
                    }

                    var styles = "left: " + position + "px";

                    // we need to create an iframe so that we can use window.stop();
                    var iframe = document.createElement("iframe");
                    iframe.setAttribute("frameborder", "0");
                    iframe.setAttribute("scrolling", "no");
                    iframe.setAttribute("marginheight", "0");
                    iframe.setAttribute("marginwidth", "0");
                    iframe.setAttribute("style", "width: 100%; height: 2500px;");

                    imageHtml =
                        '<style type="text/css">' +
                            'body { margin:0; padding: 0; } ' +
                            '.thumbnails { width: 410px; } ' +
                            '.thumb { border: 1px solid #999; border-radius: 5px; background: url(' + uriPrefix + '/pimcore/static/img/loading.gif) no-repeat center center; box-sizing: border-box; -webkit-box-sizing: border-box; -moz-box-sizing:border-box; } ' +
                            '.big { min-height: 300px; } ' +
                            '.complete { border:none; border-radius: 0; background:none; }' +
                            '.small { width: 130px; height: 130px; float: left; overflow: hidden; margin: 0 5px 5px 0; } ' +
                            '.small.complete img { min-width: 100%; max-height: 100%; } ' +
                            '/* firefox fix: remove loading/broken image icon */ @-moz-document url-prefix() { img:-moz-loading { visibility: hidden; } img:-moz-broken { -moz-force-broken-image-icon: 0;}} ' +
                            '</style>' +
                            imageHtml;

                    iframe.onload = function () {
                        this.contentWindow.document.body.innerHTML = imageHtml;
                    };

                    container.update(""); // remove all
                    container.clean(true);
                    container.dom.appendChild(iframe);
                    container.applyStyles(styles);

                    var date = new Date();
                    if(pimcore.helpers.treeNodeThumbnailLastClose === 0 || (date.getTime() - pimcore.helpers.treeNodeThumbnailLastClose) > 300) {
                        // open deferred
                        pimcore.helpers.treeNodeThumbnailTimeout = window.setTimeout(function() {
                            container.removeClass("hidden");
                        }, 500);
                    } else {
                        // open immediately
                        container.removeClass("hidden");
                    }
                }
            }.bind(this, node));
            el.on("mouseleave", function () {
                pimcore.helpers.treeNodeThumbnailPreviewHide();
            }.bind(this));
        }.bind(this, node), 200);
    }
};

pimcore.helpers.treeNodeThumbnailPreviewHide = function () {

    if(pimcore.helpers.treeNodeThumbnailTimeout) {
        clearTimeout(pimcore.helpers.treeNodeThumbnailTimeout);
        pimcore.helpers.treeNodeThumbnailTimeout = null;
    }

    var container = Ext.get("pimcore_tree_preview");
    if(container) {
        if(!container.hasClass("hidden")) {
            var date = new Date();
            pimcore.helpers.treeNodeThumbnailLastClose = date.getTime();
        }
        container.addClass("hidden");
    }
};

pimcore.helpers.showUser = function(specificUser) {
    var user = pimcore.globalmanager.get("user");
    if (user.isAllowed("users")) {
        var panel = null;
        try {
            panel = pimcore.globalmanager.get("users");
            panel.activate();
        }
        catch (e) {
            panel = new pimcore.settings.user.panel();
            pimcore.globalmanager.add("users", panel);
        }

        if (specificUser) {
            panel.openUser(specificUser);
        }
    }
};

pimcore.helpers.insertTextAtCursorPosition = function (text) {

    // get focused element
    var focusedElement = document.activeElement;
    var win = window;
    var doc = document;

    // now check if the focus is inside an iframe
    try {
        while(focusedElement.tagName.toLowerCase() == "iframe") {
            win = window[focusedElement.getAttribute("name")];
            doc = win.document;
            focusedElement = doc.activeElement;
        }
    } catch(e) {
        console.log(e);
    }

    var elTagName = focusedElement.tagName.toLowerCase();

    if(elTagName == "input" || elTagName == "textarea") {
        insertTextToFormElementAtCursor(focusedElement, text);
    } else if(elTagName == "div" && focusedElement.getAttribute("contenteditable")) {
        insertTextToContenteditableAtCursor(text, win, doc);
    }

};


pimcore.helpers.handleTabRightClick = function (tabPanel, el, index) {


    if(Ext.get(el.tabEl)) {
        Ext.get(el.tabEl).on("contextmenu", function (e) {

            var items = [];

            // this is only for the main tab panel
            if(tabPanel.getId() == "pimcore_panel_tabs") {
                items = [{
                    text: t('close_others'),
                    iconCls: "",
                    handler: function (item) {
                        pimcore.helpers.closeAllElements(el);
                        // clear the opentab store, so that also non existing elements are flushed
                        pimcore.helpers.clearOpenTab();
                    }.bind(this)
                }, {
                    text: t('close_unmodified'),
                    iconCls: "",
                    handler: function (item) {
                        pimcore.helpers.closeAllUnmodified();
                        // clear the opentab store, so that also non existing elements are flushed
                        pimcore.helpers.clearOpenTab();
                    }.bind(this)
                }];
            }

            // every tab panel can get this
            items.push({
                text: t('close_all'),
                iconCls: "",
                handler: function (item) {
                    pimcore.helpers.closeAllElements(null,tabPanel);
                    // clear the opentab store, so that also non existing elements are flushed
                    pimcore.helpers.clearOpenTab();
                }.bind(this)
            });


            var menu = new Ext.menu.Menu({
                items: items
            });

            menu.showAt(e.getXY());
            e.stopEvent();
        });
    }
};

pimcore.helpers.uploadAssetFromFileObject = function (file, url, callbackSuccess, callbackProgress, callbackFailure) {

    if(typeof callbackSuccess != "function") {
        callbackSuccess = function () {};
    }
    if(typeof callbackProgress != "function") {
        callbackProgress = function () {};
    }
    if(typeof callbackFailure != "function") {
        callbackFailure = function () {};
    }

    var data = new FormData();
    data.append('Filedata', file);
    data.append("filename", file.name);

    jQuery.ajax({
        xhr: function()
        {
            var xhr = new window.XMLHttpRequest();

            //Upload progress
            xhr.upload.addEventListener("progress", function(evt){
                callbackProgress(evt);
            }, false);

            return xhr;
        },
        processData: false,
        contentType: false,
        type: 'POST',
        url: url,
        data: data,
        success: callbackSuccess,
        error: callbackFailure
    });

};



pimcore.helpers.searchAndMove = function (parentId, callback, type) {
    if (type == "object") {
        config = {
            type: ["object"],
                subtype: {
            object: ["object", "folder"]
        },
            specific: {
                classes: null
            }
        };
    } else {
        config = {
            type: [type]
        }
    }
    pimcore.helpers.itemselector(true, function (selection) {

        var jobs = [];

        if(selection && selection.length > 0) {
            for(var i=0; i<selection.length; i++) {
                var params;
                if (type == "object") {
                    params = {
                        id: selection[i]["id"],
                        values: Ext.encode({
                            parentId: parentId
                        })
                    };
                } else {
                    params = {
                        id: selection[i]["id"],
                        parentId: parentId
                    };
                }
                jobs.push([{
                    url: "/admin/" + type + "/update",
                    params: params
                }]);
            }
        }

        if (jobs.length == 0) {
            return;
        }

        this.addChildProgressBar = new Ext.ProgressBar({
            text: t('initializing')
        });

        this.addChildWindow = new Ext.Window({
            layout:'fit',
            width:500,
            bodyStyle: "padding: 10px;",
            closable:false,
            plain: true,
            modal: true,
            items: [this.addChildProgressBar]
        });

        this.addChildWindow.show();

        var pj = new pimcore.tool.paralleljobs({
            success: function (callbackFunction) {

                if(this.addChildWindow) {
                    this.addChildWindow.close();
                }

                this.deleteProgressBar = null;
                this.addChildWindow = null;

                if(typeof callbackFunction == "function") {
                    callbackFunction();
                }

                try {
                    var node = pimcore.globalmanager.get("layout_object_tree").tree.getNodeById(this.object.id);
                    node.reload();
                } catch (e) {
                    // node is not present
                }
            }.bind(this, callback),
            update: function (currentStep, steps, percent) {
                if(this.addChildProgressBar) {
                    var status = currentStep / steps;
                    this.addChildProgressBar.updateProgress(status, percent + "%");
                }
            }.bind(this),
            failure: function (response) {
                this.addChildWindow.close();
                Ext.MessageBox.alert(t("error"), t(response));
            }.bind(this),
            jobs: jobs
        });

    }.bind(this), config);
};


pimcore.helpers.sendTestEmail = function () {

    var win = new Ext.Window({
        width: 800,
        height: 600,
        modal: true,
        title: t("send_test_email"),
        layout: "fit",
        closeAction: "close",
        items: [{
            xtype: "form",
            bodyStyle: "padding:10px;",
            layout: "pimcoreform",
            itemId: "form",
            items: [{
                xtype: "textfield",
                name: "to",
                fieldLabel: t("to"),
                width: 650
            }, {
                xtype: "textfield",
                name: "subject",
                fieldLabel: t("subject"),
                width: 650
            }, {
                xtype: "textarea",
                name: "content",
                fieldLabel: t("content"),
                width: 650,
                height: 450
            }]
        }],
        buttons: [{
            text: t("send_as_plain_text"),
            iconCls: "pimcore_icon_text",
            handler: function () {
                send("text");
            }
        }, {
            text: t("send_as_html_mime"),
            iconCls: "pimcore_icon_html",
            handler: function () {
                send("html");
            }
        }]
    });

    var send = function (type) {

        var params = win.getComponent("form").getForm().getFieldValues();
        params["type"] = type;

        Ext.Ajax.request({
            url: "/admin/email/send-test-email",
            params: params,
            method: "post"
        });

        win.close();
    }

    win.show();

};

/* this is here so that it can be opened in the parent window when in editmode frame */
pimcore.helpers.openImageCropper = function (imageId, data, saveCallback, config) {
    var cropper = new top.pimcore.element.tag.imagecropper(imageId, data, saveCallback, config);
    return cropper;
};

/* this is here so that it can be opened in the parent window when in editmode frame */
pimcore.helpers.openImageHotspotMarkerEditor = function (imageId, data, saveCallback) {
    var editor = new pimcore.element.tag.imagehotspotmarkereditor(imageId, data, saveCallback);
    return editor;
};


pimcore.helpers.editmode = {};

pimcore.helpers.editmode.openLinkEditPanel = function (data, callback) {

    var fieldPath = new Ext.form.TextField({
        fieldLabel: t('path'),
        value: data.path,
        name: "path",
        width: 320,
        cls: "pimcore_droptarget_input",
        enableKeyEvents: true,
        listeners: {
            keyup: function (el) {
                if(el.getValue().match(/^www\./)) {
                    el.setValue("http://" + el.getValue());
                }
            }
        }
    });


    fieldPath.on("render", function (el) {
        // add drop zone
        new Ext.dd.DropZone(el.getEl(), {
            reference: this,
            ddGroup: "element",
            getTargetFromEvent: function(e) {
                return fieldPath.getEl();
            },

            onNodeOver : function(target, dd, e, data) {
                return Ext.dd.DropZone.prototype.dropAllowed;
            }.bind(this),

            onNodeDrop : function (target, dd, e, data) {
                if (data.node.attributes.elementType == "asset" || data.node.attributes.elementType == "document") {
                    fieldPath.setValue(data.node.attributes.path);
                    return true;
                }
                return false;
            }.bind(this)
        });
    }.bind(this));

    /*fieldPath.on("render", function (el) {
        // register at global DnD manager
        dndManager.addDropTarget(el.getEl(), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));
    }.bind(this));*/

    var form = new Ext.FormPanel({
        itemId: "form",
        items: [
            {
                xtype:'tabpanel',
                activeTab: 0,
                deferredRender: false,
                defaults:{autoHeight:true, bodyStyle:'padding:10px'},
                border: false,
                items: [
                    {
                        title:t('basic'),
                        layout:'form',
                        border: false,
                        defaultType: 'textfield',
                        items: [
                            {
                                fieldLabel: t('text'),
                                name: 'text',
                                value: data.text
                            },
                            {
                                xtype: "compositefield",
                                items: [fieldPath, {
                                    xtype: "button",
                                    iconCls: "pimcore_icon_search",
                                    handler: function () {
                                        pimcore.helpers.itemselector(false, function (item) {
                                            if (item) {
                                                fieldPath.setValue(item.fullpath);
                                                return true;
                                            }
                                        }, {
                                            type: ["asset","document"]
                                        });
                                    }
                                }]
                            },
                            {
                                xtype:'fieldset',
                                title: t('properties'),
                                collapsible: false,
                                autoHeight:true,
                                defaultType: 'textfield',
                                items :[
                                    {
                                        xtype: "combo",
                                        fieldLabel: t('target'),
                                        name: 'target',
                                        triggerAction: 'all',
                                        editable: true,
                                        mode: "local",
                                        store: ["","_blank","_self","_top","_parent"],
                                        value: data.target
                                    },
                                    {
                                        fieldLabel: t('parameters'),
                                        name: 'parameters',
                                        value: data.parameters
                                    },
                                    {
                                        fieldLabel: t('anchor'),
                                        name: 'anchor',
                                        value: data.anchor
                                    },
                                    {
                                        fieldLabel: t('title'),
                                        name: 'title',
                                        value: data.title
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        title: t('advanced'),
                        layout:'form',
                        defaultType: 'textfield',
                        border: false,
                        items: [
                            {
                                fieldLabel: t('accesskey'),
                                name: 'accesskey',
                                value: data.accesskey
                            },
                            {
                                fieldLabel: t('relation'),
                                name: 'rel',
                                width: 300,
                                value: data.rel
                            },
                            {
                                fieldLabel: ('tabindex'),
                                name: 'tabindex',
                                value: data.tabindex
                            },
                            {
                                fieldLabel: t('class'),
                                name: 'class',
                                width: 300,
                                value: data["class"]
                            },
                            {
                                fieldLabel: t('attributes') + ' (key="value")',
                                name: 'attributes',
                                width: 300,
                                value: data["attributes"]
                            }
                        ]
                    }
                ]
            }
        ],
        buttons: [
            {
                text: t("empty"),
                listeners:  {
                    "click": callback["empty"]
                }
            },
            {
                text: t("cancel"),
                listeners:  {
                    "click": callback["cancel"]
                }
            },
            {
                text: t("save"),
                listeners: {
                    "click": callback["save"]
                },
                icon: "/pimcore/static/img/icon/tick.png"
            }
        ]
    });


    var window = new Ext.Window({
        modal: false,
        width: 500,
        height: 330,
        title: t("edit_link"),
        items: [form],
        layout: "fit"
    });

    window.show();

    return window;
};


pimcore.helpers.editmode.openVideoEditPanel = function (data, callback) {

    var form = null;
    var fieldPath = new Ext.form.TextField({
        fieldLabel: t('path'),
        value: data.path,
        name: "path",
        width: 320,
        cls: "pimcore_droptarget_input",
        enableKeyEvents: true,
        listeners: {
            keyup: function (el) {
                if(el.getValue().indexOf("you") >= 0 && el.getValue().indexOf("http") >= 0) {
                    form.getComponent("type").setValue("youtube");
                } else if (el.getValue().indexOf("vim") >= 0 && el.getValue().indexOf("http") >= 0) {
                    form.getComponent("type").setValue("vimeo");
                }
            }.bind(this)
        }
    });

    var poster = new Ext.form.TextField({
        fieldLabel: t('poster_image'),
        value: data.poster,
        name: "poster",
        width: 320,
        cls: "pimcore_droptarget_input",
        enableKeyEvents: true,
        listeners: {
            keyup: function (el) {
                //el.setValue(data.poster)
            }.bind(this)
        }
    });

    var initDD = function (el) {
        // register at global DnD manager
        new Ext.dd.DropZone(el.getEl(), {
            reference: this,
            ddGroup: "element",
            getTargetFromEvent: function(e) {
                return el.getEl();
            },

            onNodeOver : function(target, dd, e, data) {
                if (target && target.getAttribute("name") == "poster") {
                    if (data.node.attributes.elementType == "asset" && data.node.attributes.type == "image") {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                } else {
                    if (data.node.attributes.elementType == "asset" && data.node.attributes.type == "video") {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                }
                return Ext.dd.DropZone.prototype.dropNotAllowed;
            }.bind(this),

            onNodeDrop : function (target, dd, e, data) {
                if(target) {
                    if(target.getAttribute("name") == "path") {
                        if (data.node.attributes.elementType == "asset" && data.node.attributes.type == "video") {
                            fieldPath.setValue(data.node.attributes.path);
                            form.getComponent("type").setValue("asset");
                            return true;
                        }
                    } else if (target.getAttribute("name") == "poster") {
                        if (data.node.attributes.elementType == "asset" && data.node.attributes.type == "image") {
                            poster.setValue(data.node.attributes.path);
                            return true;
                        }
                    }
                }

                return false;
            }.bind(this)
        });
    };

    fieldPath.on("render", initDD);
    poster.on("render", initDD);

    var searchButton = new Ext.Button({
        iconCls: "pimcore_icon_search",
        handler: function () {
            pimcore.helpers.itemselector(false, function (item) {
                if (item) {
                    fieldPath.setValue(item.fullpath);
                    return true;
                }
            }, {
                type: ["asset"],
                subtype: {
                    asset: ["video"]
                }
            });
        }
    });

    var updateType = function (type) {
        searchButton.enable();
        var labelEl = form.getComponent("pathContainer").label;
        labelEl.update(t("path"));

        if(type != "asset") {
            searchButton.disable();

            poster.hide();
            poster.setValue("");
        } else {
            poster.show();
        }

        if(type == "youtube") {
            labelEl.update("URL / ID");
        }

        if(type == "vimeo") {
            labelEl.update("URL");
        }
    };

    form = new Ext.FormPanel({
        itemId: "form",
        bodyStyle: "padding:10px;",
        items: [{
            xtype: "combo",
            itemId: "type",
            fieldLabel: t('type'),
            name: 'type',
            triggerAction: 'all',
            editable: true,
            mode: "local",
            store: ["asset","youtube","vimeo"],
            value: data.type,
            listeners: {
                select: function (combo) {
                    var type = combo.getValue();
                    updateType(type);
                }.bind(this)
            }
        }, {
            xtype: "compositefield",
            itemId: "pathContainer",
            items: [fieldPath, searchButton]
        }, poster,{
            xtype: "textfield",
            name: "title",
            fieldLabel: t('title'),
            width: 320,
            value: data.title
        },{
            xtype: "textarea",
            name: "description",
            fieldLabel: t('description'),
            width: 320,
            height: 50,
            value: data.description
        }],
        buttons: [
            {
                text: t("cancel"),
                listeners:  {
                    "click": callback["cancel"]
                }
            },
            {
                text: t("save"),
                listeners: {
                    "click": callback["save"]
                },
                icon: "/pimcore/static/img/icon/tick.png"
            }
        ]
    });


    var window = new Ext.Window({
        width: 500,
        height: 250,
        title: t("video"),
        items: [form],
        layout: "fit",
        listeners: {
            afterrender: function () {
                updateType(data.type);
            }.bind(this)
        }
    });
    window.show();

    return window;
};


pimcore.helpers.editmode.openPdfEditPanel = function () {


    // FUNCTIONS

    var editMarkerHotspotData = function (id) {

        var hotspotMetaDataWin = new Ext.Window({
            width: 600,
            height: 440,
            closeAction: "close",
            resizable: false,
            autoScroll: true,
            items: [{
                xtype: "form",
                itemId: "form",
                bodyStyle: "padding: 10px;"
            }],
            tbar: [{
                xtype: "button",
                iconCls: "pimcore_icon_add",
                menu: [{
                    text: t("link"),
                    iconCls: "pimcore_icon_input",
                    handler: function () {
                        addItem("link");
                    }
                },"-",{
                    text: t("textfield"),
                    iconCls: "pimcore_icon_input",
                    handler: function () {
                        addItem("textfield");
                    }
                }, {
                    text: t("textarea"),
                    iconCls: "pimcore_icon_textarea",
                    handler: function () {
                        addItem("textarea");
                    }
                }, {
                    text: t("checkbox"),
                    iconCls: "pimcore_icon_checkbox",
                    handler: function () {
                        addItem("checkbox");
                    }
                }, {
                    text: t("object"),
                    iconCls: "pimcore_icon_object",
                    handler: function () {
                        addItem("object");
                    }
                }, {
                    text: t("document"),
                    iconCls: "pimcore_icon_document",
                    handler: function () {
                        addItem("document");
                    }
                }, {
                    text: t("asset"),
                    iconCls: "pimcore_icon_asset",
                    handler: function () {
                        addItem("asset");
                    }
                }]
            }],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: function (id) {

                    var data = hotspotMetaDataWin.getComponent("form").getForm().getFieldValues();
                    var normalizedData = [];

                    // when only one item is in the form
                    if(typeof data["name"] == "string") {
                        data = {
                            name: [data["name"]],
                            type: [data["type"]],
                            value: [data["value"]]
                        };
                    }

                    if(data && data["name"] && data["name"].length > 0) {
                        for(var i=0; i<data["name"].length; i++) {
                            normalizedData.push({
                                name: data["name"][i],
                                value: data["value"][i],
                                type: data["type"][i]
                            });
                        }
                    }

                    this.hotspotMetaData[id] = normalizedData;

                    hotspotMetaDataWin.close();
                }.bind(this, id)
            }],
            listeners: {
                afterrender: function (id) {
                    if(this.hotspotMetaData && this.hotspotMetaData[id]) {
                        var data = this.hotspotMetaData[id];
                        for(var i=0; i<data.length; i++) {
                            addItem(data[i]["type"], data[i]);
                        }
                    }
                }.bind(this, id)
            }
        });

        var addItem = function (hotspotMetaDataWin, type, data) {

            var id = "item-" + uniqid();
            var valueField;

            if(!data || !data["name"]) {
                data = {
                    name: "",
                    value: ""
                };
            }

            if(type == "textfield") {
                valueField = {
                    xtype: "textfield",
                    name: "value",
                    fieldLabel: t("value"),
                    width: 400,
                    value: data["value"]
                };
            } else if(type == "textarea") {
                valueField = {
                    xtype: "textarea",
                    name: "value",
                    fieldLabel: t("value"),
                    width: 400,
                    value: data["value"]
                };
            } else if(type == "checkbox") {
                valueField = {
                    xtype: "checkbox",
                    name: "value",
                    fieldLabel: t("value"),
                    checked: data["value"]
                };
            } else if(type == "object") {
                valueField = {
                    xtype: "textfield",
                    cls: "pimcore_droptarget_input",
                    name: "value",
                    fieldLabel: t("value"),
                    value: data["value"],
                    width: 400,
                    listeners: {
                        render: function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: this,
                                ddGroup: "element",
                                getTargetFromEvent: function(e) {
                                    return this.getEl();
                                }.bind(el),

                                onNodeOver : function (target, dd, e, data) {
                                    if(data.node.attributes.elementType == "object") {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    }
                                    return Ext.dd.DropZone.prototype.dropNotAllowed;
                                },

                                onNodeDrop : function (target, dd, e, data) {
                                    if(data.node.attributes.elementType == "object") {
                                        target.dom.value = data.node.attributes.path;
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            });
                        }.bind(this)
                    }
                };
            } else if(type == "asset") {
                valueField = {
                    xtype: "textfield",
                    cls: "pimcore_droptarget_input",
                    name: "value",
                    fieldLabel: t("value"),
                    value: data["value"],
                    width: 400,
                    listeners: {
                        render: function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: this,
                                ddGroup: "element",
                                getTargetFromEvent: function(e) {
                                    return this.getEl();
                                }.bind(el),

                                onNodeOver : function (target, dd, e, data) {
                                    if(data.node.attributes.elementType == "asset") {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    }
                                    return Ext.dd.DropZone.prototype.dropNotAllowed;
                                },

                                onNodeDrop : function (target, dd, e, data) {
                                    if(data.node.attributes.elementType == "asset") {
                                        target.dom.value = data.node.attributes.path;
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            });
                        }.bind(this)
                    }
                };
            } else if(type == "document" || type == "link") {

                if(type == "link") {
                    data["name"] = "link";
                }

                valueField = {
                    xtype: "textfield",
                    cls: "pimcore_droptarget_input",
                    name: "value",
                    fieldLabel: t("value"),
                    value: data["value"],
                    width: 400,
                    listeners: {
                        render: function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: this,
                                ddGroup: "element",
                                getTargetFromEvent: function(e) {
                                    return this.getEl();
                                }.bind(el),

                                onNodeOver : function (target, dd, e, data) {
                                    if(data.node.attributes.elementType == "document") {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    }
                                    return Ext.dd.DropZone.prototype.dropNotAllowed;
                                },

                                onNodeDrop : function (target, dd, e, data) {
                                    if(data.node.attributes.elementType == "document") {
                                        target.dom.value = data.node.attributes.path;
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            });
                        }.bind(this)
                    }
                };
            } else {
                // no valid type
                return;
            }

            hotspotMetaDataWin.getComponent("form").add({
                xtype: "fieldset",
                style: "padding: 0;",
                bodyStyle: "padding: 5px;",
                itemId: id,
                items: [{
                    xtype: "hidden",
                    name: "type",
                    value: type
                },{
                    xtype: "textfield",
                    name: "name",
                    value: data["name"],
                    fieldLabel: t("name")
                }, valueField],
                tbar: ["->", {
                    iconCls: "pimcore_icon_delete",
                    handler: function (hotspotMetaDataWin) {
                        var form = hotspotMetaDataWin.getComponent("form");
                        form.remove(form.getComponent(id));
                        hotspotMetaDataWin.doLayout();
                    }.bind(this, hotspotMetaDataWin)
                }]
            });

            hotspotMetaDataWin.doLayout();
        }.bind(this, hotspotMetaDataWin);

        hotspotMetaDataWin.show();
    }.bind(this);

    var addHotspot = function (config) {
        var hotspotId = "pdf-hotspot-" + uniqid();

        var pageContainerDiv = Ext.get(this.metaDataWindow.getComponent("pageContainer").body.query(".page")[0]);
        pageContainerDiv.insertHtml("beforeEnd", '<div id="' + hotspotId + '" class="pimcore_pdf_hotspot"></div>');

        var hotspotEl = Ext.get(hotspotId);

        // default dimensions
        hotspotEl.applyStyles({
            position: "absolute",
            cursor: "pointer",
            top: 0,
            left: 0,
            width: "50px",
            height: "50px"
        });

        if(typeof config == "object") {
            var imgEl = Ext.get(this.metaDataWindow.getComponent("pageContainer").body.query("img")[0]);
            var originalWidth = imgEl.getWidth();
            var originalHeight = imgEl.getHeight();

            hotspotEl.applyStyles({
                top: (originalHeight * (config["top"]/100)) + "px",
                left: (originalWidth * (config["left"]/100)) + "px",
                width: (originalWidth * (config["width"]/100)) + "px",
                height: (originalHeight * (config["height"]/100)) + "px"
            });

            if(config["data"]) {
                this.hotspotMetaData[hotspotId] = config["data"];
            }
        }

        hotspotEl.on("contextmenu", function (id, e) {
            var menu = new Ext.menu.Menu();

            menu.add(new Ext.menu.Item({
                text: t("add_data"),
                iconCls: "pimcore_icon_add_data",
                handler: function (id, item) {
                    item.parentMenu.destroy();

                    editMarkerHotspotData(id);
                }.bind(this, id)
            }));

            menu.add(new Ext.menu.Item({
                text: t("remove"),
                iconCls: "pimcore_icon_delete",
                handler: function (id, item) {
                    item.parentMenu.destroy();
                    Ext.get(id).remove();
                }.bind(this, id)
            }));

            menu.showAt(e.getXY());
            e.stopEvent();
        }.bind(this, hotspotId));


        var resizer = new Ext.Resizable(hotspotId, {
            pinned:true,
            minWidth:20,
            minHeight: 20,
            preserveRatio: false,
            dynamic:true,
            handles: 'all',
            draggable:true
        });


        return hotspotId;
    }.bind(this);



    var editTextVersion = function(config){

        var text = null;
        if (this.data.texts) {
            text = this.data.texts[this.currentPage];
        }
        if(!text){
            text = this.requestTextForCurrentPage();
        }
        this.textArea = new Ext.form.TextArea(
            {
                fieldLabel: t("pimcore_lable_text"),
                name : "text",
                width : 670,
                height: 305,
                value: text
            });

        var panel = new Ext.form.FormPanel({
            labelWidth: 80,
            bodyStyle: "padding: 10px;",
            items: [
                this.textArea
            ]
        });


        this.editTextVersionWindow = new Ext.Window({
            width: 800,
            height: 400,
            iconCls: "pimcore_icon_edit_pdf_text",
            title: t('pimcore_icon_edit_pdf_text'),
            layout: "fit",
            closeAction:'close',
            plain: true,
            maximized: false,
            items : [panel],
            scrollable : false,
            modal: true,
            buttons: [
                {
                    text: t("apply"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.textStore[this.currentPage] = this.textArea.getValue();
                        this.editTextVersionWindow.close();
                    }.bind(this)
                },
                {
                    text: t("cancel"),
                    iconCls: "pimcore_icon_delete",
                    handler: function () {
                        this.editTextVersionWindow.close();
                    }.bind(this)
                }
            ]
        });

        this.editTextVersionWindow.show();
    }.bind(this);

    var hotspotEditPage = function (page) {
        this.saveCurrentPage();
        this.currentPage = page;

        var pageContainer = this.metaDataWindow.getComponent("pageContainer");
        pageContainer.removeAll();

        var thumbUrl = "/admin/asset/get-document-thumbnail/id/"
            + this.data.id +
            "/width/400/height/400/contain/true/page/" + page;
        var page = new Ext.Panel({
            border: false,
            bodyStyle: "background: #e5e5e5; ",
            html: '<div style="margin:0 auto; position:relative; overflow: hidden;" ' +
            'class="page"><img src="' + thumbUrl + '" /></div>',
            tbar: [{
                xtype: "button",
                text: t("add_hotspot"),
                iconCls: "pimcore_icon_add_hotspot",
                handler: addHotspot
            },
                {
                    xtype: "button",
                    text: t("pimcore_icon_edit_pdf_text"),
                    iconCls: "pimcore_icon_edit_pdf_text",
                    handler: editTextVersion
                },
                "->",
                {
                    text: t("chapter"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;",
                },
                {
                    xtype: "textfield",
                    name: "chapter",
                    width: 200,
                    style: "margin: 0 10px 0 0;",
                    value: this.chapterStore[page]
                }
            ],
            listeners: {
                afterrender: function (el) {
                    var el = el.body;
                    var checks = 0;
                    var detailInterval = window.setInterval(function () {

                        try {
                            checks++;

                            var div = Ext.get(el.query(".page")[0]);
                            var img = Ext.get(el.query("img")[0]);

                            if((img.getHeight() > 100 && img.getWidth() > 100) || checks > 300 || !div || !img) {
                                window.clearInterval(detailInterval);
                            }

                            if(img.getHeight() > 100 && img.getWidth() > 100) {
                                div.applyStyles({
                                    width: img.getWidth() + "px",
                                    height: img.getHeight() + "px",
                                    visibility: "visible",
                                    "margin-left": ((el.getWidth()-img.getWidth())/2) + "px",
                                    "margin-top": ((el.getHeight()-img.getHeight())/2) + "px"
                                });
                            }
                        } catch (e) {
                            // stop the timer when an error occours
                            window.clearInterval(detailInterval);
                        }
                    }, 200);

                    // add hotspots
                    var hotspots = this.hotspotStore[this.currentPage];
                    if(hotspots) {
                        for(var i=0; i<hotspots.length; i++) {
                            addHotspot(hotspots[i]);
                        }
                    }
                }.bind(this)
            }
        });

        pageContainer.add(page);

        pageContainer.doLayout();
    };





    // START
    var thumbUrl = "";
    var pages = [];

    this.hotspotStore = {};
    this.hotspotMetaData = {};
    this.textStore = {};
    this.chapterStore = {};
    if(this.data["hotspots"]) {
        this.hotspotStore = this.data["hotspots"];
    }

    if(this.data["texts"]){
        this.textStore = this.data["texts"];
    }

    if(this.data["chapters"]){
        this.chapterStore = this.data["chapters"];
    }


    this.currentPage = null;

    for(var i=1; i<=this.data.pageCount; i++) {
        thumbUrl = "/admin/asset/get-document-thumbnail/id/"
        + this.data.id + "/width/400/height/400/contain/true/page/" + i;


        pages.push({
            style: "margin-bottom: 10px; text-align: center; cursor:pointer; ",
            bodyStyle: "min-height: 150px;",
            html: '<span id="' + this.getName() + '-page-' + i + '" style="font-size:35px; line-height: 150px;" data-src="' + thumbUrl + '">' + i + '</span>' , // blank gif image
            listeners: {
                afterrender: function (page, el) {
                    // unfortunately the panel element has no click event, so we have to add it to the image
                    // after the panel was rendered
                    var body = Ext.get(el.body);
                    body.on("click", hotspotEditPage.bind(this, page));
                }.bind(this, i)
            }
        });
    }

    this.pagesContainer = new Ext.Panel({
        width: 150,
        region: "west",
        autoScroll: true,
        bodyStyle: "padding: 10px;",
        items: pages
    });


    var loadingInterval = window.setInterval(function () {

        if(!this.pagesContainer || !this.pagesContainer.body || !this.pagesContainer.body.dom) {
            clearInterval(loadingInterval);
        } else {
            var el;
            var scroll = this.pagesContainer.body.getScroll();
            var startPage = Math.floor(scroll.top / 162); // 162 is the height of one thumbnail incl. border and margin
            for(var i=startPage; i<(startPage+5); i++) {
                el = Ext.get(this.getName() + "-page-" + i);
                if(el) {
                    // el.parent().update('<img src="' + el.getAttribute("data-src") + '" height="150" />');
                    el.parent().update('<div class="pdf-image-wrapper"><img src="' + el.getAttribute("data-src") + '" height="150" /><div class="nr ' + (this.hasMetaData(i) ? 'hasMetadata'  : '') +'" style="font-size:35px; line-height:150px; position: absolute;top:0px;width: 100%;">' + i + '</div></div>');
                }
            }
        }
    }.bind(this), 1000);

    this.metaDataWindow = new Ext.Window({
        width: 700,
        height: 510,
        closeAction: "close",
        resizable: false,
        layout: "border",
        items: [this.pagesContainer, {
            region: "center",
            layout: "fit",
            itemId: "pageContainer"
        }],
        bbar: ["->", {
            xtype: "button",
            iconCls: "pimcore_icon_apply",
            text: t("save"),
            handler: function () {
                this.saveCurrentPage();
                this.data["hotspots"] = this.hotspotStore;
                this.data["texts"] = this.textStore;
                this.data["chapters"] = this.chapterStore;
                this.metaDataWindow.close();
            }.bind(this)
        }]
    });

    this.metaDataWindow.show();
};

