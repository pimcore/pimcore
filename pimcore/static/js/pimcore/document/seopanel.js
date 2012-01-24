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
                title: t("document_seo_grid"),
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
            header: t("description"),
            dataIndex: 'description',
            width: 400
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

    onRightClick: function (node) {
        node.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('reload'),
            iconCls: "pimcore_icon_reload",
            handler: function (node) {
                node.reload();
            }.bind(this, node)
        }));
        menu.show(node.ui.getAnchor());
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
                fieldLabel: t("title"),
                name: "title",
                value: node.attributes.title,
                width: 350
            }, {
                xtype: "textarea",
                fieldLabel: t("description"),
                name: "description",
                value: node.attributes.description,
                width: 350
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