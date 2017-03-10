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

pimcore.registerNS("pimcore.object.helpers.gridConfigDialog");
pimcore.object.helpers.gridConfigDialog = Class.create({

    data: {},
    brickKeys: [],

    initialize: function (columnConfig, callback, resetCallback) {

        this.config = columnConfig;
        this.callback = callback;
        this.resetCallback = resetCallback;

        if(!this.callback) {
            this.callback = function () {};
        }

        this.configPanel = new Ext.Panel({
            layout: "border",
            items: [this.getLanguageSelection(), this.getSelectionPanel(), this.getClassDefinitionTreePanel()]

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



    resetToDefault: function() {
        if (this.resetCallback) {
            this.resetCallback();
        } else {
            console.log("not supported");
        }
        this.window.close();
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
                obj.key = child.data.key;
                obj.label = child.data.text;
                obj.type = child.data.dataType;
                obj.layout = child.data.layout;
                if (child.data.width) {
                    obj.width = child.data.width;
                }

                this.data.columns.push(obj);
            }.bind(this));
        }

        return this.data;
    },

    getLanguageSelection: function () {

        var storedata = [["default", t("default")]];
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
            xtype: "fieldset",
            layout: 'hbox',
            border: false,
            style: "border-top: none !important;",
            hideLabel: false,
            fieldLabel: t("language"),
            items: [this.languageField]
        };

        if(!this.languagePanel) {
            this.languagePanel = new Ext.form.FormPanel({
                region: "north",
                height: 43,
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
                    leaf: false,
                    isTarget: true,
                    expanded: true,
                    children: childs
                },

                viewConfig: {
                    plugins: {
                        ptype: 'treeviewdragdrop',
                        ddGroup: "columnconfigelement"
                    },
                    listeners: {
                        beforedrop: function (node, data, overModel, dropPosition, dropHandlers, eOpts) {
                            var target = overModel.getOwnerTree().getView();
                            var source = data.view;

                            if (target != source) {
                                var record = data.records[0];

                                if (this.selectionPanel.getRootNode().findChild("key", record.data.key)) {
                                    dropHandlers.cancelDrop();
                                } else {
                                    var copy = Ext.apply({}, record.data)
                                    delete copy.id;
                                    copy = record.createNode(copy);


                                    var ownerTree = this.selectionPanel;

                                    if (record.data.dataType == "classificationstore") {
                                        window.setTimeout(function () {
                                            var ccd = new pimcore.object.classificationstore.columnConfigDialog();
                                            ccd.getConfigDialog(ownerTree, copy, this.selectionPanel);
                                        }.bind(this), 100);
                                    }
                                    data.records = [copy]; // assign the copy as the new dropNode
                                }
                            }
                        }.bind(this),
                        options: {
                            target: this.selectionPanel
                        }
                    }
                },
                id:'tree',
                region:'east',
                title: t('selected_grid_columns'),
                layout:'fit',
                width: 428,
                split:true,
                autoScroll: true,
                listeners:{
                    itemcontextmenu: this.onTreeNodeContextmenu.bind(this),
                    itemdblclick: function(node, record) {
                        this.selectionPanel.getRootNode().removeChild(record, true);
                    }.bind(this)
                },
                buttons: [
                    {
                        xtype: "button",
                        text: t("reset_config"),
                        iconCls: "pimcore_icon_cancel",
                        tooltip: t('reset_config_tooltip'),
                        style: {
                            marginLeft: 100
                        },
                        handler: function () {
                            this.resetToDefault();
                        }.bind(this)
                    },
                    {
                    text: t("apply"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.commitData();
                    }.bind(this)
                }]
            });
            var store = this.selectionPanel.getStore();
            var model = store.getModel();
            model.setProxy({
                type: 'memory'
            });
        }

        return this.selectionPanel;
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        e.stopEvent();

        tree.select();

        var menu = new Ext.menu.Menu();

        if (this.id != 0) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function(node) {
                    this.selectionPanel.getRootNode().removeChild(record, true);
                }.bind(this, record)
            }));
        }

        menu.showAt(e.pageX, e.pageY);
    },


    getClassDefinitionTreePanel: function () {
        if (!this.classDefinitionTreePanel) {

            var items = [];

            this.brickKeys = [];
            this.classDefinitionTreePanel = this.getClassTree("/admin/class/get-class-definition-for-column-config",
                this.config.classid, this.config.objectId);
        }

        return this.classDefinitionTreePanel;
    },

    getClassTree: function(url, classId, objectId) {

        var classTreeHelper = new pimcore.object.helpers.classTree(true);
        var tree = classTreeHelper.getClassTree(url, classId, objectId);

        tree.addListener("itemdblclick", function(tree, record, item, index, e, eOpts ) {
            if(!record.data.root && record.datatype != "layout"
                && record.data.dataType != 'localizedfields') {
                var copy = Ext.apply({}, record.data);

                if(this.selectionPanel && !this.selectionPanel.getRootNode().findChild("key", record.data.key)) {
                    delete copy.id;
                    copy = this.selectionPanel.getRootNode().appendChild(copy);

                    var ownerTree = this.selectionPanel;

                    if (record.data.dataType == "classificationstore") {
                        var ccd = new pimcore.object.classificationstore.columnConfigDialog();
                        ccd.getConfigDialog(ownerTree, copy, this.selectionPanel);
                    }
                }
            }
        }.bind(this));

        return tree;
    }

});
