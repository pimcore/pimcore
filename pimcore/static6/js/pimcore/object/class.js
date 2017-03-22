/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
                iconCls: "pimcore_icon_class",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getClassTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_classes");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("classes");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getClassTree: function () {
        if (!this.tree) {
            this.store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                autoSync: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/class/get-tree/',
                    reader: {
                        type: 'json'

                    },
                    extraParams: {
                        grouped: 1
                    }
                }
            });


            this.tree = Ext.create('Ext.tree.Panel', {
                id: "pimcore_panel_classes_tree",
                store: this.store,
                region: "west",
                autoScroll:true,
                animate:false,
                containerScroll: true,
                width: 200,
                split: true,
                bodyBorder: false,
                root: {
                    id: '0'
                },
                listeners: this.getTreeNodeListeners(),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("add_class"),
                            iconCls: "pimcore_icon_class pimcore_icon_overlay_add",
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
                plugins:
                    [
                        Ext.create('Ext.ux.TabCloseMenu', {
                            showCloseAll: true,
                            showCloseOthers: true
                        }),
                        Ext.create('Ext.ux.TabReorderer', {})
                    ]
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick' : this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
            'beforeitemappend': function (thisNode, newChildNode, index, eOpts) {
                //TODO temporary, until changed on server side
                if (newChildNode.data.qtipCfg) {
                    if (newChildNode.data.qtipCfg.title) {
                        newChildNode.data.qtitle = newChildNode.data.qtipCfg.title;
                    }
                    if (newChildNode.data.qtipCfg.text) {
                        newChildNode.data.qtip = newChildNode.data.qtipCfg.text;
                    } else {
                        newChildNode.data.qtip = ts(newChildNode.data.text);
                    }
                }
            }

        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        if(!record.isLeaf()) {
            return;
        }

        this.openClass(record.data.id);
    },

    openClass: function (id) {
        if(Ext.getCmp("pimcore_class_editor_panel_" + id)) {
            this.getEditPanel().setActiveTab(Ext.getCmp("pimcore_class_editor_panel_" + id));
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

        var classPanel = new pimcore.object.classes.klass(data, this, this.openClass.bind(this, data.id), "pimcore_class_editor_panel_");
        pimcore.layout.refresh();
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        e.stopEvent();
        tree.select();

        if(!record.isLeaf()) {
            return;
        }


        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_class pimcore_icon_overlay_delete",
            handler: this.deleteClass.bind(this, tree, record)
        }));


        menu.showAt(e.pageX, e.pageY);
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

                    this.tree.getStore().load();

                    // update object type store
                    pimcore.globalmanager.get("object_types_store").reload();
                    pimcore.globalmanager.get("object_types_store_create").reload();

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

    deleteClass: function (tree, record) {

        Ext.Msg.confirm(t('delete'), t('delete_message'), function(btn){
            if (btn == 'yes'){
                Ext.Ajax.request({
                    url: "/admin/class/delete",
                    params: {
                        id: record.data.id
                    },
                    success: function () {
                        // refresh the object tree
                        var tree = pimcore.globalmanager.get("layout_object_tree").tree;
                        tree.getStore().load({
                            node: tree.getRootNode()
                        });

                        // update object type store
                        pimcore.globalmanager.get("object_types_store").reload();
                        pimcore.globalmanager.get("object_types_store_create").reload();
                    }
                });

                this.getEditPanel().removeAll();
                record.remove();
            }
        }.bind(this));
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").setActiveItem("pimcore_classes");
    }

});