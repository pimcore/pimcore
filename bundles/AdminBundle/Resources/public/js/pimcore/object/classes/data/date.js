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

pimcore.registerNS("pimcore.object.classes.data.date");
pimcore.object.classes.data.date = Class.create(pimcore.object.classes.data.data, {

    type: "date",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore: true,
        block: true,
        encryptedField: true
    },

    initialize: function (treeNode, initData) {
        this.type = "date";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("date");
    },

    getGroup: function () {
        return "date";
    },

    getIconClass: function () {
        return "pimcore_icon_date";
    },

    getLayout: function ($super) {
        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax, false);

        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {
        var defaultDateConfig = {
            fieldLabel: t("default_value"),
            name: "defaultValue",
            cls: "object_field",
            width: 300,
            disabled: datax.useCurrentDate
        };

        if (datax.defaultValue) {
            var tmpDate;
            if (typeof datax.defaultValue === 'object') {
                tmpDate = datax.defaultValue;
            } else {
                tmpDate = new Date(datax.defaultValue * 1000);
            }

            defaultDateConfig.value = tmpDate;
        }

        var defaultDateField = new Ext.form.DateField(defaultDateConfig);

        var specificItems = [
            defaultDateField,
            {
                xtype: 'textfield',
                width: 600,
                fieldLabel: t("default_value_generator"),
                labelWidth: 140,
                name: 'defaultValueGenerator',
                value: datax.defaultValueGenerator
            },
            {
                xtype: "checkbox",
                fieldLabel: t("use_current_date"),
                name: "useCurrentDate",
                checked: datax.useCurrentDate,
                listeners: {
                    change: this.toggleDefaultDate.bind(this, defaultDateField)
                },
                disabled: this.isInCustomLayoutEditor()
            }, {
                xtype: "panel",
                bodyStyle: "padding-top: 3px",
                style: "margin-bottom: 10px",
                html: '<span class="object_field_setting_warning">' + t('inherited_default_value_warning') + '</span>'
            }
        ];

        if (!inEncryptedField) {

            var columnTypeData = [["bigint(20)", "BIGINT"], ["date", "DATE"]];

            var columnTypeField = new Ext.form.ComboBox({
                name: "columnType",
                mode: 'local',
                autoSelect: true,
                forceSelection: true,
                editable: false,
                fieldLabel: t("column_type"),
                value: datax.columnType != "bigint(20)" && datax.columnType != "date" ? 'bigint(20)' : datax.columnType,
                store: new Ext.data.ArrayStore({
                    fields: [
                        'id',
                        'label'
                    ],
                    data: columnTypeData
                }),
                triggerAction: 'all',
                valueField: 'id',
                displayField: 'label'
            });


            specificItems.push(columnTypeField);
        }

        return specificItems;
    },

    toggleDefaultDate: function (defaultDateField, checkbox, checked) {
        if (checked) {
            defaultDateField.setValue(null);
            defaultDateField.setDisabled(true);
        } else {
            defaultDateField.enable();
        }
    },

    applyData: function ($super) {
        $super();
        this.datax.queryColumnType = this.datax.columnType;
    },

    applySpecialData: function (source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax = {};
            }
            Ext.apply(this.datax,
                {
                    defaultValue: source.datax.defaultValue,
                    useCurrentDate: source.datax.useCurrentDate,
                    defaultValueGenerator: source.datax.defaultValueGenerator,
                    columnType: source.datax.columnType
                });
        }
    }

});
