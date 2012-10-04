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

pimcore.registerNS("pimcore.settings.website");
pimcore.settings.website = Class.create({

    initialize: function () {

        this.getLayout();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_website_settings");
    },
    
    getLayout: function () {

        if (this.layout == null) {

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
            
            
            var customPropertySet = new Ext.form.FormPanel({
                title: t('add_setting'),
                collapsible: true,
                autoHeight:true,
                layout: "pimcoreform",
                defaultType: 'textfield',
                bodyStyle:'padding:10px;',
                items :[
                    {
                        fieldLabel: t('key'),
                        name: 'key'
                    },
                    {
                        fieldLabel: t('type'),
                        name: "type",
                        xtype: "combo",
                        valueField: "id",
                        displayField:'name',
                        store: propertyTypes,
                        editable: false,
                        triggerAction: 'all',
                        mode: "local",
                        listWidth: 200
                    }
                ]
            });

            customPropertySet.addButton({
                text: t('add'),
                iconCls: "pimcore_icon_add",
                listeners: {
                    "click": this.addSetFromUserDefined.bind(this, customPropertySet)
                }
            });
            
            

            // prepare store data
            var property = null;
            var key = null;

            var store = new Ext.data.Store({
                autoDestroy: true,
                url: "/admin/settings/website-load",
                reader: new Ext.data.JsonReader({
                    root: 'settings',
                    fields: ['name', 'type', 'siteId', {name: "data", type: "string", convert: function (v, rec) {
                        if (rec.type == "document" || rec.type == "asset" || rec.type == "object") {
                            var type = rec.type;
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
                    }}
                    ]
                })
            });
            
            store.load();

            this.settingsGrid = new Ext.grid.EditorGridPanel({
                autoScroll: true,
                region: "center",
                reference: this,
                trackMouseOver: true,
                store: store,
                clicksToEdit: 1,
                viewConfig: {
                    listeners: {
                        rowupdated: this.updateRows.bind(this, "rowupdated"),
                        refresh: this.updateRows.bind(this, "refresh")
                    }
                },
                autoExpandColumn: "property_value_col",
                columnLines: true,
                stripeRows: true,
                columns: [
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
                    {header: t("site"), width: 200, sortable:true, dataIndex: "siteId", editor: new Ext.form.ComboBox({
                        store: pimcore.globalmanager.get("sites"),
                        valueField: "id",
                        displayField: "domain",
                        triggerAction: "all"
                    }), renderer: function (siteId) {
                        var store = pimcore.globalmanager.get("sites");
                        var pos = store.findExact("id", siteId);
                        if(pos >= 0) {
                            return store.getAt(pos).get("domain");
                        }
                    }}
                ]
            });

            store.on("update", this.updateRows.bind(this));
            this.settingsGrid.on("viewready", this.updateRows.bind(this));
            this.settingsGrid.on("afterrender", function() {
                this.setAutoScroll(true);
            });
            this.settingsGrid.on("beforeedit", function (e) {
                if (e.grid.getStore().getAt(e.row).data.inherited) {
                    return false;
                }
                return true;
            });
            this.settingsGrid.on("rowcontextmenu", function (grid, rowIndex, event) {

                if (grid.getStore().getAt(rowIndex).data.inherited) {
                    event.stopEvent();
                    return;
                }
                
                $(grid.getView().getRow(rowIndex)).animate( { backgroundColor: '#E0EAEE' }, 100).animate( { backgroundColor: '#fff' }, 400);

                var menu = new Ext.menu.Menu();

                menu.add(new Ext.menu.Item({
                    text: t('empty'),
                    iconCls: "pimcore_icon_flush_recyclebin",
                    handler: function (grid, index) {
                        grid.getStore().getAt(index).set("data","");
                    }.bind(this, grid, rowIndex)
                }));

                menu.add(new Ext.menu.Item({
                    text: t('delete'),
                    iconCls: "pimcore_icon_delete",
                    handler: function (grid, index) {
                        grid.getStore().removeAt(index);
                    }.bind(this, grid, rowIndex)
                }));

                event.stopEvent();
                menu.showAt(event.getXY())
            }.bind(this));

            this.eastLayout = new Ext.Panel({
                region: "east",
                width: 400,
                autoScroll: true,
                bodyStyle:'padding:10px;'
            });

            this.layout = new Ext.Panel({
                title: t('website_settings'),
                border: false,
                id: "pimcore_website_settings",
                iconCls: "pimcore_icon_website",
                layout: "border",
                closable:true,
                items: [this.eastLayout, this.settingsGrid],
                buttons: [{
                    text: t("save"),
                    handler: this.save.bind(this),
                    iconCls: "pimcore_icon_apply"
                }]
            });

            this.layout.on("activate", function (customPropertySet) {
                if (customPropertySet.rendered != true) {
                    this.eastLayout.add(customPropertySet);
                    this.eastLayout.doLayout();
                }
            }.bind(this, customPropertySet));
            
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.layout);
            tabPanel.activate("pimcore_website_settings");


            this.layout.on("destroy", function () {
                pimcore.globalmanager.remove("settings_website");
            }.bind(this));
        }

        return this.layout;
    },

    getTypeRenderer: function (value, metaData, record, rowIndex, colIndex, store) {

        return '<div style="background: url(/pimcore/static/img/icon/' + value + '.png) center center no-repeat; height: 16px;" name="' + record.data.name + '">&nbsp;</div>';
    },

    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {

        var data = store.getAt(rowIndex).data;
        var type = data.type;

        if (type == "document" || type == "asset" || type == "object") {
            return '<div class="pimcore_property_droptarget">' + value + '</div>';
        } else if (type == "bool") {
            metaData.css += ' x-grid3-check-col-td';
            return String.format('<div class="x-grid3-check-col{0}" style="background-position:10px center;">&#160;</div>', value ? '-on' : '');
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

    getCellEditor: function (rowIndex) {

        var store = this.settingsGrid.getStore();
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
                settingsGrid: this.settingsGrid,
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
        var rows = Ext.get(this.settingsGrid.getEl().dom).query(".x-grid3-row");

        for (var i = 0; i < rows.length; i++) {
            try {
                var propertyName = Ext.get(rows[i]).query(".x-grid3-cell-first div div")[0].getAttribute("name");
                var storeIndex = this.settingsGrid.getStore().find("name", propertyName);

                var data = this.settingsGrid.getStore().getAt(storeIndex).data;

                if (data.type == "document" || data.type == "asset" || data.type == "object") {
                    
                    // add dnd support 
                    var dd = new Ext.dd.DropZone(rows[i], {
                        ddGroup: "element",

                        getTargetFromEvent: function(e) {
                            return this.getEl();
                        },

                        onNodeOver : function(target, dd, e, data) {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        },

                        onNodeDrop : function(myRowIndex, target, dd, e, data) {
                            var rec = this.settingsGrid.getStore().getAt(myRowIndex);
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

    addSetFromUserDefined: function (fieldset) {
        var form = fieldset.getForm();
        var selectedType = form.findField("type").getValue();
        var key = form.findField("key").getValue();

        this.add(key, selectedType, false, false, false, true);
    },

    add: function (key, type, value, config, inherited, inheritable) {

        var store = this.settingsGrid.getStore();

        // check for duplicate name
        var dublicateIndex = store.findBy(function (key, record, id) {
            if (record.data.name.toLowerCase() == key.toLowerCase()) {
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
        this.settingsGrid.getView().refresh();
    },

    save : function () {

        var values = {};
        var store = this.settingsGrid.getStore();
        store.commitChanges();

        var records = store.getRange();

        for (var i = 0; i < records.length; i++) {
            currentData = records[i];
            if (currentData) {
                if (!currentData.data.inherited) {
                    values[currentData.data.name] = {
                        data: currentData.data.data,
                        type: currentData.data.type,
                        siteId : typeof(currentData.data.siteId) != 'undefined' ? currentData.data.siteId : '' //empty string because we want to have the siteId tag in the xml file
                    };
                }
            }
        }

        var data = Ext.encode(values);
        
        Ext.Ajax.request({
            url: "/admin/settings/website-save",
            method: "post",
            params: {
                data: data
            },
            success: function (response) {
                try{
                    var res = Ext.decode(response.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("settings_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("settings_save_error"), "error",t(res.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("settings_save_error"), "error");    
                }
            }
        });
    }

});