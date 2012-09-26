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

pimcore.registerNS("pimcore.object.edit");
pimcore.object.edit = Class.create({

    initialize: function(object) {
        this.object = object;
        this.dataFields = {};

        this.fieldsToMask = [];
    },

    getLayout: function (conf) {

        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t('edit'),
                bodyStyle:'background-color: #fff;',
                padding: 10,
                border: false,
                layout: "fit",
                forceLayout: true,
                defaults: {
                    forceLayout: true
                },
                iconCls: "pimcore_icon_tab_edit",
                items: [this.getRecursiveLayout(conf).items],
                listeners: {
                    afterrender: function () {
                        pimcore.layout.refresh();
                    }
                }
            });
        }

        return this.layout;
    },

    getDataForField: function (name) {
        return this.object.data.data[name];
    },

    getMetaDataForField: function (name) {
        return this.object.data.metaData[name];
    },

    addToDataFields: function (field, name) {
        this.dataFields[name] = field;
    },

    addFieldsToMask: function (field) {
        this.fieldsToMask.push(field);
    },

    unmaskFrames: function () {

        // unmask wysiwyg editors
        for (var i = 0; i < this.fieldsToMask.length; i++) {
            this.fieldsToMask[i].unmask();
        }
    },

    maskFrames: function () {
        // this is for dnd over iframes, with this method it's not nessercery to register the dnd manager in each iframe (wysiwyg)

        // mask wysiwyg editors
        for (var i = 0; i < this.fieldsToMask.length; i++) {
            this.fieldsToMask[i].mask();
        }
    },

    disableFieldMasks: function () {
        this.fieldsToMaskBackup = this.fieldsToMask;
        this.fieldsToMask = [];
    },

    enableFieldMasks: function () {
        if(this.fieldsToMaskBackup) {
            this.fieldsToMask = this.fieldsToMaskBackup;
        }
    },

    getValues: function (omitMandatoryCheck) {

        if (!this.layout.rendered) {
            throw "edit not available";
        }

        var dataKeys = Object.keys(this.dataFields);
        var values = {};
        var currentField;
        var invalidMandatoryFields = [];
        var isInvalidMandatory;

        for (var i = 0; i < dataKeys.length; i++) {

            try {
                if (this.dataFields[dataKeys[i]] && typeof this.dataFields[dataKeys[i]] == "object") {
                    currentField = this.dataFields[dataKeys[i]];
                    if (this.object.ignoreMandatoryFields != true) {
                        if(currentField.isMandatory() == true) {
                            isInvalidMandatory = currentField.isInvalidMandatory();
                            if (isInvalidMandatory != false) {

                                // some fields can return their own error messages like fieldcollections, ...
                                if(typeof isInvalidMandatory == "object") {
                                    invalidMandatoryFields = array_merge(isInvalidMandatory, invalidMandatoryFields);
                                } else {
                                    invalidMandatoryFields.push(currentField.getTitle() + " (" + currentField.getName() + ")");
                                }
                            }
                        }
                    }

                    //only include changed values in save response.
                    if(currentField.isDirty()) {
                        values[currentField.getName()] =  currentField.getValue();
                    }
                }
            }
            catch (e) {
                console.log(e);
                values[currentField.getName()] = "";
            }
        }

        if (invalidMandatoryFields.length > 0 && !omitMandatoryCheck) {
            Ext.MessageBox.alert(t("error"), t("mandatory_field_empty") + "<br />- " + invalidMandatoryFields.join("<br />- "));
            return false;
        }

        return values;
    }

});

pimcore.object.edit.addMethods(pimcore.object.helpers.edit);