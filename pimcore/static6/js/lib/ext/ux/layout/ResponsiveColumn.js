/**
 * A simple grid-like layout for proportionally dividing container space and allocating it
 * to each item. All items in this layout are given one or more percentage sizes and CSS
 * `float:left` is used to provide the wrapping.
 *
 * To select which of the percentage sizes an item uses, this layout adds a viewport
 * {@link #states size-dependent} class name to the container. The style sheet must
 * provide the rules to select the desired size using the {@link #responsivecolumn-item}
 * mixin.
 *
 * For example, a panel in a responsive column layout might add the following styles:
 *
 *      .my-panel {
 *          // consume 50% of the available space inside the container by default
 *          @include responsivecolumn-item(50%);
 *
 *          .x-responsivecolumn-small & {
 *              // consume 100% of available space in "small" mode
 *              // (viewport width < 1000 by default)
 *              @include responsivecolumn-item(100%);
 *          }
 *      }
 *
 * Alternatively, instead of targeting specific panels in CSS, you can create reusable
 * classes:
 *
 *      .big-50 {
 *          // consume 50% of the available space inside the container by default
 *          @include responsivecolumn-item(50%);
 *      }
 *
 *      .x-responsivecolumn-small {
 *          > .small-100 {
 *              @include responsivecolumn-item(100%);
 *          }
 *      }
 *
 * These can be added to components in the layout using the `responsiveCls` config:
 *
 *      items: [{
 *          xtype: 'my-panel',
 *
 *          // Use 50% of space when viewport is "big" and 100% when viewport
 *          // is "small":
 *          responsiveCls: 'big-50 small-100'
 *      }]
 *
 * The `responsiveCls` config is provided by this layout to avoid overwriting classes
 * specified using `cls` or other standard configs.
 *
 * Internally, this layout simply uses `float:left` and CSS `calc()` (except on IE8) to
 * "flex" each item. The calculation is always based on a percentage with a spacing taken
 * into account to separate the items from each other.
 */
Ext.define('Ext.ux.layout.ResponsiveColumn', {
    extend: 'Ext.layout.container.Auto',
    alias: 'layout.responsivecolumn',

    /**
     * @cfg {Object} states
     *
     * A set of layout state names corresponding to viewport size thresholds. One of the
     * states will be used to assign the responsive column CSS class to the container to
     * trigger appropriate item sizing.
     *
     * For example:
     *
     *      layout: {
     *          type: 'responsivecolumn',
     *          states: {
     *              small: 800,
     *              medium: 1200,
     *              large: 0
     *          }
     *      }
     *
     * Given the above set of responsive states, one of the following CSS classes will be
     * added to the container:
     *
     *   - `x-responsivecolumn-small` - If the viewport is <= 800px
     *   - `x-responsivecolumn-medium` - If the viewport is > 800px and <= 1200px
     *   - `x-responsivecolumn-large` - If the viewport is > 1200px
     *
     * For sake of efficiency these classes are based on the size of the browser viewport
     * (the browser window) and not on the container size. As the size of the viewport
     * changes, this layout will maintain the appropriate CSS class on the container which
     * will then activate the appropriate CSS rules to size the child items.
     */
    states: {
        small: 1000,
        large: 0
    },

    _responsiveCls: Ext.baseCSSPrefix + 'responsivecolumn',

    initLayout: function () {
        this.innerCtCls += ' ' + this._responsiveCls;
        this.callParent();
    },

    beginLayout: function (ownerContext) {
        var me = this,
            viewportWidth = Ext.Element.getViewportWidth(),
            states = me.states,
            activeThreshold = Infinity,
            innerCt = me.innerCt,
            currentState = me._currentState,
            name, threshold, newState;

        for (name in states) {
            threshold = states[name] || Infinity;

            if (viewportWidth <= threshold && threshold <= activeThreshold) {
                activeThreshold = threshold;
                newState = name;
            }
        }

        if (newState !== currentState) {
            innerCt.replaceCls(currentState, newState, me._responsiveCls);
            me._currentState = newState;
        }

        me.callParent(arguments);
    },

    onAdd: function (item) {
        this.callParent([item]);

        var responsiveCls = item.responsiveCls;

        if (responsiveCls) {
            item.addCls(responsiveCls);
        }
    }
},
//--------------------------------------------------------------------------------------
// IE8 does not support CSS calc expressions, so we have to fallback to more traditional
// for of layout. This is very similar but much simpler than Column layout.
//
function (Responsive) {
    if (Ext.isIE8) {
        Responsive.override({
            responsiveSizePolicy: {
                readsWidth: 0,
                readsHeight: 0,
                setsWidth: 1,
                setsHeight: 0
            },

            setsItemSize: true,

            calculateItems: function (ownerContext, containerSize) {
                var me = this,
                    targetContext = ownerContext.targetContext,
                    items = ownerContext.childItems,
                    len = items.length,
                    gotWidth = containerSize.gotWidth,
                    contentWidth = containerSize.width,
                    blocked, availableWidth, i, itemContext, itemMarginWidth, itemWidth;

                // No parallel measurement, cannot lay out boxes.
                if (gotWidth === false) {
                    targetContext.domBlock(me, 'width');
                    return false;;
                }
                if (!gotWidth) {
                    // gotWidth is undefined, which means we must be width shrink wrap.
                    // Cannot calculate item widths if we're shrink wrapping.
                    return true;
                }

                for (i = 0; i < len; ++i) {
                    itemContext = items[i];

                    // The mixin encodes these in background-position syles since it is
                    // unlikely a component will have a background-image.
                    itemWidth = parseInt(itemContext.el.getStyle('background-position-x'), 10);
                    itemMarginWidth = parseInt(itemContext.el.getStyle('background-position-y'), 10);

                    itemContext.setWidth((itemWidth / 100 * (contentWidth - itemMarginWidth)) - itemMarginWidth);
                }

                ownerContext.setContentWidth(contentWidth +
                    ownerContext.paddingContext.getPaddingInfo().width);

                return true;
            },

            getItemSizePolicy: function () {
                return this.responsiveSizePolicy;
            }
        });
    }
});
