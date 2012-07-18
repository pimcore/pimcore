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

pimcore.registerNS("pimcore.layout.portal");
pimcore.layout.portal = Class.create({

    initialize: function () {
        this.activePortlets = [];
        this.loadConfiguration();
    },

    loadConfiguration: function () {
        Ext.Ajax.request({
            url: "/admin/portal/get-configuration",
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
                for (var c = 0; c <= userConf.positions[i].length; c++) {
                    try {
                        dynClass = eval(userConf.positions[i][c]);
                        if (dynClass) {
                            portletInstance = new dynClass();
                            portletInstance.setPortal(this);
                            config[i].push(portletInstance.getLayout());
                            this.activePortlets.push(userConf.positions[i][c]);
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
        tabPanel.activate("pimcore_portal");
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
            this.panel = new Ext.Panel({
                id: "pimcore_portal",
                title: t("welcome"),
                border: false,
                iconCls: "pimcore_icon_welcome",
                closable:true,
                autoScroll: true,
                tbar: {
                    items: ["->",{
                        text: t("add_portlet"),
                        iconCls: "pimcore_icon_portlet_add",
                        menu: portletMenu
                    }]
                },
                items: [
                    {
                        xtype:'portal',
                        region:'center',
                        autoScroll: false,
                        autoHeight: true,
                        items:[
                            {
                                id: "pimcore_portal_col0",
                                columnWidth:.5,
                                style:'padding:10px',
                                items:[config[0]]
                            },
                            {
                                id: "pimcore_portal_col1",
                                columnWidth:.5,
                                style:'padding:10px 10px 10px 0',
                                items:[config[1]]
                            }
                        ]
                        ,listeners: {
                        'drop': function(e) {
                            Ext.Ajax.request({
                                url: "/admin/portal/reorder-widget",
                                params: {
                                    type: e.panel.initialConfig.widgetType,
                                    column: e.columnIndex,
                                    row: e.position
                                }
                            });
                        }
                    }
                    }
                ]
            });

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("layout_portal");
            }.bind(this));

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_portal");

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    addPortlet: function (type) {

        if (in_array(type, this.activePortlets)) {
            return;
        }

        dynClass = eval(type);
        if (dynClass) {
            portletInstance = new dynClass();
            portletInstance.setPortal(this);

            var col = Ext.getCmp("pimcore_portal_col0");
            col.add(portletInstance.getLayout());
            this.panel.doLayout();
            
            Ext.Ajax.request({
                url: "/admin/portal/add-widget",
                params: {type: type}
            });

            this.activePortlets.push(type);
        }
    }

});

