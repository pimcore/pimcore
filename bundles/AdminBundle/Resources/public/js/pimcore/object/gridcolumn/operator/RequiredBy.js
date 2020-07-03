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


pimcore.registerNS("pimcore.object.gridcolumn.operator.requiredby");

pimcore.object.gridcolumn.operator.requiredby = Class.create(pimcore.object.gridcolumn.Abstract, {
    operatorGroup: "extractor",
    type: "operator",
    class: "RequiredBy",
    iconCls: "pimcore_icon_operator_requiredby",
    defaultText: "Required By",
    group: "other",

    getConfigTreeNode: function (configAttributes) {
        if (configAttributes) {
            var nodeLabel = this.getNodeLabel(configAttributes);
            var node = {
                draggable: true,
                iconCls: this.iconCls,
                text: nodeLabel,
                configAttributes: configAttributes,
                isTarget: true,
                expanded: true,
                leaf: false,
                expandable: false,
                isChildAllowed: this.allowChild
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


    getConfigDialog: function (node, params) {
        this.node = node;

        this.textField = new Ext.form.TextField({
            fieldLabel: t('label'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.label
        });

        var data = [];
        data.push(['all', t('all')]);
        data.push(['asset', t('asset')]);
        data.push(['document', t('document')]);
        data.push(['object', t('object')]);

        var store = new Ext.data.ArrayStore({
                fields: ["key", "value"],
                data: data
            }
        );


        var options = {
            fieldLabel: t('type'),
            triggerAction: "all",
            editable: true,
            selectOnFocus: true,
            queryMode: 'local',
            typeAhead: true,
            forceSelection: false,
            store: store,
            componentCls: "object_field",
            mode: "local",
            width: 200,
            padding: 10,
            displayField: "value",
            valueField: "key",
            value: this.node.data.configAttributes.elementType
        };

        this.typeField = new Ext.form.ComboBox(options);

        this.onlyCountField = new Ext.form.Checkbox({
            fieldLabel: t('only_count'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.onlyCount
        });

        this.configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [this.textField, this.typeField, this.onlyCountField],
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
            height: 400,
            modal: true,
            title: t('settings'),
            layout: "fit",
            items: [this.configPanel]
        });

        this.window.show();
        return this.window;
    },

    commitData: function (params) {
        this.node.data.configAttributes.label = this.textField.getValue();
        this.node.data.configAttributes.elementType = this.typeField.getValue();
        this.node.data.configAttributes.onlyCount = this.onlyCountField.getValue();

        var nodeLabel = this.getNodeLabel(this.node.data.configAttributes);

        this.node.set('text', nodeLabel);
        this.node.set('isOperator', true);

        this.window.close();
        if (params && params.callback) {
            params.callback();
        }
    },

    getNodeLabel: function(configAttributes) {
        var nodeLabel = configAttributes.label;
        if (configAttributes.elementType) {
            nodeLabel += '<span class="pimcore_gridnode_hint"> (' + configAttributes.elementType  + ')</span>';
        }

        return nodeLabel;
    },

    allowChild: function (targetNode, dropNode) {
        return false;
    }
});