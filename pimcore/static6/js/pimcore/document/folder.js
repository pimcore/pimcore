/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.document.folder");
pimcore.document.folder = Class.create(pimcore.document.document, {

    initialize: function(id) {

        this.id = intval(id);
        this.setType("folder");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenDocument", this, "folder");
        this.getData();
    },

    init: function () {

        if (this.isAllowed("properties")) {
            try {
                this.properties = new pimcore.document.properties(this, "document");
            } catch (e) {
                console.log(e);
            }
        }
        if (this.isAllowed("settings")) {
            try {
                this.notes = new pimcore.element.notes(this, "document");
            } catch (e) {
                console.log(e);
            }
        }

        this.dependencies = new pimcore.element.dependencies(this, "document");
        this.tagAssignment = new pimcore.element.tag.assignment(this, "document");
    },

    getSaveData : function () {
        var parameters = {};

        parameters.id = this.id;

        if (this.isAllowed("properties")) {
            // properties
            try {
                parameters.properties = Ext.encode(this.properties.getValues());
            }
            catch (e) {
                //console.log(e);
            }
        }

        return parameters;
    },

    addTab: function () {

        var tabTitle = this.data.key;
        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var tabId = "document_" + this.id;
        this.tab = new Ext.Panel({
            id: tabId,
            title: tabTitle,
            closable:true,
            layout: "border",
            items: [
                this.getLayoutToolbar(),
                this.getTabPanel()
            ],
            iconCls: "pimcore_icon_" + this.data.type,
            document: this
        });

        this.tab.on("beforedestroy", function () {
            Ext.Ajax.request({
                url: "/admin/element/unlock-element",
                params: {
                    id: this.data.id,
                    type: "document"
                }
            });
        }.bind(this));

        // remove this instance when the panel is closed
        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove("document_" + this.id);
            pimcore.helpers.forgetOpenTab("document_" + this.id + "_folder");
        }.bind(this));

        this.tab.on("activate", function () {
            this.tab.updateLayout();
            pimcore.layout.refresh();
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.setActiveItem(tabId);
            pimcore.plugin.broker.fireEvent("postOpenDocument", this, "folder");
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.tabPanel.add(this.tab);

        // recalculate the layout
        pimcore.layout.refresh();
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.Button({
                text: t('save'),
                iconCls: "pimcore_icon_publish",
                scale: "small",
                handler: this.save.bind(this)
            });

            this.toolbarButtons.remove = new Ext.Button({
                text: t('delete_folder'),
                iconCls: "pimcore_icon_delete",
                scale: "small",
                handler: this.remove.bind(this)
            });


            var buttons = [];

            if (this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }

            if(this.isAllowed("delete") && !this.data.locked) {
                buttons.push(this.toolbarButtons.remove);
            }

            buttons.push("-");

            var moreButtons = [];

            moreButtons.push({
                text: t('reload'),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            });

            moreButtons.push({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_show_in_tree",
                handler: this.selectInTree.bind(this)
            });

            moreButtons.push({
                text: t("show_metainfo"),
                iconCls: "pimcore_icon_info",
                handler: this.showMetaInfo.bind(this)
            });

            moreButtons.push(this.getTranslationButtons());

            buttons.push({
                text: t("more"),
                iconCls: "pimcore_icon_more",
                scale: "small",
                menu: moreButtons
            });

            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: this.data.id,
                scale: "small"
            });

            this.toolbar = new Ext.Toolbar({
                id: "document_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "main-toolbar",
                items: buttons,
                overflowHandler: 'menu'
            });
        }

        return this.toolbar;
    },

    getTabPanel: function () {

        var items = [];

        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }

        items.push(this.dependencies.getLayout());

        if (this.isAllowed("settings")) {
            items.push(this.notes.getLayout());
        }

        var user = pimcore.globalmanager.get("user");
        if (user.isAllowed("tags_assignment")) {
            items.push(this.tagAssignment.getLayout());
        }


        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    showMetaInfo: function() {

        new pimcore.element.metainfo([
            {
                name: "id",
                value: this.data.id
            },
            {
                name: "path",
                value: this.data.path + this.data.key
            }, {
                name: "parentid",
                value: this.data.parentId
            }, {
                name: "type",
                value: this.data.type
            }, {
                name: "modificationdate",
                type: "date",
                value: this.data.modificationDate
            }, {
                name: "creationdate",
                type: "date",
                value: this.data.creationDate
            }, {
                name: "usermodification",
                type: "user",
                value: this.data.userModification
            }, {
                name: "userowner",
                type: "user",
                value: this.data.userOwner
            },
            {
                name: "deeplink",
                value: window.location.protocol + "//" + window.location.hostname + "/admin/login/deeplink?document_" + this.data.id + "_" + this.data.type
            }
        ], "folder");
    }
});

