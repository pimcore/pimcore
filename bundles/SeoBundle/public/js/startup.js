pimcore.registerNS("pimcore.seo");


pimcore.seo = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");
        console.log("ready");

        if (perspectiveCfg.inToolbar("marketing") && perspectiveCfg.inToolbar("marketing.seo")) {
            var seoMenu = [];

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
                    text: "robots.txt",
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

            const toolbar = pimcore.globalmanager.get('layout_toolbar');
            console.log(toolbar);


            // get index of marketing.targeting
            if (seoMenu.length > 0) {
               //marketingItems.push({
               //    text: t("search_engine_optimization"),
               //    iconCls: "pimcore_nav_icon_seo",
               //    itemId: 'pimcore_menu_marketing_seo',
               //    hideOnClick: false,
               //    menu: {
               //        cls: "pimcore_navigation_flyout",
               //        shadow: false,
               //        items: seoMenu
               //    }
               //});
            }
        }

        console.log('Ready');
    },



    registerKeyBinding: function(e) {
        const user = pimcore.globalmanager.get('user');
    }
})

const seo = new pimcore.seo();