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
pimcore.document.properties = Class.create(pimcore.element.properties,{


    disallowedKeys: ["language","navigation_exclude","navigation_name","navigation_title","navigation_relation","navigation_parameters","navigation_anchor","navigation_target","navigation_class","navigation_tabindex","navigation_accesskey"],

    inheritableKeys: ["language"],

    getPropertyData: function(name){

        // language
        var propertyData;
        var record;
        var recordIndex = this.propertyGrid.getStore().findBy(function (name, rec, id) {
            if(rec.get("name") == name) {
                return true;
            }
        }.bind(this,name));
        if(recordIndex >= 0) {
            record = this.propertyGrid.getStore().getAt(recordIndex);
            if(record.get("data")) {
                propertyData = record.get("data");
            }
        }
        return propertyData;

    },


    getLayout: function ($super) {

        if(!this.layout){

            this.layout = $super();

            var languageData = this.getPropertyData("language");

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

            this.languagesPanel = new Ext.form.FormPanel({
                layout: "pimcoreform",
                title: t("language_settings"),
                bodyStyle: "padding: 10px;",
                autoWidth: true,
                height: 80,
                collapsible: false,
                items: [language]
            });

            var navigationBasic = new Ext.form.FieldSet({
                title: t('navigation_basic'),
                autoHeight:true,
                collapsible: true,
                collapsed: false,
                items :[{
                            xtype: "textfield",
                            fieldLabel: t("name"),
                            value: this.getPropertyData("navigation_name"),
                            name: "navigation_name"
                        },{
                            xtype: "textfield",
                            fieldLabel: t('title'),
                            name: "navigation_title",
                            value: this.getPropertyData("navigation_title")
                        },{
                            xtype: "combo",
                            fieldLabel: t('navigation_target'),
                            name: "navigation_target",
                            store: ["","_blank","_self","_top","_parent"],
                            value: this.getPropertyData("navigation_target"),
                            editable: false,
                            triggerAction: 'all',
                            mode: "local",
                            width: 130,
                            listWidth: 200
                        },{
                            xtype: "checkbox",
                            fieldLabel: t('navigation_exclude'),
                            name: "navigation_exclude",
                            checked: this.getPropertyData("navigation_exclude")

                }]

            });

            var navigationEnhanced = new Ext.form.FieldSet({
                title: t('navigation_enhanced'),
                autoHeight:true,
                collapsible: true,
                collapsed: true,
                items :[{
                            xtype: "textfield",
                            fieldLabel: t('class'),
                            name: 'navigation_class',
                            value: this.getPropertyData("navigation_class")
                        },{
                            xtype: "textfield",
                            fieldLabel: t('anchor'),
                            name: 'navigation_anchor',
                            value: this.getPropertyData("navigation_anchor")
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t('parameters'),
                            name: "navigation_parameters",
                            value: this.getPropertyData("navigation_parameters")
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t('relation'),
                            name: "navigation_relation",
                            value: this.getPropertyData("navigation_relation")
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t('accesskey'),
                            name: "navigation_accesskey",
                            value: this.getPropertyData("navigation_accesskey")
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t('tabindex'),
                            name: "navigation_tabindex",
                            value: this.getPropertyData("navigation_tabindex")
                        }]

            });

            this.navigationPanel =  new Ext.form.FormPanel({
                layout: "pimcoreform",
                title: t("navigation_settings"),
                bodyStyle: "padding: 10px;",
                autoWidth: true,
                autoHeight:true,
                collapsible: false,
                items: [navigationBasic,navigationEnhanced]
            });

            var systempropertiesItems = [this.languagesPanel];
            if(this.element.type == "page" || this.element.type == "link") {
                systempropertiesItems = [this.languagesPanel,this.navigationPanel];
            }

            this.systemPropertiesPanel = new Ext.Panel({
                title: t("system_properties"),
                width: 300,
                region: "east",
                autoScroll: true,
                collapsible: true,
                items: systempropertiesItems
            });

            this.layout.add(this.systemPropertiesPanel);
        }
        return this.layout;
    },

    getValues : function ($super) {

        var values = $super();


        var languageValues = this.languagesPanel.getForm().getFieldValues();
        var navigationValues = this.navigationPanel.getForm().getFieldValues();

        var systemValues = array_merge(languageValues,navigationValues);

        for(var i=0;i<this.disallowedKeys.length;i++){

            var name = this.disallowedKeys[i];

            var addProperty = false;
            var unchanged = false;
            if(systemValues[name]) {

                var record;
                var recordIndex = this.propertyGrid.getStore().findBy(function (name,rec, id) {
                    if(rec.get("name") == name) {
                        return true;
                    }
                }.bind(this,name));

                if(recordIndex >= 0) {
                    record = this.propertyGrid.getStore().getAt(recordIndex);
                    if(record.get("data")) {
                        if(record.get("data") != systemValues[name]) {
                            addProperty = true;
                        } else if(record.get("data") == systemValues[name]) {
                            unchanged=true;
                        }
                    }
                } else {
                    addProperty = true;
                }

                if(addProperty) {
                    values[name] = {
                        data: systemValues[name],
                        type: "text",
                        inheritable: in_array(name,this.inheritableKeys)
                    };
                }
            }

            if(!addProperty && !unchanged) {
                if(values[name]) {
                    delete values[name];
                }
            }

        }
        return values;
    }

});