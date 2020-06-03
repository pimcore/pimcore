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

pimcore.registerNS("pimcore.settings.metadata.predefined");
pimcore.settings.metadata.predefined = Class.create({

    initialize: function () {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("predefined_metadata");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "predefined_metadata",
                title: t("predefined_metadata_definitions"),
                iconCls: "pimcore_icon_metadata",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("predefined_metadata");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("predefined_metadata");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {
        var url = Routing.generate('pimcore_admin_settings_metadata');

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            [
                'id',
                {
                    name: 'name',
                    allowBlank: false,
                    convert: function (v, r) {
                        return v.replace(/[~]/g, "---");
                    }
                },
                'description','type',
                {name: 'data',
                    convert: function (v, r) {
                        let dataType = r.data.type;
                        if (typeof pimcore.asset.metadata.tags[dataType].prototype.convertPredefinedGridData === "function") {
                            v = pimcore.asset.metadata.tags[dataType].prototype.convertPredefinedGridData(v, r);
                        }
                        return v;
                    }
                },'config', 'targetSubtype', 'language', 'creationDate' ,'modificationDate'
            ], null, {
                remoteSort: false,
                remoteFilter: false
            }
        );

        this.store.addListener('exception', function(proxy, mode, action, options, response) {
            Ext.Msg.show({
                title: t("error"),
                msg: t(response.raw.message),
                buttons: Ext.Msg.OK,
                animEl: 'elId',
                icon: Ext.MessageBox.ERROR
            });
        });

        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });


        var languagestore = [["",t("none")]];
        for (let i=0; i<pimcore.settings.websiteLanguages.length; i++) {
            languagestore.push([pimcore.settings.websiteLanguages[i],pimcore.settings.websiteLanguages[i]]);
        }

        var supportedTypes = pimcore.helpers.getAssetMetadataDataTypes("predefined");
        var typeStore = [];

        for (let i = 0; i < supportedTypes.length; i++) {
            let type = supportedTypes[i];
            typeStore.push([type, t(type)]);
        }

        var metadataColumns = [
            {
                text: t("type"),
                dataIndex: 'type',
                editable: false,
                width: 40,
                renderer: this.getTypeRenderer.bind(this),
                sortable: true
            },
            {text: t("name"), width: 200, sortable: true, dataIndex: 'name',
                getEditor: function() { return new Ext.form.TextField({}); }
            },
            {text: t("description"), sortable: true, dataIndex: 'description',
                getEditor: function() { return new Ext.form.TextArea({}); },
                renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                    if (empty(value)) {
                        return "";
                    }
                    return nl2br(Ext.util.Format.htmlEncode(value));
                }
            },
            {text: t("type"), width: 90, sortable: true,
                dataIndex: 'type',
                getEditor: function() {
                    return new Ext.form.ComboBox({
                        editable: false,
                        store: typeStore

                    })
                }
            },
            {text: t("value"),
                flex: 510,
                sortable: true,
                dataIndex: 'data',
                editable: true,
                getEditor: this.getCellEditor.bind(this),
                renderer: this.getCellRenderer.bind(this)
            },
            {text: t("configuration"),
                width: 100,
                sortable: false,
                dataIndex: 'config',
                getEditor: function() { return new Ext.form.TextField({}); }
            },
            {
                text: t('language'),
                sortable: true,
                dataIndex: "language",
                getEditor: function() {
                    return new Ext.form.ComboBox({
                        name: "language",
                        store: languagestore,
                        editable: false,
                        triggerAction: 'all',
                        mode: "local"
                    });
                },
                width: 70
            },
            {
                text: t("target_subtype"), width: 80, sortable: true, dataIndex: 'targetSubtype',
                getEditor: function() {
                    return new Ext.form.ComboBox({
                        editable: true,
                        store: ["image", "text", "audio", "video", "document", "archive", "unknown"]
                    });
                }
            },
            {
                xtype: 'actioncolumn',
                menuText: t('delete'),
                width: 40,
                items: [{
                    tooltip: t('delete'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }]
            },
            {text: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    }
                    return "";
                }
            },
            {text: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    }
                    return "";
                }
            }
        ];

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function(editor, context, eOpts) {
                    //need to clear cached editors of cell-editing editor in order to
                    //enable different editors per row
                    editor.editors.each(function (e) {
                        try {
                            // complete edit, so the value is stored when hopping around with TAB
                            e.completeEdit();
                            Ext.destroy(e);
                        } catch (exception) {
                            // garbage collector was faster
                            // already destroyed
                        }
                    });

                    editor.editors.clear();
                }
            }
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            autoScroll: true,
            store: this.store,
            columnLines: true,
            stripeRows: true,
            bodyCls: "pimcore_editable_grid",
            trackMouseOver: true,
            columns: {
                items: metadataColumns,
                defaults: {
                    renderer: Ext.util.Format.htmlEncode
                },
            },
            clicksToEdit: 1,
            selModel: Ext.create('Ext.selection.CellModel', {}),
            bbar: this.pagingtoolbar,
            autoExpandColumn: "value_col",
            plugins: [
                this.cellEditing
            ],

            viewConfig: {
                listeners: {
                    rowupdated: this.updateRows.bind(this, "rowupdated"),
                    refresh: this.updateRows.bind(this, "refresh")
                },
                forceFit: true
            },
            tbar: {
                cls: 'pimcore_main_toolbar',
                items: [
                    {
                        text: t('add'),
                        handler: this.onAdd.bind(this),
                        iconCls: "pimcore_icon_add"
                    },"->",{
                        text: t("filter") + "/" + t("search"),
                        xtype: "tbtext",
                        style: "margin: 0 10px 0 0;"
                    },
                    this.filterField
                ]
            }
        });

        this.grid.on("viewready", this.updateRows.bind(this));
        this.store.on("update", this.updateRows.bind(this));

        return this.grid;
    },

    getTypeRenderer: function (value, metaData, record, rowIndex, colIndex, store) {

        if (value == "input") {
            value = "text";
        }
        return '<div class="pimcore_icon_' + value + '" recordid=' + record.id + '>&nbsp;</div>';
    },

    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
        var data = store.getAt(rowIndex).data;
        var type = data.type;
        return pimcore.asset.metadata.tags[type].prototype.getGridCellRenderer(value, metaData, record, rowIndex, colIndex, store);
    },

    onAdd: function (btn, ev) {
        var model = this.grid.store.getModel();
        var newEntry = new model({
            name: t('new_definition'),
            key: "new_key",
            subtype: "image",
            type: "input"
        });

        this.grid.store.insert(0, newEntry);
    },

    updateRows: function (event) {
        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid-row");

        for (let i = 0; i < rows.length; i++) {

            try {
                var list = Ext.get(rows[i]).query(".x-grid-cell-first div div");
                var firstItem = list[0];
                if (!firstItem) {
                    continue;
                }
                var recordId = firstItem.getAttribute("recordid");
                var data = this.grid.getStore().getById(recordId);
                if (!data) {
                    continue;
                }

                data = data.data;

                if(in_array(data.name, this.disallowedKeys)) {
                    Ext.get(rows[i]).addCls("pimcore_properties_hidden_row");
                }

                pimcore.asset.metadata.tags[data.type].prototype.updatePredefinedGridRow(this.grid, rows[i], data);
            }
            catch (e) {
                console.log(e);
            }
        }
    },

    getCellEditor: function (record) {
        var data = record.data;
        var type = data.type;
        var editor = pimcore.asset.metadata.tags[type].prototype.getGridCellEditor("predefined", record);
        return editor;
    }
});
