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

pimcore.registerNS("pimcore.asset.document");
pimcore.asset.document = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.setType("document");

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "document");

        this.addLoadingPanel();

        this.id = intval(id);

        this.properties = new pimcore.element.properties(this, "asset");
        this.versions = new pimcore.asset.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");
        this.notes = new pimcore.element.notes(this, "asset");

        this.getData();
    },

    getTabPanel: function () {

        var items = [];

        items.push(this.getEditPanel());

        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("versions")) {
            items.push(this.versions.getLayout());
        }
        if (this.isAllowed("settings")) {
            items.push(this.scheduler.getLayout());
        }

        items.push(this.dependencies.getLayout());

        if (this.isAllowed("settings")) {
            items.push(this.notes.getLayout());
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

    getEditPanel: function () {

        if (!this.editPanel) {

            this.editPanel = new Ext.Panel({
                title: t("preview"),
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="/admin/asset/get-preview-document/id/' + this.id + '/" frameborder="0" id="asset_document_edit_' + this.id + '"></iframe>',
                iconCls: "pimcore_icon_tab_edit"
            });
            this.editPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                Ext.get("asset_document_edit_" + this.id).setStyle({
                    width: width + "px",
                    height: (height) + "px"
                });
            }.bind(this));
        }

        return this.editPanel;
    }
});

