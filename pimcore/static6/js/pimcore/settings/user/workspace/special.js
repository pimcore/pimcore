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
            rootVisible: false,
            width: 200,
            listeners: {
                itemclick: this.onTreeNodeClick.bind(this)
            }
        });

        var rootNode =  {
            id: "0",
            root: true,
            text: t("base"),
            leaf: false,
            isTarget: true,
            expanded: true
        };

        this.tree.setRootNode(rootNode);

        var customLayouts = {
            text: t("custom_layouts"),
            icon: "/pimcore/static6/img/icon/cog_edit.png",
            type: "layouts",
            leaf: true
        };

        rootNode = this.tree.getRootNode();

        var localizedFields = rootNode.appendChild({
            text: t("localized_fields"),
            expanded: true
        });

        var localizedFieldsView = {
            text: t("view"),
            icon: "/pimcore/static6/img/icon/cog_edit.png",
            type: "lView",
            leaf: true
        };

        var localizedFieldsEdit = {
            text: t("edit"),
            type: "lEdit",
            leaf: true,
            icon: "/pimcore/static6/img/icon/cog_edit.png",
        };


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
        this.tree.updateLayout();

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

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {

        this.saveCurrentNode();

        this.editPanel.removeAll();
        this.currentNode = null;

        if (record.data.type == "lView" || record.data.type == "lEdit") {
            this.currentNode = new pimcore.settings.user.workspace.language(record.data.type,
                this.data[record.data.type]);
            this.editPanel.add(this.currentNode.getLayout());
            this.editPanel.updateLayout();
        } else if (record.data.type == "layouts") {
            var fn = this.onLayoutsClicked.bind(this);
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
        this.editPanel.updateLayout();

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