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

pimcore.registerNS("pimcore.report.analytics.overview");
pimcore.report.analytics.overview = Class.create(pimcore.report.abstract, {

    matchType: function (type) {

        // deactivate temporary
        return;
// // commented this out, otherwise JSLint would complain
//
//        var types = ["global"];
//        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types)
//                                                            && pimcore.settings.google_analytics_enabled) {
//            return true;
//        }
//        return false;
    },

    getName: function () {
        return "overview";
    },

    getIconCls: function () {
        return "pimcore_icon_analytics";
    },



    getPanel: function () {

        this.site = "default";
        this.iframeId = uniqid();


        var panel = new Ext.Panel({
            title: t("visitor_overview"),
            layout: "border",
            border: false,
            items: [this.getFilterPanel(),this.getFramePanel()]
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
                bodyCls: "pimcore_overflow_scrolling",
                html: '<iframe src="about:blank" frameborder="0" id="' + this.iframeId + '" style="width: 100%;"></iframe>',
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

    getFilterPanel: function () {

        if (!this.filterPanel) {


            var today = new Date();
            var fromDate = new Date(today.getTime() - (86400000 * 31));


            this.filterPanel = new Ext.FormPanel({
                region: 'north',
                labelWidth: 40,
                height: 40,
                layout: 'form',
                bodyStyle: 'padding:7px 0 0 5px',
                items: [
                    {
                        xtype: "datefield",
                        fieldLabel: t('from'),
                        name: 'datefrom',
                        value: fromDate,
                        cls: "pimcore_analytics_filter_form_item"
                    },
                    {
                        xtype: "datefield",
                        fieldLabel: t('to'),
                        name: 'dateto',
                        value: today,
                        cls: "pimcore_analytics_filter_form_item"
                    },
                    {
                        xtype: "button",
                        text: t("apply"),
                        cls: "pimcore_analytics_filter_form_item",
                        handler: this.setFrameUrl.bind(this)
                    }
                ]
            });
        }

        return this.filterPanel;
    },

    setFrameUrl: function () {
        var values = this.getFilterPanel().getForm().getFieldValues();

        var queryString = {};
        queryString.dateFrom = values.datefrom.getTime() / 1000;
        queryString.dateTo = values.dateto.getTime() / 1000;
        queryString.site = this.site;

        Ext.get(this.iframeId).dom.setAttribute("src",
                                Routing.getBaseUrl() + "/admin/reports/analytics/siteoverview?" + Ext.Object.toQueryString(queryString));
    }
});

// add to report broker
pimcore.report.broker.addGroup("analytics", "google_analytics", "pimcore_icon_analytics");
pimcore.report.broker.addReport(pimcore.report.analytics.overview, "analytics");
