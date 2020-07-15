/**
 * @private
 */
Ext.define('Ext.fx.runner.Css', {
    extend: 'Ext.Evented',

    requires: [
        'Ext.fx.Animation'
    ],

    prefixedProperties: {
        'transform': true,
        'transform-origin': true,
        'perspective': true,
        'transform-style': true,
        'transition': true,
        'transition-property': true,
        'transition-duration': true,
        'transition-timing-function': true,
        'transition-delay': true,
        'animation': true,
        'animation-name': true,
        'animation-duration': true,
        'animation-iteration-count': true,
        'animation-direction': true,
        'animation-timing-function': true,
        'animation-delay': true
    },

    lengthProperties: {
        'top': true,
        'right': true,
        'bottom': true,
        'left': true,
        'width': true,
        'height': true,
        'max-height': true,
        'max-width': true,
        'min-height': true,
        'min-width': true,
        'margin-bottom': true,
        'margin-left': true,
        'margin-right': true,
        'margin-top': true,
        'padding-bottom': true,
        'padding-left': true,
        'padding-right': true,
        'padding-top': true,
        'border-bottom-width': true,
        'border-left-width': true,
        'border-right-width': true,
        'border-spacing': true,
        'border-top-width': true,
        'border-width': true,
        'outline-width': true,
        'letter-spacing': true,
        'line-height': true,
        'text-indent': true,
        'word-spacing': true,
        'font-size': true,
        'translate': true,
        'translateX': true,
        'translateY': true,
        'translateZ': true,
        'translate3d': true,
        'x': true,
        'y': true
    },

    durationProperties: {
        'transition-duration': true,
        'transition-delay': true,
        'animation-duration': true,
        'animation-delay': true
    },

    angleProperties: {
        rotate: true,
        rotateX: true,
        rotateY: true,
        rotateZ: true,
        skew: true,
        skewX: true,
        skewY: true
    },

    DEFAULT_UNIT_LENGTH: 'px',

    DEFAULT_UNIT_ANGLE: 'deg',

    DEFAULT_UNIT_DURATION: 'ms',

    customProperties: {
        x: true,
        y: true
    },

    formattedNameCache: {
        'x': 'left',
        'y': 'top'
    },

    transformMethods3d: [
        'translateX',
        'translateY',
        'translateZ',
        'rotate',
        'rotateX',
        'rotateY',
        'rotateZ',
        'skewX',
        'skewY',
        'scaleX',
        'scaleY',
        'scaleZ'
    ],

    transformMethodsNo3d: [
        'translateX',
        'translateY',
        'rotate',
        'skewX',
        'skewY',
        'scaleX',
        'scaleY'
    ],

    constructor: function() {
        var me = this;

        me.transformMethods = Ext.feature.has.Css3dTransforms
            ? me.transformMethods3d
            : me.transformMethodsNo3d;

        me.vendorPrefix = Ext.browser.getStyleDashPrefix();
        me.ruleStylesCache = {};

        me.callParent();
    },

    getStyleSheet: function() {
        var styleSheet = this.styleSheet,
            styleElement, styleSheets;

        if (!styleSheet) {
            styleElement = document.createElement('style');
            styleElement.type = 'text/css';

            (document.head || document.getElementsByTagName('head')[0]).appendChild(styleElement);

            styleSheets = document.styleSheets;

            this.styleSheet = styleSheet = styleSheets[styleSheets.length - 1];
        }

        return styleSheet;
    },

    applyRules: function(selectors) {
        var styleSheet = this.getStyleSheet(),
            ruleStylesCache = this.ruleStylesCache,
            rules = styleSheet.cssRules,
            selector, properties, ruleStyle,
            ruleStyleCache, rulesLength, name, value;

        for (selector in selectors) {
            properties = selectors[selector];

            ruleStyle = ruleStylesCache[selector];

            if (ruleStyle === undefined) {
                rulesLength = rules.length;
                styleSheet.insertRule(selector + '{}', rulesLength);
                ruleStyle = ruleStylesCache[selector] = rules.item(rulesLength).style;
            }

            ruleStyleCache = ruleStyle.$cache;

            if (!ruleStyleCache) {
                ruleStyleCache = ruleStyle.$cache = {};
            }

            for (name in properties) {
                value = this.formatValue(properties[name], name);
                name = this.formatName(name);

                if (ruleStyleCache[name] !== value) {
                    ruleStyleCache[name] = value;

                    if (value === null) {
                        ruleStyle.removeProperty(name);
                    }
                    else {
                        ruleStyle.setProperty(name, value);
                    }
                }
            }
        }

        return this;
    },

    applyStyles: function(styles) {
        var id, element, elementStyle, properties, name, value;

        for (id in styles) {
            if (styles.hasOwnProperty(id)) {
                this.activeElement = element = document.getElementById(id);

                if (!element) {
                    continue;
                }

                elementStyle = element.style;

                properties = styles[id];

                for (name in properties) {
                    if (properties.hasOwnProperty(name)) {
                        value = this.formatValue(properties[name], name);
                        name = this.formatName(name);

                        if (value === null) {
                            elementStyle.removeProperty(name);
                        }
                        else {
                            elementStyle.setProperty(name, value);
                        }
                    }
                }
            }
        }

        this.activeElement = null;

        return this;
    },

    formatName: function(name) {
        var cache = this.formattedNameCache,
            formattedName = cache[name];

        if (!formattedName) {
            if ((Ext.os.is.Tizen || !Ext.feature.has.CssTransformNoPrefix) &&
                this.prefixedProperties[name]) {
                formattedName = this.vendorPrefix + name;
            }
            else {
                formattedName = name;
            }

            cache[name] = formattedName;
        }

        return formattedName;
    },

    formatValue: function(value, name) {
        var type = typeof value,
            defaultLengthUnit = this.DEFAULT_UNIT_LENGTH,
            isCustom = this.customProperties[name],
            transformMethods,
            method, i, ln,
            transformValues, values;

        if (value === null) {
            return '';
        }

        if (type === 'string') {
            if (this.lengthProperties[name]) {
                if (!Ext.dom.Element.hasUnit(value)) {
                    value = value + defaultLengthUnit;

                    if (isCustom) {
                        value = this.getCustomValue(value, name);
                    }
                }
            }

            return value;
        }
        else if (type === 'number') {
            if (value === 0) {
                return '0';
            }

            if (this.lengthProperties[name]) {
                value = value + defaultLengthUnit;

                if (isCustom) {
                    value = this.getCustomValue(value, name);
                }

                return value;
            }

            if (this.angleProperties[name]) {
                return value + this.DEFAULT_UNIT_ANGLE;
            }

            if (this.durationProperties[name]) {
                return value + this.DEFAULT_UNIT_DURATION;
            }
        }
        else if (name === 'transform') {
            transformMethods = this.transformMethods;
            transformValues = [];

            for (i = 0, ln = transformMethods.length; i < ln; i++) {
                method = transformMethods[i];

                transformValues.push(method + '(' + this.formatValue(value[method], method) + ')');
            }

            return transformValues.join(' ');
        }
        else if (Ext.isArray(value)) {
            values = [];

            for (i = 0, ln = value.length; i < ln; i++) {
                values.push(this.formatValue(value[i], name));
            }

            return (values.length > 0) ? values.join(', ') : 'none';
        }

        return value;
    },

    getCustomValue: function(value, name) {
        var el = Ext.fly(this.activeElement);

        if (name === 'x') {
            value = el.translateXY(parseInt(value, 10)).x;
        }
        else if (name === 'y') {
            value = el.translateXY(null, parseInt(value, 10)).y;
        }

        return value + this.DEFAULT_UNIT_LENGTH;
    }
});
