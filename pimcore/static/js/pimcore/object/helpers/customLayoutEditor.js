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

pimcore.registerNS("pimcore.object.helpers.customLayoutEditor");
pimcore.object.helpers.customLayoutEditor = Class.create({

    uploadUrl: '/admin/class/import-custom-layout-definition',
    exportUrl: "/admin/class/export-custom-layout-definition",

    data: {},

    showFieldName: false,

    layoutDraggable: true,

    layoutConfigurationMode: true,

    currentLayoutId: 0,

    initialize: function (klass) {

        this.klass = klass;

        this.classTreeHelper = this;
        this.showFieldName = true;

        this.saveButton = new Ext.Button(
            {
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                disabled: true,
                handler: function () {
                    this.save();
                }.bind(this)
            }
        );

        this.exportButton = new Ext.Button(
            {
                text: t("export"),
                iconCls: "pimcore_icon_class_export",
                disabled: true,
                handler: function() {
                    pimcore.helpers.download(this.getExportUrl());
                }.bind(this)
            }
        );

        this.importButton = new Ext.Button(
            {
                text: t("import"),
                iconCls: "pimcore_icon_class_import",
                disabled: true,
                handler: this.upload.bind(this)
            }
        );

        this.configPanel = new Ext.Panel({
            layout: "border",
            items: [this.getLayoutSelection(), this.getSelectionPanel(), this.getResultPanel(), this.getEditPanel()],
            bbar: [
                "->",
                this.importButton,
                this.exportButton,
                '-',
                {
                    xtype: 'button',
                    text: t('cancel'),
                    iconCls: 'pimcore_icon_delete',
                    handler: function () {
                        this.window.close();
                    }.bind(this)
                },

                this.saveButton
            ]
        });

        this.window = new Ext.Window({
            width: 1200,
            height: 700,
            modal: true,
            title: t('custom_layout_definition'),
            layout: "fit",
            items: [this.configPanel]
        });

        this.window.show();
    },

    getUploadUrl: function(){
        return this.uploadUrl + '?pimcore_admin_sid=' + pimcore.settings.sessionId + "&id=" + this.data.id;
    },

    getExportUrl: function() {
        return  this.exportUrl + "?id=" + this.data.id;
    },

    getNodeData: function (node) {
        var data = {};

        if (node.attributes.object) {
            if (typeof node.attributes.object.getData == "function") {
                data = node.attributes.object.getData();

                data.name = trim(data.name);

                // field specific validation
                var fieldValidation = true;
                if(typeof node.attributes.object.isValid == "function") {
                    fieldValidation = node.attributes.object.isValid();
                }

                // check if the name is unique, localizedfields can be used more than once
                if ((fieldValidation && in_arrayi(data.name,this.usedFieldNames) == false)
                    || data.name == "localizedfields") {

                    if(data.datatype == "data") {
                        this.usedFieldNames.push(data.name);
                    }

                    node.getUI().removeClass("tree_node_error");
                }
                else {
                    node.getUI().addClass("tree_node_error");

                    var invalidFieldsText = null;

                    if(node.attributes.object.invalidFieldNames){
                        invalidFieldsText = t("reserved_field_names_error")
                            +(implode(',',node.attributes.object.forbiddenNames));
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

        var rootNode = this.selectionPanel.getRootNode();
        var nodeData = this.getNodeData(rootNode);

        return nodeData;
    },

    getLayoutSelection: function () {
        this.layoutComboStore = new Ext.data.JsonStore({
            url: '/admin/class/get-custom-layout-definitions',
            baseParams: {
                classId: this.klass.id
            },
            fields: ['id', 'name'],
            autoLoad: true,
            root: "data",
            forceSelection:true,
            listeners: {
                load: function() {
                    this.layoutChangeCombo.setValue(this.currentLayoutId);
                }.bind(this)
            }
        });

        this.layoutChangeCombo = new Ext.form.ComboBox({
            triggerAction: "all",
            selectOnFocus: true,
            forceSelection: true,
            store: this.layoutComboStore,
            displayField: 'name',
            valueField: 'id' ,
            disableKeyFilter: "true",
            valueNotFoundText: "",
            editable: false,
            listeners: {
                focus: function(){
                    this.layoutComboStore.load();
                }.bind(this),
                select: function(field, fieldname) {
                    var layoutId = field.value;
                    this.editPanel.removeAll();
                    Ext.Ajax.request({
                        url: "/admin/class/get-custom-layout",
                        params: {
                            id: layoutId
                        },
                        success: function(response) {
                            this.initLayoutFields(true, response);
                            this.resultPanel.enable();
                            this.enableButtons();
                            this.currentLayoutId = layoutId;
                        }.bind(this)

                    });
                }.bind(this)
            }
        });

        var compositeConfig = {
            xtype: "compositefield",
            fieldLabel: t("layout"),
            items: [this.layoutChangeCombo,
                {
                    xtype: "button",
                    text: t("add_layout"),
                    iconCls: "pimcore_icon_add",
                    handler: this.addLayout.bind(this)
                },
                {
                    xtype: "button",
                    text: t("delete_layout"),
                    iconCls: "pimcore_icon_delete",
                    disabled: false,
                    handler: this.deleteLayout.bind(this)
                }
            ]
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

            this.selectionPanel = new Ext.tree.TreePanel({
                root: {
                    hidden: true
                },
                enableDD:true,
                ddGroup: "columnconfigelement",
                id:'tree',
                region:'center',
                title: t('custom_layout'),
                layout:'fit',
                width: 428,
                split:true,
                autoScroll:true,
                listeners:{
                    beforenodedrop: function(e) {
                        if(e.source.tree.el != e.target.ownerTree.el) {
                            if(e.dropNode.attributes.type != "layout"
                                && this.selectionPanel.getRootNode().findChild("key", e.dropNode.attributes.key)) {
                                e.cancel= true;
                            } else {
                                var n = e.dropNode; // the node that was dropped

                                var copy = this.recursiveCloneNode(n);
                                e.dropNode = copy; // assign the copy as the new dropNode
                            }
                        }
                    }.bind(this),
                    contextmenu: this.onTreeNodeContextmenu
                }
            });
        }

        return this.selectionPanel;
    },

    saveCurrentNode: function () {
        if (this.currentNode) {
            if (this.currentNode != "root") {
                this.currentNode.applyData();
            } else {
                // save root node data
                if (this.rootPanel) {
                    var items = this.rootPanel.findBy(function() {
                        return true;
                    });

                    for (var i = 0; i < items.length; i++) {
                        if (typeof items[i].getValue == "function") {
                            this.data[items[i].name] = items[i].getValue();
                        }
                    }
                }
            }
        }
    },

    recursiveCloneNode: function(n) {
        var config =  // copy it
            Ext.apply({}, n.attributes);

        var copy = new Ext.tree.TreeNode(config);
        copy.addListener("click", this.onTreeNodeClick);

        if (n.hasChildNodes()) {
            var childs = n.childNodes;
            var i;
            for (i = 0; i < childs.length; i++) {
                copy.appendChild(this.recursiveCloneNode(childs[i]));
            }
        }

        return copy;
    },

    onTreeNodeContextmenu: function (node) {
        node.select();

        var menu = new Ext.menu.Menu();

        var allowedTypes = {
            accordion: ["panel","region","tabpanel","text"],
            fieldset: ["data","text"],
            panel: ["data","region","tabpanel","button","accordion","fieldset","panel","text","html"],
            region: ["panel","accordion","tabpanel","text","localizedfields"],
            tabpanel: ["panel", "region", "accordion","text","localizedfields"],
            button: [],
            text: [],
            root: ["panel","region","tabpanel","accordion","text"],
            localizedfields: ["panel","tabpanel","accordion","fieldset","text","region","button"]
        };

        var parentType = "root";

        if (node.attributes.object) {
            parentType = node.attributes.object.type;
        }

        var childsAllowed = false;
        if (allowedTypes[parentType] && allowedTypes[parentType].length > 0) {
            childsAllowed = true;
        }

        if (childsAllowed) {
            // get available layouts
            var layoutMenu = [];
            var layouts = Object.keys(pimcore.object.classes.layout);

            for (var i = 0; i < layouts.length; i++) {
                if (layouts[i] != "layout") {
                    if (in_array(layouts[i], allowedTypes[parentType])) {
                        layoutMenu.push({
                            text: pimcore.object.classes.layout[layouts[i]].prototype.getTypeName(),
                            iconCls: pimcore.object.classes.layout[layouts[i]].prototype.getIconClass(),
                            handler: node.attributes.reference.addLayoutChild.bind(node, layouts[i], null, true)
                        });
                    }
                }
            }

            if (layoutMenu.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('add_layout_component'),
                    iconCls: "pimcore_icon_add",
                    hideOnClick: false,
                    menu: layoutMenu
                }));
            }
        }

        var dataMenu = [];
        dataMenu.push({
            text: pimcore.object.classes.data.localizedfields.prototype.getTypeName(),
            iconCls: pimcore.object.classes.data.localizedfields.prototype.getIconClass(),
            handler: node.attributes.reference.addDataChild.bind(node, "localizedfields", {name: "localizedfields"},
                null, true, true)
        });

        menu.add(new Ext.menu.Item({
            text: t('add_data_component'),
            iconCls: "pimcore_icon_add",
            hideOnClick: false,
            menu: dataMenu
        }));

        if (this.id != 0) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function(node) {
                    // node.attributes.reference.selectionPanel.getRootNode().removeChild( node, true);
                    node.remove(true);
                }.bind(this, node)
            }));
        }

        menu.show(node.ui.getEl());
    },


    getResultPanel: function () {
        if (!this.resultPanel) {
            this.resultPanel = this.getClassTree("/admin/class/get", this.klass.id);
        }

        return this.resultPanel;
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.Panel({
                region: "east",
                bodyStyle: "padding: 20px;",
                autoScroll: true,
                width: 700,
                split: true
            });

            this.setCurrentNode("root");
        }

        return this.editPanel;
    },

    getRootPanel: function() {


        this.rootPanel = new Ext.form.FormPanel({
            title: t("basic_configuration"),
            bodyStyle: "padding: 10px;",
            layout: "pimcoreform",
            labelWidth: 200,
            items: [
                {
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "name",
                    width: 300,
                    value: this.data.name
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("default_layout"),
                    name: "default",
                    value : 1,
                    checked: this.data.default
                },
                {
                    xtype: "textarea",
                    fieldLabel: t("description"),
                    name: "description",
                    width: 300,
                    value: this.data.description
                }
            ]
        });
        return this.rootPanel;


    },

    getClassTree: function(url, id) {
        var tree = new Ext.tree.TreePanel({
            width: 200,
            title: t('class_definitions'),
            region: "west",
            enableDrag: true,
            enableDrop: false,
            ddGroup: "columnconfigelement",
            autoScroll: true,
            split: true,
            disabled: true,
            reference: this,
            root: {
                id: "0",
                root: true,
                text: t("base"),
                draggable: false
            }
        });

        Ext.Ajax.request({
            url: url,
            params: {
                id: id
            },
            success: this.initLayoutFields.bind(this, false)
        });

        return tree;
    },

    initLayoutFields: function (isCustom, response) {
        var data = Ext.decode(response.responseText);
        if (isCustom) {
            data = data.data;
        }
        this.data = data;

        var rootNode;
        rootNode = new Ext.tree.TreeNode( {
            id: "0",
            root: true,
            text: t("base"),
            reference: this,
            leaf: false,
            isTarget: true,
            expanded: true,
            draggable: isCustom,
            listeners: {
                click: this.onTreeNodeClick
            }

        });

        if (isCustom) {
            this.selectionPanel.setRootNode(rootNode);
            this.editPanel.add(this.getRootPanel());
            this.editPanel.doLayout();
        } else {
            this.resultPanel.setRootNode(rootNode);
        }

        var baseNode = rootNode;

        if (data.layoutDefinitions) {
            if (data.layoutDefinitions.childs) {
                for (var i = 0; i < data.layoutDefinitions.childs.length; i++) {
                    var attributePrefix = "";
                    var child = this.data.layoutDefinitions.childs[i];

                    var text = t(child.name);
                    if(child.nodeType == "objectbricks") {
                        text = ts(child.title) + " " + t("columns");
                        attributePrefix = child.title;
                    }

                    baseNode.appendChild(this.recursiveAddNode(child, baseNode, attributePrefix, isCustom));
                }
                rootNode.expand();
                baseNode.expand();
            }
        }
    },

    recursiveAddNode: function (con, scope, attributePrefix, addListener) {

        var fn = null;
        var newNode = null;

        if (con.datatype == "layout") {
            fn = this.addLayoutChild.bind(scope, con.fieldtype, con, addListener);
        }
        else if (con.datatype == "data") {
            fn = this.addDataChild.bind(scope, con.fieldtype, con, attributePrefix, this.showFieldName, addListener);
        }

        newNode = fn();

        if (con.childs) {
            for (var i = 0; i < con.childs.length; i++) {
                this.recursiveAddNode(con.childs[i], newNode, attributePrefix, addListener);
            }
        }

        return newNode;
    },

    addLayoutChild: function (type, initData, addListener) {

        var nodeLabel = t(type);

        if (initData) {
            if (initData.title) {
                nodeLabel = initData.title;
            } else if (initData.name) {
                nodeLabel = initData.name;
            }
        }

        var newNode = new Ext.tree.TreeNode({
            type: "layout",
            reference: this.attributes.reference,
            draggable: this.layoutDraggable,
            iconCls: "pimcore_icon_" + type,
            text: nodeLabel
        });

        newNode.attributes.object = new pimcore.object.classes.layout[type](newNode, initData);

        if (addListener) {
            newNode.addListener("click", this.attributes.reference.onTreeNodeClick);
        }

        this.appendChild(newNode);

        if(this.rendered) {
            this.renderIndent();
            this.expand();
        }

        return newNode;
    },

    addDataChild: function (type, initData, attributePrefix, showFieldname, addListener) {

        var isLeaf = true;
        var draggable = true;

        // localizedfields can be a drop target
        if(type == "localizedfields") {
            isLeaf = false;
        }

        var key = initData.name;
        if(attributePrefix) {
            key = attributePrefix + "~" + key;
        }

        var text = ts(initData.title);
        if(showFieldname) {
            text = text + " (" + key.replace("~", ".") + ")";
        }
        var newNode = new Ext.tree.TreeNode({
            text: text,
            key: key,
            type: "data",
            reference: this.attributes.reference,
            layout: initData,
            leaf: isLeaf,
            draggable: draggable,
            dataType: type,
            iconCls: "pimcore_icon_" + type
        });

        newNode.attributes.object = new pimcore.object.classes.data[type](newNode, initData);

        if (addListener) {
            newNode.addListener("click", this.attributes.reference.onTreeNodeClick);
        }

        this.appendChild(newNode);

        if(this.rendered) {
            this.renderIndent();
            this.expand();
        }

        return newNode;
    },

    onTreeNodeClick: function () {

        this.attributes.reference.saveCurrentNode();
        this.attributes.reference.editPanel.removeAll();

        if (this.attributes.object) {

            if (this.attributes.object.datax.locked) {
                return;
            }

            if (typeof(this.attributes.object.setInCustomLayoutEditor) == "function") {
                this.attributes.object.setInCustomLayoutEditor(true);
            }
            this.attributes.reference.editPanel.add(this.attributes.object.getLayout());
            this.attributes.reference.setCurrentNode(this.attributes.object);
        }

        if (this.attributes.root) {
            var rootPanel = this.attributes.reference.getRootPanel();
            this.attributes.reference.editPanel.add(rootPanel);
            this.attributes.reference.setCurrentNode("root");
        }

        this.attributes.reference.editPanel.doLayout();
    },

    setCurrentNode: function (cn) {
        this.currentNode = cn;
    },

    save: function () {
        var id = this.layoutChangeCombo.getValue();

        if (id > 0) {
            this.saveCurrentNode();

            delete this.data.layoutDefinitions;

            var m = Ext.encode(this.getData());
            var n = Ext.encode(this.data);

            if (this.getDataSuccess) {
                Ext.Ajax.request({
                    url: "/admin/class/save-custom-layout",
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
        }
    },

    saveOnComplete: function (response) {
        try {
            var res = Ext.decode(response.responseText);
            if(res.success) {
                pimcore.helpers.showNotification(t("success"), t("layout_saved_successfully"), "success");
                this.layoutComboStore.reload();
                this.data = res.data;
            } else {
                Ext.Msg.alert(t('error'), t(res.msg));
            }
        } catch (e) {
            this.saveOnError();
        }
    },

    saveOnError: function () {
        pimcore.helpers.showNotification(t("error"), t("layout_save_error"), "error");
    },

    addLayout: function () {
        Ext.MessageBox.prompt(t('add_layout'), t('enter_the_name_of_the_new_layout'), this.addLayoutComplete.bind(this),
            null, null, "");
    },

    addLayoutComplete: function (button, value, object) {
        if (button == "ok" && value.length > 2 // && regresult == value
            && !in_array(value.toLowerCase(), this.forbiddennames)) {
            Ext.Ajax.request({
                url: "/admin/class/add-custom-layout",
                params: {
                    name: value,
                    classId: this.klass.id
                },
                success: function (response) {

                    var data = Ext.decode(response.responseText);
                    if(data && data.success) {
                        this.editPanel.removeAll();
                        this.resultPanel.enable();
                        this.enableButtons();
                        this.layoutComboStore.reload();
                        this.currentLayoutId = data.id;
                        this.layoutChangeCombo.setValue(data.id);
                        this.initLayoutFields(true, response);
                    } else {
                        Ext.Msg.alert(t('error'), t('custom_layout_save_error'));
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_class'), t('invalid_class_name'));
        }
    },


    clearSelectionPanel: function() {
        this.selectionPanel.setRootNode(new Ext.tree.TreeNode(
            {
                hidden: true
            }
        ));
    },


    deleteLayout: function () {
        var id = this.layoutChangeCombo.getValue();

        if (id > 0) {
            Ext.Msg.confirm(t('delete'), t('delete_message'), function(btn){
                if (btn == 'yes'){
                    Ext.Ajax.request({
                        url: "/admin/class/delete-custom-layout",
                        params: {
                            id: id
                        }
                    });

                    this.layoutComboStore.reload();
                    this.currentLayoutId = 0;
                    this.layoutChangeCombo.setValue(this.currentLayoutId);

                    this.editPanel.removeAll();
                    this.clearSelectionPanel();
                    this.resultPanel.disable();
                    this.saveButton.disable();
                    this.importButton.disable();
                    this.exportButton.disable();
                    this.rootPanel = null;
                }
            }.bind(this));
        }
    },

    upload: function() {

        pimcore.helpers.uploadDialog(this.getUploadUrl(), "Filedata", function() {
            Ext.Ajax.request({
                url: "/admin/class/get-custom-layout",
                params: {
                    id: this.data.id
                },
                success: function(response) {
                    this.editPanel.removeAll();
                    this.initLayoutFields(true, response);
                    this.resultPanel.enable();
                    this.enableButtons();
                    this.currentLayoutId = layoutId;
                }.bind(this)

            });
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    },

    enableButtons: function() {
        this.saveButton.enable();
        this.importButton.enable();
        this.exportButton.enable();
    }

});
