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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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

        this.initialOptions = Object.clone(options);

        if (!data) {
            data = "";
        }
        this.data = data;

        if (!options.width) {
            options.width = Ext.get(id).getWidth();
            if (options.width < 1) {
                options.width = 400;
            }
        }

        if (options.resize_disabled) {
            options.resize_enabled = false;
        }

        this.options = options;


        var textareaId = id + "_textarea";
        this.textarea = document.createElement("div");
        if(this.options["inline"] !== false) {
            this.textarea.setAttribute("contenteditable","true");
        }
        Ext.get(id).appendChild(this.textarea);

        Ext.get(id).insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');

        this.textarea.id = textareaId;
        this.textarea.innerHTML = data;
        
        var textareaHeight = 300;
        if (options.height) {
            textareaHeight = options.height;
        }

        var inactiveContainerWidth = options.width + "px";
        if (typeof options.width == "string" && options.width.indexOf("%") >= 0) {
            inactiveContainerWidth = options.width;
        }

        Ext.get(this.textarea).addClass("pimcore_wysiwyg_inactive");
        Ext.get(this.textarea).addClass("pimcore_wysiwyg");
        Ext.get(this.textarea).applyStyles("width: " + inactiveContainerWidth  + "; min-height: " + textareaHeight
                                                                                                + "px;");

        // if the width is a % value get the current width of the container in px for further processing
        if (typeof options.width == "string" && options.width.indexOf("%") >= 0) {
            this.options.width = Ext.get(this.textarea).getWidth();
            if (this.options.width < 1) {
                this.options.width = 400;
            }
            // apply the width again in px
            Ext.get(this.textarea).applyStyles("width: " + this.options.width + "px");
        }

        Ext.get(id).setStyle({
            width: options.width + "px"
        });

        
        // create mask for dnd, this is done here (in initialize) because we have to register the dom node in
        // dndZones which is used in startup.js
        var mask = document.createElement("div");
        Ext.getBody().appendChild(mask);
        mask = Ext.get(mask);

        var offset = Ext.get(id).getOffsetsTo(Ext.getBody());

        mask.addClass("pimcore_wysiwyg_mask");

        // single applyStyles because of IE, he doesn't like setStyle() here
        mask.applyStyles("top:" + offset[1] + "px;");
        mask.applyStyles("left:" + offset[0] + "px;");
        mask.applyStyles("width:" + options.width + "px;");
        mask.applyStyles("height:" + textareaHeight + "px;");
        mask.hide();


        // register at global DnD manager
        dndManager.addDropTarget(mask, this.onNodeOver.bind(this), this.onNodeDrop.bind(this));

        this.maskEl = mask;

        if(this.options["inline"] === false) {
            Ext.get(this.textarea).on("click", this.startCKeditor.bind(this));
        } else {
            this.startCKeditor();
        }
    },

    mask: function () {
        var offset = Ext.get(this.id).getOffsetsTo(Ext.getBody());

        this.maskEl.setStyle({
            width: this.options.width + "px",
            height: Ext.get(this.id).getHeight() + "px",
            top: offset[1] + "px",
            left: offset[0] + "px",
            backgroundColor: "#ff6600"
        });
        this.maskEl.show();
    },

    unmask: function () {
        this.maskEl.hide();
    },

    startCKeditor: function () {
        
        try {
            if(this.options["inline"] === false) {
                Ext.get(this.textarea).un("click", this.startCKeditor.bind(this));
                Ext.get(this.textarea).removeClass("pimcore_wysiwyg_inactive");
            }

            CKEDITOR.config.language = pimcore.globalmanager.get("user").language;

            // IE Hack see: http://dev.ckeditor.com/ticket/9958
            // problem is that every button in a CKEDITOR window fires the onbeforeunload event
            CKEDITOR.on('instanceReady', function (event) {
                event.editor.on('dialogShow', function (dialogShowEvent) {
                    if (CKEDITOR.env.ie) {
                        $(dialogShowEvent.data._.element.$).find('a[href*="void(0)"]').removeAttr('href');
                    }
                });
            });

            var eConfig = Object.clone(this.options);

            // if there is no toolbar defined use Full which is defined in CKEDITOR.config.toolbar_Full, possible
            // is also Basic
            if (!this.options["toolbar"] && !this.options["toolbarGroups"]) {
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

            var removePluginsAdd = "";
            if(eConfig.removePlugins) {
                removePluginsAdd = "," + eConfig.removePlugins;
            }

            eConfig.removePlugins = 'about,placeholder,flash,smiley,scayt,save,print,preview,newpage,maximize,forms,'
                    + 'filebrowser,templates,divarea,bgcolor,magicline' + removePluginsAdd;
            eConfig.entities = false;
            eConfig.entities_greek = false;
            eConfig.entities_latin = false;
            eConfig.allowedContent = true; // disables CKEditor ACF (will remove pimcore_* attributes from links, etc.)
            eConfig.resize_minWidth = this.options.width - 2;
            eConfig.resize_maxWidth = this.options.width - 2;

            if(this.options["inline"] === false) {
                if(this.options["height"]) {
                    eConfig.removePlugins += ",autogrow";
                } else {
                    eConfig.autogrow = true;
                }
                this.ckeditor = CKEDITOR.replace(this.textarea, eConfig);
            } else {
                if(!this.options['extraPlugins'] || this.options['extraPlugins']== ''){
                    eConfig.extraPlugins = "sourcedialog";
                }else{
                    if(this.options['extraPlugins'].indexOf("sourcedialog") == -1){
                        eConfig.extraPlugins += ",sourcedialog";
                    }
                }
                this.ckeditor = CKEDITOR.inline(this.textarea, eConfig);

                this.ckeditor.on('focus', function () {
                    Ext.get(this.textarea).removeClass("pimcore_wysiwyg_inactive");
                }.bind(this));

                this.ckeditor.on('blur', function () {
                    Ext.get(this.textarea).addClass("pimcore_wysiwyg_inactive");
                }.bind(this));

                // HACK - clean all pasted html
                this.ckeditor.on('paste', function(evt) {
                    evt.data.dataValue = '<!--class="Mso"-->' + evt.data.dataValue;
                }, null, null, 1);
            }
        }
        catch (e) {
            console.log(e);
        }
    },

    endCKeditor : function (force) {

        if (this.ckeditor && (this.options["inline"] === false || force === true)) {
            this.data = this.ckeditor.getData();

            this.ckeditor.destroy();
            this.ckeditor = null;

            Ext.get(this.textarea).on("click", this.startCKeditor.bind(this));
            Ext.get(this.textarea).addClass("pimcore_wysiwyg_inactive");
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if (!this.ckeditor ||!this.dndAllowed(data)) {
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
        var id = data.node.attributes.id;
        var uri = data.node.attributes.path;
        var browserPossibleExtensions = ["jpg","jpeg","gif","png"];

        if (data.node.attributes.elementType == "asset") {
            if (data.node.attributes.type == "image" && textIsSelected == false) {
                // images bigger than 600px or formats which cannot be displayed by the browser directly will be
                // converted by the pimcore thumbnailing service so that they can be displayed in the editor
                var defaultWidth = 600;
                var additionalAttributes = "";

                if(typeof data.node.attributes.imageWidth != "undefined") {
                    uri = "/admin/asset/get-image-thumbnail/id/" + id + "/width/" + defaultWidth + "/aspectratio/true";
                    if(data.node.attributes.imageWidth < defaultWidth
                            && in_arrayi(pimcore.helpers.getFileExtension(data.node.attributes.text),
                                        browserPossibleExtensions)) {
                        uri = data.node.attributes.path;
                        additionalAttributes += ' pimcore_disable_thumbnail="true"';
                    }

                    if(data.node.attributes.imageWidth < defaultWidth) {
                        defaultWidth = data.node.attributes.imageWidth;
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

        if (data.node.attributes.elementType == "document" && (data.node.attributes.type=="page"
                            || data.node.attributes.type=="hardlink" || data.node.attributes.type=="link")){
            insertEl = CKEDITOR.dom.element.createFromHtml('<a href="' + uri + '" pimcore_type="document" pimcore_id="'
                                                                        + id + '">' + wrappedText + '</a>');
            this.ckeditor.insertElement(insertEl);
            return true;
        }

    },

    onNodeOver: function(target, dd, e, data) {
        if (this.dndAllowed(data)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },


    dndAllowed: function(data) {

        if (data.node.attributes.elementType == "document" && (data.node.attributes.type=="page"
                            || data.node.attributes.type=="hardlink" || data.node.attributes.type=="link")){
            return true;
        } else if (data.node.attributes.elementType=="asset" && data.node.attributes.type != "folder"){
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

function closeCKeditors() {
    for (var i = 0; i < editables.length; i++) {
        if (editables[i].getType() == "wysiwyg") {
            editables[i].endCKeditor();
        }
    }
}
