/**
 * An internally used DataView for {@link Ext.form.field.ComboBox ComboBox}.
 */
Ext.define('Ext.view.BoundList', {
    extend: 'Ext.view.View',
    alias: 'widget.boundlist',
    alternateClassName: 'Ext.BoundList',

    requires: [
        'Ext.view.BoundListKeyNav',
        'Ext.layout.component.BoundList',
        'Ext.toolbar.Paging'
    ],

    mixins: [
        'Ext.mixin.Queryable'
    ],

    /**
     * @cfg {Number} pageSize
     * If greater than `0`, a {@link Ext.toolbar.Paging} is displayed at the bottom of the list
     * and store queries will execute with page {@link Ext.data.operation.Read#start start} and
     * {@link Ext.data.operation.Read#limit limit} parameters.
     */
    pageSize: 0,

    /**
     * @cfg {String} [displayField=""]
     * The field from the store to show in the view.
     */

    /**
     * @property {Ext.toolbar.Paging} pagingToolbar
     * A reference to the PagingToolbar instance in this view. Only populated if {@link #pageSize}
     * is greater than zero and the BoundList has been rendered.
     */

    /**
     * @cfg baseCls
     * @inheritdoc
     */
    baseCls: Ext.baseCSSPrefix + 'boundlist',

    /**
     * @cfg itemCls
     * @inheritdoc
     */
    itemCls: Ext.baseCSSPrefix + 'boundlist-item',
    listItemCls: '',

    /**
     * @cfg shadow
     * @inheritdoc
     */
    shadow: false,

    /**
     * @cfg trackOver
     * @inheritdoc
     */
    trackOver: true,

    /**
     * @cfg preserveScrollOnRefresh
     * @inheritdoc
     */
    preserveScrollOnRefresh: true,
    enableInitialSelection: false,
    refreshSelmodelOnRefresh: true,

    /**
     * @cfg componentLayout
     * @inheritdoc
     */
    componentLayout: 'boundlist',

    /**
     * @cfg navigationModel
     * @inheritdoc
     */
    navigationModel: 'boundlist',

    /**
     * @cfg scrollable
     * @inheritdoc
     */
    scrollable: true,

    /**
     * @property ariaEl
     * @inheritdoc
     */
    ariaEl: 'listEl',

    /**
     * @cfg tabIndex
     * @inheritdoc
     */
    tabIndex: -1,

    /**
     * @cfg childEls
     * @inheritdoc
     */
    childEls: [
        'listWrap', 'listEl'
    ],

    /* eslint-disable indent */
    /**
     * @cfg renderTpl
     * @inheritdoc
     */
    renderTpl: [
        '<div id="{id}-listWrap" data-ref="listWrap"',
                ' class="{baseCls}-list-ct ', Ext.dom.Element.unselectableCls, '">',
            '<ul id="{id}-listEl" data-ref="listEl" class="', Ext.baseCSSPrefix, 'list-plain"',
                '<tpl foreach="ariaAttributes"> {$}="{.}"</tpl>',
            '>',
            '</ul>',
        '</div>',
        '{%',
            'var pagingToolbar=values.$comp.pagingToolbar;',
            'if (pagingToolbar) {',
                'Ext.DomHelper.generateMarkup(pagingToolbar.getRenderTree(), out);',
            '}',
        '%}',
        {
            disableFormats: true
        }
    ],
    /* eslint-enable indent */

    /**
     * @cfg {String/Ext.XTemplate} tpl
     * A String or Ext.XTemplate instance to apply to inner template.
     *
     * {@link Ext.view.BoundList} is used for the dropdown list of 
     * {@link Ext.form.field.ComboBox}. To customize the template you can set the tpl on 
     * the combobox config object:
     *
     *     Ext.create('Ext.form.field.ComboBox', {
     *         fieldLabel   : 'State',
     *         queryMode    : 'local',
     *         displayField : 'text',
     *         valueField   : 'abbr',
     *         store        : Ext.create('StateStore', {
     *             fields : ['abbr', 'text'],
     *             data   : [
     *                 {"abbr":"AL", "name":"Alabama"},
     *                 {"abbr":"AK", "name":"Alaska"},
     *                 {"abbr":"AZ", "name":"Arizona"}
     *                 //...
     *             ]
     *         }),
     *         // Template for the dropdown menu.
     *         // Note the use of the "x-list-plain" and "x-boundlist-item" class,
     *         // this is required to make the items selectable.
     *         tpl: Ext.create('Ext.XTemplate',
     *             '<ul class="x-list-plain"><tpl for=".">',
     *                 '<li role="option" class="x-boundlist-item">{abbr} - {name}</li>',
     *             '</tpl></ul>'
     *         ),
     *     });
     *
     * Defaults to:
     *
     *     Ext.create('Ext.XTemplate',
     *         '<ul><tpl for=".">',
     *             '<li role="option" class="' + itemCls + '">' + me.getInnerTpl(me.displayField) +
     *             '</li>',
     *         '</tpl></ul>'
     *     );
     *
     */

    // Override because on non-touch devices, the bound field
    // retains focus so that typing may narrow the list.
    // Only when the show is triggered by a touch does the BoundList
    // get explicitly focused so that the keyboard does not appear.
    /**
     * @cfg focusOnToFront
     * @inheritdoc
     */
    focusOnToFront: false,

    /**
     * @cfg alignOnScroll
     * @inheritdoc
     */
    alignOnScroll: false,

    initComponent: function() {
        var me = this,
            baseCls = me.baseCls,
            itemCls = me.itemCls;

        me.selectedItemCls = baseCls + '-selected';

        if (me.trackOver) {
            me.overItemCls = baseCls + '-item-over';
        }

        me.itemSelector = '.' + itemCls;

        if (me.floating) {
            me.addCls(baseCls + '-floating');
        }

        if (!me.tpl) {
            // should be setting aria-posinset based on entire set of data
            // not filtered set
            me.generateTpl();
        }
        else if (!me.tpl.isTemplate) {
            me.tpl = new Ext.XTemplate(me.tpl);
        }

        if (me.pageSize) {
            me.pagingToolbar = me.createPagingToolbar();
        }

        me.callParent();
    },

    /**
     * Allow tpl to be generated programmatically to respond to changes in displayField
     * @private
     */
    generateTpl: function() {
        var me = this;

        /* eslint-disable indent, max-len */
        me.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<li role="option" unselectable="on" class="' + me.itemCls + '">' + me.getInnerTpl(me.displayField) + '</li>',
            '</tpl>'
        );
        /* eslint-enable indent, max-len */
    },

    /**
     * Updates the display field for this view. This will automatically trigger
     * an regeneration of the tpl so that the updated displayField can be used
     * @param {String} displayField
     */
    setDisplayField: function(displayField) {
        this.displayField = displayField;
        this.generateTpl();
    },

    getRefOwner: function() {
        return this.pickerField || this.callParent();
    },

    getRefItems: function() {
        var result = this.callParent(),
            toolbar = this.pagingToolbar;

        if (toolbar) {
            result.push(toolbar);
        }

        return result;
    },

    createPagingToolbar: function() {
        var me = this;

        return new Ext.toolbar.Paging({
            id: me.id + '-paging-toolbar',
            pageSize: me.pageSize,
            store: me.dataSource,
            border: false,
            ownerCt: me,
            ownerLayout: me.getComponentLayout()
        });
    },

    refresh: function() {
        var me = this,
            tpl = me.tpl;

        // Allow access to the context for XTemplate scriptlets
        tpl.field = me.pickerField;
        tpl.store = me.store;
        me.callParent();
        tpl.field = tpl.store = null;

        if (!me.ariaStaticRoles[me.ariaRole]) {
            me.refreshAriaAttributes();
        }

        // The view selectively removes item nodes, so the toolbar
        // will be preserved in the DOM
    },

    refreshAriaAttributes: function() {
        var me = this,
            store = me.store,
            selModel = me.getSelectionModel(),
            multiSelect, totalCount, nodes, node, record, index, i, len;

        // When the store is filtered or paged, we want to let the Assistive Technology
        // users know that there are more records than currently displayed. This is not
        // a requirement when the whole dataset fits the DOM.
        // Note that it is possible for the store to be filtered but not fit the DOM.
        // In that case we use filtered count as the set size.
        totalCount = store.isFiltered()
            ? store.getCount()
            : store.getTotalCount() || store.getCount();

        nodes = me.getNodes();

        multiSelect = me.pickerField && me.pickerField.multiSelect;

        for (i = 0, len = nodes.length; i < len; i++) {
            node = nodes[i];
            record = null;

            if (totalCount !== len) {
                record = me.getRecord(node);
                index = store.indexOf(record);

                node.setAttribute('aria-setsize', totalCount);
                node.setAttribute('aria-posinset', index);
            }

            // For single-select combos aria-selected must be undefined
            if (multiSelect) {
                record = record || me.getRecord(node);
                node.setAttribute('aria-selected', selModel.isSelected(record));
            }
        }
    },

    bindStore: function(store, initial) {
        var toolbar = this.pagingToolbar;

        this.callParent(arguments);

        if (toolbar) {
            toolbar.bindStore(store, initial);
        }
    },

    /**
     * A method that returns the inner template for displaying items in the list.
     * This method is useful to override when using a more complex display value, for example
     * inserting an icon along with the text.
     *
     * The XTemplate is created with a reference to the owning form field in the `field` property
     * to provide access to context. For example to highlight the currently typed value,
     * the getInnerTpl may be configured into a ComboBox as part of the
     * {@link Ext.form.field.ComboBox#listConfig listConfig}:
     *
     *     listConfig: {
     *         getInnerTpl: function() {
     *             return '{[values.name.replace(this.field.getRawValue(), "<b>" +
     *                    this.field.getRawValue() + "</b>")]}';
     *         }
     *     }
     * @param {String} displayField The {@link #cfg!displayField} for the BoundList.
     * @return {String} The inner template
     */
    getInnerTpl: function(displayField) {
        return '{' + displayField + '}';
    },

    onShow: function() {
        var field = this.pickerField;

        this.callParent();

        // If the input field is not focused, then focus it.
        if (field && field.rendered && !field.hasFocus) {
            field.focus();
        }
    },

    afterComponentLayout: function(width, height, oldWidth, oldHeight) {
        var field = this.pickerField;

        this.callParent([width, height, oldWidth, oldHeight]);

        // Bound list may change size, so realign on layout
        // **if the field is an Ext.form.field.Picker which has alignPicker!**
        if (field && field.alignPicker) {
            field.alignPicker();
        }
    },

    onItemSelect: function(record) {
        var me = this,
            node;

        node = me.callParent([record]);

        if (node) {
            if (me.ariaSelectable) {
                node.setAttribute('aria-selected', 'true');
            }
            else {
                node.removeAttribute('aria-selected');
            }
        }

        return node;
    },

    onItemDeselect: function(record) {
        var me = this,
            node;

        node = me.callParent([record]);

        if (node && me.ariaSelectable) {
            if (me.pickerField && me.pickerField.multiSelect) {
                node.setAttribute('aria-selected', 'false');
            }
            else {
                node.removeAttribute('aria-selected');
            }
        }

        return node;
    },

    // Clicking on an already selected item collapses the picker
    onItemClick: function(record) {
        // The selection change events won't fire when clicking on the selected element.
        // Detect it here.
        var me = this,
            field = me.pickerField,
            valueField, selected;

        if (!field) {
            return;
        }

        valueField = field.valueField;
        selected = me.getSelectionModel().getSelection();

        if (!field.multiSelect && selected.length) {
            selected = selected[0];

            // Not all pickerField's have a collapse API, i.e. Ext.ux.form.MultiSelect.
            if (selected && field.isEqual(record.get(valueField), selected.get(valueField)) &&
                field.collapse) {
                field.collapse();
            }
        }
    },

    onContainerClick: function(e) {
        var toolbar = this.pagingToolbar,
            clientRegion;

        // Ext.view.View template method
        // Do not continue to process the event as a container click
        // if it is within the pagingToolbar
        if (toolbar && toolbar.rendered && e.within(toolbar.el)) {
            return false;
        }

        // IE10 and IE11 will fire pointer events when user drags listWrap scrollbars,
        // which may result in selection being reset.
        if (Ext.isIE10 || Ext.isIE11) {
            clientRegion = this.listWrap.getClientRegion();

            if (!e.getPoint().isContainedBy(clientRegion)) {
                return false;
            }
        }
    },

    doDestroy: function() {
        this.pagingToolbar = Ext.destroy(this.pagingToolbar);

        this.callParent();
    },

    privates: {
        /**
         * @method getNodeContainer
         * @private
         * @inheritdoc
         */
        getNodeContainer: function() {
            return this.listEl;
        },

        getTargetEl: function() {
            return this.listEl;
        },

        getOverflowEl: function() {
            return this.listWrap;
        },

        // Do the job of a container layout at this point even though we are not a Container.
        finishRenderChildren: function() {
            var toolbar = this.pagingToolbar;

            this.callParent(arguments);

            if (toolbar) {
                toolbar.finishRender();
            }
        }
    }
});
