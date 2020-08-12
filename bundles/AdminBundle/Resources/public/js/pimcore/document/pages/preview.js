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

pimcore.registerNS("pimcore.document.pages.preview");
pimcore.document.pages.preview = Class.create({

    initialize: function(page) {
        this.page = page;
        this.mode = "full";
        this.previewTime = new Date();
        this.previewTime.setHours(0);
        this.previewTime.setMinutes(0);
        this.previewTime.setSeconds(0);
        this.previewTime.setMilliseconds(0);

        this.availableHeight = null;
    },


    getLayout: function () {

        if (this.layout == null) {

            var iframeOnLoad = "pimcore.globalmanager.get('document_" + this.page.id + "').preview.iFrameLoaded()";

            // preview switcher only for pages not for emails
            var tbar = [];
            if(this.page.getType() == "page") {

                tbar = [{
                    text: t("desktop"),
                    iconCls: "pimcore_icon_desktop",
                    handler: this.setFullMode.bind(this)
                }, {
                    text: t("tablet"),
                    iconCls: "pimcore_icon_tablet",
                    handler: this.setMode.bind(this, {device: "tablet", width: 1024, height: 768})
                }, {
                    text: t("phone"),
                    iconCls: "pimcore_icon_mobile",
                    handler: this.setMode.bind(this, {device: "phone", width: 375, height: 667})
                },{
                    text: t("phone"),
                    iconCls: "pimcore_icon_mobile_landscape",
                    handler: this.setMode.bind(this, {device: "phone", width: 667, height: 375})
                }, "-", {
                    text: t("qr_codes"),
                    iconCls: "pimcore_icon_qrcode",
                    handler: function () {
                        var codeUrl = Routing.generate('pimcore_admin_reports_qrcode_code', {documentId: this.page.id});

                        var download = function () {
                            var codeUrl = Routing.generate('pimcore_admin_reports_qrcode_code', {documentId: this.page.id, download: true});
                            pimcore.helpers.download(codeUrl);
                        };

                        var qrWindow = new Ext.Window({
                            width: 280,
                            border:false,
                            title: t("qr_codes"),
                            modal: true,
                            autoScroll: true,
                            bodyStyle: "padding: 10px; text-align:center;",
                            items: [{
                                    html: '<img src="' + codeUrl + '" style="padding:10px; height:250px;" />',
                                    border: true,
                                    height: 250
                                }, {
                                border: false,
                                buttons: [{
                                    width: "100%",
                                    text: t("download"),
                                    iconCls: "pimcore_icon_png",
                                    handler: download.bind(this)
                                }]
                            }]
                        });

                        qrWindow.show();

                    }.bind(this)
                }, "-", {
                    text: t("open_in_new_window"),
                    iconCls: "pimcore_icon_open_window",
                    handler: function () {
                        var date = new Date();
                        var link = this.page.data.path + this.page.data.key;
                        var linkParams = [];

                        linkParams.push("pimcore_preview=true");
                        linkParams.push("_dc=" + date.getTime());

                        // add target group parameter if available
                        if(this["edit"] && this.page.edit["targetGroup"]) {
                            if(this.page.edit.targetGroup && this.page.edit.targetGroup.getValue()) {
                                linkParams.push("_ptg=" + this.page.edit.targetGroup.getValue());
                            }
                        }

                        if(linkParams.length) {
                            link += "?" + linkParams.join("&");
                        }

                        window.open(link);
                    }.bind(this)
                }];
            }

            this.iframeName = "document_preview_iframe_" + this.page.id;

            this.framePanel = new Ext.Panel({
                border: false,
                region: "center",
                scrollable: false,
                bodyStyle: "background:#323232;",
                bodyCls: "pimcore_overflow_scrolling",
                html: '<iframe src="about:blank" onload="' + iframeOnLoad + '" frameborder="0" ' +
                    'style="width: 100%;background: #fff;" id="' + this.iframeName + '" ' +
                    'name="' + this.iframeName + '"></iframe>'
            });

            this.timeSlider = Ext.create('Ext.slider.Single', {
                region: 'center',
                width: '100%',
                cls: 'pimcore_document_preview_timeslider',
                tipText: function(thumb){
                    var date = new Date(thumb.value * 1000);
                    return Ext.Date.format(date, 'H:i');
                },
                listeners: {
                    change: function(field, newValue, oldValue) {
                        this.previewTime = new Date(newValue * 1000);
                        this.loadCurrentPreview();
                    }.bind(this)
                }
            });

            this.timeSelectPanel = new Ext.Panel({
                border: false,
                region: "south",
                layout: 'border',
                hidden: true,
                scrollable: false,
                collapsible: false,
                height: 33,
                items: [
                    Ext.create('Ext.form.DateField', {
                        region: 'west',
                        cls: "pimcore_block_field_date",
                        value: this.previewTime,
                        listeners: {
                            'change': function (field, newValue, oldValue) {
                                this.updateTimeSlider(newValue);
                            }.bind(this)
                        }
                    }),
                    this.timeSlider
                ]
            });
            this.updateTimeSlider(this.previewTime);

            this.layout = new Ext.Panel({
                title: t("preview"),
                border: false,
                layout: "border",
                tbar: tbar,
                height: 200,
                iconCls: "pimcore_material_icon_devices pimcore_material_icon",
                bodyCls: "pimcore_preview_body",
                items: [this.framePanel, this.timeSelectPanel]
            });

            this.layout.on("activate", function () {
                this.refresh();
            }.bind(this));


            this.framePanel.on("resize", this.onLayoutResize.bind(this));
            this.framePanel.on("afterrender", function () {
                this.loadMask = new Ext.LoadMask({
                    target: this.layout,
                    msg: t("please_wait")
                });

                this.loadMask.enable();
            }.bind(this));
        }

        return this.layout;
    },

    setFullMode: function () {
        this.getIframe().applyStyles({
            position: "relative",
            border: "0",
            width: "100%",
            height: (this.availableHeight-7) + "px",
            top: "initial",
            left: "initial"
        });

        this.loadCurrentPreview("desktop");
    },

    setMode: function (mode) {
        var iframe = this.getIframe();
        var availableWidth = this.framePanel.getWidth()-10;
        var availableHeight = this.framePanel.getHeight()-10;

        if(availableWidth < mode["width"]) {
            Ext.MessageBox.alert(t("error"), t("screen_size_to_small"));
            return;
        }

        if(availableHeight < mode["height"]) {
            mode["height"] = availableHeight;
        }

        var top = Math.floor((availableHeight - mode["height"])/2);
        var left = Math.floor((availableWidth - mode["width"])/2);

        iframe.applyStyles({
            position: "absolute",
            border: "5px solid #323232",
            width: mode["width"] + "px",
            height: mode["height"] + "px",
            top: top + "px",
            left: left + "px"
        });

        this.mode = mode["device"];

        this.loadCurrentPreview();
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        if(this.mode == "full") {
            this.setLayoutFrameDimensions(width, height);
        }

        this.availableHeight = height;
    },

    setLayoutFrameDimensions: function (width, height) {
        this.getIframe().setStyle({
            height: (height-7) + "px"
        });
    },

    iFrameLoaded: function () {
        if(this.loadMask && this.getIframe().getAttribute("src").indexOf("pimcore_preview") > 0){
            this.loadMask.hide();
        }
    },

    getIframe: function () {
        var iframe = Ext.get(this.iframeName);
        return iframe;
    },

    getIframeWindow: function () {
        return window[this.iframeName];
    },

    getIframeDocument: function () {
        return this.getIframeWindow().document;
    },

    getIframeBody: function () {
        return Ext.get(this.getIframeDocument().getElementsByTagName("body")[0]);
    },

    showTimeSlider: function() {
        this.timeSelectPanel.show();
    },

    updateTimeSlider: function(date) {
        var startDate = date.getTime() / 1000;

        this.timeSlider.setMinValue(startDate);
        this.timeSlider.setMaxValue(startDate + 86399);
        this.timeSlider.setValue(startDate);
    },

    loadCurrentPreview: function () {

        var device = this.mode;

        var date = new Date();
        var path;

        path = this.page.data.path + this.page.data.key + "?pimcore_preview=true&time=" + date.getTime() + "&forceDeviceType=" + device + "&pimcore_override_output_timestamp=" + (this.previewTime.getTime() / 1000);

        // add target group parameter if available
        if(this.page["edit"] && this.page.edit["targetGroup"]) {
            if(this.page.edit.targetGroup && this.page.edit.targetGroup.getValue()) {
                path += "&_ptg=" + this.page.edit.targetGroup.getValue();
            }
        }

        try {
            this.getIframe().dom.src = path;
        }
        catch (e) {
            console.log(e);
        }
    },

    onClose: function () {
        try {
            window[this.iframeName].location.href = "about:blank";
            Ext.get(this.iframeName).remove();
            delete window[this.iframeName];
        } catch (e) { }
    },

    refresh: function () {
        this.loadMask.show();
        this.page.saveToSession(function () {
            if (this.preview) {
                this.preview.loadCurrentPreview();
            }
        }.bind(this.page));
    }
});
