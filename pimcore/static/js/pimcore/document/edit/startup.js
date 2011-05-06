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


// debug
if (!console) {
    if (!parent.console) {
        var console = {
            log: function (v) {
            }
        };
    }
    else {
        console = parent.console;
    }
}

// some globals
var pimcore_system_i18n;
var dndZones = []; // contains elements which are able to get data per dnd
var editables = [];
var editableNames = [];
var dndManager;

Ext.onReady(function () {

    Ext.QuickTips.init();

    // i18n
    pimcore_system_i18n = parent.pimcore_system_i18n;
    
    if (typeof pimcore == "object") {
        //editWindow.protectLocation();
        pimcore.registerNS("pimcore.globalmanager");
        pimcore.registerNS("pimcore.helpers");
    
        pimcore.globalmanager = parent.pimcore.globalmanager;
        pimcore.helpers = parent.pimcore.helpers;
    }
    
    if (pimcore_document_id) {
        editWindow = pimcore.globalmanager.get("document_" + pimcore_document_id).edit;
        editWindow.reloadInProgress = false;
        editWindow.frame = window;
    }
    
    // disable reload & links
    function pimcoreOnUnload() {
        editWindow.protectLocation();
        //garbageCollect();
        //Event.unloadCache();
    }
    
    
    function getEditable(config) {
        var id = config.id;
        var type = config.type;
        var name = config.name;
        var options = config.options;
        var data = config.data;
        
        if(in_array(name,editableNames)) {
            Ext.MessageBox.alert("ERROR","Dublicate editable name: " + name);
        }
        editableNames.push(name);
        
        return new pimcore.document.tags[type](id, name, options, data);
    }
    
    if (typeof Ext == "object" && typeof pimcore == "object") {
    
        for (var i = 0; i < editableConfigurations.length; i++) {
            try {
                editables.push(getEditable(editableConfigurations[i]));
            } catch (e) {
                console.log(e);
            }
        }
    
        if (editWindow.lastScrollposition) {
            if (editWindow.lastScrollposition.top > 100) {
                window.scrollTo(editWindow.lastScrollposition.left, editWindow.lastScrollposition.top);
            }
        }
    
    
        /* Drag an Drop from Tree panel */
        // IE HACK because the body is not 100% at height
        var bodyHeight = parent.Ext.get('document_iframe_' + window.editWindow.document.id).getHeight() + "px";
        Ext.getBody().applyStyles("min-height:" + bodyHeight);
        // set handler
        dndManager = new pimcore.document.edit.dnd(parent.Ext, Ext.getBody(), parent.Ext.get('document_iframe_' + window.editWindow.document.id), dndZones);
        
        // handler for Esc
        var mapEsc = new Ext.KeyMap(document, {
            key: [27],
            fn: function () {
                closeCKeditors();
            },
            stopEvent: true
        });
    
        // handler for STRG+S
        var mapCtrlS = new Ext.KeyMap(document, {
            key: "s",
            fn: parent.pimcore.helpers.handleCtrlS,
            ctrl:true,
            stopEvent: true
        });
    
        // handler for F5
        var mapF5 = new Ext.KeyMap(document, {
            key: [116],
            fn: parent.pimcore.helpers.handleF5,
            stopEvent: true
        });
    }
});

       

