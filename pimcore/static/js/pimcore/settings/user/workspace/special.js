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

pimcore.registerNS("pimcore.settings.user.workspace.special");

pimcore.settings.user.workspace.special = Class.create({

    initialize: function (callback, data, path) {
        this.callback = callback;
        this.data = data;
        this.path = path;
    },

    show: function() {


        this.tree = new Ext.tree.TreePanel({
            region: "west",
            autoScroll: true,
            split: true,
            reference: this,
            rootVisible: false,
            width: 200
        });

        var rootNode = new Ext.tree.TreeNode( {
            id: "0",
            root: true,
            text: t("base"),
            reference: this,
            leaf: false,
            isTarget: true,
            expanded: true


        });

        this.tree.setRootNode(rootNode);

        var customLayouts = new Ext.tree.TreeNode({
            text: t("custom_layouts"),
            reference: this,
            icon: "/pimcore/static/img/icon/cog_edit.png",
            type: "layouts",
            listeners: {
                click: this.onTreeNodeClick.bind(this)
            }
        });



        var localizedFields = new Ext.tree.TreeNode({
            text: t("localized_fields"),
            reference: this,
            expanded: true
        });

        var localizedFieldsView = new Ext.tree.TreeNode({
            text: t("view"),
            reference: this,
            icon: "/pimcore/static/img/icon/cog_edit.png",
            type: "lView",
            listeners: {
                click: this.onTreeNodeClick.bind(this)
            }
        });

        var localizedFieldsEdit = new Ext.tree.TreeNode({
            text: t("edit"),
            reference: this,
            type: "lEdit",
            icon: "/pimcore/static/img/icon/cog_edit.png",
            listeners: {
                click: this.onTreeNodeClick.bind(this)
            }
        });


        localizedFields.appendChild(localizedFieldsView);
        localizedFields.appendChild(localizedFieldsEdit);
        rootNode.appendChild(localizedFields);
        rootNode.appendChild(customLayouts);

        this.editPanel = new Ext.Panel({
            region: "center"
        });

        this.configPanel = new Ext.Panel({
            layout: "border",
            items: [this.tree, this.editPanel]

        });


        this.window = new Ext.Window({
            width:600,
            height:600,
            closeAction:'close',
            layout: "fit",
            modal: true,
            items: [this.configPanel],
            title: t("special_settings") + " " + this.data.path,
            bbar: ["->",
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_apply",
                    text: t('apply'),
                    handler: this.applyData.bind(this)
                }
            ]
        });


        this.window.show();
        this.tree.doLayout();

    },



    applyData: function() {
        this.saveCurrentNode();
        this.callback(this.data);
        this.window.close();
    },

    saveCurrentNode: function() {
        if (this.currentNode) {
            var currentType = this.currentNode.getType();
            var currentValue = this.currentNode.getValue();
            this.data[currentType] = currentValue;
        }
    },

    onTreeNodeClick: function(node) {

        var reference = this;
        this.saveCurrentNode();

        reference.editPanel.removeAll();
        reference.currentNode = null;

        if (node.attributes.type == "lView" || node.attributes.type == "lEdit") {
            reference.currentNode = new pimcore.settings.user.workspace.language(node.attributes.type,
                reference.data[node.attributes.type]);
            reference.editPanel.add(reference.currentNode.getLayout());
            reference.editPanel.doLayout();
        } else if (node.attributes.type == "layouts") {
            var fn = reference.onLayoutsClicked.bind(reference);
            fn();
        }
    },

    layoutsReceived: function(response) {
        var data = Ext.decode(response.responseText);
        this.allLayouts = data.data;
        this.openLayoutEditor();

    },

    openLayoutEditor:function() {
        this.currentNode = new pimcore.settings.user.workspace.customlayouts("layouts",
                                                                this.data["layouts"], this.allLayouts);
        this.editPanel.add(this.currentNode.getLayout());
        this.editPanel.doLayout();

    },

    onLayoutsClicked: function() {
        if (!this.allLayouts) {
            Ext.Ajax.request({
                url: "/admin/class/get-all-layouts",
                success: this.layoutsReceived.bind(this)
            });
        } else {
            this.openLayoutEditor();
        }
    }


});