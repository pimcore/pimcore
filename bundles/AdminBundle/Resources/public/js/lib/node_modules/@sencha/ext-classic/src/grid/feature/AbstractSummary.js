/**
 * A small abstract class that contains the shared behaviour for any summary
 * calculations to be used in the grid.
 */
Ext.define('Ext.grid.feature.AbstractSummary', {

    extend: 'Ext.grid.feature.Feature',

    alias: 'feature.abstractsummary',

    summaryRowCls: Ext.baseCSSPrefix + 'grid-row-summary',
    summaryRowSelector: '.' + Ext.baseCSSPrefix + 'grid-row-summary',

    readDataOptions: {
        recordCreator: Ext.identityFn
    },

    // High priority rowTpl interceptor which sees summary rows early, and renders them correctly
    // and then aborts the row rendering chain. This will only see action when summary rows
    // are being updated and Table.onUpdate->Table.bufferRender renders the individual
    // updated sumary row.
    summaryRowTpl: {
        fn: function(out, values, parent) {
            // If a summary record comes through the rendering pipeline,
            // render it simply instead of proceeding through the tplchain
            if (values.record.isSummary) {
                this.summaryFeature.outputSummaryRecord(values.record, values, out, parent);
            }
            else {
                this.nextTpl.applyOut(values, out, parent);
            }
        },
        priority: 1000
    },

    /**
     * @cfg {Boolean}
     * True to show the summary row.
     */
    showSummaryRow: true,

    // Listen for store updates. Eg, from an Editor.
    init: function() {
        var me = this;

        me.view.summaryFeature = me;
        me.rowTpl = me.view.self.prototype.rowTpl;

        // Add a high priority interceptor which renders summary records simply
        // This will only see action ona bufferedRender situation where summary records are updated.
        me.view.addRowTpl(me.summaryRowTpl).summaryFeature = me;

        // Define on the instance to store info needed by summary renderers.
        me.summaryData = {};
        me.groupInfo = {};

        // Cell widths in the summary table are set directly into the cells.
        // There's no <colgroup><col>
        // Some browsers use content box and some use border box when applying the style width
        // of a TD
        if (!me.summaryTableCls) {
            me.summaryTableCls = Ext.baseCSSPrefix + 'grid-item';
        }

        // We have been configured with another class. Revert to building the selector
        if (me.hasOwnProperty('summaryRowCls')) {
            me.summaryRowSelector = '.' + me.summaryRowCls;
        }
    },

    bindStore: function(grid, store) {
        var me = this;

        Ext.destroy(me.readerListeners);

        if (me.remoteRoot) {
            me.readerListeners = store.getProxy().getReader().on({
                scope: me,
                destroyable: true,
                rawdata: me.onReaderRawData
            });
        }
    },

    onReaderRawData: function(data) {
        // Invalidate potentially existing summaryRows to force recalculation
        this.summaryRows = null;
        this.readerRawData = data;
    },

    /**
     * Toggle whether or not to show the summary row.
     * @param {Boolean} visible True to show the summary row
     * @param fromLockingPartner (private)
     */
    toggleSummaryRow: function(visible, fromLockingPartner) {
        var me = this,
            prev = me.showSummaryRow,
            doRefresh;

        visible = visible != null ? !!visible : !me.showSummaryRow;
        me.showSummaryRow = visible;

        if (visible && visible !== prev) {
            // If being shown, something may have changed while not visible, so
            // force the summary records to recalculate
            me.updateSummaryRow = true;
        }

        // If there is another side to be toggled, then toggle it (as long as we are not
        // already being commanded from that other side);
        // Then refresh the whole arrangement.
        if (me.lockingPartner) {
            if (!fromLockingPartner) {
                me.lockingPartner.toggleSummaryRow(visible, true);
                doRefresh = true;
            }
        }
        else {
            doRefresh = true;
        }

        if (doRefresh) {
            me.grid.ownerGrid.getView().refreshView();
        }
    },

    createRenderer: function(column, record) {
        var me = this,
            ownerGroup = record.ownerGroup,
            summaryData = ownerGroup ? me.summaryData[ownerGroup] : me.summaryData,
            // Use the column.getItemId() for columns without a dataIndex.
            // The populateRecord method does the same.
            dataIndex = column.dataIndex || column.getItemId();

        return function(value, metaData) {
            return column.summaryRenderer
                ? column.summaryRenderer(record.data[dataIndex], summaryData, dataIndex, metaData)
                // For no summaryRenderer, return the field value in the Feature record.
                : record.data[dataIndex];
        };
    },

    outputSummaryRecord: function(summaryRecord, contextValues, out) {
        var view = contextValues.view,
            savedRowValues = view.rowValues,
            columns = contextValues.columns || view.headerCt.getVisibleGridColumns(),
            colCount = columns.length,
            i, column,
            // Set up a row rendering values object so that we can call the rowTpl directly
            // to inject the markup of a grid row into the output stream.
            values = {
                view: view,
                record: summaryRecord,
                rowStyle: '',
                rowClasses: [ this.summaryRowCls, this.summaryItemCls ],
                itemClasses: [],
                recordIndex: -1,
                rowId: view.getRowId(summaryRecord),
                columns: columns
            };

        // Because we are using the regular row rendering pathway, temporarily swap out
        // the renderer for the summaryRenderer
        for (i = 0; i < colCount; i++) {
            column = columns[i];
            column.savedRenderer = column.renderer;

            if (column.summaryType || column.summaryRenderer) {
                column.renderer = this.createRenderer(column, summaryRecord);
            }
            else {
                column.renderer = Ext.emptyFn;
            }
        }

        // Use the base template to render a summary row
        view.rowValues = values;
        view.self.prototype.rowTpl.applyOut(values, out, parent);
        view.rowValues = savedRowValues;

        // Restore regular column renderers
        for (i = 0; i < colCount; i++) {
            column = columns[i];
            column.renderer = column.savedRenderer;
            column.savedRenderer = null;
        }
    },

    /**
     * Get the summary data for a field.
     * @private
     * @param {Ext.data.Store} store The store to get the data from
     * @param {String/Function} type The type of aggregation. If a function is specified it will
     * be passed to the stores aggregate function.
     * @param {String} field The field to aggregate on
     * @param {Ext.util.Group} group The group from which to calculate the value
     * @return {Number/String/Object} See the return type for the store functions.
     * if the group parameter is `true` An object is returned with a property named for each group
     * who's value is the summary value.
     */
    getSummary: function(store, type, field, group) {
        var isGrouped = !!group,
            item = isGrouped ? group : store;

        if (type) {
            if (Ext.isFunction(type)) {
                if (isGrouped) {
                    return item.aggregate(field, type);
                }
                else {
                    return item.aggregate(type, null, false, [field]);
                }
            }

            switch (type) {
                case 'count':
                    return item.count();

                case 'min':
                    return item.min(field);

                case 'max':
                    return item.max(field);

                case 'sum':
                    return item.sum(field);

                case 'average':
                    return item.average(field);

                default:
                    return '';

            }
        }
    },

    getRawData: function() {
        var data = this.readerRawData;

        if (data) {
            return data;
        }

        // Synchronous Proxies such as Memory proxy will set keepRawData to true
        // on their Reader instances, and may have been loaded before we were bound
        // to the store. Or the Reader may have been configured with keepRawData: true
        // manually.
        // In these cases, the Reader should have rawData on the instance.
        return this.view.getStore().getProxy().getReader().rawData;
    },

    generateSummaryData: function(groupField) {
        var me = this,
            summaryRows = me.summaryRows,
            convertedSummaryRow = {},
            remoteData = {},
            storeReader, reader, rawData, i, len, rows, row;

        // Summary rows may have been cached by previous run
        if (!summaryRows) {
            rawData = me.getRawData();

            if (!rawData) {
                return;
            }

            // Construct a new Reader instance of the same type to avoid
            // munging the one in the Store
            storeReader = me.view.store.getProxy().getReader();
            reader = Ext.create('reader.' + storeReader.type, storeReader.getConfig());

            // reset reader root and rebuild extractors to extract summaries data
            reader.setRootProperty(me.remoteRoot);

            // At this point summaryRows is still raw data, e.g. XML node
            summaryRows = reader.getRoot(rawData);

            if (summaryRows) {
                rows = [];

                if (!Ext.isArray(summaryRows)) {
                    summaryRows = [summaryRows];
                }

                len = summaryRows.length;

                for (i = 0; i < len; ++i) {
                    // Convert a raw data row into a Record's hash object using the Reader.
                    row = reader.extractRecordData(summaryRows[i], me.readDataOptions);
                    rows.push(row);
                }

                me.summaryRows = summaryRows = rows;
            }

            // By the next time the configuration may change
            reader.destroy();

            // We also no longer need the whole raw dataset
            me.readerRawData = null;
        }

        if (summaryRows) {
            for (i = 0, len = summaryRows.length; i < len; i++) {
                convertedSummaryRow = summaryRows[i];

                if (groupField) {
                    remoteData[convertedSummaryRow[groupField]] = convertedSummaryRow;
                }
            }
        }

        return groupField ? remoteData : convertedSummaryRow;
    },

    setSummaryData: function(record, colId, summaryValue, groupName) {
        var summaryData = this.summaryData;

        if (groupName) {
            if (!summaryData[groupName]) {
                summaryData[groupName] = {};
            }

            summaryData[groupName][colId] = summaryValue;
        }
        else {
            summaryData[colId] = summaryValue;
        }
    },

    destroy: function() {
        Ext.destroy(this.readerListeners);
        this.readerRawData = this.summaryRows = null;

        this.callParent();
    }
});
