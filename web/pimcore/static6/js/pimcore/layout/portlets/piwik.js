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
    setConfig: function (config) {
        var parsed = {
            site: null,
            widget: null
        };

        try {
            if (config) {
                parsed = JSON.parse(config);
            }
        } catch (e) {
            console.error('Failed to parse Piwik widget config: ', e);
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
        return "pimcore_icon_analytics";
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

        this.layout = Ext.create('Portal.view.Portlet', Object.extend(defaultConf, {
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
        });

        this.renderIframe();
        this.layout.portletId = portletId;

        return this.layout;
    },

    editSettings: function () {
        var config = this.config;

        var siteCombo = new Ext.form.ComboBox({
            xtype: "combo",
            width: 500,
            id: "pimcore_portlet_selected_piwik_site",
            autoSelect: true,
            valueField: "id",
            displayField: "title",
            value: config.site,
            fieldLabel: t("piwik_widget_site"),
            fields: ['id', 'title'],
            triggerAction: "all",
            store: new Ext.data.Store({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/reports/piwik/config/configured-sites',
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                }
            })
        });

        var widgetCombo = new Ext.form.ComboBox({
            xtype: "combo",
            width: 500,
            id: "pimcore_portlet_selected_piwik_widget",
            autoSelect: true,
            valueField: "id",
            displayField: "title",
            value: config.widget,
            fieldLabel: t("piwik_widget_widget"),
            fields: ['id', 'title'],
            triggerAction: "all",
            disabled: true
        });

        siteCombo.getStore().on('load', function() {
            siteCombo.setValue(config.site);
        });

        var siteValueListener = function () {
            var value = this.getValue();

            if (value) {
                var widgetStore = new Ext.data.Store({
                    autoDestroy: true,
                    autoLoad: true,
                    proxy: {
                        type: 'ajax',
                        url: '/admin/reports/piwik/portal-widgets/' + value,
                        reader: {
                            type: 'json',
                            rootProperty: 'data'
                        }
                    },
                    listeners: {
                        load: function () {
                            widgetCombo.setValue(config.widget);
                        }
                    }
                });

                widgetCombo.setStore(widgetStore);
                widgetCombo.enable();
            } else {
                widgetCombo.setValue(null);
                widgetCombo.setStore(null);
                widgetCombo.disable();
            }
        };

        siteCombo.on('select', siteValueListener);
        siteCombo.on('change', siteValueListener);

        var win = new Ext.Window({
            width: 550,
            height: 200,
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
                            xtype: "button",
                            text: t("save"),
                            handler: function () {
                                this.updateSettings(
                                    siteCombo.getValue(),
                                    widgetCombo.getValue()
                                );

                                win.close();
                            }.bind(this)
                        }
                    ]
                }
            ]
        });

        win.show();
    },

    updateSettings: function (site, widget) {
        this.config = {
            site: site,
            widget: widget
        };

        this.loadMask.show();

        Ext.Ajax.request({
            url: "/admin/portal/update-portlet-config",
            method: "POST",
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

        Ext.Ajax.request({
            url: "/admin/reports/piwik/portal-widgets/" + config.site + "/" + config.widget,
            method: "GET",
            success: function (response) {
                var widget = Ext.decode(response.responseText);
                var iframe = new Ext.Component({
                    autoEl: {
                        tag: 'iframe',
                        src: widget.url,
                        frameborder: 0
                    }
                });

                layout.removeAll();
                layout.add(iframe);

                layout.setTitle('Piwik: ' + widget.title);

                iframe.el.dom.onload = function() {
                    that.loadMask.hide();
                };
            },
            error: function () {
                layout.removeAll();
                layout.add(new Ext.Component({
                    html: t('portlet_piwik_error'),
                    padding: 20
                }));

                that.loadMask.hide();
            }
        });
    }
});
