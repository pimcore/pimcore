

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

                this.data.columns.push(obj);
            }.bind(this));
        }

        return this.data;
    },

    getLanguageSelection: function () {

        var storedata = [];
        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {
            storedata.push([pimcore.settings.websiteLanguages[i], pimcore.available_languages[pimcore.settings.websiteLanguages[i]]])
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
                childs.push({
                    text: nodeConf.label,
                    key: nodeConf.key,
                    type: "data",
                    dataType: nodeConf.dataType,
                    leaf: true,
                    layout: nodeConf.layout,
                    iconCls: "pimcore_icon_" + nodeConf.dataType
                });
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
            this.resultPanel = this.getClassTree("/admin/class/get-class-definition-for-column-config", this.config.classid);
        }

        return this.resultPanel;
    },

    getClassTree: function(url, id) {

        var tree = new Ext.tree.TreePanel({
            title: t('class_definitions'),
            xtype: "treepanel",
            region: "center",
            enableDrag: true,
            enableDrop: false,
            autoScroll: true,
            rootVisible: false,
            root: {
                id: "0",
                root: true,
                text: t("base"),
                draggable: false,
                leaf: true,
                isTarget: true
            },
            listeners:{
                "dblclick": function(node) {
                    if(!node.attributes.root && node.attributes.type != "layout" && node.attributes.dataType != 'localizedfields') {
                        var copy = new Ext.tree.TreeNode( // copy it
                                Ext.apply({}, node.attributes)
                                );

                        if(this.selectionPanel && !this.selectionPanel.getRootNode().findChild("key", copy.attributes.key)) {
                            this.selectionPanel.getRootNode().appendChild(copy);
                        }
                    }
                }.bind(this)
            }
        });

        Ext.Ajax.request({
            url: url, //"/admin/class/get",
            params: {
                id: id // this.config.classid
            },
            success: this.initLayoutFields.bind(this, tree)
        });

        return tree;
    },

    initLayoutFields: function (tree, response) {
        var data = Ext.decode(response.responseText);

        var keys = Object.keys(data);
        for(var i = 0; i < keys.length; i++) {
            if (data[keys[i]]) {
                if (data[keys[i]].childs) {
                    var attributePrefix = "";
                    var text = t(data[keys[i]].nodeLabel);
                    if(data[keys[i]].nodeType == "objectbricks") {
                        text = ts(data[keys[i]].nodeLabel) + " " + t("columns");
                        attributePrefix = data[keys[i]].nodeLabel;
                    }
                    var baseNode = new Ext.tree.TreeNode({
                        type: "layout",
                        draggable: false,
                        iconCls: "pimcore_icon_" + data[keys[i]].nodeType,
                        text: text
                    });

                    tree.getRootNode().appendChild(baseNode);
                    for (var j = 0; j < data[keys[i]].childs.length; j++) {
                        baseNode.appendChild(this.recursiveAddNode(data[keys[i]].childs[j], baseNode, attributePrefix));
                    }
                    if(data[keys[i]].nodeType == "object") {
                        baseNode.expand();
                    } else {
                        baseNode.collapse();
                    }
                }
            }
        }
    },

    recursiveAddNode: function (con, scope, attributePrefix) {

        var fn = null;
        var newNode = null;

        if (con.datatype == "layout") {
            fn = this.addLayoutChild.bind(scope, con.fieldtype, con);
        }
        else if (con.datatype == "data") {
            fn = this.addDataChild.bind(scope, con.fieldtype, con, attributePrefix);
        }

        newNode = fn();

        if (con.childs) {
            for (var i = 0; i < con.childs.length; i++) {
                this.recursiveAddNode(con.childs[i], newNode, attributePrefix);
            }
        }

        return newNode;
    },

    addLayoutChild: function (type, initData) {

        var nodeLabel = t(type);

        if (initData) {
            if (initData.name) {
                nodeLabel = initData.name;
            }
        }
        var newNode = new Ext.tree.TreeNode({
            type: "layout",
            draggable: false,
            iconCls: "pimcore_icon_" + type,
            text: nodeLabel
        });

        this.appendChild(newNode);

        if(this.rendered) {
            this.renderIndent();
            this.expand();
        }

        return newNode;
    },

    addDataChild: function (type, initData, attributePrefix) {

        if(type != "objectbricks" && !initData.invisible) {
            var isLeaf = true;
            var draggable = true;

            // localizedfields can be a drop target
            if(type == "localizedfields") {
                isLeaf = false;
                draggable = false;
            }

            var key = initData.name;
            if(attributePrefix) {
                key = attributePrefix + "~" + key;
            }

            var newNode = new Ext.tree.TreeNode({
                text: ts(initData.title),
                key: key,
                type: "data",
                layout: initData,
                leaf: isLeaf,
                draggable: draggable,
                dataType: type,
                iconCls: "pimcore_icon_" + type
            });

            this.appendChild(newNode);

            if(this.rendered) {
                this.renderIndent();
                this.expand();
            }

            return newNode;
        } else {
            return null;
        }

    }

});
