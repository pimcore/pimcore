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

pimcore.registerNS("pimcore.report.piwik.dashboard.iframe");
pimcore.report.piwik.dashboard.iframe = Class.create(pimcore.report.abstract, {
    matchType: function (type) {
        return !!pimcore.report.abstract.prototype.matchTypeValidate(type, ["global"]);
    },

    getName: function () {
        return "pimcore_report_piwik_dashboard_iframe";
    },

    getIconCls: function () {
        return "pimcore_icon_analytics";
    },

    getPanel: function () {
        var url = this.config.url;

        var panelId = 'report_piwik_dashboard_' + this.config.id;
        var iframeId = panelId + '_iframe';
        var toolbarId = panelId + '_toolbar';

        var panel = new Ext.Panel({
            title: this.config.title,
            id: panelId,
            layout: "fit",
            border: false,
            items: [
                new Ext.Component({
                    id: iframeId,
                    autoEl: {
                        tag: 'iframe',
                        src: url,
                        frameborder: 0
                    }
                })
            ],
            tbar: Ext.create('Ext.Toolbar', {
                id: toolbarId,
                cls: 'main-toolbar',
                items: [{
                    text: t("reload"),
                    iconCls: "pimcore_icon_reload",
                    handler: function () {
                        try {
                            Ext.get(iframeId).dom.src = url;
                        } catch (e) {
                        }
                    }
                }, {
                    text: t("open"),
                    iconCls: "pimcore_icon_open",
                    handler: function () {
                        window.open(url);
                    }
                }]
            })
        });

        return panel;
    }
});

// TODO do this on demand, not when loading the file as this is done on every pimcore admin load
Ext.Ajax.request({
    url: '/admin/reports/piwik/reports',
    success: function (response) {
        var reports = Ext.decode(response.responseText);

        Ext.Array.each(reports, function(report) {
            report.text = report.title;

            // add to report broker
            pimcore.report.broker.addGroup("piwik", "Piwik", "pimcore_icon_analytics");
            pimcore.report.broker.addReport(pimcore.report.piwik.dashboard.iframe, "piwik", report);
        });
    }.bind(this)
});
