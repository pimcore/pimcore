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
var requiredEditables = [];
var editablesReady = false;
var editableNames = [];
var editWindow;


// i18n
var pimcore_system_i18n = parent.pimcore_system_i18n;

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

// we need to disable touch support here, otherwise drag & drop of new & existing areablock doesn't work on hybrid devices
// see also https://github.com/pimcore/pimcore/issues/1542
// this should be removed in later ExtJS version ( > 6.0) as this should be hopefully fixed by then
Ext.supports.Touch = false;

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

Ext.Loader.setPath('Ext.ux', '/bundles/pimcoreadmin/js/lib/ext/ux');

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
        editWindow.toggleTagHighlighting(false);
    });

    Ext.QuickTips.init();
    Ext.MessageBox.minPromptWidth = 500;

    function getEditable(definition) {
        let type = definition.type
        let name = definition.name;
        let inherited = false;
        if(typeof definition["inherited"] != "undefined") {
            inherited = definition["inherited"];
        }

        let EditableClass = pimcore.document.editables[type] || pimcore.document.tags[type];

        if (typeof EditableClass !== 'function') {
            throw 'Editable of type `' + type + '` with name `' + name + '` could not be found.';
        }

        if (definition.inDialogBox && typeof EditableClass.prototype['render'] !== 'function') {
            throw 'Editable of type `' + type + '` with name `' + name + '` does not support the use in the dialog box.';
        }

        if (in_array(name, editableNames)) {
            pimcore.helpers.showNotification("ERROR", "Duplicate editable name: " + name, "error");
        }
        editableNames.push(name);

        let editable = new EditableClass(definition.id, name, definition.config, definition.data, inherited);
        editable.setRealName(definition.realName);
        editable.setInDialogBox(definition.inDialogBox);

        if (!definition.inDialogBox) {
            if (typeof editable['render'] === 'function') {
                editable.render();
            }
            editable.setInherited(inherited);
        }

        return editable;
    }

    if (typeof Ext == "object" && typeof pimcore == "object") {

        for (var i = 0; i < editableDefinitions.length; i++) {
            try {
                let editable = getEditable(editableDefinitions[i]);
                editables.push(editable);
                if (editableDefinitions[i]['config']['required']) {
                    requiredEditables.push(editable)
                }
            } catch (e) {
                console.error(e);
                if(e.stack) {
                    console.error(e.stack);
                }
            }
        }

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

            if (tmpEl && tmpEl.hasCls("pimcore_tag_inc")
                || tmpEl.hasCls("pimcore_tag_href")
                || tmpEl.hasCls("pimcore_tag_image")
                || tmpEl.hasCls("pimcore_tag_renderlet")
                || tmpEl.hasCls("pimcore_tag_snippet")
            ) {
                new Ext.ToolTip({
                    target: tmpEl,
                    showDelay: 100,
                    hideDelay: 0,
                    trackMouse: true,
                    html: t("click_right_for_more_options")
                });
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
