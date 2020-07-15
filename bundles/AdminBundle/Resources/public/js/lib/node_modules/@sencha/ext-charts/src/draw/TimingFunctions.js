/**
 * @class Ext.draw.TimingFunctions
 * @singleton
 * 
 * Singleton that provides easing functions for use in sprite animations.
 */
Ext.define('Ext.draw.TimingFunctions', function() {

    var pow = Math.pow,
        sin = Math.sin,
        cos = Math.cos,
        sqrt = Math.sqrt,
        pi = Math.PI,
        poly = ['quad', 'cube', 'quart', 'quint'],
        easings = {
            pow: function(p, x) {
                return pow(p, x || 6);
            },

            expo: function(p) {
                return pow(2, 8 * (p - 1));
            },

            circ: function(p) {
                return 1 - sqrt(1 - p * p);
            },

            sine: function(p) {
                return 1 - sin((1 - p) * pi / 2);
            },

            back: function(p, n) {
                n = n || 1.616;

                return p * p * ((n + 1) * p - n);
            },

            bounce: function(p) {
                var a, b;

                // eslint-disable-next-line no-constant-condition
                for (a = 0, b = 1; 1; a += b, b /= 2) {
                    if (p >= (7 - 4 * a) / 11) {
                        return b * b - pow((11 - 6 * a - 11 * p) / 4, 2);
                    }
                }
            },

            elastic: function(p, x) {
                return pow(2, 10 * --p) * cos(20 * p * pi * (x || 1) / 3);
            }
        },
        easingsMap = {},
        name, len, i;

    // Create polynomial easing equations.
    function createPoly(times) {
        return function(p) {
            return pow(p, times);
        };
    }

    function addEasing(name, easing) {
        easingsMap[name + 'In'] = function(pos) {
            return easing(pos);
        };

        easingsMap[name + 'Out'] = function(pos) {
            return 1 - easing(1 - pos);
        };

        easingsMap[name + 'InOut'] = function(pos) {
            return (pos <= 0.5) ? easing(2 * pos) / 2 : (2 - easing(2 * (1 - pos))) / 2;
        };
    }

    for (i = 0, len = poly.length; i < len; ++i) {
        easings[poly[i]] = createPoly(i + 2);
    }

    for (name in easings) {
        addEasing(name, easings[name]);
    }

    // Add linear interpolator.
    easingsMap.linear = Ext.identityFn;

    // Add aliases for quad easings.
    easingsMap.easeIn = easingsMap.quadIn;
    easingsMap.easeOut = easingsMap.quadOut;
    easingsMap.easeInOut = easingsMap.quadInOut;

    return {
        singleton: true,
        easingMap: easingsMap
    };

}, function(Cls) {
    Ext.apply(Cls, Cls.easingMap);
});
