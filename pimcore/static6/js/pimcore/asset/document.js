/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.asset.document");
pimcore.asset.document = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.id = intval(id);
        this.setType("document");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "document");

        var user = pimcore.globalmanager.get("user");

        this.properties = new pimcore.element.properties(this, "asset");
        this.versions = new pimcore.asset.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");

        if (user.isAllowed("notes_events")) {
            this.notes = new pimcore.element.notes(this, "asset");
        }

        this.tagAssignment = new pimcore.element.tag.assignment(this, "asset");
        this.metadata = new pimcore.asset.metadata(this);

        this.getData();
    },

    getTabPanel: function () {

        var items = [];
        var user = pimcore.globalmanager.get("user");

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

        if (user.isAllowed("notes_events")) {
            items.push(this.notes.getLayout());
        }

        if (user.isAllowed("tags_assignment")) {
            items.push(this.tagAssignment.getLayout());
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
            var frameUrl = '/admin/asset/get-preview-document?id=' + this.id;

            //check for native/plugin PDF viewer
            if(this.hasNativePDFViewer()) {
                frameUrl += "?native-viewer=true"
            }

            this.editPanel = new Ext.Panel({
                title: t("preview"),
                bodyCls: "pimcore_overflow_scrolling",
                html: '<iframe src="' + frameUrl + '" frameborder="0" style="width: 100%;" id="asset_document_edit_' + this.id + '"></iframe>',
                iconCls: "pimcore_icon_edit"
            });
            this.editPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                Ext.get("asset_document_edit_" + this.id).setStyle({
                    height: (height-7) + "px"
                });
            }.bind(this));
        }

        return this.editPanel;
    },

    hasNativePDFViewer: function() {

        if(Ext.isChrome || Ext.isGecko || Ext.isSafari) {
            // Firefox, Chrome and Safari have native support, no need to further test anything
            return true;
        }

        var getActiveXObject = function(name) {
            // this is IE11 only (not Edge)
            try {
                return new ActiveXObject(name);
            } catch(e) {}
        };

        var hasNavigatorPlugin = function(name) {
            if(navigator["plugins"]) {
                for (key in navigator.plugins) {
                    var plugin = navigator.plugins[key];
                    if (plugin.name == name) {
                        return true;
                    }
                }
            }

            return false;
        };

        var supported = hasNavigatorPlugin('Adobe Acrobat') || hasNavigatorPlugin('Chrome PDF Viewer')
            || hasNavigatorPlugin('WebKit built-in PDF') || hasNavigatorPlugin('Edge PDF Viewer')
            || getActiveXObject('AcroPDF.PDF') || getActiveXObject('PDF.PdfCtrl');

        return supported;
    }
});

