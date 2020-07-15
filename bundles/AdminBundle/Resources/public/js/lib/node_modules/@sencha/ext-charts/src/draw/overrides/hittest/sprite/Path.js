/**
 * @private
 * Adds hit testing methods to the Ext.draw.sprite.Path sprite.
 * Included by the Ext.draw.PathUtil.
 */
Ext.define('Ext.draw.overrides.hittest.sprite.Path', {
    override: 'Ext.draw.sprite.Path',
    requires: ['Ext.draw.Color'],

    /**
     * Tests whether the given point is inside the path.
     * @param x
     * @param y
     * @return {Boolean}
     * @member Ext.draw.sprite.Path
     */
    isPointInPath: function(x, y) {
        var attr = this.attr,
            path, matrix, params, result;

        if (attr.fillStyle === Ext.util.Color.RGBA_NONE) {
            return this.isPointOnPath(x, y);
        }

        path = attr.path;
        matrix = attr.matrix;

        if (!matrix.isIdentity()) {
            params = path.params.slice(0);
            path.transform(attr.matrix);
        }

        result = path.isPointInPath(x, y);

        if (params) {
            path.params = params;
        }

        return result;
    },

    /**
     * Tests whether the given point is on the path.
     * @param x
     * @param y
     * @return {Boolean}
     * @member Ext.draw.sprite.Path
     */
    isPointOnPath: function(x, y) {
        var attr = this.attr,
            path = attr.path,
            matrix = attr.matrix,
            params, result;

        if (!matrix.isIdentity()) {
            params = path.params.slice(0);
            path.transform(attr.matrix);
        }

        result = path.isPointOnPath(x, y);

        if (params) {
            path.params = params;
        }

        return result;
    },

    /**
     * @method hitTest
     * @inheritdoc Ext.draw.Surface#method-hitTest
     */
    hitTest: function(point, options) {
        var me = this,
            attr = me.attr,
            path = attr.path,
            matrix = attr.matrix,
            x = point[0],
            y = point[1],
            parentResult = me.callParent([point, options]),
            result = null,
            params, isFilled;

        if (!parentResult) {
            // The sprite is not visible or bounding box wasn't hit.
            return result;
        }

        options = options || Ext.draw.sprite.Sprite.defaultHitTestOptions;

        if (!matrix.isIdentity()) {
            params = path.params.slice(0);
            path.transform(attr.matrix);
        }

        if (options.fill && options.stroke) {
            isFilled = attr.fillStyle !== Ext.util.Color.NONE &&
                       attr.fillStyle !== Ext.util.Color.RGBA_NONE;

            if (isFilled) {
                if (path.isPointInPath(x, y)) {
                    result = {
                        sprite: me
                    };
                }
            }
            else {
                if (path.isPointInPath(x, y) || path.isPointOnPath(x, y)) {
                    result = {
                        sprite: me
                    };
                }
            }
        }
        else if (options.stroke && !options.fill) {
            if (path.isPointOnPath(x, y)) {
                result = {
                    sprite: me
                };
            }
        }
        else if (options.fill && !options.stroke) {
            if (path.isPointInPath(x, y)) {
                result = {
                    sprite: me
                };
            }
        }

        if (params) {
            path.params = params;
        }

        return result;
    },

    /**
     * Returns all points where this sprite intersects the given sprite.
     * The given sprite must be an instance of the {@link Ext.draw.sprite.Path} class
     * or its subclass.
     * @param path
     * @return {Array}
     * @member Ext.draw.sprite.Path
     */
    getIntersections: function(path) {
        if (!(path.isSprite && path.isPath)) {
            return [];
        }

        // eslint-disable-next-line vars-on-top
        var aAttr = this.attr,
            bAttr = path.attr,
            aPath = aAttr.path,
            bPath = bAttr.path,
            aMatrix = aAttr.matrix,
            bMatrix = bAttr.matrix,
            aParams, bParams,
            intersections;

        if (!aMatrix.isIdentity()) {
            aParams = aPath.params.slice(0);
            aPath.transform(aAttr.matrix);
        }

        if (!bMatrix.isIdentity()) {
            bParams = bPath.params.slice(0);
            bPath.transform(bAttr.matrix);
        }

        intersections = aPath.getIntersections(bPath);

        if (aParams) {
            aPath.params = aParams;
        }

        if (bParams) {
            bPath.params = bParams;
        }

        return intersections;
    }
});
