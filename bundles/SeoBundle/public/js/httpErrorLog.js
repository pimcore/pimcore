/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.bundle.seo.httpErrorLog");
/**
 * @private
 */
pimcore.bundle.seo.httpErrorLog = Class.create({

    initialize: function(id) {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_http_error_log");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_http_error_log",
                title: t("http_errors"),
                iconCls: "pimcore_icon_httperrorlog",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_http_error_log");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("bundle_seo_http_error_log");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },


    getGrid: function () {

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        var url = Routing.generate('pimcore_bundle_seo_misc_httperrorlog');

        this.store = pimcore.helpers.grid.buildDefaultStore(
            url,
            ["uri", "code", "date","count"],
            itemsPerPage
        );

        var proxy = this.store.getProxy();
        proxy.extraParams["group"] = 1;
        proxy.getReader().setRootProperty('items');

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        var typesColumns = [
            {text: "Code", width: 60, sortable: true, dataIndex: 'code'},
            {text: t("path"), width: 400, sortable: true, dataIndex: 'uri'},
            {text: t("amount"), width: 60, sortable: true, dataIndex: 'count'},
            {text: t("date"), width: 200, sortable: true, dataIndex: 'date',
                                                                    renderer: function(d) {
                var date = new Date(d * 1000);
                return Ext.Date.format(date, "Y-m-d H:i:s");
            }},
            {
                xtype: 'actioncolumn',
                text: t('open'),
                menuText: t('open'),
                width: 70,
                align: 'center',
                items: [{
                    tooltip: t('open'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        window.open(data.get("uri"));
                    }.bind(this)
                }]
            },
            this.createRedirectColumn()
        ];


        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        const val = field.getValue();
                        this.store.getProxy().extraParams.filter = val ? val : "";
                        this.store.load();
                    }
                }.bind(this)
            }
        });


        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columns : typesColumns,
            autoExpandColumn: "path",
            trackMouseOver: true,
            bbar: this.pagingtoolbar,
            columnLines: true,
            stripeRows: true,
            listeners: {
                "rowdblclick": function (grid, record, tr, rowIndex, e, eOpts ) {
                    var data = grid.getStore().getAt(rowIndex);
                    var path = Routing.generate('pimcore_bundle_seo_misc_httperrorlogdetail', {
                        uri: data.get("uri"),
                    });
                    var win = new Ext.Window({
                        closable: true,
                        width: 810,
                        autoDestroy: true,
                        height: 430,
                        modal: true,
                        html: '<iframe src="' + path + '" frameborder="0" width="100%" height="390"></iframe>'
                    });
                    win.show();
                }
            },
            viewConfig: {
                forceFit: true
            },
            tbar: {
                cls: 'pimcore_main_toolbar',
                items: [{
                    text: t("refresh"),
                    iconCls: "pimcore_icon_reload",
                    handler: this.reload.bind(this)
                }, "-",{
                    text: t("group_by_path"),
                    pressed: true,
                    iconCls: "pimcore_icon_groupby",
                    enableToggle: true,
                    handler: function (button) {
                        this.store.getProxy().extraParams.group = button.pressed ? 1 : 0;
                        this.store.load();
                    }.bind(this)
                }, "-",{
                    text: t('flush'),
                    handler: function () {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_bundle_seo_misc_httperrorlogflush'),
                            method: "DELETE",
                            success: function () {
                                var proxy = this.store.getProxy();
                                proxy.extraParams.filter = this.filterField.getValue();
                                this.store.load();
                            }.bind(this)
                        });
                    }.bind(this),
                    iconCls: "pimcore_icon_flush_recyclebin"
                }, "-", {
                    text: t("errors_from_the_last_7_days"),
                    xtype: "tbtext"
                }, '-',"->",{
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                this.filterField]
            }
        });

        return this.grid;
    },

    createRedirectColumn: function () {
        const createDropZone = (el) => new Ext.dd.DropZone(el.getEl(), {
            reference: this,
            ddGroup: "element",
            getTargetFromEvent: function (e) {
                return this.getEl();
            }.bind(el),

            onNodeOver: function (target, dd, e, data) {
                const record = data.records[0];
                if (record.data && pimcore.settings.redirects.canDrop(record.data)) {
                    return Ext.dd.DropZone.prototype.dropAllowed;
                }
                return Ext.dd.DropZone.prototype.dropNotAllowed;
            },

            onNodeDrop: function (target, dd, e, data) {
                if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                    return false;
                }

                data = data.records[0].data;
                if (pimcore.settings.redirects.canDrop(data)) {
                    const targetTypeField = this.up('container').down('textfield[name=targetType]');
                    this.setValue(data.path);
                    targetTypeField.setValue(data.elementType);
                    return true;
                }
                return false;
            }.bind(el),
        });

        return {
            xtype: "actioncolumn",
            text: "Redirect",
            menuText: "Redirect",
            width: 70,
            align: "center",
            renderer: function (
                value,
                metaData,
                record,
                rowIndex,
                colIndex,
                store,
                view
            ) {
                if (record.data.code !== 404 || record.data.sourceSite === null) {
                    // Can't hide inner content, so let's push it off the display
                    metaData.tdStyle = "text-indent: -1000em";
                }
                return value;
            },
            items: [
                {
                    tooltip: "Set a redirect",
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/workflow.svg",
                    isActionDisabled: function (
                        grid,
                        rowIndex,
                        colIndex,
                        item,
                        record
                    ) {
                        return record.data.code !== 404 || record.data.sourceSite === null;
                    },
                    handler: function (grid, rowIndex) {
                        const gridData = grid.getStore().getAt(rowIndex);
                        const windowCfg = {
                            title: t("Create a redirect"),
                            width: 600,
                            layout: "fit",
                            closeAction: "close",
                            items: [
                                {
                                    xtype: "form",
                                    bodyStyle: "padding: 10px;",
                                    defaults: {
                                        labelWidth: 70,
                                        width: 574,
                                    },
                                    itemId: "form",
                                    items: [
                                        {
                                            xtype: "textfield",
                                            name: "source",
                                            fieldLabel: t("source"),
                                            value: gridData.get("path"),
                                            disabled: true,
                                        },
                                        {
                                            xtype: "textfield",
                                            name: "target",
                                            fieldCls: "input_drop_target",
                                            fieldLabel: t("target"),
                                            value: "",
                                            listeners: {
                                                render: (el) => createDropZone(el),
                                            },
                                        },
                                        {
                                            xtype: "textfield",
                                            name: "targetType",
                                            hidden: true,
                                        },
                                    ],
                                },
                            ],
                            buttons: [
                                {
                                    text: t("cancel"),
                                    iconCls: "pimcore_icon_delete",
                                    handler: function () {
                                        win.close();
                                    },
                                },
                                {
                                    text: t("save"),
                                    iconCls: "pimcore_icon_apply",
                                    handler: function () {
                                        const data = win
                                            .getComponent("form")
                                            .getForm()
                                            .getFieldValues();
                                        data["source"] = gridData.get("path");
                                        data["sourceSite"] = gridData.get("sourceSite");
                                        data["type"] = "path";
                                        data["priority"] = 1;
                                        data["regex"] = false;
                                        data["active"] = true;

                                        if (!data["target"]) {
                                            Ext.Msg.alert(t("error"), t("Please drag an element to the target field."));
                                        } else {
                                            Ext.Ajax.request({
                                                url: Routing.generate(
                                                    "pimcore_bundle_seo_redirects_redirects",
                                                    { xaction: "create" }
                                                ),
                                                method: "POST",
                                                params: {
                                                    data: JSON.stringify(data),
                                                },
                                                success: function (response) {
                                                    Ext.Msg.alert(t("success"), t("Redirect created successfully!"));
                                                    win.close();
                                                },
                                            });
                                        }
                                    },
                                },
                            ],
                        };
                        const win = new Ext.Window(windowCfg);
                        win.show();
                    },
                },
            ],
        };
    },

    reload: function () {
        this.store.reload();
    }
});
