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

pimcore.registerNS("pimcore.document.seemode");
pimcore.document.seemode = Class.create({


    initialize: function(path) {
        this.windowInitialized = false;
        this.start();
    },

    start: function () {

        if (this.windowInitialized == false) {
            this.createWindow();
        }
        this.window.show();

        if (!path) {
            var path = this.determineCurrentPagePath();
        }

        this.setIframeSrc(path);
    },

    createWindow: function () {

        this.windowInitialized = true;

        this.window = new Ext.Window({
            layout:'fit',
            width:500,
            height:300,
            closeAction:'hide',
            plain: true,
            bodyCls: "pimcore_overflow_scrolling",
            html: '<iframe id="pimcore_seemode" name="pimcore_seemode" src="about:blank" frameborder="0" style="width: 100%;" '
                        + 'allowtransparency="false"></iframe>',
            maximized: true,
            buttons: [
                {
                    text: t("edit_current_page"),
                    iconCls: "pimcore_icon_edit",
                    handler: this.edit.bind(this)
                }
            ]
        });
        this.window.on("resize", this.setLayoutFrameDimensions.bind(this));

        pimcore.viewport.add(this.window);
    },

    setLayoutFrameDimensions: function (el, width, height, rWidth, rHeight) {

        Ext.get("pimcore_seemode").setStyle({
            height: (height-94) + "px",
            backgroundColor: "#fff"
        });
    },

    setIframeSrc: function (path) {
        var d = new Date();
        Ext.get("pimcore_seemode").dom.setAttribute("src", path + "?_time=" + d.getTime());
    },

    determineCurrentPagePath: function () {

        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var activeTab = tabPanel.getActiveTab();

        if (activeTab) {
            // test if current tab is a document
            if (activeTab.initialConfig.document) {
                return activeTab.initialConfig.document.data.path + activeTab.initialConfig.document.data.key;
            }
        }
        return "/";
    },

    edit: function () {

        // get current location
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_document_getidforpath'),
            params: {
                path: window["pimcore_seemode"].location.pathname
            },
            success: this.getIdForPathComplete.bind(this)
        });
    },

    getIdForPathComplete: function (response) {

        var r = Ext.decode(response.responseText);

        if (r) {
            if (r.id) {
                pimcore.helpers.openDocument(r.id, r.type);
            }
        }

        this.window.hide();
    }

});
