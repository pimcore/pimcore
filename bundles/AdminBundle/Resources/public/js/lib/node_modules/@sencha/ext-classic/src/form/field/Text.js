/**
 * A basic text field.  Can be used as a direct replacement for traditional text inputs,
 * or as the base class for more sophisticated input controls (like {@link Ext.form.field.TextArea}
 * and {@link Ext.form.field.ComboBox}). Has support for empty-field placeholder values
 * (see {@link #emptyText}).
 *
 * # Validation
 *
 * The Text field has a useful set of validations built in:
 *
 * - {@link #allowBlank} for making the field required
 * - {@link #minLength} for requiring a minimum value length
 * - {@link #maxLength} for setting a maximum value length (with {@link #enforceMaxLength} to add it
 *   as the `maxlength` attribute on the input element)
 * - {@link #regex} to specify a custom regular expression for validation
 *
 * In addition, custom validations may be added:
 *
 * - {@link #vtype} specifies a virtual type implementation from {@link Ext.form.field.VTypes}
 *   which can contain custom validation logic
 * - {@link #validator} allows a custom arbitrary function to be called during validation
 *
 * The details around how and when each of these validation options get used are described in the
 * documentation for {@link #getErrors}.
 *
 * By default, the field value is checked for validity immediately while the user is typing in the
 * field. This can be controlled with the {@link #validateOnChange}, {@link #checkChangeEvents}, and
 * {@link #checkChangeBuffer} configurations. Also see the details on Form Validation in the
 * {@link Ext.form.Panel} class documentation.
 *
 * # Masking and Character Stripping
 *
 * Text fields can be configured with custom regular expressions to be applied to entered values
 * before validation: see {@link #maskRe} and {@link #stripCharsRe} for details.
 *
 * # Example usage
 *
 *     @example
 *     Ext.create('Ext.form.Panel', {
 *         title: 'Contact Info',
 *         width: 300,
 *         bodyPadding: 10,
 *         renderTo: Ext.getBody(),
 *         items: [{
 *             xtype: 'textfield',
 *             name: 'name',
 *             fieldLabel: 'Name',
 *             allowBlank: false  // requires a non-empty value
 *         }, {
 *             xtype: 'textfield',
 *             name: 'email',
 *             fieldLabel: 'Email Address',
 *             vtype: 'email'  // requires value to be a valid email address format
 *         }]
 *     });
 *
 * # Custom Subclasses
 *
 * This class can be extended to provide additional functionality. The example below demonstrates
 * creating a custom search field that uses the HTML5 search input type.
 *
 *     @example
 *     // A simple subclass of Base that creates a HTML5 search field. Redirects to the
 *     // searchUrl when the Enter key is pressed.
 *     Ext.define('Ext.form.SearchField', {
 *         extend: 'Ext.form.field.Text',
 *         alias: 'widget.searchfield',
 *     
 *         inputType: 'search',
 *     
 *         // Config defining the search URL
 *         searchUrl: 'http://www.google.com/search?q={0}',
 *     
 *         // Add specialkey listener
 *         initComponent: function() {
 *             this.callParent();
 *             this.on('specialkey', this.checkEnterKey, this);
 *         },
 *     
 *         // Handle enter key presses, execute the search if the field has a value
 *         checkEnterKey: function(field, e) {
 *             var value = this.getValue();
 *             if (e.getKey() === e.ENTER && !Ext.isEmpty(value)) {
 *                 location.href = Ext.String.format(this.searchUrl, value);
 *             }
 *         }
 *     });
 *     
 *     Ext.create('Ext.form.Panel', {
 *         title: 'Base Example',
 *         bodyPadding: 5,
 *         width: 250,
 *     
 *         // Fields will be arranged vertically, stretched to full width
 *         layout: 'anchor',
 *         defaults: {
 *             anchor: '100%'
 *         },
 *         items: [{
 *             xtype: 'searchfield',
 *             fieldLabel: 'Search',
 *             name: 'query'
 *         }],
 *         renderTo: Ext.getBody()
 *     });
 */
Ext.define('Ext.form.field.Text', {
    extend: 'Ext.form.field.Base',
    alias: 'widget.textfield',
    alternateClassName: ['Ext.form.TextField', 'Ext.form.Text'],

    requires: [
        'Ext.layout.component.field.Text',
        'Ext.form.field.VTypes',
        'Ext.form.trigger.Trigger',
        'Ext.util.TextMetrics'
    ],

    /**
     * @cfg componentLayout
     * @inheritdoc
     */
    componentLayout: 'textfield',

    config: {
        /**
         * @cfg {Boolean} hideTrigger
         * `true` to hide all triggers
         */
        hideTrigger: false,

        /**
         * @cfg {Boolean} [autoHideInputMask=true]
         * Specify as `false` to always show the `inputMask`.
         * @since 6.5.0
         */
        autoHideInputMask: null,

        /**
         * @cfg {String/Ext.field.InputMask} inputMask
         *
         * **Important:** To use this config you must require `Ext.field.InputMask` or
         * use a complete framework build. The logic to implement an `inputMask` is not
         * automatically included in a build.
         * @since 6.5.0
         */
        inputMask: null,

        // @cmd-auto-dependency {aliasPrefix: "trigger.", isKeyedObject: true}
        /**
         * @cfg {Object} triggers
         * {@link Ext.form.trigger.Trigger Triggers} to use in this field.  The keys in
         * this object are unique identifiers for the triggers. The values in this object
         * are {@link Ext.form.trigger.Trigger Trigger} configuration objects.
         *
         *     Ext.create('Ext.form.field.Text', {
         *         renderTo: document.body,
         *         fieldLabel: 'My Custom Field',
         *         triggers: {
         *             foo: {
         *                 cls: 'my-foo-trigger',
         *                 handler: function() {
         *                     console.log('foo trigger clicked');
         *                 }
         *             },
         *             bar: {
         *                 cls: 'my-bar-trigger',
         *                 handler: function() {
         *                     console.log('bar trigger clicked');
         *                 }
         *             }
         *         }
         *     });
         * 
         * The weight value may be a negative value in order to position custom triggers 
         * ahead of default triggers like that of ComboBox.
         * 
         *     Ext.create('Ext.form.field.ComboBox', {
         *         renderTo: Ext.getBody(),
         *         fieldLabel: 'My Custom Field',
         *         triggers: {
         *             foo: {
         *                 cls: 'my-foo-trigger',
         *                 weight: -2, // negative to place before default triggers
         *                 handler: function() {
         *                     console.log('foo trigger clicked');
         *                 }
         *             },
         *             bar: {
         *                 cls: 'my-bar-trigger',
         *                 weight: -1, 
         *                 handler: function() {
         *                     console.log('bar trigger clicked');
         *                 }
         *             }
         *         }
         *     });
         */
        triggers: undefined
    },

    renderConfig: {
        /**
         * @cfg {Boolean} editable
         * false to prevent the user from typing text directly into the field; the field can
         * only have its value set programmatically or via an action invoked by a trigger.
         */
        editable: true
    },

    /**
     * @cfg {String} vtypeText
     * A custom error message to display in place of the default message provided for the
     * **`{@link #vtype}`** currently set for this field.
     * **Note**: only applies if **`{@link #vtype}`** is set, else ignored.
     */

    /**
     * @cfg {RegExp} stripCharsRe
     * A JavaScript RegExp object used to strip unwanted content from the value
     * during input. If `stripCharsRe` is specified,
     * every *character sequence* matching `stripCharsRe` will be removed.
     */

    /**
     * @cfg {Number} size
     * An initial value for the 'size' attribute on the text input element. This is only
     * used if the field has no configured {@link #width} and is not given a width by its
     * container's layout. Defaults to 20.
     * @deprecated 6.5.0 Please use {@link #width} instead.
     */

    /**
     * @cfg {Boolean} [grow=false]
     * true if this field should automatically grow and shrink to its content
     */

    /**
     * @cfg {Number} growMin
     * The minimum width to allow when `{@link #grow} = true`
     */
    growMin: 30,

    /**
     * @cfg {Number} growMax
     * The maximum width to allow when `{@link #grow} = true`
     */
    growMax: 800,

    /**
     * @cfg {String} vtype
     * A validation type name as defined in {@link Ext.form.field.VTypes}
     */

    /**
     * @cfg {RegExp} maskRe An input mask regular expression that will be used to filter keystrokes
     * (character being typed) that do not match.
     * Note: It does not filter characters already in the input.
     */

    /**
     * @cfg {Boolean} [disableKeyFilter=false]
     * Specify true to disable input keystroke filtering. This will ignore the
     * maskRe field.
     */

    /**
     * @cfg {Boolean} allowBlank
     * Specify false to validate that the value's length must be > 0. If `true`, then a blank value
     * is **always** taken to be valid regardless of any {@link #vtype} validation that may be
     * applied.
     *
     * If {@link #vtype} validation must still be applied to blank values, configure
     * {@link #validateBlank} as `true`;
     */
    allowBlank: true,

    /**
     * @cfg {Boolean} validateBlank
     * Specify as `true` to modify the behaviour of {@link #allowBlank} so that blank values
     * are not passed as valid, but are subject to any configure {@link #vtype} validation.
     */
    validateBlank: false,

    /**
     * @cfg {Boolean} allowOnlyWhitespace
     * Specify false to automatically trim the value before validating
     * the whether the value is blank. Setting this to false automatically
     * sets {@link #allowBlank} to false.
     */
    allowOnlyWhitespace: true,

    /**
     * @cfg {Number} minLength
     * Minimum input field length required
     */
    minLength: 0,

    /**
     * @cfg {Number} maxLength
     * Maximum input field length allowed by validation. This behavior is intended to
     * provide instant feedback to the user by improving usability to allow pasting and editing
     * or overtyping and back tracking. To restrict the maximum number of characters that can be
     * entered into the field use the
     * **{@link Ext.form.field.Text#enforceMaxLength enforceMaxLength}** option.
     *
     * Defaults to Number.MAX_VALUE.
     */
    maxLength: Number.MAX_VALUE,

    /**
     * @cfg {Boolean} enforceMaxLength
     * True to set the maxLength property on the underlying input field. Defaults to false
     */

    /**
     * @cfg {String} minLengthText
     * Error text to display if the **{@link #minLength minimum length}** validation fails.
     * @locale
     */
    minLengthText: 'The minimum length for this field is {0}',

    /**
     * @cfg {String} maxLengthText
     * Error text to display if the **{@link #maxLength maximum length}** validation fails.
     * @locale
     */
    maxLengthText: 'The maximum length for this field is {0}',

    /**
     * @cfg {Boolean} [selectOnFocus=false]
     * `true` to automatically select any existing field text when the field receives input
     * focus. Only applies when {@link #editable editable} = true
     */

    /**
     * @cfg {String} blankText
     * The error text to display if the **{@link #allowBlank}** validation fails.
     * @locale
     */
    blankText: 'This field is required',

    /**
     * @cfg {Function} validator
     * A custom validation function to be called during field validation ({@link #getErrors}).
     * If specified, this function will be called first, allowing the developer to override
     * the default validation process.
     * 
     *     Ext.create('Ext.form.field.Text', {
     *         renderTo: document.body,
     *         name: 'phone',
     *         fieldLabel: 'Phone Number',
     *         validator: function(val) {
     *             // remove non-numeric characters
     *             var tn = val.replace(/[^0-9]/g,''),
     *                 errMsg = "Must be a 10 digit telephone number";
     *             // if the numeric value is not 10 digits return an error message
     *             return (tn.length === 10) ? true : errMsg;
     *         }
     *     });
     *
     * @param {Object} value The current field value
     * @return {Boolean/String} response
     *
     *  - True if the value is valid
     *  - An error message if the value is invalid
     */

    /**
     * @cfg {RegExp} regex
     * A JavaScript RegExp object to be tested against the field value during validation.
     * If the test fails, the field will be marked invalid using
     * either **{@link #regexText}** or **{@link #invalidText}**.
     */

    /**
     * @cfg {String} regexText
     * The error text to display if **{@link #regex}** is used and the test fails during validation
     */
    regexText: '',

    /**
     * @cfg {String} emptyText
     * The default text to place into an empty field.
     *
     * Note that normally this value will be submitted to the server if this field is enabled;
     * to prevent this you can set the
     * {@link Ext.form.action.Action#submitEmptyText submitEmptyText} option of
     * {@link Ext.form.Basic#submit} to false.
     *
     * Also note that if you use {@link #inputType inputType}:'file', {@link #emptyText}
     * is not supported and should be avoided.
     *
     * Note that for browsers that support it, setting this property will use the HTML 5 placeholder
     * attribute, and for older browsers that don't support the HTML 5 placeholder attribute
     * the value will be placed directly into the input element itself as the raw value.
     * This means that older browsers will obfuscate the {@link #emptyText} value for
     * password input fields.
     */
    emptyText: '',

    /**
     * @cfg {String} emptyCls
     * The CSS class to apply to an empty field to style the **{@link #emptyText}**.
     * This class is automatically added and removed as needed depending on the current field value.
     */
    emptyCls: Ext.baseCSSPrefix + 'form-empty-field',

    /**
     * @private
     * The default CSS class for the placeholder label cover need when the browser
     * does not support a Placeholder.
     */
    placeholderCoverCls: Ext.baseCSSPrefix + 'placeholder-label',

    /**
     * @cfg {String} requiredCls
     * The CSS class to apply to a required field, i.e. a field where **{@link #allowBlank}**
     * is false.
     */
    requiredCls: Ext.baseCSSPrefix + 'form-required-field',

    /**
     * @cfg {Boolean} [enableKeyEvents=false]
     * true to enable the proxying of key events for the HTML input field
     */

    /**
     * @property ariaRole
     * @inheritdoc
     */
    ariaRole: 'textbox',

    /**
     * @cfg {Boolean} repeatTriggerClick
     * `true` to attach a {@link Ext.util.ClickRepeater click repeater} to the trigger(s).
     * Click repeating behavior can also be configured on the individual {@link #triggers
     * trigger instances} using the trigger's {@link Ext.form.trigger.Trigger#repeatClick
     * repeatClick} config.
     */
    repeatTriggerClick: false,

    /**
     * @cfg {Boolean} readOnly
     * `true` to prevent the user from changing the field, and hide all triggers.
     */

    /**
     * @cfg stateEvents
     * @inheritdoc Ext.state.Stateful#cfg-stateEvents
     * @localdoc By default the following stateEvents are added:
     * 
     *  - {@link #event-resize} - _(added by Ext.Component)_
     *  - {@link #event-change}
     */

    /**
     * @cfg {String}
     * The CSS class that is added to the div wrapping the input element and trigger button(s).
     */
    triggerWrapCls: Ext.baseCSSPrefix + 'form-trigger-wrap',

    triggerWrapFocusCls: Ext.baseCSSPrefix + 'form-trigger-wrap-focus',
    triggerWrapInvalidCls: Ext.baseCSSPrefix + 'form-trigger-wrap-invalid',

    /**
     * @cfg fieldBodyCls
     * @inheritdoc
     */
    fieldBodyCls: Ext.baseCSSPrefix + 'form-text-field-body',

    /**
     * @cfg {String}
     * The CSS class that is added to the element wrapping the input element
     */
    inputWrapCls: Ext.baseCSSPrefix + 'form-text-wrap',

    inputWrapFocusCls: Ext.baseCSSPrefix + 'form-text-wrap-focus',
    inputWrapInvalidCls: Ext.baseCSSPrefix + 'form-text-wrap-invalid',
    growCls: Ext.baseCSSPrefix + 'form-text-grow',
    heightedCls: Ext.baseCSSPrefix + 'form-text-heighted',

    /* 
     * @private
     * This property will hold all elements that require emtpyCls to be applied to them
     */
    emptyClsElements: null,

    needArrowKeys: true,

    /**
     * @cfg childEls
     * @inheritdoc
     */
    childEls: [
        /**
         * @property {Ext.dom.Element} triggerWrap
         * A reference to the element which encapsulates the input field and all
         * trigger button(s). Only set after the field has been rendered.
         */
        'triggerWrap',

        /**
         * @property {Ext.dom.Element} inputWrap
         * A reference to the element that wraps the input element. Only set after the
         * field has been rendered.
         */
        'inputWrap',

        'placeholderLabel'
    ],

    /* eslint-disable indent, max-len */
    preSubTpl: [
        '<div id="{cmpId}-triggerWrap" data-ref="triggerWrap"',
                '<tpl if="ariaEl == \'triggerWrap\'">',
                    '<tpl foreach="ariaElAttributes"> {$}="{.}"</tpl>',
                '<tpl else>',
                    ' role="presentation"',
                '</tpl>',
                ' class="{triggerWrapCls} {triggerWrapCls}-{ui}">',
            '<div id={cmpId}-inputWrap data-ref="inputWrap"',
                ' role="presentation" class="{inputWrapCls} {inputWrapCls}-{ui}">'
    ],

    postSubTpl: [
            '<tpl if="!Ext.supports.Placeholder">',
            '<label id="{cmpId}-placeholderLabel" data-ref="placeholderLabel" for="{id}" class="{placeholderCoverCls} {placeholderCoverCls}-{ui}">{placeholder}</label>',
            '</tpl>',
            '</div>', // end inputWrap
            '<tpl for="triggers">{[values.renderTrigger(parent)]}</tpl>',
        '</div>' // end triggerWrap
    ],
    /* eslint-enable indent, max-len */

    /**
     * @event autosize
     * Fires when the **{@link #autoSize}** function is triggered and the field is resized
     * according to the {@link #grow}/{@link #growMin}/{@link #growMax} configs as a result.
     * This event provides a hook for the developer to apply additional logic at runtime
     * to resize the field if needed.
     * @param {Ext.form.field.Text} this This text field
     * @param {Number} width The new field width
     */

    /**
     * @event keydown
     * Keydown input field event. This event only fires if **{@link #enableKeyEvents}**
     * is set to true.
     * @param {Ext.form.field.Text} this This text field
     * @param {Ext.event.Event} e
     */

    /**
     * @event keyup
     * Keyup input field event. This event only fires if **{@link #enableKeyEvents}**
     * is set to true.
     * @param {Ext.form.field.Text} this This text field
     * @param {Ext.event.Event} e
     */

    /**
     * @event keypress
     * Keypress input field event. This event only fires if **{@link #enableKeyEvents}**
     * is set to true.
     * @param {Ext.form.field.Text} this This text field
     * @param {Ext.event.Event} e
     */

    /**
     * @event paste
     * Fires when this field is pasted. This event only fires if **{@link #enableKeyEvents}**
     * is set to true.
     * @param {Ext.form.field.Text} this This text field
     * @param {Ext.event.Event} e
     */

    initComponent: function() {
        var me = this,
            emptyCls = me.emptyCls;

        if (me.allowOnlyWhitespace === false) {
            me.allowBlank = false;
        }

        if (me.grow) {
            me.liquidLayout = false;
        }

        //<debug>
        if (me.size) {
            Ext.log.warn('Ext.form.field.Text "size" config was deprecated in Ext 5.0. ' +
                         'Please specify a "width" or use a layout instead.');
        }
        //</debug>

        // In Ext JS 4.x the layout system used the following magic formula for converting
        // the "size" config into a pixel value.
        if (me.size) {
            me.defaultBodyWidth = me.size * 6.5 + 20;
        }

        if (!me.onTrigger1Click) {
            // for compat with 4.x TriggerField
            me.onTrigger1Click = me.onTriggerClick;
        }

        me.callParent();

        if (me.readOnly) {
            me.setReadOnly(me.readOnly);
        }

        me.fieldFocusCls = me.baseCls + '-focus';
        me.emptyUICls = emptyCls + ' ' + emptyCls + '-' + me.ui;
        me.addStateEvents('change');
    },

    initEvents: function() {
        var me = this,
            el = me.inputEl;

        me.callParent();

        if (me.maskRe || (me.vtype && me.disableKeyFilter !== true &&
            (me.maskRe = Ext.form.field.VTypes[me.vtype + 'Mask']))) {
            me.mon(el, 'keypress', me.filterKeys, me);
        }

        if (me.enableKeyEvents) {
            me.mon(el, {
                scope: me,
                keyup: me.onKeyUp,
                keydown: me.onKeyDown,
                keypress: me.onKeyPress,
                paste: me.onPaste
            });
        }
    },

    /**
     * @private
     * Treat undefined and null values as equal to an empty string value.
     */
    isEqual: function(value1, value2) {
        return this.isEqualAsString(value1, value2);
    },

    /**
     * @private
     * If grow=true, invoke the autoSize method when the field's value is changed.
     */
    onChange: function(newVal, oldVal) {
        var me = this,
            inputMask = me.getInputMask();

        me.callParent([newVal, oldVal]);
        me.autoSize();

        if (inputMask) {
            inputMask.onChange(me, newVal, oldVal);
        }
    },

    getSubTplData: function(fieldData) {
        var me = this,
            value = me.getRawValue(),
            isEmpty = me.emptyText && value.length < 1,
            maxLength = me.maxLength,
            placeholder, data, inputElAttr;

        // We can't just dump the value here, since MAX_VALUE ends up
        // being something like 1.xxxxe+300, which gets interpreted as 1
        // in the markup
        if (me.enforceMaxLength) {
            if (maxLength === Number.MAX_VALUE) {
                maxLength = undefined;
            }
        }
        else {
            maxLength = undefined;
        }

        if (me.emptyText) {
            placeholder = Ext.String.htmlEncode(me.emptyText);
        }

        data = Ext.apply(me.callParent([fieldData]), {
            triggerWrapCls: me.triggerWrapCls,
            inputWrapCls: me.inputWrapCls,
            placeholderCoverCls: me.placeholderCoverCls,
            triggers: me.orderedTriggers,
            maxLength: maxLength,
            readOnly: !me.editable || me.readOnly,
            placeholder: placeholder,
            value: value,
            fieldCls: me.fieldCls + (me.allowBlank ? '' : ' ' + me.requiredCls) +
                      (isEmpty ? ' ' + me.emptyUICls : '')
        });

        inputElAttr = data.inputElAriaAttributes;

        if (inputElAttr) {
            inputElAttr['aria-required'] = !me.allowBlank;
        }

        return data;
    },

    beforeRender: function() {
        var me = this,
            heighted;

        heighted = me.height != null || me.minHeight != null ||
            !!(me.ownerLayout && me.ownerLayout.getItemSizePolicy(me, me.fakeSizeModel).setsHeight);

        if (heighted) {
            me.protoEl.addCls(me.heightedCls);
        }

        me.callParent();
    },

    onRender: function() {
        var me = this,
            triggers = me.getTriggers(),
            elements = [],
            id;

        if (Ext.supports.FixedTableWidthBug) {
            // Workaround for https://bugs.webkit.org/show_bug.cgi?id=130239 and
            // https://code.google.com/p/chromium/issues/detail?id=377190
            // See styleHooks for more details
            me.el._needsTableWidthFix = true;
        }

        me.callParent();

        me.emptyClsElements = [me.inputEl];

        if (triggers) {
            me.invokeTriggers('onFieldRender');

            /**
             * @property {Ext.CompositeElement} triggerEl
             * @deprecated 5.0
             * A composite of all the trigger button elements. Only set after the field has
             * been rendered.
             */
            for (id in triggers) {
                elements.push(triggers[id].el);
            }

            // for 4.x compat, also set triggerCell
            me.triggerEl = me.triggerCell = new Ext.CompositeElement(elements, true);
        }

        /**
         * @property {Ext.dom.Element} inputCell
         * A reference to the element that wraps the input element. Only set after the
         * field has been rendered.
         * @deprecated 5.0 use {@link #inputWrap} instead
         */
        me.inputCell = me.inputWrap;

        me.refreshEmptyText();
    },

    onResize: function(width, height, oldWidth, oldHeight) {
        var me = this;

        if (me.rendered && me.grow) {
            me.autoSize();
        }

        me.callParent([width, height, oldWidth, oldHeight]);
    },

    afterRender: function() {
        this.callParent();
        this.invokeTriggers('afterFieldRender');
    },

    onBoxReady: function(width, height) {
        var me = this;

        me.callParent([width, height]);

        if (!me.liquidLayout) {
            this.autoSize();
        }
    },

    applyInputMask: function(value, instance) {
        var field = Ext.field,
            // eslint-disable-next-line dot-notation
            InputMask = field && field['InputMask']; // prevent Cmd detection

        //<debug>
        if (value) {
            if (!InputMask) {
                Ext.raise('Missing Ext.field.InputMask (required to use inputMask)');
            }
            // if (this.getAutoComplete()) {
            //     Ext.log.warn('Combining inputMask and autoComplete is not supported');
            // }
        }
        //</debug>

        return value ? InputMask.from(value, instance) : null;
    },

    applyTriggers: function(triggers) {
        var me = this,
            hideAllTriggers = me.getHideTrigger(),
            readOnly = me.readOnly,
            orderedTriggers = me.orderedTriggers = [],
            repeatTriggerClick = me.repeatTriggerClick,
            id, triggerCfg, trigger, triggerCls, i;

        //<debug>
        if (me.rendered) {
            Ext.raise("Cannot set triggers after field has already been rendered.");
        }

        // don't warn if we have both triggerCls and triggers, because picker field
        // uses triggerCls to style the "picker" trigger.
        if ((me.triggerCls && !triggers) || me.trigger1Cls) {
            Ext.log.warn("Ext.form.field.Text: 'triggerCls' and 'trigger<n>Cls'" +
                " are deprecated.  Use 'triggers' instead.");
        }
        //</debug>

        if (!triggers) {
            // For compatibility with 4.x, transform the trigger<n>Cls configs into the
            // new "triggers" config.
            triggers = {};

            if (me.triggerCls && !me.trigger1Cls) {
                me.trigger1Cls = me.triggerCls;
            }

            // Assignment in conditional test is deliberate here
            for (i = 1; (triggerCls = me['trigger' + i + 'Cls']); i++) {
                triggers['trigger' + i] = {
                    cls: triggerCls,
                    extraCls: Ext.baseCSSPrefix + 'trigger-index-' + i,
                    handler: 'onTrigger' + i + 'Click',
                    compat4Mode: true,
                    scope: me
                };
            }
        }

        for (id in triggers) {
            if (triggers.hasOwnProperty(id)) {
                triggerCfg = triggers[id];
                triggerCfg.field = me;
                triggerCfg.id = id;

                /*
                 * An explicitly-configured 'triggerConfig.hideOnReadOnly : false' allows
                 * {@link #hideTrigger} analysis
                 */
                if ((readOnly && triggerCfg.hideOnReadOnly !== false) ||
                    (hideAllTriggers && triggerCfg.hidden !== false)) {
                    triggerCfg.hidden = true;
                }

                if (repeatTriggerClick && (triggerCfg.repeatClick !== false)) {
                    triggerCfg.repeatClick = true;
                }

                trigger = triggers[id] = Ext.form.trigger.Trigger.create(triggerCfg);
                orderedTriggers.push(trigger);
            }
        }

        Ext.sortByWeight(orderedTriggers);

        return triggers;
    },

    /**
     * Invokes a method on all triggers.
     * @param {String} methodName
     * @param args
     * @private
     */
    invokeTriggers: function(methodName, args) {
        var me = this,
            triggers = me.getTriggers(),
            id, trigger;

        if (triggers) {
            for (id in triggers) {
                if (triggers.hasOwnProperty(id)) {
                    trigger = triggers[id];

                    // IE8 needs "|| []" if args is undefined
                    trigger[methodName].apply(trigger, args || []);
                }
            }
        }
    },

    /**
     * Returns the trigger with the given id
     * @param {String} id
     * @return {Ext.form.trigger.Trigger}
     */
    getTrigger: function(id) {
        return this.getTriggers()[id];
    },

    updateMinHeight: function(minHeight, oldMinHeight) {
        this.callParent([minHeight, oldMinHeight]);
        this.toggleCls(Ext.baseCSSPrefix + 'has-min-height', !!minHeight);
    },

    updateInputMask: function(inputMask, previous) {
        if (previous) {
            previous.release();
        }

        if (inputMask) {
            this.enableKeyEvents = true;
        }
    },

    updateHideTrigger: function(hideTrigger) {
        this.invokeTriggers(hideTrigger ? 'hide' : 'show');
    },

    updateEditable: function(editable, oldEditable) {
        this.setReadOnlyAttr(!editable || this.readOnly);
    },

    /**
     * Sets the read-only state of this field.
     * @param {Boolean} readOnly True to prevent the user changing the field and explicitly
     * hide the trigger(s). Setting this to true will supersede settings editable and
     * hideTrigger. Setting this to false will defer back to {@link #editable editable} and
     * {@link #hideTrigger hideTrigger}.
     */
    setReadOnly: function(readOnly) {
        var me = this,
            triggers = me.getTriggers(),
            hideTriggers = me.getHideTrigger(),
            trigger, id;

        readOnly = !!readOnly;

        me.callParent([readOnly]);

        if (me.rendered) {
            me.setReadOnlyAttr(readOnly || !me.editable);
        }

        if (triggers) {
            for (id in triggers) {
                trigger = triggers[id];

                /*
                 * Controlled trigger visibility state is only managed fully when 'hideOnReadOnly'
                 * is falsy.
                 * Truth table:
                 *   - If the trigger is configured/defaulted as 'hideOnReadOnly : true',
                 *     it's readOnly-visibility is determined solely by readOnly state of the Field.
                 *   - If 'hideOnReadOnly : false/undefined', the
                 *     Fields.{link #hideTrigger hideTrigger} is honored.
                 */
                if (trigger.hideOnReadOnly === true || (trigger.hideOnReadOnly !== false &&
                    !hideTriggers)) {
                    trigger.setVisible(!readOnly);
                }
            }
        }
    },

    /**
     * @private
     * Sets the readonly attribute of the input element
     */
    setReadOnlyAttr: function(readOnly) {
        var me = this,
            readOnlyName = 'readonly',
            inputEl = me.inputEl.dom;

        if (readOnly) {
            inputEl.setAttribute(readOnlyName, readOnlyName);
        }
        else {
            inputEl.removeAttribute(readOnlyName);
        }

        if (!me.ariaStaticRoles[me.ariaRole]) {
            me.inputEl.dom.setAttribute('aria-readonly', !!readOnly);
        }
    },

    /**
     * Performs any necessary manipulation of a raw String value to prepare it for conversion and/or
     * {@link #validate validation}. For text fields this applies the configured
     * {@link #stripCharsRe} to the raw value.
     * @param {String} value The unprocessed string value
     * @return {String} The processed string value
     */
    processRawValue: function(value) {
        var me = this,
            stripRe = me.stripCharsRe,
            mod, newValue;

        if (stripRe) {
            // This will force all instances that match stripRe to be removed
            // in case the user tries to add it with copy and paste EXTJS-18621
            if (!stripRe.global) {
                mod = 'g';
                mod += (stripRe.ignoreCase) ? 'i' : '';
                mod += (stripRe.multiline) ? 'm' : '';
                stripRe = new RegExp(stripRe.source, mod);
            }

            newValue = value.replace(stripRe, '');

            if (newValue !== value) {
                me.setRawValue(newValue);

                // Some components change lastValue as you type, so we need to verify
                // if this is the case here and replace the value of lastValue
                if (me.lastValue === value) {
                    me.lastValue = newValue;
                }

                value = newValue;
            }
        }

        return value;
    },

    onDisable: function() {
        this.callParent();

        if (Ext.isIE) {
            this.inputEl.dom.unselectable = 'on';
        }
    },

    onEnable: function() {
        this.callParent();

        if (Ext.isIE) {
            this.inputEl.dom.unselectable = '';
        }
    },

    onKeyDown: function(event) {
        var me = this,
            inputMask = me.getInputMask();

        if (inputMask) {
            inputMask.onKeyDown(me, me.getValue(), event);
        }

        this.fireEvent('keydown', this, event);
    },

    onKeyUp: function(e) {
        this.fireEvent('keyup', this, e);
    },

    onKeyPress: function(event) {
        var me = this,
            inputMask = me.getInputMask();

        if (inputMask) {
            inputMask.onKeyPress(me, me.getValue(), event);
        }

        me.fireEvent('keypress', me, event);
    },

    onPaste: function(e) {
        var me = this,
            inputMask = me.getInputMask();

        if (inputMask) {
            inputMask.onPaste(me, me.getValue(), e);
        }

        me.fireEvent('paste', me, e);
    },

    /**
     * Returns the value of this field's {@link #cfg-emptyText}
     * @return {String} The value of this field's emptyText
     */
    getEmptyText: function() {
        return this.emptyText;
    },

    /**
     * Sets the default text to place into an empty field
     * @param {String} value The {@link #cfg-emptyText} value for this field
     * @return {Ext.form.field.Text} this
     */
    setEmptyText: function(value) {
        var me = this,
            inputEl = me.inputEl;

        value = value || '';

        me.emptyText = value;

        if (me.rendered) {
            if (Ext.supports.Placeholder && !me.simulatePlaceholder) {
                if (value) {
                    inputEl.dom.setAttribute('placeholder', value);
                }
                else {
                    inputEl.dom.removeAttribute('placeholder');
                }
            }
            else {
                me.placeholderLabel.setHtml(value);
            }

            me.refreshEmptyText();
        }

        return this;
    },

    afterFirstLayout: function() {
        var el;

        this.callParent();

        if (Ext.isIE && this.disabled) {
            el = this.inputEl;

            if (el) {
                el.dom.unselectable = 'on';
            }
        }
    },

    /**
     * @private
     */
    toggleInvalidCls: function(hasError) {
        var method = hasError ? 'addCls' : 'removeCls';

        this.callParent([hasError]);

        this.triggerWrap[method](this.triggerWrapInvalidCls);
        this.inputWrap[method](this.inputWrapInvalidCls);
    },

    onFieldMutation: function(e) {
        this.refreshEmptyText();
        this.callParent([e]);
    },

    refreshEmptyText: function() {
        var me = this,
            inputEl = me.inputEl,
            emptyClsElements = me.emptyClsElements,
            value, isEmpty, i;

        if (inputEl) {
            value = me.getValue();
            isEmpty = !(inputEl.dom.value || (Ext.isArray(value) && value.length));

            if (me.placeholderLabel) {
                me.placeholderLabel.setDisplayed(isEmpty);
            }

            for (i = 0; i < emptyClsElements.length; i++) {
                emptyClsElements[i].toggleCls(me.emptyUICls, isEmpty);
            }
        }

    },

    setValue: function(value) {
        value = this.callParent([value]);

        this.refreshEmptyText();

        return value;
    },

    onFocus: function(e) {
        var me = this,
            inputEl = me.inputEl.dom,
            inputMask = me.getInputMask(),
            value, len;

        me.callParent([e]);

        if (me.emptyText) {
            me.autoSize();
        }

        if (inputMask) {
            inputMask.onFocus(me, inputEl.value);
        }

        me.addCls(me.fieldFocusCls);
        me.triggerWrap.addCls(me.triggerWrapFocusCls);
        me.inputWrap.addCls(me.inputWrapFocusCls);
        me.invokeTriggers('onFieldFocus', [e]);

        // This handler may be called when the focus has already shifted to another element;
        // calling inputEl.select() will forcibly focus again it which in turn might set up
        // a nasty circular race condition if focusEl !== inputEl.
        if (me.selectOnFocus && document.activeElement === inputEl) {
            value = inputEl.value;
            len = value.length;

            // We need a minimal delay here due to a bug that will deselect
            // the text on mouseup + focus.
            // https://code.google.com/p/chromium/issues/detail?id=4505
            // Note: This was fixed years ago but still persists in Safari, IE and Edge.
            Ext.asap(me.selectText, me, [0, len]);
        }
    },

    /**
     * @private
     */
    onBlur: function(e) {
        var me = this,
            inputEl = me.inputEl.dom,
            inputMask = me.getInputMask(),
            value;

        me.callParent([e]);

        value = inputEl && inputEl.value;

        me.removeCls(me.fieldFocusCls);
        me.triggerWrap.removeCls(me.triggerWrapFocusCls);
        me.inputWrap.removeCls(me.inputWrapFocusCls);
        me.invokeTriggers('onFieldBlur', [e]);

        if (inputMask && me.getAutoHideInputMask() !== false) {
            inputMask.onBlur(me, value);
        }
    },

    /**
     * @private
     */
    filterKeys: function(e) {
        var charCode;

        /*
         * Current only FF will fire keypress events for special keys.
         * 
         * On European keyboards, the right alt key, Alt Gr, is used to type certain special
         * characters. JS detects a keypress of this as ctrlKey & altKey. As such, we check
         * that alt isn't pressed so we can still process these special characters.
         */
        if ((e.ctrlKey && !e.altKey) || e.isSpecialKey()) {
            return;
        }

        charCode = String.fromCharCode(e.getCharCode());

        if (!this.maskRe.test(charCode)) {
            e.stopEvent();
        }
    },

    getState: function() {
        return this.addPropertyToState(this.callParent(), 'value');
    },

    applyState: function(state) {
        this.callParent([state]);

        if (state.hasOwnProperty('value')) {
            this.setValue(state.value);
        }
    },

    /**
     * Validates a value according to the field's validation rules and returns an array of errors
     * for any failing validations. Validation rules are processed in the following order:
     *
     * 1. **Field specific validator**
     *
     *     A validator offers a way to customize and reuse a validation specification.
     *     If a field is configured with a `{@link #validator}`
     *     function, it will be passed the current field value.  The `{@link #validator}`
     *     function is expected to return either:
     *
     *     - Boolean `true`  if the value is valid (validation continues).
     *     - a String to represent the invalid message if invalid (validation halts).
     *
     * 2. **Basic Validation**
     *
     *     If the `{@link #validator}` has not halted validation,
     *     basic validation proceeds as follows:
     *
     *     - `{@link #allowBlank}` : (Invalid message = `{@link #blankText}`)
     *
     *         Depending on the configuration of `{@link #allowBlank}`, a
     *         blank field will cause validation to halt at this step and return
     *         Boolean true or false accordingly.
     *
     *     - `{@link #minLength}` : (Invalid message = `{@link #minLengthText}`)
     *
     *         If the passed value does not satisfy the `{@link #minLength}`
     *         specified, validation halts.
     *
     *     -  `{@link #maxLength}` : (Invalid message = `{@link #maxLengthText}`)
     *
     *         If the passed value does not satisfy the `{@link #maxLength}`
     *         specified, validation halts.
     *
     * 3. **Preconfigured Validation Types (VTypes)**
     *
     *     If none of the prior validation steps halts validation, a field
     *     configured with a `{@link #vtype}` will utilize the
     *     corresponding {@link Ext.form.field.VTypes VTypes} validation function.
     *     If invalid, either the field's `{@link #vtypeText}` or
     *     the VTypes vtype Text property will be used for the invalid message.
     *     Keystrokes on the field will be filtered according to the VTypes
     *     vtype Mask property.
     *
     * 4. **Field specific regex test**
     *
     *     If none of the prior validation steps halts validation, a field's
     *     configured `{@link #regex}` test will be processed.
     *     The invalid message for this test is configured with `{@link #regexText}`
     *
     * @param {Object} value The value to validate. The processed raw value will be used
     * if nothing is passed.
     * @return {String[]} Array of any validation errors
     */
    getErrors: function(value) {
        value = arguments.length
            ? (value == null ? '' : value)
            : this.processRawValue(this.getRawValue());

        // eslint-disable-next-line vars-on-top
        var me = this,
            errors = me.callParent([value]),
            validator = me.validator,
            vtype = me.vtype,
            vtypes = Ext.form.field.VTypes,
            regex = me.regex,
            format = Ext.String.format,
            msg, trimmed, isBlank;

        if (Ext.isFunction(validator)) {
            msg = validator.call(me, value);

            if (msg !== true) {
                errors.push(msg);
            }
        }

        trimmed = me.allowOnlyWhitespace ? value : Ext.String.trim(value);

        if (trimmed.length < 1) {
            if (!me.allowBlank) {
                errors.push(me.blankText);
            }

            // If we are not configured to validate blank values,
            // there cannot be any additional errors
            if (!me.validateBlank) {
                return errors;
            }

            isBlank = true;
        }

        // If a blank value has been allowed through, then exempt it from the minLength check.
        // It must be allowed to hit the vtype validation.
        if (!isBlank && value.length < me.minLength) {
            errors.push(format(me.minLengthText, me.minLength));
        }

        if (value.length > me.maxLength) {
            errors.push(format(me.maxLengthText, me.maxLength));
        }

        if (vtype) {
            if (!vtypes[vtype](value, me)) {
                errors.push(me.vtypeText || vtypes[vtype + 'Text']);
            }
        }

        if (regex && !regex.test(value)) {
            errors.push(me.regexText || me.invalidText);
        }

        return errors;
    },

    getCaretPos: function() {
        return this.inputEl.getCaretPos();
    },

    setCaretPos: function(pos) {
        this.inputEl.setCaretPos(pos);
    },

    /**
     * Returns the selection range of an input element as an array of three values:
     *
     *      [ start, end, direction ]
     *
     * These have the same meaning as the parameters to `selectText`.
     * @return {Array}
     * @since 6.5.0
     */
    getTextSelection: function() {
        return this.inputEl.getTextSelection();
    },

    /**
     * Select the specified contents of the input field (all by default).
     * @param {Number} [start=0]
     * @param {Number} [end]
     * @param {"f"/"b"/"forward"/"backward"} [direction="f"] Pass "f" for forward,
     * "b" for backwards.
     * @return {Ext.form.field.Text} this
     * @chainable
     */
    selectText: function(start, end, direction) {
        var me = this;

        Ext.defer(function() {
            if (!me.destroyed && me.inputEl.isVisible(true)) {
                me.inputEl.selectText(start, end, direction);
            }
        }, Ext.isIE ? 10 : 0);

        return me;
    },

    // Template method, override in Combobox.
    getGrowWidth: function() {
        return this.inputEl.dom.value;
    },

    /**
     * Automatically grows the field to accommodate the width of the text up to the maximum
     * field width allowed. This only takes effect if {@link #grow} = true, and fires the
     * {@link #autosize} event if the width changes.
     */
    autoSize: function() {
        var me = this,
            triggers, triggerId, triggerWidth, inputEl, width, value;

        if (me.grow && me.rendered && me.getSizeModel().width.auto) {
            inputEl = me.inputEl;
            triggers = me.getTriggers();
            triggerWidth = 0;

            value = Ext.util.Format.htmlEncode(
                me.getGrowWidth() || (me.hasFocus ? '' : me.emptyText) || ''
            );

            // Translate spaces to non-breaking spaces
            value = value.replace(/\s/g, '&nbsp;');

            for (triggerId in triggers) {
                triggerWidth += triggers[triggerId].el.getWidth();
            }

            width = inputEl.getTextWidth(value) + triggerWidth +
                // The element that has the border depends on theme - inputWrap (classic)
                // or triggerWrap (neptune)
                me.inputWrap.getBorderWidth('lr') + me.triggerWrap.getBorderWidth('lr') +
                inputEl.getPadding('lr');

            width = Math.min(Math.max(width, me.growMin), me.growMax);

            me.bodyEl.setWidth(width);

            me.updateLayout();

            me.fireEvent('autosize', me, width);
        }
    },

    doDestroy: function() {
        var me = this;

        me.invokeTriggers('destroy');
        Ext.destroy(me.triggerRepeater);
        me.setInputMask(null);

        me.callParent();
    },

    onTriggerClick: Ext.emptyFn,

    privates: {
        /**
         * @private
         */
        getTdType: function() {
            return 'textfield';
        }
    },

    deprecated: {
        5: {
            methods: {
                /**
                 * @method getTriggerWidth
                 * Get the total width of the trigger button area.
                 * @return {Number} The total trigger width
                 * @deprecated 5.0 This method was removed.
                 */
                getTriggerWidth: function() {
                    var triggers = this.getTriggers(),
                        width = 0,
                        id;

                    if (triggers && this.rendered) {
                        for (id in triggers) {
                            if (triggers.hasOwnProperty(id)) {
                                width += triggers[id].el.getWidth();
                            }
                        }
                    }

                    return width;
                }
            }
        }
    }

}, function(TextField) {
    var calculated = Ext.layout.SizeModel.calculated;

    TextField.prototype.fakeSizeModel = calculated.pairsByHeightOrdinal[calculated.ordinal];
});
