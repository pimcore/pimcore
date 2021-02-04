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


// debug
if (typeof console == "undefined") {
    console = {
        log: function (v) {
        },
        dir: function (v) {
        },
        debug: function (v) {
        },
        info: function (v) {
        },
        warn: function (v) {
        },
        error: function (v) {
        },
        trace: function (v) {
        },
        group: function (v) {
        },
        groupEnd: function (v) {
        },
        time: function (v) {
        },
        timeEnd: function (v) {
        },
        profile: function (v) {
        },
        profileEnd: function (v) {
        }
    };
}

var xhrActive = 0; // number of active xhr requests

Ext.Loader.setConfig({
    enabled: true
});
Ext.enableAriaButtons = false;

Ext.Loader.setPath('Ext.ux', '/bundles/pimcoreadmin/js/lib/ext/ux');

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
    'Ext.ux.colorpick.Field',
    'Ext.ux.colorpick.SliderAlpha',
    'Ext.ux.DataTip',
    'Ext.ux.form.MultiSelect',
    'Ext.ux.TabCloseMenu',
    'Ext.ux.TabReorderer',
    'Ext.ux.grid.SubTable',
    'Ext.window.Toast'
]);


Ext.onReady(function () {

    pimcore.helpers.colorpicker.initOverrides();

    var StateFullProvider = Ext.extend(Ext.state.Provider, {
        namespace: "default",

        constructor: function (config) {
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

        get: function (name, defaultValue) {
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
        set: function (name, value) {
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
        namespace: "pimcore_ui_states_6"
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

        var jsonData = null;
        try {
            jsonData = Ext.decode(response.responseText);
        } catch (e) {

        }

        var date = new Date();
        var errorMessage = "Timestamp: " + date.toString() + "\n";
        var errorDetailMessage = "\n" + response.responseText;

        try {
            errorMessage += "Status: " + response.status + " | " + response.statusText + "\n";
            errorMessage += "URL: " + options.url + "\n";

            if (options["params"] && options["params"].length > 0) {
                errorMessage += "Params:\n";
                Ext.iterate(options.params, function (key, value) {
                    errorMessage += ("-> " + key + ": " + value.substr(0, 500) + "\n");
                });
            }

            if (options["method"]) {
                errorMessage += "Method: " + options.method + "\n";
            }

            if(jsonData) {
                if (jsonData['message']) {
                    errorDetailMessage = jsonData['message'];
                }

                if(jsonData['traceString']) {
                    errorDetailMessage += "\nTrace: \n" + jsonData['traceString'];
                }
            }

            errorMessage += "Message: " + errorDetailMessage;
        } catch (e) {
            errorMessage += "\n\n";
            errorMessage += response.responseText;
        }

        if (!response.aborted && options["ignoreErrors"] !== true) {
            if (response.status === 503) {
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
            } else if(jsonData && jsonData['type'] === 'ValidationException') {
                pimcore.helpers.showNotification(t("validation_failed"), jsonData['message'], "error", errorMessage);
            } else if (response.status === 403) {
                pimcore.helpers.showNotification(t("access_denied"), t("access_denied_description"), "error");
            } else {
                var message = t("error_general");
                if(jsonData && jsonData['message']) {
                    message = jsonData['message'] + "<br><br>" + t("error_general");
                }

                pimcore.helpers.showNotification(t("error"), message, "error", errorMessage);
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

    var user = new pimcore.user(pimcore.currentuser);
    pimcore.globalmanager.add("user", user);

    // document types
    Ext.define('pimcore.model.doctypes', {
        extend: 'Ext.data.Model',
        fields: [
            'id',
            {name: 'name', allowBlank: false},
            {
                name: "translatedName",
                convert: function (v, rec) {
                    return t(rec.data.name);
                },
                depends : ['name']
            },
            'group',
            {
                name: "translatedGroup",
                convert: function (v, rec) {
                    if (rec.data.group) {
                        return t(rec.data.group);
                    }
                    return '';
                },
                depends : ['group']
            },
            'module',
            'controller',
            'action',
            'template',
            {name: 'type', allowBlank: false},
            'priority',
            'creationDate',
            'modificationDate'
        ],
        proxy: {
            type: 'ajax',
            reader: {
                type: 'json',
                totalProperty: 'total',
                successProperty: 'success',
                rootProperty: 'data'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            },
            api: {
                create: Routing.generate('pimcore_admin_document_document_doctypesget', {xaction: "create"}),
                read: Routing.generate('pimcore_admin_document_document_doctypesget', {xaction: "read"}),
                update: Routing.generate('pimcore_admin_document_document_doctypesget', {xaction: "update"}),
                destroy: Routing.generate('pimcore_admin_document_document_doctypesget', {xaction: "destroy"}),
            }
        }
    });

    if (user.isAllowed("documents") || user.isAllowed("users")) {
        var store = new Ext.data.Store({
            id: 'doctypes',
            model: 'pimcore.model.doctypes',
            remoteSort: false,
            autoSync: true,
            autoLoad: true
        });

        pimcore.globalmanager.add("document_types_store", store);
    }

    //translation admin keys
    pimcore.globalmanager.add("translations_admin_missing", []);
    pimcore.globalmanager.add("translations_admin_added", []);
    pimcore.globalmanager.add("translations_admin_translated_values", []);


    var objectClassFields = [
        {name: 'id'},
        {name: 'text', allowBlank: false},
        {
            name: "translatedText",
            convert: function (v, rec) {
                return t(rec.data.text);
            },
            depends : ['text']
        },
        {name: 'icon'},
        {name: 'group'},
        {
            name: "translatedGroup",
            convert: function (v, rec) {
                if (rec.data.group) {
                    return t(rec.data.group);
                }
                return '';
            },
            depends : ['group']
        },
        {name: "propertyVisibility"}
    ];

    Ext.define('pimcore.model.objecttypes', {
        extend: 'Ext.data.Model',
        fields: objectClassFields,
        proxy: {
            type: 'ajax',
            url: Routing.generate('pimcore_admin_dataobject_class_gettree'),
            reader: {
                type: 'json'
            }
        }
    });

    var storeo = new Ext.data.Store({
        model: 'pimcore.model.objecttypes',
        id: 'object_types'
    });
    storeo.load();

    pimcore.globalmanager.add("object_types_store", storeo);


    // a store for filtered classes that can be created by the user
    Ext.define('pimcore.model.objecttypes.create', {
        extend: 'Ext.data.Model',
        fields: objectClassFields,
        proxy: {
            type: 'ajax',
            url: Routing.generate('pimcore_admin_dataobject_class_gettree', {createAllowed: true}),
            reader: {
                type: 'json'
            }
        }
    });

    var storeoc = new Ext.data.Store({
        model: 'pimcore.model.objecttypes.create',
        id: 'object_types'
    });
    storeoc.load();

    pimcore.globalmanager.add("object_types_store_create", storeoc);

    pimcore.globalmanager.add("perspective", new pimcore.perspective(pimcore.settings.perspective));


    //pimcore languages
    Ext.define('pimcore.model.languages', {
        extend: 'Ext.data.Model',
        fields: [
            {name: 'language'},
            {name: 'display'}
        ],
        proxy: {
            type: 'ajax',
            url: Routing.generate('pimcore_admin_settings_getavailableadminlanguages'),
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
        fields: ["id", "domains", "rootId", "rootPath", "domain"],
        proxy: {
            type: 'ajax',
            url: Routing.generate('pimcore_admin_settings_getavailablesites'),
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

    // target groups
    Ext.define('pimcore.model.target_groups', {
        extend: 'Ext.data.Model',
        fields: ["id", "text"]
    });

    var targetGroupStore = Ext.create('Ext.data.JsonStore', {
        model: "pimcore.model.target_groups",
        proxy: {
            type: 'ajax',
            url: Routing.generate('pimcore_admin_targeting_targetgrouplist'),
            reader: {
                type: 'json'
            }
        }
    });

    targetGroupStore.load();
    pimcore.globalmanager.add("target_group_store", targetGroupStore);


    // check for updates
    window.setTimeout(function () {

        var domains = '';
        pimcore.globalmanager.get("sites").each(function (rec) {
            if(rec.get('rootId') !== 1) {
                if(!empty(rec.get('domain'))) {
                    domains += rec.get('domain') + ",";
                }
                if(!empty(rec.get('domains'))) {
                    domains += rec.get('domains') + ",";
                }
            }
        });

        // use vanilla javascript instead of ExtJS to bypass default error handling
        var request = new XMLHttpRequest();
        request.open('POST', "https://liveupdate.pimcore.org/update-check");

        request.onload = function() {
            if (this.status >= 200 && this.status < 400) {
                var data = Ext.decode(this.response);
                if (data.latestVersion) {
                    if (pimcore.currentuser.admin) {

                        pimcore.notification.helper.incrementCount();

                        var toolbar = pimcore.globalmanager.get("layout_toolbar");
                        toolbar.notificationMenu.add({
                            text: t("update_available"),
                            iconCls: "pimcore_icon_reload",
                            handler: function () {
                                var html = '<div class="pimcore_about_window" xmlns="http://www.w3.org/1999/html">';
                                html += '<h2 style="text-decoration: underline">New Version Available!</h2>';
                                html += '<br><b>Your Version: ' + pimcore.settings.version + '</b>';
                                html += '<br><b style="color: darkgreen;">New Version: ' + data.latestVersion + '</b>';
                                html += '<h3 style="color: darkred">Please update as soon as possible!</h3>';
                                html += '</div>';

                                var win = new Ext.Window({
                                    title: "New Version Available!",
                                    width: 500,
                                    height: 220,
                                    bodyStyle: "padding: 10px;",
                                    modal: true,
                                    html: html
                                });
                                win.show();
                            }
                        });
                    }
                }

                if (data.pushStatistics) {
                    var request = new XMLHttpRequest();
                    request.open('GET', Routing.generate('pimcore_admin_index_statistics'));

                    request.onload = function () {
                        if (this.status >= 200 && this.status < 400) {
                            var res = Ext.decode(this.response);

                            var request = new XMLHttpRequest();
                            request.open('POST', "https://liveupdate.pimcore.org/statistics");

                            var data = new FormData();
                            data.append('data', encodeURIComponent(JSON.stringify(res)));

                            request.send(data);
                        }
                    };
                    request.send(data);
                }
            }
        };

        var data = new FormData();
        data.append('id', pimcore.settings.instanceId);
        data.append('revision', pimcore.settings.build);
        data.append('version', pimcore.settings.version);
        data.append('debug', pimcore.settings.debug);
        data.append('devmode', pimcore.settings.devmode);
        data.append('environment', pimcore.settings.environment);
        data.append("language", pimcore.settings.language);
        data.append("main_domain", pimcore.settings.main_domain);
        data.append("domains", domains);
        data.append("timezone", pimcore.settings.timezone);
        data.append("websiteLanguages", pimcore.settings.websiteLanguages.join(','));

        request.send(data);

    }, 5000);


    // remove loading
    Ext.get("pimcore_loading").addCls("loaded");
    Ext.get("pimcore_loading").hide();
    Ext.get("pimcore_signet").show();

    // init general layout
    try {
        pimcore.viewport = Ext.create('Ext.container.Viewport', {
            id: "pimcore_viewport",
            layout: 'fit',
            items: [
                {
                    xtype: "panel",
                    id: "pimcore_body",
                    cls: "pimcore_body",
                    layout: "border",
                    border: false,
                    items: [
                        Ext.create('Ext.panel.Panel',
                            {
                                region: 'west',
                                id: 'pimcore_panel_tree_left',
                                cls: 'pimcore_main_accordion',
                                split: {
                                    cls: 'pimcore_main_splitter'
                                },
                                width: 300,
                                minSize: 175,
                                collapsible: true,
                                collapseMode: 'header',
                                defaults: {
                                    margin: '0'
                                },
                                layout: {
                                    type: 'accordion',
                                    hideCollapseTool: true,
                                    animate: false
                                },
                                header: false,
                                hidden: true,
                                forceLayout: true,
                                hideMode: "offsets",
                                items: []
                            }
                        )
                        ,
                        Ext.create('Ext.tab.Panel', {
                            region: 'center',
                            deferredRender: false,
                            id: "pimcore_panel_tabs",
                            enableTabScroll: true,
                            hideMode: "offsets",
                            cls: "tab_panel",
                            plugins:
                                [
                                    Ext.create('Ext.ux.TabCloseMenu', {
                                        pluginId: 'tabclosemenu',
                                        showCloseAll: false,
                                        closeTabText: t("close_tab"),
                                        showCloseOthers: false,
                                        extraItemsTail: pimcore.helpers.getMainTabMenuItems()
                                    }),
                                    Ext.create('Ext.ux.TabReorderer', {}),
                                    Ext.create('Ext.ux.TabMiddleButtonClose', {})
                                ]
                        })
                        ,
                        {
                            region: 'east',
                            id: 'pimcore_panel_tree_right',
                            cls: "pimcore_main_accordion",
                            split: {
                                cls: 'pimcore_main_splitter'
                            },
                            width: 300,
                            minSize: 175,
                            collapsible: true,
                            collapseMode: 'header',
                            defaults: {
                                margin: '0'
                            },
                            layout: {
                                type: 'accordion',
                                hideCollapseTool: true,
                                animate: false
                            },
                            header: false,
                            hidden: true,
                            forceLayout: true,
                            hideMode: "offsets",
                            items: []
                        }
                    ]
                }
            ],
            listeners: {
                "afterrender": function (el) {
                    Ext.get("pimcore_navigation").show();
                    Ext.get("pimcore_avatar").show();
                    Ext.get("pimcore_logout").show();

                    pimcore.helpers.initMenuTooltips();

                    var loadMask = new Ext.LoadMask(
                        {
                            target: Ext.getCmp("pimcore_viewport"),
                            msg: t("please_wait")
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

                    // open "My Profile" when clicking on avatar
                    Ext.get("pimcore_avatar").on("click", function (ev) {
                        pimcore.helpers.openProfile();
                    });

                    // check for latest news
                    let request = new XMLHttpRequest();
                    request.open('POST', "https://liveupdate.pimcore.org/news");
                    request.onload = function() {
                        if (this.status >= 200 && this.status < 400) {
                            let data = Ext.decode(this.response);
                            if(data && data['success'] === true) {
                                let timestamp = Math.ceil(new Date().getTime()/1000);
                                let localStorageMessageKey = "pimcore_news_" + data['messageId'];
                                let messageTimestamp = localStorage.getItem(localStorageMessageKey);
                                if(messageTimestamp === null || messageTimestamp < timestamp-data['messageInterval']) {
                                    localStorage.setItem(localStorageMessageKey, timestamp);
                                    if (data['frame']) {
                                        pimcore.helpers.openGenericIframeWindow('news', data['frame']['url'], data['frame']['icon'], t(data['frame']['title']));
                                    }

                                    if (data['box']) {
                                        Ext.applyIf(data['box'], {
                                            width: 500,
                                            height: 300,
                                            bodyStyle: "padding: 10px;",
                                            modal: false,
                                        });

                                        var win = new Ext.Window(data['box']);
                                        win.show();
                                    }
                                }
                            }
                        }
                    };

                    let data = new FormData();
                    data.append('id', pimcore.settings.instanceId);
                    data.append('revision', pimcore.settings.build);
                    data.append('version', pimcore.settings.version);
                    data.append('debug', pimcore.settings.debug);
                    data.append('devmode', pimcore.settings.devmode);
                    data.append('environment', pimcore.settings.environment);
                    data.append("language", pimcore.settings.language);
                    data.append("isAdmin", pimcore.currentuser.admin);
                    request.send(data);
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

        var locateConfigs = {
            document: [],
            asset: [],
            object: []
        };

        for (var i = 0; i < elementTree.length; i++) {

            var treeConfig = elementTree[i];
            var type = treeConfig["type"];
            var side = treeConfig["position"] ? treeConfig["position"] : "left";
            var treepanel = null;
            var tree = null;
            var treetype = null;

            var locateKey = "layout_" + type + "_locateintree_tree";

            switch (type) {
                case "documents":
                    if (user.isAllowed("documents") && !treeConfig.hidden) {
                        treetype = "document";
                        tree = new pimcore.document.tree(null, treeConfig);
                        pimcore.globalmanager.add("layout_document_tree", tree);
                        treepanel = Ext.getCmp("pimcore_panel_tree_" + side);
                        treepanel.setHidden(false);
                    }
                    break;
                case "assets":
                    if (user.isAllowed("assets") && !treeConfig.hidden) {
                        treetype = "asset";
                        tree = new pimcore.asset.tree(null, treeConfig);
                        pimcore.globalmanager.add("layout_asset_tree", tree);
                        treepanel = Ext.getCmp("pimcore_panel_tree_" + side);
                        treepanel.setHidden(false);
                    }
                    break;
                case "objects":
                    if (user.isAllowed("objects")) {
                        treetype = "object";
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
                        treetype = treeConfig.treetype ? treeConfig.treetype : "object";
                        locateKey = "layout_" + treetype + "s_locateintree_tree";

                        if (user.isAllowed(treetype + "s")) {
                            treepanel = Ext.getCmp("pimcore_panel_tree_" + side);

                            // Do not add pimcore_icon_material class to non-material icons
                            let iconTypeClass = '';
                            if (treeConfig.icon.match('flat-white')) {
                                iconTypeClass += 'pimcore_icon_material';
                            }

                            var treeCls = window.pimcore[treetype].customviews.tree;

                            tree = new treeCls({
                                isCustomView: true,
                                customViewId: treeConfig.id,
                                allowedClasses: treeConfig.allowedClasses,
                                rootId: treeConfig.rootId,
                                rootVisible: treeConfig.showroot,
                                treeId: "pimcore_panel_tree_" + treetype + "_" + treeConfig.id,
                                treeIconCls: "pimcore_" + treetype + "_customview_icon_" + treeConfig.id + " " + iconTypeClass,
                                treeTitle: t(treeConfig.name),
                                parentPanel: treepanel,
                                loaderBaseParams: {}
                            }, treeConfig);
                            pimcore.globalmanager.add("layout_" + treetype + "_tree_" + treeConfig.id, tree);

                            treepanel.setHidden(false);
                        }
                    }
                    break;
            }


            if (tree && treetype) {
                locateConfigs[treetype].push({
                    key: locateKey,
                    side: side,
                    tree: tree
                });
            }

        }
        pimcore.globalmanager.add("tree_locate_configs", locateConfigs);

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


    if(pimcore.settings.twoFactorSetupRequired) {
        Ext.Msg.show({
            title: t('setup_two_factor'),
            message: t('2fa_setup_message'),
            buttons: Ext.Msg.OK,
            icon: Ext.Msg.INFO,
            fn: function(btn) {
                pimcore.settings.profile.twoFactorSettings.prototype.openSetupWindow();
            }
        });
    }

    if(pimcore.currentuser.isPasswordReset) {
        pimcore.helpers.openProfile();
    }

    // Quick Search
    var quicksearchMap = new Ext.util.KeyMap({
        target: document,
        binding: [{
            key:  Ext.event.Event.ESC,
            fn: function () {
                pimcore.helpers.hideQuickSearch();
            }
        }, {
            key: Ext.event.Event.SPACE,
            ctrl: true,
            fn: function (keyCode, e) {
                e.stopEvent();
                pimcore.helpers.showQuickSearch();
            }
        }]
    });

    var quicksearchStore = new Ext.data.Store({
        proxy: {
            type: 'ajax',
            url: Routing.generate('pimcore_admin_searchadmin_search_quicksearch'),
            reader: {
                type: 'json',
                rootProperty: 'data'
            }
        },
        listeners: {
            "beforeload": function (store) {
                var previewEl = Ext.get('pimcore_quicksearch_preview');
                if(previewEl) {
                    previewEl.setHtml('');
                }

                store.getProxy().abort();
            }
        },
        fields: ["id", 'type', "subtype", "className", "fullpath"]
    });

    var quickSearchTpl = new Ext.XTemplate(
        '<tpl for=".">',
            '<li role="option" unselectable="on" class="x-boundlist-item">' +
                '<div class="list-icon {iconCls}"></div>' +
                '<div class="list-path" title="{fullpath}">{fullpathList}</div>' +
            '</li>',
        '</tpl>'
    );

    var quicksearchContainer = Ext.get('pimcore_quicksearch');
    var quickSearchCombo = Ext.create('Ext.form.ComboBox', {
        width: 900,
        hideTrigger: true,
        border: false,
        shadow: false,
        tpl: quickSearchTpl,
        listConfig: {
            shadow: false,
            border: false,
            cls: 'pimcore_quicksearch_picker',
            navigationModel: 'quicksearch.boundlist',
            listeners: {
                "highlightitem": function (view, node, opts) {
                    // we use getAttribute() here instead of dataset -> IE11 has some strange issues with that in this case
                    var record = quicksearchStore.getAt(node.getAttribute('data-recordIndex'));
                    var previewHtml = record.get('preview');
                    if(!previewHtml) {
                        previewHtml = '<div class="no_preview">' + t('preview_not_available') + '</div>';
                    }

                    Ext.get('pimcore_quicksearch_preview').setHtml(previewHtml);
                }
            }
        },
        id: 'quickSearchCombo',
        store: quicksearchStore,
        loadingText: t('searching'),
        queryDelay: 100,
        minChars: 4,
        renderTo: quicksearchContainer,
        enableKeyEvents: true,
        displayField: 'fullpath',
        valueField: "id",
        typeAhead: true,
        listeners: {
            "expand": function (combo) {
                if(!document.getElementById('pimcore_quicksearch_preview')) {
                    combo.getPicker().getEl().insertHtml('beforeEnd', '<div id="pimcore_quicksearch_preview"></div>');
                }
            },
            "keyup": function (field) {
                if(field.getValue()) {
                    quicksearchContainer.addCls('filled');
                }
            },
            "select": function (combo, record, index) {
                pimcore.helpers.openElement(record.get('id'), record.get('type'), record.get('subtype'));
                pimcore.helpers.hideQuickSearch();
            }
        }
    });

    Ext.getBody().on('click', function (event) {
        // hide on click outside
        if(quicksearchContainer && !quicksearchContainer.isAncestor(event.target)) {
            var pickerEl = quickSearchCombo.getPicker().getEl();
            if(!pickerEl || !pickerEl.isAncestor(event.target)) {
                pimcore.helpers.hideQuickSearch();
            }
        }
    });
});


pimcore["intervals"] = {};

//add missing translation keys
pimcore["intervals"]["translations_admin_missing"] = window.setInterval(function () {
    var missingTranslations = pimcore.globalmanager.get("translations_admin_missing");
    var addedTranslations = pimcore.globalmanager.get("translations_admin_added");
    if (missingTranslations.length > 0) {
        var thresholdIndex = 500;
        var arraySurpassing = missingTranslations.length > thresholdIndex;
        var sentTranslations = arraySurpassing ? missingTranslations.slice(0, thresholdIndex) : missingTranslations;
        var params = Ext.encode(sentTranslations);
        for (var i = 0; i < sentTranslations.length; i++) {
            var translation = sentTranslations[i];
            addedTranslations.push(translation);
        }
        var restMissingTranslations = missingTranslations.slice(thresholdIndex);
        pimcore.globalmanager.add("translations_admin_missing", restMissingTranslations);
        Ext.Ajax.request({
            method: "POST",
            url: Routing.generate('pimcore_admin_translation_addadmintranslationkeys'),
            params: {keys: params}
        });
    }
}, 30000);

// session renew
pimcore["intervals"]["ping"] = window.setInterval(function () {
    Ext.Ajax.request({
        url: Routing.generate('pimcore_admin_misc_ping'),
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
                window.location.href = Routing.generate('pimcore_admin_login', {session_expired: true});
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
                window.location.href = Routing.generate('pimcore_admin_login', {session_expired: true, server_error: true});

            }
        }
    });
}, (pimcore.settings.session_gc_maxlifetime - 60) * 1000);


pimcore["intervals"]["checkNewNotification"] = window.setInterval(function (elt) {
    pimcore.notification.helper.updateFromServer();
}, 30000);

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
