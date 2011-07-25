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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.tags.objectsMetadata");
pimcore.object.tags.objectsMetadata = Class.create(pimcore.object.tags.objects, {

    type: "objectsMetadata",
    dataChanged:false,

    initialize: function (data, layoutConf) {
        this.data = [];
        this.layoutConf = layoutConf;
        if (data) {
            this.data = data;
        }

        var fields = [];
        var visibleFields = this.layoutConf.visibleFields.split(",");

        fields.push("id");

        for(var i = 0; i < visibleFields.length; i++) {
            fields.push(visibleFields[i]);
        }

        for(var i = 0; i < this.layoutConf.columns.length; i++) {
            fields.push(this.layoutConf.columns[i].key);
        }

        this.store = new Ext.data.ArrayStore({
            data: this.data,
            listeners: {
                add:function() {
                    this.dataChanged = true;
                }.bind(this),
                remove: function() {
                    this.dataChanged = true;
                }.bind(this),
                clear: function () {
                    this.dataChanged = true;
                }.bind(this),
                update: function(store) {
                    this.dataChanged = true;
                }.bind(this)
            },
            fields: fields
        });
    },


    createLayout: function(readOnly) {
        var autoHeight = false;
        if (intval(this.layoutConf.height) < 15) {
            autoHeight = true;
        }

        var cls = 'object_field';

        var visibleFields = this.layoutConf.visibleFields.split(",");

        var columns = [];
        columns.push({header: 'ID', dataIndex: 'id', width: 50});

        for(var i = 0; i < visibleFields.length; i++) {
            columns.push({header: ts(visibleFields[i]), dataIndex: visibleFields[i], width: 100, editor: null, renderer: renderer});
        }

        for(var i = 0; i < this.layoutConf.columns.length; i++) {
            var width = 100;
            if(this.layoutConf.columns[i].width) {
                width = this.layoutConf.columns[i].width
            }

            var editor = null;
            var renderer = null;
            var listeners = null;

            if(this.layoutConf.columns[i].type == "number" && !readOnly) {
                editor = new Ext.form.NumberField({});

            } else if(this.layoutConf.columns[i].type == "text" && !readOnly) {
                editor = new Ext.form.TextField({});
            } else if(this.layoutConf.columns[i].type == "select" && !readOnly) {
                var selectDataRaw = this.layoutConf.columns[i].value.split(";");
                var selectData = [];
                for(var i = 0; i < selectDataRaw.length; i++) {
                    selectData.push([selectDataRaw[i], selectDataRaw[i]]);
                }

                editor = new Ext.form.ComboBox({
                    typeAhead: true,
                    triggerAction: 'all',
                    lazyRender:true,
                    mode: 'local',
                    store: new Ext.data.ArrayStore({
                        fields: [
                            'value',
                            'label'
                        ],
                        data: selectData
                    }),
                    valueField: 'value',
                    displayField: 'label'
                });
            } else if(this.layoutConf.columns[i].type == "bool") {
                if(!readOnly) {
                    editor = new Ext.form.Checkbox();

                    listeners = {
                        "mousedown": function (col, grid, rowIndex, event) {
                            var store = grid.getStore();
                            var record = store.getAt(rowIndex);
                            record.set(col.dataIndex, !record.data[col.dataIndex]);
                            this.dataChanged = true;
                        }.bind(this)
                    };

                }
                renderer = function (value, metaData, record, rowIndex, colIndex, store) {
                    metaData.css += ' x-grid3-check-col-td';
                    return String.format('<div class="x-grid3-check-col{0}" style="background-position:10px center;">&#160;</div>', value ? '-on' : '');
                };

            }

            columns.push({
                header: ts(this.layoutConf.columns[i].label),
                dataIndex: this.layoutConf.columns[i].key,
                editor: editor,
                renderer: renderer,
                listeners: listeners,
                width: width
            });
        }


        columns.push({
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [
                            {
                                tooltip: t('open'),
                                icon: "/pimcore/static/img/icon/pencil_go.png",
                                handler: function (grid, rowIndex) {
                                    var data = grid.getStore().getAt(rowIndex);
                                    pimcore.helpers.openObject(data.data.id, "object");
                                }.bind(this)
                            }
                        ]
                    });
        if(!readOnly) {
            columns.push({
                xtype: 'actioncolumn',
                width: 30,
                items: [
                    {
                        tooltip: t('remove'),
                        icon: "/pimcore/static/img/icon/cross.png",
                        handler: function (grid, rowIndex) {
                            grid.getStore().removeAt(rowIndex);
                        }.bind(this)
                    }
                ]
            });

        }


        this.grid = new Ext.grid.EditorGridPanel({
            //plugins: [new Ext.ux.dd.GridDragDropRowOrder({})],
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: columns
            }),
            viewConfig: {
                markDirty: false
            },
            cls: cls,
            //autoExpandColumn: 'id',
            width: this.layoutConf.width,
            height: this.layoutConf.height,
            tbar: [
                {
                    xtype: "tbspacer",
                    width: 20,
                    height: 16,
                    cls: "pimcore_icon_droptarget"
                },
                {
                    xtype: "tbtext",
                    text: "<b>" + this.layoutConf.title + "</b>"
                },
                "->",
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    handler: this.empty.bind(this)
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    handler: this.openSearchEditor.bind(this)
                },
                this.getCreateControl()
            ],
            autoHeight: autoHeight,
            bodyCssClass: "pimcore_object_tag_objects"
        });

        this.grid.on("rowcontextmenu", this.onRowContextmenu);
        this.grid.reference = this;

        if(!readOnly) {
            this.grid.on("afterrender", function () {

                var dropTargetEl = this.grid.getEl();
                var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                    ddGroup    : 'element',
                    getTargetFromEvent: function(e) {
                        return this.grid.getEl().dom;
                        //return e.getTarget(this.grid.getView().rowSelector);
                    }.bind(this),
                    onNodeOver: function (overHtmlNode, ddSource, e, data) {

                        if (data.node.attributes.elementType == "object" && this.dndAllowed(data)) {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        } else {
                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }

                    }.bind(this),
                    onNodeDrop : function(target, dd, e, data) {

                        // check if data is a treenode, if not allow drop because of the reordering
                        if (!this.sourceIsTreeNode(data)) {
                            return true;
                        }

                        if (data.node.attributes.elementType != "object") {
                            return false;
                        }

                        if (this.dndAllowed(data)) {
                            var initData = {
                                id: data.node.attributes.id,
                                path: data.node.attributes.path,
                                type: data.node.attributes.className
                            };

                            if (!this.objectAlreadyExists(initData.id)) {
                                this.store.add(new this.store.recordType(initData, this.store.getCount() + 1));
                                return true;
                            }
                        }
                        return false;
                    }.bind(this)
                });
            }.bind(this));
        }


        return this.grid;
    },

    getLayoutEdit: function () {
        return this.createLayout(false);
    },

    getLayoutShow: function () {
        return this.createLayout(true);
    },
//
//
//    getLayoutShow: function () {
//
//        var autoHeight = false;
//        if (intval(this.layoutConf.height) < 15) {
//            autoHeight = true;
//        }
//
//        this.grid = new Ext.grid.GridPanel({
//            store: this.store,
//            colModel: new Ext.grid.ColumnModel({
//                defaults: {
//                    sortable: false
//                },
//                columns: [
//                    {header: 'ID', dataIndex: 'id', width: 50},
//                    {id: "path", header: t("path"), dataIndex: 'path', width: 200},
//                    {header: t("type"), dataIndex: 'type', width: 100},
//                    {
//                        xtype: 'actioncolumn',
//                        width: 30,
//                        items: [
//                            {
//                                tooltip: t('open'),
//                                icon: "/pimcore/static/img/icon/pencil_go.png",
//                                handler: function (grid, rowIndex) {
//                                    var data = grid.getStore().getAt(rowIndex);
//                                    pimcore.helpers.openObject(data.data.id, "object");
//                                }.bind(this)
//                            }
//                        ]
//                    }
//                ]
//            }),
//            width: this.layoutConf.width,
//            height: this.layoutConf.height,
//            autoHeight:autoHeight,
//            cls: "object_field",
//            autoExpandColumn: 'path',
//            title: this.layoutConf.title
//        });
//
//        return this.grid;
//    }
//    ,
//
    dndAllowed: function(data) {

        // check if data is a treenode, if not allow drop because of the reordering
        if (!this.sourceIsTreeNode(data)) {
            return true;
        }

        // only allow objects not folders
        if (data.node.attributes.type == "folder") {
            return false;
        }

        var classname = data.node.attributes.className;

        var classStore = pimcore.globalmanager.get("object_types_store");
        var classId = classStore.getAt(classStore.findExact("text", classname));
        var isAllowedClass = false;
        if(classId) {
            if (this.layoutConf.allowedClassId == classId.id) {
                isAllowedClass = true;
            }
        }
        return isAllowedClass;
    }
});