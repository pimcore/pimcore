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

pimcore.registerNS("pimcore.report.qrcode.panel");
pimcore.report.qrcode.panel = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_qrcode");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_qrcode",
                title: t("qr_codes"),
                iconCls: "pimcore_icon_qrcode",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_qrcode");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("qrcode");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },
    
    getTree: function () {
        if (!this.tree) {
            var store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                autoSync: true,
                proxy: {
                    type: 'ajax',
                    url: "/admin/reports/qrcode/tree",
                    reader: {
                        type: 'json'
                    }
                },
                root: {
                    iconCls: "pimcore_icon_thumbnails"
                }
            });

            this.tree = Ext.create('Ext.tree.Panel', {
                id: "pimcore_panel_qrcode_tree",
                store: store,
                region: "west",
                autoScroll:true,
                animate:false,
                containerScroll: true,
                width: 250,
                split: true,
                root: {
                    id: '0'
                },
                rootVisible: false,
                tbar: Ext.create('Ext.Toolbar', {
                    cls: 'main-toolbar',
                    items: [
                        {
                            text: t("add_qr_code"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addField.bind(this)
                        }
                    ]
                }),
                listeners: this.getTreeNodeListeners()
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
            'itemclick': this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
            "render": function () {
                this.getRootNode().expand();
            },
            'beforeitemappend': function (thisNode, newChildNode, index, eOpts) {
                //newChildNode.data.expanded = true;
                newChildNode.data.leaf = true;
                newChildNode.data.iconCls = "pimcore_icon_qrcode";
            }
        }
        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        this.openCode(record.data.id);
    },

    openCode: function (id) {

        var existingPanel = Ext.getCmp("pimcore_qrcode_panel_" + id);
        if(existingPanel) {
            this.editPanel.setActiveItem(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: "/admin/reports/qrcode/get",
            params: {
                name: id
            },
            success: function (response) {
                try {
                    var data = Ext.decode(response.responseText);

                    var fieldPanel = new pimcore.report.qrcode.item(data, this);
                    pimcore.layout.refresh();
                } catch (e) {
                    console.log(e);
                }
            }.bind(this)
        });
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        tree.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.deleteField.bind(this, tree, record)
        }));

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    addField: function () {
        Ext.MessageBox.prompt(t('add_qr_code'), t('enter_the_name_of_the_new_qrcode') + "(a-zA-Z-_)",
                                                this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z0-9_\-]+/);
        if (button == "ok" && value.length > 2 && regresult == value) {

            var codes = this.tree.getRootNode().childNodes;
            for (var i = 0; i < codes.length; i++) {
                if (codes[i].text == value) {
                    Ext.MessageBox.alert(t('add_thumbnail'),
                                         t('the_key_is_already_in_use_in_this_level_please_choose_an_other_key'));
                    return;
                }
            }

            Ext.Ajax.request({
                url: "/admin/reports/qrcode/add",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getStore().load();

                    if(!data || !data.success) {
                        Ext.Msg.alert(t('add_qr_code'), t('problem_creating_new_qrcode'));
                    } else {
                        this.openCode(data.id);
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('qr_codes'), t('naming_requirements_3chars'));
        }
    },

    deleteField: function (tree, record) {
        Ext.Ajax.request({
            url: "/admin/reports/qrcode/delete",
            params: {
                name: record.data.id
            }
        });

        this.getEditPanel().removeAll();
        record.remove();
    }
});

