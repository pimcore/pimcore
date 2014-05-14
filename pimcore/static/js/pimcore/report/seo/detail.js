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

pimcore.registerNS("pimcore.report.seo.detail");
pimcore.report.seo.detail = Class.create(pimcore.report.abstract, {

    matchType: function (type) {
        var types = ["global"];
        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types)) {
            return true;
        }
        return false;
    },

    getName: function () {
        return "detail";
    },

    getIconCls: function () {
        return "pimcore_icon_seo_detail";
    },

    getPanel: function () {

        this.site = "default";

        //id,host,site,url,type,typeReference,facebookShares,googlePlusOne,links,linksExternal,h1,h2,h3,h4,h5,h6,
        // h1Text,imgWithoutAlt,imgWithAlt,title,description,urlLength,urlParameters,microdata,opengraph,twitter,
        // robotsTxtBlocked,robotsMetaBlocked,lastUpdate

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            url: "/admin/reports/seo/detail",
            root: 'data',
            remoteSort: true,
            baseParams: {
                limit: 40,
                filter: ""
            },
            fields: ["host","site","url","type","typeReference","facebookShares","googlePlusOne","links",
                     "linksExternal","h1","h2","h3","h4","h5","h6","h1Text","imgWithoutAlt","imgWithAlt",
                     "title","description","urlLength","urlParameters","microdata","opengraph","twitter",
                     "robotsTxtBlocked","robotsMetaBlocked","lastUpdate","titleLength", "descriptionLength"]
        });
        this.store.load();

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: 40,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });

        // add per-page selection
        this.pagingtoolbar.add("-");

        this.pagingtoolbar.add(new Ext.Toolbar.TextItem({
            text: t("items_per_page")
        }));
        this.pagingtoolbar.add(new Ext.form.ComboBox({
            store: [
                [10, "10"],
                [20, "20"],
                [40, "40"],
                [60, "60"],
                [80, "80"],
                [100, "100"]
            ],
            mode: "local",
            width: 50,
            value: 20,
            triggerAction: "all",
            listeners: {
                select: function (box, rec, index) {
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));

        this.grid = new Ext.grid.GridPanel({
            store: this.store,
            bbar: this.pagingtoolbar,
            columns: [
                {
                    xtype: 'actioncolumn',
                    width: 30,
                    items: [{
                        tooltip: t('open'),
                        icon: "/pimcore/static/img/icon/world_go.png",
                        handler: function (grid, rowIndex) {
                            var data = grid.getStore().getAt(rowIndex);
                            window.open(data.get("url"));
                        }.bind(this)
                    }]
                },
                {header: "URL", width: 400, sortable: true, dataIndex: 'url'},
                {header: t("host"), sortable: true, dataIndex: 'host', hidden: true},
                {header: t("site"), sortable: true, dataIndex: 'site', hidden: true},
                {header: t("last_update"), sortable: true, dataIndex: 'lastUpdate', hidden: true},
                {header: t("type"), sortable: true, dataIndex: 'type', hidden: true},
                {header: t("type_reference"), sortable: true, dataIndex: 'typeReference', hidden: true},
                {header: t("facebook_shares"), sortable: true, dataIndex: 'facebookShares'},
                {header: t("google_plus_one"), sortable: true, dataIndex: 'googlePlusOne'},
                {header: t("title"), sortable: true, dataIndex: 'title'},
                {header: t("length"), sortable: true, dataIndex: 'titleLength'},
                {header: t("description"), sortable: true, dataIndex: 'description'},
                {header: t("length"), sortable: true, dataIndex: 'descriptionLength'},
                {header: t("links"), sortable: true, dataIndex: 'links'},
                {header: t("external_links"), sortable: true, dataIndex: 'linksExternal'},
                {header: "H1", sortable: true, dataIndex: 'h1'},
                {header: t("h1_text"), sortable: true, dataIndex: 'h1Text'},
                {header: "H2", sortable: true, dataIndex: 'h2'},
                {header: "H3", sortable: true, dataIndex: 'h3'},
                {header: "H4", sortable: true, dataIndex: 'h4'},
                {header: "H5", sortable: true, dataIndex: 'h5'},
                {header: "H6", sortable: true, dataIndex: 'h6'},
                {header: t("images_without_alt"), sortable: true, dataIndex: 'imgWithoutAlt'},
                {header: t("images_with_alt"), sortable: true, dataIndex: 'imgWithAlt'},
                {header: t("url_length"), sortable: true, dataIndex: 'urlLength'},
                {header: t("url_parameters"), sortable: true, dataIndex: 'urlParameters'},
                {header: t("microdata"), sortable: true, dataIndex: 'microdata'},
                {header: t("opengraph"), sortable: true, dataIndex: 'opengraph'},
                {header: t("twitter"), sortable: true, dataIndex: 'twitter'},
                {header: t("robots_txt_blocked"), sortable: true, dataIndex: 'robotsTxtBlocked'},
                {header: t("robots_meta_blocked"), sortable: true, dataIndex: 'robotsMetaBlocked'}
            ],
            columnLines: true,
            stripeRows: true,
            trackMouseOver: true,
            viewConfig: {
                forceFit: false
            }
        });

        var panel = new Ext.Panel({
            title: t("overview"),
            layout: "fit",
            border: false,
            items: [this.grid]
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
                        this.store.baseParams.site = el.getValue();
                        this.store.load();
                    }.bind(this)
                }
            }];
        }


        var container = new Ext.Panel(containerConfig);

        return container;
    }
});

// add to report broker
pimcore.report.broker.addGroup("seo", "SEO", "pimcore_icon_report_seo_group");
pimcore.report.broker.addReport(pimcore.report.seo.detail, "seo");
