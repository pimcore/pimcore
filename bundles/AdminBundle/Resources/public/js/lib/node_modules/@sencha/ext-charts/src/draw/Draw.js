/* global Float32Array */
/* eslint-disable indent */
(function() {
    if (!Ext.global.Float32Array) {
        // Typed Array polyfill
        // eslint-disable-next-line vars-on-top
        var Float32Array = function(array) {
            var i, len;

            if (typeof array === 'number') {
                this.length = array;
            }
            else if ('length' in array) {
                this.length = array.length;

                for (i = 0, len = array.length; i < len; i++) {
                    this[i] = +array[i];
                }
            }
        };

        Float32Array.prototype = [];
        Ext.global.Float32Array = Float32Array;
    }
})();
/* eslint-enable indent */

/**
 * Utility class providing mathematics functionalities through all the draw package.
 */
Ext.define('Ext.draw.Draw', {
    singleton: true,

    radian: Math.PI / 180,
    pi2: Math.PI * 2,

    /**
     * @deprecated 6.5.0 Please use the {@link Ext#identityFn} instead.
     * Function that returns its first element.
     * @param {Mixed} a
     * @return {Mixed}
     */
    reflectFn: function(a) {
        return a;
    },

    /**
     * Converting degrees to radians.
     * @param {Number} degrees
     * @return {Number}
     */
    rad: function(degrees) {
        return (degrees % 360) * this.radian;
    },

    /**
     * Converting radians to degrees.
     * @param {Number} radian
     * @return {Number}
     */
    degrees: function(radian) {
        return (radian / this.radian) % 360;
    },

    /**
     *
     * @param {Object} bbox1
     * @param {Object} bbox2
     * @param {Number} [padding]
     * @return {Boolean}
     */
    isBBoxIntersect: function(bbox1, bbox2, padding) {
        padding = padding || 0;

        return (Math.max(bbox1.x, bbox2.x) - padding >
                    Math.min(bbox1.x + bbox1.width, bbox2.x + bbox2.width)) ||
               (Math.max(bbox1.y, bbox2.y) - padding >
                    Math.min(bbox1.y + bbox1.height, bbox2.y + bbox2.height));
    },

    /**
     * Checks if a point is within a bounding box.
     * @param x
     * @param y
     * @param bbox
     * @return {Boolean}
     */
    isPointInBBox: function(x, y, bbox) {
        return !!bbox && x >= bbox.x &&
               x <= (bbox.x + bbox.width) && y >= bbox.y && y <= (bbox.y + bbox.height);
    },

    /**
     * Natural cubic spline interpolation.
     * This algorithm runs in linear time.
     *
     * @param {Array} points Array of numbers.
     */
    naturalSpline: function(points) {
        var i, j,
            ln = points.length,
            nd, d, y, ny,
            r = 0,
            zs = new Float32Array(points.length),
            result = new Float32Array(points.length * 3 - 2);

        zs[0] = 0;
        zs[ln - 1] = 0;

        for (i = 1; i < ln - 1; i++) {
            zs[i] = (points[i + 1] + points[i - 1] - 2 * points[i]) - zs[i - 1];
            r = 1 / (4 - r);
            zs[i] *= r;
        }

        for (i = ln - 2; i > 0; i--) {
            r = 3.732050807568877 + 48.248711305964385 /
                (-13.928203230275537 + Math.pow(0.07179676972449123, i));
            zs[i] -= zs[i + 1] * r;
        }

        ny = points[0];
        nd = ny - zs[0];

        for (i = 0, j = 0; i < ln - 1; j += 3) {
            y = ny;
            d = nd;
            i++;
            ny = points[i];
            nd = ny - zs[i];
            result[j] = y;
            result[j + 1] = (nd + 2 * d) / 3;
            result[j + 2] = (nd * 2 + d) / 3;
        }

        result[j] = ny;

        return result;
    },

    /**
     * Shorthand for {@link #naturalSpline}
     */
    spline: function(points) {
        return this.naturalSpline(points);
    },

    /**
     * @private
     * Cardinal spline interpolation.
     * Goes from cardinal control points to cubic Bezier control points.
     */
    cardinalToBezier: function(P1, P2, P3, P4, tension) {
        return [
            P2,
            P2 + (P3 - P1) / 6 * tension,
            P3 - (P4 - P2) / 6 * tension,
            P3
        ];
    },

    /**
     * @private
     * @param {Number[]} P An array of n x- or y-coordinates.
     * @param {Number} tension
     * @return {Float32Array} An array of 3n - 2 Bezier control points.
     */
    cardinalSpline: function(P, tension) {
        var n = P.length,
            result = new Float32Array(n * 3 - 2),
            i, bezier;

        if (tension === undefined) {
            tension = 0.5;
        }

        bezier = this.cardinalToBezier(P[0], P[0], P[1], P[2], tension);
        result[0] = bezier[0];
        result[1] = bezier[1];
        result[2] = bezier[2];
        result[3] = bezier[3];

        for (i = 0; i < n - 3; i++) {
            bezier = this.cardinalToBezier(P[i], P[i + 1], P[i + 2], P[i + 3], tension);
            result[4 + i * 3] = bezier[1];
            result[4 + i * 3 + 1] = bezier[2];
            result[4 + i * 3 + 2] = bezier[3];
        }

        bezier = this.cardinalToBezier(P[n - 3], P[n - 2], P[n - 1], P[n - 1], tension);
        result[4 + i * 3] = bezier[1];
        result[4 + i * 3 + 1] = bezier[2];
        result[4 + i * 3 + 2] = bezier[3];

        return result;
    },

    /**
     * @private
     *
     * Calculates bezier curve control anchor points for a particular point in a path, with a
     * smoothing curve applied. The smoothness of the curve is controlled by the 'value' parameter.
     * Note that this algorithm assumes that the line being smoothed is normalized going from left
     * to right; it makes special adjustments assuming this orientation.
     *
     * @param {Number} prevX X coordinate of the previous point in the path
     * @param {Number} prevY Y coordinate of the previous point in the path
     * @param {Number} curX X coordinate of the current point in the path
     * @param {Number} curY Y coordinate of the current point in the path
     * @param {Number} nextX X coordinate of the next point in the path
     * @param {Number} nextY Y coordinate of the next point in the path
     * @param {Number} value A value to control the smoothness of the curve; this is used to
     *                 divide the distance between points, so a value of 2 corresponds to
     *                 half the distance between points (a very smooth line) while higher values
     *                 result in less smooth curves. Defaults to 4.
     * @return {Object} Object containing x1, y1, x2, y2 bezier control anchor points; x1 and y1
     *                  are the control point for the curve toward the previous path point, and
     *                  x2 and y2 are the control point for the curve toward the next path point.
     */
    getAnchors: function(prevX, prevY, curX, curY, nextX, nextY, value) {
        var PI = Math.PI,
            halfPI = PI / 2,
            abs = Math.abs,
            sin = Math.sin,
            cos = Math.cos,
            atan = Math.atan,
            control1Length, control2Length, control1Angle, control2Angle,
            control1X, control1Y, control2X, control2Y, alpha;

        value = value || 4;

        // Find the length of each control anchor line, by dividing the horizontal distance
        // between points by the value parameter.
        control1Length = (curX - prevX) / value;
        control2Length = (nextX - curX) / value;

        // Determine the angle of each control anchor line. If the middle point is a vertical
        // turnaround then we force it to a flat horizontal angle to prevent the curve from
        // dipping above or below the middle point. Otherwise we use an angle that points
        // toward the previous/next target point.
        if ((curY >= prevY && curY >= nextY) || (curY <= prevY && curY <= nextY)) {
            control1Angle = control2Angle = halfPI;
        }
        else {
            control1Angle = atan((curX - prevX) / abs(curY - prevY));

            if (prevY < curY) {
                control1Angle = PI - control1Angle;
            }

            control2Angle = atan((nextX - curX) / abs(curY - nextY));

            if (nextY < curY) {
                control2Angle = PI - control2Angle;
            }
        }

        // Adjust the calculated angles so they point away from each other on the same line
        alpha = halfPI - ((control1Angle + control2Angle) % (PI * 2)) / 2;

        if (alpha > halfPI) {
            alpha -= PI;
        }

        control1Angle += alpha;
        control2Angle += alpha;

        // Find the control anchor points from the angles and length
        control1X = curX - control1Length * sin(control1Angle);
        control1Y = curY + control1Length * cos(control1Angle);
        control2X = curX + control2Length * sin(control2Angle);
        control2Y = curY + control2Length * cos(control2Angle);

        // One last adjustment, make sure that no control anchor point extends vertically past
        // its target prev/next point, as that results in curves dipping above or below and
        // bending back strangely. If we find this happening we keep the control angle but
        // reduce the length of the control line so it stays within bounds.
        if ((curY > prevY && control1Y < prevY) || (curY < prevY && control1Y > prevY)) {
            control1X += abs(prevY - control1Y) * (control1X - curX) / (control1Y - curY);
            control1Y = prevY;
        }

        if ((curY > nextY && control2Y < nextY) || (curY < nextY && control2Y > nextY)) {
            control2X -= abs(nextY - control2Y) * (control2X - curX) / (control2Y - curY);
            control2Y = nextY;
        }

        return {
            x1: control1X,
            y1: control1Y,
            x2: control2X,
            y2: control2Y
        };
    },

    /**
     * Given coordinates of the points, calculates coordinates of a Bezier curve
     * that goes through them.
     * @param dataX x-coordinates of the points.
     * @param dataY y-coordinates of the points.
     * @param value A value to control the smoothness of the curve.
     * @return {Object} Object holding two arrays, for x and y coordinates of the curve.
     */
    smooth: function(dataX, dataY, value) {
        var ln = dataX.length,
            prevX, prevY,
            curX, curY,
            nextX, nextY,
            x, y,
            smoothX = [],
            smoothY = [],
            i, anchors;

        for (i = 0; i < ln - 1; i++) {
            prevX = dataX[i];
            prevY = dataY[i];

            if (i === 0) {
                x = prevX;
                y = prevY;
                smoothX.push(x);
                smoothY.push(y);

                if (ln === 1) {
                    break;
                }
            }

            curX = dataX[i + 1];
            curY = dataY[i + 1];
            nextX = dataX[i + 2];
            nextY = dataY[i + 2];

            if (!(Ext.isNumber(nextX) && Ext.isNumber(nextY))) {
                smoothX.push(x, curX, curX);
                smoothY.push(y, curY, curY);

                break;
            }

            anchors = this.getAnchors(prevX, prevY, curX, curY, nextX, nextY, value);
            smoothX.push(x, anchors.x1, curX);
            smoothY.push(y, anchors.y1, curY);
            x = anchors.x2;
            y = anchors.y2;
        }

        return {
            smoothX: smoothX,
            smoothY: smoothY
        };
    },

    /**
     * @method
     * @private
     * Work around for iOS.
     * Nested 3d-transforms seems to prevent the redraw inside it until some event is fired.
     */
    beginUpdateIOS: Ext.os.is.iOS
        ? function() {
            this.iosUpdateEl = Ext.getBody().createChild({
                //<debug>
                'data-sticky': true,
                //</debug>
                style: 'position: absolute; top: 0px; bottom: 0px; left: 0px; right: 0px; ' +
                       'background: rgba(0,0,0,0.001); z-index: 100000'
            });
        }
        : Ext.emptyFn,

    endUpdateIOS: function() {
        this.iosUpdateEl = Ext.destroy(this.iosUpdateEl);
    }
});
