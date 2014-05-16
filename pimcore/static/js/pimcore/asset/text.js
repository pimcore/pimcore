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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.asset.text");
pimcore.asset.text = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.id = intval(id);
        this.setType("text");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "text");

        this.properties = new pimcore.element.properties(this, "asset");
        this.versions = new pimcore.asset.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");
        this.notes = new pimcore.element.notes(this, "asset");
        this.metadata = new pimcore.asset.metadata(this);

        this.getData();
    },

    getTabPanel: function () {
        var items = [];

        items.push(this.getEditPanel());

        if (this.isAllowed("publish")) {
            items.push(this.metadata.getLayout());
        }
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
            
            this.editArea = new Ext.form.TextArea({
                xtype: "textarea",
                name: "data",
                value: this.data.data,
                style: "font-family: 'Courier New', Courier, monospace;"
            });
            
            this.editPanel = new Ext.Panel({
                title: t("edit"),
                iconCls: "pimcore_icon_tab_edit",
                bodyStyle: "padding: 10px;",
                items: [this.editArea]
            });
            this.editPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                this.editArea.setWidth(width-20);
                this.editArea.setHeight(height-20);
            }.bind(this));
        }

        return this.editPanel;
    },
    
    
    getSaveData : function ($super, only) {
        var parameters = $super(only);
        
        if(!Ext.isString(only)) {
            parameters.data = this.editArea.getValue();
        }
        
        return parameters;
    }
});

