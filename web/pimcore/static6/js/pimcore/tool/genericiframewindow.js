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
        tabPanel.setActiveItem("pimcore_iframe_" + this.id);
    },

    getTabPanel: function () {

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'main-toolbar',
            items: [{
                text: t("reload"),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            }, {
                text: t("open"),
                iconCls: "pimcore_icon_cursor",
                handler: function () {
                    window.open(Ext.get("pimcore_iframe_frame_" + this.id).dom.getAttribute("src"));
                }.bind(this)
            }]
        });

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_iframe_" + this.id,
                title: this.title,
                iconCls: this.iconCls,
                border: false,
                layout: "fit",
                closable:true,
                bodyCls: "pimcore_overflow_scrolling",
                html: '<iframe src="about:blank" frameborder="0" style="width:100%;" id="pimcore_iframe_frame_'
                                    + this.id + '"></iframe>',
                tbar: toolbar
            });

            this.panel.on("resize", this.setLayoutFrameDimensions.bind(this));
            this.panel.on("afterrender", this.reload.bind(this));

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_iframe_" + this.id);

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove(this.id);
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    setLayoutFrameDimensions: function (el, width, height, rWidth, rHeight) {
        Ext.get("pimcore_iframe_frame_" + this.id).setStyle({
            height: (height - 55) + "px"
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