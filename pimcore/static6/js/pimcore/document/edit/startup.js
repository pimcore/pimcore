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
var editables = [];
var editablesReady = false;
var editableNames = [];
var editWindow;


// i18n
var pimcore_system_i18n = parent.pimcore_system_i18n;

if (typeof pimcore == "object") {
    pimcore.registerNS("pimcore.globalmanager");
    pimcore.registerNS("pimcore.helpers");

    pimcore.globalmanager = parent.pimcore.globalmanager;
    pimcore.helpers = parent.pimcore.helpers;
    pimcore.settings = parent.pimcore.settings;
}

if (pimcore_document_id) {
    editWindow = pimcore.globalmanager.get("document_" + pimcore_document_id).edit;
    editWindow.reloadInProgress = false;
    editWindow.frame = window;

    window.onbeforeunload = editWindow.iframeOnbeforeunload.bind(editWindow);
}


Ext.Loader.setConfig({
    enabled: true
});

Ext.Loader.setPath('Ext.ux', '/pimcore/static6/js/lib/ext/ux');

Ext.require([
    'Ext.dom.Element',
    'Ext.ux.form.MultiSelect'
]);

var dndManager;

Ext.onReady(function () {
    var body = Ext.getBody();

    // causes styling issues, we don't need this anyway
    body.removeCls("x-body");

    /* Drag an Drop from Tree panel */
    // IE HACK because the body is not 100% at height
    try {
        //TODO EXT5
        Ext.getBody().applyStyles("min-height:" +
        parent.Ext.get('document_iframe_' + window.editWindow.document.id).getHeight() + "px");
    } catch (e) {
        console.log(e);
    }

    try {
        // init cross frame drag & drop handler
        dndManager = new pimcore.document.edit.dnd(parent.Ext, Ext.getBody(),
            parent.Ext.get('document_iframe_' + window.editWindow.document.id));
    } catch (e) {
        console.log(e);
    }

    body.on("click", function () {
       parent.Ext.menu.MenuMgr.hideAll();
    });

    // this sets the height of the body and html element to the current absolute height of the page
    // this is because some pages set the body height, and the positioning is then done by "absolute"
    // the problem is that ExtJS relies on the body height for DnD, so if the body isn't as high as the whole page
    // dnd works only in the section covered by the specified body height
    window.setInterval(pimcore.edithelpers.setBodyHeight, 1000);

    Ext.QuickTips.init();
    
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
            pimcore.helpers.showNotification("ERROR", "Duplicate editable name: " + name, "error");
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

        editablesReady = true;

        // add lazyload styles
        // this is necessary, because otherwise ext will overwrite many default styles (reset.css)
        // and then the style detection of eg. input, textarea editable isn't accurate anymore
        Ext.each(Ext.query("link[type='pimcore-lazyload-style']"), function (item) {
            item.setAttribute("type", "text/css");
            item.setAttribute("rel", "stylesheet");
        });

        // register the global key bindings
        pimcore.helpers.registerKeyBindings(document, Ext);


        // add contextmenu note in help tool-tips
        var editablesForTooltip = Ext.query(".pimcore_editable");
        var tmpEl;
        for (var e=0; e<editablesForTooltip.length; e++) {
            tmpEl = Ext.get(editablesForTooltip[e]);
            if(tmpEl) {
                if(tmpEl.hasCls("pimcore_tag_inc") || tmpEl.hasCls("pimcore_tag_href")
                                    || tmpEl.hasCls("pimcore_tag_image") || tmpEl.hasCls("pimcore_tag_renderlet")
                                    || tmpEl.hasCls("pimcore_tag_snippet")) {
                    new Ext.ToolTip({
                        target: tmpEl,
                        showDelay: 100,
                        anchor: "left",
                        html: t("click_right_for_more_options")
                    });
                }


                if(tmpEl.hasCls("pimcore_tag_snippet") || tmpEl.hasCls("pimcore_tag_renderlet")
                                    || tmpEl.hasCls("pimcore_tag_inc") ) {
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
                                pimcore.helpers.openDocument(this.getAttribute("pimcore_id"),
                                                                                this.getAttribute("pimcore_type"));
                            }.bind(this)
                        }));

                        menu.showAt(e.getXY());

                        e.stopEvent();
                    });
                }
            }
        }

    }

    // put a mask over all iframe, because they would break the dnd functionality
    editWindow.maskFrames();

    // enable the edit tab again
    if (typeof editWindow.loadMask != 'undefined') {
        editWindow.loadMask.hide();
    }
});



       

