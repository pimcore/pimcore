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
            xtype: "fieldset",
            layout: 'hbox',
            border: false,
            hideLabel: false,
            fieldLabel: t("language"),
            items: [this.languageField]
        };

        if(!this.languagePanel) {
            this.languagePanel = new Ext.form.FormPanel({
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
                    leaf: false,
                    isTarget: true,
                    expanded: true,
                    children: childs
                },

                viewConfig: {
                    plugins: {
                        ptype: 'treeviewdragdrop',
                        //enableDrag: true,
                        //enableDrop: false,
                        //appendOnly: true,
                        ddGroup: "columnconfigelement"
                    },
                    listeners: {
                        beforedrop: function (node, data, overModel, dropPosition, dropHandlers, eOpts) {
                            var target = eOpts.options.target;
                            var source = data.view;

                            if (target != source) {
                                var record = data.records[0];

                                if (this.selectionPanel.getRootNode().findChild("key", record.data.key)) {
                                    dropHandlers.cancelDrop();
                                } else {
                                    var copy = record.createNode(Ext.apply({}, record.data));

                                    if (record.data.dataType == "keyValue") {
                                        var ccd = new pimcore.object.keyvalue.columnConfigDialog();
                                        ccd.getConfigDialog(copy, this.selectionPanel);
                                        return;
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
                autoScroll:true,
                listeners:{
                    itemcontextmenu: this.onTreeNodeContextmenu.bind(this)
                },
                buttons: [{
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

        tree.addListener("itemdblclick", function(tree, record, item, index, e, eOpts ) {
            if(!record.data.root && record.datatype != "layout"
                && record.data.dataType != 'localizedfields') {
                var copy = Ext.apply({}, record.data);

                if(this.selectionPanel && !this.selectionPanel.getRootNode().findChild("key", record.data.key)) {
                    this.selectionPanel.getRootNode().appendChild(copy);
                }

                if (record.data.dataType == "keyValue") {
                    var ccd = new pimcore.object.keyvalue.columnConfigDialog();
                    ccd.getConfigDialog(copy, this.selectionPanel);
                }
            }
        }.bind(this));

        return tree;
    }

});
