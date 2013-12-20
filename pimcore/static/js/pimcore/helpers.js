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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
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
};

pimcore.helpers.openAsset = function (id, type, ignoreForHistory) {

    if (pimcore.globalmanager.exists("asset_" + id) == false) {

        pimcore.helpers.addTreeNodeLoadingIndicator("asset", id);

        if (!pimcore.asset[type]) {
            pimcore.globalmanager.add("asset_" + id, new pimcore.asset.unknown(id));
        }
        else {
            pimcore.globalmanager.add("asset_" + id, new pimcore.asset[type](id));
        }

        pimcore.helpers.rememberOpenTab("asset_" + id + "_" + type);

        if (ignoreForHistory) {
            var element = pimcore.globalmanager.get("asset_" + id);
            element.setAddToHistory(false);
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

pimcore.helpers.openDocument = function (id, type, ignoreForHistory) {
    if (pimcore.globalmanager.exists("document_" + id) == false) {
        if (pimcore.document[type]) {
            pimcore.helpers.addTreeNodeLoadingIndicator("document", id);
            pimcore.globalmanager.add("document_" + id, new pimcore.document[type](id));
            pimcore.helpers.rememberOpenTab("document_" + id + "_" + type);

            if (ignoreForHistory) {
                var element = pimcore.globalmanager.get("document_" + id);
                element.setAddToHistory(false);
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

pimcore.helpers.openObject = function (id, type, ignoreForHistory) {
    if (pimcore.globalmanager.exists("object_" + id) == false) {
        pimcore.helpers.addTreeNodeLoadingIndicator("object", id);

        if(type != "folder" && type != "variant" && type != "object") {
            type = "object";
        }

        pimcore.globalmanager.add("object_" + id, new pimcore.object[type](id));
        pimcore.helpers.rememberOpenTab("object_" + id + "_" + type);

        if (ignoreForHistory) {
            var element = pimcore.globalmanager.get("object_" + id);
            element.setAddToHistory(false);
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
            bodyStyle: "padding: 10px; background:#fff;",
            buttonAlign: "center",
            shadow: false,
            closable: false,
            buttons: [{
                text: "OK",
                handler: function () {
                    errWin.close();
                }
            }],
            listeners: {
                afterrender: function (el) {
                    var myRobotId = "robot-" + Math.random();
                    el.getEl().addClass("swing animated");
                    el.getEl().insertHtml("afterBegin", '<div class="error-robot" id="' + myRobotId + '"><img src="/admin/misc/robohash" /></div><div class="error-bubble"></div> ');
                    window.setTimeout(function () {
                        Ext.get(myRobotId).animate({left: {from: "0px", to: "-300px"}}, 0.3, function () {
                            Ext.get(Ext.query(".error-bubble")[0]).show();
                        }, "easeOut", "run");
                    }, 1000);
                }
            }
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

pimcore.helpers.closeAllElements = function (except) {

    var exceptions = [];
    if(except instanceof Ext.Panel) {
        exceptions.push(except);
    } else if (except instanceof Array) {
        exceptions = except;
    }

    var tabs = Ext.getCmp("pimcore_panel_tabs").items;
    if (tabs.getCount() > 0) {
        tabs.each(function (item, index, length) {
            window.setTimeout(function () {
                if(!in_array(item, exceptions)) {
                    Ext.getCmp("pimcore_panel_tabs").remove(item);
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
        // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
        openTabs = JSON.parse(openTabs);
    }

    return openTabs;
};

pimcore.helpers.clearOpenTab = function () {
    localStorage.setItem("pimcore_opentabs", JSON.stringify([]));
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

    // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
    localStorage.setItem("pimcore_opentabs", JSON.stringify(openTabs));
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
                            pimcore.helpers.openAsset(parts[1], parts[2], true);
                        } else if(parts[0] == "document") {
                            pimcore.helpers.openDocument(parts[1], parts[2], true);
                        } else if(parts[0] == "object") {
                            pimcore.helpers.openObject(parts[1], parts[2], true);
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
    Ext.Ajax.request({
        url: "/admin/document/open-by-url/",
        params: {
            url: path
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
};

pimcore.helpers.openDocumentByPathDialog = function () {
    Ext.MessageBox.prompt(t("open_document_by_url"), t("path_or_url_incl_http"), function (button, value, object) {
        if (button == "ok") {
            pimcore.helpers.openDocumentByPath(value);
        }
    });
};

pimcore.helpers.isCanvasSupported = function () {
    var elem = document.createElement('canvas');
    return !!(elem.getContext && elem.getContext('2d'));
};

pimcore.helpers.urlToCanvas = function (url, callback) {

    if(!pimcore.helpers.isCanvasSupported()) {
        return;
    }

    var date = new Date();
    var frameId = "screenshotIframe_" + date.getTime();
    var iframe = document.createElement("iframe");
    iframe.setAttribute("name", frameId);
    iframe.setAttribute("id", frameId);
    iframe.setAttribute("src", url);
    iframe.setAttribute("allowtransparency", "false");
    iframe.setAttribute("style","width:1280px; height:1000px; position:absolute; left:-10000; "
        + "top:-10000px; background:#fff;");
    iframe.onload = function () {
        window.setTimeout(function () {
            html2canvas([window[frameId].document.body], {
                onrendered: function (canvas) {
                    document.body.removeChild(iframe);
                    if(typeof callback == "function") {
                        callback(canvas);
                    }
                },
                proxy: "/admin/misc/proxy/"
            });
        }, 2000);
    };

    document.body.appendChild(iframe);
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
    } /*else {
     // DISABLED BECAUSE NOT REALLY SATISFIED WITH THE RESULTS

     pimcore.helpers.urlToCanvas(path, function (id, canvas) {

     // resize canvas
     var tempCanvas = document.createElement('canvas');
     tempCanvas.width = canvas.width;
     tempCanvas.height = canvas.height;

     tempCanvas.getContext('2d').drawImage(canvas, 0, 0);

     // resize to width 400px
     canvas.width = tempCanvas.width / 3.2;
     canvas.height = tempCanvas.height / 3.2;

     // draw temp canvas back into canvas, scaled as needed
     canvas.getContext('2d').drawImage(tempCanvas, 0, 0, tempCanvas.width, tempCanvas.height, 0, 0,
     canvas.width, canvas.height);
     delete tempCanvas;

     var data = canvas.toDataURL('image/jpeg', 85);

     Ext.Ajax.request({
     url: '/admin/page/upload-screenshot',
     method: "post",
     params: {
     id: id,
     data: data
     },
     success: function () {
     if(typeof cb == "function") {
     cb();
     }
     }
     });
     }.bind(this, id));
     }*/
};

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
                        imageHtml += '<div class="thumb small"><img src="' + uriPrefix + thumbnails[i] + '" onload="this.parentNode.className += \' complete\';" /></div>';
                    }
                    imageHtml += '</div>';
                }

                var thumbnail = node.attributes.thumbnail;
                if(thumbnail) {
                    imageHtml = '<div class="thumb big"><img src="' + uriPrefix + thumbnail + '" onload="this.parentNode.className += \' complete\';" /></div>';
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
                    container.removeClass("hidden");
                    container.applyStyles(styles);
                }
            }.bind(this, node));

            el.on("mouseleave", function () {
                pimcore.helpers.treeNodeThumbnailPreviewHide();
            }.bind(this));
        }.bind(this, node), 200);
    }
};

pimcore.helpers.treeNodeThumbnailPreviewHide = function () {
    var container = Ext.get("pimcore_tree_preview");
    if(container) {
        container.addClass("hidden");
    }
}

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
            var menu = new Ext.menu.Menu({
                items: [{
                    text: t('close_others'),
                    iconCls: "",
                    handler: function (item) {
                        pimcore.helpers.closeAllElements(el);
                        // clear the opentab store, so that also non existing elements are flushed
                        pimcore.helpers.clearOpenTab();
                    }.bind(this)
                }, {
                    text: t('close_all'),
                    iconCls: "",
                    handler: function (item) {
                        pimcore.helpers.closeAllElements();
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
                }]
            });



            /*menu.add(new Ext.menu.Item({
             text: t('close_all'),
             iconCls: "",
             handler: function (item) {
             pimcore.helpers.closeAllElements();
             // clear the opentab store, so that also non existing elements are flushed
             pimcore.helpers.clearOpenTab();
             }.bind(this)
             }));*/

            menu.showAt(e.getXY());
            e.stopEvent();
        });
    }
};


