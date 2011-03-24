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

pimcore.registerNS("pimcore.settings.glossary");
pimcore.settings.glossary = Class.create({

    initialize: function () {
        this.getAvailableLanguages();
    },


    getAvailableLanguages: function () {
        Ext.Ajax.request({
            url: "/admin/settings/get-available-languages",
            success: function (response) {
                try {
                    this.languages = Ext.decode(response.responseText);
                    this.getTabPanel();
                }
                catch (e) {
                    console.log(e);
                    Ext.MessageBox.alert(t('error'), t('translations_are_not_configured') + '<br /><br /><a href="http://www.pimcore.org/documentation/" target="_blank">' + t("read_more_here") + '</a>');
                }
            }.bind(this)
        });
    },


    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_glossary");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_glossary",
                iconCls: "pimcore_icon_glossary",
                title: t("glossary"),
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_glossary");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("glossary");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var proxy = new Ext.data.HttpProxy({
            url: '/admin/settings/glossary'
        });
        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data'
        }, [
            {name: 'id'},
            {name: 'text', allowBlank: false},
            {name: 'language', allowBlank: false},
            {name: 'link', allowBlank: true},
            {name: 'abbr', allowBlank: true},
            {name: 'acronym', allowBlank: true}
        ]);
        var writer = new Ext.data.JsonWriter();

        this.store = new Ext.data.Store({
            id: 'glossary_store',
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

        var typesColumns = [
            {header: t("text"), width: 200, sortable: true, dataIndex: 'text', editor: new Ext.form.TextField({})},
            {header: t("language"), width: 50, sortable: true, dataIndex: 'language', editor: new Ext.form.ComboBox({
                store: this.languages,
                mode: "local",
                triggerAction: "all"
            })},
            {header: t("link"), width: 200, sortable: true, dataIndex: 'link', editor: new Ext.form.TextField({}), css: "background: url(/pimcore/static/img/icon/drop-16.png) right 2px no-repeat;"},
            {header: t("abbr"), width: 200, sortable: true, dataIndex: 'abbr', editor: new Ext.form.TextField({})},
            {header: t("acronym"), width: 200, sortable: true, dataIndex: 'acronym', editor: new Ext.form.TextField({})},
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                        this.updateRows();
                    }.bind(this)
                }]
            }
        ];

        this.grid = new Ext.grid.EditorGridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columns : typesColumns,
            trackMouseOver: true,
            columnLines: true,
            stripeRows: true,
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
                forceFit: true,
                listeners: {
                    rowupdated: this.updateRows.bind(this),
                    refresh: this.updateRows.bind(this)
                }
            }
        });

        this.store.on("update", this.updateRows.bind(this));
        this.grid.on("viewready", this.updateRows.bind(this));

        return this.grid;
    },

    updateRows: function () {

        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid3-row");

        for (var i = 0; i < rows.length; i++) {

            var dd = new Ext.dd.DropZone(rows[i], {
                ddGroup: "element",

                getTargetFromEvent: function(e) {
                    return this.getEl();
                },

                onNodeOver : function(target, dd, e, data) {
                    return Ext.dd.DropZone.prototype.dropAllowed;
                },

                onNodeDrop : function(myRowIndex, target, dd, e, data) {
                    var rec = this.grid.getStore().getAt(myRowIndex);
                    rec.set("link", data.node.attributes.path);

                    this.updateRows();

                    return true;
                }.bind(this, i)
            });
        }

    },

    onAdd: function (btn, ev) {
        var u = new this.grid.store.recordType({
            name: t('/')
        });
        this.grid.store.insert(0, u);

        this.updateRows();
    },

    onDelete: function () {
        var rec = this.grid.getSelectionModel().getSelectedCell();
        if (!rec) {
            return false;
        }

        this.grid.store.removeAt(rec[0]);

        this.updateRows();
    }

});