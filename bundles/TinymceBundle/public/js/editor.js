pimcore.registerNS("pimcore.bundle.tinymce.editor");
pimcore.bundle.tinymce.editor = Class.create({
    languageMapping: {
        zh_Hans: 'zh_CN',
        en: 'en_GB',
        fr: 'fr_FR',
        pt: 'pt_PT',
        sv: 'sv_SE',
        th: 'th_TH',
        hu: 'hu_HU'
    },

    initialize: function () {
        document.addEventListener(parent.pimcore.events.initializeWysiwyg, this.initializeWysiwyg.bind(this));
        document.addEventListener(parent.pimcore.events.createWysiwyg, this.createWysiwyg.bind(this));
        document.addEventListener(parent.pimcore.events.onDropWysiwyg, this.onDropWysiwyg.bind(this));
        document.addEventListener(parent.pimcore.events.beforeDestroyWysiwyg, this.beforeDestroyWysiwyg.bind(this));
    },

    initializeWysiwyg: function (e) {
        this.eConfig = {};
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

        if (e.detail.context === 'object') {
            if (!isNaN(e.detail.config.maxCharacters) && e.detail.config.maxCharacters > 0) {
                const maxChars = e.detail.config.maxCharacters;
                this.eConfig.wordcount = {
                    showParagraphs: false,
                    showWordCount: false,
                    showCharCount: true,
                    maxCharCount: maxChars
                }
            } else {
                this.eConfig.wordcount = {
                    showParagraphs: false,
                    showWordCount: false,
                    showCharCount: true,
                    maxCharCount: -1
                }
            }
        }
    },

    createWysiwyg: function (e) {
        this.textareaId = e.detail.textarea.id;
        const userLanguage = pimcore.globalmanager.get("user").language;
        let language = this.languageMapping[userLanguage];
        if (!language) {
            language = userLanguage;
        }
        tinymce.init({
            selector: `#${this.textareaId}`,
            inline: true,
            base_url: '/bundles/pimcoretinymce/build/tinymce',
            suffix: '.min',
            extended_valid_elements: 'a[name|href|target|title|pimcore_type|pimcore_id],img[style|longdesc|usemap|src|border|alt=|title|hspace|vspace|width|height|align|pimcore_type|pimcore_id]',
            language: language,
            init_instance_callback: function (editor) {
                editor.on('input', function (eChange) {
                    document.dispatchEvent(new CustomEvent(pimcore.events.changeWysiwyg, {
                        detail: {
                            e: eChange,
                            data: eChange.target.innerHTML,
                            context: e.detail.context
                        }
                    }));
                });
            }

        });

    },

    onDropWysiwyg: function (e) {
        let data = e.detail.data;

        let record = data.records[0];
        data = record.data;

        if (!tinymce.activeEditor) {
            return;
        }

        // we have to focus the editor otherwise an error is thrown in the case the editor wasn't opend before a drop element
        tinymce.activeEditor.focus();

        let wrappedText = data.text;
        let textIsSelected = false;

        // try {
        //     const selection = tinymce.activeEditor.getSelection();
        //     const bookmarks = selection.createBookmarks();
        //     const range = selection.getRanges()[0];
        //     const fragment = range.clone().cloneContents();
        //
        //     selection.selectBookmarks(bookmarks);
        //     let retval = "";
        //     const childList = fragment.getChildren();
        //     const childCount = childList.count();
        //
        //     let child;
        //     for (let i = 0; i < childCount; i++) {
        //         child = childList.getItem(i);
        //         retval += (child.getOuterHtml ?
        //             child.getOuterHtml() : child.getText());
        //     }
        //
        //     if (retval.length > 0) {
        //         wrappedText = retval;
        //         textIsSelected = true;
        //     }
        // } catch (e2) {
        // }

        // remove existing links out of the wrapped text
        wrappedText = wrappedText.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, function ($0, $1) {
            if ($1.toLowerCase() == "a") {
                return "";
            }
            return $0;
        });

        let insertEl = null;
        const id = data.id;
        let uri = data.path;
        const browserPossibleExtensions = ["jpg", "jpeg", "gif", "png"];

        if (data.elementType == "asset") {
            if (data.type == "image" && textIsSelected == false) {
                // images bigger than 600px or formats which cannot be displayed by the browser directly will be
                // converted by the pimcore thumbnailing service so that they can be displayed in the editor
                let defaultWidth = 600;
                let additionalAttributes = {};

                if (typeof data.imageWidth != "undefined") {
                    const route = 'pimcore_admin_asset_getimagethumbnail';
                    const params = {
                        id: id,
                        width: defaultWidth,
                        aspectratio: true
                    };

                    uri = Routing.generate(route, params);

                    if (data.imageWidth < defaultWidth
                        && in_arrayi(pimcore.helpers.getFileExtension(data.text),
                            browserPossibleExtensions)) {
                        uri = data.path;
                        additionalAttributes = mergeObject(additionalAttributes, {pimcore_disable_thumbnail: true});
                    }

                    if (data.imageWidth < defaultWidth) {
                        defaultWidth = data.imageWidth;
                    }

                    additionalAttributes = mergeObject(additionalAttributes, {style: `width:${defaultWidth}px;`});
                }

                additionalAttributes = mergeObject(additionalAttributes, {
                    src: uri,
                    pimcore_type: 'asset',
                    pimcore_id: id,
                    target: '_blank',
                    alt: 'asset_image'
                });
                tinymce.activeEditor.selection.setContent(tinymce.activeEditor.dom.createHTML('img', additionalAttributes));
                return true;
            } else {
                tinymce.activeEditor.selection.setContent(tinymce.activeEditor.dom.createHTML('a', {
                    href: uri,
                    pimcore_type: 'asset',
                    pimcore_id: id,
                    target: '_blank'
                }, wrappedText));
                return true;
            }
        }

        if (data.elementType == "document" && (data.type == "page"
            || data.type == "hardlink" || data.type == "link")) {
            tinymce.activeEditor.selection.setContent(tinymce.activeEditor.dom.createHTML('a', {
                href: uri,
                pimcore_type: 'document',
                pimcore_id: id
            }, wrappedText));
            return true;
        }

        if (data.elementType == "object") {
            tinymce.activeEditor.selection.setContent(tinymce.activeEditor.dom.createHTML('a', {
                href: uri,
                pimcore_type: 'object',
                pimcore_id: id
            }, wrappedText));
            return true;
        }
    },

    beforeDestroyWysiwyg: function (e) {
        tinymce.remove(`#${this.textareaId}`);
    }
})

new pimcore.bundle.tinymce.editor();
