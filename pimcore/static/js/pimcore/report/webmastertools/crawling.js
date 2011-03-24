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

pimcore.registerNS("pimcore.report.webmastertools.crawling");
pimcore.report.webmastertools.crawling = Class.create(pimcore.report.abstract, {

    matchType: function (type) {
        var types = ["global"];
        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types) && pimcore.settings.google_webmastertools_enabled) {
            return true;
        }
        return false;
    },

    getName: function () {
        return "crawling_issues";
    },

    getIconCls: function () {
        return "pimcore_icon_webmastertools_crawling";
    },

    getPanel: function () {

        var panelConfig = {
            layout: "fit",
            items: [],
            listeners: {
                "afterrender": this.loadData.bind(this)
            }
        };

        // check for sites
        var sites = pimcore.globalmanager.get("sites");
        if (sites.getTotalCount() > 0) {
            panelConfig.tbar = ["->",{
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
                        this.loadData(el.getValue());
                    }.bind(this)
                }
            }];
        }

        this.panel = new Ext.Panel(panelConfig);

        return this.panel;
    },

    loadData: function (site) {

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/reports/webmastertools/crawling',
            root: 'issues',
            baseParams: {
                site: site
            },
            idProperty: 'url',
            fields: ['title',"crawltype","issuetype","url","datedetected","detail","linkedfrom"]
        });
        this.store.load();

        var panel = new Ext.grid.GridPanel({
            title: t("google_crawling_issues"),
            store: this.store,
            autoExpandColumn: "url",
            columns: [
                {id: "url", header: 'URL', dataIndex: 'url', width: 100},
                {header: t("type"), dataIndex: 'issuetype', width: 100},
                {header: t("date"), dataIndex: 'datedetected', width: 150},
                {header: t('detail'), dataIndex: 'detail', width: 150}
            ],
            listeners: {
                "rowclick": this.rowClick.bind(this)
            }
        });

        this.panel.removeAll();
        this.panel.add(panel);
        this.panel.doLayout();
    },

    rowClick: function (grid, rowIndex, event) {

        var sel = this.store.getAt(rowIndex);
        var urls = sel.data.linkedfrom.split("\n");

        var storeData = [];
        for (var i = 0; i < urls.length; i++) {
            storeData.push([urls[i]]);
        }

        var store = new Ext.data.ArrayStore({
            autoDestroy: true,
            fields: [
                'url'
            ],
            data: storeData
        });


        var win = new Ext.Window({
            title: t("link_sources"),
            width: 600,
            height: 400,
            layout: "fit",
            items: [
                {
                    xtype: "grid",
                    store: store,
                    autoExpandColumn: "refUrl",
                    columns: [
                        {id: "refUrl", header: 'URL', dataIndex: 'url'}
                    ],
                    listeners: {
                        "rowclick": function (grid, rowIndex, event) {
                            var url = grid.getStore().getAt(rowIndex).data.url;
                            window.open(url);
                        }
                    }
                }
            ]
        });

        win.show();
    }
});

// add to report broker
pimcore.report.broker.addGroup("webmastertools", "google_webmastertools", "pimcore_icon_report_webmastertools_group");
pimcore.report.broker.addReport(pimcore.report.webmastertools.crawling, "webmastertools");
