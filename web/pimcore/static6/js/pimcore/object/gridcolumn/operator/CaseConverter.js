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


pimcore.registerNS("pimcore.object.gridcolumn.operator.caseconverter");

pimcore.object.gridcolumn.operator.caseconverter = Class.create(pimcore.object.gridcolumn.operator.Text, {
    type: "operator",
    class: "CaseConverter",
    iconCls: "pimcore_icon_operator_caseconverter",
    defaultText: "operator_caseconverter",

    getConfigTreeNode: function (configAttributes) {
        if (configAttributes) {
            var node = {
                draggable: true,
                iconCls: this.iconCls,
                text: configAttributes.label,
                configAttributes: configAttributes,
                isTarget: true,
                allowChildren: true,
                expanded: true,
                leaf: false,
                expandable: false,
                isChildAllowed: this.allowChild
            };
        } else {

            //For building up operator list
            var configAttributes = {type: this.type, class: this.class, capitalization: 0};

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
            configAttributes: {
                label: source.data.text,
                type: this.type,
                class: this.class

            },
            isChildAllowed: this.allowChild
        });

        return copy;
    },


    getConfigDialog: function (node) {
        this.node = node;

        this.textfield = new Ext.form.TextField({
            fieldLabel: t('label'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.label
        });

        var capitalization = this.node.data.configAttributes.capitalization;

        this.caseField = new Ext.form.RadioGroup({
            xtype: 'radiogroup',
            fieldLabel: t('capitalization'),
            border: true,
            columns: 1,
            vertical: true,
            items: [
                {boxLabel: t('upper'), name: 'rb', inputValue: '1', checked: capitalization == 1},
                {boxLabel: t('disabled'), name: 'rb', inputValue: '0', checked: capitalization != 1 && capitalization != -1},
                {boxLabel: t('lower'), name: 'rb', inputValue: '-1', checked: capitalization == -1}
            ]
        });

        this.configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [this.textfield, this.caseField],
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
            title: t('caseconverter_operator_settings'),
            layout: "fit",
            items: [this.configPanel]
        });

        this.window.show();
        return this.window;
    },

    commitData: function () {
        this.node.data.configAttributes.label = this.textfield.getValue();
        this.node.set('text', this.textfield.getValue());
        this.node.set('isOperator', true);

        this.node.data.configAttributes.capitalization = parseInt(this.caseField.getValue().rb);
        this.window.close();
    },

    allowChild: function (targetNode, dropNode) {
        if (targetNode.childNodes.length > 0) {
            return false;
        }
        return true;
    }
});