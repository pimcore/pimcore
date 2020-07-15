/**
 * @class Ext.chart.interactions.ItemHighlight
 * @extends Ext.chart.interactions.Abstract
 *
 * The 'itemhighlight' interaction allows the user to highlight series items in the chart.
 */
Ext.define('Ext.chart.interactions.ItemHighlight', {

    extend: 'Ext.chart.interactions.Abstract',

    type: 'itemhighlight',
    alias: 'interaction.itemhighlight',

    isItemHighlight: true,

    config: {

        gestures: {
            tap: 'onTapGesture',
            mousemove: 'onMouseMoveGesture',
            mousedown: 'onMouseDownGesture',
            mouseup: 'onMouseUpGesture',
            mouseleave: 'onMouseUpGesture'
        },

        /**
         * @cfg {Boolean} [sticky=false]
         * Disables mouse tracking.
         * Series items will only be highlighted/unhighlighted on mouse click.
         * This config has no effect on touch devices.
         */
        sticky: false,

        /**
         * @cfg {Boolean} [multiTooltips=false]
         * Enable displaying multiple tooltips for overlapping or adjacent series items within
         * {@link Ext.chart.series.Line#selectionTolerance} radius.
         * Default is to display a tooltip only for the last series item rendered.
         * When multiple tooltips are displayed, they may overlap partially or completely;
         * it is up to the developer to ensure tooltip positioning is satisfactory.
         * 
         * @since 6.6.0
         */
        multiTooltips: false
    },

    constructor: function(config) {
        this.callParent([config]);

        this.stickyHighlightItem = null;
        this.tooltipItems = [];
    },

    destroy: function() {
        this.stickyHighlightItem = this.tooltipItems = null;

        this.callParent();
    },

    onMouseMoveGesture: function(e) {
        var me = this,
            tooltipItems = me.tooltipItems,
            isMousePointer = e.pointerType === 'mouse',
            tooltips = [],
            item, oldItem, items, tooltip, oldTooltip, i, len, j, jLen;

        if (me.getSticky()) {
            return true;
        }

        if (isMousePointer && me.stickyHighlightItem) {
            me.stickyHighlightItem = null;
            me.highlight(null);
        }

        if (me.isDragging) {
            if (tooltipItems.length && isMousePointer) {
                me.hideTooltips(tooltipItems);
                tooltipItems.length = 0;
            }
        }
        else if (!me.stickyHighlightItem) {
            if (me.getMultiTooltips()) {
                items = me.getItemsForEvent(e);
            }
            else {
                item = me.getItemForEvent(e);
                items = item ? [item] : [];
            }

            for (i = 0, len = items.length; i < len; i++) {
                item = items[i];

                // Items are returned top to down, so first item is the top one.
                // Chart can only have one highlighted item.
                if (i === 0 && item !== me.getChart().getHighlightItem()) {
                    me.highlight(item);
                    me.sync();
                }

                tooltip = item.series.getTooltip();

                if (tooltip) {
                    tooltips.push(tooltip);
                }
            }

            if (isMousePointer) {
                // If we detected a mouse hit, show/refresh the tooltip
                if (items.length) {
                    for (i = 0, len = items.length; i < len; i++) {
                        item = items[i];
                        tooltip = item.series.getTooltip();

                        if (tooltip) {
                            // If there were different previously active items
                            // that are not going to be included in current active items,
                            // ask them to hide their tooltips. Unless those are
                            // the same tooltip instances that we are about to show,
                            // in which case we are just going to reposition them.
                            for (j = 0, jLen = tooltipItems.length; j < jLen; j++) {
                                oldItem = tooltipItems[j];

                                if (!Ext.Array.contains(items, oldItem)) {
                                    oldTooltip = oldItem.series.getTooltip();

                                    if (!Ext.Array.contains(tooltips, oldTooltip)) {
                                        oldItem.series.hideTooltip(oldItem, true);
                                    }
                                }
                            }

                            if (tooltip.getTrackMouse()) {
                                item.series.showTooltip(item, e);
                            }
                            else {
                                me.showUntracked(item);
                            }
                        }
                    }

                    me.tooltipItems = items;
                }
                // No mouse hit - schedule a hide for hideDelay ms.
                // If pointer enters another item within that time,
                // there will be no flickery reshow.
                else {
                    me.hideTooltips(tooltipItems);
                    tooltipItems.length = 0;
                }
            }

            return false;
        }
    },

    highlight: function(item) {
        // This is its own function to make it easier for subclasses
        // to enhance the behavior. An alternative would be to listen
        // for the chart's 'itemhighlight' event.
        this.getChart().setHighlightItem(item);
    },

    showTooltip: function(e, item) {
        item.series.showTooltip(item, e);
        Ext.Array.include(this.tooltipItems, item);
    },

    showUntracked: function(item) {
        var marker = item.sprite.getMarker(item.category),
            surface, surfaceXY, isInverseY,
            itemBBox, matrix;

        if (marker) {
            surface = marker.getSurface();
            isInverseY = surface.matrix.elements[3] < 0;
            surfaceXY = surface.element.getXY();
            itemBBox = Ext.clone(marker.getBBoxFor(item.index));

            if (isInverseY) {
                // The item.category for bar series will be 'items'.
                // The item.category for line series will be 'markers'.
                // 'items' are in the 'series' surface, which is flipped vertically
                // for cartesian series.
                // 'markers' are in the 'overlay' surface, which isn't flipped.
                // So for 'markers' we already have the bbox in a coordinate system
                // with the origin at the top-left of the surface, but for 'items'
                // we need to do a conversion.
                if (surface.getInherited().rtl) {
                    matrix = surface.inverseMatrix.clone().flipX()
                                    .translate(item.sprite.attr.innerWidth, 0, true);
                }
                else {
                    matrix = surface.inverseMatrix;
                }

                itemBBox = matrix.transformBBox(itemBBox);
            }

            itemBBox.x += surfaceXY[0];
            itemBBox.y += surfaceXY[1];
            item.series.showTooltipAt(item,
                                      itemBBox.x + itemBBox.width * 0.5,
                                      itemBBox.y + itemBBox.height * 0.5
            );
        }
    },

    onMouseDownGesture: function() {
        this.isDragging = true;
    },

    onMouseUpGesture: function() {
        this.isDragging = false;
    },

    isSameItem: function(a, b) {
        return a && b && a.series === b.series && a.field === b.field && a.index === b.index;
    },

    onTapGesture: function(e) {
        var me = this,
            item;

        // A click/tap on an item makes its highlight sticky.
        // It requires another click/tap to unhighlight.
        if (e.pointerType === 'mouse' && !me.getSticky()) {
            return;
        }

        item = me.getItemForEvent(e);

        if (me.isSameItem(me.stickyHighlightItem, item)) {
            item = null; // toggle
        }

        me.stickyHighlightItem = item;
        me.highlight(item);
    },

    privates: {
        hideTooltips: function(items, force) {
            var item, i, len;

            items = Ext.isArray(items) ? items : [items];

            for (i = 0, len = items.length; i < len; i++) {
                item = items[i];

                if (item && item.series && !item.series.destroyed) {
                    item.series.hideTooltip(item, force);
                }
            }
        }
    }
});
