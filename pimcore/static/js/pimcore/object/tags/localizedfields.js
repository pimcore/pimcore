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

    initialize: function (data, layoutConf) {

        this.data = {};
        this.languageElements = {};

        if (data) {
            this.data = data;
        }
        this.layoutConf = layoutConf;
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




        if(!this.layoutConf.width) {
            panelConf.listeners = {
                afterrender: function () {
                    this.layout.ownerCt.doLayout();
                    this.layout.setWidth(this.layout.ownerCt.getWidth()-45);
                }.bind(this)
            };
        }

        if(this.layoutConf.width) {
            panelConf.width = this.layoutConf.width;
        }

        if(this.layoutConf.height) {
            panelConf.height = this.layoutConf.height;
            panelConf.autoHeight = false;
        }

        if(this.layoutConf.layout) {
            panelConf.layout = this.layoutConf.layout;
        }

        if(this.layoutConf.region) {
            panelConf.region = this.layoutConf.region;
        }

        if(this.layoutConf.title) {
            panelConf.title = this.layoutConf.title;
        }


        this.layoutConf.datatype ="layout";
        this.layoutConf.fieldtype = "panel";

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
                items: this.getRecursiveLayout(this.layoutConf).items
            });

        }

        this.layout = new Ext.TabPanel(panelConf);

        return this.layout;
    },

    getLayoutShow: function () {

        this.layout = this.getLayoutEdit();
        this.layout.disable();

        return this.layout;
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
        return this.layoutConf.name;
    },

    isDirty: function() {
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


    }
});

pimcore.object.tags.localizedfields.addMethods(pimcore.object.helpers.edit);