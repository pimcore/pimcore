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

Ext.Loader.setConfig({
    enabled: true
});
Ext.enableAriaButtons = false;

Ext.Loader.setPath('Ext.ux', '/pimcore/static6/js/lib/ext/ux');

Ext.require([
    'Ext.button.Split',
    'Ext.container.Viewport',
    'Ext.data.JsonStore',
    'Ext.grid.column.Action',
    'Ext.grid.plugin.CellEditing',
    'Ext.form.field.ComboBox',
    'Ext.form.field.Hidden',
    'Ext.grid.column.Check',
    'Ext.grid.property.Grid',
    'Ext.form.field.Time',
    'Ext.form.FieldSet',
    'Ext.form.Label',
    'Ext.form.Panel',
    'Ext.grid.feature.Grouping',
    'Ext.grid.Panel',
    'Ext.grid.plugin.DragDrop',
    'Ext.layout.container.Accordion',
    'Ext.layout.container.Border',
    'Ext.tip.QuickTipManager',
    'Ext.tab.Panel',
    'Ext.toolbar.Paging',
    'Ext.toolbar.Spacer',
    'Ext.tree.plugin.TreeViewDragDrop',
    'Ext.tree.Panel',
    'Ext.ux.DataTip',
    'Ext.ux.form.MultiSelect',
    'Ext.ux.TabCloseMenu',
    'Ext.ux.TabReorderer',
    'Ext.window.Toast'
]);


Ext.onReady(function () {

    var StateFullProvider = Ext.extend(Ext.state.Provider, {
        namespace: "default",

        constructor : function(config){
            StateFullProvider.superclass.constructor.call(this);
            Ext.apply(this, config);

            var data = localStorage.getItem(this.namespace);
            if (!data) {
                this.state = {};
            } else {
                data = JSON.parse(data);
                if (data.state && data.user == pimcore.currentuser.id) {
                    this.state = data.state;
                } else {
                    this.state = {};
                }
            }
        },

        get : function(name, defaultValue){
            try {
                if (typeof this.state[name] == "undefined") {
                    return defaultValue
                } else {
                    return this.decodeValue(this.state[name])
                }
            } catch (e) {
                this.clear(name);
                return defaultValue;
            }
        },
        set : function(name, value){
            try {
                if (typeof value == "undefined" || value === null) {
                    this.clear(name);
                    return;
                }
                this.state[name] = this.encodeValue(value)

                var data = {
                    state: this.state,
                    user: pimcore.currentuser.id
                };
                var json = JSON.stringify(data);

                localStorage.setItem(this.namespace, json);
            } catch (e) {
                this.clear(name);
            }

            this.fireEvent("statechange", this, name, value);
        }
    });


    var provider = new StateFullProvider({
        namespace : "pimcore_ui_states_6"
    });

    Ext.state.Manager.setProvider(provider);

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

    Ext.QuickTips.init();

    Ext.Ajax.setDisableCaching(true);
    Ext.Ajax.setTimeout(900000);
    Ext.Ajax.setMethod("GET");
    Ext.Ajax.setDefaultHeaders({
        'X-pimcore-csrf-token': pimcore.settings["csrfToken"],
        'X-pimcore-extjs-version-major': Ext.getVersion().getMajor(),
        'X-pimcore-extjs-version-minor': Ext.getVersion().getMinor()
    });
    Ext.Ajax.on('requestexception', function (conn, response, options) {
        console.log("xhr request failed");

        if (response.status == 503) {
            //show wait info
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
                errorMessage += "\n\n";
                errorMessage += response.responseText;
            }
            pimcore.helpers.showNotification(t("error"), t("error_general"), "error", errorMessage);
        }

        xhrActive--;
        if (xhrActive < 1) {
            Ext.get("pimcore_logo").dom.innerHTML = '<img class="logo" src="/pimcore/static6/img/logo.png"/>';
        }

        });
    Ext.Ajax.on("beforerequest", function () {
        if (xhrActive < 1) {
            Ext.get("pimcore_logo").dom.innerHTML = '<img class="activity" src="/pimcore/static6/img/loading.gif"/>';
        }
        xhrActive++;
    });
    Ext.Ajax.on("requestcomplete", function (conn, response, options) {
        xhrActive--;
        if (xhrActive < 1) {
            Ext.get("pimcore_logo").dom.innerHTML = '<img class="logo" src="/pimcore/static6/img/logo.png"/>';
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

    var docTypesUrl = '/admin/document/doc-types?';
    // document types
    Ext.define('pimcore.model.doctypes', {
        extend: 'Ext.data.Model',
        fields: [
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
        ],
        proxy: {
            type: 'ajax',
            reader: {
                type: 'json',
                totalProperty:'total',
                successProperty:'success',
                rootProperty:'data'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            },
            api: {
                create  : docTypesUrl + "xaction=create",
                read    : docTypesUrl + "xaction=read",
                update  : docTypesUrl + "xaction=update",
                destroy : docTypesUrl + "xaction=destroy"
            }
        }
    });

    var store = new Ext.data.Store({
        id:'doctypes',
        model: 'pimcore.model.doctypes',
        remoteSort:false,
        autoSync: true,
        autoLoad: true
    });

    pimcore.globalmanager.add("document_types_store", store);
    pimcore.globalmanager.add("document_documenttype_store", ["page","snippet","email"]);

    //tranlsation admin keys
    pimcore.globalmanager.add("translations_admin_missing", new Array());
    pimcore.globalmanager.add("translations_admin_added", new Array());
    pimcore.globalmanager.add("translations_admin_translated_values", new Array());

    Ext.define('pimcore.model.objecttypes', {
        extend: 'Ext.data.Model',
        fields: [
            {name:'id'},
            {name:'text', allowBlank:false},
            {name:"translatedText", convert:function (v, rec) {
                return ts(rec.data.text);
            }},
            {name:'icon'},
            {name:"propertyVisibility"}
        ],
        proxy: {
            type: 'ajax',
            url:'/admin/class/get-tree',
            reader: {
                type: 'json'
            }
        }
    });

    var storeo = new Ext.data.Store({
        model: 'pimcore.model.objecttypes',
        id:'object_types'
    });
    storeo.load();

    pimcore.globalmanager.add("object_types_store", storeo);

    // current user
    pimcore.globalmanager.add("user", new pimcore.user(pimcore.currentuser));

    //pimcore languages
    Ext.define('pimcore.model.languages', {
        extend: 'Ext.data.Model',
        fields:  [
            {name:'language'},
            {name:'display'}
        ],
        proxy: {
            type: 'ajax',
            url:'/admin/settings/get-available-admin-languages',
            reader: {
                type: 'json'
            }
        }
    });


    var languageStore = new Ext.data.Store({
        model: "pimcore.model.languages"
    });
    languageStore.load();
    pimcore.globalmanager.add("pimcorelanguages", languageStore);

    Ext.define('pimcore.model.sites', {
        extend: 'Ext.data.Model',
        fields:  ["id", "domains", "rootId", "rootPath", "domain"],
        proxy: {
            type: 'ajax',
            url:'/admin/settings/get-available-sites',
            reader: {
                type: 'json'
            }
        }
    });

    var sitesStore = new Ext.data.Store({
        model: "pimcore.model.sites"
        //restful:false,
        //proxy:sitesProxy,
        //reader:sitesReader
    });
    sitesStore.load();
    pimcore.globalmanager.add("sites", sitesStore);

    // personas
    Ext.define('pimcore.model.personas', {
        extend: 'Ext.data.Model',
        fields: ["id", "text"]
    });

    // personas
    var personaStore = Ext.create('Ext.data.JsonStore', {
        model: "pimcore.model.personas",
        proxy: {
            type: 'ajax',
            url: '/admin/reports/targeting/persona-list/',
            reader: {
                type: 'json'
            }
        }
    });

    personaStore.load();
    pimcore.globalmanager.add("personas", personaStore);

    // STATUSBAR
    var statusbar = Ext.create('Ext.toolbar.Toolbar', {
        id: 'pimcore_statusbar',
        cls: 'pimcore_statusbar'
    });
    pimcore.globalmanager.add("statusbar", statusbar);

    // check for devmode
    if (pimcore.settings.devmode) {
        statusbar.add('<em class="fa fa-exclamation-triangle"></em> DEV-MODE');
        statusbar.add("-");
    }

    // check for debug
    if (pimcore.settings.debug) {
        statusbar.add('<em class="fa fa-exclamation-circle"></em> ' + t("debug_mode_on"));
        statusbar.add("-");
    }

    // check for maintenance
    if (!pimcore.settings.maintenance_active) {
        statusbar.add('<em class="fa fa-cog"></em> '
                + '<a href="http://www.pimcore.org/wiki/pages/viewpage.action?pageId=12124463" '
                + 'target="_blank">'
                + t("maintenance_not_active") + "</a>");
        statusbar.add("-");
    }

    //check for mail settings
    if (!pimcore.settings.mail) {
        statusbar.add('<em class="fa fa-envelope-o"></em> ' + t("mail_settings_incomplete"));
        statusbar.add("-");
    }

    statusbar.add("->");
    statusbar.add('Made with <em class="fa fa-heart-o"></em>&amp; <em class="fa fa-copyright"></em>by <a href="http://www.pimcore.org/" target="_blank" style="color:#fff;">'
                + 'pimcore GmbH</a> - Version: ' + pimcore.settings.version + " (Build: " + pimcore.settings.build + ")");



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

        pimcore.viewport = Ext.create('Ext.container.Viewport', {
            id:"pimcore_viewport",
            layout:'fit',
            items:[
                {
                    xtype:"panel",
                    id:"pimcore_body",
                    cls:"pimcore_body",
                    layout:"border",
                    bbar: statusbar,
                    border:false,
                    items:[
                        Ext.create('Ext.panel.Panel',
                        {
                            region: 'west',
                            id:'pimcore_panel_tree_left',
                            split:true,
                            width:250,
                            height: 300,
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
                        }
    )
                        ,
                        Ext.create('Ext.tab.Panel', {
                            region:'center',
                            deferredRender:false,
                            id: "pimcore_panel_tabs",
                            enableTabScroll:true,
                            hideMode:"offsets",
                            cls:"tab_panel",
                            plugins:
                                [
                                Ext.create('Ext.ux.TabCloseMenu', {
                                        pluginId: 'tabclosemenu',
                                        showCloseAll: false,
                                        showCloseOthers: false,
                                        extraItemsTail: pimcore.helpers.getMainTabMenuItems()
                                    }),
                                    Ext.create('Ext.ux.TabReorderer', {})
                                ]
                        })
                        ,
                        {
                            region:'east',
                            id:'pimcore_panel_tree_right',
                            cls: "pimcore_panel_tree",
                            split:true,
                            width:250,
                            minSize:175,
                            collapsible:true,
                            collapsed:false,
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
                    ]
                }
            ],
            listeners:{
                "afterrender":function () {
                    Ext.get("pimcore_logo").show();
                    Ext.get("pimcore_navigation").show();

                    var loadMask = new Ext.LoadMask(
                        {
                            target: Ext.getCmp("pimcore_viewport"),
                            msg:t("please_wait")
                        });
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

        //TODO comment in again
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

    pimcore.viewport.updateLayout();

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
                pimcore.viewport.updateLayout();
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
        pimcore.viewport.updateLayout();
    }
    catch (e) {
    }
};


// garbage collector
pimcore.helpers.unload = function () {

};
