/**
 * A drag proxy that creates a new element to follow the cursor.
 */
Ext.define('Ext.drag.proxy.Placeholder', {
    extend: 'Ext.drag.proxy.None',
    alias: 'drag.proxy.placeholder',

    config: {
        /**
         * @cfg {String} cls
         * A class for this proxy.
         */
        cls: '',

        /**
         * @cfg {Number[]} cursorOffset
         * Determines the position of the proxy in relation
         * to the cursor.
         */
        cursorOffset: [12, 20],

        /**
         * @cfg {String} html
         * The html for this proxy.
         */
        html: null,

        /**
         * @cfg {String} invalidCls
         * A class to add to this proxy when over an
         * invalid {@link Ext.drag.Target target}.
         */
        invalidCls: '',

        /**
         * @cfg {String} validCls
         * A class to add to this proxy when over a
         * valid {@link Ext.drag.Target target}.
         */
        validCls: ''
    },

    placeholderCls: Ext.baseCSSPrefix + 'drag-proxy-placeholder',

    /**
     * @method cleanup
     * @inheritdoc
     */
    cleanup: function() {
        this.element = Ext.destroy(this.element);
    },

    /**
     * @method getElement
     * @inheritdoc
     */
    getElement: function() {
        var el = Ext.getBody().createChild({
            cls: this.getCls(),
            html: this.getHtml()
        });

        el.addCls(this.placeholderCls);

        el.setTouchAction({
            panX: false,
            panY: false
        });

        return el;
    },

    /**
     * @method update
     * @inheritdoc
     */
    update: function(info) {
        var el = this.element,
            invalidCls = this.getInvalidCls(),
            validCls = this.getValidCls(),
            valid = info.valid;

        if (info.target) {
            // If we are valid, replace the invalidCls with the validCls.
            // Otherwise do the reverse
            el.replaceCls(valid ? invalidCls : validCls, valid ? validCls : invalidCls);
        }
        else {
            el.removeCls([invalidCls, validCls]);
        }
    },

    updateCls: function(cls, oldCls) {
        var el = this.element;

        if (el) {
            if (oldCls) {
                el.removeCls(oldCls);
            }

            if (cls) {
                el.addCls(cls);
            }
        }
    },

    updateHtml: function(html) {
        var el = this.element;

        if (el) {
            el.setHtml(html || '');
        }
    },

    updateInvalidCls: function(invalidCls, oldInvalidCls) {
        this.doUpdateCls(invalidCls, oldInvalidCls);
    },

    updateValidCls: function(validCls, oldValidCls) {
        this.doUpdateCls(validCls, oldValidCls);
    },

    destroy: function() {
        this.element = Ext.destroy(this.element);

        this.callParent();
    },

    privates: {
        /**
         * @method adjustCursorOffset
         * @inheritdoc
         */
        adjustCursorOffset: function(info, xy) {
            var offset = this.getCursorOffset();

            if (offset) {
                xy[0] += (offset[0] || 0);
                xy[1] += (offset[1] || 0);
            }

            return xy;
        },

        /**
         * Removes a class and replaces it with a new one, if the old class
         * was already on the element.
         * 
         * @param {String} cls The new class to add.
         * @param {String} oldCls The old class to remove.
         *
         * @private
         */
        doUpdateCls: function(cls, oldCls) {
            var el = this.element,
                hasCls;

            if (el) {
                if (oldCls) {
                    hasCls = cls && el.hasCls(oldCls);
                    el.removeCls(oldCls);
                }

                if (cls && hasCls) {
                    el.addCls(cls);
                }
            }
        }
    }
});
