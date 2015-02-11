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

pimcore.registerNS("pimcore.object.keyvalue.specialconfigwindow");
pimcore.object.keyvalue.specialconfigwindow = Class.create({

    initialize: function (data, keyid, parentPanel) {
        if (data) {
            this.data = data;
        } else {
            this.data = {};
        }

        this.parentPanel = parentPanel;
        this.keyid = keyid;
    },


    show: function() {

        this.searchfield = new Ext.form.TextField({
            width: 300,
            style: "float: left;",
            fieldLabel: t("search")
        });

        var editPanel = this.getEditPanel();

        this.searchWindow = new Ext.Window({
            modal: true,
            width: 600,
            height: 500,
            layout: "fit",
            resizable: false,
            title: t("keyvalue_define_select_values"),
            items: [editPanel],
            bbar: [
            "->",{
                xtype: "button",
                text: t("cancel"),
                icon: "/pimcore/static/img/icon/cancel.png",
                handler: function () {
                    this.searchWindow.close();
                }.bind(this)
            },{
                xtype: "button",
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.applyData();
                }.bind(this)
            }],
            plain: true
        });

        this.searchWindow.show();
    },

    applyData: function() {
        var value = [];

        var totalCount = this.store.data.length;

        for (var i = 0; i < totalCount; i++) {

            var record = this.store.getAt(i);
            if (record.data.key == "" || record.data.value == "") {
                alert(t("keyvalue_keyvalue_empty"));
                return;
            }
            value.push(record.data);
        }

        this.parentPanel.applyDetailedConfig(this.keyid, value);
        this.searchWindow.close();
    },

    getEditPanel: function () {
        this.resultPanel = new Ext.Panel({
            layout: "fit",
            autoScroll: true,
            items: [this.getGridPanel()],
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                }
            ]
        });

        return this.resultPanel;
    },

    onAdd: function () {
        var thePair = {"key" : "",
            "value" : ""};
        this.store.add(new this.store.recordType(thePair));
    },

    getGridPanel: function() {
        var fields = ['key', 'value'];

        this.store = new Ext.data.ArrayStore({
            data: [],
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

        var pairs = [];
        for (var i = 0; i < this.data.length; i++) {
            var pair = this.data[i];

            this.store.add(new this.store.recordType(pair));
        }

        var gridColumns = [];
        gridColumns.push({header: t("key"), width: 275, sortable: true, dataIndex: 'key',
                                                                                editor: new Ext.form.TextField({})});
        gridColumns.push({header: t("value"), width: 275, sortable: true, dataIndex: 'value',
                                                                                editor: new Ext.form.TextField({})});

        gridColumns.push({
            xtype: 'actioncolumn',
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }
            ]
        });

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: 15,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("plugin_keyvalue_no_properties")
        });


        var configuredFilters = [{
                type: "string",
                dataIndex: "name"
            },
            {
                type: "string",
                dataIndex: "key"
            },
            {
                type: "string",
                dataIndex: "value"
            }
        ];

        this.gridPanel = new Ext.grid.EditorGridPanel({
            clicksToEdit: 1,
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: gridColumns
            }),
            viewConfig: {
                markDirty: false
            },
            // cls: cls,
            width: 200,
            height: 200,
            stripeRows: true,
            tbar: {
                items: [
                    {
                        xtype: "tbspacer",
                        width: 20,
                        height: 16
                        // cls: "pimcore_icon_droptarget"
                    },
                    {
                        xtype: "tbtext",
                        text: t('keyvalue_key_unique')
                    }

                ],
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            autoHeight: true,
            bodyCssClass: "pimcore_object_tag_objects"
        });

        return this.gridPanel;
    }
});