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

pimcore.registerNS("pimcore.layout.toolbar");
pimcore.layout.toolbar = Class.create({

    initialize: function() {

        var user = pimcore.globalmanager.get("user");
        this.toolbar = Ext.getCmp("pimcore_panel_toolbar");

        var fileItems = [];

        fileItems.push({
            text: t("welcome"),
            iconCls: "pimcore_icon_welcome",
            handler: function () {
                try {
                    pimcore.globalmanager.get("layout_portal").activate();
                }
                catch (e) {
                    pimcore.globalmanager.add("layout_portal", new pimcore.layout.portal());
                }
            }
        });

        fileItems.push({
            text: t("close_all_tabs"),
            iconCls: "pimcore_icon_menu_close_tabs",
            handler: this.closeAllTabs
        });


        this.fileMenu = new Ext.menu.Menu({
            items: fileItems
        });


        var extrasItems = [];


        if (user.isAllowed("glossary")) {
            extrasItems.push({
                text: t("glossary"),
                iconCls: "pimcore_icon_glossary",
                handler: this.editGlossary
            });
        }

        if (user.isAllowed("redirects")) {
            extrasItems.push({
                text: t("redirects"),
                iconCls: "pimcore_icon_redirects",
                handler: this.editRedirects
            });
        }

        /*if (user.isAllowed("plugins")) {
            extrasItems.push({
                text: t("plugins"),
                iconCls: "pimcore_icon_plugin",
                handler: this.pluginsOverview
            });
        }*/

        if (user.isAllowed("plugins")) {
            extrasItems.push({
                text: t("extensions"),
                iconCls: "pimcore_icon_extensionmanager",
                hideOnClick: false,
                menu: [{
                    text: t("manage_extensions"),
                    iconCls: "pimcore_icon_extensionmanager_admin",
                    handler: this.extensionAdmin
                },{
                    text: t("download_extension"),
                    iconCls: "pimcore_icon_extensionmanager_download",
                    handler: this.extensionDownload
                },{
                    text: t("share_extension"),
                    iconCls: "pimcore_icon_extensionmanager_share",
                    handler: this.extensionShare
                }]
            });
        }

        
        if (user.isAllowed("system_settings")) {
            extrasItems.push({
                text: t("recyclebin"),
                iconCls: "pimcore_icon_recyclebin",
                handler: this.recyclebin
            });
        }
        if (user.isAllowed("system_settings")) {
            extrasItems.push({
                text: t("backup"),
                iconCls: "pimcore_icon_backup",
                handler: this.backup
            });
        }

        if (user.isAllowed("update")) {
            extrasItems.push({
                text: t("update"),
                iconCls: "pimcore_icon_update",
                handler: function () {
                    var update = new pimcore.settings.update();
                }
            });

            extrasItems.push({
                text: t("language_download"),
                iconCls: "pimcore_icon_languages",
                handler: function () {
                    var update = new pimcore.settings.languages();
                }
            });

        }

        if (user.isAllowed("system_settings") && user.admin) {
            extrasItems.push({
                text: t("maintenance_mode"),
                iconCls: "pimcore_icon_maintenance",
                handler: this.showMaintenance
            });
        }

        if (user.isAllowed("translations")) {
            extrasItems.push({
                text: t("translations"),
                iconCls: "pimcore_icon_translations",
                handler: this.editTranslations
            });
        }

        // admin translations only for admins
        if(user.admin) {
            extrasItems.push({
                text: t("translations_admin"),
                iconCls: "pimcore_icon_translations",
                handler: this.editTranslationsSpecific
            });
        }

        if (user.isAllowed("system_settings")) {
            extrasItems.push({
                text: t("systemlog"),
                iconCls: "pimcore_icon_systemlog",
                handler: this.showLog
            });
        }

        if (user.isAllowed("system_settings") && user.admin) {
            extrasItems.push({
                text: t("server_fileexplorer"),
                iconCls: "pimcore_icon_fileexplorer",
                handler: this.showFilexplorer
            });
        }

        if (user.isAllowed("reports")) {
            extrasItems.push({
                text: t("reports_and_marketing") + " (beta)",
                iconCls: "pimcore_icon_reports",
                handler: this.showReports
            });
        }

        if (user.isAllowed("system_settings") && user.admin) {
            extrasItems.push({
                text: t("system_infos"),
                iconCls: "pimcore_icon_info",
                hideOnClick: false,
                menu: [{
                    text: "PHP Info",
                    iconCls: "pimcore_icon_php",
                    handler: this.showPhpInfo
                },{
                    text: "Server Info",
                    iconCls: "pimcore_icon_server_info",
                    handler: this.showServerInfo
                }/*,{
                    text: "MySQL Status",
                    iconCls: "pimcore_icon_mysql",
                    handler: this.showMysqlStatus
                }*/]
            });
        }


        if (extrasItems.length > 0) {
            this.extrasMenu = new Ext.menu.Menu({
                items: extrasItems
            });
        }

        // settings menu
        var settingsItems = [];

        if (user.isAllowed("document_types")) {
            settingsItems.push({
                text: t("document_types"),
                iconCls: "pimcore_icon_doctypes",
                handler: this.editDocumentTypes
            });
        }
        if (user.isAllowed("predefined_properties")) {
            settingsItems.push({
                text: t("predefined_properties"),
                iconCls: "pimcore_icon_properties",
                handler: this.editProperties
            });
        }

        if (user.isAllowed("system_settings")) {
            settingsItems.push({
                text: t("system"),
                iconCls: "pimcore_icon_system",
                handler: this.systemSettings
            });
        }
        
        if (user.isAllowed("system_settings")) {
            settingsItems.push({
                text: t("website"),
                iconCls: "pimcore_icon_website",
                handler: this.websiteSettings
            });
        }

        if (user.isAllowed("users")) {
            settingsItems.push({
                text: t("users"),
                iconCls: "pimcore_icon_users",
                handler: this.editUsers
            });
        } else {
            settingsItems.push({
                text: t("profile"),
                iconCls: "pimcore_icon_users",
                handler: this.editProfile
            });
        }
        if (user.isAllowed("thumbnails")) {
            settingsItems.push({
                text: t("thumbnails"),
                iconCls: "pimcore_icon_thumbnails",
                handler: this.editThumbnails
            });
        }

        if (user.isAllowed("objects")) {

            var objectMenu = {
                text: t("object"),
                iconCls: "pimcore_icon_object",
                hideOnClick: false,
                menu: []
            }

            if (user.isAllowed("classes")) {
                objectMenu.menu.push({
                    text: t("classes"),
                    iconCls: "pimcore_icon_classes",
                    handler: this.editClasses
                });
                
                objectMenu.menu.push({
                    text: t("field_collections"),
                    iconCls: "pimcore_icon_fieldcollections",
                    handler: this.editFieldcollections
                });

                objectMenu.menu.push({
                    text: t("objectbricks"),
                    iconCls: "pimcore_icon_objectbricks",
                    handler: this.editObjectBricks
                });

                objectMenu.menu.push({
                    text: t("custom_views"),
                    iconCls: "pimcore_icon_custom_views",
                    handler: this.editCustomViews
                });

                settingsItems.push(objectMenu);
            }
        }

        if (user.isAllowed("routes")) {
            settingsItems.push({
                text: t("static_routes"),
                iconCls: "pimcore_icon_routes",
                handler: this.editRoutes
            });
        }

        if (user.isAllowed("clear_cache") || user.isAllowed("clear_temp_files")) {

            var cacheMenu = {
                text: t("cache"),
                iconCls: "pimcore_icon_menu_clear_cache",
                hideOnClick: false,
                menu: []
            }

            if (user.isAllowed("clear_cache")) {
                cacheMenu.menu.push({
                    text: t("clear_only_output_cache"),
                    iconCls: "pimcore_icon_menu_clear_cache",
                    handler: this.clearOutputCache
                });
            }

            if (user.isAllowed("clear_cache")) {
                cacheMenu.menu.push({
                    text: t("clear_cache"),
                    iconCls: "pimcore_icon_menu_clear_cache",
                    handler: this.clearCache
                });
            }

            if (user.isAllowed("clear_temp_files")) {
                cacheMenu.menu.push({
                    text: t("clear_temporary_files"),
                    iconCls: "pimcore_icon_menu_clear_cache",
                    handler: this.clearTemporaryFiles
                });
            }

            settingsItems.push(cacheMenu);
        }

        if (user.isAllowed("reports") && user.isAllowed("system_settings")) {
            settingsItems.push({
                text: t("reports_and_marketing") + " (beta)",
                iconCls: "pimcore_icon_reports",
                handler: this.reportSettings
            });
        }


        // help menu
        if (settingsItems.length > 0) {
            this.settingsMenu = new Ext.menu.Menu({
                items: settingsItems
            });
        }

        this.helpMenu = new Ext.menu.Menu({
            items: [
                {
                    text: t("documentation"),
                    iconCls: "pimcore_icon_menu_documentation",
                    handler: function () {
                        window.open("http://www.pimcore.org/wiki/");
                    }
                },
                {
                    text: t("report_bugs"),
                    iconCls: "pimcore_icon_menu_bugs",
                    handler: function () {
                        window.open("http://www.pimcore.org/issues");
                    }
                },
                {
                    text: t("about"),
                    iconCls: "pimcore_icon_menu_about",
                    handler: function () {
                        window.open("http://www.pimcore.org/");
                    }
                }
            ]
        });


        this.toolbar.add({
            text: t('file'),
            iconCls: "pimcore_icon_menu_file",
            cls: "pimcore_main_menu",
            menu: this.fileMenu
        });


        if (this.extrasMenu) {
            this.toolbar.add({
                text: t('extras'),
                iconCls: "pimcore_icon_menu_extras",
                cls: "pimcore_main_menu",
                menu: this.extrasMenu
            });
        }


        if (this.settingsMenu) {
            this.toolbar.add({
                text: t('settings'),
                iconCls: "pimcore_icon_menu_settings",
                cls: "pimcore_main_menu",
                menu: this.settingsMenu
            });
        }
        
        this.toolbar.add({
            text: t('search'),
            iconCls: "pimcore_icon_menu_search",
            cls: "pimcore_main_menu",
            handler: function () {
                pimcore.helpers.itemselector(false, function (selection) {
                    pimcore.helpers.openElement(selection.id,selection.type, selection.subtype);
                }, null, {moveToTab: true} );
            }
        });
        
        this.toolbar.add("->");
        

        if (user.isAllowed("seemode")) {
            this.toolbar.add({
                text: t("seemode"),
                iconCls: "pimcore_icon_menu_seemode",
                cls: "pimcore_main_menu",
                handler: pimcore.helpers.openSeemode
            });
        }

        this.toolbar.add({
            text: t('help'),
            iconCls: "pimcore_icon_menu_help",
            cls: "pimcore_main_menu",
            menu: this.helpMenu
        });

        this.toolbar.add({
            text: t('logout'),
            iconCls: "pimcore_icon_menu_logout",
            cls: "pimcore_main_menu",
            handler: this.logout
        });
        
        
        this.toolbar.add("-");
        
        this.toolbar.add(new Ext.Toolbar.Spacer({
            width: "150"
        }));


        return;
    },


    closeAllTabs: function () {
        pimcore.helpers.closeAllElements();
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

    backup: function () {
        var backup = new pimcore.settings.backup();
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

        try {
            pimcore.globalmanager.get("users").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("users", new pimcore.settings.user.panel());
        }
    },

    editProfile: function () {

        try {
            pimcore.globalmanager.get("profile").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("profile", new pimcore.settings.profile.panel());
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

    editTranslations: function () {
        try {
            pimcore.globalmanager.get("translationwebsitemanager").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("translationwebsitemanager", new pimcore.settings.translation.website());
        }
    },

    editTranslationsSpecific: function () {
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

    showLog: function () {

        try {
            pimcore.globalmanager.get("systemlog").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("systemlog", new pimcore.settings.systemlog());
        }
    },

    showReports: function () {
        try {
            pimcore.globalmanager.get("reports").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("reports", new pimcore.report.panel());
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

    editCustomViews: function () {
        try {
            pimcore.globalmanager.get("customviews").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("customviews", new pimcore.object.customviews.settings());
        }
    },

    clearCache: function () {
        Ext.Ajax.request({
            url: '/admin/settings/clear-cache'
        });
    },

    clearOutputCache: function () {
        Ext.Ajax.request({
            url: '/admin/settings/clear-output-cache'
        });
    },

    clearTemporaryFiles: function () {
        Ext.Ajax.request({
            url: '/admin/settings/clear-temporary-files'
        });
    },

    logout: function () {
        location.href = "/admin/login/logout/";
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

    extensionShare: function () {
        try {
            pimcore.globalmanager.get("extensionmanager_share").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("extensionmanager_share", new pimcore.extensionmanager.share());
        }
    },

    extensionAdmin: function () {
        try {
            pimcore.globalmanager.get("extensionmanager_admin").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("extensionmanager_admin", new pimcore.extensionmanager.admin());
        }
    },

    extensionDownload: function () {
        try {
            pimcore.globalmanager.get("extensionmanager_download").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("extensionmanager_download", new pimcore.extensionmanager.download());
        }
    },

    showPhpInfo: function () {

        var id = "phpinfo";

        try {
            pimcore.globalmanager.get(id).activate();
        }
        catch (e) {
            pimcore.globalmanager.add(id, new pimcore.tool.genericiframewindow(id, "/admin/misc/phpinfo", "pimcore_icon_php", "PHP Info"));
        }

    },

    showServerInfo: function () {

        var id = "serverinfo";

        try {
            pimcore.globalmanager.get(id).activate();
        }
        catch (e) {
            pimcore.globalmanager.add(id, new pimcore.tool.genericiframewindow(id, "/pimcore/modules/3rdparty/linfo/index.php", "pimcore_icon_server_info", "Server Info"));
        }

    },

    showMysqlStatus: function () {

        var id = "mysqlstatus";

        try {
            pimcore.globalmanager.get(id).activate();
        }
        catch (e) {
            pimcore.globalmanager.add(id, new pimcore.tool.genericiframewindow(id, "/admin/reports/system/mysql", "pimcore_icon_mysql", "MySQL Status"));
        }

    }


});