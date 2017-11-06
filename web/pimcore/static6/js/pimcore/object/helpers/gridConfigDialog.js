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
pimcore.object.helpers.gridConfigDialog = Class.create({

    data: {},
    brickKeys: [],

    initialize: function (columnConfig, callback, resetCallback, showSaveAndShareTab, settings) {

        this.config = columnConfig;
        this.callback = callback;
        this.resetCallback = resetCallback;
        this.showSaveAndShareTab = showSaveAndShareTab;
        this.isShared = settings && settings.isShared;

        this.settings = settings;

        if (!this.callback) {
            this.callback = function () {
            };
        }

        this.configPanel = new Ext.Panel({
            layout: "border",
            iconCls: "pimcore_icon_table",
            title: t("grid_configuration"),
            items: [this.getLanguageSelection(), this.getSelectionPanel(), this.getLeftPanel()]

        });


        var tabs = [this.configPanel];

        if (this.showSaveAndShareTab) {
            this.savePanel = this.getSaveAndSharePanel();
            tabs.push(this.savePanel);
        }

        this.tabPanel = new Ext.TabPanel({
            activeTab: 0,
            forceLayout: true,
            items: tabs
        });

        buttons = [];

        if (this.resetCallback) {
            buttons.push(
                {
                    xtype: "button",
                    text: t("reset_config"),
                    // hidden: this.isShared,
                    iconCls: "pimcore_icon_cancel",
                    tooltip: t('reset_config_tooltip'),
                    style: {
                        marginLeft: 100
                    },
                    handler: function () {
                        this.resetToDefault();
                    }.bind(this)
                }
            );
        }

        buttons.push({
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.commitData();
                }.bind(this)
            }
        );

        if (this.showSaveAndShareTab) {
            buttons.push({
                text: this.isShared ? t("save_copy_and_share") : t("save_and_share"),
                    iconCls: "pimcore_icon_save",
                handler: function () {
                if (!this.nameField.getValue()) {
                    Ext.Msg.show({
                        title: t("error"),
                        msg: t('name_must_not_be_empty'),
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.ERROR
                    });
                    return;
                }
                this.commitData(true);
            }.bind(this)
        });
        }

        this.window = new Ext.Window({
            width: 850,
            height: 700,
            modal: true,
            title: t('grid_column_config'),
            layout: "fit",
            items: [this.tabPanel],
            buttons: buttons
        });

        this.window.show();
    },

    getSaveAndSharePanel: function () {

        //TODO values - must not be empty

        this.userStore = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: '/admin/user/get-users',
                reader: {
                    rootProperty: 'data',
                    idProperty: 'id'
                }
            },
            fields: ['id', 'label']
        });

        this.rolesStore = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: '/admin/user/get-roles',
                reader: {
                    rootProperty: 'data',
                    idProperty: 'id'
                }
            },
            fields: ['id', 'label']
        });

        this.nameField = new Ext.form.TextField({
            fieldLabel: t('name'),
            name: 'gridConfigName',
            length: 50,
            allowBlank: false,
            width: '100%',
            value: this.settings ? this.settings.gridConfigName : ""
        });

        this.descriptionField = new Ext.form.TextArea({
            fieldLabel: t('description'),
            name: 'gridConfigDescription',
            height: 200,
            width: '100%',
            value: this.settings ? this.settings.gridConfigDescription : ""
        });

        this.userSharingField = Ext.create('Ext.form.field.Tag', {
            name: "sharedUserIds",
            width: '100%',
            height: 100,
            fieldLabel: t("shared_users"),
            queryDelay: 0,
            resizable: true,
            queryMode: 'local',
            minChars: 1,
            store: this.userStore,
            displayField: 'label',
            valueField: 'id',
            forceSelection: true,
            filterPickList: true,
            value: this.settings.sharedUserIds ? this.settings.sharedUserIds : ""
        });

        this.rolesSharingField = Ext.create('Ext.form.field.Tag', {
            name: "sharedRoleIds",
            width: '100%',
            height: 100,
            fieldLabel: t("shared_roles"),
            queryDelay: 0,
            resizable: true,
            queryMode: 'local',
            minChars: 1,
            store: this.rolesStore,
            displayField: 'label',
            valueField: 'id',
            forceSelection: true,
            filterPickList: true,
            value: this.settings.sharedRoleIds ? this.settings.sharedRoleIds : ""
        });

        this.settingsForm = Ext.create('Ext.form.FormPanel', {
            defaults: {
                labelWidth: 200
            },
            hidden: !this.showSaveAndShareTab,
            bodyStyle: "padding:10px;",
            autoScroll: true,
            border: false,
            iconCls: "pimcore_icon_save_and_share",
            title: t("save_and_share"),
            items: [this.nameField, this.descriptionField, this.userSharingField, this.rolesSharingField]
        });
        return this.settingsForm;
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
                } else {
                    console.log("config element not found");
                }
            }
        }
        return elements;
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

    commitData: function (save) {

        this.data = {};
        if (this.languageField) {
            this.data.language = this.languageField.getValue();
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
                    obj.label = child.data.text;
                    obj.type = child.data.dataType;
                    obj.layout = child.data.layout;
                    if (child.data.width) {
                        obj.width = child.data.width;
                    }
                }

                this.data.columns.push(obj);
            }.bind(this));
        }

        if (this.showSaveAndShareTab) {
            this.settings = Ext.apply(this.settings, this.settingsForm.getForm().getFieldValues());
            if (this.settings.sharedUserIds != null) {
                this.settings.sharedUserIds = this.settings.sharedUserIds.join();
            }
            if (this.settings.sharedRoleIds != null) {
                this.settings.sharedRoleIds = this.settings.sharedRoleIds.join();
            }
        }

        if (!operatorFound) {
            this.callback(this.data, this.settings, save);
            this.window.close();
        } else {
            var columnsPostData = Ext.encode(this.data.columns);
            Ext.Ajax.request({
                url: "/admin/object-helper/prepare-helper-column-configs",
                method: 'POST',
                params: {
                    columns: columnsPostData
                },
                success: function (response) {
                    var responseData = Ext.decode(response.responseText);
                    this.data.columns = responseData.columns;
                    this.callback(this.data, this.settings, save);
                    this.window.close();

                }.bind(this)
            });

        }
    },

    getLanguageSelection: function () {

        var storedata = [["default", t("default")]];
        for (var i = 0; i < pimcore.settings.websiteLanguages.length; i++) {
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

        if (!this.languagePanel) {
            this.languagePanel = new Ext.form.FormPanel({
                region: "north",
                height: 43,
                items: [compositeConfig]
            });
        }


        return this.languagePanel;
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
                                    var window = element.getConfigDialog(copy);

                                    if (window) {
                                        //this is needed because of new focus management of extjs6
                                        setTimeout(function () {
                                            window.focus();
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
                                            window.setTimeout(function () {
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
                                    var window = element.getConfigDialog(copy);

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

                        }.bind(this),
                        nodedragover: function (targetNode, dropPosition, dragData, e, eOpts) {
                            var sourceNode = dragData.records[0];

                            if (sourceNode.data.isOperator) {
                                var realOverModel = targetNode;
                                if (dropPosition == "before" || dropPosition == "after") {
                                    realOverModel = realOverModel.parentNode;
                                }

                                var sourceType = this.getNodeTypeAndClass(sourceNode);
                                var targetType = this.getNodeTypeAndClass(realOverModel);
                                var allowed = true;


                                if (typeof realOverModel.data.isChildAllowed == "function") {
                                    console.log("no child allowed");
                                    allowed = allowed && realOverModel.data.isChildAllowed(realOverModel, sourceNode);
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
                width: 428,
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

        return this.selectionPanel;


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

    getNodeTypeAndClass: function (node) {
        var type = "value";
        var className = "";
        if (node.data.configAttributes) {
            type = node.data.configAttributes.type;
            className = node.data.configAttributes['class'];
        } else if (node.data.dataType) {
            className = node.data.dataType.toLowerCase();
        }
        return {type: type, className: className};
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts) {
        e.stopEvent();

        tree.select();

        var menu = new Ext.menu.Menu();

        if (this.id != 0) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (node) {
                    record.parentNode.removeChild(record, true);
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

                menu.add(new Ext.menu.Item({
                    text: t('expand_children'),
                    iconCls: "pimcore_icon_expand_children",
                    handler: function (node) {
                        record.expandChildren();
                    }.bind(this, record)
                }));
            }

            if (record.data.isOperator) {
                menu.add(new Ext.menu.Item({
                    text: t('edit'),
                    iconCls: "pimcore_icon_edit",
                    handler: function (node) {
                        this.getConfigElement(node.data.configAttributes).getConfigDialog(node);
                    }.bind(this, record)
                }));
            }
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

    getClassTree: function (url, classId, objectId) {

        var classTreeHelper = new pimcore.object.helpers.classTree(true);
        var tree = classTreeHelper.getClassTree(url, classId, objectId);

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

    getOperatorTree: function () {
        var operators = Object.keys(pimcore.object.gridcolumn.operator);
        var childs = [];
        for (var i = 0; i < operators.length; i++) {
            if (!this.availableOperators || this.availableOperators.indexOf(operators[i]) >= 0) {
                childs.push(pimcore.object.gridcolumn.operator[operators[i]].prototype.getConfigTreeNode());
            }
        }
        
        childs.sort(
            function(x, y) {
                return x.text < y.text ? -1 : 1;
            }
        );

        var tree = new Ext.tree.TreePanel({
            title: t('operators'),
            collapsible: true,
            // collapsed: true,
            xtype: "treepanel",
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
