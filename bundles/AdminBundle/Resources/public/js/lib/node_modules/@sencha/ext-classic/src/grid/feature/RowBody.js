/**
 * The rowbody feature enhances the grid's markup to have an additional
 * tr -> td -> div which spans the entire width of the original row.
 *
 * This is useful to to associate additional information with a particular
 * record in an Ext.grid.Grid.
 *
 * Rowbodies are initially hidden unless you override {@link #getAdditionalData}.
 *
 * The events fired by RowBody are relayed to the owning 
 * {@link Ext.view.Table grid view} (and subsequently the owning grid).
 *
 * # Example
 *
 *     @example
 *     Ext.define('Animal', {
 *         extend: 'Ext.data.Model',
 *         fields: ['name', 'latin', 'desc', 'lifespan']
 *     });
 *     
 *     Ext.create('Ext.grid.Panel', {
 *         width: 400,
 *         height: 300,
 *         renderTo: Ext.getBody(),
 *         store: {
 *             model: 'Animal',
 *             data: [{
 *                 name: 'Tiger',
 *                 latin: 'Panthera tigris',
 *                 desc: 'The largest cat species, weighing up to 306 kg (670 lb).',
 *                 lifespan: '20 - 26 years (in captivity)'
 *             }, {
 *                 name: 'Roman snail',
 *                 latin: 'Helix pomatia',
 *                 desc: 'A species of large, edible, air-breathing land snail.',
 *                 lifespan: '20 - 35 years'
 *             }, {
 *                 name: 'Yellow-winged darter',
 *                 latin: 'Sympetrum flaveolum',
 *                 desc: 'A dragonfly found in Europe and mid and Northern China.',
 *                 lifespan: '4 - 6 weeks'
 *             }, {
 *                 name: 'Superb Fairy-wren',
 *                 latin: 'Malurus cyaneus',
 *                 desc: 'Common and familiar across south-eastern Australia.',
 *                 lifespan: '5 - 6 years'
 *             }]
 *         },
 *         columns: [{
 *             dataIndex: 'name',
 *             text: 'Common name',
 *             width: 125
 *         }, {
 *             dataIndex: 'latin',
 *             text: 'Scientific name',
 *             flex: 1
 *         }],
 *         features: [{
 *             ftype: 'rowbody',
 *             getAdditionalData: function(data, idx, record, orig) {
 *                 // Usually you would style the my-body-class in a CSS file
 *                 return {
 *                     rowBody: '<div style="padding: 1em">' + record.get("desc") + '</div>',
 *                     rowBodyCls: "my-body-class"
 *                 };
 *             }
 *         }],
 *         listeners: {
 *             rowbodyclick: function(view, rowEl, e, eOpts) {
 *                 var itemEl = Ext.get(rowEl).up(view.itemSelector),
 *                     rec = view.getRecord(itemEl);
 *                 
 *                 Ext.Msg.alert(rec.get('name') + ' life span', rec.get('lifespan'));
 *             }
 *         }
 *     });
 *
 *  # Cell Editing and Cell Selection Model
 *
 * Note that if {@link Ext.grid.plugin.CellEditing cell editing} or the
 * {@link Ext.selection.CellModel cell selection model} are going to be used, then the
 * {@link Ext.grid.feature.RowBody RowBody} feature, or
 * {@link Ext.grid.plugin.RowExpander RowExpander} plugin MUST be used for intra-cell navigation
 * to be correct.
 *
 * **Note:** The {@link Ext.grid.plugin.RowExpander rowexpander} plugin and the rowbody
 * feature are exclusive and cannot both be set on the same grid / tree.
 */
Ext.define('Ext.grid.feature.RowBody', {
    extend: 'Ext.grid.feature.Feature',
    alias: 'feature.rowbody',

    rowBodyCls: Ext.baseCSSPrefix + 'grid-row-body',
    innerSelector: '.' + Ext.baseCSSPrefix + 'grid-rowbody',
    rowBodyHiddenCls: Ext.baseCSSPrefix + 'grid-row-body-hidden',
    rowBodyTdSelector: 'td.' + Ext.baseCSSPrefix + 'grid-cell-rowbody',
    eventPrefix: 'rowbody',
    eventSelector: 'tr.' + Ext.baseCSSPrefix + 'grid-rowbody-tr',

    /**
     * @cfg {Boolean} [bodyBefore=false]
     * Configure as `true` to put the row expander body *before* the data row.
     */
    bodyBefore: false,

    outerTpl: {
        fn: function(out, values, parent) {
            var me = this.rowBody,
                view = values.view,
                columns = view.getVisibleColumnManager().getColumns(),
                rowValues = view.rowValues,
                rowExpanderCol = me.rowExpander && me.rowExpander.expanderColumn;

            rowValues.rowBodyColspan = columns.length;
            rowValues.rowBodyCls = me.rowBodyCls;
            rowValues.rowIdCls = me.rowIdCls;

            if (rowExpanderCol && rowExpanderCol.getView() === view) {
                view.grid.removeCls(Ext.baseCSSPrefix + 'grid-hide-row-expander-spacer');
                rowValues.addSpacerCell = true;
                rowValues.rowBodyColspan -= 1;
                rowValues.spacerCellCls = Ext.baseCSSPrefix + 'grid-cell ' + Ext.baseCSSPrefix +
                                          'grid-row-expander-spacer ' + Ext.baseCSSPrefix +
                                          'grid-cell-special';
            }
            else {
                view.grid.addCls(Ext.baseCSSPrefix + 'grid-hide-row-expander-spacer');
                rowValues.addSpacerCell = false;
            }

            this.nextTpl.applyOut(values, out, parent);

            rowValues.rowBodyCls = rowValues.rowBodyColspan = rowValues.rowBody = null;
        },
        priority: 100
    },

    /* eslint-disable indent, max-len */
    extraRowTpl: [
        '{%',
            'if(this.rowBody.bodyBefore) {',
                // MUST output column sizing elements because the first row in this table
                // contains one colspanning TD, and that overrides subsequent column width settings.
                'values.view.renderColumnSizer(values, out);',
            '} else {',
                'this.nextTpl.applyOut(values, out, parent);',
            '}',
            'values.view.rowBodyFeature.setupRowData(values.record, values.recordIndex, values);',
        '%}',
        '<tr class="' + Ext.baseCSSPrefix + 'grid-rowbody-tr {rowBodyCls} {rowIdCls}" {ariaRowAttr}>',
            '<tpl if="addSpacerCell">',
                '<td class="{spacerCellCls}"></td>',
            '</tpl>',
            '<td class="' + Ext.baseCSSPrefix + 'grid-td ' + Ext.baseCSSPrefix + 'grid-cell-rowbody" colspan="{rowBodyColspan}" {ariaCellAttr}>',
                '<div class="' + Ext.baseCSSPrefix + 'grid-rowbody {rowBodyDivCls}" {ariaCellInnerAttr}>{rowBody}</div>',
            '</td>',
        '</tr>',
        '{%',
            'if(this.rowBody.bodyBefore) {',
                'this.nextTpl.applyOut(values, out, parent);',
            '}',
        '%}', {
            priority: 100,

            beginRowSync: function(rowSync) {
                rowSync.add('rowBody', this.owner.eventSelector);
            },

            syncContent: function(destRow, sourceRow, columnsToUpdate) {
                var rowBody = this.rowBody,
                    destRowBody, sourceRowBody;

                if (rowBody.doSync) {
                    destRowBody = Ext.fly(destRow).down(rowBody.eventSelector, true);

                    // Sync the heights of row body elements in each row if they need it.
                    if (destRowBody && (sourceRowBody = Ext.fly(sourceRow).down(rowBody.eventSelector, true))) {
                        Ext.fly(destRowBody).syncContent(sourceRowBody);
                    }
                }
            }
        }
    ],
    /* eslint-enable indent, max-len */

    doSync: true,

    init: function(grid) {
        var me = this,
            view = me.view = grid.getView();

        //<debug>
        if (!me.rowExpander && grid.findPlugin('rowexpander')) {
            Ext.raise('The RowBody feature shouldn\'t be manually added when the grid' +
                      'has a RowExpander.');
        }
        //</debug>

        // The extra data means variableRowHeight
        grid.variableRowHeight = view.variableRowHeight = true;
        view.rowBodyFeature = me;

        view.headerCt.on({
            columnschanged: me.onColumnsChanged,
            scope: me
        });

        view.addTpl(me.outerTpl).rowBody = me;
        view.addRowTpl(Ext.XTemplate.getTpl(this, 'extraRowTpl')).rowBody = me;

        me.callParent(arguments);
    },

    getSelectedRow: function(view, rowIndex) {
        var selectedRow = view.getNode(rowIndex);

        if (selectedRow) {
            return Ext.fly(selectedRow).down(this.eventSelector);
        }

        return null;
    },

    // When columns added/removed, keep row body colspan in sync with number of columns.
    onColumnsChanged: function(headerCt) {
        var view = this.view,
            items, colspan, len, i;

        if (!view.rendered) {
            return;
        }

        items = view.el.query(this.rowBodyTdSelector);
        colspan = headerCt.getVisibleGridColumns().length;
        len = items.length;

        for (i = 0; i < len; ++i) {
            items[i].setAttribute('colSpan', colspan);
        }
    },

    /**
     * @method getAdditionalData
     * @protected
     * @template
     * Provides additional data to the prepareData call within the grid view.
     * The rowbody feature adds 3 additional variables into the grid view's template.
     * These are `rowBody`, `rowBodyCls`, and `rowBodyColspan`.
     * 
     *  - **rowBody:** *{String}* The HTML to display in the row body element.  Defaults 
     * to *undefined*.
     *  - **rowBodyCls:** *{String}* An optional CSS class (or multiple classes 
     * separated by spaces) to apply to the row body element.  Defaults to 
     * {@link #rowBodyCls}.
     *  - **rowBodyColspan:** *{Number}* The number of columns that the row body element 
     * should span.  Defaults to the number of visible columns.
     * 
     * @param {Object} data The data for this particular record.
     * @param {Number} idx The row index for this record.
     * @param {Ext.data.Model} record The record instance
     * @param {Object} orig The original result from the prepareData call to massage.
     * @return {Object} An object containing additional variables for use in the grid 
     * view's template
     */

    /*
     * @private
     */
    setupRowData: function(record, rowIndex, rowValues) {
        if (this.getAdditionalData) {
            Ext.apply(rowValues, this.getAdditionalData(record.data, rowIndex, record, rowValues));
        }
    }

    /**
     * @event beforerowbodymousedown
     * @preventable
     * @member Ext.view.Table
     * Fires before the mousedown event on a row body element is processed. Return false 
     * to cancel the default action.
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @param {Ext.view.View} view The rowbody's owning View
     * @param {HTMLElement} rowBodyEl The row body's element
     * @param {Ext.event.Event} e The raw event object
     */

    /**
     * @event beforerowbodymouseup
     * @preventable
     * @member Ext.view.Table
     * Fires before the mouseup event on a row body element is processed. Return false 
     * to cancel the default action.
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event beforerowbodyclick
     * @preventable
     * @member Ext.view.Table
     * Fires before the click event on a row body element is processed. Return false to 
     * cancel the default action.
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event beforerowbodydblclick
     * @preventable
     * @member Ext.view.Table
     * Fires before the dblclick event on a row body element is processed. Return false 
     * to cancel the default action.
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event beforerowbodycontextmenu
     * @preventable
     * @member Ext.view.Table
     * Fires before the contextmenu event on a row body element is processed. Return 
     * false to cancel the default action.
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event beforerowbodylongpress
     * @preventable
     * @member Ext.view.Table
     * Fires before the longpress event on a row body element is processed. Return 
     * false to cancel the default action.
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event beforerowbodykeydown
     * @preventable
     * @member Ext.view.Table
     * Fires before the keydown event on a row body element is processed. Return false 
     * to cancel the default action.
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event beforerowbodykeyup
     * @preventable
     * @member Ext.view.Table
     * Fires before the keyup event on a row body element is processed. Return false to 
     * cancel the default action.
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event beforerowbodykeypress
     * @preventable
     * @member Ext.view.Table
     * Fires before the keypress event on a row body element is processed. Return false 
     * to cancel the default action.
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event rowbodymousedown
     * @member Ext.view.Table
     * Fires when there is a mouse down on a row body element
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event rowbodymouseup
     * @member Ext.view.Table
     * Fires when there is a mouse up on a row body element
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event rowbodyclick
     * @member Ext.view.Table
     * Fires when a row body element is clicked
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event rowbodydblclick
     * @member Ext.view.Table
     * Fires when a row body element is double clicked
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event rowbodycontextmenu
     * @member Ext.view.Table
     * Fires when a row body element is right clicked
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event rowbodylongpress
     * @member Ext.view.Table
     * Fires on a row body element longpress event
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event rowbodykeydown
     * @member Ext.view.Table
     * Fires when a key is pressed down while a row body element is currently selected
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event rowbodykeyup
     * @member Ext.view.Table
     * Fires when a key is released while a row body element is currently selected
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */

    /**
     * @event rowbodykeypress
     * @member Ext.view.Table
     * Fires when a key is pressed while a row body element is currently selected.
     * 
     * **Note:** This event is fired only when the Ext.grid.feature.RowBody feature is 
     * used.
     * 
     * @inheritdoc #beforerowbodymousedown
     */
});
