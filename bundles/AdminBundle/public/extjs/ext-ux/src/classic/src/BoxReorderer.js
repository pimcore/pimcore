/**
 * Base class from Ext.ux.TabReorderer.
 */
Ext.define('Ext.ux.BoxReorderer', {
    extend: 'Ext.plugin.Abstract',
    alias: 'plugin.boxreorderer',

    requires: [
        'Ext.ux.dd.BoxContainerDD'
    ],

    mixins: {
        observable: 'Ext.util.Observable'
    },

    /**
     * @cfg {String} itemSelector
     * A {@link Ext.DomQuery DomQuery} selector which identifies the encapsulating elements of child
     * Components which participate in reordering.
     */
    itemSelector: '.x-box-item',

    /**
     * @cfg {Mixed} animate
     * If truthy, child reordering is animated so that moved boxes slide smoothly into position.
     * If this option is numeric, it is used as the animation duration in milliseconds.
     */
    animate: 100,

    /**
     * @event StartDrag
     * Fires when dragging of a child Component begins.
     * @param {Ext.ux.BoxReorderer} this
     * @param {Ext.container.Container} container The owning Container
     * @param {Ext.Component} dragCmp The Component being dragged
     * @param {Number} idx The start index of the Component being dragged.
     */

    /**
     * @event Drag
     * Fires during dragging of a child Component.
     * @param {Ext.ux.BoxReorderer} this
     * @param {Ext.container.Container} container The owning Container
     * @param {Ext.Component} dragCmp The Component being dragged
     * @param {Number} startIdx The index position from which the Component was initially dragged.
     * @param {Number} idx The current closest index to which the Component would drop.
     */

    /**
     * @event ChangeIndex
     * Fires when dragging of a child Component causes its drop index to change.
     * @param {Ext.ux.BoxReorderer} this
     * @param {Ext.container.Container} container The owning Container
     * @param {Ext.Component} dragCmp The Component being dragged
     * @param {Number} startIdx The index position from which the Component was initially dragged.
     * @param {Number} idx The current closest index to which the Component would drop.
     */

    /**
     * @event Drop
     * Fires when a child Component is dropped at a new index position.
     * @param {Ext.ux.BoxReorderer} this
     * @param {Ext.container.Container} container The owning Container
     * @param {Ext.Component} dragCmp The Component being dropped
     * @param {Number} startIdx The index position from which the Component was initially dragged.
     * @param {Number} idx The index at which the Component is being dropped.
     */

    constructor: function() {
        this.callParent(arguments);
        this.mixins.observable.constructor.call(this);
    },

    init: function(container) {
        var me = this,
            layout = container.getLayout();

        me.container = container;

        // We must use LTR method names and properties.
        // The underlying Element APIs normalize them.
        me.names = layout._props[layout.type].names;

        // Set our animatePolicy to animate the start position (ie x for HBox, y for VBox)
        me.animatePolicy = {};
        me.animatePolicy[me.names.x] = true;

        // Initialize the DD on first layout, when the innerCt has been created.
        me.container.on({
            scope: me,
            boxready: me.onBoxReady,
            beforedestroy: me.onContainerDestroy
        });
    },

    /**
     * @private
     * Clear up on Container destroy
     */
    onContainerDestroy: function() {
        var dd = this.dd;

        if (dd) {
            dd.unreg();
            this.dd = null;
        }
    },

    onBoxReady: function() {
        var me = this,
            layout = me.container.getLayout(),
            names = me.names,
            dd;

        dd = me.dd = new Ext.ux.dd.BoxContainerDD(layout.innerCt, me.container.id + '-reorderer');

        Ext.apply(dd, {
            animate: me.animate,
            reorderer: me,
            container: me.container,
            getDragCmp: me.getDragCmp,
            clickValidator: Ext.Function.createInterceptor(
                dd.clickValidator, me.clickValidator, me, false
            ),
            onMouseDown: me.onMouseDown,
            startDrag: me.startDrag,
            onDrag: me.onDrag,
            endDrag: me.endDrag,
            getNewIndex: me.getNewIndex,
            doSwap: me.doSwap,
            findReorderable: me.findReorderable,
            names: names
        });

        // Decide which dimension we are measuring, and which measurement metric defines
        // the *start* of the box depending upon orientation.
        dd.dim = names.width;
        dd.startAttr = names.beforeX;
        dd.endAttr = names.afterX;
    },

    getDragCmp: function(e) {
        return this.container.getChildByElement(e.getTarget(this.itemSelector, 10));
    },

    // check if the clicked component is reorderable
    clickValidator: function(e) {
        var cmp = this.getDragCmp(e);

        // If cmp is null, this expression MUST be coerced to boolean so that 
        // createInterceptor is able to test it against false
        return !!(cmp && cmp.reorderable !== false);
    },

    onMouseDown: function(e) {
        var me = this,
            container = me.container,
            containerBox,
            cmpEl,
            cmpBox;

        // Ascertain which child Component is being mousedowned
        me.dragCmp = me.getDragCmp(e);

        if (me.dragCmp) {
            cmpEl = me.dragCmp.getEl();
            me.startIndex = me.curIndex = container.items.indexOf(me.dragCmp);

            // Start position of dragged Component
            cmpBox = cmpEl.getBox();

            // Last tracked start position
            me.lastPos = cmpBox[me.startAttr];

            // Calculate constraints depending upon orientation
            // Calculate offset from mouse to dragEl position
            containerBox = container.el.getBox();

            if (me.dim === 'width') {
                me.minX = containerBox.left;
                me.maxX = containerBox.right - cmpBox.width;
                me.minY = me.maxY = cmpBox.top;
                me.deltaX = e.getX() - cmpBox.left;
            }
            else {
                me.minY = containerBox.top;
                me.maxY = containerBox.bottom - cmpBox.height;
                me.minX = me.maxX = cmpBox.left;
                me.deltaY = e.getY() - cmpBox.top;
            }

            me.constrainY = me.constrainX = true;
        }
    },

    startDrag: function() {
        var me = this,
            dragCmp = me.dragCmp,
            targetEl, dom, left, top, scrollable;

        if (dragCmp) {
            // For the entire duration of dragging the *Element*, defeat any positioning 
            // and animation of the dragged *Component*
            scrollable = me.container.getScrollable();

            if (scrollable) {
                // TODO remove this workaround
                scrollable.scrollBy(-1).then(function() {
                    scrollable.scrollBy(1);
                });
            }

            dragCmp.setPosition = Ext.emptyFn;
            dragCmp.animate = false;

            // Animate the BoxLayout just for the duration of the drag operation.
            if (me.animate) {
                me.container.getLayout().animatePolicy = me.reorderer.animatePolicy;
            }

            // We drag the Component element
            me.dragElId = dragCmp.getEl().id;
            me.reorderer.fireEvent('StartDrag', me, me.container, dragCmp, me.curIndex);
            // Suspend events, and set the disabled flag so that the mousedown and mouseup events
            // that are going to take place do not cause any other UI interaction.
            dragCmp.suspendEvents();
            dragCmp.disabled = true;
            dragCmp.el.setStyle('zIndex', 100);

            // add a spacer to the tab container so it doesn't shrink while we're dragging a tab
            if (!dragCmp.nextSibling()) {
                targetEl = me.container.layout.targetEl;
                dom = targetEl.dom;
                left = dom.scrollWidth - 1;
                top = dom.scrollHeight - 1;

                me.spacerEl = Ext.dom.Helper.append(targetEl, {
                    tag: 'div',
                    style: 'width: 1px;' +
                           'height: 1px;' +
                           'position: absolute;' +
                           'left: ' + left + 'px;' +
                           'top: ' + top + 'px;"'
                });
            }
        }
        else {
            me.dragElId = null;
        }
    },

    /**
     * @private
     * Find next or previous reorderable component index.
     * @param {Number} newIndex The initial drop index.
     * @return {Number} The index of the reorderable component.
     */
    findReorderable: function(newIndex) {
        var me = this,
            items = me.container.items,
            newItem;

        if (items.getAt(newIndex).reorderable === false) {
            newItem = items.getAt(newIndex);

            if (newIndex > me.startIndex) {
                while (newItem && newItem.reorderable === false) {
                    newIndex++;
                    newItem = items.getAt(newIndex);
                }
            }
            else {
                while (newItem && newItem.reorderable === false) {
                    newIndex--;
                    newItem = items.getAt(newIndex);
                }
            }
        }

        newIndex = Math.min(Math.max(newIndex, 0), items.getCount() - 1);

        if (items.getAt(newIndex).reorderable === false) {
            return -1;
        }

        return newIndex;
    },

    /**
     * @private
     * Swap 2 components.
     * @param {Number} newIndex The initial drop index.
     */
    doSwap: function(newIndex) {
        var me = this,
            items = me.container.items,
            container = me.container,
            orig, dest, tmpIndex;

        newIndex = me.findReorderable(newIndex);

        if (newIndex === -1 || newIndex === me.curIndex) {
            return;
        }

        me.reorderer.fireEvent('ChangeIndex', me, container, me.dragCmp, me.startIndex, newIndex);

        orig = items.getAt(me.curIndex);
        dest = items.getAt(newIndex);
        items.remove(orig);
        tmpIndex = Math.min(Math.max(newIndex, 0), items.getCount() - 1);
        items.insert(tmpIndex, orig);
        items.remove(dest);
        items.insert(me.curIndex, dest);

        // Make the Box Container the topmost layout participant during the layout.
        container.updateLayout({
            isRoot: true
        });
        me.curIndex = newIndex;
    },

    onDrag: function(e) {
        var me = this,
            newIndex;

        newIndex = me.getNewIndex(e.getPoint());

        if ((newIndex !== undefined)) {
            me.reorderer.fireEvent(
                'Drag', me, me.container, me.dragCmp, me.startIndex, me.curIndex
            );
            me.doSwap(newIndex);
        }

    },

    endDrag: function(e) {
        var me = this,
            dragCmp = me.dragCmp,
            container = me.container,
            layout = container.getLayout(),
            temp;

        if (e) {
            e.stopEvent();
        }

        if (dragCmp) {
            delete me.dragElId;

            // Reinstate the Component's positioning method after mouseup, 
            // and allow the layout system to animate it.
            delete dragCmp.setPosition;
            dragCmp.animate = true;

            // Ensure the lastBox is correct for the animation system to restore
            // to when it creates the "from" animation frame
            dragCmp.lastBox[me.names.x] = dragCmp.getPosition(true)[me.names.widthIndex];

            // Make the Box Container the topmost layout participant during the layout.
            container.updateLayout({
                isRoot: true
            });

            // Attempt to hook into the afteranimate event of the drag Component to call the cleanup
            temp = Ext.fx.Manager.getFxQueue(dragCmp.el.id)[0];

            if (temp) {
                temp.on({
                    afteranimate: me.reorderer.afterBoxReflow,
                    scope: me
                });
            }
            // If not animated, clean up after the mouseup has happened so that 
            // we don't click the thing being dragged
            else {
                Ext.asap(me.reorderer.afterBoxReflow, me);
            }

            if (me.animate) {
                delete layout.animatePolicy;
            }

            me.reorderer.fireEvent('drop', me, container, dragCmp, me.startIndex, me.curIndex);
        }
    },

    /**
     * @private
     * Called after the boxes have been reflowed after the drop.
     * Re-enabled the dragged Component.
     */
    afterBoxReflow: function() {
        var me = this,
            spacerEl = Ext.fly(me.spacerEl),
            dragCmp = me.dragCmp;

        dragCmp.el.setStyle('zIndex', '');
        dragCmp.disabled = false;
        dragCmp.resumeEvents();

        // remove the spacer that was added when the drag was started
        if (spacerEl) {
            spacerEl.remove();
            me.spacerEl = null;
        }
    },

    /**
     * @private
     * Calculate drop index based upon the dragEl's position.
     */
    getNewIndex: function(pointerPos) {
        var me = this,
            dragEl = me.getDragEl(),
            dragBox = Ext.fly(dragEl).getBox(),
            targetEl,
            targetBox,
            targetMidpoint,
            i = 0,
            it = me.container.items.items,
            ln = it.length,
            lastPos = me.lastPos;

        me.lastPos = dragBox[me.startAttr];

        for (; i < ln; i++) {
            targetEl = it[i].getEl();

            // Only look for a drop point if this found item is an item according to our selector
            if (targetEl.dom !== dragEl && targetEl.is(me.reorderer.itemSelector)) {
                targetBox = targetEl.getBox();
                targetMidpoint = targetBox[me.startAttr] + (targetBox[me.dim] >> 1);

                if (i < me.curIndex) {
                    if (
                        (dragBox[me.startAttr] < lastPos) &&
                        (dragBox[me.startAttr] < (targetMidpoint - 5))
                    ) {
                        return i;
                    }
                }
                else if (i > me.curIndex) {
                    if (
                        (dragBox[me.startAttr] > lastPos) &&
                        (dragBox[me.endAttr] > (targetMidpoint + 5))
                    ) {
                        return i;
                    }
                }
            }
        }
    }
});
