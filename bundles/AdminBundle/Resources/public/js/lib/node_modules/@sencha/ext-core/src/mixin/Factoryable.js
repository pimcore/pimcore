// @define Ext.Factory
/**
 * @class Ext.Factory
 * Manages factories for families of classes (classes with a common `alias` prefix). The
 * factory for a class family is a function stored as a `static` on `Ext.Factory`. These
 * are created either by directly calling `Ext.Factory.define` or by using the
 * `Ext.mixin.Factoryable` interface.
 *
 * To illustrate, consider the layout system's use of aliases. The `hbox` layout maps to
 * the `"layout.hbox"` alias that one typically provides via the `layout` config on a
 * Container.
 *
 * Under the covers this maps to a call like this:
 *
 *      Ext.Factory.layout('hbox');
 *
 * Or possibly:
 *
 *      Ext.Factory.layout({
 *          type: 'hbox'
 *      });
 *
 * The value of the `layout` config is passed to the `Ext.Factory.layout` function. The
 * exact signature of a factory method matches `{@link Ext.Factory#method!create}`.
 *
 * To define this factory directly, one could call `Ext.Factory.define` like so:
 *
 *      Ext.Factory.define('layout', 'auto');  // "layout.auto" is the default type
 *
 * @since 5.0.0
 */
Ext.Factory = function(type) {
    var me = this;

    me.aliasPrefix = type + '.';
    me.cache = {};
    me.name = type.replace(me.fixNameRe, me.fixNameFn);
    me.type = type;

    /**
     * @cfg {String} [creator]
     * The name of the method used to prepare config objects for creation. This defaults
     * to `'create'` plus the capitalized name (e.g., `'createLayout'` for the 'laoyut'
     * alias family).
     */
    me.creator = 'create' + Ext.String.capitalize(me.name);
};

Ext.Factory.prototype = {
    /**
     * @cfg {String} [aliasPrefix]
     * The prefix to apply to `type` values to form a complete alias. This defaults to the
     * proper value in most all cases and should not need to be specified.
     *
     * @since 5.0.0
     */

    /**
     * @cfg {String} [defaultProperty="type"]
     * The config property to set when the factory is given a config that is a string.
     *
     * @since 5.0.0
     */
    defaultProperty: 'type',

    /**
     * @cfg {String} [defaultType=null]
     * An optional type to use if none is given to the factory at invocation. This is a
     * suffix added to the `aliasPrefix`. For example, if `aliasPrefix="layout."` and
     * `defaultType="hbox"` the default alias is `"layout.hbox"`. This is an alternative
     * to `xclass` so only one should be provided.
     *
     * @since 5.0.0
     */

    /**
     * @cfg {String} [instanceProp="isInstance"]
     * The property that identifies an object as instance vs a config.
     *
     * @since 5.0.0
     */
    instanceProp: 'isInstance',

    /**
     * @cfg {String} [xclass=null]
     * The full classname of the type of instance to create when none is provided to the
     * factory. This is an alternative to `defaultType` so only one should be specified.
     *
     * @since 5.0.0
     */

    /**
     * @property {Ext.Class} [defaultClass=null]
     * The Class reference of the type of instance to create when none is provided to the
     * factory. This property is set from `xclass` when the factory instance is created.
     * @private
     * @readonly
     *
     * @since 5.0.0
     */

    /**
     * @cfg {String} [typeProperty="type"]
     * The property from which to read the type alias suffix.
     * @since 6.5.0
     */
    typeProperty: 'type',

    /**
     * Creates an instance of this class family given configuration options.
     *
     * @param {Object/String} [config] The configuration or instance (if an Object) or
     * just the type (if a String) describing the instance to create.
     * @param {String} [config.xclass] The full class name of the class to create.
     * @param {String} [config.type] The type string to add to the alias prefix for this
     * factory.
     * @param {String/Object} [defaultType] The type to create if no type is contained in the
     * `config`, or an object containing a default set of configs.
     * @return {Object} The newly created instance.
     *
     * @since 5.0.0
     */
    create: function(config, defaultType) {
        var me = this,
            Manager = Ext.ClassManager,
            cache = me.cache,
            typeProperty = me.typeProperty,
            alias, className, klass, suffix;

        if (config) {
            if (config[me.instanceProp]) {
                return config;
            }

            if (typeof config === 'string') {
                suffix = config;
                config = {};
                config[me.defaultProperty] = suffix;
            }

            className = config.xclass;
            suffix = config[typeProperty];
        }

        if (defaultType && defaultType.constructor === Object) {
            config = Ext.apply({}, config, defaultType);
            defaultType = defaultType[typeProperty];
        }

        if (className) {
            if (!(klass = Manager.get(className))) {
                return Manager.instantiate(className, config);
            }
        }
        else {
            if (!(suffix = suffix || defaultType || me.defaultType)) {
                klass = me.defaultClass;
            }

            //<debug>
            if (!suffix && !klass) {
                Ext.raise('No type specified for ' + me.type + '.create');
            }
            //</debug>

            if (!klass && !(klass = cache[suffix])) {
                alias = me.aliasPrefix + suffix;
                className = Manager.getNameByAlias(alias);

                // this is needed to support demand loading of the class
                if (!(klass = className && Manager.get(className))) {
                    return Manager.instantiateByAlias(alias, config);
                }

                cache[suffix] = klass;
            }
        }

        return klass.isInstance ? klass : new klass(config);
    },

    fixNameRe: /\.[a-z]/ig,
    fixNameFn: function(match) {
        return match.substring(1).toUpperCase();
    },

    clearCache: function() {
        this.cache = {};
        this.instanceCache = {};
    },

    /**
     * Sets a hook on the creation process. If the hook `fn` returns `undefined` then
     * the original `create` method is called.
     *
     * @param {Function} fn The hook function to call when `create` is invoked.
     * @param {Function} fn.original The original `create` method.
     * @param {String/Object} fn.config See {@link #method!create create}.
     * @param {String/Object} fn.defaultType See {@link #method!create create}.
     * @private
     * @since 6.5.0
     */
    hook: function(fn) {
        var me = this,
            original = me.create;

        me.create = function(config, defaultType) {
            var ret = fn.call(me, original, config, defaultType);

            if (ret === undefined) {
                ret = original.call(me, config, defaultType);
            }

            return ret;
        };
    },

    /**
     * This method accepts a `config` object and an existing `instance` if one exists
     * (can be `null`).
     *
     * The details are best explained by example:
     *
     *      config: {
     *          header: {
     *              xtype: 'itemheader'
     *          }
     *      },
     *
     *      applyHeader: function (header, oldHeader) {
     *          return Ext.Factory.widget.update(oldHeader, header,
     *              this, 'createHeader');
     *      },
     *
     *      createHeader: function (header) {
     *          return Ext.apply({
     *              xtype: 'itemheader',
     *              ownerCmp: this
     *          }, header);
     *      }
     *
     * Normally the `applyHeader` method would have to coordinate potential reuse of
     * the `oldHeader` and perhaps call `setConfig` on it with the new `header` config
     * options. If there was no `oldHeader`, of course, a new instance must be created
     * instead. These details are handled by this method. If the `oldHeader` is not
     * reused, it will be {@link Ext.Base#method!destroy destroyed}.
     *
     * For derived class flexibility, the pattern of calling out to a "creator" method
     * that only returns the config object has become widely used in many components.
     * This pattern is also covered in this method. The goal is to allow the derived
     * class to `callParent` and yet not end up with an instantiated component (since
     * the type may not yet be known).
     *
     * This mechanism should be used in favor of `Ext.factory()`.
     *
     * @param {Ext.Base} instance
     * @param {Object/String} config The configuration (see {@link #method!create}).
     * @param {Object} [creator] If passed, this object must provide the `creator`
     * method or the `creatorMethod` parameter.
     * @param {String} [creatorMethod] The name of a creation wrapper method on the
     * given `creator` instance that "upgrades" the raw `config` object into a final
     * form for creation.
     * @param {String} [defaultsConfig] The name of a config property (on the provided
     * `creator` instance) that contains defaults to be used to create instances. These
     * defaults are present in the config object passed to the `creatorMethod`.
     * @return {Object} The reconfigured `instance` or a newly created one.
     * @since 6.5.0
     */
    update: function(instance, config, creator, creatorMethod, defaultsConfig) {
        var me = this,
            aliases, defaults, reuse, type;

        // If config is falsy or a valid instance, destroy the current instance
        // (if it exists) and replace with the new one
        if (!config || config.isInstance) {
            //<debug>
            if (config && !config[me.instanceProp]) {
                Ext.raise('Config instance failed ' + me.instanceProp + ' requirement');
            }
            //</debug>

            if (instance && instance !== config) {
                instance.destroy();
            }

            return config;
        }

        if (typeof config === 'string') {
            type = config;
            config = {};
            config[me.defaultProperty] = type;
        }

        // See if the existing instance can just be reconfigured:
        if (instance) {
            if (config === true) {
                return instance;
            }

            if (!(type = config.xclass)) {
                if (!(type = config.xtype)) {
                    type = config[me.typeProperty];

                    if (type) {
                        // instance must have the right alias...
                        type = me.aliasPrefix + type;
                        aliases = instance.self.prototype;

                        // The alias for the class is on the prototype (derived
                        // classes do not really own their inherited aliases since
                        // they won't be created when using them):
                        if (aliases.hasOwnProperty('alias')) {
                            aliases = aliases.alias;

                            if (aliases) {
                                reuse = aliases === type || aliases.indexOf(type) > -1;
                            }
                        }
                    }
                }
                else {
                    // config = { xtype: ... }
                    reuse = instance.isXType(type, /* shallow= */ true);
                }
            }
            else {
                // config = { xclass: ... } so we're good if they match
                reuse = instance.$className === type;
            }

            if (reuse) {
                instance.setConfig(config);

                return instance;
            }

            instance.destroy();
        }

        if (config === true) {
            config = {};
        }

        if (creator) {
            if (defaultsConfig) {
                defaults = Ext.Config.map[defaultsConfig];
                defaults = creator[defaults.names.get]();

                if (defaults) {
                    config = Ext.merge(Ext.clone(defaults), config);
                }
            }

            creatorMethod = creatorMethod || me.creator;

            if (creator[creatorMethod]) {
                config = creator[creatorMethod](config);

                //<debug>
                if (!config) {
                    Ext.raise('Missing return value from ' + creatorMethod + ' on class ' +
                        creator.$className);
                }
                //</debug>
            }
        }

        return me.create(config);
    }
};

/**
 * For example, the layout alias family could be defined like this:
 *
 *      Ext.Factory.define('layout', {
 *          defaultType: 'auto'
 *      });
 *
 * To define multiple families at once:
 *
 *      Ext.Factory.define({
 *          layout: {
 *              defaultType: 'auto'
 *          }
 *      });
 *
 * @param {String} type The alias family (e.g., "layout").
 * @param {Object/String} [config] An object specifying the config for the `Ext.Factory`
 * to be created. If a string is passed it is treated as the `defaultType`.
 * @return {Function}
 * @static
 * @since 5.0.0
 */
Ext.Factory.define = function(type, config) {
    var Factory = Ext.Factory,
        cacheable = config && config.cacheable,
        defaultClass, factory, fn;

    if (type.constructor === Object) {
        Ext.Object.each(type, Factory.define, Factory);
    }
    else {
        factory = new Ext.Factory(type);

        if (config) {
            if (config.constructor === Object) {
                Ext.apply(factory, config);

                if (typeof(defaultClass = factory.xclass) === 'string') {
                    factory.defaultClass = Ext.ClassManager.get(defaultClass);
                }
            }
            else {
                factory.defaultType = config;
            }
        }

        /*
         *  layout = Ext.Factory.layout('hbox');
         */
        Factory[factory.name] = fn = function(config, defaultType) {
            // maintain indirection through "create" name on instance to allow
            // the hook() mechanism to replace it.
            return factory.create(config, defaultType);
        };

        if (cacheable) {
            factory.instanceCache = {};

            factory.hook(function(original, config, defaultType) {
                var cache = this.instanceCache,
                    v;

                if (typeof config === 'string' && !(v = cache[config])) {
                    v = original.call(this, config, defaultType);

                    // Validator may have cacheable:false to force new instances each time,
                    // avoiding the cache
                    if (v.cacheable !== false) {
                        cache[config] = v;
                        //<debug>
                        // this should catch some improper modifications to the shared
                        // cached instance, during development but not in production.
                        Ext.Object.freeze(v);
                        //</debug>
                    }
                }

                return v;
            });
        }

        fn.instance = factory;

        /*
         * Typically called by an applier:
         *
         *      applyLayout: function (layout, oldLayout) {
         *          return Ext.Factory.layout.update(oldLayout, layout, this);
         *      },
         *
         *      createLayout: function (config) {
         *          return Ext.apply({
         *              //.. stuff
         *          }, config);
         *      }
         */
        fn.update = function(instance, config, creator, creatorMethod, defaultsConfig) {
            return factory.update(instance, config, creator, creatorMethod, defaultsConfig);
        };
    }

    return fn;
};

Ext.Factory.clearCaches = function() {
    var Factory = Ext.Factory,
        key, item;

    for (key in Factory) {
        item = Factory[key];
        item = item.instance;

        if (item) {
            item.clearCache();
        }
    }
};

Ext.Factory.on = function(name, fn) {
    Ext.Factory[name].instance.hook(fn);
};

/**
 * This mixin automates use of `Ext.Factory`. When mixed in to a class, the `alias` of the
 * class is retrieved and combined with an optional `factoryConfig` property on that class
 * to produce the configuration to pass to `Ext.Factory`.
 *
 * The factory method created by `Ext.Factory` is also added as a static method to the
 * target class.
 *
 * Given a class declared like so:
 *
 *      Ext.define('App.bar.Thing', {
 *          mixins: [
 *              'Ext.mixin.Factoryable'
 *          ],
 *
 *          alias: 'bar.thing',  // this is detected by Factoryable
 *
 *          factoryConfig: {
 *              defaultType: 'thing',  // this is the default deduced from the alias
 *              // other configs
 *          },
 *
 *          ...
 *      });
 *
 * The produced factory function can be used to create instances using the following
 * forms:
 *
 *      var obj;
 *
 *      obj = App.bar.Thing.create('thing'); // same as "new App.bar.Thing()"
 *
 *      obj = App.bar.Thing.create({
 *          type: 'thing'       // same as above
 *      });
 *
 *      obj = App.bar.Thing.create({
 *          xclass: 'App.bar.Thing'  // same as above
 *      });
 *
 *      var obj2 = App.bar.Thing.create(obj);
 *      // obj === obj2  (passing an instance returns the instance)
 *
 * Alternatively the produced factory is available as a static method of `Ext.Factory`.
 *
 * @since 5.0.0
 */
Ext.define('Ext.mixin.Factoryable', {
    mixinId: 'factoryable',

    onClassMixedIn: function(targetClass) {
        var proto = targetClass.prototype,
            factoryConfig = proto.factoryConfig,
            alias = proto.alias,
            config = {},
            dot, createFn;

        alias = alias && alias.length && alias[0];

        if (alias && (dot = alias.lastIndexOf('.')) > 0) {
            config.type = alias.substring(0, dot);
            config.defaultType = alias.substring(dot + 1);
        }

        if (factoryConfig) {
            delete proto.factoryConfig;
            Ext.apply(config, factoryConfig);
        }

        createFn = Ext.Factory.define(config.type, config);

        if (targetClass.create === Ext.Base.create) {
            // allow targetClass to override the create method
            targetClass.create = createFn;
        }
    }

    /**
     * @property {Object} [factoryConfig]
     * If this property is specified by the target class of this mixin its properties are
     * used to configure the created `Ext.Factory`.
     */
});
