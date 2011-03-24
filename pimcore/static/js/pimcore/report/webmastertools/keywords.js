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

pimcore.registerNS("pimcore.report.webmastertools.keywords");
pimcore.report.webmastertools.keywords = Class.create(pimcore.report.abstract, {

    matchType: function (type) {
        var types = ["global"];
        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types) && pimcore.settings.google_webmastertools_enabled) {
            return true;
        }
        return false;
    },

    getName: function () {
        return "keywords";
    },

    getIconCls: function () {
        return "pimcore_icon_webmastertools_keywords";
    },



    getPanel: function () {

        var panelConfig = {
            layout: "fit",
            listeners: {
                "afterrender": this.loadData.bind(this)
            },
            border: false
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

        this.panel = new Ext.Panel({
            title: t("keywords"),
            layout:'hbox',
            layoutConfig: {
                padding: 10,
                align: "stretch"
            },
            border: true,
            items: []
        });

        panelConfig.items = [this.panel];
        var container = new Ext.Panel(panelConfig);

        return container;
    },

    loadData: function (site) {


        this.storeInternal = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/reports/webmastertools/keywords/type/internal',
            root: 'keywords',
            idProperty: 'keyword',
            fields: ['keyword'],
            baseParams: {
                site: site
            }
        });
        this.storeInternal.load();

        this.storeExternal = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/reports/webmastertools/keywords/type/external',
            root: 'keywords',
            idProperty: 'keyword',
            fields: ['keyword'],
            baseParams: {
                site: site
            }
        });
        this.storeExternal.load();

        var items = [
            {
                xtype: "grid",
                flex: 1,
                autoScroll: true,
                store: this.storeInternal,
                autoExpandColumn: "keywordInternal",
                columns: [
                    {id: "keywordInternal", header: 'Keyword', dataIndex: 'keyword'}
                ],
                title: t('internal_keywords')
            },
            {
                xtype: "grid",
                flex: 1,
                store: this.storeExternal,
                autoExpandColumn: "keywordExternal",
                columns: [
                    {id: "keywordExternal", header: 'Keyword', dataIndex: 'keyword'}
                ],
                title: t('external_keywords')
            }
        ];

        this.panel.removeAll();
        this.panel.add(items);
        this.panel.doLayout();
    }
});

// add to report broker
pimcore.report.broker.addGroup("webmastertools", "google_webmastertools", "pimcore_icon_report_webmastertools_group");
pimcore.report.broker.addReport(pimcore.report.webmastertools.keywords, "webmastertools");
