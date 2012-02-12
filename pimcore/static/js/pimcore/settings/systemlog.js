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

pimcore.registerNS("pimcore.settings.systemlog");
pimcore.settings.systemlog = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_systemlog");
    },

    getTabPanel: function () {

        this.stopButton = new Ext.Button({
            text: t("stop"),
            iconCls: "pimcore_icon_stop",
            handler: this.stop.bind(this)
        });

        this.startButton = new Ext.Button({
            text: t("auto_reload"),
            iconCls: "pimcore_icon_start",
            handler: this.start.bind(this),
            disabled: true
        });

        this.reloadButton = new Ext.Button({
            text: t("reload"),
            iconCls: "pimcore_icon_reload",
            handler: this.reload.bind(this)
        });

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_systemlog",
                title: t("systemlog"),
                iconCls: "pimcore_icon_systemlog",
                border: false,
                layout: "fit",
                closable:true,
                layout: "fit",
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="about:blank" frameborder="0" width="100%" id="pimcore_systemlog_frame"></iframe>',
                tbar: [this.startButton, this.stopButton, "-", this.reloadButton]
            });

            this.panel.on("resize", this.onLayoutResize.bind(this));

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_systemlog");


            this.panel.on("destroy", function () {
                clearInterval(this.interval);
                pimcore.globalmanager.remove("systemlog");
            }.bind(this));

            this.start();

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get("pimcore_systemlog_frame").setStyle({
            height: (height - 50) + "px"
        });
    },

    isLoadedComplete: function () {
        this.isLoaded = true;
    },

    getScrollPosition: function() {
        var d = this.frame.document.getElementsByTagName("body")[0],
            doc = this.frame.document,
            body = doc.body,
            docElement = doc.documentElement,
            l,
            t,
            ret;

        if(d == doc || d == body){
            if(Ext.isIE && Ext.isStrict){
                l = docElement.scrollLeft;
                t = docElement.scrollTop;
            }else{
                l = this.frame.pageXOffset;
                t = this.frame.pageYOffset;
            }
            ret = {left: l || (body ? body.scrollLeft : 0), top: t || (body ? body.scrollTop : 0)};
        }else{
            ret = {left: d.scrollLeft, top: d.scrollTop};
        }
        return ret;
    },

    reload: function () {

        if(this.isLoaded == false) {
            return;
        }

        try {
            this.lastScrollposition = this.getScrollPosition();
        }
        catch (e) {
            clearInterval(this.interval);
            console.log(e);
        }

        try {
            this.isLoaded = false;
            var d = new Date();
            Ext.get("pimcore_systemlog_frame").dom.src = "/admin/settings/systemlog?_dc=" + d.getTime();
        }
        catch (e) {
            clearInterval(this.interval);
            console.log(e);
        }
    },

    start: function () {
        this.interval = window.setInterval(this.reload.bind(this),5000);
        this.startButton.disable();
        this.stopButton.enable();
    },

    stop: function () {
        clearInterval(this.interval);

        this.startButton.enable();
        this.stopButton.disable();
    }

});