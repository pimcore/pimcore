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
                    this.languages.splice(0,0,"");
                    this.getTabPanel();
                }
                catch (e) {
                    console.log(e);
                    Ext.MessageBox.alert(t('error'), t('translations_are_not_configured')
                                + '<br /><br /><a href="http://www.pimcore.org/documentation/" target="_blank">'
                                + t("read_more_here") + '</a>');
                }
            }.bind(this)
        });
    },


    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_glossary");
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
            tabPanel.setActiveItem("pimcore_glossary");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("glossary");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var itemsPerPage = 20;
        this.store = pimcore.helpers.grid.buildDefaultStore(
            '/admin/settings/glossary?',
            [
                {name: 'id'},
                {name: 'text', allowBlank: false},
                {name: 'language', allowBlank: true},
                {name: 'casesensitive', allowBlank: true},
                {name: 'exactmatch', allowBlank: true},
                {name: 'site', allowBlank: true},
                {name: 'link', allowBlank: true},
                {name: 'abbr', allowBlank: true},
                {name: 'acronym', allowBlank: true},
                {name: 'creationDate', allowBlank: true},
                {name: 'modificationDate', allowBlank: true}
            ],
            itemsPerPage
        );

        this.filterField = Ext.create("Ext.form.TextField", {
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

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, itemsPerPage);

        var casesensitiveCheck = new Ext.grid.column.Check({
            header: t("casesensitive"),
            dataIndex: "casesensitive",
            width: 50
        });

        var exactmatchCheck = new Ext.grid.column.Check({
            header: t("exactmatch"),
            dataIndex: "exactmatch",
            width: 50
        });

        var typesColumns = [
            {header: t("text"), flex: 200, sortable: true, dataIndex: 'text', editor: new Ext.form.TextField({})},
            {header: t("link"), flex: 200, sortable: true, dataIndex: 'link', editor: new Ext.form.TextField({}),
                                tdCls: "pimcore_droptarget_input"},
            {header: t("abbr"), flex: 200, sortable: true, dataIndex: 'abbr', editor: new Ext.form.TextField({})},
            {header: t("acronym"), flex: 200, sortable: true, dataIndex: 'acronym',
                                editor: new Ext.form.TextField({})},
            {header: t("language"), flex: 50, sortable: true, dataIndex: 'language', editor: new Ext.form.ComboBox({
                store: this.languages,
                mode: "local",
                triggerAction: "all"
            })},
            casesensitiveCheck,
            exactmatchCheck,
            {header: t("site"), flex: 200, sortable:true, dataIndex: "site", editor: new Ext.form.ComboBox({
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
            }},
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
            },
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
            },
            {
                xtype: 'actioncolumn',
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/pimcore/static6/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                        this.updateRows();
                    }.bind(this)
                }]
            }
        ];

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'main-toolbar',
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
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            autoScroll: true,
            store: this.store,
            columns : typesColumns,
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.cellEditing
            ],

            trackMouseOver: true,
            columnLines: true,
            bbar: this.pagingtoolbar,
            stripeRows: true,
            tbar: toolbar,
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

        this.store.load();

        return this.grid;
    },

    updateRows: function () {

        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid-row");

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
                    try {
                        var record = data.records[0];
                        var data = record.data;

                        var rec = this.grid.getStore().getAt(myRowIndex);
                        rec.set("link", data.path);

                        this.updateRows();

                        return true;
                    } catch (e) {
                        console.log(e);
                    }
                }.bind(this, i)
            });
        }

    },

    onAdd: function (btn, ev) {
        this.grid.store.insert(0,{
            name: t('/')
        });

        this.updateRows();
    }
});