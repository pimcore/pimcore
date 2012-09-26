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

pimcore.registerNS("pimcore.document.tags.wysiwyg");
pimcore.document.tags.wysiwyg = Class.create(pimcore.document.tag, {

    type: "wysiwyg",

    initialize: function(id, name, options, data, inherited) {

        this.id = id;
        this.name = name;
        this.setupWrapper();
        if (!options) {
            options = {};
        }

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
        Ext.get(id).appendChild(this.textarea);

        Ext.get(id).insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');

        this.textarea.id = textareaId;
        this.textarea.innerHTML = data;
        
        var textareaHeight = 100;
        if (options.height) {
            textareaHeight = options.height;
        }

        var inactiveContainerWidth = options.width + "px";
        if (typeof options.width == "string" && options.width.indexOf("%") >= 0) {
            inactiveContainerWidth = options.width;
        }

        Ext.get(this.textarea).addClass("pimcore_wysiwyg_inactive");
        Ext.get(this.textarea).applyStyles("width: " + inactiveContainerWidth  + "; min-height: " + textareaHeight + "px;");
        Ext.get(this.textarea).on("click", this.startCKeditor.bind(this));

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

        
        // create mask for dnd, this is done here (in initialize) because we have to register the dom node in dndZones which is used in startup.js
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


        mask.dom.dndOver = false;
        mask.dom.reference = this;

        dndZones.push(mask);
        mask.on("mouseover", function (e) {
            this.dndOver = true;
        }.bind(mask));
        mask.on("mouseout", function (e) {
            this.dndOver = false;
        }.bind(mask));

        mask.reference = this;

        this.maskEl = mask;
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
            Ext.get(this.textarea).un("click", this.startCKeditor.bind(this));


            CKEDITOR.config.language = pimcore.globalmanager.get("user").language;

            var eConfig = Object.clone(this.options);

            if (!this.options.uiColor) {
                eConfig.uiColor = "#f2f2f2";
            }
            
            // if there is no toolbar defined use Full which is defined in CKEDITOR.config.toolbar_Full, possible is also Basic
            if (!this.options.toolbar) {
                eConfig.toolbar = "Full";
            }
            
            delete eConfig.width;
            
            var removePluginsAdd = "";
            if(eConfig.removePlugins) {
                removePluginsAdd = "," + eConfig.removePlugins;
            }
            eConfig.removePlugins = 'about,smiley,scayt,save,print,preview,newpage,maximize,forms,filebrowser,templates' + removePluginsAdd;
            eConfig.extraPlugins = "close,pimcoreimage,pimcorelink";
            eConfig.entities = false;
            eConfig.entities_greek = false;
            eConfig.entities_latin = false;
            eConfig.startupFocus = true;
            eConfig.resize_minWidth = this.options.width - 2;
            eConfig.resize_maxWidth = this.options.width - 2;
            eConfig.autogrow = true;
            
            if(!this.options.height) {
                eConfig.extraPlugins += ",autogrow";
            }
            
            if(this.options.sharedtoolbar !== false) {
                eConfig.sharedSpaces = {
                    top : 'pimcore_wysiwyg_topbar'
                };
                
                // close all editors which don't use the shared toolbar'
                for (var i = 0; i < editables.length; i++) {
                    if (editables[i].getType() == "wysiwyg") {
                        if(editables[i].options.sharedtoolbar === false) {
                             editables[i].endCKeditor();
                        }
                    }
                }
                
                this.checkForSharedSpaces();
            }
            else {
                // close all editors which use the shared toolbar'
                for (var i = 0; i < editables.length; i++) {
                    if (editables[i].getType() == "wysiwyg") {
                        if(editables[i].options.sharedtoolbar !== false) {
                             editables[i].endCKeditor();
                        }
                    }
                }
            }
            
            if (typeof this.options.toolbar == "string") {
                if (window[this.options.toolbar]) {
                    eConfig.toolbar = window[this.options.toolbar];
                }
            }

            /*if (!this.initialOptions.height) {
                var height = Ext.get(this.textarea).getHeight();
                eConfig.height = height;
                if (height < 10) {
                    eConfig.height = 100;
                }
            }*/

            this.ckeditor = CKEDITOR.replace(this.textarea, eConfig);
        }
        catch (e) {
            console.log(e);
        }
    },

    endCKeditor : function () {

        this.hideSharedSpaces();

        if (this.ckeditor) {
            Ext.get(this.textarea).on("click", this.startCKeditor.bind(this));

            this.data = this.ckeditor.getData();

            this.ckeditor.destroy();
            this.ckeditor = null;
        }
    },

    checkForSharedSpaces: function () {
        if (!Ext.get("pimcore_wysiwyg_topbar")) {
            this.createSharedSpaces();
        }
        this.showSharedSpaces();

        // resize
        var width = Ext.getBody().getWidth();
        Ext.get("pimcore_wysiwyg_topbar").setStyle({
            width:  width + "px"
        });
    },

    createSharedSpaces: function () {
        Ext.getBody().insertHtml("afterBegin",'<div id="pimcore_wysiwyg_topbar"></div><div id="pimcore_wysiwyg_bottombar"></div>');
        editWindow.layout.on("resize", this.resizeSharedSpaces)
    },

    hideSharedSpaces: function () {
        if (Ext.get("pimcore_wysiwyg_topbar")) {
            Ext.get("pimcore_wysiwyg_topbar").hide();
        }
    },

    showSharedSpaces: function () {
        if (Ext.get("pimcore_wysiwyg_topbar")) {
            Ext.get("pimcore_wysiwyg_topbar").show();
        }
    },

    resizeSharedSpaces: function (el, width, height, rWidth, rHeight) {

        if (Ext.get("pimcore_wysiwyg_topbar")) {
            Ext.get("pimcore_wysiwyg_topbar").setStyle({
                width:  (width - 10) + "px"
            });
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
        catch (e) {
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

                insertEl = CKEDITOR.dom.element.createFromHtml('<img src="' + uri + '" pimcore_type="asset" pimcore_id="' + id + '" style="width:' + defaultWidth + 'px;"' + additionalAttributes + ' />');
                this.ckeditor.insertElement(insertEl);
                return true;
            }
            else {
                insertEl = CKEDITOR.dom.element.createFromHtml('<a href="' + uri + '" target="_blank" pimcore_type="asset" pimcore_id="' + id + '">' + wrappedText + '</a>');
                this.ckeditor.insertElement(insertEl);
                return true;
            }
        }

        if (data.node.attributes.elementType == "document" && (data.node.attributes.type=="page" || data.node.attributes.type=="hardlink" || data.node.attributes.type=="link")){
            insertEl = CKEDITOR.dom.element.createFromHtml('<a href="' + uri + '" pimcore_type="document" pimcore_id="' + id + '">' + wrappedText + '</a>');
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

        if (data.node.attributes.elementType == "document" && (data.node.attributes.type=="page" || data.node.attributes.type=="hardlink" || data.node.attributes.type=="link")){
            return true;
        } else if (data.node.attributes.elementType=="asset" && data.node.attributes.type != "folder"){
            return true;
        }

        return false;

    },


    getValue: function () {

        var value = this.data;

        if (this.ckeditor) {
            //this.endCKeditor();
            value = this.ckeditor.getData();
        }

        this.data = value;

        return value;
    },

    getType: function () {
        return "wysiwyg";
    }
});


var ckeditor_closeplugin_command = {
    exec:function(editor){
        window.setTimeout(function () {
           closeCKeditors();
        },1000);
    }
};

// add close button plugin
var ckeditor_closeplugin_button ='close';
CKEDITOR.plugins.add(ckeditor_closeplugin_button,{
    init:function(editor){
        editor.addCommand(ckeditor_closeplugin_button,ckeditor_closeplugin_command);
        editor.ui.addButton("close",{
            label:t('close'),
            icon: "/pimcore/static/img/icon/cross.png",
            command:ckeditor_closeplugin_button
        });
    }
});


function closeCKeditors() {
    for (var i = 0; i < editables.length; i++) {
        if (editables[i].getType() == "wysiwyg") {
            editables[i].endCKeditor();
        }
    }
}


// add the close button to the default configurations
CKEDITOR.config.toolbar_Full[0].items.unshift("close");
CKEDITOR.config.toolbar_Basic[0].unshift("close");

(function () {
    var tmpToolBarFull = [];
    for (var i=0; i<CKEDITOR.config.toolbar_Full.length; i++) {
        if(CKEDITOR.config.toolbar_Full[i]["items"]) {
            tmpToolBarFull.push(CKEDITOR.config.toolbar_Full[i]);
        }
    }

    CKEDITOR.config.toolbar_Full = tmpToolBarFull;
})();

