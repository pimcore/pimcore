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

pimcore.registerNS("pimcore.element.selector.selector");
pimcore.element.selector.selector = Class.create({

    
    initialize: function (multiselect, callback, restrictions, config) {
        
        this.initialRestrictions = restrictions ? restrictions: {};
        this.callback = callback;
        this.restrictions = restrictions;
        this.multiselect = multiselect;
        this.config = typeof config != "undefined" ? config : {};
        
        if(!this.multiselect) {
            this.multiselect = false;
        }
        
        var possibleClassRestrictions = [];
        var classStore = pimcore.globalmanager.get("object_types_store");
        classStore.each(function (rec) {
             possibleClassRestrictions.push(rec.data.text);
        });
        
        var restrictionDefaults = {
            type: ["document","asset","object"],
            subtype: {
                document: ["page", "snippet","folder","link","hardlink","email"], //email added by ckogler
                asset: ["folder", "image", "text", "audio", "video", "document", "archive", "unknown"],
                object: ["object", "folder", "variant"]
            },
            specific: {
                classes: possibleClassRestrictions // put here all classes from global class store ...
            }
        };
        
        if(!this.restrictions) {
            this.restrictions = restrictionDefaults;
        }
        
        this.restrictions = Ext.applyIf(this.restrictions, restrictionDefaults);
        
        if(!this.callback) {
            this.callback = function () {};            
            //throw "";
        }

        this.panel = new Ext.Panel({
            tbar: this.getToolbar(),
            border: false,
            layout: "fit"
        });

        var windowWidth = 1000;
        if(this.multiselect) {
            windowWidth = 1250;
        }

        var windowConfig = {
            width: windowWidth,
            height: 550,
            modal: true,
            layout: "fit",
            items: [this.panel]
        };

        if(this.config["moveToTab"]) {
            windowConfig["tools"] = [{
                type: "maximize",
                tooltip: t("move_to_tab"),
                callback: this.moveToTab.bind(this)
            }];
        }

        this.window = new Ext.Window(windowConfig);
        
        this.window.show();
        
        
        var user = pimcore.globalmanager.get("user");
        
        if(in_array("document", this.restrictions.type) && user.isAllowed("documents")) {
            this.searchDocuments();
        }
        else if(in_array("asset", this.restrictions.type) && user.isAllowed("assets")) {
            this.searchAssets();
        }
        else if(in_array("object", this.restrictions.type) && user.isAllowed("objects")) {
            this.searchObjects();
        }
    },
    
    getToolbar: function () {
        
        var user = pimcore.globalmanager.get("user");
        var toolbar;
        var items = [];
        this.toolbarbuttons = {};
        
        if(in_array("document", this.restrictions.type) && user.isAllowed("documents")) {
            this.toolbarbuttons.document = new Ext.Button({
                text: t("documents"),
                handler: this.searchDocuments.bind(this),
                iconCls: "pimcore_icon_document",
                enableToggle: true
            });
            items.push(this.toolbarbuttons.document);
        }
        
        if(in_array("asset", this.restrictions.type) && user.isAllowed("assets")) {
            items.push("-");
            this.toolbarbuttons.asset = new Ext.Button({
                text: t("assets"),
                handler: this.searchAssets.bind(this),
                iconCls: "pimcore_icon_asset",
                enableToggle: true
            });
            items.push(this.toolbarbuttons.asset);
        }
        
        if(in_array("object", this.restrictions.type) && user.isAllowed("objects")) {
            items.push("-");
            this.toolbarbuttons.object = new Ext.Button({
                text: t("data_objects"),
                handler: this.searchObjects.bind(this),
                iconCls: "pimcore_icon_object",
                enableToggle: true
            });
            items.push(this.toolbarbuttons.object);
        }
        
        if(items.length > 2) {
            toolbar = {
                items: items
            };
        }
        
        return toolbar;
    },

    moveToTab: function () {

        // create new tab-panel
        this.myTabId = "pimcore_search_" + uniqid();

        var moveData = null;
        if (this.current.prepareForMove) {
            moveData = this.current.prepareForMove();
        }

        this.window.remove(this.panel, false);

        this.tabpanel = new Ext.Panel({
            id: this.myTabId,
            iconCls: "pimcore_icon_search",
            title: t(this.current.getTabTitle()),
            border: false,
            layout: "fit",
            closable:true
        });

        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.add(this.tabpanel);
        tabPanel.setActiveItem(this.myTabId);

        this.tabpanel.add(this.panel);

        pimcore.layout.refresh();

        this.window.close();

        if (this.current.afterMove) {
            this.current.afterMove(moveData);
        }

    },

    setSearch: function (panel) {
        delete this.current;
        this.panel.removeAll();
        this.panel.add(panel);
        
        this.panel.updateLayout();
    },
    
    resetToolbarButtons: function () {
        if(this.toolbarbuttons.document) {
            this.toolbarbuttons.document.toggle(false);
        }
        if(this.toolbarbuttons.asset) {
        this.toolbarbuttons.asset.toggle(false);
        }
        if(this.toolbarbuttons.object) {
            this.toolbarbuttons.object.toggle(false);
        }
    },
    
    searchDocuments: function () {
        this.resetToolbarButtons();
        this.toolbarbuttons.document.toggle(true);
        
        this.current = new pimcore.element.selector.document(this);
    },
    
    searchAssets: function () {
        this.resetToolbarButtons();
        this.toolbarbuttons.asset.toggle(true);
        
        this.current = new pimcore.element.selector.asset(this);
    },
    
    searchObjects: function () {
        this.resetToolbarButtons();
        this.toolbarbuttons.object.toggle(true);
        
        this.current = new pimcore.element.selector.object(this);
    },
    
    commitData: function (data) {       
        this.callback(data);
        this.window.close();
    }
});