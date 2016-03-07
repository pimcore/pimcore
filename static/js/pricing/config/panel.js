/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


pimcore.registerNS("pimcore.plugin.OnlineShop.pricing.config.panel");

pimcore.plugin.OnlineShop.pricing.config.panel = Class.create({

    /**
     * @var string
     */
    layoutId: "",

    /**
     * @var array
     */
    condition: [],

    /**
     * @var array
     */
    action: [],


    /**
     * constructor
     * @param layoutId
     */
    initialize: function(layoutId) {
        this.layoutId = layoutId;

        // load defined conditions & actions
        var _this = this;
        Ext.Ajax.request({
            url: "/plugin/EcommerceFramework/Pricing/get-config",
            method: "GET",
            success: function(result){
                var config = Ext.decode(result.responseText);
                _this.condition = config.condition;
                _this.action = config.action;
            }
        });

        // create layout
        this.getLayout();
    },


    /**
     * activate panel
     */
    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate( this.layoutId );
    },


    /**
     * create tab panel
     * @returns Ext.Panel
     */
    getLayout: function () {

        if (!this.layout) {

            // create new panel
            this.layout = new Ext.Panel({
                id: this.layoutId,
                title: t("plugin_onlineshop_pricing_rules"),
                iconCls: "plugin_onlineshop_pricing_rules",
                border: false,
                layout: "border",
                closable: true,

                // layout...
                items: [
                    this.getTree(),         // item tree, left side
                    this.getTabPanel()    // edit page, right side
                ]
            });

            // add event listener
            var layoutId = this.layoutId;
            this.layout.on("destroy", function () {
                pimcore.globalmanager.remove( layoutId );
            }.bind(this));

            // add panel to pimcore panel tabs
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add( this.layout );
            tabPanel.activate( this.layoutId );

            // update layout
            pimcore.layout.refresh();
        }

        return this.layout;
    },


    /**
     * return treelist
     * @returns {*}
     */
    getTree: function () {
        if (!this.tree) {
            this.tree = new Ext.tree.TreePanel({
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                containerScroll: true,
                width: 200,
                split: true,
                root: {
                    nodeType: 'async',
                    id: '0'
                },
                loader: new Ext.tree.TreeLoader({
                    dataUrl: "/plugin/EcommerceFramework/Pricing/list",
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: {
                            click: this.openRule.bind(this),
                            contextmenu: function () {
                                this.select();

                                var menu = new Ext.menu.Menu();
                                menu.add(new Ext.menu.Item({
                                    text: t('delete'),
                                    iconCls: "pimcore_icon_delete",
                                    handler: this.attributes.reference.deleteRule.bind(this)
                                }));

                                menu.show(this.ui.getAnchor());
                            }
                        },
                        reference: this,
                        iconCls: "plugin_onlineshop_pricing_icon_rule",
                        leaf: true
                    }
                }),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            // add button
                            text: t("plugin_onlineshop_pricing_config_add_rule"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addRule.bind(this)
                        }, {
                            // spacer
                            xtype: 'tbfill'
                        }, {
                            // save button
                            id: 'btnSave',
                            hidden: true,
                            text: t("plugin_onlineshop_pricing_config_save_order"),
                            iconCls: "pimcore_icon_save",
                            handler: function() {
                                // this
                                var button = this;

                                // get current order
                                var prio = 0;
                                var rules = {};

                                this.ownerCt.ownerCt.getRootNode().eachChild(function (rule){
                                    prio++;
                                    rules[ rule.id ] = prio;
                                });

                                // save order
                                Ext.Ajax.request({
                                    url: "/plugin/EcommerceFramework/Pricing/save-order",
                                    params: {
                                        rules: Ext.encode(rules)
                                    },
                                    method: "post",
                                    success: function(){
                                        button.hide();
                                    }
                                });

                            }
                        }
                    ]
                },
                // DD sorting with save button
                enableDD: true,
                listeners: {
                    enddrag: function(tree, node, e){
                        tree.getTopToolbar().items.get('btnSave').show();
                    }
                }
            });

            this.tree.on("render", function () {
                this.getRootNode().expand();
            });
        }

        return this.tree;
    },


    /**
     * add item popup
     */
    addRule: function () {
        Ext.MessageBox.prompt(t('plugin_onlineshop_pricing_config_add_rule'), t('plugin_onlineshop_pricing_config_enter_the_name_of_the_new_rule'),
                                                this.addRuleComplete.bind(this), null, null, "");
    },


    /**
     * save added item
     * @param button
     * @param value
     * @param object
     * @todo ...
     */
    addRuleComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z0-9_\-]+/);
        if (button == "ok" && value.length > 2 && regresult == value) {
            Ext.Ajax.request({
                url: "/plugin/EcommerceFramework/Pricing/add",
                params: {
                    name: value,
                    documentId: (this.page ? this.page.id : null)
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.tree.getRootNode().reload();

                    if(!data || !data.success) {
                        Ext.Msg.alert(t('add_target'), t('problem_creating_new_target'));
                    } else {
                        this.openRule(intval(data.id));
                    }
                }.bind(this)
            });
        } else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_target'), t('problem_creating_new_target'));
        }
    },


    /**
     * delete existing rule
     */
    deleteRule: function () {
        Ext.Ajax.request({
            url: "/plugin/EcommerceFramework/Pricing/delete",
            params: {
                id: this.id
            },
            success: function () {
                this.attributes.reference.tree.getRootNode().reload();
            }.bind(this)
        });
    },


    /**
     * open pricing rule
     * @param node
     */
    openRule: function (node) {

        if(!is_numeric(node)) {
            node = node.id;
        }

        // load defined rules
        Ext.Ajax.request({
            url: "/plugin/EcommerceFramework/Pricing/get",
            params: {
                id: node
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                var item = new pimcore.plugin.OnlineShop.pricing.config.item(this, res);
            }.bind(this)
        });

    },


    /**
     * @returns Ext.TabPanel
     */
    getTabPanel: function () {
        if (!this.panel) {
            this.panel = new Ext.TabPanel({
                region: "center",
                border: false
            });
        }

        return this.panel;
    }
});
