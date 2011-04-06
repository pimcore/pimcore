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

pimcore.registerNS("pimcore.settings.thumbnails");
pimcore.settings.thumbnails = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_thumbnails");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_thumbnails",
                title: t("thumbnails"),
                iconCls: "pimcore_icon_thumbnails",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_thumbnails");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("thumbnails");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var proxy = new Ext.data.HttpProxy({
            url: '/admin/settings/thumbnails'
        });
        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data'
        }, [
            {name: 'id'},
            {name: 'name', allowBlank: false},
            {name: 'description', allowBlank: true},
            {name: 'width', allowBlank: true},
            {name: 'height', allowBlank: true},
            {name: 'quality', allowBlank: false},
            {name: 'aspectratio', allowBlank: true},
            {name: 'cover', allowBlank: true},
            {name: 'contain', allowBlank: true},
            {name: 'interlace', allowBlank: true},
            {name: 'format', allowBlank: false}
        ]);
        var writer = new Ext.data.JsonWriter();

        this.store = new Ext.data.Store({
            id: 'thumbnail_store',
            restful: false,
            proxy: proxy,
            reader: reader,
            writer: writer,
            listeners: {
                write : function(store, action, result, response, rs) {
                }
            }
        });
        this.store.load();

        this.editor = new Ext.ux.grid.RowEditor();

        var typesColumns = [
            {header: t("name"), sortable: true, dataIndex: 'name', editor: new Ext.form.TextField({})},
            {header: t("description"), sortable: true, dataIndex: 'description', editor: new Ext.form.TextArea({}), renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                if(empty(value)) {
                    return "";
                }
                return nl2br(value);
            }
            },
            {header: t("width"), width: 30, sortable: false, dataIndex: 'width', editor: new Ext.ux.form.SpinnerField({})},
            {header: t("height"), width: 30, sortable: false, dataIndex: 'height', editor: new Ext.ux.form.SpinnerField({})},
            {header: t("aspect_ratio"), width: 40, sortable: false, dataIndex: 'aspectratio', editor: new Ext.form.Checkbox({})},
            {header: t("cover"), width: 40, sortable: false, dataIndex: 'cover', editor: new Ext.form.Checkbox({})},
            {header: t("contain"), width: 40, sortable: false, dataIndex: 'contain', editor: new Ext.form.Checkbox({})},
            {header: t("interlace"), width: 40, sortable: false, dataIndex: 'interlace', editor: new Ext.form.Checkbox({})},
            {header: t("quality"), width: 40, sortable: false, dataIndex: 'quality', editor: new Ext.ux.form.SpinnerField({
                maxValue: 100,
                minValue: 0
            })},
            {header: t("format"), width: 80, sortable: false, dataIndex: 'format', editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: ["PNG","GIF","JPEG","SOURCE"],
                width: 50
            })},
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            }
        ];

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            plugins: [this.editor],
            columns : typesColumns,
            columnLines: true,
            stripeRows: true,
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                },
                '-',
                {
                    text: t('delete'),
                    handler: this.onDelete.bind(this),
                    iconCls: "pimcore_icon_delete"
                },
                '-'
            ],
            viewConfig: {
                forceFit: true
            }
        });

        return this.grid;
    },


    onAdd: function (btn, ev) {
        var u = new this.grid.store.recordType();
        this.editor.stopEditing();
        this.grid.store.insert(0, u);
        this.editor.startEditing(0);
    },

    onDelete: function () {
        var rec = this.grid.getSelectionModel().getSelected();
        if (!rec) {
            return false;
        }
        this.grid.store.remove(rec);
    }

});

