/**
 * This {@link Ext.grid.Panel grid} plugin adds clipboard support to a grid.
 *
 * *Note that the grid must use the
 * {@link Ext.grid.selection.SpreadsheetModel spreadsheet selection model}
 * to utilize this plugin.*
 *
 * This class supports the following `{@link Ext.plugin.AbstractClipboard#formats formats}`
 * for grid data:
 *
 *  * `cell` - Complete field data that can be matched to other grids using the same
 *    {@link Ext.data.Model model} regardless of column order.
 *  * `text` - Cell content stripped of HTML tags.
 *  * `html` - Complete cell content, including any rendered HTML tags.
 *  * `raw` - Underlying field values based on `dataIndex`.
 *
 * The `cell` format is not valid for the `{@link Ext.plugin.AbstractClipboard#system system}`
 * clipboard format.
 */
Ext.define('Ext.grid.plugin.Clipboard', {
    extend: 'Ext.plugin.AbstractClipboard',
    alias: 'plugin.clipboard',

    requires: [
        'Ext.util.Format',
        'Ext.util.TSV'
    ],

    formats: {
        cell: {
            get: 'getCells'
        },
        html: {
            get: 'getCellData'
        },
        raw: {
            get: 'getCellData',
            put: 'putCellData'
        }
    },

    gridListeners: {
        render: 'onCmpReady'
    },

    getCellData: function(format, erase) {
        var cmp = this.getCmp(),
            selection = cmp.getSelectionModel().getSelected(),
            ret = [],
            isRaw = format === 'raw',
            isText = format === 'text',
            viewNode,
            cell, data, dataIndex, lastRecord, column, record, row, view;

        if (selection) {
            selection.eachCell(function(cellContext) {
                column = cellContext.column;
                view = cellContext.column.getView();
                record = cellContext.record;

                // Do not copy the check column or row numberer column
                if (column.ignoreExport) {
                    return;
                }

                if (lastRecord !== record) {
                    lastRecord = record;
                    ret.push(row = []);
                }

                dataIndex = column.dataIndex;

                if (isRaw) {
                    data = record.data[dataIndex];
                }
                else {
                    // Try to access the view node.
                    viewNode = view.all.item(cellContext.rowIdx);

                    // If we could not, it's because it's outside of the rendered block -
                    // recreate it.
                    if (!viewNode) {
                        viewNode = Ext.fly(view.createRowElement(record, cellContext.rowIdx));
                    }

                    cell = viewNode.dom.querySelector(column.getCellInnerSelector());
                    data = cell.innerHTML;

                    if (isText) {
                        data = Ext.util.Format.stripTags(data);
                    }
                }

                row.push(data);

                if (erase && dataIndex) {
                    record.set(dataIndex, null);
                }
            });
        }

        // See decode() comment below
        return Ext.util.TSV.encode(ret, undefined, null);
    },

    getCells: function(format, erase) {
        var cmp = this.getCmp(),
            selection = cmp.getSelectionModel().getSelected(),
            ret = [],
            dataIndex, lastRecord, record, row;

        if (selection) {
            selection.eachCell(function(cellContext) {
                record = cellContext.record;

                if (lastRecord !== record) {
                    lastRecord = record;
                    ret.push(row = {
                        model: record.self,
                        fields: []
                    });
                }

                dataIndex = cellContext.column.dataIndex;

                row.fields.push({
                    name: dataIndex,
                    value: record.data[dataIndex]
                });

                if (erase && dataIndex) {
                    record.set(dataIndex, null);
                }
            });
        }

        return ret;
    },

    getTextData: function(format, erase) {
        return this.getCellData(format, erase);
    },

    putCellData: function(data, format) {
        // We pass null as field quote here to override default TSV decoding behavior
        // that will try to unquote fields and break if double quote character is
        // encountered in the data. TSV format does not support any kind of field quoting
        // but Ext.util.TSV mistakenly assumed otherwise pre-6.5.3
        var values = Ext.util.TSV.decode(data, undefined, null),
            row,
            recCount = values.length,
            colCount = recCount ? values[0].length : 0,
            sourceRowIdx, sourceColIdx,
            view = this.getCmp().getView(),
            maxRowIdx = view.dataSource.getCount() - 1,
            maxColIdx = view.getVisibleColumnManager().getColumns().length - 1,
            selModel = view.getSelectionModel(),
            selected = selModel.getSelected(),
            navModel = view.getNavigationModel(),
            destination = selected.startCell || navModel.getPosition(),
            dataIndex, destinationStartColumn,
            dataObject = {};

        // If the view is not focused, use the first cell of the selection as the destination.
        if (!destination && selected) {
            selected.eachCell(function(c) {
                destination = c;

                return false;
            });
        }

        if (destination) {
            // Create a new Context based upon the outermost View.
            // NavigationModel works on local views.
            // TODO: remove this step when NavModel is fixed to use outermost view in locked grid.
            // At that point, we can use navModel.getPosition()
            destination = new Ext.grid.CellContext(view).setPosition(
                destination.record, destination.column
            );
        }
        else {
            destination = new Ext.grid.CellContext(view).setPosition(0, 0);
        }

        destinationStartColumn = destination.colIdx;

        for (sourceRowIdx = 0; sourceRowIdx < recCount; sourceRowIdx++) {
            row = values[sourceRowIdx];

            // Collect new values in dataObject
            for (sourceColIdx = 0; sourceColIdx < colCount; sourceColIdx++) {
                dataIndex = destination.column.dataIndex;

                if (dataIndex) {
                    switch (format) {
                        // Raw field values
                        case 'raw':
                            dataObject[dataIndex] = row[sourceColIdx];
                            break;

                        // Textual data with HTML tags stripped    
                        case 'text':
                            dataObject[dataIndex] = row[sourceColIdx];
                            break;

                        // innerHTML from the cell inner
                        case 'html':
                            break;
                    }
                }

                // If we are at the end of the destination row, break the column loop.
                if (destination.colIdx === maxColIdx) {
                    break;
                }

                destination.setColumn(destination.colIdx + 1);
            }

            // Update the record in one go.
            destination.record.set(dataObject);

            // If we are at the end of the destination store, break the row loop.
            if (destination.rowIdx === maxRowIdx) {
                break;
            }

            // Jump to next row in destination
            destination.setPosition(destination.rowIdx + 1, destinationStartColumn);
        }
    },

    putTextData: function(data, format) {
        this.putCellData(data, format);
    },

    getTarget: function(comp) {
        return comp.body;
    },

    privates: {
        validateAction: function(event) {
            var view = this.getCmp().getView();

            if (view.actionableMode) {
                return false;
            }
        }
    }
});
