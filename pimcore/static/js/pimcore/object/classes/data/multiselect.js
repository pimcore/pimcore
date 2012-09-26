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

pimcore.registerNS("pimcore.object.classes.data.multiselect");
pimcore.object.classes.data.multiselect = Class.create(pimcore.object.classes.data.data, {

    type: "multiselect",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true
    },

    initialize: function (treeNode, initData) {
        this.type = "multiselect";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("multiselect");
    },

    getGroup: function () {
        return "select";
    },
    
    getIconClass: function () {
        return "pimcore_icon_multiselect";
    },

    getLayout: function ($super) {

        if(typeof this.datax.options != "object") {
            this.datax.options = [];
        }

        this.valueStore = new Ext.data.JsonStore({
            fields: ["key", "value"],
            data: this.datax.options
        });

        this.valueGrid = new Ext.grid.EditorGridPanel({
            tbar: [{
                xtype: "tbtext",
                text: t("selection_options")
            }, "-", {
                xtype: "button",
                iconCls: "pimcore_icon_add",
                handler: function () {
                    var u = new this.valueStore.recordType({
                        key: "",
                        value: ""
                    });
                    this.valueStore.insert(0, u);
                }.bind(this)
            }],
            style: "margin-top: 10px",
            store: this.valueStore,
            plugins: [new Ext.ux.dd.GridDragDropRowOrder({})],
            selModel:new Ext.grid.RowSelectionModel({singleSelect:true}),
            columnLines: true,
            columns: [
                {header: t("display_name"), sortable: false, dataIndex: 'key', editor: new Ext.form.TextField({}), width: 200},
                {header: t("value"), sortable: false, dataIndex: 'value', editor: new Ext.form.TextField({}), width: 200},
                {
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
                }
            ],
            autoHeight: true
        });

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
            },this.valueGrid
        ]);

        return this.layout;
    },

    applyData: function ($super) {

        $super();

        var options = [];

        this.valueStore.commitChanges();
        this.valueStore.each(function (rec) {
            options.push({
                key: rec.get("key"),
                value: rec.get("value")
            });
        });

        this.datax.options = options;
    }
});
