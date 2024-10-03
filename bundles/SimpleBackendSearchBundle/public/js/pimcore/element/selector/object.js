/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS('pimcore.bundle.search.element.selector.object');

/**
 * @private
 */
pimcore.bundle.search.element.selector.object = Class.create(pimcore.bundle.search.element.selector.abstract, {
    fieldObject: {},
    gridType: 'object',

    initStore: function () {
        return 0; // dummy
    },

    getTabTitle: function() {
        return "object_search";
    },

    getForm: function () {
        let i;

        //set "Home" object ID for search grid column configuration
        this.object  = {};
        this.object.id = 1;

        this.searchType = "search";

        const compositeConfig = {
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
                    window.open("https://dev.mysql.com/doc/refman/8.0/en/fulltext-boolean.html");
                },
                iconCls: "pimcore_icon_help"
            })]
        };

        // check for restrictions
        let possibleRestrictions = pimcore.globalmanager.get('object_search_types');
        let filterStore = [];
        let selectedStore = [];
        for (i=0; i<possibleRestrictions.length; i++) {
            if(this.parent.restrictions.subtype.object && in_array(possibleRestrictions[i], this.parent.restrictions.subtype.object )) {
                filterStore.push([possibleRestrictions[i], t(possibleRestrictions[i])]);
                selectedStore.push(possibleRestrictions[i]);
            }
        }

        // add all to store if empty
        if(filterStore.length < 1) {
            for (let i=0; i<possibleRestrictions.length; i++) {
                filterStore.push([possibleRestrictions[i], t(possibleRestrictions[i])]);
                selectedStore.push(possibleRestrictions[i]);
            }
        }

        let selectedValue = selectedStore.join(",");
        if(filterStore.length > 1) {
            filterStore.splice(0,0,[selectedValue, t("all_types")]);
        }

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
            value: selectedValue,
            listeners: {
                select: function(e) {
                    if (e.value === 'folder') {
                        const defaultRecord = this.classChangeCombo.getStore().getAt(0);
                        this.classChangeCombo.setValue(defaultRecord.get(this.classChangeCombo.valueField));
                        this.classChangeCombo.fireEvent('select', this.classChangeCombo, defaultRecord);

                        this.classChangeCombo.setDisabled(true);
                    } else {
                        this.classChangeCombo.setDisabled(false);
                    }
                }.bind(this)
            }
        });


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
                filterClassStore.push([possibleClassRestrictions[i], t(possibleClassRestrictions[i])]);
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
        if(selectedValue == 'folder') {
            this.classChangeCombo.setDisabled(true);
        }

        compositeConfig.items.push(this.classChangeCombo);


        // add button
        compositeConfig.items.push({
            xtype: "button",
            iconCls: "pimcore_icon_search",
            text: t("search"),
            handler: this.search.bind(this)
        });

        this.saveColumnConfigButton = new Ext.Button({
            tooltip: t('save_grid_options'),
            iconCls: "pimcore_icon_publish",
            hidden: true,
            handler: function () {
                var asCopy = !(this.settings.gridConfigId > 0);
                this.saveConfig(asCopy)
            }.bind(this)
        });

        this.columnConfigButton = new Ext.SplitButton({
            text: t('grid_options'),
            hidden: true,
            iconCls: "pimcore_icon_table_col pimcore_icon_overlay_edit",
            handler: function () {
                this.openColumnConfig(this.selectedClass, this.classId);
            }.bind(this),
            menu: []
        });

        compositeConfig.items.push("->");

        // add grid config main button
        compositeConfig.items.push(this.columnConfigButton);

        // add grid config save button
        compositeConfig.items.push(this.saveColumnConfigButton);

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
                        return t(v);
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
                    {text: t("type"), width: 40, sortable: true, dataIndex: 'subtype'},
                    {text: t("key"), flex: 1, sortable: true, dataIndex: 'filename'}
                ],
                viewConfig: {
                    forceFit: true
                },
                listeners: {
                    rowcontextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {
                        const menu = new Ext.menu.Menu();

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
        const selectedClass = this.classChangeCombo.getValue();

        if(selectedClass.indexOf(",") > 0) { // multiple classes because of a comma in the string
            //hide column config buttons
            this.columnConfigButton.hide();
            this.saveColumnConfigButton.hide();

            // init default store
            this.initDefaultStore();
        } else {
            const classStore = pimcore.globalmanager.get("object_types_store");
            const classIdx = classStore.findExact("text", selectedClass);
            this.selectedClass = selectedClass
            this.classId = classStore.getAt(classIdx).id;
            this.settings = {};

            // get class definition
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_dataobjecthelper_gridgetcolumnconfig'),
                params: {
                    id: this.classId,
                    objectId: this.object.id,
                    gridtype: "search",
                    gridConfigId: this.settings ? this.settings.gridConfigId : null,
                    searchType: "search"
                },
                success: this.initClassStore.bind(this, selectedClass)
            });
        }
    },

    initClassStore: function (selectedClass, response, save) {
        let fields = [];
        if(response.responseText) {
            response = Ext.decode(response.responseText);
            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.sortinfo = response.sortinfo;
            this.settings = response.settings;
            this.availableConfigs = response.availableConfigs;
            this.sharedConfigs = response.sharedConfigs;
        } else {
            fields = response;
        }

        this.itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);
        const gridHelper = new pimcore.object.helpers.grid(selectedClass, fields, Routing.generate('pimcore_bundle_search_search_find'), null, true);
        gridHelper.limit = this.itemsPerPage;
        this.store = gridHelper.getStore();
        this.store.setPageSize(this.itemsPerPage);
        this.applyExtraParamsToStore();
        var gridColumns = gridHelper.getGridColumns();
        var gridfilters = gridHelper.getGridFilters();

        this.fieldObject = {};
        for(var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

        //TODO set up filter

        this.getGridPanel(gridColumns, gridfilters, selectedClass, save);

        this.buildColumnConfigMenu();
        this.columnConfigButton.show();
    },

    initDefaultStore: function () {
        this.itemsPerPage =  pimcore.helpers.grid.getDefaultPageSize(-1);
        this.store = new Ext.data.Store({
            autoDestroy: true,
            remoteSort: true,
            pageSize: this.itemsPerPage,
            proxy : {
                type: 'ajax',
                url: Routing.generate('pimcore_bundle_search_search_find'),
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                },
                extraParams: {
                    type: 'object'
                }
            },
            fields: ["id","fullpath","type","subtype","filename",{name:"classname",convert: function(v, rec){
                    return t(rec.data.classname);
                }},"published"]
        });

        var columns = [
            {text: t("type"), width: 40, sortable: true, dataIndex: 'subtype',
                renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                    return '<div style="height: 16px;" class="pimcore_icon_' + value + '" name="'
                        + t(record.data.subtype) + '">&nbsp;</div>';
                }
            },
            {text: 'ID', width: 40, sortable: true, dataIndex: 'id', hidden: true},
            {text: t("published"), width: 40, sortable: true, dataIndex: 'published', hidden: true},
            {text: t("path"), flex: 200, sortable: true, dataIndex: 'fullpath', renderer: Ext.util.Format.htmlEncode},
            {text: t("key"), width: 200, sortable: true, dataIndex: 'filename', hidden: true, renderer: Ext.util.Format.htmlEncode},
            {text: t("class"), width: 200, sortable: true, dataIndex: 'classname'}
        ];


        this.getGridPanel(columns, null);
    },

    getGridPanel: function (columns, gridfilters, selectedClass, save) {
        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store,{pageSize: this.itemsPerPage});

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
                xtype: 'patchedgridview',
                listeners: {
                    refresh: function (dataview) {
                        Ext.each(dataview.panel.columns, function (column) {
                            if (column.autoSizeColumn === true) {
                                column.autoSize();
                            }
                        })
                    }
                }
            },
            cls: 'pimcore_object_grid_panel',
            selModel: this.getGridSelModel(),
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

                const classStore = pimcore.globalmanager.get("object_types_store");
                let classId = null;
                classStore.each(function (rec) {
                    if(rec.data.text == selectedClass) {
                        classId = rec.data.id;
                    }
                });

                const columnConfig = new Ext.menu.Item({
                    text: t("grid_options"),
                    iconCls: "pimcore_icon_table_col pimcore_icon_overlay_edit",
                    handler: this.openColumnConfig.bind(this, selectedClass, classId)
                });
                const menu = grid.headerCt.getMenu();
                menu.add(columnConfig);
            }
        }.bind(this));

        if(this.parent.multiselect) {
            this.gridPanel.on("rowcontextmenu", this.onRowContextmenu.bind(this));
        }

        this.resultPanel.removeAll();
        this.resultPanel.add(this.gridPanel);
        this.resultPanel.updateLayout();

        if (save == true) {
            if (this.settings.isShared) {
                this.settings.gridConfigId = null;
            }
            this.saveConfig(false);
        }
    },

    openColumnConfig: function(selectedClass, classId) {
        const fields = this.getGridConfig().columns;
        const fieldKeys = Object.keys(fields);
        const visibleColumns = [];

        for(let i = 0; i < fieldKeys.length; i++) {
            const field = fields[fieldKeys[i]];
            if(!field.hidden) {
                var fc = {
                    key: fieldKeys[i],
                    label: field.fieldConfig.label,
                    dataType: field.fieldConfig.type,
                    layout: field.fieldConfig.layout
                };
                if (field.fieldConfig.width) {
                    fc.width = field.fieldConfig.width;
                }

                if (field.isOperator) {
                    fc.isOperator = true;
                    fc.attributes = field.fieldConfig.attributes;

                }

                visibleColumns.push(fc);
            }
        }

        let objectId;
        if(this["object"] && this.object["id"]) {
            objectId = this.object.id;
        }

        const columnConfig = {
            language: this.gridLanguage,
            classid: classId,
            selectedGridColumns: visibleColumns
        };

        const dialog = new pimcore.object.helpers.gridConfigDialog(columnConfig,
            function(data, settings, save) {
                this.saveColumnConfigButton.show(); //unhide save config button
                this.gridLanguage = data.language;
                this.itemsPerPage = data.pageSize;
                this.initClassStore(selectedClass, data.columns, save);
            }.bind(this),
            function() {
                Ext.Ajax.request({
                    url: Routing.generate('pimcore_admin_dataobject_dataobjecthelper_gridgetcolumnconfig'),
                    params: {
                        id: this.classId,
                        objectId: this.object.id,
                        gridtype: "search",
                        searchType: this.searchType
                    },
                    success: function(response) {
                        if (response) {
                            this.initClassStore(selectedClass, response, false);
                            if (typeof this.saveColumnConfigButton !== "undefined") {
                                this.saveColumnConfigButton.hide();
                            }
                        } else {
                            pimcore.helpers.showNotification(t("error"), t("error_resetting_config"),
                                "error",t(rdata.message));
                        }
                    }.bind(this),
                    failure: function () {
                        pimcore.helpers.showNotification(t("error"), t("error_resetting_config"), "error");
                    }
                });
            }.bind(this), true, this.settings,
            {
                allowPreview: true,
                classId: this.classId,
                objectId: this.object.id
            }
        );
    },

    getGridConfig : function () {
        const config = {
            language: this.gridLanguage,
            sortinfo: this.sortinfo,
            columns: {}
        };

        const cm = this.gridPanel.getView().getHeaderCt().getGridColumns();

        for (let i=0; i < cm.length; i++) {
            if(cm[i].dataIndex) {
                config.columns[cm[i].dataIndex] = {
                    name: cm[i].dataIndex,
                    position: i,
                    hidden: cm[i].hidden,
                    width: cm[i].width,
                    fieldConfig: this.fieldObject[cm[i].dataIndex],
                    isOperator: this.fieldObject[cm[i].dataIndex].isOperator
                };

            }
        }

        return config;
    },

    getGrid: function () {
        return this.gridPanel;
    },

    applyExtraParamsToStore: function () {
        let formValues = this.formPanel.getForm().getFieldValues();
        let proxy = this.store.getProxy();
        let query = Ext.util.Format.htmlEncode(formValues.query);
        proxy.setExtraParam("query", query);
        proxy.setExtraParam("type", 'object');
        proxy.setExtraParam("subtype", formValues.subtype);
        proxy.setExtraParam("class", formValues.class);

        if (this.gridLanguage) {
            proxy.setExtraParam("language", this.gridLanguage);
        }

        if (this.parent.config && this.parent.config.context) {
            proxy.setExtraParam("context", Ext.encode(this.parent.config.context));
        }

        this.updateTabTitle(query);
    },

    search: function () {
        this.applyExtraParamsToStore();
        this.pagingtoolbar.moveFirst();
    },

    createGrid: function (fromConfig, response, settings, save, context) {
        const selectedClass = this.classChangeCombo.getValue();

        this.initClassStore(selectedClass,response, save);
    },

    getTableDescription: function () {
        const selectedClass = this.classChangeCombo.getValue();

        if(selectedClass.indexOf(",") > 0) { // multiple classes because of a comma in the string
            // init default store
            this.initDefaultStore();
        } else {
            // get class definition
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_dataobjecthelper_gridgetcolumnconfig'),
                params: {
                    id: this.classId,
                    objectId: this.object.id,
                    gridtype: "search",
                    gridConfigId: this.settings ? this.settings.gridConfigId : null,
                    searchType: this.searchType
                },
                success: this.initClassStore.bind(this, selectedClass)
            });
        }
    },
});

pimcore.bundle.search.element.selector.object.addMethods(pimcore.element.helpers.gridColumnConfig);
