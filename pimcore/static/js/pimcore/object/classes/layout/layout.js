/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
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

    getType: function () {
        return this.type;
    },

    getLayout: function () {

        this.layout = new Ext.Panel({
            bodyStyle: "padding: 10px;",
            items: [
                {
                    xtype: "form",
                    title: t("general_settings"),
                    bodyStyle: "padding: 10px;",
                    style: "margin: 10px 0 10px 0",
                    items: [
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
                            store: ["","center", "north", "south", "east", "west"],
                            triggerAction: 'all',
                            editable: false
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t("title"),
                            name: "title",
                            value: this.datax.title
                        },
                        {
                            xtype: "spinnerfield",
                            fieldLabel: t("width"),
                            name: "width",
                            value: this.datax.width
                        },
                        {
                            xtype: "spinnerfield",
                            fieldLabel: t("height"),
                            name: "height",
                            value: this.datax.height
                        },
                        {
                            xtype: "checkbox",
                            fieldLabel: t("collapsible"),
                            name: "collapsible",
                            checked: this.datax.collapsible
                        },
                        {
                            xtype: "checkbox",
                            fieldLabel: t("collapsed"),
                            name: "collapsed",
                            checked: this.datax.collapsed
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t("css_style") + " (float: left; margin:10px; ...)",
                            name: "bodyStyle",
                            width: 400,
                            value: this.datax.bodyStyle
                        }
                    ]
                }/*,
                {
                    xtype: "form",
                    title: t("display_layout_to_users"),
                    bodyStyle: "padding: 10px;",
                    style: "margin: 10px 0 10px 0",
                    items: [new Ext.ux.form.SuperField({
                        allowEdit: true,
                        name: "permissions",
                        values:this.datax.permissions,
                        stripeRows:false,
                        items: [
                            new Ext.form.ComboBox({
                                fieldLabel: t("username"),
                                name: "username",
                                triggerAction: 'all',
                                editable: false,
                                store: new Ext.data.JsonStore({
                                    url: '/admin/user/get-all-users',
                                    fields: ["username"],
                                    root: "users"
                                }),
                                displayField: "username",
                                valueField: "username",
                                summaryDisplay:true
                            })
                        ]
                    })
                    ]
                }*/
            ]
        });


        this.layout.on("render", this.layoutRendered.bind(this));

        return this.layout;
    },

    layoutRendered: function (layout) {

        var items = this.layout.findBy(function() {
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

        var items = this.layout.findBy(function() {
            return true;
        });

        for (var i = 0; i < items.length; i++) {
            if (items[i].name == "name") {
                this.treeNode.setText(items[i].getValue());
                break;
            }
        }
    },

    getData: function () {
        return this.datax;
    },

    applyData: function () {

        var items = this.layout.findBy(function() {
            return true;
        });

        for (var i = 0; i < items.length; i++) {
            if (typeof items[i].getValue == "function") {
                this.datax[items[i].name] = items[i].getValue();
            }
        }

        this.datax.fieldtype = this.getType();
        this.datax.datatype = "layout";
    }

});