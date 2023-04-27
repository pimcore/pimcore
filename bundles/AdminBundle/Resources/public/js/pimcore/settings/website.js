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
                iconCls: "pimcore_icon_website_settings",
                border:false,
                layout:"fit",
                closable:true,
                items:[this.getRowEditor()],
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
        var url = Routing.generate('pimcore_admin_settings_websitesettings');

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url, ['id', 'name', 'type', 'language', 'data', 'siteId', 'creationDate', 'modificationDate'],
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

        this.languagestore = [["",t("none")]];
        let websiteLanguages = pimcore.settings.websiteLanguages;
        let selectContent = "";
        for (let i=0; i<websiteLanguages.length; i++) {
            selectContent = pimcore.available_languages[websiteLanguages[i]] + " [" + websiteLanguages[i] + "]";
            this.languagestore.push([websiteLanguages[i], selectContent]);
        }

        var typesColumns = [
            {
                text: t("type"),
                dataIndex: 'type',
                editable: false,
                flex: 20,
                renderer: this.getTypeRenderer.bind(this),
                sortable: true
            },
            {
                text: t("name"),
                dataIndex: 'name',
                flex: 100,
                editable: true,
                sortable: true,
                editor: new Ext.form.TextField({
                    listeners: {
                        'change': pimcore.helpers.htmlEncodeTextField
                    }
                })
            },
            {
                text: t('language'),
                sortable: true,
                dataIndex: "language",
                editor: new Ext.form.ComboBox({
                    store: this.languagestore,
                    mode: "local",
                    editable: false,
                    triggerAction: "all"
                }),
                flex: 50
            },
            {
                text: t("value"),
                dataIndex: 'data',
                flex: 300,
                editable: true,
                editor: new Ext.form.TextField({
                    listeners: {
                        'change': pimcore.helpers.htmlEncodeTextField
                    }
                }),
                renderer: this.getCellRenderer.bind(this),
            },
            {text: t("site"), flex: 100, sortable:true, dataIndex: "siteId",
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
                    if (pos >= 0) {
                        return store.getAt(pos).get("domain");
                    }
                    return null;
                }
            }
            ,
            {text: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
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
            {text: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
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
                menuText:t('empty'),
                width:40,
                tooltip:t('empty'),
                icon: "/bundles/pimcoreadmin/img/flat-color-icons/full_trash.svg",
                handler:function (grid, rowIndex) {
                    grid.getStore().getAt(rowIndex).set("data","");
                }.bind(this)

            }
            ,
            {
                xtype:'actioncolumn',
                menuText: t('delete'),
                width:40,
                tooltip:t('delete'),
                icon:"/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                handler:function (grid, rowIndex) {
                    let data = grid.getStore().getAt(rowIndex);
                    pimcore.helpers.deleteConfirm(t('website_settings'), data.data.name, function () {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this));
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

        this.rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToEdit: 1,
            clicksToMoveEditor: 1,
            listeners: {
                beforeedit: function(el, e) {
                    let cm = this.grid.getColumnManager().getColumns();
                    for (let i=0; i < cm.length; i++) {
                        if (cm[i].dataIndex === 'data') {
                            let editor = this.getCellEditor(e.record);
                            if (editor) {
                                e.grid.columns[i].setEditor(editor);
                            }

                            break;
                        }
                    }

                    var editorRow = el.editor.body;
                    editorRow.rowIdx = e.rowIdx;
                    // add dnd support
                    var dd = new Ext.dd.DropZone(editorRow, {
                        ddGroup: "element",

                        getTargetFromEvent: function(e) {
                            return this.getEl();
                        },

                        onNodeOver : function(elementType, node, dragZone, e, data ) {
                            if (data.records.length == 1) {
                                var record = data.records[0];
                                var data = record.data;

                                if (data.elementType == elementType) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                            }

                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }.bind(this, e.record.get('type')),

                        onNodeDrop : function(elementType, node, dragZone, e, data) {
                            if (pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                try {
                                    var record = data.records[0];
                                    var data = record.data;

                                    if (data.elementType == elementType) {
                                        Ext.getCmp('valueEditor').setValue(data.path);
                                        return true;
                                    }
                                } catch (e) {
                                    console.log(e);
                                }
                            }
                            return false;
                        }.bind(this, e.record.get('type'))
                    });
                }.bind(this),
                delay: 1
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
                items: typesColumns,
                defaults: {
                    renderer: Ext.util.Format.htmlEncode
                },
            },
            sm:  Ext.create('Ext.selection.RowModel', {}),
            bbar:this.pagingtoolbar,
            plugins: [
                this.rowEditing
            ],
            tbar: {
                cls: 'pimcore_main_toolbar',
                items: [
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
            },
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
        let data = record.data;

        let type = data.type;
        let property;

        if (type === "text") {
            property = {
                xtype: 'textfield',
                flex: 1,
                value: data.data
            }
        } else if (type == "textarea") {
            property = {
                xtype: "textarea",
                flex: 1,
                value: data.data
            }
        } else if (type == "document" || type == "asset" || type == "object") {
            property = {
                xtype: 'textfield',
                editable: false,
                id: 'valueEditor',
                fieldCls: "input_drop_target",
                flex: 1,
                value: data.data
            }
        } else if (type == "date") {
            property = Ext.create('Ext.form.field.Date', {
                format: "Y-m-d"
            });
        } else if (type == "checkbox" || type == "bool") {
            property =  {
                xtype: 'checkbox',
                flex: 1,
            }
        } else if (type == "select") {
            var config = data.config;
            property =  Ext.create('Ext.form.ComboBox', {
                triggerAction: 'all',
                editable: false,
                store: config.split(","),
                flex: 1,
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
                            if (data.records.length == 1) {
                                var record = data.records[0];
                                var data = record.data;

                                if (data.elementType == elementType) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                            }

                            return Ext.dd.DropZone.prototype.dropNotAllowed;
                        }.bind(this, data.type),

                        onNodeDrop : function(storeIndex, targetNode, dragZone, e, data) {
                            if (pimcore.helpers.dragAndDropValidateSingleItem(data)) {
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

        return Ext.util.Format.htmlEncode(value);
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

        //this.cellEditing.editors.each(Ext.destroy, Ext);
        //this.cellEditing.editors.clear();

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

        let res = store.add({
            name: key,
            data: value,
            type: type
        });

        this.rowEditing.completeEdit();
        this.rowEditing.startEdit(res[0], 1);
    }

});
