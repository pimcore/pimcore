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


pimcore.registerNS("pimcore.settings.workflow.panel");
pimcore.settings.workflow.panel = Class.create({

    initialize: function () {
        this.panels = {};
        this.getTabPanel();
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_workflows",
                title: t("workflows"),
                iconCls: "pimcore_icon_workflow",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getWorkflowTree(), this.getEditPanel()]
            });

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("workflows");
            }.bind(this));

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_workflows");

            this.panel.updateLayout();
            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getWorkflowTree: function () {
        if (!this.tree) {
            var store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                autoSync: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/workflow-settings/tree/',
                    reader: {
                        type: 'json'
                        //,
                        //totalProperty : 'total',
                        //rootProperty: 'nodes'

                    }
                },
                root: {
                    iconCls: "pimcore_icon_thumbnails"
                }
            });

            this.tree = Ext.create('Ext.tree.Panel', {
                store: store,
                id: "pimcore_panel_thumbnail_tree",
                region: "west",
                autoScroll:true,
                animate:false,
                containerScroll: true,
                width: 200,
                split: true,
                root: {
                    //nodeType: 'async',
                    id: '0',
                    expanded: true,
                    iconCls: "pimcore_icon_thumbnails"

                },
                listeners: this.getTreeNodeListeners(),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("add_workflow"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addField.bind(this)
                        }
                    ]
                }
            });
        }
        this.tree.getRootNode().expand();

        return this.tree;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick' : this.onTreeNodeClick.bind(this),
            'itemcontextmenu': this.onTreeNodeContextmenu.bind(this),
            'beforeitemappend': function (thisNode, newChildNode, index, eOpts) {
                newChildNode.data.qtip = t('id') +  ": " + newChildNode.data.id;
            }
        };

        return treeNodeListeners;
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                activeTab: 0,
                items: [],
                region: 'center'
            });
        }

        return this.editPanel;
    },

    openWorkflow: function(id) {
        try {
            var workflowPanelKey = "workflow_" + id;
            if (this.panels[workflowPanelKey]) {
                this.panels[workflowPanelKey].activate();
            } else {
                var workflowPanel = new pimcore.settings.workflow.item(id, this);
                this.panels[workflowPanelKey] = workflowPanel;
            }
        } catch (e) {
            console.log(e);
        }

    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        if(record.data.id > 0) {
            this.openWorkflow(record.data.id);
        }
    },

    addField: function () {
        Ext.MessageBox.prompt(t('add_workflow'), t('enter_the_name_of_the_new_workflow'),
            this.addFieldComplete.bind(this), null, null, "");
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

    addFieldComplete: function (button, value, object) {
        if (button == "ok" && value.length > 2) {

            var thumbnails = this.tree.getRootNode().childNodes;
            for (var i = 0; i < thumbnails.length; i++) {
                if (thumbnails[i].text == value) {
                    Ext.MessageBox.alert(t('add_workflow'),
                        t('the_key_is_already_in_use_in_this_level_please_choose_an_other_key'));
                    return;
                }
            }

            Ext.Ajax.request({
                url: "/admin/workflow-settings/add",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getStore().load();

                    if(!data || !data.success) {
                        Ext.Msg.alert(t('add_workflow'), t('problem_creating_new_workflow'));
                    } else {
                        this.openWorkflow(data.id);
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_workflow'), t('problem_creating_new_workflow'));
        }
    },

    deleteField: function (tree, record) {
        Ext.Ajax.request({
            url: "/admin/workflow-settings/delete",
            params: {
                id: record.data.id
            }
        });

        this.getEditPanel().removeAll();
        record.remove();
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").setActiveItem("pimcore_workflows");
    }
});





