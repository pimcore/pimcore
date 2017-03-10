/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.element.selector.object");
pimcore.element.selector.object = Class.create(pimcore.element.selector.abstract, {

    fieldObject: {},
    initStore: function () {
        return 0; // dummy
    },

    getTabTitle: function() {
        return "object_search";
    },

    getForm: function () {
        var i;

        var compositeConfig = {
            xtype: "toolbar",
            items: [{
                xtype: "textfield",
                name: "query",
                width: 340,
                hideLabel: true,
                enableKeyEvents: true,
                listeners: {
                    "keydown" : function (field, key) {
                        if (key.getKey() == key.ENTER) {
                            this.search();
                        }
                    }.bind(this),
                    afterrender: function () {
                        this.focus(true,500);
                    }
                }
            }, new Ext.Button({
                handler: function () {
                    window.open("http://dev.mysql.com/doc/refman/5.6/en/fulltext-boolean.html");
                },
                iconCls: "pimcore_icon_help"
            })]
        };

        // check for restrictions
        var possibleRestrictions = ["folder", "object", "variant"];
        var filterStore = [];
        var selectedStore = [];
        for (i=0; i<possibleRestrictions.length; i++) {
            if(this.parent.restrictions.subtype.object && in_array(possibleRestrictions[i],
                    this.parent.restrictions.subtype.object )) {
                filterStore.push([possibleRestrictions[i], t(possibleRestrictions[i])]);
                selectedStore.push(possibleRestrictions[i]);
            }
        }

        // add all to store if empty
        if(filterStore.length < 1) {
            for (var i=0; i<possibleRestrictions.length; i++) {
                filterStore.push([possibleRestrictions[i], t(possibleRestrictions[i])]);
                selectedStore.push(possibleRestrictions[i]);
            }
        }

        var selectedValue = selectedStore.join(",");
        if(filterStore.length > 1) {
            filterStore.splice(0,0,[selectedValue, t("all_types")]);
        }

        if(!this.parent.initialRestrictions.specific || (!this.parent.initialRestrictions.specific.classes
            || this.parent.initialRestrictions.specific.classes.length < 1)) {
            // only add the subtype filter if there is no class restriction
            compositeConfig.items.push({
                xtype: "combo",
                store: filterStore,
                queryMode: "local",
                name: "subtype",
                triggerAction: "all",
                editable: true,
                typeAhead:true,
                forceSelection: true,
                selectOnFocus: true,
                value: selectedValue
            });
        }


        // classes
        var possibleClassRestrictions = [];
        var classStore = pimcore.globalmanager.get("object_types_store");
        classStore.each(function (rec) {
            possibleClassRestrictions.push(rec.data.text);
        });

        var filterClassStore = [];
        var selectedClassStore = [];
        for (i=0; i<possibleClassRestrictions.length; i++) {
            if(in_array(possibleClassRestrictions[i], this.parent.restrictions.specific.classes )) {
                filterClassStore.push([possibleClassRestrictions[i], ts(possibleClassRestrictions[i])]);
                selectedClassStore.push(possibleClassRestrictions[i]);
            }
        }

        // add all to store if empty
        if(filterClassStore.length < 1) {
            for (i=0; i<possibleClassRestrictions.length; i++) {
                filterClassStore.push([possibleClassRestrictions[i], possibleClassRestrictions[i]]);
                selectedClassStore.push(possibleClassRestrictions[i]);
            }
        }

        var selectedClassValue = selectedClassStore.join(",");
        if(filterClassStore.length > 1) {
            filterClassStore.splice(0,0,[selectedClassValue, t("all_types")]);
        }

        this.classChangeCombo = new Ext.form.ComboBox({
            store: filterClassStore,
            queryMode: "local",
            name: "class",
            triggerAction: "all",
            editable: true,
            typeAhead: true,
            forceSelection: true,
            selectOnFocus: true,
            value: selectedClassValue,
            listeners: {
                select: this.changeClass.bind(this)
            }
        });

        compositeConfig.items.push(this.classChangeCombo);


        // add button
        compositeConfig.items.push({
            xtype: "button",
            iconCls: "pimcore_icon_search",
            text: t("search"),
            handler: this.search.bind(this)
        });

        if(!this.formPanel) {
            this.formPanel = new Ext.form.FormPanel({
                region: "north",
                bodyStyle: "padding: 2px;",
                items: [compositeConfig]
            });
        }

        return this.formPanel;
    },

    getSelectionPanel: function () {
        if(!this.selectionPanel) {

            this.selectionStore = new Ext.data.JsonStore({
                data: [],
                fields: ["id", "type", "filename", "fullpath", "subtype", {name:"classname",renderer: function(v){
                    return ts(v);
                }}]
            });

            this.selectionPanel = new Ext.grid.GridPanel({
                region: "east",
                title: t("your_selection"),
                tbar: [{
                    xtype: "tbtext",
                    text: t("double_click_to_add_item_to_selection"),
                    autoHeight: true,
                    style: {
                        whiteSpace: "normal"
                    }
                }],
                tbarCfg: {
                    autoHeight: true
                },
                width: 300,
                store: this.selectionStore,
                columns: [
                    {header: t("type"), width: 40, sortable: true, dataIndex: 'subtype'},
                    {header: t("filename"), flex: 1, sortable: true, dataIndex: 'filename'}
                ],
                viewConfig: {
                    forceFit: true
                },
                listeners: {
                    rowcontextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {
                        var menu = new Ext.menu.Menu();
                        var data = grid.getStore().getAt(rowIndex);

                        menu.add(new Ext.menu.Item({
                            text: t('remove'),
                            iconCls: "pimcore_icon_delete",
                            handler: function (index, item) {
                                this.selectionStore.removeAt(index);
                                item.parentMenu.destroy();
                            }.bind(this, rowIndex)
                        }));

                        e.stopEvent();
                        menu.showAt(e.getXY());
                    }.bind(this)
                },
                selModel: Ext.create('Ext.selection.RowModel', {}),
                bbar: ["->", {
                    text: t("select"),
                    iconCls: "pimcore_icon_apply",
                    handler: function () {
                        this.parent.commitData(this.getData());
                    }.bind(this)
                }]
            });
        }

        return this.selectionPanel;
    },

    getResultPanel: function () {
        if (!this.resultPanel) {
            this.resultPanel = new Ext.Panel({
                region: "center",
                layout: "fit"
            });

            this.resultPanel.on("afterrender", this.changeClass.bind(this));
        }

        return this.resultPanel;
    },


    changeClass: function () {

        var selectedClass = this.classChangeCombo.getValue();

        if(selectedClass.indexOf(",") > 0) { // multiple classes because of a comma in the string
            // init default store
            this.initDefaultStore();
        } else {
            // get class definition
            Ext.Ajax.request({
                url: "/admin/object-helper/grid-get-column-config",
                params: {name: selectedClass},
                success: this.initClassStore.bind(this, selectedClass)
            });
        }
    },

    initClassStore: function (selectedClass, response) {
        var fields = [];
        if(response.responseText) {
            response = Ext.decode(response.responseText);
            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.sortinfo = response.sortinfo;
        } else {
            fields = response;
        }

        var gridHelper = new pimcore.object.helpers.grid(selectedClass, fields, "/admin/search/search/find", null, true);
        this.store = gridHelper.getStore();
        this.store.setPageSize(pimcore.helpers.grid.getDefaultPageSize());
        this.applyExtraParamsToStore();
        var gridColumns = gridHelper.getGridColumns();
        var gridfilters = gridHelper.getGridFilters();

        this.fieldObject = {};
        for(var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

        //TODO set up filter

        this.getGridPanel(gridColumns, gridfilters, selectedClass);
    },

    initDefaultStore: function () {
        this.store = new Ext.data.Store({
            autoDestroy: true,
            remoteSort: true,
            pageSize: pimcore.helpers.grid.getDefaultPageSize(),
            proxy : {
                type: 'ajax',
                url: "/admin/search/search/find",
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            },
            fields: ["id","fullpath","type","subtype","filename",{name:"classname",convert: function(v, rec){
                return ts(rec.data.classname);
            }},"published"]
        });

        var columns = [
            {header: t("type"), width: 40, sortable: true, dataIndex: 'subtype',
                renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                    return '<div style="height: 16px;" class="pimcore_icon_asset  pimcore_icon_' + value + '" name="'
                        + t(record.data.subtype) + '">&nbsp;</div>';
                }
            },
            {header: 'ID', width: 40, sortable: true, dataIndex: 'id', hidden: true},
            {header: t("published"), width: 40, sortable: true, dataIndex: 'published', hidden: true},
            {header: t("path"), flex: 200, sortable: true, dataIndex: 'fullpath'},
            {header: t("filename"), width: 200, sortable: true, dataIndex: 'filename', hidden: true},
            {header: t("class"), width: 200, sortable: true, dataIndex: 'classname'}
        ];


        this.getGridPanel(columns, null);
    },

    getGridPanel: function (columns, gridfilters, selectedClass) {

        this.pagingtoolbar = this.getPagingToolbar(t("no_objects_found"));
        this.gridPanel = Ext.create('Ext.grid.Panel', {
            store: this.store,
            border: false,
            columns: columns,
            loadMask: true,
            columnLines: true,
            stripeRows: true,
            plugins: ['pimcore.gridfilters'],
            viewConfig: {
                forceFit: false,
                xtype: 'patchedgridview'
            },
            cls: 'pimcore_object_grid_panel',
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.pagingtoolbar,
            listeners: {
                rowdblclick: function (grid, record, tr, rowIndex, e, eOpts ) {

                    var data = grid.getStore().getAt(rowIndex);

                    if(this.parent.multiselect) {
                        this.addToSelection(data.data);
                    } else {
                        // select and close
                        this.parent.commitData(this.getData());
                    }
                }.bind(this)
            }
        });

        this.gridPanel.on("afterrender", function (grid) {
            if(selectedClass) {

                var classStore = pimcore.globalmanager.get("object_types_store");
                var classId = null;
                classStore.each(function (rec) {
                    if(rec.data.text == selectedClass) {
                        classId = rec.data.id;
                    }
                });

                var columnConfig = new Ext.menu.Item({
                    text: t("grid_column_config"),
                    iconCls: "pimcore_icon_table_col pimcore_icon_overlay_edit",
                    handler: this.openColumnConfig.bind(this, selectedClass, classId)
                });
                var menu = grid.headerCt.getMenu();
                menu.add(columnConfig);
            }
        }.bind(this));

        if(this.parent.multiselect) {
            this.gridPanel.on("rowcontextmenu", this.onRowContextmenu.bind(this));
        }

        this.resultPanel.removeAll();
        this.resultPanel.add(this.gridPanel);
        this.resultPanel.updateLayout();
    },

    openColumnConfig: function(selectedClass, classId) {
        var fields = this.getGridConfig().columns;

        var fieldKeys = Object.keys(fields);

        var visibleColumns = [];
        for(var i = 0; i < fieldKeys.length; i++) {
            if(!fields[fieldKeys[i]].hidden) {
                visibleColumns.push({
                    key: fieldKeys[i],
                    label: fields[fieldKeys[i]].fieldConfig.label,
                    dataType: fields[fieldKeys[i]].fieldConfig.type,
                    layout: fields[fieldKeys[i]].fieldConfig.layout
                });
            }
        }

        var columnConfig = {
            language: this.gridLanguage,
            classid: classId,
            selectedGridColumns: visibleColumns
        };
        var dialog = new pimcore.object.helpers.gridConfigDialog(columnConfig,
            function(data) {
                this.gridLanguage = data.language;
                this.initClassStore(selectedClass, data.columns);
            }.bind(this), null
        );
    },

    getGridConfig : function () {
        var config = {
            language: this.gridLanguage,
            sortinfo: this.sortinfo,
            columns: {}
        };

        var cm = this.gridPanel.getView().getHeaderCt().getGridColumns();

        for (var i=0; i < cm.length; i++) {
            if(cm[i].dataIndex) {
                config.columns[cm[i].dataIndex] = {
                    name: cm[i].dataIndex,
                    position: i,
                    hidden: cm[i].hidden,
                    fieldConfig: this.fieldObject[cm[i].dataIndex]
                };

            }
        }

        return config;
    },

    getGrid: function () {
        return this.gridPanel;
    },

    applyExtraParamsToStore: function () {
        var formValues = this.formPanel.getForm().getFieldValues();

        var proxy = this.store.getProxy();

        proxy.setExtraParam("type", "object");
        proxy.setExtraParam("query", formValues.query);
        proxy.setExtraParam("subtype", formValues.subtype);
        proxy.setExtraParam("class", formValues.class);

        if (this.parent.config && this.parent.config.context) {
            proxy.setExtraParam("context", Ext.encode(this.parent.config.context));
        }
    },

    search: function () {
        this.applyExtraParamsToStore();
        this.pagingtoolbar.moveFirst();
    }
});
