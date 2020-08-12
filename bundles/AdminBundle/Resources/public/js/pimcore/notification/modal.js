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
pimcore.registerNS("pimcore.notification.modal");

pimcore.notification.modal = Class.create({

    initialize: function (elementData) {
        this.elementData = {};

        this.getWindow().show();
        if(elementData) {
            this.addDataBySharedElementData(elementData);
        }
    },

    getWindow: function () {
        if (!this.window) {
            var recipientStore = Ext.create("Ext.data.JsonStore", {
                proxy: {
                    type: "ajax",
                    url: Routing.generate('pimcore_admin_notification_recipients')
                }
            });
            recipientStore.load();

            var href = {
                name: 'element',
                disabled: true
            };

            this.component = new Ext.form.TextField(href);

            this.component.on("render", function (el) {
                new Ext.dd.DropZone(el.getEl(), {
                    reference: this,
                    ddGroup: "element",
                    getTargetFromEvent: function (e) {
                        return this.reference.component.getEl();
                    },
                    onNodeDrop: this.onNodeDrop.bind(this)
                });

                el.getEl().on("contextmenu", this.onContextMenu.bind(this));

            }.bind(this));

            var elementItems = [
                this.component,
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    style: "margin-left: 5px",
                    handler: this.openSearchEditor.bind(this)
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    style: "margin-left: 5px",
                    handler: this.empty.bind(this)
                }
            ];
            var elementContainer = Ext.create('Ext.form.FieldContainer', {
                fieldLabel: t("attachment"),
                labelWidth: 100,
                layout: 'hbox',
                items: elementItems,
                componentCls: "object_field",
                border: false,
                style: {
                    padding: 0
                }
            });

            var items = [
            {
                xtype: "combobox",
                name: "recipientId",
                fieldLabel: t("recipient"),
                width: "100%",
                forceSelection: true,
                queryMode: "local",
                anyMatch: true,
                store: recipientStore,
                valueField: "id",
                displayField: "text",
                allowBlank: false,
                blankText: t("this_field_is_required"),
                msgTarget: "under"
            },
            {
                xtype: "textfield",
                name: "title",
                fieldLabel: t("title"),
                width: "100%",
                allowBlank: false,
                blankText: t("this_field_is_required"),
                msgTarget: "under"
            },
            {
                xtype: "textareafield",
                name: "message",
                fieldLabel: t("message"),
                width: "100%",
                allowBlank: false,
                blankText: t("this_field_is_required"),
                msgTarget: "under"
            }, elementContainer];

            var panel = new Ext.form.FormPanel({
                border: false,
                frame: false,
                bodyStyle: "padding:10px",
                url: Routing.generate('pimcore_admin_notification_send'),
                items: items,
                defaults: {labelWidth: 100},
                collapsible: false,
                autoScroll: true,
                buttons: [
                    {
                        text: t("send"),
                        iconCls: "pimcore_icon_accept",
                        formBind: true,
                        handler: this.send.bind(this)
                    },
                    {
                        text: t("close"),
                        iconCls: "pimcore_icon_cancel",
                        handler: this.close.bind(this)
                    }
                ]
            });

            this.window = new Ext.Window({
                width: 560,
                iconCls: "pimcore_icon_sms",
                title: t("notifications_send"),
                layout: "fit",
                closeAction: "close",
                plain: true,
                autoScroll: true,
                modal: false
            });

            this.window.add(panel);
        }

        return this.window;
    },

    empty: function () {
        this.elementData = {};
        this.dataChanged = true;
        this.component.setValue("");
    },

    onContextMenu: function (e) {
        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('empty'),
            iconCls: "pimcore_icon_delete",
            handler: function (item) {
                item.parentMenu.destroy();

                this.empty();
            }.bind(this)
        }));

        menu.add(new Ext.menu.Item({
            text: t('search'),
            iconCls: "pimcore_icon_search",
            handler: function (item) {
                item.parentMenu.destroy();
                this.openSearchEditor();
            }.bind(this)
        }));

        menu.add(new Ext.menu.Item({
            text: t('upload'),
            cls: "pimcore_inline_upload",
            iconCls: "pimcore_icon_upload",
            handler: function (item) {
                item.parentMenu.destroy();
                this.uploadDialog();
            }.bind(this)
        }));

        menu.showAt(e.getXY());

        e.stopEvent();
    },

    onNodeDrop: function (target, dd, e, data) {
        var record = data.records[0];
        var data = record.data;

        this.elementData.id = data.id;
        this.elementData.type = data.elementType;
        this.elementData.subtype = data.type;
        this.elementData.path = data.path;
        this.dataChanged = true;
        this.component.removeCls("strikeThrough");
        if (data.published === false) {
            this.component.addCls("strikeThrough");
        }
        this.component.setValue(data.path);

        return true;
    },

    addDataFromSelector: function (data) {
        this.elementData.id = data.id;
        this.elementData.type = data.type;
        this.elementData.subtype = data.subtype;
        this.dataChanged = true;
        this.component.removeCls("strikeThrough");
        if (data.published === false) {
            this.component.addCls("strikeThrough");
        }
        this.component.setValue(data.fullpath);
    },

    addDataBySharedElementData: function (elementData) {
        this.elementData = elementData;
        this.dataChanged = true;
        this.component.removeCls("strikeThrough");
        if (elementData.published === false) {
            this.component.addCls("strikeThrough");
        }
        this.component.setValue(elementData.path);
    },

    openSearchEditor: function () {
        var allowedTypes = ['object', 'asset', 'document'];
        var allowedSpecific = {};
        var allowedSubtypes = {};

        allowedSubtypes.object = ["object", "variant"];

        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: allowedTypes,
            subtype: allowedSubtypes,
            specific: allowedSpecific
        }, {
            context: Ext.apply({scope: "objectEditor"}, this.context)
        });
    },

    send: function () {
        var form = this.getWindow().down("form").getForm();
        var params = {}
        if (this.elementData) {
            params = {
                'elementId': this.elementData.id,
                'elementType': this.elementData.type
            }
        }
        if (form.isValid()) {
            this.getWindow().hide();
            form.submit({
                params: params,
                success: this.onSuccess.bind(this),
                failure: this.onFailure.bind(this)
            });
        }
    },

    close: function () {
        this.getWindow().hide();
        this.getWindow().destroy();
    },

    onSuccess: function (form, result, data) {
        pimcore.helpers.showNotification(t("success"), t("notification_has_been_sent"), "success");
        this.getWindow().destroy();
    },

    onFailure: function (form, result) {
        this.getWindow().destroy();
    }
});
