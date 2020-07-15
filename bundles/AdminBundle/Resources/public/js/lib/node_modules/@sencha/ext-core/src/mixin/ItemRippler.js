/**
 * @private
 * @since 6.5.0
 */
Ext.define('Ext.mixin.ItemRippler', {
    mixinId: 'itemrippler',

    config: {
        /**
         * @cfg {Boolean/Object} itemRipple
         * @cfg {String} itemRipple.color The background color of the ripple.
         * Default ripple {@link Ext.Widget#ripple} settings for each item in the data view
         *
         * For complex items, individual elements can suppress ripples by adding the
         * `x-no-ripple` class to disable rippling for a tree of elements.
         * @since 6.5.0
         */
        itemRipple: null
    },

    shouldRippleItem: function(item, e) {
        var itemRipple, ripple;

        if (e.getTarget(this.noItemRippleSelector, this.element)) {
            return false;
        }

        itemRipple = item && this.getItemRipple();

        if (itemRipple && item.isWidget) {
            ripple = item.shouldRipple(e);

            if (ripple) {
                itemRipple = Ext.apply({}, itemRipple, ripple);
            }
        }

        return itemRipple;
    },

    rippleItem: function(item, e) {
        if (!item) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            start = e.type.match(me.rippleStateRe),
            itemRipple = me.shouldRippleItem(item, e),
            release = itemRipple && itemRipple.release,
            isRelease = release === true,
            el = item.isWidget ? item.el : item,
            pos, delta, rs, rippledItems;

        // If this is a release based ripple lets track the start point
        // so we can ignore the ripple if this becomes a drag
        if (itemRipple && start && isRelease) {
            me.$rippleStart = e.getXY();
        }

        // Should we ripple?
        // Do we have a ripple config?
        // Are we in the right event (start or end)?
        // do we have an element to ripple with?
        if (itemRipple && el && ((!start && isRelease) || (start && release !== true))) {
            rippledItems = me.$rippledItems || (me.$rippledItems = []);
            rs = me.$rippleStart;

            if (rs) {
                pos = e.getXY();
                // determine the distance from the start point
                delta = Math.sqrt(Math.pow((pos[0] - rs[0]), 2) +
                                  Math.pow((pos[1] - rs[1]), 2));

                if (delta <= 8) {
                    el.ripple(e, itemRipple);
                    rippledItems.push(el);
                }
            }
            else {
                el.ripple(e, itemRipple);
                rippledItems.push(el);
            }

            me.$rippleStart = null;
        }
    },

    destroyAllRipples: function() {
        var items;

        for (items = this.$rippledItems; items && items.length;) {
            items.pop().destroyAllRipples();
        }
    },

    privates: {
        noItemRippleSelector: '.' + Ext.baseCSSPrefix + 'item-no-ripple, ' +
                        '.' + Ext.baseCSSPrefix + 'item-no-tap',
        rippleStateRe: /start|down/
    }
});
