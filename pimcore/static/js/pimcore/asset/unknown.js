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

pimcore.registerNS("pimcore.asset.unknown");
pimcore.asset.unknown = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.setType("unknown");

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "unknown");

        this.addLoadingPanel();
        this.id = parseInt(id);

        this.properties = new pimcore.settings.properties(this, "asset");
        this.versions = new pimcore.asset.versions(this);
        this.scheduler = new pimcore.settings.scheduler(this, "asset");
        this.permissions = new pimcore.asset.permissions(this);
        this.dependencies = new pimcore.settings.dependencies(this, "asset");

        this.getData();
    },

    getTabPanel: function () {
        console.log("unknown get tab panel");
        var items = [];

        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("versions")) {
            items.push(this.versions.getLayout());
        }
        if (this.isAllowed("settings")) {
            items.push(this.scheduler.getLayout());
        }
        if (this.isAllowed("permissions")) {
            items.push(this.permissions.getLayout());
        }
        items.push(this.dependencies.getLayout());

        var tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return tabbar;
    }
});