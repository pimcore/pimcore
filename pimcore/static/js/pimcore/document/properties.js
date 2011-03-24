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
 
pimcore.registerNS("pimcore.document.properties");
pimcore.document.properties = Class.create(pimcore.settings.properties,{

    disallowedKeys: ["language"],

    getLayout: function ($super) {
        this.layout = $super();


        // language
        var languageData;
        var languageRecord;
        var languageRecordIndex = this.propertyGrid.getStore().findBy(function (rec, id) {
            if(rec.get("name") == "language") {
                return true;
            }
        });
        if(languageRecordIndex >= 0) {
            languageRecord = this.propertyGrid.getStore().getAt(languageRecordIndex);
            if(languageRecord.get("data")) {
                languageData = languageRecord.get("data");
            }
        }
        var languagestore = [["",t("none")]];
        for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {
            languagestore.push([pimcore.settings.websiteLanguages[i],pimcore.settings.websiteLanguages[i]]);
        }

        var language = new Ext.form.ComboBox({
            fieldLabel: t('language'),
            name: "language",
            store: languagestore,
            editable: false,
            triggerAction: 'all',
            mode: "local",
            listWidth: 200,
            value: languageData
        });

        this.systemPropertiesPanel = new Ext.form.FormPanel({
            layout: "pimcoreform",
            title: t("system_properties"),
            width: 300,
            region: "east",
            bodyStyle: "padding: 30px 10px 10px 10px;",
            collapsible: true,
            items: [language]
        });

        this.layout.add(this.systemPropertiesPanel);

        return this.layout;
    },

    getValues : function ($super) {

        var values = $super();

        var systemValues = this.systemPropertiesPanel.getForm().getFieldValues();

        var addLanguage = false;
        if(systemValues.language) {
            var languageRecord;
            var languageRecordIndex = this.propertyGrid.getStore().findBy(function (rec, id) {
                if(rec.get("name") == "language") {
                    return true;
                }
            });
            if(languageRecordIndex >= 0) {
                languageRecord = this.propertyGrid.getStore().getAt(languageRecordIndex);
                if(languageRecord.get("data")) {
                    if(languageRecord.get("data") != systemValues.language) {
                        addLanguage = true;
                    }
                }
            } else {
                addLanguage = true;
            }

            if(addLanguage) {
                values["language"] = {
                    data: systemValues.language,
                    type: "text",
                    inheritable: true
                };
            }
        }

        if(!addLanguage) {
            if(values["language"]) {
                delete values.language;
            }
        }

        return values;
    }

});