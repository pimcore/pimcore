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

/* global google */
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
            items: [
                this.getSettings(),
                this.getConditions(),
                this.getActions()
            ]
        });

        // fill data into conditions and actions
        this.initializeConditions();
        this.initializeActions();

        this.parent.panel.add(this.tabPanel);
        this.parent.panel.setActiveTab(this.tabPanel);
        this.parent.panel.updateLayout();
    },

    initializeConditions: function() {
        var condition;
        if (this.data.conditions && this.data.conditions.length > 0) {
            for (var i = 0; i < this.data.conditions.length; i++) {
                try {
                    condition = pimcore.settings.targeting.conditions.create(this.data.conditions[i].type);
                } catch (e) {
                    console.error(e);
                    continue;
                }

                if (!condition.matchesScope('targeting_rule')) {
                    console.error('Condition ', this.data.conditions[i].type, 'does not match rule scope');
                    continue;
                }

                this.addCondition(condition, this.data.conditions[i]);
            }
        }
    },

    initializeActions: function() {
        var action;
        if (this.data.actions && this.data.actions.length > 0) {
            for (var i = 0; i < this.data.actions.length; i++) {
                try {
                    action = pimcore.settings.targeting.actions.create(this.data.actions[i].type);
                } catch (e) {
                    console.error(e);
                    continue;
                }

                this.addAction(action, this.data.actions[i]);
            }
        }
    },

    getSettings: function () {
        this.settingsForm = new Ext.form.FormPanel({
            title: t("settings"),
            bodyStyle: "padding:10px;",
            autoScroll: true,
            border:false,
            items: [{
                xtype: "textfield",
                fieldLabel: t("name"),
                name: "name",
                width: 350,
                value: this.data.name
            }, {
                name: "description",
                fieldLabel: t("description"),
                xtype: "textarea",
                width: 500,
                height: 100,
                value: this.data.description
            }, {
                name: "scope",
                fieldLabel: t("action_scope"),
                xtype: "combo",
                width: 350,
                value: this.data["scope"],
                mode: "local",
                triggerAction: "all",
                editable: false,
                store: [
                    ["hit", t("hit")],
                    ["session", t("session")],
                    ["session_with_variables", t("session_with_variables")],
                    ["visitor", t("targeting_visitor")]
                ]
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
        var createHandler = function(condition) {
            return this.addCondition.bind(this, condition);
        }.bind(this);

        var addMenu = [];
        Ext.Array.forEach(pimcore.settings.targeting.conditions.getKeys(), function(key) {
            var condition;

            try {
                condition = pimcore.settings.targeting.conditions.create(key);
            } catch (e) {
                console.error(e);
                return;
            }

            if (!condition.matchesScope('targeting_rule')) {
                return;
            }

            addMenu.push({
                iconCls: condition.getIconCls(),
                text: condition.getName(),
                disabled: !condition.isAvailable(),
                handler: createHandler(condition)
            });
        });

        this.sortAddMenu(addMenu);

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

    getActions: function () {
        var createHandler = function(action) {
            return this.addAction.bind(this, action);
        }.bind(this);

        var addMenu = [];
        Ext.Array.forEach(pimcore.settings.targeting.actions.getKeys(), function(key) {
            var action;

            try {
                action = pimcore.settings.targeting.actions.create(key);
            } catch (e) {
                console.error(e);
                return;
            }

            addMenu.push({
                iconCls: action.getIconCls(),
                text: action.getName(),
                handler: createHandler(action)
            });
        });

        this.sortAddMenu(addMenu);

        this.actionsContainer = new Ext.Panel({
            title: t("actions"),
            autoScroll: true,
            forceLayout: true,
            bodyStyle: 'padding: 0 10px 10px 10px;',
            tbar: [{
                iconCls: "pimcore_icon_add",
                menu: addMenu
            }],
            border: false
        });

        return this.actionsContainer;
    },

    sortAddMenu: function(menu) {
        menu.sort(function(a, b) {
            var nameA = a.text.toUpperCase();
            var nameB = b.text.toUpperCase();

            if (nameA < nameB) {
                return -1;
            }

            if (nameA > nameB) {
                return 1;
            }

            return 0;
        });
    },

    addCondition: function (condition, data) {
        if ('undefined' === typeof data) {
            data = {};
        }

        var item = condition.getPanel(this, data);

        // add logic for brackets
        var tab = this;
        item.on("afterrender", function (el) {
            el.getEl().applyStyles({position: "relative", "min-height": "40px", "border-bottom": "1px solid #d0d0d0"});
            var leftBracket = el.getEl().insertHtml("beforeEnd",
                '<div class="pimcore_targeting_bracket pimcore_targeting_bracket_left">(</div>', true);
            var rightBracket = el.getEl().insertHtml("beforeEnd",
                '<div class="pimcore_targeting_bracket pimcore_targeting_bracket_right">)</div>', true);

            if (data["bracketLeft"]) {
                leftBracket.addCls("pimcore_targeting_bracket_active");
            }

            if (data["bracketRight"]) {
                rightBracket.addCls("pimcore_targeting_bracket_active");
            }

            // open
            leftBracket.on("click", function (ev, el) {
                var bracket = Ext.get(el);
                bracket.toggleCls("pimcore_targeting_bracket_active");

                tab.recalculateBracketIdent(tab.conditionsContainer.items);
            });

            // close
            rightBracket.on("click", function (ev, el) {
                var bracket = Ext.get(el);
                bracket.toggleCls("pimcore_targeting_bracket_active");

                tab.recalculateBracketIdent(tab.conditionsContainer.items);
            });

            // make ident
            tab.recalculateBracketIdent(tab.conditionsContainer.items);
        });

        this.conditionsContainer.add(item);
        item.updateLayout();
        this.conditionsContainer.updateLayout();

        this.currentIndex++;

        this.recalculateButtonStatus();
    },

    addAction: function(action, data) {
        if ('undefined' === typeof data) {
            data = {};
        }

        var item = action.getPanel(this, data);

        this.actionsContainer.add(item);
        item.updateLayout();
        this.actionsContainer.updateLayout();
    },

    save: function () {
        var saveData = {
            settings: this.settingsForm.getForm().getFieldValues(),
            conditions: this.getConditionData(),
            actions: this.getActionData()
        };

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_targeting_rulesave'),
            method: 'PUT',
            params: {
                id: this.data.id,
                data: Ext.encode(saveData)
            },
            success: function () {
                this.parent.getTree().getStore().load();
                pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
            }.bind(this)
        });
    },

    getConditionData: function () {
        var condition,
            tb;

        var conditions = this.conditionsContainer.items.getRange();

        var conditionData = [];
        for (var i = 0; i < conditions.length; i++) {
            condition = conditions[i].getForm().getFieldValues();

            // get the operator (AND, OR, AND_NOT)
            tb = conditions[i].getDockedItems()[0];
            if (tb.getComponent("toggle_or").pressed) {
                condition.operator = "or";
            } else if (tb.getComponent("toggle_and_not").pressed) {
                condition.operator = "and_not";
            } else {
                condition.operator = "and";
            }

            // get the brackets
            condition.bracketLeft = Ext.get(conditions[i].getEl().query(".pimcore_targeting_bracket_left")[0])
                .hasCls("pimcore_targeting_bracket_active");

            condition.bracketRight = Ext.get(conditions[i].getEl().query(".pimcore_targeting_bracket_right")[0])
                .hasCls("pimcore_targeting_bracket_active");

            conditionData.push(condition);
        }

        return conditionData;
    },

    getActionData: function() {
        var actions = this.actionsContainer.items.getRange();

        var actionData = [];
        for (var i = 0; i < actions.length; i++) {
            actionData.push(actions[i].getForm().getFieldValues());
        }

        return actionData;
    },

    recalculateButtonStatus: function () {
        var conditions = this.conditionsContainer.items.getRange();
        var tb;

        for (var i = 0; i < conditions.length; i++) {
            tb = conditions[i].getDockedItems()[0];

            if (i === 0) {
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
            if(condition.rendered == false) {
                return;
            }

            // html from this condition
            var item = condition.getEl();


            // apply ident margin
            item.applyStyles({
                "margin-left": margin * ident + "px",
                "margin-right": margin * ident + "px"
            });


            // apply colors
            if(ident > 0) {
                item.applyStyles({
                    "border-left": "1px solid " + colors[ident],
                    "border-right": "1px solid " + colors[ident]
                });
            } else {
                item.applyStyles({
                    "border-left": "0px",
                    "border-right": "0px"
                });
            }


            // apply specials :-)
            if(ident == 0) {
                item.applyStyles({
                    "margin-top": "10px"
                });
            } else if(ident == lastIdent) {
                item.applyStyles({
                    "margin-top": "0px",
                    "margin-bottom": "0px"
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
                if(ident > 0) {
                    ident--;
                }
            }
        });

        this.conditionsContainer.updateLayout();
    }
});

