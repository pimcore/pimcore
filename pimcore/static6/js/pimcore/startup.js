/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    Ext.MessageBox.minPromptWidth = 500;

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

        if (!response.aborted && options["ignoreErrors"] !== true) {
            if (response.status == 503) {
                //show wait info
                if (!pimcore.maintenanceWindow) {
                    pimcore.maintenanceWindow = new Ext.Window({
                        closable: false,
                        title: t("please_wait"),
                        bodyStyle: "padding: 20px;",
                        html: t("the_system_is_in_maintenance_mode_please_wait"),
                        closeAction: "close",
                        modal: true
                    });
                    pimcore.viewport.add(pimcore.maintenanceWindow);
                    pimcore.maintenanceWindow.show();
                }
            } else {
                //do not remove notification, otherwise user is never informed about server exception (e.g. element cannot
                // be saved due to HTTP 500 Response)
                var date = new Date();
                var errorMessage = "Timestamp: " + date.toString() + "\n";

                try {
                    errorMessage += "Status: " + response.status + " | " + response.statusText + "\n";
                    errorMessage += "URL: " + options.url + "\n";
                    if (options["params"]) {
                        errorMessage += "Params:\n";
                        Ext.iterate(options.params, function (key, value) {
                            errorMessage += ( "-> " + key + ": " + value.substr(0, 500) + "\n");
                        });
                    }
                    if (options["method"]) {
                        errorMessage += "Method: " + options.method + "\n";
                    }
                    errorMessage += "Message: \n" + response.responseText;
                } catch (e) {
                    errorMessage += "\n\n";
                    errorMessage += response.responseText;
                }
                pimcore.helpers.showNotification(t("error"), t("error_general"), "error", errorMessage);
            }
        }

        xhrActive--;
        if (xhrActive < 1) {
            Ext.get("pimcore_loading").hide();
        }

    });
    Ext.Ajax.on("beforerequest", function () {
        if (xhrActive < 1) {
            Ext.get("pimcore_loading").show();
        }
        xhrActive++;
    });
    Ext.Ajax.on("requestcomplete", function (conn, response, options) {
        xhrActive--;
        if (xhrActive < 1) {
            Ext.get("pimcore_loading").hide();
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

    //translation admin keys
    pimcore.globalmanager.add("translations_admin_missing", new Array());
    pimcore.globalmanager.add("translations_admin_added", new Array());
    pimcore.globalmanager.add("translations_admin_translated_values", new Array());


    var objectClassFields = [
        {name:'id'},
        {name:'text', allowBlank:false},
        {name:"translatedText", convert:function (v, rec) {
            return ts(rec.data.text);
        }},
        {name:'icon'},
        {name:'group'},
        {name:"propertyVisibility"}
    ];

    Ext.define('pimcore.model.objecttypes', {
        extend: 'Ext.data.Model',
        fields: objectClassFields,
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


    // a store for filtered classes that can be created by the user
    Ext.define('pimcore.model.objecttypes.create', {
        extend: 'Ext.data.Model',
        fields: objectClassFields,
        proxy: {
            type: 'ajax',
            url:'/admin/class/get-tree?createAllowed=true',
            reader: {
                type: 'json'
            }
        }
    });

    var storeoc = new Ext.data.Store({
        model: 'pimcore.model.objecttypes.create',
        id:'object_types'
    });
    storeoc.load();

    pimcore.globalmanager.add("object_types_store_create", storeoc);

    pimcore.globalmanager.add("perspective", new pimcore.perspective(pimcore.settings.perspective));

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
    // check for devmode
    if (pimcore.settings.devmode) {
        Ext.get("pimcore_status_dev").show();
    }

    // check for debug
    if (pimcore.settings.debug) {
        Ext.get("pimcore_status_debug").show();
    }

    // check for maintenance
    if (!pimcore.settings.maintenance_active) {
        Ext.get("pimcore_status_maintenance").show();
    }

    //check for mail settings
    if (!pimcore.settings.mail) {
        Ext.get("pimcore_status_email").show();
    }

    // check for updates
    window.setTimeout(function () {
        var script = document.createElement("script");
        script.src = "https://www.pimcore.org/update/v2/statusbarUpdateCheck.php?revision=" + pimcore.settings.build;
        script.type = "text/javascript";
        Ext.query("body")[0].appendChild(script);
    }, 5000);


    // remove loading
    Ext.get("pimcore_loading").addCls("loaded");
    Ext.get("pimcore_loading").hide();

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
                    border:false,
                    items:[
                        Ext.create('Ext.panel.Panel',
                            {
                                region: 'west',
                                id:'pimcore_panel_tree_left',
                                split:true,
                                width:300,
                                minSize:175,
                                collapsible:true,
                                collapseMode: 'header',
                                animCollapse:false,
                                layout:'accordion',
                                layoutConfig:{
                                    animate:false
                                },
                                hidden: true,
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
                                        closeTabText: t("close_tab"),
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
                            width:300,
                            minSize:175,
                            collapsible:true,
                            collapseMode: 'header',
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
                "afterrender":function (el) {
                    Ext.get("pimcore_navigation").show();
                    Ext.get("pimcore_avatar").show();
                    Ext.get("pimcore_logout").show();

                    pimcore.helpers.initMenuTooltips();

                    var loadMask = new Ext.LoadMask(
                        {
                            target: Ext.getCmp("pimcore_viewport"),
                            msg:t("please_wait")
                        });
                    loadMask.enable();
                    pimcore.globalmanager.add("loadingmask", loadMask);


                    // prevent dropping files / folder outside the asset tree
                    var fn = function (e) {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'none';
                    };

                    el.getEl().dom.addEventListener("dragenter", fn, true);
                    el.getEl().dom.addEventListener("dragover", fn, true);
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


        var perspective = pimcore.globalmanager.get("perspective");
        var elementTree = perspective.getElementTree();

        for(var i = 0; i < elementTree.length; i++) {

            var treeConfig = elementTree[i];
            var type = treeConfig["type"];
            var side = treeConfig["position"] ? treeConfig["position"] : "left";
            var expanded = treeConfig["expanded"];
            var treepanel = null;
            var tree = null;

            switch (type) {
                case "documents":
                    if (user.isAllowed("documents") && !treeConfig.hidden) {
                        tree = new pimcore.document.tree(null, treeConfig);
                        pimcore.globalmanager.add("layout_document_tree", tree);
                        treepanel = Ext.getCmp("pimcore_panel_tree_" + side);
                        treepanel.setHidden(false);
                    }
                    break;
                case "assets":
                    if (user.isAllowed("assets") && !treeConfig.hidden) {
                        tree = new pimcore.asset.tree(null, treeConfig);
                        pimcore.globalmanager.add("layout_asset_tree", tree);
                        treepanel = Ext.getCmp("pimcore_panel_tree_" + side);
                        treepanel.setHidden(false);
                    }
                    break;
                case "objects":
                    if (user.isAllowed("objects")) {
                        if (!treeConfig.hidden) {
                            treepanel = Ext.getCmp("pimcore_panel_tree_" + side);
                            tree = new pimcore.object.tree(null, treeConfig);
                            pimcore.globalmanager.add("layout_object_tree", tree);
                            treepanel.setHidden(false);
                        }
                    }
                    break;
                case "customview":
                    if (!treeConfig.hidden) {
                        var treetype = treeConfig.treetype ? treeConfig.treetype : "object";
                        if (user.isAllowed(treetype + "s")) {
                            treepanel = Ext.getCmp("pimcore_panel_tree_" + side);

                            var treepanel = Ext.getCmp("pimcore_panel_tree_" + side);
                            var treeCls = window.pimcore[treetype].customviews.tree;

                            tree = new treeCls({
                                isCustomView: true,
                                customViewId: treeConfig.id,
                                allowedClasses: treeConfig.allowedClasses,
                                rootId: treeConfig.rootId,
                                rootVisible: treeConfig.showroot,
                                treeId: "pimcore_panel_tree_" + treetype + "_" + treeConfig.id,
                                treeIconCls: "pimcore_" + treetype + "_customview_icon_" + treeConfig.id,
                                treeTitle: ts(treeConfig.name),
                                parentPanel: treepanel,
                                loaderBaseParams: {}
                            }, treeConfig);
                            pimcore.globalmanager.add("layout_" + treetype + "_tree_" + treeConfig.id, tree);

                            treepanel.setHidden(false);
                        }
                    }
                    break;
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
        window.setTimeout(function () {
            layoutPortal = new pimcore.layout.portal();
            pimcore.globalmanager.add("layout_portal_welcome", layoutPortal);
        }, 1000);
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
        for (var i = 0; i < missingTranslations.length; i++) {
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
        url: "/admin/misc/ping",
        success: function (response) {

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
        failure: function (response) {
            if (response.status != 503) {
                pimcore.settings.showCloseConfirmation = false;
                window.location.href = "/admin/login/?session_expired=true&server_error=true";
            }
        }
    });
}, (pimcore.settings.session_gc_maxlifetime-60)*1000);

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
