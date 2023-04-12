/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.settings.appearance");
/**
 * @private
 */
pimcore.settings.appearance = Class.create({

    initialize: function () {

        this.getData();
    },

    getData: function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_appearance_admin_settings_get'),
            success: function (response) {

                this.data = Ext.decode(response.responseText);
                this.getTabPanel();

            }.bind(this)
        });
    },

    getValue: function (key, ignoreCheck) {

        const nk = key.split("\.");
        let current = this.data.values;

        for (let i = 0; i < nk.length; i++) {
            if (typeof current[nk[i]] != "undefined") {
                current = current[nk[i]];
            } else {
                current = null;
                break;
            }
        }

        if (ignoreCheck || (typeof current != "object" && typeof current != "array" && typeof current != "function")) {
            return current;
        }

        return "";
    },

    getTabPanel: function () {
        let urlToCustomImageField = {};

        if (!this.panel) {
            this.panel = Ext.create('Ext.panel.Panel', {
                id: "pimcore_settings_system_appearance",
                title: t("appearance_and_branding"),
                iconCls: "pimcore_icon_appearance",
                border: false,
                layout: "fit",
                closable: true
            });

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("settings_system_appearance");
            }.bind(this));

            this.layout = Ext.create('Ext.form.Panel', {
                bodyStyle: 'padding:20px 5px 20px 5px;',
                border: false,
                autoScroll: true,
                forceLayout: true,
                defaults: {
                    forceLayout: true
                },
                fieldDefaults: {
                    labelWidth: 250
                },
                buttons: [
                    {
                        text: t("save"),
                        handler: this.save.bind(this),
                        iconCls: "pimcore_icon_apply",
                        disabled: !this.getValue("writeable")
                    }
                ],
                items: [
                    {
                            xtype: 'fieldset',
                            title: t('colors'),
                            collapsible: false,
                            width: "100%",
                            autoHeight: true,
                            items: [{
                                xtype: "container",
                                html: t('color_description'),
                                style: "margin-bottom:10px;"
                            }, {
                                xtype: "textfield",
                                fieldLabel: t('login_screen'),
                                width: 330,
                                value: this.getValue("branding.color_login_screen"),
                                name: 'branding.color_login_screen'
                            }, {
                                xtype: "textfield",
                                fieldLabel: t('admin_interface'),
                                width: 330,
                                value: this.getValue("branding.color_admin_interface"),
                                name: 'branding.color_admin_interface'
                            }, {
                                xtype: "textfield",
                                fieldLabel: t('admin_interface_background'),
                                width: 330,
                                value: this.getValue("branding.color_admin_interface_background"),
                                name: 'branding.color_admin_interface_background'
                            }, {
                                xtype: "checkbox",
                                boxLabel: t('invert_colors_on_login_screen'),
                                width: 330,
                                checked: this.getValue("branding.login_screen_invert_colors"),
                                name: 'branding.login_screen_invert_colors'
                            }]
                        }, {
                            xtype: 'fieldset',
                            title: t('custom_logo'),
                            collapsible: false,
                            width: "100%",
                            autoHeight: true,
                            items: [{
                                xtype: "container",
                                html: t('branding_logo_description'),
                                style: "margin-bottom:10px;"
                            }, {
                                xtype: "container",
                                id: "pimcore_custom_branding_logo",
                                html: '<img src="'+Routing.generate('pimcore_settings_display_custom_logo')+'" />',
                            }, {
                                xtype: "button",
                                text: t("upload"),
                                iconCls: "pimcore_icon_upload",
                                handler: function () {
                                    pimcore.helpers.uploadDialog(Routing.generate('pimcore_admin_settings_uploadcustomlogo'), null,
                                        function () {
                                            const cont = Ext.getCmp("pimcore_custom_branding_logo");
                                            const date = new Date();
                                            cont.update('<img src="'+Routing.generate('pimcore_settings_display_custom_logo', {'_dc': date.getTime()})+'" />');
                                        }.bind(this));
                                }.bind(this),
                                flex: 1
                            }, {
                                xtype: "button",
                                text: t("delete"),
                                iconCls: "pimcore_icon_delete",
                                handler: function () {
                                    Ext.Ajax.request({
                                        url: Routing.generate('pimcore_admin_settings_deletecustomlogo'),
                                        method: "DELETE",
                                        success: function (response) {
                                            const cont = Ext.getCmp("pimcore_custom_branding_logo");
                                            const date = new Date();
                                            cont.update('<img src="' + Routing.generate('pimcore_settings_display_custom_logo', {'_dc': date.getTime()}) + '" />');
                                        }
                                    });
                                }.bind(this),
                                flex: 1
                            }]
                        }, {
                            xtype: 'fieldset',
                            title: t('custom_login_background_image'),
                            collapsible: false,
                            width: "100%",
                            layout: 'hbox',
                            autoHeight: true,
                            items: [{
                                fieldLabel: t("url_to_custom_image_on_login_screen"),
                                xtype: "textfield",
                                name: "branding.login_screen_custom_image",
                                fieldCls: "input_drop_target",
                                width: '95%',
                                value: this.getValue("branding.login_screen_custom_image"),
                                listeners: {
                                    "render": function (el) {
                                        urlToCustomImageField = el;
                                        new Ext.dd.DropZone(el.getEl(), {
                                            reference: this,
                                            ddGroup: "element",
                                            getTargetFromEvent: function (e) {
                                                return this.getEl();
                                            },

                                            onNodeOver: function (target, dd, e, data) {
                                                if (data.records.length === 1 && data.records[0].data.elementType === "asset") {
                                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                                }
                                            },

                                            onNodeDrop: function (target, dd, e, data) {

                                                if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                                    return false;
                                                }

                                                data = data.records[0].data;
                                                if (data.elementType === "asset") {
                                                    this.setValue(data.path);
                                                    return true;
                                                }
                                                return false;
                                            }.bind(this)
                                        });
                                    }
                                },
                            }, {
                                xtype: "button",
                                tooltip: t("delete"),
                                overflowText: t('delete'),
                                iconCls: "pimcore_icon_delete",
                                style: "margin-top: 5px; margin-left: 7px",
                                handler: function () {
                                    urlToCustomImageField.setValue('');
                                }
                            }]
                        }
                ]
            });

            this.panel.add(this.layout);

            const tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem(this.panel);

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    activate: function () {
        const tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_settings_system_appearance");
    },

    save: function () {

        this.layout.mask();

        const values = this.layout.getForm().getFieldValues();

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_settings_appearance_set'),
            method: "PUT",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {

                this.layout.unmask();

                try {
                    const res = Ext.decode(response.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");

                        Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                            if (buttonValue == "yes") {
                                window.location.reload();
                            }
                        }.bind(this));
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("saving_failed"),
                            "error", t(res.message));
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
                }
            }.bind(this)
        });
    }
});
