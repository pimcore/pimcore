/**
 * Input masks provide a way for developers to define rules that govern user input. This ensures
 * that data is submitted in an expected format and with the appropriate character set.
 *
 * Frequent uses of input masks include:
 *
 * + Zip or postal codes
 * + Times
 * + Dates
 * + Telephone numbers
 *
 * ## Character Sets
 *
 * Input mask characters can be defined by representations of the desired set.  For instance,
 * if you only want to allow numbers, you can use 0 or 9.  Here is the list of default
 * representations:
 *
 * + '*': '[A-Za-z0-9]' // any case letter A-Z, any integer
 * + 'a': '[a-z]'       // any lower case letter a-z
 * + 'A': '[A-Z]'       // any upper case letter A-Z
 * + '0': '[0-9]'       // any integer
 * + '9': '[0-9]'        // any integer
 *
 * So, to create a telephone input mask, you could use:
 *
 * + (000) 000-0000
 *
 * or
 *
 * + (999) 999-9999
 *
 * ## Telephone input mask
 *
 *     @example toolkit=modern
 *     Ext.create({
 *         fullscreen: true,
 *         xtype: 'formpanel',
 *
 *         items: [{
 *             xtype: 'textfield',
 *             label: 'Phone Number',
 *             placeholder: '(xxx) xxx-xxxx',
 *             inputMask: '(999) 999-9999'
 *         }]
 *     });
 */
Ext.define('Ext.field.InputMask', function(InputMask) { return { // eslint-disable-line brace-style
    requires: [
        'Ext.util.LRU'
    ],

    cachedConfig: {
        blank: '_',

        characters: {
            '*': '[A-Za-z0-9]',
            'a': '[a-z]',
            'A': '[A-Z]',
            '0': '[0-9]',
            '9': '[0-9]'
        },

        ignoreCase: true
    },

    config: {
        /**
         * @cfg {String} pattern (required)
         */
        pattern: null
    },

    _cached: false,
    _lastEditablePos: null,
    _mask: null,

    statics: {
        active: {},

        from: function(value, existing) {
            var active = InputMask.active,
                ret;

            if (value === null) {
                ret = null;
            }
            else if (typeof value !== 'string') {
                if (existing && !existing._cached) {
                    ret = existing;
                    ret.setConfig(value);
                }
                else {
                    ret = new InputMask(value);
                }
            }
            else if (!(ret = active[value])) {
                // No one is currently using this mask, but check the cache of
                // recently used masks. We remove the mask from the cache and
                // move it to the active set... if it was there.
                if (!(ret = InputMask.cache.remove(value))) {
                    ret = new InputMask({
                        pattern: value
                    });
                }

                active[value] = ret;
                ret._cached = 1; // this is the first user either way
            }
            else {
                // The mask was found in the active set so we can reuse it
                // (just bump the counter).
                ++ret._cached;
            }

            return ret;
        }
    },

    constructor: function(config) {
        this.initConfig(config);
    },

    release: function() {
        var me = this,
            cache = InputMask.cache,
            key;

        if (me._cached && !--me._cached) {
            key = me.getPattern();

            //<debug>
            if (InputMask.active[key] !== me) {
                Ext.raise('Invalid call to InputMask#release (not active)');
            }

            if (cache.map[key]) {
                Ext.raise('Invalid call to InputMask#release (already cached)');
            }
            //</debug>

            delete InputMask.active[key];
            cache.add(key, me);
            cache.trim(cache.maxSize);
        }
        //<debug>
        else if (me._cached === 0) {
            Ext.raise('Invalid call to InputMask#release (already released)');
        }
        //</debug>
    },

    clearRange: function(value, start, len) {
        var me = this,
            blank = me.getBlank(),
            end = start + len,
            n = value.length,
            s = '',
            i, mask, prefixLen;

        if (!blank) {
            prefixLen = me._prefix.length;

            for (i = 0; i < n; ++i) {
                if (i < prefixLen || i < start || i >= end) {
                    s += value[i];
                }
            }

            s = me.formatValue(s);
        }
        else {
            mask = me.getPattern();

            for (i = 0; i < n; ++i) {
                if (i < start || i >= end) {
                    s += value[i];
                }
                else if (me.isFixedChar(i)) {
                    s += mask[i];
                }
                else {
                    s += blank;
                }
            }
        }

        return s;
    },

    formatValue: function(value) {
        var me = this,
            blank = me.getBlank(),
            i, length, mask, prefix, s;

        if (!blank) {
            prefix = me._prefix;
            length = prefix.length;

            s = this.insertRange('', value, 0);

            for (i = s.length; i > length && me.isFixedChar(i - 1);) {
                --i;
            }

            s = (i < length) ? prefix : s.slice(0, i - 1);
        }
        else if (value) {
            s = me.formatValue('');
            s = me.insertRange(s, value, 0);
        }
        else {
            mask = me.getPattern();
            s = '';

            for (i = 0, length = mask.length; i < length; ++i) {
                if (me.isFixedChar(i)) {
                    s += mask[i];
                }
                else {
                    s += blank;
                }
            }
        }

        return s;
    },

    getEditPosLeft: function(pos) {
        var i;

        for (i = pos; i >= 0; --i) {
            if (!this.isFixedChar(i)) {
                return i;
            }
        }

        return null;
    },

    getEditPosRight: function(pos) {
        var mask = this._mask,
            len = mask.length,
            i;

        for (i = pos; i < len; ++i) {
            if (!this.isFixedChar(i)) {
                return i;
            }
        }

        return null;
    },

    getFilledLength: function(value) {
        var me = this,
            blank = me.getBlank(),
            c, i;

        if (!blank) {
            return value.length;
        }

        for (i = value && value.length; i-- > 0;) {
            c = value[i];

            if (!me.isFixedChar(i) && me.isAllowedChar(c, i)) {
                break;
            }
        }

        return ++i || me._prefix.length;
    },

    getSubLength: function(value, substr, pos) {
        var me = this,
            mask = me.getPattern(),
            k = 0,
            maskLen = mask.length,
            substrLen = substr.length,
            i;

        for (i = pos; i < maskLen && k < substrLen;) {
            if (!me.isFixedChar(i) || mask[i] === substr[k]) {
                if (me.isAllowedChar(substr[k++], i, true)) {
                    ++i;
                }
            }
            else {
                ++i;
            }
        }

        return i - pos;
    },

    insertRange: function(value, substr, pos) {
        var me = this,
            mask = me.getPattern(),
            blank = me.getBlank(),
            filled = me.isFilled(value),
            prefixLen = me._prefix.length,
            maskLen = mask.length,
            substrLen = substr.length,
            s = value,
            ch, fixed, i, k;

        if (!blank && pos > s.length) {
            s += mask.slice(s.length, pos);
        }

        for (i = pos, k = 0; i < maskLen && k < substrLen;) {
            fixed = me.isFixedChar(i);

            if (!fixed || mask[i] === substr[k]) {
                ch = substr[k++];

                if (me.isAllowedChar(ch, i, true)) {
                    if (i < s.length) {
                        if (blank || filled || i < prefixLen) {
                            s = s.slice(0, i) + ch + s.slice(i + 1);
                        }
                        else {
                            s = me.formatValue(s.substr(0, i) + ch + s.substr(i));
                        }
                    }
                    else if (!blank) {
                        s += ch;
                    }

                    ++i;
                }
            }
            else {
                if (!blank && i >= s.length) {
                    s += mask[i];
                }
                else if (blank && fixed && substr[k] === blank) {
                    ++k;
                }

                ++i;
            }
        }

        return s;
    },

    isAllowedChar: function(character, pos, allowBlankChar) {
        var me = this,
            mask = me.getPattern(),
            c, characters, rule;

        if (me.isFixedChar(pos)) {
            return mask[pos] === character;
        }

        c = mask[pos];
        characters = me.getCharacters();
        rule = characters[c];

        return !rule || rule.test(character || '') ||
               (allowBlankChar && character === me.getBlank());
    },

    isEmpty: function(value) {
        var i, len;

        for (i = 0, len = value.length; i < len; ++i) {
            if (!this.isFixedChar(i) && this.isAllowedChar(value[i], i)) {
                return false;
            }
        }

        return true;
    },

    // TODO This function would benefit from optimization
    // Used during validation and range insert
    isFilled: function(value) {
        return this.getFilledLength(value) === this._mask.length;
    },

    isFixedChar: function(pos) {
        return Ext.Array.indexOf(this._fixedCharPositions, pos) > -1;
    },

    setCaretToEnd: function(field, value) {
        var filledLen = this.getFilledLength(value),
            pos = this.getEditPosRight(filledLen);

        if (pos !== null) {
            // Because we are called during a focus event, we have to delay pushing
            // down the new caret position to the next frame or else the browser will
            // position the caret at the end of the text. Note, Ext.asap() does *not*
            // work reliably for this.
            Ext.raf(function() {
                if (!field.destroyed) {
                    field.setCaretPos(pos);

                    Ext.raf(function() {
                        if (!field.destroyed) {
                            field.setCaretPos(pos);
                        }
                    });
                }
            });
        }
    },

    //---------------------------------------------------------------------
    // Event Handling

    onBlur: function(field, value) {
        if (field.getAutoHideInputMask() !== false) {
            if (this.isEmpty(value)) {
                field.maskProcessed = true;
                field.setValue('');
            }
        }
    },

    onFocus: function(field, value) {
        // On focus we have to show the mask and move caret to the last editable position
        // If field has autoHideInputMask === false, inputMask is always shown so we only
        // move the caret
        if (field.getAutoHideInputMask() !== false) {
            if (!value) {
                field.maskProcessed = true;
                field.setValue(this._mask);
            }
        }

        this.setCaretToEnd(field, value);
    },

    onChange: function(field, value, oldValue) {
        var me = this,
            s;

        if (field.maskProcessed || value === oldValue) {
            field.maskProcessed = false;

            return true;
        }

        if (value) {
            s = me.formatValue(value);
            field.maskProcessed = true;
            field.setValue(s);
        }
    },

    processAutocomplete: function(field, value) {
        var me = this,
            s;

        if (value) {
            if (value.length > me._mask.length) {
                value = value.substr(0, me._mask.length);
            }

            s = me.formatValue(value);
            field.maskProcessed = true;
            field.inputElement.dom.value = s; // match DOM
            field.setValue(s);

            this.setCaretToEnd(field, value);
        }
    },

    /**
     * @private
     * @param field
     * @param adjustCaret {Boolean} move caret to the first editable position
     */
    showEmptyMask: function(field, adjustCaret) {
        var s = this.formatValue();

        field.maskProcessed = true;
        field.setValue(s);

        if (adjustCaret) {
            this.setCaretToEnd(field);
        }
    },

    onKeyDown: function(field, value, event) {
        if (event.ctrlKey || event.metaKey) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            // key = event.key(), // Does not work on mobile
            key = event.keyCode === event.DELETE,
            del = key === 'Delete',
            handled = del || (event.keyCode === event.BACKSPACE),
            s = value,
            caret, editPos, len, prefixLen, textSelection, start;

        if (handled) {
            caret = field.getCaretPos();
            prefixLen = me._prefix.length;
            textSelection = field.getTextSelection();
            start = textSelection[0];
            len = textSelection[1] - start;

            if (len) {
                s = me.clearRange(value, start, len);
            }
            else if (caret < prefixLen || (!del && caret === prefixLen)) {
                caret = prefixLen;
            }
            else {
                editPos = del ? me.getEditPosRight(caret) : me.getEditPosLeft(caret - 1);

                if (editPos !== null) {
                    s = me.clearRange(value, editPos, 1);
                    caret = editPos;
                }
            }

            if (s !== value) {
                field.maskProcessed = true;
                field.setValue(s);
            }

            event.preventDefault();
            field.setCaretPos(caret);
        }
    },

    onKeyPress: function(field, value, event) {
        var me = this,
            key = event.keyCode,
            ch = event.getChar(),
            mask = me.getPattern(),
            prefixLen = me._prefix.length,
            s = value,
            caretPos, pos, start, textSelection;

        if (key === event.ENTER || key === event.TAB || event.ctrlKey || event.metaKey) {
            return;
        }

        // TODO Windows Phone may need to return here

        caretPos = field.getCaretPos();
        textSelection = field.getTextSelection();

        if (me.isFixedChar(caretPos) && mask[caretPos] === ch) {
            s = me.insertRange(s, ch, caretPos);
            ++caretPos;
        }
        else {
            pos = me.getEditPosRight(caretPos);

            if (pos !== null && me.isAllowedChar(ch, pos)) {
                start = textSelection[0];

                s = me.clearRange(s, start, textSelection[1] - start);
                s = me.insertRange(s, ch, pos);
                caretPos = pos + 1;
            }
        }

        if (s !== value) {
            field.maskProcessed = true;
            field.setValue(s);
        }

        event.preventDefault();

        if (caretPos < me._lastEditablePos && caretPos > prefixLen) {
            caretPos = me.getEditPosRight(caretPos);
        }

        field.setCaretPos(caretPos);
    },

    onPaste: function(field, value, event) {
        // TODO Android browser issues
        // https://bugs.chromium.org/p/chromium/issues/detail?id=369101
        var text,
            clipdData = event.browserEvent.clipboardData;

        if (clipdData && clipdData.getData) {
            text = clipdData.getData('text/plain');
        }
        else if (Ext.global.clipboardData && Ext.global.clipboardData.getData) {
            text = Ext.global.clipboardData.getData('Text'); // IE
        }

        if (text) {
            this.paste(field, value, text, field.getTextSelection());
        }

        event.preventDefault();
    },

    paste: function(field, value, text, selection) {
        var me = this,
            caretPos = selection[0],
            len = selection[1] - caretPos,
            s = len ? me.clearRange(value, caretPos, len) : value,
            textLen = me.getSubLength(s, text, caretPos);

        s = me.insertRange(s, text, caretPos);
        caretPos += textLen;
        caretPos = me.getEditPosRight(caretPos) || caretPos;

        if (s !== value) {
            field.maskProcessed = true;
            field.setValue(s);
        }

        field.setCaretPos(caretPos);
    },

    syncPattern: function(field) {
        var fieldValue = field.getValue(),
            s;

        if (field.getAutoHideInputMask() === false) {
            // show blank input mask if there is no initial value
            if (!fieldValue) {
                this.showEmptyMask(field);
            }
            else {
                // format any value and combine with mask
                s = this.formatValue(fieldValue);
                field.maskProcessed = true;
                field.setValue(s);
            }
        }
        else {
            // field has auto hide mask, but there might be an initial value
            // don't process empty value as that will set value to match the mask
            if (fieldValue) {
                s = this.formatValue(fieldValue);
                field.maskProcessed = true;
                field.setValue(s);
            }
        }
    },

    //---------------------------------------------------------------------
    // Configs

    applyCharacters: function(map) {
        var ret = {},
            flags = this.getIgnoreCase() ? 'i' : '',
            c, v;

        for (c in map) {
            v = map[c];

            if (typeof v === 'string') {
                v = new RegExp(v, flags);
            }

            ret[c] = v;
        }

        return ret;
    },

    updatePattern: function(mask) {
        var me = this,
            characters = me.getCharacters(),
            lastEditablePos = 0,
            n = mask && mask.length,
            blank = me.getBlank(),
            fixedPosArr = [],
            prefix = '',
            str = '',
            c, i;

        for (i = 0; i < n; ++i) {
            c = mask[i];

            if (!characters[c]) {
                fixedPosArr.push(str.length);
                str += c;
            }
            else {
                lastEditablePos = str.length + 1;
                str += blank;
            }
        }

        me._lastEditablePos = lastEditablePos;
        me._mask = str;
        me._fixedCharPositions = fixedPosArr;

        // Now that _fixedCharPositions are populated, isFixedChar can be used
        for (i = 0; i < str.length && me.isFixedChar(i); ++i) {
            prefix += str[i];
        }

        me._prefix = prefix;
    }
};
}, function(InputMask) {
    InputMask.cache = new Ext.util.LRU();
    InputMask.cache.maxSize = 100;
});
