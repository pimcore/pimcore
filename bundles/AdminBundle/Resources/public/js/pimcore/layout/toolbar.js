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

pimcore.registerNS("pimcore.layout.toolbar");
pimcore.layout.toolbar = Class.create({

    initialize: function() {

        var user = pimcore.globalmanager.get("user");
        this.toolbar = Ext.getCmp("pimcore_panel_toolbar");

        var perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (perspectiveCfg.inToolbar("file")) {
            var fileItems = [];

            if (perspectiveCfg.inToolbar("file.perspectives")) {

                if (pimcore.settings.availablePerspectives.length > 1) {

                    var items = [];
                    for (var i = 0; i < pimcore.settings.availablePerspectives.length; i++) {
                        var perspective = pimcore.settings.availablePerspectives[i];
                        var itemCfg = {
                            text: t(perspective.name),
                            disabled: perspective.active,
                            handler: this.openPerspective.bind(this, perspective.name)
                        };

                        if (perspective.icon) {
                            itemCfg.icon = perspective.icon;
                        } else if (perspective.iconCls) {
                            itemCfg.iconCls = perspective.iconCls;
                        }

                        items.push(itemCfg);
                    }

                    this.perspectivesMenu = new Ext.menu.Item({
                        text: t("perspectives"),
                        iconCls: "pimcore_nav_icon_perspective",
                        hideOnClick: false,
                        menu: {
                            cls: "pimcore_navigation_flyout",
                            shadow: false,
                            items: items
                        }
                    });
                    fileItems.push(this.perspectivesMenu);
                }
            }


            if (user.isAllowed("dashboards") && perspectiveCfg.inToolbar("file.dashboards")) {
                this.dashboardMenu = new Ext.menu.Item({
                    text: t("dashboards"),
                    iconCls: "pimcore_nav_icon_dashboards",
                    hideOnClick: false,
                    menu: {
                        cls: "pimcore_navigation_flyout",
                        shadow: false,
                        items: [{
                            text: t("welcome"),
                            iconCls: "pimcore_nav_icon_dashboards",
                            handler: pimcore.helpers.openWelcomePage.bind(this)
                        }]
                    }
                });

                Ext.Ajax.request({
                    url: Routing.generate('pimcore_admin_portal_dashboardlist'),
                    success: function (response) {
                        var data = Ext.decode(response.responseText);
                        for (var i = 0; i < data.length; i++) {
                            this.dashboardMenu.menu.add(new Ext.menu.Item({
                                text: data[i],
                                iconCls: "pimcore_nav_icon_dashboards",
                                handler: function (key) {
                                    try {
                                        pimcore.globalmanager.get("layout_portal_" + key).activate();
                                    }
                                    catch (e) {
                                        pimcore.globalmanager.add("layout_portal_" + key, new pimcore.layout.portal(key));
                                    }
                                }.bind(this, data[i])
                            }));
                        }

                        this.dashboardMenu.menu.add(new Ext.menu.Separator({}));
                        this.dashboardMenu.menu.add({
                            text: t("add"),
                            iconCls: "pimcore_nav_icon_add",
                            handler: function () {
                                Ext.MessageBox.prompt(' ', t('enter_the_name_of_the_new_item'),
                                    function (button, value, object) {
                                        if (button == "ok") {
                                            Ext.Ajax.request({
                                                url: Routing.generate('pimcore_admin_portal_createdashboard'),
                                                method: 'POST',
                                                params: {
                                                    key: value
                                                },
                                                success: function (response) {
                                                    var response = Ext.decode(response.responseText);
                                                    if (response.success) {
                                                        Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                                                            if (buttonValue == "yes") {
                                                                window.location.reload();
                                                            }
                                                        });
                                                        try {
                                                            pimcore.globalmanager.get("layout_portal_" + value).activate();
                                                        }
                                                        catch (e) {
                                                            pimcore.globalmanager.add("layout_portal_" + value, new pimcore.layout.portal(value));
                                                        }
                                                    } else {
                                                        Ext.Msg.show({
                                                            title: t("error"),
                                                            msg: t(response.message),
                                                            buttons: Ext.Msg.OK,
                                                            animEl: 'elId',
                                                            icon: Ext.MessageBox.ERROR
                                                        });
                                                    }
                                                }
                                            });
                                        }
                                    }
                                );
                            }.bind(this)
                        });
                    }.bind(this)
                });

                fileItems.push(this.dashboardMenu);
            }


            if (user.isAllowed("documents") && perspectiveCfg.inToolbar("file.openDocument")) {
                fileItems.push({
                    text: t("open_document_by_id"),
                    iconCls: "pimcore_nav_icon_document pimcore_icon_overlay_go",
                    handler: pimcore.helpers.openElementByIdDialog.bind(this, "document")
                });
            }

            if (user.isAllowed("assets") && perspectiveCfg.inToolbar("file.openAsset")) {
                fileItems.push({
                    text: t("open_asset_by_id"),
                    iconCls: "pimcore_nav_icon_asset pimcore_icon_overlay_go",
                    handler: pimcore.helpers.openElementByIdDialog.bind(this, "asset")
                });
            }

            if (user.isAllowed("objects") && perspectiveCfg.inToolbar("file.openObject")) {
                fileItems.push({
                    text: t("open_data_object"),
                    iconCls: "pimcore_nav_icon_object pimcore_icon_overlay_go",
                    handler: pimcore.helpers.openElementByIdDialog.bind(this, "object")
                });
            }

            if (perspectiveCfg.inToolbar("file.searchReplace") && (user.isAllowed("objects") || user.isAllowed("documents") || user.isAllowed("assets"))) {
                fileItems.push({
                    text: t("search_replace_assignments"),
                    iconCls: "pimcore_nav_icon_search pimcore_icon_overlay_go",
                    handler: function () {
                        new pimcore.element.replace_assignments();
                    }
                });
            }

            if (perspectiveCfg.inToolbar("file.schedule") && (user.isAllowed("objects") || user.isAllowed("documents") || user.isAllowed("assets"))) {
                fileItems.push({
                    text: t('element_history'),
                    iconCls: "pimcore_nav_icon_history",
                    cls: "pimcore_main_menu",
                    handler: this.showElementHistory.bind(this)
                });
            }

            if (user.isAllowed("seemode") && perspectiveCfg.inToolbar("file.seemode")) {
                fileItems.push({
                    text: t("seemode"),
                    iconCls: "pimcore_nav_icon_seemode",
                    cls: "pimcore_main_menu",
                    handler: pimcore.helpers.openSeemode
                });
            }

            if (perspectiveCfg.inToolbar("file.closeAll")) {
                fileItems.push({
                    text: t("close_all_tabs"),
                    iconCls: "pimcore_nav_icon_close_all",
                    handler: this.closeAllTabs
                });
            }

            if (perspectiveCfg.inToolbar("file.help")) {
                // link to docs as major.minor.x
                var docsVersion = pimcore.settings.version.match(/^(\d+\.\d+)/);
                if (docsVersion) {
                    docsVersion = docsVersion[0] + '.x';
                } else {
                    docsVersion = 'latest';
                }

                fileItems.push({
                    text: t('help'),
                    iconCls: "pimcore_nav_icon_help",
                    cls: "pimcore_main_menu",
                    hideOnClick: false,
                    menu: {
                        cls: "pimcore_navigation_flyout",
                        shadow: false,
                        items: [{
                            text: t("documentation"),
                            iconCls: "pimcore_nav_icon_documentation",
                            handler: function () {
                                window.open("https://pimcore.com/docs/" + docsVersion);
                            }
                        },
                            {
                                text: t("report_bugs"),
                                iconCls: "pimcore_nav_icon_github",
                                handler: function () {
                                    window.open("https://github.com/pimcore/pimcore/issues");
                                }
                            }
                        ]
                    }
                });
            }


            if (perspectiveCfg.inToolbar("file.about")) {
                fileItems.push({
                    text: t("about_pimcore") + " &reg;",
                    iconCls: "pimcore_nav_icon_pimcore",
                    handler: function () {
                        pimcore.helpers.showAbout();
                    }
                });
            }

            this.fileMenu = new Ext.menu.Menu({
                items: fileItems,
                shadow: false,
                cls: "pimcore_navigation_flyout",
                listeners: {
                    "show": function (e) {
                        Ext.get('pimcore_menu_file').addCls('active');
                    },
                    "hide": function (e) {
                        Ext.get('pimcore_menu_file').removeCls('active');
                    }
                }
            });
        }

        if (perspectiveCfg.inToolbar("extras")) {

            var extrasItems = [];

            if (user.isAllowed("glossary") && perspectiveCfg.inToolbar("extras.glossary")) {
                extrasItems.push({
                    text: t("glossary"),
                    iconCls: "pimcore_nav_icon_glossary",
                    handler: this.editGlossary
                });
            }

            if (user.isAllowed("redirects") && perspectiveCfg.inToolbar("extras.redirects")) {
                extrasItems.push({
                    text: t("redirects"),
                    iconCls: "pimcore_nav_icon_redirects",
                    handler: this.editRedirects
                });
            }

            if (user.isAllowed("translations") && perspectiveCfg.inToolbar("extras.translations")) {
                extrasItems.push({
                    text: t("translations"),
                    iconCls: "pimcore_nav_icon_translations",
                    hideOnClick: false,
                    menu: {
                        cls: "pimcore_navigation_flyout",
                        shadow: false,
                        items: [{
                            text: t("shared_translations"),
                            iconCls: "pimcore_nav_icon_translations",
                            handler: this.editTranslations
                        }, {
                            text: "XLIFF " + t("export") + "/" + t("import"),
                            iconCls: "pimcore_nav_icon_translations",
                            handler: this.xliffImportExport
                        }, {
                            text: "Microsoft® Word " + t("export"),
                            iconCls: "pimcore_nav_icon_word_export",
                            handler: this.wordExport
                        }]
                    }
                });
            }

            if (user.isAllowed("recyclebin") && perspectiveCfg.inToolbar("extras.recyclebin")) {
                extrasItems.push({
                    text: t("recyclebin"),
                    iconCls: "pimcore_nav_icon_recyclebin",
                    handler: this.recyclebin
                });
            }

            if (user.isAllowed("plugins") && perspectiveCfg.inToolbar("extras.plugins")) {
                extrasItems.push({
                    text: t("bundles"),
                    iconCls: "pimcore_nav_icon_bundles",
                    handler: this.extensionAdmin
                });
            }

            if (user.isAllowed("notes_events") && perspectiveCfg.inToolbar("extras.notesEvents")) {
                extrasItems.push({
                    text: t('notes_events'),
                    iconCls: "pimcore_nav_icon_notes",
                    handler: this.notes
                });
            }

            if (user.isAllowed("application_logging")&& perspectiveCfg.inToolbar("extras.applicationlog")) {
                extrasItems.push({
                    text: t("log_applicationlog"),
                    iconCls: "pimcore_nav_icon_log_admin",
                    handler: this.logAdmin
                });
            }

            if(user.isAllowed("gdpr_data_extractor")&& perspectiveCfg.inToolbar("extras.gdpr_data_extractor")) {
                extrasItems.push({
                    text: t("gdpr_data_extractor"),
                    iconCls: "pimcore_nav_icon_gdpr",
                    handler: function() {
                        new pimcore.settings.gdpr.gdprPanel();
                    }
                });
            }

            if (extrasItems.length > 0) {
                extrasItems.push("-");
            }

            if (user.isAllowed("emails") && perspectiveCfg.inToolbar("extras.emails")) {
                extrasItems.push({
                    text: t("email"),
                    iconCls: "pimcore_nav_icon_email",
                    hideOnClick: false,
                    menu: {
                        cls: "pimcore_navigation_flyout",
                        shadow: false,
                        items: [{
                            text: t("email_logs"),
                            iconCls: "pimcore_nav_icon_email",
                            handler: this.sentEmailsLog
                        }, {
                            text: t("email_blacklist"),
                            iconCls: "pimcore_nav_icon_email",
                            handler: this.emailBlacklist
                        }, {
                            text: t("send_test_email"),
                            iconCls: "pimcore_nav_icon_email",
                            handler: this.sendTestEmail
                        }]
                    }
                });
            }

            if (user.admin) {
                if (perspectiveCfg.inToolbar("extras.maintenance")) {
                    extrasItems.push({
                        text: t("maintenance_mode"),
                        iconCls: "pimcore_nav_icon_maintenance",
                        handler: this.showMaintenance
                    });
                }

                if (perspectiveCfg.inToolbar("extras.systemtools")) {
                    var systemItems = [];

                    if (perspectiveCfg.inToolbar("extras.systemtools.phpinfo")) {
                        systemItems.push(
                            {
                                text: t("php_info"),
                                iconCls: "pimcore_nav_icon_php",
                                handler: this.showPhpInfo
                            }
                        );
                    }

                    if (perspectiveCfg.inToolbar("extras.systemtools.opcache")) {
                        systemItems.push(
                            {
                                text: t("php_opcache_status"),
                                iconCls: "pimcore_nav_icon_reports",
                                handler: this.showOpcacheStatus
                            }
                        );
                    }

                    if (perspectiveCfg.inToolbar("extras.systemtools.requirements")) {
                        systemItems.push(
                            {
                                text: t("system_requirements_check"),
                                iconCls: "pimcore_nav_icon_systemrequirements",
                                handler: this.showSystemRequirementsCheck
                            }
                        );
                    }

                    if (perspectiveCfg.inToolbar("extras.systemtools.serverinfo")) {
                        systemItems.push(
                            {
                                text: t("server_info"),
                                iconCls: "pimcore_nav_icon_server_info",
                                handler: this.showServerInfo
                            }
                        );
                    }

                    if (perspectiveCfg.inToolbar("extras.systemtools.database")) {
                        systemItems.push(
                            {
                                text: t("database_administration"),
                                iconCls: "pimcore_nav_icon_mysql",
                                handler: this.showAdminer
                            }
                        );
                    }

                    if (perspectiveCfg.inToolbar("extras.systemtools.fileexplorer")) {
                        systemItems.push(
                            {
                                text: t("server_fileexplorer"),
                                iconCls: "pimcore_nav_icon_fileexplorer",
                                handler: this.showFilexplorer
                            }
                        );
                    }

                    extrasItems.push({
                        text: t("system_infos_and_tools"),
                        iconCls: "pimcore_nav_icon_info",
                        hideOnClick: false,
                        menu: {
                            cls: "pimcore_navigation_flyout",
                            shadow: false,
                            items: systemItems
                        }
                    });
                }
            }


            if (extrasItems.length > 0) {
                this.extrasMenu = new Ext.menu.Menu({
                    items: extrasItems,
                    shadow: false,
                    cls: "pimcore_navigation_flyout",
                    listeners: {
                        "show": function (e) {
                            Ext.get('pimcore_menu_extras').addCls('active');
                        },
                        "hide": function (e) {
                            Ext.get('pimcore_menu_extras').removeCls('active');
                        }
                    }
                });
            }
        }

        if (perspectiveCfg.inToolbar("marketing")) {
            // marketing menu
            var marketingItems = [];

            if (user.isAllowed("reports") && perspectiveCfg.inToolbar("marketing.reports")) {
                marketingItems.push({
                    text: t("reports"),
                    iconCls: "pimcore_nav_icon_reports",
                    handler: this.showReports.bind(this, null)
                });
            }

            if (user.isAllowed("tag_snippet_management") && perspectiveCfg.inToolbar("marketing.tagmanagement")) {
                marketingItems.push({
                    text: t("tag_snippet_management"),
                    iconCls: "pimcore_nav_icon_tag",
                    handler: this.showTagManagement
                });
            }

            if (user.isAllowed("qr_codes")) {
                marketingItems.push({
                    text: t("qr_codes"),
                    iconCls: "pimcore_nav_icon_qrcode",
                    handler: this.showQRCode
                });
            }

            if (user.isAllowed("targeting") && perspectiveCfg.inToolbar("marketing.targeting")) {
                marketingItems.push({
                    text: t("personalization") + " / " + t("targeting"),
                    iconCls: "pimcore_nav_icon_usergroup",
                    hideOnClick: false,
                    menu: {
                        cls: "pimcore_navigation_flyout",
                        shadow: false,
                        items: [
                            {
                                text: t("global_targeting_rules"),
                                iconCls: "pimcore_nav_icon_targeting",
                                handler: this.showTargetingRules
                            }, {
                                text: t('target_groups'),
                                iconCls: "pimcore_nav_icon_target_groups",
                                handler: this.showTargetGroups
                            }, {
                                text: t("targeting_toolbar"),
                                iconCls: "pimcore_nav_icon_targeting_toolbar",
                                handler: this.showTargetingToolbarSettings
                            }
                        ]
                    }
                });
            }

            if (perspectiveCfg.inToolbar("marketing.seo")) {
                var seoMenu = [];

                if (user.isAllowed("documents") && user.isAllowed("seo_document_editor") && perspectiveCfg.inToolbar("marketing.seo.documents")) {
                    seoMenu.push({
                        text: t("seo_document_editor"),
                        iconCls: "pimcore_nav_icon_document_seo",
                        handler: this.showDocumentSeo
                    });
                }

                if (user.isAllowed("robots.txt") && perspectiveCfg.inToolbar("marketing.seo.robots")) {
                    seoMenu.push({
                        text: "robots.txt",
                        iconCls: "pimcore_nav_icon_robots",
                        handler: this.showRobotsTxt
                    });
                }

                if (user.isAllowed("http_errors") && perspectiveCfg.inToolbar("marketing.seo.httperrors")) {
                    seoMenu.push({
                        text: t("http_errors"),
                        iconCls: "pimcore_nav_icon_httperrorlog",
                        handler: this.showHttpErrorLog
                    });
                }

                if (seoMenu.length > 0) {
                    marketingItems.push({
                        text: t("search_engine_optimization"),
                        iconCls: "pimcore_nav_icon_seo",
                        hideOnClick: false,
                        menu: {
                            cls: "pimcore_navigation_flyout",
                            shadow: false,
                            items: seoMenu
                        }
                    });
                }
            }

            if (user.isAllowed("reports_config")) {
                if (perspectiveCfg.inToolbar("settings.customReports")) {
                    marketingItems.push({
                        text: t("custom_reports"),
                        iconCls: "pimcore_nav_icon_reports",
                        handler: this.showCustomReports
                    });
                }
            }

            if (user.isAllowed("reports") && user.isAllowed("system_settings")) {
                if (perspectiveCfg.inToolbar("settings.marketingReports")) {
                    marketingItems.push({
                        text: t("marketing_settings"),
                        iconCls: "pimcore_nav_icon_marketing_settings",
                        handler: this.reportSettings
                    });
                }
            }

            if (user.isAllowed("piwik_reports") && 'undefined' !== typeof pimcore.settings.piwik && pimcore.settings.piwik.iframe_configured) {
                marketingItems.push({
                    text: "Matomo / Piwik",
                    iconCls: "pimcore_nav_icon_matomo",
                    handler: (function() {
                        // create a promise which is resolved if the request succeeds
                        var promise = new Ext.Promise(function (resolve, reject) {
                            Ext.Ajax.request({
                                url: Routing.generate('pimcore_admin_reports_piwik_iframeintegration'),
                                ignoreErrors: true, // do not pop up error window on failure
                                success: function (response) {
                                    var data = {};

                                    try {
                                        data = Ext.decode(response.responseText);
                                    } catch (e) {
                                        reject(e);
                                        return;
                                    }

                                    if (data && data.configured && data.url) {
                                        resolve(data.url);
                                    }

                                    reject('Matomo iframe integration is not configured.');
                                },

                                failure: function(response) {
                                    try {
                                        var data = Ext.decode(response.responseText);
                                        if (data && data.message) {
                                            reject(data.message);
                                            return;
                                        }
                                    } catch (e) {}

                                    reject(response.responseText);
                                }
                            });
                        });

                        // the actual handler
                        return function () {
                            promise.then(
                                function (url) {
                                    // only open window after promise was resolved
                                    pimcore.helpers.openGenericIframeWindow(
                                        "piwik_iframe_integration",
                                        url,
                                        "pimcore_icon_matomo",
                                        "Matomo / Piwik"
                                    );
                                },
                                function (message) {
                                    if (message) {
                                        console.error(message);
                                    }
                                }
                            );
                        };
                    }())
                });
            }

            if (marketingItems.length > 0) {
                this.marketingMenu = new Ext.menu.Menu({
                    items: marketingItems,
                    shadow: false,
                    cls: "pimcore_navigation_flyout",
                    listeners: {
                        "show": function (e) {
                            Ext.get('pimcore_menu_marketing').addCls('active');
                        },
                        "hide": function (e) {
                            Ext.get('pimcore_menu_marketing').removeCls('active');
                        }
                    }
                });
            }
        }

        if (perspectiveCfg.inToolbar("settings")) {
            // settings menu
            var settingsItems = [];

            if (user.isAllowed("document_types") && perspectiveCfg.inToolbar("settings.documentTypes")) {
                settingsItems.push({
                    text: t("document_types"),
                    iconCls: "pimcore_nav_icon_doctypes",
                    handler: this.editDocumentTypes
                });
            }
            if (user.isAllowed("predefined_properties") && perspectiveCfg.inToolbar("settings.predefinedProperties")) {
                settingsItems.push({
                    text: t("predefined_properties"),
                    iconCls: "pimcore_nav_icon_properties",
                    handler: this.editProperties
                });
            }

            if (user.isAllowed("predefined_properties") && perspectiveCfg.inToolbar("settings.predefinedMetadata")) {
                settingsItems.push({
                    text: t("predefined_asset_metadata"),
                    iconCls: "pimcore_nav_icon_metadata",
                    handler: this.editPredefinedMetadata
                });
            }

            if (user.isAllowed("system_settings") && perspectiveCfg.inToolbar("settings.system")) {
                settingsItems.push({
                    text: t("system_settings"),
                    iconCls: "pimcore_nav_icon_system_settings",
                    handler: this.systemSettings
                });
            }

            if (user.isAllowed("website_settings") && perspectiveCfg.inToolbar("settings.website")) {
                settingsItems.push({
                    text: t("website_settings"),
                    iconCls: "pimcore_nav_icon_website_settings",
                    handler: this.websiteSettings
                });
            }

            if (user.isAllowed("web2print_settings") && perspectiveCfg.inToolbar("settings.web2print")) {
                settingsItems.push({
                    text: t("web2print_settings"),
                    iconCls: "pimcore_nav_icon_print_settings",
                    handler: this.web2printSettings
                });
            }

            if (user.isAllowed("users") && perspectiveCfg.inToolbar("settings.users")) {
                var userItems = [];

                if (perspectiveCfg.inToolbar("settings.users.users")) {
                    userItems.push(
                        {
                            text: t("users"),
                            handler: this.editUsers,
                            iconCls: "pimcore_nav_icon_users"
                        }
                    );
                }

                if (perspectiveCfg.inToolbar("settings.users.roles")) {
                    userItems.push(
                        {
                            text: t("roles"),
                            handler: this.editRoles,
                            iconCls: "pimcore_nav_icon_roles"
                        }
                    );
                }

                if (user.isAllowed("users")) {
                    userItems.push(
                        {
                            text: t("analyze_permissions"),
                            handler: function() {
                                var checker = new pimcore.element.permissionchecker();
                                checker.show();
                            }.bind(this),
                            iconCls: "pimcore_nav_icon_analyze_permissions"
                        }
                    );
                }

                if (userItems.length > 0) {
                    settingsItems.push({
                        text: t("users") + " / " + t("roles"),
                        iconCls: "pimcore_nav_icon_users",
                        hideOnClick: false,
                        menu: {
                            cls: "pimcore_navigation_flyout",
                            shadow: false,
                            items: userItems
                        }
                    });
                }
            }

            if (user.isAllowed("thumbnails") && perspectiveCfg.inToolbar("settings.thumbnails")) {
                settingsItems.push({
                    text: t("thumbnails"),
                    iconCls: "pimcore_nav_icon_thumbnails",
                    hideOnClick: false,
                    menu: {
                        cls: "pimcore_navigation_flyout",
                        shadow: false,
                        items: [{
                            text: t("image_thumbnails"),
                            iconCls: "pimcore_nav_icon_thumbnails",
                            handler: this.editThumbnails
                        }, {
                            text: t("video_thumbnails"),
                            iconCls: "pimcore_nav_icon_videothumbnails",
                            handler: this.editVideoThumbnails
                        }]
                    }
                });
            }

            if (user.isAllowed("objects") && perspectiveCfg.inToolbar("settings.objects")) {

                var objectMenu = {
                    text: t("data_objects"),
                    iconCls: "pimcore_nav_icon_object",
                    hideOnClick: false,
                    menu: {
                        cls: "pimcore_navigation_flyout",
                        shadow: false,
                        items: []
                    }
                };

                if (user.isAllowed("classes")) {
                    if (perspectiveCfg.inToolbar("settings.objects.classes")) {
                        objectMenu.menu.items.push({
                            text: t("classes"),
                            iconCls: "pimcore_nav_icon_class",
                            handler: this.editClasses
                        });
                    }

                    if (perspectiveCfg.inToolbar("settings.objects.fieldcollections")) {
                        objectMenu.menu.items.push({
                            text: t("field_collections"),
                            iconCls: "pimcore_nav_icon_fieldcollection",
                            handler: this.editFieldcollections
                        });
                    }

                    if (perspectiveCfg.inToolbar("settings.objects.objectbricks")) {
                        objectMenu.menu.items.push({
                            text: t("objectbricks"),
                            iconCls: "pimcore_nav_icon_objectbricks",
                            handler: this.editObjectBricks
                        });
                    }

                    if (perspectiveCfg.inToolbar("settings.objects.quantityValue")) {
                        objectMenu.menu.items.push({
                            text: t("quantityValue_field"),
                            iconCls: "pimcore_nav_icon_quantityValue",
                            cls: "pimcore_main_menu",
                            handler: function () {
                                try {
                                    pimcore.globalmanager.get("quantityValue_units").activate();
                                }
                                catch (e) {
                                    pimcore.globalmanager.add("quantityValue_units", new pimcore.object.quantityValue.unitsettings());
                                }
                            }
                        });
                    }

                    if (perspectiveCfg.inToolbar("settings.objects.classificationstore")) {
                        objectMenu.menu.items.push({
                            text: t("classification_store"),
                            iconCls: "pimcore_nav_icon_classificationstore",
                            handler: this.editClassificationStoreConfig
                        });
                    }

                    if (perspectiveCfg.inToolbar("settings.objects.bulkExport")) {
                        objectMenu.menu.items.push({
                            text: t("bulk_export"),
                            iconCls: "pimcore_nav_icon_export",
                            handler: this.bulkExport
                        });
                    }

                    if (perspectiveCfg.inToolbar("settings.objects.bulkImport")) {
                        objectMenu.menu.items.push({
                            text: t("bulk_import"),
                            iconCls: "pimcore_nav_icon_import",
                            handler: this.bulkImport.bind(this)
                        });
                    }


                    if (objectMenu.menu.items.length > 0) {
                        settingsItems.push(objectMenu);
                    }
                }
            }

            if (user.isAllowed("routes") && perspectiveCfg.inToolbar("settings.routes")) {
                settingsItems.push({
                    text: t("static_routes"),
                    iconCls: "pimcore_nav_icon_routes",
                    handler: this.editRoutes
                });
            }

            if (perspectiveCfg.inToolbar("settings.cache") && (user.isAllowed("clear_cache") || user.isAllowed("clear_temp_files") || user.isAllowed("clear_fullpage_cache"))) {

                var cacheItems = [];
                var cacheSubItems = [];

                if (user.isAllowed("clear_cache")) {

                    if (perspectiveCfg.inToolbar("settings.cache.clearAll")) {
                        cacheSubItems.push({
                            text: t("all_caches") + ' (Symfony + Data)',
                            iconCls: "pimcore_nav_icon_clear_cache",
                            handler: this.clearCache.bind(this, {'env[]': pimcore.settings['cached_environments']})
                        });
                    }

                    if (perspectiveCfg.inToolbar("settings.cache.clearData")) {
                        cacheSubItems.push({
                            text: t("data_cache"),
                            iconCls: "pimcore_nav_icon_clear_cache",
                            handler: this.clearCache.bind(this, {'only_pimcore_cache': true})
                        });
                    }

                    if (perspectiveCfg.inToolbar("settings.cache.clearSymfony")) {

                        pimcore.settings['cached_environments'].forEach(function(environment) {
                            cacheSubItems.push({
                                text: 'Symfony ' + t('environment') + ": " + environment,
                                iconCls: "pimcore_nav_icon_clear_cache",
                                handler: this.clearCache.bind(this, {
                                    'only_symfony_cache': true,
                                    'env[]': environment
                                })
                            });
                        }.bind(this));

                        cacheSubItems.push({
                            text: 'Symfony ' + t('environment') + ": " + t('all'),
                            iconCls: "pimcore_nav_icon_clear_cache",
                            handler: this.clearCache.bind(this, {'only_symfony_cache': true, 'env[]': pimcore.settings['cached_environments']})
                        });
                    }

                    cacheItems.push({
                        text: t("clear_cache"),
                        iconCls: "pimcore_nav_icon_clear_cache",
                        hideOnClick: false,
                        menu: {
                            cls: "pimcore_navigation_flyout",
                            shadow: false,
                            items: cacheSubItems
                        }
                    });
                }

                if (perspectiveCfg.inToolbar("settings.cache.clearOutput")) {
                    if (user.isAllowed("clear_fullpage_cache")) {
                        cacheItems.push({
                            text: t("clear_full_page_cache"),
                            iconCls: "pimcore_nav_icon_clear_cache",
                            handler: this.clearOutputCache
                        });
                    }
                }

                if (perspectiveCfg.inToolbar("settings.cache.clearTemp")) {
                    if (user.isAllowed("clear_temp_files")) {
                        cacheItems.push({
                            text: t("clear_temporary_files"),
                            iconCls: "pimcore_nav_icon_clear_cache",
                            handler: this.clearTemporaryFiles
                        });
                    }
                }

                if (perspectiveCfg.inToolbar("settings.cache.generatePreviews")) {
                    if (pimcore.settings.document_generatepreviews && pimcore.settings.htmltoimage) {
                        cacheItems.push({
                            text: t("generate_page_previews"),
                            iconCls: "pimcore_nav_icon_page_previews",
                            handler: this.generatePagePreviews
                        });
                    }
                }


                if (cacheItems.length > 0) {
                    var cacheMenu = {
                        text: t("cache"),
                        iconCls: "pimcore_nav_icon_clear_cache",
                        hideOnClick: false,
                        menu: {
                            cls: "pimcore_navigation_flyout",
                            shadow: false,
                            items: cacheItems
                        }
                    };

                    settingsItems.push(cacheMenu);
                }
            }

            // admin translations only for admins
            if (user.isAllowed('admin_translations')) {
                if (perspectiveCfg.inToolbar("settings.adminTranslations")) {
                    settingsItems.push({
                        text: t("admin_translations"),
                        iconCls: "pimcore_nav_icon_translations",
                        handler: this.editTranslationsSpecific
                    });
                }
            }

            // tags for elements
            if (user.isAllowed("tags_configuration") && perspectiveCfg.inToolbar("settings.tagConfiguration")) {
                settingsItems.push({
                    text: t("element_tag_configuration"),
                    iconCls: "pimcore_nav_icon_element_tags",
                    handler: this.showTagConfiguration
                });
            }

            if (user.admin) {
                settingsItems.push({
                    iconCls: "pimcore_nav_icon_icons",
                    text: t('icon_library'),
                    handler: function() {
                        pimcore.helpers.openGenericIframeWindow("icon-library", Routing.generate('pimcore_admin_misc_iconlist'), "pimcore_icon_icons", t("icon_library"));
                    }
                });
            }

            // help menu
            if (settingsItems.length > 0) {
                this.settingsMenu = new Ext.menu.Menu({
                    items: settingsItems,
                    shadow: false,
                    cls: "pimcore_navigation_flyout",
                    listeners: {
                        "show": function (e) {
                            Ext.get('pimcore_menu_settings').addCls('active');
                        },
                        "hide": function (e) {
                            Ext.get('pimcore_menu_settings').removeCls('active');
                        }
                    }
                });
            }
        }


        // search menu

        if (perspectiveCfg.inToolbar("search")) {
            var searchItems = [];

            if ((user.isAllowed("documents") || user.isAllowed("assets") || user.isAllowed("objects")) && perspectiveCfg.inToolbar("search.quickSearch")) {
                searchItems.push({
                    text: t("quicksearch"),
                    iconCls: "pimcore_nav_icon_quicksearch",
                    handler: function () {
                        pimcore.helpers.showQuickSearch();
                    }
                });
                searchItems.push('-');
            }

            var searchAction = function (type) {
                pimcore.helpers.itemselector(false, function (selection) {
                        pimcore.helpers.openElement(selection.id, selection.type, selection.subtype);
                    }, {type: [type]},
                    {
                        asTab: true,
                        context: {
                            scope: "globalSearch"
                        }
                    });
            };

            if (user.isAllowed("documents") && perspectiveCfg.inToolbar("search.documents")) {
                searchItems.push({
                    text: t("documents"),
                    iconCls: "pimcore_nav_icon_document",
                    handler: searchAction.bind(this, "document")
                });
            }

            if (user.isAllowed("assets") && perspectiveCfg.inToolbar("search.assets")) {
                searchItems.push({
                    text: t("assets"),
                    iconCls: "pimcore_nav_icon_asset",
                    handler: searchAction.bind(this, "asset")
                });
            }

            if (user.isAllowed("objects") && perspectiveCfg.inToolbar("search.objects")) {
                searchItems.push({
                    text: t("data_objects"),
                    iconCls: "pimcore_nav_icon_object",
                    handler: searchAction.bind(this, "object")
                });
            }

            if (searchItems.length > 0) {
                this.searchMenu = new Ext.menu.Menu({
                    items: searchItems,
                    shadow: false,
                    cls: "pimcore_navigation_flyout",
                    listeners: {
                        "show": function (e) {
                            Ext.get('pimcore_menu_search').addCls('active');
                        },
                        "hide": function (e) {
                            Ext.get('pimcore_menu_search').removeCls('active');
                        }
                    }
                });
            }
        }

        // notifications
        if (user.isAllowed("notifications")) {
            var notificationItems = [{
                text: t("notifications"),
                iconCls: "pimcore_nav_icon_notifications",
                handler: this.showNotificationTab.bind(this)
            }];

            if(user.isAllowed('notifications_send')) {
                notificationItems.push({
                    text: t("notifications_send"),
                    iconCls: "pimcore_nav_icon_notifications_sent",
                    id: "notifications_new",
                    handler: this.showNotificationModal.bind(this)
                });
            }

            notificationItems.push('-');

            // check for devmode
            if (pimcore.settings.devmode) {
                notificationItems.push({
                    text: t("DEV MODE"),
                    iconCls: "pimcore_nav_icon_dev_mode"
                });
                pimcore.notification.helper.incrementCount();
            }

            // check for debug
            if (pimcore.settings.debug) {
                notificationItems.push({
                    text: t("debug_mode_on"),
                    iconCls: "pimcore_nav_icon_debug_mode"
                });
                pimcore.notification.helper.incrementCount();
            }

            // check for maintenance
            if (!pimcore.settings.maintenance_active) {
                notificationItems.push({
                    text: t("maintenance_not_active"),
                    iconCls: "pimcore_nav_icon_maintenance",
                    handler: function () {
                        window.open('https://pimcore.com/docs/6.x/Development_Documentation/Getting_Started/Installation.html#page_5-Maintenance-Cron-Job');
                    }
                });
                pimcore.notification.helper.incrementCount();
            }

            //check for mail settings
            if (!pimcore.settings.mail) {
                notificationItems.push({
                    text: t("mail_settings_incomplete"),
                    iconCls: "pimcore_nav_icon_email",
                    handler: function () {
                        window.open('https://pimcore.com/docs/6.x/Development_Documentation/Tools_and_Features/System_Settings.html#page_E-Mail-Settings');
                    }
                });
                pimcore.notification.helper.incrementCount();
            }

            this.notificationMenu = new Ext.menu.Menu({
                items: notificationItems,
                cls: "pimcore_navigation_flyout"
            });
        }


        if (this.fileMenu) {
            Ext.get("pimcore_menu_file").on("mousedown", this.showSubMenu.bind(this.fileMenu));
        }
        if (this.extrasMenu) {
            Ext.get("pimcore_menu_extras").on("mousedown", this.showSubMenu.bind(this.extrasMenu));
        }
        if (this.marketingMenu) {
            Ext.get("pimcore_menu_marketing").on("mousedown", this.showSubMenu.bind(this.marketingMenu));
        }
        if (this.settingsMenu) {
            Ext.get("pimcore_menu_settings").on("mousedown", this.showSubMenu.bind(this.settingsMenu));
        }
        if (this.searchMenu) {
            Ext.get("pimcore_menu_search").on("mousedown", this.showSubMenu.bind(this.searchMenu));
        }
        if (this.notificationMenu) {
            Ext.get('pimcore_notification').show();
            Ext.get("pimcore_notification").on("mousedown", this.showSubMenu.bind(this.notificationMenu));
            pimcore.notification.helper.updateFromServer();
        }

        Ext.each(Ext.query(".pimcore_menu_item"), function (el) {
            el = Ext.get(el);

            if (el) {
                var menuVariable = el.id.replace(/pimcore_menu_/, "") + "Menu";
                if (el.hasCls("pimcore_menu_needs_children")) {
                    if (!this[menuVariable]) {
                        el.setStyle("display", "none");
                    }
                }

                el.on("mouseenter", function () {
                    if (Ext.menu.MenuMgr.hideAll()) {
                        var offsets = el.getOffsetsTo(Ext.getBody());
                        offsets[0] = 60;
                        var menu = this[menuVariable];
                        if (menu) {
                            menu.showAt(offsets);
                        }
                    }
                }.bind(this));
            } else {
                console.error("no pimcore_menu_item");
            }
        }.bind(this));

        return;
    },

    showSubMenu: function (e) {
        if(this.hidden) {
            e.stopEvent();
            var el = Ext.get(e.currentTarget);
            var offsets = el.getOffsetsTo(Ext.getBody());
            offsets[0] = 60;
            this.showAt(offsets);
        } else {
            this.hide();
        }
    },

    closeAllTabs: function () {
        pimcore.helpers.closeAllTabs();
    },

    editDocumentTypes: function () {

        try {
            pimcore.globalmanager.get("document_types").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("document_types", new pimcore.settings.document.doctypes());
        }
    },

    editProperties: function () {

        try {
            pimcore.globalmanager.get("predefined_properties").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("predefined_properties", new pimcore.settings.properties.predefined());
        }
    },


    editPredefinedMetadata: function () {

        try {
            pimcore.globalmanager.get("predefined_metadata").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("predefined_metadata", new pimcore.settings.metadata.predefined());
        }
    },

    recyclebin: function () {
        try {
            pimcore.globalmanager.get("recyclebin").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("recyclebin", new pimcore.settings.recyclebin());
        }
    },

    editUsers: function () {
        pimcore.helpers.showUser();
    },

    editRoles: function () {

        try {
            pimcore.globalmanager.get("roles").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("roles", new pimcore.settings.user.role.panel());
        }
    },

    editThumbnails: function () {
        try {
            pimcore.globalmanager.get("thumbnails").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("thumbnails", new pimcore.settings.thumbnail.panel());
        }
    },

    editVideoThumbnails: function () {
        try {
            pimcore.globalmanager.get("videothumbnails").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("videothumbnails", new pimcore.settings.videothumbnail.panel());
        }
    },

    editTranslations: function () {
        pimcore.plugin.broker.fireEvent("preEditTranslations", this, "website");
        try {
            pimcore.globalmanager.get("translationwebsitemanager").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("translationwebsitemanager", new pimcore.settings.translation.website());
        }
    },

    editTranslationsSpecific: function () {
        pimcore.plugin.broker.fireEvent("preEditTranslations", this, "admin");
        try {
            pimcore.globalmanager.get("translationadminmanager").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("translationadminmanager", new pimcore.settings.translation.admin());
        }
    },

    editRoutes: function () {

        try {
            pimcore.globalmanager.get("staticroutes").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("staticroutes", new pimcore.settings.staticroutes());
        }
    },


    editRedirects: function () {

        try {
            pimcore.globalmanager.get("redirects").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("redirects", new pimcore.settings.redirects());
        }
    },

    openPerspective: function(name) {
        location.href = Routing.generate('pimcore_admin_index', {perspective: name});
    },

    generatePagePreviews: function ()  {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_page_getlist'),
            success: function (res) {
                var data = Ext.decode(res.responseText);
                if(data && data.success) {
                    var items = data.data;
                    var totalItems = items.length;

                    var progressBar = new Ext.ProgressBar({
                        text: t('initializing')
                    });

                    var progressWin = new Ext.Window({
                        title: t("generate_page_previews"),
                        layout:'fit',
                        width:200,
                        bodyStyle: "padding: 10px;",
                        closable:false,
                        plain: true,
                        items: [progressBar],
                        listeners: pimcore.helpers.getProgressWindowListeners()
                    });

                    progressWin.show();

                    var generate = function () {
                        if(items.length > 1) {
                            var next = items.shift();

                            var date = new Date();
                            var path = next.path + "?pimcore_preview=true&time=" + date.getTime();

                            pimcore.helpers.generatePagePreview(next.id, path, function () {
                                generate();
                            });

                            var status = (totalItems-items.length) / totalItems;
                            progressBar.updateProgress(status, (Math.ceil(status*100) + "%"));
                        } else {
                            progressWin.close();
                        }
                    };

                    generate();
                }
            }
        });
    },

    sendTestEmail: function () {
        pimcore.helpers.sendTestEmail(pimcore.settings.mailDefaultAddress);
    },

    showReports: function (reportClass, reportConfig) {
        try {
            pimcore.globalmanager.get("reports").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("reports", new pimcore.report.panel());
        }

        // this is for generated/configured reports like the SQL Report
        try {
            if(reportClass) {
                pimcore.globalmanager.get("reports").openReportViaToolbar(reportClass, reportConfig);
            }
        } catch (e) {
            console.log(e);
        }
    },

    showTagManagement: function () {
        try {
            pimcore.globalmanager.get("tagmanagement").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("tagmanagement", new pimcore.settings.tagmanagement.panel());
        }
    },

    showQRCode: function () {
        try {
            pimcore.globalmanager.get("qrcode").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("qrcode", new pimcore.report.qrcode.panel());
        }
    },

    showCustomReports: function () {
        try {
            pimcore.globalmanager.get("custom_reports_settings").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("custom_reports_settings", new pimcore.report.custom.settings());
        }
    },

    showTargetingRules: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        try {
            tabPanel.setActiveTab(pimcore.globalmanager.get("targeting").getLayout());
        }
        catch (e) {
            var targeting = new pimcore.settings.targeting.rules.panel();
            pimcore.globalmanager.add("targeting", targeting);

            tabPanel.add(targeting.getLayout());
            tabPanel.setActiveTab(targeting.getLayout());

            targeting.getLayout().on("destroy", function () {
                pimcore.globalmanager.remove("targeting");
            }.bind(this));

            pimcore.layout.refresh();
        }
    },

    showTargetGroups: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        try {
            tabPanel.setActiveTab(pimcore.globalmanager.get("targetGroupsPanel").getLayout());
        }
        catch (e) {
            var targetGroups = new pimcore.settings.targeting.targetGroups.panel();
            pimcore.globalmanager.add("targetGroupsPanel", targetGroups);

            tabPanel.add(targetGroups.getLayout());
            tabPanel.setActiveTab(targetGroups.getLayout());

            targetGroups.getLayout().on("destroy", function () {
                pimcore.globalmanager.remove("targetGroupsPanel");
            }.bind(this));

            pimcore.layout.refresh();
        }
    },

    showTargetingToolbarSettings: function () {
        new pimcore.settings.targetingToolbar();
    },

    notes: function () {
        try {
            pimcore.globalmanager.get("notes").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("notes", new pimcore.element.notes());
        }
    },

    editGlossary: function () {

        try {
            pimcore.globalmanager.get("glossary").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("glossary", new pimcore.settings.glossary());
        }
    },

    systemSettings: function () {

        try {
            pimcore.globalmanager.get("settings_system").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("settings_system", new pimcore.settings.system());
        }
    },

    websiteSettings: function () {

        try {
            pimcore.globalmanager.get("settings_website").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("settings_website", new pimcore.settings.website());
        }
    },

    reportSettings: function () {

        try {
            pimcore.globalmanager.get("reports_settings").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("reports_settings", new pimcore.report.settings());
        }
    },

    web2printSettings: function () {

        try {
            pimcore.globalmanager.get("settings_web2print").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("settings_web2print", new pimcore.settings.web2print());
        }
    },

    editClassificationStoreConfig: function () {
        try {
            pimcore.globalmanager.get("classificationstore_config").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("classificationstore_config", new pimcore.object.classificationstore.storeTree());
        }
    },

    editClasses: function () {
        try {
            pimcore.globalmanager.get("classes").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("classes", new pimcore.object.klass());
        }
    },

    editFieldcollections: function () {
        try {
            pimcore.globalmanager.get("fieldcollections").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("fieldcollections", new pimcore.object.fieldcollection());
        }
    },

    editObjectBricks: function () {
        try {
            pimcore.globalmanager.get("objectbricks").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("objectbricks", new pimcore.object.objectbrick());
        }
    },

    showDocumentSeo: function () {
        try {
            pimcore.globalmanager.get("document_seopanel").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("document_seopanel", new pimcore.document.seopanel());
        }
    },

    showRobotsTxt: function () {
        try {
            pimcore.globalmanager.get("robotstxt").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("robotstxt", new pimcore.settings.robotstxt());
        }
    },

    showHttpErrorLog: function () {
        try {
            pimcore.globalmanager.get("http_error_log").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("http_error_log", new pimcore.settings.httpErrorLog());
        }
    },

    clearCache: function (params) {
        Ext.Msg.confirm(t('warning'), t('system_performance_stability_warning'), function(btn){
            if (btn == 'yes'){
                Ext.Ajax.request({
                    url: Routing.generate('pimcore_admin_settings_clearcache'),
                    method: "DELETE",
                    params: params
                });
            }
        });
    },

    clearOutputCache: function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_settings_clearoutputcache'),
            method: 'DELETE'
        });
    },

    clearTemporaryFiles: function () {
        Ext.Msg.confirm(t('warning'), t('system_performance_stability_warning'), function(btn){
            if (btn == 'yes'){
                Ext.Ajax.request({
                    url: Routing.generate('pimcore_admin_settings_cleartemporaryfiles'),
                    method: "DELETE"
                });
            }
        });
    },

    showFilexplorer: function () {
        try {
            pimcore.globalmanager.get("fileexplorer").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("fileexplorer", new pimcore.settings.fileexplorer.explorer());
        }
    },

    showMaintenance: function () {
        new pimcore.settings.maintenance();
    },

    extensionAdmin: function () {
        try {
            pimcore.globalmanager.get("extensionmanager_admin").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("extensionmanager_admin", new pimcore.extensionmanager.admin());
        }
    },

    logAdmin: function () {
        try {
            pimcore.globalmanager.get("pimcore_applicationlog_admin").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("pimcore_applicationlog_admin", new pimcore.log.admin());
        }
    },

    xliffImportExport: function () {
        try {
            pimcore.globalmanager.get("xliff").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("xliff", new pimcore.settings.translation.xliff());
        }
    },

    wordExport: function () {
        try {
            pimcore.globalmanager.get("word").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("word", new pimcore.settings.translation.word());
        }
    },

    showPhpInfo: function () {
        pimcore.helpers.openGenericIframeWindow("phpinfo", Routing.generate('pimcore_admin_misc_phpinfo'), "pimcore_icon_php", "PHP Info");
    },

    showServerInfo: function () {
        pimcore.helpers.openGenericIframeWindow("serverinfo", Routing.generate('pimcore_admin_external_linfo_index'), "pimcore_icon_server_info", "Server Info");
    },

    showOpcacheStatus: function () {
        pimcore.helpers.openGenericIframeWindow("opcachestatus", Routing.generate('pimcore_admin_external_opcache_index'), "pimcore_icon_reports", "PHP OPcache Status");
    },

    showSystemRequirementsCheck: function () {
        pimcore.helpers.openGenericIframeWindow("systemrequirementscheck", Routing.generate('pimcore_admin_install_check'), "pimcore_icon_systemrequirements", "System-Requirements Check");
    },

    showAdminer: function () {
        pimcore.helpers.openGenericIframeWindow("adminer", Routing.generate('pimcore_admin_external_adminer_adminer'), "pimcore_icon_mysql", "Database Admin");
    },

    showElementHistory: function() {
        try {
            pimcore.globalmanager.get("element_history").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("element_history", new pimcore.element.history());
        }
    },

    sentEmailsLog: function () {
        try {
            pimcore.globalmanager.get("sent_emails").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("sent_emails", new pimcore.settings.email.log());
        }
    },

    emailBlacklist: function () {
        try {
            pimcore.globalmanager.get("email_blacklist").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("email_blacklist", new pimcore.settings.email.blacklist());
        }
    },

    showTagConfiguration: function() {
        try {
            pimcore.globalmanager.get("element_tag_configuration").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("element_tag_configuration", new pimcore.element.tag.configuration());
        }
    },


    bulkImport: function() {

        Ext.Msg.confirm(t('warning'), t('warning_bulk_import'), function(btn){
            if (btn == 'yes'){
                this.doBulkImport();
            }
        }.bind(this));
    },


    doBulkImport: function() {
        var importer = new pimcore.object.bulkimport;
        importer.upload();
    },

    bulkExport: function() {
        var exporter = new pimcore.object.bulkexport();
        exporter.export();
    },

    showNotificationTab: function () {
        try {
            pimcore.globalmanager.get("notifications").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("notifications", new pimcore.notification.panel());
        }
    },

    showNotificationModal: function () {
        if (pimcore.globalmanager.get("new_notifications")) {
            pimcore.globalmanager.get("new_notifications").getWindow().destroy();
        }

        pimcore.globalmanager.add("new_notifications", new pimcore.notification.modal());
    }
});
