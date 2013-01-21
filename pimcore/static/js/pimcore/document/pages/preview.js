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

pimcore.registerNS("pimcore.document.pages.preview");
pimcore.document.pages.preview = Class.create({

    initialize: function(page) {
        this.page = page;
        this.mode = "full";
    },


    getLayout: function () {

        if (this.layout == null) {

            var iframeOnLoad = "pimcore.globalmanager.get('document_" + this.page.id + "').preview.iFrameLoaded()";

            // preview switcher only for pages not for emails
            var tbar = [];
            if(this.page.getType() == "page" && !Ext.isIE8) {

                var previewModes = [
                    {type: "desktop", name: '10" Netbook', width: 1024, height: 600, icon: ""},
                    {type: "desktop", name: '12" Netbook', width: 1024, height: 768, icon: ""},
                    {type: "desktop", name: '13" Netbook', width: 1280, height: 800, icon: ""},
                    {type: "desktop", name: '15" Netbook', width: 1366, height: 768, icon: ""},
                    {type: "desktop", name: '19" Desktop', width: 1440, height: 900, icon: ""},
                    {type: "desktop", name: '20" Desktop', width: 1600, height: 900, icon: ""},
                    {type: "desktop", name: '22" Desktop', width: 1680, height: 1050, icon: ""},
                    {type: "desktop", name: '23" Desktop', width: 1920, height: 1080, icon: ""},
                    {type: "desktop", name: '24" Desktop', width: 1920, height: 1200, icon: ""},
                    {type: "tablet", name: 'Velocity Cruz', width: 800, height: 600, icon: ""},
                    {type: "tablet", name: 'Samsung Galaxy', width: 1024, height: 600, icon: ""},
                    {type: "tablet", name: 'Apple iPad 1&2', width: 1024, height: 768, icon: ""},
                    {type: "tablet", name: 'Motorola Xoom', width: 1280, height: 800, icon: ""},
                    {type: "mobile", name: 'Apple iPhone 3/4', width: 320, height: 480, icon: ""},
                    {type: "mobile", name: 'LG Optimus S', width: 320, height: 480, icon: ""},
                    {type: "mobile", name: 'Google Nexus S', width: 480, height: 800, icon: ""},
                    {type: "tv", name: '480p TV', width: 640, height: 480, icon: ""},
                    {type: "tv", name: '720p TV', width: 1280, height: 720, icon: ""},
                    {type: "tv", name: '1080p TV', width: 1920, height: 1080, icon: ""}
                ];

                var menues = {
                    desktop: [],
                    tablet: [],
                    mobile: [],
                    tv: []
                };

                for(var i=0; i<previewModes.length; i++) {
                    menues[previewModes[i]["type"]].push({
                        text: previewModes[i]["name"] + " (" + previewModes[i]["width"] + "x" + previewModes[i]["height"] + ")",
                        handler: this.setMode.bind(this, previewModes[i])
                    });
                }

                tbar = [{
                    text: "Desktop",
                    iconCls: "pimcore_icon_desktop",
                    menu: menues["desktop"]
                }, {
                    text: "Tablet",
                    iconCls: "pimcore_icon_tablet",
                    menu: menues["tablet"]
                }, {
                    text: "Mobile",
                    iconCls: "pimcore_icon_mobile",
                    menu: menues["mobile"]
                }, {
                    text: "Smart TV",
                    iconCls: "pimcore_icon_tv",
                    menu: menues["tv"]
                }];
            }

            this.iframeName = "document_preview_iframe_" + this.page.id;

            this.framePanel = new Ext.Panel({
                border: false,
                region: "center",
                bodyStyle: "-webkit-overflow-scrolling:touch; background:#323232;",
                html: '<iframe src="about:blank" width="100%" onload="' + iframeOnLoad + '" frameborder="0" id="' + this.iframeName + '" name="' + this.iframeName + '"></iframe>'
            });

            this.stylesField = new Ext.form.TextArea({
                style: "font-family:courier",
                value: this.page.data["css"],
                enableKeyEvents: true,
                listeners: {
                    keyup: this.writeCss.bind(this),
                    change: this.writeCss.bind(this)
                }
            });

            this.cssSource = new Ext.Panel({
                title: t("source"),
                layout: "fit",
                items: [this.stylesField]
            });

            this.cssEditor = new Ext.Panel({
                title: t("style_editor"),
                bodyStyle: "padding: 10px",
                layout: "accordion",
                tbar: [{
                    xtype: "button",
                    text: t("select_element"),
                    iconCls: "pimcore_icon_cursor",
                    id: "pimcore_style_selector_" + this.page.id,
                    enableToggle: true,
                    handler: function (btn) {
                        if(btn.pressed) {
                            this.editorStartSelector();
                        } else {
                            this.editorStopSelector();
                        }
                    }.bind(this)
                }],
                html: '<strong style="display: block; padding: 30px 0 0; text-align: center;">' + t("no_item_selected") + "</strong>"
            });

            this.cssPanel = new Ext.Panel({
                border: false,
                region: "east",
                collapsible:true,
                animCollapse:false,
                collapsed: true,
                split: true,
                width: 300,
                layout: "accordion",
                items: [this.cssEditor, this.cssSource]
            });

            this.layout = new Ext.Panel({
                title: t('preview_and_styles'),
                border: false,
                layout: "border",
                tbar: tbar,
                autoScroll: true,
                iconCls: "pimcore_icon_tab_preview",
                items: [this.framePanel, this.cssPanel]
            });

            this.layout.on("activate", this.refresh.bind(this));
            this.layout.on("destroy", function () {
                if(this.editorUpdateInterval) {
                    clearInterval(this.editorUpdateInterval);
                }
            }.bind(this));
            this.framePanel.on("resize", this.onLayoutResize.bind(this));
            this.framePanel.on("afterrender", function () {
                this.loadMask = new Ext.LoadMask(this.layout.getEl(), {msg: t("please_wait")});
                this.loadMask.enable();
            }.bind(this));
        }

        return this.layout;
    },

    setMode: function (mode) {
        var iframe = this.getIframe();
        var availableWidth = this.framePanel.getWidth()-50;
        var availableHeight = this.framePanel.getHeight()-50;
        var positioningHeight = mode["height"];
        var positioningWidth = mode["width"];

        zoom = 1;

        if(mode["width"] > availableWidth || mode["height"] > availableHeight) {
            if(mode["height"] > availableHeight) {
                zoom = availableHeight / mode["height"];
            } else {
                zoom = availableWidth / mode["width"];
            }

            zoom = zoom-0.1;

            positioningHeight = Math.floor(mode["height"] * zoom);
            positioningWidth = Math.floor(mode["width"] * zoom);
        }

        var top = Math.floor((availableHeight - positioningHeight)/2);
        var left = Math.floor((availableWidth - positioningWidth)/2);

        iframe.applyStyles({
            position: "absolute",
            "transform-origin": "0 0",
            border: "5px solid #323232",
            transform: "scale(" + zoom + ")",
            zoom: zoom,
            width: mode["width"] + "px",
            height: mode["height"] + "px",
            top: top + "px",
            left: left + "px"
        });
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        if(this.mode == "full") {
            this.setLayoutFrameDimensions(width, height);
        }
    },

    setLayoutFrameDimensions: function (width, height) {
        this.getIframe().setStyle({
            height: (height-2) + "px"
        });
    },

    iFrameLoaded: function () {
        if(this.loadMask && this.getIframe().getAttribute("src").indexOf("pimcore_preview") > 0){
            this.loadMask.hide();
            this.writeCss();
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


    loadCurrentPreview: function () {
        var date = new Date();
        var path;

        path = this.page.data.path + this.page.data.key + "?pimcore_preview=true&time=" + date.getTime();

        try {
            this.getIframe().dom.src = path;
        }
        catch (e) {
            console.log(e);
        }
    },

    writeCss: function () {
        var style = null;
        var frameDoc = this.getIframeDocument();
        if(!frameDoc.getElementById("pimcore_styles")) {
            style = frameDoc.createElement("style");
            style.type = "text/css";
            style.id = "pimcore_styles";

            frameDoc.body.appendChild(style);
        } else {
            style = frameDoc.getElementById("pimcore_styles");
        }

        try {
            // IE compatibility
            style.styleSheet.cssText = this.stylesField.getValue();
        } catch (e) {
            style.innerHTML = this.stylesField.getValue();
        }
    },

    refresh: function () {
        this.loadMask.show();
        this.page.saveToSession(function () {
            if (this.preview) {
                this.preview.loadCurrentPreview();
            }
        }.bind(this.page));
    },

    getValues: function () {

        if (!this.layout.rendered) {
            throw "preview/styles not available";
        }

        var values = {
            css: this.stylesField.getValue()
        };


        return values;
    },



    // EDITOR

    editorStartSelector: function () {

        this.editorClearCurrentElement();

        Ext.each(this.getIframeBody().query("a"), function (el) {
            el.setAttribute("href", "#");
        });

        this.editorActiveFrame = {
            active: false,
            topEl: null,
            bottomEl: null,
            rightEl: null,
            leftEl: null,
            timeout: null
        };

        this.getIframeDocument().styleEditor = this;
        this.getIframeBody().on("mousemove", this.editorMouseHighlightElement);
        this.getIframeBody().on("click", this.editorSelectElement);
        //Ext.getBody().on("contextmenu", contextMenu);
        //Ext.getBody().on("click", selectElement);
    },

    editorStopSelector: function () {
        this.getIframeBody().un("mousemove", this.editorMouseHighlightElement);
        this.getIframeBody().un("click", this.editorSelectElement);
        this.editorUnFrameElement();

        Ext.getCmp("pimcore_style_selector_" + this.page.id).toggle(false);
    },

    editorMouseHighlightElement: function (e) {
        if(e.target) {
            var el = Ext.get(e.target);
            var editor = el.dom.ownerDocument.styleEditor;
            if(editor) {
                if(el) {
                    editor.editorFrameElement(el);
                } else {
                    editor.editorUnFrameElement();
                }
            }
        }
    },

    editorGetCssSelectorPart: function (el, allowTag) {

        el = Ext.get(el);

        var css = "";

        if(el.dom.tagName.toLowerCase() == "body") {
            return "body";
        }

        if(el.getAttribute("id") && el.getAttribute("id").indexOf("ext-") < 0) {
            css += "#" + el.getAttribute("id");
        }

        if(el.getAttribute("class")) {
            css += "." + el.getAttribute("class").replace(" ", ".");
        }

        if(!css) {
            css += el.dom.tagName.toLowerCase();
        }

        return css;
    },

    editorClearCurrentElement: function () {
        this.editorElement = null;
        if(this.editorUpdateInterval) {
            clearInterval(this.editorUpdateInterval);
        }

        this.cssEditor.removeAll();
        this.cssEditor.update('<strong style="display: block; padding: 30px 0 0; text-align: center;">' + t("no_item_selected") + "</strong>");
        this.cssEditor.doLayout();

    },

    editorSelectElement: function (e, el) {

        var element;

        if(typeof el == "undefined") {
            element = e.getTarget();
        } else {
            element = el;
        }

        if(element) {

            var editor = element.ownerDocument.styleEditor;
            editor.editorStopSelector();
            editor.editorElement = element;

            var parent;
            var parentElements = [];
            var hierarchy = [];
            var selectorFound = false;
            element = Ext.get(element);
            var cssPath = editor.editorGetCssSelectorPart(element, true);

            parentElements.push([cssPath, element]);
            hierarchy.push(cssPath);

            if(cssPath.indexOf("#") < 0) {
                selectorFound = true;
            }

            var selElement = element;
            while (parent = selElement.parent()) {

                //console.log(parent);

                if(!selectorFound) {
                    cssPath = editor.editorGetCssSelectorPart(parent, false) + " " + cssPath;
                }

                parentElements.push([editor.editorGetCssSelectorPart(parent, true), parent]);
                hierarchy.push(editor.editorGetCssSelectorPart(parent, true));

                if(parent.getAttribute("id") && parent.getAttribute("id").indexOf("ext-") < 0) {
                    selectorFound = true;
                }

                selElement = parent;

                if(parent.dom.tagName.toLowerCase() == "body") {
                    break;
                }
            }


            editor.cssEditor.update("");
            editor.cssEditor.removeAll();

            // hierarchy panel
            editor.cssEditor.add({
                title: t("hierarchy"),
                bodyStyle: "padding: 10px;",
                autoHeight: true,
                items: [{
                    title: hierarchy.reverse().join(" "),
                    xtype: "grid",
                    store: new Ext.data.ArrayStore({
                        fields: ['selector',"element"],
                        data: parentElements
                    }),
                    columns: [{
                        dataIndex: "selector",
                        width: 220
                    }],
                    width: 225,
                    autoHeight: true,
                    sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                    listeners: {
                        "rowclick": function (grid, rowIndex, e) {
                            this.editorFrameElement(grid.getStore().getAt(rowIndex).get("element").dom);
                            var rec = grid.getSelectionModel().getSelected();
                            if (!rec) {
                                this.editorUnFrameElement();
                            }
                        }.bind(editor),
                        "rowdblclick": function (grid, rowIndex, e) {
                            //editElement = null;
                            this.editorSelectElement(null, grid.getStore().getAt(rowIndex).get("element").dom);
                        }.bind(editor)
                    }
                }]
            });

            var stylesToCapture = ["font-family", "font-size", "font-weight", "line-height", "letter-spacing", "font-style", "color"];
            var styles = element.getStyles.apply(element, stylesToCapture);

            var getCssDimensionField = function  (name, label) {
                if(!label) {
                    label = name;
                }

                var value = "";
                var unitValue = "";
                var tmpValue = styles[name];

                if(tmpValue) {
                    if(tmpValue.indexOf("px") > 0 || tmpValue.indexOf("%") > 0 || tmpValue.indexOf("em") > 0) {
                        value = tmpValue.replace(/(px|%|em)/i,"");
                        unitValue = tmpValue.replace(/[0-9\.]+/,"");
                    } else if(typeof tmpValue == "number") {
                        value = tmpValue;
                    }
                }

                return [{
                    xtype: "spinnerfield",
                    fieldLabel: t(label),
                    name: name,
                    width: 50,
                    value: value
                }, getCssUnitField(name, unitValue)];
            };

            var getCssUnitField = function (name, value) {
                return {
                    name: name + "__unit",
                    xtype: "combo",
                    width: 50,
                    hideLabel: true,
                    mode: "local",
                    ctCls: "pimcore_css_unit_field",
                    store: [
                        ["px", "px"],
                        ["em", "em"],
                        ["%","%"]
                    ],
                    value: value,
                    triggerAction: "all"
                };
            };

            editor.cssEditor.add({
                title: t("font"),
                xtype: "form",
                bodyStyle: "padding: 10px;",
                autoHeight: true,
                labelWidth: 100,
                items: [{
                    name: "font-family",
                    xtype: "combo",
                    width: 100,
                    fieldLabel: t("font-family"),
                    mode: "local",
                    store: [
                        ["", "not set"],
                        ["Arial", "Arial"],
                        ["Lucida Sans", "Lucida Sans"],
                        ["Times New Roman","Times New Roman"],
                        ["Sans Serif","Sans Serif"],
                        ["Verdana","Verdana"]
                    ],
                    triggerAction: "all",
                    value: styles["font-family"]
                },{
                    xtype: "compositefield",
                    items: getCssDimensionField("font-size", "size")
                },{
                    name: "font-weight",
                    xtype: "combo",
                    width: 100,
                    fieldLabel: t("font-weight"),
                    mode: "local",
                    store: [
                        ["", "not set"],
                        ["normal", "normal"],
                        ["bold", "bold"],
                        ["bolder","bolder"],
                        ["lighter","lighter"]
                    ],
                    triggerAction: "all",
                    value: styles["font-weight"]
                },{
                    xtype: "compositefield",
                    items: getCssDimensionField("line-height")
                },{
                    xtype: "compositefield",
                    items: getCssDimensionField("letter-spacing")
                },{
                    name: "font-style",
                    xtype: "combo",
                    width: 100,
                    fieldLabel: t("font-style"),
                    mode: "local",
                    store: [
                        ["", "not set"],
                        ["normal", "normal"],
                        ["italic", "italic"],
                        ["oblique","oblique"]
                    ],
                    triggerAction: "all",
                    value: styles["font-style"]
                }, {
                    fieldLabel: t("color"),
                    xtype: "textfield",
                    name: "color",
                    width: 100,
                    value: styles["color"]
                }]
            });


            editor.cssEditor.doLayout();

            // check for modifications
            editor.editorUpdateInterval = window.setInterval(function () {

            }, 1000);
        }

    },

    editorFrameElement: function (el, body) {

        if(this.editorActiveFrame.active) {
            this.editorUnFrameElement();
        }

        try {
            var startDistance = 5;
            var offsets = Ext.get(el).getOffsetsTo(this.getIframeBody());
            var bodyOffsetLeft = intval(this.getIframeBody().getStyle("margin-left"));
            var bodyOffsetTop = intval(this.getIframeBody().getStyle("margin-top"));

            offsets[0] -= bodyOffsetLeft;
            offsets[1] -= bodyOffsetTop;

            offsets[0] -= startDistance;
            offsets[1] -= startDistance;

            var width = Ext.get(el).getWidth() + (startDistance*2);
            var height = Ext.get(el).getHeight() + (startDistance*2);
            var borderWidth = 1;

            if(typeof body == "undefined") {
                var body = this.getIframeBody().dom;
            }
        } catch (e) {
            return;
        }

        var top = this.getIframeDocument().createElement("div");
        top = Ext.get(top);
        top.appendTo(body);
        top.applyStyles({
            position: "absolute",
            top: (offsets[1] - borderWidth) + "px",
            left: (offsets[0] - borderWidth) + "px",
            width: (width + borderWidth*2) + "px",
            height: borderWidth + "px",
            backgroundColor: "#a3bae9",
            zIndex: 10000
        });

        var bottom = this.getIframeDocument().createElement("div");
        bottom = Ext.get(bottom);
        bottom.appendTo(body);
        bottom.applyStyles({
            position: "absolute",
            top: (offsets[1] + borderWidth + height) + "px",
            left: (offsets[0] - borderWidth) + "px",
            width: (width + borderWidth*2) + "px",
            height: borderWidth + "px",
            backgroundColor: "#a3bae9",
            zIndex: 10000
        });

        var left = this.getIframeDocument().createElement("div");
        left = Ext.get(left);
        left.appendTo(body);
        left.applyStyles({
            position: "absolute",
            top: (offsets[1] - borderWidth) + "px",
            left: (offsets[0] - borderWidth) + "px",
            width: borderWidth + "px",
            height: (height + borderWidth*2) + "px",
            backgroundColor: "#a3bae9",
            zIndex: 10000
        });

        var right = this.getIframeDocument().createElement("div");
        right = Ext.get(right);
        right.appendTo(body);
        right.applyStyles({
            position: "absolute",
            top: (offsets[1] - borderWidth) + "px",
            left: (offsets[0] + width ) + "px",
            width: borderWidth + "px",
            height: (height + borderWidth*2) + "px",
            backgroundColor: "#a3bae9",
            zIndex: 10000
        });


        var info = this.getIframeDocument().createElement("div");
        info = Ext.get(info);
        info.appendTo(body);
        info.update(this.editorGetCssSelectorPart(el));

        info.applyStyles({
            position: "absolute",
            top: (offsets[1] - borderWidth - 19) + "px",
            left: (offsets[0] - borderWidth) + "px",
            height: "15px",
            padding: "2px 5px 2px 5px",
            fontSize: "10px",
            backgroundColor: "#a3bae9",
            zIndex: 10000
        });

        this.editorActiveFrame = {
            active: true,
            topEl: top,
            bottomEl: bottom,
            rightEl: right,
            leftEl: left,
            infoEl: info
        };
    },

    editorUnFrameElement: function () {

        if(this.editorActiveFrame.active) {
            this.editorActiveFrame.topEl.remove();
            this.editorActiveFrame.bottomEl.remove();
            this.editorActiveFrame.leftEl.remove();
            this.editorActiveFrame.rightEl.remove();
            this.editorActiveFrame.infoEl.remove();

            this.editorActiveFrame.active = false;
        }
    }

});