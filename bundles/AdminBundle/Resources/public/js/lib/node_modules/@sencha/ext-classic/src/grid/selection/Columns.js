/**
 * A class which encapsulates a range of columns defining a selection in a grid.
 * @since 5.1.0
 */
Ext.define('Ext.grid.selection.Columns', {
    extend: 'Ext.grid.selection.Selection',

    type: 'columns',

    /**
     * @property {Boolean} isColumns
     * This property indicates the this selection represents selected columns.
     * @readonly
     */
    isColumns: true,

    //-------------------------------------------------------------------------
    // Base Selection API

    clone: function() {
        var me = this,
            result = new me.self(me.view),
            columns = me.selectedColumns;

        if (columns) {
            result.selectedColumns = Ext.Array.slice(columns);
        }

        return result;
    },

    eachRow: function(fn, scope) {
        var columns = this.selectedColumns;

        if (columns && columns.length) {
            this.view.dataSource.each(fn, scope || this);
        }
    },

    eachColumn: function(fn, scope) {
        var me = this,
            view = me.view,
            columns = me.selectedColumns,
            len,
            i,
            context = new Ext.grid.CellContext(view);

        if (columns) {
            len = columns.length;

            for (i = 0; i < len; i++) {
                context.setColumn(columns[i]);

                if (fn.call(scope || me, context.column, context.colIdx) === false) {
                    return false;
                }
            }
        }
    },

    eachCell: function(fn, scope) {
        var me = this,
            view = me.view,
            columns = me.selectedColumns,
            len,
            i,
            context = new Ext.grid.CellContext(view);

        if (columns) {
            len = columns.length;

            // Use Store#each instead of copying the entire dataset into an array
            // and iterating that.
            view.dataSource.each(function(record) {
                context.setRow(record);

                for (i = 0; i < len; i++) {
                    context.setColumn(columns[i]);

                    if (fn.call(scope || me, context, context.colIdx, context.rowIdx) === false) {
                        return false;
                    }
                }
            });
        }
    },

    //-------------------------------------------------------------------------
    // Methods unique to this type of Selection

    /**
     * Returns `true` if the passed {@link Ext.grid.column.Column column} is selected.
     * @param {Ext.grid.column.Column} column The column to test.
     * @return {Boolean} `true` if the passed {@link Ext.grid.column.Column column} is selected.
     */
    contains: function(column) {
        var selectedColumns = this.selectedColumns;

        if (column && column.isColumn && selectedColumns && selectedColumns.length) {
            return Ext.Array.contains(selectedColumns, column);
        }

        return false;
    },

    /**
     * Returns the number of columns selected.
     * @return {Number} The number of columns selected.
     */
    getCount: function() {
        var selectedColumns = this.selectedColumns;

        return selectedColumns ? selectedColumns.length : 0;
    },

    /**
     * Returns the columns selected.
     * @return {Ext.grid.column.Column[]} The columns selected.
     */
    getColumns: function() {
        return this.selectedColumns || [];
    },

    //-------------------------------------------------------------------------

    privates: {
        /**
         * Adds the passed Column to the selection.
         * @param {Ext.grid.column.Column} column
         * @private
         */
        add: function(column) {
            //<debug>
            if (!column.isColumn) {
                Ext.raise('Column selection must be passed a grid Column header object');
            }
            //</debug>

            Ext.Array.include((this.selectedColumns || (this.selectedColumns = [])), column);
            this.refreshColumns(column);
        },

        /**
         * @private
         */
        clear: function() {
            var me = this,
                prevSelection = me.selectedColumns;

            if (prevSelection && prevSelection.length) {
                me.selectedColumns = [];
                me.refreshColumns.apply(me, prevSelection);
            }

            me.setRangeStart(null);
        },

        setRangeStart: function(startColumn) {
            var me = this,
                prevSelection = me.getColumns();

            me.startColumn = startColumn;

            if (startColumn != null) {
                me.selectedColumns = [startColumn];
                prevSelection.push(startColumn);
                me.refreshColumns.apply(me, prevSelection);
            }
        },

        setRangeEnd: function(endColumn) {
            var me = this,
                prevSelection = me.getColumns(),
                colManager = this.view.ownerGrid.getVisibleColumnManager(),
                columns = colManager.getColumns(),
                start = colManager.indexOf(me.startColumn),
                end = colManager.indexOf(endColumn),
                i;

            // Allow looping through columns
            if (end < start) {
                i = start;
                start = end;
                end = i;
            }

            me.selectedColumns = [];

            for (i = start; i <= end; i++) {
                me.selectedColumns.push(columns[i]);
                prevSelection.push(columns[i]);
            }

            me.refreshColumns.apply(me, prevSelection);
        },

        /**
         * @return {Boolean}
         * @private
         */
        isAllSelected: function() {
            var selectedColumns = this.selectedColumns;

            // All selected means all columns, across both views if we are in a locking assembly.
            // eslint-disable-next-line max-len
            return selectedColumns && selectedColumns.length === this.view.ownerGrid.getVisibleColumnManager().getColumns().length;
        },

        /**
         * @private
         */
        refreshColumns: function(column) {
            var me = this,
                view = me.view,
                rows = view.all,
                rowIdx,
                columns = arguments,
                len = columns.length,
                colIdx,
                cellContext = new Ext.grid.CellContext(view),
                selected = [];

            if (view.rendered) {
                for (colIdx = 0; colIdx < len; colIdx++) {
                    selected[colIdx] = me.contains(columns[colIdx]);
                }

                for (rowIdx = rows.startIndex; rowIdx <= rows.endIndex; rowIdx++) {
                    cellContext.setRow(rowIdx);

                    for (colIdx = 0; colIdx < len; colIdx++) {
                        // Note colIdx is not the column's visible index.
                        // setColumn must be passed the column object
                        cellContext.setColumn(columns[colIdx]);

                        if (selected[colIdx]) {
                            view.onCellSelect(cellContext);
                        }
                        else {
                            view.onCellDeselect(cellContext);
                        }
                    }
                }
            }
        },

        /**
         * Removes the passed Column from the selection.
         * @param {Ext.grid.column.Column} column
         * @private
         */
        remove: function(column) {
            //<debug>
            if (!column.isColumn) {
                Ext.raise('Column selection must be passed a grid Column header object');
            }
            //</debug>

            if (this.selectedColumns) {
                Ext.Array.remove(this.selectedColumns, column);

                // Might be being called because of column removal/hiding.
                // In which case the view will have selected cells removed, so no refresh needed.
                if (column.getView() && column.isVisible()) {
                    this.refreshColumns(column);
                }
            }
        },

        /**
         * @private
         */
        selectAll: function() {
            var me = this;

            me.clear();
            me.selectedColumns = me.view.getSelectionModel().lastContiguousColumnRange =
                me.view.getVisibleColumnManager().getColumns();
            me.refreshColumns.apply(me, me.selectedColumns);
        },

        extendRange: function(extensionVector) {
            var me = this,
                columns = me.view.getVisibleColumnManager().getColumns(),
                i;

            for (i = extensionVector.start.colIdx; i <= extensionVector.end.colIdx; i++) {
                me.add(columns[i]);
            }
        },

        reduceRange: function(extensionVector) {
            var me = this,
                columns = me.view.getVisibleColumnManager().getColumns(),
                startIdx = extensionVector.start.colIdx,
                endIdx = extensionVector.end.colIdx,
                reduceTo = Math.abs(startIdx - endIdx) + 1,
                diff = me.selectedColumns.length - reduceTo,
                i;

            for (i = diff; i > 0; i--) {
                me.remove(columns[endIdx + i]);
            }
        },

        onSelectionFinish: function() {
            var me = this,
                range = me.getContiguousSelection();

            if (range) {
                me.view.getSelectionModel().onSelectionFinish(
                    me,
                    new Ext.grid.CellContext(me.view).setPosition(0, range[0]),
                    new Ext.grid.CellContext(me.view).setPosition(
                        me.view.dataSource.getCount() - 1, range[1]
                    )
                );
            }
            else {
                me.view.getSelectionModel().onSelectionFinish(me);
            }
        },

        /**
         * @return {Array} `[startColumn, endColumn]` if the selection represents
         * a visually contiguous set of columns.
         * The SelectionReplicator is only enabled if there is a contiguous block.
         * @private
         */
        getContiguousSelection: function() {
            var selection = Ext.Array.sort(this.getColumns(), function(c1, c2) {
                    // Use index *in ownerGrid* so that a locking assembly
                    // can order columns correctly
                    return c1.getView().ownerGrid.getVisibleColumnManager().indexOf(c1) -
                           c2.getView().ownerGrid.getVisibleColumnManager().indexOf(c2);
                }),
                len = selection.length,
                i;

            if (len) {
                for (i = 1; i < len; i++) {
                    if (selection[i].getVisibleIndex() !== selection[i - 1].getVisibleIndex() + 1) {
                        return false;
                    }
                }

                return [selection[0], selection[len - 1]];
            }
        }
    }
});
