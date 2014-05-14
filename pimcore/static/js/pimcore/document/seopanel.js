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

pimcore.registerNS("pimcore.document.seopanel");
pimcore.document.seopanel = Class.create({

    initialize: function () {
        this.getTabPanel();
    },


    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_document_seopanel");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_document_seopanel",
                title: t("seo_document_editor"),
                iconCls: "pimcore_icon_seo_document",
                border: false,
                layout: "fit",
                closable:true,
                items: []
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_document_seopanel");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("document_seopanel");
            }.bind(this));

            pimcore.layout.refresh();

            Ext.Ajax.request({
                url: "/admin/document/seopanel-tree-root",
                success: function (response) {
                    var res = Ext.decode(response.responseText);
                    if(res["id"]) {
                        this.getTreeGrid(res);
                    }
                }.bind(this)
            });
        }

        return this.panel;
    },

    getTreeGrid: function (rootNodeConfig) {


        rootNodeConfig.nodeType = "async";
        rootNodeConfig.text = "home";
        rootNodeConfig.iconCls = "pimcore_icon_home";
        rootNodeConfig.expanded = true;
        rootNodeConfig.attributes = {};

        var columns = [{
            header: t("name"),
            dataIndex: 'text',
            width: 300
        },{
            header: t("title"),
            dataIndex: 'title',
            width: 230
        },{
            header: t("length"),
            dataIndex: 'title_length',
            width: 50
        },{
            header: t("description"),
            dataIndex: 'description',
            width: 400
        },{
            header: t("length"),
            dataIndex: 'description_length',
            width: 50
        },{
            header: "H1",
            dataIndex: 'h1',
            width: 25
        },{
            header: t("h1_text"),
            dataIndex: 'h1_text',
            width: 300
        },{
            header: "H2-5",
            dataIndex: 'hx',
            width: 35
        },{
            header: t("images_with_alt"),
            dataIndex: 'imgwithalt',
            width: 120
        },{
            header: t("images_without_alt"),
            dataIndex: 'imgwithoutalt',
            width: 120
        },{
            header: t("links"),
            dataIndex: 'links',
            width: 50
        },{
            header: t("external_links"),
            dataIndex: 'externallinks',
            width: 90
        }/*,{
            header: '',
            dataIndex: "id",
            width: 50,
            id: "btn_edit",
            tpl: new Ext.XTemplate('{id:this.format}', {
                format: function(value, node) {
                    return value;
                }
            })
        }*/];

        var tree = new Ext.ux.tree.TreeGrid({
            columns: columns,
            useArrows:true,
            enableSort: false,
            animate:true,
            rootVisible: true,
            root: rootNodeConfig,
            border: false,
            loader: new Ext.ux.tree.TreeGridLoader({
                dataUrl: "/admin/document/seopanel-tree",
                requestMethod: "GET",
                baseAttrs: {
                    reference: this,
                    allowDrop: true,
                    allowChildren: true,
                    isTarget: true,
                    nodeType: "async",
                    listeners: {
                        "click": this.openEditPanel.bind(this),
                        "contextmenu": this.onRightClick.bind(this)
                    }
                }
            })
        });

        this.panel.add(tree);
        this.panel.doLayout();
    },

    onRightClick: function (node, event) {
        node.select();

        var menu = new Ext.menu.Menu();
        menu.add([{
            text: t("open"),
            iconCls: "pimcore_icon_edit",
            handler: pimcore.helpers.openDocument.bind(window, node.attributes.id, node.attributes.type)
        },{
            text: t('reload'),
            iconCls: "pimcore_icon_reload",
            handler: function (node) {
                node.reload();
            }.bind(this, node)
        },{
            text: t('open_in_new_window'),
            iconCls: "pimcore_icon_open_in_new_window",
            handler: function (node) {
                window.open(node.attributes.path);
            }.bind(this, node)
        }]);

        menu.showAt(event.getXY());
    },

    openEditPanel: function (node) {

        if(node.attributes.type != "page") {
            return;
        }

        if(this.editWindow) {
            delete this.editWindow;
        }
        if(this.formPanel) {
            delete this.formPanel;
        }

        this.formPanel = new Ext.form.FormPanel({
            bodyStyle: "padding:10px;",
            items: [{
                xtype: "textfield",
                fieldLabel: t("title") + " (" + node.attributes.title.length + ")",
                name: "title",
                value: node.attributes.title,
                width: 350,
                enableKeyEvents: true,
                listeners: {
                    "keyup": function (el) {
                        el.label.update(t("title") + " (" + el.getValue().length + "):");
                    }
                }
            }, {
                xtype: "textarea",
                fieldLabel: t("description") + " (" + node.attributes.description.length + ")",
                name: "description",
                value: node.attributes.description,
                width: 350,
                enableKeyEvents: true,
                listeners: {
                    "keyup": function (el) {
                        el.label.update(t("description") + " (" + el.getValue().length + "):");
                    }
                }
            },{
                xtype: "hidden",
                name: "id",
                value: node.attributes.id
            }]
        });

        this.editWindow = new Ext.Window({
            modal: true,
            width: 500,
            height: 200,
            closable: true,
            items: [this.formPanel],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_accept",
                handler: this.save.bind(this, node)
            }]
        });

        this.editWindow.show();
    },

    save: function (node) {

        var values = this.formPanel.getForm().getFieldValues();
        this.editWindow.close();

        Ext.Ajax.request({
            url: "/admin/document/update/",
            method: "post",
            params: values,
            success: function (node) {
                node.parentNode.reload();
            }.bind(this, node)
        });
    }

});