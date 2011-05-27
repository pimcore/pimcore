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

pimcore.registerNS("pimcore.object.classes.data.structuredTable");
pimcore.object.classes.data.structuredTable = Class.create(pimcore.object.classes.data.data, {

    type: "structuredTable",
    allowIndex: false,

    initialize: function (treeNode, initData) {
        this.type = "structuredTable";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getGroup: function () {
            return "structured";
    },

    getTypeName: function () {
        return t("structuredTable");
    },

    getIconClass: function () {
        return "pimcore_icon_table";
    },

    getLayout: function ($super) {
        this.grids = {};

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("height"),
                name: "height",
                value: this.datax.height
            },
            this.getGetGrid("rows", this.datax.rows),
            this.getGetGrid("cols", this.datax.cols)
        ]);

        return this.layout;
    },


    getGetGrid: function (title, data) {

        var store = new Ext.data.JsonStore({
            //writer: new Ext.data.JsonWriter(),
            autoDestroy: true,
            autoSave: false,
            idIndex: 1,
            fields: [
               'position',
               'key',
               'label'
            ]            
        });
        if(data) {
            store.loadData(data);
        }

        var editor = new Ext.ux.grid.RowEditor();

        var typesColumns = [
            {header: t("position"), width: 10, sortable: true, dataIndex: 'position', editor: new Ext.form.NumberField({})},
            {header: t("key"), width: 50, sortable: true, dataIndex: 'key', editor: new Ext.form.TextField({})},
            {header: t("label"), width: 200, sortable: true, dataIndex: 'label', editor: new Ext.form.TextField({})}
        ];

        this.grids[title] = new Ext.grid.GridPanel({
            title: t(title),
            autoScroll: true,
            store: store,
            height: 200,
            plugins: [editor],
            columns : typesColumns,
            columnLines: true,
            name: title,
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this, store, editor),
                    iconCls: "pimcore_icon_add"
                },
                '-',
                {
                    text: t('delete'),
                    handler: this.onDelete.bind(this, store, title),
                    iconCls: "pimcore_icon_delete"
                },
                '-'
            ],
            viewConfig: {
                forceFit: true
            }
        });

        return this.grids[title];
    },


    onAdd: function (store, editor, btn, ev) {
        var u = new store.recordType();
        editor.stopEditing();
        store.insert(0, u);
        editor.startEditing(0);
    },

    onDelete: function (store, title) {
        var rec = this.grids[title].getSelectionModel().getSelected();
        if (!rec) {
            return false;
        }
        store.remove(rec);
    },

    getData: function () {
        if(this.grids) {
            var rows = [];
            this.grids.rows.getStore().each(function(rec) {
                rows.push(rec.data);
                rec.commit();
            });
            this.datax.rows = rows;

            var cols = [];
            this.grids.cols.getStore().each(function(rec) {
                cols.push(rec.data);
                rec.commit();
            });
            this.datax.cols = cols;
        }

        return this.datax;
    }

});
