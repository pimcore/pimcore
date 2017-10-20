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


pimcore.registerNS("pimcore.object.gridcolumn.operator.boolean");

pimcore.object.gridcolumn.operator.boolean = Class.create(pimcore.object.gridcolumn.operator.Abstract, {
    type: "operator",
    class: "Boolean",
    iconCls: "pimcore_icon_operator_boolean",
    defaultText: "operator_boolean",

    getConfigTreeNode: function (configAttributes) {
        if (configAttributes) {
            var nodeLabel = this.getNodeLabel(configAttributes);
            var node = {
                draggable: true,
                iconCls: this.iconCls,
                text: nodeLabel,
                configAttributes: configAttributes,
                isTarget: true,
                isChildAllowed: this.allowChild,
                expanded: true,
                leaf: false,
                expandable: false
            };
        } else {

            //For building up operator list
            var configAttributes = {type: this.type, class: this.class};

            var node = {
                draggable: true,
                iconCls: this.iconCls,
                text: t(this.defaultText),
                configAttributes: configAttributes,
                isTarget: true,
                leaf: true,
                isChildAllowed: this.allowChild
            };
        }
        node.isOperator = true;
        return node;
    },


    getCopyNode: function (source) {
        var copy = source.createNode({
            iconCls: this.iconCls,
            text: source.data.text,
            isTarget: true,
            leaf: false,
            expandable: false,
            isOperator: true,
            isChildAllowed: this.allowChild,
            configAttributes: {
                label: source.data.text,
                type: this.type,
                class: this.class
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

        var operator = this.node.data.configAttributes.operator;
        this.operatorField = new Ext.form.RadioGroup({
            xtype: 'radiogroup',
            fieldLabel: t('operator'),
            border: true,
            columns: 1,
            vertical: true,
            items: [
                {boxLabel: t('and'), name: 'rb', inputValue: 'and', checked: operator == "and"},
                {boxLabel: t('or'), name: 'rb', inputValue: 'or', checked: operator == "or"}
            ]
        });

        this.skipNullField = new Ext.form.Checkbox({
            fieldLabel: t('skip_null'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.skipNull
        });


        this.configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [this.textField, this.skipNullField, this.operatorField],
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
            height: 300,
            modal: true,
            title: t('operator_boolean_settings'),
            layout: "fit",
            items: [this.configPanel]
        });

        this.window.show();
        return this.window;
    },

    commitData: function () {
        this.node.data.configAttributes.label = this.textField.getValue();
        this.node.data.configAttributes.skipNull = this.skipNullField.getValue();
        this.node.data.configAttributes.operator = this.operatorField.getValue().rb;

        var nodeLabel = this.getNodeLabel(this.node.data.configAttributes);
        this.node.set('text', nodeLabel);
        this.node.set('isOperator', true);

        this.window.close();
    },


    getNodeLabel: function(configAttributes) {
        var nodeLabel = configAttributes.label;
        if (configAttributes.locale) {
            nodeLabel += '<span class="pimcore_gridnode_hint"> (' + configAttributes.locale + ')</span>';
        }

        return nodeLabel;
    }
});