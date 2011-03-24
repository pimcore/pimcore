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

pimcore.registerNS("pimcore.settings.pluginupdate");
pimcore.settings.pluginupdate = Class.create({

    initialize: function (plugin, revision, host, overview, index) {

        this.plugin = plugin;
        this.revision = revision;
        this.host = host;
        this.pluginoverview = overview;
        this.pluginindex = index;

        this.window = new Ext.Window({
            layout:'fit',
            width:500,
            height:300,
            closeAction:'close',
            modal: true
        });

        pimcore.viewport.add(this.window);

        this.window.show();

        this.window.add(new Ext.Panel({
            title: t('please_wait'),
            bodyStyle: "padding: 20px;",
            html: t('looking_for_updates')
        }));
        this.window.doLayout();

        Ext.Ajax.request({
            url: "/admin/plugin/update/get-updates",
            params: {
                plugin: plugin,
                revision: revision,
                host: host
            },
            success: this.selectUpdate.bind(this)
        });
    },

    selectUpdate: function (response) {

        this.window.removeAll();

        try {
            var availableUpdates = Ext.decode(response.responseText);
        }
        catch (e) {
            this.window.add(new Ext.Panel({
                title: "ERROR",
                bodyStyle: "padding: 20px;",
                html: t('plugin_update_error') + "<br /><br />" + response.responseText
            }));
            this.window.doLayout();
        }


        // no updates available
        if (availableUpdates.revisions.length < 1 && availableUpdates.releases.length < 1) {

            var panel = new Ext.Panel({
                html: t('latest_version_already_installed'),
                bodyStyle: "padding: 20px;"
            });

            this.window.add(panel);
            this.window.doLayout();

            return;
        }

        var panelConfig = {
            title: t('select_plugin_update'),
            items: []
        };

        if (availableUpdates.releases.length > 0) {
            var storeReleases = new Ext.data.JsonStore({
                autoDestroy: true,
                root: 'releases',
                data: availableUpdates,
                idProperty: 'id',
                fields: ["id","date","text","version"]
            });

            panelConfig.items.push({
                xtype: "form",
                bodyStyle: "padding: 10px;",
                title: t('stable_updates'),
                items: [
                    {
                        xtype: "combo",
                        fieldLabel: t('select_update'),
                        name: "update_releases",
                        id: "update_releases",
                        mode: "local",
                        store: storeReleases,
                        triggerAction: "all",
                        displayField: "version",
                        valueField: "id"
                    }
                ],
                bbar: [
                    {
                        xtype: "button",
                        text: t('update'),
                        handler: this.updateStart.bind(this, "update_releases")
                    }
                ]
            });
        }

        if (availableUpdates.revisions.length > 0) {

            var storeRevisions = new Ext.data.JsonStore({
                autoDestroy: true,
                root: 'revisions',
                data: availableUpdates,
                idProperty: 'id',
                fields: ["id","date","text"]
            });

            panelConfig.items.push({
                xtype: "form",
                bodyStyle: "padding: 10px;",
                title: t('non_stable_updates'),
                items: [
                    {
                        xtype: "combo",
                        fieldLabel: t('select_update'),
                        name: "update_revisions",
                        id: "update_revisions",
                        mode: "local",
                        store: storeRevisions,
                        triggerAction: "all",
                        displayField: "text",
                        valueField: "id"
                    }
                ],
                bbar: [
                    {
                        xtype: "button",
                        text: t('update'),
                        handler: this.updateStart.bind(this, "update_revisions")
                    }
                ]
            });
        }

        this.window.add(new Ext.Panel(panelConfig));
        this.window.doLayout();
    },

    updateStart: function (type) {

        var updateId = Ext.getCmp(type).getValue();
        this.updateId = updateId;

        this.window.removeAll();


        var panel = new Ext.Panel({
            title: t('start_update'),
            html: t('downloading_packages'),
            bodyStyle: "padding: 20px;"
        });

        this.window.add(panel);
        this.window.doLayout();

        Ext.Ajax.request({
            url: "/admin/plugin/update/download",
            params: {
                id: this.updateId,
                plugin: this.plugin,
                revision: this.revision,
                host: this.host

            },
            success: this.startInstall.bind(this)
        });
    },

    startInstall: function () {
        this.window.removeAll();

        var panel = new Ext.Panel({
            title: t('start_update'),
            bodyStyle: "padding: 20px;",
            html: t('update_in_progress')
        });

        this.window.add(panel);
        this.window.doLayout();

        Ext.Ajax.request({
            url: "/admin/plugin/update/update",
            params: {
                id: this.updateId,
                plugin: this.plugin
            },
            success: this.complete.bind(this)
        });
    },

    complete: function (response) {

        this.window.removeAll();

        var error = false;
        var status;
        var panel;

        try {
            status = Ext.decode(response.responseText);
        }
        catch (e) {
            status = {
                success: false
            };
        }

        this.pluginoverview.updateAvailablePluginInfo(this.pluginindex);

        if (status.success) {
            panel = new Ext.Panel({
                title: "SUCCESS",
                bodyStyle: "padding: 20px;",
                html: t('update_successful')
            });
        }
        else {
            panel = new Ext.Panel({
                title: "ERROR",
                bodyStyle: "padding: 20px;",
                html: t('update_error') + "<br /><br />" + response.responseText
            });
        }

        this.window.add(panel);
        this.window.doLayout();
    }

});