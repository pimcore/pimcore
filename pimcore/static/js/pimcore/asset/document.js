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

pimcore.registerNS("pimcore.asset.document");
pimcore.asset.document = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.id = intval(id);
        this.setType("document");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "document");

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
            var frameUrl = '/admin/asset/get-preview-document/id/' + this.id + '/';

            //check for native/plugin PDF viewer
            if(this.hasNativePDFViewer()) {
                frameUrl += "?native-viewer=true"
            }

            this.editPanel = new Ext.Panel({
                title: t("preview"),
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="' + frameUrl + '" frameborder="0" id="asset_document_edit_' + this.id + '"></iframe>',
                iconCls: "pimcore_icon_tab_edit"
            });
            this.editPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                Ext.get("asset_document_edit_" + this.id).setStyle({
                    width: width + "px",
                    height: (height) + "px"
                });
            }.bind(this));
        }

        return this.editPanel;
    },

    hasNativePDFViewer: function() {

        var getActiveXObject = function(name) {
            try { return new ActiveXObject(name); } catch(e) {}
        };

        var getNavigatorPlugin = function(name) {
            for(key in navigator.plugins) {
                var plugin = navigator.plugins[key];
                if(plugin.name == name) return plugin;
            }
        };

        var getPDFPlugin = function() {
            return this.plugin = this.plugin || function() {
                if(typeof window["ActiveXObject"] != "undefined") {
                    return getActiveXObject('AcroPDF.PDF') || getActiveXObject('PDF.PdfCtrl');
                } else {
                    return getNavigatorPlugin('Adobe Acrobat') || getNavigatorPlugin('Chrome PDF Viewer') || getNavigatorPlugin('WebKit built-in PDF');
                }
            }();
        };

        return !!getPDFPlugin();
    }
});

