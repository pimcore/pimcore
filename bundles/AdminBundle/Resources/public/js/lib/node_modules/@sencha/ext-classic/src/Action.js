/**
 * An Action encapsulates a shareable, reusable set of properties which define a "clickable"
 * UI component such as a {@link Ext.button.Button button} or {@link Ext.menu.Item menu item},
 * or {@link Ext.panel.Panel#tools panel header tool}, or an
 * {@link Ext.grid.column.Action ActionColumn item}
 * 
 * Actions let you share handlers, configuration options and UI updates across any components
 * which were created using the Action.
 *
 * You do not have to create Action instances. They can be configured into Views
 * using the {@link Ext.container.Container#actions actions} config.
 *
 * Use a reference to an Action as the config object for any number of UI Components which share
 * the same configuration. The Action not only supplies the configuration, but allows all Components
 * based upon it to have a common set of methods called at once through a single call to the Action.
 *
 * Any Component that is to be configured with an Action may support
 * the following methods:
 *
 * - setText(String)
 * - setIconCls(String)
 * - setGlyph(String)
 * - setDisabled(Boolean)
 * - setVisible(Boolean)
 * - setHandler(String)
 *
 * This allows the Action to control its associated Components. Use a
 * {@link Ext.container.Container#getAction reference to an Action} to control
 * all components created from that action.
 *
 * Example usage:
 *
 *     @example
 *     Ext.define('ActionsExampleController', {
 *         extend: 'Ext.app.ViewController',
 *         alias: 'controller.actionsexample',
 *
 *         onOperationClick: function() {
 *             Ext.Msg.alert('Click', 'Perform the operation');
 *         },
 *
 *         onOperationToggle: function(btn, pressed) {
 *             // The action controls all UI components created from it.
 *             this.view.getAction('operation').setDisabled(pressed);
 *         }
 *     });
 *
 *     Ext.define('ActionsPanel', {
 *         extend: 'Ext.panel.Panel',
 *         controller: 'actionsexample',
 *
 *         title: 'Actions',
 *         width: 500,
 *         height: 300,
 *
 *         // Define the shared Action.  Each Component created from these will
 *         // have the same display text, icon and tooltip, and will invoke the
 *         // same controller method on click.
 *         actions: {
 *             operation: {
 *                 text: 'Do operation',
 *                  handler: 'onOperationClick',
 *                 glyph: 'xf005@FontAwesome',
 *                 tooltip: 'Perform the operation'
 *             },
 *             disableOperation: {
 *                 text: 'Disable operation',
 *                 enableToggle: true,
 *                 toggleHandler: 'onOperationToggle',
 *                 tooltip: 'Disable the operation'
 *             }
 *         },
 *
 *         // Actions are interpreted as Buttons by this view.
 *         // Other descendants such as Menus and Toolbars have
 *         // their own defaults.
 *         defaultActionType: 'button',
 *
 *         tools: [
 *             '@operation'
 *         ],
 *
 *         tbar: [
 *             // Add the Action directly to a toolbar as a menu button
 *             '@operation',
 *             {
 *                 text: 'Action Menu',
 *                 menu: [
 *                     // Add the Action to a menu as a text item
 *                     '@operation'
 *                 ]
 *             }, '@disableOperation'
 *         ],
 *
 *         bodyPadding: 10,
 *         layout: {
 *             type: 'vbox',
 *             align: 'stretch'
 *         },
 *         items: [
 *             // Add the Action to the panel body.
 *             // defaultActionType will ensure it is converted to a Button.
 *             '@operation'
 *         ]
 *     });
 *
 *     Ext.QuickTips.init();
 *     new ActionsPanel({
 *         renderTo: Ext.getBody()
 *     });
 */
Ext.define('Ext.Action', {

    /**
     * @cfg {String} [text='']
     * The text to set for all components configured by this Action.
     */
    /**
     * @cfg glyph
     * @inheritdoc Ext.panel.Header#cfg-glyph
     * @since 6.2.0
     */
    /**
     * @cfg iconCls
     * @localdoc **Note:** The CSS class(es) specifying the background image will apply 
     * to all components configured by this Action.
     * @inheritdoc Ext.panel.Header#cfg-iconCls
     */
    /**
     * @cfg {Boolean} [disabled=false]
     * True to disable all components configured by this Action, false to enable them.
     */
    /**
     * @cfg {Boolean} [hidden=false]
     * True to hide all components configured by this Action, false to show them.
     */
    /**
     * @cfg {String/Function} handler
     * The function that will be invoked by each component tied to this Action
     * when the component's primary event is triggered.
     */
    /**
     * @cfg {String} itemId
     * See {@link Ext.Component}.{@link Ext.Component#itemId itemId}.
     */
    /**
     * @cfg {Object} [scope]
     * The scope (this reference) in which the {@link #handler} is executed
     * if specified as a function instead of a named Controller method.
     * Defaults to the browser window.
     */

    /**
     * Creates new Action.
     * @param {Object} config Config object.
     */
    constructor: function(config) {
        this.initialConfig = config;
        this.itemId = config.itemId = (config.itemId || config.id || Ext.id());
        this.items = [];
    },

    /**
     * @property {Boolean} isAction
     * `true` in this class to identify an object as an instantiated Action, or subclass thereof.
     */
    isAction: true,

    /**
     * Sets the text to be displayed by all components configured by this Action.
     * @param {String} text The text to display
     */
    setText: function(text) {
        this.initialConfig.text = text;
        this.callEach('setText', [text]);
    },

    /**
     * Gets the text currently displayed by all components configured by this Action.
     */
    getText: function() {
        return this.initialConfig.text;
    },

    /**
     * Sets the {@link #iconCls icon CSS class} for all components configured by this 
     * Action.  The class should supply a background image that will be used as the icon 
     * image.
     * @param {String} cls The CSS class supplying the icon image
     */
    setIconCls: function(cls) {
        this.initialConfig.iconCls = cls;
        this.callEach('setIconCls', [cls]);
    },

    /**
     * Sets the {@link #glyph glyph} for all components configured by this 
     * Action.
     * @param {String} glyph The CSS class supplying the icon image
     */
    setGlyph: function(glyph) {
        this.initialConfig.glyph = glyph;
        this.callEach('setGlyph', [glyph]);
    },

    /**
     * Gets the icon CSS class currently used by all components configured by this Action.
     */
    getIconCls: function() {
        return this.initialConfig.iconCls;
    },

    /**
     * Sets the disabled state of all components configured by this Action.  Shortcut method
     * for {@link #enable} and {@link #disable}.
     * @param {Boolean} disabled True to disable the component, false to enable it
     */
    setDisabled: function(disabled) {
        this.initialConfig.disabled = disabled;
        this.callEach('setDisabled', [disabled]);
    },

    /**
     * Enables all components configured by this Action.
     */
    enable: function() {
        this.setDisabled(false);
    },

    /**
     * Disables all components configured by this Action.
     */
    disable: function() {
        this.setDisabled(true);
    },

    /**
     * Returns true if the components using this Action are currently disabled, else returns false.
     */
    isDisabled: function() {
        return this.initialConfig.disabled;
    },

    /**
     * Sets the hidden state of all components configured by this Action.  Shortcut method
     * for `{@link #hide}` and `{@link #show}`.
     * @param {Boolean} hidden True to hide the component, false to show it.
     */
    setHidden: function(hidden) {
        this.initialConfig.hidden = hidden;
        this.callEach('setVisible', [!hidden]);
    },

    /**
     * Shows all components configured by this Action.
     */
    show: function() {
        this.setHidden(false);
    },

    /**
     * Hides all components configured by this Action.
     */
    hide: function() {
        this.setHidden(true);
    },

    /**
     * Returns true if the components configured by this Action are currently hidden,
     * else returns false.
     */
    isHidden: function() {
        return this.initialConfig.hidden;
    },

    /**
     * Sets the function that will be called by each Component using this action when its
     * primary event (usually a click or tap) is triggered.
     * @param {String/Function} handler The function that will be invoked by the action's components
     * when clicked. See the `handler` config of the target component for the arguments passed.
     * @param {Object} [scope] The scope (this reference) in which the function is executed.
     * Defaults to an encapsulating {@link Ext.app.Controller Controller}, or the Component.
     */
    setHandler: function(handler, scope) {
        this.initialConfig.handler = handler;
        this.initialConfig.scope = scope;
        this.callEach('setHandler', [handler, scope]);
    },

    /**
     * Executes the specified function once for each Component currently tied to this Action.
     * The function passed in should accept a single argument that will be an object that supports
     * the basic Action config/method interface.
     * @param {Function} fn The function to execute for each component
     * @param {Object} scope The scope (this reference) in which the function is executed.
     * Defaults to the Component.
     */
    each: function(fn, scope) {
        Ext.each(this.items, fn, scope);
    },

    /**
     * @private
     */
    callEach: function(fnName, args) {
        var items = this.items,
            i = 0,
            len = items.length,
            item;

        Ext.suspendLayouts();

        for (; i < len; i++) {
            item = items[i];
            item[fnName].apply(item, args);
        }

        Ext.resumeLayouts(true);
    },

    /**
     * @private
     */
    addComponent: function(comp) {
        this.items.push(comp);
        comp.on('destroy', this.removeComponent, this);
    },

    /**
     * @private
     */
    removeComponent: function(comp) {
        Ext.Array.remove(this.items, comp);
    },

    /**
     * Executes this Action manually using the handler function specified in the original config
     * object or the handler function set with {@link #setHandler}. Any arguments passed to this
     * function will be passed on to the handler function.
     * @param {Object...} args Variable number of arguments passed to the handler function
     */
    execute: function() {
        this.initialConfig.handler.apply(this.initialConfig.scope || Ext.global, arguments);
    }
});
