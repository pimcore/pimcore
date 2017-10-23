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

pimcore.registerNS("pimcore.report.settings");
pimcore.report.settings = Class.create({

    initialize: function () {

        this.getData();
    },

    getData: function () {
        Ext.Ajax.request({
            url: "/admin/reports/settings/get",
            success: function (response) {

                this.data = Ext.decode(response.responseText);
                this.getTabPanel();

            }.bind(this)
        });
    },

    getValue: function (key) {

        var nk = key.split("\.");
        var current = this.data.values;

        for (var i = 0; i < nk.length; i++) {
            if (current[nk[i]] || 'boolean' === typeof current[nk[i]]) {
                current = current[nk[i]];
            }
        }

        if (typeof current != "object" && typeof current != "array" && typeof current != "function") {
            return current;
        }

        return "";
    },

    getTabPanel: function () {

        this.moduleSettings = [];

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_reports_settings",
                title: t("report_settings"),
                iconCls: "pimcore_icon_reports",
                border: false,
                layout: "fit",
                closable:true,
                bodyStyle: "padding: 10px;"

            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_reports_settings");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("reports_settings");
            }.bind(this));

            try {
                var broker = pimcore.report.settings.broker;
                var settingsContainerItems = [];
                var moduleSetting,moduleClass;

                for (var i = 0; i < broker.length; i++) {

                    moduleClass = eval(broker[i]);
                    moduleSetting = new moduleClass(this);

                    settingsContainerItems.push(moduleSetting.getLayout());
                    this.moduleSettings.push(moduleSetting);
                }

                this.settingsContainer = new Ext.TabPanel({
                    activeTab: 0,
                    deferredRender:false,
                    enableTabScroll:true,
                    items: settingsContainerItems,
                    buttons: [
                        {
                            text: t("save"),
                            handler: this.save.bind(this),
                            iconCls: "pimcore_icon_accept"
                        }
                    ]
                });

                this.panel.add(this.settingsContainer);

                this.panel.updateLayout();
                pimcore.layout.refresh();
            }
            catch (e) {
                console.log(e);
            }
        }

        return this.panel;
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_reports_settings");
    },

    save: function () {
        var values = {};

        for (var i = 0; i < this.moduleSettings.length; i++) {
            try {
                values[this.moduleSettings[i].getKey()] = this.moduleSettings[i].getValues();
            }
            catch (e) {
                console.log("unable to get configuration for report");
                console.log(e);
            }
        }

        Ext.Ajax.request({
            url: "/admin/reports/settings/save",
            method: "post",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {
                try{
                    var res = Ext.decode(response.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("system_settings_save_success"), "success");

                        Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                            if (buttonValue == "yes") {
                                window.location.reload();
                            }
                        }.bind(this));
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("system_settings_save_error"),
                                                                                        "error",t(res.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("system_settings_save_error"), "error");
                }
            }.bind(this)
        });
    }

});

pimcore.report.settings.broker = [];
