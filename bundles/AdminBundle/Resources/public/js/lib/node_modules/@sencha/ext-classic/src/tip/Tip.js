/**
 * This is the base class for {@link Ext.tip.QuickTip} and {@link Ext.tip.ToolTip} that provides
 * the basic layout and positioning that all tip-based classes require. This class can be used
 * directly for simple, statically-positioned tips that are displayed programmatically,
 * or it can be extended to provide custom tip implementations.
 */
Ext.define('Ext.tip.Tip', {
    extend: 'Ext.panel.Panel',
    xtype: 'tip',

    alternateClassName: 'Ext.Tip',

    /**
     * @cfg {Boolean} [closable=false]
     * True to render a close tool button into the tooltip header.
     */

    /**
     * @cfg {Number} [width='auto']
     * Width in pixels of the tip.  Width will be ignored if it
     * exceeds the bounds of {@link #minWidth} or {@link #maxWidth}.
     */

    /**
     * @cfg {Number} minWidth
     * The minimum width of the tip in pixels.
     */
    minWidth: 40,

    /**
     * @cfg {Number} maxWidth
     * The maximum width of the tip in pixels.
     */
    maxWidth: 500,

    /**
     * @cfg {Boolean/String} shadow
     * `true` or "sides" for the default effect, "frame" for 4-way shadow, and "drop"
     * for bottom-right shadow.
     */
    shadow: "sides",

    /**
     * @cfg {Boolean} constrainPosition
     * If `true`, then the tooltip will be automatically constrained to stay within
     * the browser viewport.
     */
    constrainPosition: true,

    autoRender: true,
    hidden: true,
    baseCls: Ext.baseCSSPrefix + 'tip',
    focusOnToFront: false,
    maskOnDisable: false,

    /**
     * @cfg {String} closeAction
     * The action to take when the close header tool is clicked:
     *
     * - **{@link #method-destroy}** : {@link #method-remove remove} the window from the DOM and
     *   {@link Ext.Component#method-destroy destroy} it and all descendant Components. The
     *   window will **not** be available to be redisplayed via the {@link #method-show} method.
     *
     * - **{@link #method-hide}** : **Default.** {@link #method-hide} the window by setting
     *   isibility to hidden and applying negative offsets. The window will be available to be
     *   redisplayed via the {@link #method-show} method.
     *
     * **Note:** This behavior has changed! setting *does* affect the {@link #method-close} method
     * which will invoke the approriate closeAction.
     */
    closeAction: 'hide',

    // Flag to Renderable to always look up the framing styles for this Component
    alwaysFramed: true,

    frameHeader: false,

    initComponent: function() {
        var me = this;

        me.floating = Ext.apply({}, {
            shadow: me.shadow
        }, me.self.prototype.floating);

        me.callParent(arguments);

        // Or in the deprecated config. Floating.doConstrain only constrains
        // if the constrain property is truthy.
        me.constrain = me.constrain || me.constrainPosition;
    },

    /**
     * Shows this tip at the specified XY position.  Example usage:
     *
     *     // Show the tip at x:50 and y:100
     *     tip.showAt([50,100]);
     *
     * @param {Number[]} xy An array containing the x and y coordinates
     */
    showAt: function(xy) {
        var me = this;

        me.calledFromShowAt = true;

        me.callParent(arguments);

        // Show may have been vetoed.
        if (me.isVisible()) {
            me.doAlignment(me.getRegion().alignTo({
                target: new Ext.util.Point(xy[0], xy[1]),
                inside: me.constrainPosition
                    ? Ext.getBody().getRegion().adjust(5, -5, -5, 5)
                    : null,
                align: 'tl-tl',
                overlap: true
            }));
        }

        me.calledFromShowAt = 0;
    },

    doAlignment: function(newRegion) {
        var me = this,
            anchorEl = me.anchorEl,
            anchorRegion = newRegion.anchor;

        me.setPagePosition([newRegion.x, newRegion.y]);

        if (anchorEl) {
            anchorEl.removeCls(me.anchorCls);

            if (anchorRegion) {
                me.anchorCls = Ext.baseCSSPrefix + 'tip-anchor-' + anchorRegion.position;
                anchorEl.addCls(me.anchorCls);
                anchorEl.show();

                // The result is to the left or right of the target
                if (anchorRegion.align & 1) {
                    anchorEl.setTop(newRegion.anchor.y - newRegion.y);
                    anchorEl.dom.style.left = '';
                }
                else {
                    anchorEl.setLeft(newRegion.anchor.x - newRegion.x);
                    anchorEl.dom.style.top = '';
                }
            }
            else {
                anchorEl.hide();
            }
        }
    },

    privates: {
        /**
         * @private
         * Set Tip draggable using base Component's draggability.
         */
        initDraggable: function() {
            var me = this;

            me.draggable = {
                el: me.getDragEl(),
                delegate: me.header.el,
                constrain: me,
                constrainTo: me.el.dom.parentNode
            };
            // Important: Bypass Panel's initDraggable. Call direct to Component's implementation.
            Ext.Component.prototype.initDraggable.call(me);
        }
    },

    // Tip does not ghost. Drag is "live"
    ghost: undefined,
    unghost: undefined
});
