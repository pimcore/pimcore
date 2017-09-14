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

pimcore.registerNS("pimcore.object.tags.link");
pimcore.object.tags.link = Class.create(pimcore.object.tags.abstract, {

    type: "link",
    dirty: false,

    initialize: function (data, fieldConfig) {

        this.data = "";
        this.defaultData = {
            type: "internal",
            path: "",
            parameters: "",
            anchor: "",
            accesskey: "",
            rel: "",
            tabindex: "",
            target: ""
        };

        if (data) {
            this.data = data;
        }
        else {
            this.data = this.defaultData;
        }
        this.fieldConfig = fieldConfig;

    },

    getGridColumnConfig: function (field) {
        var renderer = function (key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            if (record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.tdCls += " grid_value_inherited";
            }
            if (value) {
                return value.text;
            }
            return t("empty");

        }.bind(this, field.key);

        return {
            header: ts(field.label), sortable: true, dataIndex: field.key, renderer: renderer,
            getEditor: this.getWindowCellEditor.bind(this, field)
        };
    },

    getLayoutEdit: function () {

        var input = {
            name: this.fieldConfig.name
        };

        this.editButton = new Ext.Button({
            iconCls: "pimcore_icon_link pimcore_icon_overlay_edit",
            style: "margin-left: 5px",
            handler: this.openEditor.bind(this)
        });

        this.openButton = new Ext.Button({
            iconCls: "pimcore_icon_edit",
            style: "margin-left: 5px",
            handler: function() {
                if (this.data && this.data.path) {
                    if (this.data.linktype == "internal") {
                        pimcore.helpers.openElement(this.data.path, this.data.internalType);
                    } else {
                        window.open(this.data.path, "_blank");
                    }
                }
            }.bind(this)
        });

        var text = "[" + t("not_set") + "]";
        if (this.data.text) {
            text = this.data.text;
        } else if (this.data.path) {
            text = this.data.path;
        }


        this.displayField = new Ext.form.DisplayField({
            value: text
        });

        this.component = new Ext.form.FieldContainer({
            fieldLabel: this.fieldConfig.title,
            layout: 'hbox',
            border: false,
            combineErrors: false,
            items: [this.displayField, this.openButton, this.editButton],
            componentCls: "object_field"
        });

        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.editButton.hide();

        return this.component;
    },

    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    openEditor: function () {
        this.window = pimcore.helpers.editmode.openLinkEditPanel(this.data, {
            empty: this.empty.bind(this),
            cancel: this.cancel.bind(this),
            save: this.save.bind(this)
        });
    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
                type: ["asset", "document"]
            },
            {
                context: Ext.apply({scope: "objectEditor"}, this.getContext())
            });
    },

    save: function () {
        var values = this.window.getComponent("form").getForm().getFieldValues();
        if (Ext.encode(values) != Ext.encode(this.data)) {
            this.dirty = true;
        }
        this.data = values;

        var text = "[" + t("not_set") + "]";
        if (this.data.text) {
            text = this.data.text;
        } else if (this.data.path) {
            text = this.data.path;
        }

        this.displayField.setValue(text);

        // close window
        this.window.close();
    },

    empty: function () {

        // close window
        this.window.close();

        this.data = this.defaultData;
        this.dirty = true;

        // set text
        this.displayField.setValue("[" + t("not_set") + "]");
    },

    cancel: function () {
        this.window.close();
    },

    isDirty: function () {
        if (!this.isRendered()) {
            return false;
        }

        return this.dirty;
    },

    getCellEditValue: function () {
        return this.getValue();
    }
});