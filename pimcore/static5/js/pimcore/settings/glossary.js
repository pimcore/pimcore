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
        var url = '/admin/settings/glossary?';
        var proxy = {
            type: 'ajax',
            extraParams:{
                limit:itemsPerPage,
                filter:""
            },
            reader: {
                type: 'json',
                rootProperty: 'data'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            },
            api: {
                create  : url + "xaction=create",
                read    : url + "xaction=read",
                update  : url + "xaction=update",
                destroy : url + "xaction=destroy"
            },
            actionMethods: {
                create : 'POST',
                read   : 'POST',
                update : 'POST',
                destroy: 'POST'
            }
        };


        this.store = new Ext.data.Store({
            proxy: proxy,
            autoLoad: true,
            autoSync: true,
            fields: [
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
            remoteSort: true
        });


        this.filterField = new Ext.form.TextField({
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

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: itemsPerPage,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });

        // add per-page selection
        this.pagingtoolbar.add("-");

        this.pagingtoolbar.add(new Ext.Toolbar.TextItem({
            text: t("items_per_page")
        }));
        this.pagingtoolbar.add(new Ext.form.ComboBox({
            store: [
                [10, "10"],
                [20, "20"],
                [40, "40"],
                [60, "60"],
                [80, "80"],
                [100, "100"]
            ],
            mode: "local",
            width: 50,
            value: 20,
            triggerAction: "all",
            listeners: {
                select: function (box, rec, index) {
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));


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
                    icon: "/pimcore/static/img/icon/cross.png",
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
            tbar: [
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