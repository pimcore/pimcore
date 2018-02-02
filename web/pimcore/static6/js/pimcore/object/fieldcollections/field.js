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

pimcore.registerNS("pimcore.object.fieldcollections.field");
pimcore.object.fieldcollections.field = Class.create(pimcore.object.classes.klass, {

    allowedInType: 'fieldcollection',
    disallowedDataTypes: ["nonownerobjects", "user", "fieldcollections", "localizedfields", "objectbricks",
        "objectsMetadata"],
    uploadUrl: '/admin/class/import-fieldcollection',
    exportUrl: "/admin/class/export-fieldcollection",
    context: "fieldcollection",

    getId: function () {
        return this.data.key;
    },

    getRootPanel: function () {

        this.usagesStore = new Ext.data.ArrayStore({
            proxy: {
                url: '/admin/class/get-fieldcollection-usages',
                type: 'ajax',
                reader: {
                    type: 'json'
                },
                extraParams: {
                    key: this.data.key
                }
            },
            fields: ["class", "field"]
        });

        var usagesGrid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.usagesStore,
            columnLines: true,
            stripeRows: true,
            plugins: ['gridfilters'],
            width: 600,
            columns: [
                {text: t('class'), sortable: true, dataIndex: 'class', filter: 'string', flex: 1},
                {text: t('field'), sortable: true, dataIndex: 'field', filter: 'string', flex: 1}
            ],
            viewConfig: {
                forceFit: true
            }
        });

        this.rootPanel = new Ext.form.FormPanel({
            title: t("basic_configuration"),
            bodyStyle: "padding: 10px;",
            items: [{
                xtype: "textfield",
                width: 400,
                name: "parentClass",
                fieldLabel: t("parent_class"),
                value: this.data.parentClass
            }, {
                xtype: 'displayfield',
                text: '<b>' + t("used_by_class") + '</b>'
            },
                usagesGrid]
        });

        this.rootPanel.on("afterrender", function() {
            this.usagesStore.reload()
        }.bind(this));

        return this.rootPanel;
    },

    save: function () {

        this.saveCurrentNode();

        var m = Ext.encode(this.getData());
        var n = Ext.encode(this.data);

        if (this.getDataSuccess) {
            Ext.Ajax.request({
                url: "/admin/class/fieldcollection-update",
                method: "post",
                params: {
                    configuration: m,
                    values: n,
                    key: this.data.key
                },
                success: this.saveOnComplete.bind(this)
            });
        }
    },

    saveOnComplete: function (response) {
        try {
            var res = Ext.decode(response.responseText);
            if (res.success) {
                this.parentPanel.tree.getStore().load();
                pimcore.helpers.showNotification(t("success"), t("fieldcollection_saved_successfully"), "success");
            } else {
                throw "save was not successful, see log files in /var/logs";
            }
        } catch (e) {
            this.saveOnError();
        }
    },

    saveOnError: function () {
        pimcore.helpers.showNotification(t("error"), t("definition_save_error"), "error");
    },

    upload: function () {

        pimcore.helpers.uploadDialog(this.getUploadUrl(), "Filedata", function () {
            Ext.Ajax.request({
                url: "/admin/class/fieldcollection-get",
                params: {
                    id: this.getId()
                },
                success: function (response) {
                    this.data = Ext.decode(response.responseText);
                    this.parentPanel.getEditPanel().removeAll();
                    this.addLayout();
                    this.initLayoutFields();
                    pimcore.layout.refresh();
                }.bind(this)
            });
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    }


});