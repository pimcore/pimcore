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

pimcore.registerNS("pimcore.settings.website");
pimcore.settings.website = Class.create({

    initialize:function () {

        this.getTabPanel();
    },


    activate:function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_website_settings");
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
            tabPanel.setActiveItem("pimcore_website_settings");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("settings_website");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor:function () {


        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        var url = '/admin/settings/website-settings?';

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            [
                "id", 'name','type',
                {name: "data"},
                {name: 'siteId', allowBlank: true},
                {name: 'creationDate', allowBlank: true},
                {name: 'modificationDate', allowBlank: true}

            ],
            itemsPerPage
        );

        this.store.addListener('exception', function (proxy, response, operation) {
                Ext.MessageBox.show({
                    title: 'REMOTE EXCEPTION',
                    msg: operation.getError(),
                    icon: Ext.MessageBox.ERROR,
                    buttons: Ext.Msg.OK
                });
            }
        );
        this.store.setAutoSync(true);

        this.filterField = new Ext.form.TextField({
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents:true,
            listeners:{
                "keydown":function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

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
                width: 200,
                editable: true,
                getEditor: this.getCellEditor.bind(this),
                sortable: true
            },
            {
                header: t("value"),
                dataIndex: 'data',
                flex: 10,
                getEditor: this.getCellEditor.bind(this),
                editable: true,
                renderer: this.getCellRenderer.bind(this),
                listeners: {
                    "mousedown": this.cellMousedown.bind(this)
                }
            },
            {header: t("site"), width: 250, sortable:true, dataIndex: "siteId",
                editor: new Ext.form.ComboBox({
                        store: pimcore.globalmanager.get("sites"),
                        valueField: "id",
                        displayField: "domain",
                        editable: false,
                        triggerAction: "all"
                }),
                renderer: function (siteId) {
                    var store = pimcore.globalmanager.get("sites");
                    var pos = store.findExact("id", siteId);
                    if(pos >= 0) {
                        var val = store.getAt(pos).get("domain");
                        return val;
                    }
                    return null;
                }
            }
            ,
            {header: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.Date.format(date, "Y-m-d H:i:s");
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
                        return Ext.Date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            }
            ,
            {
                xtype:'actioncolumn',
                width:40,
                tooltip:t('empty'),
                icon: "/pimcore/static6/img/flat-color-icons/full_trash.svg",
                handler:function (grid, rowIndex) {
                    grid.getStore().getAt(rowIndex).set("data","");
                }.bind(this)

            }
            ,
            {
                xtype:'actioncolumn',
                width:40,
                tooltip:t('delete'),
                icon:"/pimcore/static6/img/flat-color-icons/delete.svg",
                handler:function (grid, rowIndex) {
                    grid.getStore().removeAt(rowIndex);
                }.bind(this)
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
            hideLabel: true,
            emptyText: t('type')
        });

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function(editor, context, eOpts) {
                    //need to clear cached editors of cell-editing editor in order to
                    //enable different editors per row
                    editor.editors.each(function() {
                        Ext.destroy, Ext
                    });
                    editor.editors.clear();
                }
            }
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame:false,
            autoScroll:true,
            store:this.store,
            columnLines:true,
            trackMouseOver:true,
            bodyCls: "pimcore_editable_grid",
            stripeRows:true,
            columns : {
                items: typesColumns
            },
            sm:  Ext.create('Ext.selection.RowModel', {}),
            bbar:this.pagingtoolbar,
            plugins: [
                this.cellEditing
            ],
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
                forceFit:true,
                xtype: 'patchedgridview'
            }
        });

        this.store.on("update", this.updateRows.bind(this));
        this.grid.on("viewready", this.updateRows.bind(this));
        this.grid.on("afterrender", function() {
            this.setAutoScroll(true);
        });

        this.store.load();

        return this.grid;
    },

    getTypeRenderer: function (value, metaData, record, rowIndex, colIndex, store) {

        return '<div class="pimcore_icon_' + value + '" data-id="' + record.get("id") + '">&nbsp;</div>';
    },

    getCellEditor: function (record) {
        var data = record.data;

        var type = data.type;
        var property;

        if (type == "text") {
            property = Ext.create('Ext.form.TextField');
        } else if (type == "textarea") {
            property = Ext.create('Ext.form.TextArea');
        } else if (type == "document" || type == "asset" || type == "object") {
            //no editor needed here
        } else if (type == "date") {
            property = Ext.create('Ext.form.field.Date', {
                format: "Y-m-d"
            });
        } else if (type == "checkbox") {
            //no editor needed here
        } else if (type == "select") {
            var config = data.config;
            property =  Ext.create('Ext.form.ComboBox', {
                triggerAction: 'all',
                editable: false,
                store: config.split(",")
            });
        }

        return property;
    },

    updateRows: function (event) {
        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid-row");

        for (var i = 0; i < rows.length; i++) {
            try {
                var propertyName = Ext.get(rows[i]).query(".x-grid-cell-first div div")[0].getAttribute("data-id");
                var storeIndex = this.grid.getStore().find("id", propertyName);

                var record = this.grid.getStore().getAt(storeIndex);
                var data = record.data;

                if (data.type == "document" || data.type == "asset" || data.type == "object") {

                    // add dnd support
                    var dd = new Ext.dd.DropZone(rows[i], {
                        ddGroup: "element",

                        getTargetFromEvent: function(e) {
                            return this.getEl();
                        },

                        onNodeOver : function(elementType, node, dragZone, e, data ) {

                            var record = data.records[0];
                            var data = record.data;

                            if (data.elementType == elementType) {
                                return Ext.dd.DropZone.prototype.dropAllowed;
                            }

                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }.bind(this, data.type),

                        onNodeDrop : function(storeIndex, targetNode, dragZone, e, data) {
                            try {
                                var record = data.records[0];
                                var data = record.data;
                                var rec = this.grid.getStore().getAt(storeIndex);
                                rec.set("data", data.path);

                                this.updateRows();

                                return true;
                            } catch (e) {
                                console.log(e);
                            }
                            return false;
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

        var data = record.data;
        var type = data.type;

        if (!value) {
            value = "";
        }

        if (type == "document" || type == "asset" || type == "object") {
            return '<div class="pimcore_property_droptarget">' + value + '</div>';
        } else if (type == "bool") {
            if (value) {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn x-grid-checkcolumn-checked" style=""></div></div>';
            } else {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn" style=""></div></div>';
            }
        }

        return value;
    },

    cellMousedown: function (grid, cell, rowIndex, cellIndex, e) {

        // this is used for the boolean field type

        var store = this.store;
        var record = store.getAt(rowIndex);
        var data = record.data;
        var type = data.type;

        if (type == "bool") {

            this.cellEditing.editors.each(Ext.destroy, Ext);
            this.cellEditing.editors.clear();

            record.set("data", !data.data);
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

        this.cellEditing.editors.each(Ext.destroy, Ext);
        this.cellEditing.editors.clear();

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
        if (key.length < 2 || !type || type.length < 1) {
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

        store.add({
            name: key,
            data: value,
            type: type
        });
    }

});
