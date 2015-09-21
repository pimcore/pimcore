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
                        dynClass = eval(userConf.positions[i][c].type);
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
        tabPanel.activate("pimcore_portal_" + key);
    },

    getTabPanel: function (config) {

        var portletMenu = [];
        var portlets = Object.keys(pimcore.layout.portlets);

        for (var i = 0; i < portlets.length; i++) {
            if (portlets[i] != "abstract") {
                if(pimcore.layout.portlets[portlets[i]].prototype.isAllowed(pimcore.globalmanager.get("user"))) {
                    portletMenu.push({
                        text: pimcore.layout.portlets[portlets[i]].prototype.getName(),
                        iconCls: pimcore.layout.portlets[portlets[i]].prototype.getIcon(),
                        handler: this.addPortlet.bind(this, pimcore.layout.portlets[portlets[i]].prototype.getType())
                    });
                }
            }
        }

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_portal_" + this.key,
                title: this.key == "welcome" ? t("welcome") : this.key,
                border: false,
                iconCls: "pimcore_icon_welcome",
                closable:true,
                autoScroll: true,
                tbar: {
                    items: [
                        "->",
                        {
                            text: t("add_portlet"),
                            iconCls: "pimcore_icon_portlet_add",
                            menu: portletMenu
                        },
                        {
                            text: t("delete_dashboard"),
                            iconCls: "pimcore_icon_delete",
                            hidden: (this.key == "welcome"),
                            handler: function() {
                                Ext.Msg.show({
                                    title:t('delete_dashboard'),
                                    msg: t('really_delete_dashboard'),
                                    buttons: Ext.Msg.YESNO,
                                    fn: function(btn) {
                                        if(btn == "yes") {
                                            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                                            tabPanel.remove("pimcore_portal_" + this.key);

                                            Ext.Ajax.request({
                                                url: "/admin/portal/delete-dashboard",
                                                params: {
                                                    key: this.key
                                                },
                                                success: function() {
                                                    Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                                                        if (buttonValue == "yes") {
                                                            window.location.reload();
                                                        }
                                                    });
                                                }
                                            });

                                        }
                                    }.bind(this),
                                    icon: Ext.MessageBox.QUESTION
                                });
                            }.bind(this)
                        }
                    ]
                },
                items: [
                    {
                        xtype:'portal',
                        region:'center',
                        autoScroll: false,
                        autoHeight: true,
                        items:[
                            {
                                id: "pimcore_portal_col0_" + this.key,
                                columnWidth: 0.5,
                                style:'padding:10px',
                                items:[config[0]]
                            },
                            {
                                id: "pimcore_portal_col1_" + this.key,
                                columnWidth: 0.5,
                                style:'padding:10px 10px 10px 0',
                                items:[config[1]]
                            }
                        ]
                        ,listeners: {
                        'drop': function(e) {
                            Ext.Ajax.request({
                                url: "/admin/portal/reorder-widget",
                                params: {
                                    key: this.key,
                                    id: e.panel.portletId,
                                    column: e.columnIndex,
                                    row: e.position
                                }
                            });
                        }.bind(this)
                    }
                    }
                ]
            });

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("layout_portal_" + this.key);
            }.bind(this));

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_portal_" + this.key);

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
                        this.panel.doLayout();
                    }
                }.bind(this)
            });

            this.activePortlets.push(type);
        }
    }

});

