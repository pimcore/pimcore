pimcore.registerNS("pimcore.bundle.tinymce.editor");
pimcore.bundle.tinymce.editor = Class.create({
    languageMapping: {
        fr: 'fr_FR',
        pt: 'pt_PT',
        sv: 'sv_SE',
        th: 'th_TH',
        hu: 'hu_HU'
    },

    maxChars: -1,

    initialize: function () {
        if(!parent.pimcore.wysiwyg) {
            parent.pimcore.wysiwyg = {};
            parent.pimcore.wysiwyg.editors = [];
        }
        parent.pimcore.wysiwyg.editors.push('TinyMCE');
        document.addEventListener(parent.pimcore.events.initializeWysiwyg, this.initializeWysiwyg.bind(this));
        document.addEventListener(parent.pimcore.events.createWysiwyg, this.createWysiwyg.bind(this));
        document.addEventListener(parent.pimcore.events.onDropWysiwyg, this.onDropWysiwyg.bind(this));
        document.addEventListener(parent.pimcore.events.beforeDestroyWysiwyg, this.beforeDestroyWysiwyg.bind(this));
    },

    initializeWysiwyg: function (e) {
        if (e.detail.context === 'object') {
            if (!isNaN(e.detail.config.maxCharacters) && e.detail.config.maxCharacters > 0) {
                this.maxChars = e.detail.config.maxCharacters;
            }else{
                this.maxChars = -1;
            }
        }

        this.config = e.detail.config;

        if(this.config.toolbarConfig) {
            const useNativeJson = Ext.USE_NATIVE_JSON;
            Ext.USE_NATIVE_JSON = false;
            const elementCustomConfig = Ext.decode(this.config.toolbarConfig);
            Ext.USE_NATIVE_JSON = useNativeJson;
            this.config = mergeObject(this.config, elementCustomConfig);
        }
    },

    createWysiwyg: function (e) {
        this.textareaId = e.detail.textarea.id ?? e.detail.textarea;

        const userLanguage = pimcore.globalmanager.get("user").language;
        let language = this.languageMapping[userLanguage];
        if (!language) {
            language = userLanguage;
        }
        if(language !== 'en') {
            language = {
                language_url: '/bundles/pimcoretinymce/js/langs/' + language + '.js',
                language: language,
            };
        } else {
            language = {};
        }

        const toolbar1 = 'undo redo | blocks | ' +
            'bold italic | alignleft aligncenter ' +
            'alignright alignjustify | link hr charmap';

        const toolbar2 = 'table | bullist numlist outdent indent | removeformat | ' +
            'code | searchreplace visualblocks help';

        let toolbar;
        if (e.detail.context === 'translation') {
            toolbar = {
                toolbar1: toolbar1,
                toolbar2: toolbar2
            };
        } else {
            toolbar = {
                toolbar1: `${toolbar1} | ${toolbar2}`
            };
        }

        let subSpace = '';
        if (e.detail.context === 'document') {
            subSpace = 'editables';
        } else if (e.detail.context === 'object') {
            subSpace = 'tags';
        }

        let defaultConfig = {};
        if('' !== subSpace && pimcore[e.detail.context][subSpace]) {
            defaultConfig = pimcore[e.detail.context][subSpace].wysiwyg ? pimcore[e.detail.context][subSpace].wysiwyg.defaultEditorConfig : {};
        }

        const maxChars = this.maxChars;
        let changedContent = false;

        function checkCharCount() {
            tinymce.activeEditor.getBody().style.border = '';
            tinymce.activeEditor.getElement().setAttribute('title', '');

            const charCount = tinymce.activeEditor.plugins.wordcount.body.getCharacterCount();

            if (maxChars !== -1 && charCount > maxChars) {
                tinymce.activeEditor.getBody().style.border = '1px solid red';
                tinymce.activeEditor.getElement().setAttribute('title', t('maximum_length_is') + ' ' + maxChars);
            }
        }

        const finalConfig = Object.assign({
            selector: `#${this.textareaId}`,
            height: 500,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'charmap', 'code', 'help', 'image', 'link', 'lists',
                'media', 'searchreplace', 'table', 'visualblocks', 'wordcount'
            ],
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            inline: true,
            base_url: '/bundles/pimcoretinymce/build/tinymce',
            suffix: '.min',
            convert_urls: false,
            convert_unsafe_embeds: true,
            extended_valid_elements: 'a[id|class|name|href|target|title|pimcore_id|pimcore_type],img[id|class|style|longdesc|usemap|src|border|alt=|title|hspace|vspace|width|height|align|pimcore_id|pimcore_type]',
            init_instance_callback: function (editor) {
                // Do an initial check for character count based on the initial content before there is any user input
                checkCharCount();

                editor.on('input', function (eChange) {
                    checkCharCount();
                    document.dispatchEvent(new CustomEvent(pimcore.events.changeWysiwyg, {
                        detail: {
                            e: eChange,
                            data: tinymce.activeEditor.getContent(),
                            context: e.detail.context
                        }
                    }));
                }.bind(this));
                editor.on('change', function (eChange) {
                    changedContent = true;
                }.bind(this));
                editor.on('blur', function (eChange) {
                    if (!changedContent) {
                        return;
                    }
                    changedContent = false;
                    document.dispatchEvent(new CustomEvent(pimcore.events.changeWysiwyg, {
                        detail: {
                            e: eChange,
                            data: tinymce.activeEditor.getContent(),
                            context: e.detail.context
                        }
                    }));
                }.bind(this));
            }.bind(this)
        }, language, toolbar, defaultConfig, this.config);

        document.dispatchEvent(new CustomEvent(pimcore.events.createWysiwygConfig, {
            detail: {
                data: finalConfig,
                context: e.detail.context
            }
        }));

        tinymce.init(finalConfig);

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

        let retval = tinymce.activeEditor.selection.getContent();
        if (retval.length > 0) {
            wrappedText = retval;
            textIsSelected = true;
        }

        // remove existing links out of the wrapped text
        wrappedText = wrappedText.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, function ($0, $1) {
            if ($1.toLowerCase() === "a") {
                return "";
            }
            return $0;
        });

        const id = data.id;
        let uri = data.path;
        const browserPossibleExtensions = ["jpg", "jpeg", "gif", "png"];

        if (data.elementType === "asset") {
            if (data.type === "image" && textIsSelected === false) {
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
                    target: '_blank',
                    alt: 'asset_image',
                    pimcore_id: id,
                    pimcore_type: 'asset'
                });
                tinymce.activeEditor.selection.setContent(tinymce.activeEditor.dom.createHTML('img', additionalAttributes));
                return true;
            } else {
                tinymce.activeEditor.selection.setContent(tinymce.activeEditor.dom.createHTML('a', {
                    href: uri,
                    target: '_blank',
                    pimcore_id: id,
                    pimcore_type: 'asset'
                }, wrappedText));
                return true;
            }
        }

        if (data.elementType === "document" && (data.type === "page"
            || data.type === "hardlink" || data.type === "link")) {
            tinymce.activeEditor.selection.setContent(tinymce.activeEditor.dom.createHTML('a', {
                href: uri,
                pimcore_id: id,
                pimcore_type: 'document'
            }, wrappedText));
            return true;
        }

        if (data.elementType === "object") {
            tinymce.activeEditor.selection.setContent(tinymce.activeEditor.dom.createHTML('a', {
                href: uri,
                pimcore_id: id,
                pimcore_type: 'object'
            }, wrappedText));
            return true;
        }
    },

    beforeDestroyWysiwyg: function (e) {
        tinymce.remove(`#${this.textareaId}`);
    }
})

new pimcore.bundle.tinymce.editor();
console.info('TinyMCE is deprecated, please use Quill (pimcore/quill-bundle) instead');
