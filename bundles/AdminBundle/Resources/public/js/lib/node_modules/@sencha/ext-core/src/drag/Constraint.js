/**
 * Provides constraining behavior for a {@link Ext.drag.Source}.
 */
Ext.define('Ext.drag.Constraint', {
    alias: 'drag.constraint.base',
    mixins: [
        'Ext.mixin.Factoryable'
    ],

    factoryConfig: {
        defaultType: 'base',
        type: 'drag.constraint'
    },

    config: {
        /**
         * @cfg {Boolean/String/HTMLElement/Ext.dom.Element} element
         *
         * The element to constrain to:
         * - `true` to constrain to the parent of the {@link Ext.drag.Source#element}.
         * - The id, DOM element or Ext.dom.Element to constrain to.
         */
        element: null,

        /**
         * @cfg {Boolean} horizontal
         * `true` to limit dragging to the horizontal axis.
         */
        horizontal: null,

        /**
         * @cfg {Ext.util.Region} region
         *
         * The region to constrain to.
         */
        region: null,

        /**
         * @cfg {Number/Object} snap
         * The interval to move this drag target during a drag in both dimensions.
         * - `{x: 30}`, snap only x
         * - `{y: 30}`, snap only y
         * - `{x: 30, y: 40}`, snap both
         * - `40`, snap both to `40`.
         *
         * The snap may also be a function to calculate the snap value on each tick.
         *
         *      snap: {
         *          x: function(info, x) {
         *              return x < 300 ? 150 : 500;
         *          }
         *      }
         */
        snap: null,

        /**
         * @cfg {Ext.drag.Source} source
         * The {@link Ext.drag.Source source} for the constraint. This will be
         * set automatically when constructed via the source.
         */
        source: null,

        /**
         * @cfg {Boolean} vertical
         * `true` to limit dragging to the vertical axis.
         */
        vertical: null,

        /**
         * @cfg {Number[]} x
         * The minimum and maximum x position. Use `null` to
         * not set a constraint:
         * - `[100, null]`, constrain only the minimum
         * - `[null, 100]`, constrain only the maximum
         * - `[200, 200]`, constrain both.
         */
        x: null,

        /**
         * @cfg {Number[]} y
         * The minimum and maximum y position. Use `null` to
         * not set a constraint:
         * - `[100, null]`, constrain only the minimum
         * - `[null, 100]`, constrain only the maximum
         * - `[200, 200]`, constrain both.
         */
        y: null
    },

    constructor: function(config) {
        this.initConfig(config);
    },

    applyElement: function(element) {
        if (element && typeof element !== 'boolean') {
            element = Ext.get(element);
        }

        return element || null;
    },

    applySnap: function(snap) {
        if (typeof snap === 'number') {
            snap = {
                x: snap,
                y: snap
            };
        }

        return snap;
    },

    /**
     * Constrain the position of the drag proxy using the configured rules.
     *
     * @param {Number[]} xy The position.
     * @param {Ext.drag.Info} info The drag information.
     *
     * @return {Number[]} The xy position.
     */
    constrain: function(xy, info) {
        var me = this,
            x = xy[0],
            y = xy[1],
            constrainInfo = me.constrainInfo,
            initial = constrainInfo.initial,
            constrainX = constrainInfo.x,
            constrainY = constrainInfo.y,
            snap = constrainInfo.snap,
            min, max;

        if (!constrainInfo.vertical) {
            if (snap && snap.x) {
                if (snap.xFn) {
                    x = snap.x.call(me, info, x);
                }
                else {
                    x = me.doSnap(x, initial.x, snap.x);
                }
            }

            if (constrainX) {
                min = constrainX[0];
                max = constrainX[1];

                if (min !== null && x < min) {
                    x = min;
                }

                if (max !== null && x > max) {
                    x = max;
                }
            }
        }
        else {
            x = initial.x;
        }

        if (!constrainInfo.horizontal) {
            if (snap && snap.y) {
                if (snap.yFn) {
                    y = snap.y.call(me, info, y);
                }
                else {
                    y = me.doSnap(y, initial.y, snap.y);
                }
            }

            if (constrainY) {
                min = constrainY[0];
                max = constrainY[1];

                if (min !== null && y < min) {
                    y = min;
                }

                if (max !== null && y > max) {
                    y = max;
                }
            }
        }
        else {
            y = initial.y;
        }

        return [x, y];
    },

    destroy: function() {
        this.setSource(null);
        this.setElement(null);
        this.callParent();
    },

    privates: {
        /**
         * Constrains 2 values, while taking into
         * account nulls.
         * @param {Number} a The first value.
         * @param {Number} b The second value.
         * @param {Function} resolver The function to resolve the value if
         * both are non null.
         * @return {Number} If both values are `null`, `null`. If `a` is null, `b`.
         * If `b` is null, `a`, otherwise the result of the resolver, passing a & b.
         *
         * @private
         */
        constrainValue: function(a, b, resolver) {
            var val = null,
                aNull = a === null,
                bNull = b === null;

            if (!(aNull && bNull)) {
                if (aNull) {
                    val = b;
                }
                else if (bNull) {
                    val = a;
                }
                else {
                    val = resolver(a, b);
                }
            }

            return val;
        },

        /**
         * Calculates the position to move the proxy element
         * to when using snapping.
         *
         * @param {Number} position The current mouse position.
         * @param {Number} initial The start position.
         * @param {Number} snap The snap position.
         * @return {Number} The snapped position.
         *
         * @private
         */
        doSnap: function(position, initial, snap) {
            if (!snap) {
                return position;
            }

            /* eslint-disable-next-line vars-on-top */
            var ratio = (position - initial) / snap,
                floor = Math.floor(ratio);

            // Check whether we need to snap less than current, or
            // greater than current position.
            if (ratio - floor <= 0.5) {
                ratio = floor;
            }
            else {
                ratio = floor + 1;
            }

            return initial + (snap * ratio);
        },

        /**
         * Setup data that is needed during a drag for monitoring constraints.
         * Attempt to merge min/max values with any constrain region.
         *
         * @param {Ext.drag.Info} info The drag info.
         *
         * @private
         */
        onDragStart: function(info) {
            var me = this,
                snap = me.getSnap(),
                vertical = !!me.getVertical(),
                horizontal = !!me.getHorizontal(),
                element = me.getElement(),
                region = me.getRegion(),
                proxy = info.proxy,
                proxyEl = proxy.element,
                x = me.getX(),
                y = me.getY(),
                minX = null,
                maxX = null,
                minY = null,
                maxY = null,
                rminX = null,
                rmaxX = null,
                rminY = null,
                rmaxY = null,
                pos, size;

            if (element) {
                if (typeof element === 'boolean') {
                    element = me.getSource().getElement().parent();
                }

                if (info.local) {
                    pos = element.getStyle('position');

                    if (pos === 'relative' || pos === 'absolute') {
                        size = element.getSize();
                        region = new Ext.util.Region(0, size.width, size.height, 0);
                    }
                    else {
                        region = element.getRegion(true, true);
                    }
                }
                else {
                    region = element.getRegion(true);
                }
            }

            if (region) {
                if (!vertical) {
                    rminX = region.left;
                    rmaxX = region.right - (proxyEl ? proxy.width : 0);
                }

                if (!horizontal) {
                    rminY = region.top;
                    rmaxY = region.bottom - (proxyEl ? proxy.height : 0);
                }
            }

            // The following piece sets up the numeric values for our constraint.
            // If there is an axis constraint, don't bother calculating the values since
            // it is already explicitly constrained so we can shortcut that portion.
            //
            // Attempt to merge the appropriate min/max values (if needed). With:
            // a) A region and a minimum, the larger value is needed (stricter constraint)
            // b) A region and a maximum, the smaller value is needed (stricter constraint)

            if (!vertical && (region || x)) {
                if (x) {
                    minX = x[0];
                    maxX = x[1];
                }

                if (minX !== null || maxX !== null || rminX !== null || rmaxX !== null) {
                    minX = me.constrainValue(minX, rminX, Math.max);
                    maxX = me.constrainValue(maxX, rmaxX, Math.min);
                    x = [minX, maxX];
                }
            }

            if (!horizontal && (region || y)) {
                if (y) {
                    minY = y[0];
                    maxY = y[1];
                }

                if (minY !== null || maxY !== null || rminY !== null || rmaxY !== null) {
                    minY = me.constrainValue(minY, rminY, Math.max);
                    maxY = me.constrainValue(maxY, rmaxY, Math.min);
                    y = [minY, maxY];
                }
            }

            if (snap) {
                snap = {
                    x: snap.x,
                    xFn: typeof snap.x === 'function',
                    y: snap.y,
                    yFn: typeof snap.y === 'function'
                };
            }

            me.constrainInfo = {
                initial: info.element.initial,
                vertical: vertical,
                horizontal: horizontal,
                x: x,
                y: y,
                snap: snap
            };
        }
    }
});
