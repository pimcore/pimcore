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

pimcore.registerNS("pimcore.report.custom.panel");
pimcore.report.custom.panel = Class.create({

    initialize: function () {
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                border: false,
                layout: "border",
                items: [this.getTree(), this.getEditPanel()]
            });

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
                    url: '/admin/reports/custom-report/tree',
                    reader: {
                        type: 'json'
                    }
                },
                root: {
                    iconCls: "pimcore_icon_thumbnails"
                }
            });

            this.tree = new Ext.tree.TreePanel({
                store: store,
                region: "west",
                autoScroll:true,
                animate:false,
                containerScroll: true,
                width: 250,
                split: true,
                root: {
                    id: '0',
                    expanded: true
                },
                rootVisible: false,
                listeners: this.getTreeNodeListeners(),
                tbar: {
                    items: [
                        {
                            text: t("add_custom_report"),
                            iconCls: "pimcore_icon_add",
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
                region: "center",
                plugins: ['tabclosemenu']
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick' : this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
            'beforeitemappend': function( thisNode, newChildNode, index, eOpts ) {
                newChildNode.data.leaf = true;
                newChildNode.data.expaned = true;
                newChildNode.data.iconCls = "pimcore_icon_sql"
            }
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        this.openConfig(record.data.id);
    },

    openConfig: function (id) {

        var existingPanel = Ext.getCmp("pimcore_sql_panel_" + id);
        if(existingPanel) {
            this.editPanel.setActiveTab(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: "/admin/reports/custom-report/get",
            params: {
                name: id
            },
            success: function (response) {
                var data = Ext.decode(response.responseText);

                var fieldPanel = new pimcore.report.custom.item(data, this);
                pimcore.layout.refresh();
            }.bind(this)
        });
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
        Ext.MessageBox.prompt(t('add_custom_report'), t('enter_the_name_of_the_new_report') + "(a-zA-Z-_)",
                                                this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z0-9_\-]+/);
        if (button == "ok" && value.length > 2 && regresult == value) {

            var codes = this.tree.getRootNode().childNodes;
            for (var i = 0; i < codes.length; i++) {
                if (codes[i].text == value) {
                    Ext.MessageBox.alert(t('add_custom_report'),
                                         t('the_key_is_already_in_use_in_this_level_please_choose_an_other_key'));
                    return;
                }
            }

            Ext.Ajax.request({
                url: "/admin/reports/custom-report/add",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getStore().load({
                        node: this.tree.getRootNode()
                    });

                    if(!data || !data.success) {
                        Ext.Msg.alert(t('add_custom_report'), t('problem_creating_new_custom_report'));
                    } else {
                        this.openConfig(data.id);
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_custom_report'), t('problem_creating_new_custom_report'));
        }
    },

    deleteField: function (tree, record) {
        Ext.Ajax.request({
            url: "/admin/reports/custom-report/delete",
            params: {
                name: record.data.id
            }
        });

        this.getEditPanel().removeAll();
        record.remove();
    }
});

