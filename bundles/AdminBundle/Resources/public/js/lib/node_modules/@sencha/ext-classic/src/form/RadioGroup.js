/**
 * A {@link Ext.form.FieldContainer field container} which has a specialized layout for arranging
 * {@link Ext.form.field.Radio} controls into columns, and provides convenience
 * {@link Ext.form.field.Field} methods for {@link #getValue getting}, {@link #setValue setting},
 * and {@link #validate validating} the group of radio buttons as a whole.
 *
 * ## Validation
 *
 * Individual radio buttons themselves have no default validation behavior, but
 * sometimes you want to require a user to select one of a group of radios. RadioGroup
 * allows this by setting the config `{@link #allowBlank}:false`; when the user does not check at
 * one of the radio buttons, the entire group will be highlighted as invalid and the
 * {@link #blankText error message} will be displayed according to the {@link #msgTarget} config.
 *
 * ## Layout
 *
 * The default layout for RadioGroup makes it easy to arrange the radio buttons into
 * columns; see the {@link #columns} and {@link #vertical} config documentation for details.
 * You may also use a completely different layout by setting the {@link #cfg-layout} to one of the 
 * other supported layout types; for instance you may wish to use a custom arrangement 
 * of hbox and vbox containers. In that case the Radio components at any depth will 
 * still be managed by the RadioGroup's validation.
 *
 * ## Example usage
 *
 *     @example
 *     Ext.create('Ext.form.Panel', {
 *         title: 'RadioGroup Example',
 *         width: 300,
 *         bodyPadding: 10,
 *         renderTo: Ext.getBody(),
 *         items:[{
 *             xtype: 'radiogroup',
 *             fieldLabel: 'Two Columns',
 *             // Arrange radio buttons into two columns, distributed vertically
 *             columns: 2,
 *             vertical: true,
 *             items: [
 *                 { boxLabel: 'Item 1', name: 'rb', inputValue: '1' },
 *                 { boxLabel: 'Item 2', name: 'rb', inputValue: '2', checked: true},
 *                 { boxLabel: 'Item 3', name: 'rb', inputValue: '3' },
 *                 { boxLabel: 'Item 4', name: 'rb', inputValue: '4' },
 *                 { boxLabel: 'Item 5', name: 'rb', inputValue: '5' },
 *                 { boxLabel: 'Item 6', name: 'rb', inputValue: '6' }
 *             ]
 *         }]
 *     });
 *
 * ## Example with value binding to the RadioGroup.  In the below example, "Item 2" will
 * initially be checked using `myValue: '2'` from the ViewModel.
 *
 *     @example
 *     Ext.define('MyApp.main.view.Main', {
 *         extend: 'Ext.app.ViewModel',
 *         alias: 'viewmodel.main',
 *         data: {
 *             myValue: '2'
 *         }
 *     });
 *
 *     Ext.create('Ext.form.Panel', {
 *         title: 'RadioGroup Example',
 *         viewModel: {
 *             type: 'main'
 *         },
 *         width: 300,
 *         bodyPadding: 10,
 *         renderTo: Ext.getBody(),
 *         items:[{
 *             xtype: 'radiogroup',
 *             fieldLabel: 'Two Columns',
 *             // Arrange radio buttons into two columns, distributed vertically
 *             columns: 2,
 *             vertical: true,
 *             simpleValue: true,  // set simpleValue to true to enable value binding
 *             bind: '{myValue}',
 *             items: [
 *                 { boxLabel: 'Item 1', name: 'rb', inputValue: '1' },
 *                 { boxLabel: 'Item 2', name: 'rb', inputValue: '2' },
 *                 { boxLabel: 'Item 3', name: 'rb', inputValue: '3' },
 *                 { boxLabel: 'Item 4', name: 'rb', inputValue: '4' },
 *                 { boxLabel: 'Item 5', name: 'rb', inputValue: '5' },
 *                 { boxLabel: 'Item 6', name: 'rb', inputValue: '6' }
 *             ]
 *         }]
 *     });
 *
 */
Ext.define('Ext.form.RadioGroup', {
    extend: 'Ext.form.CheckboxGroup',
    xtype: 'radiogroup',

    /**
     * @property {Boolean} isRadioGroup
     * The value `true` to identify an object as an instance of this or derived class.
     * @readonly
     * @since 6.2.0
     */
    isRadioGroup: true,

    requires: [
        'Ext.form.field.Radio'
    ],

    /**
     * @cfg {Ext.form.field.Radio[]/Object[]} items
     * An Array of {@link Ext.form.field.Radio Radio}s or Radio config objects to arrange
     * in the group.
     */

    /**
     * @cfg {Boolean} allowBlank
     * True to allow every item in the group to be blank.
     * If allowBlank = false and no items are selected at validation time,
     * {@link #blankText} will be used as the error text.
     */
    allowBlank: true,

    /**
     * @cfg {String} blankText
     * Error text to display if the {@link #allowBlank} validation fails
     * @locale
     */
    blankText: 'You must select one item in this group',

    defaultType: 'radiofield',

    /**
     * @cfg {Boolean} [local=false]
     * By default, child {@link Ext.form.field.Radio radio} `name`s are scoped to the
     * encapsulating {@link Ext.form.Panel form panel} if any, of the document.
     *
     * If you are using multiple `RadioGroup`s each of which uses the same `name`
     * configuration in child {@link Ext.form.field.Radio radio}s, configure this as
     * `true` to scope the names to within this `RadioGroup`
     */
    local: false,

    /**
     * @cfg {Boolean} simpleValue
     * When set to `true` the `value` of this group of `radiofield` components will be
     * mapped to the `inputValue` of the checked item. This is, the `getValue` method
     * will return the `inputValue` of the checked item while `setValue` will check the
     * `radiofield` whose `inputValue` matches the given value.
     *
     * This field allows the `radiogroup` to participate in binding an entire group of
     * radio buttons to a single value.
     *
     * In the below example, "Item 2" will initially be checked using `myValue: '2'` from
     * the ViewModel.
     *
     *     @example
     *     Ext.define('MyApp.main.view.Main', {
     *         extend: 'Ext.app.ViewModel',
     *         alias: 'viewmodel.main',
     *         data: {
     *             myValue: '2'
     *         }
     *     });
     *
     *     Ext.create('Ext.form.Panel', {
     *         title: 'RadioGroup Example',
     *         viewModel: {
     *             type: 'main'
     *         },
     *         width: 300,
     *         bodyPadding: 10,
     *         renderTo: Ext.getBody(),
     *         items:[{
     *             xtype: 'radiogroup',
     *             fieldLabel: 'Two Columns',
     *             // Arrange radio buttons into two columns, distributed vertically
     *             columns: 2,
     *             vertical: true,
     *             simpleValue: true,  // set simpleValue to true to enable value binding
     *             bind: '{myValue}',
     *             items: [
     *                 { boxLabel: 'Item 1', name: 'rb', inputValue: '1' },
     *                 { boxLabel: 'Item 2', name: 'rb', inputValue: '2' },
     *                 { boxLabel: 'Item 3', name: 'rb', inputValue: '3' },
     *                 { boxLabel: 'Item 4', name: 'rb', inputValue: '4' },
     *                 { boxLabel: 'Item 5', name: 'rb', inputValue: '5' },
     *                 { boxLabel: 'Item 6', name: 'rb', inputValue: '6' }
     *             ]
     *         }]
     *     });
     *
     * @since 6.2.0
     */
    simpleValue: false,

    defaultBindProperty: 'value',

    /**
     * @private
     */
    groupCls: Ext.baseCSSPrefix + 'form-radio-group',

    ariaRole: 'radiogroup',

    initRenderData: function() {
        var me = this,
            data, ariaAttr;

        data = me.callParent();
        ariaAttr = data.ariaAttributes;

        if (ariaAttr) {
            ariaAttr['aria-required'] = !me.allowBlank;
            ariaAttr['aria-invalid'] = false;
        }

        return data;
    },

    lookupComponent: function(config) {
        var result = this.callParent([config]);

        // Local means that the exclusivity of checking by name is scoped to this RadioGroup.
        // So multiple RadioGroups can be used which use the same Radio names.
        // This enables their use as a grid widget.
        if (this.local) {
            result.formId = this.getId();
        }

        return result;
    },

    getBoxes: function(query, root) {
        return (root || this).query('[isRadio]' + (query || ''));
    },

    checkChange: function() {
        var me = this,
            value, key;

        value = me.getValue();

        // Safari might throw an exception on trying to get the keys of a Number
        key = typeof value === 'object' && Ext.Object.getKeys(value)[0];

        // If the value is an array we skip out here because it's during a change
        // between multiple items, so we never want to fire a change
        if (me.simpleValue || (key && !Ext.isArray(value[key]))) {
            me.callParent(arguments);
        }
    },

    isEqual: function(value1, value2) {
        if (this.simpleValue) {
            return value1 === value2;
        }

        return this.callParent([ value1, value2 ]);
    },

    getValue: function() {
        var me = this,
            items = me.items.items,
            i, item, ret;

        if (me.simpleValue) {
            for (i = items.length; i-- > 0;) {
                item = items[i];

                if (item.checked) {
                    ret = item.inputValue;
                    break;
                }
            }
        }
        else {
            ret = me.callParent();
        }

        return ret;
    },

    /**
     * Sets the value of the radio group. The radio with corresponding name and value will be set.
     * This method is simpler than {@link Ext.form.CheckboxGroup#setValue} because only 1 value
     * is allowed for each name. You can use the setValue method as:
     *
     *     var form = Ext.create('Ext.form.Panel', {
     *         title       : 'RadioGroup Example',
     *         width       : 300,
     *         bodyPadding : 10,
     *         renderTo    : Ext.getBody(),
     *         items       : [
     *             {
     *                 xtype      : 'radiogroup',
     *                 fieldLabel : 'Group',
     *                 items      : [
     *                     { boxLabel : 'Item 1', name : 'rb', inputValue : 1 },
     *                     { boxLabel : 'Item 2', name : 'rb', inputValue : 2 }
     *                 ]
     *             }
     *         ],
     *         tbar        : [
     *             {
     *                 text    : 'setValue on RadioGroup',
     *                 handler : function() {
     *                     form.child('radiogroup').setValue({
     *                         rb : 2
     *                     });
     *                 }
     *             }
     *         ]
     *     });
     *
     * @param {Mixed} value An Object to map names to values to be set. If not an Object,
     * this `radiofield` with a matching `inputValue` will be checked.
     * @return {Ext.form.RadioGroup} this
     */
    setValue: function(value) {
        var items = this.items,
            cbValue, cmp, formId, radios, i, len, name;

        Ext.suspendLayouts();

        if (this.simpleValue) {
            for (i = 0, len = items.length; i < len; ++i) {
                cmp = items.items[i];

                cmp.$groupChange = true;
                cmp.setValue(cmp.inputValue === value);
                delete cmp.$groupChange;
            }
        }
        else if (Ext.isObject(value)) {
            cmp = items.first();
            formId = cmp ? cmp.getFormId() : null;

            for (name in value) {
                cbValue = value[name];
                radios = Ext.form.RadioManager.getWithValue(name, cbValue, formId).items;
                len = radios.length;

                for (i = 0; i < len; ++i) {
                    radios[i].setValue(true);
                }
            }
        }

        Ext.resumeLayouts(true);

        return this;
    },

    markInvalid: function(errors) {
        var ariaDom = this.ariaEl.dom;

        this.callParent([errors]);

        if (ariaDom) {
            ariaDom.setAttribute('aria-invalid', true);
        }
    },

    clearInvalid: function() {
        var ariaDom = this.ariaEl.dom;

        this.callParent();

        if (ariaDom) {
            ariaDom.setAttribute('aria-invalid', false);
        }
    }
}, function() {
    // Firefox has a nasty bug, or a misfeature, with tabbing over radio buttons
    // when there is no checked button in a group. In such case the first button
    // in the group should be focused upon tabbing into the group, and subsequent
    // tab key press should leave the group; however Firefox will tab over every
    // radio button individually as if they were checkboxes.
    // Fortunately for us this bugfeature only applies to tabbing; arrow key
    // navigation is not affected. So the issue can be easily worked around
    // by removing all group buttons from tab order upon focusing the first button
    // in the group, and restoring their tabbable state upon focusleave.
    // This works exactly the same way regardless of having or not a checked button
    // in the group, so we keep the code simple.

    // This condition should get more version specific when this bug is fixed:
    // https://bugzilla.mozilla.org/show_bug.cgi?id=1267488
    if (Ext.isGecko) {
        this.override({
            onFocusEnter: function(e) {
                var target = e.toComponent,
                    radios, i, len;

                if (target.isRadio) {
                    radios = target.getManager().getByName(target.name, target.getFormId()).items;

                    for (i = 0, len = radios.length; i < len; i++) {
                        radios[i].disableTabbing();
                    }
                }
            },

            onFocusLeave: function(e) {
                var target = e.fromComponent,
                    radios, i, len;

                if (target.isRadio) {
                    radios = target.getManager().getByName(target.name, target.getFormId()).items;

                    for (i = 0, len = radios.length; i < len; i++) {
                        radios[i].enableTabbing();
                    }
                }
            }
        });
    }
});
