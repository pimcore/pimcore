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

pimcore.registerNS("pimcore.object.helpers.gridConfigDialog");
pimcore.object.helpers.gridConfigDialog = Class.create(pimcore.element.helpers.gridConfigDialog, {

    brickKeys: [],

    getLeftPanel: function () {
        if (!this.leftPanel) {

            var items = this.getOperatorTrees();
            items.unshift(this.getClassDefinitionTreePanel());


            this.brickKeys = [];
            this.leftPanel = new Ext.Panel({
                cls: "pimcore_panel_tree pimcore_gridconfig_leftpanel",
                region: "center",
                split: true,
                width: 300,
                minSize: 175,
                collapsible: true,
                collapseMode: 'header',
                collapsed: false,
                animCollapse: false,
                layout: 'accordion',
                hideCollapseTool: true,
                header: false,
                layoutConfig: {
                    animate: false
                },
                hideMode: "offsets",
                items: items
            });
        }

        return this.leftPanel;
    },

    commitData: function (save, preview) {

        this.data = {};
        if (this.languageField) {
            this.data.language = this.languageField.getValue();
        }

        if (this.itemsPerPage) {
            this.data.pageSize = this.itemsPerPage.getValue();
        }

        var operatorFound = false;

        if (this.selectionPanel) {
            this.data.columns = [];
            this.selectionPanel.getRootNode().eachChild(function (child) {
                var obj = {};

                if (child.data.isOperator) {
                    var attributes = child.data.configAttributes;
                    var operatorChilds = this.doGetRecursiveData(child);
                    attributes.childs = operatorChilds;
                    operatorFound = true;

                    obj.isOperator = true;
                    obj.attributes = attributes;

                } else {
                    obj.key = child.data.key;
                    obj.label = child.data.layout ? child.data.layout.title : child.data.text;
                    obj.type = child.data.dataType;
                    obj.layout = child.data.layout;
                    if (child.data.width) {
                        obj.width = child.data.width;
                    }
                }

                if (child.data.locked) {
                    obj.locked = child.data.locked;
                }

                this.data.columns.push(obj);
            }.bind(this));
        }

        var user = pimcore.globalmanager.get("user");

        if (this.showSaveAndShareTab) {
            this.settings = Ext.apply(this.settings, this.settingsForm.getForm().getFieldValues());
        }

        if (this.showSaveAndShareTab && user.isAllowed("share_configurations")) {

            if (this.settings.sharedUserIds != null) {
                this.settings.sharedUserIds = this.settings.sharedUserIds.join();
            }
            if (this.settings.sharedRoleIds != null) {
                this.settings.sharedRoleIds = this.settings.sharedRoleIds.join();
            }
            this.settings.shareGlobally = this.shareGlobally ? this.shareGlobally.getValue() : false;
        } else {
            delete this.settings.sharedUserIds;
            delete this.settings.sharedRoleIds;
        }

        if (!operatorFound) {
            if (preview) {
                this.requestPreview();
            } else {
                this.callback(this.data, this.settings, save, this.context);
                this.window.close();
            }
        } else {
            var columnsPostData = Ext.encode(this.data.columns);
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_dataobjecthelper_preparehelpercolumnconfigs'),
                method: 'POST',
                params: {
                    columns: columnsPostData
                },
                success: function (preview, response) {
                    var responseData = Ext.decode(response.responseText);
                    this.data.columns = responseData.columns;

                    if (preview) {
                        this.requestPreview();
                    } else {
                        this.callback(this.data, this.settings, save, this.context);
                        this.window.close();
                    }

                }.bind(this, preview)
            });
        }
    },

    requestPreview: function () {
        if (!this.previewSettings.objectId) {
            return;
        }

        var language = this.languageField.getValue();
        var fields = this.data.columns;
        var count = fields.length;
        var i;
        var keys = [];
        for (i = 0; i < count; i++) {
            var item = fields[i];
            keys.push(item.key);
        }

        let csvMode = this.previewSettings && this.previewSettings.csvMode;

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_dataobject_gridproxy', {classId: this.previewSettings.classId, folderId: this.previewSettings.objectId}),
            method: 'POST',
            params: {
                "fields[]": keys,
                language: language,
                limit: 1,
                csvMode: csvMode,
                specificId: this.previewSettings.specificId,
                context : Ext.encode(this.context)
            },
            success: function (response) {
                let responseData = Ext.decode(response.responseText);
                if (responseData && responseData.data && responseData.data.length == 1) {
                    let rootNode = this.selectionPanel.getRootNode()
                    let childNodes = rootNode.childNodes;
                    let previewItem = responseData.data[0];
                    let store = this.selectionPanel.getStore()
                    let i;
                    let count = childNodes.length;

                    for (i = 0; i < count; i++) {
                        let node = childNodes[i];
                        let nodeId = node.id;
                        let column = this.data.columns[i];

                        let columnKey = column.key;
                        let value = previewItem[columnKey];

                        let record = store.getById(nodeId);
                        record.set("preview", value, {
                            commit: true
                        });
                    }
                }

            }.bind(this)
        });
    },

    getSelectionPanel: function () {
        if (!this.selectionPanel) {

            var childs = [];
            for (var i = 0; i < this.config.selectedGridColumns.length; i++) {
                var nodeConf = this.config.selectedGridColumns[i];

                if (nodeConf.isOperator) {
                    var child = this.doBuildChannelConfigTree([nodeConf.attributes]);
                    if (!child || !child[0]) {
                        continue;
                    }
                    child = child[0];

                } else {
                    var text = t(nodeConf.label);

                    if (nodeConf.dataType !== "system" && this.showFieldname && nodeConf.key) {
                        text = text + " (" + nodeConf.key.replace("~", ".") + ")";
                    }

                    var child = {
                        text: text,
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
                }

                if (nodeConf.locked) {
                    child.locked = nodeConf.locked;
                }

                childs.push(child);
            }

            this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1
            });

            var store = new Ext.data.TreeStore({
                fields: [{
                    name: "text"
                }, {
                    name: "preview",
                    persist: false
                }

                ],
                root: {
                    id: "0",
                    root: true,
                    text: t("selected_grid_columns"),
                    leaf: false,
                    isTarget: true,
                    expanded: true,
                    children: childs
                }
            });

            var columns = [
                {
                    xtype: 'treecolumn',
                    text: t('configuration'),
                    dataIndex: 'text',
                    flex: 90
                }
            ];

            if (this.previewSettings && this.previewSettings.allowPreview) {
                columns.push({
                    dataIndex: 'preview',
                    text: t('preview'),
                    flex: 90,
                    renderer: function (value, metaData, record) {
                        if (this.previewSettings && this.previewSettings.csvMode) {
                            return value;
                        }

                        if (record && record.parentNode.id == 0) {

                            var key = record.data.key;
                            record.data.inheritedFields = {};

                            if (key == "modificationDate" || key == "creationDate") {
                                var timestamp = intval(value) * 1000;
                                var date = new Date(timestamp);
                                return Ext.Date.format(date, "Y-m-d H:i");

                            } else if (key == "published") {
                                return Ext.String.format('<div style="text-align: left"><div role="button" class="x-grid-checkcolumn{0}" style=""></div></div>', value ? '-checked' : '');
                            } else {
                                var layout = Ext.clone(record.data.layout) || {};
                                var fieldType = record.data.dataType;

                                try {
                                    if (record.data.isOperator && record.data.configAttributes && pimcore.object.tags[record.data.configAttributes.renderer]) {
                                        var rendererType = record.data.configAttributes.renderer;
                                        var tag = pimcore.object.tags[rendererType];
                                    } else {
                                        var tag = pimcore.object.tags[fieldType];
                                    }

                                    if (tag) {
                                        layout.noteditable = true;
                                        var fc = tag.prototype.getGridColumnConfig({
                                            key: key,
                                            layout: layout
                                        }, true);

                                        if (fc.renderer) {
                                            value = fc.renderer(value, null, record);
                                        }
                                    }
                                } catch (e) {
                                    console.log(e);
                                }

                                if (typeof value == "string") {
                                    value = '<div style="max-height: 50px">' + value + '</div>';
                                }
                                return value;
                            }
                        }

                    }.bind(this)
                });
            }

            this.selectionPanel = new Ext.tree.TreePanel({
                store: store,
                plugins: [this.cellEditing],
                rootVisible: false,
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
                                if (dropPosition == "before" || dropPosition == "after") {
                                    realOverModel = overModel.parentNode;
                                }

                                if (isOperator || this.parentIsOperator(realOverModel)) {
                                    var attr = record.data;
                                    if (record.data.configAttributes) {
                                        attr = record.data.configAttributes;
                                    }
                                    var element = this.getConfigElement(attr);
                                    var copy = element.getCopyNode(record);
                                    data.records = [copy]; // assign the copy as the new dropNode
                                    var configWindow = element.getConfigDialog(copy,
                                        {
                                            callback: this.updatePreview.bind(this)
                                        });

                                    if (configWindow) {
                                        //this is needed because of new focus management of extjs6
                                        setTimeout(function () {
                                            configWindow.focus();
                                        }, 250);
                                    }

                                } else {
                                    if (this.selectionPanel.getRootNode().findChild("key", record.data.key)) {
                                        dropHandlers.cancelDrop();
                                    } else {
                                        var copy = Ext.apply({}, record.data);
                                        delete copy.id;
                                        copy = record.createNode(copy);

                                        var ownerTree = this.selectionPanel;

                                        if (record.data.dataType == "classificationstore") {
                                            setTimeout(function () {
                                                var ccd = new pimcore.object.classificationstore.columnConfigDialog();
                                                ccd.getConfigDialog(ownerTree, copy, this.selectionPanel);
                                            }.bind(this), 100);
                                        }
                                        data.records = [copy]; // assign the copy as the new dropNode
                                    }
                                }
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
                                        // attr = record.data.configAttributes;
                                    }
                                    var element = this.getConfigElement(attr);

                                    var copy = element.getCopyNode(record);
                                    data.records = [copy]; // assign the copy as the new dropNode
                                    var window = element.getConfigDialog(copy, {
                                        callback: this.updatePreview.bind(this)
                                    });

                                    if (window) {
                                        //this is needed because of new focus management of extjs6
                                        setTimeout(function () {
                                            window.focus();
                                        }, 250);
                                    }

                                    record.parentNode.removeChild(record);
                                }
                            }
                        }.bind(this),
                        drop: function (node, data, overModel) {
                            overModel.set('expandable', true);
                            this.updatePreview();

                        }.bind(this),
                        nodedragover: function (targetNode, dropPosition, dragData, e, eOpts) {
                            var sourceNode = dragData.records[0];

                            if (sourceNode.data.isOperator) {
                                var realOverModel = targetNode;
                                if (dropPosition == "before" || dropPosition == "after") {
                                    realOverModel = realOverModel.parentNode;
                                }

                                var allowed = true;


                                if (typeof realOverModel.data.isChildAllowed == "function") {
                                    console.log("no child allowed");
                                    allowed = allowed && realOverModel.data.isChildAllowed(realOverModel, sourceNode);
                                }

                                if(realOverModel.data.maxChildCount) {
                                    if (realOverModel.childNodes.length >= realOverModel.data.maxChildCount) {
                                        allowed = false;
                                    }
                                }

                                if (typeof sourceNode.data.isParentAllowed == "function") {
                                    console.log("parent not allowed");
                                    allowed = allowed && sourceNode.data.isParentAllowed(realOverModel, sourceNode);
                                }


                                return allowed;
                            } else {
                                var targetNode = targetNode;

                                var allowed = true;
                                if (this.parentIsOperator(targetNode)) {
                                    if (dropPosition == "before" || dropPosition == "after") {
                                        targetNode = targetNode.parentNode;
                                    }

                                    if (typeof targetNode.data.isChildAllowed == "function") {
                                        allowed = allowed && targetNode.data.isChildAllowed(targetNode, sourceNode);
                                    }

                                    if(targetNode.data.maxChildCount) {
                                        if (targetNode.childNodes.length >= targetNode.data.maxChildCount) {
                                            allowed = false;
                                        }
                                    }

                                    if (typeof sourceNode.data.isParentAllowed == "function") {
                                        allowed = allowed && sourceNode.data.isParentAllowed(targetNode, sourceNode);
                                    }

                                }

                                return allowed;
                            }
                        }.bind(this),
                        options: {
                            target: this.selectionPanel
                        }
                    }
                },
                id: 'tree',
                region: 'east',
                title: t('selected_grid_columns'),
                layout: 'fit',
                width: 640,
                split: true,
                autoScroll: true,
                rowLines: true,
                columnLines: true,
                listeners: {
                    itemcontextmenu: this.onTreeNodeContextmenu.bind(this)
                },
                columns: columns
            });
            var store = this.selectionPanel.getStore();
            var model = store.getModel();
            model.setProxy({
                type: 'memory'
            });
        }

        return this.selectionPanel;
    },

    getClassDefinitionTreePanel: function () {
        if (!this.classDefinitionTreePanel) {
            this.brickKeys = [];
            this.classDefinitionTreePanel = this.getClassTree(Routing.generate('pimcore_admin_dataobject_class_getclassdefinitionforcolumnconfig'),
                this.config.classid, this.config.objectId);
        }

        return this.classDefinitionTreePanel;
    },

    getClassTree: function (url, classId, objectId) {

        var classTreeHelper = new pimcore.object.helpers.classTree(this.showFieldname);
        var tree = classTreeHelper.getClassTree(url, classId, objectId);

        tree.addListener("itemdblclick", function (tree, record, item, index, e, eOpts) {
            if (!record.data.root && record.data.type != "layout"
                && record.data.dataType != 'localizedfields') {
                var copy = Ext.apply({}, record.data);

                if (this.selectionPanel && !this.selectionPanel.getRootNode().findChild("key", record.data.key)) {
                    delete copy.id;
                    copy = this.selectionPanel.getRootNode().appendChild(copy);

                    var ownerTree = this.selectionPanel;

                    if (record.data.dataType == "classificationstore") {
                        var ccd = new pimcore.object.classificationstore.columnConfigDialog();
                        ccd.getConfigDialog(ownerTree, copy, this.selectionPanel);
                    } else {
                        this.updatePreview();
                    }
                }
            }
        }.bind(this));

        return tree;
    },

    getOperatorTrees: function () {
        var operators = Object.keys(pimcore.object.gridcolumn.operator);
        var operatorGroups = [];
        // var childs = [];
        for (let i = 0; i < operators.length; i++) {
            var operator = operators[i];
            if (!this.availableOperators || this.availableOperators.indexOf(operator) >= 0) {
                var nodeConfig = pimcore.object.gridcolumn.operator[operator].prototype;
                var configTreeNode = nodeConfig.getConfigTreeNode();

                var operatorGroup = nodeConfig.operatorGroup ? nodeConfig.operatorGroup : "other";

                if (!operatorGroups[operatorGroup]) {
                    operatorGroups[operatorGroup] = [];
                }

                var groupName = nodeConfig.group || "other";
                if (!operatorGroups[operatorGroup][groupName]) {
                    operatorGroups[operatorGroup][groupName] = [];
                }
                operatorGroups[operatorGroup][groupName].push(configTreeNode);
            }
        }

        var operatorGroupKeys = [];
        for (let k in operatorGroups) {
            if (operatorGroups.hasOwnProperty(k)) {
                operatorGroupKeys.push(k);
            }
        }
        operatorGroupKeys.sort();
        var result = [];
        var len = operatorGroupKeys.length;
        for (let i = 0; i < len; i++) {
            var operatorGroupName = operatorGroupKeys[i];
            var groupNodes = operatorGroups[operatorGroupName];
            result.push(this.getOperatorTree(operatorGroupName, groupNodes));

        }
        return result;
    },

    getConfigElement: function (configAttributes) {
        var element = null;
        if (configAttributes && configAttributes.class && configAttributes.type) {
            var jsClass = configAttributes.class.toLowerCase();
            if (pimcore.object.gridcolumn[configAttributes.type] && pimcore.object.gridcolumn[configAttributes.type][jsClass]) {
                element = new pimcore.object.gridcolumn[configAttributes.type][jsClass](this.config.classid);
            }
        } else {
            var dataType = configAttributes.dataType.toLowerCase();
            if (pimcore.object.gridcolumn.value[dataType]) {
                element = new pimcore.object.gridcolumn.value[dataType](this.config.classid);
            } else {
                element = new pimcore.object.gridcolumn.value.defaultvalue(this.config.classid);
            }
        }
        return element;
    }
});
