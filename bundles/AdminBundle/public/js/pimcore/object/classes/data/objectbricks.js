/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.object.classes.data.objectbricks");
/**
 * @private
 */
pimcore.object.classes.data.objectbricks = Class.create(pimcore.object.classes.data.data, {

    type: "objectbricks",
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
        this.type = "objectbricks";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","invisible","style","noteditable"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("objectbricks");
    },

    getGroup: function () {
            return "structured";
    },

    getIconClass: function () {
        return "pimcore_icon_objectbricks";
    },

    getLayout: function ($super) {
        $super();

        this.specificPanel.removeAll();

        if(!this.inCustomLayoutEditor) {
            this.specificPanel.add({
                    xtype: "numberfield",
                    fieldLabel: t("maximum_items"),
                    name: "maxItems",
                    value: this.datax.maxItems,
                    minValue: 0
            });
        }

        this.specificPanel.add({
            xtype: "checkbox",
            fieldLabel: t("border"),
            name: "border",
            checked: this.datax.border,
        });


        if (this.inCustomLayoutEditor) {
            this.specificPanel.add(new Ext.ux.form.MultiSelect({
                fieldLabel: t("allowed_bricks"),
                name: "allowedTypes",
                value: this.datax.allowedTypes,
                displayField: "title",
                valueField: "key",
                store: Ext.create('Ext.data.JsonStore', {
                    fields: ['text'],
                    proxy: {
                        type: 'ajax',
                        url: Routing.generate('pimcore_admin_dataobject_class_objectbricklist'),
                        reader: {
                            type: 'json',
                            rootProperty: 'objectbricks'
                        }
                    },
                    autoLoad: true
                }),
                width: 600
            }));
        }
        
        return this.layout;
    },

    isValid: function ($super) {
        if(!$super()) {
            return false;
        }

        // underscore "_" ist not allowed!
        // reason: the backend creates a class with the name of this field, if it contains an _ the autoloader
        // isn't able to load this file
        var data = this.getData();
        if(data.name.match(/[_]+/)) {
            return false;
        }

        return true;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    maxItems: source.datax.maxItems,
                    border: source.datax.border,
                    allowedTypes: source.datax.allowedTypes
                });
        }
    }
    
});
