// Currently has the following issues:
// - Does not handle postEditValue
// - Fields without editors need to sync with their values in Store
// - starting to edit another record while already editing and dirty should probably prevent it
// - aggregating validation messages
// - tabIndex is not managed bc we leave elements in dom, and simply move via positioning
// - layout issues when changing sizes/width while hidden (layout bug)

/**
 * Internal utility class used to provide row editing functionality. For developers, they should use
 * the RowEditing plugin to use this functionality with a grid.
 *
 * @private
 */
Ext.define('Ext.grid.RowEditor', {
    extend: 'Ext.form.Panel',
    alias: 'widget.roweditor',

    requires: [
        'Ext.tip.ToolTip',
        'Ext.util.KeyNav',
        'Ext.grid.RowEditorButtons'
    ],

    /**
     * @cfg {Boolean} [removeUnmodified=false]
     * If configured as `true`, then canceling an edit on a newly inserted
     * record which has not been modified will delete that record from the store.
     */

    /**
     * @cfg {String} saveBtnText
     * The text for the Update button below the row edit form.
     * @locale
     */
    saveBtnText: 'Update',

    /**
     * @cfg {String} cancelBtnText
     * The text for the Cancel button below the row edit form.
     * @locale
     */
    cancelBtnText: 'Cancel',

    /**
     * @cfg {String} errorsText
     * The title for displaying an error tip.
     * @locale
     */
    errorsText: 'Errors',

    /**
     * @cfg {String} dirtyText
     * The message to display when dirty data prevents closing the row editor.
     * @locale
     */
    dirtyText: 'You need to commit or cancel your changes',

    lastScrollLeft: 0,
    lastScrollTop: 0,

    border: false,
    tabGuard: true,

    _wrapCls: Ext.baseCSSPrefix + 'grid-row-editor-wrap',

    errorCls: Ext.baseCSSPrefix + 'grid-row-editor-errors-item',
    buttonUI: 'default',

    // Change the hideMode to offsets so that we get accurate measurements when
    // the roweditor is hidden for laying out things like a TriggerField.
    hideMode: 'offsets',

    defaultFocus: 'field:canfocus',

    layout: {
        type: 'hbox',
        align: 'middle'
    },

    _cachedNode: false,

    initComponent: function() {
        var me = this,
            grid = me.editingPlugin.grid,
            Container = Ext.container.Container,
            form, normalCt, lockedCt;

        me.cls = Ext.baseCSSPrefix + 'grid-editor ' + Ext.baseCSSPrefix + 'grid-row-editor';

        me.lockable = grid.lockable;

        // Create field containing structure for when editing a lockable grid.
        if (me.lockable) {
            me.items = [
                // Locked columns container shrinkwraps the fields
                lockedCt = me.lockedColumnContainer = new Container({
                    $initParent: me,
                    id: grid.id + '-locked-editor-cells',
                    scrollable: {
                        x: false,
                        y: false
                    },
                    layout: {
                        type: 'hbox',
                        align: 'middle'
                    },
                    // Locked grid has a border, we must be exactly the same width
                    margin: '0 1 0 0'
                }),

                // Normal columns container flexes the remaining RowEditor width
                normalCt = me.normalColumnContainer = new Container({
                    $initParent: me,
                    id: grid.id + '-normal-editor-cells',
                    // not user scrollable, but needs a Scroller instance for syncing with view
                    scrollable: {
                        x: false,
                        y: false
                    },
                    layout: {
                        type: 'hbox',
                        align: 'middle'
                    },
                    flex: 1
                })
            ];

            delete lockedCt.$initParent;
            delete normalCt.$initParent;

            // keep horizontal position of fields in sync with view's horizontal scroll position
            lockedCt.getScrollable().addPartner(grid.lockedGrid.view.getScrollable(), 'x');
            normalCt.getScrollable().addPartner(grid.normalGrid.view.getScrollable(), 'x');

            grid.lockedGrid.on({
                collapse: me.onGridResize,
                expand: me.onGridResize,
                beginfloat: me.onBeginFloat,
                scope: me
            });
        }
        else {
            // initialize a scroller instance for maintaining horizontal scroll position
            me.setScrollable({
                x: false,
                y: false
            });

            // keep horizontal position of fields in sync with view's horizontal scroll position
            me.getScrollable().addPartner(grid.view.getScrollable(), 'x');

            me.lockedColumnContainer = me.normalColumnContainer = me;
        }

        me.callParent();

        if (me.fields) {
            me.addFieldsForColumn(me.fields, true);
            me.insertColumnEditor(me.fields);
            delete me.fields;
        }

        me.mon(Ext.GlobalEvents, {
            scope: me,
            show: me.repositionIfVisible
        });

        form = me.getForm();
        form.trackResetOnLoad = true;
        form.on('validitychange', me.onValidityChange, me);
        form.on('errorchange', me.onErrorChange, me);
    },

    //
    // Grid listener added when this is rendered.
    // Keep our containing element sized correctly
    //
    onGridResize: function() {
        if (this.rendered) {
            // eslint-disable-next-line vars-on-top
            var me = this,
                clientWidth = me.getClientWidth(),
                grid = me.editingPlugin.grid,
                gridBody = grid.body,
                btns = me.getFloatingButtons();

            me.wrapEl.setLocalX(
                gridBody.getOffsetsTo(grid)[0] + gridBody.getBorderWidth('l') -
                grid.el.getBorderWidth('l')
            );

            me.setWidth(clientWidth);
            btns.setLocalX((clientWidth - btns.getWidth()) / 2);

            if (me.lockable) {
                me.lockedColumnContainer.setWidth(grid.normalGrid.el.getLeft(true));
            }
        }
    },

    onBeginFloat: function(lockedGrid) {
        if (lockedGrid.isSliding && this.isVisible()) {
            return false;
        }
    },

    syncAllFieldWidths: function() {
        var me = this,
            editors = me.query('[isEditorComponent]'),
            len = editors.length,
            column, i;

        me.preventReposition = true;

        // In a locked grid, a RowEditor uses 2 inner containers, so need to use CQ to retrieve
        // configured editors which were stamped with the isEditorComponent property
        // in Editing.createColumnField
        for (i = 0; i < len; ++i) {
            column = editors[i].column;

            if (column.isVisible()) {
                me.onColumnShow(column);
            }
        }

        me.preventReposition = false;
    },

    syncFieldWidth: function(column) {
        var field = column.getEditor(),
            width;

        field._marginWidth = (field._marginWidth || field.el.getMargin('lr'));

        // Avoid negative width as this will throw Invalid Argument errors in IE
        width = Math.max(column.getWidth() - field._marginWidth, 0);

        field.setWidth(width);

        if (field.xtype === 'displayfield') {
            // displayfield must have the width set on the inputEl for ellipsis to work
            field.inputWidth = width;
        }
    },

    onValidityChange: function(form, valid) {
        this.updateButton(valid);
    },

    onErrorChange: function() {
        var me = this,
            valid;

        if (me.errorSummary && me.isVisible()) {
            valid = me.getForm().isValid();
            me[valid ? 'hideToolTip' : 'showToolTip']();
        }
    },

    updateButton: function(valid) {
        var buttons = this.floatingButtons;

        if (buttons) {
            buttons.child('#update').setDisabled(!valid);
        }
        else {
            // set flag so we can disabled when created if needed
            this.updateButtonDisabled = !valid;
        }
    },

    afterRender: function() {
        var me = this,
            plugin = me.editingPlugin,
            grid = plugin.grid;

        me.scroller = grid.getScrollable();

        me.callParent(arguments);

        // The scrollingViewEl is the TableView which scrolls
        me.scrollingView = grid.lockable ? grid.normalGrid.view : grid.view;
        me.scrollingViewEl = me.scrollingView.el;
        me.scroller.on('scroll', me.onViewScroll, me);

        // Prevent from bubbling click events to the grid view
        me.mon(me.el, {
            click: Ext.emptyFn,
            stopPropagation: true
        });

        // Ensure that the editor width always matches the total header width
        me.mon(grid, 'resize', me.onGridResize, me);

        if (me.lockable) {
            grid.lockedGrid.view.on('resize', 'onGridResize', me);
        }

        me.el.swallowEvent([
            'keypress',
            'keydown'
        ]);

        me.initKeyNav();

        me.mon(plugin.view, {
            beforerefresh: me.onBeforeViewRefresh,
            refresh: me.onViewRefresh,
            itemremove: me.onViewItemRemove,
            scope: me
        });

        me.syncAllFieldWidths();

        if (me.floatingButtons) {
            me.body.dom.setAttribute('aria-owns', me.floatingButtons.id);
        }
    },

    initKeyNav: function() {
        var me = this,
            plugin = me.editingPlugin;

        me.keyNav = new Ext.util.KeyNav({
            target: me.el,
            tab: {
                fn: me.onFieldTab,
                scope: me
            },
            enter: plugin.onEnterKey,
            esc: plugin.onEscKey,
            scope: plugin
        });
    },

    onBeforeViewRefresh: function(view) {
        var me = this,
            viewDom = view.el.dom;

        if (me.el.dom.parentNode === viewDom) {
            viewDom.removeChild(me.el.dom);
        }
    },

    onViewRefresh: function(view) {
        var me = this,
            context = me.context,
            row;

        // Ignore refresh caused by the completion process
        if (!me.completing) {
            // Recover our row node after a view refresh
            // Note that refresh could have been caused by column removal
            if (context && !context.column.destroyed && (row = view.getRow(context.record))) {
                if (view === context.column.getView()) {
                    context.row = row;
                    context.view = view;
                    me.reposition();

                    if (me.tooltip && me.tooltip.isVisible()) {
                        me.tooltip.setTarget(context.row);
                    }
                }
            }
            else {
                me.editingPlugin.cancelEdit();
            }
        }
    },

    onViewItemRemove: function(records, index, items, view) {
        var me = this,
            context = me.context,
            grid,
            store,
            gridView,
            plugin;

        // If the itemremove is due to refreshing, or we are not visible ignore it.
        // If the row for the current context record has gone after the
        // refresh, editing will be canceled there. See onViewRefresh above.
        if (!view.refreshing && context) {
            plugin = me.editingPlugin;
            grid = plugin.grid;
            store = grid.getStore();
            gridView = me.editingPlugin.view;

            // Checking if this is a deleted record or an element being derendered
            if (store.getById(me.getRecord().getId()) && !me._cachedNode) {
                // if this is an item being derendered and is also being edited
                // the flag _cachedNode will be set to true and an itemadd event will
                // be added to monitor when the editor should be reactivated.
                if (plugin.editing) {
                    me._cachedNode = true;
                    me.mon(gridView, {
                        itemadd: me.onViewItemAdd,
                        scope: me
                    });
                }
            }
            else if (!me._cachedNode) {
                me.activeField = null;
                me.editingPlugin.cancelEdit();
            }
        }
    },

    onViewItemAdd: function(records, index, items, view) {
        var me = this,
            plugin = me.editingPlugin,
            gridView, idx, record;

        // Checks if BufferedRenderer is adding the items 
        // if there was an item being edited, and it belongs to this batch
        // then update the row and node associations.
        if (me._cachedNode && me.context) {
            gridView = plugin.view;

            // Checks if there is an array of records being added
            // and if within this array, any record matches the one being edited before
            // if it does, the editor context is updated, the itemadd
            // event listener is removed and _cachedNode is cleared.
            if ((idx = Ext.Array.indexOf(records, me.context.record)) !== -1) {
                record = records[idx];
                me.context.node = record;
                me.context.row = gridView.getRow(record);
                me.context.cell = gridView.getCellByPosition(me.context, true);
                me.clearCache();
            }
        }
    },

    onViewScroll: function() {
        var me = this,
            viewEl = me.editingPlugin.view.el,
            scrollingView = me.scrollingView,
            scrollTop = me.scroller.getPosition().y,
            scrollLeft = scrollingView.getScrollX(),
            scrollTopChanged = scrollTop !== me.lastScrollTop,
            row;

        me.lastScrollTop = scrollTop;
        me.lastScrollLeft = scrollLeft;

        if (me.isVisible()) {
            row = Ext.getDom(me.context.row);

            // Only reposition if the row is in the DOM (buffered rendering may mean
            // the context row is not there)
            if (row && viewEl.contains(row)) {
                // This makes sure the Editor is repositioned if it was scrolled out of buffer range
                if (me.getLocalY()) {
                    me.setLocalY(0);
                }

                if (scrollTopChanged) {
                    // The row element in the context may be stale due to buffered rendering
                    // removing out-of-view rows, then re-inserting newly rendered ones
                    me.context.row = row;
                    me.reposition(null, true);

                    if ((me.tooltip && me.tooltip.isVisible())) {
                        me.repositionTip();
                    }
                }
            }
            // If row is NOT in the DOM, ensure the editor is out of sight
            else {
                me.setLocalY(-400);
                me.floatingButtons.hide();
            }
        }
    },

    onColumnResize: function(column, width) {
        var me = this;

        if (me.rendered && !me.editingPlugin.reconfiguring) {
            // Need to ensure our lockable/normal horizontal scrollrange is set
            me.onGridResize();
            me.onViewScroll();

            // The layout will have zeroed scroll position on the header, and we will
            // have synced to that, so resync to the correct state.
            if (me.lockable) {
                me.lockedColumnContainer.getScrollable().syncWithPartners();
                me.normalColumnContainer.getScrollable().syncWithPartners();
            }
            else {
                me.getScrollable().syncWithPartners();
            }

            if (!column.isGroupHeader) {
                me.syncFieldWidth(column);
                me.repositionIfVisible();
            }
        }
    },

    onColumnHide: function(column) {
        if (!this.editingPlugin.reconfiguring && !column.isGroupHeader) {
            column.getEditor().hide();
            this.repositionIfVisible();
        }
    },

    onColumnShow: function(column) {
        var me = this;

        if (me.rendered && !me.editingPlugin.reconfiguring && !column.isGroupHeader &&
            column.getEditor) {
            column.getEditor().show();
            me.syncFieldWidth(column);

            if (!me.preventReposition) {
                me.repositionIfVisible();
            }
        }
    },

    onColumnMove: function(column, fromIdx, toIdx) {
        var me = this,
            locked = column.isLocked(),
            fieldContainer = locked ? me.lockedColumnContainer : me.normalColumnContainer,
            columns, i, len, after, offset;

        // If moving a group, move each leaf header
        if (column.isGroupHeader) {
            Ext.suspendLayouts();
            after = toIdx > fromIdx;
            offset = after ? 1 : 0;
            columns = column.getGridColumns();

            for (i = 0, len = columns.length; i < len; ++i) {
                column = columns[i];
                toIdx = column.getIndex();

                if (after) {
                    ++offset;
                }

                me.setColumnEditor(column, toIdx + offset, fieldContainer);
            }

            Ext.resumeLayouts(true);
        }
        else {
            me.setColumnEditor(column, column.getIndex(), fieldContainer);
        }
    },

    setColumnEditor: function(column, idx, fieldContainer) {
        this.addFieldsForColumn(column);
        fieldContainer.insert(idx, column.getEditor());
    },

    onColumnAdd: function(column, pos) {

        // If a column header added, process its leaves
        if (column.isGroupHeader) {
            column = column.getGridColumns();
        }

        this.preventReposition = true;
        this.addFieldsForColumn(column);
        this.insertColumnEditor(column, pos);
        this.preventReposition = false;
    },

    insertColumnEditor: function(column, pos) {
        var me = this,
            field,
            fieldContainer,
            len, i;

        if (Ext.isArray(column)) {
            for (i = 0, len = column.length; i < len; i++) {
                me.insertColumnEditor(column[i]);
            }

            return;
        }

        if (!column.getEditor) {
            return;
        }

        if (pos == null) {
            pos = column.getIndex();
        }

        fieldContainer = column.isLocked() ? me.lockedColumnContainer : me.normalColumnContainer;

        // Insert the column's field into the editor panel.
        fieldContainer.insert(pos, field = column.getEditor());

        // Ensure the view scrolls the field into view on focus
        field.on('focus', me.onFieldFocus, me);

        me.needsSyncFieldWidths = true;
    },

    onFieldFocus: function(field) {
        // Cache the active field so that we can restore focus into its cell onHide

        // Makes the cursor always be placed at the end of the textfield
        // when the field is being edited for the first time.
        if (field.selectText) {
            field.selectText(field.inputEl.dom.value.length);
        }

        this.activeField = field;
        this.context.setColumn(field.column);

        // skipFocusScroll should be true right after the editor has been started
        if (!this.skipFocusScroll) {
            field.column.getView().getScrollable().ensureVisible(field.el);
        }
        else {
            this.skipFocusScroll = null;
        }
    },

    onFieldTab: function(e) {
        var me = this,
            activeField = me.activeField,
            rowIdx = me.context.rowIdx,
            forwards = !e.shiftKey,
            target = activeField[forwards ? 'nextNode' : 'previousNode'](':focusable'),
            count;

        // We must control where the focus goes on Tab key press in fields.
        // The reason is that if there are elements with tabIndex > 0 elsewhere
        // in the document, natural tabbing might go out of the RowEditor, and
        // it might take an undeterminable amount of Tab key presses to get back
        // to the RowEditor.
        e.stopEvent();

        // No field to TAB to, navigate forwards or backwards
        if (!target || !target.isDescendant(me)) {
            // Tabbing out of a dirty editor - wrap to the update button
            if (me.isDirty() && !me.autoUpdate) {
                me.floatingButtons.child('#update').focus();
            }
            else {
                count = me.view.dataSource.getCount();

                // Editor is clean - navigate to next or previous row
                rowIdx = rowIdx + (forwards ? 1 : -1);

                // Wrap around if we reached the end
                if (rowIdx < 0) {
                    rowIdx = count - 1;
                }
                else if (rowIdx >= count) {
                    rowIdx = 0;
                }

                if (forwards) {
                    target = me.down(':focusable:not([isButton]):first');

                    // If going back to the first column, scroll back to field.
                    // If we're in a locking view, this has to be done programatically
                    // to avoid jarring when navigating from the locked back into the normal side
                    activeField.column.getView().getScrollable().ensureVisible(
                        activeField.ownerCt.child(':focusable').el
                    );
                }
                else {
                    target = me.down(':focusable:not([isButton]):last');
                }

                // We need to park focus on a tab guard while the fields
                // are being updated with the values from new row. Also
                // we might need to scroll the view, and RowEditor transition
                // can be animated. We don't want screen readers to announce
                // the transitions.
                me.tabGuardBeforeEl.focus();

                me.editingPlugin.startEdit(rowIdx, target.column);
            }
        }
        else {
            target.focus();
        }
    },

    destroyColumnEditor: function(column) {
        var field;

        if (column.hasEditor() && (field = column.getEditor())) {
            field.destroy();
        }
    },

    getFloatingButtons: function() {
        var me = this,
            btns = me.floatingButtons;

        if (!btns && !me.destroying && !me.destroyed) {
            me.floatingButtons = btns = new Ext.grid.RowEditorButtons({
                ownerCmp: me,
                rowEditor: me,
                hidden: me.hidden
            });
        }

        return btns;
    },

    repositionIfVisible: function(c) {
        var me = this,
            view = me.view;

        // If we're showing ourselves, jump out
        // If the component we're showing doesn't contain the view
        if (c && (c === me || !c.el.isAncestor(view.el))) {
            return;
        }

        if (me.isVisible() && view.isVisible(true)) {
            me.reposition();
        }
    },

    isLayoutChild: function(ownerCandidate) {
        // RowEditor is not a floating component, but won't be laid out by the grid
        return false;
    },

    getRefOwner: function() {
        return this.editingPlugin.grid;
    },

    getRefItems: function(deep) {
        var me = this,
            result, buttons;

        if (me.lockable) {
            // refItems must include ALL children. Must include the two containers
            // because we don't know what is being searched for.
            result = [me.lockedColumnContainer];
            result.push.apply(result, me.lockedColumnContainer.getRefItems(deep));
            result.push(me.normalColumnContainer);
            result.push.apply(result, me.normalColumnContainer.getRefItems(deep));
        }
        else {
            result = me.callParent(arguments);
        }

        buttons = me.getFloatingButtons();

        if (buttons) {
            result.push.apply(result, buttons.getRefItems(deep));
        }

        return result;
    },

    reposition: function(animateConfig, fromScrollHandler) {
        var me = this,
            context = me.context,
            row = context && context.row,
            wrapEl = me.wrapEl,
            rowTop,
            localY,
            deltaY,
            afterPosition;

        // Position this editor if the context row is rendered (buffered rendering may mean
        // that it's not in the DOM at all)
        if (row && Ext.isElement(row)) {

            deltaY = me.syncButtonPosition(context);

            rowTop = me.calculateLocalRowTop(row);
            localY = me.calculateEditorTop(rowTop);

            // If not being called from scroll handler...
            // If the editor's top will end up above the fold
            // or the bottom will end up below the fold,
            // organize an afterPosition handler which will bring it into view and focus
            // the correct input field
            afterPosition = function() {
                me.syncEditorClip();
                me.wrapAnim = null;

                if (!fromScrollHandler) {
                    if (deltaY) {
                        me.scroller.scrollBy(0, deltaY, true);
                    }

                    me.focusColumnField(context.column);
                }
            };

            // Get the y position of the row relative to its top-most static parent.
            // offsetTop will be relative to the table, and is incorrect
            // when mixed with certain grid features (e.g., grouping).
            if (animateConfig) {
                me.wrapAnim = wrapEl.addAnimation(Ext.applyIf({
                    to: {
                        top: localY
                    },
                    duration: animateConfig.duration || 125,
                    callback: afterPosition
                }, animateConfig));
            }
            else {
                wrapEl.setLocalY(localY);
                afterPosition();
            }
        }
    },

    /**
     * @private
     * Returns the scroll delta required to scroll the context row into view in order to make
     * the whole of this editor visible.
     * @return {Number} the scroll delta. Zero if scrolling is not required.
     */
    getScrollDelta: function() {
        var me = this,
            scrollingViewDom = me.scroller.getElement().dom,
            context = me.context,
            body = me.body,
            deltaY = 0,
            clientHeight, scrollHeight, editorHeight;

        if (context) {
            deltaY = Ext.fly(context.row).getOffsetsTo(scrollingViewDom)[1];

            if (deltaY < 0) {
                deltaY -= body.getBorderPadding().beforeY;
            }
            else if (deltaY > 0) {
                clientHeight = scrollingViewDom.clientHeight;
                scrollHeight = scrollingViewDom.scrollHeight;
                editorHeight = me.getHeight() + me.floatingButtons.getHeight();

                // There might be not enough height to scroll
                if (clientHeight === scrollHeight && editorHeight > clientHeight) {
                    return 0;
                }

                deltaY =
                    Math.max(deltaY + editorHeight - clientHeight - body.getBorderWidth('b'), 0);

                if (deltaY > 0) {
                    deltaY -= body.getBorderPadding().afterY;
                }
            }
        }

        return deltaY;
    },

    //
    // Calculates the top pixel position of the passed row within the view's scroll space.
    // So in a large, scrolled grid, this could be several thousand pixels.
    //
    calculateLocalRowTop: function(row) {
        var grid = this.editingPlugin.grid;

        return Ext.fly(row).getOffsetsTo(grid)[1] - grid.el.getBorderWidth('t') +
               this.lastScrollTop;
    },

    // Given the top pixel position of a row in the scroll space,
    // calculate the editor top position in the view's encapsulating element.
    // This will only ever be in the visible range of the view's element.
    calculateEditorTop: function(rowTop) {
        var result = rowTop - this.lastScrollTop;

        if (this._buttonsOnTop) {
            result -= (this.body.dom.offsetHeight - this.context.row.offsetHeight -
                       this.body.getBorderPadding().afterY);
        }
        else {
            result -= this.body.getBorderPadding().beforeY;
        }

        return result;
    },

    getClientWidth: function() {
        var me = this,
            grid = me.editingPlugin.grid,
            lockedCmp,
            result;

        if (me.lockable) {
            lockedCmp = (grid.lockedGrid.collapsed && grid.lockedGrid.placeholder) ||
                        grid.lockedGrid;
            result = lockedCmp.getRegion().union(grid.scrollBody.el.getClientRegion()).width;
        }
        else {
            result = grid.view.el.dom.clientWidth;
        }

        return result;
    },

    getEditor: function(fieldInfo) {
        var me = this;

        if (Ext.isNumber(fieldInfo)) {
            // In a locked grid, a RowEditor uses 2 inner containers, so need to use CQ to retrieve
            // configured editors which were stamped with the isEditorComponent property
            // in Editing.createColumnField
            return me.query('[isEditorComponent]')[fieldInfo];
        }
        else if (fieldInfo.isHeader && !fieldInfo.isGroupHeader) {
            return fieldInfo.getEditor();
        }
    },

    addFieldsForColumn: function(column, initial) {
        var me = this,
            i, len, field, style;

        if (Ext.isArray(column)) {
            for (i = 0, len = column.length; i < len; i++) {
                me.addFieldsForColumn(column[i], initial);
            }

            return;
        }

        if (column.getEditor) {
            // Get a default display field if necessary
            field = column.getEditor(null, me.getDefaultFieldCfg());

            // Focus is managed by RowEditor
            field.preventRefocus = true;

            if (column.align === 'right') {
                style = field.fieldStyle;

                if (style) {
                    if (Ext.isObject(style)) {
                        // Create a copy so we don't clobber the object
                        style = Ext.apply({}, style);
                    }
                    else {
                        style = Ext.dom.Element.parseStyles(style);
                    }

                    if (!style.textAlign && !style['text-align']) {
                        style.textAlign = 'right';
                    }
                }
                else {
                    style = 'text-align:right';
                }

                field.fieldStyle = style;

            }

            if (column.xtype === 'actioncolumn') {
                field.fieldCls += ' ' + Ext.baseCSSPrefix + 'form-action-col-field';
            }

            if (me.isVisible() && me.context) {
                if (field.is('displayfield')) {
                    me.renderColumnData(field, me.context.record, column);
                }
                else {
                    field.suspendEvents();
                    field.setValue(me.context.record.get(column.dataIndex));
                    field.resumeEvents();
                }
            }

            if (column.hidden) {
                me.onColumnHide(column);
            }
            else if (column.rendered && !initial) {
                // Setting after initial render
                me.onColumnShow(column);
            }
        }
    },

    getDefaultFieldCfg: function() {
        return {
            xtype: 'displayfield',
            skipLabelForAttribute: true,
            // Override Field's implementation so that the default display fields
            // will not return values. This is done because
            // the display field will pick up column renderers from the grid.
            getModelData: function() {
                return null;
            }
        };
    },

    loadRecord: function(record) {
        var me = this,
            form = me.getForm(),
            fields = form.getFields(),
            items = fields.items,
            length = items.length,
            i, displayFields,
            isValid, item;

        // temporarily suspend events on form fields before loading record to prevent
        // the fields' change events from firing
        for (i = 0; i < length; i++) {
            item = items[i];
            item.suspendEvents();
            item.resetToInitialValue();
        }

        form.loadRecord(record);

        for (i = 0; i < length; i++) {
            items[i].resumeEvents();
        }

        // Because we suspend the events, none of the field events will get propagated to
        // the form, so the valid state won't be correct.
        if (form.hasInvalidField() === form.wasValid) {
            delete form.wasValid;
        }

        isValid = form.isValid();

        if (me.errorSummary) {
            if (isValid) {
                me.hideToolTip();
            }
            else {
                me.showToolTip();
            }
        }

        me.updateButton(isValid);

        // render display fields so they honor the column renderer/template
        displayFields = me.query('>displayfield');
        length = displayFields.length;

        for (i = 0; i < length; i++) {
            me.renderColumnData(displayFields[i], record);
        }
    },

    renderColumnData: function(field, record, activeColumn) {
        var me = this,
            grid = me.editingPlugin.grid,
            headerCt = grid.headerCt,
            view = me.scrollingView,
            store = view.dataSource,
            column = activeColumn || field.column,
            value = record.get(column.dataIndex),
            renderer = column.editRenderer || column.renderer,
            metaData,
            rowIdx,
            colIdx,
            scope = (column.usingDefaultRenderer && !column.scope) ? column : column.scope;

        // honor our column's renderer (TemplateHeader sets renderer for us!)
        if (renderer) {
            metaData = { tdCls: '', style: '' };
            rowIdx = store.indexOf(record);
            colIdx = headerCt.getHeaderIndex(column);

            value = renderer.call(
                scope || headerCt.ownerCt,
                value,
                metaData,
                record,
                rowIdx,
                colIdx,
                store,
                view
            );
        }

        field.setRawValue(value);
    },

    beforeEdit: function() {
        var me = this,
            scrollDelta;

        // Can't show the editor on a fragile, floated locked side
        if (me.lockable && me.editingPlugin.grid.lockedGrid.floatedFromCollapse) {
            return false;
        }

        if (me.isVisible() && (me.isDirty() || me.context.record.phantom)) {
            if (me.autoUpdate) {
                me.editingPlugin.completeEdit();
            }
            else if (me.autoCancel) {
                me.editingPlugin.cancelEdit();
            }
            else if (me.errorSummary) {
                // Scroll the visible RowEditor that is in error state back into view
                scrollDelta = me.getScrollDelta();

                if (scrollDelta) {
                    me.scrollingViewEl.scrollBy(0, scrollDelta, true);
                }

                me.showToolTip();

                return false;
            }
        }
    },

    /**
     * Start editing the specified grid at the specified position.
     * @param {Ext.data.Model} record The Store data record which backs the row to be edited.
     * @param {Ext.data.Model} columnHeader The Column object defining the column to be focused
     */
    startEdit: function(record, columnHeader) {
        var me = this,
            editingPlugin = me.editingPlugin,
            grid = editingPlugin.grid,
            context = me.context = editingPlugin.context,
            alreadyVisible = me.isVisible(),
            wrapEl = me.wrapEl,
            wasRendered = me.rendered,
            label;

        if (me._cachedNode) {
            me.clearCache();
        }

        // Ensure that the render operation does not lay out
        // The show call will update the layout
        Ext.suspendLayouts();

        if (!wasRendered) {
            me.width = me.getClientWidth();
            me.render(grid.el, grid.el.dom.firstChild);

            // The wrapEl is a container for the editor and buttons.  We use a wrap el
            // (instead of rendering the buttons inside the editor) so that the editor and
            // buttons can be clipped separately when overflowing.
            // See https://sencha.jira.com/browse/EXTJS-13851
            wrapEl = me.wrapEl = me.el.wrap();

            // Change the visibilityMode to offsets so that we get accurate measurements
            // when the roweditor is hidden for laying out things like a TriggerField.
            wrapEl.setVisibilityMode(3);

            wrapEl.addCls(me._wrapCls);
            me.getFloatingButtons().render(wrapEl);

            // On first show we need to ensure that we have the scroll positions cached
            me.onViewScroll();
        }

        me.setLocalY(0);

        // Select at the clicked position.
        context.grid.getSelectionModel().selectByPosition({
            row: record,
            column: columnHeader
        });

        if (me.rendered && me.formAriaLabel) {
            label =
                Ext.String.formatEncode(me.formAriaLabel, me.formAriaLabelRowBase + context.rowIdx);
            me.body.dom.setAttribute('aria-label', label);
        }

        // Make sure the container el is correctly sized.
        me.onGridResize();

        // Layout the form with the new content if we are already visible.
        // Otherwise, just allow resumption, and the show will update the layout.
        Ext.resumeLayouts(alreadyVisible);

        if (alreadyVisible) {
            me.reposition(true);
        }
        else {
            // this will prevent the onFieldFocus method from calling
            // scrollIntoView right after startEdit as this will be
            // handled by the Editing plugin.
            me.skipFocusScroll = true;

            me.show();
        }

        // Reload the record data.
        // After positioning so that any error tip will be aligned correctly.
        me.loadRecord(record);

        // Sync our scroll position on first show
        if (!wasRendered) {
            if (me.lockable) {
                me.lockedColumnContainer.getScrollable().syncWithPartners();
                me.normalColumnContainer.getScrollable().syncWithPartners();
            }
            else {
                me.getScrollable().syncWithPartners();
            }
        }
    },

    // determines the amount by which the row editor will overflow, and flips the buttons
    // to the top of the editor if the required scroll amount is greater than the available
    // scroll space. Returns the scrollDelta required to scroll the editor into view after
    // adjusting the button position.
    syncButtonPosition: function(context) {
        var me = this,
            scrollDelta = me.getScrollDelta(),
            floatingButtons = me.getFloatingButtons(),
            // If this is negative, it means we're not scrolling so lets just ignore it
            scrollHeight = Math.max(0, me.scroller.getSize().y - me.scroller.getClientSize().y),
            overflow = scrollDelta - (scrollHeight - me.scroller.getPosition().y);

        floatingButtons.show();

        // If that's the last visible row, buttons should be at the top regardless of scrolling,
        // but not if there is just one row which is both first and last.
        if (overflow > 0 || (context.rowIdx > 0 && context.isLastRenderedRow())) {
            if (!me._buttonsOnTop) {
                floatingButtons.setButtonPosition('top');
                me._buttonsOnTop = true;
                me.layout.setAlign('bottom');
                me.updateLayout();
            }

            scrollDelta = 0;
        }
        else if (me._buttonsOnTop !== false) {
            floatingButtons.setButtonPosition('bottom');
            me._buttonsOnTop = false;
            me.layout.setAlign('top');
            me.updateLayout();
        }
        // Ensure button Y position is synced with Editor height even if button
        // orientation doesn't change
        else {
            floatingButtons.setButtonPosition(floatingButtons.position);
        }

        return scrollDelta;
    },

    syncEditorClip: function() {
        // Since the editor is rendered to the grid el, all its visible parts must be clipped
        // when scrolled outside of the grid view area so that it does not overlap the scrollbar
        // or docked items.
        var me = this,
            tip = me.tooltip,
            // Clipping region must be *within* scrollbars, so in the case of locking view,
            // we cannot use the lockingView's el because that *contains* two grids.
            // We must use the scroller el.
            clipRegion = me.scroller.getElement().getConstrainRegion();

        me.clipTo(clipRegion);
        me.floatingButtons.clipTo(clipRegion);

        if (tip && tip.isVisible()) {
            tip.clipTo(clipRegion, 5);
        }
    },

    focusColumnField: function(column) {
        var field, didFocus;

        if (column && !column.destroyed) {
            if (column.isVisible()) {
                field = this.getEditor(column);

                if (field && field.isFocusable(true)) {
                    didFocus = true;
                    field.focus();
                }
            }

            if (!didFocus) {
                this.focusColumnField(column.next());
            }
        }
    },

    cancelEdit: function() {
        var me = this,
            form = me.getForm(),
            fields = form.getFields(),
            items = fields.items,
            length = items.length,
            i,
            record = me.context.record;

        if (me._cachedNode) {
            me.clearCache();
        }

        me.hide();

        // If we are editing a new record, and we cancel still in invalid state, then remove it.
        if (record && record.phantom && !record.modified && me.removeUnmodified) {
            me.editingPlugin.grid.store.remove(record);
        }

        form.clearInvalid();

        // temporarily suspend events on form fields before reseting the form to prevent
        // the fields' change events from firing
        for (i = 0; i < length; i++) {
            items[i].suspendEvents();
        }

        form.reset();

        for (i = 0; i < length; i++) {
            items[i].resumeEvents();
        }
    },

    /*
    * @private
    */
    clearCache: function() {
        var me = this;

        me.mun(me.editingPlugin.view, {
            itemadd: me.onViewItemAdd,
            scope: me
        });
        me._cachedNode = false;
    },

    completeEdit: function() {
        var me = this,
            form = me.getForm();

        if (!form.isValid()) {
            return false;
        }

        me.completing = true;
        form.updateRecord(me.context.record);
        me.hide();
        me.completing = false;

        return true;
    },

    onShow: function() {
        var me = this;

        me.wrapEl.show();
        me.callParent(arguments);

        if (me.needsSyncFieldWidths) {
            me.suspendLayouts();
            me.preventReposition = true;
            me.syncAllFieldWidths();
            me.preventReposition = false;
            me.resumeLayouts(true);
        }

        delete me.needsSyncFieldWidths;

        if (me.rendered) {
            me.initTabGuards(true);
        }

        me.reposition();
    },

    onHide: function() {
        var me = this,
            context = me.context,
            column,
            focusContext,
            activeEl = Ext.Element.getActiveElement();

        me.context = null;

        // If they used ESC or ENTER in a Field
        if (me.el.contains(activeEl) && me.activeField) {
            column = me.activeField.column;
        }
        // If they used a button
        else {
            column = context.column;
        }

        // Hiding could have been caused by removing our column
        if (column && !column.destroyed) {
            focusContext =
                new Ext.grid.CellContext(column.getView()).setPosition(context.record, column);
            focusContext.view.getNavigationModel().setPosition(focusContext);
        }

        me.activeField = null;
        me.wrapEl.hide();

        me.callParent(arguments);

        // RowEditor is hidden via offsets so need to deactivate tab guards manually
        if (me.rendered) {
            me.initTabGuards(false);
        }

        if (me.tooltip) {
            me.hideToolTip();
        }
    },

    onResize: function(width, height) {
        this.wrapEl.setSize(width, height);
    },

    isDirty: function() {
        return this.getForm().isDirty();
    },

    getToolTip: function() {
        var me = this,
            tip = me.tooltip,
            grid = me.editingPlugin.grid;

        if (!tip) {
            me.tooltip = tip = new Ext.tip.ToolTip({
                cls: Ext.baseCSSPrefix + 'grid-row-editor-errors',
                title: me.errorsText,
                autoHide: false,
                closable: true,
                closeAction: 'disable',
                anchor: 'left',
                anchorToTarget: true,
                targetOffset: [Ext.scrollbar.width(), 0],
                constrainPosition: true,
                constrainTo: document.body
            });
            grid.add(tip);

            // Layout may change the grid's positioning.
            me.mon(grid, {
                afterlayout: me.onGridLayout,
                scope: me
            });
        }

        return tip;
    },

    hideToolTip: function() {
        var me = this,
            tip = me.getToolTip();

        if (tip.rendered) {
            tip.disable();
        }
    },

    showToolTip: function(wrapAnim) {
        var me = this,
            tip = me.getToolTip();

        // If called while we are moving, wait till new position.
        if (!wrapAnim && me.wrapAnim) {
            return me.wrapAnim.on({
                afteranimate: me.showToolTip,
                scope: me,
                single: true
            });
        }

        tip.update(me.getErrors());
        me.repositionTip();
        tip.enable();
    },

    onGridLayout: function() {
        if (this.tooltip && this.tooltip.isVisible()) {
            this.repositionTip();
        }
    },

    repositionTip: function() {
        var me = this,
            tip = me.getToolTip();

        if (tip.isVisible()) {
            tip.realignToTarget();
        }
        else {
            tip.showBy(me.el);
        }

        me.syncEditorClip();
    },

    getErrors: function() {
        var me = this,
            errors = [],
            fields = me.query('>[isFormField]'),
            length = fields.length,
            i, fieldErrors, field;

        for (i = 0; i < length; i++) {
            field = fields[i];
            fieldErrors = field.getErrors();

            if (fieldErrors.length) {
                errors.push(me.createErrorListItem(fieldErrors[0], field.column.text));
            }
        }

        // Only complain about unsaved changes if all the fields are valid
        if (!errors.length && !me.autoCancel && me.isDirty()) {
            errors[0] = me.createErrorListItem(me.dirtyText);
        }

        return '<ul class="' + Ext.baseCSSPrefix + 'list-plain">' + errors.join('') + '</ul>';
    },

    createErrorListItem: function(e, name) {
        e = name ? name + ': ' + e : e;

        return '<li class="' + this.errorCls + '">' + e + '</li>';
    },

    doDestroy: function() {
        var me = this;

        if (me.wrapAnim) {
            Ext.fx.Manager.removeAnim(me.wrapAnim);
            me.wrapAnim = null;
        }

        // Properties must be cleared because class-specific getRefItems explicitly references them.
        me.keyNav = me.floatingButtons = me.tooltip =
            Ext.destroy(me.keyNav, me.floatingButtons, me.tooltip, me.wrapEl);

        me.callParent();
    }
});
