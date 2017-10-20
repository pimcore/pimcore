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


pimcore.registerNS("pimcore.object.gridcolumn.operator.anygetter");

pimcore.object.gridcolumn.operator.anygetter = Class.create(pimcore.object.gridcolumn.Abstract, {
        type: "operator",
        class: "AnyGetter",
        iconCls: "pimcore_icon_operator_anygetter",
        defaultText: "operator_anygetter",


        getConfigTreeNode: function (configAttributes) {
            if (configAttributes) {
                var nodeLabel = this.getNodeLabel(configAttributes);
                var node = {
                    draggable: true,
                    iconCls: this.iconCls,
                    text: nodeLabel,
                    configAttributes: configAttributes,
                    isTarget: true,
                    maxChildCount: 1,
                    expanded: true,
                    leaf: false,
                    expandable: false
                };
            } else {

                //For building up operator list
                var configAttributes = {type: this.type, class: this.class, label: t(this.defaultText)};

                var node = {
                    draggable: true,
                    iconCls: this.iconCls,
                    text: t(this.defaultText),
                    configAttributes: configAttributes,
                    isTarget: true,
                    maxChildCount: 1,
                    leaf: true
                };
            }
            node.isOperator = true;
            return node;
        },


        getCopyNode: function (source) {
            var copy = source.createNode({
                iconCls: this.iconCls,
                text: source.data.cssClass,
                isTarget: true,
                leaf: false,
                maxChildCount: 1,
                expanded: true,
                isOperator: true,
                configAttributes: {
                    label: source.data.configAttributes.label,
                    type: this.type,
                    class: this.class
                }
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

            this.attributeField = new Ext.form.TextField({
                fieldLabel: t('attribute'),
                length: 255,
                width: 200,
                value: this.node.data.configAttributes.attribute
            });

            this.param1Field = new Ext.form.TextField({
                fieldLabel: t('parameter'),
                length: 255,
                width: 200,
                value: this.node.data.configAttributes.param1
            });

            this.isArrayField = new Ext.form.Checkbox({
                fieldLabel: t('is_array'),
                length: 255,
                width: 200,
                value: this.node.data.configAttributes.isArrayType
            });

            this.forwardAttributeField = new Ext.form.TextField({
                fieldLabel: t('forward_attribute'),
                length: 255,
                width: 200,
                value: this.node.data.configAttributes.forwardAttribute
            });

            this.forwardParam1Field = new Ext.form.TextField({
                fieldLabel: t('forward_parameter'),
                length: 255,
                width: 200,
                value: this.node.data.configAttributes.forwardParam1
            });


            this.configPanel = new Ext.Panel({
                layout: "form",
                bodyStyle: "padding: 10px;",
                items: [this.textfield, this.attributeField, this.param1Field, this.isArrayField, this.forwardAttributeField, this.forwardParam1Field],
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
                title: t('operator_anygetter_settings'),
                layout: "fit",
                items: [this.configPanel]
            });

            this.window.show();
            return this.window;
        },

        commitData: function () {
            this.node.set('isOperator', true);
            this.node.data.configAttributes.label = this.textfield.getValue();
            this.node.data.configAttributes.attribute = this.attributeField.getValue();
            this.node.data.configAttributes.param1 = this.param1Field.getValue();
            this.node.data.configAttributes.isArrayType = this.isArrayField.getValue();
            this.node.data.configAttributes.forwardAttribute = this.forwardAttributeField.getValue()
            this.node.data.configAttributes.forwardParam1 = this.forwardParam1Field.getValue();

            var nodeLabel = this.getNodeLabel(this.node.data.configAttributes);
            this.node.set('text', nodeLabel);
            this.window.close();
        },

        getNodeLabel: function (configAttributes) {
            var nodeLabel = configAttributes.label ? configAttributes.label : t(this.defaultText);
            if (configAttributes.attribute) {
                nodeLabel += '<span class="pimcore_gridnode_hint"> (' + configAttributes.attribute + ')</span>';
            }

            return nodeLabel;

        }
    }
);