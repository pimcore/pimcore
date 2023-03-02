pimcore.registerNS("pimcore.bundle.personalization.startup");

pimcore.bundle.personalization.startup = Class.create({
    initialize: function () {

        // target groups
        Ext.define('pimcore.model.target_groups', {
            extend: 'Ext.data.Model',
            fields: ["id", "text"]
        });

        var targetGroupStore = Ext.create('Ext.data.JsonStore', {
            model: "pimcore.model.target_groups",
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_bundle_personalization_targeting_targetgrouplist'),
                reader: {
                    type: 'json'
                }
            }
        });

        targetGroupStore.load();
        pimcore.globalmanager.add("target_group_store", targetGroupStore);

        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));

        document.addEventListener(pimcore.events.prepareDocumentPageSettingsLayout, this.assignTargetGroupToPage.bind(this));
    },


    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (user.isAllowed("targeting") && perspectiveCfg.inToolbar("marketing.targeting")) {
            menu.marketing.items.push({
                text: t("personalization") + " / " + t("targeting"),
                iconCls: "pimcore_nav_icon_usergroup",
                itemId: 'pimcore_menu_marketing_personalization',
                hideOnClick: false,
                menu: {
                    cls: "pimcore_navigation_flyout",
                    shadow: false,
                    items: [
                        {
                            text: t("global_targeting_rules"),
                            iconCls: "pimcore_nav_icon_targeting",
                            itemId: 'pimcore_menu_marketing_personalization_global_targeting_rules',
                            handler: this.showTargetingRules
                        }, {
                            text: t('target_groups'),
                            iconCls: "pimcore_nav_icon_target_groups",
                            itemId: 'pimcore_menu_marketing_personalization_target_groups',
                            handler: this.showTargetGroups
                        }, {
                            text: t("targeting_toolbar"),
                            iconCls: "pimcore_nav_icon_targeting_toolbar",
                            itemId: 'pimcore_menu_marketing_personalization_targeting_toolbar',
                            handler: this.showTargetingToolbarSettings
                        }
                    ]
                }
            });
        }
    },

    showTargetingRules: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        try {
            tabPanel.setActiveTab(pimcore.globalmanager.get("targeting").getLayout());
        } catch (e) {
            var targeting = new pimcore.bundle.personalization.settings.rules.panel();
            pimcore.globalmanager.add("targeting", targeting);

            tabPanel.add(targeting.getLayout());
            tabPanel.setActiveTab(targeting.getLayout());

            targeting.getLayout().on("destroy", function () {
                pimcore.globalmanager.remove("targeting");
            }.bind(this));

            pimcore.layout.refresh();
        }
    },

    showTargetGroups: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        try {
            tabPanel.setActiveTab(pimcore.globalmanager.get("targetGroupsPanel").getLayout());
        } catch (e) {
            var targetGroups = new pimcore.bundle.personalization.settings.targetGroups.panel();
            pimcore.globalmanager.add("targetGroupsPanel", targetGroups);

            tabPanel.add(targetGroups.getLayout());
            tabPanel.setActiveTab(targetGroups.getLayout());

            targetGroups.getLayout().on("destroy", function () {
                pimcore.globalmanager.remove("targetGroupsPanel");
            }.bind(this));

            pimcore.layout.refresh();
        }
    },

    showTargetingToolbarSettings: function () {
        new pimcore.bundle.personalization.settings.targetingtoolbar();
    },

    assignTargetGroupToPage: function (e) {
        const document = e.detail.document;

        const assignTargetGroupBlock = {
            xtype: 'fieldset',
            title: t('assign_target_groups'),
            collapsible: true,
            autoHeight: true,
            defaults: {
                labelWidth: 300
            },
            defaultType: 'textfield',
            items: [
                Ext.create('Ext.ux.form.MultiSelect', {
                    fieldLabel: t('visitors_of_this_page_will_be_automatically_associated_with_the_selected_target_groups'),
                    store: pimcore.globalmanager.get("target_group_store"),
                    displayField: "text",
                    valueField: "id",
                    name: 'targetGroupIds',
                    width: 700,
                    //listWidth: 200,
                    value: document.data["targetGroupIds"].split(',').map(Number).filter(item => item),
                    minHeight: 100
                })
            ]
        };

        e.detail.layout.add(assignTargetGroupBlock);
    }
})

const personalization = new pimcore.bundle.personalization.startup();
