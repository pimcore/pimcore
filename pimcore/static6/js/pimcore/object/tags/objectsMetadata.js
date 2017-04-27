/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.tags.objectsMetadata");
pimcore.object.tags.objectsMetadata = Class.create(pimcore.object.tags.objects, {

    type: "objectsMetadata",
    dataChanged:false,
    idProperty: "id",
    pathProperty: "fullpath",

    initialize: function (data, fieldConfig) {
        this.data = [];
        this.fieldConfig = fieldConfig;

        var classStore = pimcore.globalmanager.get("object_types_store");
        var className = classStore.getById(fieldConfig.allowedClassId);

        var classNameText = (typeof(className) != 'undefined') ? className.data.text : '';
        this.fieldConfig.classes = [{classes: classNameText, id: fieldConfig.allowedClassId}];

        if (data) {
            this.data = data;
        }

        var fields = [];
        var visibleFields = this.fieldConfig.visibleFields.split(",");

        fields.push("id");
        fields.push("inheritedFields");
        fields.push("metadata");

        var i;

        for(i = 0; i < visibleFields.length; i++) {
            fields.push(visibleFields[i]);
        }

        for(i = 0; i < this.fieldConfig.columns.length; i++) {
            fields.push(this.fieldConfig.columns[i].key);
        }


        this.store = new Ext.data.JsonStore({
            data: this.data,
            idProperty: 'id',
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
        if (intval(this.fieldConfig.height) < 15) {
            autoHeight = true;
        }

        var cls = 'object_field';
        var i;

        var visibleFields = this.fieldConfig.visibleFields.split(",");

        var columns = [];
        columns.push({header: 'ID', dataIndex: 'id', width: 50});

        for (i = 0; i < visibleFields.length; i++) {
            if(!empty(visibleFields[i])) {
                var layout = this.fieldConfig.visibleFieldDefinitions[visibleFields[i]];

                var field = {
                    key: visibleFields[i],
                    label: layout.title == "fullpath" ? t("reference") : layout.title,
                    layout: layout,
                    position: i,
                    type: layout.fieldtype
                };

                var fc = pimcore.object.tags[layout.fieldtype].prototype.getGridColumnConfig(field);

                fc.width = 100;
                fc.hidden = false;
                fc.layout = field;
                fc.editor = null;
                fc.sortable = false;

                columns.push(fc);
            }
        }

        for (i = 0; i < this.fieldConfig.columns.length; i++) {
            var width = 100;
            if(this.fieldConfig.columns[i].width) {
                width = this.fieldConfig.columns[i].width;
            }

            var editor = null;
            var renderer = null;
            var listeners = null;

            if(this.fieldConfig.columns[i].type == "number" && !readOnly) {
                editor = new Ext.form.NumberField({});

            } else if(this.fieldConfig.columns[i].type == "text" && !readOnly) {
                editor = new Ext.form.TextField({});
            } else if(this.fieldConfig.columns[i].type == "select" && !readOnly) {
                var selectDataRaw = this.fieldConfig.columns[i].value.split(";");
                var selectData = [];
                for(var j = 0; j < selectDataRaw.length; j++) {
                    selectData.push([selectDataRaw[j], selectDataRaw[j]]);
                }

                editor = new Ext.form.ComboBox({
                    typeAhead: true,
                    forceSelection: true,
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
            } else if(this.fieldConfig.columns[i].type == "bool") {
                renderer = function (value, metaData, record, rowIndex, colIndex, store) {
                    if (value) {
                        return '<div style="text-align: center"><div role="button" class="x-grid-checkcolumn x-grid-checkcolumn-checked" style=""></div></div>';
                    } else {
                        return '<div style="text-align: center"><div role="button" class="x-grid-checkcolumn" style=""></div></div>';
                    }
                };

                listeners = {
                    "mousedown": this.cellMousedown.bind(this, this.fieldConfig.columns[i].key, this.fieldConfig.columns[i].type)
                };

                if(readOnly) {
                    columns.push(Ext.create('Ext.grid.column.Check'), {
                        header: ts(this.fieldConfig.columns[i].label),
                        dataIndex: this.fieldConfig.columns[i].key,
                        width: width,
                        renderer: renderer
                    });
                    continue;
                }

            }

            columns.push({
                header: ts(this.fieldConfig.columns[i].label),
                dataIndex: this.fieldConfig.columns[i].key,
                editor: editor,
                renderer: renderer,
                listeners: listeners,
                sortable: true,
                width: width
            });
        }


        if(!readOnly) {
            columns.push({
                xtype: 'actioncolumn',
                width: 40,
                items: [
                    {
                        tooltip: t('up'),
                        icon: "/pimcore/static6/img/flat-color-icons/up.svg",
                        handler: function (grid, rowIndex) {
                            if(rowIndex > 0) {
                                var rec = grid.getStore().getAt(rowIndex);
                                grid.getStore().removeAt(rowIndex);
                                grid.getStore().insert(rowIndex-1, [rec]);
                            }
                        }.bind(this)
                    }
                ]
            });
            columns.push({
                xtype: 'actioncolumn',
                width: 40,
                items: [
                    {
                        tooltip: t('down'),
                        icon: "/pimcore/static6/img/flat-color-icons/down.svg",
                        handler: function (grid, rowIndex) {
                            if(rowIndex < (grid.getStore().getCount()-1)) {
                                var rec = grid.getStore().getAt(rowIndex);
                                grid.getStore().removeAt(rowIndex);
                                grid.getStore().insert(rowIndex+1, [rec]);
                            }
                        }.bind(this)
                    }
                ]
            });
        }

        columns.push({
            xtype: 'actioncolumn',
            width: 40,
            items: [
                {
                    tooltip: t('open'),
                    icon: "/pimcore/static6/img/flat-color-icons/cursor.svg",
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
                width: 40,
                items: [
                    {
                        tooltip: t('remove'),
                        icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                        handler: function (grid, rowIndex) {
                            grid.getStore().removeAt(rowIndex);
                        }.bind(this)
                    }
                ]
            });
        }

        var tbarItems = [
            {
                xtype: "tbspacer",
                width: 20,
                height: 16,
                cls: "pimcore_icon_droptarget"
            },
            {
                xtype: "tbtext",
                text: "<b>" + this.fieldConfig.title + "</b>"
            }];

        if (!readOnly) {
            tbarItems = tbarItems.concat([
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
                this.getCreateControl()]);
        }


        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });


        this.component = Ext.create('Ext.grid.Panel', {
            store: this.store,
            border: true,
            style: "margin-bottom: 10px",
            enableDragDrop: true,
            ddGroup: 'element',
            trackMouseOver: true,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            columnLines: true,
            stripeRows: true,
            columns : {
                items: columns
            },
            viewConfig: {
                plugins: {
                    ptype: 'gridviewdragdrop',
                    draggroup: 'element'
                },
                markDirty: false,
                listeners: {
                    refresh: function (gridview) {
                        this.requestNicePathData(this.store.data);
                    }.bind(this),
                    drop: function () {
                        // this is necessary to avoid endless recursion when long lists are sorted via d&d
                        // TODO: investigate if there this is already fixed 6.2
                        if(this.object.toolbar && this.object.toolbar.items && this.object.toolbar.items.items) {
                            this.object.toolbar.items.items[0].focus();
                        }
                    }.bind(this),
                    // see https://github.com/pimcore/pimcore/issues/979
                    // probably a ExtJS 6.0 bug. withou this, dropdowns not working anymore if plugin is enabled
                    // TODO: investigate if there this is already fixed 6.2
                    cellmousedown: function( element , td , cellIndex , record , tr , rowIndex , e , eOpts ) {
                        if(cellIndex > visibleFields.length) {
                            return false;
                        } else {
                            return true;
                        }
                    }
                }
            },
            componentCls: cls,
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            tbar: {
                items: tbarItems,
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            autoHeight: autoHeight,
            bodyCls: "pimcore_object_tag_objects pimcore_editable_grid",
            plugins: [
                this.cellEditing
            ]
        });

        if(!readOnly) {
            this.component.on("rowcontextmenu", this.onRowContextmenu);
        }

        this.component.reference = this;

        if(!readOnly) {
            this.component.on("afterrender", function () {

                var dropTargetEl = this.component.getEl();
                var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                    ddGroup    : 'element',
                    getTargetFromEvent: function(e) {
                        return this.component.getEl().dom;
                        //return e.getTarget(this.grid.getView().rowSelector);
                    }.bind(this),
                    onNodeOver: function (overHtmlNode, ddSource, e, data) {
                        var record = data.records[0];
                        var data = record.data;
                        var fromTree = this.isFromTree(ddSource);

                        if (this.dndAllowed(data, fromTree)) {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        } else {
                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }
                    }.bind(this),
                    onNodeDrop : function(target, dd, e, data) {

                        var record = data.records[0];
                        var data = record.data;
                        var fromTree = this.isFromTree(dd);

                        if (this.dndAllowed(data, fromTree)) {

                            var toBeRequested = new Ext.util.Collection();

                            if(data["grid"] && data["grid"] == this.component) {
                                var rowIndex = this.component.getView().findRowIndex(e.target);
                                if(rowIndex !== false) {
                                    var rec = this.store.getAt(data.rowIndex);
                                    this.store.removeAt(data.rowIndex);
                                    toBeRequested.add(this.store.insert(rowIndex, [rec]));
                                    this.requestNicePathData(toBeRequested);
                                }
                            } else {
                                var initData = {
                                    id: data.id,
                                    metadata: '',
                                    inheritedFields: {}
                                };

                                if (!this.objectAlreadyExists(initData.id)) {
                                    toBeRequested.add(this.loadObjectData(initData, this.fieldConfig.visibleFields.split(",")));
                                    this.requestNicePathData(toBeRequested);
                                    return true;
                                }
                            }
                        }
                        return false;
                    }.bind(this)
                });
            }.bind(this));
        }


        return this.component;
    },

    getLayoutEdit: function () {
        return this.createLayout(false);
    },

    getLayoutShow: function () {
        return this.createLayout(true);
    },

    dndAllowed: function(data, fromTree) {
        // check if data is a treenode, if not allow drop because of the reordering
        if (!fromTree) {
            if(data["grid"] && data["grid"] == this.component) {
                return true;
            }
            return false;
        }

        // only allow objects not folders
        if (data.type == "folder" || data.elementType != "object") {
            return false;
        }

        var classname = data.className;

        var classStore = pimcore.globalmanager.get("object_types_store");
        var classId = classStore.getAt(classStore.findExact("text", classname));
        var isAllowedClass = false;

        if(classId) {
            if (this.fieldConfig.allowedClassId == classId.id) {
                isAllowedClass = true;
            }
        }
        return isAllowedClass;
    },

    addDataFromSelector: function (items) {

        if (items.length > 0) {
            toBeRequested = new Ext.util.Collection();

            for (var i = 0; i < items.length; i++) {
                var fields = this.fieldConfig.visibleFields.split(",");
                if (!this.objectAlreadyExists(items[i].id)) {
                    toBeRequested.add(this.loadObjectData(items[i], fields));
                }
            }

            this.requestNicePathData(toBeRequested);
        }
    },

    cellMousedown: function (key, colType, grid, cell, rowIndex, cellIndex, e) {

        // this is used for the boolean field type

        var store = grid.getStore();
        var record = store.getAt(rowIndex);

        if (colType == "bool") {
            record.set(key, !record.data[key]);
        }
    },

    loadObjectData: function(item, fields) {

        var newItem = this.store.add(item);

        Ext.Ajax.request({
            url: "/admin/object-helper/load-object-data",
            params: {
                id: item.id,
                'fields[]': fields
            },
            success: function (response) {
                var rdata = Ext.decode(response.responseText);
                var key;

                if(rdata.success) {
                    var rec = this.store.getById(item.id);
                    for(key in rdata.fields) {
                        rec.set(key, rdata.fields[key]);
                    }
                }
            }.bind(this)
        });

        return newItem;
    },

    normalizeTargetData: function(targets) {
        if (!targets) {
            return targets;
        }

        targets.each(function(record){
            var type = record.data.type;
            record.data.type = "object";
            record.data.subtype = type;
            record.data.path = record.data.fullpath;
        }, this);

        return targets;

    }
});