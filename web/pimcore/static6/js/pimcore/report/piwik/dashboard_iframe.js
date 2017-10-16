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

pimcore.registerNS("pimcore.report.piwik.dashboard_iframe");
pimcore.report.piwik.dashboard_iframe = Class.create(pimcore.report.abstract, {
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
        var that = this;

        this.panelId = 'report_piwik_dashboard_' + this.config.id;
        this.iframeId = this.panelId + '_iframe';
        this.toolbarId = this.panelId + '_toolbar';

        var panel = new Ext.Panel({
            title: this.config.title,
            id: this.panelId,
            layout: "fit",
            border: false,
            bodyStyle: 'padding: 5px; 10px',
            items: [],
            tbar: Ext.create('Ext.Toolbar', {
                id: this.toolbarId,
                cls: 'main-toolbar',
                items: [{
                    text: t("reload"),
                    iconCls: "pimcore_icon_reload",
                    handler: this.reloadFrame.bind(this)
                }, {
                    text: t("open"),
                    iconCls: "pimcore_icon_open",
                    handler: this.openWindow.bind(this)
                }]
            })
        });

        this.loadMask = new Ext.LoadMask({
            target: panel,
            msg: t("please_wait")
        });

        panel.on("afterrender", function (panel) {
            that.loadMask.show();
        }.bind(this));

        this.getReportConfig().then(function(config) {
            var iframe = new Ext.Component({
                id: that.iframeId,
                autoEl: {
                    tag: 'iframe',
                    src: config.url,
                    frameborder: 0
                }
            });

            panel.add(iframe);

            iframe.el.dom.onload = function() {
                that.loadMask.hide();
            };
        });

        return panel;
    },

    reloadFrame: function() {
        var that = this;

        this.loadMask.show();

        this.getReportConfig().then(function(config) {
            Ext.get(that.iframeId).dom.src = config.url;
        });
    },

    openWindow: function() {
        var that = this;

        this.getReportConfig().then(function(config) {
            window.open(config.url);
        });
    },

    /**
     * @returns {Ext.Promise}
     */
    getReportConfig: function() {
        var that = this;

        if (!this.configPromise) {
            this.configPromise = new Ext.Promise(function (resolve, reject) {
                Ext.Ajax.request({
                    url: '/admin/reports/piwik/reports/' + that.config.id,
                    success: function (response) {
                        resolve(Ext.decode(response.responseText));
                    }
                });
            });
        }

        return this.configPromise;
    }
});

if ('undefined' !== typeof pimcore.settings.piwik && 'undefined' !== typeof pimcore.settings.piwik.reports) {
    Ext.Object.each(pimcore.settings.piwik.reports, function (reportId, reportConfig) {
        reportConfig.text = reportConfig.title;

        // add to report broker
        pimcore.report.broker.addGroup("piwik", "Piwik", "pimcore_icon_analytics");
        pimcore.report.broker.addReport(pimcore.report.piwik.dashboard_iframe, "piwik", reportConfig);
    });
}
