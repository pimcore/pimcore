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
        localizedfield: false
    },

    initialize: function (treeNode, initData) {
        this.type = "fieldcollections";

        this.initData(initData);

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
        return "pimcore_icon_fieldcollections";
    },

    getLayout: function ($super) {
        $super();
        
        this.store = new Ext.data.JsonStore({
            autoDestroy: false,
            url: "/admin/class/fieldcollection-list",
            root: 'fieldcollections',
            idProperty: 'key',
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
                width: 'auto',
                value: this.datax.allowedTypes,
                displayField: "key",
                valueField: "key",
                width: 300
            }), {
                xtype: "checkbox",
                fieldLabel: t("lazy_loading"),
                name: "lazyLoading",
                checked: this.datax.lazyLoading
            },{
                xtype: "spinnerfield",
                fieldLabel: t("maximum_items"),
                name: "maxItems",
                value: this.datax.maxItems
            }
        ]);

        this.specificPanel.doLayout();
    }

});
