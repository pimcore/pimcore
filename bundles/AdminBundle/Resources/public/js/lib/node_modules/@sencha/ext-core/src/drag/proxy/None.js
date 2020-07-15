/**
 * A base class for drag proxies that are shown to represent the
 * dragged item during a drag.
 *
 * Default implementations are:
 * - {@link Ext.drag.proxy.Original}: Moves the original element.
 * - {@link Ext.drag.proxy.Placeholder}: Creates a new element each drag.
 *
 * This implementation does not provide a proxy element, so it can be used
 * for cursor tracking only.
 */
Ext.define('Ext.drag.proxy.None', {

    mixins: ['Ext.mixin.Factoryable'],

    alias: 'drag.proxy.none',

    factoryConfig: {
        aliasPrefix: 'drag.proxy.',
        type: 'dragproxy'
    },

    config: {
        source: null
    },

    constructor: function(config) {
        var getElement = config && config.getElement;

        if (getElement) {
            // Don't mutate the object the user passed. Need to do this
            // here otherwise initConfig will complain about writing over
            // the method.
            this.getElement = getElement;
            config = Ext.apply({}, config);
            delete config.getElement;
        }

        this.initConfig(config);
    },

    /**
     * @method
     * Perform any cleanup required. This is called as the drag ends.
     *
     * @template
     * @protected
     */
    cleanup: Ext.emptyFn,

    dragRevert: function(info, revertCls, options, callback) {
        var positionable = this.getPositionable(info),
            initial = info.proxy.initial;

        positionable.addCls(revertCls);

        positionable.setXY([initial.x, initial.y], Ext.apply({
            callback: function() {
                positionable.removeCls(revertCls);
                callback();
            }
        }, options));
    },

    /**
     * Get the proxy element for the drag source. This is called as
     * the drag starts. This element may be cached on the instance and
     * reused.
     *
     * @param {Ext.drag.Info} info Drag info
     *
     * @return {Ext.dom.Element} The element.
     *
     * @template
     * @protected
     */
    getElement: function() {
        return null;
    },

    getPositionable: function() {
        return this.element;
    },

    setXY: function(info, xy, animation) {
        var positionable = this.getPositionable(info);

        if (positionable) {
            positionable.setXY(xy, animation);
        }
    },

    /**
     * @method
     * Called when the target changes for the active drag. This may
     * mean the target is now `null`.
     *
     * @param {Ext.drag.Info} info Drag info
     *
     * @template
     * @protected
     */
    update: Ext.emptyFn,

    privates: {
        setupElement: function(info) {
            return (this.element = this.getElement(info));
        },

        /**
         * Adjust the xy position based on any cursor offset.
         * @param {Ext.drag.Info} info The drag info.
         * @param {Number[]} pos The xy position.
         * @return {Number[]} The adjusted position.
         *
         * @private
         */
        adjustCursorOffset: function(info, pos) {
            return pos;
        }
    }
});
