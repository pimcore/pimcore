/**
 * The Ext.grid.plugin.CellEditing plugin injects editing at a cell level for a Grid. Only a single
 * cell will be editable at a time. The field that will be used for the editor is defined at the
 * {@link Ext.grid.column.Column#editor editor}. The editor can be a field instance or a field
 * configuration.
 *
 * If an editor is not specified for a particular column then that cell will not be editable
 * and it will be skipped when activated via the mouse or the keyboard.
 *
 * The editor may be shared for each column in the grid, or a different one may be specified
 * for each column. An appropriate field type should be chosen to match the data structure
 * that it will be editing. For example, to edit a date, it would be useful to specify
 * {@link Ext.form.field.Date} as the editor.
 *
 * If the `editor` config on a column contains a `field` property, then the `editor` config
 * is used to create the wrapping {@link Ext.grid.CellEditor CellEditor}, and the `field` property
 * is used to create the editing  input field.
 *
 * ## Example
 *
 * A grid with editor for the name and the email columns:
 *
 *     @example
 *     Ext.create('Ext.data.Store', {
 *         storeId: 'simpsonsStore',
 *         fields:[ 'name', 'email', 'phone'],
 *         data: [
 *             { name: 'Lisa', email: 'lisa@simpsons.com', phone: '555-111-1224' },
 *             { name: 'Bart', email: 'bart@simpsons.com', phone: '555-222-1234' },
 *             { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
 *             { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254' }
 *         ]
 *     });
 *
 *     Ext.create('Ext.grid.Panel', {
 *         title: 'Simpsons',
 *         store: Ext.data.StoreManager.lookup('simpsonsStore'),
 *         columns: [
 *             {header: 'Name', dataIndex: 'name', editor: 'textfield'},
 *             {header: 'Email', dataIndex: 'email', flex:1,
 *                 editor: {
 *                     completeOnEnter: false,
 *
 *                     // If the editor config contains a field property, then
 *                     // the editor config is used to create the CellEditor
 *                     // and the field property is used to create the editing input field.
 *                     field: {
 *                         xtype: 'textfield',
 *                         allowBlank: false
 *                     }
 *                 }
 *             },
 *             {header: 'Phone', dataIndex: 'phone'}
 *         ],
 *         selModel: 'cellmodel',
 *         plugins: {
 *             cellediting: {
 *                 clicksToEdit: 1
 *             }
 *         },
 *         height: 200,
 *         width: 400,
 *         renderTo: Ext.getBody()
 *     });
 *
 * This requires a little explanation. We're passing in `store` and `columns` as normal, but
 * we also specify a {@link Ext.grid.column.Column#field field} on two of our columns. For the
 * Name column we just want a default textfield to edit the value, so we specify 'textfield'.
 * For the Email column we customized the editor slightly by passing allowBlank: false, which
 * will provide inline validation.
 *
 * To support cell editing, we also specified that the grid should use the 'cellmodel'
 * {@link Ext.grid.Panel#selModel selModel}, and created an instance of the CellEditing plugin,
 * which we configured to activate each editor after a single click.
 *
 */
Ext.define('Ext.grid.plugin.CellEditing', {
    alias: 'plugin.cellediting',
    extend: 'Ext.grid.plugin.Editing',

    requires: [
        'Ext.grid.CellEditor',
        'Ext.util.DelayedTask'
    ],

    /**
     * @event beforeedit
     * Fires before cell editing is triggered. Return false from event handler to stop the editing.
     *
     * @param {Ext.grid.plugin.CellEditing} editor
     * @param {Object} context An editing context event with the following properties:
     *  @param {Ext.grid.Panel} context.grid The owning grid Panel.
     *  @param {Ext.data.Model} context.record The record being edited.
     *  @param {String} context.field The name of the field being edited.
     *  @param {Mixed} context.value The field's current value.
     *  @param {HTMLElement} context.row The grid row element.
     *  @param {Ext.grid.column.Column} context.column The {@link Ext.grid.column.Column} Column}
     * being edited.
     *  @param {Number} context.rowIdx The index of the row being edited.
     *  @param {Number} context.colIdx The index of the column being edited.
     *  @param {Boolean} context.cancel Set this to `true` to cancel the edit or return false
     * from your handler.
     */

    /**
     * @event edit
     * Fires after a cell is edited. Usage example:
     *
     *     grid.on('edit', function(editor, e) {
     *         // commit the changes right after editing finished
     *         e.record.commit();
     *     });
     *
     * @param {Ext.grid.plugin.CellEditing} editor
     * @param {Object} context An editing context with the following properties:
     *  @param {Ext.grid.Panel} context.grid The owning grid Panel.
     *  @param {Ext.data.Model} context.record The record being edited.
     *  @param {String} context.field The name of the field being edited.
     *  @param {Mixed} context.value The field's current value.
     *  @param {HTMLElement} context.row The grid row element.
     *  @param {Ext.grid.column.Column} context.column The {@link Ext.grid.column.Column} Column}
     * being edited.
     *  @param {Number} context.rowIdx The index of the row being edited.
     *  @param {Number} context.colIdx The index of the column being edited.
     *  @param {Mixed} context.originalValue The original value before being edited.
     */

    /**
     * @event validateedit
     * Fires after a cell is edited, but before the value is set in the record.
     * There are three possible outcomes when handling the validateedit event:
     *
     *  - Return `true` - Return true to commit the change to the underlying record and
     *    hide the editor
     *  - Return 'false' - Return false to prevent 1) the edit from being committed to
     *    the underlying record and 2) the editor from hiding / blurring.
     *  - Set context.cancel: true and return `false` - Set the context param's cancel property
     *    to true and returning false will 1) prevent the edit from being committed to
     *    the underlying record but _will_ allow the edit to hide once blurred.
     *
     * In the following example, entering 10 in the editor field and tabbing out /
     * blurring the editor field will result in the the editor remaining focused as the
     * required validation criteria has not been met.
     *
     *     grid.on('validateedit', function(editor, context) {
     *         if (context.value < 10) {
     *             return false;
     *         }
     *     });
     *
     * If we modify the previous example by setting context.cancel to true then changing
     * the editor value from 2 to 10 and tabbing out of the field will result in the
     * editor hiding and the grid cell retaining the initial value of 2.
     *
     *     grid.on('validateedit', function(editor, context) {
     *         if (context.value < 10) {
     *             context.cancel = true;
     *             return false;
     *         }
     *     });
     *
     * Below is a usage example showing how to remove the red triangle (dirty-record
     * indicator) from some records (not all). By observing the grid's validateedit
     * event, it can be cancelled if the edit occurs on a targeted row (for example) and
     * then setting the field's new value in the Record directly:
     *
     *     grid.on('validateedit', function(editor, e) {
     *       var myTargetRow = 6;
     *
     *       if (e.row == myTargetRow) {
     *         e.cancel = true;
     *         e.record.data[e.field] = e.value;
     *       }
     *     });
     *
     * @param {Ext.grid.plugin.CellEditing} editor
     * @param {Object} context An editing context with the following properties:
     * @param {Ext.grid.Panel} context.grid The owning grid Panel.
     * @param {Ext.data.Model} context.record The record being edited.
     * @param {String} context.field The name of the field being edited.
     * @param {Mixed} context.value The field's current value.
     * @param {HTMLElement} context.row The grid row element.
     * @param {Ext.grid.column.Column} context.column The {@link Ext.grid.column.Column} Column}
     * being edited.
     * @param {Number} context.rowIdx The index of the row being edited.
     * @param {Number} context.colIdx The index of the column being edited.
     * @param {Mixed} context.originalValue The original value before being edited.
     * @param {Boolean} context.cancel Set this to `true` to cancel the edit or return false
     * from your handler (see the method description for additional details).
     */

    /**
     * @event canceledit
     * Fires when the user started editing a cell but then cancelled the edit.
     * @param {Ext.grid.plugin.CellEditing} editor
     * @param {Object} context An edit event with the following properties:
     * @param {Ext.grid.Panel} context.grid The owning grid Panel.
     * @param {Ext.data.Model} context.record The record being edited.
     * @param {String} context.field The name of the field being edited.
     * @param {Mixed} context.value The field's current value.
     * @param {HTMLElement} context.row The grid row element.
     * @param {Ext.grid.column.Column} context.column The {@link Ext.grid.column.Column} Column}
     * being edited.
     * @param {Number} context.rowIdx The index of the row being edited.
     * @param {Number} context.colIdx The index of the column being edited.
     * @param {Mixed} context.originalValue The original value before being edited.
     */

    restartEvent: null,
    cachedEditorValue: null,

    init: function(grid) {
        var me = this;

        // This plugin has an interest in entering actionable mode.
        // It places the cell editors into the tabbable flow.
        grid.registerActionable(me);

        me.callParent(arguments);

        me.editors = new Ext.util.MixedCollection(false, function(editor) {
            return editor.editorId;
        });
    },

    // Ensure editors are cleaned up.
    beforeGridHeaderDestroy: function(headerCt) {
        var me = this,
            columns = me.grid.getColumnManager().getColumns(),
            len = columns.length,
            i,
            column,
            editor;

        for (i = 0; i < len; i++) {
            column = columns[i];

            // Try to get the CellEditor which contains the field to destroy the whole assembly
            editor = me.editors.getByKey(column.getItemId());

            // Failing that, the field has not yet been accessed to add to the CellEditor,
            // but must still be destroyed
            if (!editor) {
                // If we have an editor, it will wrap the field which will be destroyed.
                editor = column.editor || column.field;
            }

            // Destroy the CellEditor or field
            Ext.destroy(editor);
            me.removeFieldAccessors(column);
        }
    },

    onReconfigure: function(grid, store, columns) {
        // Only reconfigure editors if passed a new set of columns
        if (columns) {
            this.destroyEditors();
        }

        this.callParent();
    },

    destroy: function() {
        var me = this;

        me.destroyEditors();

        if (me.restartEvent) {
            me.restartEvent = me.cachedEditorValue = Ext.destroy(me.restartEvent);
        }

        me.callParent();
    },

    /**
     * @private
     * Template method called from the base class's initEvents
     */
    initCancelTriggers: function() {
        var me = this,
            grid = me.grid;

        me.mon(grid, {
            columnresize: me.cancelEdit,
            columnmove: me.cancelEdit,
            scope: me
        });
    },

    isCellEditable: function(record, columnHeader) {
        var me = this,
            context = me.getEditingContext(record, columnHeader);

        if (context.view.isVisible(true) && context) {
            columnHeader = context.column;
            record = context.record;

            if (columnHeader && me.getEditor(record, columnHeader)) {
                return true;
            }
        }
    },

    /**
     * This method is called when actionable mode is requested for a cell. 
     * @param {Ext.grid.CellContext} position The position at which actionable mode was requested.
     * @param {Boolean} skipBeforeCheck Pass `true` to skip the possible vetoing conditions
     * like event firing.
     * @param {Boolean} doFocus Pass `true` to immediately focus the active editor.
     * @return {Boolean} `true` if this cell is actionable (editable)
     * @protected
     */
    activateCell: function(position, skipBeforeCheck, doFocus) {
        var me = this,
            record = position.record,
            column = position.column,
            prevEditor = me.getActiveEditor(),
            view = me.view,
            isResuming = me.restartEvent != null,
            context, contextGeneration, cell, editor, p, editValue, abortEdit;

        if (isResuming) {
            me.restartEvent = Ext.destroy(me.restartEvent);
        }

        context = me.getEditingContext(record, column);

        if (!context || !column.getEditor(record)) {
            return;
        }

        // Activating a new cell while editing.
        // Complete the edit, and cache the editor in the detached body.
        if (prevEditor && prevEditor.editing) {
            // Silently drop actionPosition in case completion of edit causes
            // and view refreshing which would attempt to restore actionable mode
            view.actionPosition = null;

            contextGeneration = context.generation;

            if (prevEditor.completeEdit() === false) {
                return;
            }

            // Complete edit could cause a sort or column movement.
            // Reposition context unless user code has modified it for its own purposes.
            if (context.generation === contextGeneration) {
                context.refresh();
            }
        }

        if (!skipBeforeCheck) {
            // Allow vetoing, or setting a new editor *before* we call getEditor
            contextGeneration = context.generation;

            // Disable focus restoration in any of the before edit handling.
            // We are going to be doing that below
            if (view.actionableMode) {
                view.skipSaveFocusState = true;
            }

            abortEdit = me.beforeEdit(context) === false ||
                        me.fireEvent('beforeedit', me, context) === false || context.cancel;

            // Clear temporary flag
            view.skipSaveFocusState = false;

            if (abortEdit) {
                return;
            }

            // beforeedit edit could cause sort or column movement
            // Reposition context unless user code has modified it for its own purposes.
            if (context.generation === contextGeneration) {
                context.refresh();
            }
        }

        // Recapture the editor. The beforeedit listener is allowed to replace the field.
        editor = me.getEditor(record, column);

        // If the events fired above ('beforeedit' and potentially 'edit') triggered
        // any destructive operations regather the context using the ordinal position.
        if (context.cell !== context.getCell(true)) {
            context = me.getEditingContext(context.rowIdx, context.colIdx, null, context.view);
            position.setPosition(context);
        }

        if (editor) {
            cell = Ext.get(context.cell);

            // Ensure editor is there in the cell.
            // And will then be found in the tabbable children of the activating cell
            if (!editor.rendered) {
                editor.hidden = true;
                editor.render(cell);
            }
            else {
                p = editor.el.dom.parentNode;

                if (p !== cell.dom) {
                    // This can sometimes throw an error
                    // https://code.google.com/p/chromium/issues/detail?id=432392
                    try {
                        p.removeChild(editor.el.dom);
                    }
                    catch (e) {
                        // ignore
                    }

                    if (editor.container && editor.container.dom !== cell.dom) {
                        editor.container.collect();
                    }

                    editor.container = cell;
                    cell.dom.appendChild(editor.el.dom, cell.dom.firstChild);
                }
            }

            // Refresh the contextual value in case any event handlers (either the 'beforeedit'
            // of this edit, or the 'edit' of any just terminated previous editor) mutated
            // the record
            // https://sencha.jira.com/browse/EXTJS-19899
            editValue = context.record.get(context.column.dataIndex);

            if (editValue !== context.originalValue) {
                context.value = context.originalValue = editValue;
            }

            me.setEditingContext(context);

            // Request that the editor start.
            // Ensure that the focusing defaults to false.
            // It may veto, and return with the editing flag false.
            editor.startEdit(cell, context.value, (doFocus && !isResuming) || false, isResuming);

            // Set contextual information if we began editing (can be vetoed by events)
            if (editor.editing) {
                me.setActiveEditor(editor);
                me.setActiveRecord(context.record);
                me.setActiveColumn(context.column);
                me.editing = true;
                me.scroll = position.view.el.getScroll();

                if (isResuming) {
                    editor.setValue(me.cachedEditorValue);
                    me.cachedEditorValue = null;
                }
            }

            // Return true if the cell is actionable according to us
            return editor.editing;
        }
    },

    // CellEditing only activates individual cells.
    activateRow: Ext.emptyFn,

    /**
     * Cancels the currently focused operation. In this case CellEditing.
     * the view is being changed.
     * @protected
     */
    deactivate: function() {
        var me = this,
            context = me.context,
            editors = me.editors.items,
            len = editors.length,
            editor, i, callback;

        for (i = 0; i < len; i++) {
            editor = editors[i];

            // if we are deactivating the editor because it was de-rendered by a bufferedRenderer
            // cycle (scroll while editing), we should retain the editor's info before caching
            // also only run if we don't have a suspendedEditor to make sure we don't add two 
            // listeners on locked grids.
            if (context.view.renderingRows && !me.suspendedEditor) {
                if (editor.editing) {
                    me.suspendedEditor = editor;
                    me.cachedEditorValue = editor.getValue();

                    callback = function() {
                        var ctx;

                        if (me.suspendedEditor) {
                            ctx = me.suspendedEditor.context;

                            if (me.view.getNode(ctx.record)) {
                                me.view.ownerGrid.setActionableMode(true, ctx);
                                me.suspendedEditor = null;
                            }
                        }
                        else {
                            me.restartEvent = Ext.destroy(me.restartEvent);
                            me.cachedEditorValue = null;
                        }
                    };

                    me.cancelEdit(editor);

                    me.restartEvent = me.view.on({
                        itemadd: callback,
                        destroyable: true
                    });
                }

                editor.cacheElement(true);
            }
        }
    },

    /**
     * Called by TableView#suspendActionableMode to suspend actionable processing while
     * the view is being changed.
     * @protected
     */
    suspend: function() {
        var me = this,
            editor = me.activeEditor;

        if (editor && editor.editing) {
            me.suspendedEditor = editor;
            me.suspendEvents();
            editor.suspendEvents();
            editor.cancelEdit(true);
            editor.resumeEvents();
            me.resumeEvents();
        }
    },

    /**
     * Called by TableView#resumeActionableMode to resume actionable processing after
     * the view has been changed.
     * @param {Ext.grid.CellContext} position The position at which to resume actionable processing.
     * @return {Boolean} `true` if this Actionable has successfully resumed.
     * @protected
     */
    resume: function(position) {
        var me = this,
            editor = me.activeEditor = me.suspendedEditor,
            result;

        if (editor) {
            me.suspendEvents();
            editor.suspendEvents();
            result = me.activateCell(position, true, true);
            editor.resumeEvents();
            me.resumeEvents();
            me.suspendedEditor = null;
        }

        return result;
    },

    /**
     * @deprecated 5.5.0 Use the grid's {@link Ext.panel.Table#setActionableMode actionable mode}
     * to activate cell contents. Starts editing the specified record, using the specified Column
     * definition to define which field is being edited.
     * @param {Ext.data.Model/Number} record The Store data record which backs the row to be edited,
     * or index of the record.
     * @param {Ext.grid.column.Column/Number} columnHeader The Column object defining the column
     * to be edited, or index of the column.
     */
    startEdit: function(record, columnHeader) {
        this.startEditByPosition(
            new Ext.grid.CellContext(this.view).setPosition(record, columnHeader)
        );
    },

    completeEdit: function(remainVisible) {
        var activeEd = this.getActiveEditor();

        if (activeEd) {
            activeEd.completeEdit(remainVisible);
        }
    },

    // internal getters/setters
    setEditingContext: function(context) {
        this.context = context;
    },

    setActiveEditor: function(ed) {
        this.activeEditor = ed;
    },

    getActiveEditor: function() {
        return this.activeEditor;
    },

    setActiveColumn: function(column) {
        this.activeColumn = column;
    },

    getActiveColumn: function() {
        return this.activeColumn;
    },

    setActiveRecord: function(record) {
        this.activeRecord = record;
    },

    getActiveRecord: function() {
        return this.activeRecord;
    },

    getEditor: function(record, column) {
        return this.getCachedEditor(column.getItemId(), record, column);
    },

    getCachedEditor: function(editorId, record, column) {
        var me = this,
            editors = me.editors,
            editor = editors.getByKey(editorId);

        if (!editor) {
            editor = column.getEditor(record);

            if (!editor) {
                return false;
            }

            // Allow them to specify a CellEditor in the Column
            if (!(editor instanceof Ext.grid.CellEditor)) {
                // Apply the field's editorCfg to the CellEditor config.
                // See Editor#createColumnField. A Column's editor config may
                // be used to specify the CellEditor config if it contains a field property.
                editor = Ext.widget(Ext.apply({
                    xtype: 'celleditor',
                    floating: true,
                    editorId: editorId,
                    field: editor
                }, editor.editorCfg));
            }

            // Add the Editor as a floating child of the grid
            // Prevent this field from being included in an Ext.form.Basic
            // collection, if the grid happens to be used inside a form
            editor.field.excludeForm = true;

            // If the editor is new to this grid, then add it to the grid, and ensure
            // it tells us about its life cycle.
            if (editor.column !== column) {
                editor.column = column;
                column.on('removed', me.onColumnRemoved, me);
            }

            editors.add(editor);
        }

        // Inject an upward link to its owning grid even though it is not an added child.
        editor.ownerCmp = me.grid.ownerGrid;

        if (column.isTreeColumn) {
            editor.isForTree = column.isTreeColumn;
            editor.addCls(Ext.baseCSSPrefix + 'tree-cell-editor');
        }

        // Set the owning grid.
        // This needs to be kept up to date because in a Lockable assembly, an editor
        // needs to swap sides if the column is moved across.
        editor.setGrid(me.grid);

        // Keep upward pointer correct for each use - editors are shared between locking sides
        editor.editingPlugin = me;
        editor.collectContainerElement = true;

        return editor;
    },

    onColumnRemoved: function(column) {
        var me = this,
            context = me.context;

        // If the column was being edited, when plucked out of the grid, cancel the edit.
        if (context && context.column === column) {
            me.cancelEdit();
        }

        // Remove the CellEditor of that column from the grid, and no longer listen
        // for events from it.
        column.un('removed', me.onColumnRemoved, me);
    },

    setColumnField: function(column, field) {
        var ed = this.editors.getByKey(column.getItemId());

        Ext.destroy(ed, column.field);
        this.editors.removeAtKey(column.getItemId());
        this.callParent(arguments);
    },

    /**
     * Gets the cell (td) for a particular record and column.
     * @param {Ext.data.Model} record
     * @param {Ext.grid.column.Column} column
     * @param {Boolean} [returnElement=false] `true` to return an Ext.Element,
     * else a raw `<td>` is returned.
     * @private
     */
    getCell: function(record, column, returnElement) {
        return this.grid.getView().getCell(record, column, returnElement);
    },

    onEditComplete: function(ed, value, startValue) {
        var me = this,
            context = ed.context,
            view, record;

        view = context.view;
        record = context.record;
        context.value = value;

        // Only update the record if the new value is different than the
        // startValue. When the view refreshes its el will gain focus
        if (!record.isEqual(value, startValue)) {
            view.skipSaveFocusState = true;
            record.set(context.column.dataIndex, value);
            view.skipSaveFocusState = false;
            // Changing the record may impact the position
            context.rowIdx = view.indexOf(record);
        }

        // We clear down our context here in response to the CellEditor completing.
        // We only do this if we have not already started editing a new context.
        if (me.context === context) {
            me.setActiveEditor(null);
            me.setActiveColumn(null);
            me.setActiveRecord(null);
            me.editing = false;
        }

        me.fireEvent('edit', me, context);
    },

    /**
     * Cancels any active editing.
     */
    cancelEdit: function(activeEd) {
        var me = this,
            context = me.context;

        // Called from CellEditor#onEditComplete when canceling.
        if (activeEd && activeEd.isCellEditor) {
            me.context.value =
                ('editedValue' in activeEd) ? activeEd.editedValue : activeEd.getValue();

            // Editing flag cleared in superclass.
            // canceledit event fired in superclass.
            me.callParent(arguments);

            // Clear our current editing context.
            // We only do this if we have not already started editing a new context.
            if (activeEd.context === context) {
                me.setActiveEditor(null);
                me.setActiveColumn(null);
                me.setActiveRecord(null);
            }
            // Re-instate editing flag after callParent
            else {
                me.editing = true;
            }
        }
        // This is a programmatic call to cancel any active edit
        else {
            activeEd = me.getActiveEditor();

            if (activeEd && activeEd.field) {
                activeEd.cancelEdit();
            }
        }
    },

    /**
     * Starts editing by position (row/column)
     * @param {Object} position A position with keys of row and column.
     * Example usage:
     * 
     *     cellEditing.startEditByPosition({
     *         row: 3,
     *         column: 2
     *     });
     */
    startEditByPosition: function(position) {
        var me = this,
            cm = me.grid.getColumnManager(),
            index,
            activeEditor = me.getActiveEditor();

        // If a raw {row:0, column:0} object passed.
        // The historic API is that column indices INCLUDE hidden columns, so use getColumnManager.
        if (!position.isCellContext) {
            position = new Ext.grid.CellContext(me.view).setPosition(
                position.row, me.grid.getColumnManager().getColumns()[position.column]
            );
        }

        // Coerce the edit column to the closest visible column. This typically
        // only needs to be done when called programatically, since the position
        // is handled by walkCells, which is called before this is invoked.
        index = cm.getHeaderIndex(position.column);
        position.column = cm.getVisibleHeaderClosestToIndex(index);

        // Already in actionable mode.
        if (me.grid.actionableMode) {

            // We are being asked to edit right where we are (click in an active editor
            // will get here)
            if (me.editing && position.isEqual(me.context)) {
                return;
            }

            // Finish any current edit.
            if (activeEditor) {
                activeEditor.completeEdit();
            }
        }

        // If we are STILL in actionable mode - synchronous blurring has not tipped us
        // out of actionable mode...
        if (me.grid.actionableMode) {
            // Get the editor for the position, and if there is one, focus it
            if (me.activateCell(position)) {
                // Ensure the row is activated.
                me.activateRow(me.view.all.item(position.rowIdx, true));

                activeEditor = me.getEditor(position.record, position.column);

                if (activeEditor) {
                    activeEditor.field.focus();
                }
            }
        }
        else {
            // Enter actionable mode at the requested position
            return me.grid.setActionableMode(true, position);
        }
    },

    destroyEditors: function() {
        var me = this,
            editors = me.editors;

        if (editors) {
            editors.each(Ext.destroy, Ext);
            editors.clear();
        }
    }
});
