/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.tags.localizedfields");
pimcore.object.tags.localizedfields = Class.create(pimcore.object.tags.abstract, {

    type: "localizedfields",

    frontendLanguages: null,

    initialize: function (data, fieldConfig) {

        this.data = {};
        this.metaData = {};
        this.inherited = false;
        this.languageElements = {};
        this.inheritedFields = {};
        this.referencedFields = [];
        this.availablePanels = [];
        this.dropdownLayout = false;

        if (pimcore.currentuser.admin || fieldConfig.permissionView === undefined) {
            this.frontendLanguages = pimcore.settings.websiteLanguages;
        } else {
            this.frontendLanguages = fieldConfig.permissionView;
        }

        var maxTabs = 15;
        if (typeof fieldConfig.maxTabs == "number") {
            maxTabs = fieldConfig.maxTabs;
        }

        if (this.frontendLanguages.length > maxTabs) {
            this.dropdownLayout = true;
        }

        if (data) {
            if (data.data) {
                this.data = data.data;
            }
            if (data.metaData) {
                this.metaData = data.metaData;
            }
            if (data.inherited) {
                this.inherited = data.inherited;
            }
        }
        this.fieldConfig = fieldConfig;

        this.keysToWatch = [];

        if (this.inherited) {
            for (var i=0; i < this.frontendLanguages.length; i++) {
                var currentLanguage = this.frontendLanguages[i];

                var metadataForLanguage = this.metaData[currentLanguage];
                var dataKeys = Object.keys(metadataForLanguage);

                for (var k = 0; k < dataKeys.length; k++) {
                    var dataKey = dataKeys[k];
                    var metadataForKey = metadataForLanguage[dataKey];
                    if (metadataForKey.inherited) {
                        this.keysToWatch.push({
                            lang: currentLanguage,
                            key: dataKey
                        });
                    }
                }
            }
        }
    },

    getLayoutEdit: function (showMode) {

        this.fieldConfig.datatype ="layout";
        this.fieldConfig.fieldtype = "panel";

        var wrapperConfig = {
            border: false,
            layout: "fit"
        };

        if(this.fieldConfig.width) {
            wrapperConfig.width = this.fieldConfig.width;
        }

        if(this.fieldConfig.region) {
            wrapperConfig.region = this.fieldConfig.region;
        }

        if(this.fieldConfig.title) {
            wrapperConfig.title = this.fieldConfig.title;
        }

        if (this.context.containerType == "fieldcollection") {
            this.context.subContainerType = "localizedfield";
        } else {
            this.context.containerType = "localizedfield";
        }

        var nrOfLanguages = this.frontendLanguages.length;

        if (this.dropdownLayout) {
            //TODO choose default language
            var data = [];
            for (var i = 0; i < nrOfLanguages; i++) {
                var language = this.frontendLanguages[i];
                data.push([language, ts(pimcore.available_languages[language])]);
            }

            var store = new Ext.data.ArrayStore({
                    fields: ["key", "value"],
                    data: data
                }
            );

            var options = {
                triggerAction: "all",
                editable: true,
                selectOnFocus: true,
                queryMode: 'local',
                typeAhead: true,
                forceSelection: true,
                store: store,
                componentCls: "object_field",
                mode: "local",
                width: 300,
                padding: 10,
                displayField: "value",
                valueField: "key",
                value: this.frontendLanguages[0],
                listeners:  {
                    select:    function( combo, record, index ) {
                        var oldLanguage = this.currentLanguage;
                        var newLanguage = record.data.key;
                        if (oldLanguage == newLanguage) {
                            return;
                        }

                        this.availablePanels[oldLanguage].hide();
                        this.availablePanels[newLanguage].show();
                        this.currentLanguage = newLanguage;
                        this.component.updateLayout();
                    }.bind(this)
                }
            };

            this.countrySelect = new Ext.form.ComboBox(options);

            wrapperConfig.items = [];

            //TODO choose default language, maybe user-specific ?
            for (var i = nrOfLanguages - 1; i >= 0; i--) {
                this.currentLanguage = this.frontendLanguages[i];
                this.languageElements[this.currentLanguage] = [];

                var editable =  !showMode && (pimcore.currentuser.admin ||
                    this.fieldConfig.permissionEdit === undefined ||  this.fieldConfig.permissionEdit.length == 0 || in_array(this.currentLanguage, this.fieldConfig.permissionEdit));

                var runtimeContext = Ext.clone(this.context);
                runtimeContext.language = Ext.clone(this.currentLanguage);
                var items =  this.getRecursiveLayout(this.fieldConfig, !editable, runtimeContext).items;

                var panelConf = {
                    height: "auto",
                    border: false,
                    padding: "10px",
                    items: items,
                    hidden: (i > 0)     //TODO default language
                };


                if(this.fieldConfig.height) {
                    panelConf.height = this.fieldConfig.height;
                    panelConf.autoHeight = false;
                    panelConf.autoScroll = true;
                } else {
                    panelConf.autoHeight = true;
                }

                if (this.fieldConfig.labelWidth) {
                    panelConf.labelWidth = this.fieldConfig.labelWidth;
                }

                this.tabPanel = new Ext.Panel(panelConf);

                this.availablePanels[this.currentLanguage] = this.tabPanel;
                wrapperConfig.items.push(this.tabPanel);

                wrapperConfig.tbar = [new Ext.Toolbar.TextItem({
                    text: t("language")
                }), this.countrySelect];
            }
        } else {
            var panelConf = {
                monitorResize: true,
                cls: "object_field",
                activeTab: 0,
                height: "auto",
                items: [],
                deferredRender: true,
                forceLayout: true,
                hideMode: "offsets",
                enableTabScroll:true
            };

            if(this.fieldConfig.height) {
                panelConf.height = this.fieldConfig.height;
                panelConf.autoHeight = false;
            }

            for (var i=0; i < nrOfLanguages; i++) {
                this.currentLanguage = this.frontendLanguages[i];
                this.languageElements[this.currentLanguage] = [];

                var editable =  (pimcore.currentuser.admin ||
                    this.fieldConfig.permissionEdit === undefined ||  this.fieldConfig.permissionEdit.length == 0 || in_array(this.currentLanguage, this.fieldConfig.permissionEdit));

                var runtimeContext = Ext.clone(this.context);
                runtimeContext.language = Ext.clone(this.currentLanguage);
                var items = this.getRecursiveLayout(this.fieldConfig, !editable, runtimeContext);

                var item = {
                    xtype: "panel",
                    border:false,
                    autoScroll: true,
                    padding: "10px",
                    deferredRender: false,
                    hideMode: "offsets",
                    iconCls: "pimcore_icon_language_" + this.frontendLanguages[i].toLowerCase(),
                    title: pimcore.available_languages[this.frontendLanguages[i]],
                    items: items.items
                };

                if (this.fieldConfig.labelWidth) {
                    item.labelWidth = this.fieldConfig.labelWidth;
            }

                panelConf.items.push(item);
            }

            this.tabPanel = new Ext.TabPanel(panelConf);

            wrapperConfig.items = [this.tabPanel];
        }

        wrapperConfig.border = true;
        wrapperConfig.style = "margin-bottom: 10px";
        wrapperConfig.cls = "object_localizedfields_panel";

        this.component = new Ext.Panel(wrapperConfig);
        this.component.updateLayout();

        this.fieldConfig.datatype ="data";
        this.fieldConfig.fieldtype = "localizedfields";

        return this.component;
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit(true);
        return this.component;
    },

    getDataForField: function (fieldConfig) {
        var name = fieldConfig.name;
        try {
            if (this.data[this.currentLanguage]) {
                if (typeof this.data[this.currentLanguage][name] !== undefined){
                    return this.data[this.currentLanguage][name];
                }
            }
        } catch (e) {
            console.log(e);
        }
        return;
    },

    getMetaDataForField: function(fieldConfig) {
        var name = fieldConfig.name;
        try {
            if (this.metaData[this.currentLanguage]) {
                if (this.metaData[this.currentLanguage][name]) {
                    return this.metaData[this.currentLanguage][name];
                } else if (typeof this.data[this.currentLanguage][name] !== undefined){
                    return null;
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

    addReferencedField: function (field) {
        this.referencedFields.push(field);
    },

    getValue: function () {

        var localizedData = {};
        var currentLanguage;

        for (var i=0; i < this.frontendLanguages.length; i++) {
            currentLanguage = this.frontendLanguages[i];
            localizedData[currentLanguage] = {};

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                try {

                    if(this.languageElements[currentLanguage][s].isDirty()) {
                        localizedData[currentLanguage][this.languageElements[currentLanguage][s].getName()]
                            = this.languageElements[currentLanguage][s].getValue();
                    }

                } catch (e) {

                    console.log(e);
                    localizedData[currentLanguage][this.languageElements[currentLanguage][s].getName()] = "";

                }
            }
        }

        // also add the referenced localized fields
        if(this.referencedFields.length > 0) {
            for(var r=0; r<this.referencedFields.length; r++) {
                localizedData = array_merge_recursive(localizedData, this.referencedFields[r].getValue());
            }
        }

        return localizedData;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function() {

        // also check the referenced localized fields
        if(this.referencedFields.length > 0) {
            for(var r=0; r<this.referencedFields.length; r++) {
                if(this.referencedFields[r].isDirty()) {
                    return true;
                }
            }
        }

        if(!this.isRendered()) {
            return false;
        }

        var currentLanguage;

        for (var i=0; i < this.frontendLanguages.length; i++) {

            currentLanguage = this.frontendLanguages[i];

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                if(this.languageElements[currentLanguage][s].isDirty()) {
                    return true;
                }
            }
        }

        return false;
    },

    isMandatory: function () {

        // also check the referenced localized fields
        if(this.referencedFields.length > 0) {
            for(var r=0; r<this.referencedFields.length; r++) {
                if(this.referencedFields[r].isMandatory()) {
                    return true;
                }
            }
        }

        var currentLanguage;

        for (var i=0; i < this.frontendLanguages; i++) {

            currentLanguage = this.frontendLanguages[i];

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                if(this.languageElements[currentLanguage][s].isMandatory()) {
                    return true;
                }
            }
        }

        return false;
    },

    isInvalidMandatory: function () {

        // also check the referenced localized fields
        if(this.referencedFields.length > 0) {
            for(var r=0; r<this.referencedFields.length; r++) {
                if(this.referencedFields[r].isInvalidMandatory()) {
                    return true;
                }
            }
        }

        var currentLanguage;
        var isInvalid = false;
        var invalidMandatoryFields = [];

        for (var i=0; i < this.frontendLanguages.length; i++) {

            currentLanguage = this.frontendLanguages[i];

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                if(this.languageElements[currentLanguage][s].isMandatory()) {
                    if(this.languageElements[currentLanguage][s].isInvalidMandatory()) {
                        invalidMandatoryFields.push(this.languageElements[currentLanguage][s].getTitle() + " - "
                            + currentLanguage.toUpperCase() + " ("
                            + this.languageElements[currentLanguage][s].getName() + ")");
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
    },

    dataIsNotInherited: function() {

        // also check the referenced localized fields
        if(this.referencedFields.length > 0) {
            for(var r=0; r<this.referencedFields.length; r++) {
                this.referencedFields[r].dataIsNotInherited();
            }
        }

        if (!this.inherited) {
            return true;
        }

        var foundUnmodifiedInheritedField = false;
        for (var i=0; i < this.frontendLanguages.length; i++) {

            var currentLanguage = this.frontendLanguages[i];

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {

                if (this.metaData[currentLanguage]) {
                    var languageElement = this.languageElements[currentLanguage][s];
                    var key = languageElement.name;
                    if (this.metaData[currentLanguage][key]) {
                        if (this.metaData[currentLanguage][key].inherited) {
                            if(languageElement.isDirty()) {
                                this.metaData[currentLanguage][key].inherited = false;
                                languageElement.unmarkInherited();
                            } else {
                                foundUnmodifiedInheritedField = true;
                            }
                        }
                    }
                }
            }
        }

        if (!foundUnmodifiedInheritedField) {
            this.inherited = false;
        }
        return !this.inherited;
    },

    markInherited:function (metaData) {
        // nothing to do, only sub-elements can be marked
    }

});

pimcore.object.tags.localizedfields.addMethods(pimcore.object.helpers.edit);