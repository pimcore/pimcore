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

/*global google */
pimcore.registerNS("pimcore.settings.targeting.rules.item");
pimcore.settings.targeting.rules.item = Class.create({

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
            id: "pimcore_targeting_panel_" + this.data.id,
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: this.save.bind(this)
            }],
            items: [this.getSettings(),this.getConditions(), this.getActions()]
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

    getActions: function () {
        this.actionsForm = new Ext.form.FormPanel({
            layout: "pimcoreform",
            bodyStyle: "padding: 10px",
            title: t("actions"),
            autoScroll: true,
            border:false,
            items: [{
                xtype: "fieldset",
                title: t("redirect"),
                itemId: "actions_redirect",
                collapsible: true,
                collapsed: !this.data.actions.redirectEnabled,
                items: [{
                    xtype: "textfield",
                    width: 350,
                    fieldLabel: "URL",
                    name: "redirect.url",
                    value: this.data.actions.redirectUrl,
                    cls: "input_drop_target",
                    listeners: {
                        "render": function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: this,
                                ddGroup: "element",
                                getTargetFromEvent: function(e) {
                                    return this.getEl();
                                }.bind(el),

                                onNodeOver : function(target, dd, e, data) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                },

                                onNodeDrop : function (target, dd, e, data) {
                                    if (data.node.attributes.elementType == "document") {
                                        this.setValue(data.node.attributes.path);
                                        return true;
                                    }
                                    return false;
                                }.bind(el)
                            });
                        }
                    }
                }]
            }, {
                xtype: "fieldset",
                title: t("programmatically"),
                itemId: "actions_programmatically",
                collapsible: true,
                collapsed: !this.data.actions.programmaticallyEnabled,
                items: [{
                    xtype: "displayfield",
                    value: t("in_this_case_a_developer_has_to_implement_a_logic_which_handles_this_action")
                }]
            }, {
                xtype: "fieldset",
                title: t("event"),
                itemId: "actions_event",
                collapsible: true,
                collapsed: !this.data.actions.eventEnabled,
                items: [{
                    xtype: "textfield",
                    name: "event.key",
                    width: 200,
                    fieldLabel: t("key"),
                    value: this.data.actions.eventKey
                }, {
                    xtype: "textfield",
                    name: "event.value",
                    width: 100,
                    fieldLabel: t("value"),
                    value: this.data.actions.eventValue
                }]
            }, {
                xtype: "fieldset",
                itemId: "actions_codesnippet",
                title: t("code_snippet"),
                collapsible: true,
                collapsed: !this.data.actions.codesnippetEnabled,
                items: [{
                    xtype: "textarea",
                    width: 500,
                    height: 200,
                    fieldLabel: t("code"),
                    name: "codesnippet.code",
                    value: this.data.actions.codesnippetCode
                },{
                    xtype:'combo',
                    fieldLabel: t('element_css_selector'),
                    name: "codesnippet.selector",
                    disableKeyFilter: true,
                    store: [["body","body"],["head","head"]],
                    triggerAction: "all",
                    mode: "local",
                    width: 250,
                    value: this.data.actions.codesnippetSelector
                },{
                    xtype:'combo',
                    fieldLabel: t('insert_position'),
                    name: "codesnippet.position",
                    store: [["beginning",t("beginning")],["end",t("end")],["replace",t("replace")]],
                    triggerAction: "all",
                    typeAhead: false,
                    editable: false,
                    forceSelection: true,
                    mode: "local",
                    width: 250,
                    value: this.data.actions.codesnippetPosition
                }]
            }, {
                xtype: "fieldset",
                itemId: "actions_persona",
                title: t('associate_target_group') + " (" + t("personas") + ")",
                collapsible: true,
                collapsed: !this.data.actions.personaEnabled,
                items: [{
                    xtype: "combo",
                    name: "persona.id",
                    displayField:'text',
                    valueField: "id",
                    store: pimcore.globalmanager.get("personas"),
                    editable: false,
                    width: 300,
                    triggerAction: 'all',
                    listWidth: 200,
                    mode: "local",
                    value: this.data.actions.personaId,
                    emptyText: t("select_a_persona")
                }]
            }]
        });

        return this.actionsForm;
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
            }, {
                name: "scope",
                fieldLabel: t("scope"),
                xtype: "combo",
                width: 200,
                value: this.data["scope"],
                mode: "local",
                triggerAction: "all",
                store: [["user", t("user")],["session", t("session")], ["hit", t("hit")]]
            }, {
                name: "active",
                fieldLabel: t("active"),
                xtype: "checkbox",
                checked: this.data["active"]
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
        var tab = this;
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

            // open
            leftBracket.on("click", function (ev, el) {
                var bracket = Ext.get(el);
                bracket.toggleClass("pimcore_targeting_bracket_active");

                tab.recalculateBracketIdent(tab.conditionsContainer.items);
            });

            // close
            rightBracket.on("click", function (ev, el) {
                var bracket = Ext.get(el);
                bracket.toggleClass("pimcore_targeting_bracket_active");

                tab.recalculateBracketIdent(tab.conditionsContainer.items);
            });

            // make ident
            tab.recalculateBracketIdent(tab.conditionsContainer.items);
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
        saveData["actions"] = this.actionsForm.getForm().getFieldValues();
        saveData["actions"]["redirect.enabled"] = !this.actionsForm.getComponent("actions_redirect").collapsed;
        saveData["actions"]["event.enabled"] = !this.actionsForm.getComponent("actions_event").collapsed;
        saveData["actions"]["codesnippet.enabled"] = !this.actionsForm.getComponent("actions_codesnippet").collapsed;
        saveData["actions"]["persona.enabled"] = !this.actionsForm.getComponent("actions_persona").collapsed;
        saveData["actions"]["programmatically.enabled"] = !this.actionsForm.getComponent("actions_programmatically")
                                                                                                    .collapsed;

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
            url: "/admin/reports/targeting/rule-save",
            params: {
                id: this.data.id,
                data: Ext.encode(saveData)
            },
            method: "post",
            success: function () {
                pimcore.helpers.showNotification(t("success"), t("item_saved_successfully"), "success");
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
    },


    /**
     * make ident for bracket
     * @param list
     */
    recalculateBracketIdent: function(list) {
        var ident = 0, lastIdent = 0, margin = 20;
        var colors = ["transparent","#007bff", "#00ff99", "#e1a6ff", "#ff3c00", "#000000"];

        list.each(function (condition) {

            // only rendered conditions
            if(condition.rendered == false)
                return;

            // html from this condition
            var item = condition.getEl();


            // apply ident margin
            item.applyStyles({
                "margin-left": margin * ident + "px",
                "margin-right": margin * ident + "px"
            });


            // apply colors
            if(ident > 0)
                item.applyStyles({
                    "border-left": "1px solid " + colors[ident],
                    "border-right": "1px solid " + colors[ident],
                    "padding": "0px 1px"
                });
            else
                item.applyStyles({
                    "border-left": "0px",
                    "border-right": "0px"
                });


            // apply specials :-)
            if(ident == 0) {
                item.applyStyles({
                    "margin-top": "10px"
                });
            } else if(ident == lastIdent) {
                item.applyStyles({
                    "margin-top": "0px",
                    "margin-bottom": "0px",
                    "padding": "1px"
                });
            } else {
                item.applyStyles({
                    "margin-top": "5px"
                });
            }


            // remember current ident
            lastIdent = ident;


            // check if a bracket is open
            if(item.select('.pimcore_targeting_bracket_left.pimcore_targeting_bracket_active').getCount() == 1)
            {
                ident++;
            }
            // check if a bracket is close
            else if(item.select('.pimcore_targeting_bracket_right.pimcore_targeting_bracket_active').getCount() == 1)
            {
                if(ident > 0)
                    ident--;
            }
        });
    }
});

