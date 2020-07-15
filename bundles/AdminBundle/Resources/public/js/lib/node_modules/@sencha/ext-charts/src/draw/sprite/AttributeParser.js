/* global Float32Array */
/**
 * @private
 * @class Ext.draw.sprite.AttributeParser
 *
 * Parsers used for sprite attributes if they are
 * {@link Ext.draw.sprite.AttributeDefinition#normalize normalized} (default) when being
 * {@link Ext.draw.sprite.Sprite#setAttributes set}.
 *
 * Methods of the singleton correpond either to the processor functions themselves or processor
 * factories.
 */
Ext.define('Ext.draw.sprite.AttributeParser', {
    singleton: true,
    attributeRe: /^url\(#([a-zA-Z-]+)\)$/,
    requires: [
        'Ext.draw.Color',
        'Ext.draw.gradient.GradientDefinition'
    ],

    'default': Ext.identityFn,

    string: function(n) {
        return String(n);
    },

    number: function(n) {
        // Numbers as strings will be converted to numbers,
        // null will be converted to 0.
        if (Ext.isNumber(+n)) {
            return n;
        }
    },

    /**
     * Normalize angle to the [-180,180) interval.
     * @param n Angle in radians.
     * @return {Number/undefined} Normalized angle or undefined.
     */
    angle: function(n) {
        if (Ext.isNumber(n)) {
            n %= Math.PI * 2;

            if (n < -Math.PI) {
                n += Math.PI * 2;
            }
            else if (n >= Math.PI) {
                n -= Math.PI * 2;
            }

            return n;
        }
    },

    data: function(n) {
        if (Ext.isArray(n)) {
            return n.slice();
        }
        else if (n instanceof Float32Array) {
            return new Float32Array(n);
        }
    },

    bool: function(n) {
        return !!n;
    },

    color: function(n) {
        if (n && n.isColor) {
            return n.toString();
        }
        else if (n && n.isGradient) {
            return n;
        }
        else if (!n) {
            return Ext.util.Color.NONE;
        }
        else if (Ext.isString(n)) {
            if (n.substr(0, 3) === 'url') {
                n = Ext.draw.gradient.GradientDefinition.get(n);

                if (Ext.isString(n)) {
                    return n;
                }
            }
            else {
                return Ext.util.Color.fly(n).toString();
            }
        }

        if (n.type === 'linear') {
            return Ext.create('Ext.draw.gradient.Linear', n);
        }
        else if (n.type === 'radial') {
            return Ext.create('Ext.draw.gradient.Radial', n);
        }
        else if (n.type === 'pattern') {
            return Ext.create('Ext.draw.gradient.Pattern', n);
        }
        else {
            return Ext.util.Color.NONE;
        }
    },

    limited: function(low, hi) {
        return function(n) {
            n = +n;

            return Ext.isNumber(n) ? Math.min(Math.max(n, low), hi) : undefined;
        };
    },

    limited01: function(n) {
        n = +n;

        return Ext.isNumber(n) ? Math.min(Math.max(n, 0), 1) : undefined;
    },

    /**
     * Generates a function that checks if a value matches
     * one of the given attributes.
     * @return {Function}
     */
    enums: function() {
        var enums = {},
            args = Array.prototype.slice.call(arguments, 0),
            i, ln;

        for (i = 0, ln = args.length; i < ln; i++) {
            enums[args[i]] = true;
        }

        return function(n) {
            return n in enums ? n : undefined;
        };
    }
});
