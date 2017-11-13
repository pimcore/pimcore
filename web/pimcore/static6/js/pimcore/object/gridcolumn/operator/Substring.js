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


pimcore.registerNS("pimcore.object.gridcolumn.operator.substring");

pimcore.object.gridcolumn.operator.substring = Class.create(pimcore.object.gridcolumn.Abstract, {
    type: "operator",
    class: "Substring",
    iconCls: "pimcore_icon_operator_substring",
    defaultText: "operator_substring",
    group: "string",

    getConfigTreeNode: function(configAttributes) {
        if(configAttributes) {
            var node = {
                draggable: true,
                iconCls: this.iconCls,
                text: configAttributes.label ? configAttributes.label : t(this.defaultText),
                configAttributes: configAttributes,
                isTarget: true,
                expanded: true,
                leaf: false,
                expandable: false,
                allowChildren: true,
                isChildAllowed: this.allowChild
            };
        } else {

            //For building up operator list
            var configAttributes = { type: this.type, class: this.class, label: t(this.defaultText)};

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


    getCopyNode: function(source) {
        var copy = source.createNode({
            iconCls: this.iconCls,
            text: source.data.cssClass,
            isTarget: true,
            leaf: false,
            expanded: true,
            isOperator: true,
            isChildAllowed: this.allowChild,
            configAttributes: {
                label: source.data.configAttributes.label,
                type: this.type,
                class: this.class
            }
        });
        return copy;
    },


    getConfigDialog: function(node) {
        this.node = node;

        this.textField = new Ext.form.TextField({
            fieldLabel: t('label'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.label
        });

        this.startField = new Ext.form.NumberField({
            fieldLabel: t('start'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.start,
            minValue: 0
        });

        this.lengthField = new Ext.form.NumberField({
            fieldLabel: t('length'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.length
        });


        this.ellipsesField = new Ext.form.Checkbox({
            fieldLabel: t('ellipses'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.ellipses
        });


        this.configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [this.textField, this.startField, this.lengthField, this.ellipsesField],
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
            height: 350,
            modal: true,
            title: t('operator_substring_settings'),
            layout: "fit",
            items: [this.configPanel]
        });

        this.window.show();
        return this.window;
    },

    commitData: function() {
        this.node.set('isOperator', true);
        this.node.data.configAttributes.start = this.startField.getValue();
        this.node.data.configAttributes.length = this.lengthField.getValue();
        this.node.data.configAttributes.ellipses = this.ellipsesField.getValue();
        this.node.data.configAttributes.label = this.textField.getValue();
        this.node.set('text', this.textField.getValue());
        this.window.close();
    },

    allowChild: function (targetNode, dropNode) {
        if (targetNode.childNodes.length > 0) {
            return false;
        }
        return true;
    }
});