/**
 * An abstract class for fields that have a single trigger which opens a "picker" popup below
 * the field, e.g. a combobox menu list or a date picker. It provides a base implementation
 * for toggling the picker's visibility when the trigger is clicked, as well as keyboard navigation
 * and some basic events. Sizing and alignment of the picker can be controlled via the
 * {@link #matchFieldWidth} and {@link #pickerAlign}/{@link #pickerOffset} config properties
 * respectively.
 *
 * You would not normally use this class directly, but instead use it as the parent class
 * for a specific picker field implementation. Subclasses must implement the {@link #createPicker}
 * method to create a picker component appropriate for the field.
 */
Ext.define('Ext.form.field.Picker', {
    extend: 'Ext.form.field.Text',
    alias: 'widget.pickerfield',
    alternateClassName: 'Ext.form.Picker',

    requires: ['Ext.util.KeyNav'],

    config: {
        triggers: {
            picker: {
                handler: 'onTriggerClick',
                scope: 'this',
                focusOnMousedown: true
            }
        }
    },

    renderConfig: {
        /**
         * @cfg {Boolean} editable
         * False to prevent the user from typing text directly into the field; the field can only
         * have its value set via selecting a value from the picker. In this state, the picker
         * can also be opened by clicking directly on the input field itself.
         */
        editable: true
    },

    keyMap: {
        scope: 'this',
        DOWN: 'onDownArrow',
        ESC: 'onEsc'
    },

    keyMapTarget: 'inputEl',

    /**
     * @property {Boolean} isPickerField
     * `true` in this class to identify an object as an instantiated Picker Field,
     * or subclass thereof.
     */
    isPickerField: true,

    /**
     * @cfg {Boolean} matchFieldWidth
     * Whether the picker dropdown's width should be explicitly set to match the width of the field.
     * Defaults to true.
     */
    matchFieldWidth: true,

    /**
     * @cfg {String} pickerAlign
     * The {@link Ext.util.Positionable#alignTo alignment position} with which to align the picker.
     * Defaults to "tl-bl?"
     */
    pickerAlign: 'tl-bl?',

    /**
     * @cfg {Number[]} pickerOffset
     * An offset [x,y] to use in addition to the {@link #pickerAlign} when positioning the picker.
     * Defaults to undefined.
     */

    /**
     * @cfg {String} [openCls='x-pickerfield-open']
     * A class to be added to the field's {@link #bodyEl} element when the picker is opened.
     */
    openCls: Ext.baseCSSPrefix + 'pickerfield-open',

    /**
     * @property {Boolean} isExpanded
     * True if the picker is currently expanded, false if not.
     */
    isExpanded: false,

    /**
     * @cfg {String} triggerCls
     * An additional CSS class used to style the trigger button. The trigger will always
     * get the class 'x-form-trigger' and triggerCls will be appended if specified.
     */

    /**
     * @event expand
     * Fires when the field's picker is expanded.
     * @param {Ext.form.field.Picker} field This field instance
     */

    /**
     * @event collapse
     * Fires when the field's picker is collapsed.
     * @param {Ext.form.field.Picker} field This field instance
     */

    /**
     * @event select
     * Fires when a value is selected via the picker.
     * @param {Ext.form.field.Picker} field This field instance
     * @param {Object} value The value that was selected. The exact type of this value
     * is dependent on the individual field and picker implementations.
     */

    applyTriggers: function(triggers) {
        var me = this,
            picker = triggers.picker;

        if (!picker.cls) {
            picker.cls = me.triggerCls;
        }

        return me.callParent([triggers]);
    },

    getSubTplData: function(fieldData) {
        var me = this,
            data, ariaAttr;

        data = me.callParent([fieldData]);

        if (!me.ariaStaticRoles[me.ariaRole]) {
            ariaAttr = data.ariaElAttributes;

            if (ariaAttr) {
                ariaAttr['aria-haspopup'] = true;

                // Picker fields start as collapsed
                ariaAttr['aria-expanded'] = false;
            }
        }

        return data;
    },

    initEvents: function() {
        this.callParent();

        // Disable native browser autocomplete
        if (Ext.isGecko) {
            this.inputEl.dom.setAttribute('autocomplete', 'off');
        }
    },

    updateEditable: function(editable, oldEditable) {
        var me = this;

        // Non-editable allows opening the picker by clicking the field
        if (!editable) {
            me.inputEl.on('click', me.onInputElClick, me);
        }
        else {
            me.inputEl.un('click', me.onInputElClick, me);
        }

        me.callParent([editable, oldEditable]);
    },

    /**
     * @private
     */
    onEsc: function(e) {
        if (Ext.isIE) {
            // Stop the esc key from "restoring" the previous value in IE
            // For example, type "foo". Highlight all the text, hit backspace.
            // Hit esc, "foo" will be restored. This behaviour doesn't occur
            // in any other browsers
            e.preventDefault();
        }

        if (this.isExpanded) {
            this.collapse();
            e.stopEvent();
        }
    },

    onDownArrow: function(e) {
        var me = this;

        if ((e.time - me.lastDownArrow) > 150) {
            delete me.lastDownArrow;
        }

        if (!me.isExpanded) {
            // Do not let the down arrow event propagate into the picker
            e.stopEvent();

            // Don't call expand() directly as there may be additional processing involved before
            // expanding, e.g. in the case of a ComboBox query.
            me.onTriggerClick(me, me.getPickerTrigger(), e);

            me.lastDownArrow = e.time;
        }
        else if (!e.stopped && (e.time - me.lastDownArrow) < 150) {
            delete me.lastDownArrow;
        }
    },

    /**
     * Expands this field's picker dropdown.
     */
    expand: function() {
        var me = this,
            bodyEl, picker, doc;

        if (me.rendered && !me.isExpanded && !me.destroyed) {
            bodyEl = me.bodyEl;
            picker = me.getPicker();
            doc = Ext.getDoc();
            picker.setMaxHeight(picker.initialConfig.maxHeight);

            if (me.matchFieldWidth) {
                picker.setWidth(me.bodyEl.getWidth());
            }

            // Show the picker and set isExpanded flag. alignPicker only works if isExpanded.
            picker.show();
            me.isExpanded = true;
            me.alignPicker();
            bodyEl.addCls(me.openCls);

            if (!me.ariaStaticRoles[me.ariaRole]) {
                if (!me.ariaEl.dom.hasAttribute('aria-owns')) {
                    me.ariaEl.dom.setAttribute(
                        'aria-owns', picker.listEl ? picker.listEl.id : picker.el.id
                    );
                }

                me.ariaEl.dom.setAttribute('aria-expanded', true);
            }

            // Collapse on touch outside this component tree.
            // Because touch platforms do not focus document.body on touch
            // so no focusleave would occur to trigger a collapse.
            me.touchListeners = doc.on({
                // Do not translate on non-touch platforms.
                // mousedown will blur the field.
                translate: false,
                touchstart: me.collapseIf,
                scope: me,
                delegated: false,
                destroyable: true
            });

            // Scrolling of anything which causes this field to move should collapse
            me.scrollListeners = Ext.on({
                scroll: me.onGlobalScroll,
                scope: me,
                destroyable: true
            });

            // Buffer is used to allow any layouts to complete before we align
            Ext.on('resize', me.alignPicker, me, { buffer: 1 });
            me.fireEvent('expand', me);
            me.onExpand();
        }
    },

    onExpand: Ext.emptyFn,

    /**
     * Aligns the picker to the input element
     * @protected
     */
    alignPicker: function() {
        var me = this,
            picker;

        if (me.rendered && !me.destroyed) {
            picker = me.getPicker();

            if (picker.isVisible() && picker.isFloating()) {
                me.doAlign();
            }
        }
    },

    /**
     * Performs the alignment on the picker using the class defaults
     * @private
     */
    doAlign: function() {
        var me = this,
            picker = me.picker,
            aboveSfx = '-above',
            newPos,
            isAbove;

        // Align to the trigger wrap because the border isn't always on the input element, which
        // can cause the offset to be off
        picker.el.alignTo(me.triggerWrap, me.pickerAlign, me.pickerOffset);

        // We used *element* alignTo to bypass the automatic reposition on scroll which
        // Floating#alignTo does. So we must sync the Component state.
        newPos = picker.floatParent
            ? picker.getOffsetsTo(picker.floatParent.getTargetEl())
            : picker.getXY();

        picker.x = newPos[0];
        picker.y = newPos[1];

        // add the {openCls}-above class if the picker was aligned above
        // the field due to hitting the bottom of the viewport
        isAbove = picker.el.getY() < me.inputEl.getY();
        me.bodyEl[isAbove ? 'addCls' : 'removeCls'](me.openCls + aboveSfx);
        picker[isAbove ? 'addCls' : 'removeCls'](picker.baseCls + aboveSfx);
    },

    /**
     * Collapses this field's picker dropdown.
     */
    collapse: function() {
        var me = this,
            openCls = me.openCls,
            aboveSfx = '-above',
            picker;

        if (me.isExpanded && !me.destroyed && !me.destroying) {
            picker = me.picker;

            // hide the picker and set isExpanded flag
            picker.hide();
            me.isExpanded = false;

            // remove the openCls
            me.bodyEl.removeCls([openCls, openCls + aboveSfx]);
            picker.el.removeCls(picker.baseCls + aboveSfx);

            if (!me.ariaStaticRoles[me.ariaRole]) {
                me.ariaEl.dom.setAttribute('aria-expanded', false);
            }

            // remove event listeners
            me.touchListeners.destroy();
            me.scrollListeners.destroy();

            Ext.un('resize', me.alignPicker, me);
            me.fireEvent('collapse', me);

            me.onCollapse();
        }
    },

    onCollapse: Ext.emptyFn,

    /**
     * @private
     * Runs on touchstart of doc to check to see if we should collapse the picker.
     */
    collapseIf: function(e) {
        var me = this;

        // If what was mousedowned on is outside of this Field, and is not focusable, then collapse.
        // If it is focusable, this Field will blur and collapse anyway.
        if (!me.destroyed && !e.within(me.bodyEl, false, true) && !me.owns(e.target) &&
            !Ext.fly(e.target).isFocusable()) {
            me.collapse();
        }
    },

    /**
     * Returns a reference to the picker component for this field, creating it if necessary by
     * calling {@link #createPicker}.
     * @return {Ext.Component} The picker component
     */
    getPicker: function() {
        var me = this,
            picker = me.picker;

        if (!picker) {
            me.creatingPicker = true;
            me.picker = picker = me.createPicker();
            // For upward component searches.
            picker.ownerCmp = me;
            delete me.creatingPicker;
        }

        return me.picker;
    },

    // When focus leaves the picker component, if it's to outside of this
    // Component's hierarchy
    onFocusLeave: function(e) {
        this.collapse();
        this.callParent([e]);
    },

    /**
     * @private
     * The CQ interface. Allow drilling down into the picker when it exists.
     * Important for determining whether an event took place in the bounds of some
     * higher level containing component. See AbstractComponent#owns
     */
    getRefItems: function() {
        var result = [];

        if (this.picker) {
            result[0] = this.picker;
        }

        return result;
    },

    getPickerTrigger: function() {
        return this.triggers && this.triggers.picker;
    },

    /**
     * @method
     * Creates and returns the component to be used as this field's picker.
     * Must be implemented by subclasses of Picker.
     */
    createPicker: Ext.emptyFn,

    onInputElClick: function(e) {
        this.onTriggerClick(this, this.getPickerTrigger(), e);
    },

    /**
     * Handles the trigger click; by default toggles between expanding and collapsing
     * the picker component.
     * @protected
     * @param {Ext.form.field.Picker} field This field instance.
     * @param {Ext.form.trigger.Trigger} trigger This field's picker trigger.
     * @param {Ext.event.Event} e The event that generated this call.
     */
    onTriggerClick: function(field, trigger, e) {
        var me = this;

        if (!me.readOnly && !me.disabled) {
            if (me.isExpanded) {
                me.collapse();
            }
            else {
                me.expand();
            }
        }
    },

    doDestroy: function() {
        var me = this,
            picker = me.picker;

        Ext.un('resize', me.alignPicker, me);
        Ext.destroy(me.keyNav, picker);

        if (picker) {
            me.picker = picker.pickerField = null;
        }

        me.callParent();
    },

    privates: {
        onGlobalScroll: function(scroller) {
            var scrollEl = scroller.getElement();

            // Collapse if the scroll is anywhere but inside the picker
            if (!this.picker.owns(scrollEl) && scrollEl.isAncestor(this.el)) {
                this.collapse();
            }
        }
    }
});
