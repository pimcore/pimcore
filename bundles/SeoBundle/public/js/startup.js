/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */


pimcore.registerNS("pimcore.bundle.seo.startup");

/**
 * @private
 */
pimcore.bundle.seo.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (perspectiveCfg.inToolbar("marketing") && perspectiveCfg.inToolbar("marketing.seo")) {
            let seoMenu = [];

            if (user.isAllowed("documents") && user.isAllowed("seo_document_editor") && perspectiveCfg.inToolbar("marketing.seo.documents")) {
                seoMenu.push({
                    text: t("seo_document_editor"),
                    iconCls: "pimcore_nav_icon_document_seo",
                    itemId: 'pimcore_menu_marketing_seo_document_editor',
                    handler: this.showDocumentSeo
                });
            }

            if (user.isAllowed("robots.txt") && perspectiveCfg.inToolbar("marketing.seo.robots")) {
                seoMenu.push({
                    text: t("robots.txt"),
                    iconCls: "pimcore_nav_icon_robots",
                    itemId: 'pimcore_menu_marketing_seo_robots_txt',
                    handler: this.showRobotsTxt
                });
            }

            if (user.isAllowed("http_errors") && perspectiveCfg.inToolbar("marketing.seo.httperrors")) {
                seoMenu.push({
                    text: t("http_errors"),
                    iconCls: "pimcore_nav_icon_httperrorlog",
                    itemId: 'pimcore_menu_marketing_seo_http_errors',
                    handler: this.showHttpErrorLog
                });
            }

            // get index of marketing.targeting
            if (seoMenu.length > 0) {
                menu.marketing.items.push({
                    text: t("search_engine_optimization"),
                    iconCls: "pimcore_nav_icon_seo",
                    priority: 25,
                    itemId: 'pimcore_menu_marketing_seo',
                    hideOnClick: false,
                    menu: {
                        cls: "pimcore_navigation_flyout",
                        shadow: false,
                        items: seoMenu
                    }
                });
            }
        }

        if (menu.extras && user.isAllowed("redirects") && perspectiveCfg.inToolbar("extras.redirects")) {
            menu.extras.items.push({
                text: t("redirects"),
                iconCls: "pimcore_nav_icon_redirects",
                priority: 5,
                itemId: 'pimcore_menu_extras_redirects',
                handler: this.editRedirects
            });
        }

    },

    showDocumentSeo: function () {
        try {
            pimcore.globalmanager.get("bundle_seo_seopanel").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("bundle_seo_seopanel", new pimcore.bundle.seo.seopanel());
        }
    },

    showRobotsTxt: function () {
        try {
            pimcore.globalmanager.get("bundle_seo_robotstxt").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("bundle_seo_robotstxt", new pimcore.bundle.seo.robotstxt());
        }
    },

    showHttpErrorLog: function () {
        try {
            pimcore.globalmanager.get("bundle_seo_http_error_log").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("bundle_seo_http_error_log", new pimcore.bundle.seo.httpErrorLog());
        }
    },

    registerKeyBinding: function(e) {
        const user = pimcore.globalmanager.get('user');

        if (user.isAllowed("documents") && user.isAllowed("seo_document_editor")) {
            pimcore.helpers.keyBindingMapping.seoDocumentEditor = function() {
                pimcoreBundleSeo.showDocumentSeo();
            }
        }

        if (user.isAllowed("robots.txt")) {
            pimcore.helpers.keyBindingMapping.robots = function() {
                pimcoreBundleSeo.showRobotsTxt();
            }
        }

        if (user.isAllowed("http_errors")) {
            pimcore.helpers.keyBindingMapping.httpErrorLog = function() {
                pimcoreBundleSeo.showHttpErrorLog();
            }
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
})

const pimcoreBundleSeo = new pimcore.bundle.seo.startup();