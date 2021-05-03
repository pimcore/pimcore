/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
/**
 * @private
 * @internal
 */
var editWindow;

/**
 * @private
 * @internal
 */
var editableManager = new pimcore.document.editables.manager();

/**
 * @private
 * @internal
 */
var dndManager;


if (typeof pimcore == "object") {
    pimcore.registerNS("pimcore.globalmanager");
    pimcore.registerNS("pimcore.helpers");
    pimcore.registerNS("pimcore.treenodelocator");

    pimcore.globalmanager = parent.pimcore.globalmanager;
    pimcore.helpers = parent.pimcore.helpers;
    pimcore.settings = parent.pimcore.settings;
    pimcore.treenodelocator = parent.pimcore.treenodelocator;
}

if (pimcore_document_id) {
    editWindow = pimcore.globalmanager.get("document_" + pimcore_document_id).edit;
    editWindow.reloadInProgress = false;
    editWindow.frame = window;

    window.onbeforeunload = editWindow.iframeOnbeforeunload.bind(editWindow);
}

// overwrite default z-index of windows, this ensures that CKEditor is above ExtJS Windows
Ext.WindowManager.zseed = 10020;


Ext.Ajax.setDisableCaching(true);
Ext.Ajax.setTimeout(900000);
Ext.Ajax.setMethod("GET");
Ext.Ajax.setDefaultHeaders({
    'X-pimcore-csrf-token': parent.pimcore.settings["csrfToken"],
    'X-pimcore-extjs-version-major': Ext.getVersion().getMajor(),
    'X-pimcore-extjs-version-minor': Ext.getVersion().getMinor()
});

Ext.Loader.setConfig({
    enabled: true
});

Ext.Loader.setPath('Ext.ux', '/bundles/pimcoreadmin/extjs/ext-ux/src/classic/src');

Ext.require([
    'Ext.dom.Element',
    'Ext.ux.form.MultiSelect'
]);

Ext.onReady(function () {
    var body = Ext.getBody();

    // causes styling issues, we don't need this anyway
    body.removeCls("x-body");

    try {
        // init cross frame drag & drop handler
        dndManager = new pimcore.document.edit.dnd(parent.Ext, Ext.getBody(),
            parent.Ext.get('document_iframe_' + window.editWindow.document.id));
    } catch (e) {
        console.log(e);
    }

    body.on("click", function () {
        parent.Ext.menu.MenuMgr.hideAll();
        editWindow.toggleTagHighlighting(false);
    });

    Ext.QuickTips.init();
    Ext.MessageBox.minPromptWidth = 500;


    if (typeof Ext == "object" && typeof pimcore == "object") {

        // check for duplicate editables
        var editableHtmlEls = {};
        document.querySelectorAll('.pimcore_editable').forEach(editableEl => {
            if(editableHtmlEls[editableEl.id] && editableEl.dataset.name) {
                let message = "Duplicate editable name: " + editableEl.dataset.name;
                pimcore.helpers.showNotification("ERROR", message, "error");
                throw message;
            }
            editableHtmlEls[editableEl.id] = true;
        });

        // initialize editables
        editableDefinitions.forEach(editableDef => {
            editableManager.addByDefinition(editableDef);
        });

        if (editWindow.lastScrollposition) {
            if(typeof editWindow.lastScrollposition === 'string') {
                var scrollToEl = document.querySelector(editWindow.lastScrollposition);
                if(scrollToEl) {
                    scrollToEl.scrollIntoView();
                }
            } else if (editWindow.lastScrollposition.top > 100) {
                window.scrollTo(editWindow.lastScrollposition.left, editWindow.lastScrollposition.top);
            }
            editWindow.lastScrollposition = null;
        }

        editableManager.setInitialized(true);

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

            if (tmpEl && tmpEl.hasCls("pimcore_editable_inc")
                || tmpEl.hasCls("pimcore_editable_href")
                || tmpEl.hasCls("pimcore_editable_image")
                || tmpEl.hasCls("pimcore_editable_renderlet")
                || tmpEl.hasCls("pimcore_editable_snippet")
            ) {
                new Ext.ToolTip({
                    target: tmpEl,
                    showDelay: 1000,
                    hideDelay: 0,
                    trackMouse: false,
                    html: t("click_right_for_more_options")
                });
            }
        }

        // add contextmenu menu to elements included by $this->inc();
        var incElements = Ext.query(".pimcore_editable_inc");
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
