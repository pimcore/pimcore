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
        localizedfield: true,
        classificationstore : true,
        block: true
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
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            }
        ]);

        if (!this.isInCustomLayoutEditor() && !this.isInClassificationStoreEditor()) {
            this.specificPanel.add([{
                    xtype: "numberfield",
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
                        console.log("success");
                        testStringEl.addCls("class-editor-validation-success");
                        testStringEl.removeCls("class-editor-validation-error");
                    } else {
                        console.log("error");
                        testStringEl.removeCls("class-editor-validation-success");
                        testStringEl.addCls("class-editor-validation-error");
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
                    xtype: "panel",
                    bodyStyle: "padding-top: 3px",
                    style: "margin-bottom: 10px",
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
