/**
 * A custom HeaderContainer for the {@link Ext.grid.property.Grid}.
 * Generally it should not need to be used directly.
 */
Ext.define('Ext.grid.property.HeaderContainer', {

    extend: 'Ext.grid.header.Container',

    alternateClassName: 'Ext.grid.PropertyColumnModel',

    nameWidth: 115,

    /**
     * @cfg {String} nameText
     * The column header text for the name column.
     * @locale
     */
    nameText: 'Name',

    /**
     * @cfg {String} valueText
     * The column header text for the value column.
     * @locale
     */
    valueText: 'Value',

    /**
     * @cfg {String} dateFormat
     * The {@link Ext.Date date format} to use for date values.
     * @locale
     */
    dateFormat: 'm/j/Y',

    /**
     * @cfg {String} trueText
     * The text to display for boolean `true` values.
     * @locale
     */
    trueText: 'true',

    /**
     * @cfg {String} falseText
     * The text to display for boolean `false` values.
     * @locale
     */
    falseText: 'false',

    /**
     * @private
     */
    nameColumnCls: Ext.baseCSSPrefix + 'grid-property-name',
    nameColumnInnerCls: Ext.baseCSSPrefix + 'grid-cell-inner-property-name',

    /**
     * Creates new HeaderContainer.
     * @param {Ext.grid.property.Grid} grid The grid this store will be bound to
     * @param {Object} source The source data config object
     */
    constructor: function(grid, source) {
        var me = this;

        me.grid = grid;
        me.store = source;
        me.callParent([{
            isRootHeader: true,

            enableColumnResize: Ext.isDefined(grid.enableColumnResize)
                ? grid.enableColumnResize
                : me.enableColumnResize,

            enableColumnMove: Ext.isDefined(grid.enableColumnMove)
                ? grid.enableColumnMove
                : me.enableColumnMove,

            items: [{
                header: me.nameText,
                width: grid.nameColumnWidth || me.nameWidth,
                sortable: grid.sortableColumns,
                dataIndex: grid.nameField,
                scope: me,
                renderer: me.renderProp,
                itemId: grid.nameField,
                menuDisabled: true,
                tdCls: me.nameColumnCls,
                innerCls: me.nameColumnInnerCls
            }, {
                header: me.valueText,
                scope: me,
                renderer: me.renderCell,
                getEditor: me.getCellEditor.bind(me),
                sortable: grid.sortableColumns,
                flex: 1,
                fixed: true,
                dataIndex: grid.valueField,
                itemId: grid.valueField,
                menuDisabled: true
            }]
        }]);

        // PropertyGrid needs to know which column is the editable "value" column.
        me.grid.valueColumn = me.items.getAt(1);
    },

    getCellEditor: function(record) {
        return this.grid.getCellEditor(record, this);
    },

    /**
     * @private
     * Render a property name cell
     */
    renderProp: function(v) {
        return this.getPropertyName(v);
    },

    /**
     * @private
     * Render a property value cell
     */
    renderCell: function(val, meta, rec) {
        var me = this,
            grid = me.grid,
            renderer = grid.getConfigProp(rec.get(grid.nameField), 'renderer'),
            result = val;

        if (renderer) {
            return Ext.callback(renderer, null, arguments, 0, me);
        }

        if (Ext.isDate(val)) {
            result = me.renderDate(val);
        }
        else if (Ext.isBoolean(val)) {
            result = me.renderBool(val);
        }

        return Ext.util.Format.htmlEncode(result);
    },

    /**
     * @private
     */
    renderDate: Ext.util.Format.date,

    /**
     * @private
     */
    renderBool: function(bVal) {
        return this[bVal ? 'trueText' : 'falseText'];
    },

    /**
     * @private
     * Renders custom property names instead of raw names if defined in the Grid
     */
    getPropertyName: function(name) {
        return this.grid.getConfigProp(name, 'displayName', name);
    }
});
