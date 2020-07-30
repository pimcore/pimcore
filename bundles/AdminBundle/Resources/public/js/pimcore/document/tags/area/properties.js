
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

pimcore.registerNS("pimcore.document.tags.properties");
pimcore.document.tags.properties = Class.create(pimcore.element.properties, {

    disallowedKeys: [],

    getGrid: function () {

        if (this.propertyGrid == null) {

            var propertyTypes = new Ext.data.ArrayStore({
                fields: ['id', 'name'],
                data: [
                    ["text", "Text"],
                    ["document", "Document"],
                    ["asset", "Asset"],
                    ["object", "Object"],
                    ["bool", "Checkbox"]
                ]
            });

            var customKey = new Ext.form.TextField({
                name: 'key',
                emptyText: t('key')
            });

            var customType = new Ext.form.ComboBox({
                name: "type",
                valueField: "id",
                displayField:'name',
                store: propertyTypes,
                editable: false,
                triggerAction: 'all',
                mode: "local",
                listWidth: 200,
                emptyText: t('type')
            });

            // prepare store data
            var property = null;
            var keys = Object.keys(this.element.properties);
            var key = null;
            var storeData = [];

            if (keys.length > 0) {
                for (var i = 0; i < keys.length; i++) {
                    key = keys[i];
                    property = this.element.properties[key];

                    if (property && typeof property == "object") {
                        storeData.push({
                            name: key,
                            type: property.type,
                            data: property.data,
                            config: property.config,
                            description: property.description,
                            configured: property.configured ?? false,
                        });
                    }
                }
            }

            var store = new Ext.data.Store({
                autoDestroy: true,
                data: {properties: storeData},
                sortInfo:{field: 'configured', direction: "ASC"},
                proxy: {
                    type: 'memory',
                    reader: {
                        type: 'json',
                        rootProperty: 'properties'
                    }
                },
                fields: ['name', 'type',{name: "data", type: "string", convert: function (v, rec) {
                        if (rec.data.type == "document" || rec.data.type == "asset" || rec.data.type == "object") {
                            var type = rec.data.type;
                            if (type == "document") {
                                if (v && typeof v == "object") {
                                    return v.path + v.key;
                                }
                            }
                            else if (type == "asset") {
                                if (v && typeof v == "object") {
                                    return v.path + v.filename;
                                }
                            }
                            else if (type == "object") {
                                if (v && typeof v == "object") {
                                    return v.o_path + v.o_key;
                                }
                            }

                        }

                        return v;
                    }},"configured", "all", "config", "description"],
                groupField: 'configured',
                filters: [
                    function(item) {
                        if (in_array(item.get("name"), this.disallowedKeys)) {
                            return false;
                        }
                        return true;
                    }.bind(this)
                ]
            });

            this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners: {
                    beforeedit: function(editor, context, eOpts) {
                        //need to clear cached editors of cell-editing editor in order to
                        //enable different editors per row
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

                        if (context.record.get("configured") && context.field !== "data") {
                            return false;
                        }
                    }
                }
            });

            this.propertyGrid = Ext.create('Ext.grid.Panel', {
                autoScroll: true,
                region: "center",
                layout: 'fit',
                sm:  Ext.create('Ext.selection.RowModel', {}),
                bufferedRenderer: false,
                trackMouseOver: true,
                store: store,
                bodyCls: "pimcore_editable_grid",
                plugins: [
                    this.cellEditing
                ],
                tbar: [{
                    xtype: "tbtext",
                    text: t('add_a_custom_property') + " "
                    },
                    customKey,
                    customType, {
                        xtype: "button",
                        handler: this.addSetFromUserDefined.bind(this, customKey, customType),
                        iconCls: "pimcore_icon_add"
                    }],
                clicksToEdit: 1,
                features: [
                    Ext.create('Ext.grid.feature.Grouping', {
                        listeners: {
                            rowupdated: this.updateRows.bind(this, "rowupdated"),
                            refresh: this.updateRows.bind(this, "refresh")
                        }
                    })
                ],
                autoExpandColumn: "property_value_col",
                columnLines: true,
                stripeRows: true,
                columns: [
                    {
                        text: t("type"),
                        dataIndex: 'type',
                        editable: false,
                        width: 40,
                        renderer: this.getTypeRenderer.bind(this),
                        sortable: true
                    },
                    {
                        text: t("name"),
                        dataIndex: 'name',
                        tdCls: 'nameTdCls',
                        getEditor: function() {
                            return new Ext.form.TextField({
                                allowBlank: false
                            });
                        },
                        sortable: true,
                        width: 230
                    },
                    {
                        text: t("value"),
                        dataIndex: 'data',
                        flex: 1,
                        getEditor: this.getCellEditor.bind(this),
                        editable: true,
                        renderer: this.getCellRenderer.bind(this),
                        listeners: {
                            "mousedown": this.cellMousedown.bind(this)
                        }
                    },
                    {
                        xtype: 'actioncolumn',
                        menuText: t('open'),
                        width: 40,
                        items: [{
                            tooltip: t('open'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg",
                            handler: function (grid, rowIndex) {
                                var pData = grid.getStore().getAt(rowIndex).data;
                                if (pData.all && pData.all.data) {
                                    if (pData.all.data.id) {
                                        pimcore.helpers.openElement(pData.all.data.id, pData.type, pData.all.data.type);
                                    }
                                }
                            }.bind(this),
                            getClass: function(v, meta, rec) {  // Or return a class from a function
                                if (rec.get('type') != "object" && rec.get('type') != "document"
                                    && rec.get('type') != "asset") {
                                    return "pimcore_hidden";
                                }
                            }
                        }]
                    },
                    {
                        xtype: 'actioncolumn',
                        menuText: t('delete'),
                        width: 40,
                        items: [{
                            tooltip: t('delete'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this),
                            getClass: function(v, meta, rec) {  // Or return a class from a function
                                if (rec.get('configured')) {
                                    return "pimcore_hidden";
                                }
                            }
                        }]
                    }
                ],
                viewConfig: {
                    listeners: {
                        render: function(view) {
                            view.tip = Ext.create('Ext.tip.ToolTip', {
                                target: view.getId(),
                                delegate: view.itemSelector + ' .nameTdCls',
                                trackMouse: true,
                                listeners: {
                                    beforeshow: function updateTipBody(tip) {
                                        var tipGridView = tip.target.component;
                                        var record = tipGridView.getRecord(tip.triggerElement);
                                        var text = record.get('description') ?? record.get('name');

                                        tip.update(text);
                                    }
                                }
                            });
                        },
                        destroy: function(view) {
                            delete view.tip;
                        }
                    }
                }
            });

            this.propertyGrid.getView().on("refresh", this.updateRows.bind(this, "view-refresh"));
            this.propertyGrid.getView().on("afterrender", this.updateRows.bind(this, "view-afterrender"));
            this.propertyGrid.getView().on("viewready", this.updateRows.bind(this, "view-viewready"));

            this.propertyGrid.on("viewready", this.updateRows.bind(this));
            this.propertyGrid.on("afterrender", function() {
                this.setAutoScroll(true);
            });

            this.propertyGrid.on("rowcontextmenu", function ( grid, record, tr, rowIndex, e, eOpts ) {

                var propertyData = grid.getStore().getAt(rowIndex).data;

                var menu = new Ext.menu.Menu();

                menu.add(new Ext.menu.Item({
                    text: t('delete'),
                    iconCls: "pimcore_icon_delete",
                    handler: function (grid, index) {
                        grid.getStore().removeAt(index);
                    }.bind(this, grid, rowIndex)
                }));

                if (propertyData.type == "object" || propertyData.type == "document" || propertyData.type == "asset") {
                    if (propertyData.data) {
                        menu.add(new Ext.menu.Item({
                            text: t('open'),
                            iconCls: "pimcore_icon_open",
                            handler: function (grid, index) {
                                var pData = grid.getStore().getAt(index).data;
                                if (pData.all && pData.all.data) {
                                    if (pData.all.data.id) {
                                        pimcore.helpers.openElement(pData.all.data.id, pData.type, pData.all.data.type);
                                    }
                                }
                            }.bind(this, grid, rowIndex)
                        }));
                    }
                }

                e.stopEvent();
                menu.showAt(e.pageX, e.pageY);
            }.bind(this));
        }

        return this.propertyGrid;
    },

    add: function (key, type, value, config, inherited, inheritable, description) {

        if (in_array(key, this.disallowedKeys)) {
            return;
        }

        if (typeof description != "string") {
            description = "";
        }

        if (typeof type == "undefined") {
            type = "";
        }

        var store = this.propertyGrid.getStore();

        // check for duplicate name
        var dublicateIndex = store.findBy(function (key, record, id) {
            if (record.data.name.toLowerCase() == key.toLowerCase()) {
                return true;
            }
            return false;
        }.bind(this, key));


        if (dublicateIndex >= 0) {
            Ext.MessageBox.alert(t("error"), t("name_already_in_use"));
            return;
        }

        // check for empty key & type
        if (key.length < 2 || type.length < 1) {
            Ext.MessageBox.alert(t("error"), t("name_and_key_must_be_defined"));
            return;
        }


        if (!value) {
            if (type == "bool") {
                value = true;
            }
            if (type == "document" || type == "asset" || type == "object") {
                value = "";
            }
            if (type == "text") {
                value = "";
            }
            value = "";
        }

        var model = store.getModel();
        var newRecord = new model({
            name: key,
            data: value,
            type: type,
            config: config,
            description: description,
            configured: false,
        });


        store.add(newRecord);

        this.propertyGrid.getStore().group("configured");
        this.propertyGrid.getView().refresh();
    },

    updateRows: function (event) {
        var rows = Ext.get(this.propertyGrid.getEl().dom).query(".x-grid-row");

        for (var i = 0; i < rows.length; i++) {

            try {
                var rowElement = Ext.get(rows[i]);
                var propertyName = rowElement.query(".x-grid-cell-first div div")[0].getAttribute("name");
                var storeIndex = this.propertyGrid.getStore().findExact("name", propertyName);

                var data = this.propertyGrid.getStore().getAt(storeIndex).data;

                if (data.type == "document" || data.type == "asset" || data.type == "object") {
                    // register at global DnD manager
                    if (typeof dndManager != 'undefined') {
                        dndManager.addDropTarget(rowElement, this.onNodeOver.bind(this, data), this.onNodeDrop.bind(this, storeIndex));
                    }
                }
            }
            catch (e) {
                console.log(e);
            }
        }
    },

    onNodeOver : function(dataRow, target, dd, e, data) {
        if (data.records.length === 1 && dataRow.type == data.records[0].data.elementType) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        return Ext.dd.DropZone.prototype.dropNotAllowed;

    },

    onNodeDrop : function(myRowIndex, target, dd, e, data) {

        if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
        }

        try {
            data = data.records[0].data;
            var rec = this.propertyGrid.getStore().getAt(myRowIndex);

            if (empty(rec) || data.elementType !== rec.get("type")) {
                return false;
            }


            rec.set("data", data.path);
            rec.set("all",{
                data: {
                    id: data.id,
                    type: data.type
                }
            });

            this.updateRows();

            return true;
        } catch (e) {
            console.log(e);
        }
    },

    getCellEditor: function (record, defaultField ) {
        var data = record.data;
        var type = data.type;
        var property;

        if (type == "text") {
            property = new Ext.form.TextField();
        }
        else if (type == "document" || type == "asset" || type == "object") {
            //no editor needed here
        }
        else if (type == "bool") {
            //no editor needed here
        }
        else if (type == "select") {
            var options = [];
            for (optKey in data.config) {
                options.push([optKey, data.config[optKey]]);
            }

            property = new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                mode: 'local',
                autoSelect: true,
                store: new Ext.data.ArrayStore({
                    fields: ["key", "value"],
                    data: options
                }),
                valueField: "value",
                displayField: "key"
            });
        }

        return property;
    },

    emptyGrid: function () {
        try {
            //remove only non-configured properties
            this.propertyGrid.getStore().filter('configured', false);
            this.propertyGrid.getStore().removeAll();
            this.propertyGrid.getStore().clearFilter();
        } catch (e) {
            console.log(e);
        }
    },

    getValues : function () {

        if (!this.propertyGrid.rendered) {
            throw "properties not available";
        }

        var values = {};
        var store = this.propertyGrid.getStore();
        store.commitChanges();

        var records = store.getRange();

        for (var i = 0; i < records.length; i++) {
            var currentData = records[i];
            if (currentData) {
                values[currentData.data.name] = {
                    data: currentData.data.data,
                    type: currentData.data.type,
                    configured: currentData.data.configured,
                    config: currentData.data.config,
                };

                if (currentData.data.all) {
                    values[currentData.data.name]['all'] = currentData.data.all;
                }
            }
        }


        return values;
    },

    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
        var data = store.getAt(rowIndex).data;
        var type = data.type;

        if (!value) {
            value = "";
        }

        if (type == "document" || type == "asset" || type == "object") {
            if (value) {
                return '<div class="pimcore_property_droptarget">' + value + '</div>';
            } else {
                return '<div class="pimcore_property_droptarget">&nbsp;</div>';
            }
        } else if (type == "bool") {
            if (value) {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn x-grid-checkcolumn-checked" style=""></div></div>';
            } else {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn" style=""></div></div>';
            }
        } else if (type == "select") {

            try {
                var options = data.config;
                var key = Object.keys(options).find(key => options[key] === value);
                if (key) {
                    return key;
                }
            }
            catch (e) {
            }

            return value;
        }

        return value;
    },

});
