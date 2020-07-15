/**
 * This class encapsulates the user interface for a tabular data set.
 * It acts as a centralized manager for controlling the various interface
 * elements of the view. This includes handling events, such as row and cell
 * level based DOM events. It also reacts to events from the underlying {@link Ext.selection.Model}
 * to provide visual feedback to the user.
 *
 * This class does not provide ways to manipulate the underlying data of the configured
 * {@link Ext.data.Store}.
 *
 * This is the base class for both {@link Ext.grid.View} and {@link Ext.tree.View} and is not
 * to be used directly.
 */
Ext.define('Ext.view.Table', {
    extend: 'Ext.view.View',
    xtype: [ 'tableview', 'gridview' ],
    alternateClassName: 'Ext.grid.View',

    requires: [
        'Ext.grid.CellContext',
        'Ext.view.TableLayout',
        'Ext.grid.locking.RowSynchronizer',
        'Ext.view.NodeCache',
        'Ext.util.DelayedTask',
        'Ext.util.MixedCollection',
        'Ext.scroll.TableScroller'
    ],

    // View is now queryable by virtue of having managed widgets either in widget columns
    // or in RowWidget plugin
    mixins: [
        'Ext.mixin.Queryable'
    ],

    /**
     * @property {Boolean}
     * `true` in this class to identify an object as an instantiated Ext.view.TableView,
     * or subclass thereof.
     */
    isTableView: true,

    config: {
        selectionModel: {
            type: 'rowmodel'
        }
    },

    inheritableStatics: {
        // Events a TableView may fire. Used by Ext.grid.locking.View to relay events to its
        // ownerGrid in order to quack like a genuine Ext.table.View.
        // 
        // The events below are to be relayed only from the normal side view because the events
        // are relayed from the selection model, so both sides will fire them.
        /**
         * @private
         * @static
         * @inheritable
         */
        normalSideEvents: [
            "deselect",
            "select",
            "beforedeselect",
            "beforeselect",
            "selectionchange"
        ],

        // These events are relayed from both views because they are fired independently.
        /**
         * @private
         * @static
         * @inheritable
         */
        events: [
            "blur",
            "focus",
            "move",
            "resize",
            "destroy",
            "beforedestroy",
            "boxready",
            "afterrender",
            "render",
            "beforerender",
            "removed",
            "hide",
            "beforehide",
            "show",
            "beforeshow",
            "enable",
            "disable",
            "added",
            "deactivate",
            "beforedeactivate",
            "activate",
            "beforeactivate",
            "cellkeydown",
            "beforecellkeydown",
            "cellmouseup",
            "beforecellmouseup",
            "cellmousedown",
            "beforecellmousedown",
            "cellcontextmenu",
            "beforecellcontextmenu",
            "celldblclick",
            "beforecelldblclick",
            "cellclick",
            "beforecellclick",
            "refresh",
            "itemremove",
            "itemadd",
            "beforeitemupdate",
            "itemupdate",
            "viewready",
            "beforerefresh",
            "unhighlightitem",
            "highlightitem",
            "focuschange",
            "containerkeydown",
            "containercontextmenu",
            "containerdblclick",
            "containerclick",
            "containermouseout",
            "containermouseover",
            "containermouseup",
            "containermousedown",
            "beforecontainerkeydown",
            "beforecontainercontextmenu",
            "beforecontainerdblclick",
            "beforecontainerclick",
            "beforecontainermouseout",
            "beforecontainermouseover",
            "beforecontainermouseup",
            "beforecontainermousedown",
            "itemkeydown",
            "itemcontextmenu",
            "itemdblclick",
            "itemclick",
            "itemmouseleave",
            "itemmouseenter",
            "itemmouseup",
            "itemmousedown",
            "rowclick",
            "rowcontextmenu",
            "rowdblclick",
            "rowkeydown",
            "rowmouseup",
            "rowmousedown",
            "rowkeydown",
            "beforeitemkeydown",
            "beforeitemcontextmenu",
            "beforeitemdblclick",
            "beforeitemclick",
            "beforeitemmouseleave",
            "beforeitemmouseenter",
            "beforeitemmouseup",
            "beforeitemmousedown",
            "statesave",
            "beforestatesave",
            "staterestore",
            "beforestaterestore",
            "uievent",
            "groupcollapse",
            "groupexpand",
            "scroll"
        ]
    },

    scrollable: true,

    componentLayout: 'tableview',

    baseCls: Ext.baseCSSPrefix + 'grid-view',

    unselectableCls: Ext.baseCSSPrefix + 'unselectable',

    /**
     * @cfg {String} [firstCls='x-grid-cell-first']
     * A CSS class to add to the *first* cell in every row to enable special styling
     * for the first column.
     * If no styling is needed on the first column, this may be configured as `null`.
     */
    firstCls: Ext.baseCSSPrefix + 'grid-cell-first',

    /**
     * @cfg {String} [lastCls='x-grid-cell-last']
     * A CSS class to add to the *last* cell in every row to enable special styling
     * for the last column.
     * If no styling is needed on the last column, this may be configured as `null`.
     */
    lastCls: Ext.baseCSSPrefix + 'grid-cell-last',

    itemCls: Ext.baseCSSPrefix + 'grid-item',
    selectedItemCls: Ext.baseCSSPrefix + 'grid-item-selected',
    selectedCellCls: Ext.baseCSSPrefix + 'grid-cell-selected',
    focusedItemCls: Ext.baseCSSPrefix + 'grid-item-focused',
    overItemCls: Ext.baseCSSPrefix + 'grid-item-over',
    altRowCls: Ext.baseCSSPrefix + 'grid-item-alt',
    dirtyCls: Ext.baseCSSPrefix + 'grid-dirty-cell',
    rowClsRe: new RegExp('(?:^|\\s*)' + Ext.baseCSSPrefix + 'grid-item-alt(?:\\s+|$)', 'g'),
    cellRe: new RegExp(Ext.baseCSSPrefix + 'grid-cell-([^\\s]+)(?:\\s|$)', ''),
    positionBody: true,
    positionCells: false,
    stripeOnUpdate: null,

    /**
     * @property {Boolean} actionableMode
     * This value is `true` when the grid has been set to actionable mode by the user.
     *
     * See http://www.w3.org/TR/2013/WD-wai-aria-practices-20130307/#grid
     * @readonly
     */
    actionableMode: false,

    // cfg docs inherited
    trackOver: true,

    /**
     * Override this function to apply custom CSS classes to rows during rendering. This function
     * should return the CSS class name (or empty string '' for none) that will be added
     * to the row's wrapping element. To apply multiple class names, simply return them
     * space-delimited within the string (e.g. 'my-class another-class').
     * Example usage:
     *
     *     viewConfig: {
     *         getRowClass: function(record, rowIndex, rowParams, store){
     *             return record.get("valid") ? "row-valid" : "row-error";
     *         }
     *     }
     *
     * @param {Ext.data.Model} record The record corresponding to the current row.
     * @param {Number} index The row index.
     * @param {Object} rowParams **DEPRECATED.** For row body use the
     * {@link Ext.grid.feature.RowBody#getAdditionalData getAdditionalData} method of the rowbody
     * feature.
     * @param {Ext.data.Store} store The store this grid is bound to
     * @return {String} a CSS class name to add to the row.
     * @method
     */
    getRowClass: null,

    /**
     * @cfg {Boolean} stripeRows
     * True to stripe the rows.
     *
     * This causes the CSS class **`x-grid-row-alt`** to be added to alternate rows of
     * the grid. A default CSS rule is provided which sets a background color, but you can override
     * this with a rule which either overrides the **background-color** style using the `!important`
     * modifier, or which uses a CSS selector of higher specificity.
     */
    stripeRows: true,

    /**
     * @cfg {Boolean} markDirty
     * True to show the dirty cell indicator when a cell has been modified.
     */
    markDirty: true,

    /**
     * @cfg {Boolean} [enableTextSelection=false]
     * True to enable text selection inside this view.
     */

    ariaRole: 'rowgroup',
    rowAriaRole: 'row',
    cellAriaRole: 'gridcell',

    /**
     * @property {Ext.view.Table} ownerGrid
     * A reference to the top-level owning grid component. This is actually the TablePanel
     * so it could be a tree.
     * @readonly
     * @private
     * @since 5.0.0
     */

    /**
     * @method disable
     * Disable this view.
     *
     * Disables interaction with, and masks this view.
     *
     * Note that the encapsulating {@link Ext.panel.Table} panel is *not* disabled, and other
     * *docked* components such as the panel header, the column header container, and docked
     * toolbars will still be enabled. The panel itself can be disabled if that is required,
     * or individual docked components could be disabled.
     *
     * See {@link Ext.panel.Table #disableColumnHeaders disableColumnHeaders} and
     * {@link Ext.panel.Table #enableColumnHeaders enableColumnHeaders}.
     *
     * @param {Boolean} [silent=false] Passing `true` will suppress the `disable` event
     * from being fired.
     * @since 1.1.0
     */

    /* eslint-disable indent, max-len */
    /**
     * @cfg tpl
     * @private
     * Outer tpl for TableView just to satisfy the validation within AbstractView.initComponent.
     */
    tpl: [
        '{%',
            'view = values.view;',
            'if (!(columns = values.columns)) {',
                'columns = values.columns = view.ownerCt.getVisibleColumnManager().getColumns();',
            '}',
            'values.fullWidth = 0;',
            // Stamp cellWidth into the columns
            'for (i = 0, len = columns.length; i < len; i++) {',
                'column = columns[i];',
                'values.fullWidth += (column.cellWidth = column.lastBox ? column.lastBox.width : column.width || column.minWidth);',
            '}',

            // Add the row/column line classes to the container element.
            'tableCls=values.tableCls=[];',
        '%}',
        '<div class="' + Ext.baseCSSPrefix + 'grid-item-container" role="presentation" style="width:{fullWidth}px">',
            '{[view.renderTHead(values, out, parent)]}',
            '{%',
                'view.renderRows(values.rows, values.columns, values.viewStartIndex, out);',
            '%}',
            '{[view.renderTFoot(values, out, parent)]}',
        '</div>',

        // This template is shared on the Ext.view.Table prototype, so we have to
        // clean up the closed over variables. Otherwise we'll retain the last values
        // of the template execution!
        '{% ',
            'view = columns = column = null;',
        '%}',
        {
            definitions: 'var view, tableCls, columns, i, len, column;',
            priority: 0
        }
    ],

    outerRowTpl: [
        '<table id="{rowId}" role="presentation" ',
            'data-boundView="{view.id}" ',
            'data-recordId="{record.internalId}" ',
            'data-recordIndex="{recordIndex}" ',
            'class="{[values.itemClasses.join(" ")]}" cellpadding="0" cellspacing="0" style="{itemStyle};width:0">',

                // Do NOT emit a <TBODY> tag in case the nextTpl has to emit a <COLGROUP> column sizer element.
                // Browser will create a tbody tag when it encounters the first <TR>
                '{%',
                    'this.nextTpl.applyOut(values, out, parent)',
                '%}',
        '</table>',
        {
            priority: 9999
        }
    ],

    rowTpl: [
        '{%',
            'var dataRowCls = values.recordIndex === -1 ? "" : " ' + Ext.baseCSSPrefix + 'grid-row";',
        '%}',
        '<tr class="{[values.rowClasses.join(" ")]} {[dataRowCls]}"',
                ' role="{rowRole}" {rowAttr:attributes}>',
            '<tpl for="columns">' +
                '{%',
                    'parent.view.renderCell(values, parent.record, parent.recordIndex, parent.rowIndex, xindex - 1, out, parent)',
                 '%}',
            '</tpl>',
        '</tr>',
        {
            priority: 0
        }
    ],

    cellTpl: [
        '<td class="{tdCls}" {tdAttr} {cellAttr:attributes}',
            ' style="width:{column.cellWidth}px;',
            '{% if(values.tdStyle){out.push(values.tdStyle);}%}"',
            '{% if (values.column.cellFocusable === false) {%}',
                ' role="presentation"',
            '{% } else { %}',
                ' role="{cellRole}" tabindex="-1"',
            '{% } %}',
            '  data-columnid="{[values.column.getItemId()]}">',
            '<div {unselectableAttr} class="' + Ext.baseCSSPrefix + 'grid-cell-inner {innerCls}" ',
                'style="text-align:{align};',
                '{% if (values.style) {out.push(values.style);} %}" ',
                '{cellInnerAttr:attributes}>{value}</div>',
        '</td>',
        {
            priority: 0
        }
    ],
    /* eslint-enable indent, max-len */

    /**
     * @private
     * Flag to disable refreshing SelectionModel on view refresh. Table views render rows
     * with selected CSS class already added if necessary.
     */
    refreshSelmodelOnRefresh: false,

    scrollableType: 'table',

    tableValues: {},

    // Private properties used during the row and cell render process.
    // They are allocated here on the prototype, and cleared/re-used to avoid GC churn
    // during repeated rendering.
    rowValues: {
        itemClasses: [],
        rowClasses: []
    },

    cellValues: {
        classes: [
            // for styles shared between cell and rowwrap
            Ext.baseCSSPrefix + 'grid-cell ' + Ext.baseCSSPrefix + 'grid-td'
        ]
    },

    /**
     * @event beforecellclick
     * Fired before the cell click is processed. Return false to cancel the default action.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event cellclick
     * Fired when table cell is clicked.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event beforecelldblclick
     * Fired before the cell double click is processed. Return false to cancel the default action.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event celldblclick
     * Fired when table cell is double clicked.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event beforecellcontextmenu
     * Fired before the cell right click is processed. Return false to cancel the default action.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event cellcontextmenu
     * Fired when table cell is right clicked.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event beforecellmousedown
     * Fired before the cell mouse down is processed. Return false to cancel the default action.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event cellmousedown
     * Fired when the mousedown event is captured on the cell.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event beforecellmouseup
     * Fired before the cell mouse up is processed. Return false to cancel the default action.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event cellmouseup
     * Fired when the mouseup event is captured on the cell.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event beforecellkeydown
     * Fired before the cell key down is processed. Return false to cancel the default action.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event cellkeydown
     * Fired when the keydown event is captured on the cell.
     * @param {Ext.view.Table} this
     * @param {HTMLElement} td The TD element for the cell.
     * @param {Number} cellIndex
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the cell.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines the target cell.
     */

    /**
     * @event rowclick
     * Fired when a table row is clicked.
     * @param {Ext.view.Table} this
     * @param {Ext.data.Model} record
     * @param {HTMLElement} element The TR element for the row.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines
     * the target row.
     */

    /**
     * @event rowdblclick
     * Fired when table row is double clicked.
     * @param {Ext.view.Table} this
     * @param {Ext.data.Model} record
     * @param {HTMLElement} element The TR element for the row.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines
     * the target row.
     */

    /**
     * @event rowcontextmenu
     * Fired when table row is right clicked.
     * @param {Ext.view.Table} this
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the row.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines
     * the target row.
     */

    /**
     * @event rowmousedown
     * Fired when the mousedown event is captured on the row.
     * @param {Ext.view.Table} this
     * @param {Ext.data.Model} record
     * @param {HTMLElement} tr The TR element for the row.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines
     * the target row.
     */

    /**
     * @event rowmouseup
     * Fired when the mouseup event is captured on the row.
     * @param {Ext.view.Table} this
     * @param {Ext.data.Model} record
     * @param {HTMLElement} element The TR element for the row.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines
     * the target row.
     */

    /**
     * @event rowkeydown
     * Fired when the keydown event is captured on the row.
     * @param {Ext.view.Table} this
     * @param {Ext.data.Model} record
     * @param {HTMLElement} element The TR element for the row.
     * @param {Number} rowIndex
     * @param {Ext.event.Event} e
     * @param {Ext.grid.CellContext} e.position A CellContext object which defines
     * the target row.
     */

    /**
     * @event beforerowexit
     * Fired when View is asked to exit Actionable mode in the current row,
     * and proceed to the previous/next row. If the handler returns `false`,
     * View processing is aborted.
     * @param {Ext.view.Table} this
     * @param {Ext.event.Event} keyEvent The key event that caused navigation.
     * @param {HTMLElement} prevRow Currently active table row.
     * @param {HTMLElement} nextRow Table row that is going to be focused and activated.
     * @param {Boolean} forward `true` if we're navigating forward (Tab), `false` if
     * navigating backward (Shift-Tab).
     */

    constructor: function(config) {
        // Adjust our base class if we are inside a TreePanel
        if (config.grid.isTree) {
            config.baseCls = Ext.baseCSSPrefix + 'tree-view';
        }

        this.callParent([config]);
    },

    /**
     * @private
     * Returns `true` if this view has been configured with variableRowHeight (or this has been set
     * by a plugin/feature) which might insert arbitrary markup into a grid item. Or if at least one
     * visible column has been configured with variableRowHeight. Or if the store is grouped.
     */
    hasVariableRowHeight: function(fromLockingPartner) {
        var me = this;

        return me.variableRowHeight || me.store.isGrouped() ||
               me.getVisibleColumnManager().hasVariableRowHeight() ||
               // If not already called from a locking partner, and there is a locking partner,
               // and the partner has variableRowHeight, then WE have variableRowHeight too.
               (!fromLockingPartner && me.lockingPartner &&
               me.lockingPartner.hasVariableRowHeight(true));
    },

    initComponent: function() {
        var me = this;

        if (me.columnLines) {
            me.addCls(me.grid.colLinesCls);
        }

        if (me.rowLines) {
            me.addCls(me.grid.rowLinesCls);
        }

        /**
         * @private
         * @property {Ext.dom.Fly} body
         * A flyweight Ext.Element which encapsulates a reference to the view's main row
         * containing element.
         * *Note that the `dom` reference will not be present until the first data refresh*
         */
        me.body = new Ext.dom.Fly();
        me.body.id = me.id + 'gridBody';

        // If trackOver has been turned off, null out the overCls because documented behaviour
        // in AbstractView is to turn trackOver on if overItemCls is set.
        if (!me.trackOver) {
            me.overItemCls = null;
        }

        me.headerCt.view = me;

        // Features need a reference to the grid.
        // Grid needs an immediate reference to its view so that the view
        // can reliably be got from the grid during initialization
        me.grid.view = me;
        me.initFeatures(me.grid);

        me.itemSelector = me.getItemSelector();
        me.all = new Ext.view.NodeCache(me);

        me.actionRowFly = new Ext.dom.Fly();

        me.callParent();
    },

    /**
     * @private
     * Create a config object for this view's selection model based upon the passed grid's
     * configurations.
     */
    applySelectionModel: function(selModel, oldSelModel) {
        var me = this,
            grid = me.ownerGrid,
            defaultType = selModel.type,
            disableSelection = me.disableSelection || grid.disableSelection;

        // If this is the initial configuration, pull overriding configs
        // in from the owning TablePanel.
        if (!oldSelModel) {
            // Favour a passed instance
            if (!(selModel && selModel.isSelectionModel)) {
                selModel = grid.selModel || selModel;
            }
        }

        if (selModel) {
            if (selModel.isSelectionModel) {
                selModel.allowDeselect = grid.allowDeselect || selModel.selectionMode !== 'SINGLE';
                selModel.locked = disableSelection;
            }
            else {
                if (typeof selModel === 'string') {
                    selModel = {
                        type: selModel
                    };
                }
                // Copy obsolete selType property to type property now that selection models
                // are Factoryable
                // TODO: Remove selType config after deprecation period
                else {
                    selModel.type = grid.selType || selModel.selType || selModel.type ||
                                    defaultType;
                }

                if (!selModel.mode) {
                    if (grid.simpleSelect) {
                        selModel.mode = 'SIMPLE';
                    }
                    else if (grid.multiSelect) {
                        selModel.mode = 'MULTI';
                    }
                }

                selModel = Ext.Factory.selection(Ext.apply({
                    allowDeselect: grid.allowDeselect,
                    locked: disableSelection
                }, selModel));
            }
        }

        return selModel;
    },

    updateSelectionModel: function(selModel, oldSelModel) {
        var me = this;

        if (oldSelModel) {
            oldSelModel.un({
                scope: me,
                lastselectedchanged: me.updateBindSelection,
                selectionchange: me.updateBindSelection
            });
            Ext.destroy(me.selModelRelayer);
        }

        me.selModelRelayer = me.relayEvents(selModel, [
            'selectionchange', 'beforeselect', 'beforedeselect', 'select', 'deselect', 'focuschange'
        ]);

        selModel.on({
            scope: me,
            lastselectedchanged: me.updateBindSelection,
            selectionchange: me.updateBindSelection
        });

        me.selModel = selModel;
    },

    getVisibleColumnManager: function() {
        return this.ownerCt.getVisibleColumnManager();
    },

    getColumnManager: function() {
        return this.ownerCt.getColumnManager();
    },

    getTopLevelVisibleColumnManager: function() {
        // ownerGrid refers to the topmost responsible Ext.panel.Grid.
        // This could be this view's ownerCt, or if part of a locking arrangement, the locking grid
        return this.ownerGrid.getVisibleColumnManager();
    },

    /**
     * @private
     * Move a grid column from one position to another
     * @param {Number} fromIdx The index from which to move columns
     * @param {Number} toIdx The index at which to insert columns.
     * @param {Number} [colsToMove=1] The number of columns to move beginning at the `fromIdx`
     */
    moveColumn: function(fromIdx, toIdx, colsToMove) {
        var me = this,
            multiMove = colsToMove > 1,
            range = multiMove && document.createRange ? document.createRange() : null,
            fragment = multiMove && !range ? document.createDocumentFragment() : null,
            destinationCellIdx = toIdx,
            colCount = me.getGridColumns().length,
            lastIndex = colCount - 1,
            i, j, rows, len, tr, cells, colGroups, doFirstLastClasses;

        doFirstLastClasses =
            (me.firstCls || me.lastCls) &&
            (toIdx === 0 || toIdx === colCount || fromIdx === 0 || fromIdx === lastIndex);

        // Dragging between locked and unlocked side first refreshes the view,
        // and calls onHeaderMoved with fromIndex and toIndex the same.
        if (me.rendered && toIdx !== fromIdx) {
            // Grab all rows which have column cells in.
            // That is data rows.
            rows = me.el.query(me.rowSelector);

            for (i = 0, len = rows.length; i < len; i++) {
                tr = rows[i];
                cells = tr.childNodes;

                // Keep first cell class and last cell class correct *only if needed*
                if (doFirstLastClasses) {

                    if (cells.length === 1) {
                        Ext.fly(cells[0]).addCls(me.firstCls);
                        Ext.fly(cells[0]).addCls(me.lastCls);
                        continue;
                    }

                    if (fromIdx === 0) {
                        Ext.fly(cells[0]).removeCls(me.firstCls);
                        Ext.fly(cells[1]).addCls(me.firstCls);
                    }
                    else if (fromIdx === lastIndex) {
                        Ext.fly(cells[lastIndex]).removeCls(me.lastCls);
                        Ext.fly(cells[lastIndex - 1]).addCls(me.lastCls);
                    }

                    if (toIdx === 0) {
                        Ext.fly(cells[0]).removeCls(me.firstCls);
                        Ext.fly(cells[fromIdx]).addCls(me.firstCls);
                    }
                    else if (toIdx === colCount) {
                        Ext.fly(cells[lastIndex]).removeCls(me.lastCls);
                        Ext.fly(cells[fromIdx]).addCls(me.lastCls);
                    }
                }

                // Move multi using the best technique.
                // Extract a range straight into a fragment if possible.
                if (multiMove) {
                    if (range) {
                        range.setStartBefore(cells[fromIdx]);
                        range.setEndAfter(cells[fromIdx + colsToMove - 1]);
                        fragment = range.extractContents();
                    }
                    else {
                        for (j = 0; j < colsToMove; j++) {
                            fragment.appendChild(cells[fromIdx]);
                        }
                    }

                    tr.insertBefore(fragment, cells[destinationCellIdx] || null);
                }
                else {
                    tr.insertBefore(cells[fromIdx], cells[destinationCellIdx] || null);
                }
            }

            // Shuffle the <col> elements in all <colgroup>s
            colGroups = me.el.query('colgroup');

            for (i = 0, len = colGroups.length; i < len; i++) {
                // Extract the colgroup
                tr = colGroups[i];

                // Move multi using the best technique.
                // Extract a range straight into a fragment if possible.
                if (multiMove) {
                    if (range) {
                        range.setStartBefore(tr.childNodes[fromIdx]);
                        range.setEndAfter(tr.childNodes[fromIdx + colsToMove - 1]);
                        fragment = range.extractContents();
                    }
                    else {
                        for (j = 0; j < colsToMove; j++) {
                            fragment.appendChild(tr.childNodes[fromIdx]);
                        }
                    }

                    tr.insertBefore(fragment, tr.childNodes[destinationCellIdx] || null);
                }
                else {
                    tr.insertBefore(tr.childNodes[fromIdx],
                                    tr.childNodes[destinationCellIdx] || null);
                }
            }
        }
    },

    // scroll the view to the top
    scrollToTop: Ext.emptyFn,

    /**
     * Add a listener to the main view element. It will be destroyed with the view.
     * @private
     */
    addElListener: function(eventName, fn, scope) {
        this.mon(this, eventName, fn, scope, {
            element: 'el'
        });
    },

    /**
     * Get the leaf columns used for rendering the grid rows.
     * @private
     */
    getGridColumns: function() {
        return this.ownerCt.getVisibleColumnManager().getColumns();
    },

    /**
     * Get a leaf level header by index regardless of what the nesting
     * structure is.
     * @private
     * @param {Number} index The index
     */
    getHeaderAtIndex: function(index) {
        return this.ownerCt.getVisibleColumnManager().getHeaderAtIndex(index);
    },

    /**
     * Get the cell (td) for a particular record and column.
     * @param {Ext.data.Model} record
     * @param {Ext.grid.column.Column/Number} column
     * @param {Boolean} [returnElement=false] `true` to return an Ext.Element,
     * else a raw `<td>` is returned.
     * @private
     */
    getCell: function(record, column, returnElement) {
        var row = this.getRow(record),
            cell;

        if (row) {
            if (typeof column === 'number') {
                column = this.getHeaderAtIndex(column);
            }

            cell = row.querySelector(column.getCellSelector());

            return returnElement ? Ext.get(cell) : cell;
        }
    },

    /**
     * Get a reference to a feature
     * @param {String} id The id of the feature
     * @return {Ext.grid.feature.Feature} The feature. Undefined if not found
     */
    getFeature: function(id) {
        var features = this.featuresMC;

        if (features) {
            return features.get(id);
        }
    },

    /**
     * @private
     * Finds a features by ftype in the features array
     */
    findFeature: function(ftype) {
        if (this.features) {
            return Ext.Array.findBy(this.features, function(feature) {
                if (feature.ftype === ftype) {
                    return true;
                }
            });
        }
    },

    /**
     * Initializes each feature and bind it to this view.
     * @private
     */
    initFeatures: function(grid) {
        var me = this,
            features, feature, i, len;

        // Row container element emitted by tpl
        me.tpl = this.lookupTpl('tpl');

        // The rowTpl emits a <div>
        me.rowTpl = me.lookupTpl('rowTpl');
        me.addRowTpl(me.lookupTpl('outerRowTpl'));

        // Each cell is emitted by the cellTpl
        me.cellTpl = me.lookupTpl('cellTpl');

        me.featuresMC = new Ext.util.MixedCollection();
        features = me.features = me.constructFeatures();

        for (i = 0, len = features ? features.length : 0; i < len; i++) {
            feature = features[i];

            // inject a reference to view and grid - Features need both
            feature.view = me;
            feature.grid = grid;

            me.featuresMC.add(feature);

            feature.init(grid);
        }
    },

    renderTHead: function(values, out, parent) {
        var headers = values.view.headerFns,
            len, i;

        if (headers) {
            for (i = 0, len = headers.length; i < len; ++i) {
                headers[i].call(this, values, out, parent);
            }
        }
    },

    // Currently, we don't have ordering support for header/footer functions,
    // they will be pushed on at construction time. If the need does arise,
    // we can add this functionality in the future, but for now it's not
    // really necessary since currently only the summary feature uses this.
    addHeaderFn: function(fn) {
        var headers = this.headerFns;

        if (!headers) {
            headers = this.headerFns = [];
        }

        headers.push(fn);
    },

    renderTFoot: function(values, out, parent) {
        var footers = values.view.footerFns,
            len, i;

        if (footers) {
            for (i = 0, len = footers.length; i < len; ++i) {
                footers[i].call(this, values, out, parent);
            }
        }
    },

    addFooterFn: function(fn) {
        var footers = this.footerFns;

        if (!footers) {
            footers = this.footerFns = [];
        }

        footers.push(fn);
    },

    addTpl: function(newTpl) {
        return this.insertTpl('tpl', newTpl);
    },

    addRowTpl: function(newTpl) {
        return this.insertTpl('rowTpl', newTpl);
    },

    addCellTpl: function(newTpl) {
        return this.insertTpl('cellTpl', newTpl);
    },

    insertTpl: function(which, newTpl) {
        var me = this,
            tpl, prevTpl;

        // Clone an instantiated XTemplate
        if (newTpl.isTemplate) {
            newTpl = Ext.Object.chain(newTpl);
        }
        // If we have been passed an object of the form
        // {
        //      before: fn
        //      after: fn
        // }
        // Create a template from it using the object as the member configuration
        else {
            newTpl = new Ext.XTemplate('{%this.nextTpl.applyOut(values, out, parent);%}', newTpl);
        }

        // Stop at the first TPL who's priority is less than the passed rowTpl
        for (tpl = me[which]; newTpl.priority < tpl.priority; tpl = tpl.nextTpl) {
            prevTpl = tpl;
        }

        // If we had skipped over some, link the previous one to the passed rowTpl
        if (prevTpl) {
            prevTpl.nextTpl = newTpl;
        }
        // First one
        else {
            me[which] = newTpl;
        }

        newTpl.nextTpl = tpl;

        return newTpl;
    },

    tplApplyOut: function(values, out, parent) {
        if (this.before) {
            if (this.before(values, out, parent) === false) {
                return;
            }
        }

        this.nextTpl.applyOut(values, out, parent);

        if (this.after) {
            this.after(values, out, parent);
        }
    },

    /**
     * @private
     * Converts the features array as configured, into an array of instantiated Feature objects.
     *
     * Must have no side effects other than Feature instantiation.
     *
     * MUST NOT update the this.features property, and MUST NOT update the instantiated Features.
     */
    constructFeatures: function() {
        var me = this,
            features = me.features,
            feature, result, i, len;

        if (features) {
            result = [];

            for (i = 0, len = features.length; i < len; i++) {
                feature = features[i];

                if (!feature.isFeature) {
                    feature = Ext.create('feature.' + feature.ftype, feature);
                }

                result[i] = feature;
            }
        }

        return result;
    },

    beforeRender: function() {
        this.callParent();

        if (!this.enableTextSelection) {
            this.protoEl.unselectable();
        }
    },

    updateScrollable: function(scrollable) {
        var me = this,
            ownerGrid = me.grid.ownerGrid;

        if (!ownerGrid.lockable && scrollable.isScroller && scrollable !== ownerGrid.scrollable) {
            ownerGrid.scrollable = scrollable;
        }
    },

    afterComponentLayout: function(width, height, oldWidth, oldHeight) {
        var me = this,
            ownerGrid = me.grid.ownerGrid;

        if (ownerGrid.mixins.lockable) {
            ownerGrid.syncLockableLayout();
        }

        me.callParent([width, height, oldWidth, oldHeight]);
    },

    getElConfig: function() {
        var config = this.callParent();

        // Table views are special in this regard; they should not have
        // aria-hidden and aria-disabled attributes.
        delete config['aria-hidden'];
        delete config['aria-disabled'];

        return config;
    },

    onBindStore: function(store) {
        var me = this,
            bufferedRenderer = me.bufferedRenderer;

        if (bufferedRenderer && bufferedRenderer.store !== store) {
            bufferedRenderer.bindStore(store);
        }

        // Clear view el unless we're reconfiguring - a refresh will happen.
        if (me.all && me.all.getCount() && !me.grid.reconfiguring) {
            me.clearViewEl(true);
        }

        me.callParent(arguments);
    },

    onOwnerGridHide: function() {
        var scroller = this.getScrollable(),
            bufferedRenderer = this.bufferedRederer;

        // Hide using display sets scroll to zero.
        // We should not tell any partners about this.
        if (scroller) {
            scroller.suspendPartnerSync();
        }

        // A buffered renderer should also not respond to that scroll.
        if (bufferedRenderer) {
            bufferedRenderer.disable();
        }
    },

    onOwnerGridShow: function() {
        var scroller = this.getScrollable(),
            bufferedRenderer = this.bufferedRederer;

        // Hide using display sets scroll to zero.
        // We should not tell any partners about this.
        if (scroller) {
            scroller.resumePartnerSync();
        }

        // A buffered renderer should also not respond to that scroll.
        if (bufferedRenderer) {
            bufferedRenderer.enable();
        }
    },

    getStoreListeners: function(store) {
        var me = this,
            result = me.callParent([store]),
            dataSource = me.dataSource;

        if (dataSource && dataSource.isFeatureStore) {
            // GroupStore triggers a refresh on add/remove, we don't want to have
            // it process twice
            delete result.add;
            delete result.remove;
        }

        // The BufferedRenderer handles clearing down the view on its onStoreClear method
        if (me.bufferedRenderer) {
            delete result.clear;
        }

        result.beforepageremove = me.beforePageRemove;

        return result;
    },

    beforePageRemove: function(pageMap, pageNumber) {
        var rows = this.all,
            pageSize = pageMap.getPageSize();

        // If the rendered block needs the page, access it which moves it
        // to the end of the LRU cache, and veto removal.
        if (rows.startIndex >= (pageNumber - 1) * pageSize &&
            rows.endIndex <= (pageNumber * pageSize - 1)) {
            pageMap.get(pageNumber);

            return false;
        }
    },

    /**
     * @private
     * Template method implemented starting at the AbstractView class.
     */
    onViewScroll: function(scroller, x, y) {
        // We ignore scrolling caused by focusing
        if (!this.destroyed && !this.ignoreScroll) {
            this.callParent([scroller, x, y]);
        }
    },

    /** 
     * @private
     * Create the DOM element which encapsulates the passed record.
     * Used when updating existing rows, so drills down into resulting structure.
     */
    createRowElement: function(record, index, updateColumns) {
        var me = this,
            div = me.renderBuffer,
            tplData = me.collectData([record], index),
            result;

        tplData.columns = updateColumns;
        me.tpl.overwrite(div, tplData);

        // We don't want references to be retained on the prototype
        me.cleanupData();

        // Return first element within node containing element
        result = div.dom.querySelector(me.getNodeContainerSelector()).firstChild;
        Ext.fly(result).saveTabbableState(me.saveTabOptions);

        return result;
    },

    /** 
     * @private
     * Override so that we can use a quicker way to access the row nodes.
     * They are simply all child nodes of the nodeContainer element.
     */
    bufferRender: function(records, index) {
        var me = this,
            div = me.renderBuffer,
            range = document.createRange ? document.createRange() : null,
            result;

        me.tpl.overwrite(div, me.collectData(records, index));

        // We don't want references to be retained on the prototype
        me.cleanupData();

        // Newly added rows must be untabbable by default
        div.saveTabbableState(me.saveTabOptions);

        div = div.dom.querySelector(me.getNodeContainerSelector());

        if (range) {
            range.selectNodeContents(div);
            result = range.extractContents();
        }
        else {
            result = document.createDocumentFragment();

            while (div.firstChild) {
                result.appendChild(div.firstChild);
            }
        }

        return {
            fragment: result,
            children: Ext.Array.toArray(result.childNodes)
        };
    },

    collectData: function(records, startIndex) {
        var me = this,
            tableValues = me.tableValues;

        me.rowValues.view = me;

        tableValues.view = me;
        tableValues.rows = records;
        tableValues.columns = null;
        tableValues.viewStartIndex = startIndex;
        tableValues.tableStyle = 'width:' + me.headerCt.getTableWidth() + 'px';

        return tableValues;
    },

    cleanupData: function() {
        var tableValues = this.tableValues;

        // Clean up references on the prototype
        tableValues.view = tableValues.columns = tableValues.rows = this.rowValues.view = null;
    },

    /** 
     * @private
     * Called when the table changes height.
     * For example, see examples/grid/group-summary-grid.html
     * If we have flexed column headers, we need to update the header layout
     * because it may have to accommodate (or cease to accommodate) a vertical scrollbar.
     * Only do this on platforms which have a space-consuming scrollbar.
     * Only do it when vertical scrolling is enabled.
     */
    refreshSize: function(forceLayout) {
        var me = this,
            bodySelector = me.getBodySelector(),
            lockingPartner = me.lockingPartner,
            restoreFocus;

        // keeping the current position to be restored by afterComponentLayout
        // once it's called because of resumeLayouts.
        if (!me.actionableMode) {
            restoreFocus = me.saveFocusState();
        }

        // On every update of the layout system due to data update, capture the view's main element
        // in our private flyweight.
        // IF there *is* a main element. Some TplFactories emit naked rows.
        if (bodySelector) {
            // use "down" instead of "child" because the grid table element is not a direct
            // child of the view element when a touch scroller is in use.
            me.body.attach(me.el.dom.querySelector(bodySelector));
        }

        if (!me.hasLoadingHeight) {
            // Suspend layouts in case the superclass requests a layout. We might too, so they
            // must be coalesced.
            Ext.suspendLayouts();

            me.callParent([forceLayout]);

            // We only need to adjust for height changes in the data if we, or any visible columns
            // have been configured with variableRowHeight: true
            // OR, if we are being passed the forceUpdate flag which is passed when the view's
            // item count changes.
            if (forceLayout || (me.hasVariableRowHeight() && me.dataSource.getCount())) {
                me.grid.updateLayout();
            }

            // Only flush layouts if there's no *visible* locking partner, or
            // the two partners have both refreshed to the same rendered block size.
            // If we are the first of a locking view pair, refreshing in response to a change of
            // view height, our rendered block size will be out of sync with our partner's
            // so row height equalization (called as part of a layout) will walk off the end.
            // This must be deferred until both views have refreshed to the same size.
            Ext.resumeLayouts(
                !lockingPartner || !lockingPartner.grid.isVisible() ||
                (lockingPartner.all.getCount() === me.all.getCount())
            );

            // Restore focus to the previous position in case layout cycles
            // scrolled the view back up.
            if (restoreFocus) {
                restoreFocus();
            }
        }
    },

    /**
     * @private
     * TableView is unable to lay out in isolation. It acquires information from
     * the HeaderContainer, so a request to layout a TableView MUST propagate upwards
     * into the grid.
     */
    isLayoutRoot: function() {
        return false;
    },

    clearViewEl: function(leaveNodeContainer) {
        var me = this,
            nodeContainer;

        // AbstractView will clear the view correctly
        // It also resets the scrollrange.
        if (me.rendered) {
            me.callParent();

            // If we are also removing the noe container, destroy it.
            if (!leaveNodeContainer) {
                nodeContainer = Ext.get(me.getNodeContainer());

                if (nodeContainer && nodeContainer.dom !== me.getTargetEl().dom) {
                    nodeContainer.destroy();
                }
            }
        }
    },

    getRefItems: function(deep) {
        // @private
        // CQ interface
        var me = this,
            rowContexts = me.ownerGrid.liveRowContexts,
            isLocked = !!me.isLockedView,
            result = me.callParent([deep]),
            widgetCount, i, widgets, widget, recordId;

        // Add the widgets from the RowContexts.
        // If deep, add any descendant widgets within them.
        for (recordId in rowContexts) {
            widgets = rowContexts[recordId].getWidgets();
            widgetCount = widgets.length;

            for (i = 0; i < widgetCount; i++) {
                widget = widgets[i];

                // If we're in a lockable assembly, check that we're in the same side
                if (isLocked === widget.$fromLocked) {
                    result[result.length] = widget;

                    if (deep && widget.getRefItems) {
                        result.push.apply(result, widget.getRefItems(true));
                    }
                }
            }
        }

        return result;
    },

    getMaskTarget: function() {
        // Masking a TableView masks its IMMEDIATE parent GridPanel's body.
        // Disabling/enabling a locking view relays the call to both child views.
        return this.ownerCt.body;
    },

    statics: {
        getBoundView: function(node) {
            return Ext.getCmp(node.getAttribute('data-boundView'));
        }
    },

    getRecord: function(node) {
        // If store.destroy has been called before some delayed event fires on a node,
        // we must ignore the event.
        if (this.store.destroyed) {
            return;
        }

        if (node.isModel) {
            return node;
        }

        node = this.getNode(node);

        // Must use the internalId stamped into the DOM because if this is called after a sort
        // or filter, but before the refresh, then the "data-recordIndex" will be stale.
        if (node) {
            return this.dataSource.getByInternalId(node.getAttribute('data-recordId'));
        }
    },

    indexOf: function(node) {
        node = this.getNode(node);

        if (!node && node !== 0) {
            return -1;
        }

        return this.all.indexOf(node);
    },

    indexInStore: function(node) {
        // We cannot use the stamped in data-recordindex because that is the index in the original
        // configured store NOT the index in the dataSource that is being used -
        // that may be a GroupStore.
        return node ? this.dataSource.indexOf(this.getRecord(node)) : -1;
    },

    indexOfRow: function(record) {
        var dataSource = this.dataSource,
            idx;

        if (record.isCollapsedPlaceholder) {
            idx = dataSource.indexOfPlaceholder(record);
        }
        else {
            idx = dataSource.indexOf(record);
        }

        return idx;
    },

    renderRows: function(rows, columns, viewStartIndex, out) {
        var me = this,
            rowValues = me.rowValues,
            rowCount = rows.length,
            i;

        rowValues.view = me;
        rowValues.columns = columns;

        // The roles are the same for all data rows and cells
        rowValues.rowRole = me.rowAriaRole;
        me.cellValues.cellRole = me.cellAriaRole;

        for (i = 0; i < rowCount; i++, viewStartIndex++) {
            rowValues.itemClasses.length = rowValues.rowClasses.length = 0;
            me.renderRow(rows[i], viewStartIndex, out);
        }

        // Dereference objects since rowValues is a persistent on our prototype
        rowValues.view = rowValues.columns = rowValues.record = null;
    },

    /* Alternative column sizer element renderer.
    renderTHeadColumnSizer: function(values, out) {
        var columns = this.getGridColumns(),
            len = columns.length, i,
            column, width;

        out.push('<thead><tr class="' + Ext.baseCSSPrefix + 'grid-header-row">');
        
        for (i = 0; i < len; i++) {
            column = columns[i];
            
            width = column.lastBox 
                ? column.lastBox.width
                : Ext.grid.header.Container.prototype.defaultWidth;
            
            out.push(
                '<th class="', Ext.baseCSSPrefix, 'grid-cell-', columns[i].getItemId(),
                '" style="width:' + width + 'px"></th>'
            );
        }
        out.
        push('</tr></thead>');
    },
    */

    renderColumnSizer: function(values, out) {
        var columns = values.columns || this.getGridColumns(),
            len = columns.length,
            i, column, width;

        out.push('<colgroup role="presentation">');

        for (i = 0; i < len; i++) {
            column = columns[i];

            width = column.cellWidth
                ? column.cellWidth
                : Ext.grid.header.Container.prototype.defaultWidth;

            out.push(
                '<col role="presentation" class="', Ext.baseCSSPrefix, 'grid-cell-',
                columns[i].getItemId(), '" style="width:' + width + 'px">'
            );
        }

        out.push('</colgroup>');
    },

    /**
     * @private
     * Renders the HTML markup string for a single row into the passed array as a sequence
     * of strings, or returns the HTML markup for a single row.
     *
     * @param {Ext.data.Model} record The record to render.
     * @param {Number} rowIdx The index of the row
     * @param {String[]} [out] A string array onto which to append the resulting HTML string.
     * If omitted, the resulting HTML string is returned.
     * @return {String} **only when the out parameter is omitted** The resulting HTML string.
     */
    renderRow: function(record, rowIdx, out) {
        var me = this,
            isMetadataRecord = rowIdx === -1,
            selModel = me.selectionModel,
            rowValues = me.rowValues,
            itemClasses = rowValues.itemClasses,
            rowClasses = rowValues.rowClasses,
            itemCls = me.itemCls,
            cls,
            rowTpl = me.rowTpl;

        // Define the rowAttr object now. We don't want to do it in the treeview treeRowTpl
        // because anything this is processed in a deferred callback (such as deferring initial
        // view refresh in gridview) could poke rowAttr that are then shared in tableview.rowTpl.
        // See EXTJSIV-9341.
        //
        // For example, the following shows the shared ref between a treeview's rowTpl nextTpl
        // and the superclass tableview.rowTpl:
        //
        //      tree.view.rowTpl.nextTpl === grid.view.rowTpl
        //
        rowValues.rowAttr = {};

        // Set up mandatory properties on rowValues
        rowValues.record = record;
        rowValues.recordId = record.internalId;

        // recordIndex is index in true store (NOT the data source - possibly a GroupStore)
        rowValues.recordIndex = me.store.indexOf(record);

        // rowIndex is the row number in the view.
        rowValues.rowIndex = rowIdx;
        rowValues.rowId = me.getRowId(record);
        rowValues.itemCls = rowValues.rowCls = '';

        if (!rowValues.columns) {
            rowValues.columns = me.ownerCt.getVisibleColumnManager().getColumns();
        }

        itemClasses.length = rowClasses.length = 0;

        // If it's a metadata record such as a summary record.
        // So do not decorate it with the regular CSS.
        // The Feature which renders it must know how to decorate it.
        if (!isMetadataRecord) {
            itemClasses[0] = itemCls;

            if (!me.ownerCt.disableSelection && selModel.isRowSelected) {
                // Selection class goes on the outermost row, so it goes into itemClasses
                if (selModel.isRowSelected(record)) {
                    itemClasses.push(me.selectedItemCls);
                }
            }

            if (me.stripeRows && rowIdx % 2 !== 0) {
                itemClasses.push(me.altRowCls);
            }

            if (me.getRowClass) {
                cls = me.getRowClass(record, rowIdx, null, me.dataSource);

                if (cls) {
                    rowClasses.push(cls);
                }
            }
        }

        if (out) {
            rowTpl.applyOut(rowValues, out, me.tableValues);
        }
        else {
            return rowTpl.apply(rowValues, me.tableValues);
        }
    },

    /**
     * @private
     * Emits the HTML representing a single grid cell into the passed output stream
     * (which is an array of strings).
     *
     * @param {Ext.grid.column.Column} column The column definition for which to render a cell.
     * @param {Ext.data.Model} record The record
     * @param {Number} recordIndex The row index (zero based within the {@link #store}) for which
     * to render the cell.
     * @param {Number} rowIndex The row index (zero based within this view for which
     * to render the cell.
     * @param {Number} columnIndex The column index (zero based) for which to render the cell.
     * @param {String[]} out The output stream into which the HTML strings are appended.
     */
    renderCell: function(column, record, recordIndex, rowIndex, columnIndex, out) {
        var me = this,
            renderer = column.renderer,
            fullIndex,
            selModel = me.selectionModel,
            cellValues = me.cellValues,
            classes = cellValues.classes,
            fieldValue = record.data[column.dataIndex],
            cellTpl = me.cellTpl,
            enableTextSelection = column.enableTextSelection,
            value, clsInsertPoint,
            lastFocused = me.navigationModel.getPosition();

        // Only use the view's setting if it's not been overridden on the column
        if (enableTextSelection == null) {
            enableTextSelection = me.enableTextSelection;
        }

        cellValues.record = record;
        cellValues.column = column;
        cellValues.recordIndex = recordIndex;
        cellValues.rowIndex = rowIndex;
        cellValues.columnIndex = cellValues.cellIndex = columnIndex;
        cellValues.align = column.textAlign;
        cellValues.innerCls = column.innerCls;
        cellValues.tdCls = cellValues.tdStyle = cellValues.tdAttr = cellValues.style = "";
        cellValues.unselectableAttr = enableTextSelection ? '' : 'unselectable="on"';

        // Begin setup of classes to add to cell
        classes[1] = column.getCellId();

        // On IE8, array[len] = 'foo' is twice as fast as array.push('foo')
        // So keep an insertion point and use assignment to help IE!
        clsInsertPoint = 2;

        if (renderer && renderer.call) {
            // Avoid expensive header index calculation (uses Array#indexOf)
            // if renderer doesn't use it.
            fullIndex = renderer.length > 4
                ? me.ownerCt.columnManager.getHeaderIndex(column)
                : columnIndex;

            value = renderer.call(
                column.usingDefaultRenderer ? column : column.scope || me.ownerCt,
                fieldValue, cellValues, record, recordIndex, fullIndex, me.dataSource, me
            );

            if (cellValues.css) {
                // This warning attribute is used by the compat layer
                // TODO: remove when compat layer becomes deprecated
                record.cssWarning = true;
                cellValues.tdCls += ' ' + cellValues.css;
                cellValues.css = null;
            }

            // Add any tdCls which was added to the cellValues by the renderer.
            if (cellValues.tdCls) {
                classes[clsInsertPoint++] = cellValues.tdCls;
            }
        }
        else {
            value = fieldValue;
        }

        cellValues.value = (value == null || value.length === 0) ? column.emptyCellText : value;

        if (column.tdCls) {
            classes[clsInsertPoint++] = column.tdCls;
        }

        if (me.markDirty && record.dirty && record.isModified(column.dataIndex)) {
            classes[clsInsertPoint++] = me.dirtyCls;

            if (column.dirtyTextElementId) {
                cellValues.tdAttr = (cellValues.tdAttr ? cellValues.tdAttr + ' ' : '') +
                                    'aria-describedby="' + column.dirtyTextElementId + '"';
            }
        }

        if (column.isFirstVisible) {
            classes[clsInsertPoint++] = me.firstCls;
        }

        if (column.isLastVisible) {
            classes[clsInsertPoint++] = me.lastCls;
        }

        if (!enableTextSelection) {
            classes[clsInsertPoint++] = me.unselectableCls;
        }

        if (selModel && (selModel.isCellModel || selModel.isSpreadsheetModel) &&
            selModel.isCellSelected(me, recordIndex, column)) {
            classes[clsInsertPoint++] = me.selectedCellCls;
        }

        if (lastFocused && lastFocused.record.id === record.id && lastFocused.column === column) {
            classes[clsInsertPoint++] = me.focusedItemCls;
        }

        // Chop back array to only what we've set
        classes.length = clsInsertPoint;

        cellValues.tdCls = classes.join(' ');

        cellTpl.applyOut(cellValues, out);

        // Dereference objects since cellValues is a persistent var in the XTemplate's scope chain
        cellValues.column = cellValues.record = null;
    },

    /**
     * Returns the table row given the passed Record, or index or node.
     * @param {HTMLElement/String/Number/Ext.data.Model} nodeInfo The node or record, or row index.
     * to return the top level row.
     * @return {HTMLElement} The node or null if it wasn't found
     */
    getRow: function(nodeInfo) {
        var me = this,
            rowSelector = me.rowSelector;

        if ((!nodeInfo && nodeInfo !== 0) || !me.rendered) {
            return null;
        }

        // An id
        if (Ext.isString(nodeInfo)) {
            nodeInfo = Ext.getDom(nodeInfo);

            return nodeInfo && nodeInfo.querySelectorAll(rowSelector)[0];
        }

        // Row index
        if (Ext.isNumber(nodeInfo)) {
            nodeInfo = me.all.item(nodeInfo, true);

            return nodeInfo && nodeInfo.querySelectorAll(rowSelector)[0];
        }

        // Record
        if (nodeInfo.isModel) {
            return me.getRowByRecord(nodeInfo);
        }

        // If we were passed an event, use its target
        nodeInfo = Ext.fly(nodeInfo.target || nodeInfo);

        // Passed an item, go down and get the row
        if (nodeInfo.is(me.itemSelector)) {
            return me.getRowFromItem(nodeInfo);
        }

        // Passed a child element of a row
        return nodeInfo.findParent(rowSelector, me.getTargetEl()); // already an HTMLElement
    },

    getRowId: function(record) {
        return this.id + '-record-' + record.internalId;
    },

    constructRowId: function(internalId) {
        return this.id + '-record-' + internalId;
    },

    getNodeById: function(id) {
        id = this.constructRowId(id);

        return this.retrieveNode(id, false);
    },

    getRowById: function(id) {
        id = this.constructRowId(id);

        return this.retrieveNode(id, true);
    },

    getNodeByRecord: function(record) {
        return this.retrieveNode(this.getRowId(record), false);
    },

    getRowByRecord: function(record) {
        return this.retrieveNode(this.getRowId(record), true);
    },

    getRowFromItem: function(item) {
        var rows = Ext.getDom(item).tBodies[0].childNodes,
            len = rows.length,
            i;

        for (i = 0; i < len; i++) {
            if (Ext.fly(rows[i]).is(this.rowSelector)) {
                return rows[i];
            }
        }
    },

    retrieveNode: function(id, dataRow) {
        var result = this.el.getById(id, true);

        if (dataRow && result) {
            return result.querySelector(this.rowSelector, true);
        }

        return result;
    },

    // Links back from grid rows are installed by the XTemplate as data attributes
    updateIndexes: Ext.emptyFn,

    // Outer table
    bodySelector: 'div.' + Ext.baseCSSPrefix + 'grid-item-container',

    // Element which contains rows
    nodeContainerSelector: 'div.' + Ext.baseCSSPrefix + 'grid-item-container',

    // view item. This wraps a data row
    itemSelector: 'table.' + Ext.baseCSSPrefix + 'grid-item',

    // Grid row which contains cells as opposed to wrapping item.
    rowSelector: 'tr.' + Ext.baseCSSPrefix + 'grid-row',

    // cell
    cellSelector: 'td.' + Ext.baseCSSPrefix + 'grid-cell',

    // Select column sizers and cells.
    // This may target `<COL>` elements as well as `<TD>` elements
    // `<COLGROUP>` element is inserted if the first row does not have the regular cell pattern
    // (eg is a colspanning group header row)
    sizerSelector: '.' + Ext.baseCSSPrefix + 'grid-cell',

    innerSelector: 'div.' + Ext.baseCSSPrefix + 'grid-cell-inner',

    /**
     * Returns a CSS selector which selects the outermost element(s) in this view.
     */
    getBodySelector: function() {
        return this.bodySelector;
    },

    /**
     * Returns a CSS selector which selects the element(s) which define the width of a column.
     *
     * This is used by the {@link Ext.view.TableLayout} when resizing columns.
     *
     */
    getColumnSizerSelector: function(header) {
        var selector = this.sizerSelector + '-' + header.getItemId();

        return 'td' + selector + ',col' + selector;
    },

    /**
     * Returns a CSS selector which selects items of the view rendered by the outerRowTpl
     */
    getItemSelector: function() {
        return this.itemSelector;
    },

    /**
     * Returns a CSS selector which selects a particular column if the desired header is passed,
     * or a general cell selector is no parameter is passed.
     *
     * @param {Ext.grid.column.Column} [header] The column for which to return the selector. If
     * omitted, the general cell selector which matches **ant cell** will be returned.
     *
     */
    getCellSelector: function(header) {
        return header ? header.getCellSelector() : this.cellSelector;
    },

    /*
     * Returns a CSS selector which selects the content carrying element within cells.
     */
    getCellInnerSelector: function(header) {
        return this.getCellSelector(header) + ' ' + this.innerSelector;
    },

    /**
     * Adds a CSS Class to a specific row.
     * @param {HTMLElement/String/Number/Ext.data.Model} rowInfo An HTMLElement,
     * index or instance of a model representing this row
     * @param {String} cls
     */
    addRowCls: function(rowInfo, cls) {
        var row = this.getRow(rowInfo);

        if (row) {
            Ext.fly(row).addCls(cls);
        }
    },

    /**
     * Removes a CSS Class from a specific row.
     * @param {HTMLElement/String/Number/Ext.data.Model} rowInfo An HTMLElement,
     * index or instance of a model representing this row
     * @param {String} cls
     */
    removeRowCls: function(rowInfo, cls) {
        var row = this.getRow(rowInfo);

        if (row) {
            Ext.fly(row).removeCls(cls);
        }
    },

    // GridSelectionModel invokes onRowSelect as selection changes
    onRowSelect: function(rowIdx) {
        var me = this,
            rowNode;

        me.addItemCls(rowIdx, me.selectedItemCls);

        rowNode = me.getRow(rowIdx);

        if (rowNode) {
            rowNode.setAttribute('aria-selected', true);
        }

        //<feature legacyBrowser>
        if (Ext.isIE8) {
            me.repaintBorder(rowIdx + 1);
        }
        //</feature>
    },

    // GridSelectionModel invokes onRowDeselect as selection changes
    onRowDeselect: function(rowIdx) {
        var me = this,
            rowNode;

        me.removeItemCls(rowIdx, me.selectedItemCls);

        rowNode = me.getRow(rowIdx);

        if (rowNode) {
            rowNode.removeAttribute('aria-selected');
        }

        //<feature legacyBrowser>
        if (Ext.isIE8) {
            me.repaintBorder(rowIdx + 1);
        }
        //</feature>
    },

    onCellSelect: function(position) {
        var cell = this.getCellByPosition(position, true);

        if (cell) {
            Ext.fly(cell).addCls(this.selectedCellCls);
            cell.setAttribute('aria-selected', true);
        }
    },

    onCellDeselect: function(position) {
        var cell = this.getCellByPosition(position, true);

        if (cell) {
            Ext.fly(cell).removeCls(this.selectedCellCls);
            cell.removeAttribute('aria-selected');
        }
    },

    // Old API. Used by tests now to test coercion of navigation from hidden column
    // to closest visible. Position.column includes all columns including hidden ones.
    getCellInclusive: function(position, returnDom) {
        var header, row, cell;

        if (position) {
            row = this.getRow(position.row);
            header = this.ownerCt.getColumnManager().getHeaderAtIndex(position.column);

            if (header && row) {
                cell = row.querySelector(this.getCellSelector(header));

                return returnDom ? cell : Ext.get(cell);
            }
        }

        return false;
    },

    getColumnByPosition: function(position) {
        var view, column;

        if (position) {
            column = position.column;

            // Previous column can be already destroyed via reconfigure
            if (column && !column.destroyed && column.isColumn) {
                return column;
            }
            else {
                view = position.view || this;

                // Column can be a number
                column = typeof column === 'number' ? column : position.colIdx;

                return view.getVisibleColumnManager().getHeaderAtIndex(column);
            }
        }

        return false;
    },

    getCellByPosition: function(position, returnDom) {
        var view, row, header, cell;

        if (position) {
            view = position.view || this;
            row = view.getRow(position.record || position.row);

            header = view.getColumnByPosition(position);

            if (header && row) {
                cell = row.querySelector(view.getCellSelector(header));

                return returnDom ? cell : Ext.get(cell);
            }
        }

        return false;
    },

    onFocusEnter: function(e) {
    // We need to react in a correct way to focus entering the TableView.
    // Much of this is based upon http://www.w3.org/TR/wai-aria-practices-1.1/#h-grid
    // specifically: "Once focus has been moved inside the grid, subsequent tab presses
    // that re-enter the grid shall return focus to the cell that last held focus."
    //
    // If an interior element is being focused, then if it is a cell, we enter navigable mode
    // at that cell.
    // If an interior element *within* a cell is being focused, we enter actionable mode
    // at that cell and focus that element.
    // If just the view itself is being focused we focus the lastFocused CellContext. 
    // This is the last cell position which the user navigated to in any mode, actionable
    // or navigable. It is maintained during navigation in navigable mode.
    // It is set upon focus leave if focus left during actionable mode - set to actionPosition.
    // actionPosition is cleared when actionable mode is exited.
    //
    // The important context is lastFocused.
        var me = this,
            fromComponent = e.fromComponent,
            navigationModel = me.getNavigationModel(),
            focusPosition, cell, focusTarget;

        // If a mousedown listener has synchronously focused an internal element
        // from outside and proceeded to process focus consequences, then the impending focusenter
        // MUST NOT process focus consequences.
        // See Ext.grid.NavigationModel#onCellMouseDown
        if (me.containsFocus) {
            return Ext.Component.prototype.onFocusEnter.call(me, e);
        }

        // FocusEnter while in actionable mode.
        if (me.actionableMode) {
            // If we own the actionPosition it must be due to a setActionPosition call
            // setting the actionPosition and then focusing the actionable element.
            // We need to disable view outer el focusing while focus is inside.
            if (me.actionPosition) {
                me.el.dom.setAttribute('tabIndex', '-1');
                me.cellFocused = true;

                return;
            }

            // Must have swapped sides of a lockable.
            // We don't know what we're focusing into yet.
            // So exit actionable mode.
            // We could be focusing a cell, in which case navigable mode is correct.
            // If we are focusing an interior element that is not a cell,
            // we will enter actionable mode.
            me.ownerGrid.setActionableMode(false);
        }

        // The underlying DOM event
        e = e.event;

        // We can only focus if there are rows in the row cache to focus *and* records
        // in the store to back them. Buffered Stores can produce a state where
        // the view is not cleared on the leading end of a reload operation, but the
        // store can be empty.
        if (!me.cellFocused && me.all.getCount() && me.dataSource.getCount()) {
            focusTarget = e.getTarget();

            // The View's el has been focused.
            // We now have to decide which cell to focus
            if (focusTarget === me.el.dom) {
                // This lastFocused value is set on mousedown on the scrollbar in IE/Edge.
                // Those browsers focus the element on mousedown on its scrollbar
                // which is not what we want, so throw focus back in this
                // situation.
                // See Ext.view.navigationModel for this being set.
                if (me.lastFocused === 'scrollbar') {

                    if (e.relatedTarget && e.relatedTarget.focus) {
                        e.relatedTarget.focus();
                    }

                    return;
                }

                focusPosition = me.getDefaultFocusPosition(fromComponent);

                // Not a descendant which we allow to carry focus. Focus the view el.
                if (!focusPosition) {
                    e.stopEvent();
                    me.el.focus();

                    return;
                }

                // We are entering navigable mode, so we have a focusPosition but no focusTarget
                focusTarget = null;
            }
            // Hit the invisible focus guard. This mean SHIT+TAB back into the grid.
            // Focus last cell.
            else if (focusTarget === me.tabGuardEl) {
                focusPosition = new Ext.grid.CellContext(me).setPosition(
                    me.all.endIndex, me.getVisibleColumnManager().getColumns().length - 1
                );

                focusTarget = null;
            }
            // Now there are just two valid choices.
            // Focused a cell, or an interior element within a cell.
            // eslint-disable-next-line no-cond-assign
            else if (cell = e.getTarget(me.getCellSelector())) {
                // Programmatic focus of a cell...
                if (focusTarget === cell) {
                    // We are entering navigable mode, so we have a focusPosition but no focusTarget
                    focusPosition = new Ext.grid.CellContext(me).setPosition(
                        me.getRecord(focusTarget), me.getHeaderByCell(cell)
                    );

                    focusTarget = null;
                }
                // If what is being focused an interior element, but is not a cell, we plan to enter
                // actionable mode. This will happen when an ActionColumn invokes a modal window
                // and that window is dismissed leading to automatic focus of the previously focused
                // element. This also happens when SHIFT+TAB moves back towards the view.
                // It navigated to the last tabbable element.
                // Testing whether the focusTarget isFocusable is a fix for IE. It can sometimes
                // fire a focus event with the .x-scroll-scroller as the target
                else if (focusTarget && Ext.fly(focusTarget).isFocusable() &&
                         me.el.contains(focusTarget)) {
                    // We are entering actionable mode, so we have a focusPosition and a focusTarget
                    focusPosition = new Ext.grid.CellContext(me).setPosition(
                        me.getRecord(focusTarget), me.getHeaderByCell(cell)
                    );
                }
            }
        }
        // We must exit from the above code block with focusPosition set to a CellContext
        // which is going to be either the navigable or actionable position. If focusPosition
        // is null, we are not focusing the view.
        //
        // IF we are entering actionable mode, then focusTarget will be set to an internal
        // focusable element within the cell referenced by focusPosition.

        // We calculated a cell to focus on. Either from the target element,
        // or the last focused position
        if (focusPosition) {
            // Disable tabbability of elements within this view.
            me.toggleChildrenTabbability(false);

            // If we fall through to here with a focusTarget, it means that it's an internal
            // focusable element and we request to enter actionable mode at the focusPosition
            if (focusTarget) {
                // Tell actionable mode which element we want to focus.
                // By default it focuses the first focusable in the cell.
                focusPosition.target = focusTarget;

                // If we successfully entered actionable mode at the requested position,
                // prevent entering navigable mode by nulling the focusPosition, and focus
                // the intended target (setActionableMode will have focused the *first* tabbable
                // in the cell). If we were unsuccessful, then we must proceed with focusPosition
                // set in order to enter navigable mode here.
                if (me.ownerGrid.setActionableMode(true, focusPosition)) {
                    focusPosition = null;
                }
            }

            // Test again here.
            // If we successfully entered actionable mode, this will be null.
            // If the attempt failed, it should fall back to navigable mode.
            if (focusPosition) {
                navigationModel.setPosition(focusPosition, null, e, null, true);
            }

            // We now contain focus if that was successful
            me.cellFocused = me.el.contains(Ext.Element.getActiveElement());

            if (me.cellFocused) {
                me.el.dom.setAttribute('tabIndex', '-1');
            }
        }

        // Skip the AbstractView's implementation.
        // It initializes its NavModel differently.
        Ext.Component.prototype.onFocusEnter.call(me, e);
    },

    onFocusLeave: function(e) {
        var me = this,
            isLeavingGrid;

        // If the blur was caused by a refresh, we expect things to be refocused.
        if (!me.destroying && !me.refreshing) {
            // See if focus is really leaving the grid.
            // If we have a locking partner, and focus is going to that, we're NOT leaving the grid.
            isLeavingGrid = !me.lockingPartner || !e.toComponent ||
                            (e.toComponent !== me.lockingPartner &&
                            !me.lockingPartner.isAncestor(e.toComponent));

            // Ignore this event if we do not actually contain focus.
            // CellEditors are rendered into the view's encapsulating element,
            // So focusleave will fire when they are programmatically blurred.
            // We will not have focus at that point.
            if (me.cellFocused) {

                // Blur the focused cell unless we are navigating into a locking partner,
                // in which case, the focus of that will setPosition to the target
                // without an intervening position to null.
                if (isLeavingGrid) {
                    me.getNavigationModel().setPosition(null, null, e.event, null, true);
                }

                me.cellFocused = false;
                me.focusEl = me.el;
                me.focusEl.dom.setAttribute('tabIndex', 0);
            }

            // Exiting to outside, switch back to navigation mode before clearing
            // the navigation position so that the current position's row
            // can have its tabbability saved.
            if (isLeavingGrid) {
                if (me.ownerGrid.actionableMode) {
                    // If focus is thrown back in with no specific target, it should go back into
                    // navigable mode at this position.
                    // See http://www.w3.org/TR/wai-aria-practices-1.1/#h-grid
                    // "Once focus has been moved inside the grid, subsequent tab presses
                    // that re-enter the grid shall return focus to the cell that last held focus."
                    me.lastFocused = me.actionPosition;
                    me.ownerGrid.setActionableMode(false);
                }
            }
            else {
                me.actionPosition = null;
            }

            // Skip the AbstractView's implementation.
            Ext.Component.prototype.onFocusLeave.call(me, e);
        }
    },

    // GridSelectionModel invokes onRowFocus to 'highlight'
    // the last row focused
    onRowFocus: function(rowIdx, highlight, suppressFocus) {
        var me = this;

        if (highlight) {
            me.addItemCls(rowIdx, me.focusedItemCls);

            if (!suppressFocus) {
                me.focusRow(rowIdx);
            }
            // this.el.dom.setAttribute('aria-activedescendant', row.id);
        }
        else {
            me.removeItemCls(rowIdx, me.focusedItemCls);
        }

        //<feature legacyBrowser>
        if (Ext.isIE8) {
            me.repaintBorder(rowIdx + 1);
        }
        //</feature>
    },

    /**
     * Focuses a particular row and brings it into view. Will fire the rowfocus event.
     * @param {HTMLElement/String/Number/Ext.data.Model} row An HTMLElement template node,
     * index of a template node, the id of a template node or the
     * @param {Boolean/Number} [delay] Delay the focus this number of milliseconds
     * (true for 10 milliseconds). record associated with the node.
     */
    focusRow: function(row, delay) {
        var me = this,
            focusTask = me.getFocusTask();

        if (delay) {
            focusTask.delay(Ext.isNumber(delay) ? delay : 10, me.focusRow, me, [row, false]);

            return;
        }

        // An immediate focus call must cancel any outstanding delayed focus calls.
        focusTask.cancel();

        // Do not attempt to focus if hidden or within collapsed Panel.
        if (me.isVisible(true)) {
            me.getNavigationModel().setPosition(me.getRecord(row));
        }
    },

    // Override the version in Ext.view.View because the focusable elements are the grid cells.
    /**
     * Focuses a particular row and brings it into view. Will fire the rowfocus event.
     * @param {HTMLElement/String/Number/Ext.data.Model} row An HTMLElement template node,
     * index of a template node, the id of a template node or the
     * @param {Boolean/Number} [delay] Delay the focus this number of milliseconds
     * (true for 10 milliseconds).
     * record associated with the node.
     */
    focusNode: function(row, delay) {
        this.focusRow(row, delay);
    },

    scrollRowIntoView: function(row, animate) {
        row = this.getRow(row);

        if (row) {
            this.scrollElIntoView(row, false, animate);
        }
    },

    /**
     * Focuses a particular cell and brings it into view. Will fire the rowfocus event.
     * @param {Ext.grid.CellContext} position The cell to select
     * @param {Boolean/Number} [delay] Delay the focus this number of milliseconds
     * (true for 10 milliseconds).
     */
    focusCell: function(position, delay) {
        var me = this,
            focusTask = me.getFocusTask(),
            cell; // eslint-disable-line no-unused-vars

        if (delay) {
            focusTask.delay(Ext.isNumber(delay) ? delay : 10, me.focusCell, me, [position, false]);

            return;
        }

        // An immediate focus call must cancel any outstanding delayed focus calls.
        focusTask.cancel();

        // Do not attempt to focus if hidden or within collapsed Panel
        // Maintainer: Note that to avoid an unnecessary call to me.getCellByPosition
        // if not visible, or another, nested if test, the assignment of the cell var
        // is embedded inside the condition expression.
        // eslint-disable-next-line no-cond-assign
        if (me.isVisible(true) && (cell = me.getCellByPosition(position))) {
            me.getNavigationModel().setPosition(position);
        }
    },

    findFocusPosition: function(from, currentPosition, forward, keyEvent) {
        var me = this,
            cell = currentPosition.cellElement,
            actionables = me.ownerGrid.actionables,
            len = actionables.length,
            position, tabbableChildren, focusTarget, i;

        position = currentPosition.clone();
        tabbableChildren = Ext.fly(cell).findTabbableElements();

        // Find the next or previous tabbable in this cell.
        focusTarget =
            tabbableChildren[Ext.Array.indexOf(tabbableChildren, from) + (forward ? 1 : -1)];

        // If we are exiting the cell:
        // Find next cell if possible, otherwise, we are exiting the row
        while (!focusTarget && (cell = cell[forward ? 'nextSibling' : 'previousSibling'])) {
            // Move position pointer to point to the new cell
            position.setColumn(me.getHeaderByCell(cell));

            // Inform all Actionables that we intend to activate this cell.
            // If they are actionable, they will show/insert tabbable elements in this cell.
            for (i = 0; i < len; i++) {
                actionables[i].activateCell(position);
            }

            // In case any code in the cell activation churned
            // the grid DOM and the position got refreshed.
            // eg: edit handler on previously active editor.
            cell = position.getCell(true);

            // If there are now tabbable elements in this cell (entering a row restores tabbability)
            // and Actionables also show/insert tabbables), then focus in the current direction.
            if (cell && (tabbableChildren = Ext.fly(cell).findTabbableElements()).length) {
                focusTarget = tabbableChildren[forward ? 0 : tabbableChildren.length - 1];
            }
        }

        return {
            target: focusTarget,
            position: position
        };
    },

    getDefaultFocusPosition: function(fromComponent) {
        var me = this,
            store = me.dataSource,
            focusPosition = me.lastFocused,
            newPosition = new Ext.grid.CellContext(me).setPosition(0, 0),
            targetCell, scroller;

        if (fromComponent) {
            // Tabbing in from one of our column headers; the user will expect to land
            // in that column.
            // Unless it is configured cellFocusable: false
            if (fromComponent.isColumn && fromComponent.cellFocusable !== false) {
                if (!focusPosition) {
                    focusPosition = newPosition;
                }

                focusPosition.setColumn(fromComponent);
                focusPosition.setView(fromComponent.getView());
            }
            // Tabbing in from the neighbouring TableView (eg, locking).
            // Go to column zero, same record
            else if (fromComponent.isTableView && fromComponent.lastFocused &&
                     fromComponent.ownerGrid === me.ownerGrid) {
                focusPosition = new Ext.grid.CellContext(me).setPosition(
                    fromComponent.lastFocused.record, 0
                );
            }
        }

        // We found a position from the "fromComponent, or there was a previously focused context
        if (focusPosition) {
            scroller = me.getScrollable();

            // Record is not in the store, or not in the rendered block.
            // Fall back to using the same row index.
            if (!store.contains(focusPosition.record) ||
                (scroller && !scroller.isInView(focusPosition.getRow(true)).y)) {
                focusPosition.setRow(
                    store.getAt(Math.min(focusPosition.rowIdx, store.getCount() - 1))
                );
            }
        }
        // All else fails, find the first focusable cell.
        else {
            focusPosition = newPosition;

            // Find the first focusable cell.
            targetCell = me.el.dom.querySelector(me.getCellSelector() + '[tabIndex="-1"]');

            if (targetCell) {
                focusPosition.setPosition(me.getRecord(targetCell), me.getHeaderByCell(targetCell));
            }
            // All visible columns are cellFocusable: false
            else {
                focusPosition = null;
            }
        }

        return focusPosition;
    },

    getLastFocused: function() {
        var me = this,
            lastFocused = me.lastFocused;

        if (lastFocused && lastFocused.record && lastFocused.column) {

            // If the last focused record or column has gone away, or the record
            // is no longer in the visible rendered block, we have no lastFocused
            if (me.dataSource.indexOf(lastFocused.record) !== -1 &&
                me.getVisibleColumnManager().indexOf(lastFocused.column) !== -1 &&
                me.getNode(lastFocused.record)) {
                return lastFocused;
            }
        }
    },

    scrollCellIntoView: function(cell, animate) {
        if (cell.isCellContext) {
            cell = this.getCellByPosition(cell);
        }

        if (cell) {
            this.scrollElIntoView(cell, null, animate);
        }
    },

    scrollElIntoView: function(el, hscroll, animate) {
        var scroller = this.getScrollable();

        if (scroller) {
            scroller.ensureVisible(el, {
                animation: animate,
                x: hscroll
            });
        }
    },

    syncRowHeightBegin: function() {
        var me = this,
            itemEls = me.all,
            ln = itemEls.count,
            synchronizer = [],
            RowSynchronizer = Ext.grid.locking.RowSynchronizer,
            i, j, rowSync;

        for (i = 0, j = itemEls.startIndex; i < ln; i++, j++) {
            synchronizer[i] = rowSync = new RowSynchronizer(me, itemEls.elements[j]);
            rowSync.reset();
        }

        return synchronizer;
    },

    syncRowHeightClear: function(synchronizer) {
        var me = this,
            itemEls = me.all,
            ln = itemEls.count,
            i;

        for (i = 0; i < ln; i++) {
            synchronizer[i].reset();
        }
    },

    syncRowHeightMeasure: function(synchronizer) {
        var ln = synchronizer.length,
            i;

        for (i = 0; i < ln; i++) {
            synchronizer[i].measure();
        }
    },

    syncRowHeightFinish: function(synchronizer, otherSynchronizer) {
        var ln = synchronizer.length,
            bufferedRenderer = this.bufferedRenderer,
            i;

        for (i = 0; i < ln; i++) {
            synchronizer[i].finish(otherSynchronizer[i]);
        }

        // Ensure that both BufferedRenderers have the same idea about scroll range and row height
        if (bufferedRenderer) {
            bufferedRenderer.syncRowHeightsFinish();
        }
    },

    refreshNode: function(record) {
        // Override from AbstractView.
        // Refreshing a node must force all columns to be updated.
        if (Ext.isNumber(record)) {
            record = this.store.getAt(record);
        }

        // For a TableView, refreshNode has to pass the "allColumns" flag to the handleUpdate
        // method to indicate that the whole column set must be rendered in a new row, and that
        // cell updaters may not be used.
        this.handleUpdate(this.dataSource, record, null, null, null, true);
    },

    handleUpdate: function(store, record, operation, changedFieldNames, info, allColumns) {
        var me = this,
            recordIndex = me.store.indexOf(record),
            rowTpl = me.rowTpl,
            markDirty = me.markDirty,
            dirtyCls = me.dirtyCls,
            columnsToUpdate = [],
            hasVariableRowHeight = me.variableRowHeight,
            updateTypeFlags = 0,
            ownerCt = me.ownerCt,
            cellFly = me.cellFly || (me.self.prototype.cellFly = new Ext.dom.Fly()),
            oldItemDom, oldDataRow, newItemDom, newAttrs, attLen, attName, attrIndex,
            overItemCls, columns, column, len, i, cellUpdateFlag, cell, fieldName, value,
            clearDirty, defaultRenderer, scope, elData, emptyValue;

        operation = operation || Ext.data.Model.EDIT;
        clearDirty = operation !== Ext.data.Model.EDIT;

        if (me.viewReady) {
            // Some features might need to know that we're updating
            me.updatingRows = true;

            // Table row being updated
            oldItemDom = me.getNodeByRecord(record);

            // Row might not be rendered due to buffered rendering
            // or being part of a collapsed group...
            if (oldItemDom) {
                // refreshNode can be called on a collapsed placeholder record.
                // Update it from a new rendering.
                if (record.isCollapsedPlaceholder) {
                    Ext.fly(oldItemDom).syncContent(
                        me.createRowElement(record, me.indexOfRow(record))
                    );

                    return;
                }

                overItemCls = me.overItemCls;
                columns = me.ownerCt.getVisibleColumnManager().getColumns();

                // A refreshNode operation must update all columns, and must do a full rerender.
                // Set the flags appropriately.
                if (allColumns) {
                    columnsToUpdate = columns;
                    updateTypeFlags = 1;
                }
                else {
                    // Collect an array of the columns which must be updated.
                    // If the field at this column index was changed, or column has a custom
                    // renderer (which means value could rely on any other changed field)
                    // we include the column.
                    for (i = 0, len = columns.length; i < len; i++) {
                        column = columns[i];

                        // We are not going to update the cell, but we still need
                        // to mark it as dirty.
                        if (column.preventUpdate) {
                            cell = oldItemDom.querySelector(column.getCellSelector());

                            // Mark the field's dirty status if we are configured to do so
                            // (defaults to true)
                            if (cell && !clearDirty && markDirty) {
                                cellFly.attach(cell);

                                if (record.isModified(column.dataIndex)) {
                                    cellFly.addCls(dirtyCls);

                                    if (column.dirtyTextElementId) {
                                        cell.setAttribute('aria-describedby',
                                                          column.dirtyTextElementId);
                                    }
                                }
                                else {
                                    cellFly.removeCls(dirtyCls);
                                    cell.removeAttribute('aria-describedby');
                                }
                            }
                        }
                        else {
                            // 0 = Column doesn't need update.
                            // 1 = Column needs update, and renderer has > 1 argument;
                            // We need to render a whole new HTML item.
                            // 2 = Column needs update, but renderer has 1 argument
                            // or column uses an updater.
                            cellUpdateFlag = me.shouldUpdateCell(record, column, changedFieldNames);

                            if (cellUpdateFlag) {
                                // Track if any of the updating columns yields a flag
                                // with the 1 bit set. This means that there is a custom renderer
                                // involved and a new TableView item will need rendering.
                                updateTypeFlags = updateTypeFlags | cellUpdateFlag;

                                columnsToUpdate[columnsToUpdate.length] = column;
                                hasVariableRowHeight = hasVariableRowHeight ||
                                                       column.variableRowHeight;
                            }
                        }
                    }
                }

                // Give CellEditors or other transient in-cell items a chance to get out of the way
                // if there are in the cells destined for update.
                if (me.hasListeners.beforeitemupdate) {
                    me.fireEvent(
                        'beforeitemupdate', record, recordIndex, oldItemDom, columnsToUpdate
                    );
                }

                // If there's no data row (some other rowTpl has been used; eg group header)
                // or we have a getRowClass
                // or one or more columns has a custom renderer
                // or there's more than one <TR>, we must use the full render pathway
                // to create a whole new TableView item
                if (me.getRowClass || !me.getRowFromItem(oldItemDom) ||
                    (updateTypeFlags & 1) || (oldItemDom.tBodies[0].childNodes.length > 1)) {

                    elData = oldItemDom._extData;
                    newItemDom =
                        me.createRowElement(record, me.indexOfRow(record), columnsToUpdate);

                    if (Ext.fly(oldItemDom, '_internal').hasCls(overItemCls)) {
                        Ext.fly(newItemDom).addCls(overItemCls);
                    }

                    // Copy new row attributes across. Use IE-specific method if possible.
                    // In IE10, there is a problem where the className will not get updated
                    // in the view, even though the className on the dom element is correct.
                    // See EXTJSIV-9462
                    if (Ext.isIE9m && oldItemDom.mergeAttributes) {
                        oldItemDom.mergeAttributes(newItemDom, true);
                    }
                    else {
                        newAttrs = newItemDom.attributes;
                        attLen = newAttrs.length;

                        for (attrIndex = 0; attrIndex < attLen; attrIndex++) {
                            attName = newAttrs[attrIndex].name;
                            value = newAttrs[attrIndex].value;

                            if (attName !== 'id' && oldItemDom.getAttribute(attName) !== value) {
                                oldItemDom.setAttribute(attName, value);
                            }
                        }
                    }

                    // The element's data is no longer synchronized. We just overwrite it in the DOM
                    if (elData) {
                        elData.isSynchronized = false;
                    }

                    // If we have columns which may *need* updating (think locked side
                    // of lockable grid with all columns unlocked) and the changed record is within
                    // our view, then update the view.
                    if (columns.length && (oldDataRow = me.getRow(oldItemDom))) {
                        me.updateColumns(
                            oldDataRow, newItemDom.querySelector(me.rowSelector), columnsToUpdate,
                            record
                        );
                    }

                    // Loop thru all of rowTpls asking them to sync the content
                    // they are responsible for if any.
                    while (rowTpl) {
                        if (rowTpl.syncContent) {
                            // *IF* we are selectively updating columns (have been passed
                            // changedFieldNames), then pass the column set, else pass null,
                            // and it will sync all content.
                            // eslint-disable-next-line max-len
                            if (rowTpl.syncContent(oldItemDom, newItemDom, changedFieldNames ? columnsToUpdate : null) === false) {
                                break;
                            }
                        }

                        rowTpl = rowTpl.nextTpl;
                    }
                }
                // No custom renderers found in columns to be updated,
                // we can simply update the existing cells.
                else {
                    // Loop through columns which need updating.
                    for (i = 0, len = columnsToUpdate.length; i < len; i++) {
                        column = columnsToUpdate[i];

                        // The dataIndex of the column is the field name
                        fieldName = column.dataIndex;

                        value = record.get(fieldName);
                        cell = oldItemDom.querySelector(column.getCellSelector());
                        cellFly.attach(cell);

                        // Mark the field's dirty status if we are configured to do so
                        // (defaults to true)
                        if (!clearDirty && markDirty) {
                            if (record.isModified(column.dataIndex)) {
                                cellFly.addCls(dirtyCls);

                                if (column.dirtyTextElementId) {
                                    cell.setAttribute('aria-describedby',
                                                      column.dirtyTextElementId);
                                }
                            }
                            else {
                                cellFly.removeCls(dirtyCls);
                                cell.removeAttribute('aria-describedby');
                            }
                        }

                        defaultRenderer = column.usingDefaultRenderer;
                        scope = defaultRenderer ? column : column.scope;

                        // Call the column updater which gets passed the TD element
                        if (column.updater) {
                            Ext.callback(column.updater, scope,
                                         [cell, value, record, me, me.dataSource],
                                         0, column, ownerCt
                            );
                        }
                        else {
                            if (column.renderer) {
                                value = Ext.callback(
                                    column.renderer, scope,
                                    [value, null, record, 0, 0, me.dataSource, me],
                                    0, column, ownerCt
                                );
                            }

                            emptyValue = value == null || value.length === 0;
                            value = emptyValue ? column.emptyCellText : value;

                            // Update the value of the cell's inner in the best way.
                            // We only use innerHTML of the cell's inner DIV if the renderer
                            // produces HTML. Otherwise we change the value of the single text node
                            // within the inner DIV. The emptyValue may be HTML,
                            // typically defaults to &#160;
                            if (column.producesHTML || emptyValue) {
                                cell.querySelector(me.innerSelector).innerHTML = value;
                            }
                            else {
                                cell.querySelector(me.innerSelector).childNodes[0].data = value;
                            }
                        }

                        // Add the highlight class if there is one
                        if (me.highlightClass) {
                            Ext.fly(cell).addCls(me.highlightClass);

                            // Start up a DelayedTask which will purge the changedCells stack,
                            // removing the highlight class after the expiration time
                            if (!me.changedCells) {
                                me.self.prototype.changedCells = [];

                                me.prototype.clearChangedTask =
                                    new Ext.util.DelayedTask(me.clearChangedCells, me.prototype);

                                me.clearChangedTask.delay(me.unhighlightDelay);
                            }

                            // Post a changed cell to the stack along with expiration time
                            me.changedCells.push({
                                cell: cell,
                                cls: me.highlightClass,
                                expires: Ext.Date.now() + 1000
                            });
                        }
                    }
                }

                // If we have a commit or a reject, some fields may no longer be dirty but may
                // not appear in the modified field names.
                // Remove all the dirty class here to be sure.
                if (clearDirty && markDirty && !record.dirty) {
                    Ext.fly(oldItemDom, '_internal')
                        .select('.' + dirtyCls)
                        .removeCls(dirtyCls)
                        .set({ 'aria-describedby': undefined });
                }

                // Coalesce any layouts which happen due to any itemupdate handlers
                // (eg Widget columns) with the final refreshSize layout.
                if (hasVariableRowHeight) {
                    Ext.suspendLayouts();
                }

                // Since we don't actually replace the row, we need to fire the event
                // with the old row because it's the thing that is still in the DOM
                if (me.hasListeners.itemupdate) {
                    me.fireEvent('itemupdate', record, recordIndex, oldItemDom, me);
                }

                // We only need to update the layout if any of the columns can change
                // the row height.
                if (hasVariableRowHeight) {
                    // Must climb to ownerGrid in case we've only updated one field
                    // in one side of a lockable assembly. ownerGrid is always the topmost
                    // GridPanel.
                    me.ownerGrid.updateLayout();

                    // Ensure any layouts queued by itemupdate handlers
                    // and/or the refreshSize call are executed.
                    Ext.resumeLayouts(true);
                }
            }

            me.updatingRows = false;
        }
    },

    clearChangedCells: function() {
        var me = this,
            now = Ext.Date.now(),
            changedCell, i, len;

        for (i = 0, len = me.changedCells.length; i < len;) {
            changedCell = me.changedCells[i];

            if (changedCell.expires <= now) {
                Ext.fly(changedCell.cell).removeCls(changedCell.highlightClass);
                Ext.Array.erase(me.changedCells, i, 1);
                len--;
            }
            else {
                break;
            }
        }

        // Keep repeating the delay until all highlighted cells have been cleared
        if (len) {
            me.clearChangedTask.delay(me.unhighlightDelay);
        }
    },

    updateColumns: function(oldRow, newRow, columnsToUpdate, record) {
        var me = this,
            cellSelector = me.getCellSelector(),
            colCount = columnsToUpdate.length,
            newAttrs, attLen, attName, attrIndex, colIndex, column, oldCell, newCell,
            elData, value;

        // Copy new row attributes across. Use IE-specific method if possible.
        // Must do again at this level because the row DOM passed here may be the nested row
        // in a row wrap.
        if (oldRow.mergeAttributes) {
            oldRow.mergeAttributes(newRow, true);
        }
        else {
            newAttrs = newRow.attributes;
            attLen = newAttrs.length;

            for (attrIndex = 0; attrIndex < attLen; attrIndex++) {
                attName = newAttrs[attrIndex].name;
                value = newAttrs[attrIndex].value;

                if (attName !== 'id' && oldRow.getAttribute(attName) !== value) {
                    oldRow.setAttribute(attName, value);
                }
            }
        }

        // The element's data is no longer synchronized. We just overwrote it in the DOM
        elData = oldRow._extData;

        if (elData) {
            elData.isSynchronized = false;
        }

        // Replace changed cells in the existing row structure with the new version
        // from the rendered row.
        for (colIndex = 0; colIndex < colCount; colIndex++) {
            column = columnsToUpdate[colIndex];

            // Pluck out cells using the column's unique cell selector.
            // Because in a wrapped row, there may be several TD elements.
            cellSelector = me.getCellSelector(column);
            oldCell = oldRow.querySelector(cellSelector);
            newCell = newRow.querySelector(cellSelector);

            // Copy new cell attributes across.
            newAttrs = newCell.attributes;
            attLen = newAttrs.length;

            for (attrIndex = 0; attrIndex < attLen; attrIndex++) {
                attName = newAttrs[attrIndex].name;
                value = newAttrs[attrIndex].value;

                if (attName !== 'id' && oldCell.getAttribute(attName) !== value) {
                    oldCell.setAttribute(attName, value);
                }
            }

            // The element's data is no longer synchronized. We just overwrote it in the DOM
            elData = oldCell._extData;

            if (elData) {
                elData.isSynchronized = false;
            }

            // Carefully replace just the *contents* of the content bearing inner element.
            me.oldCellFly.attach(oldCell.querySelector(me.innerSelector))
                         .syncContent(newCell.querySelector(me.innerSelector));

            if (record && column.onItemAdd) {
                column.onItemAdd([record]);
            }
        }
    },

    /**
     * @private
     * Decides whether the column needs updating
     * @return {Number} 0 = Doesn't need update.
     * 1 = Column needs update, and renderer has > 1 argument; We need to render a whole new
     * HTML item.
     * 2 = Column needs update, but renderer has 1 argument or column uses an updater.
     */
    shouldUpdateCell: function(record, column, changedFieldNames) {
        return column.shouldUpdateCell(record, changedFieldNames);
    },

    /**
     * Refreshes the grid view. Sets the sort state and focuses the previously focused row.
     *
     * **Note:** This method should only be used when `bufferedRenderer` is set to `false`.
     * BufferedRender has its own methods for managing its data's state.
     */
    refresh: function() {
        var me = this;

        if (me.destroying) {
            return;
        }

        // If there are visible columns, then refresh
        if (me.getVisibleColumnManager().getColumns().length) {
            me.callParent(arguments);

            me.headerCt.setSortState();
        }
        // If no visible columns, clear the view
        else {
            if (me.refreshCounter) {
                me.clearViewEl(true);
            }

            me.addEmptyText();
        }
    },

    processContainerEvent: function(e) {
        // If we find a component & it belongs to our grid, don't fire the event.
        // For example, grid editors resolve to the parent grid
        var cmp = Ext.Component.from(e.target.parentNode);

        if (cmp && cmp.up(this.ownerCt)) {
            return false;
        }
    },

    processItemEvent: function(record, item, rowIndex, e) {
        var me = this,
            self = me.self,
            map = self.EventMap,
            type = e.type,
            features = me.features,
            len = features.length,
            i, cellIndex, result, feature, column,
            eventPosition = e.position = me.eventPosition ||
                                         (me.eventPosition = new Ext.grid.CellContext()),
            row, cell;

        // IE has a bug whereby if you mousedown in a cell editor in one side of a locking grid
        // and then drag out of that, and mouseup in *the other side*, the mousedowned side
        // still receives the event!
        // Even though the mouseup target is *not* within it! Ignore the mouseup in this case.
        if (Ext.isIE && type === 'mouseup' && !e.within(me.el)) {
            return false;
        }

        // Only process the event if it occurred within an item which maps to a record in the store
        if (me.indexInStore(item) !== -1) {
            row = eventPosition.rowElement = item.querySelector(me.rowSelector);

            // Access the cell from the event target.
            cell = e.getTarget(me.getCellSelector(), row);

            type = self.TouchEventMap[type] || type;

            if (cell) {
                if (!cell.parentNode) {
                    // If we have no parentNode, the td has been removed from the DOM,
                    // probably via an update, so just jump out since the target for the event
                    // isn't valid
                    return false;
                }

                column = me.getHeaderByCell(cell);

                // Find the index of the header in the *full* (including hidden columns)
                // leaf column set. Because In 4.0.0 we rendered hidden cells,
                // and the cellIndex included the hidden ones.
                if (column) {
                    cellIndex = me.ownerCt.getColumnManager().getHeaderIndex(column);
                }
                else {
                    column = cell = null;
                    cellIndex = -1;
                }
            }
            else {
                cellIndex = -1;
            }

            eventPosition.setAll(
                me,
                rowIndex,
                column ? me.getVisibleColumnManager().getHeaderIndex(column) : -1,
                record,
                column
            );

            eventPosition.cellElement = cell;

            result = me.fireEvent('uievent', type, me, cell, rowIndex, cellIndex, e, record, row);

            // If the event has been stopped by a handler, tell the selModel (if it is interested)
            // and return early.
            // For example, action columns by default will stop event propagation
            // by returning `false` from its 'uievent' event handler.
            if ((result === false || me.callParent(arguments) === false)) {
                return false;
            }

            for (i = 0; i < len; ++i) {
                feature = features[i];

                // In some features, the first/last row might be wrapped to contain extra info,
                // such as grouping or summary, so we may need to stop the event
                if (feature.wrapsItem) {
                    if (feature.vetoEvent(record, row, rowIndex, e) === false) {
                        // If the feature is vetoing the event, there's a good chance that
                        // it's for some feature action in the wrapped row.
                        me.processSpecialEvent(e);

                        return false;
                    }
                }
            }

            /* eslint-disable indent, max-len */
            // if the element whose event is being processed is not an actual cell (for example
            // if using a rowbody feature and the rowbody element's event is being processed)
            // then do not fire any "cell" events
            // Don't handle cellmouseenter and cellmouseleave events for now
            if (cell && type !== 'mouseover' && type !== 'mouseout') {
                result = !(
                    // We are adding cell and feature events
                    (me['onBeforeCell' + map[type]](cell, cellIndex, record, row, rowIndex, e) === false) ||
                    (me.fireEvent('beforecell' + type, me, cell, cellIndex, record, row, rowIndex, e) === false) ||
                    (me['onCell' + map[type]](cell, cellIndex, record, row, rowIndex, e) === false) ||
                    (me.fireEvent('cell' + type, me, cell, cellIndex, record, row, rowIndex, e) === false)
                );
            }
            /* eslint-enable indent, max-len */

            if (result !== false) {
                result = me.fireEvent('row' + type, me, record, row, rowIndex, e);
            }

            return result;
        }
        else {
            // If it's not in the store, it could be a feature event, so check here
            me.processSpecialEvent(e);

            // Prevent focus/selection here until proper focus handling is added for non-data rows
            // This should probably be removed once this is implemented.
            if (e.pointerType === 'mouse') {
                e.preventDefault();
            }

            return false;
        }
    },

    processSpecialEvent: function(e) {
        var me = this,
            features = me.features,
            ln = features.length,
            type = e.type,
            i, feature, prefix, featureTarget,
            beforeArgs, args,
            panel = me.ownerCt;

        me.callParent(arguments);

        if (type === 'mouseover' || type === 'mouseout') {
            return;
        }

        type = me.self.TouchEventMap[type] || type;

        for (i = 0; i < ln; i++) {
            feature = features[i];

            if (feature.hasFeatureEvent) {
                featureTarget = e.getTarget(feature.eventSelector, me.getTargetEl());

                if (featureTarget) {
                    prefix = feature.eventPrefix;

                    // allows features to implement getFireEventArgs to change the
                    // fireEvent signature
                    beforeArgs =
                        feature.getFireEventArgs('before' + prefix + type, me, featureTarget, e);

                    args = feature.getFireEventArgs(prefix + type, me, featureTarget, e);

                    /* eslint-disable indent */
                    if (
                        // before view event
                        (me.fireEvent.apply(me, beforeArgs) === false) ||
                        // panel grid event
                        (panel.fireEvent.apply(panel, beforeArgs) === false) ||
                        // view event
                        (me.fireEvent.apply(me, args) === false) ||
                        // panel event
                        (panel.fireEvent.apply(panel, args) === false)
                    ) {
                        return false;
                    }
                    /* eslint-enable indent */
                }
            }
        }

        return true;
    },

    onCellMouseDown: Ext.emptyFn,
    onCellLongPress: Ext.emptyFn,
    onCellMouseUp: Ext.emptyFn,
    onCellClick: Ext.emptyFn,
    onCellDblClick: Ext.emptyFn,
    onCellContextMenu: Ext.emptyFn,
    onCellKeyDown: Ext.emptyFn,
    onCellKeyUp: Ext.emptyFn,
    onCellKeyPress: Ext.emptyFn,
    onBeforeCellMouseDown: Ext.emptyFn,
    onBeforeCellLongPress: Ext.emptyFn,
    onBeforeCellMouseUp: Ext.emptyFn,
    onBeforeCellClick: Ext.emptyFn,
    onBeforeCellDblClick: Ext.emptyFn,
    onBeforeCellContextMenu: Ext.emptyFn,
    onBeforeCellKeyDown: Ext.emptyFn,
    onBeforeCellKeyUp: Ext.emptyFn,
    onBeforeCellKeyPress: Ext.emptyFn,

    /**
     * Expands a particular header to fit the max content width.
     * @deprecated 6.5.0 Use {@link #autoSizeColumn} instead.
     */
    expandToFit: function(header) {
        this.autoSizeColumn(header);
    },

    /**
     * Sizes the passed header to fit the max content width.
     * *Note that group columns shrinkwrap around the size of leaf columns. Auto sizing
     * a group column autosizes descendant leaf columns.*
     * @param {Ext.grid.column.Column/Number} header The header (or index of header) to auto size.
     */
    autoSizeColumn: function(header) {
        if (Ext.isNumber(header)) {
            header = this.getGridColumns()[header];
        }

        if (header) {
            if (header.isGroupHeader) {
                header.autoSize();

                return;
            }

            delete header.flex;

            header.setWidth(this.getMaxContentWidth(header));
        }
    },

    /**
     * Returns the max contentWidth of the header's text and all cells
     * in the grid under this header.
     * @private
     */
    getMaxContentWidth: function(header) {
        var me = this,
            cells = me.getHeaderCells(header),
            originalWidth = header.getWidth(),
            columnSizers = me.getColumnResizers(header),
            ln = cells.length,
            max = Math.max,
            widthAdjust = 0,
            i, maxWidth;

        if (ln > 0) {
            if (Ext.supports.ScrollWidthInlinePaddingBug) {
                widthAdjust += me.getCellPaddingAfter(cells[0]);
            }

            if (me.columnLines) {
                widthAdjust += Ext.fly(cells[0].parentNode).getBorderWidth('lr');
            }
        }

        // Set column width to 1px so we can detect the content width by measuring scrollWidth
        for (i = 0; i < columnSizers.length; i++) {
            columnSizers[i].setWidth(1);
        }

        // We are about to measure the offsetWidth of the textEl to determine how much
        // space the text occupies, but it will not report the correct width if the titleEl
        // has text-overflow:ellipsis.  Set text-overflow to 'clip' before proceeding to
        // ensure we get the correct measurement.
        header.textEl.setStyle({
            "text-overflow": 'clip',
            display: 'table-cell'
        });

        // Allow for padding round text of header
        maxWidth = header.textEl.dom.offsetWidth + header.titleEl.getPadding('lr');

        // revert to using text-overflow defined by the stylesheet
        header.textEl.setStyle({
            "text-overflow": '',
            display: ''
        });

        for (i = 0; i < ln; i++) {
            maxWidth = max(maxWidth, cells[i].scrollWidth);
        }

        // in some browsers, the "after" padding is not accounted for in the scrollWidth
        maxWidth += widthAdjust;

        // 40 is the minimum column width.  TODO: should this be configurable?
        // One extra pixel needed. EXACT width shrinkwrap of text causes ellipsis to appear.
        maxWidth = max(maxWidth + 1, 40);

        // Set column width back to original width
        for (i = 0; i < columnSizers.length; i++) {
            columnSizers[i].setWidth(originalWidth);
        }

        return maxWidth;
    },

    getColumnResizers: function(header) {
        var me = this,
            features = me.features || [],
            resizers = [me.body.select(me.getColumnSizerSelector(header))],
            featureSizer, i;

        for (i = 0; i < features.length; i++) {
            featureSizer = features[i].columnSizer;

            if (featureSizer) {
                resizers.push(featureSizer.select(me.getColumnSizerSelector(header)));
            }
        }

        return resizers;
    },

    getHeaderCells: function(header) {
        var me = this,
            features = me.features || [],
            headerCells = me.el.query(header.getCellInnerSelector()),
            featureSizer,
            i;

        for (i = 0; i < features.length; i++) {
            featureSizer = features[i].columnSizer;

            if (featureSizer) {
                headerCells = headerCells.concat(featureSizer.query(header.getCellInnerSelector()));
            }
        }

        return headerCells;
    },

    getPositionByEvent: function(e) {
        var me = this,
            cellNode = e.getTarget(me.cellSelector),
            rowNode = e.getTarget(me.itemSelector),
            record = me.getRecord(rowNode),
            header = me.getHeaderByCell(cellNode);

        return me.getPosition(record, header);
    },

    getHeaderByCell: function(cell) {
        if (cell) {
            return this.ownerGrid.getVisibleColumnManager()
                                 .getHeaderById(Ext.getDom(cell).getAttribute('data-columnId'));
        }

        return false;
    },

    /**
     * @param {Ext.grid.CellContext} pos The current navigation position.
     * @param {String} direction 'up', 'down', 'right' and 'left'
     * @param {Function} [verifierFn] A function to verify the validity of the calculated position.
     * When using this function, you must return true to allow the newPosition to be returned.
     * @param {Ext.grid.CellContext} [verifierFn.position] The calculated new position to verify.
     * @param {Object} [scope] Scope (`this` context) to run the verifierFn in.
     * Defaults to this View.
     * @return {Ext.grid.CellContext} An object encapsulating the unique cell position.
     *
     * @private
     */
    walkCells: function(pos, direction, verifierFn, scope) {
        var me = this,
            result = pos.clone(),
            lockingPartner = me.lockingPartner && me.lockingPartner.grid.isVisible()
                ? me.lockingPartner
                : null,
            rowIdx = pos.rowIdx,
            maxRowIdx = me.dataSource.getCount() - 1,
            columns = me.ownerCt.getVisibleColumnManager().getColumns();

        switch (direction.toLowerCase()) {
            case 'right':
                // At end of row.
                if (pos.isLastColumn()) {
                    // If we're at the end of the locked view, same row, else wrap downwards
                    rowIdx = lockingPartner && me.isLockedView ? rowIdx : rowIdx + 1;

                    // If stepped past the bottom row, deny the action
                    if (rowIdx > maxRowIdx) {
                        return false;
                    }

                    // There's a locking partner to move into
                    if (lockingPartner) {
                        result.view = lockingPartner;
                    }

                    result.setPosition(rowIdx, 0);
                }
                // Not at end, just go forwards one column
                else {
                    result.navigate(+1);
                }

                break;

            case 'left':
                // At start of row.
                if (pos.isFirstColumn()) {
                    // If we're at the start of the normal view, same row, else wrap upwards
                    rowIdx = lockingPartner && me.isNormalView ? rowIdx : rowIdx - 1;

                    // If top row, deny up
                    if (rowIdx < 0) {
                        return false;
                    }

                    // There's a locking partner to move into
                    if (lockingPartner) {
                        result.view = lockingPartner;
                        columns = lockingPartner.getVisibleColumnManager().getColumns();
                    }

                    result.setPosition(rowIdx, columns[columns.length - 1]);
                }
                // Not at end, just go backwards one column
                else {
                    result.navigate(-1);
                }

                break;

            case 'up':
                // if top row, deny up
                if (rowIdx === 0) {
                    return false;
                // go up
                }
                else {
                    result.setRow(rowIdx - 1);
                }

                break;

            case 'down':
                // if bottom row, deny down
                if (rowIdx === maxRowIdx) {
                    return false;
                // go down
                }
                else {
                    result.setRow(rowIdx + 1);
                }

                break;
        }

        if (verifierFn && verifierFn.call(scope || me, result) !== true) {
            return false;
        }

        return result;
    },

    /**
     * Increments the passed row index by the passed increment which may be +ve or -ve
     *
     * Skips hidden rows.
     *
     * If no row is visible in the specified direction, returns the input row index unchanged.
     * @param {Number} startRow The zero-based row index to start from.
     * @param {Number} distance The distance to move the row by. May be +ve or -ve.
     * @deprecated 6.5.0 This method is deprecated.
     * @private
     */
    walkRows: function(startRow, distance) {
        // Note that we use the **dataSource** here because row indices mean view row indices
        // so records in collapsed groups must be omitted.
        var me = this,
            store = me.dataSource,
            moved = 0,
            lastValid = startRow,
            node,
            limit = (distance < 0) ? 0 : store.getCount() - 1,
            increment = limit ? 1 : -1,
            result = startRow;

        do {
            // Walked off the end: return the last encountered valid row
            if (limit ? result >= limit : result <= limit) {
                return lastValid || limit;
            }

            // Move the result pointer on by one position.
            // We have to count intervening VISIBLE nodes
            result += increment;

            // Stepped onto VISIBLE record: Increment the moved count.
            // We must not count stepping onto a non-rendered record as a move.
            if ((node = Ext.fly(me.getRow(result))) && node.isVisible(true)) {
                moved += increment;
                lastValid = result;
            }
        } while (moved !== distance);

        return result;
    },

    /**
     * Navigates from the passed record by the passed increment which may be +ve or -ve
     *
     * Skips hidden records.
     *
     * If no record is visible in the specified direction, returns the starting record
     * index unchanged.
     * @param {Ext.data.Model} startRec The Record to start from.
     * @param {Number} distance The distance to move from the record. May be +ve or -ve.
     */
    walkRecs: function(startRec, distance) {
        // Note that we use the **store** to access the records by index
        // because the dataSource omits records in collapsed groups.
        // This is used by selection models which use the **store**
        var me = this,
            store = me.dataSource,
            moved = 0,
            lastValid = startRec,
            // eslint-disable-next-line max-len
            limit = (distance < 0) ? 0 : (store.isBufferedStore ? store.getTotalCount() : store.getCount()) - 1,
            increment = limit ? 1 : -1,
            testIndex = store.indexOf(startRec),
            node, rec;

        do {
            // Walked off the end: return the last encountered valid record
            if (limit ? testIndex >= limit : testIndex <= limit) {
                return lastValid;
            }

            // Move the result pointer on by one position.
            // We have to count intervening VISIBLE nodes
            testIndex += increment;

            // Stepped onto VISIBLE record: Increment the moved count.
            // We must not count stepping onto a non-rendered record as a move.
            rec = store.getAt(testIndex);

            if (!rec.isCollapsedPlaceholder &&
                (node = Ext.fly(me.getNodeByRecord(rec))) && node.isVisible(true)) {
                moved += increment;
                lastValid = rec;
            }
        } while (moved !== distance);

        return lastValid;
    },

    /**
     * Returns the index of the first row in your table view deemed to be visible.
     * @return {Number}
     * @private
     */
    getFirstVisibleRowIndex: function() {
        var me = this,
            result = me.indexOf(me.all.first()) - 1,
            count;

        count = me.dataSource.isBufferedStore
            ? me.dataSource.getTotalCount()
            : me.dataSource.getCount();

        do {
            result += 1;

            if (result === count) {
                return;
            }
        } while (!Ext.fly(me.getRow(result)).isVisible(true));

        return result;
    },

    /**
     * Returns the index of the last row in your table view deemed to be visible.
     * @return {Number}
     * @private
     */
    getLastVisibleRowIndex: function() {
        var me = this,
            result = me.indexOf(me.all.last());

        do {
            result -= 1;

            if (result === -1) {
                return;
            }
        } while (!Ext.fly(me.getRow(result)).isVisible(true));

        return result;
    },

    getHeaderCt: function() {
        return this.headerCt;
    },

    getPosition: function(record, header) {
        return new Ext.grid.CellContext(this).setPosition(record, header);
    },

    doDestroy: function() {
        var me = this,
            features = me.featuresMC,
            feature, i, len;

        // We need to unbind the store first to avoid firing update events;
        // all kinds of things are bound to this store and they don't need
        // updates anymore.
        me.bindStore(null);

        if (features) {
            for (i = 0, len = features.getCount(); i < len; ++i) {
                feature = features.getAt(i);

                // Features could be already destroyed
                if (feature && !feature.destroyed) {
                    feature.destroy();
                }
            }
        }

        me.all.destroy();
        me.body.destroy();
        me.actionRowFly.destroy();

        me.callParent();
    },

    /** 
     * @private
     * Respond to store replace event which is fired by GroupStore group expand/collapse operations.
     * This saves a layout because a remove and add operation are coalesced in this operation.
     */
    onReplace: function(store, startIndex, oldRecords, newRecords) {
        var me = this,
            bufferedRenderer = me.bufferedRenderer,
            restoreFocus;

        // If there's a buffered renderer and the removal range falls inside the current view...
        if (me.rendered && bufferedRenderer) {
            // If focus was in any way in the view, whether actionable or navigable,
            // this will return a function which will restore that state.
            restoreFocus = me.saveFocusState();
            bufferedRenderer.onReplace(store, startIndex, oldRecords, newRecords);

            // If focus was in any way in this view, this will restore it
            restoreFocus();
        }
        else {
            me.callParent(arguments);
        }

        me.setPendingStripe(startIndex);
    },

    onResize: function(width, height, oldWidth, oldHeight) {
        var me = this,
            bufferedRenderer = me.bufferedRenderer;

        // Ensure the buffered renderer makes its adjustments before user resize listeners
        if (bufferedRenderer) {
            bufferedRenderer.onViewResize(me, width, height, oldWidth, oldHeight);
        }

        me.callParent([width, height, oldWidth, oldHeight]);
    },

    // after adding a row stripe rows from then on
    onAdd: function(store, records, index) {
        var me = this,
            bufferedRenderer = me.bufferedRenderer;

        // Some features might need to know if we're refreshing or just adding rows
        me.addingRows = true;

        // Only call the buffered renderer's handler if there's a need to.
        // That is if the rendered block has been moved down the dataset, or
        // the addition will tip the rendered block size over the buffered renderer's
        // calculated viewSize.
        // eslint-disable-next-line max-len
        if (me.rendered && bufferedRenderer && (bufferedRenderer.bodyTop || me.dataSource.getCount() + records.length >= bufferedRenderer.viewSize)) {
            bufferedRenderer.onReplace(store, index, [], records);
        }
        else {
            me.callParent(arguments);
        }

        me.setPendingStripe(index);

        me.addingRows = false;
    },

    // after removing a row stripe rows from then on
    onRemove: function(store, records, index) {
        var me = this,
            bufferedRenderer = me.bufferedRenderer,
            restoreFocus;

        // If there's a BufferedRenderer, and it's being used (dataset size before removal
        // was >= rendered block size)...
        if (me.rendered && bufferedRenderer &&
            me.dataSource.getCount() + records.length >= bufferedRenderer.viewSize) {
            // If focus was in any way in the view, whether actionable or navigable,
            // this will return a function which will restore that state.
            restoreFocus = me.saveFocusState();
            bufferedRenderer.onReplace(store, index, records, []);

            // If focus was in any way in this view, this will restore it
            restoreFocus();
        }
        else {
            me.callParent(arguments);
        }

        if (me.actionPosition && Ext.Array.indexOf(records, me.actionPosition.record) !== -1) {
            me.actionPosition = null;
        }

        me.setPendingStripe(index);
    },

    /**
     * @private
     * Called prior to an operation which may remove focus from this view by some kind
     * of DOM operation.
     *
     * If this view contains focus in any sense, either navigable mode, or actionable mode,
     * this method returns a function which, when called after the disruptive DOM operation
     * will restore focus to the same record/column, or, if the record has been removed, to the same
     * row index/column.
     *
     * @returns {Function} A function that will restore focus if focus was within this view,
     * or a function which does nothing is focus is not in this view.
     */
    saveFocusState: function() {
        var me = this,
            store = me.dataSource,
            actionableMode = me.actionableMode,
            navModel = me.getNavigationModel(),
            focusPosition = actionableMode ? me.actionPosition : navModel.getPosition(true),
            activeElement = Ext.fly(Ext.Element.getActiveElement()),
            focusCell = focusPosition && focusPosition.view === me &&
                        Ext.fly(focusPosition.getCell(true)),
            refocusRow, refocusCol, record;

        // The navModel may return a position that is in a locked partner, so check that
        // the focusPosition's cell contains the focus before going forward.
        // The skipSaveFocusState is set by Actionables which actively control
        // focus destination. See CellEditing#activateCell.
        if (!me.skipSaveFocusState && focusCell && focusCell.contains(activeElement)) {
            // Separate this from the instance that the nav model is using.
            focusPosition = focusPosition.clone();

            // While we deactivate the focused element, suspend focus processing on it.
            activeElement.suspendFocusEvents();

            // Suspend actionable mode.
            // Each Actionable must silently save its state ready to resume when focus
            // can be restored but should only do that if the activeElement is not the cell itself,
            // this happens when the grid is refreshed while one of the actionables is being
            // deactivated (e.g. Calling  view refresh inside CellEditor 'edit' event listener).
            if (actionableMode && focusCell.dom !== activeElement.dom) {
                me.suspendActionableMode();
            }
            // Clear position, otherwise the setPosition on the other side
            // will be rejected as a no-op if the resumption position is logically
            // equivalent.
            else {
                actionableMode = false;
                navModel.setPosition();
            }

            // Do not leave the element in tht state in case refresh fails, and restoration
            // closure not called.
            activeElement.resumeFocusEvents();

            // if the store is expanding or collapsing, we should never scroll the view.
            if (store.isExpandingOrCollapsing) {
                return Ext.emptyFn;
            }

            // The following function will attempt to refocus back in the same mode to the same cell
            // as it was at before based upon the previous record (if it's still in the store),
            // or the row index.
            return function() {
                var all;

                // May have changed due to reconfigure
                store = me.dataSource;

                // If we still have data, attempt to refocus in the same mode.
                if (store.getCount()) {
                    all = me.all;

                    // Adjust expectations of where we are able to refocus according to
                    // what kind of destruction might have been wrought on this view's DOM
                    // during focus save.
                    refocusRow =
                        Math.min(Math.max(focusPosition.rowIdx, all.startIndex), all.endIndex);

                    refocusCol = Math.min(
                        focusPosition.colIdx,
                        me.getVisibleColumnManager().getColumns().length - 1
                    );

                    record = focusPosition.record;

                    focusPosition = new Ext.grid.CellContext(me).setPosition(
                        record && store.contains(record) && !record.isCollapsedPlaceholder
                            ? record
                            : refocusRow,
                        refocusCol
                    );

                    // Maybe there are no cells. eg: all groups collapsed.
                    if (focusPosition.getCell(true)) {
                        if (actionableMode) {
                            me.resumeActionableMode(focusPosition);
                        }
                        else {
                            // Pass "preventNavigation" as true
                            // so that that does not cause selection.
                            navModel.setPosition(focusPosition, null, null, null, true);

                            if (!navModel.getPosition()) {
                                focusPosition.column.focus();
                            }
                        }
                    }
                }
                // No rows - focus associated column header
                else {
                    focusPosition.column.focus();
                }
            };
        }

        return Ext.emptyFn;
    },

    onDataRefresh: function(store) {
        // When there's a buffered renderer present, store refresh events cause TableViews to
        // go to scrollTop:0
        var me = this,
            owner = me.ownerCt;

        // If triggered during an animation, refresh once we're done
        if (owner && owner.isCollapsingOrExpanding === 2) {
            owner.on('expand', me.onDataRefresh, me, { single: true });

            return;
        }

        me.callParent([store]);
    },

    getViewRange: function() {
        var me = this;

        if (me.bufferedRenderer) {
            return me.bufferedRenderer.getViewRange();
        }

        return me.callParent();
    },

    setPendingStripe: function(index) {
        var current = this.stripeOnUpdate;

        if (current === null) {
            current = index;
        }
        else {
            current = Math.min(current, index);
        }

        this.stripeOnUpdate = current;
    },

    onEndUpdate: function() {
        var me = this,
            stripeOnUpdate = me.stripeOnUpdate,
            startIndex = me.all.startIndex;

        if (me.rendered && (stripeOnUpdate || stripeOnUpdate === 0)) {
            if (stripeOnUpdate < startIndex) {
                stripeOnUpdate = startIndex;
            }

            me.doStripeRows(stripeOnUpdate);
            me.stripeOnUpdate = null;
        }

        me.callParent(arguments);
    },

    /**
     * Stripes rows from a particular row index.
     * @param {Number} startRow
     * @param {Number} [endRow] argument specifying the last row to process.
     * By default process up to the last row.
     * @private
     */
    doStripeRows: function(startRow, endRow) {
        var me = this,
            rows, rowsLn, i, row;

        // ensure stripeRows configuration is turned on
        if (me.rendered && me.stripeRows) {
            rows = me.getNodes(startRow, endRow);

            for (i = 0, rowsLn = rows.length; i < rowsLn; i++) {
                row = rows[i];

                // Remove prior applied row classes.
                row.className = row.className.replace(me.rowClsRe, ' ');
                startRow++;

                // Every odd row will get an additional cls
                if (startRow % 2 === 0) {
                    row.className += (' ' + me.altRowCls);
                }
            }
        }
    },

    hasActiveFeature: function() {
        return (this.isGrouping && this.store.isGrouped()) || this.isRowWrapped;
    },

    getCellPaddingAfter: function(cell) {
        return Ext.fly(cell).getPadding('r');
    },

    privates: {
        saveTabOptions: {
            skipSelf: true,
            includeHidden: true
        },

        /*
         * Overridden implementation.
         * Called by refresh to collect the view item nodes.
         * Note that these may be wrapping rows which *contain* rows which map to records
         * @private
         */
        collectNodes: function(targetEl) {
            this.all.fill(this.getNodeContainer().childNodes, this.all.startIndex);
        },

        /**
         * 
         * @param {Boolean} enabled
         * @param {Ext.grid.CellContext} position The cell to activate.
         * @param {HTMLElement/Ext.dom.Element} [position.target] The element within the referenced
         * cell to focus.
         * @return {Boolean} Returns `false` if the mode did not change.
         * @private
         */
        setActionableMode: function(enabled, position) {
            var me = this,
                navModel = me.getNavigationModel(),
                actionables = me.grid.actionables,
                len = actionables.length,
                isActionable = false,
                activeEl, record, column, lockingPartner, cell, i;

            // No mode change.
            // ownerGrid's call will NOT fire mode change event upon false return.
            if (me.actionableMode === enabled) {
                // If we're not actionable already, or (we are actionable already at that position)
                // return false.
                // Test using mandatory passed position because we may not have an actionPosition
                // if we are  the lockingPartner of an actionable view that contained
                // the action position.
                //
                // If we being told to go into actionable mode but at another position,
                // we must continue. This is just actionable navigation.
                if (!enabled || position.isEqual(me.actionPosition)) {
                    return false;
                }
            }

            // If this View or its lockingPartner contains the current focus position,
            // then make the tab bumpers tabbable and move them to surround the focused row.
            if (enabled) {
                if (position && (position.view === me ||
                    (position.view === (lockingPartner = me.lockingPartner) &&
                    lockingPartner.actionableMode))) {
                    isActionable = me.activateCell(position);
                }

                // Did not enter actionable mode.
                // ownerGrid's call will NOT fire mode change event upon false return.
                return isActionable;
            }
            else {
                // Capture before exiting from actionable mode moves focus
                activeEl = Ext.fly(Ext.Element.getActiveElement());

                // Blur the focused descendant, but do not trigger focusLeave.
                // This is so that when the focus is restored to the cell which contained
                // the active content, it will not be a FocusEnter from the universe.
                if (me.el.contains(activeEl) && !Ext.fly(activeEl).is(me.getCellSelector())) {
                    // Row to return focus to.
                    record =
                        (me.actionPosition && me.actionPosition.record) || me.getRecord(activeEl);

                    column = me.getHeaderByCell(activeEl.findParent(me.getCellSelector()));

                    cell = position && position.getCell(true);

                    // Do not allow focus to fly out of the view when the actionables
                    // are deactivated (and blurred/hidden). Restore focus to the cell in which
                    // actionable mode is active.
                    // Note that the original position may no longer be valid, e.g. when the record
                    // was removed.
                    if (!position || !cell) {
                        position =
                            new Ext.grid.CellContext(me).setPosition(record || 0, column || 0);

                        cell = position.getCell(true);
                    }

                    // Ext.grid.NavigationModel#onFocusMove will NOT react and navigate
                    // because the actionableMode flag is still set at this point.
                    Ext.fly(cell).focus();

                    // Let's update the activeEl after focus here
                    activeEl = Ext.fly(Ext.Element.getActiveElement());

                    // If that focus triggered handlers (eg CellEditor after edit handlers) which
                    // programmatically moved focus somewhere, and the target cell has been
                    // unfocused, defer to that, null out position, so that we do not navigate
                    // to that cell below.
                    // See EXTJS-20395
                    if (!(me.el.contains(activeEl) && activeEl.is(me.getCellSelector()))) {
                        position = null;
                    }
                }

                // We are exiting actionable mode.
                // Tell all registered Actionables about this fact if they need to know.
                for (i = 0; i < len; i++) {
                    if (actionables[i].deactivate) {
                        actionables[i].deactivate();
                    }
                }

                // If we had begun action (we may be a dormant lockingPartner),
                // make any tabbables untabbable
                if (me.actionRow) {
                    me.actionRow.saveTabbableState({
                        skipSelf: true,
                        includeSaved: false
                    });
                }

                if (me.destroyed) {
                    return false;
                }

                // These flags MUST be set before focus restoration to the owning cell.
                // so that when Ext.grid.NavigationModel#setPosition attempts to exit
                // actionable mode, we don't recurse.
                me.actionableMode = me.ownerGrid.actionableMode = false;
                me.actionPosition = navModel.actionPosition = me.actionRow = null;

                // Push focus out to where it was requested to go.
                if (position) {
                    navModel.setPosition(position);
                }
            }
        },

        /**
         * Called to silently enter actionable mode at the passed position.
         * May be called from the {@link #setActionableMode} method, or from the
         * {@link #resumeActionableMode} method.
         * @private
         */
        activateCell: function(position) {
            var me = this,
                lockingPartner = position.view !== me ? me.lockingPartner : null,
                actionables = me.grid.actionables,
                len = actionables.length,
                navModel = me.getNavigationModel(),
                focusTarget = position.target,
                record, prevRow, focusRow, focusCell, i, isActionable, tabbableChildren;

            position = position.clone();
            record = position.record;

            position.view.grid.ensureVisible(record, {
                column: position.column
            });

            focusRow = me.all.item(position.rowIdx, true);

            // Deactivate remaining tabbables in the row we were last actionable upon.
            if (me.actionPosition) {
                prevRow = me.all.item(me.actionPosition.rowIdx, true);

                if (prevRow && focusRow !== prevRow) {
                    Ext.fly(prevRow).saveTabbableState({
                        skipSelf: true,
                        includeSaved: false
                    });
                }
            }

            // We need to set the activating flag here because we will focus the editor at during
            // the rest of this method and if this happens before actionableMode is true,
            // navigationModel's  onFocusMove method needs to know if activating events
            // should be fired.
            me.activating = true;

            // We're the focused side - attempt to see if ths focused cell is actionable
            if (!lockingPartner) {
                focusCell = Ext.fly(position.getCell(true));
                me.actionPosition = position;

                // Inform all Actionables that we intend to activate this cell.
                // If any return true, isActionable will be set
                for (i = 0; i < len; i++) {
                    isActionable =
                        isActionable || actionables[i].activateCell(position, null, true);
                }

                // In case any of the activations called external handlers which
                // caused view DOM churn, reacquire the cell.
                focusCell = Ext.fly(position.getCell(true));
            }

            // If we have a lockingPartner that is actionable
            //  or if we find some elements we can restore to tabbability
            //  or there are existing tabbable elements
            //  or a plugin declared it was actionable at this position:
            //      dive in and activate the row
            // Note that a bitwise OR operator is used in this expression so that
            // no shortcutting is used. tabbableChildren must be extracted even if
            // restoreTabbableState found some previously disabled (tabIndex === -1)
            // nodes to restore.
            if (lockingPartner ||
                (focusCell &&
                    (focusCell.restoreTabbableState({ skipSelf: true }).length |
                    (tabbableChildren = focusCell.findTabbableElements()).length)) ||
                    isActionable) {

                // We are entering actionable mode.
                // Tell all registered Actionables about this fact if they need to know.
                for (i = 0; i < len; i++) {
                    if (actionables[i].activateRow) {
                        actionables[i].activateRow(focusRow);
                    }
                }

                // Only enter actionable mode if there is an already actionable locking partner,
                // or there are tabbable children in current cell.
                if (lockingPartner || tabbableChildren.length) {
                    // Restore tabbabilty to all elements in this row
                    Ext.fly(focusRow).restoreTabbableState({ skipSelf: true });

                    // If we are the locking partner of an actionable side, we are successful
                    // already. But we must not have an actionPosition. We are not actually
                    // in possession of an active cell and we must not reject an action request
                    // at that cell in the isEqual test above.
                    if (lockingPartner) {
                        me.actionableMode = true;
                        me.actionPosition = null;
                        me.activating = false;

                        return true;
                    }

                    // If there are focusables in the actioned cell, we can enter actionable mode.
                    if (tabbableChildren) {
                        /**
                         * @property {Ext.dom.Element} actionRow
                         * Only valid when a view is in actionableMode. The currently actioned row
                         */
                        me.actionRow = me.actionRowFly.attach(focusRow);

                        me.actionableMode = me.ownerGrid.actionableMode = true;

                        // Clear current position on entry into actionable mode
                        navModel.setPosition();
                        navModel.actionPosition = me.actionPosition = position;

                        // If position was loaded with a target, focus that if it is a valid target
                        if (focusTarget && Ext.Array.contains(tabbableChildren, focusTarget)) {
                            Ext.fly(focusTarget).focus();
                        }
                        else {
                            Ext.fly(tabbableChildren[0]).focus();
                        }

                        me.activating = false;

                        // Avoid falling through to returning false
                        return true;
                    }
                }
            }

            me.activating = false;
        },

        /**
         * Called by TableView#saveFocus
         * @private
         */
        suspendActionableMode: function() {
            var me = this,
                actionables = me.grid.actionables,
                len = actionables.length,
                i;

            for (i = 0; i < len; i++) {
                actionables[i].suspend();
            }
        },

        resumeActionableMode: function(position) {
            var me = this,
                actionables = me.grid.actionables,
                len = actionables.length,
                i, activated;

            // Disable tabbability of elements within this view.
            me.toggleChildrenTabbability(false);

            for (i = 0; i < len; i++) {
                activated = activated || actionables[i].resume(position);
            }

            // If non of the Actionable responded, attempt to find a naturally focusable
            // child element.
            if (!activated) {
                me.activateCell(position);
            }
        },

        onRowExit: function(keyEvent, prevRow, newRow, forward, wrapDone) {
            var me = this,
                direction = forward ? 'nextSibling' : 'previousSibling',
                lockingPartner = me.lockingPartner,
                rowIdx;

            if (lockingPartner && lockingPartner.grid.isVisible()) {
                rowIdx = me.all.indexOf(prevRow);

                // TAB out of right side of view
                if (forward) {
                    // If normal side go to next row in locked side
                    if (me.isNormalView) {
                        rowIdx++;
                    }
                }
                // TAB out of left side of view
                // If locked side go to previous row in normal side
                else if (me.isLockedView) {
                    rowIdx--;
                }

                // We've switched sides.
                me.actionPosition = null;
                me = lockingPartner;
                newRow = me.all.item(rowIdx, true);
            }

            if (!me.hasListeners.beforerowexit ||
                me.fireEvent('beforerowexit', me, keyEvent, prevRow, newRow, forward) !== false) {
                // Activate the next row.
                // This moves actionables' tabbable items to next row, restores that row's
                // tabbability and focuses the first/last tabbable element it finds
                // depending on direction.
                me.findFirstActionableElement(keyEvent, newRow, direction, forward, wrapDone);
            }
            else {
                return false;
            }
        },

        /**
         * Finds the first actionable element in the passed direction starting by
         * looking in the passed row.
         * @private
         */
        findFirstActionableElement: function(keyEvent, focusRow, direction, forward, wrapDone) {
            var me = this,
                columns = me.getVisibleColumnManager().getColumns(),
                columnCount = columns.length,
                actionables = me.grid.actionables,
                actionableCount = actionables.length,
                position = new Ext.grid.CellContext(me),
                focusCell, focusTarget, i, j, column, isActionable, tabbableChildren, prevRow;

            if (focusRow) {
                position.setRow(focusRow);

                for (i = 0; i < actionableCount; i++) {
                    // Tell all actionables who need to know that we are moving actionable mode
                    // to a new row.
                    // They should insert any tabbable elements into appropriate cells in the row.
                    if (actionables[i].activateRow) {
                        actionables[i].activateRow(focusRow);
                    }
                }

                // Look through the columns until we find one where the Actionables return
                // that the cell is actionable or there are tabbable elements found.
                // eslint-disable-next-line max-len
                for (i = (forward ? 0 : columnCount - 1); (forward ? i < columnCount : i > -1) && !focusTarget; i = i + (forward ? 1 : -1)) {
                    column = columns[i];
                    position.setColumn(column);

                    focusCell = (focusRow.dom || focusRow).querySelector(
                        position.column.getCellSelector()
                    );

                    for (j = 0; j < actionableCount; j++) {
                        isActionable = isActionable || actionables[j].activateCell(position);
                    }

                    // In case any code in the cell activation churned
                    // the grid DOM and the position got refreshed.
                    // eg: 'edit' handler on previously active editor.
                    focusCell = Ext.fly(position.getCell(true));

                    if (focusCell) {
                        focusRow = position.getNode(true);

                        // TODO?
                        // If the focusCell is available (when using features with colspan
                        // the cell won't be there) and of there are restored tabbable elements
                        // rendered in the cell, or an Actionable is activated on this cell...

                        // If there are restored tabbable elements rendered in the cell,
                        // or an Actionable is activated on this cell...
                        focusCell.restoreTabbableState({ skipSelf: true });

                        // Read tabbable children out to determine actionability.
                        // In case new DOM has been inserted by an 'edit' handler
                        // on previously active editor.
                        if ((tabbableChildren = focusCell.findTabbableElements()).length ||
                            isActionable) {
                            prevRow = me.actionRow && me.actionRow.dom;
                            me.actionRow = me.actionRowFly.attach(focusRow);

                            // Restore tabbabilty to all elements in this row.
                            me.actionRow.restoreTabbableState({ skipSelf: true });
                            focusTarget =
                                tabbableChildren[forward ? 0 : tabbableChildren.length - 1];
                        }
                    }
                }

                // Found a focusable element, focus it.
                if (focusTarget) {

                    // Keep actionPosition synched
                    me.actionPosition = me.getNavigationModel().actionPosition = position;

                    // If an async focus platform we must wait for the blur
                    // from the deactivate to clear before we can focus the next.
                    Ext.fly(focusTarget).focus(Ext.asyncFocus ? 1 : 0);

                    // Deactivate remaining tabbables in the row we were last actionable upon.
                    if (prevRow && focusRow !== prevRow) {
                        Ext.fly(prevRow).saveTabbableState({
                            skipSelf: true,
                            includeSaved: false
                        });
                    }
                }
                else {
                    // We walked off the end of the columns  without finding a focusTarget
                    // Process onRowExit in the current direction
                    me.onRowExit(
                        keyEvent, focusRow, me.all.item(position.rowIdx + (forward ? 1 : -1)),
                        forward, wrapDone
                    );
                }
            }
            // No focusRow and not already wrapped round the whole view;
            // wrap round in the correct direction.
            else if (!wrapDone) {
                me.grid.ensureVisible(forward ? 0 : me.dataSource.getCount() - 1, {
                    callback: function(success, record, row) {
                        if (success) {
                            // Pass the flag saying we've already wrapped round once.
                            me.findFirstActionableElement(keyEvent, row, direction, forward, true);
                        }
                        else {
                            me.ownerGrid.setActionableMode(false);
                        }
                    }
                });
            }
            // If we've already wrapped, but not found a focus target, we must exit actionable mode.
            else {
                me.ownerGrid.setActionableMode(false);
            }
        },

        stretchHeight: function(height) {
            /*
             * This is used when a table view is used in a lockable assembly.
             * Y scrolling is handled by an element which contains both grid views.
             * So each view has to be stretched to the full dataset height.
             * Setting the element height does not attain the maximum possible height.
             * Maximum content height is attained by adding "stretcher" elements
             * which have large margin-top values.
             */
            var me = this,
                scroller = me.getScrollable(),
                stretchers = me.stretchers,
                shortfall;

            if (height && me.tabGuardEl) {
                if (stretchers) {
                    stretchers[0].style.marginTop = stretchers[1].style.marginTop =
                        me.el.dom.style.height = 0;
                }

                me.el.dom.style.height = scroller.constrainScrollRange(height) + 'px';

                shortfall = height - me.el.dom.offsetHeight;

                // Only resort to the stretcher els if they are needed
                if (shortfall > 0) {
                    me.el.dom.style.height = '';
                    stretchers = me.getStretchers();
                    shortfall = height - me.el.dom.offsetHeight;

                    if (shortfall > 0) {
                        stretchers[0].style.marginTop =
                            scroller.constrainScrollRange(shortfall) + 'px';

                        shortfall = height - me.el.dom.offsetHeight;

                        if (shortfall > 0) {
                            stretchers[1].style.marginTop =
                                Math.min(shortfall, scroller.maxSpacerMargin || 0) + 'px';
                        }
                    }
                }
            }
        },

        getStretchers: function() {
            var me = this,
                stretchers = me.stretchers,
                stretchCfg;

            if (stretchers) {
                // Ensure they're at the end
                me.el.appendChild(stretchers);
            }
            else {
                stretchCfg = {
                    cls: 'x-scroller-spacer',
                    style: 'position:relative'
                };
                stretchers = me.stretchers = me.el.appendChild([stretchCfg, stretchCfg], true);
            }

            return stretchers;
        }
    }
}, function(Table) {
    // Create a flyweight for manipulating cells without having to
    // create a transient Ext.Element which has then to be garbage collected.
    Table.prototype.oldCellFly = new Ext.dom.Fly();
});
