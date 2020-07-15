/**
 * @private
 */
Ext.define('Ext.layout.container.boxOverflow.Scroller', {
    extend: 'Ext.layout.container.boxOverflow.None',
    alternateClassName: 'Ext.layout.boxOverflow.Scroller',

    alias: [
        'box.overflow.scroller',
        'box.overflow.Scroller' // capitalized for 4.x compat
    ],

    requires: [
        'Ext.util.ClickRepeater',
        'Ext.Element'
    ],

    mixins: {
        observable: 'Ext.mixin.Observable'
    },

    /**
     * @cfg {Boolean} animateScroll
     * True to animate the scrolling of items within the layout (ignored if enableScroll is false)
     */
    animateScroll: false,

    /**
     * @cfg {Number} scrollIncrement
     * The number of pixels to scroll by on scroller click
     */
    scrollIncrement: 20,

    /**
     * @cfg {Number} wheelIncrement
     * The number of pixels to increment on mouse wheel scrolling.
     */
    wheelIncrement: 10,

    /**
     * @cfg {Number} scrollRepeatInterval
     * Number of milliseconds between each scroll while a scroller button is held down
     */
    scrollRepeatInterval: 60,

    /**
     * @cfg {Number} scrollDuration
     * Number of milliseconds that each scroll animation lasts
     */
    scrollDuration: 400,

    /**
     * @private
     */
    scrollerCls: Ext.baseCSSPrefix + 'box-scroller',
    beforeSuffix: '-before-scroller',
    afterSuffix: '-after-scroller',

    /**
     * @event scroll
     * @param {Ext.layout.container.boxOverflow.Scroller} scroller The layout scroller
     * @param {Number} newPosition The new position of the scroller
     */

    constructor: function(config) {
        var me = this;

        me.mixins.observable.constructor.call(me, config);
        me.layout.owner.on({
            afterrender: me.onOwnerRender,
            scope: me,
            single: true
        });
        me.layout.owner.getOverflowEl = me.ownerGetOverflowImpl;

        me.scrollPosition = 0;
        me.scrollSize = 0;
    },

    onOwnerRender: function(owner) {
        var me = this,
            scrollable = {
                isBoxOverflowScroller: true,
                x: false,
                y: false,
                listeners: {
                    scrollend: this.onScrollEnd,
                    scope: this
                }
            };

        // If no obstrusive scrollbars, allow natural scrolling on mobile touch devices
        if (!Ext.scrollbar.width() && !Ext.platformTags.desktop) {
            scrollable[owner.layout.horizontal ? 'x' : 'y'] = true;
        }
        else {
            me.wheelListener = me.layout.innerCt.on(
                'mousewheel', me.onMouseWheel, me, { destroyable: true }
            );
        }

        owner.setScrollable(scrollable);
    },

    getPrefixConfig: function() {
        return {
            role: 'presentation',
            id: this.layout.owner.id + this.beforeSuffix,
            cls: this.createScrollerCls('beforeX'),
            style: 'display:none'
        };
    },

    getSuffixConfig: function() {
        return {
            role: 'presentation',
            id: this.layout.owner.id + this.afterSuffix,
            cls: this.createScrollerCls('afterX'),
            style: 'display:none'
        };
    },

    createScrollerCls: function(xName) {
        var me = this,
            layout = me.layout,
            owner = layout.owner,
            type = me.getOwnerType(owner),
            scrollerCls = me.scrollerCls,
            cls =
                scrollerCls + ' ' +
                scrollerCls + '-' + layout.names[xName] + ' ' +
                scrollerCls + '-' + type + ' ' +
                scrollerCls + '-' + type + '-' + owner.ui;

        if (owner.plain) {
            // Add plain class for components that need separate "plain" styling (e.g. tab bar)
            cls += ' ' + scrollerCls + '-plain';
        }

        return cls;
    },

    getOverflowCls: function(direction) {
        return this.scrollerCls + '-body-' + direction;
    },

    beginLayout: function(ownerContext) {
        ownerContext.innerCtScrollPos = this.getScrollPosition();

        this.callParent(arguments);
    },

    finishedLayout: function(ownerContext) {
        var me = this,
            plan = ownerContext.state.boxPlan,
            layout = me.layout,
            names = layout.names,
            scrollPos = Math.min(me.getMaxScrollPosition(), ownerContext.innerCtScrollPos),
            lastProps;

        // If there is overflow...
        if (plan && plan.tooNarrow) {
            lastProps = ownerContext.childItems[ownerContext.childItems.length - 1].props;

            // capture this before callParent since it calls handle/clearOverflow:
            me.scrollSize = lastProps[names.x] + lastProps[names.width];
            me.updateScrollButtons();

            // Restore pre layout scroll position
            layout.innerCt[names.setScrollLeft](scrollPos);
        }

        me.callParent([ownerContext]);
    },

    handleOverflow: function(ownerContext) {
        var me = this,
            names = me.layout.names,
            getWidth = names.getWidth,
            parallelMargins = names.parallelMargins,
            scrollerWidth, targetPaddingWidth, beforeScroller, afterScroller;

        me.showScrollers();

        beforeScroller = me.getBeforeScroller();
        afterScroller = me.getAfterScroller();

        scrollerWidth = beforeScroller[getWidth]() + afterScroller[getWidth]() +
            beforeScroller.getMargin(parallelMargins) + afterScroller.getMargin(parallelMargins);

        targetPaddingWidth = ownerContext.targetContext.getPaddingInfo()[names.width];

        return {
            reservedSpace: Math.max(scrollerWidth - targetPaddingWidth, 0)
        };
    },

    /**
     * @private
     * Returns a reference to the "before" scroller element.  Creates click handlers on
     * the first call.
     */
    getBeforeScroller: function() {
        var me = this;

        return me._beforeScroller || (me._beforeScroller =
            me.createScroller(me.beforeSuffix, 'beforeRepeater', 'scrollLeft'));
    },

    /**
     * @private
     * Returns a reference to the "after" scroller element.  Creates click handlers on
     * the first call.
     */
    getAfterScroller: function() {
        var me = this;

        return me._afterScroller || (me._afterScroller =
            me.createScroller(me.afterSuffix, 'afterRepeater', 'scrollRight'));
    },

    createScroller: function(suffix, repeaterName, scrollHandler) {
        var me = this,
            owner = me.layout.owner,
            scrollerCls = me.scrollerCls,
            scrollerEl;

        scrollerEl = owner.el.getById(owner.id + suffix);

        scrollerEl.addClsOnOver(scrollerCls + '-hover');
        scrollerEl.addClsOnClick(scrollerCls + '-pressed');

        scrollerEl.setVisibilityMode(Ext.Element.DISPLAY);

        me[repeaterName] = new Ext.util.ClickRepeater(scrollerEl, {
            interval: me.scrollRepeatInterval,
            handler: scrollHandler,
            scope: me
        });

        return scrollerEl;
    },

    onMouseWheel: function(e) {
        var cmp = Ext.Component.from(e.target),
            cmpScroller = cmp.getScrollable && cmp.getScrollable();

        // Only stop the event if we are not scrolling a scrollable component
        // inside this container.
        if (!cmpScroller || (cmpScroller === this.layout.owner.getScrollable())) {
            e.stopEvent();
            this.scrollBy(this.getWheelDelta(e) * this.wheelIncrement * -1, false);
        }
    },

    getWheelDelta: function(e) {
        return e.getWheelDelta();
    },

    /**
     * @private
     */
    clearOverflow: function() {
        this.hideScrollers();
    },

    /**
     * @private
     * Shows the scroller elements. Creates the scrollers first if they are not already present.
     */
    showScrollers: function() {
        var me = this;

        me.getBeforeScroller().show();
        me.getAfterScroller().show();
        me.layout.owner.addClsWithUI(
            me.layout.direction === 'vertical' ? 'vertical-scroller' : 'scroller'
        );
        // TODO - this may invalidates data in the ContextItem's styleCache
    },

    /**
     * @private
     * Hides the scroller elements.
     */
    hideScrollers: function() {
        var me = this,
            beforeScroller = me.getBeforeScroller(),
            afterScroller = me.getAfterScroller();

        if (beforeScroller) {
            beforeScroller.hide();
            afterScroller.hide();
            me.layout.owner.removeClsWithUI(
                me.layout.direction === 'vertical' ? 'vertical-scroller' : 'scroller'
            );
            // TODO - this may invalidates data in the ContextItem's styleCache
        }
    },

    destroy: function() {
        Ext.destroyMembers(this, 'beforeRepeater', 'afterRepeater', '_beforeScroller',
                           '_afterScroller', 'wheelListener');
        this.callParent();
    },

    /**
     * @private
     * Scrolls left or right by the number of pixels specified
     * @param {Number} delta Number of pixels to scroll to the right by.
     * Use a negative number to scroll left
     * @param {Boolean} animate
     */
    scrollBy: function(delta, animate) {
        var layout = this.layout,
            scroller = layout.owner.getScrollable(),
            args = [0, 0, animate ? this.getScrollAnim() : false];

        args[layout.horizontal ? 0 : 1] = delta;
        scroller.scrollBy.apply(scroller, args);
    },

    /**
     * @private
     * @return {Object} Object passed to scrollTo when scrolling
     */
    getScrollAnim: function() {
        return {
            duration: this.scrollDuration,
            callback: this.updateScrollButtons,
            scope: this
        };
    },

    /**
     * @private
     * Enables or disables each scroller button based on the current scroll position
     */
    updateScrollButtons: function() {
        var me = this,
            beforeScroller = me.getBeforeScroller(),
            afterScroller = me.getAfterScroller(),
            scrollPos = me.getScrollPosition(),
            disabledCls;

        if (!beforeScroller || !afterScroller) {
            return;
        }

        disabledCls = me.scrollerCls + '-disabled';

        beforeScroller[scrollPos ? 'removeCls' : 'addCls'](disabledCls);
        afterScroller[scrollPos >= me.getMaxScrollPosition() ? 'addCls' : 'removeCls'](disabledCls);
    },

    /**
     * @private
     * Scrolls to the left by the configured amount
     */
    scrollLeft: function() {
        this.scrollBy(-this.scrollIncrement, false);
    },

    /**
     * @private
     * Scrolls to the right by the configured amount
     */
    scrollRight: function() {
        this.scrollBy(this.scrollIncrement, false);
    },

    /**
     * Returns the current scroll position of the innerCt element
     * @return {Number} The current scroll position
     */
    getScrollPosition: function() {
        var layout = this.layout;

        return layout.owner.getScrollable().getPosition()[layout.horizontal ? 'x' : 'y'];
    },

    /**
     * @private
     * Returns the maximum value we can scrollTo
     * @return {Number} The max scroll value
     */
    getMaxScrollPosition: function() {
        var layout = this.layout;

        return layout.owner.getScrollable().getMaxPosition()[layout.horizontal ? 'x' : 'y'];
    },

    /**
     * @private
     */
    setVertical: function() {
        var me = this,
            beforeScroller = me.getBeforeScroller(),
            afterScroller = me.getAfterScroller(),
            names = me.layout.names,
            scrollerCls = me.scrollerCls;

        beforeScroller.removeCls(scrollerCls + '-' + names.beforeY);
        afterScroller.removeCls(scrollerCls + '-' + names.afterY);

        beforeScroller.addCls(scrollerCls + '-' + names.beforeX);
        afterScroller.addCls(scrollerCls + '-' + names.afterX);

        me.callParent();
    },

    /**
     * @private
     * Scrolls to the given position. Performs bounds checking.
     * @param {Number} position The position to scroll to. This is constrained.
     * @param {Boolean} animate True to animate. If undefined, falls back to value
     * of this.animateScroll
     */
    scrollTo: function(position, animate) {
        var layout = this.layout,
            scroller = layout.owner.getScrollable(),
            args = [0, 0, animate ? this.getScrollAnim() : false];

        args[layout.horizontal ? 0 : 1] = position;
        scroller.scrollTo.apply(scroller, args);
    },

    onScrollEnd: function(scroller, x, y) {
        this.updateScrollButtons();
        this.fireEvent('scroll', this, this.layout.horizontal ? x : y, false);
    },

    /**
     * Scrolls to the given component.
     * @param {String/Number/Ext.Component} item The item to scroll to. Can be a numerical index,
     * component id or a reference to the component itself.
     * @param {Boolean} animate True to animate the scrolling
     */
    scrollToItem: function(item, animate) {
        item = this.getItem(item);

        if (item !== undefined) {
            this.layout.owner.getScrollable().ensureVisible(item.el, {
                animation: animate
            });
        }
    },

    privates: {
        // This is injected into the owner component because the scroller
        // must be applied to the element this this class scrolls
        ownerGetOverflowImpl: function() {
            return this.layout.innerCt;
        }
    }
});
