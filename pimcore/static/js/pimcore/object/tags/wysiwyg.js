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

    initialize: function (data, layoutConf) {
        this.data = "";
        if (data) {
            this.data = data;
        }
        this.layoutConf = layoutConf;
    },

    getLayoutEdit: function () {

        if (parseInt(this.layoutConf.width) < 1) {
            this.layoutConf.width = 400;
        }
        if (parseInt(this.layoutConf.height) < 1) {
            this.layoutConf.height = 300;
        }

        this.editableDivId = "object_wysiwyg_" + uniqid();

        var pConf = {
            title: this.layoutConf.title,
            width: this.layoutConf.width,
            html: '<div style="cursor:pointer;" id="' + this.editableDivId + '">' + this.data + '</div>',
            cls: "object_field"
        };

        this.layout = new Ext.Panel(pConf);

        this.layout.on("afterrender", function () {
            Ext.get(this.editableDivId).on("click", this.initCkEditor.bind(this));


            if (this.layoutConf.height) {
                Ext.get(this.editableDivId).setStyle({
                    height: this.layoutConf.height + "px"
                });
            }
            if (this.layoutConf.width) {
                Ext.get(this.editableDivId).setStyle({
                    width: this.layoutConf.width + "px"
                });
            }

            if (Ext.get(this.editableDivId).getHeight() < 10) {
                Ext.get(this.editableDivId).setStyle({
                    minHeight: "300px"
                });
            }
        }.bind(this));

        return this.layout;
    },


    getLayoutShow: function () {

        if (parseInt(this.layoutConf.width) < 1) {
            this.layoutConf.width = 400;
        }
        if (parseInt(this.layoutConf.height) < 1) {
            this.layoutConf.height = 300;
        }

        this.editableDivId = "object_wysiwyg_" + uniqid();

        var pConf = {
            title: this.layoutConf.title,
            width: this.layoutConf.width,
            html: '<div id="' + this.editableDivId + '">' + this.data + '</div>',
            cls: "object_field"
        };
        this.layout = new Ext.Panel(pConf);

        return this.layout;
    },

    initCkEditor: function () {

        var toolbar_Full =
                [
                    ['Cut','Copy','Paste','PasteText','PasteFromWord','-', 'SpellChecker', 'Scayt'],
                    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
                    ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
                    '/',
                    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
                    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
                    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
                    ['Link','Unlink','Anchor'],
                    ['Image','Flash','Table','HorizontalRule','SpecialChar','PageBreak'],
                    '/',
                    ['Styles','Format','Font','FontSize'],
                    ['TextColor','BGColor'],
                    ['Maximize', 'ShowBlocks','Source',"DestroyPimcore"]
                ];

        var eConfig = {
            uiColor: "#f2f2f2",
            toolbar: toolbar_Full,
            width: this.layoutConf.width,
            height: this.layoutConf.height,
            resize_enabled: false
        };

        if (parseInt(this.layoutConf.width) > 1) {
            eConfig.width = this.layoutConf.width;
        }
        if (parseInt(this.layoutConf.height) > 1) {
            eConfig.height = this.layoutConf.height;
        }

        this.ckeditor = CKEDITOR.replace(Ext.get(this.editableDivId).dom, eConfig);
    },

    mask: function () {
        try {
            var pan = this.layout.el;

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

        var id = data.node.attributes.id;
        var uri = data.node.attributes.path;
        
        if (data.node.attributes.elementType == "asset") {
            if (data.node.attributes.type == "image" && textIsSelected == false) {
                this.ckeditor.insertHtml('<img src="' + uri + '" pimcore_type="asset" pimcore_id="' + id + '" />');
                return true;
            }
            else {
                this.ckeditor.insertHtml('<a href="' + uri + '" pimcore_type="asset" pimcore_id="' + id + '">' + wrappedText + '</a>');
                return true;
            }
        }


        if (data.node.attributes.type == "page") {
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
        return this.layoutConf.name;
    },

    isDirty: function() {
        if(this.ckeditor) {
            return this.ckeditor.checkDirty();
        }
        return false;
//        return this.ckeditor.IsDirty();
    }
});