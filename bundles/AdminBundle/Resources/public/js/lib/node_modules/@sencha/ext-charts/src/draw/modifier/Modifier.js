/**
 * @class Ext.draw.modifier.Modifier
 *
 * Each sprite has a stack of modifiers. The resulting attributes of sprite is
 * the content of the stack top. When setting attributes to a sprite,
 * changes will be pushed-down though the stack of modifiers and pop-back the
 * additive changes; When modifier is triggered to change the attribute of a
 * sprite, it will pop-up the changes to the top.
 */
Ext.define('Ext.draw.modifier.Modifier', {

    isModifier: true,

    mixins: {
        observable: 'Ext.mixin.Observable'
    },

    config: {
        /**
         * @private
         * @cfg {Ext.draw.modifier.Modifier} lower Modifier that receives the push-down changes.
         */
        lower: null,

        /**
         * @private
         * @cfg {Ext.draw.modifier.Modifier} upper Modifier that receives the pop-up changes.
         */
        upper: null,

        /**
         * @cfg {Ext.draw.sprite.Sprite} sprite The sprite to which the modifier belongs.
         */
        sprite: null
    },

    constructor: function(config) {
        this.mixins.observable.constructor.call(this, config);
    },

    updateUpper: function(upper) {
        if (upper) {
            upper.setLower(this);
        }
    },

    updateLower: function(lower) {
        if (lower) {
            lower.setUpper(this);
        }
    },

    /**
     * @private
     * Validate attribute set before use.
     *
     * @param {Object} attr The attribute to be validated. Note that it may be already initialized,
     * so do not override properties that have already been used.
     */
    prepareAttributes: function(attr) {
        if (this._lower) {
            this._lower.prepareAttributes(attr);
        }
    },

    /**
     * @private
     * Invoked when changes need to be popped up to the top.
     * @param {Object} attr The source attributes.
     * @param {Object} changes The changes to be popped up.
     */
    popUp: function(attr, changes) {
        if (this._upper) {
            this._upper.popUp(attr, changes);
        }
        else {
            Ext.apply(attr, changes);
        }
    },

    /**
     * @private
     *
     * This method will filter out the properties from the `changes` object, if they
     * have the same values as in the `attr` object (sprite's attributes).
     *
     * If the `receiver` object is provided, the attributes with the new values will be
     * copied from the `changes` object to the `receiver` object, and the `changes`
     * object will be left unchanged.
     *
     * The method returns the `receiver` object, if it was provided, or the `changes`
     * object otherwise.
     *
     * The method also handles a special case when a sprite attribute that is meant to be
     * animated was set to a certain value (e.g. 5), that is different from the original
     * value (e.g. 3) of the attribute, and immediately set to another value again, that
     * is the same as the original value (3). In this case, the attribute's current
     * value is still the original value, because the attribute hasn't started animating
     * yet, so a comparison against the current value is not appropriate, and the target
     * value (value at the end of animation, 5) should be used for comparison instead, so
     * that 3 won't be filtered out.
     */
    filterChanges: function(attr, changes, receiver) {
        var targets = attr.targets,
            name, value;

        if (receiver) {
            for (name in changes) {
                value = changes[name];

                if (value !== attr[name] || (targets && value !== targets[name])) {
                    receiver[name] = value;
                }
            }
        }
        else {
            for (name in changes) {
                value = changes[name];

                if (value === attr[name] && (!targets || value === targets[name])) {
                    delete changes[name];
                }
            }
        }

        return receiver || changes;
    },

    /**
     * @private
     * Invoked when changes need to be pushed down to the sprite.
     * @param {Object} attr The source attributes.
     * @param {Object} changes The changes to make. This object might be changed unexpectedly
     * inside the method.
     * @return {Mixed}
     */
    pushDown: function(attr, changes) {
        return this._lower
            ? this._lower.pushDown(attr, changes)
            : this.filterChanges(attr, changes);
    }
});
