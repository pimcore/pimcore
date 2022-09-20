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
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.document.pages.preview");
Ext.define('pimcore.document.pages.preview', {
    extend: pimcore.element.abstractPreview,

    initialize: function(element) {
        this.callParent(arguments);
        this.previewTime = new Date();
        this.previewTime.setHours(0);
        this.previewTime.setMinutes(0);
        this.previewTime.setSeconds(0);
        this.previewTime.setMilliseconds(0);
    },

    getLayout: function () {
        if (this.layout == null) {
            // preview switcher only for pages not for emails
            var tbar = [];
            if (this.element.getType() === "page") {
                tbar = this.getToolbar().concat([
                    "-",
                    {
                        text: t("qr_codes"),
                        iconCls: "pimcore_icon_qrcode",
                        handler: function () {
                            var codeUrl = Routing.generate('pimcore_admin_document_page_qrcode', {id: this.element.id});

                            var download = function () {
                                var codeUrl = Routing.generate('pimcore_admin_document_page_qrcode', {id: this.element.id, download: true});
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
                    },
                    "-",
                    {
                        text: t("open_in_new_window"),
                        iconCls: "pimcore_icon_open_window",
                        handler: function () {
                            var date = new Date();
                            var link = this.element.data.path + this.element.data.key;
                            var linkParams = [];

                            linkParams.push("pimcore_preview=true");
                            linkParams.push("_dc=" + date.getTime());

                            // add target group parameter if available
                            if (this["edit"] && this.element.edit["targetGroup"]) {
                                if(this.element.edit.targetGroup && this.element.edit.targetGroup.getValue()) {
                                    linkParams.push("_ptg=" + this.element.edit.targetGroup.getValue());
                                }
                            }

                            if (linkParams.length) {
                                link += "?" + linkParams.join("&");
                            }

                            window.open(link);
                        }.bind(this)
                    }
                ]);
            }

            this.frameId = "document_preview_iframe_" + this.element.id;

            this.framePanel = new Ext.Panel({
                border: false,
                region: "center",
                scrollable: false,
                bodyStyle: "background:#323232;",
                bodyCls: "pimcore_overflow_scrolling",
                html: '<iframe src="about:blank" frameborder="0" ' +
                    'style="width: 100%;background: #fff;" id="' + this.frameId + '" ' +
                    'name="' + this.frameId + '"></iframe>',
                listeners: {
                    afterrender: function () {
                        Ext.get(this.getIframe()).on('load', function () {
                            this.iFrameLoaded();
                        }.bind(this));
                    }.bind(this)
                }
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

    iFrameLoaded: function () {
        if(this.loadMask && this.getIframe().getAttribute("src").indexOf("pimcore_preview") > 0){
            this.loadMask.hide();
        }
    },

    getIframeWindow: function () {
        return window[this.frameId];
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

        path = this.element.data.path + this.element.data.key + "?pimcore_preview=true&time=" + date.getTime() + "&forceDeviceType=" + device + "&pimcore_override_output_timestamp=" + (this.previewTime.getTime() / 1000);

        // add target group parameter if available
        if(this.element["edit"] && this.element.edit["targetGroup"]) {
            if(this.element.edit.targetGroup && this.element.edit.targetGroup.getValue()) {
                path += "&_ptg=" + this.element.edit.targetGroup.getValue();
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
            window[this.frameId].location.href = "about:blank";
            Ext.get(this.frameId).remove();
            delete window[this.frameId];
        } catch (e) { }
    }
});
