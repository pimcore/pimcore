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


pimcore.registerNS("pimcore.object.gridcolumn.operator.lfexpander");

pimcore.object.gridcolumn.operator.lfexpander = Class.create(pimcore.object.gridcolumn.operator.text, {
    operatorGroup: "transformer",
    type: "operator",
    class: "LFExpander",
    iconCls: "pimcore_icon_operator_lfexpander",
    defaultText: "LF Expander",
    group: "other",

    getConfigTreeNode: function(configAttributes) {
        if(configAttributes) {
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
            var configAttributes = { type: this.type, class: this.class};

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


    getCopyNode: function(source) {
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


    getConfigDialog: function(node, params) {
        this.node = node;

        this.textfield = new Ext.form.TextField({
            fieldLabel: t('label'),
            length: 255,
            width: 220,
            value: this.node.data.configAttributes.label
        });

        var data = [];
        data.push(["default", t("default")]);
        for (var i = 0; i < pimcore.settings.websiteLanguages.length; i++) {
            var language = pimcore.settings.websiteLanguages[i];
            data.push([language, t(pimcore.available_languages[language])]);
        }

        var localeStore = new Ext.data.ArrayStore({
                fields: ["key", "value"],
                data: data
            }
        );

        this.asArrayField = new Ext.form.Checkbox({
            fieldLabel: t('as_array'),
            value: this.node.data.configAttributes.asArray
        });


        var options = {
            triggerAction: "all",
            editable: false,
            fieldLabel: t('restrict_to_locales'),
            store: localeStore,
            listConfig: {
                width: 238
            },
            height: 250,
            displayField: 'value',
            valueField: 'key',
            value: this.node.data.configAttributes.locales
        };

        this.localesField = Ext.create('Ext.ux.form.MultiSelect', options);


        this.configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [this.textfield, this.localesField, this.asArrayField],
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
            height: 480,
            layout: "fit",
            modal: true,
            title: this.getDefaultText(),
            items: [this.configPanel]
        });

        this.window.show();
        return this.window;
    },

    commitData: function(params) {
        this.node.data.configAttributes.label = this.textfield.getValue();
        this.node.data.configAttributes.locales = this.localesField.getValue();
        this.node.data.configAttributes.asArray = this.asArrayField.getValue();
        this.node.set('text', this.textfield.getValue());
        this.node.set('isOperator', true);
        this.window.close();
        if (params && params.callback) {
            params.callback();
        }
    },

    allowChild: function(targetNode, dropNode) {
        if (targetNode.childNodes.length > 0) {
            return false;
        }
        return true;
    }

});