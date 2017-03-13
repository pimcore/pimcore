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
                iconCls: "pimcore_icon_fieldcollection",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_fieldcollections");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("fieldcollections");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getTree: function () {
        if (!this.tree) {
            this.store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                autoSync: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/class/fieldcollection-tree',
                    reader: {
                        type: 'json',
                        totalProperty : 'total',
                        rootProperty: 'nodes'

                    },
                    extraParams: {
                        grouped: 1
                    }
                }
            });

            // iconCls: "pimcore_icon_fieldcollection",

            this.tree = Ext.create('Ext.tree.Panel', {
                id: "pimcore_panel_fieldcollections_tree",
                store: this.store,
                region: "west",
                autoScroll:true,
                animate:false,
                containerScroll: true,
                width: 200,
                split: true,
                root: {
                    id: '0'
                },
                listeners: this.getTreeNodeListeners(),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("add_fieldcollection"),
                            iconCls: "pimcore_icon_fieldcollection pimcore_icon_overlay_add",
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
            this.editPanel = Ext.create('Ext.tab.Panel', {
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
            'beforeitemappend': function( thisNode, newChildNode, index, eOpts ) {
                //newChildNode.data.expanded = true;
                newChildNode.data.leaf = true;
                newChildNode.data.iconCls = "pimcore_icon_fieldcollection";
            }
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        this.openFieldcollection(record.data.id);
    },

    openFieldcollection: function (id) {

        if(Ext.getCmp("pimcore_fieldcollection_editor_panel_" + id)) {

            this.getEditPanel().setActiveTab(Ext.getCmp("pimcore_fieldcollection_editor_panel_" + id));
            return;
        }

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

        var fieldPanel = new pimcore.object.fieldcollections.field(data, this, this.openFieldcollection.bind(this, data.key), "pimcore_fieldcollection_editor_panel_");
        pimcore.layout.refresh();
        
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        e.stopEvent();
        tree.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.deleteField.bind(this, tree, record)
        }));

        menu.showAt(e.pageX, e.pageY);
    },

    addField: function () {
        Ext.MessageBox.prompt(t('add_fieldcollection'), t('enter_the_name_of_the_new_fieldcollection'),
                                                        this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z]+/);
        var forbiddennames = ["abstract","class","data","folder","list","permissions","resource",
                                                        "concrete","interface"];

        if (button == "ok" && value.length > 2 && regresult == value && !in_array(value, forbiddennames)) {
            Ext.Ajax.request({
                url: "/admin/class/fieldcollection-update",
                params: {
                    key: value
                },
                success: function (response) {
                    this.tree.getStore().load();

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

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").setActiveItem("pimcore_fieldcollections");
    },

    deleteField: function (tree, record) {

        Ext.Msg.confirm(t('delete'), t('delete_message'), function(btn){
            if (btn == 'yes'){
                Ext.Ajax.request({
                    url: "/admin/class/fieldcollection-delete",
                    params: {
                        id: record.data.id
                    }
                });

                this.getEditPanel().removeAll();
                record.remove();
            }
        }.bind(this));
    }


});