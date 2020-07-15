/* eslint-disable max-len */
/**
 * This class specifies the definition for a column inside a {@link Ext.grid.Panel}. It encompasses
 * both the grid header configuration as well as displaying data within the grid itself. If the
 * {@link #columns} configuration is specified, this column will become a column group and can
 * contain other columns inside. In general, this class will not be created directly, rather
 * an array of column configurations will be passed to the grid:
 *
 *     @example
 *     Ext.create('Ext.data.Store', {
 *         storeId:'employeeStore',
 *         fields:['firstname', 'lastname', 'seniority', 'dep', 'hired'],
 *         data:[
 *             {firstname:"Michael", lastname:"Scott", seniority:7, dep:"Management", hired:"01/10/2004"},
 *             {firstname:"Dwight", lastname:"Schrute", seniority:2, dep:"Sales", hired:"04/01/2004"},
 *             {firstname:"Jim", lastname:"Halpert", seniority:3, dep:"Sales", hired:"02/22/2006"},
 *             {firstname:"Kevin", lastname:"Malone", seniority:4, dep:"Accounting", hired:"06/10/2007"},
 *             {firstname:"Angela", lastname:"Martin", seniority:5, dep:"Accounting", hired:"10/21/2008"}
 *         ]
 *     });
 *
 *     Ext.create('Ext.grid.Panel', {
 *         title: 'Column Demo',
 *         store: Ext.data.StoreManager.lookup('employeeStore'),
 *         columns: [
 *             {text: 'First Name',  dataIndex:'firstname'},
 *             {text: 'Last Name',  dataIndex:'lastname'},
 *             {text: 'Hired Month',  dataIndex:'hired', xtype:'datecolumn', format:'M'},
 *             {text: 'Department (Yrs)', xtype:'templatecolumn', tpl:'{dep} ({seniority})'}
 *         ],
 *         width: 400,
 *         forceFit: true,
 *         renderTo: Ext.getBody()
 *     });
 *
 * # Convenience Subclasses
 *
 * There are several column subclasses that provide default rendering for various data types
 *
 *  - {@link Ext.grid.column.Action}: Renders icons that can respond to click events inline
 *  - {@link Ext.grid.column.Boolean}: Renders for boolean values
 *  - {@link Ext.grid.column.Date}: Renders for date values
 *  - {@link Ext.grid.column.Number}: Renders for numeric values
 *  - {@link Ext.grid.column.Template}: Renders a value using an {@link Ext.XTemplate} using the record data
 *
 * # Setting Widths
 *
 * The columns are laid out by a {@link Ext.layout.container.HBox} layout, so a column can either
 * be given an explicit width value or a {@link #flex} configuration. If no width is specified the grid will
 * automatically the size the column to 100px.
 * 
 * Group columns (columns with {@link #columns child columns}) may be sized using {@link #flex},
 * in which case they will apply `forceFit` to their child columns so as not to leave blank space.
 * 
 * If a group column is not flexed, its width is calculated by measuring the width of the
 * child columns, so a width option should not be specified in that case.
 *
 * # Header Options
 *
 *  - {@link #text}: Sets the header text for the column
 *  - {@link #sortable}: Specifies whether the column can be sorted by clicking the header or using the column menu
 *  - {@link #hideable}: Specifies whether the column can be hidden using the column menu
 *  - {@link #menuDisabled}: Disables the column header menu
 *  - {@link #cfg-draggable}: Specifies whether the column header can be reordered by dragging
 *  - {@link #groupable}: Specifies whether the grid can be grouped by the column dataIndex. See also {@link Ext.grid.feature.Grouping}
 *
 * # Data Options
 *
 *  - {@link #dataIndex}: The dataIndex is the field in the underlying {@link Ext.data.Store} to use as the value for the column.
 *  - {@link Ext.grid.column.Column#renderer}: Allows the underlying store
 *  value to be transformed before being displayed in the grid
 *
 * ## State saving
 *
 * When the owning {@link Ext.grid.Panel Grid} is configured
 * {@link Ext.grid.Panel#cfg-stateful}, it will save its column state (order and width)
 * encapsulated within the default Panel state of changed width and height and
 * collapsed/expanded state.
 *
 * On a `stateful` grid, not only should the Grid have a
 * {@link Ext.grid.Panel#cfg-stateId}, each column of the grid should also be configured
 * with a {@link #stateId} which identifies that column locally within the grid.
 *
 * Omitting the `stateId` config from the columns results in columns with generated
 * internal ID's.  The generated ID's are subject to change on each page load
 * making it impossible for the state manager to restore the previous state of the
 * columns.
 */
Ext.define('Ext.grid.column.Column', {
    /* eslint-enable max-len */
    extend: 'Ext.grid.header.Container',
    xtype: 'gridcolumn',

    requires: [
        'Ext.grid.ColumnComponentLayout',
        'Ext.grid.ColumnLayout',
        'Ext.app.bind.Parser' // for "format" support
    ],

    alternateClassName: 'Ext.grid.Column',

    config: {
        triggerVisible: false,

        /**
         * @cfg {Function/String/Object/Ext.util.Sorter} sorter
         * A Sorter, or sorter config object to apply when the standard user interface
         * sort gesture is invoked. This is usually clicking this column header, but
         * there are also menu options to sort ascending or descending.
         *
         * Note that a sorter may also be specified as a function which accepts two
         * records to compare.
         *
         * In 6.2.0, a `{@link Ext.app.ViewController controller}` method can be used
         * like so:
         *
         *      sorter: {
         *          sorterFn: 'sorterMethodName'
         *      }
         *
         * @since 6.0.1
         */
        sorter: null,

        /**
         * @cfg {'start'/'center'/'end'} [align='start']
         * Sets the alignment of the header and rendered columns.
         * Possible values are: `'start'`, `'center'`, and `'end'`.
         *
         * Since 6.2.0, `'left'` and `'right'` will still work, but retain their meaning
         * even when the application is in RTL mode.
         *
         * `'start'` and `'end'` always conform to the locale's text direction.
         */
        align: 'start'
    },

    baseCls: Ext.baseCSSPrefix + 'column-header',

    // Not the standard, automatically applied overCls because we must filter out
    // overs of child headers.
    hoverCls: Ext.baseCSSPrefix + 'column-header-over',

    ariaRole: 'columnheader',

    focusableContainer: false,

    sortState: null,

    possibleSortStates: ['ASC', 'DESC'],

    // These are not readable descriptions; the values go in the aria-sort attribute.
    ariaSortStates: {
        ASC: 'ascending',
        DESC: 'descending'
    },

    childEls: [
        'titleEl', 'triggerEl', 'textEl', 'textContainerEl', 'textInnerEl'
    ],

    /**
     * @cfg {Boolean} [headerWrap=false]
     * The default setting indicates that external CSS rules dictate that the title is
     * `white-space: nowrap` and therefore, width cannot affect the measured height by causing
     * text wrapping. This is what the Sencha-supplied styles set. If you change those styles
     * to allow text wrapping, you must set this to `true`.
     * @private
     */
    headerWrap: false,

    /* eslint-disable indent, max-len */
    renderTpl: [
        '<div id="{id}-titleEl" data-ref="titleEl" role="presentation"',
            '{tipMarkup}class="', Ext.baseCSSPrefix, 'column-header-inner<tpl if="!$comp.isContainer"> ', Ext.baseCSSPrefix, 'leaf-column-header</tpl>',
            '<tpl if="empty"> ', Ext.baseCSSPrefix, 'column-header-inner-empty</tpl>">',
            //
            // TODO:
            // When IE8 retires, revisit https://jsbin.com/honawo/quiet for better way to center header text
            //
            '<div id="{id}-textContainerEl" data-ref="textContainerEl" role="presentation" class="', Ext.baseCSSPrefix, 'column-header-text-container">',
                '<div role="presentation" class="', Ext.baseCSSPrefix, 'column-header-text-wrapper">',
                    '<div id="{id}-textEl" data-ref="textEl" role="presentation" class="', Ext.baseCSSPrefix, 'column-header-text',
                        '{childElCls}">',
                        '<span id="{id}-textInnerEl" data-ref="textInnerEl" role="presentation" class="', Ext.baseCSSPrefix, 'column-header-text-inner">{text}</span>',
                    '</div>',
                    '{%',
                        'values.$comp.afterText(out, values);',
                    '%}',
                '</div>',
            '</div>',
            '<tpl if="!menuDisabled">',
                '<div id="{id}-triggerEl" data-ref="triggerEl" role="presentation" unselectable="on" class="', Ext.baseCSSPrefix, 'column-header-trigger',
                '{childElCls}" style="{triggerStyle}"></div>',
            '</tpl>',
        '</div>',
        '{%this.renderContainer(out,values)%}'
    ],
    /* eslint-enable indent, max-len */

    /**
     * @cfg {Object[]} columns
     * An optional array of sub-column definitions. This column becomes a group, and houses
     * the columns defined in the `columns` config.
     *
     * Group columns may not be sortable. But they may be hideable and moveable. And you may move
     * headers into and out of a group. Note that if all sub columns are dragged out of a group,
     * the group is destroyed.
     */

    /**
     * @cfg {String} stateId
     * An identifier which identifies this column uniquely within the owning grid's
     * {@link #stateful state}.
     *
     * This does not have to be *globally* unique. A column's state is not saved standalone.
     * It is encapsulated within the owning grid's state.
     */

    /**
     * @cfg {String} dataIndex
     * The name of the field in the grid's {@link Ext.data.Store}'s {@link Ext.data.Model}
     * definition from which to draw the column's value. **Required.**
     */
    dataIndex: null,

    /**
     * @cfg {String} text
     * The header text to be used as innerHTML (html tags are accepted) to display in the Grid.
     */
    text: '\u00a0',

    /**
     * @cfg {String} header
     * The header text.
     * @deprecated 4.0 Use {@link #text} instead.
     */

    /**
     * @cfg {String} menuText
     * The text to render in the column visibility selection menu for this column.  If not
     * specified, will default to the text value.
     */
    menuText: null,

    /**
     * @cfg {String} [emptyCellText=undefined]
     * The text to display in empty cells (cells with a value of `undefined`, `null`, or `''`).
     *
     * Defaults to `&#160;` aka `&nbsp;`.
     */
    emptyCellText: '\u00a0',

    /**
     * @cfg {Boolean} sortable
     * False to disable sorting of this column. Whether local/remote sorting is used is specified in
     * `{@link Ext.data.Store#remoteSort}`.
     */
    sortable: true,

    /**
     * @cfg {Boolean} [enableTextSelection=false]
     * True to enable text selection inside grid cells in this column.
     */

    /**
     * @cfg {Boolean} lockable
     * If the grid is configured with {@link Ext.panel.Table#enableLocking enableLocking}, or has
     * columns which are configured with a {@link #locked} value, this option may be used to disable
     * user-driven locking or unlocking of this column. This column will remain in the side
     * into which its own {@link #locked} configuration placed it.
     */

    /**
     * @cfg {Boolean} groupable
     * If the grid uses a {@link Ext.grid.feature.Grouping}, this option may be used to disable
     * the header menu item to group by the column selected. By default, the header menu group
     * option is enabled. Set to false to disable (but still show) the group option
     * in the header menu for the column.
     */

    /**
     * @cfg {Boolean} fixed
     * True to prevent the column from being resizable.
     * @deprecated 4.0 Use {@link #resizable} instead.
     */

    /**
     * @cfg {Boolean} [locked=false]
     * True to lock this column in place.  Implicitly enables locking on the grid.
     * See also {@link Ext.grid.Panel#enableLocking}.
     */

    /**
     * @cfg {Boolean} [cellWrap=false]
     * True to allow whitespace in this column's cells to wrap, and cause taller column height where
     * necessary.
     *
     * This implicitly sets the {@link #variableRowHeight} config to `true`
     */

    /**
     * @cfg {Boolean} [variableRowHeight=false]
     * True to indicate that data in this column may take on an unpredictable height, possibly
     * differing from row to row.
     *
     * If this is set, then View refreshes, and removal and addition of new rows will result
     * in an ExtJS layout of the grid in order to adjust for possible addition/removal of scrollbars
     * in the case of data changing height.
     *
     * This config also tells the View's buffered renderer that row heights are unpredictable,
     * and must be remeasured as the view is refreshed.
     */

    /**
     * @cfg {Boolean} resizable
     * False to prevent the column from being resizable.
     */
    resizable: true,

    /**
     * @cfg {Boolean} hideable
     * False to prevent the user from hiding this column.
     */
    hideable: true,

    /**
     * @cfg {Boolean} menuDisabled
     * True to disable the column header menu containing sort/hide options.
     */
    menuDisabled: false,

    /**
     * @cfg {Function/String} renderer
     * A renderer is an 'interceptor' method which can be used to transform data (value,
     * appearance, etc.) before it is rendered. Example:
     *
     * **NOTE:** In previous releases, a string was treated as a method on
     * `Ext.util.Format` but that is now handled by the {@link #formatter} config.
     *
     * @param {Object} value The data value for the current cell
     *
     *     renderer: function(value){
     *         // evaluates `value` to append either `person' or `people`
     *         return Ext.util.Format.plural(value, 'person', 'people');
     *     }
     *
     * @param {Object} metaData A collection of metadata about the current cell; can be
     * used or modified by the renderer. Recognized properties are: `tdCls`, `tdAttr`,
     * and `tdStyle`.
     *
     * To add style attributes to the `&lt;td>` element, you must use the `tdStyle`
     * property. Using a style attribute in the `tdAttr` property will override the
     * styles the column sets, such as the width which will break the rendering.
     *
     * You can see an example of using the metaData parameter below.
     *
     *      Ext.create('Ext.data.Store', {
     *           storeId: 'simpsonsStore',
     *           fields: ['class', 'attr', 'style'],
     *           data: {
     *               'class': 'red-bg',
     *               'attr': 'lightyellow',
     *               'style': 'red'
     *           }
     *      });
     *
     *      Ext.create('Ext.grid.Panel', {
     *           title: 'Simpsons',
     *           store: Ext.data.StoreManager.lookup('simpsonsStore'),
     *           columns: [{
     *               text: 'Name',
     *               dataIndex: 'class',
     *               renderer: function (value, metaData) {
     *                   metaData.tdCls = value;
     *                   return value;
     *               }
     *           }, {
     *               text: 'Email',
     *               dataIndex: 'attr',
     *               flex: 1,
     *               renderer: function (value, metaData) {
     *                   metaData.tdAttr = 'bgcolor="' + value + '"';
     *                   return value;
     *               }
     *           }, {
     *               text: 'Phone',
     *               dataIndex: 'style',
     *               renderer: function (value, metaData) {
     *                   metaData.tdStyle = 'color:' + value;
     *                   return value;
     *               }
     *           }],
     *           height: 200,
     *           width: 400,
     *           renderTo: Ext.getBody()
     *       });
     *
     * @param {Ext.data.Model} record The record for the current row
     *
     *     renderer: function (value, metaData, record) {
     *         // evaluate the record's `updated` field and if truthy return the value
     *         // from the `newVal` field, else return value
     *         var updated = record.get('updated');
     *         return updated ? record.get('newVal') : value;
     *     }
     *
     * @param {Number} rowIndex The index of the current row
     *
     *     renderer: function (value, metaData, record, rowIndex) {
     *         // style the cell differently for even / odd values
     *         var odd = (rowIndex % 2 === 0);
     *         metaData.tdStyle = 'color:' + (odd ? 'gray' : 'red');
     *     }
     *
     * @param {Number} colIndex The index of the current column
     *
     *     var myRenderer = function(value, metaData, record, rowIndex, colIndex) {
     *         if (colIndex === 0) {
     *             metaData.tdAttr = 'data-qtip=' + value;
     *         }
     *         // additional logic to apply to values in all columns
     *         return value;
     *     }
     *
     *     // using the same renderer on all columns you can process the value for
     *     // each column with the same logic and only set a tooltip on the first column
     *     renderer: myRenderer
     *
     * _See also {@link Ext.tip.QuickTipManager}_
     *
     * @param {Ext.data.Store} store The data store
     *
     *     renderer: function (value, metaData, record, rowIndex, colIndex, store) {
     *         // style the cell differently depending on how the value relates to the
     *         // average of all values
     *         var average = store.average('grades');
     *         metaData.tdCls = (value < average) ? 'needsImprovement' : 'satisfactory';
     *         return value;
     *     }
     *
     * @param {Ext.view.View} view The data view
     *
     *     renderer: function (value, metaData, record, rowIndex, colIndex, store, view) {
     *         // style the cell using the dataIndex of the column
     *         var headerCt = this.getHeaderContainer(),
     *             column = headerCt.getHeaderAtIndex(colIndex);
     *
     *         metaData.tdCls = 'app-' + column.dataIndex;
     *         return value;
     *     }
     *
     * @return {String}
     * The HTML string to be rendered.
     * @controllable
     */
    renderer: false,

    /**
     * @cfg {Function/String} updater
     * An updater is a method which is used when records are updated, and an *existing* grid row
     * needs updating. The method is passed the cell element and may manipulate it in any way.
     *
     * **Note**: The updater is required to insert the {@link #emptyCellText} if there
     * is no value in the cell.
     *
     *     Ext.create('Ext.grid.Panel', {
     *         title: 'Grades',
     *         store: {
     *             fields: ['originalScore', 'newScore'],
     *             data: [{
     *                 originalScore: 70,
     *                 newScore: 70
     *             }]
     *         },
     *         columns: [{
     *             text: 'Score',
     *             dataIndex: 'newScore',
     *             editor: 'numberfield',
     *             flex: 1,
     *             updater: function (cell, value, record, view) {
     *                 var inner = Ext.get(cell).first(),
     *                     originalScore = record.get('originalScore'),
     *                     color = (value === originalScore)
     *                         ? 'black' : (value > originalScore) ? 'green' : 'red';
     *
     *                 // set the color based on the current value
     *                 // relative to the originalScore value
     *                 // * same   = black
     *                 // * higher = green
     *                 // * less   = red
     *                 inner.applyStyles({
     *                     color: color
     *                 });
     *                 // pass the value to the cell's inner el
     *                 inner.setHtml(value);
     *             }
     *         }],
     *         height: 200,
     *         width: 400,
     *         renderTo: document.body,
     *         tbar: [{
     *             xtype: 'numberfield',
     *             fieldLabel: 'New Score',
     *             value: 70,
     *             listeners: {
     *                 change: function (field, newValue) {
     *                     this.up('grid').getStore().first().set('newScore', newValue);
     *                 }
     *             }
     *         }]
     *     });
     *
     * If a string is passed it is assumed to be the name of a method defined by the
     * {@link #method-getController ViewController} or an ancestor component configured as
     * {@link #defaultListenerScope}.
     * @cfg {HTMLElement} updater.cell The HTML cell element to update.
     * @cfg {Object} updater.value The data value for the current cell
     * @cfg {Ext.data.Model} updater.record The record for the current row
     * @cfg {Ext.view.View} updater.view The current view
     *
     * **Note**: The updater is required to insert the {@link #emptyCellText} if there is no value
     * in the cell.
     *
     * @controllable
     */

    /**
     * @cfg {Object} scope
     * The scope to use when calling the
     * {@link Ext.grid.column.Column#renderer} function.
     */

    /**
     * @method defaultRenderer
     * When defined this will take precedence over the
     * {@link Ext.grid.column.Column#renderer renderer} config.
     * This is meant to be defined in subclasses that wish to supply their own renderer.
     * @protected
     * @template
     */

    /**
     * @cfg {Function/String} editRenderer
     * A renderer to be used in conjunction with
     * {@link Ext.grid.plugin.RowEditing RowEditing}. This renderer is used to display a
     * custom value for non-editable fields.
     *
     * **Note:** The editRenderer is called when the roweditor is initially shown.
     * Changes to the record during editing will not call editRenderer.
     *
     *     var store = Ext.create('Ext.data.Store', {
     *         fields: ['name', 'email'],
     *         data: [{
     *             "name": "Finn",
     *             "email": "finn@adventuretime.com"
     *         }, {
     *             "name": "Jake",
     *             "email": "jake@adventuretime.com"
     *         }]
     *     });
     *
     *     Ext.create('Ext.grid.Panel', {
     *         title: 'Land Of Ooo',
     *         store: store,
     *         columns: [{
     *             text: 'Name',
     *             dataIndex: 'name',
     *             editRenderer: function(value){
     *                 return '<span style="color:gray;">' + value + '</span>';
     *               }
     *         }, {
     *             text: 'Email',
     *             dataIndex: 'email',
     *             flex: 1,
     *             editor: {
     *                 xtype: 'textfield',
     *                 allowBlank: false
     *             }
     *         }],
     *         plugins: {
     *             rowediting: {
     *                 clicksToEdit: 1
     *             }
     *         },
     *         height: 200,
     *         width: 400,
     *         renderTo: document.body
     *     });
     *
     * @param {Object} value The data value for the current cell
     *
     *     editRenderer: function(value){
     *         // evaluates `value` to append either `person' or `people`
     *         return Ext.util.Format.plural(value, 'person', 'people');
     *     }
     *
     * @param {Object} metaData **Note:** The metadata param is passed to the
     * editRenderer, but is not used.
     *
     * @param {Ext.data.Model} record The record for the current row
     *
     *     editRenderer: function (value, metaData, record) {
     *         // evaluate the record's `updated` field and if truthy return the value
     *         // from the `newVal` field, else return value
     *         var updated = record.get('updated');
     *         return updated ? record.get('newVal') : value;
     *     }
     *
     * @param {Number} rowIndex The index of the current row
     *
     *     editRenderer: function (value, metaData, record, rowIndex) {
     *         // style the value differently for even / odd values
     *         var odd = (rowIndex % 2 === 0),
     *             color = (odd ? 'gray' : 'red');
     *         return '<span style="color:' + color + ';">' + value + '</span>';
     *     }
     *
     * @param {Number} colIndex The index of the current column
     *
     * @param {Ext.data.Store} store The data store
     *
     *     editRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
     *         // style the cell differently depending on how the value relates to the
     *         // average of all values
     *         var average = store.average('grades'),
     *             status = (value < average) ? 'needsImprovement' : 'satisfactory';
     *         return '<span class="' + status + '">' + value + '</span>';
     *     }
     *
     * @param {Ext.view.View} view The data view
     *
     *     editRenderer: function (value, metaData, record, rowIndex, colIndex, store, view) {
     *         // style the value using the dataIndex of the column
     *         var headerCt = this.getHeaderContainer(),
     *             column = headerCt.getHeaderAtIndex(colIndex);
     *
     *         return '<span class="app-' + column.dataIndex + '">' + value + '</span>';
     *     }
     *
     * @return {String}
     * The HTML string to be rendered.
     * @controllable
     */

    /**
     * @cfg {Function/String} summaryRenderer
     * A renderer to be used in conjunction with the {@link Ext.grid.feature.Summary Summary} or
     * {@link Ext.grid.feature.GroupingSummary GroupingSummary} features. This renderer is used to
     * display a summary value for this column.
     * @controllable
     */

    /**
     * @cfg {Boolean} draggable
     * False to disable drag-drop reordering of this column.
     */
    draggable: true,

    /**
     * @cfg {String} tooltip
     * A tooltip to display for this column header
     */

    /**
     * @cfg {String} [tooltipType="qtip"]
     * The type of {@link #tooltip} to use. Either 'qtip' for QuickTips or 'title' for title
     * attribute.
     */
    tooltipType: 'qtip',

    // Header does not use the typical ComponentDraggable class and therefore we
    // override this with an emptyFn. It is controlled at the HeaderDragZone.
    initDraggable: Ext.emptyFn,

    /**
     * @cfg {String} tdCls
     * A CSS class names to apply to the table cells for this column.
     */
    tdCls: '',

    /**
     * @cfg {Object/String} editor
     * An optional xtype or config object for a {@link Ext.form.field.Field Field} to use
     * for editing.
     * Only applicable if the grid is using an {@link Ext.grid.plugin.Editing Editing} plugin.
     *
     * **Note:** The {@link Ext.form.field.HtmlEditor HtmlEditor} field is not a
     * supported editor field type.
     */

    /**
     * @cfg {String} [dirtyText="Cell value has been edited"]
     * This text will be announced by Assistive Technologies such as screen readers when
     * a cell with changed ("dirty") value is focused.
     * @locale
     */
    dirtyText: "Cell value has been edited",

    /**
     * @cfg {Object/String} field
     * Alias for {@link #editor}.
     * @deprecated 4.0.5 Use {@link #editor} instead.
     */

    /**
     * @cfg {Boolean} producesHTML
     * This flag indicates that the renderer produces HTML.
     *
     * If this column is going to be updated rapidly, and the
     * {@link Ext.grid.column.Column#renderer} or {@link #cfg-updater} only produces
     * text, then to avoid the expense of HTML parsing and element production during the
     * update, this property may be configured as `false`.
     */
    producesHTML: true,

    /**
     * @cfg {Boolean} ignoreExport
     * This flag indicates that this column will be ignored when grid data is exported.
     *
     * When grid data is exported you may want to export only some columns that are important
     * and not everything. Widget, check and action columns are not relevant when data is
     * exported. You can set this flag on any column that you want to be ignored during export.
     *
     * This is used by {@link Ext.grid.plugin.Clipboard clipboard plugin} and
     * {@link Ext.grid.plugin.Exporter exporter plugin}.
     */
    ignoreExport: false,

    /**
     * @cfg {Ext.exporter.file.Style/Ext.exporter.file.Style[]} exportStyle
     *
     * A style definition that is used during data export via the {@link Ext.grid.plugin.Exporter}.
     * This style will be applied to the columns generated in the exported file.
     *
     * You could define it as a single object that will be used by all exporters:
     *
     *      {
     *          xtype: 'numbercolumn',
     *          dataIndex: 'price',
     *          text: 'Price',
     *          exportStyle: {
     *              format: 'Currency',
     *              alignment: {
     *                  horizontal: 'Right'
     *              },
     *              font: {
     *                  italic: true
     *              }
     *          }
     *      }
     *
     * You could also define it as an array of objects, each object having a `type`
     * that specifies by which exporter will be used:
     *
     *      {
     *          xtype: 'numbercolumn',
     *          dataIndex: 'price',
     *          text: 'Price',
     *          exportStyle: [{
     *              type: 'html', // used by the `html` exporter
     *              format: 'Currency',
     *              alignment: {
     *                  horizontal: 'Right'
     *              },
     *              font: {
     *                  italic: true
     *              }
     *          },{
     *              type: 'csv', // used by the `csv` exporter
     *              format: 'General'
     *          }]
     *      }
     *
     * Or you can define it as an array of objects that has:
     *
     * - one object with no `type` key that is considered the style to use by all exporters
     * - objects with the `type` key defined that are exceptions of the above rule
     *
     *
     *      {
     *          xtype: 'numbercolumn',
     *          dataIndex: 'price',
     *          text: 'Price',
     *          exportStyle: [{
     *              // no type defined means this is the default
     *              format: 'Currency',
     *              alignment: {
     *                  horizontal: 'Right'
     *              },
     *              font: {
     *                  italic: true
     *              }
     *          },{
     *              type: 'csv', // only the CSV exporter has a special style
     *              format: 'General'
     *          }]
     *      }
     *
     */
    exportStyle: null,

    /**
     * @cfg {Boolean/Function/String} exportRenderer
     *
     * During data export via the {@link Ext.grid.plugin.Exporter} plugin the data for
     * this column could be formatted in multiple ways:
     *
     * - using the `exportStyle.format`
     * - using the `formatter` if no `exportStyle` is defined
     * - using the `exportRenderer`
     *
     * If you want to use the `renderer` defined on this column then set `exportRenderer`
     * to `true`. Beware that this should only happen if the `renderer` deals only with
     * data on the record or value and it does NOT style the cell or returns an html
     * string.
     *
     *      {
     *          xtype: 'numbercolumn',
     *          dataIndex: 'price',
     *          text: 'Price',
     *          renderer: function (value, metaData, record, rowIndex, colIndex, store, view) {
     *              return Ext.util.Format.currency(value);
     *          },
     *          exportRenderer: true
     *      }
     *
     * If you don't want to use the `renderer` during export but you still want to format
     * the value in a special way then you can provide a function to `exportRenderer` or
     * a string (which is a function name on the ViewController).
     * The provided function has the same signature as the renderer.
     *
     *      {
     *          xtype: 'numbercolumn',
     *          dataIndex: 'price',
     *          text: 'Price',
     *          exportRenderer: function (value, metaData, record, rowIndex, colIndex, store, view){
     *              return Ext.util.Format.currency(value);
     *          }
     *      }
     *
     *
     *      {
     *          xtype: 'numbercolumn',
     *          dataIndex: 'price',
     *          text: 'Price',
     *          exportRenderer: 'exportAsCurrency' // this is a function on the ViewController
     *      }
     *
     *
     * If `exportStyle.format`, `formatter` and `exportRenderer` are all defined on the
     * column then the `exportStyle` wins and will be used to format the data for this
     * column.
     */
    exportRenderer: false,

    /**
     * @cfg {Boolean/Function/String} exportSummaryRenderer
     *
     * This config is similar to {@link #exportRenderer} but is applied to summary
     * records.
     *
     */
    exportSummaryRenderer: false,

    /**
     * @property {Ext.dom.Element} triggerEl
     * Element that acts as button for column header dropdown menu.
     */

    /**
     * @property {Ext.dom.Element} textEl
     * Element that contains the text in column header.
     */

    /**
     * @cfg {Boolean} [cellFocusable=true]
     * Configure as `false` to remove all cells in this column from navigation.
     *
     * This is currently used by the PivotGrid package to create columns which have
     * no semantic role, but are purely for visual indentation purposes.
     * @since 6.2.0.
     */

    /**
     * @property {Boolean} isHeader
     * @deprecated 6.5.0 see isColumn
     * Set in this class to identify, at runtime, instances which are not instances of the
     * HeaderContainer base class, but are in fact, the subclass: Header.
     */
    isHeader: true,

    /**
     * @property {Boolean} isColumn
     * @readonly
     * Set in this class to identify, at runtime, instances which are not instances of the
     * HeaderContainer base class, but are in fact simple column headers.
     */
    isColumn: true,

    scrollable: false, // Override scrollable config from HeaderContainr class

    requiresMenu: false, // allow plugins to set this property to influence if menu can be disabled

    tabIndex: -1,

    ascSortCls: Ext.baseCSSPrefix + 'column-header-sort-ASC',
    descSortCls: Ext.baseCSSPrefix + 'column-header-sort-DESC',

    componentLayout: 'columncomponent',

    groupSubHeaderCls: Ext.baseCSSPrefix + 'group-sub-header',

    groupHeaderCls: Ext.baseCSSPrefix + 'group-header',

    clickTargetName: 'titleEl',

    // So that when removing from group headers which are then empty and then get destroyed,
    // there's no child DOM left
    detachOnRemove: true,

    // We need to override the default component resizable behaviour here
    initResizable: Ext.emptyFn,

    // Property names to reference the different types of renderers and formatters that
    // we can use.
    rendererNames: {
        column: 'renderer',
        edit: 'editRenderer',
        summary: 'summaryRenderer'
    },
    formatterNames: {
        column: 'formatter',
        edit: 'editFormatter',
        summary: 'summaryFormatter'
    },

    initComponent: function() {
        var me = this;

        // Preserve the scope to resolve a custom renderer.
        // Subclasses (TreeColumn) may insist on scope being this.
        if (!me.rendererScope) {
            me.rendererScope = me.scope;
        }

        if (me.header != null) {
            me.text = me.header;
            me.header = null;
        }

        if (me.cellWrap) {
            me.tdCls = (me.tdCls || '') + ' ' + Ext.baseCSSPrefix + 'wrap-cell';
        }

        // A group header; It contains items which are themselves Headers
        if (me.columns != null) {
            me.isGroupHeader = true;
            me.ariaRole = 'presentation';

            //<debug>
            if (me.dataIndex) {
                Ext.raise('Ext.grid.column.Column: Group header may not accept a dataIndex');
            }

            if ((me.width && me.width !== Ext.grid.header.Container.prototype.defaultWidth)) {
                Ext.raise('Ext.grid.column.Column: Group header does not support ' +
                          'setting explicit widths. A group header either shrinkwraps ' +
                          'its children, or must be flexed.');
            }
            //</debug>

            // The headers become child items
            me.items = me.columns;
            me.columns = null;
            me.cls = (me.cls || '') + ' ' + me.groupHeaderCls;

            // A group cannot be sorted, or resized - it shrinkwraps its children
            me.sortable = me.resizable = false;
            me.align = 'center';
        }
        else {
            // Flexed Headers need to have a minWidth defined so that they can never be squeezed out
            // of existence by the HeaderContainer's specialized Box layout, the ColumnLayout.
            // The ColumnLayout's overridden calculateChildboxes method extends the available layout
            // space to accommodate the "desiredWidth" of all the columns.
            if (me.flex) {
                me.minWidth = me.minWidth || Ext.grid.plugin.HeaderResizer.prototype.minColWidth;
            }
        }

        me.addCls(Ext.baseCSSPrefix + 'column-header-align-' + me.getMappedAlignment(me.align));

        // Set up the renderer types: 'renderer', 'editRenderer', and 'summaryRenderer'
        me.setupRenderer();
        me.setupRenderer('edit');
        me.setupRenderer('summary');

        // Initialize as a HeaderContainer
        me.callParent();
    },

    beforeLayout: function() {
        var me = this,
            items = me.items,
            colCount = 0,
            flex = me.flex,
            len, i, item, hasFlexedChildren;

        if (flex && me.isGroupHeader) {
            if (!Ext.isArray(items)) {
                items = items.items;
            }

            len = items.length;

            for (i = 0; !hasFlexedChildren && i < len; i++) {
                item = items[i];

                if (item.isColumn && !item.hidden) {
                    ++colCount;
                    hasFlexedChildren = item.flex;
                }
            }

            // If all child columns have been given a width, we must fall back to shrinkwrapping
            // them. Save any current flex state and restore it once the layout finishes so this
            // column isn't permanently flexed
            if (!hasFlexedChildren && colCount) {
                me.savedFlex = flex;
                me.flex = null;
            }
        }

        me.callParent();
    },

    onAdded: function(container, pos, instanced) {
        var me = this;

        me.callParent([container, pos, instanced]);

        // Invalidate references, so that when asked for, they have to be regathered
        me.view = me.rootHeaderCt = me.cellSelector = me.visibleIndex = null;

        if (!me.headerId) {
            me.calculateHeaderId();
        }

        me.configureStateInfo();
    },

    _initSorterFn: function(a, b) {
        // NOTE: this method is placed as a "sorterFn" on a Sorter instance,
        // so "this" is not a Column! Our goal is to replace the sorterFn of
        // this Sorter on first use and then never get called again.
        var sorter = this,
            column = sorter.column,
            scope = column.resolveListenerScope(),
            name = sorter.methodName,
            fn = scope && scope[name],
            ret = 0;

        if (fn) {
            sorter.setSorterFn(fn);
            sorter.column = null; // no need anymore (GC friendly)

            // We are called by sort() so the ASC/DESC will be applied to what
            // we return. Therefore, the correct delegation is to directly call
            // the real sorterFn directly.
            ret = fn.call(scope, a, b);
        }
        //<debug>
        else if (!scope) {
            Ext.raise('Cannot resolve scope for column ' + column.id);
        }
        else {
            Ext.raise('No such method "' + name + '" on ' + scope.$className);
        }
        //</debug>

        return ret;
    },

    applySorter: function(sorter) {
        var me = this,
            sorterFn = sorter ? sorter.sorterFn : null,
            tablepanel, ret;

        if (typeof sorterFn === 'string') {
            // Instead of treating a string as a fieldname, it makes more sense to
            // expect it to be a sortFn on the controller.
            ret = new Ext.util.Sorter(Ext.applyIf({
                sorterFn: me._initSorterFn
            }, sorter));

            ret.methodName = sorterFn;
            ret.column = me;
        }
        else {
            tablepanel = me.getRootHeaderCt().up('tablepanel');
            // Have the sorter spec decoded by the collection that will host it.
            ret = tablepanel.store.getData().getSorters().decodeSorter(sorter);
        }

        return ret;
    },

    updateAlign: function(align) {
        // Translate according to the locale.
        // This property is read by Ext.view.Table#renderCell
        this.textAlign = this.getMappedAlignment(align);
    },

    bindFormatter: function(format) {
        var me = this;

        return function(v) {
            return format(v, me.rendererScope || me.resolveListenerScope());
        };
    },

    bindRenderer: function(renderer) {
        var me = this;

        //<debug>
        if (renderer in Ext.util.Format) {
            Ext.log.warn('Use "formatter" config instead of "renderer" to use ' +
                         'Ext.util.Format to format cell values');
        }

        //</debug>
        me.hasCustomRenderer = true;

        return function() {
            return Ext.callback(renderer, me.rendererScope, arguments, 0, me);
        };
    },

    setupRenderer: function(type) {
        var me = this,
            format, renderer, isColumnRenderer, parser, dynamic;

        // type can be null or 'edit', or 'summary'
        type = type || 'column';
        format = me[me.formatterNames[type]];
        renderer = me[me.rendererNames[type]];
        isColumnRenderer = type === 'column';

        if (!format) {
            if (renderer) {
                // Resolve a string renderer into the correct property: 'renderer',
                // 'editRenderer', or 'summaryRenderer'
                if (typeof renderer === 'string') {
                    renderer = me[me.rendererNames[type]] = me.bindRenderer(renderer);
                    dynamic = true;
                }

                if (isColumnRenderer) {
                    // If we are setting up a normal column renderer, detect if it's a custom one
                    // (reads more than one parameter)
                    // We can't read the arg list until we resolve the scope, so we must assume
                    // it's a renderer that needs a full update if it's dynamic
                    me.hasCustomRenderer = dynamic || me.shouldFlagCustomRenderer(renderer);
                }
            }
            // Column renderer could not be resolved: use the default one.
            else if (isColumnRenderer && me.defaultRenderer) {
                me.renderer = me.defaultRenderer;
                me.usingDefaultRenderer = true;
            }
        }
        else {
            /**
             * @cfg {String} formatter
             * This config accepts a format specification as would be used in a `Ext.Template`
             * formatted token. For example `'round(2)'` to round numbers to 2 decimal places
             * or `'date("Y-m-d")'` to format a Date.
             *
             * In previous releases the `renderer` config had limited abilities to use one
             * of the `Ext.util.Format` methods but `formatter` now replaces that usage and
             * can also handle formatting parameters.
             *
             * When the value begins with `"this."` (for example, `"this.foo(2)"`), the
             * implied scope on which "foo" is found is the `scope` config for the column.
             *
             * If the `scope` is not given, or implied using a prefix of `"this"`, then either the
             * {@link #method-getController ViewController} or the closest ancestor component
             * configured as {@link #defaultListenerScope} is assumed to be the object
             * with the method.
             * @since 5.0.0
             */
            parser = Ext.app.bind.Parser.fly(format);
            format = parser.compileFormat();
            parser.release();

            // processed - trees come back here to add its renderer
            me[me.formatterNames[type]] = null;

            // Set up the correct property: 'renderer', 'editRenderer', or 'summaryRenderer'
            me[me.rendererNames[type]] = me.bindFormatter(format);
        }
    },

    getView: function() {
        var rootHeaderCt;

        // Only traverse to get our view once.
        if (!this.view) {
            rootHeaderCt = this.getRootHeaderCt();

            if (rootHeaderCt) {
                this.view = rootHeaderCt.view;
            }
        }

        return this.view;
    },

    onFocusLeave: function(e) {
        this.callParent([e]);

        if (this.activeMenu) {
            this.activeMenu.hide();
        }
    },

    initItems: function() {
        var me = this;

        me.callParent(arguments);

        if (me.isGroupHeader) {
            // We need to hide the groupheader straightaway if it's configured as hidden
            // or all its children are.
            if (me.config.hidden || !me.hasVisibleChildColumns()) {
                me.hide();
            }
        }
    },

    hasVisibleChildColumns: function() {
        var items = this.items.items,
            len = items.length,
            i, item;

        for (i = 0; i < len; ++i) {
            item = items[i];

            if (item.isColumn && !item.hidden) {
                return true;
            }
        }

        return false;
    },

    onAdd: function(child) {
        var me = this;

        if (child.isColumn) {
            child.isSubHeader = true;
            child.addCls(me.groupSubHeaderCls);
        }

        if (me.isGroupHeader && me.hidden && me.hasVisibleChildColumns()) {
            me.show();
        }

        me.callParent([child]);
    },

    onRemove: function(child, isDestroying) {
        var me = this;

        if (child.isSubHeader) {
            child.isSubHeader = false;
            child.removeCls(me.groupSubHeaderCls);
        }

        me.callParent([child, isDestroying]);

        // By this point, the component will be removed from the items collection.
        //
        // Note that we don't want to remove any grouped headers that have a descendant
        // that is currently the drag target of an even lower stacked grouped header.
        // See the comments in Ext.grid.header.Container#isNested.
        if (!(me.destroyed || me.destroying) && !me.hasVisibleChildColumns() &&
            (me.ownerCt && !me.ownerCt.isNested())) {
            me.hide();
        }
    },

    initRenderData: function() {
        var me = this,
            tipMarkup = '',
            tip = me.tooltip,
            text = me.text,
            attr = me.tooltipType === 'qtip' ? 'data-qtip' : 'title';

        if (!Ext.isEmpty(tip)) {
            tipMarkup = attr + '="' + tip + '" ';
        }

        return Ext.applyIf(me.callParent(arguments), {
            text: text,
            empty: me.isEmptyText(text),
            menuDisabled: me.menuDisabled,
            tipMarkup: tipMarkup,
            triggerStyle: this.getTriggerVisible() ? 'display:block' : ''
        });
    },

    applyColumnState: function(state, storeState) {
        var me = this,
            sorter = me.getSorter(),
            stateSorters = storeState && storeState.sorters,
            len, i, savedSorter, mySorterId;

        // If we have been configured with a sorter, then there SHOULD be a sorter config
        // in the storeState with a corresponding ID from which we must restore our sorter's state.
        // (The only state we can restore is direction).
        // Then we replace the state entry with the real sorter. We MUST do this because the sorter
        // is likely to have a custom sortFn.
        if (sorter && stateSorters && (len = stateSorters.length)) {
            mySorterId = sorter.getId();

            for (i = 0; !savedSorter && i < len; i++) {
                if (stateSorters[i].id === mySorterId) {
                    sorter.setDirection(stateSorters[i].direction);
                    stateSorters[i] = sorter;
                    break;
                }
            }
        }

        // apply any columns
        me.applyColumnsState(state.columns);

        // Only state properties which were saved should be restored.
        // (Only user-changed properties were saved by getState)
        if (state.hidden != null) {
            me.hidden = state.hidden;
        }

        if (state.locked != null) {
            me.locked = state.locked;
        }

        if (state.sortable != null) {
            me.sortable = state.sortable;
        }

        if (state.width != null) {
            me.flex = null;
            me.width = state.width;
        }
        else if (state.flex != null) {
            me.width = null;
            me.flex = state.flex;
        }
    },

    getColumnState: function() {
        var me = this,
            items = me.items.items,
            state = {
                id: me.getStateId()
            };

        me.savePropsToState(['hidden', 'sortable', 'locked', 'flex', 'width'], state);

        // Check for the existence of items, since column.Action won't have them
        if (me.isGroupHeader && items && items.length) {
            state.columns = me.getColumnsState();
        }

        if ('width' in state) {
            delete state.flex; // width wins
        }

        return state;
    },

    /**
     * Sets the header text for this Column.
     * @param {String} text The header to display on this Column.
     */
    setText: function(text) {
        var me = this,
            grid;

        me.text = text;

        if (me.rendered) {
            grid = me.getView().ownerGrid;
            me.textInnerEl.setHtml(text);

            me.titleEl.toggleCls(
                Ext.baseCSSPrefix + 'column-header-inner-empty', me.isEmptyText(text)
            );

            grid.syncHeaderVisibility();
        }
    },

    /**
     * Returns the index of this column only if this column is a base level Column. If it
     * is a group column, it returns `false`.
     * @return {Number}
     */
    getIndex: function() {
        return this.isGroupColumn ? false : this.getRootHeaderCt().getHeaderIndex(this);
    },

    /**
     * Returns the index of this column in the list of *visible* columns only if this column
     * is a base level Column. If it is a group column, it returns `false`.
     * @return {Number}
     */
    getVisibleIndex: function() {
        // Note that the visibleIndex property is assigned by the owning HeaderContainer
        // when assembling the visible column set for the view.
        // eslint-disable-next-line max-len
        return this.visibleIndex != null ? this.visibleIndex : this.isGroupColumn ? false : Ext.Array.indexOf(this.getRootHeaderCt().getVisibleGridColumns(), this);
    },

    getLabelChain: function() {
        var child = this,
            labels = [],
            parent;

        while ((parent = child.up('headercontainer'))) {
            if (parent.text) {
                labels.unshift(Ext.util.Format.stripTags(parent.text));
            }

            child = parent;
        }

        return labels;
    },

    beforeRender: function() {
        var me = this,
            rootHeaderCt = me.getRootHeaderCt(),
            isSortable = me.isSortable(),
            labels = [],
            ariaAttr;

        me.textAlign = me.getMappedAlignment(me.getAlign());

        me.callParent();

        // Disable the menu if there's nothing to show in the menu, ie:
        // Column cannot be sorted, grouped or locked, and there are no grid columns
        // which may be hidden
        if (!me.requiresMenu && !isSortable && !me.groupable &&
                 !me.lockable && (rootHeaderCt.grid.enableColumnHide === false ||
                 !rootHeaderCt.getHideableColumns().length)) {
            me.menuDisabled = true;
        }

        // Wrapping text may cause unpredictable line heights.
        // variableRowHeight is interrogated by the View for all visible columns to determine
        // whether addition of new rows should cause an ExtJS layout.
        // The View's summation of the presence of visible variableRowHeight columns is also used by
        // any buffered renderer to determine how row height should be calculated when determining
        // scroll range.
        if (me.cellWrap) {
            me.variableRowHeight = true;
        }

        ariaAttr = me.ariaRenderAttributes || (me.ariaRenderAttributes = {});

        // Ext JS does not support editable column headers
        ariaAttr['aria-readonly'] = true;

        if (isSortable) {
            ariaAttr['aria-sort'] = me.ariaSortStates[me.sortState];
        }

        if (me.isSubHeader) {
            labels = me.getLabelChain();

            if (me.text) {
                labels.push(Ext.util.Format.stripTags(me.text));
            }

            if (labels.length) {
                ariaAttr['aria-label'] = labels.join(' ');
            }
        }

        me.protoEl.unselectable();
    },

    getTriggerElWidth: function() {
        var me = this,
            triggerEl = me.triggerEl,
            width = me.self.triggerElWidth;

        if (triggerEl && width === undefined) {
            triggerEl.setStyle('display', 'block');
            width = me.self.triggerElWidth = triggerEl.getWidth();
            triggerEl.setStyle('display', '');
        }

        return width;
    },

    afterComponentLayout: function(width, height, oldWidth, oldHeight) {
        var me = this,
            rootHeaderCt = me.getRootHeaderCt(),
            savedFlex = me.savedFlex;

        me.callParent([width, height, oldWidth, oldHeight]);

        if (rootHeaderCt && (oldWidth != null || me.flex) && width !== oldWidth) {
            rootHeaderCt.onHeaderResize(me, width);
        }

        if (savedFlex) {
            me.flex = savedFlex;
            delete me.savedFlex;
        }
    },

    doDestroy: function() {
        // force destroy on the textEl, IE reports a leak
        Ext.destroy(this.field, this.editor);

        this.callParent();
    },

    onTitleMouseOver: function() {
        this.titleEl.addCls(this.hoverCls);
    },

    onTitleMouseOut: function() {
        this.titleEl.removeCls(this.hoverCls);
    },

    onDownKey: function(e) {
        if (this.triggerEl) {
            this.onTitleElClick(e, this.triggerEl.dom || this.el.dom);
        }
    },

    onEnterKey: function(e) {
        this.onTitleElClick(e, this.el.dom);
    },

    /**
     * @private
     * Double click handler which, if on left or right edges, auto-sizes the column to the left.
     * @param e The dblclick event
     */
    onTitleElDblClick: function(e) {
        var me = this,
            prev,
            leafColumns,
            headerCt;

        // On left edge, resize previous *leaf* column in the grid
        if (me.isAtStartEdge(e)) {

            // Look for the previous visible column header which is a leaf
            // Note: previousNode can walk out of the container (this may be first child of a group)
            prev = me.previousNode('gridcolumn:not([hidden]):not([isGroupHeader])');

            // If found in the same grid, auto-size it
            if (prev && prev.getRootHeaderCt() === me.getRootHeaderCt()) {
                prev.autoSize();
            }
        }
        // On right edge, resize this column, or last sub-column within it
        else if (me.isAtEndEdge(e)) {

            // Click on right but in child container - auto-size last leaf column
            if (me.isGroupHeader && e.getPoint().isContainedBy(me.layout.innerCt)) {
                leafColumns = me.query('gridcolumn:not([hidden]):not([isGroupHeader])');
                leafColumns[leafColumns.length - 1].autoSize();

                return;
            }
            else {
                headerCt = me.getRootHeaderCt();

                // Cannot resize the only column in a forceFit grid.
                if (headerCt.visibleColumnManager.getColumns().length === 1 && headerCt.forceFit) {
                    return;
                }
            }

            me.autoSize();
        }
    },

    /**
     * Sizes this Column to fit the max content width.
     * *Note that group columns shrink-wrap around the size of leaf columns. Auto sizing
     * a group column auto-sizes descendant leaf columns.*
     */
    autoSize: function() {
        var me = this,
            leafColumns,
            numLeaves, i,
            headerCt;

        if (me.resizable) {
            // Group headers are shrinkwrap width, so auto-sizing one means auto-sizing leaf
            // descendants.
            if (me.isGroupHeader) {
                leafColumns = me.query('gridcolumn:not([hidden]):not([isGroupHeader])');
                numLeaves = leafColumns.length;
                headerCt = me.getRootHeaderCt();
                Ext.suspendLayouts();

                for (i = 0; i < numLeaves; i++) {
                    headerCt.autoSizeColumn(leafColumns[i]);
                }

                Ext.resumeLayouts(true);

                return;
            }

            me.getRootHeaderCt().autoSizeColumn(me);
        }
    },

    isEmptyText: function(text, visual) {
        // visual means if there's no visual information, so even &npsb; and other hard spaces are
        // reported as empty. This is used to determine whether we should hideHeaders.
        if (visual) {
            return Ext.String.trim(text).length === 0;
        }
        // Non visual means there's really nothing there to shape the container.
        // So null and empty string is empty, but "hard" spaces like '\u00a0' are content.
        // This is to determine whether the "text is empty" CSS class should be applied.
        else {
            return text == null || text === '';
        }
    },

    onTitleElClick: function(e, t, sortOnClick) {
        var me = this,
            activeHeader, prevSibling, tapMargin;

        // Tap on the resize zone triggers the menu
        if (e.pointerType === 'touch') {
            prevSibling = me.previousSibling(':not([hidden])');

            // Tap on right edge, activate this header
            if (!me.menuDisabled) {
                tapMargin = parseInt(me.triggerEl.getStyle('width'), 10);

                // triggerEl can have width: auto, in which case we use handle width * 3
                // that yields 30px for touch events. Should be enough in most cases.
                if (isNaN(tapMargin)) {
                    tapMargin = me.getHandleWidth(e) * 3;
                }

                if (me.isAtEndEdge(e, tapMargin)) {
                    activeHeader = me;
                }
            }

            // Tap on left edge, activate previous header
            if (!activeHeader && prevSibling && !prevSibling.menuDisabled && me.isAtStartEdge(e)) {
                activeHeader = prevSibling;
            }
        }
        else {
            // Firefox doesn't check the current target in a within check.
            // Therefore we check the target directly and then within (ancestors)
            // eslint-disable-next-line max-len
            activeHeader = me.triggerEl && (e.target === me.triggerEl.dom || t === me.triggerEl || e.within(me.triggerEl)) ? me : null;
        }

        // If it's not a click on the trigger or extreme edges.
        // Or if we are called from a key handler, sort this column.
        if (sortOnClick !== false && (!activeHeader && !me.isAtStartEdge(e) &&
            !me.isAtEndEdge(e) || e.getKey())) {
            me.toggleSortState();
        }

        return activeHeader;
    },

    /**
     * @private
     * Process UI events from the view. The owning TablePanel calls this method, relaying events
     * from the TableView
     * @param {String} type Event type, eg 'click'
     * @param {Ext.view.Table} view TableView Component
     * @param {HTMLElement} cell Cell HTMLElement the event took place within
     * @param {Number} recordIndex Index of the associated Store Model (-1 if none)
     * @param {Number} cellIndex Cell index within the row
     * @param {Ext.event.Event} e Original event
     */
    processEvent: function(type, view, cell, recordIndex, cellIndex, e) {
        return this.fireEvent.apply(this, arguments);
    },

    isSortable: function() {
        var rootHeader = this.getRootHeaderCt(),
            grid = rootHeader ? rootHeader.grid : null,
            sortable = this.sortable;

        if (grid && grid.sortableColumns === false) {
            sortable = false;
        }

        return sortable;
    },

    toggleSortState: function() {
        if (this.isSortable()) {
            this.sort();
        }
    },

    sort: function(direction) {
        var me = this,
            grid = me.up('tablepanel'),
            store = grid.store,
            storeIsSorted = store.isSorted(),
            storeSorters = storeIsSorted && store.getSorters(),
            sorter = me.getSorter(),
            idx = storeSorters && storeSorters.indexOf(sorter),
            currentDirection;

        // Maintain backward compatibility.
        // If the grid is NOT configured with multi column sorting, then specify "replace".
        // Only if we are doing multi column sorting do we insert it as one of a multi set.
        // Suspend layouts in case multiple views depend upon this grid's store
        // (eg lockable assemblies)
        Ext.suspendLayouts();

        if (sorter) {
            currentDirection = sorter.getDirection();

            if (!direction || currentDirection !== direction || !storeIsSorted || idx === -1) {
                // when the store is not being sorted by the current sorter, we need to manually 
                // update the direction because the store will not take care of it.
                if ((!storeIsSorted || idx === -1) && currentDirection !== direction) {
                    sorter.setDirection(direction);
                }

                store.sort(sorter, grid.multiColumnSort ? 'multi' : 'replace');
            }
        }
        else {
            store.sort(me.getSortParam(), direction, grid.multiColumnSort ? 'multi' : 'replace');
        }

        Ext.resumeLayouts(true);
    },

    /**
     * Returns the parameter to sort upon when sorting this header.
     * By default this returns the dataIndex and will not need to be overridden in most cases.
     * @return {String}
     */
    getSortParam: function() {
        return this.dataIndex;
    },

    setSortState: function(sorter) {
        // Set the UI state to reflect the state of any passed Sorter
        // Called by the grid's HeaderContainer on view refresh
        var me = this,
            direction = sorter && sorter.getDirection(),
            ascCls = me.ascSortCls,
            descCls = me.descSortCls,
            rootHeaderCt = me.getRootHeaderCt(),
            ariaDom = me.ariaEl.dom,
            changed;

        switch (direction) {
            case 'DESC':
                if (!me.hasCls(descCls)) {
                    me.addCls(descCls);
                    me.sortState = 'DESC';
                    changed = true;
                }

                me.removeCls(ascCls);
                break;

            case 'ASC':
                if (!me.hasCls(ascCls)) {
                    me.addCls(ascCls);
                    me.sortState = 'ASC';
                    changed = true;
                }

                me.removeCls(descCls);
                break;

            default:
                me.removeCls([ascCls, descCls]);
                me.sortState = null;
                break;
        }

        if (ariaDom) {
            if (me.sortState) {
                ariaDom.setAttribute('aria-sort', me.ariaSortStates[me.sortState]);
            }
            else {
                ariaDom.removeAttribute('aria-sort');
            }
        }

        // we only want to fire the event if we have actually sorted
        if (changed) {
            rootHeaderCt.fireEvent('sortchange', rootHeaderCt, me, direction);
        }
    },

    /**
     * Determines whether the UI should be allowed to offer an option to hide this column.
     *
     * A column may *not* be hidden if to do so would leave the grid with no visible columns.
     *
     * This is used to determine the enabled/disabled state of header hide menu items.
     */
    isHideable: function() {
        var result = {
            hideCandidate: this,
            result: this.hideable
        };

        if (result.result) {
            this.ownerCt.bubble(this.hasOtherMenuEnabledChildren, null, [result]);
        }

        return result.result;
    },

    hasOtherMenuEnabledChildren: function(result) {
        // Private bubble function used in determining whether this column is hideable.
        // Executes in the scope of each component in the bubble sequence
        var visibleChildren,
            count;

        // If we've bubbled out the top of the topmost HeaderContainer without finding a level
        // with at least one visible, menu-enabled child *which is not the hideCandidate*, no hide!
        if (!this.isXType('headercontainer')) {
            result.result = false;

            return false;
        }

        // If we find an ancestor level with at least one visible, menu-enabled child
        // *which is not the hideCandidate*, then the hideCandidate is hideable.
        // Note that we are not using CQ #id matchers - ':not(#' + result.hideCandidate.id + ')' -
        // to exclude the hideCandidate because CQ queries are cached for the document's lifetime.
        visibleChildren = this.query('>gridcolumn:not([hidden]):not([menuDisabled])');
        count = visibleChildren.length;

        if (Ext.Array.contains(visibleChildren, result.hideCandidate)) {
            count--;
        }

        if (count) {
            return false;
        }

        // If we go up, it's because the hideCandidate was the only hideable child,
        // so *this* becomes the hide candidate.
        result.hideCandidate = this;
    },

    /**
     * Determines whether the UI should be allowed to offer an option to lock or unlock this column.
     * Note that this includes dragging a column into the opposite side of a
     * {@link Ext.panel.Table#enableLocking lockable} grid.
     *
     * A column may *not* be moved from one side to the other of a
     * {@link Ext.panel.Table#enableLocking lockable} grid if to do so would leave one side
     * with no visible columns.
     *
     * This is used to determine the enabled/disabled state of the lock/unlock
     * menu item used in {@link Ext.panel.Table#enableLocking lockable} grids, and to determine
     * droppabilty when dragging a header.
     */
    isLockable: function() {
        var result = {
            result: this.lockable !== false
        };

        if (result.result) {
            this.ownerCt.bubble(this.hasMultipleVisibleChildren, null, [result]);
        }

        return result.result;
    },

    /**
     * Determines whether this column is in the locked side of a grid.
     * It may be a descendant node of a locked column and as such will *not* have the
     * {@link #locked} flag set.
     */
    isLocked: function() {
        if (this.locked == null) {
            this.locked = this.getInherited().inLockedGrid;
        }

        return this.locked;
    },

    hasMultipleVisibleChildren: function(result) {
        // Private bubble function used in determining whether this column is lockable.
        // Executes in the scope of each component in the bubble sequence

        // If we've bubbled out the top of the topmost HeaderContainer
        // without finding a level with more than one visible child, no hide!
        if (!this.isXType('headercontainer')) {
            result.result = false;

            return false;
        }

        // If we find an ancestor level with more than one visible child, it's fine to hide
        if (this.query('>gridcolumn:not([hidden])').length > 1) {
            return false;
        }
    },

    hide: function() {
        var me = this,
            rootHeaderCt = me.getRootHeaderCt(),
            owner = me.getRefOwner();

        // During object construction, so just set the hidden flag and jump out
        if (owner.constructing) {
            me.callParent();

            return me;
        }

        if (me.rendered && !me.isVisible()) {
            // Already hidden
            return me;
        }

        // Save our last shown width so we can gain space when shown back into fully flexed
        // HeaderContainer. If we are, say, flex: 1 and all others are fixed width,
        // then removing will do a layout which will convert all widths to flexes
        // which will mean this flex value is too small.
        if (rootHeaderCt.forceFit) {
            me.visibleSiblingCount = rootHeaderCt.getVisibleGridColumns().length - 1;

            if (me.flex) {
                me.savedWidth = me.getWidth();
                me.flex = null;
            }
        }

        rootHeaderCt.beginChildHide();
        Ext.suspendLayouts();

        // owner is a group, hide call didn't come from the owner
        if (owner.isGroupHeader) {
            // The owner only has one item that isn't hidden and it's me; hide the owner.
            if (me.isNestedGroupHeader()) {
                owner.hide();
            }

            if (me.isSubHeader && !me.isGroupHeader &&
                owner.query('>gridcolumn:not([hidden])').length === 1) {
                owner.lastHiddenHeader = me;
            }
        }

        me.callParent();

        // Notify owning HeaderContainer. Will trigger a layout and a view refresh.
        rootHeaderCt.endChildHide();
        rootHeaderCt.onHeaderHide(me);

        Ext.resumeLayouts(true);

        return me;
    },

    show: function() {
        var me = this,
            rootHeaderCt = me.getRootHeaderCt(),
            ownerCt = me.getRefOwner();

        if (me.isVisible()) {
            return me;
        }

        if (ownerCt.isGroupHeader) {
            ownerCt.lastHiddenHeader = null;
        }

        if (me.rendered) {
            // Size all other columns to accommodate re-shown column.
            if (rootHeaderCt.forceFit) {
                rootHeaderCt.applyForceFit(me);
            }
        }

        Ext.suspendLayouts();

        // If a sub header, ensure that the group header is visible
        if (me.isSubHeader && ownerCt.hidden) {
            ownerCt.show(false, true);
        }

        me.callParent(arguments);

        if (me.isGroupHeader) {
            me.maybeShowNestedGroupHeader();
        }

        // Notify owning HeaderContainer. Will trigger a layout and a view refresh.
        ownerCt = me.getRootHeaderCt();

        if (ownerCt) {
            ownerCt.onHeaderShow(me);
        }

        Ext.resumeLayouts(true);

        return me;

    },

    /**
     * @private
     * Decides whether the column needs updating
     * @return {Number} 0 = Doesn't need update.
     * 1 = Column needs update, and renderer has > 1 argument; We need to render
     * a whole new HTML item.
     * 2 = Column needs update, but renderer has 1 argument or column uses an updater.
     */
    shouldUpdateCell: function(record, changedFieldNames) {
        var len, i, field;

        // If the column has a renderer which peeks and pokes at other data,
        // return 1 which means that a whole new TableView item must be rendered.
        //
        // Note that widget columns shouldn't ever be updated.
        if (!this.preventUpdate) {
            if (this.hasCustomRenderer) {
                return 1;
            }

            // If there is a changed field list, and it's NOT a custom column renderer
            // (meaning it doesn't peek at other data, but just uses the raw field value),
            // we only have to update it if the column's field is among those changes.
            if (changedFieldNames) {
                len = changedFieldNames.length;

                for (i = 0; i < len; ++i) {
                    field = changedFieldNames[i];

                    if (field === this.dataIndex || field === record.idProperty) {
                        return 2;
                    }
                }
            }
            else {
                return 2;
            }
        }
    },

    getCellWidth: function() {
        var me = this,
            result;

        if (me.rendered && me.componentLayout && me.componentLayout.lastComponentSize) {
            // headers always have either a width or a flex
            // because HeaderContainer sets a defaults width
            // therefore we can ignore the natural width
            // we use the componentLayout's tracked width so that
            // we can calculate the desired width when rendered
            // but not visible because its being obscured by a layout
            result = me.componentLayout.lastComponentSize.width;
        }
        else if (me.width) {
            result = me.width;
        }

        // This is a group header.
        // Use getTableWidth and remember that getTableWidth adjusts for column lines and box model
        else if (!me.isColumn) {
            result = me.getTableWidth();
        }

        return result;
    },

    getCellId: function() {
        return Ext.baseCSSPrefix + 'grid-cell-' + this.getItemId();
    },

    getCellSelector: function() {
        var view;

        if (!this.cellSelector) {
            view = this.getView();

            // We must explicitly access the view's cell selector as well as this column's
            // own ID class because <col> elements are given this column's ID class.
            // If we are still atached to a view. If not, the identifying class will do.
            this.cellSelector = (view ? view.getCellSelector() : '') + '.' + this.getCellId();
        }

        return this.cellSelector;
    },

    getCellInnerSelector: function() {
        return this.getCellSelector() + ' .' + Ext.baseCSSPrefix + 'grid-cell-inner';
    },

    isAtStartEdge: function(e) {
        var offset = e.getXY()[0] - this.getX();

        // To the left of the first column, not over
        if (offset < 0 && this.getIndex() === 0) {
            return false;
        }

        return (offset < this.getHandleWidth(e));
    },

    isAtEndEdge: function(e, margin) {
        return (this.getX() + this.getWidth() - e.getXY()[0] <= (margin || this.getHandleWidth(e)));
    },

    getHandleWidth: function(e) {
        return e.pointerType === 'touch' ? 10 : 4;
    },

    setMenuActive: function(menu) {
        // Called when the column menu is activated/deactivated.
        // Change the UI to indicate active/inactive menu
        this.activeMenu = menu;
        this.titleEl[menu ? 'addCls' : 'removeCls'](this.headerOpenCls);
    },

    privates: {
        /**
         * @private
         * Mapping for locale-neutral align setting.
         * Overridden in Ext.rtl.grid.column.Column
         */
        _alignMap: {
            start: 'left',
            end: 'right'
        },

        /**
         * A method called by the render template to allow extra content after the header text.
         * @private
         */
        afterText: function(out, values) {
            if (this.dirtyText) {
                this.dirtyTextElementId = this.id + '-dirty-cell-text';
                out.push(
                    '<span id="' + this.dirtyTextElementId + '" class="' +
                        Ext.baseCSSPrefix + 'hidden-offsets">' + this.dirtyText +
                    '</span>'
                );
            }
        },

        calculateHeaderId: function() {
            var me = this,
                ownerGrid,
                counterOwner, items, item, i, len;

            if (!me.headerId) {
                // Sequential header counter MUST be based on the top level grid
                // to avoid duplicates from sides of a lockable assembly.
                ownerGrid = me.up('tablepanel');

                if (!ownerGrid) {
                    return;
                }

                items = me.items.items;

                // Action column has items as an array, so skip out here.
                if (items) {
                    for (i = 0, len = items.length; i < len; ++i) {
                        item = items[i];

                        if (item.isColumn) {
                            item.calculateHeaderId();
                        }
                    }
                }

                counterOwner = ownerGrid ? ownerGrid.ownerGrid : me.getRootHeaderCt();
                counterOwner.headerCounter = (counterOwner.headerCounter || 0) + 1;
                me.headerId = 'h' + counterOwner.headerCounter;
            }

            me.configureStateInfo();
        },

        getMappedAlignment: function(align) {
            return this._alignMap[align] || align;
        },

        configureStateInfo: function() {
            var me = this,
                sorter;

            // MUST stamp a stateId into this object; state application relies on
            // reading the property, NOT using the getter!
            // Only generate a stateId if it really needs one.
            if (!me.stateId) {
                // This was the headerId generated in 4.0, so to preserve saved state, we now
                // assign a default stateId in that same manner. The stateId's of a column are
                // not global at the stateProvider, but are local to the grid state data. The
                // headerId should still follow our standard naming convention.
                me.stateId = me.initialConfig.id || me.headerId;
            }

            sorter = me.getSorter();

            if (!me.hasSetSorter && sorter && !sorter.initialConfig.id) {
                if (me.dataIndex || me.stateId) {
                    sorter.setId((me.dataIndex || me.stateId) + '-sorter');
                    me.hasSetSorter = true;
                }
            }
        },

        onLock: function(header) {
            var items = this.items.items,
                len = items.length,
                i, item;

            for (i = 0; i < len; ++i) {
                item = items[i];

                if (item.isColumn) {
                    item.onLock(header);
                }
            }
        },

        onUnlock: function(header) {
            var items = this.items.items,
                len = items.length,
                i, item;

            for (i = 0; i < len; ++i) {
                item = items[i];

                if (item.isColumn) {
                    item.onUnlock(header);
                }
            }
        },

        shouldFlagCustomRenderer: function(renderer) {
            return renderer.length > 1;
        }
    },

    deprecated: {
        5: {
            methods: {
                bindRenderer: function(renderer) {
                    // This method restores the pre-5 meaning of "renderer" as a string:
                    // a method in Ext.util.Format. But at least we don't send all of
                    // the renderer arguments at the poor thing!
                    return function(value) {
                        return Ext.util.Format[renderer](value);
                    };
                }
            }
        }
    }

    // intentionally omit getEditor and setEditor definitions bc we applyIf into columns
    // when the editing plugin is injected

    /**
     * @method getEditor
     * Retrieves the editing field for editing associated with this header.  If the
     * field has not been instantiated it will be created.
     *
     * **Note:** This method will only have an implementation if an Editing plugin has
     * been enabled on the grid ({@link Ext.grid.plugin.CellEditing cellediting} /
     * {@link Ext.grid.plugin.RowEditing rowediting}).
     *
     * @param {Object} [record] The {@link Ext.data.Model Model} instance being edited.
     * @param {Object/String} [defaultField] An xtype or config object for a
     * {@link Ext.form.field.Field Field} to be created as the default editor if it does
     * not already exist
     * @return {Ext.form.field.Field/Boolean} The editor field associated with
     * this column.  Returns false if there is no field associated with the
     * {@link Ext.grid.column.Column Column}.
     */

    /**
     * @method setEditor
     * Sets the form field to be used for editing.
     *
     * **Note:** This method will only have an implementation if an Editing plugin has
     * been enabled on the grid ({@link Ext.grid.plugin.CellEditing cellediting} /
     * {@link Ext.grid.plugin.RowEditing rowediting}).
     *
     * @param {Object} field An object representing a field to be created.
     * If no xtype is specified a 'textfield' is assumed.
     */
});
