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

    // this sets the height of the body and html element to the current absolute height of the page
    // this is because some pages set the body height, and the positioning is then done by "absolute"
    // the problem is that ExtJS relies on the body height for DnD, so if the body isn't as high as the hole page
    // dnd works only in the section covered by the specified body height
    window.setTimeout(function () {
        try {
            var body = document.body,
                html = document.documentElement;

            var height = Math.max(body.scrollHeight, body.offsetHeight,
                html.clientHeight, html.scrollHeight, html.offsetHeight);

            Ext.getBody().setHeight(height);
            Ext.get(Ext.query("html")[0]).setHeight(height);
        } catch (e) {
            console.log(e);
        }
    }, 500);


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

        window.onbeforeunload = editWindow.iframeOnbeforeunload.bind(editWindow);
    }
    
    
    function getEditable(config) {
        var id = config.id;
        var type = config.type;
        var name = config.name;
        var options = config.options;
        var data = config.data;
        var inherited = false;
        if(typeof config["inherited"] != "undefined") {
            inherited = config["inherited"];
        }
        
        if(in_array(name,editableNames)) {
            Ext.MessageBox.alert("ERROR","Dublicate editable name: " + name);
        }
        editableNames.push(name);

        var tag = new pimcore.document.tags[type](id, name, options, data, inherited);
        tag.setInherited(inherited);

        return tag;
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
            alt: false,
            shift:false,
            stopEvent: true
        });
    
        // handler for F5
        var mapF5 = new Ext.KeyMap(document, {
            key: [116],
            fn: parent.pimcore.helpers.handleF5,
            stopEvent: true
        });

        
        // add contextmenu note in help tool-tips
        var editablesForTooltip = Ext.query(".pimcore_editable");
        var tmpEl;
        for (var e=0; e<editablesForTooltip.length; e++) {
            tmpEl = Ext.get(editablesForTooltip[e]);
            if(tmpEl) {
                if(tmpEl.hasClass("pimcore_tag_inc") || tmpEl.hasClass("pimcore_tag_href") || tmpEl.hasClass("pimcore_tag_image") || tmpEl.hasClass("pimcore_tag_renderlet") || tmpEl.hasClass("pimcore_tag_snippet")) {
                    new Ext.ToolTip({
                        target: tmpEl,
                        showDelay: 100,
                        anchor: "left",
                        title: t("click_right_for_more_options")
                    });
                }


                if(tmpEl.hasClass("pimcore_tag_snippet") || tmpEl.hasClass("pimcore_tag_renderlet") || tmpEl.hasClass("pimcore_tag_inc") ) {
                    tmpEl.on("mouseenter", function (e) {
                        pimcore.edithelpers.frameElement(this, Ext.getBody());
                    });

                    tmpEl.on("mouseleave", function () {
                        pimcore.edithelpers.unFrameElement();
                    });
                }
            }
        }

        // add contextmenu menu to elements included by $this->inc();
        var incElements = Ext.query(".pimcore_tag_inc");
        var tmpIncEl;
        for (var q=0; q<incElements.length; q++) {
            tmpIncEl = Ext.get(incElements[q]);
            if(tmpIncEl) {
                if(tmpIncEl.getAttribute("pimcore_id") && tmpIncEl.getAttribute("pimcore_type")) {
                    tmpIncEl.on("contextmenu", function (e) {
                        
                        var menu = new Ext.menu.Menu();
                        menu.add(new Ext.menu.Item({
                            text: t('open'),
                            iconCls: "pimcore_icon_open",
                            handler: function (item) {
                                item.parentMenu.destroy();
                                pimcore.helpers.openDocument(this.getAttribute("pimcore_id"), this.getAttribute("pimcore_type"));
                            }.bind(this)
                        }));

                        menu.showAt(e.getXY());

                        e.stopEvent();
                    });
                }
            }
        }

    }
});



       

