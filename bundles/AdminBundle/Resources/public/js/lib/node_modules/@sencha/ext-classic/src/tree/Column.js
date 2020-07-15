/**
 * Provides indentation and folder structure markup for a Tree taking into account
 * depth and position within the tree hierarchy.
 */
Ext.define('Ext.tree.Column', {
    extend: 'Ext.grid.column.Column',
    alias: 'widget.treecolumn',

    tdCls: Ext.baseCSSPrefix + 'grid-cell-treecolumn',

    autoLock: true,
    lockable: false,
    draggable: false,
    hideable: false,

    iconCls: Ext.baseCSSPrefix + 'tree-icon',
    checkboxCls: Ext.baseCSSPrefix + 'tree-checkbox',
    elbowCls: Ext.baseCSSPrefix + 'tree-elbow',
    expanderCls: Ext.baseCSSPrefix + 'tree-expander',
    textCls: Ext.baseCSSPrefix + 'tree-node-text',
    innerCls: Ext.baseCSSPrefix + 'grid-cell-inner-treecolumn',
    customIconCls: Ext.baseCSSPrefix + 'tree-icon-custom',
    isTreeColumn: true,

    /**
     * @cfg {Function/String} renderer
     * A renderer is an 'interceptor' method which can be used to transform data (value, 
     * appearance, etc.) before it is rendered. Note that a *tree column* renderer yields
     * the *text* of the node. The lines and icons are produced by configurations. See
     * below for more information.
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
     * `tdStyle`, `icon`, `iconCls` and `glyph`.
     *
     * For the standard grid column metaData propertyes see
     * {@link Ext.grid.column.Column#cfg-renderer column renderer}
     *
     * To change the icon used in the node, you can use the glyph metaData property as below. 
     *
     * You can see an example of using the metaData parameter below.
     *
     *      renderer: function(v, metaData, record) {
     *          metaData.glyph = record.glyph;
     *          return v;
     *      }
     *
     * or
     *
     *      renderer: function(v, metaData, record) {
     *          metaData.icon = record.icon;
     *          return v;
     *      }
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
     * The HTML string to be rendered into the text portion of the tree node.
     */

    /* eslint-disable indent, max-len */
    cellTpl: [
        '<tpl for="lines">',
            '<div class="{parent.childCls} {parent.elbowCls}-img ',
            '{parent.elbowCls}-<tpl if=".">line<tpl else>empty</tpl>" role="presentation"></div>',
        '</tpl>',
        '<div class="{childCls} {elbowCls}-img {elbowCls}',
            '<tpl if="isLast">-end</tpl><tpl if="expandable">-plus {expanderCls}</tpl>" role="presentation"></div>',
        '<tpl if="checked !== null">',
            '<div role="button" {ariaCellCheckboxAttr}',
                ' class="{childCls} {checkboxCls}<tpl if="checked"> {checkboxCls}-checked</tpl>"></div>',
        '</tpl>',
        '<tpl if="glyph">',
            '<span class="{baseIconCls}" ',
            '<tpl if="glyphFontFamily">',
                'style="font-family:{glyphFontFamily}"',
            '</tpl>',
            '>{glyph}</span>',
        '<tpl else>',
            '<tpl if="icon">',
                '<img src="{blankUrl}"',
            '<tpl else>',
                '<div',
            '</tpl>',
            ' role="presentation" class="{childCls} {baseIconCls} {customIconCls} ',
            '{baseIconCls}-<tpl if="leaf">leaf<tpl else><tpl if="expanded">parent-expanded<tpl else>parent</tpl></tpl> {iconCls}" ',
            '<tpl if="icon">style="background-image:url({icon})"/><tpl else>></div></tpl>',
        '</tpl>',
        '<tpl if="href">',
            '<a href="{href}" role="link" target="{hrefTarget}" class="{textCls} {childCls}">{value}</a>',
        '<tpl else>',
            '<span class="{textCls} {childCls}">{value}</span>',
        '</tpl>'
    ],
    /* eslint-enable indent, max-len */

    // fields that will trigger a change in the ui that aren't likely to be bound to a column
    uiFields: {
        checked: 1,
        icon: 1,
        iconCls: 1
    },

    // fields that requires a full row render
    rowFields: {
        expanded: 1,
        loaded: 1,
        expandable: 1,
        leaf: 1,
        loading: 1,
        qtip: 1,
        qtitle: 1,
        cls: 1
    },

    initComponent: function() {
        var me = this;

        me.rendererScope = me.scope;
        me.setupRenderer();

        // This always uses its own renderer.
        // Any custom renderer is used as an inner renderer to produce the text node of a tree cell.
        me.innerRenderer = me.renderer;

        me.renderer = me.treeRenderer;

        me.callParent();

        me.scope = me;
    },

    treeRenderer: function(value, metaData, record, rowIdx, colIdx, store, view) {
        var me = this,
            cls = record.get('cls'),
            rendererData;

        // The initial render will inject the cls into the TD's attributes.
        // If cls is ever *changed*, then the full rendering path is followed.
        if (metaData && cls) {
            metaData.tdCls += ' ' + cls;
        }

        rendererData =
            me.initTemplateRendererData(value, metaData, record, rowIdx, colIdx, store, view);

        return me.lookupTpl('cellTpl').apply(rendererData);
    },

    initTemplateRendererData: function(value, metaData, record, rowIdx, colIdx, store, view) {
        var me = this,
            innerRenderer = me.innerRenderer,
            data = record.data,
            parent = record.parentNode,
            rootVisible = view.rootVisible,
            lines = [],
            parentData,
            glyph,
            glyphFontFamily;

        while (parent && (rootVisible || parent.data.depth > 0)) {
            parentData = parent.data;
            lines[rootVisible ? parentData.depth : parentData.depth - 1] =
                    parent.isLastVisible() ? 0 : 1;
            parent = parent.parentNode;
        }

        // Clear down metadata properties which may be set by the user renderer
        // because we apply them if we find them set, and the metedata property
        // is a static object owned by the View.
        // metaData may not be present if the column is being rendered through a
        // default renderer with no extra params.
        if (metaData) {
            metaData.iconCls = metaData.icon = metaData.glyph = null;
        }
        else {
            metaData = {};
        }

        // Call renderer now so that we can use metaData properties that it may set.
        value = innerRenderer ? innerRenderer.apply(me.rendererScope, arguments) : value;

        // If a glyph was specified, then
        // transform glyph to the useful parts
        glyph = metaData.glyph || data.glyph;

        if (glyph) {
            glyph = Ext.Glyph.fly(glyph);
            glyphFontFamily = glyph.fontFamily;
            glyph = glyph.character;
        }

        return {
            record: record,
            baseIconCls: me.iconCls,
            customIconCls: (data.icon || data.iconCls) ? me.customIconCls : '',
            glyph: glyph,
            glyphFontFamily: glyphFontFamily,
            iconCls: metaData.iconCls || data.iconCls,
            icon: metaData.icon || data.icon,
            checkboxCls: me.checkboxCls,
            checked: data.checked,
            elbowCls: me.elbowCls,
            expanderCls: me.expanderCls,
            textCls: me.textCls,
            leaf: data.leaf,
            expandable: record.isExpandable(),
            expanded: data.expanded,
            isLast: record.isLastVisible(),
            blankUrl: Ext.BLANK_IMAGE_URL,
            href: data.href,
            hrefTarget: data.hrefTarget,
            lines: lines,
            metaData: metaData,
            // subclasses or overrides can implement a getChildCls() method, which can
            // return an extra class to add to all of the cell's child elements (icon,
            // expander, elbow, checkbox).  This is used by the rtl override to add the
            // "x-rtl" class to these elements.
            childCls: me.getChildCls ? me.getChildCls() + ' ' : '',
            value: value || store.defaultRootText
        };
    },

    shouldUpdateCell: function(record, changedFieldNames) {
        // For the TreeColumn, if any of the known tree column UI affecting fields are updated
        // the cell should be updated in whatever way.
        // 1 if a custom renderer (not our default tree cell renderer), else 2.
        var me = this,
            i = 0,
            len, field;

        // If the column has a renderer which peeks and pokes at other data,
        // return 1 which means that a whole new TableView item must be rendered.
        if (me.hasCustomRenderer) {
            return 1;
        }

        if (changedFieldNames) {
            len = changedFieldNames.length;

            for (; i < len; ++i) {
                field = changedFieldNames[i];

                // Check for fields which always require a full row update.
                if (me.rowFields[field]) {
                    return 1;
                }

                // Check for fields which require this column to be updated.
                // The TreeColumn's treeRenderer is not a custom renderer.
                if (me.uiFields[field]) {
                    return 2;
                }
            }
        }

        return me.callParent([record, changedFieldNames]);
    },

    privates: {
        shouldFlagCustomRenderer: function() {
            return this.hasCustomRenderer || (this.innerRenderer && this.innerRenderer.length > 1);
        }
    }
});
