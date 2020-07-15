/* eslint-disable max-len */
/**
 * A special type of Grid {@link Ext.grid.column.Column} that provides automatic
 * row numbering.
 *
 * Usage:
 *
 *     columns: [
 *         {xtype: 'rownumberer'},
 *         {text: "Company", flex: 1, sortable: true, dataIndex: 'company'},
 *         {text: "Price", width: 120, sortable: true, renderer: Ext.util.Format.usMoney, dataIndex: 'price'},
 *         {text: "Change", width: 120, sortable: true, dataIndex: 'change'},
 *         {text: "% Change", width: 120, sortable: true, dataIndex: 'pctChange'},
 *         {text: "Last Updated", width: 120, sortable: true, renderer: Ext.util.Format.dateRenderer('m/d/Y'), dataIndex: 'lastChange'}
 *     ]
 *
 */
Ext.define('Ext.grid.column.RowNumberer', {
    /* eslint-enable max-len */
    extend: 'Ext.grid.column.Column',
    alternateClassName: 'Ext.grid.RowNumberer',
    alias: 'widget.rownumberer',

    /**
     * @property {Boolean} isRowNumberer
     * `true` in this class to identify an object as an instantiated RowNumberer,
     * or subclass thereof.
     */
    isRowNumberer: true,

    /**
     * @cfg {String} text
     * Any valid text or HTML fragment to display in the header cell for the row number column.
     */
    text: "&#160;",

    /**
     * @cfg {Number} width
     * The default width in pixels of the row number column.
     */
    width: 30,

    /**
     * @cfg {Boolean} sortable
     * @hide
     */
    sortable: false,

    /**
     * @cfg {Boolean} draggable
     * False to disable drag-drop reordering of this column.
     */
    draggable: false,

    // Flag to Lockable to move instances of this column to the locked side.
    autoLock: true,

    // May not be moved from its preferred locked side when grid is enableLocking:true
    lockable: false,

    /**
     * @cfg align
     * @inheritdoc
     */
    align: 'right',

    /**
     * @cfg producesHTML
     * @inheritdoc
     */
    producesHTML: false,

    /**
     * @cfg ignoreExport
     * @inheritdoc
     */
    ignoreExport: true,

    constructor: function(config) {
        var me = this;

        // Copy the prototype's default width setting into an instance property to provide
        // a default width which will not be overridden by Container.applyDefaults
        // use of Ext.applyIf
        // eslint-disable-next-line no-self-assign
        me.width = me.width;

        me.callParent(arguments);

        // Override any setting from the HeaderContainer's defaults
        me.sortable = false;

        me.scope = me;
    },

    /**
     * @cfg resizable
     * @inheritdoc
     */
    resizable: false,

    /**
     * @cfg hideable
     * @inheritdoc
     */
    hideable: false,

    /**
     * @cfg menuDisabled
     * @inheritdoc
     */
    menuDisabled: true,

    /**
     * @cfg dataIndex
     * @inheritdoc
     */
    dataIndex: '',

    /**
     * @cfg cls
     * @inheritdoc
     */
    cls: Ext.baseCSSPrefix + 'row-numberer',

    /**
     * @cfg tdCls
     * @inheritdoc
     */
    tdCls: Ext.baseCSSPrefix + 'grid-cell-row-numberer ' + Ext.baseCSSPrefix + 'grid-cell-special',
    innerCls: Ext.baseCSSPrefix + 'grid-cell-inner-row-numberer',
    rowspan: undefined,

    onAdded: function() {
        var me = this;

        // Coalesce multiple item mutation events by routing them to a buffered function
        me.renumberRows = Ext.Function.createBuffered(me.renumberRows, 1, me);

        me.callParent(arguments);

        me.storeListener = me.getView().on({
            itemadd: me.renumberRows,
            itemremove: me.renumberRows,
            destroyable: true
        });
    },

    onRemoved: function() {
        var me = this;

        me.callParent(arguments);

        if (me.storeListener) {
            me.storeListener = me.storeListener.destroy();
        }

        if (me.renumberRows.timer) {
            Ext.undefer(me.renumberRows.timer);
        }

        me.renumberRows = null;
        delete me.renumberRows;
    },

    defaultRenderer: function(value, metaData, record, rowIdx, colIdx, dataSource, view) {
        var me = this,
            rowspan = me.rowspan,
            page = dataSource.currentPage,
            result = record ? view.store.indexOf(record) : value - 1;

        if (metaData && rowspan) {
            metaData.tdAttr = 'rowspan="' + rowspan + '"';
        }

        if (page > 1) {
            result += (page - 1) * dataSource.pageSize;
        }

        return result + 1;
    },

    updater: function(cell, value, record, view, dataSource) {
        var cellInner = cell && cell.querySelector(this.getView().innerSelector);

        if (cellInner) {
            cellInner.innerHTML =
                this.defaultRenderer(value, null, record, null, null, dataSource, view);
        }
    },

    renumberRows: function() {
        if (this.destroying || this.destroyed) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            view = me.getView(),
            dataSource = view.dataSource,
            recCount = dataSource.getCount(),
            context = new Ext.grid.CellContext(view).setColumn(me),
            rows = me.getView().all,
            index = rows.startIndex;

        while (index <= rows.endIndex && index < recCount) {
            context.setRow(index);
            me.updater(context.getCell(true), ++index, null, view, dataSource);
        }
    }
});
