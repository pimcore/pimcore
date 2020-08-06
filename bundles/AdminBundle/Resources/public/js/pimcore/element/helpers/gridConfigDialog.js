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

pimcore.registerNS("pimcore.element.helpers.gridConfigDialog");
pimcore.element.helpers.gridConfigDialog = Class.create({

    showFieldname: true,
    data: {},

    initialize: function (columnConfig, callback, resetCallback, showSaveAndShareTab, settings, previewSettings, additionalConfig, context) {

        this.config = columnConfig;
        this.callback = callback;
        this.resetCallback = resetCallback;
        this.showSaveAndShareTab = showSaveAndShareTab;
        this.isShared = settings && settings.isShared;
        this.previewSettings = previewSettings || {};
        this.additionalConfig = additionalConfig || {};
        this.context = context || {};

        this.settings = settings || {};

        if (!this.callback) {
            this.callback = function () {
            };
        }

        this.getConfigPanel();

        var tabs = [this.configPanel];

        if (this.showSaveAndShareTab) {
            this.savePanel = this.getSaveAndSharePanel();
            tabs.push(this.savePanel);
        }

        this.tabPanel = new Ext.TabPanel({
            activeTab: 0,
            forceLayout: true,
            items: tabs
        });

        buttons = [];

        if (this.previewSettings && this.previewSettings.allowPreview) {
            buttons.push({
                    text: t("refresh_preview"),
                    iconCls: "pimcore_icon_refresh",
                    handler: function () {
                        this.updatePreview();
                    }.bind(this)
                }
            );
        }

        if (this.resetCallback) {
            buttons.push(
                {
                    xtype: "button",
                    text: t("reset_config"),
                    // hidden: this.isShared,
                    iconCls: "pimcore_icon_cancel",
                    tooltip: t('reset_config_tooltip'),
                    style: {
                        marginLeft: 100
                    },
                    handler: function () {
                        this.resetToDefault();
                    }.bind(this)
                }
            );
        }

        buttons.push({
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.commitData();
                }.bind(this)
            }
        );

        if (this.showSaveAndShareTab) {
            buttons.push({
                text: this.isShared ? t("save_copy_and_share") : t("save_and_share"),
                iconCls: "pimcore_icon_save",
                handler: function () {
                    if (!this.nameField.getValue()) {
                        this.tabPanel.setActiveTab(this.savePanel);
                        Ext.Msg.show({
                            title: t("error"),
                            msg: t('invalid_name'),
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.ERROR
                        });
                        return;
                    }
                    this.commitData(true);
                }.bind(this)
            });
        }

        this.window = new Ext.Window({
            width: 950,
            height: '95%',
            modal: true,
            title: t('grid_options'),
            layout: "fit",
            items: [this.tabPanel],
            buttons: buttons
        });

        this.window.show();
        this.updatePreview();
    },

    getConfigPanel: function() {
        this.configPanel = new Ext.Panel({
            layout: "border",
            iconCls: "pimcore_icon_table",
            title: t("grid_configuration"),
            items: [this.getLanguageSelection(), this.getSelectionPanel(), this.getLeftPanel()]
        });
        return this.configPanel;
    },

    getLeftPanel: function () {
    },

    commitData: function (save, preview) {
    },

    getSelectionPanel: function () {
    },

    getSaveAndSharePanel: function () {

        var user = pimcore.globalmanager.get("user");
        if (user.isAllowed("share_configurations")) {

            this.userStore = new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_user_getusersforsharing'),
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'id'
                    }
                },
                fields: ['id', 'label']
            });

            this.rolesStore = new Ext.data.JsonStore({
                autoDestroy: true,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_user_getrolesforsharing'),
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'id'
                    }
                },
                fields: ['id', 'label']
            });
        }

        this.nameField = new Ext.form.TextField({
            fieldLabel: t('name'),
            name: 'gridConfigName',
            length: 50,
            allowBlank: false,
            width: '100%',
            value: this.settings ? this.settings.gridConfigName : ""
        });

        this.descriptionField = new Ext.form.TextArea({
            fieldLabel: t('description'),
            name: 'gridConfigDescription',
            height: 200,
            width: '100%',
            value: this.settings ? this.settings.gridConfigDescription : ""
        });

        if (user.isAllowed("share_configurations")) {
            this.userSharingField = Ext.create('Ext.form.field.Tag', {
                name: "sharedUserIds",
                width: '100%',
                height: 100,
                fieldLabel: t("shared_users"),
                queryDelay: 0,
                resizable: true,
                queryMode: 'local',
                minChars: 1,
                store: this.userStore,
                displayField: 'label',
                valueField: 'id',
                forceSelection: true,
                filterPickList: true,
                value: this.settings.sharedUserIds ? this.settings.sharedUserIds : ""
            });

            this.rolesSharingField = Ext.create('Ext.form.field.Tag', {
                name: "sharedRoleIds",
                width: '100%',
                height: 100,
                fieldLabel: t("shared_roles"),
                queryDelay: 0,
                resizable: true,
                queryMode: 'local',
                minChars: 1,
                store: this.rolesStore,
                displayField: 'label',
                valueField: 'id',
                forceSelection: true,
                filterPickList: true,
                value: this.settings.sharedRoleIds ? this.settings.sharedRoleIds : ""
            });
        }

        var items = [this.nameField, this.descriptionField];

        var user = pimcore.globalmanager.get("user");
        if (user.admin) {
            this.shareGlobally = new Ext.form.field.Checkbox(
                {
                    fieldLabel: t("share_globally"),
                    inputValue: true,
                    name: "shareGlobally",
                    value: this.settings.shareGlobally
                }
            );

            items.push(this.shareGlobally);
        }

        if (user.isAllowed("share_configurations")) {
            items.push(this.userSharingField);
            items.push(this.rolesSharingField);
        }

        this.settingsForm = Ext.create('Ext.form.FormPanel', {
            defaults: {
                labelWidth: 200
            },
            hidden: !this.showSaveAndShareTab,
            bodyStyle: "padding:10px;",
            autoScroll: true,
            border: false,
            iconCls: "pimcore_icon_save_and_share",
            title: user.isAllowed("share_configurations") ? t("save_and_share") : t("save"),
            items: items
        });
        return this.settingsForm;
    },

    doBuildChannelConfigTree: function (configuration) {

        var elements = [];
        if (configuration) {
            for (var i = 0; i < configuration.length; i++) {
                var configElement = this.getConfigElement(configuration[i]);
                if (configElement) {
                    var treenode = configElement.getConfigTreeNode(configuration[i]);

                    if (configuration[i].childs) {
                        var childs = this.doBuildChannelConfigTree(configuration[i].childs);
                        treenode.children = childs;
                        if (childs.length > 0) {
                            treenode.expandable = true;
                        }
                    }
                    elements.push(treenode);
                } else {
                    console.log("config element not found");
                }
            }
        }
        return elements;
    },

    resetToDefault: function () {
        if (this.resetCallback) {
            this.resetCallback();
        } else {
            console.log("not supported");
        }
        this.window.close();
    },

    doGetRecursiveData: function (node) {
        var childs = [];
        node.eachChild(function (child) {
            var attributes = child.data.configAttributes;
            attributes.childs = this.doGetRecursiveData(child);
            childs.push(attributes);
        }.bind(this));

        return childs;
    },

    updatePreview: function () {
        if (this.previewSettings && this.previewSettings.allowPreview) {
            this.commitData(false, true);
        }
    },

    getLanguageSelection: function (config) {
        config = config || {};

        var storedata = [];


        if (!config.omitDefault) {
            storedata.push(["default", t("default")]);
        }
        for (let i = 0; i < pimcore.settings.websiteLanguages.length; i++) {
            storedata.push([pimcore.settings.websiteLanguages[i],
                pimcore.available_languages[pimcore.settings.websiteLanguages[i]]]);
        }

        var itemsPerPageStore = [
            [25, "25"],
            [50, "50"],
            [100, "100"],
            [200, "200"],
            [999999, t("all")]
        ];

        let languageConfig = {
            name: "language",
            width: 250,
            mode: 'local',
            autoSelect: true,
            editable: false,
            emptyText: config.emptyText,
            value: this.config.language,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'id',
                    'label'
                ],
                data: storedata
            }),
            triggerAction: 'all',
            valueField: 'id',
            displayField: 'label'
        };

        if (!config.disablePreviewUpdate) {
            languageConfig.listeners = {
                change: function() {
                    this.updatePreview();
                }.bind(this)
            };
        }

        this.languageField = new Ext.form.ComboBox(languageConfig);

        this.itemsPerPage = new Ext.form.ComboBox({
            name: "itemsperpage",
            fieldLabel: t("items_per_page"),
            width: 200,
            mode: 'local',
            autoSelect: true,
            editable: true,
            value: (this.config.pageSize ? this.config.pageSize : pimcore.helpers.grid.getDefaultPageSize(-1)),
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'id',
                    'label'
                ],
                data: itemsPerPageStore
            }),
            triggerAction: 'all',
            valueField: 'id',
            displayField: 'label'
        });

        let items = [this.languageField];
        if (config.additionalItem) {
            items.push(config.additionalItem);
        }
        items.push({
            xtype: 'tbfill'
        });
        items.push(this.itemsPerPage);

        if (this.previewSettings.showPreviewSelector) {
            items.push({
                xtype: "button",
                text: t("preview_item"),
                iconCls: "pimcore_icon_search",
                handler: this.openSearchEditor.bind(this),
                style: {
                    marginLeft: '20px'
                },
            });
        }

        var compositeConfig = {
            xtype: "fieldset",
            layout: 'hbox',
            border: false,
            style: "border-top: none !important;",
            hideLabel: false,
            fieldLabel: t("language"),
            items: items
        };

        if (!this.languagePanel) {
            this.languagePanel = new Ext.form.FormPanel({
                region: "north",
                height: 43,
                items: [compositeConfig]
            });
        }

        return this.languagePanel;
    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.applyPreviewItem.bind(this), {
                type: this.previewSettings.previewSelectorTypes,
                subtype: this.previewSettings.previewSelectorSubTypes,
                specific: this.previewSettings.previewSelectorSpecific
            },
            {
            });

    },

    applyPreviewItem: function (data) {
        this.previewSettings.specificId = data.id;
        this.requestPreview();
    },

    parentIsOperator: function (record) {
        while (record) {
            if (record.data.isOperator) {
                return true;
            }
            record = record.parentNode;
        }
        return false;
    },

    getNodeTypeAndClass: function (node) {
        var type = "value";
        var className = "";
        if (node.data.configAttributes) {
            type = node.data.configAttributes.type;
            className = node.data.configAttributes['class'];
        } else if (node.data.dataType) {
            className = node.data.dataType.toLowerCase();
        }
        return {type: type, className: className};
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts) {
        e.stopEvent();

        tree.select();

        var menu = new Ext.menu.Menu();

        if (this.id != 0) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (node) {
                    record.parentNode.removeChild(record, true);
                }.bind(this, record)
            }));

            if (record.data.children && record.data.children.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('collapse_children'),
                    iconCls: "pimcore_icon_collapse_children",
                    handler: function (node) {
                        record.collapseChildren();
                    }.bind(this, record)
                }));

                menu.add(new Ext.menu.Item({
                    text: t('expand_children'),
                    iconCls: "pimcore_icon_expand_children",
                    handler: function (node) {
                        record.expandChildren();
                    }.bind(this, record)
                }));
            }

            if (record.data.isOperator) {
                menu.add(new Ext.menu.Item({
                    text: t('edit'),
                    iconCls: "pimcore_icon_edit",
                    handler: function (node) {
                        this.getConfigElement(node.data.configAttributes).getConfigDialog(node,
                            {
                                callback: this.updatePreview.bind(this)
                            });
                    }.bind(this, record)
                }));
            }
        }

        menu.showAt(e.pageX, e.pageY);
    },

    getOperatorTrees: function () {
        return [];
    },

    getOperatorTree: function (operatorGroupName, groups) {
        var groupKeys = [];
        for (k in groups) {
            if (groups.hasOwnProperty(k)) {
                groupKeys.push(k);
            }
        }

        groupKeys.sort();

        var len = groupKeys.length;

        var groupNodes = [];

        for (i = 0; i < len; i++) {
            var k = groupKeys[i];
            var childs = groups[k];
            childs.sort(
                function (x, y) {
                    return x.text < y.text ? -1 : 1;
                }
            );

            var groupNode = {
                iconCls: 'pimcore_icon_folder',
                text: t(k),
                allowDrag: false,
                allowDrop: false,
                leaf: false,
                expanded: true,
                children: childs
            };

            groupNodes.push(groupNode);
        }

        var tree = new Ext.tree.TreePanel({
            title: t('operator_group_' + operatorGroupName),
            iconCls: 'pimcore_icon_gridconfig_operator_' + operatorGroupName,
            xtype: "treepanel",
            region: "south",
            autoScroll: true,
            layout: 'fit',
            rootVisible: false,
            resizeable: true,
            split: true,
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    ddGroup: "columnconfigelement",
                    enableDrag: true,
                    enableDrop: false
                }
            },
            root: {
                id: "0",
                root: true,
                text: t("base"),
                draggable: false,
                leaf: false,
                isTarget: false,
                children: groupNodes
            }
        });

        return tree;
    },

    getConfigElement: function (configAttributes) {
    }

});
