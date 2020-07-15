/**
 * A base class for draggable and droppable items that wrap a DOM element.
 *
 * @abstract
 */
Ext.define('Ext.drag.Item', {
    mixins: [
        'Ext.mixin.Observable',
        'Ext.mixin.Identifiable'
    ],

    config: {
        /**
         * @cfg {Boolean} autoDestroy
         * `true` to destroy the {@link #element} when this item is destroyed.
         */
        autoDestroy: true,

        /**
         * @cfg {Ext.Component} component
         * The component for this item. This implicity sets the `element` config to be
         * the component's primary `element`. By providing the `component`, drag operations
         * will act upon the component's `x` and `y` configs (if `floated`) or `left` and
         * `top` configs otherwise.
         * @since 6.5.0
         *
         * @private
         */
        component: null,

        /**
         * @cfg {String/HTMLElement/Ext.dom.Element} element
         * The id, dom or Element reference for this item.
         */
        element: null,

        /**
         * @cfg {String/String[]} groups
         * A group controls which {@link Ext.drag.Source sources} and {@link Ext.drag.Target}
         * targets can interact with each other. Only items that have the same (or intersecting)
         * groups will react to each other. Items with no groups will be in the default pool.
         */
        groups: null
    },

    constructor: function(config) {
        this.mixins.observable.constructor.call(this, config);
    },

    /**
     * Checks whether this item is currently disabled.
     * @return {Boolean} `true` if this item is disabled.
     */
    isDisabled: function() {
        return this.disabled;
    },

    /**
     * Disable the current item to disallow it from participating
     * in drag/drop operations.
     */
    disable: function() {
        this.disabled = true;
    },

    /**
     * Enable the current item to allow it to participate in
     * drag/drop operations.
     */
    enable: function() {
        this.disabled = false;
    },

    updateComponent: function(comp, was) {
        var el;

        if (comp) {
            el = comp.el;
        }
        else if (was && was.el === this.getElement()) {
            el = null;
        }
        else {
            return;
        }

        this.setElement(el);
    },

    applyElement: function(element) {
        return element ? Ext.get(element) : null;
    },

    updateElement: function() {
        this.setupListeners();
    },

    applyGroups: function(group) {
        if (typeof group === 'string') {
            group = [group];
        }

        return group;
    },

    destroy: function() {
        var me = this,
            el = me.getElement();

        me.destroying = true;
        me.setElement(null);

        if (el && me.getAutoDestroy()) {
            el.destroy();
        }

        me.callParent();

        // This just makes it hard to ask "was destroy() called?":
        // me.destroying = false; // removed in 7.0
    },

    privates: {
        /**
        * @property {Boolean} disabled
        * `true` if this item is disabled.
        *
        * @private
        */
        disabled: false,

        convertToLocalXY: function(xy) {
            var c = this.getComponent();

            if (c) {
                xy = c.convertToLocalXY(xy);
            }
            else {
                xy = this.getElement().translateXY(xy[0], xy[1]);
                xy = [xy.x, xy.y];
            }

            return xy;
        },

        /**
         * @method
         * Gets any listeners to attach for the current element.
         * @return {Object} The listeners for thie element.
         *
         * @private
         */
        getElListeners: Ext.privateFn,

        /**
         * Detach any existing listeners and add new listeners
         * to the element.
         * 
         * @private
         */
        setupListeners: function(element) {
            var me = this,
                elListeners = me.elListeners;

            element = element || me.getElement();

            if (elListeners) {
                elListeners.destroy();
                me.elListeners = null;
            }

            if (element) {
                me.elListeners = element.on(Ext.apply({
                    scope: me,
                    destroyable: true
                }, me.getElListeners()));
            }
        }
    }
});
