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

pimcore.registerNS("pimcore.settings.targeting.personas.panel");
pimcore.settings.targeting.personas.panel= Class.create({

    initialize: function() {
        this.treeDataUrl = '/admin/reports/targeting/persona-list/';
    },


    getLayout: function () {

        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t('personas'),
                layout: "border",
                closable: true,
                border: false,
                iconCls: "pimcore_icon_personas",
                items: [this.getTree(), this.getTabPanel()]
            });
        }

        return this.layout;
    },

    getTree: function () {
        if (!this.tree) {
            this.tree = new Ext.tree.TreePanel({
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                containerScroll: true,
                border: true,
                width: 200,
                split: true,
                root: {
                    nodeType: 'async',
                    id: '0'
                },
                loader: new Ext.tree.TreeLoader({
                    dataUrl: this.treeDataUrl,
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: {
                            "click": this.openPersona.bind(this),
                            "contextmenu": function () {
                                this.select();

                                var menu = new Ext.menu.Menu();
                                menu.add(new Ext.menu.Item({
                                    text: t('delete'),
                                    iconCls: "pimcore_icon_delete",
                                    handler: this.attributes.reference.deletePersona.bind(this)
                                }));

                                menu.show(this.ui.getAnchor());
                            }
                        },
                        reference: this,
                        allowDrop: false,
                        allowChildren: false,
                        isTarget: false,
                        iconCls: "pimcore_icon_personas",
                        leaf: true
                    }
                }),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("add_persona"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addPersona.bind(this)
                        }
                    ]
                }
            });

            this.tree.on("render", function () {
                this.getRootNode().expand();
            });
        }

        return this.tree;
    },

    addPersona: function () {
        Ext.MessageBox.prompt(t('add_persona'), t('enter_the_name_of_the_new_persona'),
                                                this.addPersonaComplete.bind(this), null, null, "");
    },

    addPersonaComplete: function (button, value, object) {

        if (button == "ok" && value.length > 2) {
            Ext.Ajax.request({
                url: "/admin/reports/targeting/persona-add",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getRootNode().reload();

                    if(!data || !data.success) {
                        Ext.Msg.alert(t('add_persona'), t('problem_creating_new_persona'));
                    } else {
                        this.openPersona(intval(data.id));

                        pimcore.globalmanager.get("personas").reload();
                    }
                }.bind(this)
            });
        } else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_persona'), t('naming_requirements_3chars'));
        }
    },

    deletePersona: function () {
        Ext.Ajax.request({
            url: "/admin/reports/targeting/persona-delete",
            params: {
                id: this.id
            },
            success: function () {
                this.attributes.reference.tree.getRootNode().reload();

                pimcore.globalmanager.get("personas").reload();
            }.bind(this)
        });
    },

    openPersona: function (node) {

        if(!is_numeric(node)) {
            node = node.id;
        }


        var existingPanel = Ext.getCmp("pimcore_personas_panel_" + node);
        if(existingPanel) {
            this.panel.activate(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: "/admin/reports/targeting/persona-get",
            params: {
                id: node
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                var item = new pimcore.settings.targeting.personas.item(this, res);
            }.bind(this)
        });

    },

    getTabPanel: function () {
        if (!this.panel) {
            this.panel = new Ext.TabPanel({
                region: "center",
                border: false
            });
        }

        return this.panel;
    }
});