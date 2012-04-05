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

pimcore.registerNS("pimcore.object.tags.wysiwyg");
pimcore.object.tags.wysiwyg = Class.create(pimcore.object.tags.abstract, {

    type: "wysiwyg",

    initialize: function (data, fieldConfig) {
        this.data = "";
        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;

        if (intval(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 400;
        }
        if (intval(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 300;
        }

        this.editableDivId = "object_wysiwyg_" + uniqid();
        this.previewIframeId = "object_wysiwyg_iframe_" + uniqid();
        
    },

    getGridColumnEditor: function(field) {
        var editorConfig = {};

        if (field.config) {
            if (field.config.width) {
                if (intval(field.config.width) > 10) {
                    editorConfig.width = field.config.width;
                }
            }
        }

        if(field.layout.noteditable) {
            return null;
        }
        // WYSIWYG
        if (field.type == "wysiwyg") {
            return new Ext.form.HtmlEditor({
                width: 500,
                height: 300
            });
        }
    },

    getGridColumnFilter: function(field) {
        return {type: 'string', dataIndex: field.key};
    },    

    getLayoutEdit: function () {
        this.getLayout();
        this.disableEditing = false;
        this.component.on("afterrender", this.getPreview.bind(this));
        return this.component;
    },

    getLayout: function () {
        var pConf = {
            title: this.fieldConfig.title,
            width: this.fieldConfig.width,
            html: '<div style="position:relative;" id="' + this.editableDivId + '"></div>',
            cls: "object_field"
        };

        this.component = new Ext.Panel(pConf);
    },

    getPreview: function() {

        var iframe = document.createElement("iframe");
        iframe.setAttribute("frameborder", "0");
        iframe.setAttribute("id", this.previewIframeId);
        iframe.src = "about:blank";


        iframe.onload = this.initializePreview.bind(this);

        // HACK: unfortunately iframe.onload doesn't work in IE8, so that we have to use setTimeout()
        window.setTimeout(this.initializePreview.bind(this), 2000);

        Ext.get(this.editableDivId).update("");
        Ext.get(this.editableDivId).dom.appendChild(iframe);


        // set dimensions of iframe
        if (this.fieldConfig.height) {
            Ext.get(this.previewIframeId).setStyle({
                height: this.fieldConfig.height + "px"
            });
        }
        if (this.fieldConfig.width) {
            Ext.get(this.previewIframeId).setStyle({
                width: this.fieldConfig.width + "px"
            });
        }
    },

    initializePreview: function () {
        var document = Ext.get(this.previewIframeId).dom.contentWindow.document;
        var iframeContent = this.data;
        iframeContent += '<link href="/pimcore/static/js/lib/ckeditor/contents.css" rel="stylesheet" type="text/css" />';

        document.body.innerHTML = iframeContent;
        document.body.setAttribute("style", "height: 80%; cursor: pointer;");

        if(this.disableEditing == false) {
            Ext.get(document.body).on("click", this.initCkEditor.bind(this));
        }
    },

    getLayoutShow: function () {
        this.getLayout();
        this.disableEditing = true;
        this.component.on("afterrender", this.getPreview.bind(this));
        return this.component;
    },

    initCkEditor: function () {

        var toolbar_Full = [
            ["close_object",'Cut','Copy','Paste','PasteText','PasteFromWord','-', 'SpellChecker'],
            ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
            '/',
            ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
            ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
            ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
            ['Link','Unlink','Anchor'],
            ['Image','Flash','Table','HorizontalRule','SpecialChar','PageBreak'],
            '/',
            ['Styles','Format','Font','FontSize'],
            ['TextColor','BGColor'],
            ['Maximize', 'ShowBlocks','Source']
        ];

        var eConfig = {
            uiColor: "#f2f2f2",
            toolbar: toolbar_Full,
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            resize_enabled: false
        };

        eConfig.extraPlugins = "close_object,pimcoreimage,pimcorelink";
        
        if (intval(this.fieldConfig.width) > 1) {
            eConfig.width = this.fieldConfig.width;
        }
        if (intval(this.fieldConfig.height) > 1) {
            eConfig.height = this.fieldConfig.height;
        }

        Ext.get(this.editableDivId).update(this.data);
        this.ckeditor = CKEDITOR.replace(this.editableDivId, eConfig);
        this.ckeditor.pimcore_tag_instance = this;
    },

    mask: function () {
        try {
            var pan = this.component.el;

            if (pan) {
                pan.setStyle({
                    position: "relative"
                });

                var maskEl = pan.createChild({
                    tag: "div",
                    id: Ext.id()
                })
                
                maskEl = Ext.get(maskEl.id);
                
                maskEl.addClass("pimcore_wysiwyg_mask");
                maskEl.setStyle({
                    top: 0,
                    left: 0,
                    zIndex: 10000,
                    width: pan.getWidth() + "px",
                    height: pan.getHeight() + "px",
                    position: "absolute"
                });

                this.maskEl = maskEl.dom;

                // add drop zone
                var dd = new Ext.dd.DropZone(this.maskEl, {
                    ddGroup: "element",

                    getTargetFromEvent: function(e) {
                        return this.getEl();
                    },

                    onNodeOver : function(target, dd, e, data) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    },

                    onNodeDrop : this.onNodeDrop.bind(this)
                });
            }
        }
        catch (e) {
            console.log(e);
        }
    },

    unmask: function () {
        if (this.maskEl) {
            Ext.get(this.maskEl).remove();
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if (!this.ckeditor) {
            return;
        }

        var wrappedText = data.node.attributes.text;
        var textIsSelected = false;
        
        try {
            var selection = this.ckeditor.getSelection();
            var bookmarks = selection.createBookmarks();
            var range = selection.getRanges()[ 0 ];
            var fragment = range.clone().cloneContents();

            selection.selectBookmarks(bookmarks);
            var retval = "";
            var childList = fragment.getChildren();
            var childCount = childList.count();

            for (var i = 0; i < childCount; i++) {
                var child = childList.getItem(i);
                retval += ( child.getOuterHtml ?
                        child.getOuterHtml() : child.getText() );
            }

            if (retval.length > 0) {
                wrappedText = retval;
                textIsSelected = true;
            }
        }
        catch (e) {
        }


        // remove existing links out of the wrapped text
        wrappedText = wrappedText.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, function ($0, $1) {
            if($1.toLowerCase() == "a") {
                return "";
            }
            return $0;
        });

        var id = data.node.attributes.id;
        var uri = data.node.attributes.path;
        var browserPossibleExtensions = ["jpg","jpeg","gif","png"];
        
        if (data.node.attributes.elementType == "asset") {
            if (data.node.attributes.type == "image" && textIsSelected == false) {
                // images bigger than 600px or formats which cannot be displayed by the browser directly will be converted
                // by the pimcore thumbnailing service so that they can be displayed in the editor
                var defaultWidth = 600;
                var additionalAttributes = "";
                uri = "/admin/asset/get-image-thumbnail/id/" + id + "/width/" + defaultWidth + "/aspectratio/true";

                if(typeof data.node.attributes.imageWidth != "undefined") {
                    if(data.node.attributes.imageWidth < defaultWidth && in_arrayi(pimcore.helpers.getFileExtension(data.node.attributes.text), browserPossibleExtensions)) {
                        uri = data.node.attributes.path;
                        additionalAttributes += ' pimcore_disable_thumbnail="true"';
                    }

                    if(data.node.attributes.imageWidth < defaultWidth) {
                        defaultWidth = data.node.attributes.imageWidth;
                    }
                }

                this.ckeditor.insertHtml('<img src="' + uri + '" pimcore_type="asset" pimcore_id="' + id + '" style="width:' + defaultWidth + 'px;"' + additionalAttributes + ' />');
                return true;
            }
            else {
                this.ckeditor.insertHtml('<a href="' + uri + '" pimcore_type="asset" pimcore_id="' + id + '">' + wrappedText + '</a>');
                return true;
            }
        }

        if (data.node.attributes.elementType == "document" && (data.node.attributes.type=="page" || data.node.attributes.type=="hardlink" || data.node.attributes.type=="link")){
            this.ckeditor.insertHtml('<a href="' + uri + '" pimcore_type="document" pimcore_id="' + id + '">' + wrappedText + '</a>');
            return true;
        }

    },

    getValue: function () {

        var data = this.data;
        try {
            if (this.ckeditor) {
                data = this.ckeditor.getData();
            }
        }
        catch (e) {
        }

        this.data = data;

        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        if(this.dirty) {
            return this.dirty;
        }
        
        if(this.ckeditor) {
            return this.ckeditor.checkDirty();
        }
        return false;
//        return this.ckeditor.IsDirty();
    }
});



// add close button plugin
var ckeditor_close_objectplugin_button ='close_object';
CKEDITOR.plugins.add(ckeditor_close_objectplugin_button,{
    init:function(editor){
        editor.addCommand(ckeditor_close_objectplugin_button, {
            exec:function(editor){
                window.setTimeout(function () {
                    try {
                        this.data = this.ckeditor.getData();
                        this.ckeditor.destroy();
                        this.ckeditor = null;
                        this.dirty = true;

                        this.getPreview();
                    } catch (e) {
                        console.log(e);
                    }
                }.bind(editor.pimcore_tag_instance), 10);
            }
        });
        editor.ui.addButton("close_object",{
            label:t('close'),
            icon: "/pimcore/static/img/icon/cross.png",
            command: ckeditor_close_objectplugin_button
        });
    }
});
