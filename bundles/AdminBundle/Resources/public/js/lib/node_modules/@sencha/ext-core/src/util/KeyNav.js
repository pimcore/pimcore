/**
 * Provides a convenient wrapper for normalized keyboard navigation. KeyNav allows you to bind
 * navigation keys to function calls that will get called when the keys are pressed, providing
 * an easy way to implement custom navigation schemes for any UI component.
 *
 * The following are all of the possible keys that can be implemented: Enter, Space, Left, Right,
 * Up, Down, Tab, Esc, Page Up, Page Down, Delete, Backspace, Home, End.
 *
 * Usage:
 *
 *      var nav = new Ext.util.KeyNav({
 *          target: "my-element",
 *          
 *          left: function(e) {
 *              this.moveLeft(e.ctrlKey);
 *          },
 *          right: function(e) {
 *              this.moveRight(e.ctrlKey);
 *          },
 *          enter: function(e) {
 *              this.save();
 *          },
 *          
 *          // Binding may be a function specifiying fn, scope and defaultEventAction
 *          esc: {
 *              fn: this.onEsc,
 *              defaultEventAction: false
 *          },
 *          
 *          // Binding may be keyed by a single character
 *          A: {
 *              ctrl: true,
 *              fn: selectAll
 *          },
 *          
 *          // Binding may be keyed by a key code (45 = INSERT)
 *          45: {
 *              fn: doInsert
 *          },
 *          
 *          scope: myObject
 *     });
 */
Ext.define('Ext.util.KeyNav', {
    alternateClassName: 'Ext.KeyNav',

    requires: ['Ext.util.KeyMap'],

    /**
     * @cfg {Boolean} disabled
     * True to disable this KeyNav instance.
     */
    disabled: false,

    /**
     * @cfg {String} [defaultEventAction=false]
     * The method to call on the {@link Ext.event.Event} after this KeyNav intercepts a key.
     * Valid values are {@link Ext.event.Event#stopEvent}, {@link Ext.event.Event#preventDefault}
     * and {@link Ext.event.Event#stopPropagation}.
     *
     * If a falsy value is specified, no method is called on the key event.
     */
    defaultEventAction: false,

    /**
     * @cfg {Boolean} forceKeyDown
     *
     * Handle the keydown event instead of keypress. KeyNav automatically does this for IE
     * since IE does not propagate special keys on keypress, but setting this to true will force
     * other browsers to also handle keydown instead of keypress.
     */
    forceKeyDown: false,

    /**
     * @cfg {Ext.Component/Ext.dom.Element/HTMLElement/String} target
     * The object on which to listen for the event specified by the {@link #eventName}
     * config option.
     */

    /**
     * @cfg {String} eventName
     * The event to listen for to pick up key events.
     */
    eventName: 'keypress',

    /**
     * @cfg {Function} processEvent
     * An optional event processor function which accepts the argument list provided by the
     * {@link #eventName configured event} of the {@link #target}, and returns a keyEvent
     * for processing by the KeyMap.
     *
     * This may be useful when the {@link #target} is a Component with s complex event signature.
     * Extra information from the event arguments may be injected into the event for use
     * by the handler functions before returning it.
     */

    /**
     * @cfg {Object} [processEventScope=this]
     * The scope (`this` context) in which the {@link #processEvent} method is executed.
     */

    /**
     * @cfg {Boolean} [ignoreInputFields=false]
     * Configure this as `true` if there are any input fields within the {@link #target}, and this
     * KeyNav should not process events from input fields (`<input>`, `<textarea>` and elements
     * with `contentEditable="true"`)
     */

    /**
     * @cfg {Ext.util.KeyMap} [keyMap]
     * An optional pre-existing {@link Ext.util.KeyMap KeyMap} to use to listen for key events.
     * If not specified, one is created.
     */

    /**
     * @property {Ext.event.Event} lastKeyEvent
     * The last key event that this KeyMap handled.
     */

    /**
     * @cfg {Number} [priority]
     * The priority to set on this KeyNav's listener. Listeners with a higher priority are fired
     * before those with lower priority.
     */

    statics: {
        keyOptions: {
            left: 37,
            right: 39,
            up: 38,
            down: 40,
            space: 32,
            pageUp: 33,
            pageDown: 34,
            del: 46,
            backspace: 8,
            home: 36,
            end: 35,
            enter: 13,
            esc: 27,
            tab: 9
        }
    },

    constructor: function(config) {
        var me = this,
            keymapCfg, map;

        //<debug>
        if (arguments.length === 2) {
            Ext.raise("2-argument KeyNav constructor is removed. Use a config object instead.");
        }
        //</debug>

        config = config || {};

        keymapCfg = {
            target: config.target,
            ignoreInputFields: config.ignoreInputFields,
            eventName: me.getKeyEvent(
                'forceKeyDown' in config ? config.forceKeyDown : me.forceKeyDown,
                config.eventName
            ),
            capture: config.capture
        };

        if (me.map) {
            me.map.destroy();
        }

        // Ensure config system configs are set
        me.initConfig(config);

        if (config.processEvent) {
            keymapCfg.processEvent = config.processEvent;
            keymapCfg.processEventScope = config.processEventScope || me;
        }

        if (config.priority) {
            keymapCfg.priority = config.priority;
        }

        // If they specified a KeyMap to use, use it
        if (config.keyMap) {
            map = me.map = config.keyMap;
        }
        // Otherwise, create one, and remember to destroy it on destroy
        else {
            map = me.map = new Ext.util.KeyMap(keymapCfg);
            me.destroyKeyMap = true;
        }

        me.addBindings(config);

        map.disable();

        if (!config.disabled) {
            map.enable();
        }
    },

    addBindings: function(bindings) {
        var me = this,
            map = me.map,
            keyCodes = Ext.util.KeyNav.keyOptions,
            Event = Ext.event.Event,
            defaultScope = bindings.scope || me,
            binding, keyName, keyCode;

        for (keyName in bindings) {
            binding = bindings[keyName];

            // There is a property named after a key name.
            // It may be a function or an binding spec containing handler, scope and
            // defaultEventAction configs
            // Allow { A: { ctrl: true, handler: onCtrlA } }
            // Allow { 45: doInsert } to use key codes directly
            // Allow { F2: onKeyF2 }
            keyCode = keyName.length === 1
                ? keyName.charCodeAt(0)
                : (keyCodes[keyName] || Event[keyName.toUpperCase()]);

            if (keyCode != null) {
                keyName = keyCode;
            }

            if (binding && (keyName.length === 1 || !isNaN(keyName = parseInt(keyName, 10)))) {
                if (typeof binding === 'function') {
                    binding = {
                        handler: binding,
                        defaultEventAction: (bindings.defaultEventAction !== undefined)
                            ? bindings.defaultEventAction
                            : me.defaultEventAction
                    };
                }

                map.addBinding({
                    key: keyName,
                    ctrl: binding.ctrl,
                    shift: binding.shift,
                    alt: binding.alt,
                    handler: Ext.Function.bind(me.handleEvent, binding.scope || defaultScope,
                                               [binding.handler || binding.fn, me], true),
                    defaultEventAction: (binding.defaultEventAction !== undefined)
                        ? binding.defaultEventAction
                        : me.defaultEventAction
                });
            }
        }
    },

    /**
     * Method for filtering out the map argument
     * @private
     * @param {Number} keyCode
     * @param {Ext.event.Event} event
     * @param {Function} handler The function to call
     * @param {Ext.util.KeyNav} keyNav The owning KeyNav
     */
    handleEvent: function(keyCode, event, handler, keyNav) {
        keyNav.lastKeyEvent = event;

        return handler.call(this, event);
    },

    /**
     * Destroy this KeyNav.
     * @param {Boolean} removeEl Pass `true` to remove the element associated with this KeyNav.
     */
    destroy: function(removeEl) {
        var me = this;

        if (removeEl) {
            Ext.raise("removeEl argument in KeyNav destructor is not supported anymore.");
        }

        if (me.destroyKeyMap) {
            me.map.destroy(removeEl);
        }

        me.map = null;

        me.callParent();
    },

    /**
     * Enables this KeyNav.
     */
    enable: function() {
        // this.map will be removed if destroyed
        if (this.map) {
            this.map.enable();
            this.disabled = false;
        }
    },

    /**
     * Disables this KeyNav.
     */
    disable: function() {
        // this.map will be removed if destroyed
        if (this.map) {
            this.map.disable();
        }

        this.disabled = true;
    },

    /**
     * Convenience function for setting disabled/enabled by boolean.
     * @param {Boolean} disabled
     */
    setDisabled: function(disabled) {
        this.map.setDisabled(disabled);
        this.disabled = disabled;
    },

    isEnabled: function() {
        return !this.disabled;
    },

    /**
     * @private
     * Determines the event to bind to listen for keys. Defaults to the {@link #eventName} value,
     * but may be overridden the {@link #forceKeyDown} setting.
     *
     * @return {String} The type of event to listen for.
     */
    getKeyEvent: function(forceKeyDown, configuredEventName) {
        if (forceKeyDown || (Ext.supports.SpecialKeyDownRepeat && !configuredEventName)) {
            return 'keydown';
        }
        else {
            return configuredEventName || this.eventName;
        }
    }
});
