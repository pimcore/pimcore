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

        this.window = new Ext.Window({
            width: 850,
            height: 550,
            modal: true,
            layout: "fit",
            items: [this.panel]
        });
        
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
                text: t("objects"),
                handler: this.searchObjects.bind(this),
                iconCls: "pimcore_icon_object",
                enableToggle: true
            });
            items.push(this.toolbarbuttons.object);
        }

        if(this.config["moveToTab"]) {
            items.push("->");
            this.toolbarbuttons.moveToTab = new Ext.Button({
                text: t("move_to_tab"),
                handler: this.moveToTab.bind(this),
                iconCls: "pimcore_icon_movetotab",
                enableToggle: false
            });
            items.push(this.toolbarbuttons.moveToTab);
        }
        
        if(items.length > 1) {
            toolbar = {
                items: items
            };
        }
        
        return toolbar;
    },

    moveToTab: function () {

        this.toolbarbuttons.moveToTab.hide();

        // create new tab-panel
        this.myTabId = "pimcore_search_" + uniqid();

        this.tabpanel = new Ext.Panel({
            id: this.myTabId,
            iconCls: "pimcore_icon_search",
            title: t("search"),
            border: false,
            layout: "fit",
            closable:true,
            items: [this.panel]
        });

        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.add(this.tabpanel);
        tabPanel.activate(this.myTabId);

        pimcore.layout.refresh();

        this.window.close();
    },

    setSearch: function (panel) {
        delete this.current;
        this.panel.removeAll();
        this.panel.add(panel);
        
        this.panel.doLayout();
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