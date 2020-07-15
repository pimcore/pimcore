/**
 * ToolTip is a {@link Ext.tip.Tip} implementation that handles the common case of displaying a
 * tooltip when hovering over a certain element or elements on the page. It allows fine-grained
 * control over the tooltip's alignment relative to the target element or mouse, and the timing
 * of when it is automatically shown and hidden.
 *
 * This implementation does **not** have a built-in method of automatically populating the tooltip's
 * text based on the target element; you must either configure a fixed {@link #html} value for each
 * ToolTip instance, or implement custom logic (e.g. in a {@link #beforeshow} event listener) to
 * generate the appropriate tooltip content on the fly. See {@link Ext.tip.QuickTip} for a more
 * convenient way of automatically populating and configuring a tooltip based on specific DOM
 * attributes of each target element.
 *
 * # Basic Example
 *
 *     @example
 *     Ext.getBody().appendChild({
 *         id: 'clearButton',
 *         html: 'Clear Button',
 *         style: 'display:inline-block;background:#A2C841;padding:7px;cursor:pointer;'
 *     });
 *
 *     var tip = Ext.create('Ext.tip.ToolTip', {
 *         target: 'clearButton',
 *         html: 'Press this button to clear the form'
 *     });
 *
 * # Delegation
 *
 * In addition to attaching a ToolTip to a single element, you can also use delegation to attach
 * one ToolTip to many elements under a common parent. This is more efficient than creating many
 * ToolTip instances. To do this, point the {@link #target} config to a common ancestor of all the
 * elements, and then set the {@link #delegate} config to a CSS selector that will select all the
 * appropriate sub-elements.
 *
 * When using delegation, it is likely that you will want to programmatically change the content
 * of the ToolTip based on each delegate element; you can do this by implementing a custom
 * listener for the {@link #beforeshow} event. Example:
 *
 *     @example
 *     var store = Ext.create('Ext.data.ArrayStore', {
 *         fields: ['company', 'price', 'change'],
 *         data: [
 *             ['3m Co',                               71.72, 0.02],
 *             ['Alcoa Inc',                           29.01, 0.42],
 *             ['Altria Group Inc',                    83.81, 0.28],
 *             ['American Express Company',            52.55, 0.01],
 *             ['American International Group, Inc.',  64.13, 0.31],
 *             ['AT&T Inc.',                           31.61, -0.48]
 *         ]
 *     });
 *
 *     var grid = Ext.create('Ext.grid.Panel', {
 *         title: 'Array Grid',
 *         store: store,
 *         columns: [
 *             {text: 'Company', flex: 1, dataIndex: 'company'},
 *             {text: 'Price', width: 75, dataIndex: 'price'},
 *             {text: 'Change', width: 75, dataIndex: 'change'}
 *         ],
 *         height: 200,
 *         width: 400,
 *         renderTo: Ext.getBody()
 *     });
 *
 *     var view = grid.getView();
 *     var tip = Ext.create('Ext.tip.ToolTip', {
 *         // The overall target element.
 *         target: view.el,
 *         // Each grid row causes its own separate show and hide.
 *         delegate: view.itemSelector,
 *         // Moving within the row should not hide the tip.
 *         trackMouse: true,
 *         // Render immediately so that tip.body can be referenced prior to the first show.
 *         renderTo: Ext.getBody(),
 *         listeners: {
 *             // Change content dynamically depending on which element triggered the show.
 *             beforeshow: function updateTipBody(tip) {
 *                 tip.update('Over company "' + view.getRecord(tip.triggerElement).get('company') +
 *                            '"');
 *             }
 *         }
 *     });
 *
 * # Alignment
 *
 * The following configuration properties allow control over how the ToolTip is aligned relative to
 * the target element and/or mouse pointer:
 *
 * - {@link #anchor}
 * - {@link #anchorToTarget}
 * - {@link #trackMouse}
 * - {@link #mouseOffset}
 *
 * # Showing/Hiding
 *
 * The following configuration properties allow control over how and when the ToolTip
 * is automatically shown and hidden:
 *
 * - {@link #autoHide}
 * - {@link #showDelay}
 * - {@link #hideDelay}
 * - {@link #dismissDelay}
 */
Ext.define('Ext.tip.ToolTip', {
    extend: 'Ext.tip.Tip',
    alias: 'widget.tooltip',
    alternateClassName: 'Ext.ToolTip',

    requires: ['Ext.util.Offset'],

    /**
     * @property {HTMLElement} triggerElement
     * When a ToolTip is configured with the `{@link #delegate}`
     * option to cause selected child elements of the `{@link #target}`
     * Element to each trigger a separate show event, this property is set to
     * the DOM element which triggered the show.
     */

    /**
     * @cfg {HTMLElement/Ext.dom.Element/String} target
     * The target element or string id to monitor for mouseover events to trigger
     * showing this ToolTip.
     */

    /**
     * @cfg {Boolean} [autoHide=true]
     * True to automatically hide the tooltip after the
     * mouse exits the target element or after the `{@link #dismissDelay}`
     * has expired if set.  If `{@link #closable} = true`
     * a close tool button will be rendered into the tooltip header.
     */
    autoHide: true,

    /**
     * @cfg {Number} showDelay
     * Delay in milliseconds before the tooltip displays after the mouse enters the target element.
     */
    showDelay: 500,

    /**
     * @cfg {Number} hideDelay
     * Delay in milliseconds after the mouse exits the target element but before the tooltip
     * actually hides. Set to 0 for the tooltip to hide immediately.
     */
    hideDelay: 200,

    /**
     * @cfg {Number} dismissDelay
     * Delay in milliseconds before the tooltip automatically hides. To disable automatic hiding,
     * set dismissDelay = 0.
     */
    dismissDelay: 5000,

    /**
     * @cfg {Number[]} [targetOffset=[0, 0]]
     * When {@link #anchorToTarget} is being used to position this tip relative to its target
     * element, this may be used as an extra XY offset from the target element.
     */

    /**
     * @cfg {Number[]} [mouseOffset=[15, 18]]
     * An XY offset from the mouse position where the tooltip should be shown.
     */
    mouseOffset: [15, 18],

    /**
     * @cfg {Boolean} trackMouse
     * True to have the tooltip follow the mouse as it moves over the target element.
     */
    trackMouse: false,

    /**
     * @cfg {String} anchor
     * If specified, indicates that the tip should be anchored to a
     * particular side of the target element or mouse pointer ("top", "right", "bottom",
     * or "left"), with an arrow pointing back at the target or mouse pointer. If
     * {@link #constrainPosition} is enabled, this will be used as a preferred value
     * only and may be flipped as needed.
     */

    /**
     * @cfg {Boolean} anchorToTarget
     * True to anchor the tooltip to the target element, false to anchor it relative to the mouse
     * coordinates. When `anchorToTarget` is true, use `{@link #defaultAlign}` to control tooltip
     * alignment to the target element. When `anchorToTarget` is false, use `{@link #anchor}`
     * instead to control alignment.
     */
    anchorToTarget: true,

    /**
     * @cfg {String} delegate
     *
     * A {@link Ext.DomQuery DomQuery} simple selector which allows selection of individual elements
     * within the `{@link #target}` element to trigger showing and hiding the ToolTip as the mouse
     * moves within the target. See {@link Ext.dom.Query} for information about simple selectors.
     *
     * When specified, the child element of the target which caused a show event is placed into the
     * `{@link #triggerElement}` property before the ToolTip is shown.
     *
     * This may be useful when a Component has regular, repeating elements in it, each of which need
     * a ToolTip which contains information specific to that element.
     *
     * See the delegate example in class documentation of {@link Ext.tip.ToolTip}.
     */

    /**
     * @cfg {Boolean} [showOnTap=false]
     * On touch platforms, if {@link #showOnTap} is `true`, a tap on the target shows the tip.
     * In this case any {@link #showDelay} is ignored.
     *
     * This is useful for adding tips on elements which do not have tap listeners. It would
     * not be appropriate for a ToolTip on a {@link Ext.Button Button}.
     */

    /**
     * @private
     */
    targetCounter: 0,

    quickShowInterval: 250,

    /**
     * @cfg {String} [hideAction="hide"]
     * The method to use to hide the tooltip. Another useful method for this is `fadeOut`.
     */
    hideAction: 'hide',

    /**
     * @cfg {Number} [fadeOutDuration=1000]
     * The number of milliseconds for the `fadeOut` animation. Only valid if `hideAction`
     * is set to `fadeOut`.
     */
    fadeOutDuration: 1000,

    /**
     * @cfg {String} defaultAlign
     * A string which specifies how this ToolTip is to align with regard to its
     * {@link #currentTarget} by means of identifying the point of the tooltip to
     * join to the point of the target.
     *
     * By default, the tooltip shows at {@link #mouseOffset} pixels from the
     * triggering pointer event. Using this config anchors the ToolTip to its target
     * instead.
     *
     * This may take the following forms:
     * 
     * - **Blank**: Defaults to aligning the element's top-left corner to the target's
     *   bottom-left corner ("tl-bl").
     * - **Two anchors**: If two values from the table below are passed separated by a dash,
     *   the first value is used as the element's anchor point, and the second value is
     *   used as the target's anchor point.
     * - **Two edge/offset descriptors:** An edge/offset descriptor is an edge initial
     *   (`t`/`r`/`b`/`l`) followed by a percentage along that side. This describes a
     *   point to align with a similar point in the target. So `'t0-b0'` would be
     *   the same as `'tl-bl'`, `'l0-r50'` would place the top left corner of this item
     *   halfway down the right edge of the target item. This allows more flexibility
     *   and also describes which two edges are considered adjacent when positioning a tip pointer. 
     *
     * Following are all of the supported predefined anchor positions:
     *
     *      Value  Description
     *      -----  -----------------------------
     *      tl     The top left corner
     *      t      The center of the top edge
     *      tr     The top right corner
     *      l      The center of the left edge
     *      c      The center
     *      r      The center of the right edge
     *      bl     The bottom left corner
     *      b      The center of the bottom edge
     *      br     The bottom right corner
     *
     * You can put a '?' at the end of the alignment string to constrain the positioned element
     * to the {@link Ext.Viewport Viewport}. The element will attempt to align as specified,
     * but the position will be adjusted to constrain to the viewport if necessary. Note that
     * the element being aligned might be swapped to align to a different position than that
     * specified in order to enforce the viewport constraints.
     *
     * Example Usage:
     *
     *     // align the top left corner of the tooltip with the top right corner of its target.
     *     defaultAlign: 'tl-tr'
     *
     *     // align the bottom right corner of the tooltip with the center left edge of its target.
     *     defaultAlign: 'br-l'
     *
     *     // align the top center of the tooltip with the bottom left corner of its target.
     *     defaultAlign: 't-bl'
     *
     *     // align the 25% point on the bottom edge of this tooltip
     *     // with the 75% point on the top edge of its target.
     *     defaultAlign: 'b25-t75'
     */
    defaultAlign: 'bl-tl',

    ariaRole: 'tooltip',

    alwaysOnTop: true,

    initComponent: function() {
        var me = this;

        me.callParent();
        me.setTarget(me.target);

        // currentTarget is a flyweight which points to the activeTarget.
        me.currentTarget = new Ext.dom.Fly();
    },

    onRender: function(ct, position) {
        var me = this;

        me.callParent(arguments);

        //<debug>
        if (me.sticky) {
            // tell the spec runner to ignore this element when checking if the dom is clean
            me.el.dom.setAttribute('data-sticky', true);
        }
        //</debug>

        me.anchorEl = me.el.createChild({
            role: 'presentation',
            cls: Ext.baseCSSPrefix + 'tip-anchor'
        });
    },

    show: function() {
        // A programmatic show should align to the target
        if (!this.currentTarget.dom && this.target) {
            return this.showBy(this.target);
        }

        this.callParent();
    },

    /**
     * Binds this ToolTip to the specified element. The tooltip will be displayed when the mouse
     * moves over the element.
     * @param {String/HTMLElement/Ext.dom.Element} target The Element, HTMLElement, or
     * ID of an element to bind to
     */
    setTarget: function(target) {
        var me = this,
            listeners;

        if (me.targetListeners) {
            me.targetListeners.destroy();
        }

        if (target) {
            me.target = target = Ext.get(target.el || target);
            listeners = {
                mouseover: 'onTargetOver',
                mouseout: 'onTargetOut',
                mousemove: 'onMouseMove',
                tap: 'onTargetTap',
                scope: me,
                destroyable: true
            };

            me.targetListeners = target.on(listeners);
        }
        else {
            me.target = null;
        }
    },

    /**
     * @private
     */
    onMouseMove: function(e) {
        var me = this,
            dismissDelay = me.dismissDelay;

        // Always update pointerEvent, so that if there's a delayed show
        // scheduled, it gets the latest pointer to align to.
        me.pointerEvent = e;

        if (me.isVisible() && me.currentTarget.contains(e.target)) {
            // If they move the mouse, restart the dismiss delay
            if (dismissDelay && me.autoHide !== false) {
                me.clearTimer('dismiss');
                me.dismissTimer = Ext.defer(me.hide, dismissDelay, me);
            }

            if (me.trackMouse) {
                me.doAlignment(me.getAlignRegion());
            }
        }
    },

    /**
     * @private
     */
    getAlignRegion: function() {
        var me = this,
            anchorEl = me.anchorEl,
            align = me.getAnchorAlign(),
            overlap,
            alignSpec,
            target,
            mouseOffset = me.mouseOffset;

        if (!me.anchorSize) {
            anchorEl.addCls(Ext.baseCSSPrefix + 'tip-anchor-top');
            anchorEl.show();

            me.anchorSize = new Ext.util.Offset(
                anchorEl.getWidth(false, true), anchorEl.getHeight(false, true)
            );

            anchorEl.removeCls(Ext.baseCSSPrefix + 'tip-anchor-top');
            anchorEl.hide();
        }

        // Target region from the anchorTarget element unless trackMouse set
        if ((me.anchor || me.align) && me.anchorToTarget && !me.trackMouse) {
            target = me.currentTarget.getRegion();
        }

        // Here, we're either trackMouse: true, or we're not anchored to the target
        // element, so we should show offset from the mouse.
        // If we are being shown programatically, use 0, 0
        else {
            target = me.pointerEvent
                ? me.pointerEvent.getPoint().adjust(
                    -Math.abs(mouseOffset[1]), Math.abs(mouseOffset[0]),
                    Math.abs(mouseOffset[1]), -Math.abs(mouseOffset[0])
                )
                : new Ext.util.Point();

            if (!me.anchor) {
                overlap = true;

                if (mouseOffset[0] > 0) {
                    if (mouseOffset[1] > 0) {
                        align = 'tl-br';
                    }
                    else {
                        align = 'bl-tr';
                    }
                }
                else {
                    if (mouseOffset[1] > 0) {
                        align = 'tr-bl';
                    }
                    else {
                        align = 'br-tl';
                    }
                }
            }
        }

        alignSpec = {
            align: me.convertPositionSpec(align),
            axisLock: me.axisLock,
            target: target,
            overlap: overlap,
            offset: me.targetOffset,
            inside: me.constrainPosition
                ? (me.constrainTo || Ext.getBody().getRegion().adjust(5, -5, -5, 5))
                : null
        };

        if (me.anchor) {
            alignSpec.anchorSize = me.anchorSize;
        }

        return me.getRegion().alignTo(alignSpec);
    },

    fadeOut: function() {
        var me = this;

        me.el.fadeOut({
            duration: me.fadeOutDuration,
            callback: function() {
                me.hide();
                me.el.setOpacity('');
            }
        });
    },

    /**
     * @private
     */
    getAnchorAlign: function() {
        switch (this.anchor) {
            case 'top':
                return 'tl-bl';

            case 'left':
                return 'tl-tr';

            case 'right':
                return 'tr-tl';

            default:
                return this.defaultAlign;
        }
    },

    onTargetTap: function(e) {
        // On hybrid mouse/touch systems, we want to show the tip on touch, but
        // we don't want to show it if this is coming from a click event, because
        // the mouse is already hovered. Tap occasionally hides - eg: pickers, menus.
        if (this.showOnTap && e.pointerType !== 'mouse' && Ext.fly(e.target).isVisible(true)) {
            this.onTargetOver(e);
        }
    },

    /**
     * @private
     */
    onTargetOver: function(e) {
        var me = this,
            delegate = me.delegate,
            currentTarget = me.currentTarget,
            fromElement = e.relatedTarget || e.fromElement,
            newTarget,
            myListeners = me.hasListeners;

        if (me.disabled) {
            return;
        }

        if (delegate) {
            // Moving inside a delegate
            if (currentTarget.contains(e.target)) {
                return;
            }

            newTarget = e.getTarget(delegate);

            // Mouseovers while within a target do nothing
            if (newTarget && e.getRelatedTarget(delegate) === newTarget) {
                return;
            }
        }
        // Moved from outside the target
        else if (!me.target.contains(fromElement)) {
            newTarget = me.target.dom;
        }
        // Moving inside the target
        else {
            return;
        }

        // If pointer entered the target or a delegate child, then show.
        if (newTarget) {
            // If users need to see show events on target change, we must hide.
            if ((myListeners.beforeshow || myListeners.show) && me.isVisible()) {
                me.hide();
            }

            me.triggerElement = newTarget;
            me.pointerEvent = e;
            currentTarget.attach(newTarget);
            me.handleTargetOver(newTarget, e);
        }
        // If over a non-delegate child, behave as in target out
        else if (currentTarget.dom) {
            me.handleTargetOut();
        }
    },

    handleTargetOver: function(target, event) {
        // Separated from onTargetOver so that subclasses can handle target over in any way.

        // If we are showing on tap, show immediately
        if (event.pointerType !== 'mouse') {
            this.showFromDelay();
        }
        else {
            this.delayShow();
        }
    },

    /**
     * @private
     */
    delayShow: function() {
        var me = this;

        me.clearTimer('hide');

        if (me.hidden && !me.showTimer) {
            if (me.delegate && Ext.Date.getElapsed(me.lastHidden) < me.quickShowInterval) {
                me.showFromDelay();
            }
            else {
                me.showTimer = Ext.defer(
                    me.showFromDelay,
                    me.pointerEvent.pointerType !== 'mouse' ? 0 : me.showDelay,
                    me
                );
            }
        }
        else if (!me.hidden && me.autoHide !== false) {
            me.showFromDelay();
        }
    },

    showFromDelay: function() {
        var me = this;

        // Need to check this here since onDisable only gets called after render, which
        // the show call below may trigger
        if (!me.disabled) {
            me.fireEvent('hovertarget', me, me.currentTarget, me.currentTarget.dom);

            if (me.isVisible()) {
                me.realignToTarget();
            }
            else {
                me.triggerElement = me.currentTarget.dom;
                me.fromDelayShow = true;
                me.show();
                me.fromDelayShow = false;
            }
        }
    },

    /**
     * @private
     */
    onTargetOut: function(e) {
        // We have exited the current target
        if (this.currentTarget.dom && !this.currentTarget.contains(e.relatedTarget)) {
            this.handleTargetOut();
        }
    },

    handleTargetOut: function() {
        var me = this;

        if (me.showTimer) {
            me.clearTimer('show');
        }

        if (me.isVisible() && me.autoHide) {
            me.delayHide();
        }
    },

    /**
     * @private
     */
    delayHide: function() {
        var me = this;

        if (!me.hidden && !me.hideTimer) {
            me.clearTimer('dismiss');
            me.hideTimer = Ext.defer(me[me.hideAction], me.hideDelay, me);
        }
    },

    /**
     * Hides this tooltip if visible.
     */
    hide: function() {
        var me = this;

        // Must also do this on hide in case it was dismissed while over
        me.currentTarget.detach();

        me.clearTimer('dismiss');
        me.lastHidden = new Date();

        if (me.anchorEl) {
            me.anchorEl.hide();
        }

        me.callParent(arguments);

        me.triggerElement = null;
    },

    /**
     * Ensures this tooltip at the current event target XY position.
     */
    afterShow: function() {
        this.callParent();
        this.realignToTarget();
    },

    /**
     * Realign this tooltip to the {@link #cfg-target}.
     *
     * @since 6.2.1
     */
    realignToTarget: function() {
        var me = this;

        me.clearTimers();

        if (!me.calledFromShowAt) {
            me.doAlignment(me.getAlignRegion());
        }

        if (me.dismissDelay && me.autoHide !== false) {
            me.dismissTimer = Ext.defer(me.hide, me.dismissDelay, me);
        }
    },

    /**
     * Shows this ToolTip aligned to the passed Component or element or event according to the
     * {@link #anchor} config.
     * @param {Ext.Component/Ext.event.Event/Ext.dom.Element} target The {@link Ext.Component}
     * or {@link Ext.dom.Element}, or (Ext.event.Event} to show this ToolTip by.
     */
    showBy: function(target) {
        var me = this;

        me.align = me.defaultAlign;

        if (target.isEvent) {
            me.currentTarget.attach(target.target);
            me.pointerEvent = target;
        }
        else {
            me.currentTarget.attach(Ext.getDom(target.el || target));
            me.triggerElement = me.currentTarget.dom;
        }

        if (me.isVisible()) {
            me.realignToTarget();
        }
        else {
            me.show();
        }

        return me;
    },

    _timerNames: {},

    /**
     * @private
     */
    clearTimer: function(name) {
        var me = this,
            names = me._timerNames,
            propName = names[name] || (names[name] = name + 'Timer'),
            timer = me[propName];

        if (timer) {
            Ext.undefer(timer);
            me[propName] = null;

            // We were going to show against the target, but now not.
            if (name === 'show' && me.isHidden()) {
                me.currentTarget.detach();
            }
        }
    },

    /**
     * @private
     */
    clearTimers: function() {
        var me = this;

        me.clearTimer('show');
        me.clearTimer('dismiss');
        me.clearTimer('hide');
        me.clearTimer('enable');
    },

    onShow: function() {
        var me = this;

        me.callParent();

        me.mousedownListener = Ext.on({
            mousedown: 'onDocMouseDown',
            scope: me,
            destroyable: true
        });
    },

    onHide: function() {
        var me = this;

        me.callParent();

        Ext.destroy(me.mousedownListener);
    },

    /**
     * @private
     */
    onDocMouseDown: function(e) {
        var me = this,
            delegate = me.delegate;

        if (e.within(me.el.dom)) {
            // A real touch event inside the tip is the equivalent of
            // mousing over the tip to keep it visible, so cancel the
            // dismiss timer.
            if (e.pointerType !== 'mouse' && me.allowOver) {
                me.clearTimer('dismiss');
            }
        }
        // Only respond to the mousedown if it's not on this tip, and it's not on a target.
        // If it's on a target, onTargetTap will handle it.
        else if (!me.closable) {
            if (e.within(me.target) && (!delegate || e.getTarget(delegate))) {
                me.delayHide();
            }
            else {
                me.disable();
                me.enableTimer = Ext.defer(me.enable, 100, me);
            }
        }
    },

    /**
     * @private
     */
    doEnable: function() {
        if (!this.destroyed) {
            this.enable();
        }
    },

    onDisable: function() {
        this.callParent();
        this.clearTimers();
        this.hide();
    },

    doDestroy: function() {
        var me = this;

        me.clearTimers();

        me.destroyMembers('mousedownListener', 'anchorEl');

        me.callParent();
    },

    privates: {
        /**
         * Implementation for universal apps so that the Tooltip interface they are using works
         * when common code uses the ToolTip API.
         */
        getTrackMouse: function() {
            return this.trackMouse;
        },

        clipTo: function(clippingEl, sides) {
        // Override because we also need to clip the anchor
            var clippingRegion;

            // Allow a Region to be passed
            if (clippingEl.isRegion) {
                clippingRegion = clippingEl;
            }
            else {
                // eslint-disable-next-line max-len
                clippingRegion = (clippingEl.isComponent ? clippingEl.el : Ext.fly(clippingEl)).getConstrainRegion();
            }

            this.callParent([clippingRegion, sides]);

            // Clip the anchor to the same bounds
            this.anchorEl.clipTo(clippingRegion, sides);
        }
    }
});
