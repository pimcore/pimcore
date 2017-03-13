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

pimcore.registerNS("pimcore.object.objectbrick");
pimcore.object.objectbrick = Class.create(pimcore.object.fieldcollection, {

    getTabPanel: function () {
  
        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_objectbricks",
                title: t("objectbricks"),
                iconCls: "pimcore_icon_objectbricks",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_objectbricks");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("objectbricks");
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
                    url: '/admin/class/objectbrick-tree',
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

            this.tree = Ext.create('Ext.tree.Panel', {
                id: "pimcore_panel_objectbricks_tree",
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
                            text: t("add_objectbrick"),
                            iconCls: "pimcore_icon_objectbricks pimcore_icon_overlay_add",
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

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick': this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
            'beforeitemappend': function (thisNode, newChildNode, index, eOpts) {
                //newChildNode.data.expanded = true;
                newChildNode.data.leaf = true;
                newChildNode.data.iconCls = "pimcore_icon_objectbricks";
            }
        };
        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        this.openBrick(record.data.id);
    },

    openBrick: function (id) {
        if(Ext.getCmp("pimcore_objectbrick_editor_panel_" + id)) {
            this.getEditPanel().setActiveTab(Ext.getCmp("pimcore_objectbrick_editor_panel_" + id));
            return;
        }

        Ext.Ajax.request({
            url: "/admin/class/objectbrick-get",
            params: {
                id: id
            },
            success: this.addFieldPanel.bind(this)
        });
    },

    addFieldPanel: function (response) {

        var data = Ext.decode(response.responseText);
        var fieldPanel = new pimcore.object.objectbricks.field(data, this, this.openBrick.bind(this, data.key), "pimcore_objectbrick_editor_panel_");
        pimcore.layout.refresh();
        
    },


    addField: function () {
        Ext.MessageBox.prompt(t('add_objectbrick'), t('enter_the_name_of_the_new_objectbrick'),
                                                    this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z]+[a-zA-Z0-9]*/);
        var forbiddennames = ["abstract","class","data","folder","list","permissions","resource","concrete",
                                                                                                        "interface"];

        if (button == "ok" && value.length > 2 && regresult == value && !in_array(value, forbiddennames)) {
            Ext.Ajax.request({
                url: "/admin/class/objectbrick-update",
                params: {
                    key: value
                },
                success: function (response) {
                    this.tree.getStore().load();

                    var data = Ext.decode(response.responseText);
                    if(data && data.success) {
                        this.openBrick(data.id);
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_objectbrick'), t('problem_creating_new_objectbrick'));
        }
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").setActiveItem("pimcore_objectbricks");
    },

    deleteField: function (tree, record) {

        Ext.Msg.confirm(t('delete'), t('delete_message'), function(btn){
            if (btn == 'yes'){
                Ext.Ajax.request({
                    url: "/admin/class/objectbrick-delete",
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
