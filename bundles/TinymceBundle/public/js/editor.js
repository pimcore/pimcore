pimcore.registerNS("pimcore.bundle.tinymce.editor");
pimcore.bundle.tinymce.editor= Class.create({
    initialize: function () {
        document.addEventListener(parent.pimcore.events.initializeWysiwyg, this.initializeWysiwyg.bind(this));
        document.addEventListener(parent.pimcore.events.createWysiwyg, this.createWysiwyg.bind(this));
        document.addEventListener(parent.pimcore.events.onDropWysiwyg, this.onDropWysiwyg.bind(this));
        document.addEventListener(parent.pimcore.events.beforeDestroyWysiwyg, this.beforeDestroyWysiwyg.bind(this));
    },

    initializeWysiwyg: function (e) {
        // CKEDITOR.config.language = pimcore.globalmanager.get("user").language;
        // this.eConfig = {};
        //
        // if(e.detail.context === 'object') {
        //     this.eConfig = {
        //         width: e.detail.config.width,
        //         height: e.detail.config.height,
        //         language: pimcore.settings["language"],
        //         resize_enabled: false,
        //         entities: false,
        //         entities_greek: false,
        //         entities_latin: false,
        //         extraAllowedContent: "*[pimcore_type,pimcore_id,pimcore_disable_thumbnail]",
        //         baseFloatZIndex: 40000 // prevent that the editor gets displayed behind the grid cell editor window
        //     };
        // }
        //
        // let specificConfig = Object.assign({}, e.detail.config);
        //
        // if (!e.detail.config["toolbarGroups"] && e.detail.config['toolbarGroups'] !== false) {
        //     this.eConfig.toolbarGroups = [
        //         {name: 'basicstyles', groups: ['undo', "find", 'basicstyles', 'list']},
        //         '/',
        //         {name: 'paragraph', groups: ['align', 'indent']},
        //         {name: 'blocks'},
        //         {name: 'links'},
        //         {name: 'insert'},
        //         "/",
        //         {name: 'styles'},
        //         {name: 'tools', groups: ['colors', "tools", 'cleanup', 'mode', "others"]}
        //     ];
        // }
        //
        // delete specificConfig.width;
        //
        // if(e.detail.context === 'document') {
        //     this.eConfig.language = pimcore.settings["language"];
        //     this.eConfig.entities = false;
        //     this.eConfig.entities_greek = false;
        //     this.eConfig.entities_latin = false;
        //     this.eConfig.extraAllowedContent = "*[pimcore_type,pimcore_id]";
        // }
        //
        // if(e.detail.context === 'document' || e.detail.context === 'document') {
        //     if (typeof (pimcore[e.detail.context].tags.wysiwyg.defaultEditorConfig) == 'object') {
        //         this.eConfig = mergeObject(this.eConfig, pimcore[e.detail.context].tags.wysiwyg.defaultEditorConfig);
        //     }
        // }
        //
        // if(e.detail.context === 'document') {
        //     this.eConfig = mergeObject(this.eConfig, specificConfig);
        // } else if(e.detail.context === 'object') {
        //     if(this.eConfig.hasOwnProperty('removePlugins')) {
        //         this.eConfig.removePlugins += ",tableresize";
        //     } else {
        //         this.eConfig.removePlugins = "tableresize";
        //     }
        //
        //     if(e.detail.config.toolbarConfig) {
        //         const useNativeJson = Ext.USE_NATIVE_JSON;
        //         Ext.USE_NATIVE_JSON = false;
        //         var elementCustomConfig = Ext.decode(e.detail.config.toolbarConfig);
        //         Ext.USE_NATIVE_JSON = useNativeJson;
        //         this.eConfig = mergeObject(this.eConfig, elementCustomConfig);
        //     }
        //
        //     if(!isNaN(e.detail.config.maxCharacters) && e.detail.config.maxCharacters > 0) {
        //         const maxChars = e.detail.config.maxCharacters;
        //         this.eConfig.wordcount = {
        //             showParagraphs: false,
        //             showWordCount: false,
        //             showCharCount: true,
        //             maxCharCount: maxChars
        //         }
        //     } else {
        //         this.eConfig.wordcount = {
        //             showParagraphs: false,
        //             showWordCount: false,
        //             showCharCount: true,
        //             maxCharCount: -1
        //         }
        //     }
        // } else if (e.detail.context === 'translation') {
        //     if(this.eConfig.hasOwnProperty('removePlugins')) {
        //         this.eConfig.removePlugins += ",tableresize";
        //     } else {
        //         this.eConfig.removePlugins = "tableresize";
        //     }
        //
        //     //prevent override important settings!
        //     this.eConfig.resize_enabled = false;
        //     this.eConfig.enterMode = CKEDITOR.ENTER_BR;
        //     this.eConfig.entities = false;
        //     this.eConfig.entities_greek = false;
        //     this.eConfig.entities_latin = false;
        //     this.eConfig.extraAllowedContent = "*[pimcore_type,pimcore_id]";
        //     this.eConfig.baseFloatZIndex = 40000;   // prevent that the editor gets displayed behind the grid cell editor window
        // }
    },

    createWysiwyg: function (e) {
        // this.ckeditor = CKEDITOR.inline(e.detail.textarea, this.eConfig);
        tinymce.init({
            selector: `#${e.detail.textarea.id}`
        });
        // this.ckeditor.on('change', function(eChange) {
        //     document.dispatchEvent(new CustomEvent(pimcore.events.changeWysiwyg, {
        //         detail: {
        //             e: eChange,
        //             data: eChange.editor.getData(),
        //             context: e.detail.context
        //         }
        //     }));
        // });
    },

    onDropWysiwyg: function (e) {
        // let data = e.detail.data;
        //
        // let record = data.records[0];
        // data = record.data;
        //
        // if (!this.ckeditor) {
        //     return;
        // }
        //
        // // we have to foxus the editor otherwise an error is thrown in the case the editor wasn't opend before a drop element
        // this.ckeditor.focus();
        //
        // var wrappedText = data.text;
        // var textIsSelected = false;
        //
        // try {
        //     var selection = this.ckeditor.getSelection();
        //     var bookmarks = selection.createBookmarks();
        //     var range = selection.getRanges()[ 0 ];
        //     var fragment = range.clone().cloneContents();
        //
        //     selection.selectBookmarks(bookmarks);
        //     var retval = "";
        //     var childList = fragment.getChildren();
        //     var childCount = childList.count();
        //
        //     for (var i = 0; i < childCount; i++) {
        //         var child = childList.getItem(i);
        //         retval += ( child.getOuterHtml ?
        //             child.getOuterHtml() : child.getText() );
        //     }
        //
        //     if (retval.length > 0) {
        //         wrappedText = retval;
        //         textIsSelected = true;
        //     }
        // }
        // catch (e2) {
        // }
        //
        // // remove existing links out of the wrapped text
        // wrappedText = wrappedText.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, function ($0, $1) {
        //     if($1.toLowerCase() == "a") {
        //         return "";
        //     }
        //     return $0;
        // });
        //
        // var insertEl = null;
        // var id = data.id;
        // var uri = data.path;
        // var browserPossibleExtensions = ["jpg","jpeg","gif","png"];
        //
        // if (data.elementType == "asset") {
        //     if (data.type == "image" && textIsSelected == false) {
        //         // images bigger than 600px or formats which cannot be displayed by the browser directly will be
        //         // converted by the pimcore thumbnailing service so that they can be displayed in the editor
        //         var defaultWidth = 600;
        //         var additionalAttributes = "";
        //
        //         if(typeof data.imageWidth != "undefined") {
        //             var route = 'pimcore_admin_asset_getimagethumbnail';
        //             var params = {
        //                 id: id,
        //                 width: defaultWidth,
        //                 aspectratio: true
        //             };
        //
        //             uri = Routing.generate(route, params);
        //
        //             if(data.imageWidth < defaultWidth
        //                 && in_arrayi(pimcore.helpers.getFileExtension(data.text),
        //                     browserPossibleExtensions)) {
        //                 uri = data.path;
        //                 additionalAttributes += ' pimcore_disable_thumbnail="true"';
        //             }
        //
        //             if(data.imageWidth < defaultWidth) {
        //                 defaultWidth = data.imageWidth;
        //             }
        //
        //             additionalAttributes += ' style="width:' + defaultWidth + 'px;"';
        //         }
        //
        //         insertEl = CKEDITOR.dom.element.createFromHtml('<img src="'
        //             + uri + '" pimcore_type="asset" pimcore_id="' + id + '" ' + additionalAttributes + ' />');
        //         this.ckeditor.insertElement(insertEl);
        //         return true;
        //     }
        //     else {
        //         insertEl = CKEDITOR.dom.element.createFromHtml('<a href="' + uri
        //             + '" target="_blank" pimcore_type="asset" pimcore_id="' + id + '">' + wrappedText + '</a>');
        //         this.ckeditor.insertElement(insertEl);
        //         return true;
        //     }
        // }
        //
        // if (data.elementType == "document" && (data.type=="page"
        //     || data.type=="hardlink" || data.type=="link")){
        //     insertEl = CKEDITOR.dom.element.createFromHtml('<a href="' + uri + '" pimcore_type="document" pimcore_id="'
        //         + id + '">' + wrappedText + '</a>');
        //     this.ckeditor.insertElement(insertEl);
        //     return true;
        // }
        //
        // if (data.elementType == "object"){
        //     insertEl = CKEDITOR.dom.element.createFromHtml('<a href="' + uri + '" pimcore_type="object" pimcore_id="'
        //         + id + '">' + wrappedText + '</a>');
        //     this.ckeditor.insertElement(insertEl);
        //     return true;
        // }
    },

    beforeDestroyWysiwyg: function (e) {
        // if(this.ckeditor) {
        //     this.ckeditor.destroy();
        //     this.ckeditor = null;
        // }
    }
})

new pimcore.bundle.tinymce.editor();


// CKEDITOR.disableAutoInline = true;
