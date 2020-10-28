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
    disallowedDataTypes: ["reverseManyToManyObjectRelation", "user", "fieldcollections", "localizedfields", "objectbricks",
        "objectsMetadata"],

    uploadRoute: 'pimcore_admin_dataobject_class_importfieldcollection',
    exportRoute: 'pimcore_admin_dataobject_class_exportfieldcollection',

    context: "fieldcollection",

    getId: function () {
        return this.data.key;
    },

    getRootPanel: function () {

        this.usagesStore = new Ext.data.ArrayStore({
            proxy: {
                url: Routing.generate('pimcore_admin_dataobject_class_getfieldcollectionusages'),
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

        this.groupField = new Ext.form.field.Text(
            {
                width: 400,
                name: "group",
                fieldLabel: t("group"),
                value: this.data.group
            });

        this.rootPanel = new Ext.form.FormPanel({
            title: '<b>' + t("general_settings") + '</b>',
            bodyStyle: 'padding: 10px; border-top: 1px solid #606060 !important;',
            defaults: {
                labelWidth: 200
            },
            items: [
                {
                    xtype: "textfield",
                    width: 600,
                    name: "parentClass",
                    fieldLabel: t("parent_php_class"),
                    value: this.data.parentClass
                },
                {
                    xtype: "textfield",
                    width: 600,
                    name: "implementsInterfaces",
                    fieldLabel: t("implements_interfaces"),
                    value: this.data.implementsInterfaces
                },
                {
                    xtype: "textfield",
                    width: 600,
                    name: "title",
                    fieldLabel: t("title"),
                    value: this.data.title
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("generate_type_declarations"),
                    name: "generateTypeDeclarations",
                    checked: this.data.generateTypeDeclarations
                },
                this.groupField,
                {
                    xtype: 'displayfield',
                    text: '<b>' + t("used_by_class") + '</b>'
                },
                usagesGrid]
        });

        this.rootPanel.on("afterrender", function () {
            this.usagesStore.reload()
        }.bind(this));

        return this.rootPanel;
    },

    save: function () {

        var reload = false;
        var newGroup = this.groupField.getValue();
        if (newGroup != this.data.group) {
            this.data.group = newGroup;
            reload = true;
        }


        this.saveCurrentNode();

        var m = Ext.encode(this.getData());
        var n = Ext.encode(this.data);

        if (this.getDataSuccess) {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_class_fieldcollectionupdate'),
                method: 'PUT',
                params: {
                    configuration: m,
                    values: n,
                    key: this.data.key,
                    title: this.data.title,
                    group: this.data.group
                },
                success: this.saveOnComplete.bind(this, reload)
            });
        }
    },

    saveOnComplete: function (reload, response) {
        try {
            var res = Ext.decode(response.responseText);
            if (res.success) {
                if (reload) {
                    this.parentPanel.tree.getStore().load();
                }
                pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
            } else {
                if (res.message) {
                    pimcore.helpers.showNotification(t("error"), res.message, "error");
                } else {
                    throw "save was not successful, see log files in /var/logs";
                }
            }
        } catch (e) {
            this.saveOnError();
        }
    },

    saveOnError: function () {
        pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
    },

    upload: function () {

        pimcore.helpers.uploadDialog(this.getUploadUrl(), "Filedata", function () {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_class_fieldcollectionget'),
                params: {
                    id: this.getId()
                },
                success: function (response) {
                    this.data = Ext.decode(response.responseText);
                    this.parentPanel.getEditPanel().removeAll();
                    this.addTree();
                    this.initLayoutFields();
                    this.addLayout();
                    pimcore.layout.refresh();
                }.bind(this)
            });
        }.bind(this), function () {
            Ext.MessageBox.alert(t("error"), t("error"));
        });
    }


});
