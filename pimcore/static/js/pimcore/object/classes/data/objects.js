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

pimcore.registerNS("pimcore.object.classes.data.objects");
pimcore.object.classes.data.objects = Class.create(pimcore.object.classes.data.data, {

    type: "objects",
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
        this.type = "objects";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible","visibleGridView","visibleSearch","style"];

        this.treeNode = treeNode;
    },

    getGroup: function () {
        return "relation";
    },

    getTypeName: function () {
        return t("objects");
    },

    getIconClass: function () {
        return "pimcore_icon_object";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();

        this.specificPanel.removeAll();
        this.specificPanel.add([
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
                fieldLabel: t("lazy_loading"),
                name: "lazyLoading",
                checked: this.datax.lazyLoading
            },
            {
                xtype: "displayfield",
                hideLabel: true,
                value: t('lazy_loading_description'),
                cls: "pimcore_extra_label_bottom"
            },{
                xtype: "spinnerfield",
                fieldLabel: t("maximum_items"),
                name: "maxItems",
                value: this.datax.maxItems
            }
        ]);

        var classes = [];
        for(var i=0; i<this.datax.classes.length; i++) {
            classes.push(this.datax.classes[i]["classes"]);
        }

        this.specificPanel.add(new Ext.ux.form.MultiSelect({
            fieldLabel: t("allowed_classes"),
            name: "classes",
            value: classes.join(","),
            displayField: "text",
            valueField: "text",
            store: pimcore.globalmanager.get("object_types_store")
        }));

        return this.layout;
    }

});
