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
            xtype: "panel",
            border: false,
            cls: "object_field",
            autoHeight: true,
            forceLayout: true,
            monitorResize: true,
            layout: "fit",
            hideMode: "offsets"
        };

        var tabsConf = {
            xtype: "tabpanel",
            autoScroll: true,
            monitorResize: true,
            activeTab: 0,
            autoHeight: true,
            items: [],
            deferredRender: true,
            forceLayout: true,
            hideMode: "offsets",
            enableTabScroll:true
        };

        if(!this.fieldConfig.width) {
            //this.fieldConfig.width = 600;
            /*panelConf.listeners = {
                afterrender: function () {
                    this.component.doLayout();
                    //this.component.setWidth(this.component.ownerCt.getWidth()-45);
                }.bind(this)
            };*/
        }

        if(this.fieldConfig.width) {
            panelConf.width = this.fieldConfig.width;
        }

        if(this.fieldConfig.height) {
            panelConf.height = this.fieldConfig.height;
            panelConf.autoHeight = false;
        } else {
            panelConf.listeners = {
                afterrender: function () {
                    window.setTimeout(function () {
                        var firstTab = this.tabPanel.items.first();
                        var height = firstTab.items.first().getEl().getHeight();

                        this.tabPanel.items.first().setHeight(height); // add padding
                        this.tabPanel.items.first().doLayout();
                        this.tabPanel.getEl().parent().setHeight(height+20);

                        this.component.doLayout();
                    }.bind(this), 2000);
                }.bind(this)
            };
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

            tabsConf.items.push(new Ext.Panel({
                xtype: "panel",
                layout: "pimcoreform",
                border: false,
                autoScroll: true,
                deferredRender: false,
                hideMode: "offsets",
                title: pimcore.available_languages[pimcore.settings.websiteLanguages[i]],
                items: this.getRecursiveLayout(this.fieldConfig).items
            }));

        }

        this.tabPanel = new Ext.TabPanel(tabsConf);
        panelConf.items = [this.tabPanel];

        this.component = new Ext.Panel(panelConf);
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
                if(this.languageElements[currentLanguage][s].isDirty()) {
                    localizedData[currentLanguage][this.languageElements[currentLanguage][s].getName()] = this.languageElements[currentLanguage][s].getValue();
                }
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
        var invalidMandatoryFields = [];

        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {

            currentLanguage = pimcore.settings.websiteLanguages[i];

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                if(this.languageElements[currentLanguage][s].isMandatory()) {
                    if(this.languageElements[currentLanguage][s].isInvalidMandatory()) {
                        invalidMandatoryFields.push(this.languageElements[currentLanguage][s].getTitle() + " - " + currentLanguage.toUpperCase() + " (" + this.languageElements[currentLanguage][s].getName() + ")");
                        isInvalid = true;
                    }
                }
            }
        }

        // return the error messages not bool, this is handled in object/edit.js
        if(isInvalid) {
            return invalidMandatoryFields;
        }

        return isInvalid;
    }
});

pimcore.object.tags.localizedfields.addMethods(pimcore.object.helpers.edit);