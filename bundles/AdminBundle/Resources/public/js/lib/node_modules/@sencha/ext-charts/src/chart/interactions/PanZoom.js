/**
 * The PanZoom interaction allows the user to navigate the data for one or more chart
 * axes by panning and/or zooming. Navigation can be limited to particular axes. Zooming is
 * performed by pinching on the chart or axis area; panning is performed by single-touch dragging.
 * The interaction only works with cartesian charts/series.
 *
 * For devices which do not support multiple-touch events, zooming can not be done via pinch
 * gestures; in this case the interaction will allow the user to perform both zooming and panning
 * using the same single-touch drag gesture.
 * {@link #modeToggleButton} provides a button to indicate and toggle between two modes.
 *
 *     @example
 *     Ext.create({
 *         renderTo: document.body,
 *         xtype: 'cartesian',
 *         width: 600,
 *         height: 400,
 *         insetPadding: 40,            
 *         interactions: [{
 *             type: 'panzoom',
 *             zoomOnPan: true
 *         }],
 *         store: {
 *             fields: ['name', 'data1', 'data2', 'data3', 'data4', 'data5'],
 *             data: [{
 *                 'name': 'metric one',
 *                 'data1': 10,
 *                 'data2': 12,
 *                 'data3': 14,
 *                 'data4': 8,
 *                 'data5': 13
 *             }, {
 *                 'name': 'metric two',
 *                 'data1': 7,
 *                 'data2': 8,
 *                 'data3': 16,
 *                 'data4': 10,
 *                 'data5': 3
 *             }, {
 *                 'name': 'metric three',
 *                 'data1': 5,
 *                 'data2': 2,
 *                 'data3': 14,
 *                 'data4': 12,
 *                 'data5': 7
 *             }, {
 *                 'name': 'metric four',
 *                 'data1': 2,
 *                 'data2': 14,
 *                 'data3': 6,
 *                 'data4': 1,
 *                 'data5': 23
 *             }, {
 *                 'name': 'metric five',
 *                 'data1': 27,
 *                 'data2': 38,
 *                 'data3': 36,
 *                 'data4': 13,
 *                 'data5': 33
 *             }]
 *         },
 *         axes: [{
 *             type: 'numeric',
 *             position: 'left',
 *             fields: ['data1'],
 *             title: {
 *                 text: 'Sample Values',
 *                 fontSize: 15
 *             },
 *             grid: true,
 *             minimum: 0
 *         }, {
 *             type: 'category',
 *             position: 'bottom',
 *             fields: ['name'],
 *             title: {
 *                 text: 'Sample Values',
 *                 fontSize: 15
 *             }
 *         }],
 *         series: [{
 *             type: 'line',
 *             highlight: {
 *                 size: 7,
 *                 radius: 7
 *             },
 *             style: {
 *                 stroke: 'rgb(143,203,203)'
 *             },
 *             xField: 'name',
 *             yField: 'data1',
 *             marker: {
 *                 type: 'path',
 *                 path: ['M', - 2, 0, 0, 2, 2, 0, 0, - 2, 'Z'],
 *                 stroke: 'blue',
 *                 lineWidth: 0
 *             }
 *         }, {
 *             type: 'line',
 *             highlight: {
 *                 size: 7,
 *                 radius: 7
 *             },
 *             fill: true,
 *             xField: 'name',
 *             yField: 'data3',
 *             marker: {
 *                 type: 'circle',
 *                 radius: 4,
 *                 lineWidth: 0
 *             }
 *         }]
 *     });
 * 
 * The configuration object for the `panzoom` interaction type should specify which axes
 * will be made navigable via the `axes` config. See the {@link #axes} config documentation
 * for details on the allowed formats. If the `axes` config is not specified, it will default
 * to making all axes navigable with the default axis options.
 *
 */
Ext.define('Ext.chart.interactions.PanZoom', {

    extend: 'Ext.chart.interactions.Abstract',

    type: 'panzoom',
    alias: 'interaction.panzoom',
    requires: [
        'Ext.draw.Animator'
    ],

    config: {

        /**
         * @cfg {Object/Array} axes
         * Specifies which axes should be made navigable. The config value can take the following
         * formats:
         *
         * - An Object with keys corresponding to the {@link Ext.chart.axis.Axis#position position}
         *   of each axis that should be made navigable. Each key's value can either be an Object
         *   with further configuration options for each axis or simply `true` for a default set
         *   of options.
         *
         *       {
         *           type: 'panzoom',
         *           axes: {
         *               left: {
         *                   maxZoom: 5,
         *                   allowPan: false
         *               },
         *               bottom: true
         *           }
         *       }
         *
         *   If using the full Object form, the following options can be specified for each axis:
         *
         *   - minZoom (Number) A minimum zoom level for the axis. Defaults to `1` which is its
         *     natural size.
         *   - maxZoom (Number) A maximum zoom level for the axis. Defaults to `10`.
         *   - startZoom (Number) A starting zoom level for the axis. Defaults to `1`.
         *   - allowZoom (Boolean) Whether zooming is allowed for the axis. Defaults to `true`.
         *   - allowPan (Boolean) Whether panning is allowed for the axis. Defaults to `true`.
         *   - startPan (Boolean) A starting panning offset for the axis. Defaults to `0`.
         *
         * - An Array of strings, each one corresponding to the {@link Ext.chart.axis.Axis#position
         *   position} of an axis that should be made navigable. The default options will be used
         *   for each named axis.
         *
         *       {
         *           type: 'panzoom',
         *           axes: ['left', 'bottom']
         *       }
         *
         * If the `axes` config is not specified, it will default to making all axes navigable
         * with the default axis options.
         */
        axes: {
            top: {},
            right: {},
            bottom: {},
            left: {}
        },

        minZoom: null,

        maxZoom: null,

        /**
         * @cfg {Boolean} showOverflowArrows
         * If `true`, arrows will be conditionally shown at either end of each axis to indicate that
         * the axis is overflowing and can therefore be panned in that direction. Set this
         * to `false` to prevent the arrows from being displayed.
         */
        showOverflowArrows: true,

        /**
         * @cfg {Object} overflowArrowOptions
         * A set of optional overrides for the overflow arrow sprites' options. Only relevant when
         * {@link #showOverflowArrows} is `true`.
         */

        /**
         * @cfg {String} panGesture
         * Defines the gesture that initiates panning.
         * @private
         */
        panGesture: 'drag',

        /**
         * @cfg {String} zoomGesture
         * Defines the gesture that initiates zooming.
         * @private
         */
        zoomGesture: 'pinch',

        /**
         * @cfg {Boolean} zoomOnPanGesture
         * @deprecated 6.2 Please use {@link #zoomOnPan} instead.
         * If `true`, the pan gesture will zoom the chart.
         */
        zoomOnPanGesture: null,

        /**
         * @cfg {Boolean} zoomOnPan
         * If `true`, the pan gesture will zoom the chart.
         */
        zoomOnPan: false,

        /**
         * @cfg {Boolean} [doubleTapReset=false]
         * If `true`, the double tap on a chart will reset the current pan/zoom to show the whole
         * chart.
         */
        doubleTapReset: false,

        modeToggleButton: {
            xtype: 'segmentedbutton',
            width: 200,
            defaults: { ui: 'default-toolbar' },
            cls: Ext.baseCSSPrefix + 'panzoom-toggle',
            items: [{
                text: 'Pan',
                value: 'pan'
            }, {
                text: 'Zoom',
                value: 'zoom'
            }]
        },

        hideLabelInGesture: false // Ext.os.is.Android
    },

    stopAnimationBeforeSync: true,

    applyAxes: function(axesConfig, oldAxesConfig) {
        return Ext.merge(oldAxesConfig || {}, axesConfig);
    },

    updateZoomOnPan: function(zoomOnPan) {
        var button = this.getModeToggleButton();

        button.setValue(zoomOnPan ? 'zoom' : 'pan');
    },

    updateZoomOnPanGesture: function(zoomOnPanGesture) {
        this.setZoomOnPan(zoomOnPanGesture);
    },

    getZoomOnPanGesture: function() {
        return this.getZoomOnPan();
    },

    applyModeToggleButton: function(button, oldButton) {
        return Ext.factory(button, 'Ext.button.Segmented', oldButton);
    },

    updateModeToggleButton: function(button) {
        if (button) {
            button.on('change', 'onModeToggleChange', this);
        }
    },

    onModeToggleChange: function(segmentedButton, value) {
        this.setZoomOnPan(value === 'zoom');
    },

    getGestures: function() {
        var me = this,
            gestures = {},
            pan = me.getPanGesture(),
            zoom = me.getZoomGesture();

        gestures[zoom] = 'onZoomGestureMove';
        gestures[zoom + 'start'] = 'onZoomGestureStart';
        gestures[zoom + 'end'] = 'onZoomGestureEnd';
        gestures[pan] = 'onPanGestureMove';
        gestures[pan + 'start'] = 'onPanGestureStart';
        gestures[pan + 'end'] = 'onPanGestureEnd';
        gestures.doubletap = 'onDoubleTap';

        return gestures;
    },

    onDoubleTap: function(e) {
        var me = this,
            doubleTapReset = me.getDoubleTapReset(),
            chart, axes, axis, i, ln;

        if (doubleTapReset) {
            chart = me.getChart();
            axes = chart.getAxes();

            for (i = 0, ln = axes.length; i < ln; i++) {
                axis = axes[i];
                axis.setVisibleRange([0, 1]);
            }

            chart.redraw();
        }
    },

    onPanGestureStart: function(e) {
        var me = this,
            chart, rect, xy;

        if (!e || !e.touches || e.touches.length < 2) { // Limit drags to single touch
            chart = me.getChart();
            rect = chart.getInnerRect();
            xy = chart.element.getXY();

            e.claimGesture();
            chart.suspendAnimation();

            me.startX = e.getX() - xy[0] - rect[0];
            me.startY = e.getY() - xy[1] - rect[1];
            me.oldVisibleRanges = null;
            me.hideLabels();
            chart.suspendThicknessChanged();
            me.lockEvents(me.getPanGesture());

            return false;
        }
    },

    onPanGestureMove: function(e) {
        var me = this,
            isMouse = e.pointerType === 'mouse',
            isZoomOnPan = isMouse && me.getZoomOnPan(),
            chart, rect, xy;

        if (me.getLocks()[me.getPanGesture()] === me) { // Limit drags to single touch.
            chart = me.getChart();
            rect = chart.getInnerRect();
            xy = chart.element.getXY();

            if (isZoomOnPan) {
                me.transformAxesBy(
                    me.getZoomableAxes(e), 0, 0,
                    (e.getX() - xy[0] - rect[0]) / me.startX,
                    me.startY / (e.getY() - xy[1] - rect[1])
                );
            }
            else {
                me.transformAxesBy(
                    me.getPannableAxes(e),
                    e.getX() - xy[0] - rect[0] - me.startX,
                    e.getY() - xy[1] - rect[1] - me.startY,
                    1, 1);
            }

            me.sync();

            return false;
        }
    },

    onPanGestureEnd: function(e) {
        var me = this,
            pan = me.getPanGesture(),
            chart;

        if (me.getLocks()[pan] === me) {
            chart = me.getChart();
            chart.resumeThicknessChanged();
            me.showLabels();
            me.sync();
            me.unlockEvents(pan);
            chart.resumeAnimation();

            return false;
        }
    },

    onZoomGestureStart: function(e) {
        if (e.touches && e.touches.length === 2) {
            // eslint-disable-next-line vars-on-top
            var me = this,
                chart = me.getChart(),
                xy = chart.element.getXY(),
                rect = chart.getInnerRect(),
                x = xy[0] + rect[0],
                y = xy[1] + rect[1],
                newPoints = [
                    e.touches[0].point.x - x, e.touches[0].point.y - y,
                    e.touches[1].point.x - x, e.touches[1].point.y - y
                ],
                xDistance = Math.max(44, Math.abs(newPoints[2] - newPoints[0])),
                yDistance = Math.max(44, Math.abs(newPoints[3] - newPoints[1]));

            e.claimGesture();
            chart.suspendAnimation();
            chart.suspendThicknessChanged();
            me.lastZoomDistances = [xDistance, yDistance];
            me.lastPoints = newPoints;
            me.oldVisibleRanges = null;
            me.hideLabels();
            me.lockEvents(me.getZoomGesture());

            return false;
        }
    },

    onZoomGestureMove: function(e) {
        var me = this;

        if (me.getLocks()[me.getZoomGesture()] === me) {
            // eslint-disable-next-line vars-on-top
            var chart = me.getChart(),
                rect = chart.getInnerRect(),
                xy = chart.element.getXY(),
                x = xy[0] + rect[0],
                y = xy[1] + rect[1],
                abs = Math.abs,
                lastPoints = me.lastPoints,
                newPoints = [
                    e.touches[0].point.x - x, e.touches[0].point.y - y,
                    e.touches[1].point.x - x, e.touches[1].point.y - y
                ],
                xDistance = Math.max(44, abs(newPoints[2] - newPoints[0])),
                yDistance = Math.max(44, abs(newPoints[3] - newPoints[1])),
                lastDistances = this.lastZoomDistances || [xDistance, yDistance],
                zoomX = xDistance / lastDistances[0],
                zoomY = yDistance / lastDistances[1];

            me.transformAxesBy(me.getZoomableAxes(e),
                               rect[2] * (zoomX - 1) / 2 + newPoints[2] - lastPoints[2] * zoomX,
                               rect[3] * (zoomY - 1) / 2 + newPoints[3] - lastPoints[3] * zoomY,
                               zoomX,
                               zoomY);
            me.sync();

            return false;
        }
    },

    onZoomGestureEnd: function(e) {
        var me = this,
            zoom = me.getZoomGesture(),
            chart;

        if (me.getLocks()[zoom] === me) {
            chart = me.getChart();
            chart.resumeThicknessChanged();
            me.showLabels();
            me.sync();
            me.unlockEvents(zoom);
            chart.resumeAnimation();

            return false;
        }
    },

    hideLabels: function() {
        if (this.getHideLabelInGesture()) {
            this.eachInteractiveAxes(function(axis) {
                axis.hideLabels();
            });
        }
    },

    showLabels: function() {
        if (this.getHideLabelInGesture()) {
            this.eachInteractiveAxes(function(axis) {
                axis.showLabels();
            });
        }
    },

    isEventOnAxis: function(e, axis) {
        // TODO: right now this uses the current event position but really we want to only
        // use the gesture's start event. Pinch does not give that to us though.
        var rect = axis.getSurface().getRect();

        return rect[0] <= e.getX() && e.getX() <= rect[0] + rect[2] &&
               rect[1] <= e.getY() && e.getY() <= rect[1] + rect[3];
    },

    getPannableAxes: function(e) {
        var me = this,
            axisConfigs = me.getAxes(),
            axes = me.getChart().getAxes(),
            i,
            ln = axes.length,
            result = [],
            isEventOnAxis = false,
            config;

        if (e) {
            for (i = 0; i < ln; i++) {
                if (this.isEventOnAxis(e, axes[i])) {
                    isEventOnAxis = true;
                    break;
                }
            }
        }

        for (i = 0; i < ln; i++) {
            config = axisConfigs[axes[i].getPosition()];

            if (config && config.allowPan !== false &&
                (!isEventOnAxis || this.isEventOnAxis(e, axes[i]))) {
                result.push(axes[i]);
            }
        }

        return result;
    },

    getZoomableAxes: function(e) {
        var me = this,
            axisConfigs = me.getAxes(),
            axes = me.getChart().getAxes(),
            result = [],
            i,
            ln = axes.length,
            axis,
            isEventOnAxis = false,
            config;

        if (e) {
            for (i = 0; i < ln; i++) {
                if (this.isEventOnAxis(e, axes[i])) {
                    isEventOnAxis = true;
                    break;
                }
            }
        }

        for (i = 0; i < ln; i++) {
            axis = axes[i];
            config = axisConfigs[axis.getPosition()];

            if (config && config.allowZoom !== false &&
                (!isEventOnAxis || this.isEventOnAxis(e, axis))) {
                result.push(axis);
            }
        }

        return result;
    },

    eachInteractiveAxes: function(fn) {
        var me = this,
            axisConfigs = me.getAxes(),
            axes = me.getChart().getAxes(),
            i;

        for (i = 0; i < axes.length; i++) {
            if (axisConfigs[axes[i].getPosition()]) {
                if (false === fn.call(this, axes[i])) {
                    return;
                }
            }
        }
    },

    transformAxesBy: function(axes, panX, panY, sx, sy) {
        var rect = this.getChart().getInnerRect(),
            axesCfg = this.getAxes(),
            oldVisibleRanges = this.oldVisibleRanges,
            result = false,
            axisCfg, i;

        if (!oldVisibleRanges) {
            this.oldVisibleRanges = oldVisibleRanges = {};
            this.eachInteractiveAxes(function(axis) {
                oldVisibleRanges[axis.getId()] = axis.getVisibleRange();
            });
        }

        if (!rect) {
            return;
        }

        for (i = 0; i < axes.length; i++) {
            axisCfg = axesCfg[axes[i].getPosition()];
            result = this.transformAxisBy(
                axes[i], oldVisibleRanges[axes[i].getId()], panX, panY, sx, sy,
                this.minZoom || axisCfg.minZoom, this.maxZoom || axisCfg.maxZoom) || result;
        }

        return result;
    },

    transformAxisBy: function(axis, oldVisibleRange, panX, panY, sx, sy, minZoom, maxZoom) {
        var me = this,
            visibleLength = oldVisibleRange[1] - oldVisibleRange[0],
            visibleRange = axis.getVisibleRange(),
            actualMinZoom = minZoom || me.getMinZoom() || axis.config.minZoom,
            actualMaxZoom = maxZoom || me.getMaxZoom() || axis.config.maxZoom,
            rect = me.getChart().getInnerRect(),
            left, right, isSide, pan, length;

        if (!rect) {
            return;
        }

        isSide = axis.isSide();
        length = isSide ? rect[3] : rect[2];
        pan = isSide ? -panY : panX;

        visibleLength /= isSide ? sy : sx;

        if (visibleLength < 0) {
            visibleLength = -visibleLength;
        }

        if (visibleLength * actualMinZoom > 1) {
            visibleLength = 1;
        }

        if (visibleLength * actualMaxZoom < 1) {
            visibleLength = 1 / actualMaxZoom;
        }

        left = oldVisibleRange[0];
        right = oldVisibleRange[1];

        visibleRange = visibleRange[1] - visibleRange[0];

        if (visibleLength === visibleRange && visibleRange === 1) {
            return;
        }

        axis.setVisibleRange([
            (oldVisibleRange[0] + oldVisibleRange[1] - visibleLength) * 0.5 -
            pan / length * visibleLength,
            (oldVisibleRange[0] + oldVisibleRange[1] + visibleLength) * 0.5 -
            pan / length * visibleLength
        ]);

        return Math.abs(left - axis.getVisibleRange()[0]) > 1e-10 ||
               Math.abs(right - axis.getVisibleRange()[1]) > 1e-10;
    },

    destroy: function() {
        this.setModeToggleButton(null);
        this.callParent();
    }
});
