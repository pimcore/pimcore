/**
 * Mixin that provides the functionality to place markers.
 */
Ext.define('Ext.chart.MarkerHolder', {
    extend: 'Ext.Mixin',

    requires: [
        'Ext.chart.Markers'
    ],

    mixinConfig: {
        id: 'markerHolder',
        after: {
            constructor: 'constructor',
            preRender: 'preRender'
        },
        before: {
            destroy: 'destroy'
        }
    },

    isMarkerHolder: true,

    // The combined transformation applied to the sprite by its parents.
    // Does not include the transformation matrix of the sprite itself.
    surfaceMatrix: null,
    // The inverse of the above transformation to go back to the original state.
    inverseSurfaceMatrix: null,

    deprecated: {
        6: {
            methods: {
                /**
                 * Returns the markers bound to the given name.
                 * @param {String} name The name of the marker (e.g., "items", "labels", etc.).
                 * @return {Ext.chart.Markers[]}
                 * @method getBoundMarker
                 * @deprecated 6.0 Use {@link #getMarker} instead.
                 */
                getBoundMarker: {
                    message: "Please use the 'getMarker' method instead.",
                    fn: function(name) {
                        var marker = this.boundMarkers[name];

                        return marker ? [marker] : marker;
                    }
                }
            }
        }
    },

    constructor: function() {
        this.boundMarkers = {};
        this.cleanRedraw = false;
    },

    /**
     * Registers the given marker with the marker holder under the specified name.
     * @param {String} name The name of the marker (e.g., "items", "labels", etc.).
     * @param {Ext.chart.Markers} marker
     */
    bindMarker: function(name, marker) {
        var me = this,
            markers = me.boundMarkers;

        if (marker && marker.isMarkers) {
            //<debug>
            if (markers[name] && markers[name] === marker) {
                Ext.log.warn(me.getId(), " (MarkerHolder): the Markers instance '",
                             marker.getId(), "' is already bound under the name '", name, "'.");
            }

            //</debug>
            me.releaseMarker(name);
            markers[name] = marker;
            marker.on('destroy', me.onMarkerDestroy, me);
        }
    },

    onMarkerDestroy: function(marker) {
        this.releaseMarker(marker);
    },

    /**
     * Unregisters the given marker or a marker with the given name.
     * Providing a name of the marker is more efficient as it avoids lookup.
     * @param marker {String/Ext.chart.Markers}
     * @return {Ext.chart.Markers} Released marker or null.
     */
    releaseMarker: function(marker) {
        var markers = this.boundMarkers,
            name;

        if (marker && marker.isMarkers) {
            for (name in markers) {
                if (markers[name] === marker) {
                    delete markers[name];
                    break;
                }
            }
        }
        else {
            name = marker;
            marker = markers[name];
            delete markers[name];
        }

        return marker || null;
    },

    /**
     * Returns the marker bound to the given name (or null). See {@link #bindMarker}.
     * @param {String} name The name of the marker (e.g., "items", "labels", etc.).
     * @return {Ext.chart.Markers}
     */
    getMarker: function(name) {
        return this.boundMarkers[name] || null;
    },

    preRender: function(surface, ctx, rect) {
        var me = this,
            id = me.getId(),
            boundMarkers = me.boundMarkers,
            parent = me.getParent(),
            name, marker,
            matrix;

        if (me.surfaceMatrix) {
            matrix = me.surfaceMatrix.set(1, 0, 0, 1, 0, 0);
        }
        else {
            matrix = me.surfaceMatrix = new Ext.draw.Matrix();
        }

        me.cleanRedraw = !me.attr.dirty;

        if (!me.cleanRedraw) {
            for (name in boundMarkers) {
                marker = boundMarkers[name];

                if (marker) {
                    marker.clear(id);
                }
            }
        }

        // Parent can be either a sprite (like a composite or instancing)
        // or a surface. First, climb up and apply transformations of the
        // parent sprites.
        while (parent && parent.attr && parent.attr.matrix) {
            matrix.prependMatrix(parent.attr.matrix);
            parent = parent.getParent();
        }

        // Finally, apply the transformation used by the surface.
        matrix.prependMatrix(parent.matrix);
        me.surfaceMatrix = matrix;
        me.inverseSurfaceMatrix = matrix.inverse(me.inverseSurfaceMatrix);
    },

    putMarker: function(name, attr, index, bypassNormalization, keepRevision) {
        var marker = this.boundMarkers[name];

        if (marker) {
            marker.putMarkerFor(this.getId(), attr, index, bypassNormalization, keepRevision);
        }
    },

    getMarkerBBox: function(name, index, isWithoutTransform) {
        var marker = this.boundMarkers[name];

        if (marker) {
            return marker.getMarkerBBoxFor(this.getId(), index, isWithoutTransform);
        }
    },

    destroy: function() {
        var boundMarkers = this.boundMarkers,
            name, marker;

        for (name in boundMarkers) {
            marker = boundMarkers[name];
            marker.destroy();
        }
    }
});
