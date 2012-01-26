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

pimcore.registerNS("pimcore.settings.robotstxt");
pimcore.settings.robotstxt = Class.create({

    initialize: function(id) {

        this.getTabPanel();

        Ext.Ajax.request({
            url: "/admin/settings/robots-txt",
            method: "get",
            params: {},
            success: function (response) {

                try {
                    var data = Ext.decode(response.responseText);
                    if(data.success) {
                        this.data = data;
                        this.getTabPanel().add(this.getEditPanel());
                        this.getTabPanel().doLayout();
                    }
                } catch (e) {

                }
            }.bind(this)
        });
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_robotstxt");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_robotstxt",
                title: "robots.txt",
                iconCls: "pimcore_icon_robots",
                border: false,
                layout: "fit",
                closable:true
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_robotstxt");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("robotstxt");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getEditPanel: function () {

        if (!this.editPanel) {

            if(this.data.onFileSystem) {
                this.editArea = new Ext.Panel({
                    bodyStyle: "padding:50px;",
                    html: t("robots_txt_exists_on_filesystem")
                });
            } else {
                this.editArea = new Ext.form.TextArea({
                    xtype: "textarea",
                    name: "data",
                    value: this.data.data,
                    style: "font-family: 'Courier New', Courier, monospace;"
                });
            }

            this.editPanel = new Ext.Panel({
                bodyStyle: "padding: 10px;",
                items: [this.editArea],
                buttons: [{
                    text: t("save"),
                    iconCls: "pimcore_icon_apply",
                    handler: this.save.bind(this)
                }]
            });
            this.editPanel.on("bodyresize", function (el, width, height) {
                this.editArea.setWidth(width-20);
                this.editArea.setHeight(height-20);
            }.bind(this));
        }

        return this.editPanel;
    },


    save : function () {

        Ext.Ajax.request({
            url: "/admin/settings/robots-txt",
            method: "post",
            params: {
                data: this.editArea.getValue()
            },
            success: function (response) {

                try {
                    var data = Ext.decode(response.responseText);
                    if(data.success) {
                        pimcore.helpers.showNotification(t("success"), t("save_success"), "success");
                    } else {
                        throw "save error";
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("save_error"), "error");
                }
            }.bind(this)
        });
    }
});

