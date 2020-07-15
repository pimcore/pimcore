/**
 * Simple helper class for easily creating image components. This renders an image tag to
 * the DOM with the configured src.
 *
 * {@img Ext.Img/Ext.Img.png Ext.Img component}
 *
 * ## Example usage:
 *
 *     var changingImage = Ext.create('Ext.Img', {
 *         src: 'http://www.sencha.com/img/20110215-feat-html5.png',
 *         width: 184,
 *         height: 90,
 *         renderTo: Ext.getBody()
 *     });
 *
 *     // change the src of the image programmatically
 *     changingImage.setSrc('http://www.sencha.com/img/20110215-feat-perf.png');
 *
 * By default, only an img element is rendered and that is this component's primary
 * {@link Ext.Component#getEl element}. If the {@link Ext.Component#autoEl} property
 * is other than 'img' (the default), the a child img element will be added to the primary
 * element. This can be used to create a wrapper element around the img.
 *
 * ## Wrapping the img in a div:
 *
 *     var wrappedImage = Ext.create('Ext.Img', {
 *         src: 'http://www.sencha.com/img/20110215-feat-html5.png',
 *         autoEl: 'div', // wrap in a div
 *         renderTo: Ext.getBody()
 *     });
 *
 * ## Using a glyph
 *
 *     var glyphImage = Ext.create('Ext.Img', {
 *         glyph: 'xf015@FontAwesome',     // the "home" icon
 *         renderTo: Ext.getBody()
 *     });
 *
 * ## Image Dimensions
 *
 * You should include height and width dimensions for any image owned by a parent 
 * container.  By omitting dimensions, an owning container will not know how to 
 * size and position the image in the initial layout.
 */
Ext.define('Ext.Img', {
    extend: 'Ext.Component',
    alias: ['widget.image', 'widget.imagecomponent'],
    requires: [
        'Ext.Glyph'
    ],

    /**
     * @cfg autoEl
     * @inheritdoc
     */
    autoEl: 'img',

    /**
     * @cfg baseCls
     * @inheritdoc
     */
    baseCls: Ext.baseCSSPrefix + 'img',

    config: {
        /**
         * @cfg {String} src
         * The source of this image. See {@link Ext#resolveResource} for details on
         * locating application resources.
         * @accessor
         */
        src: null,

        /**
         * @cfg glyphorig
         * @inheritdoc Ext.panel.Header#cfg-glyph
         */
        /**
         * @cfg {Number/String} glyph
         * A numeric unicode character code to serve as the image.  If this option is used
         * The image will be rendered using a div with innerHTML set to the html entity
         * for the given character code.  The default font-family for glyphs can be set
         * globally using {@link Ext#setGlyphFontFamily Ext.setGlyphFontFamily()}. Alternatively,
         * this config option accepts a string with the charCode and font-family separated by
         * the `@` symbol. For example '65@My Font Family'.
         */
        glyph: null
    },

    /**
     * @cfg {String} alt
     * The descriptive text for non-visual UI description.
     */
    alt: '',

    /**
     * @cfg {String} title
     * Specifies additional information about the image.
     */
    title: '',

    /**
     * @cfg {String} imgCls
     * Optional CSS classes to add to the img element.
     */
    imgCls: '',

    /**
     * @property maskOnDisable
     * @inheritdoc
     */
    maskOnDisable: false,

    applySrc: function(src) {
        return src && Ext.resolveResource(src);
    },

    getElConfig: function() {
        var me = this,
            autoEl = me.autoEl,
            config = me.callParent(),
            glyph = me.glyph,
            img;

        // We were configured with a glyph, then this is a div with a single char content
        if (glyph) {
            config.tag = 'div';
            config.html = glyph.character;
            config.style = config.style || {};
            config.style.fontFamily = glyph.fontFamily;

            // A glyph is a graphic which is not an <img> tag so it should have
            // the corresponding role for Accessibility interface to recognize
            config.role = 'img';
        }
        // The default; an img element
        else if (autoEl === 'img' || (Ext.isObject(autoEl) && autoEl.tag === 'img')) {
            img = config;
        }
        // It is sometimes helpful (like in a panel header icon) to have the img wrapped
        // by a div. If our autoEl is not 'img' then we just add an img child to the el.
        else {
            config.cn = [img = {
                tag: 'img',
                id: me.id + '-img'
            }];
        }

        if (img) {
            if (me.imgCls) {
                img.cls = (img.cls ? img.cls + ' ' : '') + me.imgCls;
            }

            img.src = me.src || Ext.BLANK_IMAGE_URL;
        }

        if (me.alt) {
            (img || config).alt = me.alt;
        }
        else {
            // Images that do not have alt attribute can't be properly announced
            // by screen readers. In best case they will be silently skipped;
            // in worst case screen reader will announce data url. Yes, that very long
            // base-64 encoded string. :/
            // That will make the application totally unusable for blind people.
            (img || config).alt = '';

            //<debug>
            Ext.log.warn('For WAI-ARIA compliance, IMG elements SHOULD have an alt attribute.');
            //</debug>
        }

        if (me.title) {
            (img || config).title = me.title;
        }

        return config;
    },

    onRender: function() {
        var me = this,
            autoEl = me.autoEl,
            el;

        me.callParent(arguments);

        el = me.el;

        if (autoEl === 'img' || (Ext.isObject(autoEl) && autoEl.tag === 'img')) {
            me.imgEl = el;
        }
        else {
            me.imgEl = el.getById(me.id + '-img');
        }
    },

    doDestroy: function() {
        var me = this,
            imgEl = me.imgEl;

        // Only clean up when the img is a child, otherwise it will get handled
        // by the element destruction in the parent
        if (imgEl && me.el !== imgEl) {
            imgEl.destroy();
        }

        me.imgEl = null;

        me.callParent();
    },

    getTitle: function() {
        return this.title;
    },

    /**
     * Updates the {@link #title} of the image.
     * @param {String} title
     */
    setTitle: function(title) {
        var me = this,
            imgEl = me.imgEl;

        me.title = title || '';

        if (imgEl) {
            imgEl.dom.title = title || '';
        }
    },

    afterComponentLayout: function(width, height, oldWidth, oldHeight) {
        var heightModel = this.getSizeModel().height,
            h;

        // If we have our height set, then size the glyph as requested to make image scalable.
        if ((heightModel.calculated || heightModel.configured) && height && this.glyph) {
            h = height + 'px';
            this.setStyle({
                'line-height': h,
                'font-size': h
            });
        }

        this.callParent([width, height, oldWidth, oldHeight]);
    },

    getAlt: function() {
        return this.alt;
    },

    /**
     * Updates the {@link #alt} of the image.
     * @param {String} alt
     */
    setAlt: function(alt) {
        var me = this,
            imgEl = me.imgEl;

        me.alt = alt || '';

        if (imgEl) {
            imgEl.dom.alt = alt || '';
        }
    },

    _naturalSize: null,

    /**
     * Returns the size of the image as an object.
     * @return {Object} The size and aspect ratio of the image.
     * @return {Number} return.aspect The aspect ration of the image (`width / height`).
     * @return {Number} return.height The height of the image.
     * @return {Number} return.width The width of the image.
     * @since 6.2.0
     */
    getNaturalSize: function() {
        var me = this,
            img = me.imgEl,
            naturalSize = me._naturalSize,
            style, w, h;

        if (img && !naturalSize) {
            img = img.dom;

            me._naturalSize = naturalSize = {
                width: w = img.naturalWidth,
                height: img.naturalHeight
            };

            if (!w) {
                style = img.style;

                w = style.width;
                h = style.height;

                // As long as the width/height styles are "auto", the IMG dom element
                // will have "width" and "height" properties that are the natural size.
                style.width = style.height = 'auto';

                naturalSize.width = img.width;
                naturalSize.height = img.height;

                style.width = w;
                style.height = h;
            }

            naturalSize.aspect = naturalSize.width / naturalSize.height;
        }

        return naturalSize;
    },

    updateSrc: function(src) {
        var imgEl = this.imgEl;

        if (imgEl) {
            imgEl.dom.src = src || Ext.BLANK_IMAGE_URL;
        }
    },

    applyGlyph: function(glyph, oldGlyph) {
        if (glyph) {
            if (!glyph.isGlyph) {
                glyph = new Ext.Glyph(glyph);
            }

            if (glyph.isEqual(oldGlyph)) {
                glyph = undefined;
            }
        }

        return glyph;
    },

    updateGlyph: function(glyph, oldGlyph) {
        var el = this.el;

        if (el) {
            el.dom.innerHTML = glyph.character;
            el.setStyle(glyph.getStyle());
        }
    }
});
