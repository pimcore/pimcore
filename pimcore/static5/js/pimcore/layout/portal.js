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

pimcore.registerNS("pimcore.layout.portal");
pimcore.layout.portal = Class.create({

    key: "welcome",

    initialize: function (key) {
        this.activePortlets = [];

        if(key) {
            this.key = key;
        }

        this.loadConfiguration();
    },

    loadConfiguration: function () {
        Ext.Ajax.request({
            url: "/admin/portal/get-configuration",
            params: {
                key: this.key
            },
            success: this.initConfiguration.bind(this)
        });
    },

    initConfiguration: function (response) {
        var config = [
            [],
            []
        ];
        var userConf = Ext.decode(response.responseText);
        var dynClass;
        var portletInstance;

        this.userConf = userConf;

        if (userConf.positions.length == 2) {

            for (var i = 0; i < 2; i++) {
                for (var c = 0; c < userConf.positions[i].length; c++) {
                    try {
                        var type = userConf.positions[i][c].type;
                        //if (
                        //    type != "pimcore.layout.portlets.modifiedAssets"
                        // && type != "pimcore.layout.portlets.modifiedDocuments"
                        //&& type != "pimcore.layout.portlets.modifiedObjects"
                        //) {
                        //    continue;
                        //}

                        dynClass = eval(type);
                        if (dynClass) {
                            portletInstance = new dynClass();
                            portletInstance.setPortal(this);
                            portletInstance.setConfig(userConf.positions[i][c].config);
                            var portletLayout = portletInstance.getLayout(userConf.positions[i][c].id);

                            config[i].push(portletLayout);
                            this.activePortlets.push(userConf.positions[i][c].id);
                        }
                    }
                    catch (e) {
                        console.log(e);
                    }
                }
            }

            this.getTabPanel(config);
        }

    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_portal_" + this.key);
    },

    getTabPanel: function (config) {

        var portletMenu = [];
        var portlets = Object.keys(pimcore.layout.portlets);

        for (var i = 0; i < portlets.length; i++) {
            if (portlets[i] != "abstract") {
                portletMenu.push({
                    text: pimcore.layout.portlets[portlets[i]].prototype.getName(),
                    iconCls: pimcore.layout.portlets[portlets[i]].prototype.getIcon(),
                    handler: this.addPortlet.bind(this, pimcore.layout.portlets[portlets[i]].prototype.getType())
                });
            }
        }

        if (!this.panel) {
            this.panel = Ext.create('Portal.view.PortalPanel', {
                id: "pimcore_portal_" + this.key,
                layout: 'column',
                title: this.key == "welcome" ? t("welcome") : this.key,
                border: true,
                bodyCls: 'x-portal-body',
                iconCls: "pimcore_icon_welcome",
                closable:true,
                autoScroll: true,
                tbar: [
                    "->",
                    {
                        type: 'button',
                        text: t("add_portlet"),
                        iconCls: "pimcore_icon_portlet_add",
                        menu: portletMenu
                    }
                ]
                ,
                items:[
                    {
                        id: "pimcore_portal_col0_" + this.key,
                        xtype: 'portalcolumn',
                        //columnWidth: 0.5,
                        style:'padding:10px',
                        items: config[0],
                        title: 'left'
                    },
                    {
                        id: "pimcore_portal_col1_" + this.key,
                        xtype: 'portalcolumn',
                        //columnWidth: 0.5,
                        style:'padding:10px 10px 10px 0',
                        items: config[1],
                        title: 'right'
                    }
                ]
            });


            this.panel.on('drop', function(e) {
                Ext.Ajax.request({
                    url: "/admin/portal/reorder-widget",
                    params: {
                        key: this.key,
                        id: e.panel.portletId,
                        column: e.columnIndex,
                        row: e.position
                    }
                });

            });

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("layout_portal_" + this.key);
            }.bind(this));

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_portal_" + this.key);

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    addPortlet: function (type) {

        var dynClass = eval(type);
        if (dynClass) {

            Ext.Ajax.request({
                url: "/admin/portal/add-widget",
                params: {
                    key: this.key,
                    type: type
                },
                success: function(response) {
                    var response = Ext.decode(response.responseText);
                    if(response.success) {
                        var portletInstance = new dynClass();
                        portletInstance.setPortal(this);

                        var col = Ext.getCmp("pimcore_portal_col0_" + this.key);
                        col.add(portletInstance.getLayout(response.id));
                        this.panel.updateLayout();
                    }
                }.bind(this)
            });

            this.activePortlets.push(type);

        }
    }

});

