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

pimcore.registerNS("pimcore.object.tags.link");
pimcore.object.tags.link = Class.create(pimcore.object.tags.abstract, {

    type: "link",
    dirty: false,

    initialize: function (data, fieldConfig) {

        this.data = "";
        this.defaultData = {
            type: "internal",
            path: "",
            parameters: "",
            anchor: "",
            accesskey: "",
            rel: "",
            tabindex: "",
            target: ""
        };

        if (data) {
            this.data = data;
        }
        else {
            this.data = this.defaultData;
        }
        this.fieldConfig = fieldConfig;

    },

    getGridColumnConfig: function(field) {
        var renderer = function(key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.css += " grid_value_inherited";
            }
            if(value) {
                return value.text;
            }
            return t("empty");

        }.bind(this, field.key);

        return {header: ts(field.label), sortable: true, dataIndex: field.key, renderer: renderer};
    },

    getLayoutEdit: function () {

        var input = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            itemCls: "object_field"
        };

        this.button = new Ext.Button({
            iconCls: "pimcore_icon_edit_link",
            handler: this.openEditor.bind(this)
        });

        var textValue = "[not set]";
        if (this.data.text) {
            textValue = this.data.text;
        }
        this.displayField = new Ext.form.DisplayField({
            value: textValue
        });

        this.component = new Ext.form.CompositeField({
            xtype: 'compositefield',
            fieldLabel: this.fieldConfig.title,
            combineErrors: false,
            items: [this.displayField, this.button],
            itemCls: "object_field"
        });

        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        //this.layout.disable();
        this.button.hide();
        
        return this.component;
    },

    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    openEditor: function () {
        this.window = pimcore.helpers.editmode.openLinkEditPanel(this.data, {
            empty: this.empty.bind(this),
            cancel: this.cancel.bind(this),
            save: this.save.bind(this)
        });
    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["asset","document"]
        });
    },

    save: function () {
        var values = this.window.getComponent("form").getForm().getFieldValues();
        if(Ext.encode(values) != Ext.encode(this.data)) {
            this.dirty = true; 
        }
        this.data = values;

        var textValue = "[not set]"; 
        if (this.data.text) {
            textValue = this.data.text;
        }
        this.displayField.setValue(textValue);

        // close window
        this.window.close();
    },

    empty: function () {

        // close window
        this.window.close();

        this.data = this.defaultData;
        this.dirty = true; 

        // set text
        this.displayField.setValue("[not set]");
    },

    cancel: function () {
        this.window.close();
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        return this.dirty;
    }
});