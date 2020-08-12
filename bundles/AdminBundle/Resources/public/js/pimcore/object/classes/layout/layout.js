/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.classes.layout.layout");
pimcore.object.classes.layout.layout = Class.create({

    initData: function (d) {

        this.datax = {
            name: t("layout"),
            datatype: "layout",
            fieldtype: this.getType()
        };

        if (d) {
            if (d.datatype && d.fieldtype && d.name) {
                var keys = Object.keys(d);
                for (var i = 0; i < keys.length; i++) {
                    if (keys[i] != "childs") {
                        this.datax[keys[i]] = d[keys[i]];
                    }
                }
            }
        }
    },

    supportsTitle: function () {
        return true;
    },

    getType: function () {
        return this.type;
    },

    getLayout: function () {

        var regionData = [
            ["-", ""],
            ["center", "center"],
            ["north", "north"],
            ["south", "south"],
            ["east", "east"],
            ["west", "west"]
        ];

        var regionStore = Ext.create('Ext.data.ArrayStore', {
            data: regionData,
            fields: [
                'display',
                'value'
            ]
        });

        let items = [
            {
                xtype: "textfield",
                fieldLabel: t("name"),
                name: "name",
                enableKeyEvents: true,
                value: this.datax.name
            },
            {
                xtype: "combo",
                fieldLabel: t("region"),
                name: "region",
                value: this.datax.region,
                store: regionStore,
                displayField: 'display',
                valueField: 'value',
                triggerAction: 'all',
                editable: false
            }];

        if (this.supportsTitle()) {
            items.push({
                xtype: "textfield",
                fieldLabel: t("title"),
                name: "title",
                value: this.datax.title
            });
        }

        items = items.concat([
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "numberfield",
                fieldLabel: t("height"),
                name: "height",
                value: this.datax.height
            },
            {
                xtype: "checkbox",
                fieldLabel: t("collapsible"),
                name: "collapsible",
                checked: this.datax.collapsible || this.datax.collapsed,
                listeners: {
                    change: function (row, checked) {
                        if (!checked) {
                            //force uncheck on collapsed checkbox
                            row.nextNode().setValue(false);
                        }
                    }
                }
            },
            {
                xtype: "checkbox",
                fieldLabel: t("collapsed"),
                name: "collapsed",
                checked: this.datax.collapsed,
                listeners: {
                    change: function (row, checked) {
                        if (checked) {
                            //force check on collapsible checkbox
                            row.previousNode().setValue(true);
                        }
                    }
                }
            },
            {
                xtype: "textfield",
                fieldLabel: t("css_style") + " (float: left; margin:10px; ...)",
                name: "bodyStyle",
                width: 400,
                value: this.datax.bodyStyle
            }
        ]);

        this.layout = new Ext.Panel({
            title: '<b>' + this.getTypeName() + '</b>',
            bodyStyle: 'padding: 10px;',
            items: [
                {
                    xtype: "form",
                    bodyStyle: "padding: 10px;",
                    style: "margin: 0 0 10px 0",
                    items: items
                }
            ]
        });


        this.layout.on("render", this.layoutRendered.bind(this));

        return this.layout;
    },

    layoutRendered: function (layout) {

        var items = this.layout.queryBy(function () {
            return true;
        });

        for (var i = 0; i < items.length; i++) {
            if (items[i].name == "name") {
                items[i].on("keyup", this.updateName.bind(this));
                break;
            }
        }
    },

    updateName: function () {

        var items = this.layout.queryBy(function () {
            return true;
        });

        for (var i = 0; i < items.length; i++) {
            if (items[i].name == "name") {
                this.treeNode.set('text', items[i].getValue());
                break;
            }
        }
    },

    getData: function () {
        return this.datax;
    },

    applyData: function () {

        var items = this.layout.queryBy(function () {
            return true;
        });

        for (var i = 0; i < items.length; i++) {
            if (typeof items[i].getValue == "function") {
                this.datax[items[i].name] = items[i].getValue();
            }
        }

        this.datax.fieldtype = this.getType();
        this.datax.datatype = "layout";
    },

    setInCustomLayoutEditor: function (inCustomLayoutEditor) {
        this.inCustomLayoutEditor = inCustomLayoutEditor;
    },

    isInCustomLayoutEditor: function () {
        return this.inCustomLayoutEditor;
    },

    getIconFormElement: function () {
        var iconStore = new Ext.data.ArrayStore({
            proxy: {
                url: Routing.generate('pimcore_admin_dataobject_class_geticons'),
                type: 'ajax',
                reader: {
                    type: 'json'
                }
            },
            fields: ["text", "value"]
        });

        var iconFieldId = Ext.id();
        var iconField = new Ext.form.field.Text({
            id: iconFieldId,
            name: "icon",
            width: 396,
            value: this.datax.icon,
            listeners: {
                "afterrender": function (el) {
                    el.inputEl.applyStyles("background:url(" + el.getValue() + ") right center no-repeat;");
                }
            }
        });


        var container = {
            xtype: "fieldcontainer",
            layout: "hbox",
            fieldLabel: t("icon"),
            defaults: {
                labelWidth: 200
            },
            items: [
                iconField,
                {
                    xtype: "combobox",
                    store: iconStore,
                    width: 50,
                    valueField: 'value',
                    displayField: 'text',
                    listeners: {
                        select: function (ele, rec, idx) {
                            var icon = ele.container.down("#" + iconFieldId);
                            var newValue = rec.data.value;
                            icon.component.setValue(newValue);
                            icon.component.inputEl.applyStyles("background:url(" + newValue + ") right center no-repeat;");
                            return newValue;
                        }.bind(this)
                    }
                },
                {
                    iconCls: "pimcore_icon_refresh",
                    xtype: "button",
                    tooltip: t("refresh"),
                    handler: function (iconField) {
                        iconField.inputEl.applyStyles("background:url(" + iconField.getValue() + ") right center no-repeat;");
                    }.bind(this, iconField)
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_icons",
                    text: t('icon_library'),
                    handler: function () {
                        pimcore.helpers.openGenericIframeWindow("icon-library", Routing.generate('pimcore_admin_misc_iconlist'), "pimcore_icon_icons", t("icon_library"));
                    }
                }
            ]
        };

        return container;
    }

});
