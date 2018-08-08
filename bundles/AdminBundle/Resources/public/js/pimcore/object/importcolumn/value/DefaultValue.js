/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


pimcore.registerNS("pimcore.object.importcolumn.value.defaultvalue");

pimcore.object.importcolumn.value.defaultvalue = Class.create(pimcore.object.importcolumn.Abstract, {

    type: "value",
    class: "DefaultValue",

    getConfigTreeNode: function (configAttributes) {
        var node = {
            draggable: true,
            iconCls: "pimcore_icon_" + configAttributes.dataType,
            text: configAttributes.label,
            qtip: configAttributes.attribute,
            configAttributes: configAttributes,
            isValue: true,
            isTarget: true,
            leaf: true
        };

        return node;
    },

    getCopyNode: function (source) {

        var copy = source.createNode({
            iconCls: source.data.iconCls,
            text: source.data.text,
            isTarget: true,
            leaf: true,
            dataType: source.data.dataType,
            qtip: source.data.key,
            isValue: true,
            configAttributes: {
                label: source.data.text,
                type: this.type,
                class: this.class,
                attribute: source.data.key,
                dataType: source.data.dataType
            }
        });
        return copy;
    },

    getConfigDialog: function (node) {
        this.node = node;

        this.textField = new Ext.form.TextField({
            fieldLabel: t('label'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.label
        });

        this.attributeField = new Ext.form.field.Text({
            value: this.node.data.configAttributes.attribute,
            disabled: true,
            fieldLabel: t('attribute')
        });

        var mode = this.node.data.configAttributes.mode ? this.node.data.configAttributes.mode : "default";

        this.modeField = new Ext.form.RadioGroup({
            xtype: 'radiogroup',
            fieldLabel: t('mode'),
            border: true,
            columns: 1,
            vertical: true,
            items: [
                {boxLabel: t('default'), name: 'mode', inputValue: 'default', checked: mode == "default" },
                {boxLabel: t('direct'), name: 'mode', inputValue: 'direct', checked: mode == "direct"},
            ]
        });

        this.doNotOverwrite = new Ext.form.field.Checkbox(
            {
                fieldLabel: t("do_not_overwrite"),
                inputValue: true,
                name: "doNotOverwrite",
                value: this.node.data.configAttributes.doNotOverwrite
            }
        );

        this.skipEmptyValues = new Ext.form.field.Checkbox(
            {
                fieldLabel: t("skip_empty_values"),
                inputValue: true,
                name: "skipEmptyValues",
                value: this.node.data.configAttributes.skipEmptyValues
            }
        );

        this.configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [this.textField, this.attributeField, this.modeField, this.doNotOverwrite, this.skipEmptyValues],
            buttons: [{
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.commitData();
                }.bind(this)
            }]
        });

        this.window = new Ext.Window({
            width: 400,
            height: 400,
            modal: true,
            title: t('settings'),
            layout: "fit",
            items: [this.configPanel]
        });

        this.window.show();
        return this.window;
    },

    commitData: function () {
        this.node.data.configAttributes.label = this.textField.getValue();
        this.node.data.configAttributes.mode = this.modeField.getValue().mode;
        this.node.data.configAttributes.doNotOverwrite = this.doNotOverwrite.getValue();
        this.node.data.configAttributes.skipEmptyValues = this.skipEmptyValues.getValue();

        var nodeLabel = this.textField.getValue();
        this.node.set('text', nodeLabel);


        this.node.set('isValue', true);
        this.window.close();
    }
});
