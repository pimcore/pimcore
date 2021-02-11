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

pimcore.registerNS("pimcore.object.tags.advancedManyToManyRelation");
pimcore.object.tags.advancedManyToManyRelation = Class.create(pimcore.object.tags.abstractRelations, {

    type: "advancedManyToManyRelation",
    dataChanged: false,
    idProperty: "rowId",
    pathProperty: "path",
    allowBatchAppend: true,
    allowBatchRemove: true,
    dataObjectFolderAllowed: false,

    initialize: function (data, fieldConfig) {
        this.data = [];
        this.fieldConfig = fieldConfig;

        this.fieldConfig.classes = this.fieldConfig.classes.filter(function (x) {
            if (x.classes == 'folder') {
                this.dataObjectFolderAllowed = true;
                return false;
            }
            return true;
        }.bind(this));

        if (data) {
            this.data = data;
        }

        var fields = [];

        fields.push("id");
        fields.push("path");
        fields.push("inheritedFields");
        fields.push("metadata");
        fields.push("type");
        fields.push("subtype");

        var i;

        for (i = 0; i < this.fieldConfig.columns.length; i++) {
            fields.push(this.fieldConfig.columns[i].key);
        }

        fields.push("rowId");

        var modelName = 'ObjectsMultihrefMetadataEntry';
        if (!Ext.ClassManager.isCreated(modelName)) {
            Ext.define(modelName, {
                extend: 'Ext.data.Model',
                idProperty: this.idProperty,
                fields: fields
            });
        }

        this.store = new Ext.data.JsonStore({
            data: this.data,
            listeners: {
                add: function () {
                    this.dataChanged = true;
                }.bind(this),
                remove: function () {
                    this.dataChanged = true;
                }.bind(this),
                clear: function () {
                    this.dataChanged = true;
                }.bind(this),
                update: function (store) {
                    if (store.ignoreDataChanged) {
                        return;
                    }
                    this.dataChanged = true;
                }.bind(this)
            },
            model: modelName
        });

    },


    createLayout: function (readOnly) {
        var autoHeight = false;
        if (intval(this.fieldConfig.height) < 15) {
            autoHeight = true;
        }

        var cls = 'object_field';
        var i;

        var columns = [];
        columns.push({text: 'ID', dataIndex: 'id', width: 50});
        columns.push({text: t('reference'), dataIndex: 'path', flex: 1, renderer: this.fullPathRenderCheck.bind(this)});

        var visibleFieldsCount = columns.length;

        for (i = 0; i < this.fieldConfig.columns.length; i++) {
            var width = 100;
            if (this.fieldConfig.columns[i].width) {
                width = this.fieldConfig.columns[i].width;
            }

            var cellEditor = null;
            var renderer = null;
            var listeners = null;

            if (this.fieldConfig.columns[i].type == "number" && !readOnly) {
                cellEditor = function () {
                    return new Ext.form.NumberField({});
                };
            } else if (this.fieldConfig.columns[i].type == "text" && !readOnly) {
                cellEditor = function () {
                    return new Ext.form.TextField({});
                };
            } else if (this.fieldConfig.columns[i].type == "select") {
                if(!readOnly) {
                    var selectData = [];
                    if (this.fieldConfig.columns[i].value) {
                        var selectDataRaw = this.fieldConfig.columns[i].value.split(";");
                        for (var j = 0; j < selectDataRaw.length; j++) {
                            selectData.push([selectDataRaw[j], t(selectDataRaw[j])]);
                        }
                    }

                    cellEditor = function (selectData) {
                        return new Ext.form.ComboBox({
                            typeAhead: true,
                            queryDelay: 0,
                            queryMode: "local",
                            forceSelection: true,
                            triggerAction: 'all',
                            lazyRender: false,
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
                    }.bind(this, selectData);
                }

                renderer = function (value, metaData, record, rowIndex, colIndex, store) {
                    return t(value);
                }
            } else if (this.fieldConfig.columns[i].type == "multiselect") {
                if(!readOnly) {
                    cellEditor = function (fieldInfo) {
                        return new pimcore.object.helpers.metadataMultiselectEditor({
                            fieldInfo: fieldInfo
                        });
                    }.bind(this, this.fieldConfig.columns[i]);
                }

                renderer = function (value, metaData, record, rowIndex, colIndex, store) {
                    if (Ext.isString(value)) {
                        value = value.split(',');
                    }

                    if (Ext.isArray(value)) {
                        return value.map(function (str) {
                            return t(str);
                        }).join(',')
                    } else {
                        return value;
                    }
                }
            } else if (this.fieldConfig.columns[i].type === "bool" || this.fieldConfig.columns[i].type === "columnbool") {
                renderer = function (value, metaData, record, rowIndex, colIndex, store) {
                    if (this.fieldConfig.noteditable) {
                        metaData.tdCls += ' grid_cbx_noteditable';
                    }

                    return Ext.String.format('<div style="text-align: center"><div role="button" class="x-grid-checkcolumn {0}" style=""></div></div>', value ? 'x-grid-checkcolumn-checked' : '');
                }.bind(this);

                if (readOnly) {
                    columns.push(Ext.create('Ext.grid.column.Check', {
                        text: t(this.fieldConfig.columns[i].label),
                        dataIndex: this.fieldConfig.columns[i].key,
                        width: width,
                        renderer: renderer
                    }));
                    continue;
                }

                cellEditor = function (type, readOnly) {
                    var editor = Ext.create('Ext.form.field.Checkbox', {style: 'margin-top: 2px;'});

                    if (!readOnly && type === "columnbool") {
                        editor.addListener('change', function (el, newValue) {
                            window.gridPanel = el.up('gridpanel');
                            window.el = el;
                            var gridPanel = el.up('gridpanel');
                            var columnKey = el.up().column.dataIndex;
                            if (newValue) {
                                gridPanel.getStore().each(function (record) {
                                    if (!!record.get(columnKey)) {
                                        // note, we don't need to check for the row here as the editor fires another change
                                        // on blur, which updates the underlying record without a subsequent event being fired.
                                        record.set(columnKey, false);
                                    }
                                });
                            }
                        });
                    }

                    return editor;
                }.bind(this, this.fieldConfig.columns[i].type, readOnly);
            }

            var columnConfig = {
                text: t(this.fieldConfig.columns[i].label),
                dataIndex: this.fieldConfig.columns[i].key,
                renderer: renderer,
                listeners: listeners,
                width: width
            };

            if (cellEditor) {
                columnConfig.getEditor = cellEditor;
            }

            columns.push(columnConfig);
        }


        columns.push({text: t("type"), dataIndex: 'type', width: 100});
        columns.push({text: t("subtype"), dataIndex: 'subtype', width: 100});


        if (!readOnly) {
            columns.push({
                xtype: 'actioncolumn',
                menuText: t('up'),
                width: 40,
                hideable: false,
                items: [
                    {
                        tooltip: t('up'),
                        icon: "/bundles/pimcoreadmin/img/flat-color-icons/up.svg",
                        handler: function (grid, rowIndex) {
                            if (rowIndex > 0) {
                                var rec = grid.getStore().getAt(rowIndex);
                                grid.getStore().removeAt(rowIndex);
                                grid.getStore().insert(rowIndex - 1, [rec]);
                            }
                        }.bind(this)
                    }
                ]
            });
            columns.push({
                xtype: 'actioncolumn',
                menuText: t('down'),
                width: 40,
                hideable: false,
                items: [
                    {
                        tooltip: t('down'),
                        icon: "/bundles/pimcoreadmin/img/flat-color-icons/down.svg",
                        handler: function (grid, rowIndex) {
                            if (rowIndex < (grid.getStore().getCount() - 1)) {
                                var rec = grid.getStore().getAt(rowIndex);
                                grid.getStore().removeAt(rowIndex);
                                grid.getStore().insert(rowIndex + 1, [rec]);
                            }
                        }.bind(this)
                    }
                ]
            });
        }

        columns.push({
            xtype: 'actioncolumn',
            menuText: t('open'),
            width: 40,
            hideable: false,
            items: [
                {
                    tooltip: t('open'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        var subtype = data.data.subtype;
                        if (data.data.type === "object" && data.data.subtype !== "folder" && data.data.subtype !== null) {
                            subtype = "object";
                        }
                        pimcore.helpers.openElement(data.data.id, data.data.type, subtype);

                    }.bind(this)
                }
            ]
        });

        if (!readOnly) {
            columns.push({
                xtype: 'actioncolumn',
                menuText: t('remove'),
                width: 40,
                hideable: false,
                items: [
                    {
                        tooltip: t('remove'),
                        icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                        handler: function (grid, rowIndex) {
                            grid.getStore().removeAt(rowIndex);
                        }.bind(this)
                    }
                ]
            });
        }

        var toolbarItems = this.getEditToolbarItems(readOnly);


        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function (editor, context, eOpts) {
                    editor.editors.each(function (e) {
                        try {
                            // complete edit, so the value is stored when hopping around with TAB
                            e.completeEdit();
                            Ext.destroy(e);
                        } catch (exception) {
                            // garbage collector was faster
                            // already destroyed
                        }
                    });

                    editor.editors.clear();
                }
            }
        });


        this.component = Ext.create('Ext.grid.Panel', {
            store: this.store,
            border: true,
            style: "margin-bottom: 10px",
            enableDragDrop: true,
            ddGroup: 'element',
            trackMouseOver: true,
            selModel: {
                selType: (this.fieldConfig.enableBatchEdit ? 'checkboxmodel' : 'rowmodel')
            },
            columnLines: true,
            stripeRows: true,
            columns: {
                defaults: {
                    sortable: false
                },
                items: columns
            },
            viewConfig: {
                plugins: {
                    ptype: 'gridviewdragdrop',
                    draggroup: 'element'
                },
                markDirty: false,
                enableTextSelection: true,
                listeners: {
                    afterrender: function (gridview) {
                        this.requestNicePathData(this.store.data, true);
                    }.bind(this),
                    drop: function () {
                        // this is necessary to avoid endless recursion when long lists are sorted via d&d
                        // TODO: investigate if there this is already fixed 6.2
                        if (this.object.toolbar && this.object.toolbar.items && this.object.toolbar.items.items) {
                            this.object.toolbar.items.items[0].focus();
                        }
                    }.bind(this),
                    // see https://github.com/pimcore/pimcore/issues/979
                    // probably a ExtJS 6.0 bug. withou this, dropdowns not working anymore if plugin is enabled
                    // TODO: investigate if there this is already fixed 6.2
                    cellmousedown: function (element, td, cellIndex, record, tr, rowIndex, e, eOpts) {
                        if (this.fieldConfig.noteditable == true || cellIndex >= visibleFieldsCount) {
                            return false;
                        } else {
                            return true;
                        }
                    }.bind(this)
                }
            },
            componentCls: cls,
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            tbar: {
                items: toolbarItems,
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            autoHeight: autoHeight,
            bodyCls: "pimcore_object_tag_objects pimcore_editable_grid",
            plugins: [
                this.cellEditing
            ],
            listeners: {
                rowdblclick: this.gridRowDblClickHandler
            }
        });

        this.component.on("rowcontextmenu", this.onRowContextmenu.bind(this));
        this.component.reference = this;

        if (!readOnly) {
            this.component.on("afterrender", function () {

                var dropTargetEl = this.component.getEl();
                var gridDropTarget = new Ext.dd.DropZone(dropTargetEl, {
                    ddGroup: 'element',
                    getTargetFromEvent: function (e) {
                        return this.component.getEl().dom;
                        //return e.getTarget(this.grid.getView().rowSelector);
                    }.bind(this),

                    onNodeOver: function (overHtmlNode, ddSource, e, data) {
                        var returnValue = Ext.dd.DropZone.prototype.dropAllowed;
                        data.records.forEach(function (record) {
                            var fromTree = this.isFromTree(ddSource);
                            if (!this.dndAllowed(record.data, fromTree)) {
                                returnValue = Ext.dd.DropZone.prototype.dropNotAllowed;
                            }
                        }.bind(this));

                        return returnValue;
                    }.bind(this),

                    onNodeDrop: function (target, dd, e, data) {

                        try {

                            this.nodeElement = data;
                            var fromTree = this.isFromTree(dd);
                            var toBeRequested = new Ext.util.Collection();

                            data.records.forEach(function (record) {
                                var data = record.data;
                                if (this.dndAllowed(data, fromTree)) {
                                    if (data["grid"] && data["grid"] == this.component) {
                                        var rowIndex = this.component.getView().findRowIndex(e.target);
                                        if (rowIndex !== false) {
                                            var rec = this.store.getAt(data.rowIndex);
                                            this.store.removeAt(data.rowIndex);
                                            toBeRequested.add(this.store.insert(rowIndex, [rec]));
                                            this.requestNicePathData(toBeRequested);
                                        }
                                    } else {
                                        var initData = {
                                            id: data.id,
                                            path: data.path,
                                            type: data.elementType,
                                            published: data.published
                                        };

                                        if (initData.type === "object") {
                                            if (data.className) {
                                                initData.subtype = data.className;
                                            } else {
                                                initData.subtype = "folder";
                                            }
                                        }

                                        if (initData.type === "document" || initData.type === "asset") {
                                            initData.subtype = data.type;
                                        }

                                        // check for existing element
                                        if (this.fieldConfig.allowMultipleAssignments || !this.elementAlreadyExists(initData.id, initData.type)) {
                                            toBeRequested.add(this.store.add(initData));
                                        }
                                    }
                                }
                            }.bind(this));

                            if (toBeRequested.length) {
                                this.requestNicePathData(toBeRequested);
                                return true;
                            }

                            return false;

                        } catch (e) {
                            console.log(e);
                        }

                    }.bind(this)
                });

                if (this.fieldConfig.enableBatchEdit) {
                    var grid = this.component;
                    var menu = grid.headerCt.getMenu();

                    var batchAllMenu = new Ext.menu.Item({
                        text: t("batch_change"),
                        iconCls: "pimcore_icon_table pimcore_icon_overlay_go",
                        handler: function (grid) {
                            var columnDataIndex = menu.activeHeader;
                            this.batchPrepare(columnDataIndex, grid, false, false);
                        }.bind(this, grid)
                    });

                    menu.add(batchAllMenu);

                    var batchSelectedMenu = new Ext.menu.Item({
                        text: t("batch_change_selected"),
                        iconCls: "pimcore_icon_structuredTable pimcore_icon_overlay_go",
                        handler: function (grid) {
                            menu = grid.headerCt.getMenu();
                            var columnDataIndex = menu.activeHeader;
                            this.batchPrepare(columnDataIndex, grid, true, false);
                        }.bind(this, grid)
                    });
                    menu.add(batchSelectedMenu);
                    menu.on('beforeshow', function (batchAllMenu, batchSelectedMenu, grid) {
                        var menu = grid.headerCt.getMenu();
                        var columnDataIndex = menu.activeHeader.dataIndex;
                        var metaIndex = this.fieldConfig.columnKeys.indexOf(columnDataIndex);

                        if (metaIndex < 0) {
                            batchSelectedMenu.hide();
                            batchAllMenu.hide();
                        } else {
                            batchSelectedMenu.show();
                            batchAllMenu.show();
                        }

                    }.bind(this, batchSelectedMenu, batchAllMenu, grid));
                }
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

    getEditToolbarItems: function (readOnly) {
        var toolbarItems = [
            {
                xtype: "tbspacer",
                width: 24,
                height: 24,
                cls: "pimcore_icon_droptarget"
            },
            {
                xtype: "tbtext",
                text: "<b>" + this.fieldConfig.title + "</b>"
            },
            "->"
        ];

        toolbarItems = toolbarItems.concat(this.getFilterEditToolbarItems());

        if (!readOnly) {
            toolbarItems = toolbarItems.concat([
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    handler: this.empty.bind(this)
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    handler: this.openSearchEditor.bind(this)
                }
                //,
                //this.getCreateControl()
            ]);
        }

        if (this.fieldConfig.assetsAllowed && this.fieldConfig.noteditable == false) {
            toolbarItems.push({
                xtype: "button",
                cls: "pimcore_inline_upload",
                iconCls: "pimcore_icon_upload",
                handler: this.uploadDialog.bind(this)
            });
        }

        return toolbarItems;
    },

    dndAllowed: function (data, fromTree) {

        var i;

        // check if data is a treenode, if not check if the source is the same grid because of the reordering
        if (!fromTree) {
            if (data["grid"] && data["grid"] == this.component) {
                return true;
            }
            return false;
        }

        var elementType = data.elementType;
        var isAllowed = false;
        var subType;

        if (elementType == "object" && this.fieldConfig.objectsAllowed) {

            if (data.type == 'folder') {
                if (this.dataObjectFolderAllowed || this.fieldConfig.classes.length <= 0) {
                    isAllowed = true;
                }
            } else {
                var classname = data.className;

                isAllowed = false;
                if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
                    for (i = 0; i < this.fieldConfig.classes.length; i++) {
                        if (this.fieldConfig.classes[i].classes == classname) {
                            isAllowed = true;
                            break;
                        }
                    }
                } else {
                    if (!this.dataObjectFolderAllowed) {
                        isAllowed = true;
                    }
                }
            }
        } else if (elementType == "asset" && this.fieldConfig.assetsAllowed) {
            subType = data.type;
            isAllowed = false;
            if (this.fieldConfig.assetTypes != null && this.fieldConfig.assetTypes.length > 0) {
                for (i = 0; i < this.fieldConfig.assetTypes.length; i++) {
                    if (this.fieldConfig.assetTypes[i].assetTypes == subType) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no asset types configured - allow all
                isAllowed = true;
            }

        } else if (elementType == "document" && this.fieldConfig.documentsAllowed) {
            subType = data.type;
            isAllowed = false;
            if (this.fieldConfig.documentTypes != null && this.fieldConfig.documentTypes.length > 0) {
                for (i = 0; i < this.fieldConfig.documentTypes.length; i++) {
                    if (this.fieldConfig.documentTypes[i].documentTypes == subType) {
                        isAllowed = true;
                        break;
                    }
                }
            } else {
                //no document types configured - allow all
                isAllowed = true;
            }
        }
        return isAllowed;

    },

    empty: function () {
        this.store.removeAll();
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts) {

        var menu = new Ext.menu.Menu();
        var data = record;

        // check if field noteditable property is false
        if (this.fieldConfig.noteditable == false) {
            menu.add(new Ext.menu.Item({
                text: t('remove'),
                iconCls: "pimcore_icon_delete",
                handler: this.removeElement.bind(this, rowIndex)
            }));
        }

        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "pimcore_icon_open",
            handler: function (data, item) {

                item.parentMenu.destroy();

                var subtype = data.data.subtype;
                if (data.data.type === "object" && data.data.subtype !== "folder" && data.data.subtype !== null) {
                    subtype = "object";
                }
                pimcore.helpers.openElement(data.data.id, data.data.type, subtype);
            }.bind(this, data)
        }));

        menu.add(new Ext.menu.Item({
            text: t('search'),
            iconCls: "pimcore_icon_search",
            handler: function (item) {
                item.parentMenu.destroy();
                this.openSearchEditor();
            }.bind(this)
        }));

        e.stopEvent();
        menu.showAt(e.getXY());
    },

    isDirty: function () {
        if (!this.isRendered()) {
            return false;
        }

        return this.dataChanged;
    },

    addDataFromSelector: function (items) {
        if (items.length > 0) {
            var toBeRequested = new Ext.util.Collection();

            for (var i = 0; i < items.length; i++) {
                if (this.fieldConfig.allowMultipleAssignments || !this.elementAlreadyExists(items[i].id, items[i].type)) {

                    var subtype = items[i].subtype;
                    if (items[i].type == "object") {
                        if (items[i].subtype == "object") {
                            if (items[i].classname) {
                                subtype = items[i].classname;
                            }
                        }
                    }

                    toBeRequested.add(this.store.add({
                        id: items[i].id,
                        path: items[i].fullpath,
                        type: items[i].type,
                        subtype: subtype,
                        published: items[i].published
                    }));
                }
            }

            this.requestNicePathData(toBeRequested);
        }
    },


    elementAlreadyExists: function (id, type) {

        // check max amount in field
        if (this.fieldConfig["maxItems"] && this.fieldConfig["maxItems"] >= 1) {
            if (this.store.getCount() >= this.fieldConfig.maxItems) {
                Ext.Msg.alert(t("error"), t("limit_reached"));
                return true;
            }
        }

        // check for existing element
        var result = this.store.queryBy(function (id, type, record, rid) {
            if (record.data.id == id && record.data.type == type) {
                return true;
            }
            return false;
        }.bind(this, id, type));

        if (result.length < 1) {
            return false;
        }
        return true;
    },

    isFromTree: function (ddSource) {
        var klass = Ext.getClass(ddSource);
        var className = klass.getName();
        var fromTree = className == "Ext.tree.ViewDragZone";
        return fromTree;
    },

    getValue: function () {

        var tmData = [];

        var data = this.store.queryBy(function (record, id) {
            return true;
        });


        for (var i = 0; i < data.items.length; i++) {
            tmData.push(data.items[i].data);
        }

        return tmData;
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.fieldConfig.assetUploadPath, "path", function (res) {
            try {
                var data = Ext.decode(res.response.responseText);
                if (data["id"]) {
                    var toBeRequested = new Ext.util.Collection();
                    toBeRequested.add(this.store.add({
                        id: data["id"],
                        path: data["fullpath"],
                        type: "asset",
                        subtype: data["type"]
                    }));
                    this.requestNicePathData(toBeRequested);
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this), null, this.context);
    },

    removeElement: function (index, item) {
        this.store.removeAt(index);
        item.parentMenu.destroy();
    },

    openSearchEditor: function () {

        var allowedTypes = [];
        var allowedSpecific = {};
        var allowedSubtypes = {};
        var i;

        if (this.fieldConfig.objectsAllowed) {
            allowedTypes.push("object");
            allowedSubtypes.object = [];
            if (this.fieldConfig.classes != null && this.fieldConfig.classes.length > 0) {
                allowedSpecific.classes = [];
                allowedSubtypes.object.push("object", "variant");
                for (i = 0; i < this.fieldConfig.classes.length; i++) {
                    allowedSpecific.classes.push(this.fieldConfig.classes[i].classes);

                }
            }
            if (this.dataObjectFolderAllowed) {
                allowedSubtypes.object.push("folder");
            }

            if (allowedSubtypes.length == 0) {
                allowedSubtypes.object = ["object", "folder", "variant"];
            }
        }
        if (this.fieldConfig.assetsAllowed) {
            allowedTypes.push("asset");
            if (this.fieldConfig.assetTypes != null && this.fieldConfig.assetTypes.length > 0) {
                allowedSubtypes.asset = [];
                for (i = 0; i < this.fieldConfig.assetTypes.length; i++) {
                    allowedSubtypes.asset.push(this.fieldConfig.assetTypes[i].assetTypes);
                }
            }
        }
        if (this.fieldConfig.documentsAllowed) {
            allowedTypes.push("document");
            if (this.fieldConfig.documentTypes != null && this.fieldConfig.documentTypes.length > 0) {
                allowedSubtypes.document = [];
                for (i = 0; i < this.fieldConfig.documentTypes.length; i++) {
                    allowedSubtypes.document.push(this.fieldConfig.documentTypes[i].documentTypes);
                }
            }
        }

        pimcore.helpers.itemselector(true, this.addDataFromSelector.bind(this), {
                type: allowedTypes,
                subtype: allowedSubtypes,
                specific: allowedSpecific
            },
            {
                context: Ext.apply({scope: "objectEditor"}, this.getContext())
            });

    },

    requestNicePathData: function (targets, isInitialLoad) {
        if (!this.object) {
            return;
        }

        var context = this.getContext();
        var loadEditModeData = false;
        if (isInitialLoad && this.fieldConfig.optimizedAdminLoading && context['containerType'] == 'object') {
            loadEditModeData = true;
        }

        var nicePathRequested = pimcore.helpers.requestNicePathData(
            {
                type: "object",
                id: this.object.id
            },
            targets,
            {
                idProperty: this.idProperty,
                loadEditModeData: loadEditModeData
            },
            this.fieldConfig,
            context,
            pimcore.helpers.requestNicePathDataGridDecorator.bind(this, this.component.getView()),
            pimcore.helpers.getNicePathHandlerStore.bind(this, this.store, {
                idProperty: this.idProperty,
                pathProperty: this.pathProperty,
                loadEditModeData: loadEditModeData,
                fields: this.fieldConfig.columnKeys
            }, this.component.getView())
        );

        // unfortunately we have to use a timeout here to adjust the height of grids configured
        // with autoHeight: true, there are no other events that would work, see also:
        // - https://github.com/pimcore/pimcore/pull/4337
        // - https://github.com/pimcore/pimcore/pull/4909
        // - https://github.com/pimcore/pimcore/pull/5367
        if (nicePathRequested) {
            window.setTimeout(function () {
                this.component.getView().refresh();
            }.bind(this), 500);
        }
    },

    getGridColumnConfig: function (field) {
        return {
            text: t(field.label), width: 150, sortable: false, dataIndex: field.key,
            getEditor: this.getWindowCellEditor.bind(this, field),
            renderer: pimcore.object.helpers.grid.prototype.advancedRelationGridRenderer.bind(this, field, "path")
        };
    },

    getCellEditValue: function () {
        return this.getValue();
    }
});

// @TODO BC layer, to be removed in v7.0
pimcore.object.tags.multihrefMetadata = pimcore.object.tags.advancedManyToManyRelation;
