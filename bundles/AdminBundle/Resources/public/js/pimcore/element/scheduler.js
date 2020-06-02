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

pimcore.registerNS("pimcore.element.scheduler");
pimcore.element.scheduler = Class.create({

    initialize: function(element, type, options) {
        this.options = Ext.Object.merge({
            supportsVersions: true
        }, options);

        this.element = element;
        this.type = type;
    },

    getLayout: function () {
        if (this.layout == null) {

            var tasksData = [];
            var d = null;
            var rawTask = [];

            if (this.element.data.scheduledTasks.length > 0) {
                var td = [];

                for (var i = 0; i < this.element.data.scheduledTasks.length; i++) {
                    rawTask = this.element.data.scheduledTasks[i];
                    d = new Date(intval(rawTask.date) * 1000);

                    td = [
                        d,
                        Ext.Date.format(d, "H:i"),
                        rawTask.action
                    ];

                    if (this.options.supportsVersions) {
                        td.push(rawTask.version);
                    }

                    td.push(rawTask.active);
                    tasksData.push(td);
                }
            }

            var storeFields = [
                {
                    name: "date",
                    convert: function (v, rec) {
                        var ret = v;
                        if (v instanceof Date) {
                            ret = Ext.Date.format(v, "Y-m-d");
                        }
                        return ret;
                    }
                }, {
                name: "time",
                convert: function (v, rec) {
                    var ret = v;
                    if (v instanceof Date) {
                        ret = Ext.Date.format(v, "H:i");
                    }
                    return ret;
                }
            },
                "action"
            ];

            if (this.options.supportsVersions) {
                storeFields.push("version");
            }

            storeFields.push("active");

            var store = new Ext.data.SimpleStore({
                fields: storeFields,
                data: tasksData
            });

            var actionTypes = this.buildActionsColumnStore();

            if (this.options.supportsVersions) {
                this.versions = new Ext.data.Store({
                    autoDestroy: true,
                    proxy: {
                        type: 'ajax',
                        url: Routing.generate('pimcore_admin_element_getversions'),
                        extraParams: {
                            id: this.element.id,
                            elementType: this.type
                        },
                        reader: {
                            type: 'json',
                            rootProperty: 'versions'
                        }
                    },
                    fields: ['id', {name: 'date', convert: function (v, rec) {
                            var d = new Date(intval(v) * 1000);

                            var ret = Ext.Date.format(d, "Y-m-d H:i");

                            if (rec.data.note) {
                                ret += " - " + rec.data.note;
                            }

                            if (rec.data.user) {
                                ret += " - " + rec.data.user.name;
                            }
                            return ret;
                        }}, 'note', {name:'name', convert: function (v, rec) {
                            if (rec.data.user) {
                                if (rec.data.user.name) {
                                    return rec.data.user.name;
                                }
                            }
                            return null;
                        }}]
                });
            }

            var checkColumn = Ext.create('Ext.grid.column.Check', {
                text: t("active"),
                dataIndex: 'active',
                width: 50,
                sortable: true
            });

            var propertiesColumns = [
                {text: t("date"), width: 120, sortable: true, dataIndex: 'date', editor: new Ext.form.DateField()                },
                {text: t("time"), width: 100, sortable: true, dataIndex: 'time', editor: new Ext.form.TimeField({
                        format: "H:i",
                    })
                },
                {text: t("action"), width: 100, sortable: false, dataIndex: 'action', editor: new Ext.form.ComboBox({
                    triggerAction: 'all',
                    editable: false,
                    store: actionTypes,
                    displayField:'name',
                    valueField: "key",
                    mode: 'local'
                }),renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
                    try {
                        var rec = actionTypes.findRecord("key", value);
                        if (rec) {
                            return rec.get("name");
                        }
                    }
                    catch (e) {
                        console.log(e);

                    }

                    return "";
                }}
            ];

            if (this.options.supportsVersions) {
                propertiesColumns.push({
                    text: t("version"),
                    width: 200,
                    sortable: false,
                    dataIndex: 'version',
                    editor: new Ext.form.ComboBox({
                        triggerAction: 'all',
                        editable: false,
                        store: this.versions,
                        displayField: 'date',
                        valueField: "id",
                        listeners: {
                            "expand": function (el) {
                                el.getStore().reload();
                            }
                        }
                    })
                });
            }

            propertiesColumns.push(checkColumn);
            propertiesColumns.push({
                xtype: 'actioncolumn',
                menuText: t('delete'),
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            });

            this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1
            });

            this.grid = Ext.create('Ext.grid.Panel', {
                frame: false,
                autoScroll: true,
                store: store,
                stripeRows: true,
                trackMouseOver: true,
                columnLines: true,
                columns : propertiesColumns,
                tbar: [
                    {
                        text: t('add'),
                        handler: this.onAdd.bind(this),
                        iconCls: "pimcore_icon_add"
                    },
                    {
                        text: t('delete'),
                        handler: this.onDelete.bind(this),
                        iconCls: "pimcore_icon_delete"
                    }
                ],
                plugins: [
                    this.cellEditing
                    ]
            });


            this.layout = new Ext.Panel({
                tabConfig: {
                    tooltip: t('schedule')
                },
                border: false,
                iconCls: "pimcore_material_icon_scheduler pimcore_material_icon",
                items: [this.grid],
                layout: "fit"
            });
        }

        return this.layout;
    },

    buildActionsColumnStore: function() {
        var actions = [];

        if ("document" === this.type || "object" === this.type) {
            if(this.element.isAllowed("publish")) {
                actions.push(["publish", t("publish")]);
            }

            if(this.element.isAllowed("unpublish")) {
                actions.push(["unpublish", t("unpublish")]);
            }
        }

        if(this.element.isAllowed("delete")) {
            actions.push(["delete", t("delete")]);
        }

        if (this.options.supportsVersions && this.element.isAllowed("publish") && this.element.isAllowed("versions")) {
            actions.push(["publish-version", t("publish_version")]);
        }

        return new Ext.data.SimpleStore({
            fields: ['key', 'name'],
            data: actions
        });
    },

    onAdd: function (btn, ev) {
        var model = this.grid.getStore().getModel();
        var u = new model();
        u.set("date", new Date());
        u.set("active", true);
        this.grid.store.insert(0, [u]);
    },

    onDelete: function () {
        var rec = this.grid.getSelectionModel().getSelection();

        if (!rec) {
            return false;
        }
        this.grid.store.remove(rec[0]);
    },

    getValues: function () {
        if (!this.grid.rendered) {
            throw "scheduler not available";
        }

        var values = [];
        var data = this.grid.store.getRange();

        var value;
        for (var i = 0; i < data.length; i++) {
            value = {
                date:  data[i].data.date,
                time: data[i].data.time,
                action: data[i].data.action,
                active: data[i].data.active
            };

            if (this.options.supportsVersions) {
                value.version = data[i].data.version;
            }

            values.push(value);
        }

        return values;
    }
});
