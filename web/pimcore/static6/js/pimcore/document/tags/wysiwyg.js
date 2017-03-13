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

/*global CKEDITOR*/
pimcore.registerNS("pimcore.document.tags.wysiwyg");
pimcore.document.tags.wysiwyg = Class.create(pimcore.document.tag, {

    type: "wysiwyg",

    initialize: function(id, name, options, data, inherited) {

        this.id = id;
        this.name = name;
        this.setupWrapper();
        options = this.parseOptions(options);

        if (!data) {
            data = "";
        }
        this.data = data;
        this.options = options;


        var textareaId = id + "_textarea";
        this.textarea = document.createElement("div");
        this.textarea.setAttribute("contenteditable","true");

        Ext.get(id).appendChild(this.textarea);

        Ext.get(id).insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');

        this.textarea.id = textareaId;
        this.textarea.innerHTML = data;

        var textareaHeight = 100;
        if (options.height) {
            textareaHeight = options.height;
        }
        if (options.placeholder) {
            this.textarea.setAttribute('data-placeholder', options["placeholder"]);
        }

        var inactiveContainerWidth = options.width + "px";
        if (typeof options.width == "string" && options.width.indexOf("%") >= 0) {
            inactiveContainerWidth = options.width;
        }

        Ext.get(this.textarea).addCls("pimcore_wysiwyg");
        Ext.get(this.textarea).applyStyles("width: " + inactiveContainerWidth  + "; min-height: " + textareaHeight
                                                                                                + "px;");

        // register at global DnD manager
        if (typeof dndManager !== 'undefined') {
            dndManager.addDropTarget(Ext.get(id), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));
        }

        this.startCKeditor();

        this.checkValue();
    },

    startCKeditor: function () {
        
        try {
            CKEDITOR.config.language = pimcore.globalmanager.get("user").language;
            var eConfig = Object.clone(this.options);

            // if there is no toolbar defined use Full which is defined in CKEDITOR.config.toolbar_Full, possible
            // is also Basic
            if (!this.options["toolbarGroups"]) {
                eConfig.toolbarGroups = [
                    { name: 'clipboard', groups: [ "sourcedialog", 'clipboard', 'undo', "find" ] },
                    { name: 'basicstyles', groups: [ 'basicstyles', 'list'] },
                    '/',
                    { name: 'paragraph', groups: [ 'align', 'indent'] },
                    { name: 'blocks' },
                    { name: 'links' },
                    { name: 'insert' },
                    "/",
                    { name: 'styles' },
                    { name: 'tools', groups: ['colors', "tools", 'cleanup', 'mode', "others"] }
                ];
            }

            delete eConfig.width;

            eConfig.language = pimcore.settings["language"];
            eConfig.entities = false;
            eConfig.entities_greek = false;
            eConfig.entities_latin = false;
            eConfig.extraAllowedContent = "*[pimcore_type,pimcore_id]";

            if(typeof(pimcore.document.tags.wysiwyg.defaultEditorConfig) == 'object'){
                eConfig = mergeObject(pimcore.document.tags.wysiwyg.defaultEditorConfig,eConfig);
            }

            this.ckeditor = CKEDITOR.inline(this.textarea, eConfig);

            this.ckeditor.on('change', this.checkValue.bind(this));

                // disable URL field in image dialog
            this.ckeditor.on("dialogShow", function (e) {
                var urlField = e.data.getElement().findOne("input");
                if(urlField && urlField.getValue()) {
                    if(urlField.getValue().indexOf("/image-thumbnails/") > 1) {
                        urlField.getParent().getParent().getParent().hide();
                    }
                } else if (urlField) {
                    urlField.getParent().getParent().getParent().show();
                }
            });
        }
        catch (e) {
            console.log(e);
        }
    },

    onNodeDrop: function (target, dd, e, data) {
        var record = data.records[0];
        data = record.data;

        if (!this.ckeditor ||!this.dndAllowed(data)) {
            return;
        }

        // we have to foxus the editor otherwise an error is thrown in the case the editor wasn't opend before a drop element
        this.ckeditor.focus();

        var wrappedText = data.text;
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
        catch (e2) {
        }

        // remove existing links out of the wrapped text
        wrappedText = wrappedText.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, function ($0, $1) {
            if($1.toLowerCase() == "a") {
                return "";
            }
            return $0;
        });

        var insertEl = null;
        var id = data.id;
        var uri = data.path;
        var browserPossibleExtensions = ["jpg","jpeg","gif","png"];

        if (data.elementType == "asset") {
            if (data.type == "image" && textIsSelected == false) {
                // images bigger than 600px or formats which cannot be displayed by the browser directly will be
                // converted by the pimcore thumbnailing service so that they can be displayed in the editor
                var defaultWidth = 600;
                var additionalAttributes = "";

                if(typeof data.imageWidth != "undefined") {
                    uri = "/admin/asset/get-image-thumbnail?id=" + id + "&width=" + defaultWidth + "&aspectratio=true";
                    if(data.imageWidth < defaultWidth
                            && in_arrayi(pimcore.helpers.getFileExtension(data.text),
                                        browserPossibleExtensions)) {
                        uri = data.path;
                        additionalAttributes += ' pimcore_disable_thumbnail="true"';
                    }

                    if(data.imageWidth < defaultWidth) {
                        defaultWidth = data.imageWidth;
                    }

                    additionalAttributes += ' style="width:' + defaultWidth + 'px;"';
                }

                insertEl = CKEDITOR.dom.element.createFromHtml('<img src="'
                            + uri + '" pimcore_type="asset" pimcore_id="' + id + '" ' + additionalAttributes + ' />');
                this.ckeditor.insertElement(insertEl);
                return true;
            }
            else {
                insertEl = CKEDITOR.dom.element.createFromHtml('<a href="' + uri
                            + '" target="_blank" pimcore_type="asset" pimcore_id="' + id + '">' + wrappedText + '</a>');
                this.ckeditor.insertElement(insertEl);
                return true;
            }
        }

        if (data.elementType == "document" && (data.type=="page"
                            || data.type=="hardlink" || data.type=="link")){
            insertEl = CKEDITOR.dom.element.createFromHtml('<a href="' + uri + '" pimcore_type="document" pimcore_id="'
                                                                        + id + '">' + wrappedText + '</a>');
            this.ckeditor.insertElement(insertEl);
            return true;
        }

    },

    checkValue: function () {

        var value = this.getValue();

        if(trim(strip_tags(value)).length < 1) {
            Ext.get(this.textarea).addCls("empty");
        } else {
            Ext.get(this.textarea).removeCls("empty");
        }
    },

    onNodeOver: function(target, dd, e, data) {
        var record = data.records[0];
        data = record.data;
        if (this.dndAllowed(data)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },


    dndAllowed: function(data) {

        if (data.elementType == "document" && (data.type=="page"
                            || data.ype=="hardlink" || data.type=="link")){
            return true;
        } else if (data.elementType=="asset" && data.type != "folder"){
            return true;
        }

        return false;

    },


    getValue: function () {

        var value = this.data;

        if (this.ckeditor) {
            value = this.ckeditor.getData();
        }

        this.data = value;

        return value;
    },

    getType: function () {
        return "wysiwyg";
    }
});

CKEDITOR.disableAutoInline = true;
