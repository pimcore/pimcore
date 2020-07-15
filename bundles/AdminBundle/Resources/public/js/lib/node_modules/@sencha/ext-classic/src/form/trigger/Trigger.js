/**
 * @class Ext.form.trigger.Trigger
 * Base class for {@link Ext.form.field.Text#cfg-triggers Text Field triggers}
 */
Ext.define('Ext.form.trigger.Trigger', {
    alias: 'trigger.trigger',

    requires: [
        'Ext.util.ClickRepeater'
    ],

    mixins: [
        'Ext.mixin.Factoryable'
    ],

    factoryConfig: {
        defaultType: 'trigger'
    },

    /**
     * @cfg {Boolean} repeatClick
     * `true` to attach a {@link Ext.util.ClickRepeater click repeater} to the trigger
     */
    repeatClick: false,

    /**
     * @cfg cls
     * @inheritdoc Ext.panel.Header#cfg-iconCls
     */

    /**
     * @cfg {String} extraCls
     * An additional CSS class (or classes) to be added to the trigger's element. Can
     * be a single class name (e.g. 'foo') or a space-separated list of class names
     * (e.g. 'foo bar').
     */

    /**
     * @cfg {Function/String} [handler=undefined]
     * Function to run when trigger is clicked or tapped.
     * @controllable
     */

    /**
     * @cfg {Boolean} hidden
     * `true` to initially render the trigger hidden.
     */
    hidden: false,

    /**
     * @cfg {Boolean} [hideOnReadOnly=true]
     * Set 'false' to prevent trigger from being hidden even though the related field is
     * set {@link Ext.form.field.Text#readOnly readOnly}
     */
    hideOnReadOnly: undefined,

    /**
     * @cfg {Object} scope
     * Execution context for the {@link #handler} function.
     */

    /**
     * @cfg {String} tooltip
     * The triggers tooltip text. This text is available when using `Ext.QuickTips`.
     * @since 6.2.0
     */
    tooltip: null,

    /**
     * @cfg {Number} weight
     * An optional weighting to change the ordering of the items. The default weight is
     * `0`.  Triggers are sorted by weight in ascending order before being rendered.  
     * The value may be a negative value in order to position custom triggers ahead of 
     * default triggers like that of ComboBox.
     */
    weight: 0,

    /**
     * @cfg {Number} width The trigger's width, in pixels. Typically this is not needed
     * as the trigger width is normally determined by the style sheet, (see
     * {@link Ext.form.field.Text#$form-trigger-width extjs-text-field} or
     * {@link Ext.form.field.Text#$extjs-text-field-ui}).
     */

    /**
     * @cfg {Boolean} preventMouseDown
     * @private
     * If true, preventDefault() will be called on the mousedown event.  This prevents
     * a click on the trigger from blurring the field, which is desirable in most cases.
     * File field sets this to false, because preventing the default behavior of touchstart
     * prevents the browser's file dialog from opening.
     */
    preventMouseDown: true,

    /**
     * @cfg {Boolean} [focusOnMouseDown=false] If `true`, the field will be focused upon
     * mousedown on the trigger. This should be used only for main Picker field triggers
     * that expand and collapse the picker; additional triggers should not focus the field.
     * @private
     */
    focusOnMousedown: false,

    /**
     * @property {String}
     * @private
     * The base CSS class that is always added to the trigger element.
     */
    baseCls: Ext.baseCSSPrefix + 'form-trigger',

    /**
     * @property {String}
     * @private
     * The CSS class that is added to the trigger element when the field is focused.
     */
    focusCls: Ext.baseCSSPrefix + 'form-trigger-focus',

    /**
     * @property {String}
     * @private
     * The CSS class that is added to the trigger element when it is hovered.
     */
    overCls: Ext.baseCSSPrefix + 'form-trigger-over',

    /**
     * @property {String}
     * @private
     * The CSS class that is added to the trigger element it is clicked.
     */
    clickCls: Ext.baseCSSPrefix + 'form-trigger-click',

    /**
     * @private
     */
    validIdRe: Ext.validIdRe,

    /**
     * @property {Ext.Template/String/Array} bodyTpl
     * @protected
     * An optional template for rendering child elements inside the trigger element.
     * Useful for creating more complex triggers such as {@link Ext.form.trigger.Spinner}.
     */

    /* eslint-disable indent */
    renderTpl: [
        '<div id="{triggerId}" class="{baseCls} {baseCls}-{ui} {cls} {cls}-{ui} {extraCls} ',
                '{childElCls}"<tpl if="triggerStyle"> style="{triggerStyle}"</tpl>',
                '<tpl if="ariaRole"> role="{ariaRole}"<tpl else> role="presentation"</tpl>',
            '>',
            '{[values.$trigger.renderBody(values)]}',
        '</div>'
    ],
    /* eslint-enable indent */

    constructor: function(config) {
        var me = this,
            cls;

        Ext.apply(me, config);

        // extra over/click/focus cls for compat with 4.x TriggerField
        if (me.compat4Mode) {
            cls = me.cls;
            me.focusCls = [me.focusCls, cls + '-focus'];
            me.overCls = [me.overCls, cls + '-over'];
            me.clickCls = [me.clickCls, cls + '-click'];
        }

        //<debug>
        if (!me.validIdRe.test(me.id)) {
            Ext.raise('Invalid trigger "id": "' + me.id + '"');
        }
        //</debug>
    },

    /**
     * @protected
     * Called when this trigger's field is rendered
     */
    afterFieldRender: function() {
        var me = this,
            tip = me.tooltip;

        me.initEvents();

        if (tip) {
            me.tooltip = null;
            me.setTooltip(tip);
        }
    },

    destroy: function() {
        var me = this;

        me.clickRepeater = me.el = Ext.destroy(me.clickRepeater, me.el);
        me.callParent();
    },

    /**
     * @method
     * Allows addition of data to the render data object for the {@link #bodyTpl}.
     * @protected
     * @return {Object}
     */
    getBodyRenderData: Ext.emptyFn,

    /**
     * Get the element for this trigger.
     * @return {Ext.dom.Element} The element for this trigger, `null` if not rendered.
     */
    getEl: function() {
        return this.el || null;
    },

    /**
     * Returns the element that should receive the "state" classes - {@link #focusCls},
     * {@link #overCls}, and {@link #clickCls}.
     * @protected
     */
    getStateEl: function() {
        return this.el;
    },

    /**
     * Hides the trigger
     */
    hide: function() {
        var me = this,
            el = me.el;

        me.hidden = true;

        if (el) {
            el.hide();
        }
    },

    initEvents: function() {
        var me = this,
            isFieldEnabled = me.isFieldEnabled,
            stateEl = me.getStateEl(),
            el = me.el;

        stateEl.addClsOnOver(me.overCls, isFieldEnabled, me);
        stateEl.addClsOnClick(me.clickCls, isFieldEnabled, me);

        if (me.repeatClick) {
            me.clickRepeater = new Ext.util.ClickRepeater(el, {
                preventDefault: true,
                handler: me.onClick,
                listeners: {
                    mousedown: me.onClickRepeaterMouseDown,
                    mouseup: me.onClickRepeaterMouseUp,
                    scope: me
                },
                scope: me
            });
        }
        else {
            me.field.mon(el, {
                click: me.onClick,
                mousedown: me.onMouseDown,
                scope: me
            });
        }
    },

    /**
     * @private
     */
    isFieldEnabled: function() {
        return !this.field.disabled;
    },

    /**
     * Returns `true` if this Trigger is visible.
     * @return {Boolean} `true` if this trigger is visible, `false` otherwise.
     *
     * @since 5.0.0
     */
    isVisible: function() {
        var me = this,
            field = me.field,
            hidden = false;

        if (me.hidden || !field || !me.rendered || me.destroyed) {
            hidden = true;
        }

        return !hidden;
    },

    /**
     * @protected
     * Handles a click on the trigger's element
     */
    onClick: function() {
        var me = this,
            args = arguments,
            e = me.clickRepeater ? args[1] : args[0],
            handler = me.handler,
            field = me.field;

        if (handler && !field.readOnly && me.isFieldEnabled()) {
            Ext.callback(me.handler, me.scope, [field, me, e], 0, field);
        }
    },

    // "this" refers to our owning input field.
    resolveListenerScope: function(scope) {
        return this.field.resolveSatelliteListenerScope(this, scope);
    },

    onMouseDown: function(e) {
        // If it was a genuine mousedown or pointerdown, NOT a touch, then focus the input field.
        // Usually, the field will be focused, but the mousedown on the trigger
        // might be the user's first contact with the field.
        // It's definitely NOT the user's first contact with our field if the field
        // has the focus.
        // It is also possible that there are multiple triggers on the field, and only one
        // of them causes picker expand/collapse. When picker is about to be collapsed
        // we need to focus the input; otherwise if the picker was focused the focus will go
        // to the document body which is not what we want. However if the mousedown was on
        // a trigger that does not cause collapse we should NOT focus the field.
        if (e.pointerType !== 'touch' && (!this.field.containsFocus || this.focusOnMousedown)) {
            this.field.focus();
        }

        if (this.preventMouseDown) {
            // Stop the mousedown from blurring our field
            e.preventDefault();
        }
    },

    onClickRepeaterMouseDown: function(clickRepeater, e) {
        // If it was a genuine mousedown, NOT a touch, then focus the input field.
        // Usually, the field will be focused, but the mousedown on the trigger
        // might be the user's first contact with the field.
        if (!e.parentEvent || e.parentEvent.type === 'mousedown') {
            this.field.inputEl.focus();
        }

        // Stop the mousedown from blurring our field
        e.preventDefault();
    },

    onClickRepeaterMouseUp: function(clickRepeater, e) {
        var me = this,
            field = me.field;

        Ext.callback(me.endHandler, me.scope, [field, me, e], 0, field);
    },

    /**
     * @protected
     * Called when this trigger's field is blurred
     */
    onFieldBlur: function() {
        this.getStateEl().removeCls(this.focusCls);
    },

    /**
     * @protected
     * Called when this trigger's field is focused
     */
    onFieldFocus: function() {
        this.getStateEl().addCls(this.focusCls);
    },

    /**
     * @protected
     * Called when this trigger's field is rendered
     */
    onFieldRender: function() {
        var me = this,
            /**
             * @property {Ext.dom.Element} el
             * @private
             * The trigger's main element
             */
            el = me.el = me.field.triggerWrap.selectNode('#' + me.domId, false);

        // ensure that the trigger does not consume space when hidden
        el.setVisibilityMode(Ext.Element.DISPLAY);
        me.rendered = true;
    },

    /**
     * Renders the bodyTpl
     * @param renderData
     * @private
     * @return {String}
     */
    renderBody: function(renderData) {
        var me = this,
            bodyTpl = me.bodyTpl;

        Ext.apply(renderData, me.getBodyRenderData());

        return bodyTpl ? Ext.XTemplate.getTpl(me, 'bodyTpl').apply(renderData) : '';
    },

    /**
     * Generates the trigger markup. Called during rendering of the field the trigger
     * belongs to.
     * @param {Object} fieldData The render data object of the parent field.
     * @private
     * @return {String}
     */
    renderTrigger: function(fieldData) {
        var me = this,
            width = me.width,
            triggerStyle = me.hidden ? 'display:none;' : '';

        if (width) {
            triggerStyle += 'width:' + width;
        }

        return Ext.XTemplate.getTpl(me, 'renderTpl').apply({
            $trigger: me,
            fieldData: fieldData,
            ui: fieldData.ui,
            childElCls: fieldData.childElCls,
            triggerId: me.domId = me.field.id + '-trigger-' + me.id,
            cls: me.cls,
            triggerStyle: triggerStyle,
            extraCls: me.extraCls,
            baseCls: me.baseCls,
            ariaRole: me.ariaRole
        });
    },

    setHidden: function(hidden) {
        if (hidden !== this.hidden) {
            this[hidden ? 'hide' : 'show']();
        }
    },

    setTooltip: function(tip) {
        var me = this,
            el = me.el,
            was = me.tooltip;

        if (tip !== was) {
            me.tooltip = tip;

            if (el) {
                el.dom.setAttribute('data-qtip', Ext.htmlEncode(tip));
            }
        }
    },

    setVisible: function(visible) {
        this.setHidden(!visible);
    },

    /**
     * Shows the trigger
     */
    show: function() {
        var me = this,
            el = me.el;

        me.hidden = false;

        if (el) {
            el.show();
        }
    }
});
