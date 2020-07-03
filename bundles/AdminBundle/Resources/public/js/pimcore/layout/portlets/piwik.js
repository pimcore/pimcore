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

pimcore.registerNS("pimcore.layout.portlets.piwik");
pimcore.layout.portlets.piwik = Class.create(pimcore.layout.portlets.abstract, {

    isAvailable: function() {
        return pimcore.globalmanager.get("user").isAllowed("piwik_reports");
    },

    setConfig: function (config) {
        var parsed = {
            site: null,
            widget: null,
            period: null,
            date: null
        };

        try {
            if (config) {
                parsed = JSON.parse(config);
            }
        } catch (e) {
            console.error('Failed to parse Matomo/Piwik widget config: ', e);
        }

        this.config = parsed;
    },

    getType: function () {
        return "pimcore.layout.portlets.piwik";
    },

    getName: function () {
        return "Piwik";
    },

    getIcon: function () {
        return "pimcore_icon_piwik";
    },

    getLayout: function (portletId) {
        var that = this;

        var defaultConf = this.getDefaultConfig();
        defaultConf.tools = [
            {
                type: 'gear',
                handler: this.editSettings.bind(this)
            },
            {
                type: 'close',
                handler: this.remove.bind(this)
            }
        ];

        this.layout = Ext.create('Portal.view.Portlet', Object.assign(defaultConf, {
            title: this.getName(),
            iconCls: this.getIcon(),
            height: 275,
            layout: "fit",
            items: []
        }));

        this.loadMask = new Ext.LoadMask({
            target: this.layout,
            msg: t("please_wait")
        });

        this.layout.on("afterrender", function () {
            that.loadMask.show();
            that.renderIframe();
        });

        this.layout.on("destroy", function() {
            that.loadMask.destroy();
        });

        this.layout.portletId = portletId;

        return this.layout;
    },

    editSettings: function () {
        var config = this.config || {};

        var siteCombo = new Ext.form.ComboBox({
            name: "site",
            xtype: "combo",
            width: 500,
            autoSelect: true,
            valueField: "id",
            displayField: "title",
            value: config.site,
            fieldLabel: t("piwik_widget_site"),
            fields: ['id', 'title'],
            mode: "local",
            triggerAction: "all",
            store: pimcore.analytics.piwik.WidgetStoreProvider.getConfiguredSitesStore()
        });

        var widgetCombo = new Ext.form.ComboBox({
            name: "widget",
            xtype: "combo",
            width: 500,
            autoSelect: true,
            valueField: "id",
            displayField: "title",
            value: config.widget,
            fieldLabel: t("piwik_widget_widget"),
            fields: ['id', 'title'],
            mode: "local",
            triggerAction: "all",
            disabled: true
        });

        var siteValueListener = function () {
            var value = siteCombo.getValue();

            if (value) {
                widgetCombo.setStore(pimcore.analytics.piwik.WidgetStoreProvider.getPortalWidgetsStore(value));
                widgetCombo.enable();
            } else {
                widgetCombo.setValue(null);
                widgetCombo.setStore(null);
                widgetCombo.disable();
            }
        };

        siteCombo.on('select', siteValueListener);
        siteCombo.on('change', siteValueListener);
        siteValueListener();

        var win = new Ext.Window({
            width: 550,
            height: 280,
            modal: true,
            title: t('portlet_piwik_widget'),
            closeAction: "destroy",
            items: [
                {
                    xtype: "form",
                    bodyStyle: "padding: 10px",
                    items: [
                        siteCombo,
                        widgetCombo,
                        {
                            name: 'period',
                            fieldLabel: t("piwik_widget_period"),
                            xtype: 'combo',
                            store: [
                                ['day', t('piwik_period_day')],
                                ['week', t('piwik_period_week')],
                                ['month', t('piwik_period_month')],
                                ['year', t('piwik_period_year')]
                            ],
                            mode: 'local',
                            width: 500,
                            value: ('undefined' !== typeof config.period) ? config.period : 'day',
                            editable: false,
                            triggerAction: 'all'
                        },
                        {
                            name: 'date',
                            fieldLabel: t("piwik_widget_date"),
                            xtype: 'combo',
                            store: [
                                ['yesterday', t('piwik_date_yesterday')],
                                ['today', t('piwik_date_today')]
                            ],
                            mode: 'local',
                            width: 500,
                            value: ('undefined' !== typeof config.date) ? config.date : 'yesterday',
                            editable: true,
                            triggerAction: 'all'
                        },
                        {
                            xtype: "button",
                            text: t("save"),
                            handler: function (button) {
                                var form = button.up('form').getForm();
                                this.updateSettings(form.getValues());

                                win.close();
                            }.bind(this)
                        }
                    ]
                }
            ]
        });

        win.show();
    },

    updateSettings: function (data) {
        this.config = data;
        this.loadMask.show();

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_portal_updateportletconfig'),
            method: 'PUT',
            params: {
                key: this.portal.key,
                id: this.layout.portletId,
                config: JSON.stringify(this.config)
            },
            success: function () {
                this.renderIframe();
            }.bind(this),

            failure: function() {
                this.loadMask.hide();
            }.bind(this)
        });
    },

    renderIframe: function () {
        var that = this;
        var config = this.config;
        var layout = this.layout;

        if (!config || !config.site || !config.widget) {
            layout.removeAll();
            layout.add(new Ext.Component({
                html: t('portlet_piwik_unconfigured'),
                padding: 20
            }));

            that.loadMask.hide();

            return;
        }

        var params = {
            period: 'day',
            date: 'yesterday'
        };

        if (config.period) {
            params.period = config.period;
        }

        if (config.date) {
            params.date = config.date;
        }

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_reports_piwik_portalwidget', {configKey: config.site, widgetId: config.widget}),
            params: params,
            method: "GET",
            ignoreErrors: true, // do not pop up error window on failure
            success: function (response) {
                var widget = Ext.decode(response.responseText);
                var iframe = new Ext.Component({
                    autoEl: {
                        tag: 'iframe',
                        src: widget.url,
                        frameborder: 0
                    }
                });

                var title = 'Matomo/Piwik: ' + widget.title;
                title += ' (period: ' + params.period + ', date: ' + params.date + ')';

                layout.removeAll();
                layout.add(iframe);
                layout.setTitle(title);

                iframe.el.dom.onload = function() {
                    that.loadMask.hide();
                };
            },
            failure: function (response) {
                var message = t('portlet_piwik_error');

                try {
                    var json = Ext.decode(response.responseText);
                    if (json && json.message) {
                        message += ' ' + json.message;
                    }
                } catch (e) {}

                layout.removeAll();
                layout.add(new Ext.Component({
                    html: message,
                    padding: 20,
                    style: "color: #ff0000"
                }));

                that.loadMask.hide();
            }
        });
    }
});
