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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.classes.data.input");
pimcore.object.classes.data.input = Class.create(pimcore.object.classes.data.data, {

    type: "input",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true
    },

    initialize: function (treeNode, initData) {
        this.type = "input";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("input");
    },

    getGroup: function () {
            return "text";
    },

    getIconClass: function () {
        return "pimcore_icon_input";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            }
        ]);

        if (!this.isInCustomLayoutEditor()) {
            this.specificPanel.add([{
                    xtype: "spinnerfield",
                    fieldLabel: t("columnlength"),
                    name: "columnLength",
                    value: this.datax.columnLength
                }
            ]);


            var regexSet;
            var checkRegex = function () {
                var testStringEl = regexSet.getComponent("regexTestString");
                var regex = regexSet.getComponent("regex").getValue();
                var testString = testStringEl.getValue();

                try {
                    var regexp = new RegExp(regex);
                    if(regexp.test(testString)) {
                        testStringEl.getEl().applyStyles({
                            background: "green",
                            color: "white"
                        });
                    } else {
                        testStringEl.getEl().applyStyles({
                            background: "red",
                            color: "white"
                        });
                    }
                } catch(e) {
                    console.log(e);
                }
            };

            regexSet = new Ext.form.FieldSet({
                xtype: "fieldset",
                style: "margin-top:10px;",
                title: t("regex_validation"),
                items: [{
                    xtype: "textfield",
                    fieldLabel: t("regex"),
                    itemId: "regex",
                    name: "regex",
                    width: 400,
                    value: this.datax["regex"],
                    enableKeyEvents: true,
                    listeners: {
                        keyup: checkRegex
                    }
                }, {
                    xtype: "displayfield",
                    hideLabel:true,
                    html:'<span class="object_field_setting_warning">' + t('object_regex_info')+' (Delimiter: #)</span>'
                }, {
                    xtype: "textfield",
                    fieldLabel: t("test_string"),
                    itemId: "regexTestString",
                    width: 400,
                    enableKeyEvents: true,
                    listeners: {
                        keyup: checkRegex
                    }
                }]
            });

            this.specificPanel.add(regexSet);
        }

        return this.layout;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    width: source.datax.width,
                    columnLength: source.datax.columnLength,
                    regex: source.datax.regex
                });
        }
    }
});
