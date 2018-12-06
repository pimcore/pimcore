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


pimcore.registerNS("pimcore.object.gridcolumn.operator.trimmer");

pimcore.object.gridcolumn.operator.trimmer = Class.create(pimcore.object.gridcolumn.operator.text, {
    operatorGroup: "transformer",
    type: "operator",
    class: "Trimmer",
    iconCls: "pimcore_icon_operator_trimmer",
    defaultText: "Trimmer",
    group: "string",

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
            var configAttributes = {type: this.type, class: this.class, trim: 0};

            var node = {
                draggable: true,
                iconCls: this.iconCls,
                text: this.getDefaultText(),
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


    getConfigDialog: function (node, params) {
        this.node = node;

        this.textfield = new Ext.form.TextField({
            fieldLabel: t('label'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.label
        });

        var trim = this.node.data.configAttributes.trim;

        this.trimField = new Ext.form.RadioGroup({
            xtype: 'radiogroup',
            fieldLabel: t('trim'),
            border: true,
            columns: 1,
            vertical: true,
            items: [
                {boxLabel: t('left'), name: 'rb', inputValue: '1', checked: trim == 1},
                {boxLabel: t('right'), name: 'rb', inputValue: '2', checked: trim == 2},
                {boxLabel: t('both'), name: 'rb', inputValue: '2', checked: trim == 3},
                {boxLabel: t('disabled'), name: 'rb', inputValue: '0', checked: isNaN(trim) || trim == 0}
            ]
        });

        this.configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [this.textfield, this.trimField],
            buttons: [{
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.commitData(params);
                }.bind(this)
            }]
        });

        this.window = new Ext.Window({
            width: 400,
            height: 300,
            modal: true,
            title: t('settings'),
            layout: "fit",
            items: [this.configPanel]
        });

        this.window.show();
        return this.window;
    },

    commitData: function (params) {
        this.node.data.configAttributes.label = this.textfield.getValue();
        this.node.set('text', this.textfield.getValue());
        this.node.set('isOperator', true);

        this.node.data.configAttributes.trim = parseInt(this.trimField.getValue().rb);
        this.window.close();
        if (params && params.callback) {
            params.callback();
        }
    },

    allowChild: function (targetNode, dropNode) {
        if (targetNode.childNodes.length > 0) {
            return false;
        }
        return true;
    }
});