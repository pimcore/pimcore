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

pimcore.registerNS("pimcore.tool.genericiframewindow");
pimcore.tool.genericiframewindow = Class.create({

    initialize: function (id, src, iconCls, title) {

        this.id = id;
        this.src = src;
        this.iconCls = iconCls;
        this.title = title;

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_iframe_" + this.id);
    },

    getTabPanel: function () {

        this.reloadButton = new Ext.Button({
            text: t("reload"),
            iconCls: "pimcore_icon_reload",
            handler: this.reload.bind(this)
        });

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_iframe_" + this.id,
                title: this.title,
                iconCls: this.iconCls,
                border: false,
                layout: "fit",
                closable:true,
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="about:blank" frameborder="0" width="100%" id="pimcore_iframe_frame_'
                                    + this.id + '"></iframe>',
                tbar: [this.reloadButton]
            });

            this.panel.on("resize", this.onLayoutResize.bind(this));
            this.panel.on("afterrender", this.reload.bind(this));

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_iframe_" + this.id);


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove(this.id);
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get("pimcore_iframe_frame_" + this.id).setStyle({
            height: (height - 50) + "px"
        });
    },

    reload: function () {
        try {
            var d = new Date();
            Ext.get("pimcore_iframe_frame_" + this.id).dom.src = this.src;
        }
        catch (e) {
            console.log(e);
        }
    }

});