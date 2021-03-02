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

pimcore.registerNS("pimcore.object.tags.localizedfields");
pimcore.object.tags.localizedfields = Class.create(pimcore.object.tags.abstract, {

    type: "localizedfields",

    frontendLanguages: null,

    tabPanelDefaultConfig: {
        monitorResize: true,
        cls: "object_field object_field_type_localizedfields",
        activeTab: 0,
        height: "auto",
        items: [],
        deferredRender: true,
        forceLayout: true,
        hideMode: "offsets",
        enableTabScroll: true
    },

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
            for (var i = 0; i < this.frontendLanguages.length; i++) {
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

    hideLabels: function() {
        return (typeof this.fieldConfig.hideLabelsWhenTabsReached == 'number' && this.frontendLanguages.length >= this.fieldConfig.hideLabelsWhenTabsReached);
    },

    getLayoutEdit: function (showMode) {
        this.fieldConfig.datatype = "layout";
        this.fieldConfig.fieldtype = "panel";

        var wrapperConfig = {
            border: this.fieldConfig.border,
            layout: "fit"
        };

        if (this.fieldConfig.width) {
            wrapperConfig.width = this.fieldConfig.width;
        }

        if (this.fieldConfig.region) {
            wrapperConfig.region = this.fieldConfig.region;
        }

        if (this.fieldConfig.title && this.dropdownLayout) {
            wrapperConfig.title = this.fieldConfig.title;
        }

        if (this.context.containerType == "fieldcollection") {
            this.context.subContainerType = "localizedfield";
        } else {
            this.context.containerType = "localizedfield";
        }

        var nrOfLanguages = this.frontendLanguages.length;
        var configureSplitViewButton;
        var tbarItems = [];
        var disableSplitViewButton;
        var isSplitViewEnabled = this.isSplitViewEnabled();

        if (this.fieldConfig.provideSplitView) {
            configureSplitViewButton = new Ext.Button({
                tooltip: t('split_view_settings'),
                iconCls: 'pimcore_icon_side_by_side',
                handler: this.configureSplitView.bind(this)
            });
            if (isSplitViewEnabled) {
                disableSplitViewButton = new Ext.Button({
                    tooltip: t('disable_split_view'),
                    iconCls: 'pimcore_icon_revert',
                    handler: this.hideSplitView.bind(this)
                });
            }
        }

        if (isSplitViewEnabled && this.fieldConfig.provideSplitView) {
            var panelConf = {};
            panelConf.left =  Ext.clone(this.tabPanelDefaultConfig);

            if (this.fieldConfig.height) {
                panelConf.left.height = this.fieldConfig.height;
                panelConf.left.autoHeight = false;
            }

            panelConf.right = Ext.clone(panelConf.left);

            var hideLabels = this.hideLabels();

            var existingSettings = this.getCurrentSplitViewSettings();

            if (existingSettings) {
                for (var currentLanguage in existingSettings.side) {
                    if (!in_array(currentLanguage, this.frontendLanguages)) {
                        continue;
                    }

                    if (existingSettings.side.hasOwnProperty(currentLanguage)) {
                        var side;
                        if (existingSettings.side[currentLanguage] == -1) {
                            side = "left";
                        } else if (existingSettings.side[currentLanguage] == +1) {
                            side = "right";
                        }
                        if (!side) {
                            continue;
                        }

                        var dataProvider = this.getDataProvider(currentLanguage);
                        this.languageElements[currentLanguage] = [];

                        var editable = (pimcore.currentuser.admin ||
                            this.fieldConfig.permissionEdit === undefined || this.fieldConfig.permissionEdit.length == 0 || in_array(currentLanguage, this.fieldConfig.permissionEdit));

                        var runtimeContext = Ext.clone(this.context);
                        runtimeContext.language = Ext.clone(currentLanguage);
                        var panelConfig = this.fieldConfig;

                        var item = this.getTabItem(panelConfig, editable, runtimeContext, dataProvider);
                        this.styleLanguageTab(item, hideLabels, currentLanguage);

                        if (this.fieldConfig.labelWidth) {
                            item.labelWidth = this.fieldConfig.labelWidth;
                        }

                        if (side == "left") {
                            item.style = "border-right: 1px dotted #DDD;";
                        }
                        panelConf[side].items.push(item);
                    }
                }
            }

            var tabPanelLeft = new Ext.TabPanel(panelConf["left"]);
            var tabPanelRight = new Ext.TabPanel(panelConf["right"]);

            var splitViewConfig = {
                border: true,
                style: "margin-bottom: 10px",
                tbar: [
                    this.fieldConfig.title,  '->', disableSplitViewButton, configureSplitViewButton
                ],
                height: 'auto',
                layout: {
                    type: 'hbox',
                    padding: 5
                },
                items: [{
                    xtype: 'panel',
                    height: 'auto',
                    items: [tabPanelLeft],
                    flex: 1
                }, {
                    xtype: 'panel',
                    height: 'auto',
                    items: [tabPanelRight],
                    flex: 1
                }]
            };

            if (this.fieldConfig.width) {
                splitViewConfig.width = this.fieldConfig.width;
            }

            var splitView = Ext.create('Ext.panel.Panel', splitViewConfig);
            splitView.excludeFromUiStateRestore = true;

            this.component = splitView;
        } else {
            if (this.dropdownLayout) {
                var data = [];
                for (var i = 0; i < nrOfLanguages; i++) {
                    var language = this.frontendLanguages[i];
                    data.push([language, t(pimcore.available_languages[language])]);
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
                    listeners: {
                        select: function (combo, record, index) {
                            var oldLanguage = this.currentLanguage;
                            var newLanguage = record.data.key;
                            if (oldLanguage == newLanguage) {
                                return;
                            }

                            this.availablePanels[oldLanguage].hide();
                            this.component.updateLayout();
                            this.availablePanels[newLanguage].show();
                            this.currentLanguage = newLanguage;
                            this.component.updateLayout();
                        }.bind(this)
                    }
                };

                this.countrySelect = new Ext.form.ComboBox(options);

                wrapperConfig.items = [];

                for (var i = nrOfLanguages - 1; i >= 0; i--) {
                    var currentLanguage = this.frontendLanguages[i];
                    this.currentLanguage = currentLanguage;         // remember active language

                    var dataProvider = this.getDataProvider(currentLanguage);

                    this.languageElements[currentLanguage] = [];

                    var editable = !showMode && (pimcore.currentuser.admin ||
                        this.fieldConfig.permissionEdit === undefined || this.fieldConfig.permissionEdit.length == 0 || in_array(currentLanguage, this.fieldConfig.permissionEdit));

                    var runtimeContext = Ext.clone(this.context);
                    runtimeContext.language = Ext.clone(currentLanguage);
                    var items = this.getRecursiveLayout(this.fieldConfig, !editable, runtimeContext, false, false, dataProvider).items;

                    var panelConf = {
                        height: "auto",
                        border: false,
                        padding: "10px",
                        items: items,
                        hidden: (i > 0)     //TODO default language
                    };

                    if (this.fieldConfig.height) {
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
                    this.tabPanel.excludeFromUiStateRestore = true;
                    this.availablePanels[currentLanguage] = this.tabPanel;
                    wrapperConfig.items.push(this.tabPanel);

                    tbarItems = [new Ext.Toolbar.TextItem({
                        text: t("language")
                    }), this.countrySelect];

                    if (configureSplitViewButton) {
                        tbarItems.push('->');
                        tbarItems.push(configureSplitViewButton);
                    }
                }
            } else {
                var panelConf = Ext.clone(this.tabPanelDefaultConfig);

                if (this.fieldConfig.height) {
                    panelConf.height = this.fieldConfig.height;
                    panelConf.autoHeight = false;
                }

                if(this.fieldConfig.tabPosition) {
                    panelConf.tabPosition = this.fieldConfig.tabPosition;
                }

                var hideLabels = this.hideLabels();

                for (var i = 0; i < nrOfLanguages; i++) {
                    var currentLanguage = this.frontendLanguages[i];
                    var dataProvider = this.getDataProvider(currentLanguage);
                    this.languageElements[currentLanguage] = [];

                    var editable = !showMode && (pimcore.currentuser.admin ||
                        this.fieldConfig.permissionEdit === undefined || this.fieldConfig.permissionEdit.length == 0 || in_array(currentLanguage, this.fieldConfig.permissionEdit));

                    var runtimeContext = Ext.clone(this.context);
                    runtimeContext.language = Ext.clone(currentLanguage);
                    var panelConfig = this.fieldConfig;
                    var item = this.getTabItem(panelConfig, editable, runtimeContext, dataProvider);

                    this.styleLanguageTab(item, hideLabels, this.frontendLanguages[i]);

                    if (this.fieldConfig.labelWidth) {
                        item.labelWidth = this.fieldConfig.labelWidth;
                    }

                    panelConf.items.push(item);
                }

                if(this.fieldConfig.title) {
                    if(this.fieldConfig.provideSplitView) {
                        tbarItems.push(
                            {
                                xtype: "tbtext",
                                text: this.fieldConfig.title
                            });
                    } else {
                        wrapperConfig.title = this.fieldConfig.title;
                    }
                }


                if (configureSplitViewButton) {
                    if (tbarItems) {
                        tbarItems.push('->');
                    }
                    tbarItems.push(configureSplitViewButton);
                }

                this.tabPanel = new Ext.TabPanel(panelConf);
                wrapperConfig.items = [this.tabPanel];
            }

            wrapperConfig.style = "margin-bottom: 10px";
            wrapperConfig.cls = "object_localizedfields_panel";


            if (tbarItems) {
                wrapperConfig.tbar = tbarItems;
            }

            this.component = new Ext.Panel(wrapperConfig);
        }

        this.component.updateLayout();

        this.fieldConfig.datatype = "data";
        this.fieldConfig.fieldtype = "localizedfields";

        return this.component;
    },

    getTabItem: function(panelConfig, editable, runtimeContext, dataProvider) {
        var item = {
            xtype: "panel",
            border: false,
            autoScroll: true,
            padding: "10px",
            deferredRender: true,
            hideMode: "offsets",
            items: [],
            listeners: {
                afterrender: function (l, editable, runtimeContext, dataProvider, panel) {
                    if (!panel.__tabpanel_initialized) {
                        panel.__tabpanel_initialized = true;
                        if (l.childs && typeof l.childs == "object") {
                            if (l.childs.length > 0) {
                                l.items = [];
                                for (var i = 0; i < l.childs.length; i++) {
                                    var childConfig = l.childs[i];

                                    // inherit label width from localized fields configuration
                                    if (this.fieldConfig.labelWidth) {
                                        childConfig.labelWidth = this.fieldConfig.labelWidth;
                                    }

                                    var children = this.getRecursiveLayout(childConfig, !editable, runtimeContext, false, false, dataProvider, true);
                                    if (children) {
                                        panel.add(children);
                                    }
                                }
                            }
                            panel.updateLayout();
                        }

                        if (panel.setActiveTab) {
                            var activeTab = panel.items.items[0];
                            if (activeTab) {
                                activeTab.updateLayout();
                                panel.setActiveTab(activeTab);
                            }
                        }

                    }
                }.bind(this, panelConfig, editable, runtimeContext, dataProvider)
            }
        };
        return item;
    },

    styleLanguageTab: function(item, hideLabels, language) {
        if (hideLabels) {
            item.title = '<div class="pimcore_icon_language_' + language.toLowerCase() + '" title="' + pimcore.available_languages[language] + '" style="width: 20px; height:20px;"></div>';
            item.tbar = Ext.create('Ext.toolbar.Toolbar', {
                style: 'margin-bottom:10px;',
                items: [{
                    text: t('grid_current_language') + ': ' + pimcore.available_languages[language],
                    xtype: "tbtext",
                    style: 'font-size: 13px;'
                }
                ]
            });
        } else {
            item.iconCls = "pimcore_icon_language_" + language.toLowerCase();
            item.title = t(pimcore.available_languages[language]);
        }
    },

    getLayoutShow: function () {
        this.component = this.getLayoutEdit(true);
        return this.component;
    },

    addReferencedField: function (field) {
        this.referencedFields.push(field);
    },

    getValue: function () {
        var localizedData = {};
        var currentLanguage;
        var ignoreIsDirty = ['fieldcollection'].includes(this.getContext().containerType) || ['block'].includes(this.getContext().subContainerType);

        for (var i = 0; i < this.frontendLanguages.length; i++) {
            currentLanguage = this.frontendLanguages[i];
            if (!this.languageElements[currentLanguage]) {
                continue;
            }
            localizedData[currentLanguage] = {};

            for (var s = 0; s < this.languageElements[currentLanguage].length; s++) {
                try {
                    if (ignoreIsDirty || this.languageElements[currentLanguage][s].isDirty()) {
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
        if (this.referencedFields.length > 0) {
            for (var r = 0; r < this.referencedFields.length; r++) {
                localizedData = array_merge_recursive(localizedData, this.referencedFields[r].getValue());
            }
        }

        return localizedData;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function () {
        // also check the referenced localized fields
        if (this.referencedFields.length > 0) {
            for (var r = 0; r < this.referencedFields.length; r++) {
                if (this.referencedFields[r].isDirty()) {
                    return true;
                }
            }
        }

        if (!this.isRendered()) {
            return false;
        }

        var currentLanguage;

        for (var i = 0; i < this.frontendLanguages.length; i++) {
            currentLanguage = this.frontendLanguages[i];
            if (!this.languageElements[currentLanguage]) {
                continue;
            }

            for (var s = 0; s < this.languageElements[currentLanguage].length; s++) {
                if (this.languageElements[currentLanguage][s].isDirty()) {
                    return true;
                }
            }
        }

        return false;
    },

    isMandatory: function () {
        // also check the referenced localized fields
        if (this.referencedFields.length > 0) {
            for (var r = 0; r < this.referencedFields.length; r++) {
                if (this.referencedFields[r].isMandatory()) {
                    return true;
                }
            }
        }

        var currentLanguage;

        for (var i = 0; i < this.frontendLanguages; i++) {
            currentLanguage = this.frontendLanguages[i];

            for (var s = 0; s < this.languageElements[currentLanguage].length; s++) {
                if (this.languageElements[currentLanguage][s].isMandatory()) {
                    return true;
                }
            }
        }

        return false;
    },

    removeInheritanceSourceButton: function () {
        //nothing to do
    },

    dataIsNotInherited: function (fromObjectbrick) {
        // also check the referenced localized fields
        if (this.referencedFields.length > 0) {
            for (var r = 0; r < this.referencedFields.length; r++) {
                this.referencedFields[r].dataIsNotInherited();
            }
        }

        if (!fromObjectbrick && !this.inherited) {
            return true;
        }

        var foundUnmodifiedInheritedField = false;
        for (var i = 0; i < this.frontendLanguages.length; i++) {
            var currentLanguage = this.frontendLanguages[i];

            for (var s = 0; s < this.languageElements[currentLanguage].length; s++) {
                if (this.metaData[currentLanguage]) {
                    var languageElement = this.languageElements[currentLanguage][s];
                    var key = languageElement.name;
                    if (this.metaData[currentLanguage][key]) {
                        if (this.metaData[currentLanguage][key].inherited) {
                            if (languageElement.isDirty()) {
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

        if (!fromObjectbrick) {
            if (!foundUnmodifiedInheritedField) {
                this.inherited = false;
            }
        }
        return !this.inherited;
    },

    markInherited: function (metaData) {
        // nothing to do, only sub-elements can be marked
    },

    getDataProvider: function (currentLanguage) {
        var dataProvider = {
            getDataForField: function (currentLanguage, fieldConfig) {
                var name = fieldConfig.name;
                try {
                    if (this.data[currentLanguage]) {
                        if (typeof this.data[currentLanguage][name] !== undefined) {
                            return this.data[currentLanguage][name];
                        }
                    }
                } catch (e) {
                    console.log(e);
                }
                return;

            }.bind(this, currentLanguage),

            getMetaDataForField: function (currentLanguage, fieldConfig) {
                var name = fieldConfig.name;
                try {
                    if (this.metaData[currentLanguage]) {
                        if (this.metaData[currentLanguage][name]) {
                            return this.metaData[currentLanguage][name];
                        } else if (typeof this.data[currentLanguage][name] !== undefined) {
                            return null;
                        }
                    }
                } catch (e) {
                    console.log(e);
                }
                return;

            }.bind(this, currentLanguage),

            addToDataFields: function (currentLanguage, field, name) {
                this.languageElements[currentLanguage].push(field);
            }.bind(this, currentLanguage)
        };

        return dataProvider;
    },

    getLocalStorageKey: function () {
        return "pimcore_lfSplitView_" + this.object.data.general.o_className;
    },


    isSplitViewEnabled: function () {
        var existingSettings = this.getCurrentSplitViewSettings();
        var enabled = existingSettings ? existingSettings["enabled"] : false;
        return enabled;
    },

    getCurrentSplitViewSettings: function () {
        var existingSettings = localStorage.getItem(this.getLocalStorageKey());
        if (existingSettings) {
            try {
                existingSettings = JSON.parse(existingSettings);
            } catch (e) {
            }
        }
        return existingSettings;
    },


    hideSplitView: function () {
        var existingSettings = this.getCurrentSplitViewSettings();
        if (existingSettings) {
            existingSettings["enabled"] = false;
        }

        var localStorageKey = this.getLocalStorageKey();
        localStorageData = JSON.stringify(existingSettings);
        localStorage.setItem(localStorageKey, localStorageData);
        var params = {};
        if (this.object.data.currentLayoutId) {
            params["layoutId"] = this.object.data.currentLayoutId;
        }
        this.object.reload(params);
    },

    configureSplitView: function () {

        var existingSettings = this.getCurrentSplitViewSettings();

        var nrOfLanguages = this.frontendLanguages.length;

        var data = [];

        if (existingSettings) {
            var existingLanguages = [];
            for (var language in existingSettings.side) {
                if (!in_array(language, this.frontendLanguages)) {
                    continue;
                }
                if (existingSettings.side.hasOwnProperty(language)) {
                    existingLanguages.push(language);
                    var languageSetting = existingSettings.side[language];
                    data.push([
                        language,
                        languageSetting == -1 ? true : false,
                        languageSetting == +1 ? true : false
                    ]);
                }
            }

            // append "new" languages
            for (var i = 0; i < nrOfLanguages; i++) {
                var language = this.frontendLanguages[i];
                if (!in_array(language, existingLanguages)) {
                    data.push([
                        language
                    ]);
                }
            }
        } else {
            for (var i = 0; i < nrOfLanguages; i++) {
                data.push([
                    this.frontendLanguages[i],
                    true,
                    false
                ]);
            }
        }

        var store = new Ext.data.ArrayStore({
                fields: ["language", "left", "right"],
                data: data
            }
        );

        var gridPanel = new Ext.grid.GridPanel({
            store: store,
            border: false,
            ddGroup: 'splitviewconfig',
            viewConfig: {
                plugins: {
                    ptype: 'gridviewdragdrop',
                    draggroup: 'splitviewconfig'
                },
                forceFit: true,
                markDirty: false
            },
            columns: [
                {
                    text: t('language'),
                    dataIndex: 'language',
                    flex: 5,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return t(pimcore.available_languages[value]);
                    }
                }, {
                    xtype: 'checkcolumn',
                    text: t('left'),
                    dataIndex: 'left',
                    flex: 1,
                    listeners: {
                        checkChange: function (column, rowIndex, checked, eOpts) {
                            if (checked) {
                                var record = store.getAt(rowIndex);
                                record.set("right", 0);
                            }
                        }
                    }
                }, {
                    xtype: 'checkcolumn',
                    text: t('right'),
                    dataIndex: 'right',
                    flex: 1,
                    listeners: {
                        checkChange: function (column, rowIndex, checked, eOpts) {
                            if (checked) {
                                var record = store.getAt(rowIndex);
                                record.set("left", 0);
                            }
                        }
                    }
                },
                {
                    xtype: 'actioncolumn',
                    menuText: t('up'),
                    width: 40,
                    items: [
                        {
                            tooltip: t('up'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/up.svg",
                            handler: function (grid, rowIndex) {
                                if (rowIndex > 0) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    grid.getStore().removeAt(rowIndex);
                                    grid.getStore().insert(rowIndex - 1, [rec]);
                                }
                            }.bind(this)
                        }
                    ]
                },
                {
                    xtype: 'actioncolumn',
                    menuText: t('down'),
                    width: 40,
                    items: [
                        {
                            tooltip: t('down'),
                            icon: "/bundles/pimcoreadmin/img/flat-color-icons/down.svg",
                            handler: function (grid, rowIndex) {
                                if (rowIndex < (grid.getStore().getCount() - 1)) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    grid.getStore().removeAt(rowIndex);
                                    grid.getStore().insert(rowIndex + 1, [rec]);
                                }
                            }.bind(this)
                        }
                    ]
                }
            ],
            columnLines: true,
            bodyCls: "pimcore_editable_grid",
            stripeRows: true,
            listeners: {
                rowdblclick: function (grid, record, tr, rowIndex, e, eOpts) {
                    var record = store.getAt(rowIndex);
                    if (record.get("left")) {
                        record.set("left", 0);
                        record.set("right", 1);
                    } else if (record.get("right")) {
                        record.set("right", 0);
                        record.set("left", 1);
                    } else {
                        record.set("left", 1);
                    }

                }.bind(this)
            }
        });

        this.splitViewSettingsWindow = new Ext.Window({
            width: 600,
            maxHeight: 800,
            autoScroll: true,
            closeAction: 'close',
            modal: true,
            items: [gridPanel],
            title: t("split_view_settings"),
            bbar: ["->",
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_apply",
                    text: t('apply'),
                    handler: this.applySplitViewSettings.bind(this, store)
                },
                {
                    text: t("cancel"),
                    handler: function() {
                        this.splitViewSettingsWindow.close();
                    }.bind(this),
                    iconCls: "pimcore_icon_cancel"
                }
            ]
        });

        this.splitViewSettingsWindow.show();
    },

    doApplySplitViewSettings: function(store) {
        var localStorageKey = this.getLocalStorageKey();
        var localStorageData = {
            "enabled": true,
            "side": {}
        };
        store.each(function (record, id) {
            var language = record.get("language");
            var left = record.get("left");
            var right = record.get("right");
            if (left) {
                localStorageData["side"][language] = -1;
            }
            if (right) {
                localStorageData["side"][language] = +1;
            }
        });

        localStorageData = JSON.stringify(localStorageData);

        localStorage.setItem(localStorageKey, localStorageData);
        this.splitViewSettingsWindow.close();
        var params = {};
        if (this.object.data.currentLayoutId) {
            params["layoutId"] = this.object.data.currentLayoutId;
        }
        this.object.reload(params);
    },

    applySplitViewSettings: function (store) {
        if (this.object.dirty) {
            Ext.MessageBox.show({
                title: t('split_view_object_dirty_title'),
                msg: t('split_view_object_dirty_msg'),
                buttons: Ext.Msg.YESNO,
                icon: Ext.MessageBox.WARNING,
                fn: function (store, btn) {
                    if (btn == "yes") {
                        this.doApplySplitViewSettings(store);
                    } else {
                        this.splitViewSettingsWindow.close();
                    }
                }.bind(this, store)
            });
        } else {
            this.doApplySplitViewSettings(store);
        }
    }
});

pimcore.object.tags.localizedfields.addMethods(pimcore.object.helpers.edit);
