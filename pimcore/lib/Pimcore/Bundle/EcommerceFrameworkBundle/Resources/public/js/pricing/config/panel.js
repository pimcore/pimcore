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


pimcore.registerNS("pimcore.bundle.EcommerceFramework.pricing.config.panel");

pimcore.bundle.EcommerceFramework.pricing.config.panel = Class.create({

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
     * panels of open pricing rules
     */
    panels: {},


    /**
     * constructor
     * @param layoutId
     */
    initialize: function(layoutId) {
        this.layoutId = layoutId;

        // load defined conditions & actions
        var _this = this;
        Ext.Ajax.request({
            url: "/admin/ecommerceframework/pricing/get-config",
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
        tabPanel.setActiveItem( this.layoutId );
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
                title: t("bundle_ecommerce_pricing_rules"),
                iconCls: "bundle_ecommerce_pricing_rules",
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
            tabPanel.setActiveItem( this.layoutId );

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
            this.saveButton = new Ext.Button({
                // save button
                hidden: true,
                text: t("bundle_ecommerce_pricing_config_save_order"),
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
                        url: "/admin/ecommerceframework/pricing/save-order",
                        params: {
                            rules: Ext.encode(rules)
                        },
                        method: "post",
                        success: function(){
                            button.hide();
                        }
                    });

                }
            });

            var store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                autoSync: true,
                proxy: {
                    type: 'ajax',
                    url: "/admin/ecommerceframework/pricing/list",
                    reader: {
                        type: 'json'
                    }
                }
            });

            this.tree = new Ext.tree.TreePanel({
                store: store,
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                containerScroll: true,
                width: 200,
                split: true,
                rootVisible: false,
                viewConfig: {
                    plugins: {
                        ptype: 'treeviewdragdrop'
                    }
                },
                listeners: {
                    itemclick: this.openRule.bind(this),
                    itemcontextmenu: function (tree, record, item, index, e, eOpts ) {
                        tree.select();

                        var menu = new Ext.menu.Menu();
                        menu.add(new Ext.menu.Item({
                            text: t('delete'),
                            iconCls: "pimcore_icon_delete",
                            handler: this.deleteRule.bind(this, tree, record)
                        }));

                        e.stopEvent();
                        menu.showAt(e.pageX, e.pageY);
                    }.bind(this),
                    'beforeitemappend': function (thisNode, newChildNode, index, eOpts) {
                        newChildNode.data.leaf = true;
                    },
                    itemmove: function(tree, oldParent, newParent, index, eOpts ) {
                        this.saveButton.show();
                    }.bind(this)
                },
                tbar: {
                    items: [
                        {
                            // add button
                            text: t("bundle_ecommerce_pricing_config_add_rule"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addRule.bind(this)
                        }, {
                            // spacer
                            xtype: 'tbfill'
                        }, this.saveButton
                    ]
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
        Ext.MessageBox.prompt(t('bundle_ecommerce_pricing_config_add_rule'), t('bundle_ecommerce_pricing_config_enter_the_name_of_the_new_rule'),
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
                url: "/admin/ecommerceframework/pricing/add",
                params: {
                    name: value,
                    documentId: (this.page ? this.page.id : null)
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.refresh(this.tree.getRootNode());

                    if(!data || !data.success) {
                        Ext.Msg.alert(t('add_target'), t('problem_creating_new_target'));
                    } else {
                        this.openRule(null, intval(data.id));
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

    refresh: function (record) {
        var ownerTree = record.getOwnerTree();
        record.data.expanded = true;
        ownerTree.getStore().load({
            node: record
        });
    },
    /**
     * delete existing rule
     */
    deleteRule: function (tree, record) {
        Ext.Ajax.request({
            url: "/admin/ecommerceframework/pricing/delete",
            params: {
                id: record.id
            },
            success: function () {
                this.refresh(this.tree.getRootNode());
            }.bind(this)
        });
    },


    /**
     * open pricing rule
     * @param node
     */
    openRule: function (tree, record, item, index, e, eOpts ) {

        if(!is_numeric(record)) {
            record = record.id;
        }

        //try {
            var pricingRuleKey = "pricingrule_" + record;
            if (this.panels[pricingRuleKey]) {
                this.panels[pricingRuleKey].activate();
            } else {
                // load defined rules
                Ext.Ajax.request({
                    url: "/admin/ecommerceframework/pricing/get",
                    params: {
                        id: record
                    },
                    success: function (response) {
                        var res = Ext.decode(response.responseText);
                        var item = new pimcore.bundle.EcommerceFramework.pricing.config.item(this, res);
                        this.panels[pricingRuleKey] = item;
                    }.bind(this)
                });
            }
        //} catch (e) {
        //    console.log(e);
        //}



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
