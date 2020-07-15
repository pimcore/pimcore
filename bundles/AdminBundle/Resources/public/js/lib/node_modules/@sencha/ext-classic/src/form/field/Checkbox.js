/**
 * Single checkbox field. Can be used as a direct replacement for traditional checkbox fields.
 * Also serves as a parent class for {@link Ext.form.field.Radio radio buttons}.
 *
 * ## Labeling
 *
 * In addition to the {@link Ext.form.Labelable standard field labeling options}, checkboxes
 * may be given an optional {@link #boxLabel} which will be displayed immediately after checkbox.
 * Also see {@link Ext.form.CheckboxGroup} for a convenient method of grouping related checkboxes.
 *
 * # Values
 *
 * The main value of a checkbox is a boolean, indicating whether or not the checkbox is checked.
 * The following values will check the checkbox:
 *
 * - `true`
 * - `'true'`
 * - `'1'`
 * - `'on'`
 *
 * Any other value will un-check the checkbox.
 *
 * In addition to the main boolean value, you may also specify a separate {@link #inputValue}.
 * This will be sent as the parameter value when the form is
 * {@link Ext.form.Basic#submit submitted}. You will want to set this value if you have multiple
 * checkboxes with the same {@link #name}. If not specified, the value `on` will be used.
 *
 * ## Example usage
 *
 *     @example
 *     Ext.create('Ext.form.Panel', {
 *         bodyPadding: 10,
 *         width: 300,
 *         title: 'Pizza Order',
 *         items: [
 *             {
 *                 xtype: 'fieldcontainer',
 *                 fieldLabel: 'Toppings',
 *                 defaultType: 'checkboxfield',
 *                 items: [
 *                     {
 *                         boxLabel  : 'Anchovies',
 *                         name      : 'topping',
 *                         inputValue: '1',
 *                         id        : 'checkbox1'
 *                     }, {
 *                         boxLabel  : 'Artichoke Hearts',
 *                         name      : 'topping',
 *                         inputValue: '2',
 *                         checked   : true,
 *                         id        : 'checkbox2'
 *                     }, {
 *                         boxLabel  : 'Bacon',
 *                         name      : 'topping',
 *                         inputValue: '3',
 *                         id        : 'checkbox3'
 *                     }
 *                 ]
 *             }
 *         ],
 *         bbar: [
 *             {
 *                 text: 'Select Bacon',
 *                 handler: function() {
 *                     Ext.getCmp('checkbox3').setValue(true);
 *                 }
 *             },
 *             '-',
 *             {
 *                 text: 'Select All',
 *                 handler: function() {
 *                     Ext.getCmp('checkbox1').setValue(true);
 *                     Ext.getCmp('checkbox2').setValue(true);
 *                     Ext.getCmp('checkbox3').setValue(true);
 *                 }
 *             },
 *             {
 *                 text: 'Deselect All',
 *                 handler: function() {
 *                     Ext.getCmp('checkbox1').setValue(false);
 *                     Ext.getCmp('checkbox2').setValue(false);
 *                     Ext.getCmp('checkbox3').setValue(false);
 *                 }
 *             }
 *         ],
 *         renderTo: Ext.getBody()
 *     });
 */
Ext.define('Ext.form.field.Checkbox', {
    extend: 'Ext.form.field.Base',
    alias: ['widget.checkboxfield', 'widget.checkbox'],
    alternateClassName: 'Ext.form.Checkbox',
    requires: ['Ext.XTemplate', 'Ext.form.CheckboxManager' ],

    /**
     * @cfg {Boolean/String/Number} modelValue
     * The value to use for {@link #getModelData} when checked.
     *
     * @since 6.2.1
     */
    modelValue: true,

    /**
     * @cfg {Boolean/String/Number} modelValueUnchecked
     * The value to use for {@link #getModelData} when unchecked.
     *
     * @since 6.2.1
     */
    modelValueUnchecked: false,

    // inputEl should always retain the same size, never stretch
    stretchInputElFixed: false,

    /**
     * @property {Ext.dom.Element} boxLabelEl
     * A reference to the label element created for the {@link #boxLabel}. Only present
     * if the component has been rendered and has a boxLabel configured.
     */

    /**
     * @cfg childEls
     * @inheritdoc
     */
    childEls: [
        'boxLabelEl',
        'innerWrapEl',
        'displayEl'
    ],

    /* eslint-disable indent, max-len */
    // note: {id} here is really {inputId}, but {cmpId} is available
    /**
     * @cfg fieldSubTpl
     * @inheritdoc
     */
    fieldSubTpl: [
        '<div id="{cmpId}-innerWrapEl" data-ref="innerWrapEl" role="presentation"',
            ' class="{wrapInnerCls}">',
            '<tpl if="labelAlignedBefore">',
                '{beforeBoxLabelTpl}',
                '<label id="{cmpId}-boxLabelEl" data-ref="boxLabelEl" {boxLabelAttrTpl} class="{boxLabelCls} ',
                        '{boxLabelCls}-{ui} {boxLabelCls}-{boxLabelAlign} {noBoxLabelCls} {childElCls}" for="{id}">',
                    '{beforeBoxLabelTextTpl}',
                    '{boxLabel}',
                    '{afterBoxLabelTextTpl}',
                '</label>',
                '{afterBoxLabelTpl}',
            '</tpl>',
            '<span id="{cmpId}-displayEl" data-ref="displayEl" role="presentation" class="{fieldCls} {typeCls} ',
                '{typeCls}-{ui} {inputCls} {inputCls}-{ui} {fixCls} {childElCls} {afterLabelCls}">',
                '<input type="{inputType}" id="{id}" name="{inputName}" data-ref="inputEl" {inputAttrTpl}',
                    '<tpl if="tabIdx != null"> tabindex="{tabIdx}"</tpl>',
                    '<tpl if="disabled"> disabled="disabled"</tpl>',
                    '<tpl if="checked"> checked="checked"</tpl>',
                    '<tpl if="fieldStyle"> style="{fieldStyle}"</tpl>',
                    ' class="{checkboxCls}" autocomplete="off" hidefocus="true" ',
                    '<tpl foreach="ariaElAttributes"> {$}="{.}"</tpl>',
                    '<tpl foreach="inputElAriaAttributes"> {$}="{.}"</tpl>',
                    '/>',
            '</span>',
            '<tpl if="!labelAlignedBefore">',
                '{beforeBoxLabelTpl}',
                '<label id="{cmpId}-boxLabelEl" data-ref="boxLabelEl" {boxLabelAttrTpl} class="{boxLabelCls} ',
                        '{boxLabelCls}-{ui} {boxLabelCls}-{boxLabelAlign} {noBoxLabelCls} {childElCls}" for="{id}">',
                    '{beforeBoxLabelTextTpl}',
                    '{boxLabel}',
                    '{afterBoxLabelTextTpl}',
                '</label>',
                '{afterBoxLabelTpl}',
            '</tpl>',
        '</div>',
        {
            disableFormats: true,
            compiled: true
        }
    ],
    /* eslint-enable indent, max-len */

    /**
     * @cfg publishes
     * @inheritdoc
     */
    publishes: {
        checked: 1
    },

    subTplInsertions: [
        /**
         * @cfg {String/Array/Ext.XTemplate} beforeBoxLabelTpl
         * An optional string or `XTemplate` configuration to insert in the field markup
         * before the box label element. If an `XTemplate` is used, the component's
         * {@link Ext.form.field.Base#getSubTplData subTpl data} serves as the context.
         */
        'beforeBoxLabelTpl',

        /**
         * @cfg {String/Array/Ext.XTemplate} afterBoxLabelTpl
         * An optional string or `XTemplate` configuration to insert in the field markup
         * after the box label element. If an `XTemplate` is used, the component's
         * {@link Ext.form.field.Base#getSubTplData subTpl data} serves as the context.
         */
        'afterBoxLabelTpl',

        /**
         * @cfg {String/Array/Ext.XTemplate} beforeBoxLabelTextTpl
         * An optional string or `XTemplate` configuration to insert in the field markup
         * before the box label text. If an `XTemplate` is used, the component's
         * {@link Ext.form.field.Base#getSubTplData subTpl data} serves as the context.
         */
        'beforeBoxLabelTextTpl',

        /**
         * @cfg {String/Array/Ext.XTemplate} afterBoxLabelTextTpl
         * An optional string or `XTemplate` configuration to insert in the field markup
         * after the box label text. If an `XTemplate` is used, the component's
         * {@link Ext.form.field.Base#getSubTplData subTpl data} serves as the context.
         */
        'afterBoxLabelTextTpl',

        /**
         * @cfg {String/Array/Ext.XTemplate} boxLabelAttrTpl
         * An optional string or `XTemplate` configuration to insert in the field markup
         * inside the box label element (as attributes). If an `XTemplate` is used, the component's
         * {@link Ext.form.field.Base#getSubTplData subTpl data} serves as the context.
         */
        'boxLabelAttrTpl',

        'inputAttrTpl'
    ],

    /**
     * @property {Boolean} isCheckbox
     * `true` in this class to identify an object as an instantiated Checkbox, or subclass thereof.
     */
    isCheckbox: true,

    /**
     * @cfg {String} focusCls
     * The CSS class to use when the checkbox receives focus
     */
    focusCls: 'form-checkbox-focus',

    /**
     * @cfg {String} [fieldCls='x-form-field']
     * The default CSS class for the checkbox
     */

    /**
     * @private
     */
    fieldBodyCls: Ext.baseCSSPrefix + 'form-cb-wrap',

    /**
     * @cfg {Boolean} checked
     * true if the checkbox should render initially checked
     */
    checked: false,

    /**
     * @cfg {String} checkedCls
     * The CSS class(es) added to the component's main element when it is in the checked state.
     * You can add your own class (checkedCls='myClass x-form-cb-checked') or replace the default 
     * class altogether (checkedCls='myClass').
     */
    checkedCls: Ext.baseCSSPrefix + 'form-cb-checked',

    /**
     * @cfg {String} boxLabel
     * An optional text label that will appear next to the checkbox. Whether it appears before
     * or after the checkbox is determined by the {@link #boxLabelAlign} config.
     */

    /**
     * @cfg {String} boxLabelCls
     * The CSS class to be applied to the {@link #boxLabel} element
     */
    boxLabelCls: Ext.baseCSSPrefix + 'form-cb-label',

    /**
     * @cfg {String} boxLabelAlign
     * The position relative to the checkbox where the {@link #boxLabel} should appear.
     * Recognized values are 'before' and 'after'.
     */
    boxLabelAlign: 'after',

    afterLabelCls: Ext.baseCSSPrefix + 'form-cb-after',

    wrapInnerCls: Ext.baseCSSPrefix + 'form-cb-wrap-inner',

    noBoxLabelCls: Ext.baseCSSPrefix + 'form-cb-no-box-label',

    /**
     * @cfg {String/Boolean} inputValue
     * The value that should go into the generated input element's value attribute and
     * should be used as the parameter value when submitting as part of a form.
     */
    inputValue: 'on',

    /**
     * @cfg {String} uncheckedValue
     * If configured, this will be submitted as the checkbox's value during form submit
     * if the checkbox is unchecked. By default this is undefined, which results in
     * nothing being submitted for the checkbox field when the form is submitted
     * (the default behavior of HTML checkboxes).
     */

    /**
     * @cfg {Function/String} [handler=undefined]
     * A function called when the {@link #checked} value changes (can be used instead of handling
     * the {@link #change change event}).
     * @cfg {Ext.form.field.Checkbox} handler.checkbox The Checkbox being toggled.
     * @cfg {Boolean} handler.checked The new checked state of the checkbox.
     * @controllable
     */

    /**
     * @cfg {Object} scope
     * An object to use as the scope ('this' reference) of the {@link #handler} function.
     *
     * Defaults to this Checkbox.
     */

    /**
     * @private
     */
    checkChangeEvents: [],

    // See IE8 override
    changeEventName: 'change',

    /**
     * @cfg inputType
     * @inheritdoc
     */
    inputType: 'checkbox',

    /**
     * @cfg isTextInput
     * @inheritdoc
     */
    isTextInput: false,

    /**
     * @property ariaRole
     * @inheritdoc
     */
    ariaRole: 'native',

    /**
     * @private
     */
    onRe: /^on$/i,

    // the form-cb css class is for styling shared between checkbox and subclasses (radio)
    inputCls: Ext.baseCSSPrefix + 'form-cb',
    _checkboxCls: Ext.baseCSSPrefix + 'form-cb-input',

    initComponent: function() {
        var me = this,
            value = me.value;

        if (value !== undefined) {
            me.checked = me.isChecked(value, me.inputValue);
        }

        me.callParent();

        me.getManager().add(me);
    },

    // Checkboxes and Radio buttons may have their names managed by their respective group.
    // This happens in CheckboxGroup.onAdd() so we skip default name assignment here.
    initDefaultName: Ext.emptyFn,

    initValue: function() {
        var me = this,
            checked = !!me.checked;

        /**
         * @property {Object} originalValue
         * The original value of the field as configured in the {@link #checked} configuration,
         * or as loaded by the last form load operation if the form's
         * {@link Ext.form.Basic#trackResetOnLoad trackResetOnLoad} setting is `true`.
         */
        me.originalValue = me.initialValue = me.lastValue = checked;

        // Set the initial checked state
        me.setValue(checked);
    },

    getElConfig: function() {
        var me = this;

        // Add the checked class if this begins checked
        if (me.isChecked(me.rawValue, me.inputValue)) {
            me.addCls(me.checkedCls);
        }

        if (!me.fieldLabel) {
            me.skipLabelForAttribute = true;
        }

        return me.callParent();
    },

    getModelData: function() {
        var me = this,
            o = me.callParent(arguments);

        if (o) {
            o[me.getName()] = me.checked ? me.modelValue : me.modelValueUnchecked;
        }

        return o;
    },

    getSubTplData: function(fieldData) {
        var me = this,
            boxLabel = me.boxLabel,
            boxLabelAlign = me.boxLabelAlign,
            labelAlignedBefore = boxLabelAlign === 'before',
            data, inputElAttr;

        data = Ext.apply(me.callParent([fieldData]), {
            inputType: me.inputType,
            checkboxCls: me._checkboxCls,
            disabled: me.readOnly || me.disabled,
            checked: !!me.checked,
            wrapInnerCls: me.wrapInnerCls,
            boxLabel: boxLabel,
            boxLabelCls: me.boxLabelCls,
            boxLabelAlign: boxLabelAlign,
            labelAlignedBefore: labelAlignedBefore,
            afterLabelCls: labelAlignedBefore ? me.afterLabelCls : '',
            noBoxLabelCls: !boxLabel ? me.noBoxLabelCls : '',

            // We need to have name attribute on the <input> element
            // even if it wasn't specified in component config;
            // some browsers (Chrome, Safari) will treat missing name
            // as empty, grouping all radio buttons with empty name
            // together. This causes funky but unwanted effects
            // with regards to keyboard navigation.
            inputName: me.name || me.id
        });

        inputElAttr = data.inputElAriaAttributes;

        if (inputElAttr) {
            // aria-readonly is not valid for Checkboxes and Radio buttons
            delete inputElAttr['aria-readonly'];
        }

        return data;
    },

    initEvents: function() {
        var me = this;

        me.callParent();

        me.inputEl.on(me.changeEventName, me.onChangeEvent, me, { delegated: false });

        // In all IE versions it is possible to focus ANY element by clicking
        // regardless of tabIndex attribute. In this case, clicking on boxLabelEl
        // will end up focusing its parent bodyEl before focusing and activating
        // the associated input element. Dark wizardry in Focus publisher fails
        // to propagate the second focusin event so we have to accommodate here
        // by not allowing bodyEl to focus.
        if (Ext.isIE) {
            me.bodyEl.on('mousedown', me.onBodyElMousedown, me);
        }

        // Conversely in Safari and Firefox on Mac clicking either box label or input
        // itself will result in input activation, value change, and immediate blur
        // to the document body. We place more faith in consistency over platform
        // specific quirks so have to force inputEl focus here and prevent blurring.
        // Oh Sanity Where Art Thou. :/
        else if (Ext.isMac && (Ext.isGecko || Ext.isSafari)) {
            me.boxLabelEl.on('mousedown', me.onBoxLabelOrInputMousedown, me);
            me.inputEl.on('mousedown', me.onBoxLabelOrInputMousedown, me);
        }
    },

    /**
     * Sets the {@link #boxLabel} for this checkbox.
     * @param {String} boxLabel The new label
     */
    setBoxLabel: function(boxLabel) {
        var me = this;

        me.boxLabel = boxLabel;

        if (me.rendered) {
            me.boxLabelEl.setHtml(boxLabel);
            me.boxLabelEl[boxLabel ? 'removeCls' : 'addCls'](me.noBoxLabelCls);
            me.updateLayout();
        }
    },

    /**
     * @private
     * Handle mousedown events on bodyEl. See explanations in initEvents().
     */
    onBodyElMousedown: function(e) {
        if (e.target !== this.inputEl.dom) {
            e.preventDefault();
        }
    },

    /**
     * @private
     * Handle mousedown events on boxLabelEl and inputEl.
     * See explanations in initEvents().
     */
    onBoxLabelOrInputMousedown: function(e) {
        this.inputEl.focus();
        e.preventDefault();
    },

    /**
     * @private
     * Handle the change event from the DOM.
     */
    onChangeEvent: function(e) {
        this.updateValueFromDom();
    },

    /**
     * @private
     */
    updateValueFromDom: function() {
        var me = this,
            inputEl = me.inputEl && me.inputEl.dom;

        if (inputEl) {
            me.checked = me.rawValue = me.value = inputEl.checked;

            me.checkChange();
        }
    },

    /**
     * @private
     */
    updateCheckedCls: function(checked) {
        var me = this;

        checked = checked != null ? checked : me.getValue();

        me[checked ? 'addCls' : 'removeCls'](me.checkedCls);
    },

    /**
     * Returns the checked state of the checkbox.
     * @return {Boolean} True if checked, else false
     */
    getRawValue: function() {
        var inputEl = this.inputEl && this.inputEl.dom;

        return inputEl ? inputEl.checked : this.checked;
    },

    /**
     * Returns the checked state of the checkbox.
     * @return {Boolean} True if checked, else false
     */
    getValue: function() {
        var inputEl = this.inputEl && this.inputEl.dom;

        return inputEl ? inputEl.checked : this.checked;
    },

    /**
     * Returns the submit value for the checkbox which can be used when submitting forms.
     * @return {String} If checked the {@link #inputValue} is returned; otherwise the
     * {@link #uncheckedValue} (or null if the latter is not configured).
     */
    getSubmitValue: function() {
        var unchecked = this.uncheckedValue,
            uncheckedVal = Ext.isDefined(unchecked) ? unchecked : null;

        return this.getValue() ? this.inputValue : uncheckedVal;
    },

    isChecked: function(rawValue, inputValue) {
        var ret = false;

        if (rawValue === true || rawValue === 'true') {
            ret = true;
        }
        else {
            if (inputValue !== 'on' && (inputValue || inputValue === 0) &&
                (Ext.isString(rawValue) || Ext.isNumber(rawValue))) {
                ret = rawValue == inputValue; // eslint-disable-line eqeqeq
            }
            else {
                ret = rawValue === '1' || rawValue === 1 || this.onRe.test(rawValue);
            }
        }

        return ret;
    },

    /**
     * Sets the checked state of the checkbox.
     *
     * @param {Boolean/String/Number} value The following values will check the checkbox:
     * - `true, 'true'.
     * - '1', 1, or 'on'`, when there is no {@link #inputValue}.
     * - Value that matches the {@link #inputValue}.
     * Any other value will un-check the checkbox.
     * @return {Boolean} the new checked state of the checkbox
     */
    setRawValue: function(value) {
        var me = this,
            inputEl = me.inputEl && me.inputEl.dom,
            checked = me.isChecked(value, me.inputValue);

        if (inputEl) {
            // Setting checked property will fire unwanted propertychange event in IE8.
            me.duringSetRawValue = true;
            inputEl.checked = checked;
            me.duringSetRawValue = false;

            me.updateCheckedCls(checked);
        }

        me.checked = me.rawValue = checked;

        if (!me.duringSetValue) {
            me.lastValue = checked;
        }

        return checked;
    },

    /**
     * Sets the checked state of the checkbox, and invokes change detection.
     * @param {Array/Boolean/String} checked The following values will check the checkbox:
     * `true, 'true', '1', or 'on'`, as well as a String that matches the {@link #inputValue}.
     * Any other value will  un-check the checkbox.
     *
     * You may also pass an array of string values. If an array of strings is passed, all checkboxes
     * in the group with a matched name will be checked.  The checkbox will be unchecked
     * if a corresponding value is not found in the array.
     * @return {Ext.form.field.Checkbox} this
     */
    setValue: function(checked) {
        var me = this,
            boxes, i, len, box;

        // If an array of strings is passed, find all checkboxes in the group with the same name
        // as this one and check all those whose inputValue is in the array, un-checking all the 
        // others. This is to facilitate setting values from Ext.form.Basic#setValues, 
        // but is not publicly documented as we don't want users depending on this 
        // behavior.
        if (Ext.isArray(checked)) {
            boxes = me.getManager().getByName(me.name, me.getFormId()).items;
            len = boxes.length;

            for (i = 0; i < len; ++i) {
                box = boxes[i];
                box.setValue(Ext.Array.contains(checked, box.inputValue));
            }
        }
        else {
            // The callParent() call ends up trigger setRawValue, we only want to modify
            // the lastValue when setRawValue being called independently.
            me.duringSetValue = true;
            me.callParent(arguments);
            delete me.duringSetValue;
        }

        return me;
    },

    /**
     * @method valueToRaw
     * @private
     */
    valueToRaw: Ext.identityFn,

    /**
     * @private
     * Called when the checkbox's checked state changes. Invokes the {@link #handler} callback
     * function if specified.
     */
    onChange: function(newVal, oldVal) {
        var me = this,
            handler = me.handler;

        me.updateCheckedCls(newVal);

        if (handler) {
            Ext.callback(handler, me.scope, [me, newVal], 0, me);
        }

        me.callParent(arguments);

        if (me.reference && me.publishState) {
            me.publishState('checked', newVal);
        }
    },

    /**
     * @private
     */
    resetOriginalValue: function(fromBoxInGroup) {
        var me = this,
            boxes, box, len, i;

        // If we're resetting the value of a field in a group, also reset the others.
        if (!fromBoxInGroup) {
            boxes = me.getManager().getByName(me.name, me.getFormId()).items;
            len = boxes.length;

            for (i = 0; i < len; ++i) {
                box = boxes[i];

                if (box !== me) {
                    boxes[i].resetOriginalValue(true);
                }
            }
        }

        me.callParent();
    },

    doDestroy: function() {
        this.getManager().removeAtKey(this.id);

        this.callParent();
    },

    getManager: function() {
        return Ext.form.CheckboxManager;
    },

    onEnable: function() {
        var me = this,
            inputEl = me.inputEl && me.inputEl.dom;

        me.callParent();

        if (inputEl) {
            // Can still be disabled if the field is readOnly
            inputEl.disabled = me.readOnly;
        }
    },

    setReadOnly: function(readOnly) {
        var me = this,
            inputEl = me.inputEl && me.inputEl.dom;

        if (inputEl) {
            // Set the button to disabled when readonly
            inputEl.disabled = !!readOnly || me.disabled;
        }

        me.callParent(arguments);
    },

    getFormId: function() {
        var me = this,
            form;

        if (!me.formId) {
            form = me.up('form');

            if (form) {
                me.formId = form.id;
            }
        }

        return me.formId;
    },

    getFocusClsEl: function() {
        return this.displayEl;
    }
});
