/**
 * @class Ext.fx.target.Element
 * 
 * This class represents a animation target for an {@link Ext.dom.Element}. In general this class
 * will not be created directly, the {@link Ext.dom.Element} will be passed to the animation and
 * and the appropriate target will be created.
 */
Ext.define('Ext.fx.target.Element', {
    extend: 'Ext.fx.target.Target',

    type: 'element',

    constructor: function(target) {
        this.callParent([target]);

        // Allow simple local left/top style setting for top level absolute positioned elements.
        this.isAbsoluteOnPage = this.target.dom.parentNode === document.body &&
                                this.target.isStyle('position', 'absolute');
    },

    getElVal: function(el, attr, val) {
        if (val === undefined) {
            if (attr === 'x') {
                val = el.getX();
            }
            else if (attr === 'y') {
                val = el.getY();
            }
            else if (attr === 'scrollTop') {
                val = el.getScroll().top;
            }
            else if (attr === 'scrollLeft') {
                val = el.getScroll().left;
            }
            else if (attr === 'height') {
                val = el.getHeight();
            }
            else if (attr === 'width') {
                val = el.getWidth();
            }
            else {
                val = el.getStyle(attr);
            }
        }

        return val;
    },

    getAttr: function(attr, val) {
        var el = this.target;

        return [[ el, this.getElVal(el, attr, val)]];
    },

    setAttr: function(targetData) {
        var ln = targetData.length,
            attrs, attr, o, i, j, ln2;

        for (i = 0; i < ln; i++) {
            attrs = targetData[i].attrs;

            for (attr in attrs) {
                if (attrs.hasOwnProperty(attr)) {
                    ln2 = attrs[attr].length;

                    for (j = 0; j < ln2; j++) {
                        o = attrs[attr][j];
                        this.setElVal(o[0], attr, o[1]);
                    }
                }
            }
        }
    },

    setElVal: function(element, attr, value) {
        if (attr === 'x') {
            if (this.isAbsoluteOnPage) {
                element.setLocalX(value);
            }
            else {
                element.setXY([value, null]);
            }
        }
        else if (attr === 'y') {
            if (this.isAbsoluteOnPage) {
                element.setLocalY(value);
            }
            else {
                element.setXY([null, value]);
            }
        }
        else if (attr === 'scrollTop') {
            element.scrollTo('top', value);
        }
        else if (attr === 'scrollLeft') {
            element.scrollTo('left', value);
        }
        else if (attr === 'width') {
            element.setWidth(value);
        }
        else if (attr === 'height') {
            element.setHeight(value);
        }
        else {
            element.setStyle(attr, value);
        }
    }
});
