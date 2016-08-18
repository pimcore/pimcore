/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
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

    getType: function () {
        return this.type;
    },

    getLayout: function () {

        var regionData = [
            [ "-", "" ],
            [ "center", "center" ],
            [ "north", "north" ],
            [ "south", "south" ],
            [ "east", "east" ],
            [ "west", "west" ]
        ];

        var regionStore = Ext.create('Ext.data.ArrayStore', {
            data     : regionData,
            fields   : [
                'display',
                'value'
            ]
        });


        this.layout = new Ext.Panel({
            items: [
                {
                    xtype: "form",
                    title: t("general_settings"),
                    bodyStyle: "padding: 10px;",
                    style: "margin: 0 0 10px 0",
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
                            store: regionStore,
                            displayField: 'display',
                            valueField: 'value',
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
                                    if(!checked) {
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
                                    if(checked) {
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
                    ]
                }
            ]
        });


        this.layout.on("render", this.layoutRendered.bind(this));

        return this.layout;
    },

    layoutRendered: function (layout) {

        var items = this.layout.queryBy(function() {
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

        var items = this.layout.queryBy(function() {
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

        var items = this.layout.queryBy(function() {
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

    setInCustomLayoutEditor: function(inCustomLayoutEditor) {
        this.inCustomLayoutEditor = inCustomLayoutEditor;
    },

    isInCustomLayoutEditor: function() {
        return this.inCustomLayoutEditor;
    }

});