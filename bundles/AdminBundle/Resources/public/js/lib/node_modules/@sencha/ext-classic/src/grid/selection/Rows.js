/**
 * A class which encapsulates a range of rows defining a selection in a grid.
 * @since 5.1.0
 */
Ext.define('Ext.grid.selection.Rows', {
    extend: 'Ext.grid.selection.Selection',

    requires: [
        'Ext.util.Collection'
    ],

    type: 'rows',

    /**
     * @property {Boolean} isRows
     * This property indicates the this selection represents selected rows.
     * @readonly
     */
    isRows: true,

    //-------------------------------------------------------------------------
    // Base Selection API

    clone: function() {
        var me = this,
            result = new me.self(me.view);

        // Clone our record collection
        if (me.selectedRecords) {
            result.selectedRecords = me.selectedRecords.clone();
        }

        // Clone the current drag range
        if (me.rangeStart) {
            result.setRangeStart(me.rangeStart);
            result.setRangeEnd(me.rangeEnd);
        }

        return result;
    },

    //-------------------------------------------------------------------------
    // Methods unique to this type of Selection

    addOne: function(record) {
        var me = this,
            selection;

        //<debug>
        if (!(record.isModel)) {
            Ext.raise('Row selection must be passed a record');
        }
        //</debug>

        selection = me.selectedRecords || (me.selectedRecords = me.createRecordCollection());

        if (!selection.byInternalId.get(record.internalId)) {
            selection.add(record);
            me.view.onRowSelect(record);
        }
    },

    add: function(record) {
        var me = this,
            i, len;

        if (record.isModel) {
            me.addOne(record);
        }
        else if (Ext.isArray(record)) {
            for (i = 0, len = record.length; i < len; i++) {
                me.addOne(record[i]);
            }
        }
        //<debug>
        else {
            Ext.raise('add must be called with a record or array of records');
        }
        //</debug>
    },

    removeOne: function(record) {
        var me = this;

        //<debug>
        if (!(record.isModel)) {
            Ext.raise('Row selection must be passed a record');
        }
        //</debug>

        if (me.selectedRecords && me.selectedRecords.byInternalId.get(record.internalId)) {
            me.selectedRecords.remove(record);
            me.view.onRowDeselect(record);
            // Flag when selectAll called.
            // While this is set, a call to contains will add the record to the collection
            // and return true
            me.allSelected = false;

            return true;
        }

        return false;
    },

    remove: function(record) {
        var me = this,
            ret = true,
            i, len;

        if (record.isModel) {
            return me.removeOne(record);
        }
        else if (Ext.isArray(record)) {
            for (i = 0, len = record.length; i < len; i++) {
                ret &= me.removeOne(record[i]);
            }
        }
        //<debug>
        else {
            Ext.raise('remove must be called with a record or array of records');
        }
        //</debug>

        return ret;
    },

    /**
     * Returns `true` if the passed {@link Ext.data.Model record} is selected.
     * @param {Ext.data.Model} record The record to test.
     * @return {Boolean} `true` if the passed {@link Ext.data.Model record} is selected.
     */
    contains: function(record) {
        if (!record || !record.isModel) {
            return false;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            result = false,
            selectedRecords = me.selectedRecords;

        // Flag set when selectAll is called in the selModel.
        // This allows buffered stores to treat all *rendered* records
        // as selected, so that the selection model will always encompass
        // What the user *sees* as selected
        if (me.allSelected) {
            me.add(record);

            return true;
        }

        // First check if the record is in our collection
        if (selectedRecords) {
            result = !!selectedRecords.byInternalId.get(record.internalId);
        }

        return result;
    },

    /**
     * Returns the number of records selected
     * @return {Number} The number of records selected.
     */
    getCount: function() {
        var selectedRecords = this.selectedRecords;

        return (selectedRecords && selectedRecords.length) || 0;
    },

    /**
     * Returns the records selected.
     * @return {Ext.data.Model[]} The records selected.
     */
    getRecords: function() {
        var selectedRecords = this.selectedRecords;

        return selectedRecords ? selectedRecords.getRange() : [];
    },

    selectAll: function() {
        var me = this,
            ds = me.view.dataSource,
            rangeSize = ds.isBufferedStore ? ds.getData().getCount() : ds.getCount();

        me.clear();
        me.setRangeStart(0);
        me.setRangeEnd(rangeSize - 1);

        // Adds the records to the collection
        me.addRange();

        // While this is set, a call to contains will add the record to the collection
        // and return true.
        // This is so that buffer rendered stores can utulize row based selectAll
        me.allSelected = true;
    },

    /**
     * @return {Number} The row index of the first row in the range or zero if no range.
     */
    getFirstRowIndex: function() {
        return this.getCount() ? this.view.dataSource.indexOf(this.selectedRecords.first()) : 0;
    },

    /**
     * @return {Number} The row index of the last row in the range or -1 if no range.
     */
    getLastRowIndex: function() {
        return this.getCount() ? this.view.dataSource.indexOf(this.selectedRecords.last()) : -1;
    },

    eachRow: function(fn, scope) {
        var selectedRecords = this.selectedRecords;

        if (selectedRecords) {
            selectedRecords.each(fn, scope || this);
        }
    },

    eachColumn: function(fn, scope) {
        var columns = this.view.getVisibleColumnManager().getColumns(),
            len = columns.length,
            i;

        // If we have any records selected, then all visible columns are selected.
        if (this.selectedRecords) {
            for (i = 0; i < len; i++) {
                if (fn.call(this || scope, columns[i], i) === false) {
                    return;
                }
            }
        }
    },

    eachCell: function(fn, scope) {
        var me = this,
            selection = me.selectedRecords,
            view = me.view,
            columns = view.ownerGrid.getVisibleColumnManager().getColumns(),
            abort = false,
            colCount, i, j, context, range, recCount;

        if (columns) {
            colCount = columns.length;
            context = new Ext.grid.CellContext(view);

            // Use Collection#each instead of copying the entire dataset into an array
            // and iterating that.
            if (selection) {
                selection.each(function(record) {
                    context.setRow(record);

                    for (i = 0; i < colCount; i++) {
                        context.setColumn(columns[i]);

                        // eslint-disable-next-line max-len
                        if (fn.call(scope || me, context, context.colIdx, context.rowIdx) === false) {
                            abort = true;

                            return false;
                        }
                    }
                });
            }

            // If called during a drag select, or SHIFT+arrow select, include the drag range
            if (!abort) {
                range = me.getRange();

                if (range[0] === range[1]) {
                    return;
                }

                me.view.dataSource.getRange(range[0], range[1], {
                    forRender: false,
                    callback: function(records) {
                        recCount = records.length;

                        for (i = 0; !abort && i < recCount; i++) {
                            context.setRow(records[i]);

                            for (j = 0; !abort && j < colCount; j++) {
                                context.setColumn(columns[j]);

                                // eslint-disable-next-line max-len
                                if (fn.call(scope || me, context, context.colIdx, context.rowIdx) === false) {
                                    abort = true;
                                }
                            }
                        }
                    }
                });
            }
        }
    },

    /**
     * This method is called to indicate the start of multiple changes to the selected row set.
     *
     * Internally this method increments a counter that is decremented by `{@link #endUpdate}`. It
     * is important, therefore, that if you call `beginUpdate` directly you match that
     * call with a call to `endUpdate` or you will prevent the collection from updating
     * properly.
     */
    beginUpdate: function() {
        var selectedRecords = this.selectedRecords;

        if (selectedRecords) {
            selectedRecords.beginUpdate();
        }
    },

    /**
     * This method is called after modifications are complete on a selected row set. For details
     * see `{@link #beginUpdate}`.
     */
    endUpdate: function() {
        var selectedRecords = this.selectedRecords;

        if (selectedRecords) {
            selectedRecords.endUpdate();
        }
    },

    destroy: function() {
        this.selectedRecords = Ext.destroy(this.selectedRecords);
        this.callParent();
    },

    //-------------------------------------------------------------------------

    privates: {
        /**
         * @private
         */
        clear: function() {
            var me = this,
                view = me.view;

            // Flag when selectAll called.
            // While this is set, a call to contains will add the record to the collection
            // and return true
            me.allSelected = false;

            if (me.selectedRecords) {
                me.eachRow(function(record) {
                    view.onRowDeselect(record);
                });

                me.selectedRecords.clear();
            }

            me.setRangeStart(null);
        },

        /**
         * @return {Boolean}
         * @private
         */
        isAllSelected: function() {
            // This branch has a flag because it encompasses a possibly buffered store,
            // where the full dataset might not be present, so a flag indicates that all
            // records are selected even as they flow into or out of the buffered page cache.
            return !!this.allSelected;
        },

        /**
         * Used during drag/shift+downarrow range selection on start.
         * @param {Number} start The start row index of the row drag selection.
         * @param {Boolean} suppressEvent True to prevent onRowSelect from being fired.
         * @private
         */
        setRangeStart: function(start, suppressEvent) {
            // Flag when selectAll called.
            // While this is set, a call to contains will add the record to the collection
            // and return true
            this.allSelected = false;
            this.rangeStart = this.rangeEnd = start;

            if (!suppressEvent) {
                this.view.onRowSelect(start);
            }
        },

        /**
         * Used during drag/shift+downarrow range selection on change of row.
         * @param {Number} end The end row index of the row drag selection.
         * @param {Boolean} append True if are appending to an existing selection.
         * @private
         */
        setRangeEnd: function(end, append) {
            var me = this,
                view = me.view,
                store = view.dataSource,
                selected = me.selectedRecords,
                withinRange, range, lastRange, rowIdx, rec;

            // Update the range as requested, then calculate the
            // range in lowest index first form
            me.rangeEnd = end;
            range = me.getRange();
            lastRange = me.lastRange || range;

            rowIdx = Math.min(range[0], lastRange[0]);
            end = Math.max(range[1], lastRange[1]);

            // Loop through the union of last range and current range
            for (; rowIdx <= end; rowIdx++) {
                rec = store.getAt(rowIdx);
                withinRange = (rowIdx < range[0] || rowIdx > range[1]);

                // If we are outside the current range, deselect
                if (!append && withinRange) {
                    // If we are deselecting, also remove from collection
                    if (selected && me.contains(rec)) {
                        selected.remove(rec);
                    }

                    view.onRowDeselect(rowIdx);
                }
                else {
                    if (append && withinRange) {
                        continue;
                    }

                    view.onRowSelect(rowIdx);
                }
            }

            me.lastRange = range;
        },

        extendRange: function(extensionVector) {
            var me = this,
                store = me.view.dataSource,
                i;

            for (i = extensionVector.start.rowIdx; i <= extensionVector.end.rowIdx; i++) {
                me.add(store.getAt(i));
            }
        },

        /**
         * @private
         * Called through {@link Ext.grid.selection.SpreadsheetModel#getLastSelected} by
         * {@link Ext.panel.Table#updateBindSelection} when publishing the `selection` property.
         * It should yield the last record selected.
         */
        getLastSelected: function() {
            var sel = this.selectedRecords;

            return sel && sel.last();
        },

        /**
         * @return {Number[]}
         * @private
         */
        getRange: function() {
            var start = this.rangeStart,
                end = this.rangeEnd;

            if (start == null) {
                return [0, -1];
            }
            else if (start <= end) {
                return [start, end];
            }

            return [end, start];
        },

        /**
         * Returns the size of the mousedown+drag, or SHIFT+arrow selection range.
         * @return {Number}
         * @private
         */
        getRangeSize: function() {
            var range = this.getRange();

            return range[1] - range[0] + 1;
        },

        /**
         * @return {Ext.util.Collection}
         * @private
         */
        createRecordCollection: function() {
            var store = this.view.dataSource,
                result = new Ext.util.Collection({
                    rootProperty: 'data',
                    extraKeys: {
                        byInternalId: {
                            rootProperty: false,
                            property: 'internalId'
                        }
                    },
                    sorters: [
                        function(r1, r2) {
                            return store.indexOf(r1) - store.indexOf(r2);
                        }
                    ]
                });

            return result;
        },

        /**
         * Called at the end of a drag, or shift+downarrow row range select.
         * The record range delineated by the start and end row indices is added to the selected
         * Collection.
         * @private
         */
        addRange: function() {
            var me = this,
                range,
                selection;

            if (me.rangeStart != null) {
                range = me.getRange();

                selection = me.selectedRecords ||
                            (me.selectedRecords = me.createRecordCollection());

                me.view.dataSource.getRange(range[0], range[1], {
                    forRender: false,
                    callback: function(range) {
                        selection.add.apply(selection, range);
                    }
                });
            }
        },

        onSelectionFinish: function() {
            var me = this,
                range = me.getContiguousSelection();

            if (range) {
                me.view.getSelectionModel().onSelectionFinish(
                    me,
                    new Ext.grid.CellContext(me.view).setPosition(range[0], 0),
                    new Ext.grid.CellContext(me.view).setPosition(
                        range[1], me.view.getVisibleColumnManager().getColumns().length - 1
                    )
                );
            }
            else {
                me.view.getSelectionModel().onSelectionFinish(me);
            }
        },

        /**
         * @return {Array} `[startRowIndex, endRowIndex]` if the selection represents
         * a visually contiguous set of rows.
         * The SelectionReplicator is only enabled if there is a contiguous block.
         * @private
         */
        getContiguousSelection: function() {
            var store = this.view.dataSource,
                selection, len, i;

            if (this.selectedRecords) {
                selection = Ext.Array.sort(this.selectedRecords.getRange(), function(r1, r2) {
                    return store.indexOf(r1) - store.indexOf(r2);
                });

                len = selection.length;

                if (len) {
                    for (i = 1; i < len; i++) {
                        if (store.indexOf(selection[i]) !== store.indexOf(selection[i - 1]) + 1) {
                            return false;
                        }
                    }

                    return [store.indexOf(selection[0]), store.indexOf(selection[len - 1])];
                }
            }
        }
    }
});
