/**
 * Instances of this class encapsulate a position in a grid's row/column coordinate system.
 *
 * Cells are addressed using the owning {@link #record} and {@link #column} for robustness. 
 * the column may be moved, the store may be sorted, and the CellContext will still reference
 * the same *logical* cell. Be aware that due to buffered rendering the *physical* cell may not
 * exist.
 *
 * The {@link #setPosition} method however allows a numeric row and column to be passed in. These
 * are immediately converted.
 *
 * Be careful not to make `CellContext` objects *too* persistent. If the owning record is removed,
 * or the owning column is removed, the reference will be stale.
 *
 * Freshly created context objects, such as those exposed by events from the
 * {@link Ext.grid.selection.SpreadsheetModel spreadsheet selection model} are safe to use
 * until your application mutates the store, or changes the column set.
 */
Ext.define('Ext.grid.CellContext', {

    /**
     * @property {Boolean} isCellContext
     * @readonly
     * `true` in this class to identify an object as an instantiated CellContext,
     * or subclass thereof.
     */
    isCellContext: true,

    /**
     * @readonly
     * @property {Ext.grid.column.Column} column
     * The grid column which owns the referenced cell.
     */

    /**
     * @readonly
     * @property {Ext.data.Model} record
     * The store record which maps to the referenced cell.
     */

    /**
     * @readonly
     * @property {Number} rowIdx
     * The row number in the store which owns the referenced cell.
     *
     * *Be aware that after the initial call to {@link #setPosition}, this value may become stale
     * due to subsequent store mutation.*
     */

    /**
     * @readonly
     * @property {Number} colIdx
     * The column index in the owning View's leaf column set of the referenced cell.
     *
     * *Be aware that after the initial call to {@link #setPosition}, this value may become stale
     * due to subsequent column mutation.*
     */

    generation: 0,

    /**
      * Creates a new CellContext which references a {@link Ext.view.Table GridView}
      * @param {Ext.view.Table} view The {@link Ext.view.Table GridView} for which the cell context
      * is needed.
      *
      * To complete creation of a valid context, use the {@link #setPosition} method.
      */
    constructor: function(view) {
        this.view = view;
    },

    /**
     * Binds this cell context to a logical cell defined by the {@link #record} and {@link #column}.
     *
     * @param {Number/Ext.data.Model} row The row index or record which owns the required cell.
     * @param {Number/Ext.grid.column.Column} col The column index (In the owning View's leaf
     * column set), or the owning {@link Ext.grid.column.Column column}.
     *
     * A one argument form may be used in the form of an array:
     *
     *     [column, row]
     *
     * Or another CellContext may be passed.
     *
     * @return {Ext.grid.CellContext} this CellContext object.
     */
    setPosition: function(row, col) {
        var me = this;

        // We were passed {row: 1, column: 2, view: myView} or [2, 1]
        if (arguments.length === 1) {
            // A [column, row] array passed
            if (row.length) {
                col = row[0];
                row = row[1];
            }
            else if (row.isCellContext) {
                return me.setAll(row.view, row.rowIdx, row.colIdx, row.record, row.column);
            }
            // An object containing {row: r, column: c}
            else {
                if (row.view) {
                    me.view = row.view;
                }

                col = row.column;
                row = row.row;
            }
        }

        me.setRow(row);
        me.setColumn(col);

        return me;
    },

    setAll: function(view, recordIndex, columnIndex, record, columnHeader) {
        var me = this;

        me.view = view;
        me.rowIdx = recordIndex;
        me.colIdx = columnIndex;
        me.record = record;
        me.column = columnHeader;
        me.generation++;

        return me;
    },

    setRow: function(row) {
        var me = this,
            dataSource = me.view.dataSource,
            oldRecord = me.record,
            count;

        // eslint-disable-next-line eqeqeq
        if (row != undefined) {
            // Row index passed, < 0 meaning count from the tail (-1 is the last, etc)
            if (typeof row === 'number') {
                count = dataSource.getCount();
                row = row < 0 ? Math.max(count + row, 0) : Math.max(Math.min(row, count - 1), 0);

                me.rowIdx = row;
                me.record = dataSource.getAt(row);
            }
            // row is a Record
            else if (row.isModel) {
                me.record = row;
                me.rowIdx = dataSource.indexOf(row);
            }
            // row is a grid row, or Element wrapping row
            else if (row.tagName || row.isElement) {
                me.record = me.view.getRecord(row);

                // If it's a placeholder record for a collapsed group, index it correctly
                // eslint-disable-next-line max-len
                me.rowIdx = me.record ? (me.record.isCollapsedPlaceholder ? dataSource.indexOfPlaceholder(me.record) : dataSource.indexOf(me.record)) : -1;
            }
        }

        if (me.record !== oldRecord) {
            me.generation++;
        }

        return me;
    },

    setColumn: function(col) {
        var me = this,
            colMgr = me.view.getVisibleColumnManager(),
            oldColumn = me.column;

        // Maintainer:
        // We MUST NOT update the context view with the column's view because this context
        // may be for an Ext.locking.View which spans two grid views, and a column references
        // its local grid view.
        // eslint-disable-next-line eqeqeq
        if (col != undefined) {
            if (typeof col === 'number') {
                me.colIdx = col;
                me.column = colMgr.getHeaderAtIndex(col);
            }
            else if (col.isHeader) {
                me.column = col;
                // Must use the Manager's indexOf because view may be a locking view
                // And Column#getVisibleIndex returns the index of the column within its own header.
                me.colIdx = colMgr.indexOf(col);
            }
        }

        if (me.column !== oldColumn) {
            me.generation++;
        }

        return me;
    },

    setView: function(view) {
        this.view = view;
        this.refresh();
    },

    /**
     * Returns the cell object referenced *at the time of calling*. Note that grid DOM is transient,
     * and  the cell referenced may be removed from the DOM due to paging or buffered rendering
     * or column or record removal.
     *
     * @param {Boolean} returnDom Pass `true` to return a DOM object instead of an
     * {@link Ext.dom.Element Element}.
     * @return {HTMLElement/Ext.dom.Element} The cell referenced by this context.
     */
    getCell: function(returnDom) {
        return this.view.getCellByPosition(this, returnDom);
    },

    /**
     * Returns the row object referenced *at the time of calling*. Note that grid DOM is transient,
     * and  the row referenced may be removed from the DOM due to paging or buffered rendering
     * or column or record removal.
     *
     * @param {Boolean} returnDom Pass `true` to return a DOM object instead of an
     * {@link Ext.dom.Element Element}.
     * @return {HTMLElement/Ext.dom.Element} The grid row referenced by this context.
     */
    getRow: function(returnDom) {
        var result = this.view.getRow(this.record);

        return returnDom ? result : Ext.get(result);
    },

    /**
     * Returns the view node object (the encapsulating element of a data row) referenced
     * *at the time of calling*. Note that grid DOM is transient, and the node referenced
     * may be removed from the DOM due to paging or buffered rendering or column or record removal.
     *
     * @param {Boolean} returnDom Pass `true` to return a DOM object instead of an
     * {@link Ext.dom.Element Element}.
     * @return {HTMLElement/Ext.dom.Element} The grid item referenced by this context.
     */
    getNode: function(returnDom) {
        var result = this.view.getNode(this.record);

        return returnDom ? result : Ext.get(result);
    },

    /**
     * Compares this CellContext object to another CellContext to see if they refer to the same
     * cell.
     * @param {Ext.grid.CellContext} other The CellContext to compare.
     * @return {Boolean} `true` if the other cell context references the same cell as this.
     */
    isEqual: function(other) {
        return other && other.isCellContext && other.record === this.record &&
               other.column === this.column;
    },

    /**
     * Creates a clone of this CellContext.
     *
     * The clone may be retargeted without affecting the reference of this context.
     * @return {Ext.grid.CellContext} A copy of this context, referencing the same cell.
     */
    clone: function() {
        var me = this,
            result = new me.self(me.view);

        result.rowIdx = me.rowIdx;
        result.colIdx = me.colIdx;
        result.record = me.record;
        result.column = me.column;

        return result;
    },

    privates: {
        isFirstColumn: function() {
            var cell = this.getCell(true);

            if (cell) {
                return !cell.previousSibling;
            }
        },

        isLastColumn: function() {
            var cell = this.getCell(true);

            if (cell) {
                return !cell.nextSibling;
            }
        },

        isLastRenderedRow: function() {
            return this.view.all.endIndex === this.rowIdx;
        },

        getLastColumnIndex: function() {
            var row = this.getRow(true);

            if (row) {
                return row.lastChild.cellIndex;
            }

            return -1;
        },

        refresh: function() {
            var me = this,
                newRowIdx = me.view.dataSource.indexOf(me.record),
                newColIdx = me.view.getVisibleColumnManager().indexOf(me.column);

            me.setRow(newRowIdx === -1 ? me.rowIdx : me.record);
            me.setColumn(newColIdx === -1 ? me.colIdx : me.column);
        },

        /**
         * @private
         * Navigates left or right within the current row.
         * @param {Number} direction `-1` to go towards the row start or `1` to go towards row end
         */
        navigate: function(direction) {
            var me = this,
                columns = me.view.getVisibleColumnManager().getColumns();

            switch (direction) {
                case -1:
                    do {
                        // If we iterate off the start, wrap back to the end.
                        if (!me.colIdx) {
                            me.colIdx = columns.length - 1;
                        }
                        else {
                            me.colIdx--;
                        }

                        me.setColumn(me.colIdx);
                    } while (!me.getCell(true));

                    break;

                case 1:
                    do {
                        // If we iterate off the end, wrap back to the start.
                        if (me.colIdx >= columns.length) {
                            me.colIdx = 0;
                        }
                        else {
                            me.colIdx++;
                        }

                        me.setColumn(me.colIdx);
                    } while (!me.getCell(true));

                    break;
            }
        }
    },

    statics: {
        compare: function(c1, c2) {
            return c1.rowIdx - c2.rowIdx || c1.colIdx - c2.colIdx;
        }
    }
});
