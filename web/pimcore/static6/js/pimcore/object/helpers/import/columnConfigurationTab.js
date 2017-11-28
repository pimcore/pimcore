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

pimcore.registerNS("pimcore.object.helpers.import.columnConfigurationTab");
pimcore.object.helpers.import.columnConfigurationTab = Class.create({

    initialize: function (config, callback) {

        this.config = config;

        this.configPanel = new Ext.Panel({
            layout: "border",
            iconCls: "pimcore_icon_table",
            title: t("csv_column_configuration"),
            items: []
        });

        this.rebuildPanel();
    },

    rebuildPanel: function() {
        this.configPanel.removeAll(true);
        this.selectionPanel = null;
        this.leftPanel = null;
        this.classDefinitionTreePanel = null;
        this.configPanel.add(this.getSelectionPanel());
        this.configPanel.add(this.getLeftPanel());
    },

    getPanel: function () {
        return this.configPanel;
    },

    getLeftPanel: function () {
        if (!this.leftPanel) {

            var items = [
                this.getClassDefinitionTreePanel(),
                this.getOperatorTree()
            ];

            this.brickKeys = [];
            this.leftPanel = new Ext.Panel({
                layout: "border",
                region: "center",
                items: items
            });
        }

        return this.leftPanel;
    },

    resetToDefault: function () {
        if (this.resetCallback) {
            this.resetCallback();
        } else {
            console.log("not supported");
        }
        this.window.close();
    },


    doGetRecursiveData: function (node) {
        var childs = [];
        node.eachChild(function (child) {
            var attributes = child.data.configAttributes;
            attributes.childs = this.doGetRecursiveData(child);
            childs.push(attributes);
        }.bind(this));

        return childs;
    },

    commitData: function () {

        this.config.selectedGridColumns = [];

        var operatorFound = false;

        if (this.selectionPanel) {
            this.selectionPanel.getRootNode().eachChild(function (child) {
                var obj = {};

                var attributes = child.data.configAttributes;
                var operatorChilds = this.doGetRecursiveData(child);
                attributes.childs = operatorChilds;
                operatorFound = true;

                obj.isOperator = child.data.isOperator;
                obj.isValue = child.data.isValue;
                obj.attributes = attributes;

                this.config.selectedGridColumns.push(obj);
            }.bind(this));
        }
    },


    updatePreviewArea: function () {
        var rootNode = this.selectionPanel.getRootNode();

        var dataPreview = this.config.dataPreview;
        if (dataPreview) {
            dataPreview = dataPreview[0];
        }

        var children = rootNode.childNodes;
        if (children && children.length > 0) {
            for (var i = 0; i < children.length; i++) {
                var c = children[i];
                c.set("csvIdx", i, {
                    dirty: false
                });

                var preview = "";
                if (dataPreview && dataPreview["field_" + i]) {
                    preview = dataPreview["field_" + i];
                }
                c.set("csvLabel", preview, {
                    dirty: false
                });
            }
        }
    },

    getSelectionPanel: function () {
        if (!this.selectionPanel) {

            var childs = [];

            for (var i = 0; i < this.config.selectedGridColumns.length; i++) {
                var nodeConf = this.config.selectedGridColumns[i];

                if (nodeConf.isOperator || nodeConf.isValue) {
                    var child = this.doBuildChannelConfigTree([nodeConf.attributes]);
                    if (!child || !child[0]) {
                        continue;
                    }
                    child = child[0];
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
                }
                ,
                columns: [{
                    xtype: 'treecolumn', //this is so we know which column will show the tree
                    text: 'Task',
                    dataIndex: 'text',
                    flex: 2,
                    sortable: true,
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {

                        if (record.data && record.data.configAttributes && record.data.configAttributes.class == "Ignore") {
                            metaData.tdCls += ' pimcore_import_operator_ignore';
                        }

                        return value;
                    }
                },
                    {
                        text: t('col_idx'),
                        dataIndex: 'csvIdx',
                        width: 50,
                        sortable: true
                    },
                    {
                        text: t('col_label'),
                        dataIndex: 'csvLabel',
                        flex: 2,
                        sortable: true
                    }
                ],
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
                                var isOperator = record.data.isOperator;
                                var realOverModel = overModel;
                                var isOverwrite = false;
                                if (dropPosition == "before" || dropPosition == "after") {
                                    realOverModel = overModel.parentNode;
                                } else {
                                    if (typeof realOverModel.data.isOverwriteAllowed == "function") {
                                        if (realOverModel.data.isOverwriteAllowed(realOverModel, record)) {
                                            isOverwrite = true;
                                        }
                                    }
                                }
                               
                                var attr = record.data;
                                if (record.data.configAttributes) {
                                    attr = record.data.configAttributes;
                                }
                                var element = this.getConfigElement(attr);

                                var copy = element.getCopyNode(record);

                                if (attr.key && attr.key.indexOf("~") !== -1) {
                                    var brickOperator = new pimcore.object.importcolumn.operator.objectbricksetter();
                                    var brickNode = brickOperator.getConfigTreeNode();
                                    brickNode.expanded = true;
                                    brickNode.configAttributes.attr = record.data.brickField;
                                    keyParts = attr.key.split("~");
                                    brickNode.configAttributes.brickType = keyParts[0];
                                    brickNode = realOverModel.createNode(brickNode);
                                    brickNode.appendChild(copy);
                                    copy = brickNode;

                                }



                                if (isOverwrite) {
                                    var parentNode = realOverModel.parentNode;
                                    parentNode.replaceChild(copy, realOverModel);
                                    dropHandlers.cancelDrop();
                                    this.updatePreviewArea();

                                } else {
                                    data.records = [copy]; // assign the copy as the new dropNode
                                }
                                this.showConfigWindow(element, copy);

                               
                            } else {
                                // node has been moved inside right selection panel
                                var record = data.records[0];
                                var isOperator = record.data.isOperator;
                                var realOverModel = overModel;
                                if (dropPosition == "before" || dropPosition == "after") {
                                    realOverModel = overModel.parentNode;
                                }

                                if (isOperator || this.parentIsOperator(realOverModel)) {
                                    var attr = record.data;
                                    if (record.data.configAttributes) {
                                        // there is nothing to do, this guy has been configured already
                                        return;
                                    }
                                    var element = this.getConfigElement(attr);
                                    var copy = element.getCopyNode(record);

                                    data.records = [copy]; // assign the copy as the new dropNode
                                    this.showConfigWindow(element, copy);
                                    record.parentNode.removeChild(record);

                                }
                            }
                        }.bind(this),
                        drop: function (node, data, overModel) {

                            var record  = data.records[0];
                            record.set("csvLabel", null, {
                                dirty: false
                            });

                            record.set("csvIdx", null, {
                                dirty: false
                            });

                            this.updatePreviewArea();

                        }.bind(this),
                        nodedragover: function (targetNode, dropPosition, dragData, e, eOpts) {
                            var sourceNode = dragData.records[0];
                            var realOverModel = targetNode;

                            if (dropPosition == "before" || dropPosition == "after") {
                                realOverModel = realOverModel.parentNode;
                            } else {
                                // special handling for replacing nodes
                                if (typeof realOverModel.data.isOverwriteAllowed == "function") {
                                    if (realOverModel.data.isOverwriteAllowed(realOverModel, sourceNode)) {
                                        return true;
                                    }
                                }
                            }

                            var allowed = true;

                            if (typeof realOverModel.data.isChildAllowed == "function") {
                                allowed = allowed && realOverModel.data.isChildAllowed(realOverModel, sourceNode);
                            }

                            if (typeof sourceNode.data.isParentAllowed == "function") {
                                allowed = allowed && sourceNode.data.isParentAllowed(realOverModel, sourceNode);
                            }
                            return allowed;

                        }.bind(this),
                        options: {
                            target: this.selectionPanel
                        }
                    }
                },
                region: 'east',
                title: t('selected_grid_columns'),
                layout: 'fit',
                width: 600,
                split: true,
                autoScroll: true,
                listeners: {
                    itemcontextmenu: this.onTreeNodeContextmenu.bind(this),
                    itemdblclick: function (node, record) {
                        this.selectionPanel.getRootNode().removeChild(record, true);
                    }.bind(this)
                }
            });
            var store = this.selectionPanel.getStore();
            var model = store.getModel();
            model.setProxy({
                type: 'memory'
            });
        }

        this.updatePreviewArea();
        return this.selectionPanel;
    },

    showConfigWindow: function (element, node) {
        var window = element.getConfigDialog(node);

        if (window) {
            //this is needed because of new focus management of extjs6
            setTimeout(function () {
                window.focus();
            }, 250);
        }
    },

    parentIsOperator: function (record) {
        while (record) {
            if (record.data.isOperator) {
                return true;
            }
            record = record.parentNode;
        }
        return false;
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts) {
        e.stopEvent();

        tree.select();

        var rootNode = tree.getStore().getRootNode();

        var menu = new Ext.menu.Menu();

        if (this.id != 0) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (record) {
                    record.parentNode.removeChild(record, true);
                    this.updatePreviewArea();
                }.bind(this, record)
            }));

            if (record.data.children && record.data.children.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('collapse_children'),
                    iconCls: "pimcore_icon_collapse_children",
                    handler: function (node) {
                        record.collapseChildren();
                    }.bind(this, record)
                }));

            }

            if (record.data.isOperator || record.data.isValue) {
                menu.add(new Ext.menu.Item({
                    text: t('edit'),
                    iconCls: "pimcore_icon_edit",
                    handler: function (node) {
                        this.getConfigElement(node.data.configAttributes).getConfigDialog(node);
                    }.bind(this, record)
                }));

                if (record.parentNode == rootNode) {
                    menu.add(new Ext.menu.Item({
                        text: t('ignore'),
                        iconCls: "pimcore_icon_operator_ignore",
                        handler: function (node) {
                            var replacement = pimcore.object.importcolumn.operator.ignore.prototype.getCopyNode(node);
                            var parent = node.parentNode;
                            parent.replaceChild(replacement, node);
                            this.updatePreviewArea();
                        }.bind(this, record)
                    }));
                }

                menu.add(this.getChangeTypeMenu(record));
            }
        }

        menu.showAt(e.pageX, e.pageY);
    },


    getClassDefinitionTreePanel: function () {
        if (!this.classDefinitionTreePanel) {

            var items = [];

            this.brickKeys = [];
            this.classDefinitionTreePanel = this.getClassTree("/admin/class/get-class-definition-for-column-config",
                this.config.classId, 0);
        }

        return this.classDefinitionTreePanel;
    },

    getClassTree: function (url, classId) {

        var classTreeHelper = new pimcore.object.helpers.classTree(true);
        var tree = classTreeHelper.getClassTree(url, classId);

        tree.addListener("itemdblclick", function (tree, record, item, index, e, eOpts) {
            if (!record.data.root && record.datatype != "layout"
                && record.data.dataType != 'localizedfields') {
                var copy = Ext.apply({}, record.data);

                if (this.selectionPanel && !this.selectionPanel.getRootNode().findChild("key", record.data.key)) {
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
    },

    getChangeTypeMenu: function(record) {
        var operators = Object.keys(pimcore.object.importcolumn.operator);
        var childs = [];
        for (var i = 0; i < operators.length; i++) {
            childs.push(pimcore.object.importcolumn.operator[operators[i]].prototype.getConfigTreeNode());
        }

        childs.sort(
            function (x, y) {
                return x.text < y.text ? -1 : 1;
            }
        );

        var menu = [];
        for (var i = 0; i < childs.length; i++) {
            var child = childs[i];
            var item = new Ext.menu.Item({
                text: child.text,
                iconCls: child.iconCls,
                hideOnClick: true,
                handler: function (node, newType) {
                    var jsClass = newType.toLowerCase();
                    var replacement = pimcore.object.importcolumn.operator[jsClass].prototype.getConfigTreeNode();

                    replacement.expanded = node.data.expanded;
                    replacement.expandable = node.data.expandable;
                    replacement.leaf = node.data.leaf;

                    replacement = node.createNode(replacement);
                    replacement.data.configAttributes.label = node.data.configAttributes.label;

                    var parent = node.parentNode;
                    var originalChilds = [];

                    node.eachChild(function(child) {
                        originalChilds.push(child);
                    });


                    node.removeAll();
                    parent.replaceChild(replacement, node);

                    replacement.appendChild(originalChilds);

                    var element = this.getConfigElement(replacement.data.configAttributes);
                    this.showConfigWindow(element, replacement);
                    this.updatePreviewArea();
                }.bind(this, record, child.configAttributes.class)
            });
            menu.push(item);
        }

        var changeTypeItem =  new Ext.menu.Item({
            text: t('change_type'),
            iconCls: "pimcore_icon_convert",
            hideOnClick: false,
            menu: menu
        });
        return changeTypeItem;

    },


    getOperatorTree: function () {
        var operators = Object.keys(pimcore.object.importcolumn.operator);
        var childs = [];
        for (var i = 0; i < operators.length; i++) {
            if (!this.availableOperators || this.availableOperators.indexOf(operators[i]) >= 0) {
                childs.push(pimcore.object.importcolumn.operator[operators[i]].prototype.getConfigTreeNode());
            }
        }

        childs.sort(
            function (x, y) {
                return x.text < y.text ? -1 : 1;
            }
        );

        var tree = new Ext.tree.TreePanel({
            title: t('operators'),
            collapsible: true,
            region: "south",
            autoScroll: true,
            height: 200,
            rootVisible: false,
            resizeable: true,
            split: true,
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    ddGroup: "columnconfigelement",
                    enableDrag: true,
                    enableDrop: false
                }
            },
            root: {
                id: "0",
                root: true,
                text: t("base"),
                draggable: false,
                leaf: false,
                isTarget: false,
                children: childs
            }
        });

        return tree;
    },

    getConfigElement: function (configAttributes) {
        var element = null;
        if (configAttributes && configAttributes.class && configAttributes.type) {
            var jsClass = configAttributes.class.toLowerCase();
            if (pimcore.object.importcolumn[configAttributes.type] && pimcore.object.importcolumn[configAttributes.type][jsClass]) {
                element = new pimcore.object.importcolumn[configAttributes.type][jsClass](this.config.classId);
            }
        } else {
            var dataType = configAttributes.dataType ? configAttributes.dataType.toLowerCase() : null;
            if (dataType && pimcore.object.importcolumn.value[dataType]) {
                element = new pimcore.object.importcolumn.value[dataType](this.config.classId);
            } else {
                element = new pimcore.object.importcolumn.value.defaultvalue(this.config.classId);
            }
        }
        return element;
    },

    doBuildChannelConfigTree: function (configuration) {

        var elements = [];
        if (configuration) {
            for (var i = 0; i < configuration.length; i++) {
                var configElement = this.getConfigElement(configuration[i]);
                if (configElement) {
                    var treenode = configElement.getConfigTreeNode(configuration[i]);

                    if (configuration[i].childs) {
                        var childs = this.doBuildChannelConfigTree(configuration[i].childs);
                        treenode.children = childs;
                        if (childs.length > 0) {
                            treenode.expandable = true;
                        }
                    }
                    elements.push(treenode);
                }
            }
        }
        return elements;
    }
});
