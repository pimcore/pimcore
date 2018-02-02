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


pimcore.registerNS("pimcore.bundle.EcommerceFramework.pricing.config.item");
pimcore.bundle.EcommerceFramework.pricing.config.item = Class.create({

    /**
     * pimcore.bundle.EcommerceFramework.pricing.config.panel
     */
    parent: {},


    /**
     * constructor
     * @param parent
     * @param data
     */
    initialize: function(parent, data) {
        this.parent = parent;
        this.data = data;
        this.currentIndex = 0;

        this.tabPanel = new Ext.TabPanel({
            title: this.data.name,
            closable: true,
            deferredRender: false,
            forceLayout: true,
            id: "pimcore_pricing_panel_" + this.data.id,
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
        this.tabPanel.on("beforedestroy", function () {
            delete this.parent.panels["pricingrule_" + this.data.id];
        }.bind(this));


        // add saved conditions
        if(this.data.condition)
        {
            var list = this;
            var level = 0;
            var open = 0;
            var handleCondition = function(condition){
                if(condition.type == 'Bracket')
                {
                    // workarround for brackets
                    level++;
                    Ext.each(condition.conditions, function(item, index, allItems){
                        item.condition.operator = item.operator;

                        if(level > 1)
                        {
                            if(index == 0)
                            {
                                item.condition.bracketLeft = true;
                                open++;
                            }
                            if(index == allItems.length -1 && open > 0)
                            {
                                item.condition.bracketRight = true;
                                open--;
                            }
                        }

                        handleCondition(item.condition);
                    });
                }
                else
                {
                    // normal condition
                    list.addCondition("condition" + ucfirst(condition.type), condition);
                }
            };

            handleCondition(this.data.condition);
        }

        // add saved actions
        if(this.data.actions)
        {
            var list = this;
            Ext.each(this.data.actions, function(action){
                list.addAction("action" + ucfirst(action.type), action);
            });
        }

        // ...
        var panel = this.parent.getTabPanel();
        panel.add(this.tabPanel);
        panel.setActiveTab(this.tabPanel);
        panel.updateLayout();
    },

    activate: function () {
        var panel = this.parent.getTabPanel();
        panel.setActiveTab(this.tabPanel);
        panel.updateLayout();
    },

    /**
     * Basic rule Settings
     * @returns Ext.form.FormPanel
     */
    getSettings: function () {
        var data = this.data;

        // create tabs for available languages
        var langTabs = [];
        Ext.each(pimcore.settings.websiteLanguages, function(lang){
            var tab = {
                title: pimcore.available_languages[ lang ],
                items: [{
                    xtype: "textfield",
                    name: "label." + lang,
                    fieldLabel: t("label"),
                    width: 350,
                    value: data.label[ lang ]
                }, {
                    xtype: "textarea",
                    name: "description." + lang,
                    fieldLabel: t("description"),
                    width: 500,
                    height: 100,
                    value: data.description[ lang ]
                }]
            };

            langTabs.push( tab );
        });

        // ...
        this.settingsForm = new Ext.form.FormPanel({
            title: t("settings"),
            bodyStyle: "padding:10px;",
            autoScroll: true,
            //border:false,
            items: [{
                border: true,
                style: "margin-bottom: 10px",
                cls: "object_localizedfields_panel",
                xtype: 'panel',
                items: [{
                    xtype: "tabpanel",
                    defaults: {
                        autoHeight:true
                        ,
                        bodyStyle:'padding:10px;'
                    },
                    items: langTabs
                }]
                }, {
                name: "behavior",
                fieldLabel: t("bundle_ecommerce_pricing_config_behavior"),
                xtype: "combo",
                store: [
                    ["additiv", t("bundle_ecommerce_pricing_config_additiv")],
                    ["stopExecute", t("bundle_ecommerce_pricing_config_stopExecute")]
                ],
                mode: "local",
                width: 300,
                editable: false,
                value: this.data.behavior,
                triggerAction: "all"
            }, {
                xtype: "checkbox",
                name: "active",
                fieldLabel: t("active"),
                checked: this.data.active == "1"
            }]
        });

        return this.settingsForm;
    },

    /**
     * @returns Ext.Panel
     */
    getConditions: function() {

        // init
        var _this = this;
        var addMenu = [];
        var itemTypes = Object.keys(pimcore.bundle.EcommerceFramework.pricing.conditions);
        // show only defined conditions
        Ext.each(this.parent.condition, function (condition) {
            var method = "condition" + condition;
            if(itemTypes.indexOf(method) != -1)
            {
                addMenu.push({
                    iconCls: "bundle_ecommerce_pricing_icon_" + method,
                    text: pimcore.bundle.EcommerceFramework.pricing.conditions[method](null, null,true),
                    handler: _this.addCondition.bind(_this, method)
                });
            }
        });


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

    /**
     * @returns {*}
     * @todo
     */
    getActions: function () {

        // init
        var _this = this;
        var addMenu = [];
        var itemTypes = Object.keys(pimcore.bundle.EcommerceFramework.pricing.actions);

        // show only defined actions
        Ext.each(this.parent.action, function (action) {
            var method = "action" + action;
            if(itemTypes.indexOf(method) != -1)
            {
                addMenu.push({
                    iconCls: "bundle_ecommerce_pricing_icon_" + method,
                    text: pimcore.bundle.EcommerceFramework.pricing.actions[method](null, null,true),
                    handler: _this.addAction.bind(_this, method)
                });
            }
        });


        this.actionsContainer = new Ext.Panel({
            title: t("actions"),
            autoScroll: true,
            forceLayout: true,
            tbar: [{
                iconCls: "pimcore_icon_add",
                menu: addMenu
            }],
            border: false
        });

        return this.actionsContainer;
    },


    /**
     * add condition item
     * @param type
     * @param data
     */
    addCondition: function (type, data) {

        // create condition
        var item = pimcore.bundle.EcommerceFramework.pricing.conditions[type](this, data);

        // add logic for brackets
        var tab = this;
        item.on("afterrender", function (el) {
            el.getEl().applyStyles({position: "relative", "min-height": "40px", "border-bottom": "1px solid #d0d0d0"});
            var leftBracket = el.getEl().insertHtml("beforeEnd",
                '<div class="pimcore_targeting_bracket pimcore_targeting_bracket_left">(</div>', true);
            var rightBracket = el.getEl().insertHtml("beforeEnd",
                '<div class="pimcore_targeting_bracket pimcore_targeting_bracket_right">)</div>', true);

            if(data["bracketLeft"]){
                leftBracket.addCls("pimcore_targeting_bracket_active");
            }
            if(data["bracketRight"]){
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

    /**
     * add action item
     * @param type
     * @param data
     */
    addAction: function (type, data) {

        var item = pimcore.bundle.EcommerceFramework.pricing.actions[type](this, data);

        this.actionsContainer.add(item);
        item.updateLayout();
        this.actionsContainer.updateLayout();
    },

    /**
     * save config
     */
    save: function () {
        var saveData = {};

        // general settings
        saveData["settings"] = this.settingsForm.getForm().getFieldValues();

        // get defined conditions
        var conditionsData = [];
        var operator;
        var conditions = this.conditionsContainer.items.getRange();
        for (var i=0; i<conditions.length; i++) {
            var condition = {};

            // collect condition settings
            for(var c=0; c<conditions[i].items.length; c++)
            {
                var item = conditions[i].items.getAt(c);
                try {
                    // workaround for pimcore.object.tags.objects
                    if(item.reference)
                    {
                        condition[ item.reference.getName() ] = item.reference.getValue();
                    }
                    else
                    {
                        condition[ item.getName() ] = item.getValue();
                    }
                } catch (e){}

            }
            condition['type'] = conditions[i].type;

            // get the operator (AND, OR, AND_NOT)
            var tb = conditions[i].getDockedItems()[0];
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
                                                                .hasCls("pimcore_targeting_bracket_active");
            condition["bracketRight"] = Ext.get(conditions[i].getEl().query(".pimcore_targeting_bracket_right")[0])
                                                                .hasCls("pimcore_targeting_bracket_active");

            conditionsData.push(condition);
        }
        saveData["conditions"] = conditionsData;

        // get defined actions
        var actionData = [];
        var actions = this.actionsContainer.items.getRange();
        for (var i=0; i<actions.length; i++) {
            var action = {};
            action = actions[i].getForm().getFieldValues();
            action['type'] = actions[i].type;

            actionData.push(action);
        }
        saveData["actions"] = actionData;

        // send data
        Ext.Ajax.request({
            url: "/admin/ecommerceframework/pricing/save",
            params: {
                id: this.data.id,
                data: Ext.encode(saveData)
            },
            method: "post",
            success: this.saveOnComplete.bind(this)
        });
    },

    /**
     * saved
     */
    saveOnComplete: function () {
        this.parent.refresh(this.parent.getTree().getRootNode());
        pimcore.helpers.showNotification(t("success"), t("bundle_ecommerce_pricing_config_saved_successfully"), "success");
    },

    recalculateButtonStatus: function () {
        var conditions = this.conditionsContainer.items.getRange();
        var tb;
        for (var i=0; i<conditions.length; i++) {
            var tb = conditions[i].getDockedItems()[0];
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


/**
 * CONDITION TYPES
 */
pimcore.registerNS("pimcore.bundle.EcommerceFramework.pricing.conditions");
pimcore.bundle.EcommerceFramework.pricing.conditions = {

    detectBlockIndex: function (blockElement, container) {
        // detect index
        var index;

        for(var s=0; s<container.items.items.length; s++) {
            if(container.items.items[s].getId() == blockElement.getId()) {
                index = s;
                break;
            }
        }
        return index;
    },

    /**
     * @param name
     * @param index
     * @param parent
     * @param data
     * @param iconCls
     * @returns {Array}
     * @todo idents berechnung ausführen wenn eine condition verschoben wird
     */
    getTopBar: function (name, index, parent, data, iconCls) {

        var toggleGroup = "g_" + index + parent.data.id;
        if(!data["operator"]) {
            data.operator = "and";
        }

        return [{
            iconCls: iconCls,
            disabled: true
        }, {
            xtype: "tbtext",
            text: "<b>" + name + "</b>"
        },"-",{
            iconCls: "pimcore_icon_up",
            handler: function (blockId, parent) {

                var container = parent.conditionsContainer;
                var blockElement = Ext.getCmp(blockId);
                var index = pimcore.bundle.EcommerceFramework.pricing.conditions.detectBlockIndex(blockElement, container);
                var tmpContainer = pimcore.viewport;

                var newIndex = index-1;
                if(newIndex < 0) {
                    newIndex = 0;
                }

                // move this node temorary to an other so ext recognizes a change
                container.remove(blockElement, false);
                tmpContainer.add(blockElement);
                container.updateLayout();
                tmpContainer.updateLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                container.insert(newIndex, blockElement);
                container.updateLayout();
                tmpContainer.updateLayout();

                parent.recalculateButtonStatus();

                pimcore.layout.refresh();

                parent.recalculateBracketIdent(parent.conditionsContainer.items);
            }.bind(window, index, parent)
        },{
            iconCls: "pimcore_icon_down",
            handler: function (blockId, parent) {

                var container = parent.conditionsContainer;
                var blockElement = Ext.getCmp(blockId);
                var index = pimcore.bundle.EcommerceFramework.pricing.conditions.detectBlockIndex(blockElement, container);
                var tmpContainer = pimcore.viewport;

                // move this node temorary to an other so ext recognizes a change
                container.remove(blockElement, false);
                tmpContainer.add(blockElement);
                container.updateLayout();
                tmpContainer.updateLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                container.insert(index+1, blockElement);
                container.updateLayout();
                tmpContainer.updateLayout();

                parent.recalculateButtonStatus();

                pimcore.layout.refresh();
                parent.recalculateBracketIdent(parent.conditionsContainer.items);

            }.bind(window, index, parent)
        },"-", {
            text: t("AND"),
            toggleGroup: toggleGroup,
            enableToggle: true,
            itemId: "toggle_and",
            pressed: (data.operator == "and") ? true : false
        },{
            text: t("OR"),
            toggleGroup: toggleGroup,
            enableToggle: true,
            itemId: "toggle_or",
            pressed: (data.operator == "or") ? true : false
        },{
            text: t("AND_NOT"),
            toggleGroup: toggleGroup,
            enableToggle: true,
            itemId: "toggle_and_not",
            pressed: (data.operator == "and_not") ? true : false
        },"->",{
            iconCls: "pimcore_icon_delete",
            handler: function (index, parent) {
                parent.conditionsContainer.remove(Ext.getCmp(index));
                parent.recalculateButtonStatus();
                parent.recalculateBracketIdent(parent.conditionsContainer.items);
            }.bind(window, index, parent)
        }];
    },

    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    conditionDateRange: function (panel, data, getName) {

        //
        var niceName = t("bundle_ecommerce_pricing_config_condition_daterange");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        // check params
        if(typeof data == "undefined") {
            data = {};
        }

        // create item
        var myId = Ext.id();
        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'DateRange',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:30px;",
            tbar: this.getTopBar(niceName, myId, panel, data, "bundle_ecommerce_pricing_icon_conditionDateRange"),
            items: [{
                xtype:'datefield',
                fieldLabel: t("bundle_ecommerce_pricing_config_condition_daterange_from"),
                name: "starting",
                format: 'd.m.Y',
                altFormats: 'U',
                value: data.starting,
                width: 400
            },{
                xtype:'datefield',
                fieldLabel: t("bundle_ecommerce_pricing_config_condition_daterange_until"),
                name: "ending",
                format: 'd.m.Y',
                altFormats: 'U',
                value: data.ending,
                width: 400
            }],
            listeners: {

            }
        });

        return item;
    },

    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    conditionCatalogProduct: function (panel, data, getName) {

        var niceName = t("bundle_ecommerce_pricing_config_condition_catalog_product");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'CatalogProduct',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, "bundle_ecommerce_pricing_icon_conditionCatalogProduct"),
            items: [
                new pimcore.bundle.EcommerceFramework.pricing.config.objects(data.products, {
                    name: "products",
                    title: "",
                    visibleFields: "path",
                    height: 200,
                    width: 500,
                    columns: [],

                    // ?
                    columnType: null,
                    datatype: "data",
                    fieldtype: "objects",

                    // ??
                    index: false,
                    invisible: false,
                    lazyLoading: false,
                    locked: false,
                    mandatory: false,
                    maxItems: "",
                    noteditable: false,
                    permissions: null,
                    phpdocType: "array",
                    queryColumnType: "text",
                    relationType: true,
                    style: "",
                    tooltip: "",
                    visibleGridView: false,
                    visibleSearch: false
                }).getLayoutEdit()
            ]
        });

        return item;
    },


    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    conditionCatalogCategory: function (panel, data, getName) {

        var niceName = t("bundle_ecommerce_pricing_config_condition_catalog_category");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'CatalogCategory',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 0px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, "bundle_ecommerce_pricing_icon_conditionCatalogCategory"),
            items: [
                new pimcore.bundle.EcommerceFramework.pricing.config.objects(data.categories, {
                    name: "categories",
                    title: "",
                    visibleFields: "path",
                    height: 200,
                    width: 500,
                    columns: [],

                    // ?
                    columnType: null,
                    datatype: "data",
                    fieldtype: "objects",

                    // ??
                    index: false,
                    invisible: false,
                    lazyLoading: false,
                    locked: false,
                    mandatory: false,
                    maxItems: "",
                    noteditable: false,
                    permissions: null,
                    phpdocType: "array",
                    queryColumnType: "text",
                    relationType: true,
                    style: "",
                    tooltip: "",
                    visibleGridView: false,
                    visibleSearch: false
                }).getLayoutEdit()
            ]
        });

        return item;
    },


    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    conditionCartAmount: function (panel, data, getName) {

        var niceName = t("bundle_ecommerce_pricing_config_condition_cart_amount");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'CartAmount',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, "bundle_ecommerce_pricing_icon_conditionCartAmount"),
            items: [{
                xtype: "numberfield",
                fieldLabel: t("bundle_ecommerce_pricing_config_condition_cart_amount"),
                name: "limit",
                width: 300,
                value: data.limit
            }]
        });

        return item;
    },


    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    conditionToken: function (panel, data, getName) {

        //
        var niceName = t("bundle_ecommerce_pricing_config_condition_token");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        // check params
        if(typeof data == "undefined") {
            data = {};
        }

        // create item
        var myId = Ext.id();
        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'Token',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, "bundle_ecommerce_pricing_icon_conditionToken"),
            items: [{
                xtype: "textfield",
                fieldLabel: t("bundle_ecommerce_pricing_config_condition_token_value"),
                name: "token",
                width: 200,
                value: data.token
            }],
        });

        return item;
    },


    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    conditionSold: function (panel, data, getName) {

        //
        var niceName = t("bundle_ecommerce_pricing_config_condition_sold");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        // check params
        if(typeof data == "undefined") {
            data = {};
        }

        // create item
        var myId = Ext.id();
        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'Sold',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, "bundle_ecommerce_pricing_icon_conditionSold"),
            items: [{
                xtype: "numberfield",
                fieldLabel: t("bundle_ecommerce_pricing_config_condition_sold_count"),
                name: "count",
                width: 300,
                value: data.count
            }],
        });

        return item;
    },


    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    conditionSales: function (panel, data, getName) {

        //
        var niceName = t("bundle_ecommerce_pricing_config_condition_sales");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        // check params
        if(typeof data == "undefined") {
            data = {};
        }

        // create item
        var myId = Ext.id();
        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'Sales',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, "bundle_ecommerce_pricing_icon_conditionSales"),
            items: [{
                xtype: "numberfield",
                fieldLabel: t("bundle_ecommerce_pricing_config_condition_sales_amount"),
                name: "amount",
                width: 300,
                value: data.amount
            }],
        });

        return item;
    },


    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    conditionClientIp: function (panel, data, getName) {

        //
        var niceName = t("bundle_ecommerce_pricing_config_condition_client-ip");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }


        // create item
        var myId = Ext.id();
        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'ClientIp',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, "bundle_ecommerce_pricing_icon_conditionClientIp"),
            items: [{
                xtype: "textfield",
                fieldLabel: t("bundle_ecommerce_pricing_config_condition_client_ip"),
                name: "ip",
                width: 300,
                value: data.ip
            }]
        });


        // set default value
        if(data.ip == undefined)
        {
            Ext.Ajax.request({
                url: "/admin/settings/get-system",
                success: function (response) {

                    var settings = Ext.decode(response.responseText);
                    item.getForm().findField('ip').setValue( settings.config.client_ip );

                }.bind(this)
            });
        }


        return item;
    },

    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    conditionVoucherToken: function (panel, data, getName) {
        var niceName = t("bundle_ecommerce_pricing_config_condition_voucherToken");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        // check params
        if(typeof data == "undefined") {
            data = {};
        }

        // create item
        var myId = Ext.id();
        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'VoucherToken',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, "bundle_ecommerce_pricing_icon_conditionVoucherToken"),
            items: [
                new pimcore.bundle.EcommerceFramework.pricing.config.objects(data.whiteList, {
                    classes: [
                        "OnlineShopVoucherSeries"
                    ],
                    name: "whiteList",
                    title: "White List",
                    visibleFields: "path",
                    height: 200,
                    width: 600,
                    columns: [],

                    // ?
                    columnType: null,
                    datatype: "data",
                    fieldtype: "objects",

                    // ??
                    index: false,
                    invisible: false,
                    lazyLoading: false,
                    locked: false,
                    mandatory: false,
                    maxItems: "",
                    noteditable: false,
                    permissions: null,
                    phpdocType: "array",
                    queryColumnType: "text",
                    relationType: true,
                    style: "",
                    tooltip: "",
                    visibleGridView: false,
                    visibleSearch: false
                }).getLayoutEdit()
            ]
        });

        return item;
    }

};



/**
 * ACTION TYPES
 */
pimcore.registerNS("pimcore.bundle.EcommerceFramework.pricing.actions");
pimcore.bundle.EcommerceFramework.pricing.actions = {

    /**
     * @param name
     * @param index
     * @param parent
     * @param data
     * @param iconCls
     * @returns {Array}
     */
    getTopBar: function (name, index, parent, data, iconCls) {
        return [
            {
                iconCls: iconCls,
                disabled: true
            },
            {
                xtype: "tbtext",
                text: "<b>" + name + "</b>"
            },
            "->",
            {
                iconCls: "pimcore_icon_delete",
                handler: function (index, parent) {
                    parent.actionsContainer.remove(Ext.getCmp(index));
                }.bind(window, index, parent)
        }];
    },

    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    actionGift: function (panel, data, getName) {

        // getName macro
        var niceName = t("bundle_ecommerce_pricing_config_action_gift");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        // check params
        if(typeof data == "undefined") {
            data = {};
        }

        // config
        var iconCls = 'bundle_ecommerce_pricing_icon_actionGift';

        // create item
        var myId = Ext.id();
        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'Gift',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, iconCls),
            items: [
                {
                    xtype: "textfield",
                    fieldLabel: t("bundle_ecommerce_pricing_config_action_gift_product"),
                    name: "product",
                    width: 500,
                    cls: "input_drop_target",
                    value: data.product,
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
                                    var record = data.records[0];
                                    var data = record.data;

                                    if (data.type == "object" || data.type == "variant") {
                                        this.setValue(data.path);
                                        return true;
                                    }
                                    return false;
                                }.bind(el)
                            });
                        }
                    }
                }
            ]
        });

        return item;
    },

    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    actionCartDiscount: function (panel, data, getName) {

        // getName macro
        var niceName = t("bundle_ecommerce_pricing_config_action_cart_discount");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        // check params
        if(typeof data == "undefined") {
            data = {};
        }

        // config
        var iconCls = 'bundle_ecommerce_pricing_icon_actionCartDiscount';

        // create item
        var myId = Ext.id();
        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'CartDiscount',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, iconCls),
            items: [
                {
                    xtype: "numberfield",
                    fieldLabel: t("bundle_ecommerce_pricing_config_action_cart_discount_amount"),
                    name: "amount",
                    width: 200,
                    value: data.amount
                }, {
                    xtype: "numberfield",
                    fieldLabel: t("bundle_ecommerce_pricing_config_action_cart_discount_percent"),
                    name: "percent",
                    width: 200,
                    value: data.percent
                }
            ]
        });

        return item;
    },

    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    actionProductDiscount: function (panel, data, getName) {

        // getName macro
        var niceName = t("bundle_ecommerce_pricing_config_action_product_discount");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        // check params
        if(typeof data == "undefined") {
            data = {};
        }

        // config
        var iconCls = 'bundle_ecommerce_pricing_icon_actionProductDiscount';

        // create item
        var myId = Ext.id();
        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'ProductDiscount',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, iconCls),
            items: [
                {
                    xtype: "numberfield",
                    fieldLabel: t("bundle_ecommerce_pricing_config_action_product_discount_amount"),
                    name: "amount",
                    width: 200,
                    value: data.amount
                }, {
                    xtype: "numberfield",
                    fieldLabel: t("bundle_ecommerce_pricing_config_action_product_discount_percent"),
                    name: "percent",
                    width: 200,
                    value: data.percent
                }
            ]
        });

        return item;
    },

    /**
     * @param panel
     * @param data
     * @param getName
     * @returns Ext.form.FormPanel
     */
    actionFreeShipping: function (panel, data, getName) {

        // getName macro
        var niceName = t("bundle_ecommerce_pricing_config_action_free_shipping");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        // check params
        if(typeof data == "undefined") {
            data = {};
        }

        // config
        var iconCls = 'bundle_ecommerce_pricing_icon_actionFreeShipping';

        // create item
        var myId = Ext.id();
        var item =  new Ext.form.FormPanel({
            id: myId,
            type: 'FreeShipping',
            forceLayout: true,
            style: "margin: 10px 0 0 0",
//            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data, iconCls)
        });

        return item;
    }
};
