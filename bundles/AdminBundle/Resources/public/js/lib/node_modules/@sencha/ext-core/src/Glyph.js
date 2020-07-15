/**
 * @class Ext.Glyph
 * @private
 * A class which parses a `glyph` config and provides ways of creating the relevant DOM or yielding
 * information about the selected codepoint and font.
 */
Ext.define('Ext.Glyph', {
    /**
     * @property {Boolean} isGlyph
     * `true` in this class to identify an object as an instantiated Glyph, or subclass thereof.
     */
    isGlyph: true,

    /**
     * @property {Number} codepoint
     * The unicode codepoint of the configured glyph.
     */

    /**
     * @property {String} character
     * A single character string representing the selected glyph. This may safely
     * be injected directly into HTML.
     */

    /**
     * @property {String} fontFamily
     * The name of the font family configured. If none was configured, it uses the library default.
     * The default font-family  for glyphs can be set globally using 
     * {@link Ext.app.Application#glyphFontFamily glyphFontFamily} application 
     * config or the {@link Ext#setGlyphFontFamily Ext.setGlyphFontFamily()} method.
     * It is initially set to `'Pictos'`.
     */

    /**
     * 
     * @param {String/Number} glyph
     * If a `string` is passed, it may be the character itself, or the unicode codepoint.
     * for example:
     *
     *     new Ext.Glyph('H');      // the "home" icon in the default (Pictos) font.
     *     new Ext.Glyph('x48');    // the "home" icon in the default (Pictos) font.
     *     new Ext.Glyph(72);       // the "home" icon in the default (Pictos) font.
     *
     * An `@` separator may be used to denote the font:
     *
     *     new Ext.Glyph('xf015@FontAwesome');  // The "home" icon in the FontAwesome font.
     */
    constructor: function(glyph) {
        if (glyph) {
            this.setGlyph(glyph);
        }
    },

    /**
     * 
     * @param {String/Number} glyph
     * If a `string` is passed, it may be the character itself, or the unicode codepoint.
     * for example:
     *
     *     myGlyph.setGlyph('H');      // the "home" icon in the default (Pictos) font.
     *     myGlyph.setGlyph('x48');    // the "home" icon in the default (Pictos) font.
     *     myGlyph.setGlyph(72);       // the "home" icon in the default (Pictos) font.
     *
     * An `@` separator may be used to denote the font:
     *
     *     myGlyph.setGlyph('xf015@FontAwesome');  // The "home" icon in the FontAwesome font.
     *
     */
    setGlyph: function(glyph) {
        var glyphParts;

        this.glyphConfig = glyph;

        if (typeof glyph === 'string') {
            glyphParts = glyph.split('@');
            glyph = isNaN(glyphParts[0])
                ? parseInt('0' + glyphParts[0], 16)
                : parseInt(glyphParts[0], 10);

            // If the glyph specification cannot be parsed as a number
            // we use the codepoint of the first character.
            // If the raw string isNaN, we prepend '0' so that a possible 'xf005' will parse as hex,
            // otherwise parse it as decimal.
            if (isNaN(glyph) || !glyph) {
                glyph = glyphParts[0].charCodeAt(0);
            }

            this.fontFamily = glyphParts[1] || Ext._glyphFontFamily;
        }
        else {
            this.fontFamily = Ext._glyphFontFamily;
        }

        this.codepoint = glyph;
        this.character = Ext.String.fromCodePoint(this.codepoint);

        return this;
    },

    getStyle: function() {
        return {
            'font-family': this.fontFamily
        };
    },

    isEqual: function(other) {
        return other && other.isGlyph && other.codepoint === this.codepoint &&
               other.fontFamily === this.fontFamily;
    },

    statics: (function() {
        var instance;

        return {
            /**
             * @method fly
             * @static
             * Returns a static, *singleton* `Glyph` instance encapsulating the passed
             * configuration. See {@link #method-setGlyph}.
             *
             * Note that the returned `Glyph` is reused upon each call, so only use this when
             * the encapsulated information is consumed immediately. For a persistent `Glyph`
             * instance, instantiate a new one.
             *
             * @param {String/Number} glyph
             * If a `string` is passed, it may be the character itself, or the unicode codepoint.
             * for example:
             *
             *     Ext.Glyph.fly('H');      // the "home" icon in the default (Pictos) font.
             *     Ext.Glyph.fly('x48');    // the "home" icon in the default (Pictos) font.
             *     Ext.Glyph.fly(72);       // the "home" icon in the default (Pictos) font.
             *
             * An `@` separator may be used to denote the font:
             *
             *     Ext.Glyph.fly('xf015@FontAwesome');  // The "home" icon in the FontAwesome font.
             *
             * @returns {Ext.Glyph} A static `Glyph` instance encapsulating the passed
             * configuration.
             */
            fly: function(glyph) {
                return glyph.isGlyph
                    ? glyph
                    : (instance || (instance = new Ext.Glyph())).setGlyph(glyph);
            }
        };
    })()
});
