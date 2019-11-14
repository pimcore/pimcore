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


pimcore.registerNS("pimcore.object.importcolumn.operator.iterator");

pimcore.object.importcolumn.operator.iterator = Class.create(pimcore.object.gridcolumn.Abstract, {
    type: "operator",
    class: "Iterator",
    iconCls: "pimcore_icon_operator_iterator",
    defaultText: "Iterator",

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

        this.configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [this.textField],
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
            height: 200,
            modal: true,
            title: this.getDefaultText(),
            layout: "fit",
            items: [this.configPanel]
        });

        this.window.show();
        return this.window;
    },

    commitData: function () {
        this.node.data.configAttributes.label = this.textField.getValue();

        var nodeLabel = this.getNodeLabel(this.node.data.configAttributes);
        this.node.set('text', nodeLabel);
        this.node.set('isOperator', true);

        this.window.close();
    },

    allowChild: function (targetNode, dropNode) {
        // if (targetNode.childNodes.length > 0) {
        //     return false;
        // }
        return true;
    },

    getNodeLabel: function(configAttributes) {
        var nodeLabel = configAttributes.label;

        return nodeLabel;
    }
});
