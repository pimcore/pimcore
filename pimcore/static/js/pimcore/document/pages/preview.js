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

pimcore.registerNS("pimcore.document.pages.preview");
pimcore.document.pages.preview = Class.create({

    initialize: function(page) {
        this.page = page;
        this.mode = "full";

        this.editorModifications = {};
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
                    {type: "tablet", name: 'Apple iPad (mini)', width: 1024, height: 768, icon: ""},
                    {type: "tablet", name: 'Google Nexus 10', width: 1280, height: 800, icon: ""},
                    {type: "tablet", name: 'Google Nexus 7', width: 960, height: 600, icon: ""},
                    {type: "mobile", name: 'Apple iPhone 3/4', width: 320, height: 480, icon: ""},
                    {type: "mobile", name: 'Apple iPhone 5 (c/s)', width: 320, height: 568, icon: ""},
                    {type: "mobile", name: 'Apple iPhone 6', width: 375, height: 667, icon: ""},
                    {type: "mobile", name: 'Apple iPhone 6 Plus', width: 414, height: 736, icon: ""},
                    {type: "mobile", name: 'LG Optimus S', width: 320, height: 480, icon: ""},
                    {type: "mobile", name: 'Google Nexus S', width: 480, height: 800, icon: ""},
                    {type: "mobile", name: 'Google Nexus 5 (five)', width: 360, height: 598, icon: ""},
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
                        text: previewModes[i]["name"] + " (" + previewModes[i]["width"] + "x"
                                                                            + previewModes[i]["height"] + ")",
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
                }, "-", {
                    text: t("qr_codes"),
                    iconCls: "pimcore_icon_qrcode",
                    handler: function () {
                        var codeUrl = "/admin/reports/qrcode/code/documentId/" + this.page.id;
                        var download = function (format) {
                            var codeUrl = "/admin/reports/qrcode/code/documentId/"
                                + this.page.id + "/renderer/" + format + "/download/true" +
                                "/moduleSize/20";
                            pimcore.helpers.download(codeUrl);
                        }

                        var qrWindow = new Ext.Window({
                            width: 280,
                            border:false,
                            title: t("qr_codes"),
                            modal: true,
                            autoScroll: true,
                            bodyStyle: "padding: 10px;",
                            items: [{
                                    html: '<img src="' + codeUrl + '" style="padding:10px; width:228px;" />',
                                    border: true,
                                    height: 250
                                }, {
                                border: false,
                                buttons: [{
                                    text: "PNG",
                                    iconCls: "pimcore_icon_png",
                                    handler: download.bind(this, "image")
                                },{
                                    text: "EPS",
                                    iconCls: "pimcore_icon_eps",
                                    handler: download.bind(this, "eps")
                                }, {
                                    text: "SVG",
                                    iconCls: "pimcore_icon_svg",
                                    handler: download.bind(this, "svg")
                                }]
                            }]
                        });

                        qrWindow.show();

                    }.bind(this)
                }];
            }

            this.iframeName = "document_preview_iframe_" + this.page.id;

            this.framePanel = new Ext.Panel({
                border: false,
                region: "center",
                bodyStyle: "-webkit-overflow-scrolling:touch; background:#323232;",
                html: '<iframe src="about:blank" width="100%" onload="' + iframeOnLoad + '" frameborder="0" id="'
                    + this.iframeName + '" name="' + this.iframeName + '"' +
                    'style="background: #fff;"></iframe>'
            });

            this.stylesField = new Ext.form.TextArea({
                style: "font-family:courier",
                value: this.page.data["css"],
                enableKeyEvents: true,
                listeners: {
                    keyup: function () {
                        this.editorClearCurrentElement();
                        this.writeCss();
                    }.bind(this),
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
                autoScroll: true,
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
                },{
                    xtype: "button",
                    text: t("clear_all_styles"),
                    iconCls: "pimcore_icon_delete",
                    handler: function () {
                        this.stylesField.setValue("");
                        this.writeCss();
                        this.editorClearCurrentElement();
                    }.bind(this)
                }],
                html: '<strong style="display: block; padding: 30px 0 0; text-align: center;">'
                                                                    + t("no_item_selected") + "</strong>"
            });

            // check if CSS-Panel should be enabled
            var cssPanelEnabled = true;
            if(!pimcore.globalmanager.get("user").isAllowed("document_style_editor")) {
                cssPanelEnabled = false;
            }
            if(!(this.page.isAllowed("save") || this.page.isAllowed("publish"))) {
                cssPanelEnabled = false;
            }
            if(Ext.isIE8) {
                cssPanelEnabled = false;
            }
            this.cssPanelEnabled = cssPanelEnabled;


            this.cssPanel = new Ext.Panel({
                border: false,
                region: "east",
                collapsible:true,
                animCollapse:false,
                collapsed: true,
                split: true,
                width: 300,
                hidden: !cssPanelEnabled,
                layout: "accordion",
                items: [this.cssEditor, this.cssSource]
            });

            this.layout = new Ext.Panel({
                title: cssPanelEnabled ? t('preview_and_styles') : t("preview"),
                border: false,
                layout: "border",
                tbar: tbar,
                autoScroll: true,
                iconCls: "pimcore_icon_tab_preview",
                items: [this.framePanel, this.cssPanel]
            });

            this.layout.on("activate", function () {
                this.refresh();
                this.editorClearCurrentElement();
            }.bind(this));
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

        if(availableWidth < mode["width"] || availableHeight < mode["height"]) {
            Ext.MessageBox.alert(t("error"), t("screen_size_to_small"));
            return;
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

        // add persona parameter if available
        if(this.page["edit"] && this.page.edit["persona"]) {
            if(this.page.edit.persona && this.page.edit.persona.getValue()) {
                path += "&_ptp=" + this.page.edit.persona.getValue();
            }
        }

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
    },

    getValues: function () {

        if (!this.layout.rendered) {
            throw "preview/styles not available";
        }

        if(this.cssPanel.hidden) {
            throw "styles not available";
        }

        var values = {
            css: this.stylesField.getValue()
        };

        return values;
    },


    // EDITOR

    editorStartSelector: function () {

        this.editorUnFrameElement();
        this.editorClearCurrentElement();

            if(!this.getIframeWindow()["pimcore"] || !this.getIframeWindow()["pimcore"]["editor"]) {
                this.getIframeWindow().pimcore = {
                    editor: this
                };
            }

            var iscope = this.getIframeWindow().pimcore;
            iscope.selectorMove = function (e) {
                if(e.target) {
                    var el = Ext.get(e.target);
                    var editor = el.dom.ownerDocument.defaultView.pimcore.editor;
                    if(editor) {
                        if(el) {
                            editor.editorFrameElement(el);
                        } else {
                            editor.editorUnFrameElement();
                        }
                    }
                }
                return false;
            };

            iscope.editorSelectElement = function (e, el, singleElement) {

                var element;
                if(typeof el == "undefined") {
                    element = e.target;
                } else {
                    element = el;
                }

                if(element) {

                    var iframeWin = element.ownerDocument.defaultView;
                    var iscopeTmp = iframeWin.pimcore;
                    var editor = iscopeTmp.editor;

                    editor.editorStopSelector();
                    editor.layout.disable();
                    editor.editorClearCurrentElement();

                    editor.cssEditor.update('<strong style="display: block; padding: 30px 0 0; text-align: center;">'
                                                                            + t("please_wait") + "...</strong>");
                    editor.editorElement = element;

                    // check for prev. activated element in positioning module, otherwise the listeners cannot be
                    // removed
                    if(iscopeTmp["positioningActiveElement"]) {
                        try {
                            var oldEl = iscopeTmp["positioningActiveElement"];
                            oldEl.setStyle("cursor", "auto");
                            oldEl.dom.removeEventListener("mousedown", iscopeTmp.positioningStart, false);
                            editor.getIframeBody().dom.removeEventListener("mousemove", iscopeTmp.positioningMove,
                                                                                                            false);
                            editor.getIframeBody().dom.removeEventListener("mouseup", iscopeTmp.positioningStop,
                                                                                                            false);
                            editor.getIframeBody().dom.removeEventListener("mouseleave", iscopeTmp.positioningStop,
                                                                                                            false);
                        } catch (e2) {
                            console.log(e2);
                        }
                    }

                    window.setTimeout(function () {
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

                        hierarchy = hierarchy.reverse().join(" ");

                        if(singleElement === true) {
                            selElement = element.dom;
                            var names = [];
                            while (selElement.parentNode) {
                                if (selElement.id && selElement.id.indexOf("ext-") < 0) {
                                    names.unshift('#' + selElement.id);
                                    break;
                                } else {
                                    if (selElement == selElement.ownerDocument.documentElement) {
                                        names.unshift(selElement.tagName);
                                    } else {
                                        for (var c = 1, e = selElement; e.previousElementSibling;e = e.previousElementSibling, c++);
                                        names.unshift(selElement.tagName + ":nth-child(" + c + ")");
                                    }
                                    selElement = selElement.parentNode;
                                }
                            }
                            hierarchy = names.join(" > ");
                        }

                        if(hierarchy.lastIndexOf("#") >= 0) {
                            hierarchy = hierarchy.substring(hierarchy.lastIndexOf("#"));
                        }

                        var itemAmount = 0;
                        try {
                            itemAmount = editor.getIframeBody().query(hierarchy).length;
                            if(singleElement !== true) {
                                if(itemAmount > 1) {
                                    var answer = window.confirm(
                                                        t("there_are_more_than_one_items_for_the_given_selector"));
                                    if(!answer) {
                                        editor.getIframeWindow().pimcore.editorSelectElement(e, element.dom, true);
                                        return;
                                    }
                                }
                            }
                        } catch (e3) {
                            console.log(e3);
                        }


                        editor.cssEditor.update("");
                        editor.cssEditor.removeAll();

                        // hierarchy panel
                        editor.cssEditor.add({
                            title: t("hierarchy"),
                            bodyStyle: "padding: 10px;",
                            autoHeight: true,
                            items: [{
                                title: hierarchy,
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
                                        editor.getIframeWindow().pimcore.editorSelectElement(null,
                                                            grid.getStore().getAt(rowIndex).get("element").dom);
                                    }.bind(editor)
                                }
                            }]
                        });

                        var stylesToCapture = ["font-family", "font-size", "font-weight", "line-height",
                            "letter-spacing", "font-style", "color", "text-decoration", "text-align", "text-transform",
                            "width", "height", "padding-top", "padding-right","padding-bottom","padding-left",
                            "margin-left", "margin-right","margin-top","margin-bottom",
                            "top","left","right","bottom","position", "opacity"];
                        var styles = element.getStyles.apply(element, stylesToCapture);

                        var getCssDimensionField = function  (name, label) {
                            if(!label) {
                                label = name;
                            }

                            var value = "";
                            var unitValue = "";
                            var tmpValue = styles[name];

                            if(tmpValue) {
                                if(tmpValue.indexOf("px") > 0 || tmpValue.indexOf("%") > 0
                                                                                        || tmpValue.indexOf("em") > 0) {
                                    value = tmpValue.replace(/(px|%|em)/i,"");
                                    unitValue = tmpValue.replace(/[0-9\.]+/,"");
                                } else if(typeof tmpValue == "number") {
                                    value = tmpValue;
                                }
                            }

                            return [{
                                xtype: "spinnerfield",
                                fieldLabel: t(label),
                                itemId: name,
                                name: name,
                                width: 50,
                                value: value
                            }, getCssUnitField(name, unitValue)];
                        };

                        var getCssUnitField = function (name, value) {
                            return {
                                name: name + "__unit",
                                itemId: name + "__unit",
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
                                value: value ? value : "px",
                                triggerAction: "all"
                            };
                        };

                        editor.cssEditor.add({
                            title: t("positioning"),
                            id: "pimcore_style_editor_position_" + editor.page.id,
                            xtype: "form",
                            bodyStyle: "padding: 10px;",
                            autoHeight: true,
                            labelWidth: 100,
                            disabled: !in_array(styles["position"], ["absolute", "fixed"]) || (itemAmount > 1),
                            items: [{
                                xtype: "compositefield",
                                items: getCssDimensionField("top")
                            },{
                                xtype: "compositefield",
                                items: getCssDimensionField("right")
                            },{
                                xtype: "compositefield",
                                items: getCssDimensionField("bottom")
                            },{
                                xtype: "compositefield",
                                items: getCssDimensionField("left")
                            }],
                            listeners: {
                                activate: function () {

                                    var editor = this;
                                    var el = Ext.get(this.editorElement);
                                    el.setStyle("cursor","pointer");

                                    var offsets, topReference, leftReference,
                                        topPosition, leftPosition, active = false, styleClearTimeout;

                                    if(!this.getIframeWindow()["pimcore"]) {
                                        this.getIframeWindow().pimcore = {};
                                    }

                                    var iscope = this.getIframeWindow().pimcore;
                                    iscope.positioningActiveElement = el;

                                    var setPosition = function () {
                                        var cont = Ext.getCmp("pimcore_style_editor_position_" + editor.page.id);

                                        var data = {
                                            top: topPosition,
                                            left: leftPosition,
                                            bottom: "",
                                            right: ""
                                        };

                                        try {
                                            Ext.each(cont.findByType("spinnerfield"), function (v) {
                                                try {
                                                    v.setValue(data[v.getName()]);
                                                } catch (e) {
                                                    console.log(e);
                                                }
                                            });
                                        } catch (e) {
                                            //editor.getIframeBody().removeAllListeners();
                                        }

                                        editor.writeCss();

                                        // remove styling attributes from element (otherwise style via styles textarea
                                        // isn't possible)
                                        styleClearTimeout = window.setTimeout(function () {
                                            var style = el.getAttribute("style");
                                            style = style.replace(/top[^;]+;/, "");
                                            style = style.replace(/left[^;]+;/, "");
                                            el.dom.setAttribute("style", style);
                                        }, 1000);
                                    };

                                    // only add the events once
                                    iscope.positioningMove = function (e, element) {

                                        el.applyStyles({
                                            bottom: "auto",
                                            right: "auto"
                                        });

                                        topPosition = e.clientY-topReference;
                                        leftPosition = e.clientX-leftReference;

                                        el.setTop(topPosition);
                                        el.setLeft(leftPosition);

                                        return false;
                                    };

                                    iscope.positioningStop = function (e) {

                                        editor.getIframeBody().dom.removeEventListener("mousemove",
                                                                                iscope.positioningMove, false);
                                        setPosition();
                                        return false;
                                    };

                                    iscope.positioningStart = function (e) {

                                        if(styleClearTimeout) {
                                            clearTimeout(styleClearTimeout);
                                        }

                                        offsets = el.getOffsetsTo(editor.getIframeBody());

                                        // this is unfortunately in jQuery => should be replaced by ExtJS but it seems
                                        // that there's not method for that
                                        var parent = jQuery(el.dom).offsetParent();
                                        parent = Ext.get(parent[0]);

                                        var offsetParent = el.getOffsetsTo(parent);
                                        topReference = offsets[1] - offsetParent[1];
                                        leftReference = offsets[0] - offsetParent[0];

                                        topReference = topReference + (e.clientY - offsets[1]);
                                        leftReference = leftReference + (e.clientX - offsets[0]);

                                        editor.getIframeBody().dom.addEventListener("mousemove",
                                                                                    iscope.positioningMove, false);

                                        e.preventDefault();
                                        return false;
                                    };


                                    editor.getIframeDocument().onselectstart = function () { return false; };
                                    el.dom.ondragstart = function() { return false; };

                                    el.dom.addEventListener("mousedown", iscope.positioningStart, false);
                                    editor.getIframeBody().dom.addEventListener("mouseup",
                                                                                    iscope.positioningStop, false);
                                    editor.getIframeBody().dom.addEventListener("mouseleave",
                                                                                    iscope.positioningStop, false);

                                }.bind(editor),
                                deactivate: function () {
                                    try {
                                        var editor = this;
                                        var el = Ext.get(this.editorElement);
                                        el.setStyle("cursor","auto");

                                        var iscope = editor.getIframeWindow().pimcore;
                                        el.dom.removeEventListener("mousedown", iscope.positioningStart, false);
                                        editor.getIframeBody().dom.removeEventListener("mousemove",
                                                                                    iscope.positioningMove, false);
                                        editor.getIframeBody().dom.removeEventListener("mouseup",
                                                                                    iscope.positioningStop, false);
                                        editor.getIframeBody().dom.removeEventListener("mouseleave",
                                                                                    iscope.positioningStop, false);
                                    } catch (e) {
                                        console.log(e);
                                    }
                                }.bind(editor)
                            }
                        });

                        editor.cssEditor.add({
                            title: t("text"),
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
                            },{
                                name: "text-align",
                                xtype: "combo",
                                width: 100,
                                fieldLabel: t("text-align"),
                                mode: "local",
                                store: [
                                    ["", "not set"],
                                    ["left", "left"],
                                    ["center", "center"],
                                    ["justify", "justify"],
                                    ["right","right"]
                                ],
                                triggerAction: "all",
                                value: styles["text-align"]
                            },{
                                name: "text-decoration",
                                xtype: "combo",
                                width: 100,
                                fieldLabel: t("text-decoration"),
                                mode: "local",
                                store: [
                                    ["", "not set"],
                                    ["none", "none"],
                                    ["underline", "underline"],
                                    ["overline", "overline"],
                                    ["blink", "blink"],
                                    ["line-through","line-through"]
                                ],
                                triggerAction: "all",
                                value: styles["text-decoration"]
                            },{
                                name: "text-transform",
                                xtype: "combo",
                                width: 100,
                                fieldLabel: t("text-transform"),
                                mode: "local",
                                store: [
                                    ["", "not set"],
                                    ["none", "none"],
                                    ["capitalize", "capitalize"],
                                    ["uppercase", "uppercase"],
                                    ["lowercase", "lowercase"]
                                ],
                                triggerAction: "all",
                                value: styles["text-transform"]
                            }]
                        });

                        editor.cssEditor.add({
                            title: t("appearance"),
                            xtype: "form",
                            bodyStyle: "padding: 10px;",
                            autoHeight: true,
                            labelWidth: 100,
                            items: [{
                                xtype: "compositefield",
                                items: getCssDimensionField("width")
                            },{
                                xtype: "compositefield",
                                items: getCssDimensionField("height")
                            },{
                                xtype: "fieldset",
                                title: t("padding"),
                                items: [{
                                    xtype: "compositefield",
                                    items: getCssDimensionField("padding-top","top")
                                },{
                                    xtype: "compositefield",
                                    items: getCssDimensionField("padding-right", "right")
                                },{
                                    xtype: "compositefield",
                                    items: getCssDimensionField("padding-bottom", "bottom")
                                },{
                                    xtype: "compositefield",
                                    items: getCssDimensionField("padding-left", "left")
                                }]
                            },{
                                xtype: "fieldset",
                                title: t("margin"),
                                items: [{
                                    xtype: "compositefield",
                                    items: getCssDimensionField("margin-top", "top")
                                },{
                                    xtype: "compositefield",
                                    items: getCssDimensionField("margin-right", "right")
                                },{
                                    xtype: "compositefield",
                                    items: getCssDimensionField("margin-bottom", "bottom")
                                },{
                                    xtype: "compositefield",
                                    items: getCssDimensionField("margin-left", "left")
                                }]
                            }/*,{
                                xtype: "sliderfield",
                                name: "opacity",
                                fieldLabel: t("opacity"),
                                value: styles["opacity"],
                                minValue: 0,
                                maxValue: 1,
                                increment: 0.05,
                                decimalPrecision: 2
                            }*/]
                        });


                        editor.cssEditor.doLayout();

                        // check for modifications
                        editor.editorUpdateInterval = window.setInterval(function () {
                            var css = "";
                            var cssData = {};

                            Ext.each(this.cssEditor.findByType("form"), function (v) {
                                try {
                                    Ext.apply(cssData, v.getForm().getFieldValues());
                                } catch (e) {
                                    console.log(e);
                                }
                            });

                            var init  = false;
                            if(typeof this.editorModifications[hierarchy] == "undefined") {
                                this.editorModifications[hierarchy] = {
                                    initial: cssData
                                };
                                init = true;
                            }

                            // extract existing css for the current element, so that it can be added later again
                            // if not modified
                            var cssContent = this.stylesField.getValue();
                            if(!cssContent) {
                                cssContent = "";
                            }
                            var existingCss = cssContent.match(new RegExp(preg_quote(hierarchy) + ".*\\{([^\\}]+)\\}"));
                            if(!existingCss) {
                                existingCss = ["",""];
                            }
                            existingCss = existingCss[1].replace(/(\r\n|\n|\r)/gm, "");
                            existingCss = existingCss.split(";");

                            var existingCssStore = {};
                            var u;
                            for(var i=0; i<existingCss.length; i++) {
                                existingCss[i] = trim(existingCss[i]);
                                if(existingCss[i]) {
                                    u = existingCss[i].split(":");
                                    if(trim(u[0]) && trim(u[1])) {
                                        existingCssStore[trim(u[0])] = existingCss[i];
                                    }
                                }
                            }

                            // generate css
                            Ext.iterate(cssData, function (key, value) {
                                if( value !== this.editorModifications[hierarchy]["initial"][key]
                                                                    && key.indexOf("__unit") < 0 && key != "css") {

                                    value = String(value);

                                    this.editorModifications[hierarchy]["initial"][key] = value;

                                    if(value.length < 1) {
                                        return;
                                    }

                                    // remove from existing styles
                                    if(existingCssStore[key]) {
                                        delete existingCssStore[key];
                                    }

                                    // check if this field has an unit
                                    if(typeof cssData[key + "__unit"] != "undefined") {
                                        value += cssData[key + "__unit"];
                                    }
                                    css += key + ": " + value + ";\n";
                                }
                            }.bind(this));


                            // add existing css again
                            Ext.iterate(existingCssStore, function (key, value) {
                                css += value + ";\n";
                            });

                            // put together
                            if(css) {
                                css = hierarchy + " {\n" + css + "}";
                            }


                            // check if it is the initial
                            if(init) {
                                this.editorModifications[hierarchy] = {
                                    initial: cssData,
                                    css: css
                                };
                            } else {
                                if(css != this.editorModifications[hierarchy]["css"]) {
                                    this.editorUnFrameElement();
                                    this.editorModifications[hierarchy]["css"] = css;

                                    // remove existing rules for current element -> existing not modified styles
                                    // were added already above
                                    cssContent = cssContent.replace(new RegExp(preg_quote(hierarchy)
                                                                                                  + "[^\\}]*\\}"), "");
                                    cssContent = cssContent.replace(/^\s*$[\n\r]{1,}/gm, '');
                                    cssContent = cssContent.replace(/\}/gm, "}\n");

                                    cssContent += css;
                                    this.stylesField.setValue(cssContent);
                                    this.writeCss();
                                }
                            }
                        }.bind(editor), 200);

                        editor.layout.enable();

                    }, 100);
                }
                return false;
            };

            Ext.each(this.getIframeBody().query("a"), function (el) {
                el.setAttribute("href", "#");
            });


        var iscope = this.getIframeWindow().pimcore;

        this.editorActiveFrame = {
            active: false,
            topEl: null,
            bottomEl: null,
            rightEl: null,
            leftEl: null,
            timeout: null
        };

        this.getIframeBody().dom.addEventListener("click", iscope.editorSelectElement, false);
        this.getIframeBody().dom.addEventListener("mousemove", iscope.selectorMove, false);
    },

    editorStopSelector: function () {

        var iscope = this.getIframeWindow().pimcore;
        this.getIframeBody().dom.removeEventListener("click", iscope.editorSelectElement, false);
        this.getIframeBody().dom.removeEventListener("mousemove", iscope.selectorMove, false);
        this.editorUnFrameElement();

        Ext.getCmp("pimcore_style_selector_" + this.page.id).toggle(false);
    },

    editorGetCssSelectorPart: function (el, allowTag) {

        el = Ext.get(el);

        var css = "";

        if(el.dom.tagName.toLowerCase() == "body") {
            return "body";
        }

        if(el.getAttribute("id") && el.getAttribute("id").indexOf("ext-") < 0) {
            css += "#" + el.getAttribute("id");
        } else if(el.getAttribute("class")) {
            css += "." + el.getAttribute("class").replace(/ /g,".");
            css = css.replace("..", ".");
        }

        if(!css) {
            css += el.dom.tagName.toLowerCase();
        }

        return css;
    },

    editorClearCurrentElement: function () {
        if(!this.cssPanelEnabled) {
            return;
        }

        this.editorElement = null;
        if(this.editorUpdateInterval) {
            clearInterval(this.editorUpdateInterval);
        }

        this.cssEditor.removeAll();
        this.cssEditor.update('<strong style="display: block; padding: 30px 0 0; text-align: center;">'
                                                            + t("no_item_selected") + "</strong>");
        this.cssEditor.doLayout();
    },

    editorFrameElement: function (el, body) {

        if(this.editorActiveFrame.active) {
            this.editorUnFrameElement();
        }

        var startDistance;
        var offsets;
        var bodyOffsetLeft;
        var bodyOffsetTop;
        var width;
        var height;
        var borderWidth;

        try {
            startDistance = 5;
            offsets = Ext.get(el).getOffsetsTo(this.getIframeBody());
            bodyOffsetLeft = intval(this.getIframeBody().getStyle("margin-left"));
            bodyOffsetTop = intval(this.getIframeBody().getStyle("margin-top"));

            offsets[0] -= bodyOffsetLeft;
            offsets[1] -= bodyOffsetTop;

            offsets[0] -= startDistance;
            offsets[1] -= startDistance;

            width = Ext.get(el).getWidth() + (startDistance*2);
            height = Ext.get(el).getHeight() + (startDistance*2);
            borderWidth = 1;

            if(typeof body == "undefined") {
                body = this.getIframeBody().dom;
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

        if(this.editorActiveFrame && this.editorActiveFrame.active) {
            this.editorActiveFrame.topEl.remove();
            this.editorActiveFrame.bottomEl.remove();
            this.editorActiveFrame.leftEl.remove();
            this.editorActiveFrame.rightEl.remove();
            this.editorActiveFrame.infoEl.remove();

            this.editorActiveFrame.active = false;
        }
    }

});