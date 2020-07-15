/**
 * Internal utility class that provides default configuration for cell editing.
 * @private
 */
Ext.define('Ext.grid.CellEditor', {
    extend: 'Ext.Editor',
    alias: 'widget.celleditor',

    /**
     * @property {Boolean} isCellEditor
     * @readonly
     * `true` in this class to identify an object as an instantiated CellEditor,
     * or subclass thereof.
     */
    isCellEditor: true,

    alignment: 'l-l!',

    hideEl: false,

    cls: Ext.baseCSSPrefix + 'small-editor ' +
        Ext.baseCSSPrefix + 'grid-editor ' +
        Ext.baseCSSPrefix + 'grid-cell-editor',

    treeNodeSelector: '.' + Ext.baseCSSPrefix + 'tree-node-text',

    shim: false,

    shadow: false,
    floating: true,
    alignOnScroll: false,
    useBoundValue: false,
    focusLeaveAction: 'completeEdit',

    // Set the grid that owns this editor.
    // Called by CellEditing#getEditor
    setGrid: function(grid) {
        this.grid = grid;
    },

    startEdit: function(boundEl, value, doFocus, isResuming) {
        this.context = this.editingPlugin.context;
        this.callParent([boundEl, value, doFocus, isResuming]);
    },

    /**
     * @private
     * Shows the editor, end ensures that it is rendered into the correct view
     * Hides the grid cell inner element when a cell editor is shown.
     */
    onShow: function() {
        var me = this,
            innerCell = me.boundEl.dom.querySelector(me.context.view.innerSelector);

        if (innerCell) {
            if (me.isForTree) {
                innerCell = innerCell.querySelector(me.treeNodeSelector);
            }

            Ext.fly(innerCell).hide();
        }

        me.callParent(arguments);
    },

    onFocusEnter: function() {
        var me = this,
            context = me.context,
            view = context.view;

        // Focus restoration after a refresh may require realignment and correction
        // of the context because it could have been due to a or filter operation and
        // the context may have changed position.
        me.reattachToBody();
        context.node = view.getNode(context.record);
        context.row = view.getRow(context.record);
        context.cell = context.getCell(true);
        context.rowIdx = view.indexOf(context.row);
        me.realign(true);

        me.callParent(arguments);

        // Ensure that hide processing does not throw focus back to the previously focused element.
        me.focusEnterEvent = null;
    },

    onFocusLeave: function(e) {
        var me = this,
            view = me.context.view,
            related = Ext.fly(e.relatedTarget);

        // Quit editing in whichever way.
        // The default is completeEdit.
        // If we received an ESC, this will be cancelEdit.
        if (me[me.focusLeaveAction]() === false) {
            e.event.stopEvent();

            return;
        }

        delete me.focusLeaveAction;

        // If the related target is not a cell, turn actionable mode off
        if (!view.destroyed && view.el.contains(related) &&
            (!related.isAncestor(e.target) || related === view.el) &&
            !related.up(view.getCellSelector(), view.el, true)) {
            me.context.grid.setActionableMode(false, view.actionPosition);
        }

        me.cacheElement();

        // Bypass Editor's onFocusLeave
        Ext.container.Container.prototype.onFocusLeave.apply(me, arguments);
    },

    completeEdit: function(remainVisible) {
        var me = this,
            context = me.context;

        if (me.editing) {
            context.value = me.field.value;

            if (me.editingPlugin.validateEdit(context) === false) {
                if (context.cancel) {
                    context.value = me.originalValue;
                    me.editingPlugin.cancelEdit();
                }

                return !!context.cancel;
            }
        }

        me.callParent([remainVisible]);
    },

    onEditComplete: function(remainVisible, canceling) {
        var me = this,
            activeElement = Ext.Element.getActiveElement(),
            ctx = me.context,
            store = ctx && ctx.store,
            boundEl;

        me.editing = false;

        // Must refresh the boundEl in case DOM has been churned during edit.
        boundEl = me.boundEl = me.context.getCell();

        // We have to test if boundEl is still present because it could have been
        // de-rendered by a bufferedRenderer scroll.
        if (boundEl) {
            me.restoreCell();

            // IF we are just terminating, and NOT being terminated due to focus
            // having moved out of this editor, then we must prevent any upcoming blur
            // from letting focus fly out of the view.
            // onFocusLeave will have no effect because the editing flag is cleared.
            if (boundEl.contains(activeElement) && boundEl.dom !== activeElement) {
                boundEl.focus();
            }
        }

        me.callParent(arguments);

        // Do not rely on events to sync state with editing plugin,
        // Inform it directly.
        if (canceling) {
            me.editingPlugin.cancelEdit(me);

            // When expanding/collapsing a node, the editor will lose focus
            // and cancel the editing, but at the same time the expand/collapse
            // will call actionable#suspend that will cause this editor to remain visible
            // and will prevent the element from being cached. So if remainVisible is true
            // and we are expanding/collapsing we should always cache the element.
            if (remainVisible && store && store.isExpandingOrCollapsing) {
                me.cacheElement();
            }
        }
        else {
            me.editingPlugin.onEditComplete(me, me.getValue(), me.startValue);
        }
    },

    cacheElement: function(force) {
        if ((!this.editing || force) && !this.destroyed && !this.isDetaching) {
            this.isDetaching = true;
            this.detachFromBody();
            this.isDetaching = false;
        }
    },

    /**
     * @private
     * Hiding blurs, and blur will terminate the edit.
     * We must not allow superclass Editor to terminate the edit and make
     * sure the element has been cached.
     */
    onHide: function() {
        this.cacheElement(true);
        Ext.Editor.superclass.onHide.apply(this, arguments);
    },

    onSpecialKey: function(field, event, eOpts) {
        var me = this,
            key = event.getKey(),
            complete = me.completeOnEnter && key === event.ENTER &&
                      (!eOpts || !eOpts.fromBoundList),
            cancel = me.cancelOnEsc && key === event.ESC,
            view = me.editingPlugin.view;

        if (complete || cancel) {
            // Do not let the key event bubble into the NavigationModel
            // after we're done processing it.
            // We control the navigation action here; we focus the cell.
            event.stopEvent();

            // Maintain visibility so that focus doesn't leak.
            // We need to direct focusback to the owning cell.
            if (cancel) {
                me.focusLeaveAction = 'cancelEdit';
            }

            view.ownerGrid.setActionableMode(false);
        }
    },

    getRefOwner: function() {
        return this.column && this.column.getView();
    },

    restoreCell: function() {
        var me = this,
            innerCell = me.boundEl.dom.querySelector(me.context.view.innerSelector);

        if (innerCell) {
            if (me.isForTree) {
                innerCell = innerCell.querySelector(me.treeNodeSelector);
            }

            Ext.fly(innerCell).show();
        }
    },

    /**
     * @private
     * Fix checkbox blur when it is clicked.
     */
    afterRender: function() {
        var me = this,
            field = me.field;

        me.callParent(arguments);

        if (field.isCheckbox) {
            field.mon(field.inputEl, {
                mousedown: me.onCheckBoxMouseDown,
                click: me.onCheckBoxClick,
                scope: me
            });
        }
    },

    /**
     * @private
     * Because when checkbox is clicked it loses focus  completeEdit is bypassed.
     */
    onCheckBoxMouseDown: function() {
        this.completeEdit = Ext.emptyFn;
    },

    /**
     * @private
     * Restore checkbox focus and completeEdit method.
     */
    onCheckBoxClick: function() {
        delete this.completeEdit;
        this.field.focus(false, 10);
    },

    /**
     * @private
     * Realigns the Editor to the grid cell, or to the text node in the grid inner cell
     * if the inner cell contains multiple child nodes.
     */
    realign: function(autoSize) {
        var me = this,
            boundEl = me.boundEl,
            innerCell = boundEl.dom.querySelector(me.context.view.innerSelector),
            innerCellTextNode = innerCell.firstChild,
            width = boundEl.getWidth(),
            offsets = Ext.Array.clone(me.offsets),
            grid = me.grid,
            xOffset,
            v = '',

            // innerCell is empty if there are no children, or there is one text node,
            // and it contains whitespace
            isEmpty = !innerCellTextNode || (innerCellTextNode.nodeType === 3 &&
                      !(Ext.String.trim(v = innerCellTextNode.data).length));

        if (me.isForTree) {
            // When editing a tree, adjust the width and offsets of the editor to line
            // up with the tree cell's text element
            xOffset = me.getTreeNodeOffset(innerCell);
            width -= Math.abs(xOffset);
            offsets[0] += xOffset;
        }

        if (grid.columnLines) {
            // Subtract the column border width so that the editor displays inside the
            // borders. The column border could be either on the left or the right depending
            // on whether the grid is RTL - using the sum of both borders works in both modes.
            width -= boundEl.getBorderWidth('rl');
        }

        if (autoSize === true) {
            me.field.setWidth(width);
        }

        // https://sencha.jira.com/browse/EXTJSIV-10871 Ensure the data bearing element
        // has a height from text.
        if (isEmpty) {
            innerCell.innerHTML = 'X';
        }

        me.alignTo(boundEl, me.alignment, offsets);

        if (isEmpty) {
            innerCell.firstChild.data = v;
        }
    },

    getTreeNodeOffset: function(innerCell) {
        return Ext.fly(innerCell.querySelector(this.treeNodeSelector)).getOffsetsTo(innerCell)[0];
    }
});
