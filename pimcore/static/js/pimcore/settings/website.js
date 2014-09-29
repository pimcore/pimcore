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

pimcore.registerNS("pimcore.settings.website");
pimcore.settings.website = Class.create({

    initialize:function () {

        this.getTabPanel();
    },


    activate:function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_website_settings");
    },


    getTabPanel:function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id:"pimcore_website_settings",
                title: t('website_settings'),
                iconCls: "pimcore_icon_website",
                border:false,
                layout:"fit",
                closable:true,
                items:[this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_website_settings");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("settings_website");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor:function () {

        var proxy = new Ext.data.HttpProxy({
            url:'/admin/settings/website-settings'
        });
        var reader = new Ext.data.JsonReader({
            totalProperty:'total',
            successProperty:'success',
            root:'data'
        },
            ["id", 'name','type',{name: "data", type: "string", convert: function (v, rec) {
                return v;
            }},
            {name: 'siteId', allowBlank: true},
            {name: 'creationDate', allowBlank: true},
            {name: 'modificationDate', allowBlank: true}

            ]
        );
        var writer = new Ext.data.JsonWriter();


        var itemsPerPage = 20;

        this.store = new Ext.data.Store({
            id:'settings_website_store',
            restful:false,
            proxy:proxy,
            reader:reader,
            writer:writer,
            remoteSort:true,
            baseParams:{
                limit:itemsPerPage,
                filter:""
            },
            listeners:{
                write:function (store, action, result, response, rs) {
                }
            }
        });
        this.store.load();


        this.filterField = new Ext.form.TextField({
            xtype:"textfield",
            width:200,
            style:"margin: 0 10px 0 0;",
            enableKeyEvents:true,
            listeners:{
                "keydown":function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        this.store.baseParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize:itemsPerPage,
            store:this.store,
            displayInfo:true,
            displayMsg:'{0} - {1} / {2}',
            emptyMsg:t("no_objects_found")
        });

        // add per-page selection
        this.pagingtoolbar.add("-");

        this.pagingtoolbar.add(new Ext.Toolbar.TextItem({
            text:t("items_per_page")
        }));
        this.pagingtoolbar.add(new Ext.form.ComboBox({
            store:[
                [10, "10"],
                [20, "20"],
                [40, "40"],
                [60, "60"],
                [80, "80"],
                [100, "100"]
            ],
            mode:"local",
            width:50,
            value:20,
            triggerAction:"all",
            listeners:{
                select:function (box, rec, index) {
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));

        var typesColumns = [
            {
                header: t("type"),
                dataIndex: 'type',
                editable: false,
                width: 40,
                renderer: this.getTypeRenderer.bind(this),
                sortable: true
            },
            {
                header: t("name"),
                dataIndex: 'name',
                editor: new Ext.form.TextField({
                    allowBlank: false
                }),
                sortable: true
            },
            {
                id: "property_value_col",
                header: t("value"),
                dataIndex: 'data',
                getCellEditor: this.getCellEditor.bind(this),
                editable: true,
                renderer: this.getCellRenderer.bind(this),
                listeners: {
                    "mousedown": this.cellMousedown.bind(this)
                }
            },
            {header: t("site"), width: 200, sortable:true, dataIndex: "siteId",
                editor: new Ext.form.ComboBox({
                        store: pimcore.globalmanager.get("sites"),
                        valueField: "id",
                        displayField: "domain",
                        triggerAction: "all"
                }),
                renderer: function (siteId) {
                    var store = pimcore.globalmanager.get("sites");
                    var pos = store.findExact("id", siteId);
                    if(pos >= 0) {
                        var val = store.getAt(pos).get("domain");
                        return val;
                    }
                }
            }
            ,
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            }
            ,
            {header: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            }
            ,
            {
                xtype:'actioncolumn',
                width:30,
                items:[
                    {
                        tooltip:t('empty'),
                        icon: "/pimcore/static/img/icon/bin_empty.png",
                        handler:function (grid, rowIndex) {
                            grid.getStore().getAt(rowIndex).set("data","");
                        }.bind(this)
                    }
                ]
            }
            ,
            {
                xtype:'actioncolumn',
                width:30,
                items:[
                    {
                        tooltip:t('delete'),
                        icon:"/pimcore/static/img/icon/cross.png",
                        handler:function (grid, rowIndex) {
                            grid.getStore().removeAt(rowIndex);
                        }.bind(this)
                    }
                ]
            }
        ];



        var propertyTypes = new Ext.data.SimpleStore({
            fields: ['id', 'name'],
            data: [
                ["text", "Text"],
                ["document", "Document"],
                ["asset", "Asset"],
                ["object", "Object"],
                ["bool", "Checkbox"]
            ]
        });

        this.customKeyField = new Ext.form.TextField({
            name: 'key',
            emptyText: t('key')
        });

        var customType = new Ext.form.ComboBox({
            fieldLabel: t('type'),
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

        this.grid = new Ext.grid.EditorGridPanel({
            frame:false,
            autoScroll:true,
            store:this.store,
            columnLines:true,
            trackMouseOver:true,
            stripeRows:true,
            columns:typesColumns,
            clicksToEdit: 1,
            sm:new Ext.grid.RowSelectionModel({singleSelect:true}),
            bbar:this.pagingtoolbar,
            tbar:[
                {
                    xtype: "tbtext",
                    text: t('add_setting') + " "
                },
                this.customKeyField, customType,
                {
                    xtype: "button",
                    handler: this.addSetFromUserDefined.bind(this, this.customKeyField, customType),
                    iconCls: "pimcore_icon_add"
                },
                '->',
                {
                    text:t("filter") + "/" + t("search"),
                    xtype:"tbtext",
                    style:"margin: 0 10px 0 0;"
                },
                this.filterField
            ]
            ,
            viewConfig: {
                listeners: {
                    rowupdated: this.updateRows.bind(this, "rowupdated"),
                    refresh: this.updateRows.bind(this, "refresh")
                },
                forceFit:true
            }
        });

        this.store.on("update", this.updateRows.bind(this));
        this.grid.on("viewready", this.updateRows.bind(this));
        this.grid.on("afterrender", function() {
            this.setAutoScroll(true);
        });

        return this.grid;
    },

    getTypeRenderer: function (value, metaData, record, rowIndex, colIndex, store) {

        return '<div style="background: url(/pimcore/static/img/icon/' + value + '.png) center center no-repeat; '
            + 'height: 16px;" data-id="' + record.get("id") + '">&nbsp;</div>';
    },

    getCellEditor: function (rowIndex) {

        var store = this.grid.getStore();
        var data = store.getAt(rowIndex).data;
        var value = data.all;

        var type = data.type;
        var property;

        if (type == "text") {
            property = new Ext.form.TextField();
        }
        else if (type == "document" || type == "asset" || type == "object") {

            property = new Ext.form.TextField({
                disabled: true,
                grid: this.grid,
                myRowIndex: rowIndex,
                style: {
                    visibility: "hidden"
                }
            });
        }

        else if (type == "bool") {
            property = new Ext.form.Checkbox();
            return;
        }

        else if (type == "select") {
            var config = data.config;
            property = new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: config.split(",")
            });
        }

        return new Ext.grid.GridEditor(property);
    },

    updateRows: function (event) {
        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid3-row");

        for (var i = 0; i < rows.length; i++) {
            try {
                var propertyName = Ext.get(rows[i]).query(".x-grid3-cell-first div div")[0].getAttribute("data-id");
                var storeIndex = this.grid.getStore().find("id", propertyName);

                var data = this.grid.getStore().getAt(storeIndex).data;

                if (data.type == "document" || data.type == "asset" || data.type == "object") {

                    // add dnd support
                    var dd = new Ext.dd.DropZone(rows[i], {
                        ddGroup: "element",

                        getTargetFromEvent: function(e) {
                            return this.getEl();
                        },

                        onNodeOver : function(elementType, target, dd, e, data) {
                            if (data.node.attributes.elementType != elementType) {
                                return false;
                            }

                            return Ext.dd.DropZone.prototype.dropAllowed;
                        }.bind(this, data.type),

                        onNodeDrop : function(myRowIndex, target, dd, e, data) {
                            var rec = this.grid.getStore().getAt(myRowIndex);
                            rec.set("data", data.node.attributes.path);

                            this.updateRows();

                            return true;
                        }.bind(this, storeIndex)
                    });
                }
            }
            catch (e) {
                console.log(e);
            }
        }
    },

    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {

        var data = store.getAt(rowIndex).data;
        var type = data.type;

        if (type == "document" || type == "asset" || type == "object") {
            return '<div class="pimcore_property_droptarget">' + value + '</div>';
        } else if (type == "bool") {
            metaData.css += ' x-grid3-check-col-td';
            return String.format(
                '<div class="x-grid3-check-col{0}" style="background-position:10px center;">&#160;</div>',
                value ? '-on' : '');
        }

        return value;
    },

    cellMousedown: function (col, grid, rowIndex, event) {

        // this is used for the boolean field type

        var store = grid.getStore();
        var record = store.getAt(rowIndex);
        var data = record.data;
        var type = data.type;

        if (type == "bool") {
            record.set("data", !record.data.data);
        }
    },

    addSetFromUserDefined: function (customKey, customType) {
        if(in_array(customKey.getValue(), this.disallowedKeys)) {
            Ext.MessageBox.alert(t("error"), t("name_is_not_allowed"));
        }
        this.add(customKey.getValue(), customType.getValue(), false, false, false, true);
        this.customKeyField.setValue(null);
    },


    add: function (key, type, value, config, inherited, inheritable) {

        var store = this.grid.getStore();

        // check for duplicate name
        var dublicateIndex = store.findBy(function (key, record, id) {
            if (record.get("name").toLowerCase() == key.toLowerCase()) {
                return true;
            }
            return false;
        }.bind(this, key));


        if (dublicateIndex >= 0) {
            if (store.getAt(dublicateIndex).data.inherited == false) {
                Ext.MessageBox.alert(t("error"), t("name_already_in_use"));
                return;
            }
        }

        // check for empty key & type
        if (key.length < 2 && type.length < 1) {
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

        if (typeof inheritable != "boolean") {
            inheritable = true;
        }

        var newRecord = new store.recordType({
            name: key,
            data: value,
            type: type
        });

        store.add(newRecord);
        this.grid.getView().refresh();
    }

});