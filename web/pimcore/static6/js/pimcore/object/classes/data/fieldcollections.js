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

pimcore.registerNS("pimcore.object.classes.data.fieldcollections");
pimcore.object.classes.data.fieldcollections = Class.create(pimcore.object.classes.data.data, {

    type: "fieldcollections",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: false,
        fieldcollection: false,
        localizedfield: false,
        classificationstore : false
    },

    initialize: function (treeNode, initData) {
        this.type = "fieldcollections";

        this.initData(initData);

        if (typeof this.datax.lazyLoading == "undefined") {
            this.datax.lazyLoading = true;
        }

        // overwrite default settings
        this.availableSettingsFields = ["name","title","noteditable","invisible","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("fieldcollections");
    },

    getGroup: function () {
            return "structured";
    },

    getIconClass: function () {
        return "pimcore_icon_fieldcollection";
    },

    getLayout: function ($super) {
        $super();
        
        this.store = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: "/admin/class/fieldcollection-list",
                reader: {
                    type: 'json',
                    rootProperty: 'fieldcollections',
                    idProperty: 'key'
                }
            },
            autoDestroy: false,
            fields: ['key'],
            listeners: {
                load: this.initSelection.bind(this)
            }
        });
        this.store.load();
        
        this.specificPanel.removeAll();

        return this.layout;
    },
    
    initSelection: function () {
        this.specificPanel.add([
            new Ext.ux.form.MultiSelect({
                name: "allowedTypes",
                triggerAction: "all",
                editable: false,
                fieldLabel: t("allowed_types"),
                store: this.store,
                value: this.datax.allowedTypes,
                displayField: "key",
                valueField: "key",
                width: 400,
                height: 100
            }), {
                xtype: "checkbox",
                fieldLabel: t("lazy_loading"),
                name: "lazyLoading",
                checked: this.datax.lazyLoading
            },{
                xtype: "numberfield",
                fieldLabel: t("maximum_items"),
                name: "maxItems",
                value: this.datax.maxItems,
                minValue: 0
            },
            {
                xtype: "checkbox",
                fieldLabel: t("disallow_addremove"),
                name: "disallowAddRemove",
                checked: this.datax.disallowAddRemove
            },
            {
                xtype: "checkbox",
                fieldLabel: t("disallow_reorder"),
                name: "disallowReorder",
                checked: this.datax.disallowReorder
            }
        ]);

        this.specificPanel.updateLayout();

        this.standardSettingsForm.add(
            [
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
                }
            ]

        );

        this.standardSettingsForm.updateLayout();
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    allowedTypes: source.datax.allowedTypes,
                    lazyLoading: source.datax.lazyLoading,
                    maxItems: source.datax.maxItems,
                    disallowAddRemove: source.datax.disallowAddRemove,
                    disallowReorder: source.datax.disallowReorder
                });
        }
    }

});
