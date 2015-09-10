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

pimcore.registerNS("pimcore.object.classes.klass");
pimcore.object.classes.klass = Class.create({

    allowedInType: 'object',
    disallowedDataTypes: [],
    uploadUrl: '/admin/class/import-class',
    exportUrl: "/admin/class/export-class",



    initialize: function (data, parentPanel, reopen) {
        this.parentPanel = parentPanel;
        this.data = data;

        this.addLayout();
        this.initLayoutFields();
        this.reopen = reopen;
    },

    getUploadUrl: function(){
        return this.uploadUrl + '?pimcore_admin_sid=' + pimcore.settings.sessionId + "&id=" + this.getId();
    },

    getExportUrl: function() {
        return  this.exportUrl + "?id=" + this.getId();
    },

    addLayout: function () {

        this.editpanel = new Ext.Panel({
            region: "center",
            bodyStyle: "padding: 10px;",
            autoScroll: true
        });

        this.tree = Ext.create('Ext.tree.Panel', {
            region: "west",
            width: 300,
            split: true,
            enableDD: true,
            useArrows: true,
            autoScroll: true,
            root: {
                id: "0",
                root: true,
                text: t("base"),
                leaf: true,
                iconCls: "pimcore_icon_class",
                isTarget: true
            },
            listeners: this.getTreeNodeListeners(),
            tbar: {
                items: [
                      "->",
                    {
                        text: t("configure_custom_layouts"),
                        iconCls: "pimcore_icon_class_add",
                        hidden: (this instanceof pimcore.object.fieldcollections.field) || (this instanceof pimcore.object.objectbricks.field),
                        handler: this.configureCustomLayouts.bind(this)
                    }
                ]
            },
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    ddGroup: "element"
                }
            }
        });

        var displayId = this.data.key ? this.data.key : this.data.id; // because the field-collections use that also

        var panelButtons = [];

        panelButtons.push({
            text: t('reload_definition'),
            handler: this.onRefresh.bind(this),
            iconCls: "pimcore_icon_reload"
        });

        panelButtons.push({
            text: t("import"),
            iconCls: "pimcore_icon_class_import",
            handler: this.upload.bind(this)
        });

        panelButtons.push({
            text: t("export"),
            iconCls: "pimcore_icon_class_export",
            handler: function() {
                pimcore.helpers.download(this.getExportUrl());
            }.bind(this)
        });


        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        });


        var name = "";
        if(this.data.name) {
            name = this.data.name + " ( ID: " + displayId + ")";
        } else {
            name = "ID: " + displayId;
        }

        this.panel = new Ext.Panel({
            border: false,
            layout: "border",
            closable: true,
            title: name,
            id: "pimcore_class_editor_panel_" + this.getId(),
            items: [
                this.tree,
                this.editpanel
            ],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);

        this.editpanel.add(this.getRootPanel());
        this.setCurrentNode("root");
        this.parentPanel.getEditPanel().setActiveTab(this.panel);

        pimcore.layout.refresh();
    },

    configureCustomLayouts: function() {
        try {
            var dialog = new pimcore.object.helpers.customLayoutEditor(this.data);
        } catch (e) {
            console.log(e);
        }
    },

    getId: function(){
        return  this.data.id;
    },

    upload: function() {

        pimcore.helpers.uploadDialog(this.getUploadUrl(), "Filedata", function() {
            Ext.Ajax.request({
                url: "/admin/class/get",
                params: {
                    id: this.data.id
                },
                success: function(response) {
                    this.data = Ext.decode(response.responseText);
                    this.parentPanel.getEditPanel().removeAll();
                    this.addLayout();
                    this.initLayoutFields();
                    pimcore.layout.refresh();
                }.bind(this)
            });
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    },

    reload: function(response) {

    },

    initLayoutFields: function () {

        if (this.data.layoutDefinitions) {
            if (this.data.layoutDefinitions.childs) {
                for (var i = 0; i < this.data.layoutDefinitions.childs.length; i++) {
                    this.tree.getRootNode().appendChild(this.recursiveAddNode(this.data.layoutDefinitions.childs[i],
                        this.tree.getRootNode()));
                }
                this.tree.getRootNode().expand();
            }
        }
    },

    recursiveAddNode: function (con, scope) {

        var fn = null;
        var newNode = null;

        if (con.datatype == "layout") {
            fn = this.addLayoutChild.bind(scope, con.fieldtype, con);
        }
        else if (con.datatype == "data") {
            fn = this.addDataChild.bind(scope, con.fieldtype, con);
        }

        newNode = fn();

        if (con.childs) {
            for (var i = 0; i < con.childs.length; i++) {
                this.recursiveAddNode(con.childs[i], newNode);
            }
        }

        return newNode;
    },


    getTreeNodeListeners: function () {

        var listeners = {
            "itemclick" : this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this)
        };
        return listeners;
    },



    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {

        try {
            this.saveCurrentNode();
        } catch (e) {
            console.log(e);
        }


        try {
            this.editpanel.removeAll();

            if (record.data.editor) {

                if (record.data.editor.datax.locked) {
                    return;
                }

                this.editpanel.add(record.data.editor.getLayout());

                this.setCurrentNode(record.data.editor);
            }

            if (record.data.root) {
                this.editpanel.add(this.getRootPanel());
                this.setCurrentNode("root");
            }

            this.editpanel.updateLayout();
        } catch (e) {
            console.log(e);
        }
    },

     getDataMenu: function(tree, record, allowedTypes, parentType, editMode) {
        // get available data types
        var dataMenu = [];
        var dataComps = Object.keys(pimcore.object.classes.data);

        var parentRestrictions;
        var groups = new Array();
        var groupNames = ["text","numeric","date","select","relation","structured","geo","other"];
        for (var i = 0; i < dataComps.length; i++) {

            var dataComp = pimcore.object.classes.data[dataComps[i]];

            // check for disallowed types
            var allowed = false;

            if('object' !== typeof dataComp) {
                var tt = typeof dataComp;
                if (dataComp.prototype.allowIn[this.allowedInType]) {
                    allowed = true;
                }
            }

            if (!allowed) {
                continue;
            }


            if (dataComps[i] != "data") { // class data is an abstract class => disallow
                if (in_array("data", allowedTypes[parentType]) || in_array(dataComps[i], allowedTypes[parentType]) ) {

                    // check for restrictions from a parent field (eg. localized fields)
                    if(in_array("data", allowedTypes[parentType])) {
                        parentRestrictions = this.getRestrictionsFromParent(record);
                        if(parentRestrictions != null) {
                            if(!in_array(dataComps[i], allowedTypes[parentRestrictions])) {
                                continue;
                            }
                        }
                    }

                    var group = pimcore.object.classes.data[dataComps[i]].prototype.getGroup();
                    if (!groups[group]) {
                        if (!in_array(group, groupNames)) {
                            groupNames.push(group);
                        }
                        groups[group] = new Array();
                    }
                    var handler;
                    if (editMode) {
                        handler = this.changeDataType.bind(this, tree, record, dataComps[i], true);
                    } else {
                        handler = this.addDataChild.bind(record, dataComps[i]);
                    }

                    groups[group].push({
                        text: pimcore.object.classes.data[dataComps[i]].prototype.getTypeName(),
                        iconCls: pimcore.object.classes.data[dataComps[i]].prototype.getIconClass(),
                        handler: handler
                    });
                }
            }
        }

        for (i = 0; i < groupNames.length; i++) {
            if (groups[groupNames[i]] && groups[groupNames[i]].length > 0) {
                dataMenu.push(new Ext.menu.Item({
                    text: t(groupNames[i]),
                    iconCls: "pimcore_icon_data_group_" + groupNames[i],
                    hideOnClick: false,
                    menu: groups[groupNames[i]]
                }));
            }
        }
        return dataMenu;
    },


    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        e.stopEvent();
        tree.select();

        var menu = new Ext.menu.Menu();

        //get all allowed data types for localized fields
        var lftypes = ["panel","tabpanel","accordion","fieldset","text","region","button"];
        var dataComps = Object.keys(pimcore.object.classes.data);

        for (var i = 0; i < dataComps.length; i++) {
            if ('object' === typeof pimcore.object.classes.data[dataComps[i]]) {
                continue;
            }
            if(pimcore.object.classes.data[dataComps[i]].prototype.allowIn['localizedfield']) {
                lftypes.push(dataComps[i]);
            }
        }

        // specify which childs a layout can have
        // the child-type "data" is a placehoder for all data components
        var allowedTypes = {
            accordion: ["panel","region","tabpanel","text"],
            fieldset: ["data","text"],
            panel: ["data","region","tabpanel","button","accordion","fieldset","panel","text","html"],
            region: ["panel","accordion","tabpanel","text","localizedfields"],
            tabpanel: ["panel", "region", "accordion","text","localizedfields"],
            button: [],
            text: [],
            root: ["panel","region","tabpanel","accordion","text"],
            localizedfields: lftypes
        };

        var parentType = "root";

        if (record.data.editor) {
            parentType = record.data.editor.type;
        }

        var changeTypeAllowed = false;
        if (record.data.type == "data") {
            changeTypeAllowed = true;
        }

        var childsAllowed = false;
        if (allowedTypes[parentType] && allowedTypes[parentType].length > 0) {
            childsAllowed = true;
        }

        if (childsAllowed || changeTypeAllowed) {
            // get available layouts
            var layoutMenu = [];
            var layouts = Object.keys(pimcore.object.classes.layout);

            for (var i = 0; i < layouts.length; i++) {
                if (layouts[i] != "layout") {
                    if (in_array(layouts[i], allowedTypes[parentType])) {
                        layoutMenu.push({
                            text: pimcore.object.classes.layout[layouts[i]].prototype.getTypeName(),
                            iconCls: pimcore.object.classes.layout[layouts[i]].prototype.getIconClass(),
                            handler: this.addLayoutChild.bind(record, layouts[i])
                        });
                    }

                }
            }

            var getDataMenu = this.getDataMenu.bind(this, tree, record);
            var addDataMenu = getDataMenu(allowedTypes, parentType, false);

            if (layoutMenu.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('add_layout_component'),
                    iconCls: "pimcore_icon_add",
                    hideOnClick: false,
                    menu: layoutMenu
                }));
            }

            if (addDataMenu.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('add_data_component'),
                    iconCls: "pimcore_icon_add",
                    hideOnClick: false,
                    menu: addDataMenu
                }));
            }

            if (changeTypeAllowed) {
                var changeDataMenu = getDataMenu(allowedTypes, record.parentNode.data.editor.type, true);
                menu.add(new Ext.menu.Item({
                    text: t('change_type'),
                    iconCls: "pimcore_icon_change_type",
                    hideOnClick: false,
                    menu: changeDataMenu
                }));
            }

            if (record.data.type == "data") {
                var dataComps = Object.keys(pimcore.object.classes.data);
                menu.add(new Ext.menu.Item({
                    text: t('duplicate'),
                    iconCls: "pimcore_icon_clone",
                    hideOnClick: true,
                    handler: this.changeDataType.bind(this, tree, record, record.data.editor.type, dataComps, false)
                }));
            }
        }

        var deleteAllowed = true;

        if (record.data.editor) {
            if (record.data.editor.datax.locked) {
                deleteAllowed = false;
            }
        }

        if (this.id != 0 && deleteAllowed) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.removeChild.bind(this, tree, record)
            }));
        }

        menu.showAt(e.pageX, e.pageY);
    },

    getRestrictionsFromParent: function (node) {
        if(node.data.editor.type == "localizedfields") {
            return "localizedfields";
        } else {
            if(node.parentNode && node.parentNode.getDepth() > 0) {
                var parentType = this.getRestrictionsFromParent(node.parentNode);
                if(parentType != null) {
                    return parentType;
                }
            }
        }

        return null;
    },

    setCurrentNode: function (cn) {
        this.currentNode = cn;
    },

    saveCurrentNode: function () {
        if (this.currentNode) {
            if (this.currentNode != "root") {
                this.currentNode.applyData();
            }  else {
                // save root node data
                var items = this.rootPanel.queryBy(function() {
                    return true;
                });

                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    if (typeof item.getValue == "function") {
                        this.data[item.name] = item.getValue();
                    }
                }
            }
        }
    },

    getRootPanel: function () {
        this.allowInheritance = new Ext.form.Checkbox({
            fieldLabel: t("allow_inherit"),
            name: "allowInherit",
            checked: this.data.allowInherit,
            listeners: {
                "change": function(field, checked) {
                    if(checked == true) {
                        this.allowVariants.setDisabled(false);
                    } else {
                        this.allowVariants.setValue(false);
                        this.allowVariants.setDisabled(true);
                        this.showVariants.setValue(false);
                        this.showVariants.setDisabled(true);
                    }
                }.bind(this)
            }
        });


        this.allowVariants = new Ext.form.Checkbox({
            fieldLabel: t("allow_variants"),
            name: "allowVariants",
            checked: this.data.allowVariants,
            disabled: !this.data.allowInherit,
            listeners: {
                "change": function(field, checked) {
                    if(checked == true) {
                        this.showVariants.setDisabled(false);
                    } else {
                        this.showVariants.setValue(false);
                        this.showVariants.setDisabled(true);
                    }
                }.bind(this)
            }
        });

        this.showVariants = new Ext.form.Checkbox({
            fieldLabel: t("show_variants"),
            name: "showVariants",
            checked: this.data.showVariants,
            disabled: !this.data.allowInherit
        });


        this.rootPanel = new Ext.form.FormPanel({
            title: t("basic_configuration"),
            //border: true,
            bodyStyle: "padding: 10px;",
            defaults: {
                labelWidth: 200
            },
            items: [
                {
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "name",
                    width: 500,
                    value: this.data.name
                },
                {
                    xtype: "textarea",
                    fieldLabel: t("description"),
                    name: "description",
                    width: 500,
                    value: this.data.description
                },
                this.allowInheritance,
                this.allowVariants,
                this.showVariants,
                {
                    xtype: "textfield",
                    fieldLabel: t("parent_class"),
                    name: "parentClass",
                    width: 600,
                    value: this.data.parentClass
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("icon"),
                    name: "icon",
                    width: 600,
                    value: this.data.icon,
                    enableKeyEvents: true,
                    listeners: {
                        "keyup": function (el) {
                            el.inputEl.applyStyles("background:url(" + el.getValue() + ") right center no-repeat;");
                        },
                        "afterrender": function (el) {
                            el.inputEl.applyStyles("background:url(" + el.getValue() + ") right center no-repeat;");
                        }
                    }
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("preview_url"),
                    name: "previewUrl",
                    width: 600,
                    value: this.data.previewUrl
                },
                {
                    xtype: "displayfield",
                    hideLabel: true,
                    width: 600,
                    value: "<b>" + t('visibility_of_system_properties') + "</b>",
                    cls: "pimcore_extra_label_headline"
                },
                {
                    xtype: "checkbox",
                    fieldLabel: "ID (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.id",
                    checked: this.data.propertyVisibility.grid.id
                },
                {
                    xtype: "checkbox",
                    fieldLabel: "ID (" + t("search") + ")",
                    name: "propertyVisibility.search.id",
                    checked: this.data.propertyVisibility.search.id
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("path") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.path",
                    checked: this.data.propertyVisibility.grid.path
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("path") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.path",
                    checked: this.data.propertyVisibility.search.path
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("published") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.published",
                    checked: this.data.propertyVisibility.grid.published
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("published") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.published",
                    checked: this.data.propertyVisibility.search.published
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("modificationDate") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.modificationDate",
                    checked: this.data.propertyVisibility.grid.modificationDate
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("modificationDate") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.modificationDate",
                    checked: this.data.propertyVisibility.search.modificationDate
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("creationDate") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.creationDate",
                    checked: this.data.propertyVisibility.grid.creationDate
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("creationDate") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.creationDate",
                    checked: this.data.propertyVisibility.search.creationDate
                }
            ]
        });

        return this.rootPanel;
    },

    addLayoutChild: function (type, initData) {

        var nodeLabel = t(type);

        if (initData) {
            if (initData.name) {
                nodeLabel = initData.name;
            }
        }

        var newNode = {
            text: nodeLabel,
            type: "layout",
            iconCls: "pimcore_icon_" + type,
            leaf: false,
            expandable: false
        };
        newNode = this.appendChild(newNode);

        //to hide or show the expanding icon depending if childs are available or not
        newNode.addListener('remove', function(node, removedNode, isMove) {
            if(!node.hasChildNodes()) {
                node.set('expandable', false);
            }
        });
        newNode.addListener('append', function(node) {
            node.set('expandable', true);
        });


        var editor = new pimcore.object.classes.layout[type](newNode, initData);
        newNode.set("editor", editor);

        this.expand();

        return newNode;
    },

    addDataChild: function (type, initData) {

        var nodeLabel = t(type);

        if (initData) {
            if (initData.name) {
                nodeLabel = initData.name;
            }
        }

        var newNode = {
            text: nodeLabel,
            type: "data",
            leaf: true,
            iconCls: "pimcore_icon_" + type
        };

        newNode = this.appendChild(newNode);

        //to hide or show the expanding icon depending if childs are available or not
        newNode.addListener('move', function(node, oldParent, newParent) {
            newParent.set('expandable', true);
        });

        var editor = new pimcore.object.classes.data[type](newNode, initData);
        newNode.set("editor", editor);

        this.expand();

        return newNode;
    },

    changeDataType: function (tree, record, type, initData, removeExisting) {
        try {
           record.data.editor.applyData();

            var nodeLabel = record.data.text;

            var theData = {};

            theData.name = nodeLabel;
            theData.datatype = "data";
            theData.fieldtype = type;

            var isLeaf = this.leaf;

            if (!removeExisting) {
                var matches = nodeLabel.match(/\d+$/);

                if (matches) {
                    var number = matches[0];

                    var numberLength = number.length;
                    number = parseInt(number);
                    number = number + 1;

                    var l = nodeLabel.length;

                    nodeLabel = nodeLabel.substring(0, l - numberLength);
                } else {
                    number = 1;
                }
                nodeLabel = nodeLabel + number;
            }



            var newNode = {
                text: nodeLabel,
                type: "data",
                leaf: true,
                iconCls: "pimcore_icon_" + type
                //,
                //listeners: this.getTreeNodeListeners()
            };

            if (!removeExisting) {
                theData.name = nodeLabel;
            }

            var editor = new pimcore.object.classes.data[type](newNode, theData);
            newNode = record.parentNode.insertBefore(newNode, record);

            var availableFields = editor.availableSettingsFields;
            for (var i = 0;  i < availableFields.length; i++) {
                var field = availableFields[i];
                if (record.data.editor.datax[field]) {
                    if (field != "name") {
                        editor.datax[field] = record.data.editor.datax[field];
                    }
                }
            }

            newNode.data.editor = editor;
            newNode.data.editor.applySpecialData(record.data.editor);

            var parentNode = record.parentNode;
            if (removeExisting) {
                parentNode.removeChild(record);

            } else {
                parentNode.insertBefore(record, newNode);
            }

            //newNode.select();
            var f = this.onTreeNodeClick.bind(this, newNode.getOwnerTree(), newNode);
            f();

            var ownerTree = newNode.getOwnerTree();
            var selModel = ownerTree.getSelectionModel();
            selModel.select(newNode);


            return newNode;
        } catch (e) {
         console.log(e);
        }
    },




    removeChild: function (tree, record) {
        if (this.id != 0) {
            if (this.currentNode == record.data.editor) {
                this.currentNode = null;
                var rootNode = this.tree.getRootNode();
                var f = this.onTreeNodeClick.bind(this, this.tree, rootNode);
                f();
            }
            record.remove();
        }
    },

    getNodeData: function (node) {

        var data = {};

        if (node.data.editor) {
            if (typeof node.data.editor.getData == "function") {
                data = node.data.editor.getData();

                data.name = trim(data.name);

                // field specific validation
                var fieldValidation = true;
                if(typeof node.data.editor.isValid == "function") {
                    fieldValidation = node.data.editor.isValid();
                }

                var view = this.tree.getView();
                // check if the name is unique, localizedfields can be used more than once
                var nodeEl = Ext.fly(view.getNodeByRecord(node));

                if ((fieldValidation && in_arrayi(data.name,this.usedFieldNames) == false) || data.name == "localizedfields") {

                    if(data.datatype == "data") {
                        this.usedFieldNames.push(data.name);
                    }

                    if(nodeEl) {
                        nodeEl.removeCls("tree_node_error");
                    }
                }
                else {
                    if(nodeEl) {
                        nodeEl.addCls("tree_node_error");
                    }


                    var invalidFieldsText = t("class_field_name_error") + ": '" + data.name + "'";

                    if(node.data.editor.invalidFieldNames){
                        invalidFieldsText = t("reserved_field_names_error")
                            +(implode(',',node.data.editor.forbiddenNames));
                    }

                    pimcore.helpers.showNotification(t("error"), t("some_fields_cannot_be_saved"), "error",
                        invalidFieldsText);



                    this.getDataSuccess = false;
                    return false;
                }
            }
        }

        data.childs = null;
        if (node.childNodes.length > 0) {
            data.childs = [];

            for (var i = 0; i < node.childNodes.length; i++) {
                data.childs.push(this.getNodeData(node.childNodes[i]));
            }
        }

        return data;
    },

    getData: function () {

        this.getDataSuccess = true;

        this.usedFieldNames = [];

        var rootNode = this.tree.getRootNode();
        var nodeData = this.getNodeData(rootNode);

        return nodeData;
    },

    save: function () {

        this.saveCurrentNode();

        var regresult = this.data["name"].match(/[a-zA-Z][a-zA-Z0-9]+/);

        if (this.data["name"].length > 2 && regresult == this.data["name"] && !in_array(this.data["name"].toLowerCase(),
            this.parentPanel.forbiddennames)) {
            delete this.data.layoutDefinitions;

            var m = Ext.encode(this.getData());
            var n = Ext.encode(this.data);

            if (this.getDataSuccess) {
                Ext.Ajax.request({
                    url: "/admin/class/save",
                    method: "post",
                    params: {
                        configuration: m,
                        values: n,
                        id: this.data.id
                    },
                    success: this.saveOnComplete.bind(this),
                    failure: this.saveOnError.bind(this)
                });
            }
        } else {
            Ext.Msg.alert(t('add_class'), t('invalid_class_name'));
        }
    },

    saveOnComplete: function (response) {

        try {
            var res = Ext.decode(response.responseText);
            if(res.success) {
                this.parentPanel.tree.getStore().load();
                pimcore.globalmanager.get("object_types_store").load();

                // set the current modification date, to detect modifcations on the class which are not made here
                this.data.modificationDate = res['class'].modificationDate;

                pimcore.helpers.showNotification(t("success"), t("class_saved_successfully"), "success");
            } else {
                throw "save was not successful, see debug.log";
            }
        } catch (e) {
            this.saveOnError();
        }

    },

    saveOnError: function () {
        pimcore.helpers.showNotification(t("error"), t("class_save_error"), "error");
    },

    onRefresh: function() {
        this.parentPanel.getEditPanel().remove(this.panel);
        this.reopen();
    }
});