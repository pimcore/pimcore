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

pimcore.registerNS("pimcore.object.helpers.gridConfigDialog");
pimcore.object.helpers.gridConfigDialog = Class.create({

    data: {},
    brickKeys: [],

    initialize: function (columnConfig, callback) {

        this.config = columnConfig;
        this.callback = callback;

        if(!this.callback) {
            this.callback = function () {};
        }

        this.configPanel = new Ext.Panel({
            layout: "border",
            items: [this.getLanguageSelection(), this.getSelectionPanel(), this.getResultPanel()]

        });

        this.window = new Ext.Window({
            width: 850,
            height: 550,
            modal: true,
            title: t('grid_column_config'),
            layout: "fit",
            items: [this.configPanel]
        });

        this.window.show();
    },


    commitData: function () {
        var data = this.getData();
        this.callback(data);
        this.window.close();
    },

    getData: function () {

        this.data = {};
        if(this.languageField) {
            this.data.language = this.languageField.getValue();
        }

        if(this.selectionPanel) {
            this.data.columns = [];
            this.selectionPanel.getRootNode().eachChild(function(child) {
                var obj = {};
                obj.key = child.attributes.key;
                obj.label = child.attributes.text;
                obj.type = child.attributes.dataType;
                obj.layout = child.attributes.layout;
                if (child.attributes.width) {
                    obj.width = child.attributes.width;
                }

                this.data.columns.push(obj);
            }.bind(this));
        }

        return this.data;
    },

    getLanguageSelection: function () {

        var storedata = [];
        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {
            storedata.push([pimcore.settings.websiteLanguages[i],
                pimcore.available_languages[pimcore.settings.websiteLanguages[i]]]);
        }

        this.languageField = new Ext.form.ComboBox({
            name: "language",
            width: 330,
            mode: 'local',
            autoSelect: true,
            editable: false,
            value: this.config.language,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'id',
                    'label'
                ],
                data: storedata
            }),
            triggerAction: 'all',
            valueField: 'id',
            displayField: 'label'
        });


        var compositeConfig = {
            xtype: "compositefield",
            hideLabel: false,
            fieldLabel: t("language"),
            items: [this.languageField]
        };

        if(!this.languagePanel) {
            this.languagePanel = new Ext.form.FormPanel({
                layout: "pimcoreform",
                region: "north",
                bodyStyle: "padding: 5px;",
                height: 35,
                items: [compositeConfig]
            });
        }

        return this.languagePanel;
    },

    getSelectionPanel: function () {
        if(!this.selectionPanel) {


            var childs = [];
            for (var i = 0; i < this.config.selectedGridColumns.length; i++) {
                var nodeConf = this.config.selectedGridColumns[i];
                var child = {
                    text: nodeConf.label,
                    key: nodeConf.key,
                    type: "data",
                    dataType: nodeConf.dataType,
                    leaf: true,
                    layout: nodeConf.layout,
                    iconCls: "pimcore_icon_" + nodeConf.dataType
                };
                if (nodeConf.width) {
                    child.width = nodeConf.width;
                }
                childs.push(child);
            }

            this.selectionPanel = new Ext.tree.TreePanel({
                root: {
                    id: "0",
                    root: true,
                    text: t("selected_grid_columns"),
                    reference: this,
                    leaf: false,
                    isTarget: true,
                    expanded: true,
                    children: childs
                },

                enableDD:true,
                ddGroup: "columnconfigelement",
                id:'tree',
                region:'east',
                title: t('selected_grid_columns'),
                layout:'fit',
                width: 428,
                split:true,
                autoScroll:true,
                listeners:{
                    beforenodedrop: function(e) {
                        if(e.source.tree.el != e.target.ownerTree.el) {
                            if(this.selectionPanel.getRootNode().findChild("key", e.dropNode.attributes.key)) {
                                e.cancel= true;
                            } else {
                                var n = e.dropNode; // the node that was dropped
                                var copy = new Ext.tree.TreeNode( // copy it
                                    Ext.apply({}, n.attributes)
                                );
                                e.dropNode = copy; // assign the copy as the new dropNode

                                if (e.dropNode.attributes.dataType == "keyValue") {

                                    var ccd = new pimcore.object.keyvalue.columnConfigDialog();
                                    ccd.getConfigDialog(copy, this.selectionPanel);
                                    return;
                                }
                            }
                        }
                    }.bind(this),
                    contextmenu: this.onTreeNodeContextmenu.bind(this)
                },
                buttons: [{
                    text: t("apply"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.commitData();
                    }.bind(this)
                }]
            });

        }

        return this.selectionPanel;
    },

    onTreeNodeContextmenu: function (node) {
        node.select();

        var menu = new Ext.menu.Menu();

        if (this.id != 0) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function(node) {
                    this.selectionPanel.getRootNode().removeChild(node, true);
                }.bind(this, node)
            }));
        }

        menu.show(node.ui.getEl());
    },


    getResultPanel: function () {
        if (!this.resultPanel) {

            var items = [];

            this.brickKeys = [];
            this.resultPanel = this.getClassTree("/admin/class/get-class-definition-for-column-config",
                this.config.classid, this.config.objectId);
        }

        return this.resultPanel;
    },

    getClassTree: function(url, classId, objectId) {

        var classTreeHelper = new pimcore.object.helpers.classTree(true);
        var tree = classTreeHelper.getClassTree(url, classId, objectId);

        tree.addListener("dblclick", function(node) {
            if(!node.attributes.root && node.attributes.type != "layout"
                && node.attributes.dataType != 'localizedfields') {
                var copy = new Ext.tree.TreeNode( // copy it
                    Ext.apply({}, node.attributes)
                );

                if(this.selectionPanel && !this.selectionPanel.getRootNode().findChild("key", copy.attributes.key)) {
                    this.selectionPanel.getRootNode().appendChild(copy);
                }

                if (copy.attributes.dataType == "keyValue") {
                    var ccd = new pimcore.object.keyvalue.columnConfigDialog();
                    ccd.getConfigDialog(copy, this.selectionPanel);
                }
            }
        }.bind(this));

        return tree;
    }

});
