// @tag class
/**
 * @class Ext.Base
 *
 * The root of all classes created with {@link Ext#define}.
 *
 * Ext.Base is the building block of all Ext classes. All classes in Ext inherit from Ext.Base.
 * All prototype and static members of this class are inherited by all other classes.
 */
Ext.Base = (function(flexSetter) {
// @define Ext.Base
// @require Ext.Util
// @require Ext.Version
// @require Ext.Configurator
// @uses Ext.ClassManager
// @uses Ext.mixin.Watchable

/* eslint-disable indent */
var noArgs = [],
    baseStaticMember,
    baseStaticMembers = [],
    //<debug>
    makeDeprecatedMethod = function(oldName, newName, msg) {
        var message = '"' + oldName + '" is deprecated.';

        if (msg) {
            message += ' ' + msg;
        }
        else if (newName) {
            message += ' Please use "' + newName + '" instead.';
        }

        return function() {
            Ext.raise(message);
        };
    },
    addDeprecatedProperty = function(object, oldName, newName, message) {
        if (!message) {
            message = '"' + oldName + '" is deprecated.';
        }

        if (newName) {
            message += ' Please use "' + newName + '" instead.';
        }

        if (message) {
            Ext.Object.defineProperty(object, oldName, {
                get: function() { // eslint-disable-line getter-return
                    Ext.raise(message);
                },
                set: function(value) {
                    Ext.raise(message);
                },
                configurable: true
            });
        }
    },
    //</debug>
    getOwnObject = function(proto, name) {
        if (!proto.hasOwnProperty(name)) {
            proto[name] = Ext.Object.chain(getOwnObject(proto.superclass, name));
        }

        return proto[name];
    },
    makeAliasFn = function(name) {
        return function() {
            return this[name].apply(this, arguments);
        };
    },
    Version = Ext.Version,
    leadingDigitRe = /^\d/,
    oneMember = {},
    aliasOneMember = {},
    Base = function() {},
    BasePrototype = Base.prototype,
    Reaper;

    Ext.Reaper = Reaper = {
        delay: 100,
        queue: [],
        timer: null,

        add: function(obj) {
            if (!Reaper.timer) {
                Reaper.timer = Ext.defer(Reaper.tick, Reaper.delay);
            }

            Reaper.queue.push(obj);
        },

        flush: function() {
            if (Reaper.timer) {
                Ext.undefer(Reaper.timer);
                Reaper.timer = null;
            }

            /* eslint-disable-next-line vars-on-top */
            var queue = Reaper.queue,
                n = queue.length,
                i, obj;

            Reaper.queue = [];

            for (i = 0; i < n; ++i) {
                obj = queue[i];

                if (obj && obj.$reap) {
                    obj.$reap();
                }
            }
        },

        tick: function() {
            Reaper.timer = null;
            Reaper.flush();
        }
    };

    // These static properties will be copied to every newly created class with {@link Ext#define}
    Ext.apply(Base, {
        $className: 'Ext.Base',

        $isClass: true,

        /**
         * Create a new instance of this Class.
         *
         *     Ext.define('My.cool.Class', {
         *         ...
         *     });
         *
         *     My.cool.Class.create({
         *         someConfig: true
         *     });
         *
         * All parameters are passed to the constructor of the class.
         *
         * @return {Object} the created instance.
         * @static
         * @inheritable
         */
        create: function() {
            return Ext.create.apply(Ext, [this].concat(Array.prototype.slice.call(arguments, 0)));
        },

        addConfigTransform: function(methodName, priority) {
            var transforms = getOwnObject(this.prototype, '$configTransforms');

            //<debug>
            if (this.$configTransforms) {
                Ext.raise('Config transforms cannot be added after instances are created');
            }
            //</debug>

            transforms[methodName] = priority;
        },

        /**
         * This method applies a versioned, deprecation declaration to this class. This
         * is typically called by the `deprecated` config.
         * @private
         */
        addDeprecations: function(deprecations) {
            var me = this,
                all = [],
                compatVersion = Ext.getCompatVersion(deprecations.name),
                //<debug>
                configurator = me.getConfigurator(),
                displayName = (me.$className || '') + '#',
                //</debug>
                deprecate, versionSpec, index, message, target,
                enabled, existing, fn, names, oldName, newName, member, statics, version;

            for (versionSpec in deprecations) {
                if (leadingDigitRe.test(versionSpec)) {
                    version = new Ext.Version(versionSpec);
                    version.deprecations = deprecations[versionSpec];
                    all.push(version);
                }
            }

            all.sort(Version.compare);

            for (index = all.length; index--;) {
                deprecate = (version = all[index]).deprecations;
                target = me.prototype;
                statics = deprecate.statics;

                // If user specifies, say 4.2 compatibility and we have a 5.0 deprecation
                // then that block needs to be "enabled" to "revert" to behaviors prior
                // to 5.0. By default, compatVersion === currentVersion, so there are no
                // enabled blocks. In dev mode we still want to visit all the blocks and
                // possibly add shims to detect use of deprecated methods, but in a build
                // (if the deprecated block remains somehow) we just break the loop.
                enabled = compatVersion && compatVersion.lt(version);

                //<debug>
                if (!enabled) {} else // eslint-disable-line no-empty, brace-style
                //</debug>
                if (!enabled) {
                    // we won't get here in dev mode when !enabled
                    break;
                }

                while (deprecate) {
                    names = deprecate.methods;

                    if (names) {
                        for (oldName in names) {
                            member = names[oldName];
                            fn = null;

                            if (!member) {
                                /*
                                 * Something like:
                                 *
                                 *      '5.1': {
                                 *          methods: {
                                 *              removedMethod: null
                                 *          }
                                 *      }
                                 *
                                 * Since there is no recovering the method, we always put
                                 * on a shim to catch abuse.
                                 */

                                //<debug>
                                // The class should not already have a method by the oldName
                                Ext.Assert.isNotDefinedProp(target, oldName);

                                fn = makeDeprecatedMethod(displayName + oldName);
                                //</debug>
                            }
                            else if (Ext.isString(member)) {
                                /*
                                 * Something like:
                                 *
                                 *      '5.1': {
                                 *          methods: {
                                 *              oldName: 'newName'
                                 *          }
                                 *      }
                                 *
                                 * If this block is enabled, we just put an alias in place.
                                 * Otherwise we need to inject a
                                 */

                                //<debug>
                                // The class should not already have a method by the oldName
                                Ext.Assert.isNotDefinedProp(target, oldName);
                                Ext.Assert.isDefinedProp(target, member);
                                //</debug>

                                if (enabled) {
                                    // This call to the real method name must be late
                                    // bound if it is to pick up overrides and such.
                                    fn = makeAliasFn(member);
                                }
                                //<debug>
                                else {
                                    fn = makeDeprecatedMethod(displayName + oldName, member);
                                }
                                //</debug>
                            }
                            else {
                                /*
                                 * Something like:
                                 *
                                 *      '5.1': {
                                 *          methods: {
                                 *              foo: function() { ... }
                                 *          }
                                 *      }
                                 *
                                 * Or this:
                                 *
                                 *      '5.1': {
                                 *          methods: {
                                 *              foo: {
                                 *                  fn: function() { ... },
                                 *                  message: 'Please use "bar" instead.'
                                 *              }
                                 *          }
                                 *      }
                                 *
                                 * Or just this:
                                 *
                                 *      '5.1': {
                                 *          methods: {
                                 *              foo: {
                                 *                  message: 'Use something else instead.'
                                 *              }
                                 *          }
                                 *      }
                                 *
                                 * If this block is enabled, and "foo" is an existing
                                 * method, than we apply the given method as an override.
                                 * If "foo" is not existing, we simply add the method.
                                 *
                                 * If the block is not enabled and there is no existing
                                 * method by that name, than we add a shim to prevent
                                 * abuse.
                                 */
                                message = '';

                                if (member.message || member.fn) {
                                    //<debug>
                                    message = member.message;
                                    //</debug>
                                    member = member.fn;
                                }

                                existing = target.hasOwnProperty(oldName) && target[oldName];

                                if (enabled && member) {
                                    member.$owner = me;
                                    member.$name = oldName;
                                    //<debug>
                                    member.name = displayName + oldName;
                                    //</debug>

                                    if (existing) {
                                        member.$previous = existing;
                                    }

                                    fn = member;
                                }
                                //<debug>
                                else if (!existing) {
                                    fn = makeDeprecatedMethod(displayName + oldName, null,
                                                              message);
                                }
                                //</debug>
                            }

                            if (fn) {
                                target[oldName] = fn;
                            }
                        } // for oldName
                    }

                    //-------------------------------------
                    // Debug only
                    //<debug>

                    names = deprecate.configs;

                    if (names) {
                        //
                        //  '6.0': {
                        //      configs: {
                        //          dead: null,
                        //
                        //          renamed: 'newName',
                        //
                        //          removed: {
                        //              message: 'This config was replaced by pixie dust'
                        //          }
                        //      }
                        //  }
                        //
                        configurator.addDeprecations(names);
                    }

                    names = deprecate.properties;

                    if (names && !enabled) {
                        // For properties about the only thing we can do is (on Good
                        // Browsers), add warning shims for accessing them. So if the
                        // block is enabled, we don't want those.
                        for (oldName in names) {
                            newName = names[oldName];

                            if (Ext.isString(newName)) {
                                addDeprecatedProperty(target, displayName + oldName, newName);
                            }
                            else if (newName && newName.message) {
                                addDeprecatedProperty(target, displayName + oldName, null,
                                                      newName.message);
                            }
                            else {
                                addDeprecatedProperty(target, displayName + oldName);
                            }
                        }
                    }

                    //</debug>
                    //-------------------------------------

                    // reset to handle statics and apply them to the class
                    deprecate = statics;
                    statics = null;
                    target = me;
                }
            }
        },

        /**
         * @private
         * @static
         * @inheritable
         * @param parentClass
         */
        extend: function(parentClass) {
            var me = this,
                parentPrototype = parentClass.prototype,
                prototype, name, statics;

            prototype = me.prototype = Ext.Object.chain(parentPrototype);
            prototype.self = me;

            me.superclass = prototype.superclass = parentPrototype;

            if (!parentClass.$isClass) {
                for (name in BasePrototype) {
                    if (name in prototype) {
                        prototype[name] = BasePrototype[name];
                    }
                }
            }

            //<feature classSystem.inheritableStatics>
            // Statics inheritance
            statics = parentPrototype.$inheritableStatics;

            if (statics) {
                for (name in statics) {
                    if (!me.hasOwnProperty(name)) {
                        me[name] = parentClass[name];
                    }
                }
            }
            //</feature>

            if (parentClass.$onExtended) {
                me.$onExtended = parentClass.$onExtended.slice();
            }

            //<feature classSystem.config>
            me.getConfigurator();
            //</feature>
        },

        /**
         * @private
         * @static
         * @inheritable
         */
        $onExtended: [],

        /**
         * @private
         * @static
         * @inheritable
         */
        triggerExtended: function() {
            //<debug>
            if (Ext.classSystemMonitor) {
                Ext.classSystemMonitor(this, 'Ext.Base#triggerExtended', arguments);
            }
            //</debug>

            /* eslint-disable-next-line vars-on-top */
            var callbacks = this.$onExtended,
                ln = callbacks.length,
                i, callback;

            if (ln > 0) {
                for (i = 0; i < ln; i++) {
                    callback = callbacks[i];
                    callback.fn.apply(callback.scope || this, arguments);
                }
            }
        },

        /**
         * @private
         * @static
         * @inheritable
         */
        onExtended: function(fn, scope) {
            this.$onExtended.push({
                fn: fn,
                scope: scope
            });

            return this;
        },

        /**
         * Add / override static properties of this class.
         *
         *     Ext.define('My.cool.Class', {
         *         ...
         *     });
         *
         *     My.cool.Class.addStatics({
         *         someProperty: 'someValue',      // My.cool.Class.someProperty = 'someValue'
         *         method1: function() { ... },    // My.cool.Class.method1 = function() { ... };
         *         method2: function() { ... }     // My.cool.Class.method2 = function() { ... };
         *     });
         *
         * @param {Object} members
         * @return {Ext.Base} this
         * @static
         * @inheritable
         */
        addStatics: function(members) {
            this.addMembers(members, true);

            return this;
        },

        /**
         * @private
         * @static
         * @inheritable
         * @param {Object} members
         */
        addInheritableStatics: function(members) {
            var me = this,
                proto = me.prototype,
                inheritableStatics = me.$inheritableStatics,
                name, member, current;

            if (!inheritableStatics) {
                inheritableStatics = Ext.apply({}, proto.$inheritableStatics);
                me.$inheritableStatics = proto.$inheritableStatics = inheritableStatics;
            }

            //<debug>
            /* eslint-disable-next-line vars-on-top */
            var className = Ext.getClassName(me) + '.';
            //</debug>

            for (name in members) {
                if (members.hasOwnProperty(name)) {
                    member = members[name];
                    current = me[name];

                    //<debug>
                    if (typeof member === 'function') {
                        member.name = className + name;
                    }
                    //</debug>

                    if (typeof current === 'function' && !current.$isClass && !current.$nullFn) {
                        member.$previous = current;
                    }

                    me[name] = member;
                    inheritableStatics[name] = true;
                }
            }

            return me;
        },

        /**
         * Add methods / properties to the prototype of this class.
         *
         *     Ext.define('My.awesome.Cat', {
         *         constructor: function() {
         *             ...
         *         }
         *     });
         *
         *      My.awesome.Cat.addMembers({
         *          meow: function() {
         *             alert('Meowww...');
         *          }
         *      });
         *
         *      var kitty = new My.awesome.Cat();
         *      kitty.meow();
         *
         * @param {Object} members The members to add to this class.
         * @param {Boolean} [isStatic=false] Pass `true` if the members are static.
         * @param {Boolean} [privacy=false] Pass `true` if the members are private. This
         * only has meaning in debug mode and only for methods.
         * @static
         * @inheritable
         */
        addMembers: function(members, isStatic, privacy) {
            var me = this, // this class
                cloneFunction = Ext.Function.clone,
                target = isStatic ? me : me.prototype,
                defaultConfig = !isStatic && target.defaultConfig,
                enumerables = Ext.enumerables,
                privates = members.privates,
                configs, i, ln, member, name, subPrivacy, privateStatics;

            //<debug>
            /* eslint-disable-next-line vars-on-top, one-var */
            var displayName = (me.$className || '') + '#';
            //</debug>

            if (privates) {
                // This won't run for normal class private members but will pick up all
                // others (statics, overrides, etc).
                delete members.privates;

                if (!isStatic) {
                    privateStatics = privates.statics;
                    delete privates.statics;
                }

                //<debug>
                subPrivacy = privates.privacy || privacy || 'framework';
                //</debug>

                me.addMembers(privates, isStatic, subPrivacy);

                if (privateStatics) {
                    me.addMembers(privateStatics, true, subPrivacy);
                }
            }

            for (name in members) {
                if (members.hasOwnProperty(name)) {
                    member = members[name];

                    //<debug>
                    if (privacy === true) {
                        privacy = 'framework';
                    }

                    if (member && member.$nullFn && privacy !== member.$privacy) {
                        Ext.raise('Cannot use stock function for private method ' +
                            (me.$className ? me.$className + '#' : '') + name);
                    }
                    //</debug>

                    if (typeof member === 'function' && !member.$isClass && !member.$nullFn) {
                        if (member.$owner) {
                            member = cloneFunction(member);
                        }

                        if (target.hasOwnProperty(name)) {
                            member.$previous = target[name];
                        }

                        // This information is needed by callParent() and callSuper() as
                        // well as statics() and even Ext.fly().
                        member.$owner = me;
                        member.$name = name;

                        //<debug>
                        member.name = displayName + name;

                        /* eslint-disable-next-line vars-on-top */
                        var existing = target[name];

                        if (privacy) {
                            member.$privacy = privacy;

                            // The general idea here is that an existing, non-private
                            // method can be marked private. This is because the other
                            // way is strictly forbidden (private method going public)
                            // so if a method is in that gray area it can only be made
                            // private in doc form which allows a derived class to make
                            // it public.
                            if (existing && existing.$privacy && existing.$privacy !== privacy) {
                                Ext.privacyViolation(me, existing, member, isStatic);
                            }
                        }
                        else if (existing && existing.$privacy) {
                            Ext.privacyViolation(me, existing, member, isStatic);
                        }
                        //</debug>
                    // The last part of the check here resolves a conflict if we have the same
                    // property declared as both a config and a member on the class so that
                    // the config wins.
                    }
                    else if (defaultConfig && (name in defaultConfig) &&
                             !target.config.hasOwnProperty(name)) {
                        // This is a config property so it must be added to the configs
                        // collection not just smashed on the prototype...
                        (configs || (configs = {}))[name] = member;

                        continue;
                    }

                    target[name] = member;
                }
            }

            if (configs) {
                // Add any configs found in the normal members arena:
                me.addConfig(configs);
            }

            if (enumerables) {
                for (i = 0, ln = enumerables.length; i < ln; ++i) {
                    if (members.hasOwnProperty(name = enumerables[i])) {
                        member = members[name];

                        // The enumerables are all functions...
                        if (member && !member.$nullFn) {
                            if (member.$owner) {
                                member = cloneFunction(member);
                            }

                            member.$owner = me;
                            member.$name = name;
                            //<debug>
                            member.name = displayName + name;
                            //</debug>

                            if (target.hasOwnProperty(name)) {
                                member.$previous = target[name];
                            }
                        }

                        target[name] = member;
                    }
                }
            }

            return this;
        },

        /**
         * @private
         * @static
         * @inheritable
         * @param name
         * @param member
         * @param privacy
         */
        addMember: function(name, member, privacy) {
            oneMember[name] = member;
            this.addMembers(oneMember, false, privacy);
            delete oneMember[name];

            return this;
        },

        hookMember: function(name, member) {
            var existing = this.prototype[name];

            return this.addMember(name, member, existing && existing.$privacy);
        },

        /**
         * Borrow another class' members to the prototype of this class.
         *
         *     Ext.define('Bank', {
         *         money: '$$$',
         *         printMoney: function() {
         *             alert('$$$$$$$');
         *         }
         *     });
         *
         *     Ext.define('Thief', {
         *         ...
         *     });
         *
         *     Thief.borrow(Bank, ['money', 'printMoney']);
         *
         *     var steve = new Thief();
         *
         *     alert(steve.money); // alerts '$$$'
         *     steve.printMoney(); // alerts '$$$$$$$'
         *
         * @param {Ext.Base} fromClass The class to borrow members from
         * @param {Array/String} members The names of the members to borrow
         * @return {Ext.Base} this
         * @static
         * @inheritable
         * @private
         */
        borrow: function(fromClass, members) {
            //<debug>
            if (Ext.classSystemMonitor) {
                Ext.classSystemMonitor(this, 'Ext.Base#borrow', arguments);
            }
            //</debug>

            /* eslint-disable-next-line vars-on-top */
            var prototype = fromClass.prototype,
                membersObj = {},
                i, ln, name;

            members = Ext.Array.from(members);

            for (i = 0, ln = members.length; i < ln; i++) {
                name = members[i];
                membersObj[name] = prototype[name];
            }

            return this.addMembers(membersObj);
        },

        /**
         * Override members of this class. Overridden methods can be invoked via
         * {@link Ext.Base#method!callParent}.
         *
         *     Ext.define('My.Cat', {
         *         constructor: function() {
         *             alert("I'm a cat!");
         *         }
         *     });
         *
         *     My.Cat.override({
         *         constructor: function() {
         *             alert("I'm going to be a cat!");
         *
         *             this.callParent(arguments);
         *
         *             alert("Meeeeoooowwww");
         *         }
         *     });
         *
         *     var kitty = new My.Cat(); // alerts "I'm going to be a cat!"
         *                               // alerts "I'm a cat!"
         *                               // alerts "Meeeeoooowwww"
         *
         * Direct use of this method should be rare. Use {@link Ext#define Ext.define}
         * instead:
         *
         *     Ext.define('My.CatOverride', {
         *         override: 'My.Cat',
         *         constructor: function() {
         *             alert("I'm going to be a cat!");
         *
         *             this.callParent(arguments);
         *
         *             alert("Meeeeoooowwww");
         *         }
         *     });
         *
         * The above accomplishes the same result but can be managed by the {@link Ext.Loader}
         * which can properly order the override and its target class and the build process
         * can determine whether the override is needed based on the required state of the
         * target class (My.Cat).
         *
         * @param {Object} members The properties to add to this class. This should be
         * specified as an object literal containing one or more properties.
         * @return {Ext.Base} this class
         * @static
         * @inheritable
         */
        override: function(members) {
            var me = this,
                statics = members.statics,
                inheritableStatics = members.inheritableStatics,
                config = members.config,
                mixins = members.mixins,
                cachedConfig = members.cachedConfig;

            if (statics || inheritableStatics || config) {
                members = Ext.apply({}, members);
            }

            if (statics) {
                me.addMembers(statics, true);
                delete members.statics;
            }

            if (inheritableStatics) {
                me.addInheritableStatics(inheritableStatics);
                delete members.inheritableStatics;
            }

            if (members.platformConfig) {
                me.addPlatformConfig(members);
            }

            if (config) {
                me.addConfig(config);
                delete members.config;
            }

            if (cachedConfig) {
                me.addCachedConfig(cachedConfig);
                delete members.cachedConfig;
            }

            delete members.mixins;

            me.addMembers(members);

            if (mixins) {
                me.mixin(mixins);
            }

            return me;
        },

        addPlatformConfig: function(data) {
            var me = this,
                prototype = me.prototype,
                platformConfigs = data.platformConfig,
                added, classConfigs, configs, configurator, keys, name, value, i, ln;

            delete prototype.platformConfig;

            //<debug>
            if (platformConfigs instanceof Array) {
                throw new Error('platformConfigs must be specified as an object.');
            }
            //</debug>

            configurator = me.getConfigurator();
            classConfigs = configurator.configs;

            // Get the keys shortest to longest (ish).
            keys = Ext.getPlatformConfigKeys(platformConfigs);

            // To leverage the Configurator#add method, we want to generate potentially
            // two objects to pass in: "added" and "hoisted". For any properties in an
            // active platformConfig rule that set proper Configs in the base class, we
            // need to put them in "added". If instead of the proper Config coming from
            // a base class, it comes from this class's config block, we still need to
            // put that config in "added" but we also need move the class-level config
            // out of "config" and into "hoisted".
            //
            // This will ensure that the config defined at the class level is added to
            // the Configurator first.
            for (i = 0, ln = keys.length; i < ln; ++i) {
                configs = platformConfigs[keys[i]];
                added = null;

                for (name in configs) {
                    value = configs[name];

                    // We have a few possibilities for each config name:
                    if (name in classConfigs) {
                        //  It is a proper Config defined by a base class.
                        (added || (added = {}))[name] = value;
                    }
                    else {
                        //  It is just a property to put on the prototype.
                        prototype[name] = value;
                    }
                }

                if (added) {
                    configurator.add(added);
                }
            }
        },

        /**
         * @protected
         * @static
         * @inheritable
         */
        callParent: function(args) {
            var method;

            // This code is intentionally inlined for the least amount of debugger stepping
            return (method = this.callParent.caller) && (method.$previous ||
                  ((method = method.$owner ? method : method.caller) &&
                        method.$owner.superclass.self[method.$name])).apply(this, args || noArgs);
        },

        /**
         * @protected
         * @static
         * @inheritable
         */
        callSuper: function(args) {
            var method;

            // This code is intentionally inlined for the least amount of debugger stepping
            return (method = this.callSuper.caller) &&
                    ((method = method.$owner ? method : method.caller) &&
                      method.$owner.superclass.self[method.$name]).apply(this, args || noArgs);
        },

        //<feature classSystem.mixins>
        /**
         * Used internally by the mixins pre-processor
         * @private
         * @static
         * @inheritable
         */
        mixin: function(name, mixinClass) {
            var me = this,
                mixin, prototype, key, statics, i, ln,
                mixinName, mixinValue, mixins,
                mixinStatics, staticName;

            if (typeof name !== 'string') {
                mixins = name;

                if (mixins instanceof Array) {
                    for (i = 0, ln = mixins.length; i < ln; i++) {
                        mixin = mixins[i];
                        me.mixin(mixin.prototype.mixinId || mixin.$className, mixin);
                    }
                }
                else {
                    // Not a string or array - process the object form:
                    // mixins: {
                    //     foo: ...
                    // }
                    for (mixinName in mixins) {
                        me.mixin(mixinName, mixins[mixinName]);
                    }
                }

                return;
            }

            mixin = mixinClass.prototype;
            prototype = me.prototype;

            if (mixin.onClassMixedIn) {
                mixin.onClassMixedIn.call(mixinClass, me);
            }

            if (!prototype.hasOwnProperty('mixins')) {
                if ('mixins' in prototype) {
                    prototype.mixins = Ext.Object.chain(prototype.mixins);
                }
                else {
                    prototype.mixins = {};
                }
            }

            for (key in mixin) {
                mixinValue = mixin[key];

                if (key === 'mixins') {
                    // if 2 superclasses (e.g. a base class and a mixin) of this class both
                    // have a mixin with the same id, the first one wins, that is to say,
                    // the first mixin's methods to be applied to the prototype will not
                    // be overwritten by the second one.  Since this is the case we also
                    // want to make sure we use the first mixin's prototype as the mixin
                    // reference, hence the "applyIf" below.  A real world example of this
                    // is Ext.Widget which mixes in Ext.mixin.Observable.  Ext.Widget can
                    // be mixed into subclasses of Ext.Component, which mixes in
                    // Ext.util.Observable.  In this example, since the first "observable"
                    // mixin's methods win, we also want its reference to be preserved.
                    Ext.applyIf(prototype.mixins, mixinValue);
                }
                /* eslint-disable-next-line max-len */
                else if (!(key === 'mixinId' || key === 'config' || key === '$inheritableStatics') && (prototype[key] === undefined)) {
                    prototype[key] = mixinValue;
                }
            }

            //<feature classSystem.inheritableStatics>
            // Mixin statics inheritance
            statics = mixin.$inheritableStatics;

            if (statics) {
                mixinStatics = {};

                for (staticName in statics) {
                    if (!me.hasOwnProperty(staticName)) {
                        mixinStatics[staticName] = mixinClass[staticName];
                    }
                }

                me.addInheritableStatics(mixinStatics);
            }
            //</feature>

            //<feature classSystem.config>
            if ('config' in mixin) {
                me.addConfig(mixin.config, mixinClass);
            }
            //</feature>

            prototype.mixins[name] = mixin;

            if (mixin.afterClassMixedIn) {
                mixin.afterClassMixedIn.call(mixinClass, me);
            }

            return me;
        },
        //</feature>

        //<feature classSystem.config>
        /**
         * Adds new config properties to this class. This is called for classes when they
         * are declared, then for any mixins that class may define and finally for any
         * overrides defined that target the class.
         *
         * @param {Object} config
         * @param {Ext.Class} [mixinClass] The mixin class if the configs are from a mixin.
         * @private
         * @static
         * @inheritable
         */
        addConfig: function(config, mixinClass) {
            var cfg = this.$config || this.getConfigurator();

            cfg.add(config, mixinClass);
        },

        addCachedConfig: function(config, isMixin) {
            var cached = {},
                key;

            for (key in config) {
                cached[key] = {
                    cached: true,
                    $value: config[key]
                };
            }

            this.addConfig(cached, isMixin);
        },

        /**
         * Returns the `Ext.Configurator` for this class.
         *
         * @return {Ext.Configurator}
         * @private
         * @static
         * @inheritable
         */
        getConfigurator: function() {
            // the Ext.Configurator ctor will set $config so micro-opt out fn call:
            return this.$config || new Ext.Configurator(this);
        },
        //</feature>

        /**
         * Get the current class' name in string format.
         *
         *     Ext.define('My.cool.Class', {
         *         constructor: function() {
         *             alert(this.self.getName()); // alerts 'My.cool.Class'
         *         }
         *     });
         *
         *     My.cool.Class.getName(); // 'My.cool.Class'
         *
         * @return {String} className
         * @static
         * @inheritable
         */
        getName: function() {
            return Ext.getClassName(this);
        },

        /**
         * Create aliases for existing prototype methods. Example:
         *
         *     Ext.define('My.cool.Class', {
         *         method1: function() { ... },
         *         method2: function() { ... }
         *     });
         *
         *     var test = new My.cool.Class();
         *
         *     My.cool.Class.createAlias({
         *         method3: 'method1',
         *         method4: 'method2'
         *     });
         *
         *     test.method3(); // test.method1()
         *
         *     My.cool.Class.createAlias('method5', 'method3');
         *
         *     test.method5(); // test.method3() -> test.method1()
         *
         * @param {String/Object} alias The new method name, or an object to set multiple aliases.
         * See {@link Ext.Function#flexSetter flexSetter}
         * @param {String/Object} origin The original method name
         * @static
         * @inheritable
         * @method
         */
        createAlias: flexSetter(function(alias, origin) {
            aliasOneMember[alias] = function() {
                return this[origin].apply(this, arguments);
            };

            this.override(aliasOneMember);

            delete aliasOneMember[alias];
        })
    });

    // Capture the set of static members on Ext.Base that we want to copy to all
    // derived classes. This array is used by Ext.Class as well as the optimizer.
    for (baseStaticMember in Base) {
        if (Base.hasOwnProperty(baseStaticMember)) {
            baseStaticMembers.push(baseStaticMember);
        }
    }

    Base.$staticMembers = baseStaticMembers;

    //<feature classSystem.config>
    Base.getConfigurator(); // lazily create now so as not capture in $staticMembers
    //</feature>

    Base.addMembers({
        /** @private */
        $className: 'Ext.Base',

        /**
         * @property {Object/Array} $configTransforms
         * A prototype-chained object storing transform method names and priorities stored
         * on the class prototype. On first instantiation, this object is converted into
         * an array that is sorted by priority and stored on the constructor.
         * @private
         */
        $configTransforms: {},

        /**
         * @property {Boolean} isInstance
         * This value is `true` and is used to identify plain objects from instances of
         * a defined class.
         * @protected
         * @readonly
         */
        isInstance: true,

        /**
         * @property {Boolean} $configPrefixed
         * The value `true` causes `config` values to be stored on instances using a
         * property name prefixed with an underscore ("_") character. A value of `false`
         * stores `config` values as properties using their exact name (no prefix).
         * @private
         * @since 5.0.0
         */
        $configPrefixed: true,

        /**
         * @property {Boolean} $configStrict
         * The value `true` instructs the `initConfig` method to only honor values for
         * properties declared in the `config` block of a class. When `false`, properties
         * that are not declared in a `config` block will be placed on the instance.
         * @private
         * @since 5.0.0
         */
        $configStrict: true,

        /**
         * @property {Boolean} isConfiguring
         * This property is set to `true` during the call to `initConfig`.
         * @protected
         * @readonly
         * @since 5.0.0
         */
        isConfiguring: false,

        /**
         * @property {Boolean} isFirstInstance
         * This property is set to `true` if this instance is the first of its class.
         * @protected
         * @readonly
         * @since 5.0.0
         */
        isFirstInstance: false,

        /**
         * @property {Boolean} destroyed
         * This property is set to `true` after the `destroy` method is called.
         */
        destroyed: false,

        /**
         * @property {Boolean/"async"} [clearPropertiesOnDestroy=true]
         * Setting this property to `false` will prevent nulling object references
         * on a Class instance after destruction. Setting this to `"async"` will delay
         * the clearing for approx 50ms.
         * @protected
         * @since 6.2.0
         */
        clearPropertiesOnDestroy: true,

        /**
         * @property {Boolean} [clearPrototypeOnDestroy=false]
         * Setting this property to `true` will result in setting the object's
         * prototype to `null` after the destruction sequence is fully completed.
         * After that, most attempts at calling methods on the object instance
         * will result in "method not defined" exception. This can be very helpful
         * with tracking down otherwise hard to find bugs like runaway Ajax requests,
         * timed functions not cleared on destruction, etc.
         *
         * Note that this option can only work in browsers that support `Object.setPrototypeOf`
         * method, and is only available in debugging mode.
         * @private
         * @since 6.2.0
         */
        clearPrototypeOnDestroy: false,

        /**
         * Get the reference to the class from which this object was instantiated. Note that unlike
         * {@link Ext.Base#self}, `this.statics()` is scope-independent and it always returns
         * the class from which it was called, regardless of what `this` points to during run-time
         *
         *     Ext.define('My.Cat', {
         *         statics: {
         *             totalCreated: 0,
         *             speciesName: 'Cat' // My.Cat.speciesName = 'Cat'
         *         },
         *
         *         constructor: function() {
         *             var statics = this.statics();
         *
         *             // always equals to 'Cat' no matter what 'this' refers to
         *             // equivalent to: My.Cat.speciesName
         *             alert(statics.speciesName);
         * 
         *
         *             alert(this.self.speciesName);   // dependent on 'this'
         *
         *             statics.totalCreated++;
         *         },
         *
         *         clone: function() {
         *             var cloned = new this.self();   // dependent on 'this'
         *
         *             // equivalent to: My.Cat.speciesName
         *             cloned.groupName = this.statics().speciesName;
         *
         *             return cloned;
         *         }
         *     });
         *
         *
         *     Ext.define('My.SnowLeopard', {
         *         extend: 'My.Cat',
         *
         *         statics: {
         *             speciesName: 'Snow Leopard' // My.SnowLeopard.speciesName = 'Snow Leopard'
         *         },
         *
         *         constructor: function() {
         *             this.callParent();
         *         }
         *     });
         *
         *     var cat = new My.Cat();                 // alerts 'Cat', then alerts 'Cat'
         *
         *     var snowLeopard = new My.SnowLeopard(); // alerts 'Cat', then alerts 'Snow Leopard'
         *
         *     var clone = snowLeopard.clone();
         *     alert(Ext.getClassName(clone));         // alerts 'My.SnowLeopard'
         *     alert(clone.groupName);                 // alerts 'Cat'
         *
         *     alert(My.Cat.totalCreated);             // alerts 3
         *
         * @protected
         * @return {Ext.Class}
         */
        statics: function() {
            var method = this.statics.caller,
                self = this.self;

            if (!method) {
                return self;
            }

            return method.$owner;
        },

        /**
         * Call the "parent" method of the current method. That is the method previously
         * overridden by derivation or by an override (see {@link Ext#define}).
         *
         *      Ext.define('My.Base', {
         *          constructor: function(x) {
         *              this.x = x;
         *          },
         *
         *          statics: {
         *              method: function(x) {
         *                  return x;
         *              }
         *          }
         *      });
         *
         *      Ext.define('My.Derived', {
         *          extend: 'My.Base',
         *
         *          constructor: function() {
         *              this.callParent([21]);
         *          }
         *      });
         *
         *      var obj = new My.Derived();
         *
         *      alert(obj.x);  // alerts 21
         *
         * This can be used with an override as follows:
         *
         *      Ext.define('My.DerivedOverride', {
         *          override: 'My.Derived',
         *
         *          constructor: function(x) {
         *              this.callParent([x*2]); // calls original My.Derived constructor
         *          }
         *      });
         *
         *      var obj = new My.Derived();
         *
         *      alert(obj.x);  // now alerts 42
         *
         * This also works with static and private methods.
         *
         *      Ext.define('My.Derived2', {
         *          extend: 'My.Base',
         *
         *          // privates: {
         *          statics: {
         *              method: function(x) {
         *                  return this.callParent([x*2]); // calls My.Base.method
         *              }
         *          }
         *      });
         *
         *      alert(My.Base.method(10));     // alerts 10
         *      alert(My.Derived2.method(10)); // alerts 20
         *
         * Lastly, it also works with overridden static methods.
         *
         *      Ext.define('My.Derived2Override', {
         *          override: 'My.Derived2',
         *
         *          // privates: {
         *          statics: {
         *              method: function(x) {
         *                  return this.callParent([x*2]); // calls My.Derived2.method
         *              }
         *          }
         *      });
         *
         *      alert(My.Derived2.method(10); // now alerts 40
         *
         * To override a method and replace it and also call the superclass method, use
         * {@link #method-callSuper}. This is often done to patch a method to fix a bug.
         *
         * @protected
         * @param {Array/Arguments} args The arguments, either an array or the `arguments` object
         * from the current method, for example: `this.callParent(arguments)`
         * @return {Object} Returns the result of calling the parent method
         */
        callParent: function(args) {
            // NOTE: this code is deliberately as few expressions (and no function calls)
            // as possible so that a debugger can skip over this noise with the minimum number
            // of steps. Basically, just hit Step Into until you are where you really wanted
            // to be.
            var method,
                superMethod = (method = this.callParent.caller) && (method.$previous ||
                        ((method = method.$owner ? method : method.caller) &&
                                method.$owner.superclass[method.$name]));

            //<debug>
            if (!superMethod) {
                method = this.callParent.caller;

                /* eslint-disable-next-line vars-on-top */
                var parentClass, methodName;

                if (!method.$owner) {
                    if (!method.caller) {
                        throw new Error("Attempting to call a protected method from the " +
                                        "public scope, which is not allowed");
                    }

                    method = method.caller;
                }

                parentClass = method.$owner.superclass;
                methodName = method.$name;

                if (!(methodName in parentClass)) {
                    throw new Error("this.callParent() was called but there's no such method (" +
                                    methodName + ") found in the parent class (" +
                                    (Ext.getClassName(parentClass) || 'Object') + ")");
                }
            }
            //</debug>

            return superMethod.apply(this, args || noArgs);
        },

        /**
         * This method is used by an **override** to call the superclass method but
         * bypass any overridden method. This is often done to "patch" a method that
         * contains a bug but for whatever reason cannot be fixed directly.
         *
         * Consider:
         *
         *      Ext.define('Ext.some.Class', {
         *          method: function() {
         *              console.log('Good');
         *          }
         *      });
         *
         *      Ext.define('Ext.some.DerivedClass', {
         *          extend: 'Ext.some.Class',
         *          
         *          method: function() {
         *              console.log('Bad');
         * 
         *              // ... logic but with a bug ...
         *              
         *              this.callParent();
         *          }
         *      });
         *
         * To patch the bug in `Ext.some.DerivedClass.method`, the typical solution is to create an
         * override:
         *
         *      Ext.define('App.patches.DerivedClass', {
         *          override: 'Ext.some.DerivedClass',
         *          
         *          method: function() {
         *              console.log('Fixed');
         * 
         *              // ... logic but with bug fixed ...
         *
         *              this.callSuper();
         *          }
         *      });
         *
         * The patch method cannot use {@link #method-callParent} to call the superclass
         * `method` since that would call the overridden method containing the bug. In
         * other words, the above patch would only produce "Fixed" then "Good" in the
         * console log, whereas, using `callParent` would produce "Fixed" then "Bad"
         * then "Good".
         *
         * @protected
         * @param {Array/Arguments} args The arguments, either an array or the `arguments` object
         * from the current method, for example: `this.callSuper(arguments)`
         * @return {Object} Returns the result of calling the superclass method
         */
        callSuper: function(args) {
            // NOTE: this code is deliberately as few expressions (and no function calls)
            // as possible so that a debugger can skip over this noise with the minimum number
            // of steps. Basically, just hit Step Into until you are where you really wanted
            // to be.
            var method,
                superMethod = (method = this.callSuper.caller) &&
                        ((method = method.$owner ? method : method.caller) &&
                          method.$owner.superclass[method.$name]);

            //<debug>
            if (!superMethod) {
                method = this.callSuper.caller;

                /* eslint-disable-next-line vars-on-top */
                var parentClass, methodName;

                if (!method.$owner) {
                    if (!method.caller) {
                        throw new Error("Attempting to call a protected method from the " +
                                        "public scope, which is not allowed");
                    }

                    method = method.caller;
                }

                parentClass = method.$owner.superclass;
                methodName = method.$name;

                if (!(methodName in parentClass)) {
                    throw new Error("this.callSuper() was called but there's no such method (" +
                                    methodName + ") found in the parent class (" +
                                    (Ext.getClassName(parentClass) || 'Object') + ")");
                }
            }
            //</debug>

            return superMethod.apply(this, args || noArgs);
        },

        /**
         * @property {Ext.Class} self
         *
         * Get the reference to the current class from which this object was instantiated. Unlike
         * {@link Ext.Base#statics}, `this.self` is scope-dependent and it's meant to be used
         * for dynamic inheritance. See {@link Ext.Base#statics} for a detailed comparison
         *
         *     Ext.define('My.Cat', {
         *         statics: {
         *             speciesName: 'Cat' // My.Cat.speciesName = 'Cat'
         *         },
         *
         *         constructor: function() {
         *             alert(this.self.speciesName); // dependent on 'this'
         *         },
         *
         *         clone: function() {
         *             return new this.self();
         *         }
         *     });
         *
         *
         *     Ext.define('My.SnowLeopard', {
         *         extend: 'My.Cat',
         *         statics: {
         *             speciesName: 'Snow Leopard' // My.SnowLeopard.speciesName = 'Snow Leopard'
         *         }
         *     });
         *
         *     var cat = new My.Cat();                     // alerts 'Cat'
         *     var snowLeopard = new My.SnowLeopard();     // alerts 'Snow Leopard'
         *
         *     var clone = snowLeopard.clone();
         *     alert(Ext.getClassName(clone));             // alerts 'My.SnowLeopard'
         *
         * @protected
         */
        self: Base,

        // Default constructor, simply returns `this`
        constructor: function() {
            return this;
        },

        //<feature classSystem.config>
        /**
         * Initialize configuration for this class. a typical example:
         *
         *     Ext.define('My.awesome.Class', {
         *         // The default config
         *         config: {
         *             name: 'Awesome',
         *             isAwesome: true
         *         },
         *
         *         constructor: function(config) {
         *             this.initConfig(config);
         *         }
         *     });
         *
         *     var awesome = new My.awesome.Class({
         *         name: 'Super Awesome'
         *     });
         *
         *     alert(awesome.getName()); // 'Super Awesome'
         *
         * @protected
         * @param {Object} instanceConfig
         * @return {Ext.Base} this
         * @chainable
         */
        initConfig: function(instanceConfig) {
            var me = this,
                cfg = me.self.getConfigurator();

            me.initConfig = Ext.emptyFn; // ignore subsequent calls to initConfig
            me.initialConfig = instanceConfig || {};
            cfg.configure(me, instanceConfig);

            return me;
        },

        beforeInitConfig: Ext.emptyFn,

        /**
         * Returns a specified config property value. If the name parameter is not passed,
         * all current configuration options will be returned as key value pairs.
         * @param {String} [name] The name of the config property to get.
         * @param {Boolean} [peek=false] `true` to peek at the raw value without calling the getter.
         * @param {Boolean} [ifInitialized=false] `true` to only return the initialized property
         * value, not the raw config value, and *not* to trigger initialization. Returns
         * `undefined` if the property has not yet been initialized.
         * @return {Object} The config property value.
         */
        getConfig: function(name, peek, ifInitialized) {
            var me = this,
                ret, cfg, propName;

            if (name) {
                cfg = me.self.$config.configs[name];

                if (cfg) {
                    propName = me.$configPrefixed ? cfg.names.internal : name;

                    // They only want the fully initialized value, not the initial config,
                    //  but only if it's already present on this instance.
                    // They don't want to trigger the initGetter.
                    // This form is used by Bindable#updatePublishes to initially publish
                    // the properties it's being asked make publishable.
                    if (ifInitialized) {
                        ret = me.hasOwnProperty(propName) ? me[propName] : null;
                    }
                    else if (peek) {
                        // Attempt to return the instantiated property on this instance first.
                        // Only return the config object if it has not yet been pulled through
                        // the applier into the instance.
                        ret = me.hasOwnProperty(propName) ? me[propName] : me.config[name];
                    }
                    else {
                        ret = me[cfg.names.get]();
                    }
                }
                else {
                    ret = me[name];
                }
            }
            else {
                ret = me.getCurrentConfig();
            }

            return ret;
        },

        /**
         * Destroys member properties by name.
         *
         * If a property name is the name of a *config*, the getter is *not* invoked, so
         * if the config has not been initialized, nothing will be done.
         *
         * The property will be destroyed, and the corrected name (if the property is a *config*
         * and config names are prefixed) will set to `null` in this object's dictionary.
         *
         * @param {String...} args One or more names of the properties to destroy and remove from
         * the object.
         */
        destroyMembers: function() {
            var me = this,
                configs = me.self.$config.configs,
                len = arguments.length,
                cfg, name, value, i;

            for (i = 0; i < len; i++) {
                name = arguments[i];
                cfg = configs[name];
                name = cfg && me.$configPrefixed ? cfg.names.internal : name;
                value = me.hasOwnProperty(name) && me[name];

                if (value) {
                    Ext.destroy(value);
                    me[name] = null;
                }
            }
        },

        freezeConfig: function(name) {
            var me = this,
                config = Ext.Config.get(name),
                names = config.names,
                value = me[names.get]();

            me[names.set] = function(v) {
                //<debug>
                if (v !== value) {
                    Ext.raise('Cannot change frozen config "' + name + '"');
                }
                //</debug>

                return me;
            };

            //<debug>
            if (!Ext.isIE8) {
                Object.defineProperty(me, me.$configPrefixed ? names.internal : name, {
                    get: function() {
                        return value;
                    },
                    set: function(v) {
                        if (v !== value) {
                            Ext.raise('Cannot change frozen config "' + name + '"');
                        }
                    }
                });
            }
            //</debug>
        },

        /**
         * Sets a single/multiple configuration options.
         * @param {String/Object} name The name of the property to set, or a set of key value
         * pairs to set.
         * @param {Object} [value] The value to set for the name parameter.
         * @param {Object} [options] (private)
         * @return {Ext.Base} this
         */
        setConfig: function(name, value, options) {
            // options can have the following properties:
            // - defaults `true` to only set the config(s) that have not been already set on
            // this instance.
            // - strict `false` to apply properties to the instance that are not configs,
            // and do not have setters.
            var me = this,
                configurator,
                config,
                prop;

            if (name) {
                configurator = me.self.getConfigurator();

                if (typeof name === 'string') {
                    config = configurator.configs[name];

                    if (!config) {
                        if (me.$configStrict) {
                            prop = me.self.prototype[name];

                            if ((typeof prop === 'function') && !prop.$nullFn) {
                                //<debug>
                                Ext.Error.raise("Cannot override method " + name + " on " +
                                                me.$className + " instance.");
                                //</debug>

                                return me;
                            }
                            //<debug>
                            else {
                                if (name !== 'type') {
                                    Ext.log.warn('No such config "' + name + '" for class ' +
                                        me.$className);
                                }
                            }
                            //</debug>
                        }

                        config = Ext.Config.map[name] || Ext.Config.get(name);
                    }

                    if (me[config.names.set]) {
                        me[config.names.set](value);
                    }
                    else {
                        // apply non-config props directly to the instance
                        me[name] = value;
                    }
                }
                else {
                    // This should not have "options ||" except that it shipped in that
                    // broken state, so we use it if present for compat.
                    configurator.reconfigure(me, name, options || value);
                }
            }

            return me;
        },

        getConfigWatcher: function() {
            return this.$configWatch || (this.$configWatch = new Ext.mixin.Watchable());
        },

        /**
         * Watches config properties.
         *
         *      instance.watchConfig({
         *          title: 'onTitleChange',
         *          scope: me
         *      });
         *
         * @private
         * @since 6.7.0
         */
        watchConfig: function(name, fn, scope) {
            var watcher = this.getConfigWatcher();

            return watcher.on.apply(watcher, arguments);
        },

        $configWatch: null,

        /**
         * @private
         */
        getCurrentConfig: function() {
            var cfg = this.self.getConfigurator();

            return cfg.getCurrentConfig(this);
        },

        /**
         * @param {String} name
         * @private
         */
        hasConfig: function(name) {
            return name in this.defaultConfig;
        },

        /**
         * Returns the initial configuration passed to the constructor when
         * instantiating this class.
         *
         * Given this example Ext.button.Button definition and instance:
         *
         *     Ext.define('MyApp.view.Button', {
         *         extend: 'Ext.button.Button',
         *         xtype: 'mybutton',
         *     
         *         scale: 'large',
         *         enableToggle: true
         *     });
         *
         *     var btn = Ext.create({
         *         xtype: 'mybutton',
         *         renderTo: Ext.getBody(),
         *         text: 'Test Button'
         *     });
         *
         * Calling `btn.getInitialConfig()` would return an object including the config
         * options passed to the `create` method:
         *
         *     xtype: 'mybutton',
         *     renderTo: // The document body itself
         *     text: 'Test Button'
         *
         * Calling `btn.getInitialConfig('text')`returns **'Test Button'**.
         *
         * @param {String} [name] Name of the config option to return.
         * @return {Object/Mixed} The full config object or a single config value
         * when `name` parameter specified.
         */
        getInitialConfig: function(name) {
            var config = this.config;

            if (!name) {
                return config;
            }

            return config[name];
        },
        //</feature>

        $links: null,

        /**
         * Adds a "destroyable" object to an internal list of objects that will be destroyed
         * when this instance is destroyed (via `{@link #method!destroy}`).
         * @param {String} name
         * @param {Object} value
         * @return {Object} The `value` passed.
         * @private
         */
        link: function(name, value) {
            var me = this,
                links = me.$links || (me.$links = {});

            links[name] = true;
            me[name] = value;

            return value;
        },

        /**
         * Destroys a given set of `{@link #link linked}` objects. This is only needed if
         * the linked object is being destroyed before this instance.
         * @param {String[]} names The names of the linked objects to destroy.
         * @return {Ext.Base} this
         * @private
         */
        unlink: function(names) {
            var me = this,
                i, ln, link, value;

            //<debug>
            if (!Ext.isArray(names)) {
                Ext.raise('Invalid argument - expected array of strings');
            }
            //</debug>

            for (i = 0, ln = names.length; i < ln; i++) {
                link = names[i];
                value = me[link];

                if (value) {
                    if (value.isInstance && !value.destroyed) {
                        value.destroy();
                    }
                    else if (value.parentNode && 'nodeType' in value) {
                        value.parentNode.removeChild(value);
                    }
                }

                me[link] = null;
            }

            return me;
        },

        $reap: function() {
            var me = this,
                keepers = me.$noClearOnDestroy,
                props, prop, val, t, i, len;

            // This only returns own keys which is *much* faster than iterating
            // over the whole prototype chain and calling hasOwnProperty()
            props = Ext.Object.getKeys(me);

            for (i = 0, len = props.length; i < len; i++) {
                prop = props[i];
                val = me[prop];

                // typeof null === 'object' :(
                if (val && !(keepers && keepers[prop])) {
                    t = typeof val;

                    // Object may retain references to other objects. Functions can do too
                    // if they are closures, and most of the *own* function properties
                    // are closures indeed. We skip Ext.emptyFn and the like though,
                    // they're mostly harmless.
                    if (t === 'object' || (t === 'function' && !val.$noClearOnDestroy)) {
                        me[prop] = null;
                    }
                }
            }

            me.$nulled = true;

            //<debug>
            // We also want to make sure no methods are called on the destroyed object,
            // because that may lead to accessing nulled properties and resulting exceptions.
            if (Object.setPrototypeOf) {
                if (me.clearPrototypeOnDestroy && !me.$vetoClearingPrototypeOnDestroy) {
                    props = me.$preservePrototypeProperties;

                    if (props) {
                        for (i = 0, len = props.length; i < len; i++) {
                            prop = props[i];

                            if (!me.hasOwnProperty(prop)) {
                                /* eslint-disable-next-line no-self-assign */
                                me[prop] = me[prop];
                            }
                        }
                    }

                    Object.setPrototypeOf(me, null);
                }
            }
            //</debug>
        },

        /**
         * This method is called to cleanup an object and its resources. After calling
         * this method, the object should not be used any further in any way, including
         * access to its methods and properties.
         *
         * To prevent potential memory leaks, all object references will be nulled
         * at the end of destruction sequence, unless {@link #clearPropertiesOnDestroy}
         * is set to `false`.
         */
        destroy: function() {
            var me = this,
                links = me.$links,
                clearPropertiesOnDestroy = me.clearPropertiesOnDestroy;

            if (links) {
                me.$links = null;
                me.unlink(Ext.Object.getKeys(links));
            }

            me.destroy = Ext.emptyFn;

            // isDestroyed added for compat reasons
            me.isDestroyed = me.destroyed = true;

            // By this time the destruction is complete. Now we can make sure
            // no objects are retained by the husk of this ex-Instance.
            if (clearPropertiesOnDestroy === true) {
                // Observable mixin will call destroyObservable that will reap the properties.
                if (!me.isObservable) {
                    me.$reap();
                }
            }
            else if (clearPropertiesOnDestroy) {
                //<debug>
                if (clearPropertiesOnDestroy !== 'async') {
                    Ext.raise('Invalid value for clearPropertiesOnDestroy');
                }
                //</debug>

                Reaper.add(me);
            }
        }
    });

    /**
     * @method callOverridden
     * Call the original method that was previously overridden with {@link Ext.Base#override}
     *
     *     Ext.define('My.Cat', {
     *         constructor: function() {
     *             alert("I'm a cat!");
     *         }
     *     });
     *
     *     My.Cat.override({
     *         constructor: function() {
     *             alert("I'm going to be a cat!");
     *
     *             this.callOverridden();
     *
     *             alert("Meeeeoooowwww");
     *         }
     *     });
     *
     *     var kitty = new My.Cat(); // alerts "I'm going to be a cat!"
     *                               // alerts "I'm a cat!"
     *                               // alerts "Meeeeoooowwww"
     *
     * @param {Array/Arguments} args The arguments, either an array or the `arguments` object
     * from the current method, for example: `this.callOverridden(arguments)`
     * @return {Object} Returns the result of calling the overridden method
     * @deprecated 4.1.0 Use {@link #method-callParent} instead.
     * @protected
     */
    BasePrototype.callOverridden = BasePrototype.callParent;

    //<debug>
    Ext.privacyViolation = function(cls, existing, member, isStatic) {
        var name = member.$name,
            conflictCls = existing.$owner && existing.$owner.$className,
            s = isStatic ? 'static ' : '',
            msg = member.$privacy
                ? 'Private ' + s + member.$privacy + ' method "' + name + '"'
                : 'Public ' + s + 'method "' + name + '"';

        if (cls.$className) {
            msg = cls.$className + ': ' + msg;
        }

        if (!existing.$privacy) {
            msg += conflictCls
                ? ' hides public method inherited from ' + conflictCls
                : ' hides inherited public method.';
        }
        else {
            msg += conflictCls
                ? ' conflicts with private ' + existing.$privacy +
                  ' method declared by ' + conflictCls
                : ' conflicts with inherited private ' + existing.$privacy + ' method.';
        }

        /* eslint-disable-next-line vars-on-top */
        var compat = Ext.getCompatVersion(),
            ver = Ext.getVersion();

        // When compatibility is enabled, log problems instead of throwing errors.
        if (ver && compat && compat.lt(ver)) {
            Ext.log.error(msg);
        }
        else {
            Ext.raise(msg);
        }
    };

    Ext.Reaper.tick.$skipTimerCheck = true;
    //</debug>

    return Base;
}(Ext.Function.flexSetter));
