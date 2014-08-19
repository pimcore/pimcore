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

pimcore.registerNS("pimcore.object.klass");
pimcore.object.klass = Class.create({

    forbiddennames: ["abstract","class","data","folder","list","permissions","resource","concrete","interface",
                    "service", "fieldcollection", "localizedfield", "objectbrick"],


    initialize: function () {

        this.getTabPanel();
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_classes",
                title: t("classes"),
                iconCls: "pimcore_icon_classes",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getClassTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_classes");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("classes");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getClassTree: function () {
        if (!this.tree) {
            this.tree = new Ext.tree.TreePanel({
                id: "pimcore_panel_classes_tree",
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
                    dataUrl: '/admin/class/get-tree/',
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: this.getTreeNodeListeners(),
                        reference: this,
                        allowDrop: false,
                        allowChildren: false,
                        isTarget: false,
                        leaf: true
                    },
                    baseParams: {
                        grouped: 1
                    }
                }),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("add_class"),
                            iconCls: "pimcore_icon_class_add",
                            handler: this.addClass.bind(this)
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
                region: "center",
                enableTabScroll:true
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
        if(!node.isLeaf()) {
            return;
        }

        this.openClass(node.id);
    },

    openClass: function (id) {
        if(Ext.getCmp("pimcore_class_editor_panel_" + id)) {
            this.getEditPanel().activate(Ext.getCmp("pimcore_class_editor_panel_" + id));
            return;
        }

        if (id > 0) {
            Ext.Ajax.request({
                url: "/admin/class/get",
                params: {
                    id: id
                },
                success: this.addClassPanel.bind(this)
            });
        }
    },

    addClassPanel: function (response) {

        var data = Ext.decode(response.responseText);

        /*if (this.classPanel) {
            this.getEditPanel().removeAll();
            delete this.classPanel;
        }*/

        var classPanel = new pimcore.object.classes.klass(data, this, this.openClass.bind(this, data.id));
        pimcore.layout.refresh();
    },

    onTreeNodeContextmenu: function (node) {

        if(!node.isLeaf()) {
            return;
        }

        this.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_class_delete",
            handler: this.attributes.reference.deleteClass.bind(this)
        }));

        menu.show(this.ui.getAnchor());
    },

    addClass: function () {
        Ext.MessageBox.prompt(t('add_class'), t('enter_the_name_of_the_new_class'), this.addClassComplete.bind(this),
                                                        null, null, "");
    },

    addClassComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z][a-zA-Z0-9]+/);

        if (button == "ok" && value.length > 2 && regresult == value
                                                && !in_array(value.toLowerCase(), this.forbiddennames)) {
            Ext.Ajax.request({
                url: "/admin/class/add",
                params: {
                    name: value
                },
                success: function (response) {

                    this.tree.getRootNode().reload();

                    // update object type store
                    pimcore.globalmanager.get("object_types_store").reload();

                    var data = Ext.decode(response.responseText);
                    if(data && data.success) {
                        this.openClass(data.id);
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_class'), t('invalid_class_name'));
        }
    },

    deleteClass: function () {

        Ext.Msg.confirm(t('delete'), t('delete_message'), function(btn){
            if (btn == 'yes'){
                Ext.Ajax.request({
                    url: "/admin/class/delete",
                    params: {
                        id: this.id
                    },
                    success: function () {
                        // refresh the object tree
                        pimcore.globalmanager.get("layout_object_tree").tree.getRootNode().reload();

                        // update object type store
                        pimcore.globalmanager.get("object_types_store").reload();
                    }
                });

                this.attributes.reference.getEditPanel().removeAll();
                this.remove();
            }
        }.bind(this));
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").activate("pimcore_classes");
    }

});