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

/*global google */
pimcore.registerNS("pimcore.settings.targeting.personas.item");
pimcore.settings.targeting.personas.item = Class.create({

    initialize: function(parent, data) {
        this.parent = parent;
        this.data = data;
        this.currentIndex = 0;

        this.tabPanel = new Ext.TabPanel({
            activeTab: 0,
            title: this.data.name,
            closable: true,
            deferredRender: false,
            forceLayout: true,
            id: "pimcore_personas_panel_" + this.data.id,
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: this.save.bind(this)
            }],
            items: [this.getSettings(),this.getConditions()]
        });


        // fill data into conditions
        if(this.data.conditions && this.data.conditions.length > 0) {
            for(var i=0; i<this.data.conditions.length; i++) {
                this.addCondition("item" + ucfirst(this.data.conditions[i].type), this.data.conditions[i]);
            }
        }

        this.parent.panel.add(this.tabPanel);
        this.parent.panel.activate(this.tabPanel);
        this.parent.panel.doLayout();
    },

    getSettings: function () {

        this.settingsForm = new Ext.form.FormPanel({
            layout: "pimcoreform",
            title: t("settings"),
            bodyStyle: "padding:10px;",
            autoScroll: true,
            border:false,
            items: [{
                xtype: "textfield",
                fieldLabel: t("name"),
                name: "name",
                width: 250,
                disabled: true,
                value: this.data.name
            }, {
                name: "description",
                fieldLabel: t("description"),
                xtype: "textarea",
                width: 400,
                height: 100,
                value: this.data.description
            }]
        });

        return this.settingsForm;
    },

    getConditions: function() {
        var addMenu = [];
        var itemTypes = Object.keys(pimcore.settings.targeting.conditions);
        for(var i=0; i<itemTypes.length; i++) {
            if(itemTypes[i].indexOf("item") == 0) {
                addMenu.push({
                    iconCls: "pimcore_icon_add",
                    handler: this.addCondition.bind(this, itemTypes[i]),
                    text: pimcore.settings.targeting.conditions[itemTypes[i]](null, null,true)
                });
            }
        }

        this.conditionsContainer = new Ext.Panel({
            title: t("conditions"),
            autoScroll: true,
            forceLayout: true,
            tbar: [{
                iconCls: "pimcore_icon_add",
                menu: addMenu
            }],
            border: false
        });

        return this.conditionsContainer;
    },

    addCondition: function (type, data) {

        var item = pimcore.settings.targeting.conditions[type](this, data);

        // add logic for brackets
        item.on("afterrender", function (el) {
            el.getEl().applyStyles({position: "relative", "min-height": "40px"});
            var leftBracket = el.getEl().insertHtml("beforeEnd",
                                '<div class="pimcore_targeting_bracket pimcore_targeting_bracket_left">(</div>', true);
            var rightBracket = el.getEl().insertHtml("beforeEnd",
                                '<div class="pimcore_targeting_bracket pimcore_targeting_bracket_right">)</div>', true);

            if(data["bracketLeft"]){
                leftBracket.addClass("pimcore_targeting_bracket_active");
            }
            if(data["bracketRight"]){
                rightBracket.addClass("pimcore_targeting_bracket_active");
            }

            leftBracket.on("click", function (ev, el) {
                Ext.get(el).toggleClass("pimcore_targeting_bracket_active");
            });

            rightBracket.on("click", function (ev, el) {
                Ext.get(el).toggleClass("pimcore_targeting_bracket_active");
            });
        });

        this.conditionsContainer.add(item);
        item.doLayout();
        this.conditionsContainer.doLayout();

        this.currentIndex++;

        this.recalculateButtonStatus();
    },

    save: function () {

        var saveData = {};
        saveData["settings"] = this.settingsForm.getForm().getFieldValues();

        var conditionsData = [];
        var condition, tb, operator;
        var conditions = this.conditionsContainer.items.getRange();
        for (var i=0; i<conditions.length; i++) {
            condition = conditions[i].getForm().getFieldValues();

            // get the operator (AND, OR, AND_NOT)
            tb = conditions[i].getTopToolbar();
            if (tb.getComponent("toggle_or").pressed) {
                operator = "or";
            } else if (tb.getComponent("toggle_and_not").pressed) {
                operator = "and_not";
            } else {
                operator = "and";
            }
            condition["operator"] = operator;

            // get the brackets
            condition["bracketLeft"] = Ext.get(conditions[i].getEl().query(".pimcore_targeting_bracket_left")[0])
                                                                .hasClass("pimcore_targeting_bracket_active");
            condition["bracketRight"] = Ext.get(conditions[i].getEl().query(".pimcore_targeting_bracket_right")[0])
                                                                .hasClass("pimcore_targeting_bracket_active");

            conditionsData.push(condition);
        }
        saveData["conditions"] = conditionsData;

        Ext.Ajax.request({
            url: "/admin/reports/targeting/persona-save",
            params: {
                id: this.data.id,
                data: Ext.encode(saveData)
            },
            method: "post",
            success: function () {

            }.bind(this)
        });
    },

    recalculateButtonStatus: function () {
        var conditions = this.conditionsContainer.items.getRange();
        var tb;
        for (var i=0; i<conditions.length; i++) {
            tb = conditions[i].getTopToolbar();
            if(i==0) {
                tb.getComponent("toggle_and").hide();
                tb.getComponent("toggle_or").hide();
                tb.getComponent("toggle_and_not").hide();
            } else {
                tb.getComponent("toggle_and").show();
                tb.getComponent("toggle_or").show();
                tb.getComponent("toggle_and_not").show();
            }
        }
    }

});

