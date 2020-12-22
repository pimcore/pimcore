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

pimcore.registerNS("pimcore.settings.targeting.rules.panel");
pimcore.settings.targeting.rules.panel= Class.create({

    initialize: function() {
        this.treeDataUrl = Routing.generate('pimcore_admin_targeting_rulelist');
    },


    getLayout: function () {

        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t('global_targeting_rules'),
                layout: "border",
                closable: true,
                border: false,
                iconCls: "pimcore_icon_targeting",
                items: [
                    this.getTree(),
                    this.getTabPanel()
                ]
            });
        }

        return this.layout;
    },

    getTree: function () {
        if (!this.tree) {
            this.saveButton = new Ext.Button({
                // save button
                hidden: true,
                text: t("save_order"),
                iconCls: "pimcore_icon_save",
                handler: function () {
                    // this
                    var button = this;

                    // get current order
                    var prio = 0;
                    var rules = {};

                    this.ownerCt.ownerCt.getRootNode().eachChild(function (rule) {
                        prio++;
                        rules[rule.id] = prio;
                    });

                    // save order
                    Ext.Ajax.request({
                        url: Routing.generate('pimcore_admin_targeting_ruleorder'),
                        params: {
                            rules: Ext.encode(rules)
                        },
                        method: "post",
                        success: function () {
                            button.hide();
                        }
                    });
                }
            });

            var store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                autoSync: true,
                proxy: {
                    type: 'ajax',
                    url: this.treeDataUrl,
                    reader: {
                        type: 'json'
                    }
                }
            });

            this.tree = new Ext.tree.TreePanel({
                store: store,
                region: "west",
                useArrows: true,
                autoScroll: true,
                animate: true,
                containerScroll: true,
                width: 200,
                split: true,
                rootVisible: false,
                root: {
                    id: '0'
                },
                viewConfig: {
                    plugins: {
                        ptype: 'treeviewdragdrop'
                    }
                },
                tbar: {
                    cls: 'pimcore_toolbar_border_bottom',
                    items: [
                        {
                            text: t("add"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addTarget.bind(this)
                        },
                        this.saveButton
                    ]
                },
                listeners: this.getTreeNodeListeners()
            });

        }

        return this.tree;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick': this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
            "itemmove": function(tree, oldParent, newParent, index, eOpts ) {
                this.saveButton.show();
            }.bind(this),
            "render": function () {
                this.getRootNode().expand();
            },
            'beforeitemappend': function (thisNode, newChildNode, index, eOpts) {
                var classes = [];
                var iconClasses = ['pimcore_icon_targeting'];

                if (!newChildNode.data.active) {
                    classes.push('pimcore_unpublished');
                }

                //newChildNode.data.expanded = true;
                newChildNode.data.leaf = true;
                newChildNode.data.cls = classes.join(' ');
                newChildNode.data.iconCls = iconClasses.join(' ');
            }
        };

        return treeNodeListeners;
    },

    addTarget: function () {
        Ext.MessageBox.prompt(' ', t('enter_the_name_of_the_new_item'),
                                                this.addTargetComplete.bind(this), null, null, "");
    },

    addTargetComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z0-9_\-]+/);
        if (button == "ok" && value.length > 2 && regresult == value) {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_targeting_ruleadd'),
                method: 'POST',
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getStore().load();

                    if(!data || !data.success) {
                        Ext.Msg.alert(' ', t('failed_to_create_new_item'));
                    } else {
                        this.openTarget(intval(data.id));
                    }
                }.bind(this)
            });
        } else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(' ', t('naming_requirements_3chars'));
        }
    },



    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        this.openTarget(record.data);
    },


    deleteTarget: function (tree, record) {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_targeting_ruledelete'),
            method: 'DELETE',
            params: {
                id: record.data.id
            },
            success: function () {
                this.tree.getStore().load();
            }.bind(this)
        });
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        tree.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.deleteTarget.bind(this, tree, record)
        }));

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },


    openTarget: function (node) {

        if(!is_numeric(node)) {
            node = node.id;
        }


        var existingPanel = Ext.getCmp("pimcore_targeting_panel_" + node);
        if(existingPanel) {
            this.panel.setActiveItem(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_targeting_ruleget'),
            params: {
                id: node
            },
            success: function (response) {
                try {
                    var res = Ext.decode(response.responseText);
                    var item = new pimcore.settings.targeting.rules.item(this, res);
                } catch (e) {
                    console.log(e);
                }
            }.bind(this)
        });

    },

    getTabPanel: function () {
        if (!this.panel) {
            this.panel = new Ext.TabPanel({
                region: "center",
                border: false,
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

        return this.panel;
    }
});
