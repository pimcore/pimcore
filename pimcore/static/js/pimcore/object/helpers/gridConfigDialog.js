

pimcore.registerNS("pimcore.object.helpers.gridConfigDialog");
pimcore.object.helpers.gridConfigDialog = Class.create({

    data: {},

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
            items.push(this.getClassTree("/admin/class/get", this.config.classid, t("object_class"), null));
            for(var i = 0; i < this.config.brickKeys.length; i++) {
                items.push(this.getClassTree("/admin/class/objectbrick-get", this.config.brickKeys[i], ts(this.config.brickKeys[i]), this.config.brickKeys[i]));
            }

            this.resultPanel = new Ext.Panel({
                region: "center",
                layout: "fit",
                title: t('class_definitions'),
                layout:'accordion',
                defaults: {
                    // applied to each contained panel
                    //bodyStyle: 'padding:10px'
                },
                layoutConfig: {
                    // layout-specific configs go here
                    titleCollapse: true,
                    animate: false,
                    activeOnTop: false
                },
                items: items
            });
        }

        return this.resultPanel;
    },

    getClassTree: function(url, id, title, attributePrefix) {

        var tree = new Ext.tree.TreePanel({
            title: title,
            xtype: "treepanel",
            region: "center",
            enableDrag: true,
            enableDrop: false,
            autoScroll: true,
            root: {
                id: "0",
                root: true,
                text: t("base"),
                reference: this,
                leaf: true,
                isTarget: true
            },
            listeners:{
                "dblclick": function(node) {
                    var copy = new Ext.tree.TreeNode( // copy it
                        Ext.apply({}, node.attributes)
                    );

                    if(this.selectionPanel && !this.selectionPanel.getRootNode().findChild("key", copy.attributes.key)) {
                        this.selectionPanel.getRootNode().appendChild(copy);
                    }

                }.bind(this)
            }
        });

        Ext.Ajax.request({
            url: url, //"/admin/class/get",
            params: {
                id: id // this.config.classid
            },
            success: this.initLayoutFields.bind(this, tree, attributePrefix)
        });

        return tree;
    },

    initLayoutFields: function (tree, attributePrefix, response) {
        var data = Ext.decode(response.responseText);

        if (data.layoutDefinitions) {
            if (data.layoutDefinitions.childs) {
                for (var i = 0; i < data.layoutDefinitions.childs.length; i++) {
                    tree.getRootNode().appendChild(this.recursiveAddNode(data.layoutDefinitions.childs[i], tree.getRootNode(), attributePrefix));
                }
                tree.getRootNode().expand();
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

        this.renderIndent();
        this.expand();

        return newNode;
    },

    addDataChild: function (type, initData, attributePrefix) {

        if(type != "objectbricks") {
            var isLeaf = true;

            // localizedfields can be a drop target
            if(type == "localizedfields") {
                isLeaf = false;
            }

            var key = initData.name;
            if(attributePrefix) {
                key = attributePrefix + "." + key;
            }


            var newNode = new Ext.tree.TreeNode({
                text: ts(initData.title),
                key: key,
                type: "data",
                leaf: isLeaf,
                dataType: type,
                iconCls: "pimcore_icon_" + type
            });

            this.appendChild(newNode);

            this.renderIndent();
            this.expand();

            return newNode;
        } else {
            return null;
        }

    }

});
