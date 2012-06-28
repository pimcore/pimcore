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

pimcore.registerNS("pimcore.object.fieldcollection");
pimcore.object.fieldcollection = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_fieldcollections",
                title: t("field_collections"),
                iconCls: "pimcore_icon_fieldcollections",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_fieldcollections");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("fieldcollections");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getTree: function () {
        if (!this.tree) {
            this.tree = new Ext.tree.TreePanel({
                id: "pimcore_panel_fieldcollections_tree",
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
                    dataUrl: '/admin/class/fieldcollection-tree',
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: this.getTreeNodeListeners(),
                        reference: this,
                        allowDrop: false,
                        allowChildren: false,
                        isTarget: false,
                        iconCls: "pimcore_icon_fieldcollection",
                        leaf: true
                    }
                }),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("add_fieldcollection"),
                            iconCls: "pimcore_icon_fieldcollection_add",
                            handler: this.addField.bind(this)
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

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                region: "center"
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'click' : this.onTreeNodeClick.bind(this),
            "contextmenu": this.onTreeNodeContextmenu
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (node) {
        this.openFieldcollection(node.id);
    },

    openFieldcollection: function (id) {
        Ext.Ajax.request({
            url: "/admin/class/fieldcollection-get",
            params: {
                id: id
            },
            success: this.addFieldPanel.bind(this)
        });
    },

    addFieldPanel: function (response) {

        var data = Ext.decode(response.responseText);

        /*if (this.fieldPanel) {
            this.getEditPanel().removeAll();
            delete this.fieldPanel;
        }*/

        var fieldPanel = new pimcore.object.fieldcollections.field(data, this);
        pimcore.layout.refresh();
        
    },

    onTreeNodeContextmenu: function () {
        this.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.attributes.reference.deleteField.bind(this)
        }));

        menu.show(this.ui.getAnchor());
    },

    addField: function () {
        Ext.MessageBox.prompt(t('add_fieldcollection'), t('enter_the_name_of_the_new_fieldcollection'), this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z]+/);
        var forbiddennames = ["abstract","class","data","folder","list","permissions","resource","concrete","interface"];

        if (button == "ok" && value.length > 2 && regresult == value && !in_array(value, forbiddennames)) {
            Ext.Ajax.request({
                url: "/admin/class/fieldcollection-update",
                params: {
                    key: value
                },
                success: function (response) {
                    this.tree.getRootNode().reload();

                    var data = Ext.decode(response.responseText);
                    if(data && data.success) {
                        this.openFieldcollection(data.id);
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_fieldcollection'), t('problem_creating_new_fieldcollection'));
        }
    },

    deleteField: function () {
        Ext.Ajax.request({
            url: "/admin/class/fieldcollection-delete",
            params: {
                id: this.id
            }
        });

        this.attributes.reference.getEditPanel().removeAll();
        this.remove();
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").activate("pimcore_fieldcollections");
    }

});