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

pimcore.registerNS("pimcore.report.seo.socialoverview");
pimcore.report.seo.socialoverview = Class.create(pimcore.report.abstract, {

    matchType: function (type) {
        var types = ["global"];
        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types)) {
            return true;
        }
        return false;
    },

    getName: function () {
        return "overview";
    },

    getIconCls: function () {
        return "pimcore_icon_report_social_group";
    },

    getPanel: function () {

        this.site = "default";
        this.iframeId = uniqid();


        var panel = new Ext.Panel({
            title: t("overview"),
            layout: "border",
            border: false,
            items: [this.getFramePanel()]
        });

        var containerConfig = {
            border: false,
            layout: "fit",
            items: [panel]
        };

        // check for sites
        var sites = pimcore.globalmanager.get("sites");
        if (sites.getTotalCount() > 0) {
            containerConfig.tbar = ["->",{
                xtype: 'tbtext',
                text: t("select_site")
            },{
                xtype: "combo",
                store: sites,
                valueField: "id",
                displayField: "domain",
                triggerAction: "all",
                listeners: {
                    "select": function (el) {
                        this.site = el.getValue();
                        this.setFrameUrl();
                    }.bind(this)
                }
            }];
        }


        var container = new Ext.Panel(containerConfig);

        return container;
    },

    getFramePanel: function () {

        if (!this.framePanel) {
            this.framePanel = new Ext.Panel({
                listeners: {
                    "resize": this.framePanelResize.bind(this)
                },
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="about:blank" frameborder="0" id="' + this.iframeId + '" width="100%"></iframe>',
                region: "center"
            });

            this.framePanel.on("afterrender", this.setFrameUrl.bind(this));
        }
        return this.framePanel;
    },

    framePanelResize: function (el, width, height, rWidth, rHeight) {
        Ext.get(this.iframeId).setStyle({
            height: (height) + "px"
        });
    },

    setFrameUrl: function () {

        var queryString = {};
        queryString.site = this.site;

        Ext.get(this.iframeId).dom.setAttribute("src", "/admin/reports/seo/social-overview?"
                                                                    + Ext.urlEncode(queryString));
    }
});

// add to report broker
pimcore.report.broker.addGroup("social", "social_media", "pimcore_icon_report_social_group");
pimcore.report.broker.addReport(pimcore.report.seo.socialoverview, "social");
