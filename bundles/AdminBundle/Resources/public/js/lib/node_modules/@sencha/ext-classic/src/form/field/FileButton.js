/**
 *
 */
Ext.define('Ext.form.field.FileButton', {
    extend: 'Ext.button.Button',
    alias: 'widget.filebutton',

    childEls: [
        'fileInputEl'
    ],

    inputCls: Ext.baseCSSPrefix + 'form-file-input',

    cls: Ext.baseCSSPrefix + 'form-file-btn',

    preventDefault: false,

    // Button element *looks* focused but it should never really receive focus itself,
    // and with it being a <div></div> we don't need to render tabindex attribute at all
    tabIndex: undefined,

    // IE and Edge implement File input as two elements: text box and a button,
    // both are focusable and have a tab stop. Since we make file input transparent,
    // this results in users having to press Tab key twice with no visible action
    // just to go past our File input widget. There is no way to configure this behavior.
    // The workaround is as follows: we place two tabbable elements around the file input,
    // and forward the focus to the file input element whenever either guard is tabbed
    // into. We also intercept Tab keydown events on the file input, and fudge focus
    // before keyup so that when default action happens the focus will go outside
    // of the widget just like it should.
    // This mechanism is quite similar to what we use in Window component for trapping
    // focus, and in floating mixin to allow tabbing out of the floater.
    useTabGuards: Ext.isIE || Ext.isEdge,

    promptCalled: false,

    autoEl: {
        tag: 'div',
        unselectable: 'on'
    },

    /* eslint-disable indent */
    /*
     * This <input type="file"/> element is placed above the button element to intercept
     * mouse clicks, as well as receive focus. This is the only way to make browser file input
     * dialog open on user action, and populate the file input value when file(s) are selected.
     * The tabIndex value here comes from the template arguments generated in getTemplateArgs
     * method below; it is copied from the owner FileInput's tabIndex property.
     */
    afterTpl: [
        '<input id="{id}-fileInputEl" data-ref="fileInputEl" class="{childElCls} {inputCls}" ',
            'type="file" size="1" name="{inputName}" unselectable="on" ',
            '<tpl if="accept != null">accept="{accept}"</tpl>',
            '<tpl if="tabIndex != null">tabindex="{tabIndex}"</tpl>',
        '>'
    ],
    /* eslint-enable indent */

    keyMap: null,
    ariaEl: 'fileInputEl',

    /**
     * @private
     */
    getAfterMarkup: function(values) {
        return this.lookupTpl('afterTpl').apply(values);
    },

    getTemplateArgs: function() {
        var me = this,
            args;

        args = me.callParent();

        args.inputCls = me.inputCls;
        args.inputName = me.inputName || me.id;
        args.tabIndex = me.tabIndex != null ? me.tabIndex : null;
        args.accept = me.accept || null;
        args.role = me.ariaRole;

        return args;
    },

    afterRender: function() {
        var me = this,
            listeners, cfg;

        me.callParent(arguments);

        // We place focus and blur listeners on fileInputEl to activate Button's
        // focus and blur style treatment
        listeners = {
            scope: me,
            mousedown: me.handlePrompt,
            keydown: me.handlePrompt,
            change: me.fireChange,
            focus: me.onFileFocus,
            blur: me.onFileBlur,
            destroyable: true
        };

        if (me.useTabGuards) {
            cfg = {
                tag: 'span',
                role: 'button',
                'aria-hidden': 'true',
                'data-tabguard': 'true',
                style: {
                    height: 0,
                    width: 0
                }
            };

            cfg.tabIndex = me.tabIndex != null ? me.tabIndex : 0;

            // We are careful about inserting tab guards *around* the fileInputEl.
            // Keep in mind that IE8/9 have framed buttons so DOM structure
            // can be complex.
            me.beforeInputGuard = me.el.createChild(cfg, me.fileInputEl);
            me.afterInputGuard = me.el.createChild(cfg);
            me.afterInputGuard.insertAfter(me.fileInputEl);

            me.beforeInputGuard.on('focus', me.onInputGuardFocus, me);
            me.afterInputGuard.on('focus', me.onInputGuardFocus, me);

            listeners.keydown = me.onFileInputKeydown;
        }

        me.fileInputElListeners = me.fileInputEl.on(listeners);
    },

    doDestroy: function() {
        var me = this;

        if (me.fileInputElListeners) {
            me.fileInputElListeners.destroy();
        }

        if (me.beforeInputGuard) {
            me.beforeInputGuard.destroy();
            me.beforeInputGuard = null;
        }

        if (me.afterInputGuard) {
            me.afterInputGuard.destroy();
            me.afterInputGuard = null;
        }

        me.callParent();
    },

    fireChange: function(e) {
        this.fireEvent('change', this, e, this.fileInputEl.dom.value);
    },

    /**
     * @private
     * Creates the file input element. It is inserted into the trigger button component, made
     * invisible, and floated on top of the button's other content so that it will receive the
     * button's clicks.
     */
    createFileInput: function(isTemporary) {
        var me = this,
            fileInputEl, listeners;

        fileInputEl = me.fileInputEl = me.el.createChild({
            name: me.inputName || me.id,
            id: !isTemporary ? me.id + '-fileInputEl' : undefined,
            cls: me.inputCls + (me.getInherited().rtl ? ' ' + Ext.baseCSSPrefix + 'rtl' : ''),
            tag: 'input',
            type: 'file',
            size: 1,
            unselectable: 'on'
        }, me.afterInputGuard); // Nothing special happens outside of IE/Edge

        // This is our focusEl
        fileInputEl.dom.setAttribute('data-componentid', me.id);

        if (me.tabIndex != null) {
            me.setTabIndex(me.tabIndex);
        }

        if (me.accept) {
            fileInputEl.dom.setAttribute('accept', me.accept);
        }

        // We place focus and blur listeners on fileInputEl to activate Button's
        // focus and blur style treatment
        listeners = {
            scope: me,
            change: me.fireChange,
            mousedown: me.handlePrompt,
            keydown: me.handlePrompt,
            focus: me.onFileFocus,
            blur: me.onFileBlur
        };

        if (me.useTabGuards) {
            listeners.keydown = me.onFileInputKeydown;
        }

        fileInputEl.on(listeners);
    },

    handlePrompt: function(e) {
        var key;

        if (e.type === 'keydown') {
            key = e.getKey();
            // We need this conditional here because IE doesn't open the prompt on ENTER
            this.promptCalled = ((!Ext.isIE && key === e.ENTER) || key === e.SPACE) ? true : false;
        }
        else {
            this.promptCalled = true;
        }
    },

    onFileFocus: function(e) {
        var ownerCt = this.ownerCt;

        if (!this.hasFocus) {
            this.onFocus(e);
        }

        if (ownerCt && !ownerCt.hasFocus) {
            ownerCt.onFocus(e);
        }
    },

    onFileBlur: function(e) {
        var ownerCt = this.ownerCt;

        // We should not go ahead with blur if this was called because
        // the fileInput was clicked and the upload window is causing this event
        if (this.promptCalled) {
            this.promptCalled = false;
            e.preventDefault();

            return;
        }

        if (this.hasFocus) {
            this.onBlur(e);
        }

        if (ownerCt && ownerCt.hasFocus) {
            ownerCt.onBlur(e);
        }
    },

    onInputGuardFocus: function(e) {
        this.fileInputEl.focus();
    },

    onFileInputKeydown: function(e) {
        var key = e.getKey(),
            focusTo;

        if (key === e.TAB) {
            focusTo = e.shiftKey ? this.beforeInputGuard : this.afterInputGuard;

            if (focusTo) {
                // We need to skip the next focus to avoid it bouncing back
                // to the input field.
                focusTo.suspendEvent('focus');
                focusTo.focus();

                // In IE focus events are asynchronous so we can't enable focus event
                // in the same event loop.
                Ext.defer(function() {
                    focusTo.resumeEvent('focus');
                }, 1);
            }
        }
        else if (key === e.ENTER || key === e.SPACE) {
            this.handlePrompt(e);
        }

        // Returning true will allow the event to take default action
        return true;
    },

    reset: function(remove) {
        var me = this;

        if (remove) {
            me.fileInputEl.destroy();
        }

        me.createFileInput(!remove);

        if (remove) {
            me.ariaEl = me.fileInputEl;
        }
    },

    restoreInput: function(el) {
        var me = this;

        me.fileInputEl.destroy();
        el = Ext.get(el);

        if (me.useTabGuards) {
            el.insertBefore(me.afterInputGuard);
        }
        else {
            me.el.appendChild(el);
        }

        me.fileInputEl = el;
    },

    onDisable: function() {
        this.callParent();
        this.fileInputEl.dom.disabled = true;
    },

    onEnable: function() {
        this.callParent();
        this.fileInputEl.dom.disabled = false;
    },

    privates: {
        getFocusEl: function() {
            return this.fileInputEl;
        },

        getFocusClsEl: function() {
            return this.el;
        },

        setTabIndex: function(tabIndex) {
            var me = this;

            if (!me.focusable) {
                return;
            }

            me.tabIndex = tabIndex;

            if (!me.rendered || me.destroying || me.destroyed) {
                return;
            }

            if (me.useTabGuards) {
                me.fileInputEl.dom.setAttribute('tabIndex', -1);
                me.beforeInputGuard.dom.setAttribute('tabIndex', tabIndex);
                me.afterInputGuard.dom.setAttribute('tabIndex', tabIndex);
            }
            else {
                me.fileInputEl.dom.setAttribute('tabIndex', tabIndex);
            }
        }
    }
});
