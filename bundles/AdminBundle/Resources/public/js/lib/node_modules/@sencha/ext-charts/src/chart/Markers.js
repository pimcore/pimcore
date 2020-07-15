/**
 * @class Ext.chart.Markers
 * @extends Ext.draw.sprite.Instancing
 * 
 * Marker sprite. A specialized version of instancing sprite that groups instances.
 * Putting a marker is grouped by its category id. Clearing removes that category.
 */
Ext.define('Ext.chart.Markers', {
    extend: 'Ext.draw.sprite.Instancing',
    isMarkers: true,
    defaultCategory: 'default',

    constructor: function() {
        this.callParent(arguments);
        // `categories` maps category names to a map that maps instance index in category to its
        // global index: categoryName: {instanceIndexInCategory: globalInstanceIndex}
        this.categories = {};
        // The `revisions` map keeps revision numbers of instance categories.
        // When a marker (instance) is put (created or updated), it gets the revision
        // of the category. When a category is cleared, its revision is incremented,
        // but its instances are not removed.
        // An instance is only rendered if its revision matches category revision.
        // In other words, a marker has to be put again after its category has been cleared
        // or it won't render.
        this.revisions = {};
    },

    destroy: function() {
        this.categories = null;
        this.revisions = null;
        this.callParent();
    },

    getMarkerFor: function(category, index) {
        var categoryInstances;

        if (category in this.categories) {
            categoryInstances = this.categories[category];

            if (index in categoryInstances) {
                return this.get(categoryInstances[index]);
            }
        }
    },

    /**
     * Clears the markers in the category.
     * @param {String} category
     */
    clear: function(category) {
        category = category || this.defaultCategory;

        if (!(category in this.revisions)) {
            this.revisions[category] = 1;
        }
        else {
            this.revisions[category]++;
        }
    },

    clearAll: function() {
        this.callParent();
        this.categories = {};
        this.revisions = {};
    },

    /**
     * Puts a marker in the category with additional attributes.
     * @param {String} category
     * @param {Object} attr
     * @param {String|Number} index
     * @param {Boolean} [bypassNormalization]
     * @param {Boolean} [keepRevision]
     */
    putMarkerFor: function(category, attr, index, bypassNormalization, keepRevision) {
        var me = this,
            categoryInstances, instance;

        category = category || this.defaultCategory;
        categoryInstances = me.categories[category] || (me.categories[category] = {});

        if (index in categoryInstances) {
            me.setAttributesFor(categoryInstances[index], attr, bypassNormalization);
        }
        else {
            // get the index of the instance created on next line
            categoryInstances[index] = me.getCount();
            me.add(attr, bypassNormalization);
        }

        instance = me.get(categoryInstances[index]);

        if (instance) {
            instance.category = category;

            if (!keepRevision) {
                instance.revision = me.revisions[category] || (me.revisions[category] = 1);
            }
        }
    },

    /**
     *
     * @param {String} category
     * @param {Mixed} index
     * @param {Boolean} [isWithoutTransform]
     */
    getMarkerBBoxFor: function(category, index, isWithoutTransform) {
        var categoryInstances;

        if (category in this.categories) {
            categoryInstances = this.categories[category];

            if (index in categoryInstances) {
                return this.getBBoxFor(categoryInstances[index], isWithoutTransform);
            }
        }
    },

    getBBox: function() {
        return null;
    },

    render: function(surface, ctx, rect) {
        var me = this,
            surfaceRect = surface.getRect(),
            revisions = me.revisions,
            mat = me.attr.matrix,
            template = me.getTemplate(),
            templateAttr = template.attr,
            ln = me.instances.length,
            instance, i;

        mat.toContext(ctx);
        template.preRender(surface, ctx, rect);
        template.useAttributes(ctx, surfaceRect);

        for (i = 0; i < ln; i++) {
            instance = me.get(i);

            if (instance.hidden || instance.revision !== revisions[instance.category]) {
                continue;
            }

            ctx.save();
            template.attr = instance;
            template.useAttributes(ctx, surfaceRect);
            template.render(surface, ctx, rect);
            ctx.restore();
        }

        template.attr = templateAttr;
    }
});
