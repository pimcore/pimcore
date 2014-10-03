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


// debug
if (typeof console == "undefined") {
    console = {
        log:function (v) {
        },
        dir:function (v) {
        },
        debug:function (v) {
        },
        info:function (v) {
        },
        warn:function (v) {
        },
        error:function (v) {
        },
        trace:function (v) {
        },
        group:function (v) {
        },
        groupEnd:function (v) {
        },
        time:function (v) {
        },
        timeEnd:function (v) {
        },
        profile:function (v) {
        },
        profileEnd:function (v) {
        }
    };
}

var layoutDocumentTree = null;
var layoutAssetTree = null;
var layoutObjectTree = null;
var xhrActive = 0; // number of active xhr requests

Ext.onReady(function () {

    // confirmation to close pimcore
    window.onbeforeunload = function () {

        // set this here as a global so that eg. the editmode can access this (edit::iframeOnbeforeunload()),
        // to prevent multiple warning messages to be shown
        pimcore.globalmanager.add("pimcore_reload_in_progress", true);

        if (!pimcore.settings.devmode) {
            // check for opened tabs and if the user has configured the warnings
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            var user = pimcore.globalmanager.get("user");
            if (pimcore.settings.showCloseConfirmation && tabPanel.items.getCount() > 0 && user["closeWarning"]) {
                return t("do_you_really_want_to_close_pimcore");
            }
        }
    };


    // define some globals
    Ext.chart.Chart.CHART_URL = '/pimcore/static/js/lib/ext/resources/charts.swf';
    Ext.QuickTips.init();
    Ext.Ajax.method = "GET";
    Ext.Ajax.disableCaching = true;
    Ext.Ajax.timeout = 900000;
    Ext.Ajax.defaultHeaders = {
        'X-pimcore-csrf-token': pimcore.settings["csrfToken"]
    };
    Ext.Ajax.on('requestexception', function (conn, response, options) {
        console.log("xhr request failed");

        if (response.status == 503) {
            // show wait info
            if (!pimcore.maintenanceWindow) {
                pimcore.maintenanceWindow = new Ext.Window({
                    closable:false,
                    title:t("please_wait"),
                    bodyStyle:"padding: 20px;",
                    html:t("the_system_is_in_maintenance_mode_please_wait"),
                    closeAction:"close",
                    modal:true
                });
                pimcore.viewport.add(pimcore.maintenanceWindow);
                pimcore.maintenanceWindow.show();
            }

        } else {
            //do not remove notification, otherwise user is never informed about server exception (e.g. element cannot
            // be saved due to HTTP 500 Response)
            var errorMessage = "";

            try {
                errorMessage = "Status: " + response.status + " | " + response.statusText + "\n";
                errorMessage += "URL: " + options.url + "\n";
                if(options["params"]) {
                    errorMessage += "Params:\n";
                    Ext.iterate(options.params, function (key, value) {
                        errorMessage += ( "-> " + key + ": " + value.substr(0,500) + "\n");
                    });
                }
                if(options["method"]) {
                    errorMessage += "Method: " + options.method + "\n";
                }
                errorMessage += "Message: \n" + response.responseText;
            } catch (e) {
                errorMessage = response.responseText;
            }
            pimcore.helpers.showNotification(t("error"), t("error_general"), "error", errorMessage);
        }

        xhrActive--;
        if (xhrActive < 1) {
            Ext.get("pimcore_logo").dom.innerHTML = '<img class="logo" src="/pimcore/static/img/logo.png"/>';
        }
    });
    Ext.Ajax.on("beforerequest", function () {
        if (xhrActive < 1) {
            Ext.get("pimcore_logo").dom.innerHTML = '<img class="activity" src="/pimcore/static/img/loading.gif"/>';
        }
        xhrActive++;
    });
    Ext.Ajax.on("requestcomplete", function (conn, response, options) {
        xhrActive--;
        if (xhrActive < 1) {
            Ext.get("pimcore_logo").dom.innerHTML = '<img class="logo" src="/pimcore/static/img/logo.png"/>';
        }

        // redirect to login-page if session is expired
        if (typeof response.getResponseHeader == "function") {
            if (response.getResponseHeader("X-Pimcore-Auth") == "required") {
                //pimcore.settings.showCloseConfirmation = false;
                //window.location.href = "/admin/login/?session_expired=true";

                var errorMessage = "";

                try {
                    errorMessage = "Status: " + response.status + " | " + response.statusText + "\n";
                    errorMessage += "URL: " + options.url + "\n";
                    if(options["params"]) {
                        errorMessage += "Params:\n";
                        Ext.iterate(options.params, function (key, value) {
                            errorMessage += ( "-> " + key + ": " + value + "\n");
                        });
                    }
                    if(options["method"]) {
                        errorMessage += "Method: " + options.method + "\n";
                    }
                    errorMessage += "Message: \n" + response.responseText;
                } catch (e) {
                    errorMessage = response.responseText;
                }

                pimcore.helpers.showNotification(t("session_error"), t("session_error_text"), "error", errorMessage);
            }
        }
    });


    // document types
    var proxy = new Ext.data.HttpProxy({
        url:'/admin/document/doc-types'
    });
    var reader = new Ext.data.JsonReader({
        totalProperty:'total',
        successProperty:'success',
        root:'data'
    }, [
        {name:'id'},
        {name:'name', allowBlank:false},
        {name:'module', allowBlank:true},
        {name:'controller', allowBlank:true},
        {name:'action', allowBlank:true},
        {name:'template', allowBlank:true},
        {name:'type', allowBlank:false},
        {name:'priority', allowBlank:true},
        {name: 'creationDate', allowBlank: true},
        {name: 'modificationDate', allowBlank: true}
    ]);
    var writer = new Ext.data.JsonWriter();
    var store = new Ext.data.Store({
        id:'doctypes',
        restful:false,
        proxy:proxy,
        reader:reader,
        writer:writer,
        remoteSort:false,
        listeners:{
            write:function (store, action, result, response, rs) {
            },
            save:function (store, batch, data) {
            }
        }
    });
    store.load();

    pimcore.globalmanager.add("document_types_store", store);
    pimcore.globalmanager.add("document_documenttype_store", ["page","snippet","email"]);

    //tranlsation admin keys
    pimcore.globalmanager.add("translations_admin_missing", new Array());
    pimcore.globalmanager.add("translations_admin_added", new Array());
    pimcore.globalmanager.add("translations_admin_translated_values", new Array());

    // classes
    var proxyo = new Ext.data.HttpProxy({
        url:'/admin/class/get-tree'
    });
    var readero = new Ext.data.JsonReader({
        totalProperty:'total',
        successProperty:'success',
        idProperty:'id'
    }, [
        {name:'id'},
        {name:'text', allowBlank:false},
        {name:"translatedText", convert:function (v, rec) {
            return ts(rec.text);
        }},
        {name:'icon'},
        {name:"propertyVisibility"}
    ]);
    var storeo = new Ext.data.Store({
        id:'object_types',
        restful:false,
        proxy:proxyo,
        reader:readero
    });
    storeo.load();

    pimcore.globalmanager.add("object_types_store", storeo);

    // current user
    pimcore.globalmanager.add("user", new pimcore.user(pimcore.currentuser));

    //pimcore languages
    var languageProxy = new Ext.data.HttpProxy({
        url:'/admin/settings/get-available-admin-languages'
    });
    var languageReader = new Ext.data.JsonReader({
        totalProperty:'total',
        successProperty:'success'
    }, [
        {name:'language'},
        {name:'display'}
    ]);

    var languageStore = new Ext.data.Store({
        restful:false,
        proxy:languageProxy,
        reader:languageReader
    });
    languageStore.load();
    pimcore.globalmanager.add("pimcorelanguages", languageStore);


    // sites
    var sitesProxy = new Ext.data.HttpProxy({
        url:'/admin/settings/get-available-sites'
    });
    var sitesReader = new Ext.data.JsonReader({
        totalProperty:'total',
        successProperty:'success'
    }, ["id", "domains", "rootId", "rootPath", "domain"]);

    var sitesStore = new Ext.data.Store({
        restful:false,
        proxy:sitesProxy,
        reader:sitesReader
    });
    sitesStore.load();
    pimcore.globalmanager.add("sites", sitesStore);

    // personas
    var personaStore = new Ext.data.JsonStore({
        url: "/admin/reports/targeting/persona-list/",
        fields: ["id", "text"]
    });
    personaStore.load();
    pimcore.globalmanager.add("personas", personaStore);

    // STATUSBAR
    var statusbar = new Ext.ux.StatusBar({
        id:'pimcore_statusbar',
        statusAlign:'right'
    });
    pimcore.globalmanager.add("statusbar", statusbar);

    // check for devmode
    if (pimcore.settings.devmode) {
        statusbar.add('<div class="pimcore_statusbar_devmode">DEV-MODE</div>');
        statusbar.add("-");
    }

    // check for debug
    if (pimcore.settings.debug) {
        statusbar.add('<div class="pimcore_statusbar_debug">' + t("debug_mode_on") + "</div>");
        statusbar.add("-");
    }
    // check for maintenance
    if (!pimcore.settings.maintenance_active) {
        statusbar.add('<div class="pimcore_statusbar_maintenance">'
                + '<a href="http://www.pimcore.org/wiki/pages/viewpage.action?pageId=12124463" '
                + 'target="_blank">'
                + t("maintenance_not_active") + "</a></div>");
        statusbar.add("-");
    }

    //check for mail settings
    if (!pimcore.settings.mail) {
        statusbar.add('<div class="pimcore_statusbar_mail">' + t("mail_settings_incomplete") + "</div>");
        statusbar.add("-");
    }

    // check for flash player
    if (!swfobject.hasFlashPlayerVersion("11")) {
        statusbar.add('<div class="pimcore_statusbar_flash">' + t("update_flash") + "</div>");
        statusbar.add("-");
    }

    statusbar.add("->");
    statusbar.add('&copy by <a href="http://www.pimcore.org/" target="_blank" style="color:#fff;">'
                + 'pimcore GmbH</a> - pimcore Version: ' + pimcore.settings.version + " (Build: " + pimcore.settings.build + ")");

    // check for updates
    window.setTimeout(function () {
        var script = document.createElement("script");
        script.src = "https://www.pimcore.org/update/v2/statusbarUpdateCheck.php?revision=" + pimcore.settings.build;
        script.type = "text/javascript";
        Ext.query("body")[0].appendChild(script);
    }, 5000);


    // remove loading
    Ext.get("pimcore_loading").remove();

    // init general layout
    try {
        var user = pimcore.globalmanager.get("user");

        pimcore.viewport = new Ext.Viewport({
            id:"pimcore_viewport",
            layout:'fit',
            items:[
                {
                    xtype:"panel",
                    id:"pimcore_body",
                    cls:"pimcore_body",
                    layout:"border",
                    border:false,
                    items:[
                        {
                            region:'west',
                            ctCls:"pimcore_body_inner",
                            id:'pimcore_panel_tree_left',
                            cls: "pimcore_panel_tree",
                            title: t("user") + ": " + user["name"],
                            split:true,
                            width:250,
                            minSize:175,
                            collapsible:true,
                            animCollapse:false,
                            layout:'accordion',
                            layoutConfig:{
                                animate:false
                            },
                            forceLayout:true,
                            hideMode:"offsets",
                            items:[]
                        },
                        new Ext.TabPanel({
                            region:'center',
                            deferredRender:false,
                            id:"pimcore_panel_tabs",
                            enableTabScroll:true,
                            hideMode:"offsets",
                            cls:"tab_panel",
                            listeners: {
                                add: pimcore.helpers.handleTabRightClick
                            }
                        }),
                        {
                            region:'east',
                            id:'pimcore_panel_tree_right',
                            cls: "pimcore_panel_tree",
                            split:true,
                            width:250,
                            minSize:175,
                            collapsible:true,
                            collapsed:true,
                            animCollapse:false,
                            layout:'accordion',
                            hidden:true,
                            layoutConfig:{
                                animate:false
                            },
                            forceLayout:true,
                            hideMode:"offsets",
                            items:[]
                        }
                    ],
                    bbar:statusbar
                }
            ],
            listeners:{
                "afterrender":function () {
                    Ext.get("pimcore_logo").show();
                    Ext.get("pimcore_navigation").show();

                    var loadMask = new Ext.LoadMask(Ext.getCmp("pimcore_viewport").getEl(), {msg:t("please_wait")});
                    loadMask.enable();
                    pimcore.globalmanager.add("loadingmask", loadMask);
                }
            }
        });


        // add sidebar panels

        if (user.memorizeTabs || pimcore.helpers.forceOpenMemorizedTabsOnce()) {
            // open previous opened tabs after the trees are ready
            pimcore.layout.treepanelmanager.addOnReadyCallback(function () {
                window.setTimeout(function () {
                    pimcore.helpers.openMemorizedTabs();
                }, 500);
            });
        }


        var treepanel = Ext.getCmp("pimcore_panel_tree_left");

        if (user.isAllowed("documents")) {
            layoutDocumentTree = new pimcore.document.tree();
            pimcore.globalmanager.add("layout_document_tree", layoutDocumentTree);
        }
        if (user.isAllowed("assets")) {
            layoutAssetTree = new pimcore.asset.tree();
            pimcore.globalmanager.add("layout_asset_tree", layoutAssetTree);
        }
        if (user.isAllowed("objects")) {
            layoutObjectTree = new pimcore.object.tree();
            pimcore.globalmanager.add("layout_object_tree", layoutObjectTree);

            // add custom views
            if (pimcore.settings.customviews) {
                if (pimcore.settings.customviews.length > 0) {
                    var cv;
                    var cvTree;
                    for (var cvs = 0; cvs < pimcore.settings.customviews.length; cvs++) {
                        cv = pimcore.settings.customviews[cvs];

                        cvTree = new pimcore.object.customviews.tree({
                            allowedClasses:cv.allowedClasses,
                            rootId:cv.rootId,
                            rootVisible:cv.showroot,
                            treeId:"pimcore_panel_tree_customviews_" + cv.id,
                            treeIconCls:"pimcore_object_customviews_icon_" + cv.id,
                            treeTitle:ts(cv.name),
                            parentPanel:Ext.getCmp("pimcore_panel_tree_left"),
                            index:(cvs + 10),
                            loaderBaseParams:{}
                        });
                    }
                }
            }
        }

    }
    catch (e) {
        console.log(e);
    }

    layoutToolbar = new pimcore.layout.toolbar();
    pimcore.globalmanager.add("layout_toolbar", layoutToolbar);


    // check for activated maintenance-mode with this session-id
    if (pimcore.settings.maintenance_mode) {
        pimcore.helpers.showMaintenanceDisableButton();
    }


    if (user.isAllowed("dashboards") && pimcore.globalmanager.get("user").welcomescreen) {
        layoutPortal = new pimcore.layout.portal();
        pimcore.globalmanager.add("layout_portal", layoutPortal);
    }

    pimcore.viewport.doLayout();

    // NOTE: the event pimcoreReady is fired in pimcore.layout.treepanelmanager
    pimcore.layout.treepanelmanager.startup();

    pimcore.helpers.registerKeyBindings(document);
});


pimcore["intervals"] = {};

//add missing translation keys
pimcore["intervals"]["translations_admin_missing"] = window.setInterval(function () {
    var missingTranslations = pimcore.globalmanager.get("translations_admin_missing");
    var addedTranslations = pimcore.globalmanager.get("translations_admin_added");
    if (missingTranslations.length > 0) {
        var params = Ext.encode(missingTranslations);
        for (i = 0; i < missingTranslations.length; i++) {
            addedTranslations.push(missingTranslations[i]);
        }
        pimcore.globalmanager.add("translations_admin_missing", new Array());
        Ext.Ajax.request({
            method:"post",
            url:"/admin/translation/add-admin-translation-keys",
            params:{keys:params}
        });
    }

}, 30000);

// session renew
pimcore["intervals"]["ping"] = window.setInterval(function () {
    Ext.Ajax.request({
        url:"/admin/misc/ping",
        success:function (response) {

            var data;

            try {
                data = Ext.decode(response.responseText);

                if (data.success != true) {
                    throw "session seems to be expired";
                }
            } catch (e) {
                data = false;
                pimcore.settings.showCloseConfirmation = false;
                window.location.href = "/admin/login/?session_expired=true";
            }

            if (pimcore.maintenanceWindow) {
                pimcore.maintenanceWindow.close();
                window.setTimeout(function () {
                    delete pimcore.maintenanceWindow;
                }, 2000);
                pimcore.viewport.doLayout();
            }

            if (data) {
                // here comes the check for maintenance mode, ...
            }
        },
        failure:function (response) {
            if (response.status != 503) {
                pimcore.settings.showCloseConfirmation = false;
                window.location.href = "/admin/login/?session_expired=true&server_error=true";
            }
        }
    });
}, 60000);


// refreshes the layout
pimcore.registerNS("pimcore.layout.refresh");
pimcore.layout.refresh = function () {
    try {
        pimcore.viewport.doLayout();
    }
    catch (e) {
    }
};


// garbage collector
pimcore.helpers.unload = function () {

};
