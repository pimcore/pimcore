/**
 * @class Ext.draw.sprite.Instancing
 * @extends Ext.draw.sprite.Sprite
 *
 * Sprite that represents multiple instances based on the given template.
 */
Ext.define('Ext.draw.sprite.Instancing', {
    extend: 'Ext.draw.sprite.Sprite',
    alias: 'sprite.instancing',
    type: 'instancing',
    isInstancing: true,

    config: {
        /**
         * @cfg {Object} [template] The sprite template used by all instances.
         */
        template: null,

        /**
         * @cfg {Array} [instances]
         * The instances of the {@link #template} sprite as configs of attributes.
         */
        instances: null
    },

    instances: null,

    applyTemplate: function(template) {
        var surface;

        //<debug>
        if (!Ext.isObject(template)) {
            Ext.raise("A template of an instancing sprite must either be " +
                "a sprite instance or a valid config object from which a template " +
                "sprite will be created.");
        }
        else if (template.isInstancing || template.isComposite) {
            Ext.raise("Can't use an instancing or composite sprite " +
                "as a template for an instancing sprite.");
        }

        //</debug>
        if (!template.isSprite) {
            if (!template.xclass && !template.type) {
                // For compatibility with legacy charts.
                template.type = 'circle';
            }

            template = Ext.create(template.xclass || 'sprite.' + template.type, template);
        }

        surface = template.getSurface();

        if (surface) {
            surface.remove(template);
        }

        template.setParent(this);

        return template;
    },

    updateTemplate: function(template, oldTemplate) {
        if (oldTemplate) {
            delete oldTemplate.ownAttr;
        }

        template.setSurface(this.getSurface());

        // ownAttr is used to get a reference to the template's attributes
        // when one of the instances is rendering, as at that moment the template's
        // attributes (template.attr) are the instance's attributes.
        template.ownAttr = template.attr;

        this.clearAll();
        this.setDirty(true);
    },

    updateInstances: function(instances) {
        var i, ln;

        this.clearAll();

        if (Ext.isArray(instances)) {
            for (i = 0, ln = instances.length; i < ln; i++) {
                this.add(instances[i]);
            }
        }
    },

    updateSurface: function(surface) {
        var template = this.getTemplate();

        if (template && !template.destroyed) {
            template.setSurface(surface);
        }
    },

    get: function(index) {
        return this.instances[index];
    },

    getCount: function() {
        return this.instances.length;
    },

    clearAll: function() {
        var template = this.getTemplate();

        template.attr.children = this.instances = [];
        this.position = 0;
    },

    /**
     * @deprecated 6.2.0
     * Deprecated, use the {@link #add} method instead.
     */
    createInstance: function(config, bypassNormalization, avoidCopy) {
        return this.add(config, bypassNormalization, avoidCopy);
    },

    /**
     * Creates a new sprite instance.
     *
     * @param {Object} config The configuration of the instance.
     * @param {Boolean} [bypassNormalization] 'true' to bypass attribute normalization.
     * @param {Boolean} [avoidCopy] 'true' to avoid copying the `config` object.
     * @return {Object} The attributes of the instance.
     */
    add: function(config, bypassNormalization, avoidCopy) {
        var me = this,
            template = me.getTemplate(),
            originalAttr = template.attr,
            attr = Ext.Object.chain(originalAttr);

        template.modifiers.target.prepareAttributes(attr);
        template.attr = attr;
        template.setAttributes(config, bypassNormalization, avoidCopy);
        attr.template = template;
        me.instances.push(attr);
        template.attr = originalAttr;
        me.position++;

        return attr;
    },

    /**
     * Not supported.
     * 
     * @return {null}
     */
    getBBox: function() {
        return null;
    },

    /**
     * Returns the bounding box for the instance at the given index.
     *
     * @param {Number} index The index of the instance.
     * @param {Boolean} [isWithoutTransform] 'true' to not apply sprite transforms
     * to the bounding box.
     * @return {Object} The bounding box for the instance.
     */
    getBBoxFor: function(index, isWithoutTransform) {
        var template = this.getTemplate(),
            originalAttr = template.attr,
            bbox;

        template.attr = this.instances[index];
        bbox = template.getBBox(isWithoutTransform);
        template.attr = originalAttr;

        return bbox;
    },

    /**
     * @private
     * Checks if the instancing sprite can be seen.
     * @return {Boolean}
     */
    isVisible: function() {
        var attr = this.attr,
            parent = this.getParent(),
            result;

        result = parent && parent.isSurface && !attr.hidden && attr.globalAlpha;

        return !!result;
    },

    /**
     * @private
     * Checks if the instance of an instancing sprite can be seen.
     * @param {Number} index The index of the instance.
     */
    isInstanceVisible: function(index) {
        var me = this,
            template = me.getTemplate(),
            originalAttr = template.attr,
            instances = me.instances,
            result = false;

        if (!Ext.isNumber(index) || index < 0 || index >= instances.length || !me.isVisible()) {
            return result;
        }

        template.attr = instances[index];

        // TODO This is clearly a bug, fix it
        // eslint-disable-next-line no-undef
        result = template.isVisible(point, options);
        template.attr = originalAttr;

        return result;
    },

    render: function(surface, ctx, rect) {
        //<debug>
        if (!this.getTemplate()) {
            Ext.raise('An instancing sprite must have a template.');
        }
        //</debug>

        // eslint-disable-next-line vars-on-top
        var me = this,
            template = me.getTemplate(),
            surfaceRect = surface.getRect(),
            mat = me.attr.matrix,
            originalAttr = template.attr,
            instances = me.instances,
            ln = me.position,
            i;

        mat.toContext(ctx);
        template.preRender(surface, ctx, rect);
        template.useAttributes(ctx, surfaceRect);

        template.isSpriteInstance = true;

        for (i = 0; i < ln; i++) {
            if (instances[i].hidden) {
                continue;
            }

            ctx.save();
            template.attr = instances[i];
            template.useAttributes(ctx, surfaceRect);
            template.render(surface, ctx, rect);
            ctx.restore();
        }

        template.isSpriteInstance = false;

        template.attr = originalAttr;
    },

    /**
     * Sets the attributes for the instance at the given index.
     * 
     * @param {Number} index the index of the instance
     * @param {Object} changes the attributes to change
     * @param {Boolean} [bypassNormalization] 'true' to avoid attribute normalization
     */
    setAttributesFor: function(index, changes, bypassNormalization) {
        var template = this.getTemplate(),
            originalAttr = template.attr,
            attr = this.instances[index];

        if (!attr) {
            return;
        }

        template.attr = attr;

        if (bypassNormalization) {
            changes = Ext.apply({}, changes);
        }
        else {
            changes = template.self.def.normalize(changes);
        }

        template.modifiers.target.pushDown(attr, changes);
        template.attr = originalAttr;
    },

    destroy: function() {
        var me = this,
            template = me.getTemplate();

        me.instances = null;

        if (template) {
            template.destroy();
        }

        me.callParent();
    }
});
