/**
 * A mixin for components that need to interact with the keyboard. The primary config
 * for this class is the `{@link #keyMap keyMap}` config. This config is an object
 * with key names as its properties and with values that describe how the key event
 * should be handled.
 *
 * Key names may key name as documented in `Ext.event.Event`, numbers (which are treated
 * as `keyCode` values), single characters (for those that are not defined in
 * `Ext.event.Event`) or `charCode` values prefixed by '#' (e.g., "#65" for `charCode=65`).
 *
 * Entries that use a `keyCode` will be processed in a `keydown` event listener, while
 * those that use a `charCode` will be processed in `keypress`. This can be overridden
 * if the `keyMap` entry specifies an `event` property.
 *
 * Key names may be preceded by key modifiers. The modifier keys can be specified
 * by prepending the modifier name to the key name separated by `+` or `-` (e.g.,
 * "Ctrl+A" or "Ctrl-A"). Only one of these delimiters can be used in a given
 * entry.
 *
 * Valid modifier names are:
 *
 *  - Alt
 *  - Shift
 *  - Control (or "Ctrl" for short)
 *  - Command (or "Cmd" or "Meta")
 *  - CommandOrControl (or "CmdOrCtrl") for Cmd on Mac, Ctrl otherwise.
 *
 * *All these names are case insensitive and will be stored in upper case internally.*
 *
 * For example:
 *
 *      Ext.define('MyChartPanel', {
 *          extend: 'Ext.panel.Panel',
 *
 *          mixins: [
 *              'Ext.mixin.Keyboard'
 *          ],
 *
 *          controller: 'mycontroller',
 *
 *          // Map keys to methods (typically in a ViewController):
 *          keyMap: {
 *              ENTER: 'onEnterKey',
 *
 *              "ALT+PRINT_SCREEN": 'doScreenshot',
 *
 *              // Cmd on Mac OS X, Ctrl on Windows/Linux.
 *              "CmdOrCtrl+C": 'doCopy',
 *
 *              // This one is handled by a class method.
 *              ESC: {
 *                  handler: 'destroy',
 *                  scope: 'this',
 *                  event: 'keypress'  // default would be keydown
 *              },
 *
 *              "ALT+DOWN": 'openExpander',
 *
 *              // Match any key modifiers and invoke before any other DOWN keys
 *              // handlers with lower or default priority.
 *              "*+DOWN": {
 *                  handler: 'preprocessDownKey',
 *                  priority: 100
 *              }
 *          }
 *      });
 *
 * The method names are interpreted in the same way that event listener names are
 * interpreted.
 *
 * @since 6.2.0
 */
Ext.define('Ext.mixin.Keyboard', function(Keyboard) { return { // eslint-disable-line brace-style
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'keyboard'
    },

    /**
     * @property {Ext.event.Event} lastKeyMapEvent
     * The last key event processed is cached on the component for use in subsequent
     * event handlers.
     * @since 6.6.0
     */

    config: {
        /**
         * @cfg {Object} keyMap
         * An object containing handlers for keyboard events. The property names of this
         * object are the key name and any modifiers. The values of the properties are the
         * descriptors of how to handle each event.
         *
         * The handler descriptor can be simply the handler function(either the
         * literal function or the method name), or it can be an object with these
         * properties:
         *
         *  - `handler`: The function or its name to call to handle the event.
         *  - `scope`: The this pointer context (can be "this" or "controller").
         *  - `event`: An optional override of the key event to which to listen.
         *
         * **Important:** Calls to `setKeyMap` do not replace the entire `keyMap` but
         * instead update the provided mappings. That is, unless `null` is passed as the
         * value of the `keyMap` which will clear the `keyMap` of all entries.
         *
         * @cfg {String} [keyMap.scope] The default scope to apply to key handlers
         * which do not specify a scope. This is processed the same way as the scope of
         * {@link #cfg-listeners}. It defaults to the `"controller"`, but using `'this'`
         * means that an instance method will be used.
         */
        keyMap: {
            $value: null,
            cached: true,

            merge: function(value, baseValue, cls, mixin) {
                var ret, key, ucKey, v, vs;

                // Allow nulling out parent class config
                if (value === null) {
                    return value;
                }

                // We promote all values into objects but these objects do not get
                // merged with base class values. Further, the keys get toUpperCased
                // to normalize this aspect ('esc' vs 'ESC' vs 'Esc'). We do not want
                // to overwrite a class baseValue with an instances value since those
                // are additive (in applyKeyMap/combineKeyMaps).
                ret = (baseValue && !cls.isInstance) ? Ext.Object.chain(baseValue) : {};

                for (key in value) {
                    if (key !== 'scope') {
                        ucKey = key.toUpperCase();

                        if (!mixin || ret[ucKey] === undefined) {
                            // Promote to an object so we can always store the scope.
                            v = value[key];

                            if (v) {
                                if (typeof v === 'string' || typeof v === 'function') {
                                    v = {
                                        handler: v
                                    };
                                }
                                else {
                                    v = Ext.apply({
                                        handler: v.fn // overwritten by v.handler
                                    }, v);
                                }

                                vs = v.scope || value.scope || 'self';

                                v.scope = (vs === 'controller') ? 'self.controller' : vs;
                            }

                            ret[ucKey] = v;
                        }
                    }
                }

                return ret;
            }
        },

        /**
         * @cfg {Boolean} keyMapEnabled
         * Enables or disables processing keys in the `keyMap`. This value starts as
         * `null` and if it is `null` when `initKeyMap` is called, it will automatically
         * be set to `true`. Since `initKeyMap` is called by `Ext.Component` at the
         * proper time, this is not something application code normally handles.
         */
        keyMapEnabled: null
    },

    /**
     * @cfg {String} keyMapTarget
     * The name of the member that should be used to listen for keydown/keypress events.
     * This is intended to be controlled at the class level not per instance.
     * @protected
     */
    keyMapTarget: 'el',

    applyKeyMap: function(keyMap, existingKeyMap) {
        var me = this,
            // During cached config setup, we don't yet have our own (instance) "config"
            // so we can tell from that being present that we need our own keyMap.
            own = me.hasOwnProperty('config');

        if (own && existingKeyMap && existingKeyMap.$owner !== me) {
            // As a cached config, we can be created with an existing value, but
            // we do not want to modify that shared instance, so make a copy.
            existingKeyMap = Ext.apply({}, existingKeyMap);
        }

        keyMap = keyMap ? Keyboard.combineKeyMaps(existingKeyMap, keyMap, own && me) : null;

        if (me._keyMapReady) {
            me.setKeyMapListener(keyMap && me.getKeyMapEnabled());
        }

        return keyMap;
    },

    /**
     * This method should be called when the instance is ready to start listening for
     * keyboard events. This is called automatically for `Ext.Component` and derived
     * classes. This is done after the component is rendered.
     * @protected
     */
    initKeyMap: function() {
        var me = this,
            enabled = me.getKeyMapEnabled();

        me._keyMapReady = true;

        if (enabled === null) {
            me.setKeyMapEnabled(true);
        }
        else {
            me.setKeyMapListener(enabled && me.getKeyMap());
        }
    },

    disableKeyMapGroup: function(group) {
        this.setKeyMapGroupEnabled(group, false);
    },

    enableKeyMapGroup: function(group) {
        this.setKeyMapGroupEnabled(group, true);
    },

    setKeyMapGroupEnabled: function(group, state) {
        var me = this,
            disabledGroups = me.disabledKeyMapGroups || (me.disabledKeyMapGroups = {});

        disabledGroups[group] = !state;
    },

    updateKeyMapEnabled: function(enabled) {
        this.setKeyMapListener(enabled && this._keyMapReady && this.getKeyMap());
    },

    privates: {
        //<debug>
        _keyMapListenCount: 0,
        //</debug>
        _keyMapReady: false,

        // Descending priority sort
        comparePriorities: function(lhs, rhs) {
            return (rhs.priority || 0) - (lhs.priority || 0);
        },

        findKeyMapEntries: function(e) {
            var me = this,
                disabledGroups = me.disabledKeyMapGroups,
                keyMap = me.getKeyMap(),
                entries = keyMap && Keyboard.getKeyName(e),
                result = [],
                entry, len, i;

            entries = entries && keyMap[entries];

            if (entries) {
                // Ensure that the entries are in priority order
                if (!entries.sorted) {
                    Ext.Array.sort(entries, me.comparePriorities);
                    entries.sorted = true;
                }

                len = entries.length;

                for (i = 0; i < len; i++) {
                    entry = entries[i];

                    // If the key code and the modifier flags match, add entry
                    // to invocation list.
                    if (!disabledGroups || !disabledGroups[entry.group]) {
                        if (Keyboard.matchEntry(entry, e)) {
                            result.push(entry);
                        }
                    }
                }
            }

            return result;
        },

        onKeyMapEvent: function(e) {
            var me = this,
                entries = me.getKeyMapEnabled() ? me.findKeyMapEntries(e) : null,
                len = entries && entries.length,
                i, entry, result;

            me.lastKeyMapEvent = e;

            for (i = 0; i < len && result !== false; i++) {
                entry = entries[i];
                result = Ext.callback(entry.handler, entry.scope, [e, this], 0, this);
            }

            return result;
        },

        setKeyMapListener: function(enabled) {
            var me = this,
                listener = me._keyMapListener,
                eventSource;

            if (listener) {
                // We always destroy the old listener since the eventSource could be
                // different now...
                listener.destroy();
                listener = null;
            }

            if (enabled) {
                //<debug>
                ++me._keyMapListenCount;
                //</debug>

                if (enabled) {
                    eventSource = me[me.keyMapTarget];

                    if (typeof eventSource === 'function') {
                        eventSource = eventSource.call(me); // eg, 'getFocusEl'
                    }

                    listener = eventSource.on({
                        destroyable: true,
                        scope: me,
                        keydown: 'onKeyMapEvent',
                        keypress: 'onKeyMapEvent'
                    });
                }
            }

            me._keyMapListener = listener || null;
        },

        statics: {
            _charCodeRe: /^#([\d]+)$/,
            // eslint-disable-next-line max-len, no-useless-escape
            _keySpecRe: /^(?:(?:(\*)[\+\-])|(?:([a-z\+\-]*)[\+\-]))?(?:([a-z0-9_]+|[\+\-]|(?:#?\d+))(?:\:([a-z]+))?)$/i,
            _delimiterRe: /-|\+/,

            _keyMapEvents: {
                charCode: 'keypress',
                keyCode: 'keydown'
            },

            combineKeyMaps: function(existingKeyMap, keyMap, owner) {
                var defaultScope = keyMap.scope || 'controller',
                    entry, key, mapping, existingMapping;

                for (key in keyMap) {
                    if (key === 'scope') {
                        continue;
                    }

                    if (!(mapping = keyMap[key])) {
                        //<debug>
                        if (mapping === undefined) {
                            Ext.raise('keyMap entry "' + key + '" is undefined');
                        }
                        //</debug>

                        // if we have no mapping (eg, "ESC: null") and no mappings to
                        // overwrite, we can skip over it.
                        if (!existingKeyMap) {
                            continue;
                        }
                    }
                    else {
                        if (typeof mapping === 'string' || typeof mapping === 'function') {
                            // Direct calls to setKeyMap() can get here because
                            // instance and class configs go through merge
                            mapping = {
                                handler: mapping,
                                scope: defaultScope
                            };
                        }
                        else if (mapping) {
                            mapping = Ext.apply({
                                handler: mapping.fn, // mapping.handler will override
                                scope: defaultScope  // mapping.scope will override
                                // all other properties of mapping are kept
                            }, mapping);
                        }

                        existingKeyMap = existingKeyMap || {}; // we'll need a keyMap
                    }

                    if (Keyboard.parseEntry(key, entry = mapping || {})) {
                        // Key modifiers are stripped off the key name
                        // so we end up with an object like this:
                        //
                        //  "PRINT_SCREEN": {
                        //      handler: 'doSummat',
                        //      scope: 'controller',
                        //      altKey: true
                        //  }
                        //
                        // or
                        //
                        //  "UP": {
                        //      handler: 'doSummat'
                        //      scope: 'controller',
                        //      ignoreModifiers: true
                        //  }
                        //
                        existingMapping = existingKeyMap[entry.name];

                        if (existingMapping) {
                            if (owner && existingMapping.$owner !== owner) {
                                existingKeyMap[entry.name] = existingMapping =
                                    existingMapping.slice();
                                existingMapping.$owner = owner;
                            }

                            existingMapping.push(mapping);

                            existingMapping.sorted = false;
                        }
                        else {
                            existingMapping = existingKeyMap[entry.name] = [ mapping ];
                            existingMapping.$owner = owner;
                            existingMapping.sorted = true;
                        }
                    }
                    //<debug>
                    else {
                        Ext.raise('Invalid keyMap key specification "' + key + '"');
                    }
                    //</debug>
                }

                if (existingKeyMap && owner) {
                    existingKeyMap.$owner = owner;
                }

                return existingKeyMap || null;
            },

            getKeyName: function(event) {
                var keyCode;

                if (event.isEvent) {
                    keyCode = event.keyCode || event.charCode;
                    event = event.browserEvent;

                    // If it's the combination code, 229, then use the W3C code property.
                    // https://developer.mozilla.org/en/docs/Web/API/KeyboardEvent/code
                    if (keyCode === 229 && 'code' in event) {
                        if (Ext.String.startsWith(event.code, 'Key')) {
                            return event.key.substr(3);
                        }

                        if (Ext.String.startsWith(event.code, 'Digit')) {
                            return event.key.substr(5);
                        }
                    }
                }
                else {
                    keyCode = event;
                }

                // We are in a position of having a numeric key code, attempt to translate it
                // to a name.
                return Ext.event.Event.keyCodes[keyCode] || String.fromCharCode(keyCode);
            },

            matchEntry: function(entry, e) {
                var ev = e.browserEvent,
                    code;

                if (e.type !== entry.event) {
                    return false;
                }

                if (!(code = entry.charCode)) {
                    if (entry.keyCode !== e.keyCode ||
                        (!entry.ignoreModifiers && !entry.shiftKey !== !ev.shiftKey)) {
                        // when using keyCode, SHIFT must match too
                        return false;
                    }
                }
                else if (e.getCharCode() !== code) {
                    return false;
                }

                // NOTE: All modifier key properties are !-ed to ensure boolean-ness since
                // they can be undefined...
                // Entry can be flagged to ignore modifiers and invoke purely on key match.
                return entry.ignoreModifiers ||
                        (!entry.ctrlKey === !ev.ctrlKey &&
                         !entry.altKey === !ev.altKey &&
                         !entry.metaKey === !ev.metaKey &&
                         !entry.shiftKey === !ev.shiftKey);
            },

            parseEntry: function(key, entry) {
                key = key.toUpperCase();

                // eslint-disable-next-line vars-on-top
                var me = this,
                    Event = Ext.event.Event,
                    keyFlags = Event.keyFlags,
                    parts = me._keySpecRe.exec(key),
                    type = 'keyCode',
                    name, code, i, match, n;

                // The _keySpecRe will split up a string thus:
                //
                // 'ALT+CTRL+A:GROUP' -> [.., undefined, "ALT+CTRL", "A", "GROUP"]
                //
                // '*+A:GROUP' -> [.., "*", undefined, "A", "GROUP"]
                //
                // 'ALT+CTRL+A' -> [.., undefined, "ALT+CTRL", "A", undefined]
                //
                // So parts is:
                // [0] - Whole matched string
                // [1] - All modifiers indicator, ie: '*'
                // [2] - Delimited modifiers list, eg: 'ctrl+alt'
                // [3] - The key name
                // [4] - The optional group name

                if (parts) {
                    name = parts[3];

                    if (parts[4]) {
                        entry.group = parts[4];
                    }

                    // If "*" modifier used, then means ignore modifiers and invoke
                    // on raw key match.
                    if (!(entry.ignoreModifiers = !!parts[1]) && parts[2]) {
                        // Otherwise set flags according to modifer names if any.
                        parts = parts[2].split(me._delimiterRe);
                        n = parts.length;

                        for (i = 0; i < n; i++) {
                            //<debug>
                            if (!keyFlags[parts[i]]) {
                                return false;
                            }
                            //</debug>

                            entry[keyFlags[parts[i]]] = true;
                        }
                    }

                    // Entry is named by the unmodified key name.
                    // Entries for the same key are kept as a prioritized array.
                    entry.name = name;

                    // Set the keyCode from the 'PRINT_SCREEN' key name.
                    if (isNaN(code = Event[name])) {
                        // Support charCode from a single letter or '#65' format.
                        if (!(match = me._charCodeRe.exec(name))) {
                            if (name.length === 1) {
                                code = name.charCodeAt(0);
                            }
                        }
                        else {
                            code = +match[1]; // #42
                        }

                        if (code) {
                            type = 'charCode';
                        }
                        else {
                            // Last chance! Just a number (keyCode) like "27: 'onEscape'"?
                            code = +name;
                        }

                        entry.name = Keyboard.getKeyName(code);
                    }

                    entry.event = entry.event || me._keyMapEvents[type];

                    return !isNaN(code) && (entry[type] = code);
                }
            }
        } // statics
    } // privates
};
});
