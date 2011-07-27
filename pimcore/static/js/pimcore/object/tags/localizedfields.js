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

pimcore.registerNS("pimcore.object.tags.localizedfields");
pimcore.object.tags.localizedfields = Class.create(pimcore.object.tags.abstract, {

    type: "localizedfields",

    initialize: function (data, fieldConfig) {

        this.data = {};
        this.languageElements = {};

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;
    },

    getLayoutEdit: function () {

        var panelConf = {
            autoScroll: true,
            monitorResize: true,
            cls: "object_field",
            activeTab: 0,
            height: 200,
            items: [],
            deferredRender: false,
            forceLayout: true,
            hideMode: "offsets",
            enableTabScroll:true
        };




        if(!this.fieldConfig.width) {
            panelConf.listeners = {
                afterrender: function () {
                    this.component.ownerCt.doLayout();
                    this.component.setWidth(this.component.ownerCt.getWidth()-45);
                }.bind(this)
            };
        }

        if(this.fieldConfig.width) {
            panelConf.width = this.fieldConfig.width;
        }

        if(this.fieldConfig.height) {
            panelConf.height = this.fieldConfig.height;
            panelConf.autoHeight = false;
        }

        if(this.fieldConfig.layout) {
            panelConf.layout = this.fieldConfig.layout;
        }

        if(this.fieldConfig.region) {
            panelConf.region = this.fieldConfig.region;
        }

        if(this.fieldConfig.title) {
            panelConf.title = this.fieldConfig.title;
        }


        this.fieldConfig.datatype ="layout";
        this.fieldConfig.fieldtype = "panel";

        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {

            this.currentLanguage = pimcore.settings.websiteLanguages[i];
            this.languageElements[this.currentLanguage] = [];

            panelConf.items.push({
                xtype: "panel",
                layout: "pimcoreform",
                bodyStyle: "padding: 10px;",
                autoScroll: true,
                deferredRender: false,
                hideMode: "offsets",
                title: pimcore.available_languages[pimcore.settings.websiteLanguages[i]],
                items: this.getRecursiveLayout(this.fieldConfig).items
            });

        }

        this.component = new Ext.TabPanel(panelConf);

        return this.component;
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getDataForField: function (name) {
        try {
            if (this.data[this.currentLanguage]) {
                if (this.data[this.currentLanguage][name]) {
                    return this.data[this.currentLanguage][name];
                }
            }
        } catch (e) {
            console.log(e);
        }
        return;
    },

    getMetaDataForField: function(name) {
        return null;
    },

    addToDataFields: function (field, name) {
        this.languageElements[this.currentLanguage].push(field);
    },

    addFieldsToMask: function (field) {
        this.object.edit.fieldsToMask.push(field);
    },

    getValue: function () {

        var localizedData = {};
        var currentLanguage;

        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {

            currentLanguage = pimcore.settings.websiteLanguages[i];
            localizedData[currentLanguage] = {};

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                localizedData[currentLanguage][this.languageElements[currentLanguage][s].getName()] = this.languageElements[currentLanguage][s].getValue();
            }
        }

        return localizedData;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        var currentLanguage;

        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {

            currentLanguage = pimcore.settings.websiteLanguages[i];

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                if(this.languageElements[currentLanguage][s].isDirty()) {
                    return true;
                }
            }
        }

        return false;
    },

    isMandatory: function () {

        var currentLanguage;

        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {

            currentLanguage = pimcore.settings.websiteLanguages[i];

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                if(this.languageElements[currentLanguage][s].isMandatory()) {
                    return true;
                }
            }
        }

        return false;
    },

    isInvalidMandatory: function () {

        var currentLanguage;
        var isInvalid = false;

        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {

            currentLanguage = pimcore.settings.websiteLanguages[i];

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                if(this.languageElements[currentLanguage][s].isMandatory()) {
                    if(this.languageElements[currentLanguage][s].isInvalidMandatory()) {
                        isInvalid = true;
                    }
                }
            }
        }

        return isInvalid;
    }
});

pimcore.object.tags.localizedfields.addMethods(pimcore.object.helpers.edit);