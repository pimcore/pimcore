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

    
    initialize: function (multiselect, callback, restrictions) {
        
        this.initialRestrictions = restrictions ? restrictions: {};
        this.callback = callback;
        this.restrictions = restrictions;
        this.multiselect = multiselect;
        
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
                document: ["page", "snippet","folder"],
                asset: ["folder", "image", "text", "audio", "video", "document", "archive", "unknown"],
                object: ["object", "folder"]
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
        
        this.window = new Ext.Window({
            width: 850,
            height: 550,
            modal: true,
            tbar: this.getToolbar(),
            layout: "fit"
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
            this.toolbarbuttons.asset = new Ext.Button({
                text: t("assets"),
                handler: this.searchAssets.bind(this),
                iconCls: "pimcore_icon_asset",
                enableToggle: true
            });
            items.push(this.toolbarbuttons.asset);
        }
        
        if(in_array("object", this.restrictions.type) && user.isAllowed("objects")) {
            this.toolbarbuttons.object = new Ext.Button({
                text: t("objects"),
                handler: this.searchObjects.bind(this),
                iconCls: "pimcore_icon_object",
                enableToggle: true
            });
            items.push(this.toolbarbuttons.object);
        }
        
        if(items.length > 1) {
            toolbar = {
                items: items
            };
        }
        
        return toolbar;
    },
    
    setSearch: function (panel) {
        delete this.current;
        this.window.removeAll();
        this.window.add(panel);
        
        this.window.doLayout();
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