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

pimcore.registerNS("pimcore.object.tags.classificationstore");
pimcore.object.tags.classificationstore = Class.create(pimcore.object.tags.abstract, {

    type: "classificationstore",

    initialize: function (data, fieldConfig) {

        this.activeGroups = {};
        this.groupCollectionMapping = {};
        this.languageElements = {};
        this.groupElements = {};
        this.languagePanels = {};

        this.data = "";

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
            if (data.activeGroups) {
                this.activeGroups = data.activeGroups;
            }

            if (data.groupCollectionMapping) {
                this.groupCollectionMapping = data.groupCollectionMapping;
            }
        }
        this.fieldConfig = fieldConfig;

        if (this.fieldConfig.localized) {
            if (pimcore.currentuser.admin || fieldConfig.permissionView === undefined) {
                this.frontendLanguages = pimcore.settings.websiteLanguages.slice(0);
                this.frontendLanguages.unshift("default");
            } else {
                this.frontendLanguages = fieldConfig.permissionView;
            }
        } else {
            this.frontendLanguages = [];
            this.frontendLanguages.unshift("default");
        }

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

        this.dropdownLayout = false;
    },

    getGridColumnEditor: function(field) {
        return false;
    },

    getGridColumnFilter: function(field) {
        return false;
    },

    getLayoutEdit: function () {

        this.fieldConfig.datatype ="layout";
        this.fieldConfig.fieldtype = "panel";

        var wrapperConfig = {
            bodyCls: "pimcore_object_tag_classification_store",
            border: true,
            style: "margin-bottom: 10px",
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

        var nrOfLanguages = this.frontendLanguages.length;

        var tbarItems = [];

        if (!this.fieldConfig.noteditable && !this.fieldConfig.disallowAddRemove) {
            tbarItems.push(
                {
                    xtype: 'button',
                    iconCls: "pimcore_icon_add",
                    handler: function() {
                        var storeId = this.fieldConfig.storeId;
                        var keySelectionWindow = new pimcore.object.classificationstore.keySelectionWindow(
                            {
                                parent: this,
                                enableGroups: true,
                                enableCollections: true,
                                enableGroupByKey: true,
                                storeId: storeId,
                                object: this.object,
                                fieldname: this.fieldConfig.name,
                                maxItems: this.fieldConfig.maxItems
                            }
                        );
                        keySelectionWindow.show();
                    }.bind(this)
                }
            );
        }

        if (this.dropdownLayout) {

        } else {
            var panelConf = {
                autoScroll: true,
                cls: "object_field object_field_type_" + this.type,
                activeTab: 0,
                height: "auto",
                items: [],
                deferredRender: true,
                forceLayout: true,
                enableTabScroll: true,
                tbar: {
                    items: tbarItems
                }
            };

            if(this.fieldConfig.height) {
                panelConf.height = this.fieldConfig.height;
                panelConf.autoHeight = false;
            }


            for (var i=0; i < nrOfLanguages; i++) {
                this.currentLanguage = this.frontendLanguages[i];
                this.languageElements[this.currentLanguage] = [];
                this.groupElements[this.currentLanguage] = {};

                var childItems = [];


                for (var groupId in this.fieldConfig.activeGroupDefinitions) {
                    var groupedChildItems = [];

                    if (this.fieldConfig.activeGroupDefinitions.hasOwnProperty(groupId)) {
                        var group = this.fieldConfig.activeGroupDefinitions[groupId];

                        var fieldset = this.createGroupFieldset(this.currentLanguage, group, groupedChildItems);

                        childItems.push(fieldset);

                    }
                }
                var title = this.frontendLanguages[i];
                if (title != "default") {
                    var title = t(pimcore.available_languages[title]);
                    var icon = "pimcore_icon_language_" + this.frontendLanguages[i].toLowerCase();
                } else {
                    var title = t(title);
                    var icon = "pimcore_icon_white_flag";
                }

                var item = new Ext.Panel({
                    border: false,
                    height: 'auto',
                    padding: "10px",
                    deferredRender: false,
                    hideMode: "offsets",
                    iconCls: icon,
                    title: title,
                    items: childItems
                });

                this.languagePanels[this.currentLanguage] = item;

                if (this.fieldConfig.labelWidth) {
                    item.labelWidth = this.fieldConfig.labelWidth;
                }

                panelConf.items.push(item);
            }


            this.tabPanel = new Ext.TabPanel(panelConf);

            wrapperConfig.items = [this.tabPanel];

        }

        this.currentLanguage = this.frontendLanguages[0];

        this.component = new Ext.Panel(wrapperConfig);

        this.component.updateLayout();
        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        return this.component;
    },

    getValue: function () {
        var localizedData = {};
        var currentLanguage;

        for (var i=0; i < this.frontendLanguages.length; i++) {
            currentLanguage = this.frontendLanguages[i];
            localizedData[currentLanguage] = {};

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                if(this.languageElements[currentLanguage][s].isDirty()) {
                    var languageElement = this.languageElements[currentLanguage][s];
                    var groupId =  languageElement.fieldConfig.csGroupId;
                    var keyId = languageElement.fieldConfig.csKeyId;
                    var value = languageElement.getValue();

                    if (!localizedData[currentLanguage][groupId]) {
                        localizedData[currentLanguage][groupId] = {};
                    }

                    localizedData[currentLanguage][groupId][keyId] = value;

                }
            }
        }

        var activeGroups = {};

        for (var key in this.activeGroups) {
            if (this.activeGroups.hasOwnProperty(key)) {
                if (this.activeGroups[key]) {
                    activeGroups[key] = true;
                }
            }
        }

        return {
            "data" : localizedData,
            "activeGroups": activeGroups,
            "groupCollectionMapping" : this.groupCollectionMapping
        };

    },

    getName: function () {
        return this.fieldConfig.name;
    },

    addToDataFields: function (field, name) {
        this.languageElements[this.currentLanguage].push(field);
    },

    getDataForField: function (fieldConfig) {

        var groupId = fieldConfig.csGroupId;
        var keyId = fieldConfig.csKeyId;

        try {
            if (this.data[this.currentLanguage]) {
                if (this.data[this.currentLanguage][groupId]) {
                    if (typeof this.data[this.currentLanguage][groupId][keyId] !== undefined) {
                        return this.data[this.currentLanguage][groupId][keyId];
                    }
                }
            }
        } catch (e) {
            console.log(e);
        }
        return;
    },

    getMetaDataForField: function(fieldConfig) {

        var groupId = fieldConfig.csGroupId;
        var keyId = fieldConfig.csKeyId;

        try {
            if (this.metaData[this.currentLanguage]) {
                if (this.metaData[this.currentLanguage][groupId]) {
                    if (typeof this.metaData[this.currentLanguage][groupId][keyId] !== "undefined") {
                        return this.metaData[this.currentLanguage][groupId][keyId];
                    }

                }
            }
        } catch (e) {
            console.log(e);
        }
        return;

    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        if (this.groupModified) {
            return true;
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
        var currentLanguage;

        for (var i=0; i < this.frontendLanguages.length; i++) {

            currentLanguage = this.frontendLanguages[i];

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {
                if(this.languageElements[currentLanguage][s].isMandatory()) {
                    return true;
                }
            }
        }

        return false;
    },

    createGroupFieldset: function (language, group, groupedChildItems, isNew) {
        var groupId = group.id;
        var groupTitle = group.description ? t(group.name) + " - " + t(group.description) : t(group.name);
        var invisibleItems = [];

        var editable = !this.fieldConfig.noteditable &&
            (pimcore.currentuser.admin
                || this.fieldConfig.permissionEdit === undefined
                || this.fieldConfig.permissionEdit.length == 0
                || in_array(this.currentLanguage, this.fieldConfig.permissionEdit));


        var csKeys = group.keys;
        var expandable = false;

        var index = -1;

        for (var k = 0; k < csKeys.length; k++) {
            index++;

            var csKey = csKeys[k];
            var definition = csKey.definition;

            definition.csKeyId = csKey.id;
            definition.csGroupId = group.id;

            if (this.fieldConfig.labelWidth) {
                definition.labelWidth = this.fieldConfig.labelWidth;
            }

            // creating the fallback tooltip or translate the fallback given from the api
            if (!definition.tooltip || definition.tooltip.indexOf(csKey.name + " - ") == 0) {
                definition.tooltip = t(csKey.name) + " - " + t(csKey.description);
            } else {
                definition.tooltip = t(definition.tooltip);
            }

            if (this.fieldConfig.hideEmptyData && !isNew) {
                // check if we should hide the feature because it is empty but only if the group hasn't been just added added via the dialog
                if (!this.data[language] || !this.data[language][group.id] || typeof this.data[language][group.id][csKey.id] === "undefined") {
                    expandable = true;

                    invisibleItems.push({
                        "definition": definition,
                        "index": index
                    });

                    continue;
                }
            }

            var context = this.getContext();
            context["type"] = this.type;

            if (isNew) {
                context["applyDefaults"] = true;
            }

            var childItem = this.getRecursiveLayout(definition, !editable, context);

            groupedChildItems.push(childItem);
        }

        var config = {
            title: groupTitle,
            items: groupedChildItems,
            collapsible: true
        };

        var tools = [];
        if (!this.fieldConfig.noteditable && !this.fieldConfig.disallowAddRemove) {
            tools.push(
                {
                    type: 'close',
                    qtip: t('remove_group'),
                    handler: function () {
                        this.deleteGroup(groupId);
                    }.bind(this)

                });
        }

        if (expandable) {
            var expandableId = Ext.id();
            tools.push(
                {
                    type: 'expand',
                    qtip: t('expand_cs_group'),
                    id: expandableId,
                    handler: function (editable, groupId, language, e, el, legend, tool) {
                        if (tool.__isExpanding) {
                            return;
                        }
                        tool.__isExpanding = true;
                        var invisibleItems = tool.__invisibleItems;
                        if (!invisibleItems) {
                            return;
                        }

                        tool.el.dom.classList.add('x-tool-expanding');

                        window.setTimeout(function (editable, groupId, language, e, el, legend, tool) {
                            Ext.suspendLayouts();

                            var fieldset = this.groupElements[language][groupId];

                            var currentLanguage = this.currentLanguage;
                            // switch the language before call getRecursiveLayout (getDataForField)
                            this.currentLanguage = language;

                            for (var i = 0; i < invisibleItems.length; i++) {
                                var item = invisibleItems[i];
                                var definition = item["definition"];
                                var index = item["index"];
                                var childItem = this.getRecursiveLayout(definition, !editable);
                                fieldset.insert(index, childItem);
                            }

                            this.currentLanguage = currentLanguage;
                            Ext.resumeLayouts(true);
                            tool.hide();

                            // not needed anymore
                            delete tool.__invisibleItems;
                            this.object.hotUpdateInitData();
                        }.bind(this, editable, groupId, language, e, el, legend, tool), 0);


                    }.bind(this, editable, groupId, language)

                });


        }

        if (tools) {
            config.tools = tools;
        }

        if (isNew) {
            config.cls = "pimcore_new_cs_group";
        }

        var fieldset = new Ext.create('pimcore.FieldSetTools', config);

        if (expandable) {
            var expandableTool = Ext.getCmp(expandableId);
            expandableTool.__invisibleItems = invisibleItems;

        }

        this.groupElements[language][groupId] = fieldset;
        return fieldset;
    },

    deleteGroup: function(groupId) {
        var currentLanguage;

        this.groupModified = true;

        for (var i=0; i < this.frontendLanguages.length; i++) {

            currentLanguage = this.frontendLanguages[i];

            var fieldset = this.groupElements[currentLanguage][groupId];
            if (fieldset) {
                fieldset.destroy();
                var languagePanel = this.languagePanels[currentLanguage];
                languagePanel.updateLayout();
            } else {
                console.log("no fieldset???");
            }

            delete this.groupElements[currentLanguage][groupId];

            for (var j = this.languageElements[currentLanguage].length - 1; j >= 0; j--) {
                var element = this.languageElements[currentLanguage][j];
                if (element.fieldConfig.csGroupId == groupId) {
                    this.languageElements[currentLanguage].splice(j, 1);
                }

            }
        }

        this.component.updateLayout();

        delete this.activeGroups[groupId];
        delete this.groupCollectionMapping[groupId];

    },

    handleAddGroups: function (response) {
        var data = Ext.decode(response.responseText);

        var addedGroups= {};

        var nrOfLanguages = this.frontendLanguages.length;

        var activeLanguage = this.currentLanguage;

        var newGroupIds = [];

        for (var groupId in data) {
            if (!this.activeGroups[groupId]) {
                newGroupIds.push(groupId);
            }
        }

        if (
            this.fieldConfig.maxItems > 0 &&
            (this.getUsedActiveGroups().length + newGroupIds.length) > this.fieldConfig.maxItems
        ) {
            pimcore.helpers.showNotification(t('validation_failed'), t('limit_reached'), 'error');

            return;
        }

        for (var i=0; i < nrOfLanguages; i++) {
            var currentLanguage = this.frontendLanguages[i];
            this.currentLanguage = currentLanguage;

            for (let g = 0; g < newGroupIds.length; g++ ) {
                let groupId = newGroupIds[g];
                var groupedChildItems = [];

                if (data.hasOwnProperty(groupId)) {

                    var group = data[groupId];

                    addedGroups[groupId] = true;
                    this.groupCollectionMapping[groupId] = group.collectionId;

                    var fieldset = this.createGroupFieldset(currentLanguage, group, groupedChildItems, true);
                    var panel = this.languagePanels[currentLanguage];

                    panel.add(fieldset);
                    fieldset.updateLayout();

                    this.groupModified = true;
                }
            }
        }

        for (var groupId in addedGroups) {
            this.activeGroups[groupId] = true;
        }

        this.component.updateLayout();
        this.currentLanguage = activeLanguage;

    },

    handleSelectionWindowClosed: function() {
        // nothing to do
    },

    requestPending: function() {
        // nothing to do
    },

    dataIsNotInherited: function() {

        if (!this.inherited) {
            return true;
        }

        var foundUnmodifiedInheritedField = false;
        for (var i=0; i < this.frontendLanguages.length; i++) {

            var currentLanguage = this.frontendLanguages[i];

            for (var s=0; s<this.languageElements[currentLanguage].length; s++) {

                if (this.metaData[currentLanguage]) {
                    var languageElement = this.languageElements[currentLanguage][s];
                    var fieldConfig = languageElement.fieldConfig;
                    var groupId = fieldConfig.csGroupId;
                    var keyId = fieldConfig.csKeyId;

                    if (this.metaData[currentLanguage][groupId][keyId]) {
                        if (this.metaData[currentLanguage][groupId][keyId].inherited) {
                            if(languageElement.isDirty()) {
                                this.metaData[currentLanguage][groupId][keyId].inherited = false;
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
    },

    getUsedActiveGroups: function () {
        var activeGroups = [];

        // The array must be checked for empty entries
        for (var key in this.activeGroups) {
            if (this.activeGroups[key]) {
                activeGroups.push(parseInt(key));
            }
        }

        return activeGroups;
    }
});

pimcore.object.tags.classificationstore.addMethods(pimcore.object.helpers.edit);
