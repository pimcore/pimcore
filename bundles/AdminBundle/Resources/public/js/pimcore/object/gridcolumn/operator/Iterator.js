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


pimcore.registerNS("pimcore.object.gridcolumn.operator.iterator");

pimcore.object.gridcolumn.operator.iterator = Class.create(pimcore.object.gridcolumn.operator.text, {
    operatorGroup: "transformer",
    type: "operator",
    class: "Iterator",
    iconCls: "pimcore_icon_operator_iterator",
    defaultText: "Iterator",
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
            var configAttributes = {type: this.type, class: this.class, capitalization: 0};

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


        this.configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [this.textfield],
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
            title: this.getDefaultText(),
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