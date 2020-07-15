/**
 * @class Ext.draw.engine.Svg
 * @extends Ext.draw.Surface
 *
 * SVG engine.
 */
Ext.define('Ext.draw.engine.Svg', {
    extend: 'Ext.draw.Surface',
    requires: ['Ext.draw.engine.SvgContext'],
    isSVG: true,

    config: {
        /**
         * @cfg {Boolean} highPrecision
         * Nothing needs to be done in high precision mode.
         */
        highPrecision: false
    },

    getElementConfig: function() {
        return {
            reference: 'element',
            style: {
                position: 'absolute'
            },
            children: [
                {
                    reference: 'bodyElement',
                    style: {
                        width: '100%',
                        height: '100%',
                        position: 'relative'
                    },
                    children: [
                        {
                            tag: 'svg',
                            reference: 'svgElement',
                            namespace: "http://www.w3.org/2000/svg",
                            width: '100%',
                            height: '100%',
                            version: 1.1
                        }
                    ]
                }
            ]
        };
    },

    constructor: function(config) {
        var me = this;

        me.callParent([config]);
        me.mainGroup = me.createSvgNode("g");
        me.defsElement = me.createSvgNode("defs");
        // me.svgElement is assigned in element creation of Ext.Component.
        me.svgElement.appendChild(me.mainGroup);
        me.svgElement.appendChild(me.defsElement);
        me.ctx = new Ext.draw.engine.SvgContext(me);
    },

    /**
     * Creates a DOM element under the SVG namespace of the given type.
     * @param {String} type The type of the SVG DOM element.
     * @return {*} The created element.
     */
    createSvgNode: function(type) {
        var node = document.createElementNS("http://www.w3.org/2000/svg", type);

        return Ext.get(node);
    },

    /**
     * @private
     * Returns the SVG DOM element at the given position.
     * If it does not already exist or is a different element tag,
     * it will be created and inserted into the DOM.
     * @param {Ext.dom.Element} group The parent DOM element.
     * @param {String} tag The SVG element tag.
     * @param {Number} position The position of the element in the DOM.
     * @return {Ext.dom.Element} The SVG element.
     */
    getSvgElement: function(group, tag, position) {
        var childNodes = group.dom.childNodes,
            length = childNodes.length,
            element;

        if (position < length) {
            element = childNodes[position];

            if (element.tagName === tag) {
                return Ext.get(element);
            }
            else {
                Ext.destroy(element);
            }
        }
        else if (position > length) {
            Ext.raise("Invalid position.");
        }

        element = Ext.get(this.createSvgNode(tag));

        if (position === 0) {
            group.insertFirst(element);
        }
        else {
            element.insertAfter(Ext.fly(childNodes[position - 1]));
        }

        element.cache = {};

        return element;
    },

    /**
     * @private
     * Applies attributes to the given element.
     * @param {Ext.dom.Element} element The DOM element to be applied.
     * @param {Object} attributes The attributes to apply to the element.
     */
    setElementAttributes: function(element, attributes) {
        var dom = element.dom,
            cache = element.cache,
            name, value;

        for (name in attributes) {
            value = attributes[name];

            if (cache[name] !== value) {
                cache[name] = value;
                dom.setAttribute(name, value);
            }
        }
    },

    /**
     * @private
     * Gets the next reference element under the SVG 'defs' tag.
     * @param {String} tagName The type of reference element.
     * @return {Ext.dom.Element} The reference element.
     */
    getNextDef: function(tagName) {
        return this.getSvgElement(this.defsElement, tagName, this.defsPosition++);
    },

    /**
     * @method clearTransform
     * @inheritdoc
     */
    clearTransform: function() {
        var me = this;

        me.mainGroup.set({ transform: me.matrix.toSvg() });
    },

    /**
     * @method clear
     * @inheritdoc
     */
    clear: function() {
        this.ctx.clear();
        this.removeSurplusDefs();
        this.defsPosition = 0;
    },

    removeSurplusDefs: function() {
        var defsElement = this.defsElement,
            defs = defsElement.dom.childNodes,
            ln = defs.length,
            i;

        for (i = ln - 1; i > this.defsPosition; i--) {
            defsElement.removeChild(defs[i]);
        }
    },

    /**
     * @method renderSprite
     * @inheritdoc
     */
    renderSprite: function(sprite) {
        var me = this,
            rect = me.getRect(),
            ctx = me.ctx;

        // This check is simplistic, but should result in a better performance
        // compared to !sprite.isVisible() when most surface sprites are visible.
        if (sprite.attr.hidden || sprite.attr.globalAlpha === 0) {
            // Create an empty group for each hidden sprite,
            // so that when these sprites do become visible,
            // they don't need groups to be created and don't
            // mess up the previous order of elements in the
            // document, i.e. sprites rendered in the next
            // frame reuse the same elements they used in the
            // previous frame.
            ctx.save();
            ctx.restore();

            return;
        }

        // Each sprite is rendered in its own group ('g' element),
        // returned by the `ctx.save` method.
        // Essentially, the group _is_ the sprite.
        sprite.element = ctx.save();
        sprite.preRender(this);
        sprite.useAttributes(ctx, rect);

        if (false === sprite.render(this, ctx, [0, 0, rect[2], rect[3]])) {
            return false;
        }

        sprite.setDirty(false);
        ctx.restore();
    },

    /**
     * @private
     */
    toSVG: function(size, surfaces) {
        var className = Ext.getClassName(this),
            svg, surface, rect, i;

        svg = '<svg version="1.1" baseProfile="full" xmlns="http://www.w3.org/2000/svg"' +
            ' width="' + size.width + '"' +
            ' height="' + size.height + '">';

        for (i = 0; i < surfaces.length; i++) {
            surface = surfaces[i];

            if (Ext.getClassName(surface) !== className) {
                continue;
            }

            rect = surface.getRect();
            svg += '<g transform="translate(' + rect[0] + ',' + rect[1] + ')">';
            svg += this.serializeNode(surface.svgElement.dom);
            svg += '</g>';
        }

        svg += '</svg>';

        return svg;
    },

    b64EncodeUnicode: function(str) {
        // Since DOMStrings are 16-bit-encoded strings, in most browsers calling window.btoa
        // on a Unicode string will cause a Character Out Of Range exception if a character
        // exceeds the range of a 8-bit ASCII-encoded character. More information:
        // https://developer.mozilla.org/en/docs/Web/API/WindowBase64/Base64_encoding_and_decoding
        return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function(match, p1) {
            return String.fromCharCode('0x' + p1);
        }));
    },

    flatten: function(size, surfaces) {
        var svg = '<?xml version="1.0" standalone="yes"?>';

        svg += this.toSVG(size, surfaces);

        return {
            data: 'data:image/svg+xml;base64,' + this.b64EncodeUnicode(svg),
            type: 'svg'
        };
    },

    /**
     * @private
     * Serializes an SVG DOM element and its children recursively into a string.
     * @param {Object} node DOM element to serialize.
     * @return {String}
     */
    serializeNode: function(node) {
        var result = '',
            i, n, attr, child;

        if (node.nodeType === document.TEXT_NODE) {
            return node.nodeValue;
        }

        result += '<' + node.nodeName;

        if (node.attributes.length) {
            for (i = 0, n = node.attributes.length; i < n; i++) {
                attr = node.attributes[i];
                result += ' ' + attr.name + '="' + Ext.String.htmlEncode(attr.value) + '"';
            }
        }

        result += '>';

        if (node.childNodes && node.childNodes.length) {
            for (i = 0, n = node.childNodes.length; i < n; i++) {
                child = node.childNodes[i];
                result += this.serializeNode(child);
            }
        }

        result += '</' + node.nodeName + '>';

        return result;
    },

    /**
     * Destroys the Canvas element and prepares it for Garbage Collection.
     */
    destroy: function() {
        var me = this;

        me.ctx.destroy();
        me.mainGroup.destroy();
        me.defsElement.destroy();

        delete me.mainGroup;
        delete me.defsElement;
        delete me.ctx;

        me.callParent();
    },

    remove: function(sprite, destroySprite) {
        if (sprite && sprite.element) {
            // If sprite has an associated SVG element, remove it from the surface.
            sprite.element.destroy();
            sprite.element = null;
        }

        this.callParent(arguments);
    }
});
