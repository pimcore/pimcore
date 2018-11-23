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


pimcore.registerNS("pimcore.object.gridcolumn.operator.geopointrenderer");

pimcore.object.gridcolumn.operator.geopointrenderer = Class.create(pimcore.object.gridcolumn.Abstract, {
    operatorGroup: "renderer",
    type: "operator",
    class: "GeopointRenderer",
    iconCls: "pimcore_icon_geopoint",
    defaultText: "Geopoint Renderer",
    group: "string",
    maxChildCount: 1,

    getConfigTreeNode: function(configAttributes) {
        if(configAttributes) {
            var nodeLabel = this.getNodeLabel(configAttributes);

            var node = {
                draggable: true,
                iconCls: this.iconCls,
                text: nodeLabel,
                configAttributes: configAttributes,
                isTarget: true,
                allowChildren: true,
                expanded: true,
                leaf: false,
                expandable: false
            };
        } else {

            //For building up operator list
            var configAttributes = { type: this.type, class: this.class, renderer: "geopoint"};

            var node = {
                draggable: true,
                iconCls: this.iconCls,
                text: this.getDefaultText(),
                configAttributes: configAttributes,
                isTarget: true,
                leaf: true
            };
        }
        node.isOperator = true;
        node.isRenderer = true;
        return node;
    },


    getCopyNode: function(source) {
        var copy = source.createNode({
            iconCls: this.iconCls,
            text: source.data.text,
            isTarget: true,
            leaf: false,
            expandable: false,
            isOperator: true,
            isRenderer: true,
            configAttributes: {
                label: source.data.text,
                type: this.type,
                class: this.class,
                renderer: "geopoint"
            }
        });

        return copy;
    },


    getConfigDialog: function(node, params) {
        this.node = node;

        this.textField = new Ext.form.TextField({
            fieldLabel: t('label'),
            labelWidth: 200,
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
                    this.commitData(params);
                }.bind(this)
            }]
        });

        this.window = new Ext.Window({
            width: 400,
            height: 350,
            modal: true,
            title: t('operator_renderer_settings'),
            layout: "fit",
            items: [this.configPanel]
        });

        this.window.show();
        return this.window;
    },

    commitData: function(params) {
        this.node.set('isOperator', true);

        this.node.data.configAttributes.label = this.textField.getValue();
        this.node.data.configAttributes.renderer = "geopoint";

        var nodeLabel = this.getNodeLabel(this.node.data.configAttributes);
        this.node.set('text', nodeLabel);

        this.window.close();

        if (params && params.callback) {
            params.callback();
        }
    },

    getDefaultText: function () {
        return this.defaultText;
    },

    getNodeLabel: function (configAttributes) {
        var nodeLabel = configAttributes.label ? configAttributes.label : this.getDefaultText();
        return nodeLabel;
    }
});