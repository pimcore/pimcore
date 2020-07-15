/**
 * Acts as a mediator between sources and targets. 
 * 
 * Typically this class will not be used in user code.
 *
 * @private
 */
Ext.define('Ext.drag.Manager', {
    singleton: true,

    uses: [
        'Ext.mixin.Inheritable'
    ],

    /**
     * @property {String} dragCls
     * A class added to the body while a drag is active.
     *
     * @private
     */
    dragCls: Ext.baseCSSPrefix + 'drag-body',

    // If we need to use the current mousemove target to find the over el,
    // but pointer-events is not supported, AND the delta position does not place the mouse outside
    // of the dragEl, temporarily move the dragEl away, and fake the mousemove target by using
    // document.elementFromPoint while it's out of the way.
    // The pointer events implementation is bugged in IE9/10 and opera, so fallback even if they
    // report that they support it.
    // IE8m do not support it so they will auto fall back
    pointerBug: Ext.isTouch || (!Ext.supports.CSSPointerEvents || Ext.isIE10m || Ext.isOpera),

    constructor: function() {
        this.targets = {};
        this.nativeTargets = [];

        Ext.onReady(this.init, this);
    },

    init: function() {
        // The purpose of listening for these events is to track when a
        // native drag enters the document so we can create and maintain
        // a single drag.Info object for it. Need to use a "stack-like" mechanism
        // to track while elements are being entered/left, keeping a count is
        // not sufficient because Gecko can fire multiple events for the
        // same element in some instances. So just keep pushing/removing the
        // element from the tracking array. Once we hit 0, the drag is out
        // of the document. On drop, we clear it manually because there is
        // no longer an active drag.
        Ext.getDoc().on({
            scope: this,
            dragenter: {
                capture: true,
                fn: 'onNativeDragEnter'
            },
            dragleave: 'onNativeDragLeave',
            dragover: 'onNativeDragOver',
            drop: 'onNativeDrop'
        });
    },

    destroy: function() {
        var me = this,
            targets = me.targets,
            key;

        me.destroying = true;

        for (key in targets) {
            targets[key].destroy();
        }

        me.targets = null;

        me.callParent();

        // This just makes it hard to ask "was destroy() called?":
        // me.destroying = false; // removed in 7.0
    },

    privates: {
        /**
         * A shim for elementFromPoint to allow RTL behaviour.
         * @param {Number} x The x coordinate.
         * @param {Number} y The y coordinate
         * @return {HTMLElement} The element.
         *
         * @private
         */
        elementFromPoint: function(x, y) {
            if (Ext.rootInheritedState.rtl) {
                x = Ext.Element.getViewportWidth() - x;
            }

            return Ext.dom.Element.fromPagePoint(x, y, true);
        },

        /**
         * Get the matching target (if any) at a particular point.
         * @param {Ext.drag.Info} info The drag info.
         * @return {Ext.drag.Target} The matching target, `null` if not found.
         *
         * @private
         */
        getAtPoint: function(info) {
            var current = info.cursor.current,
                elementMap = info.elementMap,
                isUnderCursor = info.proxy.isUnderCursor,
                proxyEl = this.pointerBug && isUnderCursor ? info.proxy.element.dom : null,
                target, el;

            if (proxyEl) {
                proxyEl.style.visibility = 'hidden';
            }

            el = this.elementFromPoint(current.x, current.y);

            if (proxyEl) {
                proxyEl.style.visibility = 'visible';
            }

            while (el) {
                target = elementMap[el.id];

                if (target) {
                    return target;
                }

                el = el.parentNode;
            }

            return null;
        },

        /**
         * Spins up an info object based on a native drag.
         * @param {Ext.event.Event} e The event.
         * @return {Ext.drag.Info} The info. Cached for a single drag.
         *
         * @private
         */
        getNativeDragInfo: function(e) {
            var info = this.nativeDragInfo;

            if (!info) {
                this.nativeDragInfo = info = new Ext.drag.Info();
                info.isNative = true;
            }

            return info;
        },

        /**
         * Called on drag cancel.
         * @param {Ext.drag.Info} info The drag info.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onDragCancel: function() {
            Ext.getBody().removeCls(this.dragCls);
        },

        /**
         * Called when drag completes.
         * @param {Ext.drag.Info} info The drag info.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onDragEnd: function(info, e) {
            info.finalize();
            Ext.getBody().removeCls(this.dragCls);
        },

        /**
         * Called for each drag movement.
         * @param {Ext.drag.Info} info The drag info.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onDragMove: function(info, e) {
            this.processDrag(info);
        },

        /**
         * Called when drag starts.
         * @param {Ext.drag.Info} info The drag info.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onDragStart: function(info, e) {
            var me = this,
                source = info.source,
                targets = me.targets,
                groups = source.getGroups(),
                targetMap = {},
                possibleTargets = {},
                elementMap = {},
                id, target, targetGroups, groupMap, groupOk, len, i;

            elementMap = {};
            possibleTargets = {};

            if (groups) {
                groupMap = Ext.Array.toMap(groups);
            }

            // Exclude any invalid targets so they don't get used during
            // a drag. This means targets that are locked or have groups that don't
            // match
            for (id in targets) {
                target = targets[id];

                if (!target.isDisabled()) {
                    groupOk = false;
                    targetGroups = target.getGroups();

                    // If neither has groups, proceed. Otherwise, it
                    // can only be correct if both have groups, then we
                    // need to check if they intersect. If one has groups
                    // and not the other it's not possible to intersect.
                    if (!groupMap && !targetGroups) {
                        groupOk = true;
                    }
                    else if (groupMap && targetGroups) {
                        for (i = 0, len = targetGroups.length; i < len; ++i) {
                            if (groupMap[targetGroups[i]]) {
                                groupOk = true;
                                break;
                            }
                        }
                    }

                    if (groupOk) {
                        possibleTargets[id] = target;
                    }
                }

                targetMap[id] = target;
                elementMap[target.getElement().id] = target;
            }

            info.possibleTargets = possibleTargets;
            info.targetMap = targetMap;
            info.elementMap = elementMap;

            Ext.getBody().addCls(me.dragCls);

            me.processDrag(info);
        },

        /**
         * Handle a native dragenter event from outside the browser.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onNativeDragEnter: function(e) {
            var nativeTargets = this.nativeTargets,
                target = e.target;

            // Need to preventDefault to stop browser navigating to the dropped item.
            e.preventDefault();

            if (nativeTargets[nativeTargets.length - 1] !== target) {
                nativeTargets.push(target);
            }

        },

        /**
         * Handle a native dragleave event.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onNativeDragLeave: function(e) {
            var nativeTargets = this.nativeTargets;

            Ext.Array.remove(nativeTargets, e.target);

            if (nativeTargets.length === 0) {
                this.nativeDragInfo = null;
            }
        },

        /**
         * Handle a native dragover event.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onNativeDragOver: function(e) {
            // Need to preventDefault to stop browser navigating to the dropped item.
            e.preventDefault();
        },

        /**
         * Handle a native drop event.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onNativeDrop: function(e) {
            // Need to preventDefault to stop browser navigating to the dropped item.
            e.preventDefault();

            this.nativeTargets.length = 0;
            this.nativeDragInfo = null;
        },

        /**
         * Process a drag movement.
         * @param {Ext.drag.Info} info The drag info.
         *
         * @private
         */
        processDrag: function(info) {
            info.setActive(this.getAtPoint(info));
        },

        /**
         * Register a target with this group. This is intended to
         * be called by the target.
         * @param {Ext.drag.Target} target The target.
         *
         * @private
         */
        register: function(target) {
            this.targets[target.getId()] = target;
        },

        /**
         * Unregister a target with this group. This is intended to
         * be called by the target.
         * @param {Ext.drag.Target} target The target.
         *
         * @private
         */
        unregister: function(target) {
            var id;

            if (this.destroying) {
                return;
            }

            id = target.getId();

            this.targets[id] = null;
            delete this.targets[id];
        }
    }
});
