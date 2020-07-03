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

pimcore.registerNS("pimcore.document.document_language_overview");
pimcore.document.document_language_overview = Class.create({

    initialize: function (document) {

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_document_languagetreeroot'),
            params: {
                id: document.id
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                if(res.root["id"]) {
                    this.showWindow(res.root, res.columns, res.languages);
                }
            }.bind(this)
        });

    },


    showWindow: function (rootNodeConfig, columns, languages) {

        var width = window.innerWidth - 200;
        width = width > 1200 ? 1200 : width;

        this.win = this.win ? this.win : new Ext.Window({
            width: width,
            height: 600,
            modal: true,
            bodyStyle: "padding:10px",
            items: [this.getTreeGrid(rootNodeConfig, columns, languages)],
            title: t('document_language_overview'),
            buttons: [{
                text: t("close"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.win.close();
                }.bind(this)
            }]
        });

        this.win.show();
    },

    hideWindow: function() {
        if(this.win) {
            this.win.close();
        }
    },

    getTreeGrid: function (rootNodeConfig, columns, languages) {

        for(var i=1; i<columns.length; i++) {
            columns[i].renderer = function(value) {

                var classAddon = !value.published && value.itemType == 'document' ? ' class="strikeThrough"' : '';
                var actionsButton = '<img src="/bundles/pimcoreadmin/img/flat-color-icons/edit.svg" class="x-action-col-icon x-action-col-0" style="position: absolute;" />';

                return actionsButton + '<span title="' + value.fullPath + '"' + classAddon +' style="margin-left: 25px;">' + value.text + '</span>';
            };
        }

        rootNodeConfig.nodeType = "async";
        rootNodeConfig.expanded = true;
        rootNodeConfig.attributes = {};


        var store = Ext.create('Ext.data.TreeStore', {
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_document_document_languagetree'),
                extraParams: {languages:languages.join(',')}
            }
        });

        var tree = Ext.create('Ext.tree.Panel', {
                store: store,
                height: 500,
                columns: columns,
                enableSort: false,
                animate: false,
                rootVisible: true,
                root: rootNodeConfig,
                border: false,
                lines: true,
                cls: "pimcore_document_seo_tree",
                listeners: {
                    "cellcontextmenu": this.onCellContextmenu.bind(this),
                    "cellclick": this.onCellContextmenu.bind(this),
                    'render': function () {
                        this.getRootNode().expand();
                    }
                }
            }
        );

        return tree;
    },

    onCellContextmenu: function (tree, td, cellIndex, record, tr, rowIndex, e, eOpts ) {

        if(cellIndex == 0 && e.type == 'click') {
            return;
        }

        tree.select();
        var column = tree.config.grid.columns[cellIndex];
        var data = cellIndex == 0 ? record.data : record.data[column.dataIndex];


        var menu = new Ext.menu.Menu();
        if(data.itemType != 'empty') {
            if(data.permissions.view) {
                menu.add([{
                    text: t("open"),
                    iconCls: "pimcore_icon_edit",
                    handler: function() {
                        pimcore.helpers.openDocument(data.id, data.type);
                        this.hideWindow();
                    }.bind(this)
                }]);
            }

            if(!data.published && data.permissions.publish) {
                menu.add([{
                    text: t("publish"),
                    iconCls: "pimcore_icon_publish",
                    handler: function() {
                        this.publishDocument(tree, data, 'publish');
                    }.bind(this)
                }]);
            }

            if(data.published && data.permissions.unpublish) {
                menu.add([{
                    text: t("unpublish"),
                    iconCls: "pimcore_icon_unpublish",
                    handler: function() {
                        this.publishDocument(tree, data, 'unpublish');
                    }.bind(this)
                }]);
            }
        } else {
            menu.add([{
                text: t("create_translation_inheritance"),
                iconCls: "pimcore_icon_page pimcore_icon_overlay_add",
                handler: function() {
                    this.createTranslation(record, column, true);
                }.bind(this)
            },{
                text: t("create_translation"),
                iconCls: "pimcore_icon_page pimcore_icon_overlay_add",
                handler: function() {
                    this.createTranslation(record, column, false);
                }.bind(this)
            }]);
        }
        menu.add([{
            text: t('reload'),
            iconCls: "pimcore_icon_reload",
            handler: function (tree) {
                tree.getStore().reload();
            }.bind(this, tree)
        }]);

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    publishDocument: function (tree, data, task) {
        var id = data.id;
        var type = data.type;

        var parameters = {};
        parameters.id = id;

        Ext.Ajax.request({
            url: '/admin/' + type + '/save?task=' + task,
            method: "PUT",
            params: parameters,
            success: function (task, response) {
                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        var options = {
                            elementType: "document",
                            id: data.id,
                            published: task != "unpublish"
                        };
                        pimcore.elementservice.setElementPublishedState(options);
                        pimcore.elementservice.setElementToolbarButtons(options);
                        pimcore.elementservice.reloadVersions(options);
                        tree.getStore().reload();

                        pimcore.helpers.showNotification(t("success"), t("successful_" + task + "_document"),
                            "success");
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_" + task + "_document"),
                            "error", t(rdata.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("error_" + task + "_document"), "error");
                }

            }.bind(this, task)
        });

    },

    createTranslation: function(record, column, inheritance) {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_document_translationdetermineparent'),
            params: {
                id: record.data.id,
                language: column.dataIndex
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                if(!res.success) {
                    Ext.MessageBox.alert(t("error"), t("document_translation_parent_not_found"));
                } else {

                    var pageForm = new Ext.form.FormPanel({
                        border: false,
                        defaults: {
                            labelWidth: 170
                        },
                        items: [{
                            xtype: "textfield",
                            width: "100%",
                            fieldLabel: t('key'),
                            itemId: "key",
                            name: 'key',
                            enableKeyEvents: true,
                            listeners: {
                                keyup: function (el) {
                                    pageForm.getComponent("name").setValue(el.getValue());
                                }
                            }
                        },{
                            xtype: "textfield",
                            itemId: "name",
                            fieldLabel: t('navigation'),
                            name: 'name',
                            width: "100%"
                        },{
                            xtype: "textfield",
                            itemId: "title",
                            fieldLabel: t('title'),
                            name: 'title',
                            width: "100%"
                        }]
                    });

                    var win = new Ext.Window({
                        modal: true,
                        width: 600,
                        bodyStyle: "padding:10px",
                        items: [pageForm],
                        title: t(inheritance ? "create_translation_inheritance" : "create_translation"),
                        buttons: [{
                            text: t("cancel"),
                            iconCls: "pimcore_icon_cancel",
                            handler: function () {
                                win.close();
                            }
                        }, {
                            text: t("apply"),
                            iconCls: "pimcore_icon_apply",
                            handler: function () {

                                var params = pageForm.getForm().getFieldValues();
                                win.disable();

                                Ext.Ajax.request({
                                    url: Routing.generate('pimcore_admin_element_getsubtype'),
                                    params: {
                                        id: res.targetId,
                                        type: "document"
                                    },
                                    success: function (response) {
                                        var resData = Ext.decode(response.responseText);
                                        if(resData.success) {
                                            if(params["key"].length >= 1) {
                                                params["parentId"] = resData["id"];
                                                params["type"] = record.data.type;
                                                params["translationsBaseDocument"] = record.data.id;
                                                params["language"] = column.dataIndex;
                                                if(inheritance) {
                                                    params["inheritanceSource"] = record.data.id;
                                                }

                                                Ext.Ajax.request({
                                                    url: Routing.generate('pimcore_admin_document_document_add'),
                                                    method: 'POST',
                                                    params: params,
                                                    success: function (response) {
                                                        response = Ext.decode(response.responseText);
                                                        if (response && response.success) {
                                                            pimcore.helpers.openDocument(response.id, response.type);

                                                            win.close();
                                                            this.hideWindow();
                                                        } else {
                                                            win.enable();
                                                            Ext.MessageBox.alert(t("error"), response.message);
                                                        }
                                                    }.bind(this)
                                                });
                                            } else {
                                                win.enable();
                                            }
                                        } else {
                                            Ext.MessageBox.alert(t("error"), t("element_not_found"));
                                        }
                                    }.bind(this)
                                });
                            }.bind(this)
                        }]
                    });

                    win.show();
                }
            }.bind(this)
        });
    }

});
