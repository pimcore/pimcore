/**
 * `tagfield` provides a combobox that removes the hassle of dealing with long and unruly select 
 * options. The selected list is visually maintained in the value display area instead of 
 * within the picker itself. Users may easily add or remove `tags` from the 
 * display value area.
 *
 *     @example
 *     var shows = Ext.create('Ext.data.Store', {
 *         fields: ['id','show'],
 *         data: [
 *             {id: 0, show: 'Battlestar Galactica'},
 *             {id: 1, show: 'Doctor Who'},
 *             {id: 2, show: 'Farscape'},
 *             {id: 3, show: 'Firefly'},
 *             {id: 4, show: 'Star Trek'},
 *             {id: 5, show: 'Star Wars: Christmas Special'}
 *         ]
 *     });
 *
 *     Ext.create('Ext.form.Panel', {
 *         renderTo: Ext.getBody(),
 *         title: 'Sci-Fi Television',
 *         height: 200,
 *         width: 500,
 *         items: [{
 *             xtype: 'tagfield',
 *             fieldLabel: 'Select a Show',
 *             store: shows,
 *             displayField: 'show',
 *             valueField: 'id',
 *             queryMode: 'local',
 *             filterPickList: true
 *         }]
 *     });
 *       
 * ### History
 *
 * Inspired by the SuperBoxSelect component for ExtJS 3,
 * which in turn was inspired by the BoxSelect component for ExtJS 2.
 *
 * Various contributions and suggestions made by many members of the ExtJS community which
 * can be seen in the [user extension forum post](http://www.sencha.com/forum/showthread.php?134751-Ext.ux.form.field.BoxSelect).
 *
 * By: kvee_iv http://www.sencha.com/forum/member.php?29437-kveeiv
 */
Ext.define('Ext.form.field.Tag', {
    extend: 'Ext.form.field.ComboBox',
    xtype: 'tagfield',

    requires: [
        'Ext.selection.Model',
        'Ext.data.Store',
        'Ext.data.ChainedStore',
        'Ext.view.TagKeyNav'
    ],

    /**
     * @property noWrap
     * @inheritdoc
     */
    noWrap: false,

    /**
     * @cfg allowOnlyWhitespace
     * @hide
     * Currently unsupported since the value of a tagfield is an array of values and shouldn't
     * ever be a string.
     */

    /**
     * @cfg {String} valueParam
     * The name of the parameter used to load unknown records into the store. If left unspecified,
     * {@link #valueField} will be used.
     */

    /**
     * @cfg {Boolean} multiSelect
     * If set to `true`, allows the combo field to hold more than one value at a time, and allows
     * selecting multiple items from the dropdown list. The combo's text field will show all
     * selected values using the template defined by {@link #labelTpl}.
     *
     */
    multiSelect: true,

    /**
     * @cfg {String} delimiter
     * The character(s) used to separate new values to be added when {@link #createNewOnEnter}
     * or {@link #createNewOnBlur} are set.
     * `{@link #multiSelect} = true`.
     */
    delimiter: ',',

    /**
     * @cfg {String/Ext.XTemplate} labelTpl
     * The {@link Ext.XTemplate XTemplate} to use for the inner
     * markup of the labeled items. Defaults to the configured {@link #displayField}
     */

    /**
     * @cfg {String/Ext.XTemplate} tipTpl
     * The {@link Ext.XTemplate XTemplate} to use for the tip of the labeled items. 
     *
     * @since  5.1.1
     */
    tipTpl: undefined,

    /**
     * @cfg forceSelection
     * @inheritdoc
     *
     * When {@link #forceSelection} is `false`, new records can be created by the user as they
     * are typed. These records are **not** added to the combo's store. Multiple new values
     * may be added by separating them with the {@link #delimiter}, and can be further configured
     * using the {@link #createNewOnEnter} and {@link #createNewOnBlur} configuration options.
     *
     * This functionality is primarily useful for things such as an email address.
     */
    forceSelection: true,

    /**
     * @cfg {Boolean} createNewOnEnter
     * Has no effect if {@link #forceSelection} is `true`.
     *
     * With this set to `true`, the creation described in
     * {@link #forceSelection} will also be triggered by the 'enter' key.
     */
    createNewOnEnter: false,

    /**
     * @cfg {Boolean} createNewOnBlur
     * Has no effect if {@link #forceSelection} is `true`.
     *
     * With this set to `true`, the creation described in
     * {@link #forceSelection} will also be triggered when the field loses focus.
     *
     * Please note that this behavior is also affected by the configuration options
     * {@link #autoSelect} and {@link #selectOnTab}. If those are true and an existing
     * item would have been selected as a result, the partial text the user has entered will
     * be discarded and the existing item will be added to the selection.
     *
     * Setting this option to `true` is not recommended for accessible applications.
     */
    createNewOnBlur: false,

    /**
     * @cfg {Boolean} encodeSubmitValue
     * Has no effect if {@link #multiSelect} is `false`.
     *
     * Controls the formatting of the form submit value of the field as returned by
     * {@link #getSubmitValue}
     *
     * - `true` for the field value to submit as a json encoded array in a single GET/POST variable
     * - `false` for the field to submit as an array of GET/POST variables
     */
    encodeSubmitValue: false,

    /**
     * @cfg {Boolean} triggerOnClick
     * `true` to activate the trigger when clicking in empty space in the field. Note that the
     * subsequent behavior of this is controlled by the field's {@link #triggerAction}.
     * This behavior is similar to that of a basic ComboBox with {@link #editable} `false`.
     */
    triggerOnClick: true,

    /**
     * @cfg {Boolean} stacked
     * - `true` to have each selected value fill to the width of the form field
     * - `false to have each selected value size to its displayed contents
     */
    stacked: false,

    /**
     * @cfg {Boolean} filterPickList
     * True to hide the currently selected values from the drop down list.
     *
     * Setting this option to `true` is not recommended for accessible applications.
     *
     * - `true` to hide currently selected values from the drop down pick list
     * - `false` to keep the item in the pick list as a selected item
     */
    filterPickList: false,

    /**
     * @cfg {Boolean} clearOnBackspace
     * Set to `false` to disable clearing selected values with Backspace key. This mode
     * is recommended for accessible applications.
     */
    clearOnBackspace: true,

    /**
     * @cfg {Boolean} grow
     *
     * `true` if this field should automatically grow and shrink vertically to its content.
     * Note that this overrides the natural trigger grow functionality, which is used to size
     * the field horizontally.
     */
    grow: true,

    /**
     * @cfg {Number/Boolean} growMin
     * Has no effect if {@link #grow} is `false`
     *
     * The minimum height to allow when {@link #grow} is `true`, or `false` to allow for
     * natural vertical growth based on the current selected values. See also {@link #growMax}.
     */
    growMin: false,

    /**
     * @cfg {Number/Boolean} growMax
     * Has no effect if {@link #grow} is `false`
     *
     * The maximum height to allow when {@link #grow} is `true`, or `false` to allow for
     * natural vertical growth based on the current selected values. See also {@link #growMin}.
     */
    growMax: false,

    /**
     * @cfg {Boolean} simulatePlaceholder
     * @private
     */
    simulatePlaceholder: true,

    /**
     * @cfg selectOnFocus
     * @inheritdoc
     */
    selectOnFocus: true,

    /**
     * @cfg growToLongestValue
     * @hide
     * Currently unsupported since this is used for horizontal growth and this component
     * only supports vertical growth.
     */

    /**
     * @cfg {String} ariaHelpText
     * The text to be announced by screen readers when input element is
     * focused. This text is used when this component is configured not to allow creating
     * new values; when {@link #createNewOnEnter} is set to `true`, {@link #ariaHelpTextEditable}
     * will be used instead.
     * @locale
     */
    ariaHelpText: 'Use Up and Down arrows to view available values, Enter to select. ' +
                  'Use Left and Right arrows to view selected values, Delete key to deselect.',

    /**
     * @cfg {String} ariaHelpTextEditable
     * The text to be announced by screen readers when
     * input element is focused. This text is used when {@link #createNewOnEnter} is set to `true`;
     * see also {@link #ariaHelpText}.
     * @locale
     */
    ariaHelpTextEditable: 'Use Up and Down arrows to view available values, Enter to select. ' +
                          'Type and press Enter to create a new value. ' +
                          'Use Left and Right arrows to view selected values, ' +
                          'Delete key to deselect.',

    /**
     * @cfg {String} ariaSelectedText
     * Template text for announcing selected values to screen
     * reader users. '{0}' will be replaced with the list of selected values.
     * @locale
     */
    ariaSelectedText: 'Selected {0}.',

    /**
     * @cfg {String} ariaDeselectedText
     * Template text for announcing deselected values to
     * screen reader users. '{0}' will be replaced with the list of values removed from
     * selected list.
     * @locale
     */
    ariaDeselectedText: '{0} removed from selection.',

    /**
     * @cfg {String} ariaNoneSelectedText
     * Text to announce to screen reader users when no
     * values are currently selected. This text is used when Tag field is focused.
     * @locale
     */
    ariaNoneSelectedText: 'No value selected.',

    /**
     * @cfg {String} ariaSelectedListLabel
     * Label to be announced to screen reader users
     * when they use Left and Right arrow keys to navigate the list of currently selected values.
     * @locale
     */
    ariaSelectedListLabel: 'Selected values',

    /**
     * @cfg {String} ariaAvailableListLabel
     * Label to be announced to screen reader users
     * when they use Up and Down arrow keys to navigate the list of available values.
     * @locale
     */
    ariaAvailableListLabel: 'Available values',

    /**
     * @event autosize
     * Fires when the **{@link #autoSize}** function is triggered and the field is resized
     * according to the {@link #grow}/{@link #growMin}/{@link #growMax} configs as a result.
     * This event provides a hook for the developer to apply additional logic at runtime
     * to resize the field if needed.
     * @param {Ext.form.field.Tag} this This field
     * @param {Number} height The new field height
     */

    /* eslint-disable indent, max-len */
    /**
     * @cfg fieldSubTpl
     * @private
     */
    fieldSubTpl: [
        // listWrapper div is tabbable in Firefox, for some unfathomable reason
        '<div id="{cmpId}-listWrapper" data-ref="listWrapper"' + (Ext.isGecko ? ' tabindex="-1"' : ''),
            '<tpl foreach="ariaElAttributes"> {$}="{.}"</tpl>',
            ' class="' + Ext.baseCSSPrefix + 'tagfield {fieldCls} {typeCls} {typeCls}-{ui}"<tpl if="wrapperStyle"> style="{wrapperStyle}"</tpl>>',
            '<span id="{cmpId}-selectedText" data-ref="selectedText" aria-hidden="true" class="' + Ext.baseCSSPrefix + 'hidden-clip"></span>',
            '<ul id="{cmpId}-itemList" data-ref="itemList" role="presentation" class="' + Ext.baseCSSPrefix + 'tagfield-list{itemListCls}">',
                '<li id="{cmpId}-inputElCt" data-ref="inputElCt" role="presentation" class="' + Ext.baseCSSPrefix + 'tagfield-input">',
                    '<input id="{cmpId}-inputEl" data-ref="inputEl" type="{type}" ',
                    '<tpl if="name">name="{name}" </tpl>',
                    '<tpl if="value"> value="{[Ext.util.Format.htmlEncode(values.value)]}"</tpl>',
                    '<tpl if="size">size="{size}" </tpl>',
                    '<tpl if="tabIdx != null">tabindex="{tabIdx}" </tpl>',
                    '<tpl if="disabled"> disabled="disabled"</tpl>',
                    '<tpl foreach="inputElAriaAttributes"> {$}="{.}"</tpl>',
                    'class="' + Ext.baseCSSPrefix + 'tagfield-input-field {inputElCls} {emptyCls} {fixCls}" autocomplete="off">',
                '</li>',
            '</ul>',
            '<ul id="{cmpId}-ariaList" data-ref="ariaList" role="listbox"',
                '<tpl if="ariaSelectedListLabel"> aria-label="{ariaSelectedListLabel}"</tpl>',
                '<tpl if="multiSelect"> aria-multiselectable="true"</tpl>',
                ' class="' + Ext.baseCSSPrefix + 'tagfield-arialist">',
            '</ul>',
          '</div>',
        {
            disableFormats: true
        }
    ],

    postSubTpl: [
            '<label id="{cmpId}-placeholderLabel" data-ref="placeholderLabel" for="{cmpId}-inputEl" class="{placeholderCoverCls} {placeholderCoverCls}-{ui} {emptyCls}">{emptyText}</label>',
            '</div>', // end inputWrap
            '<tpl for="triggers">{[values.renderTrigger(parent)]}</tpl>',
        '</div>' // end triggerWrap
    ],
    /* eslint-enable indent, max-len */

    extraFieldBodyCls: Ext.baseCSSPrefix + 'tagfield-body',

    /**
     * @private
     */
    childEls: [
        'listWrapper', 'itemList', 'inputEl', 'inputElCt', 'selectedText', 'ariaList'
    ],

    /**
     * @private
     */
    clearValueOnEmpty: false,
    ariaSelectable: true,

    /**
     * @property ariaEl
     * @inheritdoc
     */
    ariaEl: 'listWrapper',

    tagItemCls: Ext.baseCSSPrefix + 'tagfield-item',
    tagItemTextCls: Ext.baseCSSPrefix + 'tagfield-item-text',
    tagItemCloseCls: Ext.baseCSSPrefix + 'tagfield-item-close',

    tagItemSelector: '.' + Ext.baseCSSPrefix + 'tagfield-item',
    tagItemCloseSelector: '.' + Ext.baseCSSPrefix + 'tagfield-item-close',
    tagSelectedCls: Ext.baseCSSPrefix + 'tagfield-item-selected',

    initComponent: function() {
        var me = this,
            typeAhead = me.typeAhead,
            delimiter = me.delimiter;

        //<debug>
        if (typeAhead && !me.editable) {
            Ext.raise('If typeAhead is enabled the combo must be editable: true -- ' +
                      'please change one of those settings.');
        }
        //</debug>

        // Allow unmatched textual values to be converted into new value records.
        if (me.createNewOnEnter || me.createNewOnBlur) {
            me.forceSelection = false;
        }

        me.typeAhead = false;

        if (me.value == null) {
            me.value = [];
        }

        // This is the selection model for selecting tags in the tag list.
        // NOT the dropdown BoundList. Create the selModel before calling parent,
        // we need it to be available when we bind the store.
        me.selectionModel = new Ext.selection.Model({
            mode: 'MULTI',
            onSelectChange: function(record, isSelected, suppressEvent, commitFn) {
                commitFn();
            },
            // Relay these selection events passing the field instead of exposing
            // the underlying selection model
            listeners: {
                scope: me,
                selectionchange: me.onSelectionChange,
                focuschange: me.onFocusChange
            }
        });

        // Users might want to implement centralized help
        if (!me.ariaHelp) {
            me.ariaHelp = me.createNewOnEnter ? me.ariaHelpTextEditable : me.ariaHelpText;
        }

        me.callParent();

        me.typeAhead = typeAhead;

        if (delimiter && me.multiSelect) {
            me.delimiterRegexp = new RegExp(Ext.String.escapeRegex(delimiter));
        }
    },

    initEvents: function() {
        var me = this,
            inputEl = me.inputEl;

        me.callParent(arguments);

        if (!me.enableKeyEvents) {
            inputEl.on('keydown', me.onKeyDown, me);
            inputEl.on('keyup', me.onKeyUp, me);
        }

        me.listWrapper.on({
            scope: me,
            click: me.onItemListClick,
            mousedown: me.onItemMouseDown
        });
    },

    createPicker: function() {
        var me = this,
            config;

        // Avoid munging config on the prototype
        config = Ext.apply({
            navigationModel: 'tagfield'
        }, me.defaultListConfig);

        if (me.ariaAvailableListLabel) {
            config.ariaRenderAttributes = {
                'aria-label': Ext.String.htmlEncode(me.ariaAvailableListLabel)
            };
        }

        me.defaultListConfig = config;

        return me.callParent();
    },

    isValid: function() {
        var me = this,
            disabled = me.disabled,
            validate = me.forceValidation || !disabled;

        return validate ? me.validateValue(me.getValue()) : disabled;
    },

    onBindStore: function(store) {
        var me = this;

        me.callParent([store]);

        if (store) {
            // We collect picked records in a value store so that a selection model
            // can track selection
            me.valueStore = new Ext.data.Store({
                model: store.getModel(),

                // Assign a proxy here so we don't get the proxy from the model
                proxy: 'memory',

                // We may have the empty store here, so just ignore empty models
                useModelWarning: false
            });

            me.selectionModel.bindStore(me.valueStore);

            // Picked records disappear from the BoundList
            if (me.filterPickList) {
                me.listFilter = new Ext.util.Filter({
                    scope: me,
                    filterFn: me.filterPicked
                });

                me.changingFilters = true;
                store.filter(me.listFilter);
                me.changingFilters = false;
            }
        }
    },

    filterPicked: function(rec) {
        return !this.valueCollection.contains(rec);
    },

    onUnbindStore: function(store) {
        var me = this,
            valueStore = me.valueStore,
            picker = me.picker;

        if (picker) {
            picker.bindStore(null);
        }

        if (valueStore) {
            valueStore.destroy();
            me.valueStore = null;
        }

        if (me.filterPickList && !store.destroyed) {
            me.changingFilters = true;
            store.removeFilter(me.listFilter);
            me.changingFilters = false;
        }

        me.callParent(arguments);
    },

    clearInput: function() {
        var me = this,
            valueRecords = me.getValueRecords(),
            inputValue = me.inputEl && me.inputEl.dom.value,
            lastDisplayValue;

        if (valueRecords.length && inputValue) {
            lastDisplayValue = valueRecords[valueRecords.length - 1].get(me.displayField);

            if (!Ext.String.startsWith(lastDisplayValue, inputValue, true)) {
                return;
            }

            me.inputEl.dom.value = '';

            if (me.queryMode === 'local') {
                me.clearLocalFilter();
                // we need to refresh the picker after removing 
                // the local filter to display the updated data
                me.getPicker().refresh();
            }
        }
    },

    onValueCollectionEndUpdate: function() {
        var me = this,
            pickedRecords = me.valueCollection.items,
            valueStore = me.valueStore;

        if (me.isSelectionUpdating()) {
            return;
        }

        // Ensure the source store is filtered down
        if (me.filterPickList) {
            me.changingFilters = true;
            me.store.filter(me.listFilter);
            me.changingFilters = false;
        }

        me.callParent();

        Ext.suspendLayouts();

        if (valueStore) {
            valueStore.suspendEvents();
            valueStore.loadRecords(pickedRecords);
            valueStore.resumeEvents();
        }

        me.refreshEmptyText();
        me.clearInput();

        Ext.resumeLayouts(true);

        me.alignPicker();
    },

    checkValueOnDataChange: Ext.emptyFn,

    onSelectionChange: function(selModel, selectedRecs) {
        var me = this,
            inputEl = me.inputEl,
            item;

        me.applyMultiselectItemMarkup();
        me.applyAriaListMarkup();
        me.applyAriaSelectedText();

        // Focus does not really change but we're pretending it does
        if (inputEl) {
            if (selectedRecs.length === 0) {
                inputEl.dom.removeAttribute('aria-activedescendant');
            }
            else {
                item = me.getAriaListNode(selectedRecs[0]);

                if (item) {
                    inputEl.dom.setAttribute('aria-activedescendant', item.id);
                }
            }
        }

        me.fireEvent('valueselectionchange', me, selectedRecs);
    },

    onFocusChange: function(selectionModel, oldFocused, newFocused) {
        var me = this;

        me.callParent([selectionModel, oldFocused, newFocused]);
        me.fireEvent('valuefocuschange', me, oldFocused, newFocused);
    },

    getAriaListNode: function(record) {
        var ariaList = this.ariaList,
            node;

        if (ariaList && record) {
            node = ariaList.selectNode('[data-recordid="' + record.internalId + '"]');
        }

        return node;
    },

    doDestroy: function() {
        Ext.destroy(this.selectionModel);

        // This will unbind the store, which will destroy the valueStore
        this.callParent();
    },

    getSubTplData: function(fieldData) {
        var me = this,
            id = me.id,
            data = me.callParent(arguments),
            emptyText = me.emptyText,
            isEmpty = emptyText && data.value.length < 1,
            growMin = me.growMin,
            growMax = me.growMax,
            wrapperStyle = '',
            attr;

        data.value = '';
        data.emptyText = isEmpty ? emptyText : '';
        data.itemListCls = '';
        data.emptyCls = isEmpty ? me.emptyUICls : '';

        if (me.grow) {
            if (Ext.isNumber(growMin) && growMin > 0) {
                wrapperStyle += 'min-height:' + growMin + 'px;';
            }

            if (Ext.isNumber(growMax) && growMax > 0) {
                wrapperStyle += 'max-height:' + growMax + 'px;';
            }
        }
        else {
            wrapperStyle += 'max-height: 1px;';
        }

        data.wrapperStyle = wrapperStyle;

        if (me.stacked === true) {
            data.itemListCls += ' ' + Ext.baseCSSPrefix + 'tagfield-stacked';
        }

        if (!me.multiSelect) {
            data.itemListCls += ' ' + Ext.baseCSSPrefix + 'tagfield-singleselect';
        }

        if (!me.ariaStaticRoles[me.ariaRole]) {
            data.multiSelect = me.multiSelect;
            data.ariaSelectedListLabel = Ext.String.htmlEncode(me.ariaSelectedListLabel);

            attr = data.ariaElAttributes;

            if (attr) {
                attr['aria-owns'] = id + '-inputEl ' + id + '-picker ' + id + '-ariaList';
            }

            attr = data.inputElAriaAttributes;

            if (attr) {
                attr.role = 'textbox';
                attr['aria-describedby'] = id + '-selectedText ' + (attr['aria-describedby'] || '');
            }
        }

        return data;
    },

    onRender: function(container, index) {
        var me = this;

        me.callParent([container, index]);
        me.emptyClsElements.push(me.listWrapper, me.placeholderLabel);
    },

    afterRender: function() {
        var me = this,
            inputEl = me.inputEl,
            emptyText = me.emptyText;

        if (emptyText) {
            // We remove HTML5 placeholder here because we use the placeholderLabel instead.
            if (Ext.supports.Placeholder && inputEl) {
                inputEl.dom.removeAttribute('placeholder');
            }
        }

        me.applyMultiselectItemMarkup();
        me.applyAriaListMarkup();
        me.applyAriaSelectedText();

        me.callParent();
    },

    findRecord: function(field, value) {
        var matches = this.getStore().queryRecords(field, value);

        return matches.length ? matches[0] : false;
    },

    /**
     * Get the current cursor position in the input field, for key-based navigation
     * @private
     */
    getCursorPosition: function() {
        var cursorPos;

        if (document.selection) {
            cursorPos = document.selection.createRange();
            cursorPos.collapse(true);
            cursorPos.moveStart('character', -this.inputEl.dom.value.length);
            cursorPos = cursorPos.text.length;
        }
        else {
            cursorPos = this.inputEl.dom.selectionStart;
        }

        return cursorPos;
    },

    /**
     * Check to see if the input field has selected text, for key-based navigation
     * @private
     */
    hasSelectedText: function() {
        var inputEl = this.inputEl.dom,
            sel, range;

        if (document.selection) {
            sel = document.selection;
            range = sel.createRange();

            return (range.parentElement() === inputEl);
        }
        else {
            return inputEl.selectionStart !== inputEl.selectionEnd;
        }
    },

    /**
     * Handles keyDown processing of key-based selection of labeled items.
     * Supported keyboard controls:
     *
     * - If pick list is expanded
     *
     *     - `CTRL-A` will select all the items in the pick list
     *
     * - If the cursor is at the beginning of the input field and there are values present
     *
     *     - `CTRL-A` will highlight all the currently selected values
     *     - `BACKSPACE` and `DELETE` will remove any currently highlighted selected values
     *     - `RIGHT` and `LEFT` will move the current highlight in the appropriate direction
     *     - `SHIFT-RIGHT` and `SHIFT-LEFT` will add to the current highlight in the appropriate
     *       direction
     *
     * @protected
     */
    onKeyDown: function(e) {
        var me = this,
            key = e.getKey(),
            inputEl = me.inputEl,
            rawValue = inputEl && inputEl.dom.value,
            valueCollection = me.valueCollection,
            selModel = me.selectionModel,
            stopEvent = false,
            valueCount, lastSelectionIndex, records, text, i, len;

        if (me.destroyed || me.readOnly || me.disabled || !me.editable) {
            return;
        }

        valueCount = valueCollection.getCount();

        if (valueCount > 0 && rawValue === '') {
            // Keyboard navigation of current values
            lastSelectionIndex = (selModel.getCount() > 0)
                ? valueCollection.indexOf(selModel.getLastSelected())
                : -1;

            // Backspace can be used to clear the rightmost selected value.
            // Delete key should only remove selected value if it is highlighted.
            if ((key === e.BACKSPACE && me.clearOnBackspace) ||
                (key === e.DELETE && lastSelectionIndex > -1)) {
                // Delete token
                if (lastSelectionIndex > -1) {
                    if (selModel.getCount() > 1) {
                        lastSelectionIndex = -1;
                    }

                    records = selModel.getSelection();
                    text = [];

                    for (i = 0, len = records.length; i < len; i++) {
                        text.push(records[i].get(me.displayField));
                    }

                    text = text.join(', ');
                }
                else {
                    records = valueCollection.last();
                    text = records.get(me.displayField);
                }

                valueCollection.remove(records);

                // Announce the change
                if (text) {
                    me.ariaErrorEl.dom.innerHTML =
                        Ext.String.formatEncode(me.ariaDeselectedText, text);
                }

                selModel.clearSelections();

                if (lastSelectionIndex === (valueCount - 1)) {
                    selModel.select(valueCollection.last());
                }
                else if (lastSelectionIndex > -1) {
                    selModel.select(lastSelectionIndex);
                }
                else if (valueCollection.getCount()) {
                    selModel.select(valueCollection.last());
                }

                stopEvent = true;
            }
            else if (key === e.RIGHT || key === e.LEFT) {
                // Navigate and select tokens
                if (lastSelectionIndex === -1 && key === e.LEFT) {
                    selModel.select(valueCollection.last());
                    stopEvent = true;
                }
                else if (lastSelectionIndex > -1) {
                    if (key === e.RIGHT) {
                        if (lastSelectionIndex < (valueCount - 1)) {
                            selModel.select(lastSelectionIndex + 1, e.shiftKey);
                            stopEvent = true;
                        }
                        else if (!e.shiftKey) {
                            selModel.deselectAll();
                            stopEvent = true;
                        }
                    }
                    else if (key === e.LEFT && (lastSelectionIndex > 0)) {
                        selModel.select(lastSelectionIndex - 1, e.shiftKey);
                        stopEvent = true;
                    }
                }
            }
            else if (key === e.A && e.ctrlKey) {
                // Select all tokens
                selModel.selectAll();
                stopEvent = e.A;
            }
        }

        if (stopEvent) {
            me.preventKeyUpEvent = stopEvent;
            e.stopEvent();

            return;
        }

        // Prevent key up processing for enter if it is being handled by the picker
        if (me.isExpanded && key === e.ENTER && me.picker.highlightedItem) {
            me.preventKeyUpEvent = true;
        }

        if (me.enableKeyEvents) {
            me.callParent(arguments);
        }

        if (!e.isSpecialKey() && !e.hasModifier()) {
            selModel.deselectAll();
        }
    },

    /**
     * Handles auto-selection and creation of labeled items based on this field's
     * delimiter, as well as the keyUp processing of key-based selection of labeled items.
     * @protected
     */
    onKeyUp: function(e, t) {
        var me = this,
            inputEl = me.inputEl,
            rawValue = inputEl.dom.value,
            preventKeyUpEvent = me.preventKeyUpEvent;

        if (me.preventKeyUpEvent) {
            e.stopEvent();

            if (preventKeyUpEvent === true || e.getKey() === preventKeyUpEvent) {
                delete me.preventKeyUpEvent;
            }

            return;
        }

        if (me.multiSelect && me.delimiterRegexp && me.delimiterRegexp.test(rawValue) ||
                (me.createNewOnEnter && e.getKey() === e.ENTER)) {
            // Announce new value(s)
            if (me.createNewOnEnter && rawValue) {
                me.ariaErrorEl.dom.innerHTML =
                    Ext.String.formatEncode(me.ariaSelectedText, rawValue);
            }

            rawValue = Ext.Array.clean(rawValue.split(me.delimiterRegexp));
            inputEl.dom.value = '';

            me.setValue(me.valueStore.getRange().concat(rawValue));

            inputEl.focus();
        }

        me.callParent([e, t]);
    },

    onEsc: function(e) {
        var me = this,
            selModel = me.selectionModel,
            isExpanded = me.isExpanded;

        me.callParent([e]);

        if (!isExpanded && selModel.getCount() > 0) {
            selModel.deselectAll();
        }

        e.stopEvent();
    },

    /**
     * Overridden to get and set the DOM value directly for type-ahead suggestion
     * (bypassing get/setRawValue)
     * @protected
     */
    onTypeAhead: function() {
        var me = this,
            displayField = me.displayField,
            inputElDom = me.inputEl.dom,
            record = me.getStore().findRecord(displayField, inputElDom.value),
            newValue, len, selStart;

        if (record) {
            newValue = record.get(displayField);
            len = newValue.length;
            selStart = inputElDom.value.length;

            if (selStart !== 0 && selStart !== len) {
                // Setting the raw value will cause a field mutation event.
                // Prime the lastMutatedValue so that this does not cause a requery.
                me.lastMutatedValue = newValue;

                inputElDom.value = newValue;
                me.selectText(selStart, newValue.length);
            }
        }
    },

    /**
     * Delegation control for selecting and removing labeled items or triggering
     * list collapse/expansion
     * @protected
     */
    onItemListClick: function(e) {
        var me = this,
            selectionModel = me.selectionModel,
            itemEl = e.getTarget(me.tagItemSelector),
            closeEl = itemEl ? e.getTarget(me.tagItemCloseSelector) : false;

        if (me.readOnly || me.disabled) {
            return;
        }

        e.stopPropagation();

        if (itemEl) {
            if (closeEl) {
                me.removeByListItemNode(itemEl);

                if (me.valueStore.getCount() > 0) {
                    me.fireEvent('select', me, me.valueStore.getRange());
                }
            }
            else {
                me.toggleSelectionByListItemNode(itemEl, e.shiftKey);
            }

            // If not using touch interactions, focus the input
            if (!Ext.supports.TouchEvents) {
                me.inputEl.focus();
            }
        }
        else {
            if (selectionModel.getCount() > 0) {
                selectionModel.deselectAll();
            }

            me.inputEl.focus();

            if (me.triggerOnClick) {
                me.onTriggerClick();
            }
        }
    },

    // Prevent item from receiving focus.
    // See EXTJS-17686.
    onItemMouseDown: function(e) {
        if (e.target !== this.inputEl.dom) {
            e.preventDefault();
        }
    },

    /**
     * Build the markup for the labeled items. Template must be built on demand due to ComboBox
     * initComponent life cycle for the creation of on-demand stores (to account for automatic
     * valueField/displayField setting)
     * @private
     */
    getMultiSelectItemMarkup: function() {
        var me = this,
            childElCls = (me._getChildElCls && me._getChildElCls()) || ''; // hook for rtl cls

        if (!me.multiSelectItemTpl) {
            if (!me.labelTpl) {
                me.labelTpl = '{' + me.displayField + '}';
            }

            me.labelTpl = me.lookupTpl('labelTpl');

            if (me.tipTpl) {
                me.tipTpl = me.lookupTpl('tipTpl');
            }

            /* eslint-disable indent, max-len */
            me.multiSelectItemTpl = new Ext.XTemplate([
                '<tpl for=".">',
                    '<li data-selectionIndex="{[xindex - 1]}" data-recordId="{internalId}" role="presentation" class="' + me.tagItemCls + childElCls,
                    '<tpl if="this.isSelected(values)">',
                    ' ' + me.tagSelectedCls,
                    '</tpl>',
                    '{%',
                        'values = values.data;',
                    '%}',
                    me.tipTpl ? '" data-qtip="{[this.getTip(values)]}">' : '">',
                    '<div role="presentation" class="' + me.tagItemTextCls + '">{[this.getItemLabel(values)]}</div>',
                    '<div role="presentation" class="' + me.tagItemCloseCls + childElCls + '"></div>',
                    '</li>',
                '</tpl>',
                {
                    isSelected: function(rec) {
                        return me.selectionModel.isSelected(rec);
                    },
                    getItemLabel: function(values) {
                        return Ext.String.htmlEncode(me.labelTpl.apply(values));
                    },
                    getTip: function(values) {
                        return Ext.String.htmlEncode(me.tipTpl.apply(values));
                    },
                    strict: true
                }
            ]);
            /* eslint-enable indent, max-len */
        }

        if (!me.multiSelectItemTpl.isTemplate) {
            me.multiSelectItemTpl = this.lookupTpl('multiSelectItemTpl');
        }

        return me.multiSelectItemTpl.apply(me.valueCollection.getRange());
    },

    /**
     * Update the labeled items rendering
     * @private
     */
    applyMultiselectItemMarkup: function() {
        var me = this,
            itemList = me.itemList;

        if (itemList) {
            itemList.select('.' + Ext.baseCSSPrefix + 'tagfield-item').destroy();
            me.inputElCt.insertHtml('beforeBegin', me.getMultiSelectItemMarkup());
            me.autoSize();
        }
    },

    /**
     * Build the markup for ARIA listbox.
     * @private
     */
    getAriaListMarkup: function() {
        var me = this,
            values;

        if (!me.ariaListItemTpl) {
            /* eslint-disable indent, max-len */
            me.ariaListItemTpl = new Ext.XTemplate([
                '<tpl for=".">',
                    '<li id="' + me.id + '-{internalId}" role="option"',
                        ' class="' + Ext.baseCSSPrefix + 'tagfield-arialist-item"',
                        ' aria-selected="{[this.isPicked(values)]}"',
                        '  data-recordId="{internalId}"',
                        '>',
                            '{[this.getItemLabel(values.data)]}',
                    '</li>',
                '</tpl>',
                {
                    isPicked: function(rec) {
                        return me.filterPicked(rec) ? 'false' : 'true';
                    },
                    isSelected: function(rec) {
                        return me.selectionModel.isSelected(rec) ? 'true' : 'false';
                    },
                    getItemLabel: function(values) {
                        return Ext.String.htmlEncode(me.labelTpl.apply(values));
                    },
                    strict: true
                }
            ]);
            /* eslint-enable indent, max-len */
        }

        if (!me.ariaListItemTpl.isTemplate) {
            me.ariaListtemTpl = me.lookupTpl('ariaListItemTpl');
        }

        values = me.valueCollection.getRange();

        return me.ariaListItemTpl.apply(values);
    },

    applyAriaListMarkup: function() {
        var me = this,
            ariaList = me.ariaList;

        if (ariaList) {
            ariaList.select('*').destroy();
            ariaList.insertHtml('afterBegin', me.getAriaListMarkup());
        }
    },

    getAriaSelectedText: function(values) {
        var me = this;

        if (!me.ariaSelectedItemTpl) {
            /* eslint-disable indent */
            me.ariaSelectedItemTpl = new Ext.XTemplate([
                '<tpl for="." between=", ">',
                    '{[this.getItemLabel(values.data)]}',
                '</tpl>',
                {
                    getItemLabel: function(values) {
                        return Ext.String.htmlEncode(me.labelTpl.apply(values));
                    },
                    strict: true
                }
            ]);
            /* eslint-enable indent */
        }

        if (!me.ariaSelectedItemTpl.isTemplate) {
            me.ariaSelectedItemTpl = me.lookupTpl('ariaSelectedItemTpl');
        }

        return Ext.String.format(me.ariaSelectedText, me.ariaSelectedItemTpl.apply(values));
    },

    applyAriaSelectedText: function() {
        var me = this,
            selectedText = me.selectedText,
            records, text;

        if (selectedText) {
            records = me.valueCollection.getRange();
            text = records.length ? me.getAriaSelectedText(records) : me.ariaNoneSelectedText;

            // selectedText element is not aria-live so OK to update every time
            selectedText.dom.innerHTML = Ext.String.htmlEncode(text);
        }
    },

    /**
     * Returns the record from valueStore for the labeled item node
     */
    getRecordByListItemNode: function(itemEl) {
        return this.valueCollection.items[Number(itemEl.getAttribute('data-selectionIndex'))];
    },

    /**
     * Toggle of labeled item selection by node reference
     */
    toggleSelectionByListItemNode: function(itemEl, keepExisting) {
        var me = this,
            rec = me.getRecordByListItemNode(itemEl),
            selModel = me.selectionModel;

        if (rec) {
            if (selModel.isSelected(rec)) {
                selModel.deselect(rec);
            }
            else {
                selModel.select(rec, keepExisting);
            }
        }
    },

    /**
     * Removal of labelled item by node reference
     */
    removeByListItemNode: function(itemEl) {
        var me = this,
            rec = me.getRecordByListItemNode(itemEl);

        if (rec) {
            me.pickerSelectionModel.deselect(rec);
        }
    },

    // Private implementation.
    // The display value is always the raw value.
    // Picked values are displayed by the tag template.
    getDisplayValue: function() {
        return this.getRawValue();
    },

    /**
     * @method getRawValue
     * @inheritdoc
     * Intercept calls to getRawValue to pretend there is no inputEl for rawValue handling,
     * so that we can use inputEl for user input of just the current value.
     */
    getRawValue: function() {
        var me = this,
            records = me.getValueRecords(),
            values = [],
            i, len;

        for (i = 0, len = records.length; i < len; i++) {
            values.push(records[i].data[me.displayField]);
        }

        return values.join(',');
    },

    setRawValue: function(value) {
        // setRawValue is not supported for tagfield.
        return;
    },

    /**
     * Removes a value or values from the current value of the field
     * @param {Mixed} value The value or values to remove from the current value,
     * see {@link #setValue}
     */
    removeValue: function(value) {
        var me = this,
            valueCollection = me.valueCollection,
            len, i, item,
            toRemove = [];

        if (value) {
            value = Ext.Array.from(value);

            // Ensure that the remove values are records
            for (i = 0, len = value.length; i < len; ++i) {
                item = value[i];

                // If a key is supplied, find the matching value record from our value collection
                if (!item.isModel) {
                    item = valueCollection.byValue.get(item);
                }

                if (item) {
                    toRemove.push(item);
                }
            }

            me.valueCollection.beginUpdate();
            me.pickerSelectionModel.deselect(toRemove);
            me.valueCollection.endUpdate();
        }
    },

    getValue: function() {
        var value = this.callParent();

        if (value) {
            value = Ext.Array.from(value);
        }

        return value;
    },

    /**
     * Sets the specified value(s) into the field. The following value formats are recognized:
     *
     * - Single Values
     *
     *     - A string associated to this field's configured {@link #valueField}
     *     - A record containing at least this field's configured {@link #valueField} and
     *      {@link #displayField}
     *
     * - Multiple Values
     *
     *     - If {@link #multiSelect} is `true`, a string containing multiple strings as
     *       specified in the Single Values section above, concatenated in to one string
     *       with each entry separated by this field's configured {@link #delimiter}
     *     - An array of strings as specified in the Single Values section above
     *     - An array of records as specified in the Single Values section above
     *
     * In any of the string formats above, the following occurs if an associated record cannot
     * be found:
     *
     * 1. If {@link #forceSelection} is `false`, a new record of the {@link #store}'s configured
     *    model type will be created using the given value as the {@link #displayField} and
     *    {@link #valueField}. This record will be added to the current value, but it will **not**
     *    be added to the store.
     * 2. If {@link #forceSelection} is `true` and {@link #queryMode} is `remote`, the list
     *    of unknown values will be submitted as a call to the {@link #store}'s load as a parameter
     *    named by the {@link #valueParam} with values separated by the configured
     *    {@link #delimiter}.
     *    ** This process will cause setValue to asynchronously process. ** This will only be
     *    attempted once. Any unknown values that the server does not return records for
     *    will be removed.
     * 3. Otherwise, unknown values will be removed.
     *
     * @param {Mixed} value The value(s) to be set, see method documentation for details
     * @param add (private)
     * @param skipLoad (private)
     * @return {Ext.form.field.Field/Boolean} this, or `false` if asynchronously querying
     * for unknown values
     */
    setValue: function(value, add, skipLoad) {
        var me = this,
            valueStore = me.valueStore,
            valueField = me.valueField,
            unknownValues = [],
            store = me.store,
            autoLoadOnValue = me.autoLoadOnValue,
            isLoaded = store.getCount() > 0 || store.isLoaded(),
            pendingLoad = store.hasPendingLoad(),
            unloaded = autoLoadOnValue && !isLoaded && !pendingLoad,
            record, len, i, valueRecord, cls, params, isNull;

        if (Ext.isEmpty(value)) {
            value = null;
            isNull = true;
        }
        else if (Ext.isString(value) && me.multiSelect) {
            value = value.split(me.delimiter);
        }
        else {
            value = Ext.Array.from(value, true);
        }

        if (!isNull && me.queryMode === 'remote' && !store.isEmptyStore &&
            skipLoad !== true && unloaded) {
            for (i = 0, len = value.length; i < len; i++) {
                record = value[i];

                if (!record || !record.isModel) {
                    valueRecord = valueStore.findExact(valueField, record);

                    if (valueRecord > -1) {
                        value[i] = valueStore.getAt(valueRecord);
                    }
                    else {
                        valueRecord = me.findRecord(valueField, record);

                        if (!valueRecord) {
                            if (me.forceSelection) {
                                unknownValues.push(record);
                            }
                            else {
                                valueRecord = {};
                                valueRecord[me.valueField] = record;
                                valueRecord[me.displayField] = record;

                                cls = me.valueStore.getModel();
                                valueRecord = new cls(valueRecord);
                            }
                        }

                        if (valueRecord) {
                            value[i] = valueRecord;
                        }
                    }
                }
            }

            if (unknownValues.length) {
                params = {};
                params[me.valueParam || me.valueField] = unknownValues.join(me.delimiter);

                store.load({
                    params: params,
                    callback: function() {
                        me.setValue(value, add, true);
                        me.autoSize();
                        me.lastQuery = false;
                    }
                });

                return false;
            }
        }

        // For single-select boxes, use the last good (formal record) value if possible
        if (!isNull && !me.multiSelect && value.length > 0) {
            for (i = value.length - 1; i >= 0; i--) {
                if (value[i].isModel) {
                    value = value[i];
                    break;
                }
            }

            if (Ext.isArray(value)) {
                value = value[value.length - 1];
            }
        }

        return me.callParent([value, add]);
    },

    // Private internal setting of value when records are added to the valueCollection
    // setValue itself adds to the valueCollection.
    updateValue: function() {
        var me = this,
            valueArray = me.valueCollection.getRange(),
            len = valueArray.length,
            i;

        for (i = 0; i < len; i++) {
            valueArray[i] = valueArray[i].get(me.valueField);
        }

        // Set the value of this field. If we are multi-selecting, then that is an array.
        me.setHiddenValue(valueArray);
        me.value = me.multiSelect ? valueArray : valueArray[0];

        if (!Ext.isDefined(me.value)) {
            me.value = undefined;
        }

        me.applyMultiselectItemMarkup();
        me.applyAriaListMarkup();
        me.applyAriaSelectedText();

        me.checkChange();
    },

    /**
     * Returns the records for the field's current value
     * @return {Array} The records for the field's current value
     */
    getValueRecords: function() {
        return this.valueCollection.getRange();
    },

    /**
     * @method getSubmitData
     * @inheritdoc
     * Overridden to optionally allow for submitting the field as a json encoded array.
     */
    getSubmitData: function() {
        var me = this,
            val = me.callParent(arguments);

        if (me.multiSelect && me.encodeSubmitValue && val && val[me.name]) {
            val[me.name] = Ext.encode(val[me.name]);
        }

        return val;
    },

    /**
     * Overridden to handle partial-input selections more directly
     */
    assertValue: function() {
        var me = this,
            rawValue = me.inputEl.dom.value,
            rec = !Ext.isEmpty(rawValue) ? me.findRecordByDisplay(rawValue) : false,
            value = false;

        if (!rec && !me.forceSelection && me.createNewOnBlur && !Ext.isEmpty(rawValue)) {
            value = rawValue;
        }
        else if (rec) {
            value = rec;
        }

        if (value) {
            me.addValue(value);
        }

        me.inputEl.dom.value = '';

        me.collapse();
        me.refreshEmptyText();
    },

    /**
     * Overridden to be more accepting of varied value types
     */
    isEqual: function(v1, v2) {
        var fromArray = Ext.Array.from,
            valueField = this.valueField,
            i, len, t1, t2;

        v1 = fromArray(v1);
        v2 = fromArray(v2);
        len = v1.length;

        if (len !== v2.length) {
            return false;
        }

        for (i = 0; i < len; i++) {
            t1 = v1[i].isModel ? v1[i].get(valueField) : v1[i];
            t2 = v2[i].isModel ? v2[i].get(valueField) : v2[i];

            if (t1 !== t2) {
                return false;
            }
        }

        return true;
    },

    /**
     * Intercept calls to onFocus to add focusCls, because the base field
     * classes assume this should be applied to inputEl
     */
    onFocus: function() {
        var me = this,
            focusCls = me.focusCls,
            itemList = me.itemList;

        if (focusCls && itemList) {
            itemList.addCls(focusCls);
        }

        me.callParent(arguments);
    },

    /**
     * Intercept calls to onBlur to remove focusCls, because the base field
     * classes assume this should be applied to inputEl
     */
    onBlur: function() {
        var me = this,
            focusCls = me.focusCls,
            itemList = me.itemList;

        if (focusCls && itemList) {
            itemList.removeCls(focusCls);
        }

        me.callParent(arguments);
    },

    /**
     * Intercept calls to renderActiveError to add invalidCls, because the base
     * field classes assume this should be applied to inputEl
     */
    renderActiveError: function() {
        var me = this,
            invalidCls = me.invalidCls,
            itemList = me.itemList,
            hasError = me.hasActiveError();

        if (invalidCls && itemList) {
            itemList[hasError ? 'addCls' : 'removeCls'](me.invalidCls + '-field');
        }

        me.callParent(arguments);
    },

    /**
     * Initiate auto-sizing for height based on {@link #grow}, if applicable.
     */
    autoSize: function() {
        var me = this;

        if (me.grow && me.rendered) {
            me.autoSizing = true;
            me.updateLayout();
        }

        return me;
    },

    /**
     * Track height change to fire {@link #event-autosize} event, when applicable.
     */
    afterComponentLayout: function() {
        var me = this,
            height;

        if (me.autoSizing) {
            height = me.getHeight();

            if (height !== me.lastInputHeight) {
                if (me.isExpanded) {
                    me.alignPicker();
                }

                me.fireEvent('autosize', me, height);
                me.lastInputHeight = height;
                me.autoSizing = false;
            }
        }
    }
});
