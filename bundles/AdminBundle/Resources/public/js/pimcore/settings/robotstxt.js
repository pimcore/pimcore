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

pimcore.registerNS("pimcore.settings.robotstxt");
pimcore.settings.robotstxt = Class.create({
    onFileSystem: false,
    data: {},

    initialize: function(id) {
        this.getTabPanel();
        this.load();
    },

    load: function () {
        this.panel.setLoading(true);

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_settings_robotstxtget'),
            success: function (response) {

                try {
                    var data = Ext.decode(response.responseText);
                    if(data.success) {
                        this.data = data.data;
                        this.onFileSystem = data.onFileSystem;

                        this.loadSites();
                    }
                } catch (e) {

                }
            }.bind(this)
        });
    },

    loadSites: function() {
        this.formPanel = new Ext.form.Panel({
            layout: 'fit'
        });

        var items = [];

        pimcore.globalmanager.get("sites").load(function(records) {
            Ext.each(records, function(record) {
                items.push(this.getEditPanel(record))
            }.bind(this));


            var buttons = [];

            if (this.onFileSystem) {
                buttons.push(t("robots_txt_exists_on_filesystem"));
            }

            buttons.push({
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                disabled: this.onFileSystem,
                handler: this.save.bind(this)
            });

            this.formPanel.add({
                xtype: 'tabpanel',
                layout: 'fit',
                items: items,
                buttons: buttons
            });

            this.panel.add(this.formPanel);
            this.panel.setLoading(false);
        }.bind(this));
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_robotstxt");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_robotstxt",
                title: "robots.txt",
                iconCls: "pimcore_icon_robots",
                border: false,
                layout: "fit",
                closable:true,
                items: []
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_robotstxt");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("robotstxt");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getEditPanel: function (siteRecord) {
        var editArea = new Ext.form.TextArea({
            xtype: "textarea",
            name: 'data['+siteRecord.get('id')+']',
            value: this.data.hasOwnProperty(siteRecord.get('id')) ? this.data[siteRecord.getId('id')] : '',
            width: "100%",
            height: "100%",
            style: "font-family: 'Courier New', Courier, monospace;",
            disabled: this.onFileSystem
        });

        var editPanel = new Ext.Panel({
            title: siteRecord.get('domain'),
            layout: 'fit',
            iconCls: 'pimcore_icon_robots',
            bodyStyle: "padding: 10px;",
            items: [editArea]
        });

        editPanel.on("bodyresize", function (el, width, height) {
            editArea.setWidth(width-20);
            editArea.setHeight(height-20);
        });

        return editPanel;
    },


    save : function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_settings_robotstxtput'),
            method: "PUT",
            params: this.formPanel.form.getFieldValues(),
            success: function (response) {
                try {
                    var data = Ext.decode(response.responseText);
                    if(data.success) {
                        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
                    } else {
                        throw "save error";
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
                }
            }.bind(this)
        });
    }
});

