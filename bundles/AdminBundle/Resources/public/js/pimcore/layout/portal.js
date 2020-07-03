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
            url: Routing.generate('pimcore_admin_portal_getconfiguration'),
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
                            if (!dynClass.prototype.isAvailable()) {
                                continue;
                            }

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
            var portletType = portlets[i];

            if (pimcore.settings.disabledPortlets["pimcore.layout.portlets." + portletType]) {
                continue;
            }

            if (!pimcore.layout.portlets[portletType].prototype.isAvailable()) {
                continue;
            }

            if (portletType != "abstract") {
                portletMenu.push({
                    text: pimcore.layout.portlets[portletType].prototype.getName(),
                    iconCls: pimcore.layout.portlets[portletType].prototype.getIcon(),
                    handler: this.addPortlet.bind(this, pimcore.layout.portlets[portletType].prototype.getType())
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
                        iconCls: "pimcore_icon_add",
                        menu: portletMenu
                    },
                    {
                        text: t("delete"),
                        iconCls: "pimcore_icon_delete",
                        hidden: (this.key == "welcome"),
                        handler: function() {
                            Ext.Msg.show({
                                msg: t('delete_message'),
                                buttons: Ext.Msg.YESNO,
                                fn: function(btn) {
                                    if(btn == "yes") {
                                        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                                        tabPanel.remove("pimcore_portal_" + this.key);

                                        Ext.Ajax.request({
                                            url: Routing.generate('pimcore_admin_portal_deletedashboard'),
                                            method: "DELETE",
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
                    url: Routing.generate('pimcore_admin_portal_reorderwidget'),
                    method: 'PUT',
                    params: {
                        key: this.key,
                        id: e.panel.portletId,
                        column: e.columnIndex,
                        row: e.position
                    }
                });

            }.bind(this));

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
                url: Routing.generate('pimcore_admin_portal_addwidget'),
                method: 'POST',
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

