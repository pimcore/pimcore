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
        return "pimcore_icon_analytics_overview";
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
                        itemCls: "pimcore_analytics_filter_form_item"
                    },
                    {
                        xtype: "datefield",
                        fieldLabel: t('to'),
                        name: 'dateto',
                        value: today,
                        itemCls: "pimcore_analytics_filter_form_item"
                    },
                    {
                        xtype: "button",
                        text: "apply",
                        itemCls: "pimcore_analytics_filter_form_item",
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
                                "/admin/reports/analytics/siteoverview?" + Object.toQueryString(queryString));
    }
});

// add to report broker
pimcore.report.broker.addGroup("analytics", "google_analytics", "pimcore_icon_report_analytics_group");
pimcore.report.broker.addReport(pimcore.report.analytics.overview, "analytics");
