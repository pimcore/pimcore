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

pimcore.registerNS("pimcore.object.classes.klass");
pimcore.object.classes.klass = Class.create({

    allowedInType: 'object',
    disallowedDataTypes: [],
    context: "class",
    uploadRoute: 'pimcore_admin_dataobject_class_importclass',
    exportRoute: 'pimcore_admin_dataobject_class_exportclass',

    initialize: function (data, parentPanel, reopen, editorPrefix) {
        this.parentPanel = parentPanel;
        this.data = data;
        this.editorPrefix = editorPrefix;
        this.reopen = reopen;

        this.addTree();
        this.initLayoutFields();
        this.addLayout();
    },

    getUploadUrl: function(){
        return Routing.generate(this.uploadRoute, {id: this.getId()});
    },

    getExportUrl: function() {
        return Routing.generate(this.exportRoute, {id: this.getId()});
    },


    addTree: function() {
        this.tree = Ext.create('Ext.tree.Panel', {
            region: "west",
            width: 300,
            split: true,
            enableDD: true,
            autoScroll: true,
            root: {
                id: "0",
                root: true,
                text: t("general_settings"),
                leaf: true,
                iconCls: "pimcore_icon_class",
                isTarget: true
            },
            listeners: this.getTreeNodeListeners(),
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    ddGroup: "element"
                }
            }
        });
    },

    addLayout: function () {

        this.editpanel = new Ext.Panel({
            region: "center",
            bodyStyle: "padding: 10px;",
            autoScroll: true
        });

        var displayId = this.data.key ? this.data.key : this.data.id; // because the field-collections use that also

        var panelButtons = [];

        panelButtons.push({
            text: t("configure_custom_layouts"),
            iconCls: "pimcore_icon_class pimcore_icon_overlay_add",
            hidden: (this instanceof pimcore.object.fieldcollections.field) || (this instanceof pimcore.object.objectbricks.field),
            handler: this.configureCustomLayouts.bind(this)
        });

        panelButtons.push({
            text: t('reload_definition'),
            handler: this.onRefresh.bind(this),
            iconCls: "pimcore_icon_reload"
        });

        panelButtons.push({
            text: t("import"),
            iconCls: "pimcore_icon_upload",
            handler: this.upload.bind(this)
        });

        panelButtons.push({
            text: t("export"),
            iconCls: "pimcore_icon_download",
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
            //id: "pimcore_class_editor_panel_" + this.getId(),
            id: this.editorPrefix + this.getId(),
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
                url: Routing.generate('pimcore_admin_dataobject_class_get'),
                params: {
                    id: this.data.id
                },
                success: function(response) {
                    this.data = Ext.decode(response.responseText);
                    this.parentPanel.getEditPanel().removeAll();
                    this.addTree();
                    this.initLayoutFields();
                    this.addLayout();
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
            fn = this.addLayoutChild.bind(scope, con.fieldtype, con, this.context);
        }
        else if (con.datatype == "data") {
            fn = this.addDataChild.bind(scope, con.fieldtype, con, this.context);
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

        // @TODO: ignoredAliases are there for BC reasons, to be removed in v7
        var ignoredAliases = ['multihrefMetadata','objectsMetadata','objects','multihref','href','nonownerobjects'];
        ignoredAliases.forEach(function (item) {
            dataComps = array_remove_value(dataComps, item);
        });

        var parentRestrictions;
        var groups = [];
        var groupNames = ["text","numeric","date","select","media","relation","geo","crm","structured","other"];
        for (var i = 0; i < dataComps.length; i++) {
            var dataCompName = dataComps[i];
            var dataComp = pimcore.object.classes.data[dataCompName];

            // check for disallowed types
            var allowed = false;

            if('object' !== typeof dataComp) {
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
                        groups[group] = [];
                    }
                    var handler;
                    if (editMode) {
                        handler = this.changeDataType.bind(this, tree, record, dataComps[i], true, this.context);
                    } else {
                        handler = this.addNewDataChild.bind(this, record, dataComps[i], this.context);
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

        var allowedTypes = pimcore.object.helpers.layout.getAllowedTypes(this);

        var dataComps = Object.keys(pimcore.object.classes.data);

        for (var i = 0; i < dataComps.length; i++) {
            var dataCompName = dataComps[i];
            if ('object' === typeof pimcore.object.classes.data[dataCompName]) {
                continue;
            }
            var component = pimcore.object.classes.data[dataCompName];
            if(component.prototype.allowIn['localizedfield']) {
                allowedTypes.localizedfields.push(dataCompName);
            }

            if(component.prototype.allowIn['block']) {
                allowedTypes.block.push(dataCompName);
            }
        }


        // the child-type "data" is a placehoder for all data components


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
                            handler: function (record, type, context) {
                                var newNode = this.addLayoutChild.bind(record, type, null, context)();
                                newNode.getOwnerTree().getSelectionModel().select(newNode);
                                this.onTreeNodeClick(null, newNode);
                            }.bind(this, record, layouts[i], this.context)
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
                    text: t('convert_to'),
                    iconCls: "pimcore_icon_convert",
                    hideOnClick: false,
                    menu: changeDataMenu
                }));
            }

            if (record.data.type == "data") {
                menu.add(new Ext.menu.Item({
                    text: t('clone'),
                    iconCls: "pimcore_icon_clone",
                    hideOnClick: true,
                    handler: this.changeDataType.bind(this, tree, record, record.data.editor.type, false, this.context)
                }));
            }

            menu.add(new Ext.menu.Item({
                text: t('copy'),
                iconCls: "pimcore_icon_copy",
                hideOnClick: true,
                handler: this.copyNode.bind(this, tree, record)
            }));

            if (childsAllowed) {
                if (pimcore && pimcore.classEditor && pimcore.classEditor.clipboard) {
                    menu.add(new Ext.menu.Item({
                        text: t('paste'),
                        iconCls: "pimcore_icon_paste",
                        hideOnClick: true,
                        handler: this.dropNode.bind(this, tree, record)
                    }));
                }
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

    cloneNode:  function(tree, node) {
        var theReference = this;
        var nodeLabel = node.data.text;
        var nodeType = node.data.type;

        var config = {
            text: nodeLabel,
            type: nodeType,
            leaf: node.data.leaf,
            expanded: node.data.expanded
        };


        config.listeners = theReference.getTreeNodeListeners();

        if (node.data.editor) {
            config.iconCls = node.data.editor.getIconClass();
        }

        var newNode = node.createNode(config);

        var theData = {};

        if (node.data.editor) {
            theData = Ext.apply(theData, node.data.editor.datax);
        }

        if (node.data.editor) {
            var definitions = newNode.data.editor = pimcore.object.classes[nodeType];
            var editorType = node.data.editor.type;
            var editor = definitions[editorType];

            newNode.data.editor = new editor(newNode, theData);
        }

        if (nodeType == "data") {
            var availableFields = newNode.data.editor.availableSettingsFields;
            for (var i = 0; i < availableFields.length; i++) {
                var field = availableFields[i];
                if (node.data.editor.datax[field]) {
                    if (field != "name") {
                        newNode.data.editor.datax[field] = node.data.editor.datax[field];
                    }
                }
            }

            newNode.data.editor.applySpecialData(node.data.editor);
        }


        var len = node.childNodes ? node.childNodes.length : 0;

        var i = 0;

        // Move child nodes across to the copy if required
        for (i = 0; i < len; i++) {
            var childNode = node.childNodes[i];
            var clonedChildNode = this.cloneNode(tree, childNode);

            newNode.appendChild(clonedChildNode);
        }
        return newNode;
    },


    copyNode: function(tree, record) {
        if (!pimcore.classEditor) {
            pimcore.classEditor = {};
        }

        var newNode = this.cloneNode(tree, record);
        pimcore.classEditor.clipboard = newNode;

    },

    dropNode: function(tree, record) {
        var node = pimcore.classEditor.clipboard;
        var newNode = this.cloneNode(tree, node);

        record.appendChild(newNode);
        tree.updateLayout();
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
                var items = this.rootPanel.queryBy(function(item) {
                    if (item == this.compositeIndicesPanel) {
                        return false;
                    }
                    return true;
                });

                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    if (typeof item.getValue == "function") {
                        this.data[item.name] = item.getValue();
                    }
                }

                if (this.compositeIndicesPanel) {
                    this.collectCompositeIndices();
                }
            }
        }
    },

    collectCompositeIndices: function() {
        var indexData = [];
        for(let s=0; s<this.compositeIndicesPanel.items.items.length; s++) {
            var entry = this.compositeIndicesPanel.items.items[s];
            var items = entry.queryBy(function(item) {
                return true;
            });

            var indexItem = {};
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                if (typeof item.getValue == "function") {
                    indexItem[item.name] = item.getValue();
                }
            }
            indexData.push(indexItem);
        }

        this.data["compositeIndices"] = indexData;
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

        var getPhpClassName = function (name) {
            return "Pimcore\\Model\\DataObject\\" + ucfirst(name);
        };

        var iconStore = new Ext.data.ArrayStore({
            proxy: {
                url: Routing.generate('pimcore_admin_dataobject_class_geticons'),
                type: 'ajax',
                reader: {
                    type: 'json'
                },
                extraParams: {
                    classId: this.getId()
                }
            },
            fields: ["text", "value"]
        });

        var iconField = new Ext.form.field.Text({
            id: "iconfield-" + this.getId(),
            name: "icon",
            width: 396,
            value: this.data.icon,
            listeners: {
                "afterrender": function (el) {
                    el.inputEl.applyStyles("background:url(" + el.getValue() + ") right center no-repeat;");
                }
            }
        });

        this.compositeIndexTypeStore = new Ext.data.ArrayStore({
            data: [['query'], ['localized_query'],['store'], ['localized_store']],
            fields: ['value']
        });

        var suggestedColumns = [];
        var store = this.tree.getStore();
        var data = store.getData();
        for (let i = 0; i < data.items.length; i++) {
            let record = data.items[i];
            if (record.data.type == "data") {
                suggestedColumns.push([record.data.text]);
            }
        }

        this.tagstore = new Ext.data.ArrayStore({
            data: suggestedColumns,
            fields: ['value']
        });

        this.compositeIndicesPanel = new Ext.Panel({
            autoScroll: true
        });

        this.rootPanel = new Ext.form.FormPanel({
            title: '<b>' + t("general_settings") + '</b>',
            bodyStyle: 'padding: 10px;',
            defaults: {
                labelWidth: 200
            },
            items: [
                {
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "name",
                    width: 500,
                    enableKeyEvents: true,
                    value: this.data.name,
                    listeners: {
                        keyup: function (el) {
                            this.rootPanel.getComponent("phpClassName").setValue(getPhpClassName(el.getValue()))
                        }.bind(this)
                    }
                },
                {
                    xtype: "textarea",
                    fieldLabel: t("description"),
                    name: "description",
                    width: 500,
                    value: this.data.description
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("unique_identifier"),
                    disabled: true,
                    value: this.data.id,
                    width: 500
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("PHP Class Name"),
                    name: "phpClassName",
                    itemId: "phpClassName",
                    width: 500,
                    disabled: true,
                    value: getPhpClassName(this.data.name)
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("parent_php_class"),
                    name: "parentClass",
                    width: 600,
                    value: this.data.parentClass
                },
                {
                    xtype: "textfield",
                    width: 600,
                    name: "implementsInterfaces",
                    fieldLabel: t("implements_interfaces"),
                    value: this.data.implementsInterfaces
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("use_traits"),
                    name: "useTraits",
                    width: 600,
                    value: this.data.useTraits
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("listing_parent_php_class"),
                    name: "listingParentClass",
                    width: 600,
                    value: this.data.listingParentClass
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("listing_use_traits"),
                    name: "listingUseTraits",
                    width: 600,
                    value: this.data.listingUseTraits
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("link_generator_reference"),
                    name: "linkGeneratorReference",
                    width: 600,
                    value: this.data.linkGeneratorReference
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("preview_url"),
                    name: "previewUrl",
                    width: 600,
                    value: this.data.previewUrl
                },
                {
                    xtype: "fieldcontainer",
                    layout: "hbox",
                    fieldLabel: t("icon"),
                    defaults: {
                        labelWidth: 200
                    },
                    items: [
                        iconField,
                        {
                            xtype: "combobox",
                            store: iconStore,
                            width: 50,
                            valueField: 'value',
                            displayField: 'text',
                            listeners: {
                                select: function (ele, rec, idx) {
                                    var icon = ele.container.down("#iconfield-" + this.getId());
                                    var newValue = rec.data.value;
                                    icon.component.setValue(newValue);
                                    icon.component.inputEl.applyStyles("background:url(" + newValue + ") right center no-repeat;");
                                    return newValue;
                                }.bind(this)
                            }
                        },
                        {
                            iconCls: "pimcore_icon_refresh",
                            xtype: "button",
                            tooltip: t("refresh"),
                            handler: function(iconField) {
                                iconField.inputEl.applyStyles("background:url(" + iconField.getValue() + ") right center no-repeat;");
                            }.bind(this, iconField)
                        },
                        {
                            xtype: "button",
                            iconCls: "pimcore_icon_icons",
                            text: t('icon_library'),
                            handler: function () {
                                pimcore.helpers.openGenericIframeWindow("icon-library", Routing.generate('pimcore_admin_misc_iconlist'), "pimcore_icon_icons", t("icon_library"));
                            }
                        }
                    ]
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("group"),
                    name: "group",
                    width: 600,
                    value: this.data.group
                },
                this.allowInheritance,
                this.allowVariants,
                this.showVariants,
                {
                    xtype: "checkbox",
                    fieldLabel: t("generate_type_declarations"),
                    name: "generateTypeDeclarations",
                    checked: this.data.generateTypeDeclarations
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("show_applogger_tab"),
                    name: "showAppLoggerTab",
                    checked: this.data.showAppLoggerTab
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("show_fieldlookup"),
                    name: "showFieldLookup",
                    checked: this.data.showFieldLookup
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("enable_grid_locking"),
                    name: "enableGridLocking",
                    checked: this.data.enableGridLocking
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("encrypt_data"),
                    name: "encryption",
                    style: 'margin: 0',
                    checked: this.data.encryption
                },
                {
                    xtype: 'container',
                    html: t('encrypt_data_description'),
                    style: 'margin-bottom:10px'
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
                    boxLabel: "ID (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.id",
                    checked: this.data.propertyVisibility.grid.id
                },
                {
                    xtype: "checkbox",
                    boxLabel: "ID (" + t("search") + ")",
                    name: "propertyVisibility.search.id",
                    checked: this.data.propertyVisibility.search.id
                },
                {
                    xtype: "checkbox",
                    boxLabel: t("key") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.key",
                    checked: this.data.propertyVisibility.grid.key
                },
                {
                    xtype: "checkbox",
                    boxLabel: t("key") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.key",
                    checked: this.data.propertyVisibility.search.key
                },
                {
                    xtype: "checkbox",
                    boxLabel: t("path") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.path",
                    checked: this.data.propertyVisibility.grid.path
                },
                {
                    xtype: "checkbox",
                    boxLabel: t("path") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.path",
                    checked: this.data.propertyVisibility.search.path
                },
                {
                    xtype: "checkbox",
                    boxLabel: t("published") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.published",
                    checked: this.data.propertyVisibility.grid.published
                },
                {
                    xtype: "checkbox",
                    boxLabel: t("published") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.published",
                    checked: this.data.propertyVisibility.search.published
                },
                {
                    xtype: "checkbox",
                    boxLabel: t("modificationDate") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.modificationDate",
                    checked: this.data.propertyVisibility.grid.modificationDate
                },
                {
                    xtype: "checkbox",
                    boxLabel: t("modificationDate") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.modificationDate",
                    checked: this.data.propertyVisibility.search.modificationDate
                },
                {
                    xtype: "checkbox",
                    boxLabel: t("creationDate") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.creationDate",
                    checked: this.data.propertyVisibility.grid.creationDate
                },
                {
                    xtype: "checkbox",
                    boxLabel: t("creationDate") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.creationDate",
                    checked: this.data.propertyVisibility.search.creationDate
                },
                {
                    xtype: "displayfield",
                    hideLabel: true,
                    width: 600,
                    value: "<b>" + t('composite_indices') + "</b>",
                    cls: "pimcore_extra_label_headline"
                },
                {
                    xtype: 'button',
                    text: t('add'),
                    iconCls: "pimcore_icon_add",
                    handler: function () {
                        this.addCompositeIndex();
                    }.bind(this)
                },
                this.compositeIndicesPanel,
                {
                    xtype: "displayfield",
                    hideLabel: true,
                    width: 600,
                    value: "<b>" + t('uses_these_bricks') + "</b>",
                    cls: "pimcore_extra_label_headline"
                },
                this.getBricksGrid()

            ]
        });

        if (this.data.compositeIndices) {
            for (let i = 0; i < this.data.compositeIndices.length; i++) {
                let indexData = this.data.compositeIndices[i];
                this.addCompositeIndex(indexData);
            }
        }

        this.rootPanel.on("afterrender", function() {
            this.usagesStore.reload();
        }.bind(this));

        return this.rootPanel;
    },

    addCompositeIndex: function(data) {
        data = data || {};
        var keyField = {
            xtype: 'textfield',
            name: "index_key",
            fieldLabel: t("key"),
            labelWidth: 100,
            width: 250,
            value: data.index_key
        };

        var tagsField = new Ext.form.field.Tag({
            name: "index_columns",
            width:550,
            resizable: true,
            minChars: 2,
            store: this.tagstore,
            fieldLabel: t("columns"),
            value: data.columns,
            draggable: true,
            displayField: 'value',
            valueField: 'value',
            forceSelection: false,
            delimiter: '\x01',
            createNewOnEnter: true,
            componentCls: 'superselect-no-drop-down',
            value: data.index_columns
        });

        var removeButton = new Ext.button.Button({
            iconCls: "pimcore_icon_minus",
            style: "margin-left: 10px"
        });

        var typeCombo = {
            xtype: 'combo',
            name: "index_type",
            triggerAction: "all",
            editable: true,
            queryMode: 'local',
            autoComplete: false,
            forceSelection: true,
            selectOnFocus: true,
            fieldLabel: t("table"),
            store: this.compositeIndexTypeStore,
            width: 250,
            displayField: 'value',
            valueField: 'value',
            value: data.index_type ? data.index_type : "query",
            labelWidth: 70,
            style: "margin-left: 10px"
        };

        var keyEntry = new Ext.form.FieldContainer({
            layout: 'hbox',
            border: false,
            items: [keyField, typeCombo, removeButton]
        });


        var entry = new Ext.form.FieldContainer({
            layout: 'vbox',
            border: false,
            items: [keyEntry, tagsField]
        });


        removeButton.addListener("click", function() {
            this.compositeIndicesPanel.remove(entry);
        }.bind(this, entry));

        this.compositeIndicesPanel.add(entry);
    },

    getBricksGrid: function() {
        this.usagesStore = new Ext.data.ArrayStore({
            proxy: {
                url: Routing.generate('pimcore_admin_dataobject_class_getbrickusages'),
                type: 'ajax',
                reader: {
                    type: 'json'
                },
                extraParams: {
                    classId: this.getId()
                }
            },
            fields: ["objectbrick", "field"]
        });

        var usagesGrid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.usagesStore,
            columnLines: true,
            stripeRows: true,
            plugins: ['gridfilters'],
            width: 600,
            columns: [
                {text: t('objectbrick'), sortable: true, dataIndex: 'objectbrick', filter: 'string', flex: 1},
                {text: t('field'), sortable: true, dataIndex: 'field', filter: 'string', flex: 1}
            ],
            viewConfig: {
                forceFit: true
            }
        });
        return usagesGrid;

    },

    addLayoutChild: function (type, initData, context) {

        var nodeLabel = t(type);

        if (initData) {
            if (initData.name) {
                nodeLabel = initData.name;
            }
        }

        var newNode = {
            text: nodeLabel,
            type: "layout",
            iconCls: pimcore.object.classes.layout[type].prototype.getIconClass(),
            leaf: false,
            expandable: false,
            expanded: true
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

    addNewDataChild: function (record, type, context) {
        var node = this.addDataChild.bind(record, type, {}, context)();
        node.getOwnerTree().getSelectionModel().select(node);
        this.onTreeNodeClick(null, node);

        var result = this.editpanel.query('field[name=name]');
        if(result.length && typeof result[0]['focus'] == 'function') {
            result[0].focus();
        }
    },

    addDataChild: function (type, initData, context) {

        var nodeLabel = '';

        if (initData) {
            if (initData.name) {
                nodeLabel = initData.name;
            }
        }

        var newNode = {
            text: nodeLabel,
            type: "data",
            leaf: true,
            iconCls: pimcore.object.classes.data[type].prototype.getIconClass()
        };

        if (type == "localizedfields" || type == "block") {
            newNode.leaf = false;
            newNode.expanded = true;
            newNode.expandable = false;
        }

        newNode = this.appendChild(newNode);

        var editor = new pimcore.object.classes.data[type](newNode, initData);
        editor.setContext(context);
        newNode.set("editor", editor);

        this.expand();

        return newNode;
    },

    changeDataType: function (tree, record, type, removeExisting, context) {
        try {
            this.saveCurrentNode();

            var nodeLabel = record.data.text;

            var theData = {};

            theData.name = nodeLabel;
            theData.datatype = "data";
            theData.fieldtype = type;

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


            var parentNode = record.parentNode;

            var newNode = {
                text: nodeLabel,
                type: "data",
                leaf: true,
                iconCls: pimcore.object.classes.data[type].prototype.getIconClass()
            };

            newNode = parentNode.createNode(newNode);

            if (!removeExisting) {
                theData.name = nodeLabel;
            }

            var editor = new pimcore.object.classes.data[type](newNode, theData);
            editor.setContext(context);
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

                var containerAwareDataName = data.name;
                var parentNode = node.parentNode;
                while (parentNode) {
                    if (parentNode.data.editor && Ext.isFunction(parentNode.data.editor.getData)) {
                        var parentData = parentNode.data.editor.getData();
                        if (parentData.datatype == "data" && parentNode.data.editor.type == "block") {
                            containerAwareDataName = "block-" + parentData.name + "-" + containerAwareDataName;
                            break;
                        }
                    }

                    parentNode = parentNode.parentNode;
                }

                if ((fieldValidation && in_arrayi(containerAwareDataName,this.usedFieldNames) == false) || data.name == "localizedfields" && data.fieldtype == "localizedfields") {

                    if(data.datatype == "data") {
                        this.usedFieldNames.push(containerAwareDataName);
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

        var isValidName = /^[a-zA-Z][a-zA-Z0-9]+$/;

        if (this.data["name"].length > 2 &&
            isValidName.test(this.data["name"]) &&
            !in_arrayi(this.data["name"], this.parentPanel.forbiddenNames)
        ) {
            delete this.data.layoutDefinitions;

            var m = Ext.encode(this.getData());
            var n = Ext.encode(this.data);

            if (this.getDataSuccess) {
                Ext.Ajax.request({
                    url: Routing.generate('pimcore_admin_dataobject_class_save'),
                    method: "PUT",
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
            Ext.Msg.alert(' ', t('invalid_class_name'));
        }
    },

    saveOnComplete: function (response) {

        try {
            var res = Ext.decode(response.responseText);
            if(res.success) {
                // refresh all class stores
                this.parentPanel.tree.getStore().load();
                pimcore.globalmanager.get("object_types_store").load();
                pimcore.globalmanager.get("object_types_store_create").load();

                // set the current modification date, to detect modifications on the class which are not made here
                this.data.modificationDate = res['class'].modificationDate;

                pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
            } else {
                if (res.message) {
                    pimcore.helpers.showNotification(t("error"), res.message, "error");
                } else {
                    throw "save was not successful, see log files in /var/logs";
                }
            }
        } catch (e) {
            this.saveOnError();
        }

    },

    saveOnError: function () {
        pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
    },

    onRefresh: function() {
        this.parentPanel.getEditPanel().remove(this.panel);
        this.reopen();
    }
});
