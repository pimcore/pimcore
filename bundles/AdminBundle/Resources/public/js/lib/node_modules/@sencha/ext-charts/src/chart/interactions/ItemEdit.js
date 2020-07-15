/**
 * @class Ext.chart.interactions.ItemEdit
 * @extends Ext.chart.interactions.ItemHighlight
 *
 * The 'itemedit' interaction allows the user to edit store data
 * by dragging series items in the chart.
 *
 * The 'itemedit' interaction extends the
 * {@link Ext.chart.interactions.ItemHighlight 'itemhighlight'} interaction,
 * so it also acts like one. If you need both interactions in a single chart,
 * 'itemedit' should be sufficient. Hovering/tapping will result in highlighting,
 * and dragging will result in editing.
 */
Ext.define('Ext.chart.interactions.ItemEdit', {
    extend: 'Ext.chart.interactions.ItemHighlight',
    requires: [
        'Ext.tip.ToolTip'
    ],

    type: 'itemedit',
    alias: 'interaction.itemedit',

    isItemEdit: true,

    config: {
        /**
         * @cfg {Object} [style=null]
         * The style that will be applied to the series item on dragging.
         * By default, series item will have no fill,
         * and will have a dashed stroke of the same color.
         */
        style: null,

        /**
         * @cfg {Function/String} [renderer=null]
         * A function that returns style attributes for the item that's being dragged.
         * This is useful if you want to give a visual feedback to the user when
         * they dragged to a certain point.
         *
         * @param {Object} [data] The following properties are available:
         *
         * @param {Object} data.target The object containing the xField/xValue or/and
         * yField/yValue properties, where the xField/yField specify the store records
         * being edited and the xValue/yValue the target values to be set when
         * the interaction ends. The object also contains the 'index' of the record
         * being edited.
         * @param {Object} data.style The style that is going to be used for the dragged item.
         * The attributes returned by the renderer will be applied on top of this style.
         * @param {Object} data.item The series item being dragged.
         * This is actually the {@link Ext.chart.AbstractChart#highlightItem}.
         *
         * @return {Object} The style attributes to be set on the dragged item.
         */
        renderer: null,

        /**
         * @cfg {Object/Boolean} [tooltip=true]
         */
        tooltip: true,

        gestures: {
            dragstart: 'onDragStart',
            drag: 'onDrag',
            dragend: 'onDragEnd'
        },

        cursors: {
            ewResize: 'ew-resize',
            nsResize: 'ns-resize',
            move: 'move'
        }

        /**
         * @private
         * @cfg {Boolean} [sticky=false]
         */
    },

    /**
     * @event beginitemedit
     * Fires when item edit operation (dragging) begins.
     * @param {Ext.chart.AbstractChart} chart The chart the interaction belongs to.
     * @param {Ext.chart.interactions.ItemEdit} interaction The interaction.
     * @param {Object} item The item that is about to be edited.
     */

    /**
     * @event enditemedit
     * Fires when item edit operation (dragging) ends.
     * @param {Ext.chart.AbstractChart} chart The chart the interaction belongs to.
     * @param {Ext.chart.interactions.ItemEdit} interaction The interaction.
     * @param {Object} item The item that was edited.
     * @param {Object} target The object containing target values the were used.
     */

    item: null, // Item being edited.

    applyTooltip: function(tooltip) {
        var config;

        if (tooltip) {
            config = Ext.apply({}, tooltip, {
                renderer: this.defaultTooltipRenderer,
                constrainPosition: true,
                shrinkWrapDock: true,
                autoHide: true,
                trackMouse: true,
                mouseOffset: [20, 20]
            });

            tooltip = new Ext.tip.ToolTip(config);
        }

        return tooltip;
    },

    defaultTooltipRenderer: function(tooltip, item, target, e) {
        var parts = [];

        if (target.xField) {
            parts.push(target.xField + ': ' + target.xValue);
        }

        if (target.yField) {
            parts.push(target.yField + ': ' + target.yValue);
        }

        tooltip.setHtml(parts.join('<br>'));
    },

    onDragStart: function(e) {
        var me = this,
            chart = me.getChart(),
            item = chart.getHighlightItem();

        e.claimGesture();

        if (item) {
            chart.fireEvent('beginitemedit', chart, me, me.item = item);

            // If ItemEdit interaction comes before other interactions
            // in the chart's 'interactions' config, this will
            // prevent other interactions hijacking the 'dragstart'
            // event. We only stop event propagation is there's
            // an item to edit under cursor/finger, otherwise we
            // let other interactions (e.g. 'panzoom') handle the event.
            return false;
        }
    },

    onDrag: function(e) {
        var me = this,
            chart = me.getChart(),
            item = chart.getHighlightItem(),
            type = item && item.sprite.type;

        if (item) {
            switch (type) {
                case 'barSeries':
                    return me.onDragBar(e);

                case 'scatterSeries':
                    return me.onDragScatter(e);
            }
        }
    },

    highlight: function(item) {
        var me = this,
            chart = me.getChart(),
            flipXY = chart.getFlipXY(),
            cursors = me.getCursors(),
            type = item && item.sprite.type,
            style = chart.el.dom.style;

        me.callParent([item]);

        if (item) {
            switch (type) {
                case 'barSeries':
                    if (flipXY) {
                        style.cursor = cursors.ewResize;
                    }
                    else {
                        style.cursor = cursors.nsResize;
                    }

                    break;

                case 'scatterSeries':
                    style.cursor = cursors.move;
                    break;
            }
        }
        else {
            chart.el.dom.style.cursor = 'default';
        }
    },

    onDragBar: function(e) {
        var me = this,
            chart = me.getChart(),
            isRtl = chart.getInherited().rtl,
            flipXY = chart.isCartesian && chart.getFlipXY(),
            item = chart.getHighlightItem(),
            marker = item.sprite.getMarker('items'),
            instance = marker.getMarkerFor(item.sprite.getId(), item.index),
            surface = item.sprite.getSurface(),
            surfaceRect = surface.getRect(),
            xy = surface.getEventXY(e),
            matrix = item.sprite.attr.matrix,
            renderer = me.getRenderer(),
            style, changes, params, positionY;

        if (flipXY) {
            positionY = isRtl ? surfaceRect[2] - xy[0] : xy[0];
        }
        else {
            positionY = surfaceRect[3] - xy[1];
        }

        style = {
            x: instance.x,
            y: positionY,
            width: instance.width,
            height: instance.height + (instance.y - positionY),
            radius: instance.radius,
            fillStyle: 'none',
            lineDash: [4, 4],
            zIndex: 100
        };
        Ext.apply(style, me.getStyle());

        if (Ext.isArray(item.series.getYField())) { // stacked bars
            positionY = positionY - instance.y - instance.height;
        }

        me.target = {
            index: item.index,
            yField: item.field,
            yValue: (positionY - matrix.getDY()) / matrix.getYY()
        };

        params = [chart, {
            target: me.target,
            style: style,
            item: item
        }];
        changes = Ext.callback(renderer, null, params, 0, chart);

        if (changes) {
            Ext.apply(style, changes);
        }

        // The interaction works by putting another series item instance
        // under 'itemedit' ID with a slightly different style (default) or
        // whatever style the user provided.
        item.sprite.putMarker('items', style, 'itemedit');

        me.showTooltip(e, me.target, item);
        surface.renderFrame();
    },

    onDragScatter: function(e) {
        var me = this,
            chart = me.getChart(),
            isRtl = chart.getInherited().rtl,
            flipXY = chart.isCartesian && chart.getFlipXY(),
            item = chart.getHighlightItem(),
            marker = item.sprite.getMarker('markers'),
            instance = marker.getMarkerFor(item.sprite.getId(), item.index),
            surface = item.sprite.getSurface(),
            surfaceRect = surface.getRect(),
            xy = surface.getEventXY(e),
            matrix = item.sprite.attr.matrix,
            xAxis = item.series.getXAxis(),
            isEditableX = xAxis && xAxis.getLayout().isContinuous,
            renderer = me.getRenderer(),
            style, changes, params,
            positionX, positionY,
            hintX, hintY;

        if (flipXY) {
            positionY = isRtl ? surfaceRect[2] - xy[0] : xy[0];
        }
        else {
            positionY = surfaceRect[3] - xy[1];
        }

        if (isEditableX) {
            if (flipXY) {
                positionX = surfaceRect[3] - xy[1];
            }
            else {
                positionX = xy[0];
            }
        }
        else {
            positionX = instance.translationX;
        }

        if (isEditableX) {
            hintX = xy[0];
            hintY = xy[1];
        }
        else {
            if (flipXY) {
                hintX = xy[0];
                hintY = instance.translationY; // no change
            }
            else {
                hintX = instance.translationX;
                hintY = xy[1]; // no change
            }
        }

        style = {
            translationX: hintX,
            translationY: hintY,
            scalingX: instance.scalingX,
            scalingY: instance.scalingY,
            r: instance.r,
            fillStyle: 'none',
            lineDash: [4, 4],
            zIndex: 100
        };
        Ext.apply(style, me.getStyle());

        me.target = {
            index: item.index,
            yField: item.field,
            yValue: (positionY - matrix.getDY()) / matrix.getYY()
        };

        if (isEditableX) {
            Ext.apply(me.target, {
                xField: item.series.getXField(),
                xValue: (positionX - matrix.getDX()) / matrix.getXX()
            });
        }

        params = [chart, {
            target: me.target,
            style: style,
            item: item
        }];
        changes = Ext.callback(renderer, null, params, 0, chart);

        if (changes) {
            Ext.apply(style, changes);
        }

        // This marker acts as a visual hint while dragging.
        item.sprite.putMarker('markers', style, 'itemedit');

        me.showTooltip(e, me.target, item);
        surface.renderFrame();
    },

    showTooltip: function(e, target, item) {
        var tooltip = this.getTooltip(),
            config, chart;

        if (tooltip && Ext.toolkit !== 'modern') {
            config = tooltip.config;
            chart = this.getChart();
            Ext.callback(config.renderer, null, [tooltip, item, target, e], 0, chart);
            // If trackMouse is set, a ToolTip shows by its pointerEvent
            tooltip.pointerEvent = e;

            if (tooltip.isVisible()) {
                // After show handling repositions according
                // to configuration. trackMouse uses the pointerEvent
                // If aligning to an element, it uses a currentTarget
                // flyweight which may be attached to any DOM element.
                tooltip.realignToTarget();
            }
            else {
                tooltip.show();
            }
        }
    },

    hideTooltip: function() {
        var tooltip = this.getTooltip();

        if (tooltip && Ext.toolkit !== 'modern') {
            tooltip.hide();
        }
    },

    onDragEnd: function(e) {
        var me = this,
            target = me.target,
            chart = me.getChart(),
            store = chart.getStore(),
            record;

        if (target) {
            record = store.getAt(target.index);

            if (target.yField) {
                record.set(target.yField, target.yValue, {
                    convert: false
                });
            }

            if (target.xField) {
                record.set(target.xField, target.xValue, {
                    convert: false
                });
            }

            if (target.yField || target.xField) {
                me.getChart().onDataChanged();
            }

            me.target = null;
        }

        me.hideTooltip();

        if (me.item) {
            chart.fireEvent('enditemedit', chart, me, me.item, target);
        }

        me.highlight(me.item = null);
    },

    destroy: function() {
        // Peek at the config, so we don't create one just to destroy it,
        // if a user has set 'tooltip' config to 'false'.
        var tooltip = this.getConfig('tooltip', true);

        Ext.destroy(tooltip);
        this.callParent();
    }

});
